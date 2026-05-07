using System;
using Best.SocketIO;
using Best.SocketIO.Events;
using Newtonsoft.Json;
using UnityEngine;

namespace LudoClassicOffline
{
    public static class PrivateTableSocketHandler
    {
        private static SocketManager socketManager;
        private static Socket socket;
        private static string waitingCode;
        private static int waitingMaxPlayers;
        private static DashBoardManagerOffline dashboard;

        public static void StartWaiting(string code, int maxPlayers, DashBoardManagerOffline db)
        {
            waitingCode = code;
            waitingMaxPlayers = maxPlayers;
            dashboard = db;

            if (socket != null && socket.IsOpen)
            {
                JoinRoom(code);
                return;
            }

            string socketUrl = Configuration.BaseSocketUrl + "/socket.io/";
            var options = new SocketOptions { Reconnection = false, AutoConnect = true };
            socketManager = new SocketManager(new Uri(socketUrl), options);
            socket = socketManager.GetSocket(Configuration.PrivateTableSocketNamespace);

            socket.On(SocketIOEventTypes.Connect, () =>
            {
                Debug.Log("[PrivateTable] Socket connected, joining room: " + code);
                JoinRoom(code);
            });

            socket.On<string>("private-table-player-joined", OnPlayerJoined);
            socket.On<string>("private-table-start", OnTableStart);
        }

        private static void JoinRoom(string code)
        {
            socket.Emit("get-private-table-info", JsonConvert.SerializeObject(new { code }));
        }

        private static void OnPlayerJoined(string jsonStr)
        {
            try
            {
                var payload = JsonConvert.DeserializeObject<PlayerJoinedPayload>(jsonStr);
                Debug.Log($"[PrivateTable] Players: {payload.current_players}/{payload.max_players}");
                if (dashboard != null)
                    dashboard.OnPrivateTablePlayerJoined(payload.current_players, payload.max_players);
            }
            catch (Exception e) { Debug.LogError("[PrivateTable] OnPlayerJoined parse error: " + e.Message); }
        }

        private static void OnTableStart(string jsonStr)
        {
            try
            {
                var payload = JsonConvert.DeserializeObject<TableStartPayload>(jsonStr);
                Debug.Log($"[PrivateTable] All players joined! TableId: {payload.table_id}");

                if (dashboard != null)
                    dashboard.OnPrivateTableAllPlayersReady(waitingMaxPlayers, payload.table_id);
            }
            catch (Exception e) { Debug.LogError("[PrivateTable] OnTableStart parse error: " + e.Message); }
        }

        public static void Disconnect()
        {
            if (socketManager != null)
            {
                socketManager.Close();
                socketManager = null;
                socket = null;
            }
        }

        [Serializable] private class PlayerJoinedPayload { public int current_players; public int max_players; }
        [Serializable] private class TableStartPayload { public int table_id; public string code; public int prize_pool; public int winner_prize; }
    }
}
