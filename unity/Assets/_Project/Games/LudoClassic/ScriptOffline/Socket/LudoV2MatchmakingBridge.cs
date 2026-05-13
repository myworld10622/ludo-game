using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using Best.HTTP;
using Best.SocketIO;
using Best.SocketIO.Events;
using Newtonsoft.Json;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoV2MatchmakingBridge : MonoBehaviour
    {
        private void LateUpdate()
        {
#if UNITY_EDITOR
            TickLocalMatchmakingPreview();
#endif
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
        private bool isPrivateTableMode;
        private string privateTableCode;
        private bool isClaimingNextTournamentRound;
        private bool isServerDrivenGameMode;
        private bool isReconnecting;
        private const float ReconnectRetryDelay = 2f;
        private const int   MaxReconnectAttempts = 5;
        private int         reconnectAttempts;
        // Anti-cheat nonce chain — echoed back to server to prevent replay attacks
        private string currentTurnNonce;
        private string currentRollNonce;
        public bool IsServerDrivenGameMode => isServerDrivenGameMode;
        // Server seat index of the local player (0-based). Used for ego-view remapping.
        private int localSeatOffset;
        // Set to true once the server sends ludo.game.my_seat — prevents snapshot renders from overriding the authoritative offset.
        private bool hasMySeatFromServer;
        // No ego-view rotation — server seat = visual seat (same board for all players)
        private int ToVisualSeat(int serverSeat, int maxPlayers)
            => serverSeat;
        private const float NextTournamentClaimDelaySeconds = 0.5f;
        private string activeTournamentUuid;
        private string activeTournamentEntryUuid;
        private LudoV2QueueJoinEnvelope queuedRoom;
        private LudoV2RoomSnapshot latestSnapshot;
        private int lastAnnouncedSeatCount;
        private LudoRoomChatController roomChatController;
        private AgoraVoiceManager agoraVoiceManager;
        private LudoFriendPanelController friendPanelController;
        private const string DefaultSeatAvatarUrl =
            "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
        private const float LocalPreviewBotJoinDelaySeconds = 8f;
#if UNITY_EDITOR
        private bool localPreviewActive;
        private int localPreviewMaxPlayers;
        private float localPreviewNextBotAt;
        private LudoV2RoomSnapshot localPreviewSnapshot;
#endif

        public static event Action<LudoV2ChatMessagePayload> OnChatMessageReceived;
        public static event Action<List<LudoV2ChatMessagePayload>> OnChatHistoryReceived;

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

            roomChatController = GetComponent<LudoRoomChatController>();
            if (roomChatController != null)
            {
                if (dashBoardManager != null)
                {
                    roomChatController.manualRootCanvas = dashBoardManager.ludoChatCanvas;
                }
                roomChatController.SetChatAvailability(false);
                roomChatController.enabled = false;
            }

            agoraVoiceManager = GetComponent<AgoraVoiceManager>();
            if (agoraVoiceManager == null)
            {
                agoraVoiceManager = gameObject.AddComponent<AgoraVoiceManager>();
            }
            agoraVoiceManager.SetVoiceAvailability(false);

            friendPanelController = GetComponent<LudoFriendPanelController>();
            if (friendPanelController == null)
            {
                friendPanelController = gameObject.AddComponent<LudoFriendPanelController>();
            }
            friendPanelController.SetRoomActionAvailability(false);
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
            isPrivateTableMode = false;
            activeTournamentUuid = null;
            activeTournamentEntryUuid = null;
            lastAnnouncedSeatCount = 0;
            latestSnapshot = null;
            dashBoardManager.backButton.SetActive(false);
            dashBoardManager.lobbySelectPanal.SetActive(false);
            dashBoardManager.SetLobbyUiBlocking(false);

#if UNITY_EDITOR
            if (ShouldUseLocalMatchmakingPreview())
            {
                StartLocalMatchmakingPreview(entryFee, maxPlayers);
                return true;
            }
#endif

            dashBoardManager.ShowMatchmakingLoader(true);
            QueueAndConnectAsync(entryFee, maxPlayers);
            return true;
        }

        public bool TryStartPrivateTableMatchmaking(int tableId, int maxPlayers, int entryFee, string code = null)
        {
            if (!Configuration.IsLudoV2Enabled())
            {
                return false;
            }

            // If already queueing/started for the SAME private table code, skip reconnect.
            // Otherwise (different code or re-enter), disconnect old socket and restart fresh.
            string incomingCode = code ?? tableId.ToString();
            if (isQueueing || hasStartedMatch)
            {
                if (string.Equals(privateTableCode, incomingCode, StringComparison.OrdinalIgnoreCase) && !hasStartedMatch)
                {
                    Debug.Log($"[LudoV2] Already queueing for {incomingCode} — skipping reconnect");
                    return true;
                }
                Debug.Log($"[LudoV2] New private table join ({incomingCode}), resetting from previous state");
                DisconnectSocket();
                hasStartedMatch = false;
                hasEnteredWaitingBoard = false;
                isQueueing = false;
                hasMySeatFromServer = false;
                localSeatOffset = 0;
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
            isPrivateTableMode = true;
            privateTableCode = code ?? tableId.ToString();
            activeTournamentUuid = null;
            activeTournamentEntryUuid = null;
            lastAnnouncedSeatCount = 0;
            latestSnapshot = null;
            queuedRoom = new LudoV2QueueJoinEnvelope
            {
                success = true,
                data = new LudoV2QueueRoomData { room_uuid = tableId.ToString() }
            };
            dashBoardManager.backButton.SetActive(false);
            dashBoardManager.lobbySelectPanal.SetActive(false);
            dashBoardManager.SetLobbyUiBlocking(false);

            ConnectSocket(maxPlayers, entryFee, ResolveGameMode());
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
            request.TimeoutSettings.ConnectTimeout = TimeSpan.FromSeconds(8);
            request.TimeoutSettings.Timeout = TimeSpan.FromSeconds(12);
            request.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            request.SetHeader("Accept", "application/json");
            request.SetHeader("Content-Type", "application/json");
            request.UploadSettings.UploadStream = new MemoryStream(
                System.Text.Encoding.UTF8.GetBytes(
                    JsonConvert.SerializeObject(new Dictionary<string, object>
                    {
                        { "room_type", "public" },
                        { "play_mode", playMode },
                        { "game_mode", gameMode },
                        { "max_players", maxPlayers },
                        { "entry_fee", entryFee },
                        { "allow_bots", true },
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

            string socketUrl = Configuration.BaseSocketUrl.TrimEnd('/') + "/socket.io/";
            socketManager = new SocketManager(new Uri(socketUrl), BuildSocketOptions());
            namespaceSocket = socketManager.GetSocket(Configuration.LudoV2SocketNamespace);
            namespaceSocket.On(SocketIOEventTypes.Connect, () => OnSocketConnected(maxPlayers, entryFee, gameMode));
            namespaceSocket.On(SocketIOEventTypes.Disconnect, OnSocketDisconnected);
            namespaceSocket.On<Error>(SocketIOEventTypes.Error, OnSocketError);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.room.waiting", OnRoomWaiting);
            namespaceSocket.On<LudoV2RoomStarting>("ludo.room.starting", OnRoomStarting);
            namespaceSocket.On<LudoV2BotJoined>("ludo.room.bot_joined", OnBotJoined);
            namespaceSocket.On<LudoV2ChatMessagePayload>("ludo.chat.message", OnChatMessage);
            namespaceSocket.On<LudoV2ChatHistoryPayload>("ludo.chat.history", OnChatHistory);
            namespaceSocket.On<LudoV2EmojiPayload>("ludo.chat.emoji", OnEmojiReceived);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.game.snapshot", OnRoomSnapshot);
            namespaceSocket.On<LudoV2MatchResult>("ludo.game.result", OnMatchResult);
            namespaceSocket.On<LudoV2ErrorPayload>("ludo.error", OnSocketPayloadError);
            namespaceSocket.On<LudoV2TurnStarted>("ludo.game.turn_started", OnTurnStarted);
            namespaceSocket.On<LudoV2DiceRolled>("ludo.game.dice_rolled", OnDiceRolled);
            namespaceSocket.On<LudoV2TokenMoved>("ludo.game.token_moved", OnTokenMoved);
            namespaceSocket.On<LudoV2TurnMissed>("ludo.game.turn_missed", OnTurnMissed);
            namespaceSocket.On<LudoV2GameState>("ludo.game.state", OnGameStateSync);
            namespaceSocket.On<LudoV2MySeat>("ludo.game.my_seat", OnMySeatAssigned);
        }

        private void ConnectTournamentSocket()
        {
            DisconnectSocket();

            string socketUrl = Configuration.BaseSocketUrl.TrimEnd('/') + "/socket.io/";
            socketManager = new SocketManager(new Uri(socketUrl), BuildSocketOptions());
            namespaceSocket = socketManager.GetSocket(Configuration.LudoV2SocketNamespace);
            namespaceSocket.On(SocketIOEventTypes.Connect, OnTournamentSocketConnected);
            namespaceSocket.On(SocketIOEventTypes.Disconnect, OnSocketDisconnected);
            namespaceSocket.On<Error>(SocketIOEventTypes.Error, OnSocketError);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.tournament.room_claimed", OnRoomWaiting);
            namespaceSocket.On<LudoV2RoomStarting>("ludo.room.starting", OnRoomStarting);
            namespaceSocket.On<LudoV2ChatMessagePayload>("ludo.chat.message", OnChatMessage);
            namespaceSocket.On<LudoV2ChatHistoryPayload>("ludo.chat.history", OnChatHistory);
            namespaceSocket.On<LudoV2EmojiPayload>("ludo.chat.emoji", OnEmojiReceived);
            namespaceSocket.On<LudoV2RoomSnapshot>("ludo.game.snapshot", OnRoomSnapshot);
            namespaceSocket.On<LudoV2MatchResult>("ludo.game.result", OnMatchResult);
            namespaceSocket.On<LudoV2ErrorPayload>("ludo.error", OnSocketPayloadError);
            namespaceSocket.On<LudoV2ErrorPayload>("ludo.tournament.room_claim_failed", OnSocketPayloadError);
            namespaceSocket.On<LudoV2TurnStarted>("ludo.game.turn_started", OnTurnStarted);
            namespaceSocket.On<LudoV2DiceRolled>("ludo.game.dice_rolled", OnDiceRolled);
            namespaceSocket.On<LudoV2TokenMoved>("ludo.game.token_moved", OnTokenMoved);
            namespaceSocket.On<LudoV2TurnMissed>("ludo.game.turn_missed", OnTurnMissed);
            namespaceSocket.On<LudoV2GameState>("ludo.game.state", OnGameStateSync);
            namespaceSocket.On<LudoV2MySeat>("ludo.game.my_seat", OnMySeatAssigned);
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
            Debug.Log($"[LudoV2] Socket connected — roomUuid={queuedRoom?.data?.room_uuid} private={isPrivateTableMode}");
            namespaceSocket.Emit(
                "ludo.queue.join",
                JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "userId", int.Parse(Configuration.GetId()) },
                    { "displayName", LudoDisplayNameUtility.LocalPlayerLabel() },
                    { "roomUuid", queuedRoom.data.room_uuid },
                    { "roomType", isPrivateTableMode ? "private" : "public" },
                    { "playMode", entryFee > 0 ? "cash" : "practice" },
                    { "gameMode", gameMode },
                    { "maxPlayers", maxPlayers },
                    { "entryFee", entryFee },
                    { "allowBots", !isPrivateTableMode },
                })
            );
        }

        private void OnTournamentSocketConnected()
        {
            namespaceSocket.Emit(
                "ludo.tournament.claim_room",
                JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "userId", int.Parse(Configuration.GetId()) },
                    { "accessToken", Configuration.GetToken() },
                    { "tournamentUuid", activeTournamentUuid },
                    { "tournamentEntryUuid", activeTournamentEntryUuid },
                })
            );
        }

        private void OnRoomWaiting(LudoV2RoomSnapshot snapshot)
        {
            latestSnapshot = snapshot;
            isClaimingNextTournamentRound = false;
            RunOnMainThread(() =>
            {
                EnsureWaitingBoardVisible(snapshot);
                RenderSeatsFromSnapshot(snapshot);
                UpdateWaitingBoardMessage(snapshot);
            });
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
            // If server says game is already in progress and we haven't bootstrapped yet,
            // treat this snapshot as a late-join start (ludo.room.starting was missed).
            bool isInProgress = string.Equals(snapshot.state, "in_progress", StringComparison.OrdinalIgnoreCase)
                             || string.Equals(snapshot.state, "starting", StringComparison.OrdinalIgnoreCase);
            if (isInProgress && !hasStartedMatch && snapshot.seats != null && snapshot.seats.Count > 0)
            {
                Debug.Log("[LudoV2] Snapshot late-join bootstrap — state=" + snapshot.state);
                isServerDrivenGameMode = snapshot.seats.TrueForAll(
                    s => string.Equals(s.playerType, "human", StringComparison.OrdinalIgnoreCase));
                // Always use server-driven for 2 real humans
                if (snapshot.real_players >= 2) isServerDrivenGameMode = true;
                var syntheticStart = new LudoV2RoomStarting
                {
                    room_id = snapshot.room_id,
                    started_with_bots = snapshot.bot_players > 0,
                    seats = snapshot.seats,
                };
                // Use BootstrapLateJoin for both states — sets hasStartedMatch=true immediately,
                // preventing double-bootstrap if ludo.room.starting event also arrives.
                RunOnMainThread(() => BootstrapLateJoin(syntheticStart));
                return;
            }
            RunOnMainThread(() =>
            {
                EnsureWaitingBoardVisible(snapshot);
                RenderSeatsFromSnapshot(snapshot);
                UpdateWaitingBoardMessage(snapshot);
            });
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
            RunOnMainThread(() =>
            {
                HideWaitingBoardMessage();
                BootstrapExistingOfflineFlow(payload);
            });
        }

        private void BootstrapExistingOfflineFlow(LudoV2RoomStarting payload)
        {
            int maxPlayers = payload.seats != null ? Mathf.Clamp(payload.seats.Count, 2, 4) : 2;
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

            bool allHumans = (payload.seats != null) &&
                payload.seats.Count > 0 &&
                payload.seats.TrueForAll(s => string.Equals(s.playerType, "human", StringComparison.OrdinalIgnoreCase));
            isServerDrivenGameMode = allHumans;

            // Ego-view: local player is always visual seat 0.
            localSeatOffset = GetLocalSeatIndex(latestSnapshot);
            Debug.Log($"[LudoV2] LocalSeatOffset={localSeatOffset} (server seatNo={localSeatOffset + 1})");

            EnsureWaitingBoardVisible(latestSnapshot);
            RenderSeatsFromSnapshot(latestSnapshot);
            socketNumberEventReceiver.maxPlayer = maxPlayers;
            socketNumberEventReceiver.ChangeCukiSeatIndex();
            socketNumberEventReceiver.isServerDrivenGameMode = isServerDrivenGameMode;
            socketNumberEventReceiver.ludoNumberGsNew.GameTimerStart(5);
            // In server-driven mode the 5-s countdown UI still shows; StartUserTurn()
            // is suppressed in LudoNumberUiManagerOffline and instead server sends
            // ludo.game.turn_started after ~5.5 s.
        }

        // Like BootstrapExistingOfflineFlow but skips the 5-s countdown — used when
        // we join a game that already started (ludo.room.starting was missed).
        private void BootstrapLateJoin(LudoV2RoomStarting payload)
        {
            int maxPlayers = payload.seats != null ? Mathf.Clamp(payload.seats.Count, 2, 4) : 2;
            string roomId = string.IsNullOrWhiteSpace(payload.room_id) ? queuedRoom?.data?.room_uuid : payload.room_id;

            latestSnapshot = new LudoV2RoomSnapshot
            {
                room_id = roomId,
                state = "in_progress",
                max_players = maxPlayers,
                current_players = payload.seats?.Count ?? maxPlayers,
                real_players = payload.seats?.FindAll(seat => seat.playerType == "human").Count ?? 1,
                bot_players = payload.seats?.FindAll(seat => seat.playerType == "bot").Count ?? 0,
                seats = payload.seats ?? new List<LudoV2SeatData>(),
            };

            isServerDrivenGameMode = true;
            hasStartedMatch = true;
            hasReportedMatchCompletion = false;
            isQueueing = false;

            localSeatOffset = GetLocalSeatIndex(latestSnapshot);
            Debug.Log($"[LudoV2] LateJoin LocalSeatOffset={localSeatOffset}");

            EnsureWaitingBoardVisible(latestSnapshot);
            RenderSeatsFromSnapshot(latestSnapshot);
            socketNumberEventReceiver.maxPlayer = maxPlayers;
            socketNumberEventReceiver.ChangeCukiSeatIndex();
            socketNumberEventReceiver.isServerDrivenGameMode = true;
            // Use 1-second countdown so Time() coroutine fires and shows the game board.
            socketNumberEventReceiver.ludoNumberGsNew.GameTimerStart(1);
        }

        private void EnsureWaitingBoardVisible(LudoV2RoomSnapshot snapshot)
        {
            if (hasEnteredWaitingBoard)
            {
                return;
            }

            hasEnteredWaitingBoard = true;
            dashBoardManager.ShowMatchmakingLoader(false);
            // Allow both landscape orientations; board is landscape-only but let the
            // device auto-rotate between left and right so the user can hold naturally.
            Screen.autorotateToPortrait = false;
            Screen.autorotateToPortraitUpsideDown = false;
            Screen.autorotateToLandscapeLeft = true;
            Screen.autorotateToLandscapeRight = true;
            Screen.orientation = ScreenOrientation.AutoRotation;
            dashBoardManager.fTUEPanal.SetActive(false);
            dashBoardManager.fTUEManager.SetActive(false);
            dashBoardManager.selectGameModePanal.SetActive(false);
            dashBoardManager.lobbySelectPanal.SetActive(false);
            dashBoardManager.onlineLobbySelectionPanel.SetActive(false);
            dashBoardManager.backButton.SetActive(false);
            dashBoardManager.SetLobbyUiBlocking(false);
            Canvas dashboardCanvas = dashBoardManager.dashBordPanal != null
                ? dashBoardManager.dashBordPanal.GetComponent<Canvas>()
                : null;
            if (dashboardCanvas != null)
            {
                dashboardCanvas.enabled = false;
            }
            socketNumberEventReceiver.ludoNumberGsNew.board.SetActive(true);
            GameObject board = socketNumberEventReceiver.ludoNumberGsNew.board;
            if (board.transform.parent != null)
                board.transform.parent.localScale = new Vector3(1.10f, 1.10f, 1f);
            SetBoardCanvasScalerForLandscape(board);
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
            roomChatController?.SetChatAvailability(false);
            agoraVoiceManager?.HandleRoomOpened();
            friendPanelController?.SetRoomActionAvailability(true);
            LudoFriendPanelController.RefreshRoomPlayerActionsIfPresent();
        }

        private void SetBoardCanvasScalerForLandscape(GameObject boardObject)
        {
            Canvas rootCanvas = boardObject.GetComponentInParent<Canvas>(true);
            if (rootCanvas != null) rootCanvas = rootCanvas.rootCanvas;
            if (rootCanvas == null) rootCanvas = boardObject.GetComponentInChildren<Canvas>(true);
            if (rootCanvas == null) return;

            CanvasScaler scaler = rootCanvas.GetComponent<CanvasScaler>();
            if (scaler == null) return;

            scaler.referenceResolution = new Vector2(1920f, 1080f);
            scaler.matchWidthOrHeight = 1f;
        }

        private void RenderSeatsFromSnapshot(LudoV2RoomSnapshot snapshot)
        {
            if (snapshot == null || snapshot.seats == null)
            {
                return;
            }

            // Only recalculate from snapshot when the server hasn't authoritatively told us our seat.
            // Once my_seat arrives, hasMySeatFromServer=true and we never overwrite localSeatOffset here.
            if (!hasMySeatFromServer)
                localSeatOffset = GetLocalSeatIndex(snapshot);
            Debug.Log($"[LudoV2] RenderSeats localSeatOffset={localSeatOffset} hasMySeat={hasMySeatFromServer}");

            socketNumberEventReceiver.joinTableResponse = BuildJoinTableResponse(snapshot);
            socketNumberEventReceiver.signUpResponce = BuildSignUpResponse(snapshot);
            socketNumberEventReceiver.userTurnStart = BuildUserTurnStart();
            ResetVisibleSeatWidgets();
            socketNumberEventReceiver.ludoNumbersAcknowledgementHandler.JoinTableAcknowledged();
        }

        private JoinTableResponse BuildJoinTableResponse(LudoV2RoomSnapshot snapshot)
        {
            int maxP = snapshot.max_players > 0 ? snapshot.max_players : 2;
            List<PlayerInfoData> playerInfo = new List<PlayerInfoData>();
            foreach (var seat in snapshot.seats)
            {
                int serverIdx = Mathf.Max(0, seat.seatNo - 1);
                int visualIdx = ToVisualSeat(serverIdx, maxP);
                string uid = seat.userId?.ToString() ?? seat.botCode ?? string.Empty;
                playerInfo.Add(new PlayerInfoData
                {
                    playerSeatIndex = visualIdx,
                    userId = uid,
                    username = LudoDisplayNameUtility.ResolveDisplayName(uid, seat.displayName ?? ("Seat " + seat.seatNo), visualIdx),
                    userProfile = DefaultSeatAvatarUrl,
                });
                Debug.Log($"[LudoV2] Seat serverIdx={serverIdx} visualIdx={visualIdx} userId={uid} name={seat.displayName}");
            }

            return new JoinTableResponse
            {
                data = new JoinTableResponseData
                {
                    maxPlayerCount = maxP,
                    tableId = snapshot.room_id,
                    queueKey = snapshot.room_id,
                    playerInfo = playerInfo,
                    playerMoves = new List<int>(),
                    thisPlayerSeatIndex = localSeatOffset, // local player's actual server seat index
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
            int maxP = snapshot.max_players > 0 ? snapshot.max_players : 2;
            List<SignUpResponceClass.PlayerInfo> players = new List<SignUpResponceClass.PlayerInfo>();

            foreach (var seat in snapshot.seats)
            {
                int serverIdx = Mathf.Max(0, seat.seatNo - 1);
                int visualIdx = ToVisualSeat(serverIdx, maxP);
                string uid = seat.userId?.ToString() ?? seat.botCode ?? string.Empty;
                players.Add(new SignUpResponceClass.PlayerInfo
                {
                    seatIndex = visualIdx,
                    userId = uid,
                    username = LudoDisplayNameUtility.ResolveDisplayName(uid, seat.displayName ?? ("Seat " + seat.seatNo), visualIdx),
                    avatar = DefaultSeatAvatarUrl,
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
                    numberOfPlayers = maxP,
                    tableState = snapshot.state == "starting" ? "PLAYING" : "WAITING_FOR_PLAYERS",
                    leftPlayerInfo = new List<object>(),
                    playerInfo = players,
                    movesLeft = 24,
                    thisPlayerSeatIndex = localSeatOffset, // local player's actual server seat index
                    playerMoves = new List<int>(),
                    userTurnDetails = new SignUpResponceClass.UserTurnDetails
                    {
                        currentTurnSeatIndex = 0,
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
            return new UserTimeStart
            {
                data = new UserTimeStartData
                {
                    startTurnSeatIndex = 0, // local player always visual seat 0 at game start
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
                // Reset seat index so stale data in inactive slots can't match any turn seat
                playerControl.playerInfoData.playerSeatIndex = -1;
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

        public bool TrySendEmoji(int emojiId, int fallbackSeatIndex = 0)
        {
            if (!Configuration.IsLudoV2Enabled() || namespaceSocket == null || !namespaceSocket.IsOpen)
            {
                return false;
            }

            string roomId = latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
            if (string.IsNullOrWhiteSpace(roomId))
            {
                return false;
            }

            int seatIndex = latestSnapshot != null ? GetLocalSeatIndex(latestSnapshot) : Mathf.Max(0, fallbackSeatIndex);

            namespaceSocket.Emit(
                "ludo.chat.emoji",
                JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "room_id", roomId },
                    { "emoji_id", emojiId },
                    { "seat_index", seatIndex },
                })
            );

            return true;
        }

        public bool TrySendChatMessage(string message)
        {
            if (!Configuration.IsLudoV2Enabled() || namespaceSocket == null || !namespaceSocket.IsOpen)
            {
                return false;
            }

            string roomId = latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
            if (string.IsNullOrWhiteSpace(roomId) || string.IsNullOrWhiteSpace(message))
            {
                return false;
            }

            namespaceSocket.Emit(
                "ludo.chat.send",
                JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "room_id", roomId },
                    { "message", message.Trim() },
                    { "client_message_id", Guid.NewGuid().ToString("N") },
                })
            );

            return true;
        }

        public bool TryRequestChatHistory(int limit = 50)
        {
            if (!Configuration.IsLudoV2Enabled() || namespaceSocket == null || !namespaceSocket.IsOpen)
            {
                return false;
            }

            string roomId = latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
            if (string.IsNullOrWhiteSpace(roomId))
            {
                return false;
            }

            namespaceSocket.Emit(
                "ludo.chat.history",
                JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "room_id", roomId },
                    { "limit", Mathf.Clamp(limit, 1, 100) },
                })
            );

            return true;
        }

        private void OnChatMessage(LudoV2ChatMessagePayload payload)
        {
            if (payload == null)
            {
                return;
            }

            Debug.Log("Ludo v2 chat message: " + JsonConvert.SerializeObject(payload));
            RunOnMainThread(() => OnChatMessageReceived?.Invoke(payload));
        }

        private void OnChatHistory(LudoV2ChatHistoryPayload payload)
        {
            List<LudoV2ChatMessagePayload> messages = payload?.messages ?? new List<LudoV2ChatMessagePayload>();
            Debug.Log("Ludo v2 chat history count: " + messages.Count);
            RunOnMainThread(() => OnChatHistoryReceived?.Invoke(messages));
        }

        private void RunOnMainThread(Action action)
        {
            var dispatcher = UnityMainThreadDispatcher.Instance;
            if (dispatcher == null)
            {
                // Auto-create dispatcher if not present in scene — required for socket callbacks
                var go = new GameObject("UnityMainThreadDispatcher");
                dispatcher = go.AddComponent<UnityMainThreadDispatcher>();
                Debug.LogWarning("[LudoV2] UnityMainThreadDispatcher was missing — created automatically");
            }
            dispatcher.Enqueue(action);
        }

        private void OnEmojiReceived(LudoV2EmojiPayload payload)
        {
            if (payload == null || socketNumberEventReceiver?.emojiResponse?.Data == null)
            {
                return;
            }

            socketNumberEventReceiver.emojiResponse.Data.emoji = payload.emoji_id;
            socketNumberEventReceiver.emojiResponse.Data.seatIndex = Mathf.Max(0, payload.sender?.seat_no - 1 ?? 0);
            socketNumberEventReceiver.emojiResponse.Data.tableId = payload.room_id ?? latestSnapshot?.room_id ?? string.Empty;

            if (socketNumberEventReceiver.ludoNumberGsNew != null)
            {
                socketNumberEventReceiver.ludoNumberGsNew.EmojiSet();
            }
        }

        // ── Server-driven game events ──────────────────────────────────────────

        private void OnTurnStarted(LudoV2TurnStarted payload)
        {
            if (payload == null) return;
            // Auto-enable server-driven mode when server sends turn events (handles allHumans parse failure)
            if (!isServerDrivenGameMode) isServerDrivenGameMode = true;
            int maxP = latestSnapshot?.max_players > 0 ? latestSnapshot.max_players : 2;
            int visualSeat = ToVisualSeat(payload.seat_index, maxP);
            bool isMyTurn = payload.seat_index == localSeatOffset;
            Debug.Log($"[DBG][turn_started] serverSeat={payload.seat_index} visualSeat={visualSeat} | localSeatOffset={localSeatOffset} hasMySeat={hasMySeatFromServer} | isMyTurn={isMyTurn} nonce={payload.turn_nonce}");
            // Capture nonce only for local player's turn (no nonce needed for opponent turns)
            if (payload.seat_index == localSeatOffset)
            {
                currentTurnNonce = payload.turn_nonce;
                currentRollNonce = null;
            }
            RunOnMainThread(() =>
            {
                // Late-join: if game started on server before this client received room.starting,
                // bootstrap from the latest snapshot now so the board becomes visible.
                if (!hasStartedMatch && latestSnapshot != null)
                {
                    Debug.Log("[LudoV2] Late-join via turn_started — bootstrapping");
                    var syntheticStart = new LudoV2RoomStarting
                    {
                        room_id = latestSnapshot.room_id,
                        started_with_bots = latestSnapshot.bot_players > 0,
                        seats = latestSnapshot.seats,
                    };
                    BootstrapLateJoin(syntheticStart);
                }
                socketNumberEventReceiver?.OnServerTurnStarted(visualSeat);
            });
        }

        private void OnDiceRolled(LudoV2DiceRolled payload)
        {
            if (payload == null) return;
            if (!isServerDrivenGameMode) isServerDrivenGameMode = true;
            int maxP = latestSnapshot?.max_players > 0 ? latestSnapshot.max_players : 2;
            int visualSeat = ToVisualSeat(payload.seat_index, maxP);
            bool isMyRoll = payload.seat_index == localSeatOffset;
            Debug.Log($"[DBG][dice_rolled] serverSeat={payload.seat_index} visualSeat={visualSeat} dice={payload.dice_value} | localSeatOffset={localSeatOffset} hasMySeat={hasMySeatFromServer} | isMyRoll={isMyRoll} nonce={payload.roll_nonce}");
            // Capture roll nonce for the local player's move
            if (payload.seat_index == localSeatOffset)
            {
                currentRollNonce = payload.roll_nonce;
                currentTurnNonce = null;  // turn nonce consumed after dice rolled
            }
            RunOnMainThread(() =>
            {
                socketNumberEventReceiver?.OnServerDiceRolled(visualSeat, payload.dice_value);
            });
        }

        private void OnTokenMoved(LudoV2TokenMoved payload)
        {
            if (payload == null) return;
            if (!isServerDrivenGameMode) isServerDrivenGameMode = true;
            int maxP = latestSnapshot?.max_players > 0 ? latestSnapshot.max_players : 2;
            int visualSeat = ToVisualSeat(payload.seat_index, maxP);
            bool isLocalPlayer = payload.seat_index == localSeatOffset;
            int killedCount = payload.killed_tokens?.Count ?? 0;
            Debug.Log($"[LudoV2] token_moved serverSeat={payload.seat_index} visualSeat={visualSeat} token={payload.token_index} isWin={payload.is_win} isLocal={isLocalPlayer} killed={killedCount}");

            // For opponent tokens: drive animation via OnServerTokenMoved.
            // For local player: local animation already ran when token was tapped — skip to avoid double-move bug.
            if (payload.token_index >= 0 && !isLocalPlayer)
            {
                RunOnMainThread(() =>
                    socketNumberEventReceiver?.OnServerTokenMoved(
                        visualSeat, payload.token_index, payload.dice_value, payload.extra_turn)
                );
            }

            // Teleport any killed tokens back to yard using the authoritative position snapshot.
            // Using dice_value=0 was wrong (E-10) — it leaves the token on the board visually.
            // Instead, use payload.tokens which already has the killed token at position -1.
            if (payload.killed_tokens != null && payload.tokens != null)
            {
                foreach (var kt in payload.killed_tokens)
                {
                    if (kt == null) continue;
                    int killedServerSeat  = kt.seat_index;
                    int killedVisualSeat  = ToVisualSeat(killedServerSeat, maxP);
                    int killedTokenIdx    = kt.token_index;
                    // Authoritative position is -1 (yard); teleport directly via board sync helper
                    RunOnMainThread(() =>
                        TeleportTokenToPosition(killedVisualSeat, killedTokenIdx, -1)
                    );
                }
            }

            // Handle server-confirmed win for any player
            if (payload.is_win)
            {
                bool localWon = isLocalPlayer;
                RunOnMainThread(() => HandleServerBattleFinish(localWon));
            }
        }

        private void HandleServerBattleFinish(bool localPlayerWon)
        {
            if (socketNumberEventReceiver?.ludoNumberGsNew == null) return;
            socketNumberEventReceiver.ludoNumberGsNew.BattleFinishFromServer(localPlayerWon);
        }

        // Teleports a single token to an authoritative board position without animation.
        // position == -1 returns the token to the yard (killed or reset).
        private void TeleportTokenToPosition(int visualSeat, int tokenIndex, int position)
        {
            var gsNew = socketNumberEventReceiver?.ludoNumberGsNew;
            if (gsNew == null) return;
            if (visualSeat < 0 || visualSeat >= gsNew.ludoNumberPlayerControl.Length) return;
            var cmList = gsNew.ludoNumberPlayerControl[visualSeat]?.coockieMovementList;
            if (cmList == null || tokenIndex < 0 || tokenIndex >= cmList.Count) return;
            var cm = cmList[tokenIndex];
            if (cm == null) return;
            cm.myLastBoxIndex = position;
            cm.TokenMoveOnRejoin();
        }

        private void OnTurnMissed(LudoV2TurnMissed payload)
        {
            if (!isServerDrivenGameMode || payload == null) return;
            int maxP = latestSnapshot?.max_players > 0 ? latestSnapshot.max_players : 2;
            int visualSeat = ToVisualSeat(payload.seat_index, maxP);
            Debug.Log($"[LudoV2] turn_missed serverSeat={payload.seat_index} visualSeat={visualSeat} reason={payload.reason}");
            RunOnMainThread(() => CommonUtil.ShowToast("Turn missed"));
        }

        // Server confirms which seat belongs to this client — authoritative offset source.
        // Overrides the userId-based heuristic in GetLocalSeatIndex which can fail when
        // the client stores user_code in PlayerPrefs["id"] instead of the database id.
        private void OnMySeatAssigned(LudoV2MySeat payload)
        {
            if (payload == null || payload.seat_no <= 0) return;
            int newOffset = payload.seat_no - 1;
            Debug.Log($"[LudoV2] my_seat assigned seat_no={payload.seat_no} → localSeatOffset={newOffset} (was {localSeatOffset})");
            localSeatOffset = newOffset;
            hasMySeatFromServer = true;
            // Re-render seats with the corrected offset so colors/positions are right.
            if (latestSnapshot != null)
            {
                RunOnMainThread(() => RenderSeatsFromSnapshot(latestSnapshot));
            }
        }

        // ── Public API for dice & move ─────────────────────────────────────────

        public void TryRollDice()
        {
            if (namespaceSocket == null || !namespaceSocket.IsOpen) return;
            string roomId = latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
            Debug.Log($"[LudoV2] TryRollDice roomId={roomId} nonce={currentTurnNonce} serverMode={isServerDrivenGameMode}");
            var payload = new Dictionary<string, object>
            {
                { "room_id",    roomId },
                { "user_id",    int.Parse(Configuration.GetId()) },
                { "turn_nonce", currentTurnNonce ?? string.Empty },
            };
            namespaceSocket.Emit("ludo.game.roll_dice", JsonConvert.SerializeObject(payload));
        }

        public void TryMoveToken(int tokenIndex, bool extraTurn, bool isWin)
        {
            if (!isServerDrivenGameMode || namespaceSocket == null || !namespaceSocket.IsOpen) return;
            // Block move if we have no valid roll nonce — means it's not our turn or we haven't rolled yet
            if (string.IsNullOrEmpty(currentRollNonce))
            {
                Debug.LogWarning($"[LudoV2] TryMoveToken BLOCKED — no roll nonce (not our turn or nonce missing)");
                return;
            }
            string roomId = latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
            Debug.Log($"[LudoV2] TryMoveToken tokenIndex={tokenIndex} nonce={currentRollNonce}");
            var payload = new Dictionary<string, object>
            {
                { "room_id",     roomId },
                { "token_index", tokenIndex },
                { "user_id",     int.Parse(Configuration.GetId()) },
                { "roll_nonce",  currentRollNonce ?? string.Empty },
                // extra_turn and is_win intentionally omitted — server computes them
            };
            namespaceSocket.Emit("ludo.game.move_token", JsonConvert.SerializeObject(payload));
        }

        private void OnSocketPayloadError(LudoV2ErrorPayload payload)
        {
            string message = payload?.message ?? "Ludo room error";

            if (hasEnteredWaitingBoard || hasStartedMatch)
            {
                Debug.LogWarning("Ludo v2 room error: " + message);
                CommonUtil.ShowToast(message);
                return;
            }

            FailMatchmaking(message);
        }

        private void OnMatchResult(LudoV2MatchResult payload)
        {
            if (payload == null) return;

            // For server-driven mode: show win/lose panel from server result
            if (isServerDrivenGameMode && !isTournamentMode)
            {
                bool localWon = IsLocalWinner(payload);
                RunOnMainThread(() => HandleServerBattleFinish(localWon));
                return;
            }

            if (!isTournamentMode) return;

            bool tournamentStillRunning = string.Equals(
                payload.settlement?.data?.status,
                "running",
                StringComparison.OrdinalIgnoreCase
            );

            if (!tournamentStillRunning) return;
            if (!IsLocalWinner(payload) || isClaimingNextTournamentRound) return;

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
            Debug.Log($"[LudoV2] Socket disconnected — intentional={isIntentionalDisconnect} hasStarted={hasStartedMatch}");
            if (isIntentionalDisconnect) return;

            if (hasStartedMatch && isServerDrivenGameMode && !isReconnecting)
            {
                reconnectAttempts = 0;
                isReconnecting = true;
                StartCoroutine(ReconnectCoroutine());
                return;
            }

            if (!hasStartedMatch && isQueueing)
            {
                FailMatchmaking("Ludo room disconnected");
            }
        }

        private IEnumerator ReconnectCoroutine()
        {
            string roomId = latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
            string socketUrl = Configuration.BaseSocketUrl.TrimEnd('/') + "/socket.io/";

            while (isReconnecting && reconnectAttempts < MaxReconnectAttempts)
            {
                reconnectAttempts++;
                Debug.Log($"[LudoV2] Reconnect attempt {reconnectAttempts}/{MaxReconnectAttempts} roomId={roomId}");
                yield return new WaitForSeconds(ReconnectRetryDelay);

                // Build a fresh socket that, on connect, emits session.reconnect instead of joining a queue
                isIntentionalDisconnect = false;
                var mgr = new SocketManager(new Uri(socketUrl), BuildSocketOptions());
                var sock = mgr.GetSocket(Configuration.LudoV2SocketNamespace);

                bool connected = false;
                sock.On(SocketIOEventTypes.Connect, () =>
                {
                    connected = true;
                    sock.Emit(
                        "ludo.session.reconnect",
                        JsonConvert.SerializeObject(new Dictionary<string, object>
                        {
                            { "room_id", roomId },
                            { "user_id", int.Parse(Configuration.GetId()) },
                        })
                    );
                });
                sock.On(SocketIOEventTypes.Disconnect, OnSocketDisconnected);
                sock.On<Error>(SocketIOEventTypes.Error, OnSocketError);
                // Re-register all game event handlers on the new socket
                sock.On<LudoV2RoomSnapshot>("ludo.game.snapshot", OnRoomSnapshot);
                sock.On<LudoV2MatchResult>("ludo.game.result", OnMatchResult);
                sock.On<LudoV2TurnStarted>("ludo.game.turn_started", OnTurnStarted);
                sock.On<LudoV2DiceRolled>("ludo.game.dice_rolled", OnDiceRolled);
                sock.On<LudoV2TokenMoved>("ludo.game.token_moved", OnTokenMoved);
                sock.On<LudoV2TurnMissed>("ludo.game.turn_missed", OnTurnMissed);
                sock.On<LudoV2GameState>("ludo.game.state", OnGameStateSync);
                sock.On<LudoV2ErrorPayload>("ludo.error", OnSocketPayloadError);
                sock.On<LudoV2MySeat>("ludo.game.my_seat", OnMySeatAssigned);

                // Wait for connection (up to 4s)
                float waited = 0f;
                while (!connected && waited < 4f)
                {
                    yield return null;
                    waited += Time.unscaledDeltaTime;
                }

                if (connected)
                {
                    socketManager = mgr;
                    namespaceSocket = sock;
                    isReconnecting = false;
                    reconnectAttempts = 0;
                    Debug.Log("[LudoV2] Reconnect successful.");
                    yield break;
                }

                // Failed this attempt — clean up before retrying
                try { sock.Disconnect(); } catch { }
            }

            if (isReconnecting)
            {
                isReconnecting = false;
                Debug.LogWarning("[LudoV2] Reconnect failed after max attempts.");
                CommonUtil.ShowToast("Connection lost. Returning to lobby.");
                FailMatchmaking("Connection lost");
            }
        }

        private void OnGameStateSync(LudoV2GameState payload)
        {
            if (!isServerDrivenGameMode || payload == null) return;
            Debug.Log($"[LudoV2] game.state received — current_seat={payload.current_seat} rolled={payload.rolled} dice={payload.dice_value}");
            RunOnMainThread(() => ApplyBoardSync(payload));
        }

        private void ApplyBoardSync(LudoV2GameState payload)
        {
            if (payload?.tokens == null) return;
            int maxP = latestSnapshot?.max_players > 0 ? latestSnapshot.max_players : 2;
            var gsNew = socketNumberEventReceiver?.ludoNumberGsNew;
            if (gsNew == null) return;

            // Restore nonces from server snapshot so the reconnected player can act immediately.
            // Server sends turn_nonce when rolled=false, roll_nonce when rolled=true.
            currentTurnNonce = !string.IsNullOrEmpty(payload.turn_nonce) ? payload.turn_nonce : null;
            currentRollNonce = !string.IsNullOrEmpty(payload.roll_nonce) ? payload.roll_nonce : null;

            // Stop any running coroutines (pending animations, timers) before overwriting state
            gsNew.StopAllCoroutines();

            // Teleport each token to its authoritative position (no animation)
            for (int serverSeat = 0; serverSeat < payload.tokens.Length && serverSeat < maxP; serverSeat++)
            {
                int visualSeat = ToVisualSeat(serverSeat, maxP);
                if (visualSeat < 0 || visualSeat >= gsNew.ludoNumberPlayerControl.Length) continue;
                var playerControl = gsNew.ludoNumberPlayerControl[visualSeat];
                var cmList = playerControl?.coockieMovementList;
                if (cmList == null) continue;

                int[] seatTokens = payload.tokens[serverSeat];
                for (int ti = 0; ti < cmList.Count && ti < seatTokens.Length; ti++)
                {
                    var cm = cmList[ti];
                    if (cm == null) continue;
                    cm.myLastBoxIndex = seatTokens[ti];
                    cm.TokenMoveOnRejoin();
                }
            }

            // Restore current turn
            int currentVisual = ToVisualSeat(payload.current_seat, maxP);
            socketNumberEventReceiver.OnServerTurnStarted(currentVisual);

            // If dice was already rolled this turn, restore dice UI without retriggering full dice animation
            if (payload.rolled && payload.dice_value.HasValue)
            {
                socketNumberEventReceiver.OnServerDiceRolled(currentVisual, payload.dice_value.Value);
            }

            Debug.Log($"[LudoV2] Board sync complete. currentVisual={currentVisual} rolled={payload.rolled} dice={payload.dice_value}");
        }

        private void OnSocketError(Error error)
        {
            Debug.LogWarning($"[LudoV2] Socket error: {error?.message}");
            if (!hasStartedMatch)
            {
                FailMatchmaking(error?.message ?? "Ludo socket error");
            }
        }

#if UNITY_EDITOR
        private bool ShouldUseLocalMatchmakingPreview()
        {
            return Application.isEditor && PlayerPrefs.GetInt("ludo_v2_live_in_editor", 0) != 1;
        }

        private void StartLocalMatchmakingPreview(int entryFee, int maxPlayers)
        {
            string roomId = "editor-local-" + DateTime.UtcNow.Ticks;
            queuedRoom = new LudoV2QueueJoinEnvelope
            {
                success = true,
                data = new LudoV2QueueRoomData
                {
                    room_uuid = roomId,
                    status = "waiting",
                    max_players = maxPlayers,
                    current_players = 1,
                    current_real_players = 1,
                    current_bot_players = 0,
                },
            };

            socketNumberEventReceiver.entryFee = entryFee;
            socketNumberEventReceiver.winAmt = entryFee * Mathf.Max(2, maxPlayers);

            LudoV2RoomSnapshot snapshot = CreateLocalSnapshot(roomId, maxPlayers);
            latestSnapshot = snapshot;
            localPreviewSnapshot = snapshot;
            localPreviewMaxPlayers = maxPlayers;
            localPreviewNextBotAt = Time.realtimeSinceStartup + LocalPreviewBotJoinDelaySeconds;
            localPreviewActive = true;

            EnsureWaitingBoardVisible(snapshot);
            RenderSeatsFromSnapshot(snapshot);
            UpdateWaitingBoardMessage(snapshot);
        }

        private void TickLocalMatchmakingPreview()
        {
            if (!localPreviewActive || localPreviewSnapshot == null || Time.realtimeSinceStartup < localPreviewNextBotAt)
            {
                return;
            }

            if (!isQueueing || hasStartedMatch)
            {
                localPreviewActive = false;
                return;
            }

            if (localPreviewSnapshot.seats.Count < localPreviewMaxPlayers)
            {
                int nextSeatNo = localPreviewSnapshot.seats.Count + 1;
                localPreviewSnapshot.seats.Add(CreateLocalBotSeat(nextSeatNo));
                localPreviewSnapshot.current_players = localPreviewSnapshot.seats.Count;
                localPreviewSnapshot.bot_players = localPreviewSnapshot.seats.FindAll(seat => seat != null && seat.playerType == "bot").Count;
                localPreviewSnapshot.real_players = localPreviewSnapshot.seats.Count - localPreviewSnapshot.bot_players;
                latestSnapshot = localPreviewSnapshot;
                RenderSeatsFromSnapshot(localPreviewSnapshot);
                UpdateWaitingBoardMessage(localPreviewSnapshot);
                localPreviewNextBotAt = Time.realtimeSinceStartup + LocalPreviewBotJoinDelaySeconds;
                return;
            }

            localPreviewActive = false;
            OnRoomStarting(new LudoV2RoomStarting
            {
                room_id = localPreviewSnapshot.room_id,
                started_with_bots = localPreviewSnapshot.bot_players > 0,
                seats = localPreviewSnapshot.seats,
            });
        }

        private LudoV2RoomSnapshot CreateLocalSnapshot(string roomId, int maxPlayers)
        {
            int localUserId = 0;
            int.TryParse(Configuration.GetId(), out localUserId);

            return new LudoV2RoomSnapshot
            {
                room_id = roomId,
                state = "waiting",
                max_players = maxPlayers,
                current_players = 1,
                real_players = 1,
                bot_players = 0,
                seats = new List<LudoV2SeatData>
                {
                    new LudoV2SeatData
                    {
                        seatNo = 1,
                        userId = localUserId,
                        playerType = "human",
                        displayName = LudoDisplayNameUtility.LocalPlayerLabel(),
                        isConnected = true,
                        isReady = true,
                    },
                },
            };
        }

        private LudoV2SeatData CreateLocalBotSeat(int seatNo)
        {
            return new LudoV2SeatData
            {
                seatNo = seatNo,
                userId = null,
                playerType = "bot",
                displayName = LudoDisplayNameUtility.NeutralSeatLabel(seatNo - 1),
                botCode = "BOT-" + seatNo,
                isConnected = true,
                isReady = true,
            };
        }
#endif

        private void FailMatchmaking(string message)
        {
            Debug.LogWarning("Ludo v2 matchmaking failed: " + message);
            dashBoardManager.ShowMatchmakingLoader(false);
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
            dashBoardManager.SetLobbyUiBlocking(true);
            dashBoardManager.lobbySelectPanal.SetActive(true);
            dashBoardManager.backButton.SetActive(true);
            roomChatController?.SetChatAvailability(false);
            roomChatController?.ClearMessages();
            agoraVoiceManager?.HandleRoomClosed();
            friendPanelController?.SetRoomActionAvailability(false);
            CommonUtil.ShowToast(message);
            DisconnectSocket();
        }

        private void DisconnectSocket()
        {
            roomChatController?.SetChatAvailability(false);
            roomChatController?.ClearMessages();
            agoraVoiceManager?.HandleRoomClosed();
            friendPanelController?.SetRoomActionAvailability(false);

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

        // Call when user returns to lobby after a match (win panel home btn, back button, etc.)
        public void ForceResetMatchState()
        {
            isQueueing = false;
            hasStartedMatch = false;
            hasEnteredWaitingBoard = false;
            hasReportedMatchCompletion = false;
            isClaimingNextTournamentRound = false;
            isReconnecting = false;
            reconnectAttempts = 0;
            currentTurnNonce = null;
            currentRollNonce = null;
            latestSnapshot = null;
            lastAnnouncedSeatCount = 0;
            hasMySeatFromServer = false;
            localSeatOffset = 0;
            CancelInvoke(nameof(ClaimNextTournamentRound));
            DisconnectSocket();
        }

        public string GetActiveRoomId()
        {
            return latestSnapshot?.room_id ?? queuedRoom?.data?.room_uuid;
        }

        public string GetAgoraVoiceChannelName()
        {
            string roomId = GetActiveRoomId();
            if (string.IsNullOrWhiteSpace(roomId))
            {
                return null;
            }

            if (isTournamentMode)
            {
                return "ludo_tournament_" + roomId;
            }

            if (isPrivateTableMode)
            {
                return "ludo_private_" + (string.IsNullOrWhiteSpace(privateTableCode) ? roomId : privateTableCode);
            }

            return "ludo_room_" + roomId;
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

            // In server-driven mode the server handles settlement via _autoSettle
            if (isServerDrivenGameMode)
            {
                Debug.Log("[LudoV2] ReportMatchCompleted skipped — server handles settlement in server-driven mode.");
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
                        username = LudoDisplayNameUtility.ResolveDisplayName(
                            seat?.userId?.ToString(),
                            seat?.displayName ?? player.username ?? string.Empty,
                            Mathf.Max(0, seatNo - 1)
                        ),
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

            string payload = JsonConvert.SerializeObject(new Dictionary<string, object>
            {
                { "tournament_uuid", isTournamentMode ? activeTournamentUuid : null },
                { "tournament_entry_uuid", isTournamentMode ? activeTournamentEntryUuid : null },
                { "winner", winnerPayload },
                { "placements", placements },
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
            string message;
            if (isPrivateTableMode && !string.IsNullOrEmpty(privateTableCode))
                message = remainingSeats > 0
                    ? $"Code: {privateTableCode}\nWaiting for {remainingSeats} more player{(remainingSeats == 1 ? "" : "s")}..."
                    : $"Code: {privateTableCode}\nStarting match...";
            else
                message = remainingSeats > 0
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
        public string match_uuid;
        public List<LudoV2SeatData> seats;
    }

    [Serializable]
    public class LudoV2BotJoined
    {
        public string room_id;
        public LudoV2SeatData seat;
    }

    [Serializable]
    public class LudoV2MySeat
    {
        public int seat_no;
        public string room_id;
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
    public class LudoV2EmojiPayload
    {
        public string room_id;
        public int emoji_id;
        public LudoV2EmojiSender sender;
        public string created_at;
    }

    [Serializable]
    public class LudoV2ChatHistoryPayload
    {
        public string room_id;
        public List<LudoV2ChatMessagePayload> messages;
    }

    [Serializable]
    public class LudoV2ChatMessagePayload
    {
        public string message_id;
        public string room_id;
        public string match_uuid;
        public string message_type;
        public string sender_type;
        public string message;
        public LudoV2ChatSenderPayload sender;
        public string created_at;
    }

    [Serializable]
    public class LudoV2ChatSenderPayload
    {
        public int? user_id;
        public int seat_no;
        public string display_name;
        public string player_id;
        public string avatar;
        public string bot_code;
    }

    [Serializable]
    public class LudoV2EmojiSender
    {
        public int? user_id;
        public int seat_no;
        public string display_name;
        public string player_type;
    }

    [Serializable]
    public class LudoV2ErrorPayload
    {
        public string message;
    }

    [Serializable]
    public class LudoV2MatchResult
    {
        public string                  room_id;
        public LudoV2WinnerPayload     winner;
        public LudoV2SettlementPayload settlement;
        public bool                    cancelled;
        public List<LudoV2Placement>   placements;
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

    // ── Server-driven game event payloads ──────────────────────────────────────

    [Serializable]
    public class LudoV2TurnStarted
    {
        public int    seat_index;
        public bool   is_bot;
        public string turn_nonce;   // must be echoed in roll_dice
    }

    [Serializable]
    public class LudoV2DiceRolled
    {
        public int        seat_index;
        public int        dice_value;
        public List<int>  legal_tokens;
        public bool       has_moves;
        public string     roll_nonce;   // must be echoed in move_token
    }

    [Serializable]
    public class LudoV2KilledToken
    {
        public int seat_index;
        public int token_index;
    }

    [Serializable]
    public class LudoV2TokenMoved
    {
        public int                    seat_index;
        public int                    token_index;
        public int                    dice_value;
        public bool                   extra_turn;
        public bool                   is_win;
        public List<LudoV2KilledToken> killed_tokens;
        public int[][]                tokens;         // full authoritative board snapshot
    }

    [Serializable]
    public class LudoV2TurnMissed
    {
        public int    seat_index;
        public string reason;
    }

    [Serializable]
    public class LudoV2GameState
    {
        public string   room_id;
        public int[][]  tokens;             // tokens[serverSeat][tokenIdx] = position (-1..56)
        public int      current_seat;       // server seat index whose turn it is
        public int?     dice_value;         // null if dice not yet rolled this turn
        public bool     rolled;             // whether dice was rolled this turn
        public int[]    finished_seats;     // server seat indices that have finished
        public int?     timer_remaining_ms; // ms left on current turn timer (null = unknown)
        public string   turn_nonce;         // valid when rolled=false (awaiting roll_dice)
        public string   roll_nonce;         // valid when rolled=true  (awaiting move_token)
    }

    [Serializable]
    public class LudoV2Placement
    {
        public int    seat_no;
        public int?   user_id;
        public int    finish_position;
        public int    score;
        public bool   is_winner;
        public string result;
    }
}
