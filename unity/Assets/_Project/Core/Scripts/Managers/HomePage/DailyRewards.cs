using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using TMPro;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
#if UNITY_EDITOR
using UnityEditor;
using UnityEditor.SceneManagement;
#endif

[ExecuteAlways]
public class DailyRewards : MonoBehaviour
{
    private const string RecoveryPopupName = "Startup-Recovery-Popup";
    private const string PreferredRecoveryCanvasName = "HomePageCanvas";

    public Transform dailyrewardpanel,
        dailyrewadcontent;
    public List<GameObject> dailyrewardlist;
    public Button collect;
    [SerializeField] private Sprite popupFrameSprite;
    [SerializeField] private bool autoResizeRecoveryPopup = false;
    [SerializeField] private bool showRecoveryReminderOnStartup = false;
    private WelcomBonusRoot bonus;
    public Profile profile_wallet;
    private GameObject _promoPopupRoot;
    private GameObject _recoveryPopupRoot;
    private Image _promoBannerImage;
    private TextMeshProUGUI _promoTitleText;
    private TextMeshProUGUI _promoMessageText;
    private TextMeshProUGUI _promoButtonText;
    private string _promoTargetUrl;
    private GameObject _recoveryHomeView;
    private GameObject _recoveryVerifyView;
    private RectTransform _recoveryPanelRect;
    private RectTransform _recoveryTitleBarRect;
    private Button _recoveryCloseButton;
    private Button _recoveryVerifyWhatsappButton;
    private Button _recoveryVerifyEmailButton;
    private Button _recoveryLaterButton;
    private Button _recoverySendOtpButton;
    private Button _recoveryVerifyOtpButton;
    private Button _recoveryBackButton;
    private TextMeshProUGUI _recoveryTitleText;
    private TextMeshProUGUI _recoveryMessageText;
    private TextMeshProUGUI _recoveryVerifyTitleText;
    private TextMeshProUGUI _recoveryStatusText;
    private InputField _recoveryChannelInput;
    private InputField _recoveryOtpInput;
    private TextMeshProUGUI _recoverySendOtpLabel;
    private string _recoveryChannelType;
    private string _recoveryPendingOtpId;
    private string _recoveryPendingChannelValue;
    private Vector2 _recoveryLastCanvasSize = Vector2.zero;

    private static bool rewardsShown = false;
    private static bool promoShown = false;
    private static bool recoveryShown = false;

    private void OnEnable()
    {
#if UNITY_EDITOR
        if (!Application.isPlaying)
        {
            ScheduleEditorRecoveryPopupRefresh();
        }
#endif
    }

#if UNITY_EDITOR
    private void OnValidate()
    {
        if (!Application.isPlaying)
        {
            ScheduleEditorRecoveryPopupRefresh();
        }
    }
#endif

    private void Update()
    {
        RefreshRecoveryPopupLayoutIfNeeded();
    }

#if UNITY_EDITOR
    private void OnDisable()
    {
        if (!Application.isPlaying)
        {
            EditorApplication.delayCall -= EnsureRecoveryPopupForEditor;
        }
    }

    private void ScheduleEditorRecoveryPopupRefresh()
    {
        EditorApplication.delayCall -= EnsureRecoveryPopupForEditor;
        EditorApplication.delayCall += EnsureRecoveryPopupForEditor;
    }

    private void EnsureRecoveryPopupForEditor()
    {
        if (this == null || Application.isPlaying)
        {
            return;
        }

        Scene activeScene = gameObject.scene;
        if (!activeScene.IsValid() || !activeScene.isLoaded)
        {
            return;
        }

        EnsureRecoveryPopup();
        if (_recoveryPopupRoot != null)
        {
            _recoveryPopupRoot.SetActive(false);
        }
        if (_recoveryVerifyView != null)
        {
            _recoveryVerifyView.SetActive(false);
        }
        if (_recoveryHomeView != null)
        {
            _recoveryHomeView.SetActive(true);
        }
        CommonUtil.ForceHideExistingStatusPopup();
        RefreshRecoveryPopupLayoutIfNeeded(true);

        EditorUtility.SetDirty(this);
        EditorSceneManager.MarkSceneDirty(activeScene);
    }

    public void EditorEnsureRecoveryPopupForDesign()
    {
        EnsureRecoveryPopupForEditor();
    }
#endif

    async void Awake()
    {
        if (!Application.isPlaying)
        {
            return;
        }

        PrepareRecoveryPopupForRuntime();

        if (!rewardsShown)
        {
            rewardsShown = true; // Mark rewards as shown
            await ShowRewards();
        }
    }

    public async void DailyRewardButton()
    {
        OpenRecoveryPopupFromShortcut();
    }

    private void PrepareRecoveryPopupForRuntime()
    {
        if (!Application.isPlaying)
        {
            return;
        }

        EnsureRecoveryPopup();
        if (_recoveryPopupRoot == null)
        {
            return;
        }

        ShowRecoveryHomeView("Secure Your Account", GetDefaultRecoveryReminderMessage());
        _recoveryPopupRoot.SetActive(false);

        if (_recoveryHomeView != null)
        {
            _recoveryHomeView.SetActive(true);
        }

        if (_recoveryVerifyView != null)
        {
            _recoveryVerifyView.SetActive(false);
        }
    }

    public async Task ShowRewards(bool click = false)
    {
        if (APIManager.Instance == null)
        {
            Debug.LogWarning("DailyRewards skipped because APIManager.Instance is null.");
            return;
        }

        UserSettingOutPuts settingsResponse = await FetchPopupSettings();
        if (settingsResponse?.setting != null)
        {
            if (!click && await TryShowRecoveryReminderPopup(settingsResponse.setting))
            {
                return;
            }

            if (!click && !IsEnabledFlag(settingsResponse.setting.daily_bonus_status, true))
            {
                await TryShowPromotionPopup(settingsResponse.setting);
                return;
            }
        }

        string Url = Configuration.Url + Configuration.Welcomebonus;
        Debug.Log("RES_Check + API-Call + ShowRewards");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        bonus = new WelcomBonusRoot();
        bonus = await APIManager.Instance.Post<WelcomBonusRoot>(Url, formData);
        if (bonus == null)
        {
            Debug.LogWarning("DailyRewards response was null.");
            return;
        }

        if (bonus.welcome_bonus == null)
        {
            Debug.Log("DailyRewards response did not include welcome_bonus data.");
            return;
        }

        Debug.Log("bonus.collected_days" + bonus.collected_days);
        Debug.Log("bonus.welcome_bonus.Count" + bonus.welcome_bonus.Count);
        if (bonus.collected_days <= bonus.welcome_bonus.Count)
        {
            Debug.Log("RES_check + Welcome Count " + bonus.welcome_bonus.Count);
            if (
                !click
                && (
                    bonus.today_collected != "0"
                    || bonus.welcome_bonus.Count == bonus.collected_days
                )
            )
            {
                if (settingsResponse?.setting != null)
                {
                    await TryShowPromotionPopup(settingsResponse.setting);
                }
                return;
            }
            else
            {
                int rewardSlots = dailyrewardlist != null ? dailyrewardlist.Count : 0;
                if (rewardSlots == 0)
                {
                    Debug.LogWarning("DailyRewards has no reward slot objects assigned.");
                    return;
                }

                for (int i = 0; i < bonus.welcome_bonus.Count && i < rewardSlots; i++)
                {
                    if (dailyrewardlist[i] == null)
                    {
                        continue;
                    }

                    dailyrewardlist[i].transform.GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        bonus.welcome_bonus[i].coin;
                    if ((i + 1) <= bonus.collected_days)
                    {
                        dailyrewardlist[i]
                            .transform.GetChild(0)
                            .GetChild(0)
                            .gameObject.SetActive(true);
                    }
                }
            }
            if (dailyrewardpanel != null)
            {
                dailyrewardpanel.gameObject.SetActive(false);
            }
            Debug.Log("RES_check + Open daily rewards");
        }
        if (dailyrewardpanel != null)
        {
            PopUpUtil.ButtonClick(dailyrewardpanel.gameObject);
        }
    }

    public async void Collect()
    {
        await collectRewards();
    }

    public async Task collectRewards()
    {
        string Url = Configuration.Url + Configuration.Collect_welcome_bonus;
        Debug.Log("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        messageprint message = new messageprint();
        message = await APIManager.Instance.Post<messageprint>(Url, formData);
        if (LoaderUtil.instance != null)
        {
            LoaderUtil.instance.ShowToast(message.message);
        }

        Profile profile = GetComponent<Profile>();
        if (profile != null)
        {
            profile.UpdateWallet();
        }

        if (dailyrewardpanel != null)
        {
            PopUpUtil.ButtonCancel(dailyrewardpanel.gameObject);
        }
    }

    private async Task<UserSettingOutPuts> FetchPopupSettings()
    {
        string url = Configuration.Url + Configuration.Usersetting;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        try
        {
            return await APIManager.Instance.Post<UserSettingOutPuts>(url, formData);
        }
        catch (System.Exception exception)
        {
            Debug.LogWarning("DailyRewards settings fetch failed: " + exception.Message);
            return null;
        }
    }

    private static bool IsEnabledFlag(string value, bool defaultValue)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return defaultValue;
        }

        string normalized = value.Trim().ToLowerInvariant();
        return normalized == "1" || normalized == "on" || normalized == "yes" || normalized == "true";
    }

    private async Task TryShowPromotionPopup(Setting setting)
    {
        if (promoShown || setting == null || !IsEnabledFlag(setting.app_popop_status, false))
        {
            return;
        }

        bool hasContent =
            !string.IsNullOrWhiteSpace(setting.app_popup_title)
            || !string.IsNullOrWhiteSpace(setting.app_popup_message)
            || !string.IsNullOrWhiteSpace(setting.app_popup_url)
            || !string.IsNullOrWhiteSpace(setting.app_popup_image);

        if (!hasContent)
        {
            return;
        }

        promoShown = true;
        EnsurePromotionPopup();
        if (_promoPopupRoot == null)
        {
            return;
        }

        _promoTargetUrl = setting.app_popup_url ?? string.Empty;
        if (_promoTitleText != null)
        {
            _promoTitleText.text = string.IsNullOrWhiteSpace(setting.app_popup_title) ? "Promotion" : setting.app_popup_title;
        }
        if (_promoMessageText != null)
        {
            _promoMessageText.text = string.IsNullOrWhiteSpace(setting.app_popup_message) ? "Check out the latest event and offers." : setting.app_popup_message;
        }
        if (_promoButtonText != null)
        {
            _promoButtonText.text = string.IsNullOrWhiteSpace(setting.app_popup_button_text) ? "Open" : setting.app_popup_button_text;
        }

        if (_promoBannerImage != null)
        {
            _promoBannerImage.gameObject.SetActive(false);
            if (!string.IsNullOrWhiteSpace(setting.app_popup_image) && ImageUtil.Instance != null)
            {
                Sprite sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(setting.app_popup_image);
                if (sprite != null)
                {
                    _promoBannerImage.sprite = sprite;
                    _promoBannerImage.gameObject.SetActive(true);
                }
            }
        }

        PopUpUtil.ButtonClick(_promoPopupRoot);
    }

    private async Task<bool> TryShowRecoveryReminderPopup(Setting setting)
    {
        if (!showRecoveryReminderOnStartup || recoveryShown || setting == null || !IsEnabledFlag(setting.recovery_should_show_reminder, false))
        {
            return false;
        }

        EnsureRecoveryPopup();
        if (_recoveryPopupRoot == null)
        {
            return false;
        }

        recoveryShown = true;
        ShowRecoveryHomeView(
            string.IsNullOrWhiteSpace(setting.recovery_reminder_title) ? "Secure Your Account" : setting.recovery_reminder_title,
            string.IsNullOrWhiteSpace(setting.recovery_reminder_message)
                ? "Verify WhatsApp or email recovery now so you can recover your username and password later."
                : setting.recovery_reminder_message
        );
        PopUpUtil.ButtonClick(_recoveryPopupRoot);
        await Task.CompletedTask;
        return true;
    }

    public void OpenRecoveryPopupFromShortcut()
    {
        EnsureRecoveryPopup();
        if (_recoveryPopupRoot == null)
        {
            CommonUtil.ShowStyledMessage("Recovery popup is not available right now.", "Recovery", true);
            return;
        }

        if (dailyrewardpanel != null)
        {
            PopUpUtil.ButtonCancel(dailyrewardpanel.gameObject);
        }

        recoveryShown = true;
        ShowRecoveryHomeView(
            "Secure Your Account",
            "Verify WhatsApp or email recovery now so you can recover your username and password later."
        );
        PopUpUtil.ButtonClick(_recoveryPopupRoot);
    }

    private string GetDefaultRecoveryReminderMessage()
    {
        return "Verify WhatsApp or email recovery now so you can recover your username and password later.";
    }

    private void EnsureRecoveryPopup()
    {
        if (_recoveryPopupRoot != null)
        {
            RefreshRecoveryPopupLayoutIfNeeded(true);
            return;
        }

        Canvas canvas = FindPreferredRecoveryCanvas();

        if (canvas == null)
        {
            return;
        }

        Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        GameObject existingPopup = FindSceneObjectByName(RecoveryPopupName);
        if (existingPopup != null)
        {
            RectTransform popupRect = existingPopup.GetComponent<RectTransform>();
            if (popupRect != null && popupRect.parent != canvas.rootCanvas.transform)
            {
                popupRect.SetParent(canvas.rootCanvas.transform, false);
            }

            _recoveryPopupRoot = existingPopup;
            BindRecoveryPopupReferences();
            WireRecoveryPopupButtons();
            RefreshRecoveryPopupLayoutIfNeeded(true);
            return;
        }

        _recoveryPopupRoot = new GameObject(
            RecoveryPopupName,
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        _recoveryPopupRoot.transform.SetParent(canvas.rootCanvas.transform, false);
        RectTransform rootRect = _recoveryPopupRoot.GetComponent<RectTransform>();
        rootRect.anchorMin = Vector2.zero;
        rootRect.anchorMax = Vector2.one;
        rootRect.offsetMin = Vector2.zero;
        rootRect.offsetMax = Vector2.zero;
        _recoveryPopupRoot.GetComponent<Image>().color = new Color(0f, 0f, 0f, 0.74f);

        GameObject panel = new GameObject(
            "Panel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        panel.transform.SetParent(_recoveryPopupRoot.transform, false);
        _recoveryPanelRect = panel.GetComponent<RectTransform>();
        _recoveryPanelRect.anchorMin = new Vector2(0.5f, 0.5f);
        _recoveryPanelRect.anchorMax = new Vector2(0.5f, 0.5f);
        _recoveryPanelRect.pivot = new Vector2(0.5f, 0.5f);
        _recoveryPanelRect.sizeDelta = new Vector2(720f, 620f);
        Image panelImage = panel.GetComponent<Image>();
        ApplyPopupFrame(panelImage);

        GameObject titleBar = new GameObject(
            "TitleBar",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        titleBar.transform.SetParent(panel.transform, false);
        _recoveryTitleBarRect = titleBar.GetComponent<RectTransform>();
        _recoveryTitleBarRect.anchorMin = new Vector2(0.5f, 1f);
        _recoveryTitleBarRect.anchorMax = new Vector2(0.5f, 1f);
        _recoveryTitleBarRect.pivot = new Vector2(0.5f, 1f);
        _recoveryTitleBarRect.sizeDelta = new Vector2(650f, 100f);
        _recoveryTitleBarRect.anchoredPosition = new Vector2(0f, -50f);
        titleBar.GetComponent<Image>().color = new Color32(72, 16, 34, 215);

        _recoveryCloseButton = CreatePromoButton(panel.transform, "X", new Vector2(72f, 72f), new Vector2(0f, 0f), new Color32(96, 18, 28, 255));
        _recoveryCloseButton.gameObject.name = "CloseButton";

        _recoveryTitleText = CreatePromoText(titleBar.transform, "Secure Your Account", 40, Color.white, TextAlignmentOptions.Center, FontStyles.Bold);
        _recoveryTitleText.gameObject.name = "TitleText";
        RectTransform titleRect = _recoveryTitleText.rectTransform;
        titleRect.anchorMin = Vector2.zero;
        titleRect.anchorMax = Vector2.one;
        titleRect.offsetMin = new Vector2(28f, 0f);
        titleRect.offsetMax = new Vector2(-86f, 0f);

        _recoveryMessageText = CreatePromoText(panel.transform, "", 30, new Color32(255, 228, 190, 255), TextAlignmentOptions.Center, FontStyles.Normal);
        _recoveryMessageText.gameObject.name = "MessageText";
        _recoveryMessageText.enableWordWrapping = true;
        SetPromoRect(_recoveryMessageText.rectTransform, new Vector2(590f, 118f), new Vector2(0f, 350f));

        _recoveryHomeView = new GameObject("HomeView", typeof(RectTransform));
        _recoveryHomeView.transform.SetParent(panel.transform, false);
        SetPromoRect(_recoveryHomeView.GetComponent<RectTransform>(), new Vector2(620f, 300f), new Vector2(0f, -26f));

        _recoveryVerifyWhatsappButton = CreatePromoButton(_recoveryHomeView.transform, "Verify WhatsApp", new Vector2(380f, 100f), new Vector2(0f, 78f), new Color32(24, 128, 42, 255));
        _recoveryVerifyWhatsappButton.gameObject.name = "VerifyWhatsAppButton";

        _recoveryVerifyEmailButton = CreatePromoButton(_recoveryHomeView.transform, "Verify Email", new Vector2(380f, 100f), new Vector2(0f, -20f), new Color32(30, 90, 150, 255));
        _recoveryVerifyEmailButton.gameObject.name = "VerifyEmailButton";

        _recoveryLaterButton = CreatePromoButton(_recoveryHomeView.transform, "Later", new Vector2(380f, 100f), new Vector2(0f, -118f), new Color32(120, 60, 20, 255));
        _recoveryLaterButton.gameObject.name = "LaterButton";

        _recoveryVerifyView = new GameObject("VerifyView", typeof(RectTransform));
        _recoveryVerifyView.transform.SetParent(panel.transform, false);
        SetPromoRect(_recoveryVerifyView.GetComponent<RectTransform>(), new Vector2(640f, 380f), new Vector2(0f, -30f));

        _recoveryVerifyTitleText = CreatePromoText(_recoveryVerifyView.transform, "Verify Recovery", 38, Color.white, TextAlignmentOptions.Center, FontStyles.Bold);
        _recoveryVerifyTitleText.gameObject.name = "VerifyTitleText";
        SetPromoRect(_recoveryVerifyTitleText.rectTransform, new Vector2(540f, 54f), new Vector2(0f, 130f));

        _recoveryChannelInput = CreateRecoveryInput(_recoveryVerifyView.transform, font, "Enter value", false, new Vector2(520f, 70f), new Vector2(0f, 62f));
        _recoveryChannelInput.gameObject.name = "RecoveryChannelInput";
        _recoveryOtpInput = CreateRecoveryInput(_recoveryVerifyView.transform, font, "Enter OTP", false, new Vector2(520f, 70f), new Vector2(0f, -18f));
        _recoveryOtpInput.gameObject.name = "RecoveryOtpInput";

        _recoverySendOtpButton = CreatePromoButton(_recoveryVerifyView.transform, "Send OTP", new Vector2(210f, 68f), new Vector2(-120f, -94f), new Color32(24, 128, 42, 255));
        _recoverySendOtpButton.gameObject.name = "SendOtpButton";
        _recoverySendOtpLabel = _recoverySendOtpButton.GetComponentInChildren<TextMeshProUGUI>(true);

        _recoveryVerifyOtpButton = CreatePromoButton(_recoveryVerifyView.transform, "Verify", new Vector2(210f, 68f), new Vector2(120f, -94f), new Color32(30, 90, 150, 255));
        _recoveryVerifyOtpButton.gameObject.name = "VerifyOtpButton";

        _recoveryBackButton = CreatePromoButton(_recoveryVerifyView.transform, "Back", new Vector2(190f, 62f), new Vector2(0f, -152f), new Color32(110, 40, 20, 255));
        _recoveryBackButton.gameObject.name = "BackButton";

        _recoveryStatusText = CreatePromoText(_recoveryVerifyView.transform, "", 28, new Color32(255, 228, 190, 255), TextAlignmentOptions.Center, FontStyles.Normal);
        _recoveryStatusText.gameObject.name = "StatusText";
        _recoveryStatusText.enableWordWrapping = true;
        SetPromoRect(_recoveryStatusText.rectTransform, new Vector2(540f, 70f), new Vector2(0f, -198f));

        WireRecoveryPopupButtons();
        RefreshRecoveryPopupLayoutIfNeeded(true);

        _recoveryPopupRoot.SetActive(false);
        _recoveryVerifyView.SetActive(false);
    }

    private Canvas FindPreferredRecoveryCanvas()
    {
        Canvas directCanvas = GetComponentInParent<Canvas>(true);
        if (directCanvas != null && directCanvas.gameObject.scene == gameObject.scene)
        {
            return directCanvas.rootCanvas;
        }

        Scene scene = gameObject.scene;
        if (scene.IsValid() && scene.isLoaded)
        {
            Canvas[] sceneCanvases = Resources.FindObjectsOfTypeAll<Canvas>();
            Canvas fallbackRootCanvas = null;
            foreach (Canvas candidate in sceneCanvases)
            {
                if (candidate == null || candidate.gameObject.scene != scene)
                {
                    continue;
                }

                Canvas rootCanvas = candidate.rootCanvas != null ? candidate.rootCanvas : candidate;
                if (rootCanvas == null || rootCanvas.gameObject.scene != scene)
                {
                    continue;
                }

                if (rootCanvas.name == PreferredRecoveryCanvasName)
                {
                    return rootCanvas;
                }

                if (fallbackRootCanvas == null)
                {
                    fallbackRootCanvas = rootCanvas;
                }
            }

            if (fallbackRootCanvas != null)
            {
                return fallbackRootCanvas;
            }
        }

        return null;
    }

    private GameObject FindSceneObjectByName(string objectName)
    {
        Scene scene = gameObject.scene;
        if (!scene.IsValid() || !scene.isLoaded)
        {
            return null;
        }

        GameObject[] roots = scene.GetRootGameObjects();
        foreach (GameObject root in roots)
        {
            GameObject match = FindChildRecursiveByName(root.transform, objectName);
            if (match != null)
            {
                return match;
            }
        }

        return null;
    }

    private static GameObject FindChildRecursiveByName(Transform root, string objectName)
    {
        if (root == null)
        {
            return null;
        }

        if (root.name == objectName)
        {
            return root.gameObject;
        }

        for (int i = 0; i < root.childCount; i++)
        {
            GameObject match = FindChildRecursiveByName(root.GetChild(i), objectName);
            if (match != null)
            {
                return match;
            }
        }

        return null;
    }

    private void ApplyRecoveryEditorPreview()
    {
        if (_recoveryPopupRoot == null)
        {
            return;
        }

        ShowRecoveryHomeView("Secure Your Account", GetDefaultRecoveryReminderMessage());
        _recoveryPopupRoot.SetActive(true);

        if (_recoveryHomeView != null)
        {
            _recoveryHomeView.SetActive(true);
        }

        if (_recoveryVerifyView != null)
        {
            _recoveryVerifyView.SetActive(false);
        }
    }

    private void BindRecoveryPopupReferences()
    {
        if (_recoveryPopupRoot == null)
        {
            return;
        }

        Transform root = _recoveryPopupRoot.transform;
        _recoveryPanelRect = FindRectAny(root, "Panel");
        _recoveryTitleBarRect = FindRectAny(root, "Panel/TitleBar");
        _recoveryCloseButton = FindButtonAny(root, "Panel/CloseButton", "Panel/X-Button");
        _recoveryTitleText = FindTextAny(root, "Panel/TitleBar/TitleText", "Panel/TitleBar/Text");
        _recoveryMessageText = FindTextAny(root, "Panel/MessageText", "Panel/Text");
        _recoveryHomeView = FindObjectAny(root, "Panel/HomeView");
        _recoveryVerifyView = FindObjectAny(root, "Panel/VerifyView");
        _recoveryVerifyWhatsappButton = FindButtonAny(root, "Panel/HomeView/VerifyWhatsAppButton", "Panel/HomeView/VerifyWhatsApp-Button");
        _recoveryVerifyEmailButton = FindButtonAny(root, "Panel/HomeView/VerifyEmailButton", "Panel/HomeView/VerifyEmail-Button");
        _recoveryLaterButton = FindButtonAny(root, "Panel/HomeView/LaterButton", "Panel/HomeView/Later-Button");
        _recoveryVerifyTitleText = FindTextAny(root, "Panel/VerifyView/VerifyTitleText", "Panel/VerifyView/Text");
        _recoveryChannelInput = FindInputAny(root, "Panel/VerifyView/RecoveryChannelInput", "Panel/VerifyView/Entervalue-Input");
        _recoveryOtpInput = FindInputAny(root, "Panel/VerifyView/RecoveryOtpInput", "Panel/VerifyView/EnterOTP-Input");
        _recoverySendOtpButton = FindButtonAny(root, "Panel/VerifyView/SendOtpButton", "Panel/VerifyView/SendOTP-Button");
        _recoveryVerifyOtpButton = FindButtonAny(root, "Panel/VerifyView/VerifyOtpButton", "Panel/VerifyView/Verify-Button");
        _recoveryBackButton = FindButtonAny(root, "Panel/VerifyView/BackButton", "Panel/VerifyView/Back-Button");
        _recoveryStatusText = FindTextAny(root, "Panel/VerifyView/StatusText", "Panel/VerifyView/Text (1)", "Panel/VerifyView/Text");
        _recoverySendOtpLabel = _recoverySendOtpButton != null ? _recoverySendOtpButton.GetComponentInChildren<TextMeshProUGUI>(true) : null;
        NormalizeRecoveryHierarchyNames();
    }

    private void WireRecoveryPopupButtons()
    {
        WireButton(_recoveryCloseButton, CloseRecoveryPopup);
        WireButton(_recoveryVerifyWhatsappButton, () => BeginRecoveryVerification("whatsapp"));
        WireButton(_recoveryVerifyEmailButton, () => BeginRecoveryVerification("email"));
        WireButton(_recoveryLaterButton, DismissRecoveryReminderAsync);
        WireButton(_recoverySendOtpButton, SendRecoveryOtpAsync);
        WireButton(_recoveryVerifyOtpButton, VerifyRecoveryOtpAsync);
        WireButton(_recoveryBackButton, BackToRecoveryHome);
    }

    private void RefreshRecoveryPopupLayoutIfNeeded(bool force = false)
    {
        if (_recoveryPopupRoot == null)
        {
            return;
        }

#if UNITY_EDITOR
        if (!Application.isPlaying && !autoResizeRecoveryPopup)
        {
            return;
        }
#endif

        if (Application.isPlaying && !autoResizeRecoveryPopup)
        {
            return;
        }

        RectTransform canvasRect = _recoveryPopupRoot.transform.parent as RectTransform;
        if (canvasRect == null)
        {
            return;
        }

        Vector2 canvasSize = canvasRect.rect.size;
        if (!force && (canvasSize - _recoveryLastCanvasSize).sqrMagnitude < 1f)
        {
            return;
        }

        _recoveryLastCanvasSize = canvasSize;
        ApplyRecoveryPopupLayout(canvasSize);
    }

    private void ApplyRecoveryPopupLayout(Vector2 canvasSize)
    {
        if (_recoveryPanelRect == null || _recoveryTitleBarRect == null)
        {
            return;
        }

        bool isPortrait = canvasSize.y >= canvasSize.x;
        float sidePadding = isPortrait ? 42f : 60f;
        float verticalPadding = isPortrait ? 110f : 64f;
        float portraitMaxWidth = canvasSize.x * 0.84f;
        float portraitMaxHeight = canvasSize.y * 0.58f;
        float landscapeMaxWidth = Mathf.Min(920f, canvasSize.x * 0.78f);
        float landscapeMaxHeight = Mathf.Min(1040f, canvasSize.y * 0.72f);
        float panelWidth = isPortrait
            ? Mathf.Min(portraitMaxWidth, Mathf.Max(320f, canvasSize.x - (sidePadding * 2f)))
            : Mathf.Min(landscapeMaxWidth, Mathf.Max(420f, canvasSize.x - (sidePadding * 2f)));
        float panelHeight = isPortrait
            ? Mathf.Min(portraitMaxHeight, Mathf.Max(420f, canvasSize.y - (verticalPadding * 2f)))
            : Mathf.Min(landscapeMaxHeight, Mathf.Max(520f, canvasSize.y - (verticalPadding * 2f)));
        _recoveryPanelRect.sizeDelta = new Vector2(panelWidth, panelHeight);

        float titleWidth = Mathf.Max(220f, panelWidth - 120f);
        _recoveryTitleBarRect.sizeDelta = new Vector2(titleWidth, isPortrait ? 74f : 84f);
        _recoveryTitleBarRect.anchoredPosition = new Vector2(0f, isPortrait ? -20f : -26f);

        if (_recoveryCloseButton != null)
        {
            RectTransform closeRect = _recoveryCloseButton.GetComponent<RectTransform>();
            SetAnchoredRect(closeRect, new Vector2(72f, 72f), new Vector2(1f, 1f), new Vector2(1f, 1f), new Vector2(1f, 1f), new Vector2(-20f, -20f));
            TextMeshProUGUI closeText = _recoveryCloseButton.GetComponentInChildren<TextMeshProUGUI>(true);
            if (closeText != null)
            {
                closeText.fontSize = isPortrait ? 28 : 34;
            }
        }

        if (_recoveryTitleText != null)
        {
            _recoveryTitleText.fontSize = isPortrait ? 31 : 40;
        }

        if (_recoveryMessageText != null)
        {
            _recoveryMessageText.fontSize = isPortrait ? 24 : 31;
            SetPromoRect(_recoveryMessageText.rectTransform, new Vector2(panelWidth - (isPortrait ? 64f : 120f), isPortrait ? 104f : 190f), new Vector2(0f, (panelHeight * 0.5f) - (isPortrait ? 116f : 250f)));
        }

        RectTransform homeRect = _recoveryHomeView != null ? _recoveryHomeView.GetComponent<RectTransform>() : null;
        if (homeRect != null)
        {
            SetPromoRect(homeRect, new Vector2(panelWidth - (isPortrait ? 54f : 100f), Mathf.Max(250f, panelHeight - (isPortrait ? 180f : 430f))), new Vector2(0f, isPortrait ? -22f : -70f));
        }

        float homeButtonWidth = Mathf.Min(isPortrait ? panelWidth - 118f : 470f, panelWidth - 140f);
        float homePrimaryHeight = isPortrait ? 72f : 102f;
        float homeSecondaryHeight = isPortrait ? 68f : 96f;
        PositionRecoveryButton(_recoveryVerifyWhatsappButton, homeButtonWidth, homePrimaryHeight, isPortrait ? 84f : 110f, isPortrait);
        PositionRecoveryButton(_recoveryVerifyEmailButton, homeButtonWidth, homePrimaryHeight, isPortrait ? -18f : -20f, isPortrait);
        PositionRecoveryButton(_recoveryLaterButton, homeButtonWidth, homeSecondaryHeight, isPortrait ? -120f : -150f, isPortrait);

        RectTransform verifyRect = _recoveryVerifyView != null ? _recoveryVerifyView.GetComponent<RectTransform>() : null;
        float verifyWidth = panelWidth - (isPortrait ? 44f : 90f);
        float verifyHeight = Mathf.Max(300f, panelHeight - (isPortrait ? 170f : 410f));
        if (verifyRect != null)
        {
            SetPromoRect(verifyRect, new Vector2(verifyWidth, verifyHeight), new Vector2(0f, isPortrait ? -20f : -70f));
        }

        if (_recoveryVerifyTitleText != null)
        {
            _recoveryVerifyTitleText.fontSize = isPortrait ? 28 : 38;
            SetPromoRect(_recoveryVerifyTitleText.rectTransform, new Vector2(verifyWidth - 54f, 54f), new Vector2(0f, (verifyHeight * 0.5f) - (isPortrait ? 34f : 52f)));
        }

        if (_recoveryChannelInput != null)
        {
            SetPromoRect(_recoveryChannelInput.GetComponent<RectTransform>(), new Vector2(verifyWidth - 54f, isPortrait ? 66f : 100f), new Vector2(0f, isPortrait ? 48f : 120f));
        }

        if (_recoveryOtpInput != null)
        {
            SetPromoRect(_recoveryOtpInput.GetComponent<RectTransform>(), new Vector2(verifyWidth - 54f, isPortrait ? 66f : 100f), new Vector2(0f, isPortrait ? -24f : -10f));
        }

        float actionButtonWidth = Mathf.Min(isPortrait ? 180f : 300f, (verifyWidth - (isPortrait ? 64f : 110f)) * 0.5f);
        float actionButtonHeight = isPortrait ? 64f : 92f;
        PositionVerifyActionButton(_recoverySendOtpButton, -((actionButtonWidth * 0.5f) + (isPortrait ? 8f : 16f)), actionButtonWidth, actionButtonHeight, isPortrait ? -92f : -155f, isPortrait);
        PositionVerifyActionButton(_recoveryVerifyOtpButton, (actionButtonWidth * 0.5f) + (isPortrait ? 8f : 16f), actionButtonWidth, actionButtonHeight, isPortrait ? -92f : -155f, isPortrait);
        PositionRecoveryButton(_recoveryBackButton, Mathf.Min(isPortrait ? 168f : 240f, verifyWidth - 128f), isPortrait ? 58f : 84f, isPortrait ? -146f : -275f, isPortrait);

        if (_recoveryStatusText != null)
        {
            _recoveryStatusText.fontSize = isPortrait ? 22 : 28;
            SetPromoRect(_recoveryStatusText.rectTransform, new Vector2(verifyWidth - 20f, isPortrait ? 60f : 120f), new Vector2(0f, isPortrait ? -190f : -335f));
        }

        ResizeButtonLabel(_recoveryVerifyWhatsappButton, isPortrait ? 28 : 34);
        ResizeButtonLabel(_recoveryVerifyEmailButton, isPortrait ? 28 : 34);
        ResizeButtonLabel(_recoveryLaterButton, isPortrait ? 28 : 34);
        ResizeButtonLabel(_recoverySendOtpButton, isPortrait ? 24 : 34);
        ResizeButtonLabel(_recoveryVerifyOtpButton, isPortrait ? 24 : 34);
        ResizeButtonLabel(_recoveryBackButton, isPortrait ? 24 : 34);
    }

    private static void PositionRecoveryButton(Button button, float width, float height, float y, bool isPortrait)
    {
        if (button == null)
        {
            return;
        }

        SetPromoRect(button.GetComponent<RectTransform>(), new Vector2(width, height), new Vector2(0f, y));
        ResizeButtonLabel(button, isPortrait ? 28 : 34);
    }

    private static void PositionVerifyActionButton(Button button, float x, float width, float height, float y, bool isPortrait)
    {
        if (button == null)
        {
            return;
        }

        SetPromoRect(button.GetComponent<RectTransform>(), new Vector2(width, height), new Vector2(x, y));
        ResizeButtonLabel(button, isPortrait ? 24 : 34);
    }

    private static void ResizeButtonLabel(Button button, int fontSize)
    {
        if (button == null)
        {
            return;
        }

        TextMeshProUGUI label = button.GetComponentInChildren<TextMeshProUGUI>(true);
        if (label != null)
        {
            label.fontSize = fontSize;
        }
    }

    private static void WireButton(Button button, UnityEngine.Events.UnityAction action)
    {
        if (button == null)
        {
            return;
        }

        button.onClick.RemoveAllListeners();
        button.onClick.AddListener(action);
    }

    private void NormalizeRecoveryHierarchyNames()
    {
        RenameIfPresent(_recoveryCloseButton, "CloseButton");
        RenameIfPresent(_recoveryTitleText, "TitleText");
        RenameIfPresent(_recoveryMessageText, "MessageText");
        RenameIfPresent(_recoveryVerifyWhatsappButton, "VerifyWhatsAppButton");
        RenameIfPresent(_recoveryVerifyEmailButton, "VerifyEmailButton");
        RenameIfPresent(_recoveryLaterButton, "LaterButton");
        RenameIfPresent(_recoveryVerifyTitleText, "VerifyTitleText");
        RenameIfPresent(_recoveryChannelInput, "RecoveryChannelInput");
        RenameIfPresent(_recoveryOtpInput, "RecoveryOtpInput");
        RenameIfPresent(_recoverySendOtpButton, "SendOtpButton");
        RenameIfPresent(_recoveryVerifyOtpButton, "VerifyOtpButton");
        RenameIfPresent(_recoveryBackButton, "BackButton");
        RenameIfPresent(_recoveryStatusText, "StatusText");
    }

    private static void RenameIfPresent(Component component, string name)
    {
        if (component != null)
        {
            component.gameObject.name = name;
        }
    }

    private static GameObject FindObject(Transform root, string path)
    {
        Transform found = root.Find(path);
        return found != null ? found.gameObject : null;
    }

    private static GameObject FindObjectAny(Transform root, params string[] paths)
    {
        foreach (string path in paths)
        {
            GameObject found = FindObject(root, path);
            if (found != null)
            {
                return found;
            }
        }

        return null;
    }

    private static RectTransform FindRect(Transform root, string path)
    {
        GameObject found = FindObject(root, path);
        return found != null ? found.GetComponent<RectTransform>() : null;
    }

    private static RectTransform FindRectAny(Transform root, params string[] paths)
    {
        GameObject found = FindObjectAny(root, paths);
        return found != null ? found.GetComponent<RectTransform>() : null;
    }

    private static TextMeshProUGUI FindText(Transform root, string path)
    {
        GameObject found = FindObject(root, path);
        return found != null ? found.GetComponent<TextMeshProUGUI>() : null;
    }

    private static TextMeshProUGUI FindTextAny(Transform root, params string[] paths)
    {
        GameObject found = FindObjectAny(root, paths);
        return found != null ? found.GetComponent<TextMeshProUGUI>() : null;
    }

    private static Button FindButton(Transform root, string path)
    {
        GameObject found = FindObject(root, path);
        return found != null ? found.GetComponent<Button>() : null;
    }

    private static Button FindButtonAny(Transform root, params string[] paths)
    {
        GameObject found = FindObjectAny(root, paths);
        return found != null ? found.GetComponent<Button>() : null;
    }

    private static InputField FindInput(Transform root, string path)
    {
        GameObject found = FindObject(root, path);
        return found != null ? found.GetComponent<InputField>() : null;
    }

    private static InputField FindInputAny(Transform root, params string[] paths)
    {
        GameObject found = FindObjectAny(root, paths);
        return found != null ? found.GetComponent<InputField>() : null;
    }

    private void ShowRecoveryHomeView(string title, string message)
    {
        if (_recoveryTitleText != null)
        {
            _recoveryTitleText.text = title;
        }

        if (_recoveryMessageText != null)
        {
            _recoveryMessageText.text = message;
        }

        if (_recoveryHomeView != null)
        {
            _recoveryHomeView.SetActive(true);
        }

        if (_recoveryVerifyView != null)
        {
            _recoveryVerifyView.SetActive(false);
        }

        _recoveryChannelType = string.Empty;
        _recoveryPendingOtpId = string.Empty;
        _recoveryPendingChannelValue = string.Empty;
    }

    private void BeginRecoveryVerification(string channelType)
    {
        _recoveryChannelType = channelType;
        _recoveryPendingOtpId = string.Empty;
        _recoveryPendingChannelValue = string.Empty;

        if (_recoveryHomeView != null)
        {
            _recoveryHomeView.SetActive(false);
        }

        if (_recoveryVerifyView != null)
        {
            _recoveryVerifyView.SetActive(true);
        }

        if (_recoveryVerifyTitleText != null)
        {
            _recoveryVerifyTitleText.text = channelType == "whatsapp" ? "Verify WhatsApp" : "Verify Email";
        }

        if (_recoveryChannelInput != null)
        {
            _recoveryChannelInput.text = string.Empty;
            _recoveryChannelInput.contentType = channelType == "email" ? InputField.ContentType.EmailAddress : InputField.ContentType.Standard;
            _recoveryChannelInput.characterValidation = channelType == "email" ? InputField.CharacterValidation.EmailAddress : InputField.CharacterValidation.None;
            Text placeholder = _recoveryChannelInput.placeholder as Text;
            if (placeholder != null)
            {
                placeholder.text = channelType == "whatsapp" ? "Enter WhatsApp number" : "Enter email address";
            }
            _recoveryChannelInput.ForceLabelUpdate();
        }

        if (_recoveryOtpInput != null)
        {
            _recoveryOtpInput.text = string.Empty;
        }

        if (_recoverySendOtpLabel != null)
        {
            _recoverySendOtpLabel.text = "Send OTP";
        }

        if (_recoveryStatusText != null)
        {
            _recoveryStatusText.text = channelType == "whatsapp"
                ? "We'll send a verification code to this WhatsApp number."
                : "We'll send a verification code to this email address.";
        }
    }

    private void BackToRecoveryHome()
    {
        ShowRecoveryHomeView(
            _recoveryTitleText != null ? _recoveryTitleText.text : "Secure Your Account",
            _recoveryMessageText != null ? _recoveryMessageText.text : "Verify WhatsApp or email recovery now."
        );
    }

    private async void DismissRecoveryReminderAsync()
    {
        await SendRecoveryReminderDismiss();
        CloseRecoveryPopup();
    }

    private async void SendRecoveryOtpAsync()
    {
        if (APIManager.Instance == null || string.IsNullOrWhiteSpace(_recoveryChannelType) || _recoveryChannelInput == null)
        {
            return;
        }

        string channelValue = NormalizeRecoveryValue(_recoveryChannelType, _recoveryChannelInput.text);
        string localValidationError = ValidateRecoveryInput(_recoveryChannelType, channelValue);
        if (!string.IsNullOrWhiteSpace(localValidationError))
        {
            SetRecoveryStatus(localValidationError, true);
            return;
        }

        string url = Configuration.Url + Configuration.UserRecoverySendOtp;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "channel_type", _recoveryChannelType },
            { "channel_value", channelValue },
        };

        OTP response = await APIManager.Instance.Post<OTP>(url, formData);
        if (response == null)
        {
            SetRecoveryStatus("Recovery OTP request failed. Please try again.", true);
            return;
        }

        if (response.code == 429)
        {
            SetRecoveryStatus(
                response.retry_after > 0
                    ? $"Please wait {response.retry_after}s before requesting another OTP."
                    : (string.IsNullOrWhiteSpace(response.message) ? "Please wait before requesting another OTP." : response.message),
                true
            );
            return;
        }

        if (response.code != 200 || string.IsNullOrWhiteSpace(response.otp_id))
        {
            SetRecoveryStatus(string.IsNullOrWhiteSpace(response.message) ? "Recovery OTP request failed." : response.message, true);
            return;
        }

        _recoveryPendingOtpId = response.otp_id;
        _recoveryPendingChannelValue = channelValue;
        if (_recoverySendOtpLabel != null)
        {
            _recoverySendOtpLabel.text = "Resend OTP";
        }

        SetRecoveryStatus("OTP sent. Enter the code to verify this recovery channel.", false);
    }

    private async void VerifyRecoveryOtpAsync()
    {
        if (APIManager.Instance == null)
        {
            return;
        }

        if (string.IsNullOrWhiteSpace(_recoveryPendingOtpId) || string.IsNullOrWhiteSpace(_recoveryPendingChannelValue))
        {
            SetRecoveryStatus("Send OTP first.", true);
            return;
        }

        if (_recoveryOtpInput == null || string.IsNullOrWhiteSpace(_recoveryOtpInput.text))
        {
            SetRecoveryStatus("Please enter OTP.", true);
            return;
        }

        string url = Configuration.Url + Configuration.UserRecoveryVerifyOtp;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "channel_type", _recoveryChannelType },
            { "channel_value", _recoveryPendingChannelValue },
            { "otp_id", _recoveryPendingOtpId },
            { "otp", _recoveryOtpInput.text.Trim() },
        };

        UserSettingOutPuts response = await APIManager.Instance.Post<UserSettingOutPuts>(url, formData);
        if (response == null)
        {
            SetRecoveryStatus("Recovery verification failed. Please try again.", true);
            return;
        }

        if (response.code != 200)
        {
            SetRecoveryStatus(string.IsNullOrWhiteSpace(response.message) ? "Recovery verification failed." : response.message, true);
            return;
        }

        CommonUtil.ShowStyledMessage(
            string.IsNullOrWhiteSpace(response.message) ? "Recovery channel verified." : response.message,
            "Verification Success",
            isError: false
        );
        CloseRecoveryPopup();
    }

    private async Task SendRecoveryReminderDismiss()
    {
        if (APIManager.Instance == null)
        {
            return;
        }

        string url = Configuration.Url + Configuration.UserRecoveryReminderDismiss;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        await APIManager.Instance.Post<UserSettingOutPuts>(url, formData);
    }

    private void CloseRecoveryPopup()
    {
        if (_recoveryPopupRoot != null)
        {
            PopUpUtil.ButtonCancel(_recoveryPopupRoot);
        }
    }

    private void SetRecoveryStatus(string message, bool isError)
    {
        if (_recoveryStatusText != null)
        {
            _recoveryStatusText.color = isError ? new Color32(255, 190, 190, 255) : new Color32(205, 255, 190, 255);
            _recoveryStatusText.text = message;
        }
    }

    private static string NormalizeRecoveryValue(string channelType, string input)
    {
        if (string.IsNullOrWhiteSpace(input))
        {
            return string.Empty;
        }

        if (channelType == "email")
        {
            return input.Trim().ToLowerInvariant();
        }

        System.Text.StringBuilder builder = new System.Text.StringBuilder();
        foreach (char c in input)
        {
            if (char.IsDigit(c))
            {
                builder.Append(c);
            }
        }
        return builder.ToString();
    }

    private static string ValidateRecoveryInput(string channelType, string value)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return channelType == "email" ? "Please enter email address." : "Please enter WhatsApp number.";
        }

        if (channelType == "email")
        {
            return value.Contains("@") && value.Contains(".") ? string.Empty : "Please enter a valid email address.";
        }

        return value.Length < 10 || value.Length > 15 ? "Please enter a valid WhatsApp number." : string.Empty;
    }

    private void EnsurePromotionPopup()
    {
        if (_promoPopupRoot != null)
        {
            return;
        }

        Canvas canvas = GetComponentInParent<Canvas>(true);
        if (canvas == null)
        {
            Canvas[] canvases = Resources.FindObjectsOfTypeAll<Canvas>();
            if (canvases != null && canvases.Length > 0)
            {
                canvas = canvases[0];
            }
        }

        if (canvas == null)
        {
            return;
        }

        _promoPopupRoot = new GameObject(
            "Startup-Promotion-Popup",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        _promoPopupRoot.transform.SetParent(canvas.rootCanvas.transform, false);
        RectTransform rootRect = _promoPopupRoot.GetComponent<RectTransform>();
        rootRect.anchorMin = Vector2.zero;
        rootRect.anchorMax = Vector2.one;
        rootRect.offsetMin = Vector2.zero;
        rootRect.offsetMax = Vector2.zero;
        _promoPopupRoot.GetComponent<Image>().color = new Color(0f, 0f, 0f, 0.74f);

        GameObject panel = new GameObject(
            "Panel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        panel.transform.SetParent(_promoPopupRoot.transform, false);
        RectTransform panelRect = panel.GetComponent<RectTransform>();
        panelRect.anchorMin = new Vector2(0.5f, 0.5f);
        panelRect.anchorMax = new Vector2(0.5f, 0.5f);
        panelRect.pivot = new Vector2(0.5f, 0.5f);
        panelRect.sizeDelta = new Vector2(860f, 980f);
        Image panelImage = panel.GetComponent<Image>();
        panelImage.color = new Color32(88, 16, 22, 255);

        Button closeButton = CreatePromoButton(panel.transform, "X", new Vector2(82f, 82f), new Vector2(360f, 420f), new Color32(96, 18, 28, 255));
        closeButton.onClick.AddListener(() => PopUpUtil.ButtonCancel(_promoPopupRoot));

        _promoTitleText = CreatePromoText(panel.transform, "Promotion", 36, Color.white, TextAlignmentOptions.Center, FontStyles.Bold);
        SetPromoRect(_promoTitleText.rectTransform, new Vector2(620f, 70f), new Vector2(0f, 390f));

        GameObject bannerGo = new GameObject("Banner", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
        bannerGo.transform.SetParent(panel.transform, false);
        _promoBannerImage = bannerGo.GetComponent<Image>();
        _promoBannerImage.preserveAspect = true;
        _promoBannerImage.color = Color.white;
        SetPromoRect(_promoBannerImage.rectTransform, new Vector2(720f, 360f), new Vector2(0f, 150f));
        _promoBannerImage.gameObject.SetActive(false);

        _promoMessageText = CreatePromoText(panel.transform, "", 28, new Color32(255, 228, 190, 255), TextAlignmentOptions.Center, FontStyles.Normal);
        _promoMessageText.enableWordWrapping = true;
        SetPromoRect(_promoMessageText.rectTransform, new Vector2(720f, 180f), new Vector2(0f, -120f));

        Button actionButton = CreatePromoButton(panel.transform, "Open", new Vector2(340f, 92f), new Vector2(0f, -360f), new Color32(24, 128, 42, 255));
        _promoButtonText = actionButton.GetComponentInChildren<TextMeshProUGUI>(true);
        actionButton.onClick.AddListener(OpenPromotionTarget);

        _promoPopupRoot.SetActive(false);
    }

    private void OpenPromotionTarget()
    {
        if (string.IsNullOrWhiteSpace(_promoTargetUrl))
        {
            if (LoaderUtil.instance != null)
            {
                LoaderUtil.instance.ShowToast("Promotion link not available.");
            }
            return;
        }

        Application.OpenURL(_promoTargetUrl);
        if (_promoPopupRoot != null)
        {
            PopUpUtil.ButtonCancel(_promoPopupRoot);
        }
    }

    private void ApplyPopupFrame(Image panelImage)
    {
        if (panelImage == null)
        {
            return;
        }

        if (popupFrameSprite != null)
        {
            panelImage.sprite = popupFrameSprite;
            panelImage.type = Image.Type.Sliced;
            panelImage.color = Color.white;
            panelImage.preserveAspect = false;
            return;
        }

        panelImage.color = new Color32(76, 18, 40, 255);
    }

    private static TextMeshProUGUI CreatePromoText(Transform parent, string textValue, int fontSize, Color color, TextAlignmentOptions alignment, FontStyles fontStyle)
    {
        GameObject go = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(TextMeshProUGUI));
        go.transform.SetParent(parent, false);
        TextMeshProUGUI text = go.GetComponent<TextMeshProUGUI>();
        text.text = textValue;
        text.fontSize = fontSize;
        text.color = color;
        text.alignment = alignment;
        text.fontStyle = fontStyle;
        return text;
    }

    private Button CreatePromoButton(Transform parent, string label, Vector2 size, Vector2 anchoredPosition, Color32 color)
    {
        GameObject buttonGo = new GameObject(
            label.Replace(" ", string.Empty) + "-Button",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Button),
            typeof(Outline)
        );
        buttonGo.transform.SetParent(parent, false);
        RectTransform rect = buttonGo.GetComponent<RectTransform>();
        SetPromoRect(rect, size, anchoredPosition);
        Image image = buttonGo.GetComponent<Image>();
        image.color = color;
        Outline outline = buttonGo.GetComponent<Outline>();
        outline.effectColor = new Color32(255, 190, 90, 120);
        outline.effectDistance = new Vector2(2f, -2f);

        TextMeshProUGUI text = CreatePromoText(buttonGo.transform, label, 34, Color.white, TextAlignmentOptions.Center, FontStyles.Bold);
        RectTransform textRect = text.rectTransform;
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = Vector2.zero;
        textRect.offsetMax = Vector2.zero;
        text.raycastTarget = false;

        return buttonGo.GetComponent<Button>();
    }

    private static InputField CreateRecoveryInput(Transform parent, Font font, string placeholderText, bool password, Vector2 size, Vector2 anchoredPosition)
    {
        GameObject go = new GameObject(
            placeholderText.Replace(" ", string.Empty) + "-Input",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(InputField),
            typeof(Outline)
        );
        go.transform.SetParent(parent, false);
        RectTransform rect = go.GetComponent<RectTransform>();
        SetPromoRect(rect, size, anchoredPosition);

        Image bg = go.GetComponent<Image>();
        bg.color = new Color32(55, 10, 24, 255);
        Outline outline = go.GetComponent<Outline>();
        outline.effectColor = new Color32(255, 190, 90, 110);
        outline.effectDistance = new Vector2(2f, -2f);

        GameObject placeholderGo = new GameObject("Placeholder", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        placeholderGo.transform.SetParent(go.transform, false);
        Text placeholder = placeholderGo.GetComponent<Text>();
        placeholder.font = font;
        placeholder.text = placeholderText;
        placeholder.fontSize = 30;
        placeholder.color = new Color32(190, 150, 160, 190);
        placeholder.alignment = TextAnchor.MiddleLeft;
        placeholder.raycastTarget = false;
        RectTransform placeholderRect = placeholder.GetComponent<RectTransform>();
        placeholderRect.anchorMin = Vector2.zero;
        placeholderRect.anchorMax = Vector2.one;
        placeholderRect.offsetMin = new Vector2(20f, 0f);
        placeholderRect.offsetMax = new Vector2(-20f, 0f);

        GameObject textGo = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        textGo.transform.SetParent(go.transform, false);
        Text text = textGo.GetComponent<Text>();
        text.font = font;
        text.fontSize = 30;
        text.color = Color.white;
        text.alignment = TextAnchor.MiddleLeft;
        RectTransform textRect = text.GetComponent<RectTransform>();
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = new Vector2(20f, 0f);
        textRect.offsetMax = new Vector2(-20f, 0f);

        InputField input = go.GetComponent<InputField>();
        input.targetGraphic = bg;
        input.textComponent = text;
        input.placeholder = placeholder;
        input.lineType = InputField.LineType.SingleLine;
        input.contentType = password ? InputField.ContentType.Password : InputField.ContentType.Standard;
        input.characterValidation = InputField.CharacterValidation.None;

        return input;
    }

    private static void SafeShowToast(string message)
    {
        if (LoaderUtil.instance != null)
        {
            LoaderUtil.instance.ShowToast(message);
        }
        else
        {
            CommonUtil.ShowToast(message);
        }
    }

    private static void SetPromoRect(RectTransform rect, Vector2 size, Vector2 anchoredPosition)
    {
        rect.anchorMin = new Vector2(0.5f, 0.5f);
        rect.anchorMax = new Vector2(0.5f, 0.5f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.sizeDelta = size;
        rect.anchoredPosition = anchoredPosition;
        rect.localScale = Vector3.one;
    }

    private static void SetAnchoredRect(RectTransform rect, Vector2 size, Vector2 anchorMin, Vector2 anchorMax, Vector2 pivot, Vector2 anchoredPosition)
    {
        rect.anchorMin = anchorMin;
        rect.anchorMax = anchorMax;
        rect.pivot = pivot;
        rect.sizeDelta = size;
        rect.anchoredPosition = anchoredPosition;
        rect.localScale = Vector3.one;
    }
}
