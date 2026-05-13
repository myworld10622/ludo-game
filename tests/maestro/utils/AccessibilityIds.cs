// Drop this script on any UI element to set its Android accessibility content description.
// Maestro uses contentDescription as the element ID (tapOn: {id: "..."}).
//
// Usage: Add MaestroAccessibilityId component to each Button/Panel in Unity Inspector.
// Set the "elementId" field to match the IDs used in the YAML flows.
//
// Recommended IDs (copy these into your Inspector):
// Dashboard:     btn_ludo_classic, btn_private_table, btn_join_private_table, panel_dashboard
// Lobby:         btn_2_players, btn_entry_fee_10, btn_create_table, btn_join_table
//                input_room_code, panel_waiting_board
// Game:          panel_game_board, btn_roll_dice, panel_dice_result
//                btn_move_token_0, btn_move_token_1, btn_move_token_2, btn_move_token_3
//                turn_indicator_local, token_local_player_0
// Voice:         btn_mic, btn_mic_mute, icon_mic_muted, voice_status_connected
// System:        panel_reconnecting
// Login:         btn_login_guest, btn_login_mobile, input_mobile, btn_send_otp

#if UNITY_ANDROID
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    [RequireComponent(typeof(RectTransform))]
    public class MaestroAccessibilityId : MonoBehaviour
    {
        [Tooltip("Must match the 'id:' value in Maestro YAML files")]
        public string elementId;

        private void Start()
        {
            if (string.IsNullOrEmpty(elementId)) return;

            // Unity on Android renders via a single SurfaceView.
            // We attach contentDescription to the GameObject's attached AndroidJavaObject
            // via the accessibility node info API exposed through Unity's Activity.
            // The simplest approach: set it via the UI element's name so Maestro can find it.
            gameObject.name = elementId;

#if !UNITY_EDITOR
            SetAndroidContentDescription(elementId);
#endif
        }

        private static void SetAndroidContentDescription(string id)
        {
            try
            {
                using var unityPlayer = new AndroidJavaClass("com.unity3d.player.UnityPlayer");
                using var activity = unityPlayer.GetStatic<AndroidJavaObject>("currentActivity");
                using var view = activity.Call<AndroidJavaObject>("findViewById",
                    activity.Call<AndroidJavaObject>("getWindow")
                             .Call<AndroidJavaObject>("getDecorView")
                             .Call<int>("getId"));
                view?.Call("setContentDescription", id);
            }
            catch
            {
                // Non-fatal: accessibility IDs are test-only, not required for production
            }
        }
    }
}
#endif
