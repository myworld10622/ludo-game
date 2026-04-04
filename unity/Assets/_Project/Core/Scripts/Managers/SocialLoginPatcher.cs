using UnityEngine;
using UnityEngine.UI;

namespace AndroApps
{
    /// <summary>
    /// Injects a social-login section (Google / Facebook / Instagram) into the
    /// existing login panel at runtime — no scene edits needed.
    ///
    /// Attach this MonoBehaviour to the same GameObject as AuthManager, or to any
    /// active GameObject in the LoginRegister scene. It auto-finds the login panel
    /// and appends the social-login strip below the main login area.
    ///
    /// Each button opens Configuration.Website + "auth/{provider}" in the device
    /// browser. The backend (Laravel Socialite) handles the OAuth flow and returns
    /// a token which the user can paste / the app can intercept via deep-link.
    /// </summary>
    public class SocialLoginPatcher : MonoBehaviour
    {
        private AuthManager _auth;
        private bool _patched;

        // ── Lifecycle ──────────────────────────────────────────────────────────

        /// Called by AuthManager if it holds a reference to this component.
        public void Initialize(AuthManager auth)
        {
            _auth = auth;
            Patch();
        }

        private void Start()
        {
            if (_auth != null) return;
            _auth = GetComponent<AuthManager>() ?? FindObjectOfType<AuthManager>();
            if (_auth == null) return;
            Patch();
        }

        // ── Main patch ─────────────────────────────────────────────────────────

        private void Patch()
        {
            if (_patched) return;
            // LoginPageRedesigner builds its own social buttons — skip injection
            if (_auth != null && _auth.GetComponent<LoginPageRedesigner>() != null) return;

            // Find the login panel (assigned in inspector on AuthManager)
            GameObject loginPanel = _auth?.loginpanel;

            if (loginPanel == null)
            {
                // Fallback: search by name in scene
                loginPanel = GameObject.Find("loginpanel")
                          ?? GameObject.Find("LoginPanel")
                          ?? GameObject.Find("Login Panel");
            }

            if (loginPanel == null)
            {
                Debug.LogWarning("[SocialLoginPatcher] Login panel not found.");
                return;
            }

            // Prevent double-injection
            if (loginPanel.transform.Find("_SocialSection") != null) return;

            BuildSocialSection(loginPanel.transform);
            _patched = true;
            Debug.Log("[SocialLoginPatcher] Social login section injected.");
        }

        // ── Build social login section ─────────────────────────────────────────

        private void BuildSocialSection(Transform loginPanelTransform)
        {
            Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");

            // ── Outer container — anchored to bottom of login panel ────────────
            var section = new GameObject("_SocialSection",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup));
            section.transform.SetParent(loginPanelTransform, false);

            section.GetComponent<Image>().color = new Color32(0, 0, 0, 0); // transparent

            var sectionVL = section.GetComponent<VerticalLayoutGroup>();
            sectionVL.padding              = new RectOffset(28, 28, 14, 28);
            sectionVL.spacing              = 18f;
            sectionVL.childControlWidth    = true;
            sectionVL.childControlHeight   = true;
            sectionVL.childForceExpandWidth  = true;
            sectionVL.childForceExpandHeight = false;
            sectionVL.childAlignment       = TextAnchor.UpperCenter;

            var sectionRt = section.GetComponent<RectTransform>();
            // Anchor to bottom-centre of parent, stretch full width
            sectionRt.anchorMin        = new Vector2(0f, 0f);
            sectionRt.anchorMax        = new Vector2(1f, 0f);
            sectionRt.pivot            = new Vector2(0.5f, 0f);
            sectionRt.anchoredPosition = new Vector2(0f, 8f);
            sectionRt.sizeDelta        = new Vector2(0f, 260f);

            // ── Divider row: ──── OR CONTINUE WITH ──── ───────────────────────
            BuildDividerRow(section.transform, font);

            // ── Social buttons row ─────────────────────────────────────────────
            var btnRow = new GameObject("SocialBtnRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            btnRow.transform.SetParent(section.transform, false);
            btnRow.GetComponent<LayoutElement>().preferredHeight = 120f;

            var hl = btnRow.GetComponent<HorizontalLayoutGroup>();
            hl.spacing              = 24f;
            hl.childControlWidth    = true;
            hl.childControlHeight   = true;
            hl.childForceExpandWidth  = true;
            hl.childForceExpandHeight = false;
            hl.childAlignment       = TextAnchor.MiddleCenter;

            // Google
            BuildSocialBtn(btnRow.transform, font,
                icon: "G",
                label: "Google",
                iconColor:  new Color32(255, 255, 255, 255),
                bgColor:    new Color32(219, 68,  55,  255),   // Google red
                rimColor:   new Color32(255, 120, 105, 255),
                url: Configuration.SocialGoogleUrl);

            // Facebook
            BuildSocialBtn(btnRow.transform, font,
                icon: "f",
                label: "Facebook",
                iconColor:  new Color32(255, 255, 255, 255),
                bgColor:    new Color32(24,  119, 242, 255),   // Facebook blue
                rimColor:   new Color32(80,  160, 255, 255),
                url: Configuration.SocialFacebookUrl);

            // Instagram
            BuildSocialBtn(btnRow.transform, font,
                icon: "♦",
                label: "Instagram",
                iconColor:  new Color32(255, 255, 255, 255),
                bgColor:    new Color32(193, 53,  132, 255),   // Instagram pink
                rimColor:   new Color32(240, 100, 175, 255),
                url: Configuration.SocialInstagramUrl);

            // ── Guest login hint ───────────────────────────────────────────────
            var guestHint = BuildLabel(section.transform, font,
                "— or continue as Guest —",
                24, FontStyle.Normal, new Color32(200, 170, 175, 180));
            guestHint.alignment = TextAnchor.MiddleCenter;
            var guestLE = guestHint.gameObject.AddComponent<LayoutElement>();
            guestLE.preferredHeight = 36f;
        }

        // ── Divider: ─── OR CONTINUE WITH ─── ────────────────────────────────

        private static void BuildDividerRow(Transform parent, Font font)
        {
            var row = new GameObject("Divider",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            row.transform.SetParent(parent, false);
            row.GetComponent<LayoutElement>().preferredHeight = 36f;

            var hl = row.GetComponent<HorizontalLayoutGroup>();
            hl.spacing              = 12f;
            hl.childControlWidth    = true;
            hl.childControlHeight   = true;
            hl.childForceExpandWidth  = true;
            hl.childForceExpandHeight = false;
            hl.childAlignment       = TextAnchor.MiddleCenter;

            BuildLine(row.transform, new Color32(160, 80, 90, 160));

            var lbl = BuildLabel(row.transform, font, "OR CONTINUE WITH",
                22, FontStyle.Bold, new Color32(255, 190, 80, 220));
            lbl.alignment = TextAnchor.MiddleCenter;
            var lblLE = lbl.gameObject.AddComponent<LayoutElement>();
            lblLE.preferredWidth  = 340f;
            lblLE.flexibleWidth   = 0f;

            BuildLine(row.transform, new Color32(160, 80, 90, 160));
        }

        private static void BuildLine(Transform parent, Color32 color)
        {
            var go = new GameObject("Line",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            go.GetComponent<Image>().color = color;
            var le = go.GetComponent<LayoutElement>();
            le.flexibleWidth   = 1f;
            le.preferredHeight = 2f;
            le.minHeight       = 2f;
        }

        // ── Individual social button ───────────────────────────────────────────

        private static void BuildSocialBtn(
            Transform parent, Font font,
            string icon, string label,
            Color32 iconColor, Color32 bgColor, Color32 rimColor,
            string url)
        {
            // Card
            var card = new GameObject(label + "Btn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(Button), typeof(VerticalLayoutGroup), typeof(LayoutElement), typeof(Outline));
            card.transform.SetParent(parent, false);

            var cardImg = card.GetComponent<Image>();
            cardImg.color = bgColor;
            cardImg.raycastTarget = true;

            var outline = card.GetComponent<Outline>();
            outline.effectColor    = rimColor;
            outline.effectDistance = new Vector2(0f, -3f);

            var cardLE = card.GetComponent<LayoutElement>();
            cardLE.preferredHeight = 120f;
            cardLE.minHeight       = 100f;

            var vl = card.GetComponent<VerticalLayoutGroup>();
            vl.padding              = new RectOffset(8, 8, 10, 10);
            vl.spacing              = 4f;
            vl.childControlWidth    = true;
            vl.childControlHeight   = true;
            vl.childForceExpandWidth  = true;
            vl.childForceExpandHeight = false;
            vl.childAlignment       = TextAnchor.MiddleCenter;

            var btn = card.GetComponent<Button>();
            btn.targetGraphic = cardImg;
            var cb = btn.colors;
            cb.normalColor      = bgColor;
            cb.highlightedColor = Lighten(bgColor, 25);
            cb.pressedColor     = Darken(bgColor,  25);
            btn.colors = cb;
            string capturedUrl = url;
            btn.onClick.AddListener(() => Application.OpenURL(capturedUrl));

            // Icon label (big letter / emoji)
            var iconTxt = BuildLabel(card.transform, font, icon, 52, FontStyle.Bold, iconColor);
            iconTxt.alignment = TextAnchor.MiddleCenter;
            var iconLE = iconTxt.gameObject.AddComponent<LayoutElement>();
            iconLE.preferredHeight = 60f;

            // Provider name
            var nameTxt = BuildLabel(card.transform, font, label, 26, FontStyle.Bold, Color.white);
            nameTxt.alignment = TextAnchor.MiddleCenter;
            var nameLE = nameTxt.gameObject.AddComponent<LayoutElement>();
            nameLE.preferredHeight = 34f;
        }

        // ── Text helper ────────────────────────────────────────────────────────

        private static Text BuildLabel(Transform parent, Font font, string text,
            int size, FontStyle style, Color color)
        {
            var go = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            go.transform.SetParent(parent, false);
            var t = go.GetComponent<Text>();
            t.font             = font;
            t.text             = text;
            t.fontSize         = size;
            t.fontStyle        = style;
            t.color            = color;
            t.alignment        = TextAnchor.MiddleCenter;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            t.raycastTarget    = false;
            return t;
        }

        // ── Color utilities ────────────────────────────────────────────────────

        private static Color32 Lighten(Color32 c, int amt) =>
            new Color32(
                (byte)Mathf.Clamp(c.r + amt, 0, 255),
                (byte)Mathf.Clamp(c.g + amt, 0, 255),
                (byte)Mathf.Clamp(c.b + amt, 0, 255), c.a);

        private static Color32 Darken(Color32 c, int amt) =>
            new Color32(
                (byte)Mathf.Clamp(c.r - amt, 0, 255),
                (byte)Mathf.Clamp(c.g - amt, 0, 255),
                (byte)Mathf.Clamp(c.b - amt, 0, 255), c.a);
    }
}
