using System;
using System.Collections;
using System.Collections.Generic;
using Best.HTTP;
using Best.SocketIO;
using Best.SocketIO.Events;
using Newtonsoft.Json;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class PrivateTableManager : MonoBehaviour
    {
        public static PrivateTableManager Instance { get; private set; }

        [Header("Popup References")]
        public GameObject privateTablePopup;
        public InputField feeAmountInput;
        public InputField gameCodeInput;
        public Button createButton;
        public Button joinButton;
        public Button cancelButton;

        [Header("Waiting Panel")]
        public GameObject waitingPanel;
        public Text waitingCodeText;
        public Text waitingPlayersText;
        public Text waitingPrizeText;
        public Text waitingFeeStatusText;
        public Button cancelWaitingButton;
        public Button copyCodeButton;

        [Header("Join Panel")]
        public GameObject joinPanel;
        public InputField joinCodeInput;
        public Button confirmJoinButton;
        public Button cancelJoinButton;

        [Header("Loading")]
        public GameObject joinLoadingOverlay;   // assign a full-screen loading panel in Inspector

        private SocketManager socketManager;
        private Socket socket;

        private string currentTableCode;
        private int currentTableId;
        private int currentMaxPlayers;
        private int currentFee;
        private bool isConnecting;

        private void Awake()
        {
            if (Instance != null && Instance != this) { Destroy(gameObject); return; }
            Instance = this;
        }

        private void OnDestroy()
        {
            DisconnectSocket();
        }

        // ── Called by DashBoardManagerOffline when CREATE (Private Table) pressed ──
        public void OnCreatePressed(int maxPlayers)
        {
            int fee = 0;
            if (!string.IsNullOrEmpty(feeAmountInput?.text))
                int.TryParse(feeAmountInput.text, out fee);

            currentMaxPlayers = maxPlayers;
            currentFee = fee;

            SetButtonsInteractable(false);
            CommonUtil.ShowToast("Creating table...");

            ConnectAndEmit(() =>
            {
                socket.Emit("create-private-table", JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "user_id", int.Parse(Configuration.GetId()) },
                    { "token", Configuration.GetToken() },
                    { "fee_amount", fee },
                    { "max_players", maxPlayers },
                }));
            });
        }

        // ── Called when user wants to join by code ──
        public void OnJoinPressed()
        {
            string code = joinCodeInput?.text?.Trim();
            if (!IsValidPrivateTableCode(code))
            {
                CommonUtil.ShowToast("Please enter a valid 6-digit table code.");
                return;
            }

            SetButtonsInteractable(false);
            ShowJoinLoader(true);

            ConnectAndEmit(() =>
            {
                socket.Emit("join-private-table", JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "user_id", int.Parse(Configuration.GetId()) },
                    { "token", Configuration.GetToken() },
                    { "code", code },
                }));
            });
        }

        // ── Leave table while waiting ──
        public void CancelWaiting()
        {
            if (string.IsNullOrEmpty(currentTableCode))
            {
                DisconnectSocket();
                HideWaitingPanel();
                return;
            }

            if (cancelWaitingButton != null)
                cancelWaitingButton.interactable = false;

            if (socket != null && socket.IsOpen)
            {
                socket.Emit("leave-private-table", JsonConvert.SerializeObject(new Dictionary<string, object>
                {
                    { "user_id", int.Parse(Configuration.GetId()) },
                    { "token", Configuration.GetToken() },
                    { "code", currentTableCode },
                }));
            }
            else
            {
                // Socket gone — just hide panel
                HideWaitingPanel();
                DisconnectSocket();
            }
        }

        // ── Copy code button — copies a full shareable message ──
        public void CopyCodeToClipboard()
        {
            if (!string.IsNullOrEmpty(currentTableCode))
            {
                PassNPlayPopup.CopyToClipboard(BuildShareMessage(currentTableCode));
                CommonUtil.ShowToast("Share message copied!");
            }
        }

        // ── Tap the code label itself → copy only the code ──
        public void CopyOnlyCode()
        {
            if (!string.IsNullOrEmpty(currentTableCode))
            {
                PassNPlayPopup.CopyToClipboard(currentTableCode);
                CommonUtil.ShowToast("Room code copied: " + currentTableCode);
            }
        }

        private static string BuildShareMessage(string code)
            => $"Play Rox Ludo with me!\nRoom Code: {code}\nPlay at roxludo.com";

        private static bool IsValidPrivateTableCode(string code)
        {
            if (string.IsNullOrEmpty(code) || code.Length != 6)
                return false;

            for (int i = 0; i < code.Length; i++)
            {
                if (!char.IsDigit(code[i]))
                    return false;
            }

            return true;
        }

        // ── Attach a one-time click listener to the code Text so tapping it copies the code ──
        private void MakeCodeLabelClickable(Text codeText)
        {
            if (codeText == null) return;
            var trigger = codeText.GetComponent<EventTrigger>()
                          ?? codeText.gameObject.AddComponent<EventTrigger>();
            trigger.triggers.Clear();
            var entry = new EventTrigger.Entry { eventID = EventTriggerType.PointerClick };
            entry.callback.AddListener(_ => CopyOnlyCode());
            trigger.triggers.Add(entry);
        }

        private void ShowJoinLoader(bool show)
        {
            if (joinLoadingOverlay != null)
                joinLoadingOverlay.SetActive(show);
        }

        private void ConnectAndEmit(Action onConnected)
        {
            if (isConnecting) return;

            if (socket != null && socket.IsOpen)
            {
                onConnected?.Invoke();
                return;
            }

            isConnecting = true;
            string socketUrl = Configuration.BaseSocketUrl.TrimEnd('/') + "/socket.io/";
            var options = new SocketOptions { Reconnection = false, AutoConnect = true };
            socketManager = new SocketManager(new Uri(socketUrl), options);
            socket = socketManager.GetSocket(Configuration.PrivateTableSocketNamespace);

            socket.On(SocketIOEventTypes.Connect, () =>
            {
                isConnecting = false;
                Debug.Log("[PrivateTable] Socket connected");
                onConnected?.Invoke();
            });

            socket.On(SocketIOEventTypes.Error, (string err) =>
            {
                isConnecting = false;
                ShowJoinLoader(false);
                Debug.LogError("[PrivateTable] Socket error: " + err);
                CommonUtil.ShowStyledMessage("Connection failed. Please check your internet and try again.", "Connection Error", true);
                SetButtonsInteractable(true);
            });

            socket.On<string>("private-table-created", OnTableCreated);
            socket.On<string>("private-table-joined", OnTableJoined);
            socket.On<string>("private-table-player-joined", OnPlayerJoined);
            socket.On<string>("private-table-player-left", OnPlayerLeft);
            socket.On<string>("private-table-start", OnTableStart);
            socket.On<string>("private-table-left", OnTableLeftConfirmed);

            socket.On(SocketIOEventTypes.Disconnect, () =>
            {
                isConnecting = false;
                Debug.Log("[PrivateTable] Socket disconnected");
            });
        }

        private void OnTableCreated(string jsonStr)
        {
            SetButtonsInteractable(true);
            var resp = JsonConvert.DeserializeObject<PrivateTableResponse>(jsonStr);
            if (!resp.success)
            {
                HandleError(resp.error_code, resp.message ?? "Failed to create table.");
                return;
            }

            currentTableCode = resp.code;
            currentTableId = resp.table_id;

            PassNPlayPopup.CopyToClipboard(BuildShareMessage(resp.code));
            CommonUtil.ShowToast($"Table created! Share message copied (Code: {resp.code})");

            // Go directly to the game board — player waits there for others to join
            if (DashBoardManagerOffline.Instance != null)
                DashBoardManagerOffline.Instance.EnterPrivateTableBoard(
                    resp.code, resp.max_players, resp.table_id,
                    fee: resp.fee_amount, currentPlayers: 1, isCreator: true);
        }

        private void OnTableJoined(string jsonStr)
        {
            ShowJoinLoader(false);
            SetButtonsInteractable(true);
            var resp = JsonConvert.DeserializeObject<PrivateTableJoinResponse>(jsonStr);
            if (!resp.success)
            {
                HandleError(resp.error_code, resp.message ?? "Failed to join table.");
                return;
            }

            currentTableCode = resp.code;
            currentTableId = resp.table_id;
            currentMaxPlayers = resp.max_players;
            currentFee = resp.fee_amount;

            CommonUtil.ShowToast(resp.message ?? "Joined! Entering game room...");

            // Go directly to the game board — player waits there for others to join
            if (DashBoardManagerOffline.Instance != null)
                DashBoardManagerOffline.Instance.EnterPrivateTableBoard(
                    resp.code, resp.max_players, resp.table_id,
                    fee: resp.fee_amount, currentPlayers: resp.current_players, isCreator: false);
        }

        private void OnPlayerJoined(string jsonStr)
        {
            var payload = JsonConvert.DeserializeObject<PlayerCountPayload>(jsonStr);
            if (waitingPlayersText != null)
                waitingPlayersText.text = $"{payload.current_players}/{payload.max_players} Players";

            CommonUtil.ShowToast($"A player joined! ({payload.current_players}/{payload.max_players})");
        }

        private void OnPlayerLeft(string jsonStr)
        {
            var payload = JsonConvert.DeserializeObject<PlayerCountPayload>(jsonStr);
            if (waitingPlayersText != null)
                waitingPlayersText.text = $"{payload.current_players}/{payload.max_players} Players";

            CommonUtil.ShowToast($"A player left. ({payload.current_players}/{payload.max_players})");
        }

        private void OnTableLeftConfirmed(string jsonStr)
        {
            var resp = JsonConvert.DeserializeObject<SimpleResponse>(jsonStr);
            if (!resp.success)
            {
                CommonUtil.ShowToast(resp.message ?? "Could not leave table.");
                if (cancelWaitingButton != null)
                    cancelWaitingButton.interactable = true;
                return;
            }

            HideWaitingPanel();
            DisconnectSocket();
            currentTableCode = string.Empty;
            currentTableId = 0;

            CommonUtil.ShowToast(resp.message ?? "Left table. Balance refunded.");
        }

        private void OnTableStart(string jsonStr)
        {
            var payload = JsonConvert.DeserializeObject<TableStartPayload>(jsonStr);
            Debug.Log($"[PrivateTable] All players ready — game starting! Prize: {payload.winner_prize}");

            // Board is already open (entered on join/create). Signal the bridge to start the match.
            CommonUtil.ShowToast("All players joined — game starting!");

            if (DashBoardManagerOffline.Instance != null)
                DashBoardManagerOffline.Instance.OnPrivateTableAllPlayersReady(currentMaxPlayers, payload.table_id);
        }

        private void ShowWaitingPanel(string code, int current, int max, int fee)
        {
            if (privateTablePopup != null) privateTablePopup.SetActive(false);
            if (joinPanel != null) joinPanel.SetActive(false);
            if (waitingPanel != null) waitingPanel.SetActive(true);

            if (waitingCodeText != null)
            {
                waitingCodeText.text = code + "  📋";  // visual hint it's tappable
                MakeCodeLabelClickable(waitingCodeText);
            }
            if (waitingPlayersText != null) waitingPlayersText.text = $"{current}/{max} Players";

            int prizePool = fee * max;
            int winnerPrize = Mathf.RoundToInt(prizePool * 0.80f);
            if (waitingPrizeText != null)
                waitingPrizeText.text = fee > 0 ? $"Prize Pool: ₹{winnerPrize}" : "Free Table";

            if (cancelWaitingButton != null)
                cancelWaitingButton.interactable = true;

            PassNPlayPopup.CopyToClipboard(BuildShareMessage(code));
        }

        private void HideWaitingPanel()
        {
            if (waitingPanel != null) waitingPanel.SetActive(false);
        }

        private void HandleError(string errorCode, string message)
        {
            // Always reset UI state so user can retry
            ShowJoinLoader(false);
            SetButtonsInteractable(true);

            switch (errorCode)
            {
                case "insufficient_balance":
                    CommonUtil.ShowStyledMessage(message, "Insufficient Balance", true);
                    break;

                case "invalid_code":
                case "table_not_found":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "Room code is invalid. Please check and try again." : message,
                        "Invalid Code", true);
                    break;

                case "table_full":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "This room is already full." : message,
                        "Room Full", true);
                    break;

                case "table_expired":
                case "expired":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "This room code has expired. Ask the host to create a new table." : message,
                        "Code Expired", true);
                    break;

                case "table_completed":
                case "game_over":
                case "completed":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "This game has already finished. Please create a new table." : message,
                        "Game Over", true);
                    break;

                case "already_started":
                case "game_started":
                case "game_already_started":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "This game has already started. You cannot join now." : message,
                        "Game Started", true);
                    break;

                case "already_joined":
                case "already_in_table":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "You are already in this room." : message,
                        "Already Joined", true);
                    break;

                case "invalid_user":
                case "unauthenticated":
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "Session expired. Please log in again." : message,
                        "Session Expired", true);
                    break;

                default:
                    CommonUtil.ShowStyledMessage(
                        string.IsNullOrEmpty(message) ? "Something went wrong. Please try again." : message,
                        "Error", true);
                    break;
            }
        }

        private void SetButtonsInteractable(bool interactable)
        {
            if (createButton != null)      createButton.interactable = interactable;
            if (joinButton != null)        joinButton.interactable = interactable;
            if (confirmJoinButton != null) confirmJoinButton.interactable = interactable;
        }

        private void DisconnectSocket()
        {
            if (socketManager != null)
            {
                socketManager.Close();
                socketManager = null;
                socket = null;
            }
            isConnecting = false;
        }

        // ── Response Models ──────────────────────────────────────────────────

        [Serializable]
        private class PrivateTableResponse
        {
            public bool success;
            public string message;
            public string error_code;
            public string code;
            public int table_id;
            public int fee_amount;
            public int max_players;
            public string status;
        }

        [Serializable]
        private class PrivateTableJoinResponse
        {
            public bool success;
            public string message;
            public string error_code;
            public string code;
            public int table_id;
            public int fee_amount;
            public int max_players;
            public int current_players;
            public int prize_pool;
            public string status;
        }

        [Serializable]
        private class PlayerCountPayload
        {
            public int current_players;
            public int max_players;
        }

        [Serializable]
        private class TableStartPayload
        {
            public int table_id;
            public string code;
            public int prize_pool;
            public int winner_prize;
        }

        [Serializable]
        private class SimpleResponse
        {
            public bool success;
            public string message;
        }
    }
}
