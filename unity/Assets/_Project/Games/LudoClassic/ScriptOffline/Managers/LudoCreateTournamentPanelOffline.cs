using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    /// <summary>
    /// "Create Tournament" — redirects the user to the web panel.
    /// No form is shown in Unity. The user creates tournaments in their browser.
    /// URL: Configuration.Website + "login"
    /// </summary>
    public class LudoCreateTournamentPanelOffline : MonoBehaviour
    {
        private DashBoardManagerOffline dashboard;
        private GameObject panelRoot;
        private bool hasBuiltUi;
        private Font runtimeFont;

        private static readonly Color32 BgColor     = new Color32(20, 30,  50,  252);
        private static readonly Color32 CardColor   = new Color32(41, 52,  71,  255);
        private static readonly Color32 AccentBlue  = new Color32(70, 130, 240, 255);
        private static readonly Color32 AccentGreen = new Color32(39, 170, 90,  255);
        private static readonly Color32 MutedColor  = new Color32(170, 185, 210, 210);

        // ── Init ──────────────────────────────────────────────────────────────

        public void Initialize(DashBoardManagerOffline owner)
        {
            dashboard = owner;
            EnsureUi();
        }

        public void OpenPanel()
        {
            EnsureUi();
            // Refresh the URL label each time (user_id might not have been ready on first build)
            RefreshUrlLabel();
            panelRoot.transform.SetAsLastSibling();
            panelRoot.SetActive(true);
        }

        public void ClosePanel()
        {
            if (panelRoot != null) panelRoot.SetActive(false);
            dashboard?.OpenTournamentPanel();
        }

        // ── URL label (refreshed each open) ───────────────────────────────────

        private Text urlLabel;

        private void RefreshUrlLabel()
        {
            if (urlLabel == null) return;
            urlLabel.text = Configuration.Website + "login";
        }

        // ── UI Build ──────────────────────────────────────────────────────────

        private void EnsureUi()
        {
            if (hasBuiltUi) return;

            if (dashboard == null) dashboard = GetComponent<DashBoardManagerOffline>();

            Transform parent = dashboard?.dashBordPanal != null
                ? dashboard.dashBordPanal.transform
                : transform;

            // Full-screen backdrop
            panelRoot = new GameObject("CreateTournamentPanel",
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster), typeof(CanvasRenderer), typeof(Image));
            panelRoot.transform.SetParent(parent, false);
            var panelCanvas = panelRoot.GetComponent<Canvas>();
            panelCanvas.overrideSorting = true;
            panelCanvas.sortingOrder = 32010;
            panelRoot.GetComponent<Image>().color = BgColor;
            var pr = panelRoot.GetComponent<RectTransform>();
            pr.anchorMin = Vector2.zero; pr.anchorMax = Vector2.one;
            pr.offsetMin = pr.offsetMax = Vector2.zero;

            // ── Centered card ─────────────────────────────────────────────────
            var card = new GameObject("Card",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup));
            card.transform.SetParent(panelRoot.transform, false);
            card.GetComponent<Image>().color = CardColor;
            var cr = card.GetComponent<RectTransform>();
            cr.anchorMin        = new Vector2(0.04f, 0.12f);
            cr.anchorMax        = new Vector2(0.96f, 0.88f);
            cr.offsetMin        = cr.offsetMax = Vector2.zero;

            var vl = card.GetComponent<VerticalLayoutGroup>();
            vl.padding              = new RectOffset(32, 32, 36, 36);
            vl.spacing              = 20f;
            vl.childControlWidth    = true;
            vl.childControlHeight   = true;
            vl.childForceExpandWidth  = true;
            vl.childForceExpandHeight = false;

            // ── Globe icon ────────────────────────────────────────────────────
            var icon = MakeText(card.transform, "🌐", 90, FontStyle.Normal, Color.white);
            icon.alignment = TextAnchor.MiddleCenter;
            SetFlexHeight(icon.gameObject, 110f);

            // ── Heading ───────────────────────────────────────────────────────
            var heading = MakeText(card.transform,
                "Create Your Tournament on the Web",
                54, FontStyle.Bold, Color.white);
            heading.alignment = TextAnchor.MiddleCenter;
            heading.horizontalOverflow = HorizontalWrapMode.Wrap;
            SetFlexHeight(heading.gameObject, 120f);

            // ── Sub-text ──────────────────────────────────────────────────────
            var sub = MakeText(card.transform,
                "Use your personal tournament management panel in the browser.\nCreate, configure and monitor your tournament from any device.",
                36, FontStyle.Normal, MutedColor);
            sub.alignment   = TextAnchor.MiddleCenter;
            sub.horizontalOverflow = HorizontalWrapMode.Wrap;
            SetFlexHeight(sub.gameObject, 120f);

            // ── URL box ───────────────────────────────────────────────────────
            var urlBox = new GameObject("UrlBox",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup));
            urlBox.transform.SetParent(card.transform, false);
            urlBox.GetComponent<Image>().color = new Color32(28, 40, 62, 255);
            var ubVl = urlBox.GetComponent<VerticalLayoutGroup>();
            ubVl.padding              = new RectOffset(20, 20, 14, 14);
            ubVl.spacing              = 8f;
            ubVl.childControlWidth    = true;
            ubVl.childControlHeight   = true;
            ubVl.childForceExpandWidth  = true;
            ubVl.childForceExpandHeight = false;
            var urlBoxLE = urlBox.AddComponent<LayoutElement>();
            urlBoxLE.minHeight = 110f;

            var urlCaption = MakeText(urlBox.transform,
                "Your Panel URL", 30, FontStyle.Bold,
                new Color32(100, 160, 255, 255));
            urlCaption.alignment = TextAnchor.MiddleLeft;
            SetFlexHeight(urlCaption.gameObject, 40f);

            urlLabel = MakeText(urlBox.transform,
                Configuration.Website + "login",
                34, FontStyle.Normal, Color.white);
            urlLabel.alignment = TextAnchor.MiddleLeft;
            urlLabel.fontStyle  = FontStyle.Normal;
            SetFlexHeight(urlLabel.gameObject, 48f);

            // ── Login hint ────────────────────────────────────────────────────
            var loginHint = MakeText(card.transform,
                "Login using User ID, username, email address, or mobile number with your password.",
                34, FontStyle.Normal, MutedColor);
            loginHint.alignment   = TextAnchor.MiddleCenter;
            loginHint.horizontalOverflow = HorizontalWrapMode.Wrap;
            SetFlexHeight(loginHint.gameObject, 80f);

            // ── Email sent notice ─────────────────────────────────────────────
            var notice = MakeText(card.transform,
                "✓  Open the login page in your browser and sign in to access your panel.",
                32, FontStyle.Italic, new Color32(80, 200, 120, 220));
            notice.alignment = TextAnchor.MiddleCenter;
            notice.horizontalOverflow = HorizontalWrapMode.Wrap;
            SetFlexHeight(notice.gameObject, 60f);

            // ── Buttons row ───────────────────────────────────────────────────
            var btnRow = new GameObject("BtnRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup));
            btnRow.transform.SetParent(card.transform, false);
            var brHL = btnRow.GetComponent<HorizontalLayoutGroup>();
            brHL.spacing              = 16f;
            brHL.childControlWidth    = true;
            brHL.childControlHeight   = true;
            brHL.childForceExpandWidth  = false;
            brHL.childForceExpandHeight = false;
            var btnRowLE = btnRow.AddComponent<LayoutElement>();
            btnRowLE.minHeight = 80f;

            // Spacer left
            var sL = new GameObject("SpL", typeof(RectTransform), typeof(LayoutElement));
            sL.transform.SetParent(btnRow.transform, false);
            sL.GetComponent<LayoutElement>().flexibleWidth = 1f;

            // "Got it" close button
            var closeBtn = MakeButton(btnRow.transform, "Got it!", AccentGreen, 42);
            closeBtn.GetComponent<LayoutElement>().preferredWidth  = 360f;
            closeBtn.GetComponent<LayoutElement>().preferredHeight = 96f;
            closeBtn.onClick.AddListener(ClosePanel);

            // Spacer right
            var sR = new GameObject("SpR", typeof(RectTransform), typeof(LayoutElement));
            sR.transform.SetParent(btnRow.transform, false);
            sR.GetComponent<LayoutElement>().flexibleWidth = 1f;

            // ── X close (top-right) ───────────────────────────────────────────
            var xBtn = MakeButton(panelRoot.transform, "✕", new Color32(140, 36, 36, 220), 30);
            var xR = xBtn.GetComponent<RectTransform>();
            xR.anchorMin        = new Vector2(1f, 1f); xR.anchorMax = new Vector2(1f, 1f);
            xR.pivot            = new Vector2(1f, 1f);
            xR.anchoredPosition = new Vector2(-16f, -16f);
            xR.sizeDelta        = new Vector2(72f, 72f);
            xBtn.onClick.AddListener(ClosePanel);

            panelRoot.SetActive(false);
            hasBuiltUi = true;
        }

        // ── Helpers ───────────────────────────────────────────────────────────

        private Text MakeText(Transform parent, string text, int size,
            FontStyle style, Color color)
        {
            var go = new GameObject("T",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            go.transform.SetParent(parent, false);
            var t = go.GetComponent<Text>();
            t.font             = GetFont();
            t.text             = text;
            t.fontSize         = size;
            t.fontStyle        = style;
            t.color            = color;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            return t;
        }

        private static void SetFlexHeight(GameObject go, float preferred)
        {
            var le = go.GetComponent<LayoutElement>() ?? go.AddComponent<LayoutElement>();
            le.preferredHeight = preferred;
        }

        private Button MakeButton(Transform parent, string label, Color32 bgColor, int fontSize)
        {
            var go = new GameObject(label + "Btn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(Button), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            go.GetComponent<Image>().color = bgColor;
            var btn = go.GetComponent<Button>();
            btn.targetGraphic = go.GetComponent<Image>();

            var lbl = MakeText(go.transform, label, fontSize, FontStyle.Bold, Color.white);
            lbl.alignment = TextAnchor.MiddleCenter;
            var lr = lbl.GetComponent<RectTransform>();
            lr.anchorMin = Vector2.zero; lr.anchorMax = Vector2.one;
            lr.offsetMin = lr.offsetMax = Vector2.zero;

            return btn;
        }

        private Font GetFont()
        {
            return Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        }
    }
}
