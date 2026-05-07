using System;
using System.Collections;
using Best.HTTP;
using Best.SocketIO;
using Best.SocketIO.Events;
using Newtonsoft.Json;
using UnityEngine;
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
        public Button cancelWaitingButton;

        [Header("Join Panel")]
        public GameObject joinPanel;
        public InputField joinCodeInput;
        public Button confirmJoinButton;
        public Button cancelJoinButton;

        private SocketManager socketManager;
        private Socket socket;

        private string currentTableCode;
        private int currentTableId;
        private int currentMaxPlayers;
        private int currentFee;

        private void Awake()
        {
            if (Instance != null && Instance != this) { Destroy(gameObject); return; }
            Instance = this;
        }

        private void OnDestroy()
        {
            DisconnectSocket();
        }

        // ── Called by DashBoardManagerOffline when PLAY (Private Table) pressed ──
        public void OnCreatePressed(int maxPlayers)
        {
            int fee = 0;
            if (!string.IsNullOrEmpty(feeAmountInput?.text))
                int.TryParse(feeAmountInput.text, out fee);

            currentMaxPlayers = maxPlayers;
            currentFee = fee;

            ConnectAndEmit(() =>
            {
                socket.Emit("create-private-table", JsonConvert.SerializeObject(new
                {
                    user_id = int.Parse(Configuration.GetId()),
                    token = Configuration.GetToken(),
                    fee_amount = fee,
                    max_players = maxPlayers,
                }));
            });
        }

        // ── Called when user wants to join by code ──
        public void OnJoinPressed()
        {
            string code = joinCodeInput?.text?.Trim().ToUpper();
            if (string.IsNullOrEmpty(code) || code.Length != 6)
            {
                ShowError("Please enter a valid 6-character code.");
                return;
            }

            ConnectAndEmit(() =>
            {
                socket.Emit("join-private-table", JsonConvert.SerializeObject(new
                {
                    user_id = int.Parse(Configuration.GetId()),
                    token = Configuration.GetToken(),
                    code = code,
                }));
            });
        }

        private void ConnectAndEmit(Action onConnected)
        {
            if (socket != null && socket.IsOpen)
            {
                onConnected?.Invoke();
                return;
            }

            string socketUrl = Configuration.BaseSocketUrl + "/socket.io/";
            var options = new SocketOptions { Reconnection = false, AutoConnect = true };
            socketManager = new SocketManager(new Uri(socketUrl), options);
            socket = socketManager.GetSocket(Configuration.PrivateTableSocketNamespace);

            socket.On(SocketIOEventTypes.Connect, () =>
            {
                Debug.Log("[PrivateTable] Socket connected");
                onConnected?.Invoke();
            });

            socket.On<string>("private-table-created", OnTableCreated);
            socket.On<string>("private-table-joined", OnTableJoined);
            socket.On<string>("private-table-player-joined", OnPlayerJoined);
            socket.On<string>("private-table-start", OnTableStart);

            socket.On(SocketIOEventTypes.Disconnect, () =>
            {
                Debug.Log("[PrivateTable] Socket disconnected");
            });
        }

        private void OnTableCreated(string jsonStr)
        {
            var resp = JsonConvert.DeserializeObject<PrivateTableResponse>(jsonStr);
            if (!resp.success)
            {
                ShowError(resp.message ?? "Failed to create table.");
                return;
            }

            currentTableCode = resp.code;
            currentTableId = resp.table_id;

            ShowWaitingPanel(resp.code, 1, resp.max_players, resp.fee_amount);
        }

        private void OnTableJoined(string jsonStr)
        {
            var resp = JsonConvert.DeserializeObject<PrivateTableJoinResponse>(jsonStr);
            if (!resp.success)
            {
                ShowError(resp.message ?? "Failed to join table.");
                return;
            }

            currentTableCode = resp.code;
            currentTableId = resp.table_id;

            ShowWaitingPanel(resp.code, resp.current_players, resp.max_players, resp.fee_amount);
        }

        private void OnPlayerJoined(string jsonStr)
        {
            var payload = JsonConvert.DeserializeObject<PlayerJoinedPayload>(jsonStr);
            if (waitingPlayersText != null)
                waitingPlayersText.text = $"{payload.current_players}/{payload.max_players} Players";
        }

        private void OnTableStart(string jsonStr)
        {
            var payload = JsonConvert.DeserializeObject<TableStartPayload>(jsonStr);
            Debug.Log($"[PrivateTable] Game starting! Prize: {payload.winner_prize}");

            HideWaitingPanel();

            // Hand off to existing Ludo game flow — same as Pass N Play
            if (DashBoardManagerOffline.Instance != null)
                DashBoardManagerOffline.Instance.StartPrivateTableMatch(currentMaxPlayers, payload.table_id);
        }

        private void ShowWaitingPanel(string code, int current, int max, int fee)
        {
            if (privateTablePopup != null) privateTablePopup.SetActive(false);
            if (joinPanel != null) joinPanel.SetActive(false);
            if (waitingPanel != null) waitingPanel.SetActive(true);

            if (waitingCodeText != null)    waitingCodeText.text = $"Code: {code}";
            if (waitingPlayersText != null) waitingPlayersText.text = $"{current}/{max} Players";

            int prizePool = fee * max;
            int winnerPrize = Mathf.RoundToInt(prizePool * 0.80f);
            if (waitingPrizeText != null)
                waitingPrizeText.text = fee > 0 ? $"Prize: {winnerPrize} coins" : "Free Table";

            GUIUtility.systemCopyBuffer = code;
            Debug.Log($"[PrivateTable] Code copied to clipboard: {code}");
        }

        private void HideWaitingPanel()
        {
            if (waitingPanel != null) waitingPanel.SetActive(false);
        }

        private void ShowError(string message)
        {
            Debug.LogWarning("[PrivateTable] " + message);
        }

        public void CancelWaiting()
        {
            DisconnectSocket();
            HideWaitingPanel();
        }

        private void DisconnectSocket()
        {
            if (socketManager != null)
            {
                socketManager.Close();
                socketManager = null;
                socket = null;
            }
        }

        // ── Response Models ──────────────────────────────────────────────────

        [Serializable]
        private class PrivateTableResponse
        {
            public bool success;
            public string message;
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
            public string code;
            public int table_id;
            public int fee_amount;
            public int max_players;
            public int current_players;
            public int prize_pool;
            public string status;
        }

        [Serializable]
        private class PlayerJoinedPayload
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
    }
}
