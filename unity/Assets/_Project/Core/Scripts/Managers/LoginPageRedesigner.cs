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
    /// State 1 – Login    : compact login form, social buttons, guest option
    /// State 2 – Signup   : restores original loginpanel in signup mode
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

        // ── App palette ────────────────────────────────────────────────────────
        private static readonly Color32 BgColor     = new Color32( 20,   4,   8,  80); // 30% opacity — bg image visible
        private static readonly Color32 CardColor   = new Color32( 44,  10,  18, 210); // slightly transparent card
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

                CacheSceneLogo(loginRoot.transform.root);
                loginRoot.SetActive(false);
                SetSceneLogoVisible(false);
                if (_auth.LogInDetail?.LogInPnl != null)
                {
                    _auth.LogInDetail.LogInPnl.SetActive(false);
                }

                _overlay = new GameObject("_LoginRedesign",
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

            var heroPanel = new GameObject("HeroPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            heroPanel.transform.SetParent(_landingPanel.transform, false);
            heroPanel.GetComponent<Image>().color = new Color32(78, 8, 20, 178);
            var heroRect = heroPanel.GetComponent<RectTransform>();
            heroRect.anchorMin = new Vector2(0.04f, 0.06f);
            heroRect.anchorMax = new Vector2(0.96f, 0.94f);
            heroRect.offsetMin = heroRect.offsetMax = Vector2.zero;

            if (_sceneLogoSprite != null)
            {
                var logoGo = new GameObject("LogoImage",
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                logoGo.transform.SetParent(heroPanel.transform, false);
                var logoImage = logoGo.GetComponent<Image>();
                logoImage.sprite = _sceneLogoSprite;
                logoImage.preserveAspect = true;
                logoImage.color = Color.white;
                var logoRect = logoGo.GetComponent<RectTransform>();
                logoRect.anchorMin = new Vector2(0.04f, 0.50f);
                logoRect.anchorMax = new Vector2(0.96f, 0.88f);
                logoRect.offsetMin = logoRect.offsetMax = Vector2.zero;
            }
            else if (modelSprite != null)
            {
                var modelGo = new GameObject("ModelImage",
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                modelGo.transform.SetParent(heroPanel.transform, false);
                var img = modelGo.GetComponent<Image>();
                img.sprite = modelSprite;
                img.preserveAspect = true;
                img.color = Color.white;
                var mr = modelGo.GetComponent<RectTransform>();
                mr.anchorMin = new Vector2(0.18f, 0.54f);
                mr.anchorMax = new Vector2(0.82f, 0.82f);
                mr.offsetMin = mr.offsetMax = Vector2.zero;
            }

            var content = new GameObject("LandingContent",
                typeof(RectTransform), typeof(VerticalLayoutGroup));
            content.transform.SetParent(heroPanel.transform, false);
            var contentRect = content.GetComponent<RectTransform>();
            contentRect.anchorMin = new Vector2(0.08f, 0.10f);
            contentRect.anchorMax = new Vector2(0.92f, 0.50f);
            contentRect.offsetMin = contentRect.offsetMax = Vector2.zero;

            var layout = content.GetComponent<VerticalLayoutGroup>();
            layout.spacing = 20f;
            layout.childControlWidth = true;
            layout.childControlHeight = true;
            layout.childForceExpandWidth = true;
            layout.childForceExpandHeight = false;
            layout.childAlignment = TextAnchor.MiddleCenter;

            if (_sceneLogoSprite == null)
            {
                AddLabel(content.transform, font, "REX LUDO", 100, FontStyle.Bold, AccentGold, 154f)
                    .alignment = TextAnchor.MiddleCenter;
            }

            AddLabel(content.transform, font, "Play. Win. Rule.", 46, FontStyle.Italic, MutedText, 68f)
                .alignment = TextAnchor.MiddleCenter;
            Spacer(content.transform, 14f);

            var loginBtn = MakeBtn(content.transform, font, "Login Account",
                AccentRed, Color.white, 56, 122f);
            loginBtn.onClick.AddListener(ShowLoginForm);

            AddLabel(content.transform, font, "OR", 38, FontStyle.Normal, MutedText, 48f)
                .alignment = TextAnchor.MiddleCenter;

            var signupBtn = MakeBtn(content.transform, font, "Create New Account",
                new Color32(0, 0, 0, 0), AccentGold, 56, 122f);
            var signupOutline = signupBtn.gameObject.AddComponent<Outline>();
            signupOutline.effectColor = AccentGold;
            signupOutline.effectDistance = new Vector2(2f, -2f);
            signupBtn.onClick.AddListener(ShowSignup);

            var guestBtn = MakeBtn(content.transform, font, "Play as Guest",
                new Color32(0, 0, 0, 0), MutedText, 44, 76f);
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

            if (modelSprite != null)
            {
                var modelGo = new GameObject("ModelSmall",
                    typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
                modelGo.transform.SetParent(_loginFormPanel.transform, false);
                var img = modelGo.GetComponent<Image>();
                img.sprite = modelSprite;
                img.preserveAspect = true;
                img.color = Color.white;
                var mr = modelGo.GetComponent<RectTransform>();
                mr.anchorMin = new Vector2(0.20f, 0.74f);
                mr.anchorMax = new Vector2(0.80f, 0.96f);
                mr.offsetMin = mr.offsetMax = Vector2.zero;
            }

            var card = new GameObject("LoginCard",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup));
            card.transform.SetParent(_loginFormPanel.transform, false);
            card.GetComponent<Image>().color = CardColor;
            var cr = card.GetComponent<RectTransform>();
            cr.anchorMin = new Vector2(0.03f, 0.04f);
            cr.anchorMax = new Vector2(0.97f, 0.76f);
            cr.offsetMin = cr.offsetMax = Vector2.zero;

            var cvl = card.GetComponent<VerticalLayoutGroup>();
            cvl.padding              = new RectOffset(40, 40, 36, 32);
            cvl.spacing              = 16f;
            cvl.childControlWidth    = true;
            cvl.childControlHeight   = true;
            cvl.childForceExpandWidth  = true;
            cvl.childForceExpandHeight = false;
            cvl.childAlignment       = TextAnchor.UpperCenter;

            // Heading — bigger
            AddLabel(card.transform, font, "Welcome Back!", 88, FontStyle.Bold, Color.white, 112f)
                .alignment = TextAnchor.MiddleCenter;

            // Sub-text
            AddLabel(card.transform, font, "Login to your account", 44, FontStyle.Normal, MutedText, 60f)
                .alignment = TextAnchor.MiddleCenter;

            // Identifier input — taller, bigger font
            AddLabel(card.transform, font, "ID / Mobile / Email", 40, FontStyle.Normal, MutedText, 52f);
            _idInput = MakeInput(card.transform, font, "Enter your ID, Mobile or Email", false, 108f, 44);

            // Password input
            AddLabel(card.transform, font, "Password", 40, FontStyle.Normal, MutedText, 52f);
            _pwInput = MakeInput(card.transform, font, "Enter your password", true, 108f, 44);

            Spacer(card.transform, 4f);

            // Login button — vivid orange-gold, white text, full contrast like social buttons
            var loginBtn = MakeBtn(card.transform, font, "LOGIN WITH REX LUDO",
                new Color32(255, 140, 0, 255), Color.white, 50, 116f);
            var loginShadow = loginBtn.gameObject.AddComponent<Shadow>();
            loginShadow.effectColor    = new Color32(140, 60, 0, 200);
            loginShadow.effectDistance = new Vector2(0f, -4f);
            loginBtn.onClick.AddListener(DoLogin);

            // Divider
            AddLabel(card.transform, font, "─── OR ───",
                34, FontStyle.Normal, MutedText, 44f).alignment = TextAnchor.MiddleCenter;

            // Social row — same height as before
            var socialRow = new GameObject("SocialRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            socialRow.transform.SetParent(card.transform, false);
            socialRow.GetComponent<LayoutElement>().preferredHeight = 96f;
            var shl = socialRow.GetComponent<HorizontalLayoutGroup>();
            shl.spacing              = 14f;
            shl.childControlWidth    = true;
            shl.childControlHeight   = true;
            shl.childForceExpandWidth  = true;
            shl.childForceExpandHeight = false;
            shl.childAlignment       = TextAnchor.MiddleCenter;

            AddSocialBtn(socialRow.transform, font, "Google",    new Color32(219,  68,  55, 255), Configuration.SocialGoogleUrl,    36);
            AddSocialBtn(socialRow.transform, font, "Facebook",  new Color32( 24, 119, 242, 255), Configuration.SocialFacebookUrl,  36);
            AddSocialBtn(socialRow.transform, font, "Instagram", new Color32(193,  53, 132, 255), Configuration.SocialInstagramUrl, 36);

            // Guest — lighter, bigger
            var guestBtn = MakeBtn(card.transform, font, "Play as Guest",
                MidRed, new Color32(255, 235, 200, 255), 40, 80f);
            guestBtn.onClick.AddListener(DoGuestLogin);

            // Back
            var backBtn = MakeBtn(card.transform, font, "← Back",
                new Color32(0, 0, 0, 0), new Color32(220, 195, 200, 255), 36, 56f);
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

        // ── State transitions ──────────────────────────────────────────────────

        private void ShowLanding()
        {
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
            string label, Color32 bg, string url, int fontSize)
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

            string capturedUrl = url;
            btn.onClick.AddListener(() => Application.OpenURL(capturedUrl));
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
