using DG.Tweening;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    /// <summary>
    /// Pure-code identity UX using only existing scene components (no new Text/TMP — avoids missing font issue).
    /// Attach automatically via GameManagerOffline.Awake.
    /// </summary>
    public class PlayerIdentityUIManager : MonoBehaviour
    {
        public static PlayerIdentityUIManager Instance { get; private set; }

        private LudoNumberGsNewOffline _gsNew;
        private int _localSeat = -1;
        private bool _marksApplied;

        // Gold tint for local player's profile image
        private static readonly Color LocalProfileTint  = new Color(1f, 0.85f, 0.3f, 1f);
        // Bright white for active-turn dice; dim gray for inactive
        private static readonly Color DiceActiveColor   = new Color(1f, 1f, 1f, 1f);
        private static readonly Color DiceInactiveColor = new Color(0.4f, 0.4f, 0.4f, 0.5f);
        // Tint colors for turnProfileBlink per role
        private static readonly Color YourTurnBlinkColor = new Color(0.2f, 1f, 0.2f, 1f);   // green
        private static readonly Color OppTurnBlinkColor  = new Color(1f, 0.35f, 0.35f, 1f);  // red

        void Awake() { Instance = this; }

        private LudoNumberGsNewOffline GsNew =>
            _gsNew != null ? _gsNew : (_gsNew = FindObjectOfType<LudoNumberGsNewOffline>());

        // ── Called by LudoV2MatchmakingBridge after seat confirmed ────────────
        public void SetLocalSeat(int seatIndex)
        {
            _localSeat = seatIndex;
            _marksApplied = false;
            // Try immediately; retry after 0.5 s if controllers not ready yet
            ApplyLocalPlayerMark();
            Invoke(nameof(ApplyLocalPlayerMark), 0.5f);
            Invoke(nameof(ApplyLocalPlayerMark), 1.5f);
        }

        // ── Called by LudoNumberGsNewOffline.UserTurnStart ───────────────────
        public void RefreshTurnUX(int turnSeatIndex)
        {
            if (GsNew == null || GsNew.ludoNumberPlayerControl == null) return;

            // Ensure marks applied in case SetLocalSeat was called before controllers loaded
            if (!_marksApplied) ApplyLocalPlayerMark();

            for (int i = 0; i < GsNew.ludoNumberPlayerControl.Length; i++)
            {
                var ctrl = GsNew.ludoNumberPlayerControl[i];
                if (ctrl == null) continue;

                bool isActiveTurn = ctrl.playerInfoData.playerSeatIndex == turnSeatIndex;
                bool isLocal      = ctrl.playerInfoData.playerSeatIndex == _localSeat;

                // ── Dice brightness ──────────────────────────────────────────
                SetDiceColor(ctrl, isActiveTurn ? DiceActiveColor : DiceInactiveColor);

                // ── turnProfileBlink color: green for local, red for opponent ─
                if (ctrl.ludoNumbersUserData?.turnProfileBlink != null)
                {
                    var blinkImg = ctrl.ludoNumbersUserData.turnProfileBlink.GetComponent<Image>();
                    if (blinkImg != null)
                        blinkImg.color = isLocal ? YourTurnBlinkColor : OppTurnBlinkColor;
                }
            }
        }

        // ── Mark the local player's profile with gold tint ───────────────────
        private void ApplyLocalPlayerMark()
        {
            if (_localSeat < 0 || GsNew == null || GsNew.ludoNumberPlayerControl == null) return;

            bool anyFound = false;
            for (int i = 0; i < GsNew.ludoNumberPlayerControl.Length; i++)
            {
                var ctrl = GsNew.ludoNumberPlayerControl[i];
                if (ctrl == null) continue;

                bool isLocal = ctrl.playerInfoData.playerSeatIndex == _localSeat;
                if (!isLocal) continue;

                anyFound = true;

                // Tint profile image gold so local player is visually distinct
                if (ctrl.playerProfile != null)
                    ctrl.playerProfile.color = LocalProfileTint;

                // Also tint userImage inside userData if that exists
                if (ctrl.ludoNumbersUserData?.userImage != null)
                    ctrl.ludoNumbersUserData.userImage.color = LocalProfileTint;

                // Punch-scale the profile once to draw attention on entry
                Transform punchTarget = ctrl.playerProfile != null
                    ? ctrl.playerProfile.transform
                    : (ctrl.ludoNumbersUserData?.userImage != null
                        ? ctrl.ludoNumbersUserData.userImage.transform
                        : null);
                if (punchTarget != null)
                {
                    punchTarget.DOKill();
                    punchTarget.DOPunchScale(new Vector3(0.25f, 0.25f, 0f), 0.5f, 5, 0.3f);
                }

                Debug.Log($"[IdentityUX] Local player mark applied to slot {i} seat={_localSeat}");
                _marksApplied = true;
            }

            if (anyFound) CancelInvoke(nameof(ApplyLocalPlayerMark));
        }

        private static void SetDiceColor(LudoNumberPlayerControlOffline ctrl, Color color)
        {
            if (ctrl?.ludoNumbersUserData?.dice == null) return;
            // Try Image on the dice root
            var img = ctrl.ludoNumbersUserData.dice.GetComponent<Image>();
            if (img == null)
                img = ctrl.ludoNumbersUserData.dice.GetComponentInChildren<Image>();
            if (img != null)
                img.color = color;
        }
    }
}
