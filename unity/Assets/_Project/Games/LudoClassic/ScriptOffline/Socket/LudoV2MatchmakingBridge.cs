using System;
using System.Collections.Generic;
using System.IO;
using Best.HTTP;
using Best.SocketIO;
using Best.SocketIO.Events;
using Newtonsoft.Json;
using UnityEngine;

namespace LudoClassicOffline
{
    public class LudoV2MatchmakingBridge : MonoBehaviour
    {
        private void LateUpdate()
        {
        }
        public static LudoV2MatchmakingBridge Instance { get; private set; }

        public DashBoardManagerOffline dashBoardManager;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;

        private SocketManager socketManager;
        private Socket namespaceSocket;
        private bool isQueueing;
        private bool hasStartedMatch;
        private bool hasEnteredWaitingBoard;
        private bool isIntentionalDisconnect;
        private bool hasReportedMatchCompletion;
        private bool isTournamentMode;
        private bool isClaimingNextTournamentRound;
        private const float NextTournamentClaimDelaySeconds = 0.5f;
        private string activeTournamentUuid;
        private string activeTournamentEntryUuid;
        private LudoV2QueueJoinEnvelope queuedRoom;
        private LudoV2RoomSnapshot latestSnapshot;
        private int lastAnnouncedSeatCount;

        private void Awake()
        {
            if (Instance == null)
            {
                Instance = this;
            }

            if (dashBoardManager == null)
            {
                dashBoardManager = GetComponent<DashBoardManagerOffline>();
            }

            if (socketNumberEventReceiver == null)
            {
                socketNumberEventReceiver = FindObjectOfType<SocketNumberEventReceiverOffline>();
            }
        }

        private void OnDestroy()
        {
            if (Instance == this)
            {
                Instance = null;
            }
        }

        private void OnDisable()
        {
            if (!isIntentionalDisconnect)
            {
                DisconnectSocket();
            }
        }

        public bool TryStartMatchmaking(int entryFee, int maxPlayers)
        {
            if (!Configuration.IsLudoV2Enabled())
            {
                return false;
            }

            if (isQueueing || hasStartedMatch)
            {
                CommonUtil.ShowToast("Ludo matchmaking already in progress");
                return true;
            }

            if (string.IsNullOrWhiteSpace(Configuration.GetId()) || string.IsNullOrWhiteSpace(Configuration.GetToken()))
            {
                CommonUtil.ShowToast("Please login again");
                return false;
            }

            if (dashBoardManager == null || socketNumberEventReceiver == null)
            {
                Debug.LogWarning("LudoV2MatchmakingBridge missing scene references.");
                return false;
            }

            isQueueing = true;
            hasStartedMatch = false;
            hasEnteredWaitingBoard = false;
            hasReportedMatchCompletion = false;
            isTournamentMode = false;
            activeTournamentUuid = null;
            activeTournamentEntryUuid = null;
            lastAnnouncedSeatCount = 0;
            latestSnapshot = null;
            dashBoardManager.backButton.SetActive(false);
            dashBoardManager.lobbySelectPanal.SetActive(false);

            QueueAndConnectAsync(entryFee, maxPlayers);
            return true;
        }

        public bool TryStartTournamentMatchmaking(string tournamentUuid, string tournamentEntryUuid)
        {
            if (!Configuration.IsLudoV2Enabled())
            {
                return false;
            }

            if (isQueueing || hasStartedMatch)
            {
                CommonUtil.ShowToast("Ludo matchmaking already in progress");
                return true;
            }

            if (string.IsNullOrWhiteSpace(Configuration.GetId()) || string.IsNullOrWhiteSpace(Configuration.GetToken()))
            {
                CommonUtil.ShowToast("Please login again");
                return false;
            }

            if (string.IsNullOrWhiteSpace(tournamentUuid) || string.IsNullOrWhiteSpace(tournamentEntryUuid))
            {
                CommonUtil.ShowToast("Tournament room details are missing");
                return false;
            }

            if (dashBoardManager == null || socketNumberEventReceiver == null)
            {
                Debug.LogWarning("LudoV2MatchmakingBridge missing scene references.");
                return false;
            }

            isQueueing = true;
            hasStartedMatch = false;
            hasEnteredWaitingBoard = false;
            hasReportedMatchCompletion = false;
            isTournamentMode = true;
            activeTournamentUuid = tournamentUuid;
            activeTournamentEntryUuid = tournamentEntryUuid;
            lastAnnouncedSeatCount = 0;
            latestSnapshot = null;
            dashBoardManager.backButton.SetActive(false);
            dashBoardManager.lobbySelectPanal.SetActive(false);

            ConnectTournamentSocket();
            return true;
        }

        private async void QueueAndConnectAsync(int entryFee, int maxPlayers)
        {
            string playMode = entryFee > 0 ? "cash" : "practice";
            string gameMode = ResolveGameMode();

            var request = HTTPRequest.CreatePost(Configuration.LudoV2QueueJoinUrl);
            request.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            request.SetHeader("Accept", "application/json");
            request.SetHeader("Content-Type", "application/json");
            request.UploadSettings.UploadStream = new MemoryStream(
                System.Text.Encoding.UTF8.GetBytes(
                    JsonConvert.SerializeObject(new
                    {
                        room_type = "public",
                        play_mode = playMode,
                        game_mode = gameMode,
                        max_players = maxPlayers,
                        entry_fee = entryFee,
                        allow_bots = true,
                    })
                )
            );

            try
            {
                var response = await request.GetHTTPResponseAsync();
                if (!response.IsSuccess)
                {
                    FailMatchmaking("Ludo queue request failed");
                    return;
                }

                queuedRoom = JsonConvert.DeserializeObject<LudoV2QueueJoinEnvelope>(response.DataAsText);
                if (queuedRoom == null || !queuedRoom.success || queuedRoom.data == null)
                {
                    FailMatchmaking(queuedRoom?.message ?? "Unable to join Ludo room");
                    return;
                }

                ConnectSocket(maxPlayers, entryFee, gameMode);
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Ludo v2 queue error: " + ex.Message);
                FailMatchmaking("Unable to connect to Ludo queue");
            }
        }

        private void ConnectSocket(int maxPlayers, int entryFee, string gameMode)
        {
            DisconnectSocket();

            string socketUrl = Configuration.BaseSocketUrl + "/socket.io/";
            socketManager = new SocketManager(new Uri(socketUrl), BuildSocketOptions());
            namespaceSocket = socketManager.GetSocket(Configuration.LudoV2SocketNamespace);
            namespaceSocket.On(SocketIOEventTypes.Connect, () => OnSocketConnected(maxPlayers, entryFee, gameMode));
            namespaceSocket.On(SocketIOEventTypes.Disconnect, OnSocketDisconnected);
            namespaceSocket.On<Error>(SocketIOEventTypes.Error, OnSocketError);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.room.waiting", OnRoomWaiting);
            namespaceSocket.On<LudoV2RoomStarting>("ludo.room.starting", OnRoomStarting);
            namespaceSocket.On<LudoV2BotJoined>("ludo.room.bot_joined", OnBotJoined);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.game.snapshot", OnRoomSnapshot);
            namespaceSocket.On<LudoV2MatchResult>("ludo.game.result", OnMatchResult);
            namespaceSocket.On<LudoV2ErrorPayload>("ludo.error", OnSocketPayloadError);
        }

        private void ConnectTournamentSocket()
        {
            DisconnectSocket();

            string socketUrl = Configuration.BaseSocketUrl + "/socket.io/";
            socketManager = new SocketManager(new Uri(socketUrl), BuildSocketOptions());
            namespaceSocket = socketManager.GetSocket(Configuration.LudoV2SocketNamespace);
            namespaceSocket.On(SocketIOEventTypes.Connect, OnTournamentSocketConnected);
            namespaceSocket.On(SocketIOEventTypes.Disconnect, OnSocketDisconnected);
            namespaceSocket.On<Error>(SocketIOEventTypes.Error, OnSocketError);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.tournament.room_claimed", OnRoomWaiting);
            namespaceSocket.On<LudoV2RoomStarting>("ludo.room.starting", OnRoomStarting);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.game.snapshot", OnRoomSnapshot);
            namespaceSocket.On<LudoV2MatchResult>("ludo.game.result", OnMatchResult);
            namespaceSocket.On<LudoV2ErrorPayload>("ludo.error", OnSocketPayloadError);
            namespaceSocket.On<LudoV2ErrorPayload>("ludo.tournament.room_claim_failed", OnSocketPayloadError);
        }

        private SocketOptions BuildSocketOptions()
        {
            SocketOptions options = new SocketOptions();
            options.Reconnection = false;
            options.AutoConnect = true;
            options.Timeout = TimeSpan.FromMilliseconds(10000);
            return options;
        }

        private void OnSocketConnected(int maxPlayers, int entryFee, string gameMode)
        {
            namespaceSocket.Emit(
                "ludo.queue.join",
                JsonConvert.SerializeObject(new
                {
                    userId = int.Parse(Configuration.GetId()),
                    displayName = Configuration.GetName(),
                    roomUuid = queuedRoom.data.room_uuid,
                    roomType = "public",
                    playMode = entryFee > 0 ? "cash" : "practice",
                    gameMode = gameMode,
                    maxPlayers = maxPlayers,
                    entryFee = entryFee,
                    allowBots = true,
                })
            );
        }

        private void OnTournamentSocketConnected()
        {
            namespaceSocket.Emit(
                "ludo.tournament.claim_room",
                JsonConvert.SerializeObject(new
                {
                    userId = int.Parse(Configuration.GetId()),
                    accessToken = Configuration.GetToken(),
                    tournamentUuid = activeTournamentUuid,
                    tournamentEntryUuid = activeTournamentEntryUuid,
                })
            );
        }

        private void OnRoomWaiting(LudoV2RoomSnapshot snapshot)
        {
            latestSnapshot = snapshot;
            isClaimingNextTournamentRound = false;
            EnsureWaitingBoardVisible(snapshot);
            RenderSeatsFromSnapshot(snapshot);
            UpdateWaitingBoardMessage(snapshot);
            Debug.Log("Ludo v2 waiting room: " + JsonConvert.SerializeObject(snapshot));
        }

        private void OnBotJoined(LudoV2BotJoined payload)
        {
            if (latestSnapshot != null && latestSnapshot.seats != null && payload?.seat != null)
            {
                latestSnapshot.seats.Add(payload.seat);
                latestSnapshot.current_players = latestSnapshot.seats.Count;
                latestSnapshot.bot_players += 1;
                RenderSeatsFromSnapshot(latestSnapshot);
                UpdateWaitingBoardMessage(latestSnapshot);
            }
        }

        private void OnRoomSnapshot(LudoV2RoomSnapshot snapshot)
        {
            latestSnapshot = snapshot;
            EnsureWaitingBoardVisible(snapshot);
            RenderSeatsFromSnapshot(snapshot);
            UpdateWaitingBoardMessage(snapshot);
            Debug.Log("Ludo v2 snapshot: " + JsonConvert.SerializeObject(snapshot));
        }

        private void OnRoomStarting(LudoV2RoomStarting payload)
        {
            if (hasStartedMatch)
            {
                return;
            }

            isClaimingNextTournamentRound = false;
            hasStartedMatch = true;
            isQueueing = false;
            hasReportedMatchCompletion = false;
            HideWaitingBoardMessage();
            BootstrapExistingOfflineFlow(payload);
        }

        private void BootstrapExistingOfflineFlow(LudoV2RoomStarting payload)
        {
            int maxPlayers = payload.seats != null && payload.seats.Count >= 4 ? 4 : 2;
            string roomId = string.IsNullOrWhiteSpace(payload.room_id) ? queuedRoom?.data?.room_uuid : payload.room_id;

            latestSnapshot = new LudoV2RoomSnapshot
            {
                room_id = roomId,
                state = "starting",
                max_players = maxPlayers,
                current_players = payload.seats?.Count ?? maxPlayers,
                real_players = payload.seats?.FindAll(seat => seat.playerType == "human").Count ?? 1,
                bot_players = payload.seats?.FindAll(seat => seat.playerType == "bot").Count ?? 0,
                seats = payload.seats ?? new List<LudoV2SeatData>(),
            };

            EnsureWaitingBoardVisible(latestSnapshot);
            RenderSeatsFromSnapshot(latestSnapshot);
            socketNumberEventReceiver.maxPlayer = maxPlayers;
            socketNumberEventReceiver.ChangeCukiSeatIndex();
            socketNumberEventReceiver.ludoNumberGsNew.GameTimerStart(5);
        }

        private void EnsureWaitingBoardVisible(LudoV2RoomSnapshot snapshot)
        {
            if (hasEnteredWaitingBoard)
            {
                return;
            }

            hasEnteredWaitingBoard = true;
            dashBoardManager.fTUEPanal.SetActive(false);
            dashBoardManager.fTUEManager.SetActive(false);
            dashBoardManager.selectGameModePanal.SetActive(false);
            dashBoardManager.lobbySelectPanal.SetActive(false);
            dashBoardManager.onlineLobbySelectionPanel.SetActive(false);
            dashBoardManager.backButton.SetActive(false);
            Canvas dashboardCanvas = dashBoardManager.dashBordPanal != null
                ? dashBoardManager.dashBordPanal.GetComponent<Canvas>()
                : null;
            if (dashboardCanvas != null)
            {
                dashboardCanvas.enabled = false;
            }
            socketNumberEventReceiver.ludoNumberGsNew.board.SetActive(true);
            socketNumberEventReceiver.ludoNumberGsNew.winPanel.SetActive(false);
            if (socketNumberEventReceiver.ludoNumberGsNew.ludoNumberUiManager != null)
            {
                socketNumberEventReceiver.ludoNumberGsNew.ludoNumberUiManager.timerCountScreen.SetActive(false);
                socketNumberEventReceiver.ludoNumberGsNew.ludoNumberUiManager.startPanel.SetActive(false);
            }
            socketNumberEventReceiver.ludoNumbersAcknowledgementHandler.ludoNumberTostMessage.ShowToastMessages(
                ToastMessage.WAITFORPLAYER
            );
            UpdateWaitingBoardMessage(snapshot);
        }

        private void RenderSeatsFromSnapshot(LudoV2RoomSnapshot snapshot)
        {
            if (snapshot == null || snapshot.seats == null)
            {
                return;
            }

            socketNumberEventReceiver.joinTableResponse = BuildJoinTableResponse(snapshot);
            socketNumberEventReceiver.signUpResponce = BuildSignUpResponse(snapshot);
            socketNumberEventReceiver.userTurnStart = BuildUserTurnStart();
            ResetVisibleSeatWidgets();
            socketNumberEventReceiver.ludoNumbersAcknowledgementHandler.JoinTableAcknowledged();
        }

        private JoinTableResponse BuildJoinTableResponse(LudoV2RoomSnapshot snapshot)
        {
            int localSeatIndex = GetLocalSeatIndex(snapshot);
            List<PlayerInfoData> playerInfo = new List<PlayerInfoData>();
            foreach (var seat in snapshot.seats)
            {
                playerInfo.Add(new PlayerInfoData
                {
                    playerSeatIndex = Mathf.Max(0, seat.seatNo - 1),
                    userId = seat.userId?.ToString() ?? seat.botCode ?? string.Empty,
                    username = seat.displayName ?? ("Seat " + seat.seatNo),
                    userProfile = string.Empty,
                });
            }

            return new JoinTableResponse
            {
                data = new JoinTableResponseData
                {
                    maxPlayerCount = snapshot.max_players,
                    tableId = snapshot.room_id,
                    queueKey = snapshot.room_id,
                    playerInfo = playerInfo,
                    playerMoves = new List<int>(),
                    thisPlayerSeatIndex = localSeatIndex,
                    turnTimer = 15,
                    extraTimer = 5,
                },
                metrics = new JoinTableMetrics
                {
                    tableId = snapshot.room_id,
                    userId = Configuration.GetId(),
                },
                userId = Configuration.GetId(),
                tableId = snapshot.room_id,
            };
        }

        private SignUpResponceClass.SignUpResponce BuildSignUpResponse(LudoV2RoomSnapshot snapshot)
        {
            int localSeatIndex = GetLocalSeatIndex(snapshot);
            List<SignUpResponceClass.PlayerInfo> players = new List<SignUpResponceClass.PlayerInfo>();

            foreach (var seat in snapshot.seats)
            {
                players.Add(new SignUpResponceClass.PlayerInfo
                {
                    seatIndex = Mathf.Max(0, seat.seatNo - 1),
                    userId = seat.userId?.ToString() ?? seat.botCode ?? string.Empty,
                    username = seat.displayName ?? ("Seat " + seat.seatNo),
                    avatar = string.Empty,
                    tokenDetails = new List<int> { 0, 0, 0, 0 },
                    score = 0,
                    missedTurnCount = 0,
                    highestToken = 0,
                    remainingTimer = 15,
                });
            }

            return new SignUpResponceClass.SignUpResponce
            {
                userId = Configuration.GetId(),
                tableId = snapshot.room_id,
                data = new SignUpResponceClass.SignUpResponceData
                {
                    isAbleToReconnect = false,
                    roomName = snapshot.room_id,
                    activePlayer = snapshot.current_players,
                    numberOfPlayers = snapshot.max_players,
                    tableState = snapshot.state == "starting" ? "PLAYING" : "WAITING_FOR_PLAYERS",
                    leftPlayerInfo = new List<object>(),
                    playerInfo = players,
                    movesLeft = 24,
                    thisPlayerSeatIndex = localSeatIndex,
                    playerMoves = new List<int>(),
                    userTurnDetails = new SignUpResponceClass.UserTurnDetails
                    {
                        currentTurnSeatIndex = localSeatIndex,
                        diceValue = 1,
                        isExtraTurn = false,
                        isExtraTime = false,
                        isDiceAnimated = false,
                        remainingTimer = 15,
                    },
                    turnTimer = 15,
                    extraTimer = 5,
                    gameTimer = 5,
                    mainGameTimer = 0,
                    isSix = false,
                    userTurnCount = 0,
                },
                metrics = new SignUpResponceClass.Metrics
                {
                    userId = Configuration.GetId(),
                    tableId = snapshot.room_id,
                },
            };
        }

        private UserTimeStart BuildUserTurnStart()
        {
            int localSeatIndex = GetLocalSeatIndex(latestSnapshot);
            return new UserTimeStart
            {
                data = new UserTimeStartData
                {
                    startTurnSeatIndex = localSeatIndex,
                    diceValue = 1,
                    isExtraTurn = false,
                    movesLeft = 24,
                    tokenPosition = new List<TokenPosition>(),
                    userTurnCount = 0,
                },
            };
        }

        private void ResetVisibleSeatWidgets()
        {
            foreach (var playerControl in socketNumberEventReceiver.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl)
            {
                if (playerControl == null)
                {
                    continue;
                }

                playerControl.gameObject.SetActive(false);
                playerControl.ludoNumbersUserData.leaveTableImage.SetActive(true);
                playerControl.ludoNumbersUserData.userNameText.text = string.Empty;
            }
        }

        private string ResolveGameMode()
        {
            if (MGPSDK.MGPGameManager.instance != null && MGPSDK.MGPGameManager.instance.sdkConfig != null)
            {
                return MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName;
            }

            return "CLASSIC";
        }

        private int GetLocalSeatIndex(LudoV2RoomSnapshot snapshot)
        {
            if (snapshot?.seats == null || snapshot.seats.Count == 0)
            {
                return 0;
            }

            if (!int.TryParse(Configuration.GetId(), out int localUserId))
            {
                return 0;
            }

            LudoV2SeatData localSeat = snapshot.seats.Find(seat => seat != null && seat.userId.HasValue && seat.userId.Value == localUserId);
            return localSeat != null ? Mathf.Max(0, localSeat.seatNo - 1) : 0;
        }

        private void OnSocketPayloadError(LudoV2ErrorPayload payload)
        {
            FailMatchmaking(payload?.message ?? "Ludo room error");
        }

        private void OnMatchResult(LudoV2MatchResult payload)
        {
            if (!isTournamentMode || payload == null)
            {
                return;
            }

            bool tournamentStillRunning = string.Equals(
                payload.settlement?.data?.status,
                "running",
                StringComparison.OrdinalIgnoreCase
            );

            if (!tournamentStillRunning)
            {
                return;
            }

            if (!IsLocalWinner(payload) || isClaimingNextTournamentRound)
            {
                return;
            }

            isClaimingNextTournamentRound = true;
            hasStartedMatch = false;
            hasReportedMatchCompletion = false;
            latestSnapshot = null;

            if (socketNumberEventReceiver?.ludoNumberGsNew != null)
            {
                socketNumberEventReceiver.ludoNumberGsNew.winPanel.SetActive(false);
                socketNumberEventReceiver.ludoNumberGsNew.board.SetActive(true);
            }

            CommonUtil.ShowToast("Qualified for next round");

            if (namespaceSocket != null && namespaceSocket.IsOpen)
            {
                namespaceSocket.Emit("ludo.room.leave");
                CancelInvoke(nameof(ClaimNextTournamentRound));
                Invoke(nameof(ClaimNextTournamentRound), NextTournamentClaimDelaySeconds);
            }
        }

        private void ClaimNextTournamentRound()
        {
            if (!isTournamentMode || namespaceSocket == null || !namespaceSocket.IsOpen)
            {
                isClaimingNextTournamentRound = false;
                return;
            }

            OnTournamentSocketConnected();
        }

        private void OnSocketDisconnected()
        {
            if (isIntentionalDisconnect)
            {
                return;
            }

            if (!hasStartedMatch && isQueueing)
            {
                FailMatchmaking("Ludo room disconnected");
            }
        }

        private void OnSocketError(Error error)
        {
            if (!hasStartedMatch)
            {
                FailMatchmaking(error?.message ?? "Ludo socket error");
            }
        }

        private void FailMatchmaking(string message)
        {
            Debug.LogWarning("Ludo v2 matchmaking failed: " + message);
            isQueueing = false;
            hasStartedMatch = false;
            isClaimingNextTournamentRound = false;
            CancelInvoke(nameof(ClaimNextTournamentRound));
            HideWaitingBoardMessage();
            Canvas dashboardCanvas = dashBoardManager.dashBordPanal != null
                ? dashBoardManager.dashBordPanal.GetComponent<Canvas>()
                : null;
            if (dashboardCanvas != null)
            {
                dashboardCanvas.enabled = true;
            }
            dashBoardManager.lobbySelectPanal.SetActive(true);
            dashBoardManager.backButton.SetActive(true);
            CommonUtil.ShowToast(message);
            DisconnectSocket();
        }

        private void DisconnectSocket()
        {
            if (socketManager == null)
            {
                return;
            }

            isIntentionalDisconnect = true;
            CancelInvoke(nameof(ClaimNextTournamentRound));
            if (namespaceSocket != null && namespaceSocket.IsOpen)
            {
                namespaceSocket.Disconnect();
            }

            namespaceSocket = null;
            socketManager = null;
            isIntentionalDisconnect = false;
        }

        private bool IsLocalWinner(LudoV2MatchResult payload)
        {
            if (!int.TryParse(Configuration.GetId(), out int localUserId))
            {
                return false;
            }

            if (payload.winner != null && payload.winner.user_id.HasValue)
            {
                return payload.winner.user_id.Value == localUserId;
            }

            if (payload.winner != null && latestSnapshot?.seats != null)
            {
                int winningSeatNo = payload.winner.seat_no;
                int localSeatIndex = GetLocalSeatIndex(latestSnapshot);
                return winningSeatNo == localSeatIndex + 1;
            }

            return false;
        }

        public void ReportMatchCompleted(BattleFinishData battleFinishData)
        {
            if (!Configuration.IsLudoV2Enabled() || !hasStartedMatch || hasReportedMatchCompletion)
            {
                return;
            }

            if (namespaceSocket == null || !namespaceSocket.IsOpen || latestSnapshot?.seats == null)
            {
                Debug.Log("Skipping Ludo v2 settlement emit because socket/snapshot is unavailable.");
                return;
            }

            if (battleFinishData?.payload?.players == null || battleFinishData.payload.players.Count == 0)
            {
                Debug.Log("Skipping Ludo v2 settlement emit because battle finish data is empty.");
                return;
            }

            List<object> placements = new List<object>();
            object winnerPayload = null;

            foreach (AvtarData player in battleFinishData.payload.players)
            {
                if (player == null)
                {
                    continue;
                }

                int seatNo = player.seatIndex + 1;
                LudoV2SeatData seat = latestSnapshot.seats.Find(item => item != null && item.seatNo == seatNo);
                bool isWinner = string.Equals(player.winType, "win", StringComparison.OrdinalIgnoreCase)
                    || string.Equals(player.winType, "tie", StringComparison.OrdinalIgnoreCase);
                int finishPosition = isWinner ? 1 : 2;

                placements.Add(new
                {
                    seat_no = seatNo,
                    finish_position = finishPosition,
                    score = player.score,
                    is_winner = isWinner,
                    payout_amount = player.winAmount,
                    stats = new
                    {
                        win_type = player.winType ?? string.Empty,
                        username = seat?.displayName ?? player.username ?? string.Empty,
                        player_type = seat?.playerType ?? "unknown",
                        bot_code = seat?.botCode,
                    },
                });

                if (winnerPayload == null && isWinner)
                {
                    winnerPayload = new
                    {
                        seat_no = seatNo,
                        user_id = seat?.userId,
                    };
                }
            }

            string payload = JsonConvert.SerializeObject(new
            {
                tournament_uuid = isTournamentMode ? activeTournamentUuid : null,
                tournament_entry_uuid = isTournamentMode ? activeTournamentEntryUuid : null,
                winner = winnerPayload,
                placements,
            });

            HideWaitingBoardMessage();
            namespaceSocket.Emit(isTournamentMode ? "ludo.tournament.match_complete" : "ludo.match.complete", payload);
            hasReportedMatchCompletion = true;
            Debug.Log("Ludo v2 match completion emitted: " + payload);
        }

        private void UpdateWaitingBoardMessage(LudoV2RoomSnapshot snapshot)
        {
            if (socketNumberEventReceiver?.ludoNumbersAcknowledgementHandler?.ludoNumberTostMessage == null || snapshot == null)
            {
                return;
            }

            int occupiedSeats = snapshot.seats?.Count ?? snapshot.current_players;
            int remainingSeats = Mathf.Max(0, snapshot.max_players - occupiedSeats);
            string message = remainingSeats > 0
                ? $"Waiting for {remainingSeats} more player{(remainingSeats == 1 ? "" : "s")}..."
                : "Starting match...";

            socketNumberEventReceiver.ludoNumbersAcknowledgementHandler.ludoNumberTostMessage.SetWaitingMessage(message);

            if (occupiedSeats != lastAnnouncedSeatCount)
            {
                lastAnnouncedSeatCount = occupiedSeats;
            }
        }

        private void HideWaitingBoardMessage()
        {
            if (socketNumberEventReceiver?.ludoNumbersAcknowledgementHandler?.ludoNumberTostMessage == null)
            {
                return;
            }

            socketNumberEventReceiver.ludoNumbersAcknowledgementHandler.ludoNumberTostMessage.HideWaitForPlayerToast(true);
        }
    }

    [Serializable]
    public class LudoV2QueueJoinEnvelope
    {
        public bool success;
        public string message;
        public LudoV2QueueRoomData data;
    }

    [Serializable]
    public class LudoV2QueueRoomData
    {
        public string room_uuid;
        public string status;
        public int max_players;
        public int current_players;
        public int current_real_players;
        public int current_bot_players;
    }

    [Serializable]
    public class LudoV2RoomSnapshot
    {
        public string room_id;
        public string state;
        public int max_players;
        public int current_players;
        public int real_players;
        public int bot_players;
        public string fill_bots_at;
        public List<LudoV2SeatData> seats;
    }

    [Serializable]
    public class LudoV2RoomStarting
    {
        public string room_id;
        public bool started_with_bots;
        public List<LudoV2SeatData> seats;
    }

    [Serializable]
    public class LudoV2BotJoined
    {
        public string room_id;
        public LudoV2SeatData seat;
    }

    [Serializable]
    public class LudoV2SeatData
    {
        public int seatNo;
        public int? userId;
        public string playerType;
        public string displayName;
        public string botCode;
        public bool isConnected;
        public bool isReady;
    }

    [Serializable]
    public class LudoV2ErrorPayload
    {
        public string message;
    }

    [Serializable]
    public class LudoV2MatchResult
    {
        public string room_id;
        public LudoV2WinnerPayload winner;
        public LudoV2SettlementPayload settlement;
    }

    [Serializable]
    public class LudoV2WinnerPayload
    {
        public int seat_no;
        public int? user_id;
    }

    [Serializable]
    public class LudoV2SettlementPayload
    {
        public bool success;
        public string message;
        public LudoV2TournamentSettlementData data;
    }

    [Serializable]
    public class LudoV2TournamentSettlementData
    {
        public string uuid;
        public string status;
        public int current_active_entries;
    }
}
