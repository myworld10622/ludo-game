using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Reflection;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using System.Xml.Serialization;
using AndroApps;
using EasyButtons;
using EasyUI.Toast;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using LudoClassicOffline;
#if UNITY_EDITOR
using UnityEditor;
using UnityEditor.SceneManagement;
#endif

[ExecuteAlways]
public class Profile : MonoBehaviour
{
    [Header("User Detiails")]
    public Image profilepic;
    public Image profilesettingpic;
    public Image profilesettingpic2;
    public Text wallet,
        name,
        id;
    public TextMeshProUGUI profilewallet,
        profilename,
        profileid;

    [Header("Profile Detiails")]
    public InputField entername;
    public InputField EmailAddressInputField,
        phonenumber;
    public Texture2D texture2d;

    [Header("Persistent Profile Editor")]
    [SerializeField] private RectTransform profileEditorRoot;
    [SerializeField] private InputField profileNameEditor;
    [SerializeField] private InputField profileEmailEditor;
    [SerializeField] private InputField profilePhoneEditor;
    [SerializeField] private InputField profileGenderEditor;
    [SerializeField] private InputField profileDobEditor;
    [SerializeField] private InputField profileStateEditor;
    [SerializeField] private InputField profileCityEditor;
    [SerializeField] private Text profileEditorStatus;
    private float lastProfileEditorNormalizeAt = -10f;

    [Header("Update Password")]
    public InputField oldpassword;
    public InputField newpassword;

    [Header("Update Bank Details")]
    public InputField IFSCCode;
    public InputField account_number;
    public InputField account_holder_name;
    public InputField bank_name;
    public InputField upi_id;
    public GameObject passbook_logo_img;
    public Image passbook_img;
    public GameObject bank_selected;
    public GameObject bank_panel;

    [Header("Crypto Wallet")]
    public InputField crypto_address;
    public InputField crypto_wallet_type;
    public GameObject crypto_logo_img;
    public Image crypto_img;
    public GameObject crypto_selected;
    public GameObject crypto_panel;

    [Header("KYC Details")]
    public InputField aadhar_no;
    public InputField pan_no;
    public GameObject aadhar_logo_img;
    public Image aadhar_img;
    public GameObject pan_logo_img;
    public Image pan_img;

    [Header("Avatar Panel")]
    /*  private List<string> avatarname = new List<string>();
     private List<Image> images = new List<Image>(); */
    public GameObject avatar;
    public GameObject avatar_penal;
    public Transform content;
    public Sprite selectedavatar;
    UserSettingOutPuts settingputput;
    public BannerManager banner;

    [Header("Games Button")]
    public List<GameObject> allgames,
        rummygames,
        smallgames,
        roulettegames,
        coingames,
        allhistory,
        Slotgames;
    public List<string> activegamenames,
        activegamenamesinunity;
    public List<playbuttongames> games;
    public List<playbuttongames> activegames;
    public List<GameObject> activehistory;
    public GameObject gridlayoutgroup,
        gamecontent;

    #region private variables

    newLogInOutputs LogInOutput;

    public Animator RefreshWallet;
    public GameSelection selection;

    #endregion

    #region  popup

    public GameObject ProfilePopup;

    public void PopUpPanelOpen(GameObject obj)
    {
        Debug.Log("OpenPopupName:" + obj.name);
        if (obj.name == "Profile")
        {
            profilesettingpic2.sprite = profilepic.sprite;
            PopUpUtil.ButtonClick(obj);
            EnsureProfileEditorBindings(true);
            ApplyProfileDataToEditor();
            if (Application.isPlaying)
                StartCoroutine(RefreshProfileEditorVisualsDelayed());
            return;
        }
        else if (obj.name == "Bank Details")
        {
            // IFSCCode.text = string.Empty;
            // account_holder_name.text = string.Empty;
            // account_number.text = string.Empty;
            // bank_name.text = string.Empty;
        }
        else if (obj.name == "Bank Details")
        {
            // IFSCCode.text = string.Empty;
            // account_holder_name.text = string.Empty;
            // account_number.text = string.Empty;
            // bank_name.text = string.Empty;
            EnsureBankDetailBindings();
        }
        PopUpUtil.ButtonClick(obj);
    }

    public void PopUpPanelClose(GameObject obj)
    {
        PopUpUtil.ButtonCancel(obj);
    }

    public void OpenFriendRequests()
    {
        GameObject bootstrap = GameObject.Find("HomePageFriendBootstrap");
        if (bootstrap == null)
        {
            bootstrap = new GameObject("HomePageFriendBootstrap");
        }

        if (bootstrap.GetComponent<LudoFriendApiService>() == null)
        {
            bootstrap.AddComponent<LudoFriendApiService>();
        }

        LudoFriendPanelController controller = bootstrap.GetComponent<LudoFriendPanelController>();
        if (controller == null)
        {
            controller = bootstrap.AddComponent<LudoFriendPanelController>();
        }

        controller.SetRoomActionAvailability(false);
        controller.SetHomeShortcutAvailability(false);
        controller.OpenHomePanelFromShortcut();
    }

    #endregion

    #region show existing details

    void Awake()
    {
        if (!Application.isPlaying)
        {
#if UNITY_EDITOR
            EditorApplication.delayCall -= EnsureProfileEditorInEditor;
            EditorApplication.delayCall += EnsureProfileEditorInEditor;
#endif
            return;
        }

        if (SpriteManager.Instance != null && profilepic != null)
        {
            profilepic.sprite = SpriteManager.Instance.profile_image;
        }
        selection = this.GetComponent<GameSelection>();
        EnsureBankDetailBindings();
        HideNonLudoGamesImmediately();
    }

    async void OnEnable()
    {
        if (!Application.isPlaying)
        {
            EnsureProfileEditorBindings(true);
            ApplyProfileDataToEditor();
            return;
        }

        // #if UNITY_WEBGL
        //         allgames.Find(game => game.name == "color_prediction_vertical").SetActive(false);
        //         GridLayoutGroup layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
        //         layoutGroup.constraintCount = 4;
        // #endif
        HideNonLudoGamesImmediately();
        EnsureBankDetailBindings();
        SetUserProfileDetails();
        //AudioManager._instance.StopBackgroundAudio();
        await UpdateData(Configuration.GetId(), Configuration.GetToken());
        await InitializeGamesAsync();
        await ShowGamesAsync(0);

    }

    private void LateUpdate()
    {
        if (!Application.isPlaying || ProfilePopup == null || !ProfilePopup.activeInHierarchy)
            return;

        if (Time.unscaledTime - lastProfileEditorNormalizeAt < 0.2f)
            return;

        if (NeedsProfileEditorRefresh())
        {
            EnsureProfileEditorBindings(true);
            lastProfileEditorNormalizeAt = Time.unscaledTime;
        }
    }

#if UNITY_EDITOR
    private void OnValidate()
    {
        if (Application.isPlaying)
            return;

        EditorApplication.delayCall -= EnsureProfileEditorInEditor;
        EditorApplication.delayCall += EnsureProfileEditorInEditor;
    }

    private void EnsureProfileEditorInEditor()
    {
        if (this == null || gameObject == null)
            return;

        EnsureProfileEditorBindings(true);
        ApplyProfileDataToEditor();

        if (gameObject.scene.IsValid())
            EditorSceneManager.MarkSceneDirty(gameObject.scene);
    }

    private void Reset()
    {
        if (Application.isPlaying)
            return;

        EditorApplication.delayCall -= EnsureProfileEditorInEditor;
        EditorApplication.delayCall += EnsureProfileEditorInEditor;
    }

    [ContextMenu("Rebuild Persistent Profile Editor")]
    private void RebuildPersistentProfileEditor()
    {
        if (profileEditorRoot != null)
        {
            Undo.DestroyObjectImmediate(profileEditorRoot.gameObject);
        }

        profileEditorRoot = null;
        profileNameEditor = null;
        profileEmailEditor = null;
        profilePhoneEditor = null;
        profileGenderEditor = null;
        profileDobEditor = null;
        profileStateEditor = null;
        profileCityEditor = null;
        profileEditorStatus = null;

        EnsureProfileEditorBindings(true);
        ApplyProfileDataToEditor();

        if (gameObject.scene.IsValid())
            EditorSceneManager.MarkSceneDirty(gameObject.scene);
    }
#endif
    /// <summary>
    /// Start is called on the frame when a script is enabled just before
    /// any of the Update methods is called the first time.
    /// </summary>
    private void Start()
    {

        // #if UNITY_WEBGL
        //         allgames.Find(game => game.name == "color_prediction_vertical").SetActive(false);
        //         GridLayoutGroup layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
        //         layoutGroup.constraintCount = 4;
        // #else
        //         GridLayoutGroup layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
        //         layoutGroup.constraintCount = 4;
        // #endif

    }

    public async void OpenTermsAndCondition()
    {
        CommonUtil.OpenTandC();
    }

    public async void OpenPrivacyAndPolicy()
    {
        CommonUtil.OpenPrivacyPolicy();
    }

    #endregion

    #region load Home Page
    public async void GetRandomNotifications()
    {
        string url = Configuration.GameNotificcations;
        var formData = new Dictionary<string, string> { { "user_id", "" }, { "token", "" } };
        notificationUserList notifications = await APIManager.Instance.Post<notificationUserList>(
            url,
            formData
        );
        await InitializeGamesAsync();
        Debug.Log($"RES+Message: {notifications.message}\nRES+Code: {notifications.code}");
    }

    public async void GetProfileImage(string profile_pic)
    {
        string profile_url = Configuration.ProfileImage + profile_pic;
        SpriteManager.Instance.profile_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
            profile_url
        );
    }

    public async Task GetBannerImage(string notificationpic)
    {
        if (SpriteManager.Instance == null)
        {
            return;
        }

        string image_url = Configuration.NotificationBannerImage + notificationpic;
        SpriteManager.Instance.welcome_app_banner = await ImageUtil.Instance.GetSpriteFromURLAsync(
            image_url
        );

        if (banner != null)
        {
            banner.enabled = true;
        }
    }

    public async void PostUserSetting(string url)
    {
        if (APIManager.Instance == null)
        {
            Debug.LogWarning("Profile.PostUserSetting skipped because APIManager.Instance is null.");
            return;
        }

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        var UserSettingOutPut = await APIManager.Instance.Post<UserSettingOutPuts>(url, formData);

        if (UserSettingOutPut == null)
        {
            Debug.LogWarning("Profile.PostUserSetting received a null response.");
            return;
        }

        Debug.Log($"RES+Message: {UserSettingOutPut.message}\nRES+Code: {UserSettingOutPut.code}");

        Debug.Log("RES_Check + getting images");

        if (SpriteManager.Instance == null)
        {
            Debug.LogWarning("Profile.PostUserSetting skipped banner/avatar preload because SpriteManager.Instance is null.");
            return;
        }

        if (SpriteManager.Instance.app_banner == null)
        {
            SpriteManager.Instance.app_banner = new List<Sprite>();
        }

        if (SpriteManager.Instance.app_banner_name == null)
        {
            SpriteManager.Instance.app_banner_name = new List<string>();
        }

        if (SpriteManager.Instance.avatar == null)
        {
            SpriteManager.Instance.avatar = new List<Sprite>();
        }

        SpriteManager.Instance.app_banner.Clear();
        SpriteManager.Instance.app_banner_name.Clear();
        if (UserSettingOutPut.app_banner != null)
        {
            for (int i = 0; i < UserSettingOutPut.app_banner.Count; i++)
            {
                Debug.Log("RES_Check + getting images");
                string app_banner_image_url =
                    Configuration.BannerImage + UserSettingOutPut.app_banner[i].banner;
                Sprite bannerSprite = ImageUtil.Instance != null
                    ? await ImageUtil.Instance.GetSpriteFromURLAsync(app_banner_image_url)
                    : null;

                if (bannerSprite != null)
                {
                    SpriteManager.Instance.app_banner.Add(bannerSprite);
                }

                SpriteManager.Instance.app_banner_name.Add(UserSettingOutPut.app_banner[i].banner);
            }
        }

        if (LogInOutput != null)
        {
            if (!string.IsNullOrWhiteSpace(LogInOutput.notification_image))
            {
                await GetBannerImage(LogInOutput.notification_image);
            }
            await DownloadProfileImage();
        }

        SpriteManager.Instance.avatar.Clear();
        if (UserSettingOutPut.avatar != null)
        {
            for (int i = 0; i < UserSettingOutPut.avatar.Count; i++)
            {
                await DownloadAvatarImage(UserSettingOutPut.avatar[i]);
            }
        }
        SetUserProfileDetails();
        SetBankDetails();
        SetCryptoDetails();
        SetKYCDetails();
    }

    public async Task DownloadProfileImage()
    {
        Debug.Log(
            "RES_check + Profile image download 3 "
                + Configuration.ProfileImage
                + Configuration.GetProfilePic()
        );
        string app_avatar_image_url = Configuration.ProfileImage + Configuration.GetProfilePic();
        Debug.Log(
            "RES_check + Avatar " + Configuration.ProfileImage + Configuration.GetProfilePic()
        );
        SpriteManager.Instance.profile_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
            app_avatar_image_url
        );
        Debug.Log("RES_check + Profile image download 3");
    }

    public async Task DownloadAvatarImage(string pic_url)
    {
        Debug.Log("RES_check + avatar image download 3 " + Configuration.ProfileImage + pic_url);
        string app_avatar_image_url = Configuration.ProfileImage + pic_url;
        Debug.Log("RES_check + Avatar " + Configuration.ProfileImage + pic_url);
        SpriteManager.Instance.avatar.Add(
            await ImageUtil.Instance.GetSpriteFromURLAsync(app_avatar_image_url)
        );
        Debug.Log("RES_check + Avatar image download 3");
    }
    #endregion

    #region Switch Bank Details and Crypto Details

    public void SwitchBankAndCrypto(int i)
    {
        if (i == 0)
        {
            bank_panel.SetActive(true);
            bank_selected.SetActive(true);
            crypto_panel.SetActive(false);
            crypto_selected.SetActive(false);
        }
        else
        {
            bank_panel.SetActive(false);
            bank_selected.SetActive(false);
            crypto_panel.SetActive(true);
            crypto_selected.SetActive(true);
        }
    }

    #endregion

    #region update basic details
    public void UpdateProfileDetails()
    {
        EnsureProfileEditorBindings(true);

        string enteredName = GetProfileEditorValue(profileNameEditor, entername);
        string enteredGenderRaw = GetProfileEditorValue(profileGenderEditor, null);
        string enteredGender = NormalizeGender(enteredGenderRaw);
        string enteredDob = GetProfileEditorValue(profileDobEditor, null);

        if (string.IsNullOrWhiteSpace(enteredName))
        {
            ShowToastSafe("Please Enter Name");
            return;
        }

        if (!string.IsNullOrWhiteSpace(enteredGenderRaw) && string.IsNullOrWhiteSpace(enteredGender))
        {
            ShowToastSafe("Please Enter Gender as male, female, or other");
            return;
        }

        if (!string.IsNullOrWhiteSpace(enteredDob) && !DateTime.TryParse(enteredDob, out _))
        {
            ShowToastSafe("Please Enter Date Of Birth in valid format");
            return;
        }

        PlayerPrefs.SetString("name", enteredName);

        SetProfileDetails();
    }

    private void SaveProfileDetails()
    {
        string profileName = GetProfileEditorValue(profileNameEditor, entername);
        string profileEmail = GetStoredEmail();

        PlayerPrefs.SetString("name", profileName);
        PlayerPrefs.SetString("MyEmail", profileEmail);
        PlayerPrefs.SetString("email", profileEmail);
    }

    private void UpdateUI()
    {
        string updatedName = Configuration.GetName();
        name.text = updatedName;
        profilename.text = updatedName;
        profilepic.sprite = SpriteManager.Instance.profile_image;
        profilesettingpic.sprite = SpriteManager.Instance.profile_image;
        profilesettingpic2.sprite = SpriteManager.Instance.profile_image;
    }

    private bool IsEmailValid(string email)
    {
        if (email.Contains("@") && email.Contains("."))
        {
            return true;
        }
        return false;
    }
    private bool IsValidEmail(string email)
    {
        // Ensure email does NOT contain spaces and follows a valid format
        string pattern = @"^[^\s@]+@[^\s@]+\.[^\s@]+$";

        if (email.Contains(" "))  // Reject if spaces exist anywhere
        {
            return false;
        }

        return Regex.IsMatch(email, pattern);
    }


    public async void SetProfileDetails()
    {
        string Url = Configuration.Url + Configuration.Update_profile;
        Debug.Log("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "email", GetStoredEmail() },
            { "name", GetProfileEditorValue(profileNameEditor, entername) },
            { "gender", NormalizeGender(GetProfileEditorValue(profileGenderEditor, null)) },
            { "date_of_birth", NormalizeDateForApi(GetProfileEditorValue(profileDobEditor, null)) },
            { "state", GetProfileEditorValue(profileStateEditor, null) },
            { "city", GetProfileEditorValue(profileCityEditor, null) },
            { "profile_pic", SpriteManager.Instance.base64forimgrofile },
        };
        UpdateProfileOutputs details = new UpdateProfileOutputs();
        details = await APIManager.Instance.Post<UpdateProfileOutputs>(Url, formData);
        Debug.Log($"RES+Message: {details.message}\nRES+Code: {details.code}");

        if (details.code == 200)
        {
            string actualEmail = GetStoredEmail();
            string actualName = GetProfileEditorValue(profileNameEditor, entername);
            string actualGender = NormalizeGender(GetProfileEditorValue(profileGenderEditor, null));
            string actualDob = NormalizeDateForApi(GetProfileEditorValue(profileDobEditor, null));
            string actualState = GetProfileEditorValue(profileStateEditor, null);
            string actualCity = GetProfileEditorValue(profileCityEditor, null);

            if (entername != null) entername.text = actualName;
            if (EmailAddressInputField != null) EmailAddressInputField.text = actualEmail;
            SaveProfileDetails();
            CacheProfileData(actualName, actualEmail, actualGender, actualDob, actualState, actualCity);
            UpdateUI();
            PopUpPanelClose(ProfilePopup);
            ResetFieldUpdatePassword();
            ShowToastSafe("Profile Updated Successfully");
            selectedavatar = null;
        }
        else if (!string.IsNullOrWhiteSpace(details.message))
        {
            ShowToastSafe(details.message);
        }
    }

    public static string MaskMobile(string mobile)
    {
        if (string.IsNullOrEmpty(mobile) || mobile.Length != 10)
            return "Invalid Mobile Number";

        string firstFour = mobile.Substring(0, 4);
        string lastTwo = mobile.Substring(mobile.Length - 2);
        string maskedMiddle = new string('*', 4);

        return $"{firstFour}{maskedMiddle}{lastTwo}";
    }

    public static string MaskEmail(string email)
    {
        if (string.IsNullOrEmpty(email) || !email.Contains("@"))
            return string.Empty;

        string[] parts = email.Split('@');
        string username = parts[0];
        string domain = parts[1];

        int halfLength = username.Length / 2;
        string firstHalf = username.Substring(0, halfLength);
        string maskedHalf = new string('*', username.Length - halfLength);

        return $"{firstHalf}{maskedHalf}@{domain}";
    }

    #endregion

    #region update profile pic

    public void OnUpdateProfileImageButtonClick(string target)
    {
        CommonUtil.CheckLog("Clicked profile image");
        ImageUtil.Instance.OpenGallery(target, profilesettingpic2, null);
    }

    // Method to open the gallery and get the image path
    public async Task UpdateProfileImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    #endregion

    #region logout

    public void logout()
    {
        PlayerPrefs.DeleteAll();
        GetComponent<SettingManager>().ResetSettingButtons();
        selection.loaddynamicscenebyname("LoginRegister");
    }

    #endregion

    #region update password

    public void ResetFieldUpdatePassword()
    {
        oldpassword.text = "";
        newpassword.text = "";
    }

    public async void OnUpdatePassword()
    {
        if (oldpassword.text.Length == 0)
        {
            ShowToastSafe("Please fill old password details");
        }
        else if (newpassword.text.Length == 0)
        {
            ShowToastSafe("Please fill new password details");
        }
        else
        {
            await PostUpdatePassword(oldpassword.text, newpassword.text);
        }
    }

    #endregion

    #region Update Bank Details


    public void OnUpdatePassbookImageButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery("passbook", passbook_img, passbook_logo_img);
        passbook_img.transform.parent.gameObject.SetActive(true);
    }

    // Method to open the gallery and get the image path
    public async Task UpdatePassbookImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    public async void OnUpdateBankDetails()
    {
        EnsureBankDetailBindings();

        if (bank_name.text.Length == 0)
        {
            ShowToastSafe("Please fill bank details");
        }
        else if (account_number.text.Length == 0)
        {
            ShowToastSafe("Please fill account number");
        }
        else if (account_holder_name.text.Length == 0)
        {
            ShowToastSafe("Please fill account holder name");
        }
        else if (IFSCCode.text.Length == 0)
        {
            ShowToastSafe("Please fill IFSC Code");
        }
        else
        {
            await PostUpdateBankDetails(
                IFSCCode.text,
                account_holder_name.text,
                bank_name.text,
                account_number.text,
                GetSelectedPassbookBase64(),
                upi_id != null ? upi_id.text : string.Empty
            );
        }
    }

    #endregion

    #region  update Crypto Details

    public void OnUpdateCryptoImageButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery(target, crypto_img, crypto_logo_img);
        crypto_img.transform.parent.gameObject.SetActive(true);
    }

    // Method to open the gallery and get the image path
    public async Task UpdateCryptoImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    public async void OnUpdateCryptoDetails()
    {
        if (crypto_address.text.Length == 0)
        {
            ShowToastSafe("Please fill crypto address");
        }
        else if (crypto_wallet_type.text.Length == 0)
        {
            ShowToastSafe("Please fill crypto wallet");
        }
        else if (SpriteManager.Instance.base64forimgcrypto.Length == 0)
        {
            ShowToastSafe("Please upload crypto image");
        }
        else
        {
            await PostUpdateCryptoDetails(
                crypto_address.text,
                crypto_wallet_type.text,
                SpriteManager.Instance.base64forimgpassbook
            );
        }
    }

    #endregion

    #region Update KYC Details

    public void OnUpdateAadharImageButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery(target, aadhar_img, aadhar_logo_img);
        aadhar_img.transform.parent.gameObject.SetActive(true);
    }

    // Method to open the gallery and get the image path
    public async Task UpdateAadharImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() =>
        {
            ImageUtil.Instance.OpenGallery(target, aadhar_img, aadhar_logo_img);
        });
    }

    public void OnUpdatePanImageButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery(target, pan_img, pan_logo_img);
        pan_img.transform.parent.gameObject.SetActive(true);
    }

    // Method to open the gallery and get the image path
    public async Task UpdatePanImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() =>
        {
            ImageUtil.Instance.OpenGallery(target, pan_img, pan_logo_img);
        });
    }

    public async void OnUpdate_kyc()
    {
        if (aadhar_no.text.Length == 0)
        {
            ShowToastSafe("Please fill your aadhar number");
        }
        else if (SpriteManager.Instance.base64forimgaadhar.Length == 0)
        {
            ShowToastSafe("Please upload your aadhar photo");
        }
        else if (pan_no.text.Length == 0)
        {
            ShowToastSafe("Please fill your pan number");
        }
        else if (SpriteManager.Instance.base64forimgpan.Length == 0)
        {
            ShowToastSafe("Please upload your pan image");
        }
        else
        {
            await PostUpdateKYDetails(
                aadhar_no.text,
                SpriteManager.Instance.base64forimgaadhar,
                pan_no.text,
                SpriteManager.Instance.base64forimgpan
            );
        }
    }

    #endregion

    #region Backend Avatars

    public void OnClickAvatar()
    {
        ShowAvatars();
    }

    private List<GameObject> AvatarReset = new List<GameObject>();

    public void ShowAvatars()
    {
        /*   foreach (Transform obj in content)
          {
              Destroy(obj);
          } */
        avatar_penal.SetActive(true);
        AvatarReset.ForEach(x => Destroy(x));
        for (int i = 0; i < SpriteManager.Instance.avatar.Count; i++)
        {
            GameObject go = Instantiate(avatar, content);
            go.gameObject.name = SpriteManager.Instance.avatar_name[i];
            Debug.Log("RES_Check + name " + go.name);
            AvatarReset.Add(go);
            //images.Add(go.transform.GetChild(0).GetComponent<Image>());
            Debug.Log("RES_Check + fetch images");
            go.transform.GetChild(0).GetComponent<Image>().sprite = SpriteManager.Instance.avatar[
                i
            ];
            go.transform.GetComponent<Button>().onClick.AddListener(() => avataringname(go));
        }
    }

    public void avataringname(GameObject name)
    {
        int count = content.childCount;
        for (int i = 0; i < count; i++)
        {
            Image img = content.GetChild(i).GetChild(0).GetComponent<Image>();
            img.color = new Color(img.color.r, img.color.g, img.color.b, 1f);
        }
        texture2d = name.transform.GetChild(0).GetComponent<Image>().sprite.texture;
        Image selectedImg = name.transform.GetChild(0).GetComponent<Image>(); //1206
        selectedImg.color = new Color(
            selectedImg.color.r,
            selectedImg.color.g,
            selectedImg.color.b,
            0.5f
        );

        selectedavatar = name.transform.GetChild(0).GetComponent<Image>().sprite;
    }

    public void confirm()
    {
        if (selectedavatar != null)
        {
            Texture2D readableTexture = ConvertToUncompressed(texture2d);
            // Encode the texture to PNG and save it as a Base64 string
            byte[] imageBytes = readableTexture.EncodeToPNG();
            string base64Image = Convert.ToBase64String(imageBytes);
            PlayerPrefs.SetString("profile_pic", base64Image);
            PlayerPrefs.Save();

            SpriteManager.Instance.base64forimgrofile = base64Image;
            SpriteManager.Instance.profile_image = selectedavatar;

            profilepic.sprite = selectedavatar;
            profilesettingpic.sprite = selectedavatar;
            profilesettingpic2.sprite = selectedavatar;

            SetProfileDetails();
            avatar_penal.SetActive(false);
        }
        else
        {
            CommonUtil.ShowToast("Please Select Avatar");
        }
    }

    public Texture2D ConvertToUncompressed(Texture2D originalTexture)
    {
        // Check if the original texture is null
        if (originalTexture == null)
        {
            Debug.LogError("Original texture is null.");
            return null;
        }

        // Create a new texture in RGBA32 format, which is readable
        Texture2D uncompressedTexture = new Texture2D(
            originalTexture.width,
            originalTexture.height,
            TextureFormat.RGBA32,
            false
        );

        // If the original texture is not readable, you cannot get its pixels directly
        // You might need to use a different approach if the original texture is not readable.
        // This example assumes you have already downloaded the texture correctly.

        // Get the pixels of the original texture using a method that ensures you can access them
        Color[] pixels = originalTexture.GetPixels(); // This will fail if the original is not readable

        // Set the pixels to the new uncompressed texture
        uncompressedTexture.SetPixels(pixels);
        uncompressedTexture.Apply(); // Apply changes to the new texture

        return uncompressedTexture;
    }

    #endregion

    #region  Set Details

    public void SetKYCDetails()
    {
        if (LogInOutput.user_kyc.Count == 1)
        {
            aadhar_no.text = LogInOutput.user_kyc[0].aadhar_no;
            pan_no.text = LogInOutput.user_kyc[0].pan_no;
            DownloadPanImage();
            DownloadAadharImage();
        }
    }

    public async void DownloadAadharImage()
    {
        if (LogInOutput.user_kyc[0].aadhar_img != "")
        {
            string aadhar_image_url =
                Configuration.ProfileImage + LogInOutput.user_kyc[0].aadhar_img;
            SpriteManager.Instance.aadhar_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
                aadhar_image_url
            );
            aadhar_img.transform.parent.gameObject.SetActive(true);
            aadhar_img.sprite = SpriteManager.Instance.aadhar_image;
            aadhar_logo_img.SetActive(false);
        }
    }

    public async void DownloadPanImage()
    {
        if (LogInOutput.user_kyc[0].pan_img != "")
        {
            string pan_image_url = Configuration.ProfileImage + LogInOutput.user_kyc[0].pan_img;
            SpriteManager.Instance.pan_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
                pan_image_url
            );
            pan_img.transform.parent.gameObject.SetActive(true);
            pan_img.sprite = SpriteManager.Instance.pan_image;
            pan_logo_img.SetActive(false);
        }
    }

    public void SetBankDetails()
    {
        EnsureBankDetailBindings();
        if (LogInOutput.user_bank_details.Count == 1)
        {
            if (IFSCCode != null) IFSCCode.text = LogInOutput.user_bank_details[0].ifsc_code;
            if (account_holder_name != null) account_holder_name.text = LogInOutput.user_bank_details[0].acc_holder_name;
            if (account_number != null) account_number.text = LogInOutput.user_bank_details[0].acc_no;
            if (bank_name != null) bank_name.text = LogInOutput.user_bank_details[0].bank_name;
            if (upi_id != null) upi_id.text = LogInOutput.user_bank_details[0].upi_id;
            DownloadPassbookImage();
        }
    }

    public async void DownloadPassbookImage()
    {
        EnsureBankDetailBindings();
        if (LogInOutput.user_bank_details[0].passbook_img != "")
        {
            string passbook_image_url =
                Configuration.ProfileImage + LogInOutput.user_bank_details[0].passbook_img;
            SpriteManager.Instance.passbook_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
                passbook_image_url
            );
            if (passbook_img != null)
            {
                passbook_img.transform.parent.gameObject.SetActive(true);
                passbook_img.sprite = SpriteManager.Instance.passbook_image;
            }
            if (passbook_logo_img != null)
            {
                passbook_logo_img.SetActive(false);
            }
        }
    }

    public void SetCryptoDetails()
    {
        if (LogInOutput.user_bank_details.Count == 1)
        {
            crypto_address.text = LogInOutput.user_bank_details[0].crypto_address;
            crypto_wallet_type.text = LogInOutput.user_bank_details[0].crypto_wallet_type;
            DownloadCryptoImage();
        }
    }

    public async void DownloadCryptoImage()
    {
        if (LogInOutput.user_bank_details[0].crypto_qr != "")
        {
            string crypto_image_url =
                Configuration.ProfileImage + LogInOutput.user_bank_details[0].crypto_qr;
            SpriteManager.Instance.crypto_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
                crypto_image_url
            );
            crypto_img.transform.parent.gameObject.SetActive(true);
            crypto_img.sprite = SpriteManager.Instance.crypto_image;
            crypto_logo_img.SetActive(false);
        }
    }

    public void SetUserProfileDetails()
    {
        // Populate UI elements after updatedata is completed
        string currentWallet = Configuration.GetWallet();
        string currentId = new StringBuilder().Append("ID :").Append(Configuration.GetId()).ToString();
        string currentName = Configuration.GetName();

        if (wallet != null) wallet.text = currentWallet;
        if (profilewallet != null) profilewallet.text = currentWallet;
        if (id != null) id.text = currentId;
        if (profileid != null) profileid.text = currentId;
        if (name != null) name.text = currentName;
        if (profilename != null) profilename.text = currentName;
        if (SpriteManager.Instance != null && SpriteManager.Instance.profile_image != null)
        {
            if (profilepic != null)         profilepic.sprite         = SpriteManager.Instance.profile_image;
            if (profilesettingpic != null)  profilesettingpic.sprite  = SpriteManager.Instance.profile_image;
            if (profilesettingpic2 != null) profilesettingpic2.sprite = SpriteManager.Instance.profile_image;
        }

        if (entername != null) entername.text = currentName;
        if (EmailAddressInputField != null) EmailAddressInputField.text = GetStoredEmail();
        if (phonenumber != null) phonenumber.text = GetStoredMobile();

        EnsureProfileEditorBindings(true);
        ApplyProfileDataToEditor();
    }

    private void CacheProfileData(string profileName, string email, string gender, string dob, string stateValue, string cityValue)
    {
        if (LogInOutput != null && LogInOutput.user_data != null && LogInOutput.user_data.Count > 0)
        {
            UserDatum user = LogInOutput.user_data[0];
            user.name = profileName;
            user.email = email;
            user.gender = gender;
            user.date_of_birth = dob;
            user.state = stateValue;
            user.city = cityValue;
        }
    }

    private string GetStoredEmail()
    {
        string email = PlayerPrefs.GetString("MyEmail", string.Empty);
        if (string.IsNullOrWhiteSpace(email))
            email = PlayerPrefs.GetString("email", string.Empty);
        if (string.IsNullOrWhiteSpace(email) && LogInOutput?.user_data != null && LogInOutput.user_data.Count > 0)
            email = LogInOutput.user_data[0].email ?? string.Empty;
        return email.Trim();
    }

    private string GetStoredMobile()
    {
        string mobile = Configuration.getmobile();
        if (string.IsNullOrWhiteSpace(mobile) && LogInOutput?.user_data != null && LogInOutput.user_data.Count > 0)
            mobile = LogInOutput.user_data[0].mobile ?? string.Empty;
        return (mobile ?? string.Empty).Trim();
    }

    private string GetStoredGender()
    {
        return NormalizeGender(LogInOutput?.user_data != null && LogInOutput.user_data.Count > 0
            ? LogInOutput.user_data[0].gender
            : string.Empty);
    }

    private string GetStoredDateOfBirth()
    {
        return LogInOutput?.user_data != null && LogInOutput.user_data.Count > 0
            ? NormalizeDateForApi(LogInOutput.user_data[0].date_of_birth)
            : string.Empty;
    }

    private string GetStoredState()
    {
        return LogInOutput?.user_data != null && LogInOutput.user_data.Count > 0
            ? (LogInOutput.user_data[0].state ?? string.Empty).Trim()
            : string.Empty;
    }

    private string GetStoredCity()
    {
        return LogInOutput?.user_data != null && LogInOutput.user_data.Count > 0
            ? (LogInOutput.user_data[0].city ?? string.Empty).Trim()
            : string.Empty;
    }

    private string NormalizeGender(string gender)
    {
        string normalized = (gender ?? string.Empty).Trim().ToLowerInvariant();
        switch (normalized)
        {
            case "m":
            case "male":
                return "male";
            case "f":
            case "female":
                return "female";
            case "other":
                return "other";
            default:
                return string.Empty;
        }
    }

    private string NormalizeDateForApi(string value)
    {
        if (string.IsNullOrWhiteSpace(value))
            return string.Empty;
        if (DateTime.TryParse(value, out DateTime parsed))
            return parsed.ToString("yyyy-MM-dd");
        return value.Trim();
    }

    private string GetProfileEditorValue(InputField editorField, InputField fallbackField)
    {
        if (editorField != null)
            return (editorField.text ?? string.Empty).Trim();
        if (fallbackField != null)
            return (fallbackField.text ?? string.Empty).Trim();
        return string.Empty;
    }

    private void ApplyProfileDataToEditor()
    {
        EnsureProfileEditorBindings(true);
        if (profileNameEditor != null) profileNameEditor.text = Configuration.GetName();
        if (profileGenderEditor != null) profileGenderEditor.text = GetStoredGender();
        if (profileDobEditor != null) profileDobEditor.text = GetStoredDateOfBirth();
        if (profileStateEditor != null) profileStateEditor.text = GetStoredState();
        if (profileCityEditor != null) profileCityEditor.text = GetStoredCity();
        if (profileEditorStatus != null) profileEditorStatus.text = "Update your profile details here.";
    }

    private IEnumerator RefreshProfileEditorVisualsDelayed()
    {
        yield return null;
        EnsureProfileEditorBindings(true);
        ApplyProfileDataToEditor();
        yield return new WaitForEndOfFrame();
        EnsureProfileEditorBindings(true);
        ApplyProfileDataToEditor();
    }

    private void EnsureProfileEditorBindings(bool allowCreate)
    {
        if (ProfilePopup == null)
            return;

        if (profileEditorRoot == null)
            profileEditorRoot = ProfilePopup.transform.Find("ProfileDetailsRuntime") as RectTransform;
        if (profileEditorRoot != null && allowCreate)
        {
            bool hasLegacyFields =
                profileEditorRoot.Find("EmailField") != null
                || profileEditorRoot.Find("PhoneField") != null
                || profileEditorRoot.Find("EmailFieldBlock") != null
                || profileEditorRoot.Find("PhoneFieldBlock") != null;
            bool missingNewRows =
                profileEditorRoot.Find("GenderDobRow") == null
                || profileEditorRoot.Find("CityStateRow") == null;

            if (hasLegacyFields || missingNewRows)
            {
#if UNITY_EDITOR
                if (!Application.isPlaying)
                    Undo.DestroyObjectImmediate(profileEditorRoot.gameObject);
                else
                    Destroy(profileEditorRoot.gameObject);
#else
                Destroy(profileEditorRoot.gameObject);
#endif
                profileEditorRoot = null;
                profileNameEditor = null;
                profileEmailEditor = null;
                profilePhoneEditor = null;
                profileGenderEditor = null;
                profileDobEditor = null;
                profileStateEditor = null;
                profileCityEditor = null;
                profileEditorStatus = null;
            }
        }
        if (profileEditorRoot == null && allowCreate)
            profileEditorRoot = BuildProfileEditor();
        if (profileEditorRoot == null)
            return;

        if (profileNameEditor == null) profileNameEditor = profileEditorRoot.Find("NameField")?.GetComponent<InputField>();
        if (profileGenderEditor == null) profileGenderEditor = profileEditorRoot.Find("GenderField")?.GetComponent<InputField>();
        if (profileDobEditor == null) profileDobEditor = profileEditorRoot.Find("DobField")?.GetComponent<InputField>();
        if (profileStateEditor == null) profileStateEditor = profileEditorRoot.Find("StateField")?.GetComponent<InputField>();
        if (profileCityEditor == null) profileCityEditor = profileEditorRoot.Find("CityField")?.GetComponent<InputField>();
        if (profileEditorStatus == null) profileEditorStatus = profileEditorRoot.Find("HintText")?.GetComponent<Text>();

        NormalizeProfileEditorLayout();
        if (Application.isPlaying)
            lastProfileEditorNormalizeAt = Time.unscaledTime;
    }

    private bool NeedsProfileEditorRefresh()
    {
        return ProfileFieldNeedsRefresh(profileNameEditor, 96f, 56)
            || ProfileFieldNeedsRefresh(profileGenderEditor, 96f, 56)
            || ProfileFieldNeedsRefresh(profileDobEditor, 96f, 56)
            || ProfileFieldNeedsRefresh(profileCityEditor, 96f, 56)
            || ProfileFieldNeedsRefresh(profileStateEditor, 96f, 56);
    }

    private bool ProfileFieldNeedsRefresh(InputField field, float expectedHeight, int expectedTextSize)
    {
        if (field == null)
            return false;

        LayoutElement layout = field.GetComponent<LayoutElement>();
        if (layout != null && layout.minHeight < expectedHeight - 1f)
            return true;

        RectTransform rect = field.GetComponent<RectTransform>();
        if (rect != null && rect.sizeDelta.y < expectedHeight - 1f)
            return true;

        Text text = field.textComponent;
        if (text != null && text.fontSize < expectedTextSize)
            return true;

        return false;
    }

    private RectTransform BuildProfileEditor()
    {
        if (ProfilePopup == null)
            return null;

        RectTransform popupRect = ProfilePopup.GetComponent<RectTransform>();
        if (popupRect == null)
            return null;

        if (popupRect.sizeDelta.y < 760f)
            popupRect.sizeDelta = new Vector2(popupRect.sizeDelta.x, 760f);

        Font font = entername != null && entername.textComponent != null && entername.textComponent.font != null
            ? entername.textComponent.font
            : Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");

#if UNITY_EDITOR
        if (!Application.isPlaying && popupRect.gameObject.scene.IsValid())
        {
            Undo.RegisterFullObjectHierarchyUndo(ProfilePopup, "Build Profile Editor");
        }
#endif
        GameObject root = new GameObject("ProfileDetailsRuntime", typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(ContentSizeFitter));
        root.transform.SetParent(popupRect, false);
        RectTransform rootRect = root.GetComponent<RectTransform>();
        rootRect.anchorMin = new Vector2(0f, 0f);
        rootRect.anchorMax = new Vector2(1f, 1f);
        rootRect.offsetMin = new Vector2(90f, 96f);
        rootRect.offsetMax = new Vector2(-90f, -300f);

        VerticalLayoutGroup layout = root.GetComponent<VerticalLayoutGroup>();
        layout.spacing = 18f;
        layout.padding = new RectOffset(0, 0, 18, 0);
        layout.childAlignment = TextAnchor.UpperCenter;
        layout.childControlHeight = false;
        layout.childControlWidth = true;
        layout.childForceExpandHeight = false;
        layout.childForceExpandWidth = true;

        ContentSizeFitter fitter = root.GetComponent<ContentSizeFitter>();
        fitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
        fitter.verticalFit = ContentSizeFitter.FitMode.Unconstrained;

        CreateProfileEditorField(rootRect, font, "Name", "Full Name", "NameField", false);

        RectTransform genderDobRow = CreateProfileEditorRow(rootRect, "GenderDobRow");
        CreateProfileEditorField(genderDobRow, font, "Gender", "male / female / other", "GenderField", false);
        CreateProfileEditorField(genderDobRow, font, "DOB", "yyyy-mm-dd", "DobField", false);

        RectTransform cityStateRow = CreateProfileEditorRow(rootRect, "CityStateRow");
        CreateProfileEditorField(cityStateRow, font, "City", "City", "CityField", false);
        CreateProfileEditorField(cityStateRow, font, "State", "State", "StateField", false);

        Text hint = CreateRuntimeText(rootRect, font, "HintText", 28, FontStyle.Normal, TextAnchor.MiddleLeft, new Color32(255, 235, 180, 255), 76f);
        hint.text = "Update your profile details here.";
        hint.horizontalOverflow = HorizontalWrapMode.Wrap;
        hint.verticalOverflow = VerticalWrapMode.Overflow;

#if UNITY_EDITOR
        if (!Application.isPlaying && root.scene.IsValid())
            EditorSceneManager.MarkSceneDirty(root.scene);
#endif
        return rootRect;
    }

    private RectTransform CreateProfileEditorRow(RectTransform parent, string objectName)
    {
        GameObject rowObject = new GameObject(objectName, typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
        rowObject.transform.SetParent(parent, false);
        RectTransform rowRect = rowObject.GetComponent<RectTransform>();
        rowRect.sizeDelta = new Vector2(0f, 132f);

        LayoutElement rowLayout = rowObject.GetComponent<LayoutElement>();
        rowLayout.minHeight = 132f;
        rowLayout.preferredHeight = 132f;

        HorizontalLayoutGroup layout = rowObject.GetComponent<HorizontalLayoutGroup>();
        layout.spacing = 16f;
        layout.padding = new RectOffset(0, 0, 0, 0);
        layout.childAlignment = TextAnchor.UpperLeft;
        layout.childControlHeight = true;
        layout.childControlWidth = true;
        layout.childForceExpandHeight = false;
        layout.childForceExpandWidth = true;

        return rowRect;
    }

    private InputField CreateProfileEditorField(RectTransform parent, Font font, string labelText, string placeholder, string objectName, bool readOnly)
    {
        GameObject blockObject = new GameObject(objectName + "Block", typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
        blockObject.transform.SetParent(parent, false);
        RectTransform blockRect = blockObject.GetComponent<RectTransform>();
        blockRect.sizeDelta = new Vector2(0f, 132f);

        LayoutElement blockLayout = blockObject.GetComponent<LayoutElement>();
        blockLayout.minHeight = 132f;
        blockLayout.preferredHeight = 132f;
        blockLayout.flexibleWidth = 1f;

        VerticalLayoutGroup blockGroup = blockObject.GetComponent<VerticalLayoutGroup>();
        blockGroup.spacing = 10f;
        blockGroup.padding = new RectOffset(0, 0, 0, 0);
        blockGroup.childAlignment = TextAnchor.UpperLeft;
        blockGroup.childControlHeight = false;
        blockGroup.childControlWidth = true;
        blockGroup.childForceExpandHeight = false;
        blockGroup.childForceExpandWidth = true;

        Text label = CreateRuntimeText(blockRect, font, objectName + "Label", 26, FontStyle.Bold, TextAnchor.MiddleLeft, Color.white, 40f);
        label.text = labelText;

        GameObject fieldObject = new GameObject(objectName, typeof(RectTransform), typeof(Image), typeof(InputField), typeof(LayoutElement));
        fieldObject.transform.SetParent(blockRect, false);
        RectTransform fieldRect = fieldObject.GetComponent<RectTransform>();
        fieldRect.sizeDelta = new Vector2(0f, 96f);

        LayoutElement layout = fieldObject.GetComponent<LayoutElement>();
        layout.minHeight = 96f;
        layout.preferredHeight = 96f;
        layout.flexibleWidth = 1f;

        Image bg = fieldObject.GetComponent<Image>();
        bg.color = new Color32(92, 20, 32, 255);

        InputField field = fieldObject.GetComponent<InputField>();
        field.readOnly = readOnly;
        field.lineType = InputField.LineType.SingleLine;
        field.characterValidation = InputField.CharacterValidation.None;

        GameObject placeholderObject = new GameObject("Placeholder", typeof(RectTransform), typeof(Text));
        placeholderObject.transform.SetParent(fieldObject.transform, false);
        RectTransform placeholderRect = placeholderObject.GetComponent<RectTransform>();
        placeholderRect.anchorMin = Vector2.zero;
        placeholderRect.anchorMax = Vector2.one;
        placeholderRect.offsetMin = new Vector2(22f, 12f);
        placeholderRect.offsetMax = new Vector2(-22f, -12f);
        Text placeholderText = placeholderObject.GetComponent<Text>();
        placeholderText.font = font;
        placeholderText.fontSize = 34;
        placeholderText.fontStyle = FontStyle.Italic;
        placeholderText.alignment = TextAnchor.MiddleLeft;
        placeholderText.color = new Color32(220, 200, 200, 180);
        placeholderText.text = placeholder;

        GameObject textObject = new GameObject("Text", typeof(RectTransform), typeof(Text));
        textObject.transform.SetParent(fieldObject.transform, false);
        RectTransform textRect = textObject.GetComponent<RectTransform>();
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = new Vector2(22f, 12f);
        textRect.offsetMax = new Vector2(-22f, -12f);
        Text text = textObject.GetComponent<Text>();
        text.font = font;
        text.fontSize = 56;
        text.fontStyle = FontStyle.Normal;
        text.alignment = TextAnchor.MiddleLeft;
        text.color = Color.white;

        field.textComponent = text;
        field.placeholder = placeholderText;

        return field;
    }

    private void NormalizeProfileEditorLayout()
    {
        if (profileEditorRoot == null)
            return;

        RectTransform popupRect = ProfilePopup != null ? ProfilePopup.GetComponent<RectTransform>() : null;
        if (popupRect != null && popupRect.sizeDelta.y < 760f)
            popupRect.sizeDelta = new Vector2(popupRect.sizeDelta.x, 760f);

        profileEditorRoot.offsetMin = new Vector2(90f, 96f);
        profileEditorRoot.offsetMax = new Vector2(-90f, -300f);

        VerticalLayoutGroup rootGroup = profileEditorRoot.GetComponent<VerticalLayoutGroup>();
        if (rootGroup != null)
        {
            rootGroup.spacing = 18f;
            rootGroup.padding = new RectOffset(0, 0, 18, 0);
            rootGroup.childAlignment = TextAnchor.UpperCenter;
        }

        Image referenceImage = profileEditorRoot.Find("NameField")?.GetComponent<Image>();
        Sprite referenceSprite = referenceImage != null ? referenceImage.sprite : null;
        Image.Type referenceType = referenceImage != null ? referenceImage.type : Image.Type.Simple;
        Color referenceColor = referenceImage != null ? referenceImage.color : new Color32(92, 20, 32, 255);

        ApplyFieldBlockLayout("NameFieldBlock", 154f);
        ApplyFieldLayout("NameField", 96f, 34, 56, referenceSprite, referenceType, referenceColor, true, TextAnchor.MiddleCenter);
        NormalizeInputFieldVisual(profileNameEditor, 96f, 34, 56, referenceSprite, referenceType, referenceColor, true, TextAnchor.MiddleCenter);

        ApplyRowLayout("GenderDobRow", 140f, 18f);
        ApplyFieldBlockLayout("GenderFieldBlock", 136f);
        ApplyFieldBlockLayout("DobFieldBlock", 136f);
        ApplyFieldLayout("GenderField", 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);
        ApplyFieldLayout("DobField", 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);
        NormalizeInputFieldVisual(profileGenderEditor, 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);
        NormalizeInputFieldVisual(profileDobEditor, 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);

        ApplyRowLayout("CityStateRow", 140f, 18f);
        ApplyFieldBlockLayout("CityFieldBlock", 136f);
        ApplyFieldBlockLayout("StateFieldBlock", 136f);
        ApplyFieldLayout("CityField", 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);
        ApplyFieldLayout("StateField", 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);
        NormalizeInputFieldVisual(profileCityEditor, 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);
        NormalizeInputFieldVisual(profileStateEditor, 96f, 34, 56, referenceSprite, referenceType, referenceColor, false, TextAnchor.MiddleLeft);

        if (profileEditorStatus != null)
        {
            profileEditorStatus.fontSize = 28;
            profileEditorStatus.resizeTextForBestFit = false;
        }
    }

    private void ApplyRowLayout(string rowName, float height, float spacing)
    {
        RectTransform rowRect = profileEditorRoot.Find(rowName) as RectTransform;
        if (rowRect == null)
            return;

        rowRect.sizeDelta = new Vector2(0f, height);
        LayoutElement rowLayout = rowRect.GetComponent<LayoutElement>();
        if (rowLayout != null)
        {
            rowLayout.minHeight = height;
            rowLayout.preferredHeight = height;
        }

        HorizontalLayoutGroup rowGroup = rowRect.GetComponent<HorizontalLayoutGroup>();
        if (rowGroup != null)
            rowGroup.spacing = spacing;
    }

    private void ApplyFieldBlockLayout(string blockName, float height)
    {
        RectTransform blockRect = profileEditorRoot.Find(blockName) as RectTransform;
        if (blockRect == null)
            return;

        blockRect.sizeDelta = new Vector2(0f, height);
        LayoutElement blockLayout = blockRect.GetComponent<LayoutElement>();
        if (blockLayout != null)
        {
            blockLayout.minHeight = height;
            blockLayout.preferredHeight = height;
            blockLayout.flexibleWidth = 1f;
        }

        VerticalLayoutGroup blockGroup = blockRect.GetComponent<VerticalLayoutGroup>();
        if (blockGroup != null)
            blockGroup.spacing = 10f;
    }

    private void ApplyFieldLayout(string fieldName, float fieldHeight, int placeholderFontSize, int textFontSize, Sprite sprite, Image.Type imageType, Color imageColor, bool centerPlaceholder, TextAnchor textAlignment)
    {
        RectTransform fieldRect = profileEditorRoot.Find(fieldName) as RectTransform;
        if (fieldRect == null)
            return;

        fieldRect.sizeDelta = new Vector2(0f, fieldHeight);

        LayoutElement layout = fieldRect.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.minHeight = fieldHeight;
            layout.preferredHeight = fieldHeight;
            layout.flexibleWidth = 1f;
        }

        Image bg = fieldRect.GetComponent<Image>();
        if (bg != null)
        {
            bg.sprite = sprite;
            bg.type = imageType;
            bg.color = imageColor;
        }

        Text placeholder = fieldRect.Find("Placeholder")?.GetComponent<Text>();
        if (placeholder != null)
        {
            placeholder.fontSize = placeholderFontSize;
            placeholder.alignment = centerPlaceholder ? TextAnchor.MiddleCenter : TextAnchor.MiddleLeft;
        }

        Text text = fieldRect.Find("Text")?.GetComponent<Text>();
        if (text != null)
        {
            text.fontSize = textFontSize;
            text.alignment = textAlignment;
        }

        RectTransform placeholderRect = fieldRect.Find("Placeholder") as RectTransform;
        if (placeholderRect != null)
        {
            placeholderRect.offsetMin = new Vector2(18f, 10f);
            placeholderRect.offsetMax = new Vector2(-18f, -10f);
        }

        RectTransform textRect = fieldRect.Find("Text") as RectTransform;
        if (textRect != null)
        {
            textRect.offsetMin = new Vector2(18f, 10f);
            textRect.offsetMax = new Vector2(-18f, -10f);
        }
    }

    private void NormalizeInputFieldVisual(InputField field, float fieldHeight, int placeholderFontSize, int textFontSize, Sprite sprite, Image.Type imageType, Color imageColor, bool centerPlaceholder, TextAnchor textAlignment)
    {
        if (field == null)
            return;

        RectTransform fieldRect = field.GetComponent<RectTransform>();
        if (fieldRect != null)
            fieldRect.sizeDelta = new Vector2(0f, fieldHeight);

        LayoutElement layout = field.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.minHeight = fieldHeight;
            layout.preferredHeight = fieldHeight;
            layout.flexibleWidth = 1f;
        }

        Image bg = field.GetComponent<Image>();
        if (bg != null)
        {
            bg.sprite = sprite != null ? sprite : bg.sprite;
            bg.type = imageType;
            bg.color = imageColor;
        }

        if (field.placeholder is Text placeholderText)
        {
            placeholderText.fontSize = placeholderFontSize;
            placeholderText.resizeTextForBestFit = false;
            placeholderText.alignment = centerPlaceholder ? TextAnchor.MiddleCenter : TextAnchor.MiddleLeft;

            RectTransform placeholderRect = placeholderText.GetComponent<RectTransform>();
            if (placeholderRect != null)
            {
                placeholderRect.offsetMin = new Vector2(18f, 10f);
                placeholderRect.offsetMax = new Vector2(-18f, -10f);
            }
        }

        Text inputText = field.textComponent;
        if (inputText != null)
        {
            inputText.fontSize = textFontSize;
            inputText.resizeTextForBestFit = false;
            inputText.alignment = textAlignment;
            inputText.horizontalOverflow = HorizontalWrapMode.Overflow;
            inputText.verticalOverflow = VerticalWrapMode.Truncate;
            inputText.supportRichText = false;

            RectTransform textRect = inputText.GetComponent<RectTransform>();
            if (textRect != null)
            {
                textRect.offsetMin = new Vector2(18f, 10f);
                textRect.offsetMax = new Vector2(-18f, -10f);
            }
        }
    }

    private Text CreateRuntimeText(RectTransform parent, Font font, string objectName, int fontSize, FontStyle fontStyle, TextAnchor alignment, Color color, float height)
    {
        GameObject textObject = new GameObject(objectName, typeof(RectTransform), typeof(Text), typeof(LayoutElement));
        textObject.transform.SetParent(parent, false);
        RectTransform rect = textObject.GetComponent<RectTransform>();
        rect.sizeDelta = new Vector2(0f, height);

        LayoutElement layout = textObject.GetComponent<LayoutElement>();
        layout.minHeight = height;
        layout.preferredHeight = height;

        Text text = textObject.GetComponent<Text>();
        text.font = font;
        text.fontSize = fontSize;
        text.fontStyle = fontStyle;
        text.alignment = alignment;
        text.color = color;
        return text;
    }

    #endregion

    #region Games according to api

    public async void ShowGames(int selected)
    {
        await ShowGamesAsync(selected);
    }

    public void UpdateWalletButton()
    {
        RefreshWallet.Play("Refreshwallet");
        StartCoroutine(UpdateWallet());
    }

    public IEnumerator UpdateWallet() //string Token)
    {
        string url = Configuration.Url + Configuration.wallet;
        /*  var formData = new Dictionary<string, string>
         {
             { "user_id", Configuration.GetId() },
             { "token", Configuration.GetToken() },
         };
         Wallet myResponse = await APIManager.Instance.Post<Wallet>(url, formData);
         if (myResponse.code == 200)
         {
             PlayerPrefs.SetString("wallet", myResponse.wallet);
             PlayerPrefs.Save();
             SetUserProfileDetails();
         }
         else
         {
             CommonUtil.CheckLog("Error_new:" + myResponse.message);
         }
    */
        Debug.Log("RES_Check + API-Call + wallet " + url);
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId()); // before Configuration.GetId()
        form.AddField("token", Configuration.GetToken()); // before Configuration.GetToken()
        UnityWebRequest www = UnityWebRequest.Post(url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);
        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + GetWallet: " + responseText);
            Wallet wallet = new Wallet();
            wallet = JsonUtility.FromJson<Wallet>(responseText);
            if (wallet.code == 200)
            {
                PlayerPrefs.SetString("wallet", wallet.wallet);
                PlayerPrefs.SetString("winning", wallet.winning_wallet);
                PlayerPrefs.SetString("bonus", wallet.bonus_wallet);
                PlayerPrefs.Save();
                SetUserProfileDetails();
            }
            else
            {
                Debug.Log("errornew" + www.error);
                Debug.Log("error" + www.error);
            }
        }
    }

    public async Task ShowGamesAsync(int selected)
    {
        List<GameObject> combinedGameList = GetConfiguredGameObjects();
        List<GameObject> selectedGameList = selected switch
        {
            0 => GetLudoOnlyGameObjects(combinedGameList),
            1 => FilterValidGameObjects(rummygames),
            2 => FilterValidGameObjects(smallgames),
            3 => FilterValidGameObjects(roulettegames),
            4 => FilterValidGameObjects(coingames),
            5 => FilterValidGameObjects(Slotgames),
            _ => throw new ArgumentException("Invalid selection.", nameof(selected)),
        };
        activegamenamesinunity = new List<string>();
        bool hasServerGameSettings = activegamenames != null && activegamenames.Count > 0;

        combinedGameList.ForEach(game => game.SetActive(false));

        selectedGameList.ForEach(game =>
        {
            bool isActive = !hasServerGameSettings || IsLobbyGameEnabled(game);
            game.SetActive(isActive);

            if (isActive)
            {
                activegamenamesinunity.Add(game.name);
            }
        });

        if (activegamenamesinunity.Count == 0)
        {
            var ludoOnly = selectedGameList.Find(game =>
                game != null
                && NormalizeGameKey(game.name).Contains("ludo")
            );

            if (ludoOnly != null)
            {
                ludoOnly.SetActive(true);
                activegamenamesinunity.Add(ludoOnly.name);
            }
        }

        Debug.Log($"CHECK ACTIVE GAME COUNT {activegamenamesinunity.Count}");
        GridLayoutGroup layoutGroup = null;
        if (gridlayoutgroup != null)
        {
            layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
        }

        if (activegamenamesinunity.Count == 11)
        {
#if UNITY_WEBGL
            allgames.Find(game => game.name == "color_prediction_vertical").SetActive(false);
            if (layoutGroup != null)
            {
                layoutGroup.constraintCount = 6;
            }
#else
            if (layoutGroup != null)
            {
                layoutGroup.constraintCount = 6;
            }
#endif
        }
        else if (selectedGameList.Count == 5)
        {
            if (layoutGroup != null)
            {
                layoutGroup.constraintCount = 4;
            }
        }
        else if (activegamenamesinunity.Count == 19)
        {
#if UNITY_WEBGL
            allgames.Find(game => game.name == "color_prediction_vertical").SetActive(false);
            if (layoutGroup != null)
            {
                layoutGroup.constraintCount = 9;
            }
#else
            if (layoutGroup != null)
            {
                layoutGroup.constraintCount = 10;
            }
            Debug.Log($"CHECK LIST COUNT else {selectedGameList.Count}");

#endif
        }
        else
        {
            int roundedUpCount = Mathf.CeilToInt(activegamenamesinunity.Count / 2f);
            roundedUpCount += 1;
            if (layoutGroup != null)
            {
                layoutGroup.constraintCount = Mathf.Max(roundedUpCount, 4);
            }
            CommonUtil.CheckLog("Rounded int " + roundedUpCount);
        }
        /* if (selected == 0)
        {
            GridLayoutGroup layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
            layoutGroup.constraintCount = 10;
        }
        /* else if (selected == 1)
        {
            GridLayoutGroup layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
            layoutGroup.constraintCount = 8;
        } */
        /*   else
          {
              int roundedUpCount = Mathf.CeilToInt(activegamenamesinunity.Count / 2f);
              roundedUpCount += 1;
              GridLayoutGroup layoutGroup = gridlayoutgroup.GetComponent<GridLayoutGroup>();
              layoutGroup.constraintCount = Mathf.Max(roundedUpCount, 4);
              CommonUtil.CheckLog("Rounded int " + roundedUpCount);
          }
           */
        // Adjust GridLayoutGroup constraint count
        // Refresh game content
#if UNITY_WEBGL
        combinedGameList.Find(game => game.name == "color_prediction_vertical")?.SetActive(false);
#endif
        if (gamecontent != null && gamecontent.gameObject != null)
        {
            gamecontent.gameObject.SetActive(false);
            gamecontent.gameObject.SetActive(true);
        }
    }

    public async Task InitializeGamesAsync()
    {
        activegamenames = await FetchGameSettingsAsync();

        // Filter active games and history based on settings
        //activegames = games.Where(game => activegamenames.Contains(game.backendname)).ToList();
        // activehistory = allhistory.Where(game => activegamenames.Contains(game.name)).ToList();

        // // Deactivate all history games initially
        // allhistory.ForEach(game => game.SetActive(false));

        // // Activate only filtered history games
        // activehistory.ForEach(game => game.SetActive(true));

        // Show default game selection (0)
        if (activegamenames == null)
        {
            activegamenames = new List<string>();
        }
    }

    private bool IsLobbyGameEnabled(GameObject gameObject)
    {
        if (gameObject == null)
        {
            return false;
        }

        string normalizedObjectName = NormalizeGameKey(gameObject.name);

        if (activegamenames.Any(name => MatchesServerGameSetting(name, normalizedObjectName)))
        {
            return true;
        }

        if (games != null)
        {
            var mappedGame = games.FirstOrDefault(item =>
                item != null
                && (
                    NormalizeGameKey(item.backendname) == normalizedObjectName
                    || NormalizeGameKey(item.name) == normalizedObjectName
                )
            );

            if (mappedGame != null)
            {
                return activegamenames.Any(name =>
                    MatchesServerGameSetting(name, NormalizeGameKey(mappedGame.backendname))
                    || MatchesServerGameSetting(name, NormalizeGameKey(mappedGame.name))
                );
            }
        }

        return false;
    }

    private string NormalizeGameKey(string value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return string.Empty;
        }

        return Regex.Replace(value.Trim().ToLowerInvariant(), @"[^a-z0-9]+", "_")
            .Trim('_');
    }

    private bool MatchesServerGameSetting(string serverValue, string targetValue)
    {
        string normalizedServerValue = NormalizeGameKey(serverValue);
        string normalizedTargetValue = NormalizeGameKey(targetValue);

        if (normalizedServerValue == normalizedTargetValue)
        {
            return true;
        }

        if (normalizedServerValue == "ludo")
        {
            return normalizedTargetValue == "ludo"
                || normalizedTargetValue == "ludo_online"
                || normalizedTargetValue.Contains("ludo");
        }

        return false;
    }

    private List<GameObject> GetConfiguredGameObjects()
    {
        var configuredGames = new List<GameObject>();
        configuredGames.AddRange(FilterValidGameObjects(allgames));
        configuredGames.AddRange(FilterValidGameObjects(rummygames));
        configuredGames.AddRange(FilterValidGameObjects(smallgames));
        configuredGames.AddRange(FilterValidGameObjects(roulettegames));
        configuredGames.AddRange(FilterValidGameObjects(coingames));
        configuredGames.AddRange(FilterValidGameObjects(Slotgames));

        return configuredGames
            .Where(game => game != null)
            .Distinct()
            .ToList();
    }

    private List<GameObject> GetLudoOnlyGameObjects(List<GameObject> source)
    {
        List<GameObject> validGames = FilterValidGameObjects(source);
        List<GameObject> ludoOnlineGames = validGames
            .Where(game => NormalizeGameKey(game.name) == "ludo_online")
            .ToList();

        if (ludoOnlineGames.Count > 0)
        {
            return ludoOnlineGames;
        }

        List<GameObject> genericLudoGames = validGames
            .Where(game => NormalizeGameKey(game.name).Contains("ludo"))
            .ToList();

        return genericLudoGames;
    }

    private List<GameObject> FilterValidGameObjects(List<GameObject> source)
    {
        if (source == null)
        {
            return new List<GameObject>();
        }

        return source
            .Where(game => game != null)
            .Distinct()
            .ToList();
    }

    private void HideNonLudoGamesImmediately()
    {
        List<GameObject> combinedGameList = GetConfiguredGameObjects();
        List<GameObject> ludoGames = GetLudoOnlyGameObjects(combinedGameList);

        foreach (GameObject game in combinedGameList)
        {
            if (game == null)
            {
                continue;
            }

            game.SetActive(ludoGames.Contains(game));
        }
    }

    #endregion

    #region API Functions

    public async Task UpdateData(string id, string token) //string Token)
    {
        string Url = Configuration.Url + Configuration.profile;
        Debug.Log("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "fcm", Configuration.getFCMToken() },
            { "app_version", "1" },
            { "id", id },
            { "token", token },
        };
        LogInOutput = new newLogInOutputs();
        LogInOutput = await APIManager.Instance.Post<newLogInOutputs>(Url, formData);
        if (LogInOutput.code == 411)
        {
            logout();
        }
        if (LogInOutput.code == 200)
        {
            Debug.Log("RES_Check + Login Profile Data : " + LogInOutput.user_data[0]);
            PlayerPrefs.SetString("id", LogInOutput.user_data[0].id);
            PlayerPrefs.SetString("mobile", LogInOutput.user_data[0].mobile);
            PlayerPrefs.SetString("token", LogInOutput.user_data[0].token);
            PlayerPrefs.SetString("wallet", LogInOutput.user_data[0].wallet);
            PlayerPrefs.SetString("profile_pic", LogInOutput.user_data[0].profile_pic);
            PlayerPrefs.SetString("name", LogInOutput.user_data[0].name);
            PlayerPrefs.SetString("email", LogInOutput.user_data[0].email);
            PlayerPrefs.SetString("bonus", LogInOutput.user_data[0].bonus_wallet);
            PlayerPrefs.SetString("winning", LogInOutput.user_data[0].winning_wallet);
            PlayerPrefs.SetString("unutilized", LogInOutput.user_data[0].unutilized_wallet);

            PlayerPrefs.SetString("share_text", LogInOutput.setting.share_text);
            PlayerPrefs.SetString("referral_code", LogInOutput.user_data[0].referral_code);
            PlayerPrefs.SetString("referral_link", LogInOutput.setting.referral_link);
            PlayerPrefs.SetString("getdollar", LogInOutput.setting.dollar);

            Debug.Log("share_text: " + PlayerPrefs.GetString("share_text"));
            Debug.Log("referral_code: " + PlayerPrefs.GetString("referral_code"));
            Debug.Log("referral_link: " + PlayerPrefs.GetString("referral_link"));

            if (LogInOutput.user_bank_details.Count > 0)
            {
                Debug.Log("RES_Check + Passbook " + LogInOutput.user_bank_details[0].passbook_img);
                PlayerPrefs.SetString(
                    "passbook_pic",
                    LogInOutput.user_bank_details[0].passbook_img
                );
            }
            if (LogInOutput.user_kyc.Count > 0)
            {
                PlayerPrefs.SetString("adhar_pic", LogInOutput.user_kyc[0].aadhar_img);
                PlayerPrefs.SetString("pan_pic", LogInOutput.user_kyc[0].pan_img);
            }
            PlayerPrefs.Save();
            //GetProfileImage(LogInOutput.user_data[0].profile_pic);
            GetRandomNotifications();
            SetUserProfileDetails();
            PostUserSetting(Configuration.Url + Configuration.Usersetting);
        }
        else
        {
            if (LoaderUtil.instance != null)
                ShowToastSafe(LogInOutput.message);
            else
                CommonUtil.ShowToast(LogInOutput.message);
        }
    }

    private async Task<List<string>> FetchGameSettingsAsync()
    {
        if (APIManager.Instance == null)
        {
            Debug.LogWarning("FetchGameSettingsAsync skipped because APIManager.Instance is null.");
            return new List<string>();
        }

        string Url = Configuration.Url + Configuration.gameonoff;
        Debug.Log("RES_Check +FetchGameSettingsAsync");

        var formData = new Dictionary<string, string>
        {
            { "id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        GameRootObject rootobject = await APIManager.Instance.Post<GameRootObject>(Url, formData);

        if (rootobject == null)
        {
            Debug.LogWarning("Game settings response was null.");
            return new List<string>();
        }

        if (rootobject.code == 200)
        {
            if (rootobject.games != null && rootobject.games.Count > 0)
            {
                return rootobject.games
                    .Where(game => game != null && game.visibility == "1" && game.status == "1")
                    .Select(game => game.game)
                    .Where(game => !string.IsNullOrWhiteSpace(game))
                    .ToList();
            }

            Debug.LogWarning("Game settings response did not include parsed games list. Falling back to Ludo-only visibility.");
            return new List<string> { "ludo" };
        }

        return new List<string>();
    }

    public async Task PostUpdatePassword(string oldpassword, string newpassword)
    {
        string Url = Configuration.Url + Configuration.Change_password;
        Debug.Log("RES_Check + API-Call + PostUpdatePassword");

        var formData = new Dictionary<string, string>
        {
            { "old_password", oldpassword },
            { "new_password", newpassword },
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        BankOutputs BankOutput = new BankOutputs();
        BankOutput = await APIManager.Instance.Post<BankOutputs>(Url, formData);
        if (BankOutput.code == 200)
        {
            PopUpPanelClose(ProfilePopup);
            ResetFieldUpdatePassword();
            ShowToastSafe(BankOutput.message);
        }
        else if (BankOutput.code == 406)
        {
            ShowToastSafe(BankOutput.message);
        }
    }

    public async Task PostUpdateBankDetails(
        string ifsc_code,
        string acc_holder_name,
        string bank_name,
        string acc_no,
        string base64forimgpassbook,
        string upiId
    )
    {
        if (acc_no.Length < 9)
        {
            CommonUtil.ShowToast("Please Enter valid Account Number"); //India: Bank account numbers range from 9 to 18 digits, depending on the bank.
            return;
        }
        string Url = Configuration.Url + Configuration.Update_bank_details;
        var formData = new Dictionary<string, string>
        {
            { "ifsc_code", ifsc_code },
            { "acc_holder_name", acc_holder_name },
            { "bank_name", bank_name },
            { "acc_no", acc_no },
            { "upi_id", upiId ?? string.Empty },
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        if (!string.IsNullOrWhiteSpace(base64forimgpassbook))
        {
            formData.Add("passbook_img", base64forimgpassbook);
        }
        BankOutputs BankOutput = new BankOutputs();
        BankOutput = await APIManager.Instance.Post<BankOutputs>(Url, formData);
        if (BankOutput.code == 200)
        {
            GameObject bankPopup = account_holder_name != null
                && account_holder_name.transform != null
                && account_holder_name.transform.parent != null
                && account_holder_name.transform.parent.parent != null
                && account_holder_name.transform.parent.parent.parent != null
                && account_holder_name.transform.parent.parent.parent.parent != null
                ? account_holder_name.transform.parent.parent.parent.parent.gameObject
                : bank_panel;
            if (bankPopup != null)
            {
                PopUpPanelClose(bankPopup);
            }
            ShowToastSafe(BankOutput.message);
        }
    }

    private void EnsureBankDetailBindings()
    {
        if (bank_panel == null)
        {
            bank_panel = FindDeepChild(transform, "Bank Details");
        }

        if (upi_id == null)
        {
            GameObject upiObject = GameObject.Find("UPI ID InputField (Legacy)");
            if (upiObject != null)
            {
                upi_id = upiObject.GetComponent<InputField>();
            }
        }

        if (bank_panel != null && upi_id == null)
        {
            InputField[] bankInputs = bank_panel.GetComponentsInChildren<InputField>(true);
            upi_id = bankInputs.FirstOrDefault(input =>
                input != null &&
                input.gameObject.name.IndexOf("UPI", StringComparison.OrdinalIgnoreCase) >= 0);
        }

        if (passbook_img == null)
        {
            Image[] images = bank_panel != null ? bank_panel.GetComponentsInChildren<Image>(true) : GetComponentsInChildren<Image>(true);
            passbook_img = images.FirstOrDefault(img =>
                img != null &&
                img.gameObject.name.IndexOf("passbook", StringComparison.OrdinalIgnoreCase) >= 0);
        }

        if (passbook_logo_img == null && passbook_img != null && passbook_img.transform.parent != null)
        {
            foreach (Transform child in passbook_img.transform.parent)
            {
                if (child != null && child.gameObject != passbook_img.gameObject)
                {
                    passbook_logo_img = child.gameObject;
                    break;
                }
            }
        }

        ConfigureUpiInputField();
    }

    private void ConfigureUpiInputField()
    {
        if (upi_id == null)
            return;

        upi_id.contentType = InputField.ContentType.Standard;
        upi_id.characterValidation = InputField.CharacterValidation.None;
        upi_id.characterLimit = 0;
        upi_id.lineType = InputField.LineType.SingleLine;
        upi_id.keyboardType = TouchScreenKeyboardType.Default;
    }

    private bool HasExistingOrSelectedPassbook()
    {
        string selectedBase64 = GetSelectedPassbookBase64();
        if (!string.IsNullOrWhiteSpace(selectedBase64))
            return true;

        return LogInOutput != null
            && LogInOutput.user_bank_details != null
            && LogInOutput.user_bank_details.Count > 0
            && !string.IsNullOrWhiteSpace(LogInOutput.user_bank_details[0].passbook_img);
    }

    private string GetSelectedPassbookBase64()
    {
        return SpriteManager.Instance != null ? (SpriteManager.Instance.base64forimgpassbook ?? string.Empty) : string.Empty;
    }

    private static void ShowToastSafe(string message)
    {
        bool isError = IsErrorStyleMessage(message);
        CommonUtil.ShowStyledMessage(
            message,
            isError ? "Error" : "Success",
            isError
        );
    }

    private static bool IsErrorStyleMessage(string message)
    {
        if (string.IsNullOrWhiteSpace(message))
            return false;

        string lowered = message.Trim().ToLowerInvariant();
        return lowered.Contains("please ")
            || lowered.Contains("invalid")
            || lowered.Contains("failed")
            || lowered.Contains("error")
            || lowered.Contains("unable")
            || lowered.Contains("required");
    }

    private static GameObject FindDeepChild(Transform root, string childName)
    {
        if (root == null)
            return null;

        for (int i = 0; i < root.childCount; i++)
        {
            Transform child = root.GetChild(i);
            if (child.name == childName)
                return child.gameObject;

            GameObject found = FindDeepChild(child, childName);
            if (found != null)
                return found;
        }

        return null;
    }

    public async Task PostUpdateCryptoDetails(
        string crypto_addressLocal,
        string crypto_wallet_type,
        string base64forimgcrypto
    )
    {
        string Url = Configuration.Url + Configuration.Update_bank_details;
        var formData = new Dictionary<string, string>
        {
            { "crypto_address", crypto_addressLocal },
            { "crypto_wallet_type", crypto_wallet_type },
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "crypto_qr", base64forimgcrypto },
        };
        BankOutputs BankOutput = new BankOutputs();
        BankOutput = await APIManager.Instance.Post<BankOutputs>(Url, formData);
        if (BankOutput.code == 200)
        {
            PopUpPanelClose(crypto_address.transform.parent.parent.parent.gameObject);
            ShowToastSafe(BankOutput.message);
        }
    }

    public async Task PostUpdateKYDetails(
        string aadhar_no_local,
        string aadhar_img,
        string pan_no,
        string pan_img
    )
    {
        if (!Regex.IsMatch(aadhar_no_local, @"^\d{12}$"))
        {
            CommonUtil.ShowToast("Please Enter a Valid Aadhaar Number");
            return;
        }

        // PAN validation: 5 letters, 4 digits, 1 letter
        if (!Regex.IsMatch(pan_no, @"^[A-Z]{5}[0-9]{4}[A-Z]$"))
        {
            CommonUtil.ShowToast("Please Enter a Valid PAN Number");
            return;
        }

        string Url = Configuration.Url + Configuration.Update_kyc;
        var formData = new Dictionary<string, string>
        {
            { "aadhar_no", aadhar_no_local },
            { "aadhar_img", aadhar_img },
            { "pan_img", pan_img },
            { "pan_no", pan_no },
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        messageprint BankOutput = new messageprint();
        BankOutput = await APIManager.Instance.Post<messageprint>(Url, formData);
        if (BankOutput.code == 200)
        {
            PopUpPanelClose(aadhar_no.transform.parent.parent.parent.gameObject);
            ShowToastSafe(BankOutput.message);
        }
        else
        {
            ShowToastSafe(BankOutput.message);
        }
    }

    public async Task PostUserSetting()
    {
        string Url = Configuration.Url + Configuration.Update_kyc;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        settingputput = new UserSettingOutPuts();
        settingputput = await APIManager.Instance.Post<UserSettingOutPuts>(Url, formData);
    }

    #endregion

    #region Logout

    public void LogoutFromGame()
    {
        PlayerPrefs.DeleteAll();

        if (selection == null)
        {
            selection = GetComponent<GameSelection>() ?? FindObjectOfType<GameSelection>();
        }

        if (selection != null)
        {
            selection.loaddynamicscenebyname("LoginRegister");
        }
        else
        {
            Debug.LogWarning("GameSelection reference is null during logout. Falling back to SceneManager.LoadScene.");
            UnityEngine.SceneManagement.SceneManager.LoadScene("LoginRegister");
        }
    }

    #endregion

    public GameObject gameObject;
    [Button]
    public void SetSliderAndImage()
    {
        for (int i = 0; i <= allgames.Count; i++)
        {
            var obj = Instantiate(gameObject, allgames[i].transform);
            obj.gameObject.name = "Progress";
            obj.transform.localPosition = Vector3.zero;
            obj.gameObject.SetActive(false);
        }
    }
}
