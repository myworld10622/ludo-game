using UnityEngine;
using UnityEngine.UI;
using System.Collections.Generic;
using System.Linq;

namespace AndroApps
{
    /// <summary>
    /// Overlays the existing login panel with a Zynga-Poker-style landing screen.
    ///
    /// State 0 – Landing  : Model image (left) + choice buttons (right)
    /// State 1 – Login    : centered responsive login form
    /// State 2 – Signup   : centered responsive signup form
    ///
    /// Attach via AuthManager.Start() with AddComponent + Initialize(this).
    /// Assign 'modelSprite' (Model.png) via Inspector on the AuthManager GameObject.
    /// </summary>
    public class LoginPageRedesigner : MonoBehaviour
    {
        [Header("Assign Model.png sprite here")]
        public Sprite modelSprite;

        private AuthManager    _auth;
        private GameObject     _overlay;
        private GameObject     _landingPanel;
        private GameObject     _loginFormPanel;
        private GameObject     _signupFormPanel;
        private InputField     _idInput;
        private InputField     _pwInput;
        private InputField     _signupMobileOrEmailInput;
        private InputField     _signupPasswordInput;
        private InputField     _signupReferralInput;
        private Toggle         _signupTermsToggle;
        private GameObject     _sceneLogoObject;
        private Sprite         _sceneLogoSprite;
        private bool           _built;
        private bool           _buildSucceeded;
        private readonly List<GameObject> _disabledOriginalChildren = new List<GameObject>();
        private const string OverlayName = "_LoginRedesign";

        // ── App palette ────────────────────────────────────────────────────────
        private static readonly Color32 BgColor     = new Color32( 20,   4,   8,  70); // light dim so the bg image stays visible
        private static readonly Color32 CardColor   = new Color32( 78,   8,  24, 150); // 50% transparent red modal
        private static readonly Color32 AccentGold  = new Color32(218, 165,  32, 255);
        private static readonly Color32 AccentRed   = new Color32(180,  35,  55, 255);
        private static readonly Color32 DarkRed     = new Color32(118,  18,  28, 255);
        private static readonly Color32 MidRed      = new Color32( 80,  12,  22, 255);
        private static readonly Color32 InputBg     = new Color32( 70,  14,  24, 255);
        private static readonly Color32 MutedText   = new Color32(210, 180, 185, 200);

        // ── Lifecycle ──────────────────────────────────────────────────────────

        public void Initialize(AuthManager auth)
        {
            _auth = auth;
            Build();
        }

#if UNITY_EDITOR
        public void RebuildPersistentUiInEditor(AuthManager auth = null)
        {
            if (Application.isPlaying)
            {
                return;
            }

            _auth = auth != null ? auth : (_auth != null ? _auth : GetComponent<AuthManager>() ?? FindObjectOfType<AuthManager>());

            GameObject loginRoot = _auth != null ? _auth.loginpanel : null;
            Canvas rootCanvas = ResolveRootCanvas(loginRoot);
            Transform existingOverlay = rootCanvas != null ? FindDirectChild(rootCanvas.transform, OverlayName) : null;
            if (existingOverlay != null)
            {
                DestroyImmediate(existingOverlay.gameObject);
            }

            if (_overlay != null)
            {
                DestroyImmediate(_overlay);
            }

            _overlay = null;
            _landingPanel = null;
            _loginFormPanel = null;
            _signupFormPanel = null;
            _idInput = null;
            _pwInput = null;
            _signupMobileOrEmailInput = null;
            _signupPasswordInput = null;
            _signupReferralInput = null;
            _signupTermsToggle = null;
            _built = false;
            _buildSucceeded = false;

            Build();
        }
#endif

        public bool BuildSucceeded => _buildSucceeded && _overlay != null;

        private void Start()
        {
            if (_auth != null) return;
            _auth = GetComponent<AuthManager>() ?? FindObjectOfType<AuthManager>();
            if (_auth == null) return;
            Build();
        }

        // ── Build ──────────────────────────────────────────────────────────────

        private void Build()
        {
            if (_built) return;

            try
            {
                GameObject loginRoot = _auth != null ? _auth.loginpanel : null;
                Canvas rootCanvas = ResolveRootCanvas(loginRoot);
                if (loginRoot == null || rootCanvas == null)
                {
                    throw new MissingReferenceException("Login root or root canvas is missing.");
                }

                if (TryBindExistingOverlay(rootCanvas.transform))
                {
                    CacheSceneLogo(loginRoot.transform.root);
                    loginRoot.SetActive(false);
                    SetSceneLogoVisible(false);
                    if (_auth.LogInDetail?.LogInPnl != null)
                    {
                        _auth.LogInDetail.LogInPnl.SetActive(false);
                    }

                    ApplyResponsiveAuthLayout();
                    ShowLanding();
                    _buildSucceeded = true;
                    _built = true;
                    return;
                }

                CacheSceneLogo(loginRoot.transform.root);
                loginRoot.SetActive(false);
                SetSceneLogoVisible(false);
                if (_auth.LogInDetail?.LogInPnl != null)
                {
                    _auth.LogInDetail.LogInPnl.SetActive(false);
                }

                _overlay = new GameObject(OverlayName,
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                Transform parent = rootCanvas.transform;
                _overlay.transform.SetParent(parent, false);

                _overlay.GetComponent<Image>().color = BgColor;

                var or2 = _overlay.GetComponent<RectTransform>();
                or2.anchorMin = Vector2.zero;
                or2.anchorMax = Vector2.one;
                or2.offsetMin = or2.offsetMax = Vector2.zero;
                or2.SetAsLastSibling();

                BuildLandingPanel();
                BuildLoginFormPanel();
                BuildSignupFormPanel();

                ApplyResponsiveAuthLayout();
                ShowLanding();
                _buildSucceeded = true;
                _built = true;
            }
            catch (System.Exception ex)
            {
                Debug.LogError("[LoginPageRedesigner] Build failed: " + ex);
                _buildSucceeded = false;
                _built = false;

                if (_overlay != null)
                {
                    Destroy(_overlay);
                    _overlay = null;
                }

                if (_auth != null)
                {
                    SetSceneLogoVisible(true);
                    if (_auth.loginpanel != null)
                        _auth.loginpanel.SetActive(true);
                    if (_auth.LogInDetail != null && _auth.LogInDetail.LogInPnl != null)
                        _auth.LogInDetail.LogInPnl.SetActive(true);
                    if (_auth.SignUpDetail != null && _auth.SignUpDetail.SignUpPnl != null)
                        _auth.SignUpDetail.SignUpPnl.SetActive(false);
                }
            }
        }

        // ── Landing panel (State 0) ────────────────────────────────────────────

        private void BuildLandingPanel()
        {
            _landingPanel = new GameObject("LandingPanel",
                typeof(RectTransform), typeof(CanvasRenderer));
            _landingPanel.transform.SetParent(_overlay.transform, false);
            Stretch(_landingPanel.GetComponent<RectTransform>());

            Font font = GetFont();
            EnsureModelSprite();

            // ── Left panel — Model (30% width) ───────────────────────────────
            var leftPanel = new GameObject("LeftPanel",
                typeof(RectTransform), typeof(CanvasRenderer));
            leftPanel.transform.SetParent(_landingPanel.transform, false);
            var lp = leftPanel.GetComponent<RectTransform>();
            lp.anchorMin = new Vector2(0f, 0f);
            lp.anchorMax = new Vector2(0.32f, 1f);
            lp.offsetMin = lp.offsetMax = Vector2.zero;

            if (modelSprite != null)
            {
                var modelGo = new GameObject("ModelImage",
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                modelGo.transform.SetParent(leftPanel.transform, false);
                var img = modelGo.GetComponent<Image>();
                img.sprite = modelSprite;
                img.preserveAspect = true;
                img.color = Color.white;
                var mr = modelGo.GetComponent<RectTransform>();
                mr.anchorMin = new Vector2(0f, 0f);
                mr.anchorMax = new Vector2(1f, 0.90f);   // bottom-anchored character
                mr.offsetMin = mr.offsetMax = Vector2.zero;
            }

            // ── Right panel — Buttons (68% width) ────────────────────────────
            var rightPanel = new GameObject("RightPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            rightPanel.transform.SetParent(_landingPanel.transform, false);
            rightPanel.GetComponent<Image>().color = new Color32(60, 6, 16, 200);
            var rp = rightPanel.GetComponent<RectTransform>();
            rp.anchorMin = new Vector2(0.32f, 0f);
            rp.anchorMax = new Vector2(1f, 1f);
            rp.offsetMin = rp.offsetMax = Vector2.zero;

            // Logo at top of right panel
            if (_sceneLogoSprite != null)
            {
                var logoGo = new GameObject("LogoImage",
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                logoGo.transform.SetParent(rightPanel.transform, false);
                var logoImage = logoGo.GetComponent<Image>();
                logoImage.sprite = _sceneLogoSprite;
                logoImage.preserveAspect = true;
                logoImage.color = Color.white;
                var logoRect = logoGo.GetComponent<RectTransform>();
                logoRect.anchorMin = new Vector2(0.02f, 0.58f);
                logoRect.anchorMax = new Vector2(0.98f, 0.96f);
                logoRect.offsetMin = logoRect.offsetMax = Vector2.zero;
            }

            // Content (buttons) in the right panel
            var content = new GameObject("LandingContent",
                typeof(RectTransform), typeof(VerticalLayoutGroup));
            content.transform.SetParent(rightPanel.transform, false);
            var contentRect = content.GetComponent<RectTransform>();
            contentRect.anchorMin = new Vector2(0.06f, 0.08f);
            contentRect.anchorMax = new Vector2(0.94f, _sceneLogoSprite != null ? 0.56f : 0.90f);
            contentRect.offsetMin = contentRect.offsetMax = Vector2.zero;

            var layout = content.GetComponent<VerticalLayoutGroup>();
            layout.spacing = 22f;
            layout.childControlWidth = true;
            layout.childControlHeight = true;
            layout.childForceExpandWidth = true;
            layout.childForceExpandHeight = false;
            layout.childAlignment = TextAnchor.MiddleCenter;

            if (_sceneLogoSprite == null)
            {
                AddLabel(content.transform, font, "ROX LUDO", 96, FontStyle.Bold, AccentGold, 130f)
                    .alignment = TextAnchor.MiddleCenter;
            }

            AddLabel(content.transform, font, "Play. Win. Rule.", 42, FontStyle.Italic, MutedText, 60f)
                .alignment = TextAnchor.MiddleCenter;
            Spacer(content.transform, 10f);

            // "I've played before" label like Zynga Poker
            AddLabel(content.transform, font, "I've played before.", 36, FontStyle.Normal, MutedText, 50f)
                .alignment = TextAnchor.MiddleCenter;

            var loginBtn = MakeBtn(content.transform, font, "Login with existing account",
                new Color32(34, 170, 80, 255), Color.white, 48, 116f);
            loginBtn.onClick.AddListener(ShowLoginForm);

            AddLabel(content.transform, font, "OR", 36, FontStyle.Normal, MutedText, 46f)
                .alignment = TextAnchor.MiddleCenter;

            AddLabel(content.transform, font, "I'm new and would like to play.", 36, FontStyle.Normal, MutedText, 50f)
                .alignment = TextAnchor.MiddleCenter;

            var signupBtn = MakeBtn(content.transform, font, "Create new account",
                AccentGold, new Color32(30, 10, 0, 255), 48, 116f);
            signupBtn.onClick.AddListener(ShowSignup);

            var guestBtn = MakeBtn(content.transform, font, "Play as Guest",
                new Color32(0, 0, 0, 0), new Color32(235, 215, 220, 220), 40, 72f);
            guestBtn.onClick.AddListener(DoGuestLogin);

            // Exit App button — top-right corner of landing panel
            var exitGo = new GameObject("ExitAppBtn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Button));
            exitGo.transform.SetParent(_landingPanel.transform, false);
            var exitR = exitGo.GetComponent<RectTransform>();
            exitR.anchorMin        = new Vector2(1f, 1f);
            exitR.anchorMax        = new Vector2(1f, 1f);
            exitR.pivot            = new Vector2(1f, 1f);
            exitR.sizeDelta        = new Vector2(88f, 88f);
            exitR.anchoredPosition = new Vector2(-18f, -18f);
            exitGo.GetComponent<Image>().color = new Color32(160, 30, 45, 220);
            var exitBtn = exitGo.GetComponent<Button>();
            exitBtn.onClick.AddListener(() =>
            {
#if UNITY_EDITOR
                UnityEditor.EditorApplication.isPlaying = false;
#else
                Application.Quit();
#endif
            });
            var exitLbl = new GameObject("X", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            exitLbl.transform.SetParent(exitGo.transform, false);
            var elr = exitLbl.GetComponent<RectTransform>();
            elr.anchorMin = Vector2.zero; elr.anchorMax = Vector2.one;
            elr.offsetMin = elr.offsetMax = Vector2.zero;
            var et = exitLbl.GetComponent<Text>();
            et.font      = GetFont();
            et.text      = "✕";
            et.fontSize  = 42;
            et.fontStyle = FontStyle.Bold;
            et.color     = Color.white;
            et.alignment = TextAnchor.MiddleCenter;
        }

        // ── Login form panel (State 1) ─────────────────────────────────────────

        private void BuildLoginFormPanel()
        {
            _loginFormPanel = new GameObject("LoginFormPanel",
                typeof(RectTransform), typeof(CanvasRenderer));
            _loginFormPanel.transform.SetParent(_overlay.transform, false);
            Stretch(_loginFormPanel.GetComponent<RectTransform>());

            Font font = GetFont();
            EnsureModelSprite();

            // Background model stays visible behind the centered red card.
            var leftPanel = new GameObject("LeftPanel",
                typeof(RectTransform), typeof(CanvasRenderer));
            leftPanel.transform.SetParent(_loginFormPanel.transform, false);
            var lp = leftPanel.GetComponent<RectTransform>();
            lp.anchorMin = new Vector2(0f, 0f);
            lp.anchorMax = new Vector2(1f, 1f);
            lp.offsetMin = lp.offsetMax = Vector2.zero;

            if (modelSprite != null)
            {
                var modelGo = new GameObject("ModelImage",
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                modelGo.transform.SetParent(leftPanel.transform, false);
                var img = modelGo.GetComponent<Image>();
                img.sprite = modelSprite;
                img.preserveAspect = true;
                img.color = Color.white;
                var mr = modelGo.GetComponent<RectTransform>();
                mr.anchorMin = new Vector2(0f, 0.04f);
                mr.anchorMax = new Vector2(0.42f, 0.88f);
                mr.offsetMin = mr.offsetMax = Vector2.zero;
            }

            // Form layer covers the screen; the card is centered by ApplyResponsiveAuthLayout.
            var rightPanel = new GameObject("RightPanel",
                typeof(RectTransform), typeof(CanvasRenderer));
            rightPanel.transform.SetParent(_loginFormPanel.transform, false);
            var rp = rightPanel.GetComponent<RectTransform>();
            rp.anchorMin = new Vector2(0f, 0f);
            rp.anchorMax = new Vector2(1f, 1f);
            rp.offsetMin = rp.offsetMax = Vector2.zero;

            var card = new GameObject("LoginCard",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup));
            card.transform.SetParent(rightPanel.transform, false);
            card.GetComponent<Image>().color = CardColor;
            var cr = card.GetComponent<RectTransform>();
            cr.anchorMin = new Vector2(0.07f, 0.10f);
            cr.anchorMax = new Vector2(0.93f, 0.90f);
            cr.offsetMin = cr.offsetMax = Vector2.zero;

            var cvl = card.GetComponent<VerticalLayoutGroup>();
            cvl.padding              = new RectOffset(42, 42, 34, 28);
            cvl.spacing              = 14f;
            cvl.childControlWidth    = true;
            cvl.childControlHeight   = true;
            cvl.childForceExpandWidth  = true;
            cvl.childForceExpandHeight = false;
            cvl.childAlignment       = TextAnchor.UpperCenter;

            // Heading — bigger
            AddLabel(card.transform, font, "Welcome Back!", 54, FontStyle.Bold, Color.white, 76f)
                .alignment = TextAnchor.MiddleCenter;

            // Sub-text
            AddLabel(card.transform, font, "Login to your account", 31, FontStyle.Normal, MutedText, 42f)
                .alignment = TextAnchor.MiddleCenter;

            // Identifier input — taller, bigger font
            AddLabel(card.transform, font, "ID / Mobile / Email / Username", 28, FontStyle.Normal, MutedText, 34f);
            _idInput = MakeInput(card.transform, font, "Enter your ID, Mobile or Email", false, 76f, 32);

            // Password input
            AddLabel(card.transform, font, "Password", 28, FontStyle.Normal, MutedText, 34f);
            _pwInput = MakeInput(card.transform, font, "Enter your password", true, 76f, 32);

            Spacer(card.transform, 4f);

            // Login button — green, stylish decorated text
            var loginBtn = MakeBtn(card.transform, font, "✦  LOGIN WITH ROX LUDO  ✦",
                new Color32(34, 170, 80, 255), Color.white, 34, 82f);
            var loginShadow = loginBtn.gameObject.AddComponent<Shadow>();
            loginShadow.effectColor    = new Color32(10, 80, 25, 200);
            loginShadow.effectDistance = new Vector2(0f, -4f);
            loginBtn.onClick.AddListener(DoLogin);

            // Guest — lighter, bigger
            var guestBtn = MakeBtn(card.transform, font, "Play as Guest",
                MidRed, new Color32(255, 235, 200, 255), 30, 58f);
            guestBtn.onClick.AddListener(DoGuestLogin);

            // Back
            var backBtn = MakeBtn(card.transform, font, "← Back",
                new Color32(0, 0, 0, 0), new Color32(220, 195, 200, 255), 28, 44f);
            var backOutline = backBtn.gameObject.AddComponent<Outline>();
            backOutline.effectColor    = MidRed;
            backOutline.effectDistance = new Vector2(1f, -1f);
            backBtn.onClick.AddListener(ShowLanding);
        }

        private void BuildSignupFormPanel()
        {
            _signupFormPanel = new GameObject("SignupFormPanel",
                typeof(RectTransform), typeof(CanvasRenderer));
            _signupFormPanel.transform.SetParent(_overlay.transform, false);
            Stretch(_signupFormPanel.GetComponent<RectTransform>());

            Font font = GetFont();

            var card = new GameObject("SignupCard",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup));
            card.transform.SetParent(_signupFormPanel.transform, false);
            card.GetComponent<Image>().color = new Color32(CardColor.r, CardColor.g, CardColor.b, 210);
            var cr = card.GetComponent<RectTransform>();
            cr.anchorMin = new Vector2(0.03f, 0.04f);
            cr.anchorMax = new Vector2(0.97f, 0.96f);
            cr.offsetMin = cr.offsetMax = Vector2.zero;

            var cvl = card.GetComponent<VerticalLayoutGroup>();
            cvl.padding = new RectOffset(40, 40, 36, 28);
            cvl.spacing = 16f;
            cvl.childControlWidth = true;
            cvl.childControlHeight = true;
            cvl.childForceExpandWidth = true;
            cvl.childForceExpandHeight = false;
            cvl.childAlignment = TextAnchor.UpperCenter;

            AddLabel(card.transform, font, "Create New Account", 52, FontStyle.Bold, AccentGold, 72f)
                .alignment = TextAnchor.MiddleCenter;
            AddLabel(card.transform, font, "Sign up with mobile or email", 36, FontStyle.Normal, new Color32(235, 215, 220, 220), 46f)
                .alignment = TextAnchor.MiddleCenter;

            Spacer(card.transform, 4f);

            AddLabel(card.transform, font, "Mobile Or Email", 36, FontStyle.Normal, new Color32(235, 215, 220, 220), 44f);
            _signupMobileOrEmailInput = MakeInput(card.transform, font, "Enter mobile number or email", false, 96f, 36);

            AddLabel(card.transform, font, "Password", 36, FontStyle.Normal, new Color32(235, 215, 220, 220), 44f);
            _signupPasswordInput = MakeInput(card.transform, font, "Create your password", true, 96f, 36);

            AddLabel(card.transform, font, "Referral Code", 36, FontStyle.Normal, new Color32(235, 215, 220, 220), 44f);
            _signupReferralInput = MakeInput(card.transform, font, "Enter referral code (optional)", false, 96f, 34);

            var termsRow = new GameObject("TermsRow",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            termsRow.transform.SetParent(card.transform, false);
            termsRow.GetComponent<LayoutElement>().preferredHeight = 46f;
            var termsLayout = termsRow.GetComponent<HorizontalLayoutGroup>();
            termsLayout.spacing = 12f;
            termsLayout.childControlWidth = false;
            termsLayout.childControlHeight = true;
            termsLayout.childForceExpandWidth = false;
            termsLayout.childForceExpandHeight = false;
            termsLayout.childAlignment = TextAnchor.MiddleLeft;

            var toggleGo = new GameObject("TermsToggle",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Toggle), typeof(LayoutElement));
            toggleGo.transform.SetParent(termsRow.transform, false);
            toggleGo.GetComponent<LayoutElement>().preferredWidth = 40f;
            var toggleRect = toggleGo.GetComponent<RectTransform>();
            toggleRect.sizeDelta = new Vector2(40f, 40f);

            var toggleBg = new GameObject("Background",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            toggleBg.transform.SetParent(toggleGo.transform, false);
            var toggleBgRect = toggleBg.GetComponent<RectTransform>();
            toggleBgRect.anchorMin = new Vector2(0.5f, 0.5f);
            toggleBgRect.anchorMax = new Vector2(0.5f, 0.5f);
            toggleBgRect.sizeDelta = new Vector2(34f, 34f);
            toggleBg.GetComponent<Image>().color = new Color32(255, 255, 255, 38);

            var toggleCheck = new GameObject("Checkmark",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            toggleCheck.transform.SetParent(toggleBg.transform, false);
            var toggleCheckRect = toggleCheck.GetComponent<RectTransform>();
            toggleCheckRect.anchorMin = new Vector2(0.5f, 0.5f);
            toggleCheckRect.anchorMax = new Vector2(0.5f, 0.5f);
            toggleCheckRect.sizeDelta = new Vector2(20f, 20f);
            toggleCheck.GetComponent<Image>().color = AccentGold;

            _signupTermsToggle = toggleGo.GetComponent<Toggle>();
            _signupTermsToggle.targetGraphic = toggleBg.GetComponent<Image>();
            _signupTermsToggle.graphic = toggleCheck.GetComponent<Image>();
            _signupTermsToggle.isOn = _auth != null && _auth.registertoggle != null ? _auth.registertoggle.isOn : true;

            var termsLabel = AddLabel(termsRow.transform, font, "I agree to Terms & Conditions", 20, FontStyle.Normal, MutedText, 40f);
            termsLabel.alignment = TextAnchor.MiddleLeft;
            termsLabel.horizontalOverflow = HorizontalWrapMode.Overflow;
            termsLabel.resizeTextForBestFit = true;
            termsLabel.resizeTextMinSize = 14;
            termsLabel.resizeTextMaxSize = 20;

            Spacer(card.transform, 4f);

            var createBtn = MakeBtn(card.transform, font, "Create Account",
                AccentRed, Color.white, 34, 82f);
            createBtn.onClick.AddListener(DoSignup);

            var footerRow = new GameObject("SignupFooterRow",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            footerRow.transform.SetParent(card.transform, false);
            footerRow.GetComponent<LayoutElement>().preferredHeight = 56f;
            var footerLayout = footerRow.GetComponent<HorizontalLayoutGroup>();
            footerLayout.spacing = 16f;
            footerLayout.childControlWidth = true;
            footerLayout.childControlHeight = true;
            footerLayout.childForceExpandWidth = true;
            footerLayout.childForceExpandHeight = false;
            footerLayout.childAlignment = TextAnchor.MiddleCenter;

            var loginBtn = MakeBtn(footerRow.transform, font, "Login",
                new Color32(0, 0, 0, 0), new Color32(255, 210, 100, 255), 38, 60f);
            var loginOutline = loginBtn.gameObject.AddComponent<Outline>();
            loginOutline.effectColor = AccentGold;
            loginOutline.effectDistance = new Vector2(2f, -2f);
            loginBtn.onClick.AddListener(ShowLanding);

            var guestBtn = MakeBtn(footerRow.transform, font, "Play as Guest",
                new Color32(0, 0, 0, 0), new Color32(235, 215, 220, 220), 36, 60f);
            guestBtn.onClick.AddListener(DoGuestLogin);
        }

        private void ApplyResponsiveAuthLayout()
        {
            if (_overlay == null)
            {
                return;
            }

            Image overlayImage = _overlay.GetComponent<Image>();
            if (overlayImage != null)
            {
                overlayImage.color = BgColor;
            }

            RectTransform overlayRect = _overlay.GetComponent<RectTransform>();
            Rect rect = overlayRect != null ? overlayRect.rect : new Rect(0f, 0f, Screen.width, Screen.height);
            bool portrait = rect.height >= rect.width;

            ApplyLoginResponsiveLayout(portrait);
            ApplySignupResponsiveLayout(portrait);
            HideLegacySocialLoginUi();

            Canvas.ForceUpdateCanvases();
            RebuildIfAvailable("LoginFormPanel/RightPanel/LoginCard");
            RebuildIfAvailable("SignupFormPanel/SignupCard");
        }

        private void ApplyLoginResponsiveLayout(bool portrait)
        {
            Transform loginPanel = FindByPath(_overlay.transform, "LoginFormPanel");
            if (loginPanel == null)
            {
                return;
            }

            Transform leftPanel = FindByPath(loginPanel, "LeftPanel");
            if (leftPanel != null)
            {
                SetAnchors(leftPanel as RectTransform, Vector2.zero, Vector2.one);
                Transform model = leftPanel.Find("ModelImage");
                RectTransform modelRect = model as RectTransform;
                if (modelRect != null)
                {
                    SetAnchors(
                        modelRect,
                        portrait ? new Vector2(-0.08f, 0.00f) : new Vector2(0.00f, 0.02f),
                        portrait ? new Vector2(0.56f, 0.72f) : new Vector2(0.38f, 0.92f));
                }
            }

            Transform rightPanel = FindByPath(loginPanel, "RightPanel");
            if (rightPanel != null)
            {
                SetAnchors(rightPanel as RectTransform, Vector2.zero, Vector2.one);
            }

            Transform card = FindByPath(loginPanel, "RightPanel/LoginCard");
            if (card == null)
            {
                return;
            }

            RectTransform cardRect = card as RectTransform;
            if (portrait)
            {
                SetAnchors(cardRect, new Vector2(0.035f, 0.035f), new Vector2(0.965f, 0.965f));
            }
            else
            {
                SetAnchors(cardRect, new Vector2(0.34f, 0.045f), new Vector2(0.965f, 0.955f));
            }

            Image cardImage = card.GetComponent<Image>();
            if (cardImage != null)
            {
                cardImage.color = CardColor;
            }

            VerticalLayoutGroup layout = card.GetComponent<VerticalLayoutGroup>();
            if (layout != null)
            {
                layout.padding = portrait ? new RectOffset(34, 34, 24, 20) : new RectOffset(38, 38, 22, 18);
                layout.spacing = portrait ? 10f : 8f;
                layout.childAlignment = TextAnchor.MiddleCenter;
                layout.childControlWidth = true;
                layout.childControlHeight = true;
                layout.childForceExpandWidth = true;
                layout.childForceExpandHeight = false;
            }

            for (int i = 0; i < card.childCount; i++)
            {
                Transform child = card.GetChild(i);
                Text directText = child.GetComponent<Text>();
                if (directText != null)
                {
                    ApplyLoginLabelSize(directText, portrait);
                    continue;
                }

                InputField input = child.GetComponent<InputField>();
                if (input != null)
                {
                    StyleInput(input, portrait ? 48 : 42, portrait ? 118f : 98f);
                    continue;
                }

                Button button = child.GetComponent<Button>();
                if (button != null)
                {
                    ApplyLoginButtonSize(button, portrait);
                    continue;
                }

                if (child.name == "SocialRow")
                {
                    child.gameObject.SetActive(false);
                }
            }
        }

        private void ApplySignupResponsiveLayout(bool portrait)
        {
            Transform card = FindByPath(_overlay.transform, "SignupFormPanel/SignupCard");
            if (card == null)
            {
                return;
            }

            RectTransform cardRect = card as RectTransform;
            SetAnchors(
                cardRect,
                portrait ? new Vector2(0.035f, 0.025f) : new Vector2(0.18f, 0.035f),
                portrait ? new Vector2(0.965f, 0.975f) : new Vector2(0.82f, 0.965f));

            Image cardImage = card.GetComponent<Image>();
            if (cardImage != null)
            {
                cardImage.color = CardColor;
            }

            VerticalLayoutGroup layout = card.GetComponent<VerticalLayoutGroup>();
            if (layout != null)
            {
                layout.padding = portrait ? new RectOffset(32, 32, 22, 18) : new RectOffset(38, 38, 22, 18);
                layout.spacing = portrait ? 9f : 8f;
                layout.childAlignment = TextAnchor.MiddleCenter;
                layout.childControlWidth = true;
                layout.childControlHeight = true;
                layout.childForceExpandWidth = true;
                layout.childForceExpandHeight = false;
            }

            for (int i = 0; i < card.childCount; i++)
            {
                Transform child = card.GetChild(i);
                Text directText = child.GetComponent<Text>();
                if (directText != null)
                {
                    ApplySignupLabelSize(directText, portrait);
                    continue;
                }

                InputField input = child.GetComponent<InputField>();
                if (input != null)
                {
                    StyleInput(input, portrait ? 42 : 36, portrait ? 96f : 78f);
                    continue;
                }

                Button button = child.GetComponent<Button>();
                if (button != null)
                {
                    ApplyGenericButtonSize(button, portrait ? 42 : 34, portrait ? 84f : 68f);
                    continue;
                }

                if (child.name == "TermsRow")
                {
                    SetLayoutHeight(child, portrait ? 62f : 52f);
                    Text termsText = child.GetComponentInChildren<Text>(true);
                    if (termsText != null)
                    {
                        termsText.fontSize = portrait ? 30 : 25;
                        termsText.resizeTextForBestFit = true;
                        termsText.resizeTextMinSize = portrait ? 24 : 20;
                        termsText.resizeTextMaxSize = portrait ? 30 : 25;
                    }
                }
                else if (child.name == "SignupFooterRow")
                {
                    SetLayoutHeight(child, portrait ? 78f : 64f);
                    Button[] buttons = child.GetComponentsInChildren<Button>(true);
                    for (int b = 0; b < buttons.Length; b++)
                    {
                        ApplyGenericButtonSize(buttons[b], portrait ? 38 : 32, portrait ? 74f : 60f);
                    }
                }
            }
        }

        private void ApplyLoginLabelSize(Text label, bool portrait)
        {
            if (label == null)
            {
                return;
            }

            string text = label.text ?? string.Empty;
            if (text.IndexOf("OR", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                label.gameObject.SetActive(false);
                return;
            }

            label.gameObject.SetActive(true);
            label.resizeTextForBestFit = false;
            label.verticalOverflow = VerticalWrapMode.Overflow;

            if (text.IndexOf("Welcome", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                label.fontSize = portrait ? 76 : 62;
                label.alignment = TextAnchor.MiddleCenter;
                SetLayoutHeight(label.transform, portrait ? 96f : 78f);
            }
            else if (text.IndexOf("Login to", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                label.fontSize = portrait ? 52 : 42;
                label.fontStyle = FontStyle.Bold;
                label.alignment = TextAnchor.MiddleCenter;
                SetLayoutHeight(label.transform, portrait ? 64f : 52f);
            }
            else
            {
                label.fontSize = portrait ? 44 : 36;
                label.fontStyle = FontStyle.Bold;
                label.alignment = TextAnchor.MiddleLeft;
                SetLayoutHeight(label.transform, portrait ? 54f : 44f);
            }
        }

        private void ApplySignupLabelSize(Text label, bool portrait)
        {
            if (label == null)
            {
                return;
            }

            string text = label.text ?? string.Empty;
            label.resizeTextForBestFit = false;
            label.verticalOverflow = VerticalWrapMode.Overflow;

            if (text.IndexOf("Create New", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                label.fontSize = portrait ? 58 : 48;
                label.alignment = TextAnchor.MiddleCenter;
                SetLayoutHeight(label.transform, portrait ? 74f : 62f);
            }
            else if (text.IndexOf("Sign up", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                label.fontSize = portrait ? 38 : 32;
                label.alignment = TextAnchor.MiddleCenter;
                SetLayoutHeight(label.transform, portrait ? 46f : 38f);
            }
            else
            {
                label.fontSize = portrait ? 34 : 28;
                label.alignment = TextAnchor.MiddleLeft;
                SetLayoutHeight(label.transform, portrait ? 42f : 34f);
            }
        }

        private void ApplyLoginButtonSize(Button button, bool portrait)
        {
            Text label = button.GetComponentInChildren<Text>(true);
            string text = label != null ? label.text ?? string.Empty : string.Empty;

            if (text.IndexOf("LOGIN WITH", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                ApplyGenericButtonSize(button, portrait ? 46 : 38, portrait ? 102f : 84f);
            }
            else if (text.IndexOf("Guest", System.StringComparison.OrdinalIgnoreCase) >= 0)
            {
                ApplyGenericButtonSize(button, portrait ? 44 : 36, portrait ? 92f : 72f);
            }
            else
            {
                ApplyGenericButtonSize(button, portrait ? 40 : 32, portrait ? 66f : 54f);
            }
        }

        private void ApplyGenericButtonSize(Button button, int fontSize, float height)
        {
            if (button == null)
            {
                return;
            }

            SetLayoutHeight(button.transform, height);
            Text label = button.GetComponentInChildren<Text>(true);
            if (label != null)
            {
                label.fontSize = fontSize;
                label.resizeTextForBestFit = true;
                label.resizeTextMinSize = Mathf.Max(16, fontSize - 8);
                label.resizeTextMaxSize = fontSize;
                label.alignment = TextAnchor.MiddleCenter;
            }
        }

        private void StyleInput(InputField input, int fontSize, float height)
        {
            if (input == null)
            {
                return;
            }

            SetLayoutHeight(input.transform, height);

            Image image = input.GetComponent<Image>();
            if (image != null)
            {
                image.color = InputBg;
            }

            if (input.textComponent != null)
            {
                input.textComponent.fontSize = fontSize;
                input.textComponent.color = Color.white;
                input.textComponent.alignment = TextAnchor.MiddleLeft;
                input.textComponent.resizeTextForBestFit = false;
                input.textComponent.verticalOverflow = VerticalWrapMode.Overflow;
                RectTransform textRect = input.textComponent.transform as RectTransform;
                if (textRect != null)
                {
                    textRect.offsetMin = new Vector2(22f, 0f);
                    textRect.offsetMax = new Vector2(-22f, 0f);
                }
            }

            Text placeholder = input.placeholder as Text;
            if (placeholder != null)
            {
                placeholder.fontSize = Mathf.Max(22, fontSize - 2);
                placeholder.color = new Color32(230, 190, 200, 175);
                placeholder.alignment = TextAnchor.MiddleLeft;
                placeholder.resizeTextForBestFit = false;
                RectTransform placeholderRect = placeholder.transform as RectTransform;
                if (placeholderRect != null)
                {
                    placeholderRect.offsetMin = new Vector2(22f, 0f);
                    placeholderRect.offsetMax = new Vector2(-22f, 0f);
                }
            }
        }

        private void HideLegacySocialLoginUi()
        {
            HideIfFound("LoginFormPanel/RightPanel/LoginCard/SocialRow");
            HideIfFound("loginpanel/_SocialSection");

            Transform card = FindByPath(_overlay.transform, "LoginFormPanel/RightPanel/LoginCard");
            if (card == null)
            {
                return;
            }

            for (int i = 0; i < card.childCount; i++)
            {
                Transform child = card.GetChild(i);
                if (child == null)
                {
                    continue;
                }

                if (child.name == "SocialRow"
                    || child.name.IndexOf("Google", System.StringComparison.OrdinalIgnoreCase) >= 0
                    || child.name.IndexOf("Facebook", System.StringComparison.OrdinalIgnoreCase) >= 0
                    || child.name.IndexOf("Instagram", System.StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    child.gameObject.SetActive(false);
                    continue;
                }

                Text label = child.GetComponent<Text>();
                if (label != null && (label.text ?? string.Empty).IndexOf("OR", System.StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    child.gameObject.SetActive(false);
                }
            }
        }

        private void HideIfFound(string path)
        {
            Transform target = FindByPath(_overlay.transform, path);
            if (target != null)
            {
                target.gameObject.SetActive(false);
            }
        }

        private void RebuildIfAvailable(string path)
        {
            Transform target = FindByPath(_overlay.transform, path);
            RectTransform rect = target as RectTransform;
            if (rect != null)
            {
                LayoutRebuilder.ForceRebuildLayoutImmediate(rect);
            }
        }

        private static void SetAnchors(RectTransform rect, Vector2 min, Vector2 max)
        {
            if (rect == null)
            {
                return;
            }

            rect.anchorMin = min;
            rect.anchorMax = max;
            rect.offsetMin = Vector2.zero;
            rect.offsetMax = Vector2.zero;
        }

        private static void SetLayoutHeight(Transform transform, float height)
        {
            if (transform == null)
            {
                return;
            }

            LayoutElement layout = transform.GetComponent<LayoutElement>();
            if (layout == null)
            {
                layout = transform.gameObject.AddComponent<LayoutElement>();
            }

            layout.preferredHeight = height;
            layout.minHeight = height;
        }

        // ── State transitions ──────────────────────────────────────────────────

        private void ShowLanding()
        {
            ApplyResponsiveAuthLayout();
            SetSceneLogoVisible(false);
            if (_auth.SignUpDetail?.SignUpPnl != null)
            {
                _auth.SignUpDetail.SignUpPnl.SetActive(false);
            }
            _landingPanel.SetActive(true);
            _loginFormPanel.SetActive(false);
            if (_signupFormPanel != null)
            {
                _signupFormPanel.SetActive(false);
            }
        }

        private void ShowLoginForm()
        {
            ApplyResponsiveAuthLayout();
            SetSceneLogoVisible(false);
            if (_auth.SignUpDetail?.SignUpPnl != null)
            {
                _auth.SignUpDetail.SignUpPnl.SetActive(false);
            }
            _landingPanel.SetActive(false);
            _loginFormPanel.SetActive(true);
            if (_signupFormPanel != null)
            {
                _signupFormPanel.SetActive(false);
            }
        }

        private void ShowSignup()
        {
            ApplyResponsiveAuthLayout();
            SetSceneLogoVisible(false);
            _overlay.SetActive(true);
            _landingPanel.SetActive(false);
            _loginFormPanel.SetActive(false);
            if (_signupFormPanel != null)
            {
                _signupFormPanel.SetActive(true);
            }
            if (_auth.loginpanel != null)
                _auth.loginpanel.SetActive(false);
            if (_auth.LogInDetail?.LogInPnl != null)
                _auth.LogInDetail.LogInPnl.SetActive(false);
            if (_auth.SignUpDetail?.SignUpPnl != null)
                _auth.SignUpDetail.SignUpPnl.SetActive(false);
        }

        /// <summary>Called externally (e.g. from a "Back" button on signup panel) to restore landing.</summary>
        public void RestoreOverlay()
        {
            if (_auth.SignUpDetail?.SignUpPnl != null)
            {
                _auth.SignUpDetail.SignUpPnl.SetActive(false);
            }
            if (_auth.loginpanel != null)
            {
                _auth.loginpanel.SetActive(false);
            }
            if (_auth.LogInDetail?.LogInPnl != null)
            {
                _auth.LogInDetail.LogInPnl.SetActive(false);
            }
            SetSceneLogoVisible(false);
            _overlay.SetActive(true);
            ShowLanding();
        }

        // ── Actions ────────────────────────────────────────────────────────────

        private void DoLogin()
        {
            // Sync our runtime inputs into AuthManager's TMP fields, then trigger login
            if (_auth.LogInDetail?.MobileInputfield != null)
                _auth.LogInDetail.MobileInputfield.text = _idInput != null ? _idInput.text : string.Empty;
            if (_auth.LogInDetail?.PasswordInputfield != null)
                _auth.LogInDetail.PasswordInputfield.text = _pwInput != null ? _pwInput.text : string.Empty;

            _auth.OnClickLogIn();
        }

        private void DoGuestLogin()
        {
            _auth.OnClickGuest();
        }

        private void DoSignup()
        {
            if (_auth == null || _auth.SignUpDetail == null)
            {
                return;
            }

            string loginValue = _signupMobileOrEmailInput != null ? _signupMobileOrEmailInput.text.Trim() : string.Empty;
            string passwordValue = _signupPasswordInput != null ? _signupPasswordInput.text : string.Empty;
            string referralValue = _signupReferralInput != null ? _signupReferralInput.text.Trim() : string.Empty;
            bool isEmail = !string.IsNullOrEmpty(loginValue) && loginValue.Contains("@");

            if (_auth.SignUpDetail.EmailInputfield != null)
            {
                _auth.SignUpDetail.EmailInputfield.text = isEmail ? loginValue : string.Empty;
            }

            if (_auth.SignUpDetail.MobileInputfield != null)
            {
                _auth.SignUpDetail.MobileInputfield.text = isEmail ? string.Empty : loginValue;
            }

            if (_auth.SignUpDetail.PasswordInputfield != null)
            {
                _auth.SignUpDetail.PasswordInputfield.text = passwordValue;
            }

            if (_auth.SignUpDetail.ReferralCodeInputfield != null)
            {
                _auth.SignUpDetail.ReferralCodeInputfield.text = referralValue;
            }

            if (_auth.registertoggle != null)
            {
                _auth.registertoggle.isOn = _signupTermsToggle == null || _signupTermsToggle.isOn;
            }

            if (_auth.SignUpDetail._Toggle != null)
            {
                _auth.SignUpDetail._Toggle.isOn = _signupTermsToggle == null || _signupTermsToggle.isOn;
            }

            _auth.OnClickSignUp();
        }

        private bool TryBindExistingOverlay(Transform parent)
        {
            _overlay = FindDirectChild(parent, OverlayName)?.gameObject;
            if (_overlay == null)
            {
                return false;
            }

            _landingPanel = FindByPath(_overlay.transform, "LandingPanel")?.gameObject;
            _loginFormPanel = FindByPath(_overlay.transform, "LoginFormPanel")?.gameObject;
            _signupFormPanel = FindByPath(_overlay.transform, "SignupFormPanel")?.gameObject;
            Transform loginCard = FindByPath(_overlay.transform, "LoginFormPanel/RightPanel/LoginCard");
            Transform signupCard = FindByPath(_overlay.transform, "SignupFormPanel/SignupCard");
            InputField[] loginInputs = loginCard != null ? loginCard.GetComponentsInChildren<InputField>(true) : new InputField[0];
            InputField[] signupInputs = signupCard != null ? signupCard.GetComponentsInChildren<InputField>(true) : new InputField[0];
            _idInput = loginInputs.Length > 0 ? loginInputs[0] : null;
            _pwInput = loginInputs.Length > 1 ? loginInputs[1] : null;
            _signupMobileOrEmailInput = signupInputs.Length > 0 ? signupInputs[0] : null;
            _signupPasswordInput = signupInputs.Length > 1 ? signupInputs[1] : null;
            _signupReferralInput = signupInputs.Length > 2 ? signupInputs[2] : null;
            _signupTermsToggle = FindComponent<Toggle>(_overlay.transform, "SignupFormPanel/SignupCard/TermsRow/TermsToggle");

            if (_landingPanel == null
                || _loginFormPanel == null
                || _signupFormPanel == null
                || _idInput == null
                || _pwInput == null
                || _signupMobileOrEmailInput == null
                || _signupPasswordInput == null
                || _signupReferralInput == null
                || _signupTermsToggle == null)
            {
                Debug.LogWarning("[LoginPageRedesigner] Existing _LoginRedesign hierarchy found but some references could not be rebound. Reusing it anyway to avoid duplicate runtime UI.");
            }

            WirePersistentUiListeners();
            return true;
        }

        private void WirePersistentUiListeners()
        {
            Button loginWithExistingBtn = FindButton(_overlay.transform, "LandingPanel/RightPanel/LandingContent/Login with existing accountBtn");
            if (loginWithExistingBtn != null)
            {
                loginWithExistingBtn.onClick.RemoveAllListeners();
                loginWithExistingBtn.onClick.AddListener(ShowLoginForm);
            }

            Button createAccountBtn = FindButton(_overlay.transform, "LandingPanel/RightPanel/LandingContent/Create new accountBtn");
            if (createAccountBtn != null)
            {
                createAccountBtn.onClick.RemoveAllListeners();
                createAccountBtn.onClick.AddListener(ShowSignup);
            }

            Button guestLandingBtn = FindButton(_overlay.transform, "LandingPanel/RightPanel/LandingContent/Play as GuestBtn");
            if (guestLandingBtn != null)
            {
                guestLandingBtn.onClick.RemoveAllListeners();
                guestLandingBtn.onClick.AddListener(DoGuestLogin);
            }

            Button exitBtn = FindButton(_overlay.transform, "LandingPanel/ExitAppBtn");
            if (exitBtn != null)
            {
                exitBtn.onClick.RemoveAllListeners();
                exitBtn.onClick.AddListener(() =>
                {
#if UNITY_EDITOR
                    UnityEditor.EditorApplication.isPlaying = false;
#else
                    Application.Quit();
#endif
                });
            }

            Button loginBtn = FindButton(_overlay.transform, "LoginFormPanel/RightPanel/LoginCard/✦  LOGIN WITH ROX LUDO  ✦Btn");
            if (loginBtn != null)
            {
                loginBtn.onClick.RemoveAllListeners();
                loginBtn.onClick.AddListener(DoLogin);
            }

            Button guestLoginBtn = FindButton(_overlay.transform, "LoginFormPanel/RightPanel/LoginCard/Play as GuestBtn");
            if (guestLoginBtn != null)
            {
                guestLoginBtn.onClick.RemoveAllListeners();
                guestLoginBtn.onClick.AddListener(DoGuestLogin);
            }

            Button backBtn = FindButton(_overlay.transform, "LoginFormPanel/RightPanel/LoginCard/← BackBtn");
            if (backBtn != null)
            {
                backBtn.onClick.RemoveAllListeners();
                backBtn.onClick.AddListener(ShowLanding);
            }

            Button createBtn = FindButton(_overlay.transform, "SignupFormPanel/SignupCard/Create AccountBtn");
            if (createBtn != null)
            {
                createBtn.onClick.RemoveAllListeners();
                createBtn.onClick.AddListener(DoSignup);
            }

            Button signupLoginBtn = FindButton(_overlay.transform, "SignupFormPanel/SignupCard/SignupFooterRow/LoginBtn");
            if (signupLoginBtn != null)
            {
                signupLoginBtn.onClick.RemoveAllListeners();
                signupLoginBtn.onClick.AddListener(ShowLanding);
            }

            Button signupGuestBtn = FindButton(_overlay.transform, "SignupFormPanel/SignupCard/SignupFooterRow/Play as GuestBtn");
            if (signupGuestBtn != null)
            {
                signupGuestBtn.onClick.RemoveAllListeners();
                signupGuestBtn.onClick.AddListener(DoGuestLogin);
            }
        }

        // ── UI factory helpers ─────────────────────────────────────────────────

        private static Text AddLabel(Transform parent, Font font, string text,
            int size, FontStyle style, Color color, float height)
        {
            var go = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            go.transform.SetParent(parent, false);

            var t = go.GetComponent<Text>();
            t.font               = font;
            t.text               = text;
            t.fontSize           = size;
            t.fontStyle          = style;
            t.color              = color;
            t.alignment          = TextAnchor.MiddleLeft;
            t.verticalOverflow   = VerticalWrapMode.Overflow;
            t.horizontalOverflow = HorizontalWrapMode.Wrap;
            t.raycastTarget      = false;

            go.AddComponent<LayoutElement>().preferredHeight = height;
            return t;
        }

        private static Button MakeBtn(Transform parent, Font font, string label,
            Color32 bg, Color textColor, int fontSize, float height)
        {
            var go = new GameObject(label + "Btn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(Button), typeof(LayoutElement));
            go.transform.SetParent(parent, false);

            var img = go.GetComponent<Image>();
            img.color = bg;

            go.GetComponent<LayoutElement>().preferredHeight = height;

            var btn = go.GetComponent<Button>();
            btn.targetGraphic = img;
            var cb = btn.colors;
            cb.normalColor = bg;
            cb.highlightedColor = Lighten(bg, 20);
            cb.pressedColor     = Darken(bg, 20);
            btn.colors = cb;

            // Label child
            var lblGo = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            lblGo.transform.SetParent(go.transform, false);
            var t = lblGo.GetComponent<Text>();
            t.font      = font;
            t.text      = label;
            t.fontSize  = fontSize;
            t.fontStyle = FontStyle.Bold;
            t.color     = textColor;
            t.alignment = TextAnchor.MiddleCenter;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            t.raycastTarget    = false;
            var tr = lblGo.GetComponent<RectTransform>();
            tr.anchorMin = Vector2.zero;
            tr.anchorMax = Vector2.one;
            tr.offsetMin = tr.offsetMax = Vector2.zero;

            return btn;
        }

        private static InputField MakeInput(Transform parent, Font font,
            string placeholder, bool password, float height, int fontSize)
        {
            var go = new GameObject("Input",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(InputField), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            go.GetComponent<Image>().color = InputBg;
            go.GetComponent<LayoutElement>().preferredHeight = height;

            // Placeholder text
            var ph = new GameObject("Placeholder",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            ph.transform.SetParent(go.transform, false);
            var pht = ph.GetComponent<Text>();
            pht.font               = font;
            pht.text               = placeholder;
            pht.fontSize           = fontSize;
            pht.color              = new Color32(160, 110, 125, 150);
            pht.alignment          = TextAnchor.MiddleLeft;
            pht.verticalOverflow   = VerticalWrapMode.Overflow;
            pht.raycastTarget      = false;
            var phr = ph.GetComponent<RectTransform>();
            phr.anchorMin = Vector2.zero;
            phr.anchorMax = Vector2.one;
            phr.offsetMin = new Vector2(18, 0);
            phr.offsetMax = new Vector2(-18, 0);

            // Input text
            var txGo = new GameObject("Text",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            txGo.transform.SetParent(go.transform, false);
            var txt = txGo.GetComponent<Text>();
            txt.font             = font;
            txt.fontSize         = fontSize;
            txt.color            = Color.white;
            txt.alignment        = TextAnchor.MiddleLeft;
            txt.verticalOverflow = VerticalWrapMode.Overflow;
            var txr = txGo.GetComponent<RectTransform>();
            txr.anchorMin = Vector2.zero;
            txr.anchorMax = Vector2.one;
            txr.offsetMin = new Vector2(18, 0);
            txr.offsetMax = new Vector2(-18, 0);

            var input = go.GetComponent<InputField>();
            input.targetGraphic  = go.GetComponent<Image>();
            input.textComponent  = txt;
            input.placeholder    = pht;
            if (password)
                input.inputType = InputField.InputType.Password;

            return input;
        }

        private static void AddSocialBtn(Transform parent, Font font,
            string label, Color32 bg, System.Action onClick, int fontSize)
        {
            var go = new GameObject(label + "SBtn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(Button), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            go.GetComponent<Image>().color = bg;
            go.GetComponent<LayoutElement>().preferredHeight = 82f;

            var btn = go.GetComponent<Button>();
            btn.targetGraphic = go.GetComponent<Image>();

            var lblGo = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            lblGo.transform.SetParent(go.transform, false);
            var t = lblGo.GetComponent<Text>();
            t.font      = font;
            t.text      = label;
            t.fontSize  = fontSize;
            t.fontStyle = FontStyle.Bold;
            t.color     = Color.white;
            t.alignment = TextAnchor.MiddleCenter;
            t.verticalOverflow = VerticalWrapMode.Overflow;
            t.raycastTarget    = false;
            var lr = lblGo.GetComponent<RectTransform>();
            lr.anchorMin = Vector2.zero;
            lr.anchorMax = Vector2.one;
            lr.offsetMin = lr.offsetMax = Vector2.zero;

            if (onClick != null)
            {
                btn.onClick.AddListener(() => onClick());
            }
        }

        private static void Spacer(Transform parent, float height)
        {
            var go = new GameObject("Spacer",
                typeof(RectTransform), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            go.GetComponent<LayoutElement>().preferredHeight = height;
        }

        private static void Stretch(RectTransform rt)
        {
            rt.anchorMin = Vector2.zero;
            rt.anchorMax = Vector2.one;
            rt.offsetMin = rt.offsetMax = Vector2.zero;
        }

        private static Transform FindByPath(Transform root, string path)
        {
            return root == null || string.IsNullOrWhiteSpace(path) ? null : root.Find(path);
        }

        private static Button FindButton(Transform root, string path)
        {
            return FindByPath(root, path)?.GetComponent<Button>();
        }

        private static T FindComponent<T>(Transform root, string path) where T : Component
        {
            return FindByPath(root, path)?.GetComponent<T>();
        }

        private static Transform FindDirectChild(Transform parent, string childName)
        {
            if (parent == null)
            {
                return null;
            }

            for (int i = 0; i < parent.childCount; i++)
            {
                Transform child = parent.GetChild(i);
                if (child != null && child.name == childName)
                {
                    return child;
                }
            }

            return null;
        }

        private void CacheSceneLogo(Transform root)
        {
            if (root == null)
            {
                return;
            }

            Image logoImage = root.GetComponentsInChildren<Image>(true)
                .FirstOrDefault(image => image != null && image.gameObject.name == "Logo");

            if (logoImage == null)
            {
                return;
            }

            _sceneLogoObject = logoImage.gameObject;
            _sceneLogoSprite = logoImage.sprite;
        }

        private void SetSceneLogoVisible(bool isVisible)
        {
            if (_sceneLogoObject != null)
            {
                _sceneLogoObject.SetActive(isVisible);
            }
        }

        private static Canvas ResolveRootCanvas(GameObject loginRoot)
        {
            if (loginRoot != null)
            {
                Canvas[] parentCanvases = loginRoot.GetComponentsInParent<Canvas>(true);
                if (parentCanvases != null && parentCanvases.Length > 0)
                {
                    Canvas topCanvas = parentCanvases[parentCanvases.Length - 1];
                    if (topCanvas != null)
                    {
                        return topCanvas.rootCanvas != null ? topCanvas.rootCanvas : topCanvas;
                    }
                }
            }

            Canvas anyCanvas = Object.FindObjectsOfType<Canvas>(true)
                .OrderByDescending(canvas =>
                {
                    RectTransform rect = canvas != null ? canvas.transform as RectTransform : null;
                    if (rect == null)
                    {
                        return 0f;
                    }

                    Rect r = rect.rect;
                    return Mathf.Abs(r.width * r.height);
                })
                .FirstOrDefault();

            return anyCanvas != null && anyCanvas.rootCanvas != null ? anyCanvas.rootCanvas : anyCanvas;
        }

        private static Font GetFont() =>
            Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");

        // Loads Model.png if modelSprite not yet assigned via Inspector
        private void EnsureModelSprite()
        {
            if (modelSprite != null) return;
#if UNITY_EDITOR
            modelSprite = UnityEditor.AssetDatabase.LoadAssetAtPath<Sprite>(
                "Assets/_Project/Core/UI/Common/Models/Model.png");
#endif
        }

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
