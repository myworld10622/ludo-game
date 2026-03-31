using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace AndroApps
{
    /// <summary>
    /// Patches the signup panel at runtime (no scene/prefab edits needed).
    ///
    /// Final layout inside InputFields (height expanded to 665px, centered):
    ///   y= 274   [👤 Enter Your Name          ]
    ///   y= 147   [✉  Enter your email address ]
    ///   y=  64   ────────── OR ──────────
    ///   y= -20   [📱 Enter Your Mobile Number ]
    ///   y=-147   [🔒 Enter Password            ]
    ///   y=-274   [🔗 Enter Referral Code       ]
    ///
    /// Also adjusts panel title (up) and Signup button (down) for breathing room.
    /// </summary>
    public class SignupTogglePatcher : MonoBehaviour
    {
        private AuthManager _auth;

        // Row geometry (from scene)
        private const float RowHeight = 117f;
        private const float RowWidth  = 791f;
        private const float RowX      = 6.4f;
        private const float RowStep   = 127f;   // 117 + 10 gap

        // Y positions — 5 rows centered, with OR separator taking 167px between email/mobile
        private const float Y_Name     =  274f;
        private const float Y_Email    =  147f;   // 274 - 127
        private const float Y_Or       =   64f;   // midpoint in the 167px gap
        private const float Y_Mobile   =  -20f;   // 147 - 167
        private const float Y_Password = -147f;   // -20 - 127
        private const float Y_Referral = -274f;   // -147 - 127

        // InputFields container: Panel=1008, new height=665 → offset = -(1008-665) = -343
        private const float ContainerSizeDeltaY = -343f;

        // ── Lifecycle ──────────────────────────────────────────────────────────

        public void Initialize(AuthManager auth)
        {
            _auth = auth;
            PatchSignupPanel();
        }

        private void Start()
        {
            if (_auth == null)
            {
                _auth = GetComponent<AuthManager>();
                if (_auth == null) return;
                PatchSignupPanel();
            }
        }

        // ADD THIS FUNCTION INSIDE CLASS (anywhere inside class)

private void SetInputFieldColor(TMP_InputField field)
{
    if (field == null) return;

    // User input text color
    if (field.textComponent != null)
        field.textComponent.color = Color.white;

    // Placeholder color
    if (field.placeholder != null)
    {
        var ph = field.placeholder.GetComponent<TMP_Text>();
        if (ph != null)
            ph.color = new Color(1f, 1f, 1f, 0.5f);
    }
}

        // ── Patch ──────────────────────────────────────────────────────────────

        private void PatchSignupPanel()
        {
            var detail = _auth.SignUpDetail;
            if (detail == null || detail.MobileInputfield == null) return;

            Transform mobileRow  = detail.MobileInputfield.transform;
            Transform formLayout = mobileRow.parent;
            if (formLayout == null) { Debug.LogWarning("[SignupTogglePatcher] formLayout null."); return; }

            // ── 0. Expand + re-center InputFields container ───────────────────
            var containerRt = formLayout.GetComponent<RectTransform>();
            if (containerRt != null)
            {
                containerRt.sizeDelta        = new Vector2(containerRt.sizeDelta.x, ContainerSizeDeltaY);
                containerRt.anchoredPosition = new Vector2(containerRt.anchoredPosition.x, 0f);
            }

            // ── 1. Name row — keep visible, move to Y_Name ────────────────────
            if (detail.NameInputfield != null)
            {
                detail.NameInputfield.gameObject.SetActive(true);
                SetRowRect(detail.NameInputfield.transform, Y_Name);
                // Name row keeps its person icon (unchanged)
            }

            // ── 2. Email row — clone Name row, position at Y_Email ────────────
            Transform nameRow = detail.NameInputfield != null
                ? detail.NameInputfield.transform : null;

            GameObject emailRow = nameRow != null
                ? Instantiate(nameRow.gameObject, formLayout)
                : Instantiate(mobileRow.gameObject, formLayout);

            emailRow.name = "EmailInputRow";
            emailRow.SetActive(true);
            SetRowRect(emailRow.transform, Y_Email);

            // Configure TMP_InputField for email
            TMP_InputField emailField = emailRow.GetComponent<TMP_InputField>()
                                     ?? emailRow.GetComponentInChildren<TMP_InputField>();
            if (emailField != null)
            {
                emailField.contentType    = TMP_InputField.ContentType.EmailAddress;
                emailField.characterLimit = 0;
                emailField.text           = string.Empty;

                if (emailField.placeholder != null)
                {
                    var ph = emailField.placeholder.GetComponent<TMP_Text>();
                    if (ph != null) ph.text = "Enter your email address";
                }
            }

            // Replace person icon → email icon in cloned row
            ReplaceIconWithEmoji(emailRow.transform, "✉");

            // ── 3. OR separator between email and mobile ──────────────────────
            BuildOrLabel(formLayout, Y_Or);

            // ── 4. Mobile row — hide flag, full-width, phone icon, Y_Mobile ──
            var flagBox = mobileRow.Find("flag-box");
            if (flagBox != null)
                flagBox.gameObject.SetActive(false);

            SetRowRect(mobileRow, Y_Mobile);

            // Fix placeholder if a previous pass changed it
            if (detail.MobileInputfield.placeholder != null)
            {
                var mph = detail.MobileInputfield.placeholder.GetComponent<TMP_Text>();
                if (mph != null && mph.text.ToLower().Contains("email"))
                    mph.text = "Enter Your Mobile Number";
            }

            // Add phone icon (flag-box is hidden; add fresh icon)
            AddLeftEmoji(mobileRow, "📱");

            // ── 5. Reposition Password & Referral ─────────────────────────────
            if (detail.PasswordInputfield != null)
                SetRowRect(detail.PasswordInputfield.transform, Y_Password);

            if (detail.ReferralCodeInputfield != null)
                SetRowRect(detail.ReferralCodeInputfield.transform, Y_Referral);

            // ── 6. Wire email ref ─────────────────────────────────────────────
            detail.EmailInputfield = emailField;

            // ── 7. Adjust panel title (up) + Signup button (down) ────────────
            AdjustPanelSpacing(formLayout.parent, containerRt);

            Debug.Log("[SignupTogglePatcher] Patch complete.");

            // ── 8. Set input field colors (WHITE TEXT FIX) ─────────────

SetInputFieldColor(detail.NameInputfield);
SetInputFieldColor(emailField);
SetInputFieldColor(detail.MobileInputfield);
SetInputFieldColor(detail.PasswordInputfield);
SetInputFieldColor(detail.ReferralCodeInputfield);
        }

        // ── Icon helpers ───────────────────────────────────────────────────────

        /// Finds "profile-icon" child, hides its Image, overlays an emoji Text.
        private static void ReplaceIconWithEmoji(Transform row, string emoji)
        {
            var iconTf = row.Find("profile-icon");
            if (iconTf == null) return;

            // Hide existing Image sprite
            var img = iconTf.GetComponent<Image>();
            if (img != null) img.color = Color.clear;

            AddEmojiText(iconTf, emoji, 52);
        }

        /// Adds a new icon child to the left side of a row (for rows without an existing icon).
        private static void AddLeftEmoji(Transform row, string emoji)
        {
            // Avoid duplicates on re-patch
            if (row.Find("_EmojiIcon") != null) return;

            var go = new GameObject("_EmojiIcon",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            go.transform.SetParent(row, false);

            var rt = go.GetComponent<RectTransform>();
            rt.anchorMin        = new Vector2(0f, 0.5f);
            rt.anchorMax        = new Vector2(0f, 0.5f);
            rt.pivot            = new Vector2(0f, 0.5f);
            rt.anchoredPosition = new Vector2(20f, 0f);
            rt.sizeDelta        = new Vector2(65f, 65f);

            var t = go.GetComponent<Text>();
            t.text      = emoji;
            t.fontSize  = 52;
            t.alignment = TextAnchor.MiddleCenter;
            t.color     = Color.white;
            t.font      = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        }

        /// Adds an emoji Text child to an existing transform.
        private static void AddEmojiText(Transform parent, string emoji, int size)
        {
            if (parent.Find("_Emoji") != null) return;

            var go = new GameObject("_Emoji",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            go.transform.SetParent(parent, false);

            var rt = go.GetComponent<RectTransform>();
            rt.anchorMin = Vector2.zero;
            rt.anchorMax = Vector2.one;
            rt.offsetMin = rt.offsetMax = Vector2.zero;

            var t = go.GetComponent<Text>();
            t.text      = emoji;
            t.fontSize  = size;
            t.alignment = TextAnchor.MiddleCenter;
            t.color     = Color.white;
            t.font      = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        }

        // ── OR separator ───────────────────────────────────────────────────────

        private static void BuildOrLabel(Transform parent, float y)
        {
            if (parent.Find("_OrSeparator") != null) return;

            var row = new GameObject("_OrSeparator",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            row.transform.SetParent(parent, false);

            var hl = row.GetComponent<HorizontalLayoutGroup>();
            hl.spacing              = 14f;
            hl.childControlWidth    = true;
            hl.childControlHeight   = true;
            hl.childForceExpandWidth  = true;
            hl.childForceExpandHeight = false;
            hl.childAlignment       = TextAnchor.MiddleCenter;

            var le = row.GetComponent<LayoutElement>();
            le.preferredHeight = 32f;

            var rt = row.GetComponent<RectTransform>();
            rt.anchorMin        = new Vector2(0.5f, 0.5f);
            rt.anchorMax        = new Vector2(0.5f, 0.5f);
            rt.pivot            = new Vector2(0.5f, 0.5f);
            rt.sizeDelta        = new Vector2(RowWidth, 32f);
            rt.anchoredPosition = new Vector2(RowX, y);

            var lineColor = new Color32(180, 140, 80, 180);
            var textColor = new Color32(255, 200, 100, 230);

            BuildLine(row.transform, lineColor);
            BuildOrText(row.transform, "OR", textColor);
            BuildLine(row.transform, lineColor);
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

        private static void BuildOrText(Transform parent, string label, Color32 color)
        {
            var go = new GameObject("OrLabel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            var t = go.GetComponent<Text>();
            t.text      = label;
            t.fontSize  = 28;
            t.fontStyle = FontStyle.Bold;
            t.color     = color;
            t.alignment = TextAnchor.MiddleCenter;
            t.font      = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            var le = go.GetComponent<LayoutElement>();
            le.preferredWidth = 70f;
            le.flexibleWidth  = 0f;
        }

        // ── Row rect helper ────────────────────────────────────────────────────

        private static void SetRowRect(Transform t, float y)
        {
            var rt = t.GetComponent<RectTransform>();
            if (rt == null) return;
            rt.anchorMin        = new Vector2(0.5f, 0.5f);
            rt.anchorMax        = new Vector2(0.5f, 0.5f);
            rt.pivot            = new Vector2(0.5f, 0.5f);
            rt.sizeDelta        = new Vector2(RowWidth, RowHeight);
            rt.anchoredPosition = new Vector2(RowX, y);
        }

        // ── Panel spacing: title up, button down ──────────────────────────────

        private static void AdjustPanelSpacing(Transform panel, RectTransform skipRt)
        {
            if (panel == null) return;
            foreach (Transform child in panel)
            {
                var rt = child.GetComponent<RectTransform>();
                if (rt == null || rt == skipRt) continue;

                // "Signup" title — anchored to top (anchorMin.y ≈ 1)
                if (Mathf.Approximately(rt.anchorMin.y, 1f))
                {
                    rt.anchoredPosition = new Vector2(rt.anchoredPosition.x, 10f);
                    continue;
                }

                // Bottom-anchored elements (button & "Already Have Account")
                if (Mathf.Approximately(rt.anchorMin.y, 0f))
{
    if (rt.name.Contains("Signup"))
    {
        // Signup button
        rt.anchoredPosition = new Vector2(rt.anchoredPosition.x, 35f);
    }
    else if (rt.name.Contains("Already"))
    {
        // Already have account
        rt.anchoredPosition = new Vector2(rt.anchoredPosition.x, -35f);
    }
}
            }
        }
    }
}
