// Attach to a persistent GameObject (e.g. GameManager) to register ALL accessibility IDs
// at startup. More reliable than per-component approach for Unity Canvas UI.
//
// For each Button/Panel you want testable, add an entry to the map below.

using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{
    public class UnityAccessibilityBridge : MonoBehaviour
    {
        // key = Unity GameObject name, value = Maestro accessibility ID
        private static readonly Dictionary<string, string> IdMap = new()
        {
            // Dashboard
            { "LudoClassicButton",      "btn_ludo_classic" },
            { "PrivateTableButton",     "btn_private_table" },
            { "JoinPrivateTableButton", "btn_join_private_table" },
            { "DashboardPanel",         "panel_dashboard" },

            // Lobby / waiting
            { "TwoPlayerButton",        "btn_2_players" },
            { "EntryFee10Button",       "btn_entry_fee_10" },
            { "CreateTableButton",      "btn_create_table" },
            { "JoinTableButton",        "btn_join_table" },
            { "RoomCodeInput",          "input_room_code" },
            { "WaitingBoardPanel",      "panel_waiting_board" },

            // Game board
            { "GameBoardPanel",         "panel_game_board" },
            { "RollDiceButton",         "btn_roll_dice" },
            { "DiceResultPanel",        "panel_dice_result" },
            { "MoveToken0Button",       "btn_move_token_0" },
            { "MoveToken1Button",       "btn_move_token_1" },
            { "MoveToken2Button",       "btn_move_token_2" },
            { "MoveToken3Button",       "btn_move_token_3" },
            { "TurnIndicatorLocal",     "turn_indicator_local" },
            { "LocalPlayerToken0",      "token_local_player_0" },

            // Voice
            { "MicButton",              "btn_mic" },
            { "MicMuteButton",          "btn_mic_mute" },
            { "MicMutedIcon",           "icon_mic_muted" },
            { "VoiceStatusConnected",   "voice_status_connected" },

            // System
            { "ReconnectingPanel",      "panel_reconnecting" },
        };

        private void Awake()
        {
#if UNITY_ANDROID && !UNITY_EDITOR
            foreach (var (goName, accessId) in IdMap)
            {
                var go = GameObject.Find(goName);
                if (go == null) continue;
                go.AddComponent<MaestroAccessibilityId>().elementId = accessId;
            }
#endif
        }
    }
}
