using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    /// <summary>
    /// Pure-code identity UX: no new scene objects needed.
    /// Attach this to any persistent GameObject in the Ludo scene (e.g., the LudoManager root).
    /// Hooks are called from LudoV2MatchmakingBridge and LudoNumberGsNewOffline.
    /// </summary>
    public class PlayerIdentityUIManager : MonoBehaviour
    {
        public static PlayerIdentityUIManager Instance { get; private set; }

        private LudoNumberGsNewOffline _gsNew;
        private int _localSeat = -1;

        // Runtime-created "YOU" label and "YOUR TURN / OPP TURN" banner per player slot
        private TextMeshProUGUI[] _youLabels;
        private TextMeshProUGUI[] _turnBanners;

        private static readonly Color YouLabelColor   = new Color(1f, 0.85f, 0f, 1f);   // gold
        private static readonly Color YourTurnColor   = new Color(0.2f, 1f, 0.2f, 1f);  // bright green
        private static readonly Color OppTurnColor    = new Color(1f, 0.35f, 0.35f, 1f); // soft red
        private static readonly Color DiceDimColor    = new Color(0.45f, 0.45f, 0.45f, 0.55f);
        private static readonly Color DiceBrightColor = Color.white;
        private static readonly Color ProfileGlowColor = new Color(1f, 0.84f, 0f, 1f);  // gold border

        void Awake()
        {
            Instance = this;
        }

        private LudoNumberGsNewOffline GsNew =>
            _gsNew != null ? _gsNew : (_gsNew = FindObjectOfType<LudoNumberGsNewOffline>());

        // ── called by LudoV2MatchmakingBridge after seat is confirmed ────────────
        public void SetLocalSeat(int seatIndex)
        {
            _localSeat = seatIndex;
            // Defer one frame so player controls are fully initialised
            Invoke(nameof(ApplyLocalPlayerMark), 0.2f);
        }

        // ── called by LudoNumberGsNewOffline.UserTurnStart ───────────────────────
        public void RefreshTurnUX(int turnSeatIndex)
        {
            if (GsNew == null || GsNew.ludoNumberPlayerControl == null) return;

            for (int i = 0; i < GsNew.ludoNumberPlayerControl.Length; i++)
            {
                var ctrl = GsNew.ludoNumberPlayerControl[i];
                if (ctrl == null) continue;

                bool isActive = ctrl.playerInfoData.playerSeatIndex == turnSeatIndex;
                bool isLocal  = ctrl.playerInfoData.playerSeatIndex == _localSeat;

                // ── dice brightness ──────────────────────────────────────────────
                if (ctrl.ludoNumbersUserData?.dice != null)
                {
                    var img = ctrl.ludoNumbersUserData.dice.GetComponent<Image>();
                    if (img != null)
                        img.color = isActive ? DiceBrightColor : DiceDimColor;
                }

                // ── turn banner label ────────────────────────────────────────────
                if (_turnBanners != null && i < _turnBanners.Length && _turnBanners[i] != null)
                {
                    if (isActive)
                    {
                        string msg = isLocal ? "YOUR TURN" : "OPP TURN";
                        _turnBanners[i].text = msg;
                        _turnBanners[i].color = isLocal ? YourTurnColor : OppTurnColor;
                        _turnBanners[i].gameObject.SetActive(true);
                        _turnBanners[i].transform.DOKill();
                        _turnBanners[i].transform.localScale = Vector3.one * 0.6f;
                        _turnBanners[i].transform.DOScale(1f, 0.25f).SetEase(Ease.OutBack);
                    }
                    else
                    {
                        _turnBanners[i].gameObject.SetActive(false);
                    }
                }
            }
        }

        // ── sets up persistent visual marks for the local player ────────────────
        private void ApplyLocalPlayerMark()
        {
            if (GsNew == null || GsNew.ludoNumberPlayerControl == null) return;

            int n = GsNew.ludoNumberPlayerControl.Length;
            _youLabels   = new TextMeshProUGUI[n];
            _turnBanners = new TextMeshProUGUI[n];

            for (int i = 0; i < n; i++)
            {
                var ctrl = GsNew.ludoNumberPlayerControl[i];
                if (ctrl == null) continue;

                bool isLocal = ctrl.playerInfoData.playerSeatIndex == _localSeat;

                // ── "YOU" label above local player's profile ─────────────────────
                if (isLocal && ctrl.playerProfile != null)
                {
                    _youLabels[i] = CreateLabel("YouLabel", ctrl.playerProfile.transform,
                        "▶ YOU ◀", 14, YouLabelColor, new Vector2(0f, 52f));

                    // Gold outline on profile image
                    var outline = ctrl.playerProfile.GetComponent<Outline>()
                        ?? ctrl.playerProfile.gameObject.AddComponent<Outline>();
                    outline.effectColor   = ProfileGlowColor;
                    outline.effectDistance = new Vector2(4f, -4f);
                    outline.enabled = true;
                }

                // ── turn banner (shown dynamically on each turn) ──────────────────
                Transform anchor = ctrl.playerProfile != null
                    ? ctrl.playerProfile.transform
                    : ctrl.transform;
                _turnBanners[i] = CreateLabel("TurnBanner", anchor,
                    "", 12, YourTurnColor, new Vector2(0f, -52f));
                _turnBanners[i].gameObject.SetActive(false);
            }
        }

        // ── helpers ──────────────────────────────────────────────────────────────
        private static TextMeshProUGUI CreateLabel(string objName, Transform parent,
            string text, float fontSize, Color color, Vector2 anchoredPos)
        {
            var go = new GameObject(objName, typeof(RectTransform));
            go.transform.SetParent(parent, false);
            var rt = go.GetComponent<RectTransform>();
            rt.sizeDelta = new Vector2(120f, 30f);
            rt.anchoredPosition = anchoredPos;

            var tmp = go.AddComponent<TextMeshProUGUI>();
            tmp.text       = text;
            tmp.fontSize   = fontSize;
            tmp.color      = color;
            tmp.alignment  = TextAlignmentOptions.Center;
            tmp.fontStyle  = FontStyles.Bold;
            return tmp;
        }
    }
}
