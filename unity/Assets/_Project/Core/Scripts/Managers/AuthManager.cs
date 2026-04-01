using System;
using System.Collections;
using System.Collections.Generic;
using System.Text.RegularExpressions;
using DG.Tweening;
using EasyUI.Toast;
using JetBrains.Annotations;
using Mkey;
using Newtonsoft;
using Newtonsoft.Json;
//using Profile;
using TMPro;
using UniRx;
using UnityEditor;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

//using UnityEngine.Windows.Speech;
//using static UnityEngine.Rendering.DebugUI;

namespace AndroApps
{
    public class AuthManager : MonoBehaviour
    {
        public int number;

        [Header("LogIn Fields")]
        public newLogInDetails LogInDetail;

        [Header("SignIn Fields")]
        public newSignUpDetails SignUpDetail;

        [Header("LogInOutput Response")]
        public newLogInOutputs LogInOutput;

        [Header("SignInOutput Response")]
        public newSignUpOutputs SignUpOutput;
        public bool isGuest = false;

        // [Header("Homepageprofile")]
        // public GameObject homepage;
        //  public GameObject loginpanel;

        // [Header("Current player i from playerprefs")]
        // public string id;
        // public UnityEngine.UI.Toggle toggle;
        //  public SetHomePageDetails details;

        // [Header("Profile Details")]
        // public Text username;
        // public Text userID,
        //     usercoins;
        // public Image profilepic,
        //     profilepic2;

        // [Header("ProfileDetiails")]
        // public InputField entername;
        // public InputField bankaccountnumber,
        //     adhaarnumber,
        //     UPInumber,
        //     phonenumber;

        // public RectTransform[] panels; // Assign the panels in the inspector
        // public UnityEngine.UI.Button[] buttons; // Assign the buttons in the inspector

        // [SerializeField]
        // private GameObject Setting;


        //public RectTransform panelToSlide;

        // public Toggle music,
        //     sound;

        private bool isAnimating = false;
        private bool isOpen = false;
        public GameObject forgotpanel;

        [Header("Sing up details")]
        string Mobile,
            Password,
            Name,
            Referral,
            Otp_id;

        public Toggle logintoggle,
            registertoggle;

        public List<TextMeshProUGUI> TMPPlaceholder_Text;
        public List<TextMeshProUGUI> TMPInput_Text;
        public GameBackendData data;
        public int otp_id;
        public TextMeshProUGUI number_otp,
            otp_text;

        // password_text;

        public InputField password_text;
        public GameObject otp_panel,
            password_panel,
            loginpanel;

        void OnEnable()
        {
            foreach (var txt in TMPInput_Text)
            {
                txt.color = data.inputcolor;
            }
            foreach (var txt in TMPPlaceholder_Text)
            {
                txt.color = data.placeholdercolor;
            }
        }

        private void Awake()
        {
            if (Application.platform == RuntimePlatform.Android)
            {
                EnableSystemVolumeControl();
            }
            if (!PlayerPrefs.HasKey("Reffral-ID"))
            {
                string copiedText = GUIUtility.systemCopyBuffer;
                string result = copiedText.Substring(copiedText.IndexOf('-') + 1);
                if (copiedText.Contains("777-"))
                {
                    PlayerPrefs.SetString("Reffral-ID", result);
                    //SignUpDetail.ReferralCodeInputfield.text = result;
                }
            }
        }

        public async void GuestLogin()
        {
            CommonUtil.CheckLog("Login");
            string Url = Configuration.Url + Configuration.guest_register;

            Debug.Log("GuestLogin" + Url);
            string token = GenerateToken();
            var formData = new Dictionary<string, string> { { "unique_token", token } };
            var resp = await APIManager.Instance.Post<Guest>(Url, formData);

            if (resp == null)
            {
                showtoastmessage("Guest login failed. Check backend URL and try again.");
                return;
            }

            CommonUtil.CheckLog(
                $"RES_Check  + Message: {resp.message}\nRES_Check  + Code: {resp.code}"
            );

            if (ImageUtil.Instance != null)
            {
                ImageUtil.Instance.isGuest = true;
            }
            else
            {
                Debug.LogWarning("ImageUtil.Instance is null during guest login. Continuing without guest image state.");
            }

            PlayerPrefs.SetString("id", resp.user_id);
            PlayerPrefs.SetString("token", resp.token);
            PlayerPrefs.Save();

            if (SceneLoader.Instance != null)
            {
                SceneLoader.Instance.LoadScene("HomePage");
            }
            else
            {
                Debug.LogWarning("SceneLoader.Instance is null during guest login. Falling back to SceneManager.LoadScene.");
                SceneManager.LoadScene("HomePage");
            }
        }

        public async void OpenTermsAndCondition()
        {
            CommonUtil.OpenTandC();
        }

        public async void OpenPrivacyAndPolicy()
        {
            CommonUtil.OpenPrivacyPolicy();
        }

        public static string GenerateToken()
        {
            return System.Guid.NewGuid().ToString();
        }

        void EnableSystemVolumeControl()
        {
            // Get the current Android Activity
            AndroidJavaClass unityPlayer = new AndroidJavaClass("com.unity3d.player.UnityPlayer");
            AndroidJavaObject currentActivity = unityPlayer.GetStatic<AndroidJavaObject>(
                "currentActivity"
            );

            // Run on UI Thread to set volume control stream to MEDIA
            currentActivity.Call(
                "runOnUiThread",
                new AndroidJavaRunnable(() =>
                {
                    currentActivity.Call("setVolumeControlStream", 3); // STREAM_MUSIC = 3
                })
            );
        }

        #region update data


        string FormatNumber(string number)
        {
            if (float.Parse(number) >= 1000 && float.Parse(number) < 10000)
            {
                return (float.Parse(number) / 1000f).ToString("0.0") + "k";
            }
            else if (float.Parse(number) >= 10000)
            {
                return (float.Parse(number) / 1000).ToString("0.#") + "k";
            }
            else
            {
                return number.ToString();
            }
        }

        public void showtoastmessage(string message)
        {
            Toast.Show(message, 3f);
        }

        public void forgotpassword(TMP_InputField mobilenumber)
        {
            if (mobilenumber.text.Length < 10)
            {
                showtoastmessage("Please Enter Valid Mobile Number");
            }
            else
            {
                UpdatePassword(mobilenumber.text.ToString());
            }
        }

        public async void UpdatePassword(string mobileno)
        {
            number_otp.text = "";
            password_text.text = "";
            CommonUtil.CheckLog("Login");
            if (string.IsNullOrEmpty(mobileno))
            {
                showtoastmessage("Please Enter Valid Mobile Number");
            }
            string Url = Configuration.Url + Configuration.Forgot;

            var formData = new Dictionary<string, string> { { "mobile", mobileno } };
            var resp = await APIManager.Instance.Post<OTP>(Url, formData);

            CommonUtil.CheckLog(
                $"RES_Check  + Message: {resp.message}\nRES_Check  + Code: {resp.code}"
            );

            if (resp.code == 200)
            {
                otp_id = int.Parse(resp.otp_id);
                number_otp.text = mobileno;
                showtoastmessage(resp.message);
                otp_panel.gameObject.SetActive(true);
                password_panel.gameObject.SetActive(false);
            }
            else
            {
                showtoastmessage(resp.message);
            }
        }

        public void clickreset()
        {
            if (otp_text.text.Length > 3)
            {
                ResetPassword();
            }
            else
            {
                showtoastmessage("Please Enter OTP");
            }
        }

        public async void ResetPassword() //string Token)
        {
            string Url = Configuration.Url + Configuration.UpdatePassword;
            CommonUtil.CheckLog("RES_Check + API-Call + UpdatePassword");

            if (password_text.text.Length <= 0)
            {
                showtoastmessage("Enter New Passward");
                return;
            }
            //string passwordnput = password_text.text;
            Debug.Log("PAssword Data " + password_text.text);

            var formData = new Dictionary<string, string>
            {
                { "otp", otp_text.text },
                { "otp_id", otp_id.ToString() },
                { "mobile", number_otp.text },
                { "new_password", password_text.text },
            };
            Debug.Log(JsonUtility.ToJson(formData));

            messageprint resp = new messageprint();
            resp = await APIManager.Instance.Post<messageprint>(Url, formData);

            if (resp.code == 200)
            {
                otp_panel.gameObject.SetActive(false);
                loginpanel.SetActive(true);
            }

            showtoastmessage(resp.message);
        }

        public async void updatedata(string id, string token) //string Token)
        {
            string Url = Configuration.Url + Configuration.profile;
            CommonUtil.CheckLog("RES_Check + API-Call + profile");

            var formData = new Dictionary<string, string>
            {
                { "fcm", Configuration.getFCMToken() },
                { "app_version", "1" },
                { "id", id },
                { "token", token },
            };
            newLogInOutputs LogInOutput = new newLogInOutputs();
            LogInOutput = await APIManager.Instance.Post<newLogInOutputs>(Url, formData);
            if (LogInOutput.code == 411)
            {
                logout();
            }
            if (LogInOutput.code == 200)
            {
                CommonUtil.CheckLog("RES_Check + Login Profile Data : " + LogInOutput.user_data[0]);
                PlayerPrefs.SetString("id", LogInOutput.user_data[0].id);
                PlayerPrefs.SetString("mobile", LogInOutput.user_data[0].mobile);
                PlayerPrefs.SetString("token", LogInOutput.user_data[0].token);
                PlayerPrefs.SetString("wallet", LogInOutput.user_data[0].wallet);
                PlayerPrefs.SetString("profile_pic", LogInOutput.user_data[0].profile_pic);
                PlayerPrefs.SetString("name", LogInOutput.user_data[0].name);

                PlayerPrefs.Save();
            }
            else
            {
                showtoastmessage(LogInOutput.message);
            }
        }
        #endregion

        #region Login
        public void OnClickLogIn()
        {
            AudioManager._instance?.ButtonClick();
            number = 0;
            CommonUtil.CheckLog("RES_Check Login Called");
            Password = LogInDetail.PasswordInputfield.text;
            Mobile = LogInDetail.MobileInputfield.text;
            if (Mobile == string.Empty)
            {
                showtoastmessage("Please Enter Your Login ID or Username");
                return;
            }
            else if (Password == string.Empty)
            {
                showtoastmessage("Please Enter Your Password");
                return;
            }
            else
            {
                if (logintoggle.isOn)
                    LogInId(Password, Mobile);
                else
                    showtoastmessage("Please agree with our terms & conditions to continue");
            }
        }

        public void OnClickGuest()
        {
            AudioManager._instance?.ButtonClick();
            GuestLogin();
        }

        private bool isprossesing = false;

        public async void LogInId(string Password, string Mobile) //string Token)
        {
            if (isprossesing)
                return;
            isprossesing = true;
            CommonUtil.CheckLog("Login");

            string Url = Configuration.Url + Configuration.LogIn;

            var formData = new Dictionary<string, string>
            {
                { "mobile", Mobile },
                { "password", Password },
            };
            newLogInOutputs LogInOutput = new newLogInOutputs();
            LogInOutput = await APIManager.Instance.Post<newLogInOutputs>(Url, formData);

            if (LogInOutput.code == 200)
            {
                CommonUtil.CheckLog("RES_Check + Profile Data : " + LogInOutput.user_data[0]);
                PlayerPrefs.SetString("id", LogInOutput.user_data[0].id);
                PlayerPrefs.SetString("token", LogInOutput.user_data[0].token);
                PlayerPrefs.SetString("wallet", LogInOutput.user_data[0].wallet);
                PlayerPrefs.SetString("profile_pic", LogInOutput.user_data[0].profile_pic);
                PlayerPrefs.SetString("name", LogInOutput.user_data[0].name);
                PlayerPrefs.SetString("MyEmail", LogInOutput.user_data[0].email);
                PlayerPrefs.Save();
                LogInDetail.LogInPnl.SetActive(false);
                //CurrentPackage.passbook_img = LogInOutput.user_bank_details[0].passbook_img;

                LogInDetail.PasswordInputfield.text = string.Empty;
                LogInDetail.MobileInputfield.text = string.Empty;
                GetProfileImage(LogInOutput.user_data[0].profile_pic);
                //util.instance.LoadScene("DNT");
            }
            else
            {
                CommonUtil.CheckLog("error" + LogInOutput.message);
                showtoastmessage(LogInOutput.message);
            }
            isprossesing = false;
        }
        #endregion

        public async void GetProfileImage(string profile_pic)
        {
            try
            {
                if (SpriteManager.Instance != null)
                {
                    await SpriteManager.Instance.UpdateData(
                        Configuration.GetId(),
                        Configuration.GetToken()
                    );
                }
                else
                {
                }
            }
            catch (Exception ex)
            {
                Debug.LogWarning("GetProfileImage pre-home sync failed: " + ex.Message);
            }

            if (SceneLoader.Instance != null)
            {
                SceneLoader.Instance.LoadScene("HomePage");
            }
            else
            {
                SceneManager.LoadScene("HomePage");
            }
            //SceneManager.LoadSceneAsync("HomePage");
            // StartCoroutine(
            //     ImageUtil.Instance.DownloadImage(
            //         profile_pic,
            //         Configuration.ProfileImage,
            //         sprite =>
            //         {
            //             if (sprite != null)
            //             { //                 // Use the sprite (e.g., assign to an Image component)
            //                 SpriteManager.Instance.welcome_app_banner = sprite;
            //             }
            //             else
            //             {
            //                 LogUtil.CheckLogError("Failed to download or create sprite.");
            //             }
            //         }
            //     )
            // );
        }

        #region Logout

        public void logout()
        {
            PlayerPrefs.DeleteAll();
            LogInDetail.LogInPnl.SetActive(true);
            //homepage.SetActive(false);
        }

        #endregion

        #region HomePage

        public void ShowHomePage()
        {
            //pmanager.setuserdetails();
            SceneManager.LoadSceneAsync("HomePage");
            //StartCoroutine(details.GetProfileDetails());
        }

        #endregion

        #region Signup

        public void OnClickSignUp()
        {
            CommonUtil.CheckLog("signup click");
            number   = 1;
            Password = SignUpDetail.PasswordInputfield.text;
            Referral = SignUpDetail.ReferralCodeInputfield != null
                ? SignUpDetail.ReferralCodeInputfield.text : string.Empty;

            string emailText  = SignUpDetail.EmailInputfield  != null
                ? SignUpDetail.EmailInputfield.text.Trim()  : string.Empty;
            string mobileText = SignUpDetail.MobileInputfield != null
                ? SignUpDetail.MobileInputfield.text.Trim() : string.Empty;

            bool hasEmail  = !string.IsNullOrEmpty(emailText);
            bool hasMobile = !string.IsNullOrEmpty(mobileText);

            if (!hasEmail && !hasMobile)
            {
                showtoastmessage("Please Enter Mobile Number or Email Address");
                return;
            }

            if (string.IsNullOrEmpty(Password))
            {
                showtoastmessage("Please Enter Your Password");
                return;
            }

            if (!registertoggle.isOn)
            {
                showtoastmessage("Please agree with our terms & conditions to continue");
                return;
            }

            if (hasEmail)
            {
                // ── Email mode ─────────────────────────────────────────────────
                if (!emailText.Contains("@") || !emailText.Contains("."))
                {
                    showtoastmessage("Please Enter a Valid Email Address");
                    return;
                }

                Mobile = string.Empty;
                // Auto-generate name from email prefix (Name field is hidden)
                Name = emailText.Split('@')[0];

                PostSignupWithEmail(emailText, Name, Password, Referral);
            }
            else
            {
                // ── Mobile mode ────────────────────────────────────────────────
                if (mobileText.Length < 10)
                {
                    showtoastmessage("Please Enter Valid Mobile Number");
                    return;
                }

                Mobile = mobileText;
                // Auto-generate name from mobile number (Name field is hidden)
                Name = "User" + mobileText.Substring(mobileText.Length - 4);

                PostUserSendOtp(Mobile, "register");
            }
        }

        // ── Email signup — direct register via new API (no OTP flow) ─────────
        private async void PostSignupWithEmail(
            string email,
            string name,
            string password,
            string referral
        )
        {
            string Url = Configuration.BaseUrl + "api/v1/auth/register";

            // Backend validation requires `username` to be alpha_dash (A-Z a-z 0-9 _ -).
            // Email local-part can contain dots/symbols, so sanitize it.
            string emailPrefix = email.Split('@')[0];
            string safePrefix =
                Regex.Replace(emailPrefix, @"[^A-Za-z0-9_-]", "_").Trim('_', '-');
            if (string.IsNullOrEmpty(safePrefix))
                safePrefix = "user";

            string username = safePrefix.ToLower();
            if (username.Length > 50)
                username = username.Substring(0, 50);

            var formData = new Dictionary<string, string>
            {
                { "username", username },
                { "email", email },
                { "password", password },
                { "device_name", Application.productName },
                // Laravel dot validation expects `profile.first_name` -> send as nested array.
                { "profile[first_name]", name },
            };

            // Avoid sending empty referral_code (exists validation will fail for empty string).
            if (!string.IsNullOrEmpty(referral))
                formData.Add("referral_code", referral);

            var v1Response = await APIManager.Instance.Post<AuthRegisterV1Response>(Url, formData);

            if (
                v1Response != null
                && v1Response.success
                && v1Response.data != null
                && v1Response.data.user != null
                && !string.IsNullOrEmpty(v1Response.data.user.user_code)
                && !string.IsNullOrEmpty(v1Response.data.token)
            )
            {
                PlayerPrefs.SetString("id", v1Response.data.user.user_code);
                PlayerPrefs.SetString("token", v1Response.data.token);
                PlayerPrefs.SetString("MyEmail", email);
                PlayerPrefs.Save();

                SignUpDetail?.Clear();
                if (SignUpDetail?.SignUpPnl  != null) SignUpDetail.SignUpPnl.SetActive(false);
                if (LogInDetail?.LogInPnl    != null) LogInDetail.LogInPnl.SetActive(false);

                showtoastmessage("Registered Successfully");

                if (SceneLoader.Instance != null)
                    SceneLoader.Instance.LoadScene("HomePage");
                else
                    UnityEngine.SceneManagement.SceneManager.LoadScene("HomePage");
            }
            else
            {
                showtoastmessage(v1Response?.message ?? "Registration failed. Please try again.");
            }
        }

        public async void PostUserSendOtp(string mobile, string type)
        {
            string Url = Configuration.Url + Configuration.Usersendotp;
            var formData = new Dictionary<string, string>
            {
                { "mobile", mobile },
                { "type", type },
                { "referral_code", Referral },
            };
            Debug.Log("Referral" + Referral);
            OtpManager.Instance.OtpOutput = new OtpOutputs();
            OtpManager.Instance.OtpOutput = await APIManager.Instance.Post<OtpOutputs>(
                Url,
                formData
            );
            Otp_id = OtpManager.Instance.OtpOutput.otp_id.ToString();
            CommonUtil.CheckLog("Response SignUp : " + LogInOutput.message);

            if (OtpManager.Instance.OtpOutput.code == 200)
            {
                SignUpDetail.OtpPanel.SetActive(true);
                SignUpDetail.SignUpPnl.SetActive(false);
            }
            else if (OtpManager.Instance.OtpOutput.code == 404)
            {
                showtoastmessage(OtpManager.Instance.OtpOutput.message);
            }
        }

        public void OnClickOtp()
        {
            number = 2;
            CommonUtil.CheckLog("OTP click");

            //if (OtpManager.Instance.OtpDetail.MobileInputfield.text == )
            if (OtpManager.Instance.OtpDetail.OTPCodeInputfield.text.Length <= 0)
            {
                showtoastmessage("Please Enter OTP");
            }
            else if (OtpManager.Instance.OtpDetail.OTPCodeInputfield.text.Length >= 1)
            {
                string otp = OtpManager.Instance.OtpDetail.OTPCodeInputfield.text;

                PostSignup(
                    Name,
                    Mobile,
                    Password,
                    Referral,
                    otp,
                    Otp_id,
                    "register",
                    Application.productName
                );
            }
            // else
            // {
            //     showtoastmessage("Please Enter Minimum 4 Digit");
            // }
        }

        async void PostSignup(
            string Name,
            string mobile,
            string Password,
            string Referrel,
            string otp,
            string otp_id,
            string type,
            string app
        )
        {
            string Url = Configuration.Url + Configuration.Signup;

            var formData = new Dictionary<string, string>
            {
                { "name", Name },
                { "mobile", mobile },
                { "password", Password },
                { "type", type },
                { "otp_id", otp_id },
                { "otp", otp },
                { "gender", "m" },
                { "app", app },
                { "referral_code", Referrel },
            };
            SignUpOutput = new newSignUpOutputs();
            SignUpOutput = await APIManager.Instance.Post<newSignUpOutputs>(Url, formData);
            if (SignUpOutput.code == 200)
            {
                PlayerPrefs.SetString("id", SignUpOutput.user_id);
                PlayerPrefs.SetString("token", SignUpOutput.token);
                PlayerPrefs.Save();

                if (SignUpDetail != null)
                {
                    SignUpDetail.MobileInputfield.text =
                        SignUpDetail.PasswordInputfield.text =
                        SignUpDetail.NameInputfield.text = "";

                    if (SignUpDetail.SignUpPnl != null)
                        SignUpDetail.SignUpPnl.SetActive(false);

                    if (SignUpDetail.OtpPanel != null)
                        SignUpDetail.OtpPanel.SetActive(false);
                }

                if (LogInDetail != null && LogInDetail.LogInPnl != null)
                {
                    LogInDetail.LogInPnl.SetActive(false);
                }

                CommonUtil.CheckLog("RES_Check + Register");
                showtoastmessage("Registered Successfully");

                if (SceneLoader.Instance != null)
                {
                    SceneLoader.Instance.LoadScene("HomePage");
                }
                else
                {
                    Debug.LogWarning("SceneLoader.Instance is null after signup. Falling back to SceneManager.LoadScene.");
                    SceneManager.LoadScene("HomePage");
                }
            }
            else
            {
                showtoastmessage(SignUpOutput.message);
            }
        }

        #endregion

        #region comming soon

        public void CommingSoon()
        {
            showtoastmessage("Comming Soon");
        }

        #endregion

        #region DoTween panel animation

        private void Start()
        {
            Screen.sleepTimeout = SleepTimeout.NeverSleep;
            ClosePanel();
            ConfigureLoginIdentifierField();
            if (SignUpDetail.ReferralCodeInputfield != null)
                SignUpDetail.ReferralCodeInputfield.text = PlayerPrefs.GetString("Reffral-ID");

            // Patch signup panel with Email/Mobile toggle buttons (code-driven, no scene edit)
            var patcher = gameObject.AddComponent<SignupTogglePatcher>();
            patcher.Initialize(this);

            // Add listeners to each button
            // for (int i = 0; i < buttons.Length; i++)
            // {
            //     int index = i; // Required to capture the correct value of i in lambda expression
            //     buttons[i].onClick.AddListener(() => AnimatePanel(panels[index]));
            // }
        }

        private void ConfigureLoginIdentifierField()
        {
            if (LogInDetail == null || LogInDetail.MobileInputfield == null)
                return;

            var loginField = LogInDetail.MobileInputfield;
            loginField.contentType = TMP_InputField.ContentType.Standard;
            loginField.characterValidation = TMP_InputField.CharacterValidation.None;
            loginField.lineType = TMP_InputField.LineType.SingleLine;
            loginField.keyboardType = TouchScreenKeyboardType.Default;

            if (loginField.placeholder != null)
            {
                var placeholder = loginField.placeholder.GetComponent<TMP_Text>();
                if (placeholder != null)
                    placeholder.text = "Enter User ID / Username / Email / Mobile";
            }

            loginField.ForceLabelUpdate();
        }

        private void AnimatePanel(RectTransform panel)
        {
            if (isAnimating)
                return; // Prevent multiple clicks while animating

            isAnimating = true;
            // Ensure the panel is initially scaled down
            panel.localScale = Vector3.zero;

            // Animate the panel to scale up to its full size
            panel
                .DOScale(Vector3.one, 0.5f)
                .SetEase(Ease.OutBack)
                .OnComplete(() =>
                {
                    isAnimating = false; // Allow animation again after completion
                });
        }

        public void TogglePanel()
        {
            if (isOpen)
            {
                ClosePanel();
            }
            else
            {
                OpenPanel();
            }
        }

        public void OpenPanel()
        {
            isOpen = true;
            //   panelToSlide.DOAnchorPosX(0, 0.5f).SetEase(Ease.OutQuint);
        }

        public void ClosePanel()
        {
            isOpen = false;
            //panelToSlide.DOAnchorPosX(panelToSlide.rect.width, 1f).SetEase(Ease.OutQuint);
        }
        #endregion

        #region Clear text on click when going to new panel

        public void OnClickClearDatainLogin()
        {
            LogInDetail.MobileInputfield.text = "";
            LogInDetail.PasswordInputfield.text = "";
        }

        public void OnClearSignUpDetails()
        {
            SignUpDetail.Clear();
        }

        #endregion

        #region Go to url

        public void OnClickTandC()
        {
            //Application.OpenURL(Configuration.TermsAndCondititon);
            showtoastmessage("Cannot open this link in Demo Version");
        }

        public void OnClickPrivacyAndPolicy()
        {
            openwebview(Configuration.Website + "privacy-policy.php");
            //Application.OpenURL(Configuration.PrivacyAndpolicy);
            //   showtoastmessage("Cannot open this link in Demo Version");
        }

        public void OnClickContactUs()
        {
            //Application.OpenURL(Configuration.ContactUs);
            showtoastmessage("Cannot open this link in Demo Version");
        }

        public void OnClickDeleteAcc()
        {
            //Application.OpenURL(Configuration.DeleteAccount);
            showtoastmessage("Cannot open this link in Demo Version");
        }

        #endregion
        public void OpenTurmCondition()
        {
            openwebview(Configuration.Website + "terms-conditions.php");
        }

        public void openwebview(string url)
        {
            // LogUtil.CheckLog("RES_check + open" + url);
            // NewAudioManager.instance?.PlayButtonSound();
            // util.instance.ShowUrlPopupPositionSize(url);
            Application.OpenURL(url);
        }

        public void ShowPassward(bool m_on)
        {
            if (m_on)
            {
                LogInDetail.PasswordInputfield.contentType = TMP_InputField.ContentType.Standard;
                SignUpDetail.PasswordInputfield.contentType = TMP_InputField.ContentType.Standard;
            }
            else
            {
                LogInDetail.PasswordInputfield.contentType = TMP_InputField.ContentType.Password;
                SignUpDetail.PasswordInputfield.contentType = TMP_InputField.ContentType.Password;
            }
            LogInDetail.PasswordInputfield.ForceLabelUpdate();
            SignUpDetail.PasswordInputfield.ForceLabelUpdate();
        }
    }
}
