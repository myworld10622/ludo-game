using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using System.Globalization;
using System.Linq;
using System.Net;
using System.Threading.Tasks;
using Gpm.WebView;
using Mkey;
using TMPro;
using Unity.Burst.Intrinsics;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

// [ExecuteAlways] removed to prevent overriding manual Inspector adjustments in the Editor.
public class PaymentManager : MonoBehaviour
{
    public Transform content;
    public GameObject prefab;
    public TMP_InputField custom,
        utr_inputfield;
    public GameObject dialogue,
        manual_panel;
    public Button automatic_button;
    public Image qr_code_image,
        manual_ss_img;
    public GameObject manual_ss_logo;
    private int automatic_amount,
        manual_amount;
    private string _selectedAutomaticPlanId = string.Empty;
    private string _selectedAutomaticAmount = string.Empty;
    public GameObject transaction_prefab;
    public Transform transaction_parent;

    public List<GameObject> transaction_panel_obj;
    public List<GameObject> add_cash_panel_obj;

    private Dictionary<string, GameObject> spawned_objects = new Dictionary<string, GameObject>();
    List<GameObject> instantiatedHistory = new List<GameObject>();

    public GameObject selectedAddcash,
        finalpanel;
    public GameObject selectedRecentTransaction;

    public Sprite UploadScreenshort;
    [Header("Skin")]
    public Image[] backgroundImages;
    public Sprite popupBgSprite;        // Pop up.png
    public Sprite amountBtnSprite;      // add-button.png  (optional, for preset buttons)
    public Sprite toggleSprite;         // toggle bae.png  (optional, for pay method tabs)
    public USDTManual uSDTManual;
    public TextMeshProUGUI principal,
        bonus,
        newamount;
    public List<GameObject> buttonsobjs;

    public GameObject USDT_AUTO;

    [Header("Manual Gateway UI")]
    public TextMeshProUGUI manualGatewayNameText;
    public TextMeshProUGUI manualUpiIdText;
    public TextMeshProUGUI manualBankNameText;
    public TextMeshProUGUI manualAccountHolderText;
    public TextMeshProUGUI manualAccountNumberText;
    public TextMeshProUGUI manualIfscCodeText;
    public GameObject manualUpiSection;
    public GameObject manualBankSection;

    // payment method: 0 = UPI/Bank, 1 = Crypto
    private int _paymentMethod = 0;
    private GameObject _upiTab, _cryptoTab;
    private GameObject _extrasRoot;   // holds injected UI — destroyed on disable
    private Vector2 _lastLayoutCanvasSize = new Vector2(-1f, -1f);
    private Vector2 _lastLayoutScreenSize = new Vector2(-1f, -1f);
    private ScreenOrientation _lastLayoutOrientation = ScreenOrientation.Unknown;
    private bool _lastLayoutPortrait = true;
    private bool _isApplyingLayout = false;
    private bool _layoutInitialized = false;
    private int _pendingGatewayOrderId;
    private string _pendingGatewayTransactionId;
    private bool _isAutomaticStatusCheckRunning;
    private bool _automaticGatewayFlowOpen;
    private bool _isManualPaymentSubmitting;
    private PaymentOptionsConfig _paymentOptionsConfig;
    private GameObject _transferQuickButton;
    private GameObject _transferPopupRoot;
    private GameObject _transferConfirmPopupRoot;
    private InputField _transferQueryInput;
    private InputField _transferAmountInput;
    private Text _transferLookupSummaryText;
    private Text _transferHistoryEmptyText;
    private RectTransform _transferHistoryContent;
    private GameObject _transferHistorySectionRoot;
    private TransferPlayer _selectedTransferPlayer;
    private bool _isTransferLookupRunning;
    private bool _isTransferSubmitRunning;

    void OnDisable()
    {
        if (_extrasRoot != null) { RemoveGeneratedObject(_extrasRoot); _extrasRoot = null; }
        if (_transferQuickButton != null) { RemoveGeneratedObject(_transferQuickButton); _transferQuickButton = null; }
        if (_transferPopupRoot != null) { RemoveGeneratedObject(_transferPopupRoot); _transferPopupRoot = null; }
        if (_transferConfirmPopupRoot != null) { RemoveGeneratedObject(_transferConfirmPopupRoot); _transferConfirmPopupRoot = null; }
        _upiTab = null; _cryptoTab = null;
        _transferQueryInput = null;
        _transferAmountInput = null;
        _transferLookupSummaryText = null;
        _transferHistoryEmptyText = null;
        _transferHistoryContent = null;
        _transferHistorySectionRoot = null;
        _selectedTransferPlayer = null;
    }

    async void OnEnable()
    {
        _layoutInitialized = false;
#if UNITY_WEBGL
        if (USDT_AUTO != null) USDT_AUTO.SetActive(false);
#endif
        ApplyDirectSkin();
        ApplyResponsiveAddCashLayout();
        
        await AvailableChips();
        if (this == null || !gameObject.activeInHierarchy) return;
        
        DefaultSet();
        BindPaymentOptionDialogButtons();
    }

    void Update()
    {
        if (!gameObject.activeInHierarchy) return;

        Canvas canvas = GetComponentInParent<Canvas>();
        RectTransform canvasRect = canvas != null ? canvas.transform as RectTransform : null;
        Vector2 canvasSize = canvasRect != null
            ? new Vector2(canvasRect.rect.width, canvasRect.rect.height)
            : new Vector2(Screen.width, Screen.height);
        Vector2 screenSize = new Vector2(Screen.width, Screen.height);
        bool portrait = IsPortraitLayout(canvasSize);

        if (Vector2.SqrMagnitude(canvasSize - _lastLayoutCanvasSize) < 0.25f
            && Vector2.SqrMagnitude(screenSize - _lastLayoutScreenSize) < 0.25f
            && Screen.orientation == _lastLayoutOrientation
            && portrait == _lastLayoutPortrait)
        {
            return;
        }

        // Only apply layout if resolution or orientation actually changed
        if (_layoutInitialized && 
            canvasSize == _lastLayoutCanvasSize && 
            screenSize == _lastLayoutScreenSize && 
            Screen.orientation == _lastLayoutOrientation) 
            return;

        ApplyResponsiveAddCashLayout();
    }

    private void ApplyDirectSkin()
    {
        // Add Cash visuals are authored in CanvasMain/ADD Chip. Do not recolor
        // scene images at runtime, otherwise Play Mode diverges from hierarchy.
    }

    // ── Inject Bank/UPI + Crypto toggle and preset amounts ─────────────────────
    private void InjectAddCashExtras()
    {
        if (content == null) return;

        Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");

        // Build a standalone GameObject OUTSIDE scroll — sibling of scroll's parent
        // This avoids any conflict with existing layout on content
        Transform panel = content.parent?.parent ?? content.parent ?? transform;

        _extrasRoot = new GameObject("_AddCashExtras",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
        _extrasRoot.transform.SetParent(panel, false);

        // Full-width strip anchored below header area
        var rt = _extrasRoot.GetComponent<RectTransform>();
        rt.anchorMin        = new Vector2(0f,   0.04f);
        rt.anchorMax        = new Vector2(1f,   0.82f);
        rt.offsetMin        = new Vector2(10f,  0f);
        rt.offsetMax        = new Vector2(-10f, 0f);

        _extrasRoot.GetComponent<Image>().color = new Color32(0, 0, 0, 0); // transparent

        // Vertical stack
        var vl = _extrasRoot.AddComponent<VerticalLayoutGroup>();
        vl.spacing               = 16f;
        vl.padding               = new RectOffset(12, 12, 12, 12);
        vl.childControlWidth     = true;
        vl.childControlHeight    = true;
        vl.childForceExpandWidth  = true;
        vl.childForceExpandHeight = false;
        vl.childAlignment        = TextAnchor.UpperCenter;

        // ── UPI / Bank  |  Crypto tabs ─────────────────────────────────────────
        var methodRow = MakeHRow(_extrasRoot.transform, 12f, 90f);
        _upiTab    = MakePayMethodBtn(methodRow.transform, font, "UPI / Bank", active: true,  sprite: toggleSprite);
        _cryptoTab = MakePayMethodBtn(methodRow.transform, font, "Crypto",     active: false, sprite: toggleSprite);

        _upiTab.GetComponent<Button>().onClick.AddListener(() => {
            _paymentMethod = 0;
            SetPayMethodStyle(_upiTab,    active: true);
            SetPayMethodStyle(_cryptoTab, active: false);
            if (USDT_AUTO != null) USDT_AUTO.SetActive(false);
        });
        _cryptoTab.GetComponent<Button>().onClick.AddListener(() => {
            _paymentMethod = 1;
            SetPayMethodStyle(_cryptoTab, active: true);
            SetPayMethodStyle(_upiTab,    active: false);
            if (USDT_AUTO != null) USDT_AUTO.SetActive(true);
        });

        // ── "Select Amount" label ──────────────────────────────────────────────
        MakeLabel(_extrasRoot.transform, font, "Select Amount:", 28);

        // ── Preset buttons — 2 per row ─────────────────────────────────────────
        int[] presets = { 100, 500, 2500, 5000, 10000 };
        for (int i = 0; i < presets.Length; i += 2)
        {
            var row = MakeHRow(_extrasRoot.transform, 12f, 90f);
            for (int j = 0; j < 2 && (i + j) < presets.Length; j++)
            {
                int amt = presets[i + j];
                var btn = MakeAmountBtn(row.transform, font, "+" + amt, amountBtnSprite);
                int captured = amt;
                btn.onClick.AddListener(() => { if (custom != null) custom.text = captured.ToString(); });
            }
            if (presets.Length - i == 1)
            {
                var sp = new GameObject("Sp", typeof(RectTransform), typeof(LayoutElement));
                sp.transform.SetParent(row.transform, false);
                sp.GetComponent<LayoutElement>().flexibleWidth = 1f;
            }
        }
    }

    private static GameObject MakeHRow(Transform parent, float spacing, float h)
    {
        var row = new GameObject("Row",
            typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
        row.transform.SetParent(parent, false);
        var le = row.GetComponent<LayoutElement>();
        le.minHeight = le.preferredHeight = h;
        var hl = row.GetComponent<HorizontalLayoutGroup>();
        hl.spacing = spacing;
        hl.childControlWidth = hl.childControlHeight = true;
        hl.childForceExpandWidth = hl.childForceExpandHeight = true;
        hl.childAlignment = TextAnchor.MiddleCenter;
        return row;
    }

    private static void SetFontSize(GameObject go, int size)
    {
        foreach (var t in go.GetComponentsInChildren<Text>(true))
            t.fontSize = size;
    }

    private static GameObject MakePayMethodBtn(Transform parent, Font font, string label, bool active, Sprite sprite = null)
    {
        var go = new GameObject(label + "Btn",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
            typeof(Button), typeof(LayoutElement));
        go.transform.SetParent(parent, false);

        var img = go.GetComponent<Image>();
        if (sprite != null) { img.sprite = sprite; img.type = Image.Type.Sliced; }
        img.color = active ? new Color32(218, 130, 20, 255) : new Color32(80, 12, 22, 255);

        var le = go.GetComponent<LayoutElement>();
        le.preferredHeight = 80f;
        le.minHeight       = 80f;

        var lbl = new GameObject("Lbl",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        lbl.transform.SetParent(go.transform, false);
        var t = lbl.GetComponent<Text>();
        t.font               = font;
        t.text               = label;
        t.fontSize           = 32;
        t.fontStyle          = FontStyle.Bold;
        t.color              = Color.white;
        t.alignment          = TextAnchor.MiddleCenter;
        t.horizontalOverflow = HorizontalWrapMode.Overflow;   // ← stops vertical stacking
        t.verticalOverflow   = VerticalWrapMode.Overflow;
        t.raycastTarget      = false;
        var lr = lbl.GetComponent<RectTransform>();
        lr.anchorMin = Vector2.zero; lr.anchorMax = Vector2.one;
        lr.offsetMin = new Vector2(8, 0); lr.offsetMax = new Vector2(-8, 0);

        var btn = go.GetComponent<Button>();
        btn.targetGraphic = img;
        var cb = btn.colors;
        cb.highlightedColor = new Color32(255, 170, 40, 255);
        cb.pressedColor     = new Color32(60, 8, 16, 255);
        btn.colors = cb;
        return go;
    }

    private static void SetPayMethodStyle(GameObject tab, bool active)
    {
        if (tab == null) return;
        var img = tab.GetComponent<Image>();
        if (img != null) img.color = active ? new Color32(218, 130, 20, 255) : new Color32(80, 12, 22, 255);
        var t = tab.GetComponentInChildren<Text>();
        if (t != null)
        {
            string name = t.text.Contains("UPI") ? "UPI / Bank" : "Crypto";
            t.text = (active ? "● " : "○ ") + name;
        }
    }

    private static Button MakeAmountBtn(Transform parent, Font font, string label, Sprite sprite = null)
    {
        var go = new GameObject(label + "Btn",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
            typeof(Button), typeof(LayoutElement));
        go.transform.SetParent(parent, false);
        var le = go.GetComponent<LayoutElement>();
        le.preferredHeight = 90f;
        le.minHeight       = 90f;
        var imgAmt = go.GetComponent<Image>();
        if (sprite != null) { imgAmt.sprite = sprite; imgAmt.type = Image.Type.Sliced; }
        imgAmt.color = new Color32(118, 18, 28, 255);

        var btn = go.GetComponent<Button>();
        btn.targetGraphic = go.GetComponent<Image>();
        var cb = btn.colors;
        cb.highlightedColor = new Color32(180, 40, 55, 255);
        cb.pressedColor     = new Color32(60, 8, 16, 255);
        btn.colors = cb;

        var lbl = new GameObject("Lbl",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        lbl.transform.SetParent(go.transform, false);
        var t = lbl.GetComponent<Text>();
        t.font               = font;
        t.text               = label;
        t.fontSize           = 32;
        t.fontStyle          = FontStyle.Bold;
        t.color              = new Color32(255, 210, 70, 255);
        t.alignment          = TextAnchor.MiddleCenter;
        t.horizontalOverflow = HorizontalWrapMode.Overflow;
        t.verticalOverflow   = VerticalWrapMode.Overflow;
        t.raycastTarget      = false;
        var lr = lbl.GetComponent<RectTransform>();
        lr.anchorMin = Vector2.zero; lr.anchorMax = Vector2.one;
        lr.offsetMin = lr.offsetMax = Vector2.zero;

        return btn;
    }

    private static Text MakeLabel(Transform parent, Font font, string text, int size)
    {
        var go = new GameObject("Lbl",
            typeof(RectTransform), typeof(CanvasRenderer), typeof(Text), typeof(LayoutElement));
        go.transform.SetParent(parent, false);
        var le = go.GetComponent<LayoutElement>();
        le.preferredHeight = size + 18f;
        le.flexibleWidth   = 1f;
        var t = go.GetComponent<Text>();
        t.font               = font;
        t.text               = text;
        t.fontSize           = size;
        t.color              = new Color32(210, 180, 185, 200);
        t.alignment          = TextAnchor.MiddleLeft;
        t.horizontalOverflow = HorizontalWrapMode.Overflow;
        t.verticalOverflow   = VerticalWrapMode.Overflow;
        t.raycastTarget      = false;
        return t;
    }

    // ── Tab styling ────────────────────────────────────────────────────────────
    private static readonly Color32 TabActive   = new Color32(218, 130,  20, 255);
    private static readonly Color32 TabInactive = new Color32( 90,  14,  24, 255);
    private static readonly Color32 TabTextOn   = new Color32(255, 255, 255, 255);
    private static readonly Color32 TabTextOff  = new Color32(255, 200, 160, 220);

    private static void StyleTab(GameObject tab, bool active)
    {
        if (tab == null) return;
        var img = tab.GetComponent<Image>();
        if (img != null) img.color = active ? TabActive : TabInactive;
        foreach (var t in tab.GetComponentsInChildren<Text>(true))
            t.color = active ? TabTextOn : TabTextOff;
        foreach (var t in tab.GetComponentsInChildren<TMPro.TMP_Text>(true))
            t.color = active ? TabTextOn : TabTextOff;
    }

    public void DefaultSet()
    {
        if (manual_ss_img != null)
        {
            manual_ss_img.sprite = UploadScreenshort;
        }

        if (utr_inputfield != null)
        {
            utr_inputfield.text = "";
        }
        if (manual_ss_logo != null)
        {
            manual_ss_logo.SetActive(true);
        }
        if (custom != null)
        {
            custom.text = "";
        }

        ShowMainAddCashPanel();
    }

    #region Add Chips

    public void ClickAddCashButton()
    {
        ShowMainAddCashPanel();
    }

    private void ShowMainAddCashPanel()
    {
        transform.SetAsLastSibling();
        HidePaymentFlowPanels();

        RectTransform automatic = FindDirectChild(transform, "Automatic");
        if (automatic != null)
            automatic.gameObject.SetActive(true);

        foreach (var obj in transaction_panel_obj)
        {
            if (obj != null)
            {
                obj.SetActive(false);
            }
        }
        foreach (var obj in add_cash_panel_obj)
        {
            if (obj != null)
            {
                obj.SetActive(true);
            }
        }

        if (selectedAddcash != null)
            selectedAddcash.SetActive(true);
        if (selectedRecentTransaction != null)
            selectedRecentTransaction.SetActive(false);

        StyleTab(selectedAddcash, active: true);
        StyleTab(selectedRecentTransaction, active: false);

        // if (finalpanel != null)
        //     finalpanel.SetActive(false);

        ApplyResponsiveAddCashLayout();
        HideTransferEntryPoint();
        BindPresetAmountButtons();
    }

    private void HidePaymentFlowPanels()
    {
        if (dialogue != null)
            dialogue.SetActive(false);
        if (manual_panel != null)
            manual_panel.SetActive(false);
        if (_transferPopupRoot != null)
            _transferPopupRoot.SetActive(false);
        if (_transferConfirmPopupRoot != null)
            _transferConfirmPopupRoot.SetActive(false);

        GameObject usdtAuto = ResolveUsdtAutoPanel();
        if (usdtAuto != null)
            usdtAuto.SetActive(false);

        RectTransform usdtManual = FindDirectChild(transform, "USDT-Manual");
        if (usdtManual != null)
            usdtManual.gameObject.SetActive(false);
    }

    private GameObject ResolveUsdtAutoPanel()
    {
        RectTransform directPanel = FindDirectChild(transform, "USDT Auto");
        if (directPanel != null)
            return directPanel.gameObject;

        return USDT_AUTO;
    }

    private void HideTransferEntryPoint()
    {
        if (_transferQuickButton != null)
            _transferQuickButton.SetActive(false);
    }

    private async void ShowPaymentOptionsPanel()
    {
        if (dialogue == null) return;

        CommonUtil.CheckLog("PAY_TRACE ShowPaymentOptionsPanel");

        // Fetch payment options visibility config (cached after first load)
        if (_paymentOptionsConfig == null)
        {
            try
            {
                var cfg = await APIManager.Instance.Post<PaymentOptionsConfig>(
                    Configuration.paymentOptionsConfig,
                    new System.Collections.Generic.Dictionary<string, string>
                    {
                        { "user_id", Configuration.GetId() },
                        { "token",   Configuration.GetToken() }
                    });
                if (cfg != null && cfg.code == 200)
                    _paymentOptionsConfig = cfg;
            }
            catch { /* ignore — fall back to showing all options */ }
        }

        // Bind (show/hide) BEFORE layout so the layout counts active buttons correctly
        BindPaymentOptionDialogButtons(_paymentOptionsConfig);
        HidePaymentFlowPanels();
        dialogue.SetActive(true);
        dialogue.transform.SetAsLastSibling();
        transform.SetAsLastSibling();
        ApplyResponsiveAddCashLayout(); // repositions only active buttons
    }

    private void BindPaymentOptionDialogButtons(PaymentOptionsConfig cfg = null)
    {
        if (dialogue == null)
            return;

        Transform tableBg = FindChildRecursive(dialogue.transform, "Table-Bg");
        if (tableBg == null)
            return;

        List<Button> optionButtons = new List<Button>();
        for (int index = 0; index < tableBg.childCount; index++)
        {
            Button button = tableBg.GetChild(index).GetComponent<Button>();
            if (button == null)
                continue;

            optionButtons.Add(button);
        }

        if (optionButtons.Count < 4)
        {
            CommonUtil.CheckLog("PAY_TRACE BindPaymentOptionDialogButtons skipped: found " + optionButtons.Count + " candidate buttons");
            return;
        }

        // Determine which options are enabled (default all on if no config)
        bool[] enabled = {
            cfg == null || cfg.option_1_enabled,
            cfg == null || cfg.option_2_enabled,
            cfg == null || cfg.option_3_enabled,
            cfg == null || cfg.option_4_enabled,
        };

        System.Action[] actions = { OpenManual, OpenAutomaticGatewayFromOption, OpenUsdtManualOption, OpenUsdtAutoOption };
        string[] labels = { "UPI / Bank (Option 1)", "UPI / Bank (Option 2)", "USDT Manual", "BEP20 USDT" };

        // Hide disabled buttons; stack visible ones top-to-bottom with 115px gap
        float startY = 148f;
        float gap    = 115f;
        int visible  = 0;
        for (int i = 0; i < 4; i++)
        {
            optionButtons[i].gameObject.SetActive(enabled[i]);
            if (!enabled[i]) continue;

            RectTransform rect = optionButtons[i].transform as RectTransform;
            if (rect != null)
            {
                rect.anchoredPosition = new Vector2(0f, startY - visible * gap);
                rect.sizeDelta = new Vector2(700f, 104f);
            }
            ConfigurePaymentOptionButton(optionButtons[i], labels[i], actions[i]);
            visible++;
        }

        CommonUtil.CheckLog("PAY_TRACE BindPaymentOptionDialogButtons visible=" + visible);
    }

    private void ConfigurePaymentOptionButton(Button button, string label, Action onClick)
    {
        if (button == null || onClick == null)
            return;

        SetPaymentOptionButtonLabel(button, label);
        button.onClick.RemoveAllListeners();
        button.onClick.AddListener(() =>
        {
            CommonUtil.CheckLog("PAY_TRACE Option button clicked: " + label);
            if (dialogue != null)
                dialogue.SetActive(false);

            onClick();
        });
    }

    private void SetPaymentOptionButtonLabel(Button button, string label)
    {
        if (button == null)
            return;

        TMP_Text text = button.GetComponentInChildren<TMP_Text>(true);
        if (text != null)
            text.text = label;
    }

    private void EnsureTransferQuickButton(
        RectTransform details,
        float rowWidth,
        float inputWidth,
        float addButtonWidth,
        float inputTop,
        bool portrait
    )
    {
        if (details == null)
            return;

        if (_transferQuickButton == null)
        {
            _transferQuickButton = new GameObject(
                "Transfer-Button",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image),
                typeof(Button)
            );
            _transferQuickButton.transform.SetParent(details, false);

            Image image = _transferQuickButton.GetComponent<Image>();
            Sprite sprite = ResolveButtonSprite();
            if (sprite != null)
            {
                image.sprite = sprite;
                image.type = Image.Type.Sliced;
            }
            image.color = new Color32(118, 18, 28, 255);

            Button button = _transferQuickButton.GetComponent<Button>();
            ColorBlock colors = button.colors;
            colors.highlightedColor = new Color32(180, 40, 55, 255);
            colors.pressedColor = new Color32(60, 8, 16, 255);
            button.colors = colors;
            button.onClick.AddListener(OpenTransferPopup);

            GameObject label = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            label.transform.SetParent(_transferQuickButton.transform, false);
            Text text = label.GetComponent<Text>();
            text.font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            text.text = "Transfer";
            text.fontStyle = FontStyle.Bold;
            text.alignment = TextAnchor.MiddleCenter;
            text.color = Color.white;
            text.horizontalOverflow = HorizontalWrapMode.Overflow;
            text.verticalOverflow = VerticalWrapMode.Overflow;
            text.raycastTarget = false;
            RectTransform labelRect = label.GetComponent<RectTransform>();
            labelRect.anchorMin = Vector2.zero;
            labelRect.anchorMax = Vector2.one;
            labelRect.offsetMin = Vector2.zero;
            labelRect.offsetMax = Vector2.zero;
        }

        _transferQuickButton.transform.SetAsLastSibling();

        RectTransform transferRect = _transferQuickButton.transform as RectTransform;
        SetTopLeft(
            transferRect,
            new Vector2(addButtonWidth, portrait ? 66f : 70f),
            new Vector2(inputWidth + 12f, inputTop - (portrait ? 88f : 94f))
        );
        ConfigureAnyText(transferRect, portrait ? 24f : 28f, Color.white, true);
    }

    public void OpenTransferPopup()
    {
        EnsureTransferPopup();

        if (_transferPopupRoot == null)
            return;

        EnsureTransferPopupVisibleParent();
        HidePaymentFlowPanels();
        _transferPopupRoot.SetActive(true);
        _transferPopupRoot.transform.SetAsLastSibling();
        Transform popupParent = _transferPopupRoot.transform.parent;
        if (popupParent != null)
            popupParent.SetAsLastSibling();

        ResetTransferForm();
    }

    private void CloseTransferPopup()
    {
        if (_transferConfirmPopupRoot != null)
            _transferConfirmPopupRoot.SetActive(false);
        if (_transferPopupRoot != null)
            _transferPopupRoot.SetActive(false);
    }

    private void EnsureTransferPopup()
    {
        if (_transferPopupRoot != null)
        {
            EnsureTransferPopupVisibleParent();
            return;
        }

        Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        Sprite popupSprite = ResolvePopupSprite();
        Transform popupParent = FindPreferredPopupParent();

        _transferPopupRoot = new GameObject(
            "Transfer-Popup",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        _transferPopupRoot.transform.SetParent(popupParent, false);
        RectTransform rootRect = _transferPopupRoot.transform as RectTransform;
        rootRect.anchorMin = Vector2.zero;
        rootRect.anchorMax = Vector2.one;
        rootRect.offsetMin = Vector2.zero;
        rootRect.offsetMax = Vector2.zero;
        Image overlayImage = _transferPopupRoot.GetComponent<Image>();
        overlayImage.color = new Color(0f, 0f, 0f, 0.72f);

        GameObject panel = new GameObject(
            "Panel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        panel.transform.SetParent(_transferPopupRoot.transform, false);
        RectTransform panelRect = panel.transform as RectTransform;
        panelRect.anchorMin = new Vector2(0.5f, 0.5f);
        panelRect.anchorMax = new Vector2(0.5f, 0.5f);
        panelRect.pivot = new Vector2(0.5f, 0.5f);
        panelRect.sizeDelta = new Vector2(760f, 920f);
        Image panelImage = panel.GetComponent<Image>();
        if (popupSprite != null)
        {
            panelImage.sprite = popupSprite;
            panelImage.type = Image.Type.Sliced;
        }
        panelImage.color = popupSprite != null ? Color.white : new Color32(88, 16, 22, 255);

        VerticalLayoutGroup layout = panel.AddComponent<VerticalLayoutGroup>();
        layout.padding = new RectOffset(34, 34, 28, 28);
        layout.spacing = 16f;
        layout.childControlWidth = true;
        layout.childControlHeight = false;
        layout.childForceExpandWidth = true;
        layout.childForceExpandHeight = false;

        LayoutElement panelLayout = panel.AddComponent<LayoutElement>();
        panelLayout.preferredWidth = 760f;
        panelLayout.preferredHeight = 920f;

        GameObject header = CreateRuntimeRow(panel.transform, 8f, 80f);
        CreateRuntimeLabel(header.transform, font, "Wallet Transfer", 34, Color.white, TextAnchor.MiddleLeft, FontStyle.Bold);
        Button closeButton = CreateRuntimeButton(header.transform, font, "X", 80f, 80f, new Color32(90, 12, 22, 255), Color.white, popupSprite);
        closeButton.onClick.AddListener(CloseTransferPopup);

        CreateRuntimeLabel(
            panel.transform,
            font,
            "Enter player ID, mobile, email or username.",
            30,
            new Color32(255, 228, 190, 255),
            TextAnchor.MiddleLeft
        );

        GameObject lookupRow = CreateRuntimeRow(panel.transform, 12f, 84f);
        _transferQueryInput = CreateRuntimeInputField(
            lookupRow.transform,
            font,
            "Player ID / Mobile / Email / Username",
            520f,
            84f
        );
        _transferQueryInput.onValueChanged.AddListener(_ => ClearTransferSelection(false));
        Button lookupButton = CreateRuntimeButton(
            lookupRow.transform,
            font,
            "Find",
            160f,
            84f,
            new Color32(118, 18, 28, 255),
            Color.white,
            ResolveButtonSprite()
        );
        lookupButton.onClick.AddListener(() => _ = LookupTransferPlayer());

        _transferLookupSummaryText = CreateRuntimeLabel(
            panel.transform,
            font,
            "Player details will appear here.",
            28,
            new Color32(245, 225, 195, 255),
            TextAnchor.UpperLeft
        );
        LayoutElement summaryLayout = _transferLookupSummaryText.gameObject.AddComponent<LayoutElement>();
        summaryLayout.preferredHeight = 150f;
        summaryLayout.minHeight = 150f;
        _transferLookupSummaryText.horizontalOverflow = HorizontalWrapMode.Wrap;
        _transferLookupSummaryText.verticalOverflow = VerticalWrapMode.Overflow;

        GameObject amountSpacer = new GameObject("Transfer-Amount-Spacer", typeof(RectTransform), typeof(LayoutElement));
        amountSpacer.transform.SetParent(panel.transform, false);
        LayoutElement amountSpacerLayout = amountSpacer.GetComponent<LayoutElement>();
        amountSpacerLayout.preferredHeight = 36f;
        amountSpacerLayout.minHeight = 36f;

        GameObject amountRow = CreateRuntimeRow(panel.transform, 12f, 84f);
        _transferAmountInput = CreateRuntimeInputField(
            amountRow.transform,
            font,
            "Enter amount",
            520f,
            84f,
            InputField.ContentType.DecimalNumber
        );
        Button transferButton = CreateRuntimeButton(
            amountRow.transform,
            font,
            "Transfer",
            160f,
            84f,
            new Color32(28, 128, 42, 255),
            Color.white,
            ResolveButtonSprite()
        );
        transferButton.onClick.AddListener(OpenTransferConfirmPopup);

        _transferHistorySectionRoot = new GameObject(
            "Transfer-History-Section",
            typeof(RectTransform),
            typeof(VerticalLayoutGroup),
            typeof(LayoutElement)
        );
        _transferHistorySectionRoot.transform.SetParent(panel.transform, false);
        VerticalLayoutGroup historyLayout = _transferHistorySectionRoot.GetComponent<VerticalLayoutGroup>();
        historyLayout.spacing = 12f;
        historyLayout.childControlWidth = true;
        historyLayout.childControlHeight = false;
        historyLayout.childForceExpandWidth = true;
        historyLayout.childForceExpandHeight = false;
        LayoutElement historyRootLayout = _transferHistorySectionRoot.GetComponent<LayoutElement>();
        historyRootLayout.preferredHeight = 0f;
        historyRootLayout.minHeight = 0f;
        historyRootLayout.flexibleHeight = 0f;

        CreateRuntimeLabel(
            _transferHistorySectionRoot.transform,
            font,
            "Recent Transfers",
            32,
            new Color32(255, 190, 60, 255),
            TextAnchor.MiddleLeft,
            FontStyle.Bold
        );

        ScrollRect historyScroll = CreateRuntimeScroll(_transferHistorySectionRoot.transform, out _transferHistoryContent, out _transferHistoryEmptyText, font);
        LayoutElement scrollLayout = historyScroll.gameObject.AddComponent<LayoutElement>();
        scrollLayout.preferredHeight = 360f;
        scrollLayout.flexibleHeight = 1f;
        scrollLayout.minHeight = 280f;
        _transferHistorySectionRoot.SetActive(false);

        EnsureTransferConfirmPopup(font, popupSprite, popupParent);
        _transferPopupRoot.SetActive(false);
    }

    private void EnsureTransferConfirmPopup(Font font, Sprite popupSprite, Transform popupParent)
    {
        if (_transferConfirmPopupRoot != null)
            return;

        _transferConfirmPopupRoot = new GameObject(
            "Transfer-Confirm-Popup",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        _transferConfirmPopupRoot.transform.SetParent(popupParent, false);
        RectTransform rootRect = _transferConfirmPopupRoot.transform as RectTransform;
        rootRect.anchorMin = Vector2.zero;
        rootRect.anchorMax = Vector2.one;
        rootRect.offsetMin = Vector2.zero;
        rootRect.offsetMax = Vector2.zero;
        _transferConfirmPopupRoot.GetComponent<Image>().color = new Color(0f, 0f, 0f, 0.76f);

        GameObject panel = new GameObject(
            "Panel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        panel.transform.SetParent(_transferConfirmPopupRoot.transform, false);
        RectTransform panelRect = panel.transform as RectTransform;
        panelRect.anchorMin = new Vector2(0.5f, 0.5f);
        panelRect.anchorMax = new Vector2(0.5f, 0.5f);
        panelRect.pivot = new Vector2(0.5f, 0.5f);
        panelRect.sizeDelta = new Vector2(800f, 560f);
        Image image = panel.GetComponent<Image>();
        if (popupSprite != null)
        {
            image.sprite = popupSprite;
            image.type = Image.Type.Sliced;
        }
        image.color = popupSprite != null ? Color.white : new Color32(88, 16, 22, 255);

        VerticalLayoutGroup layout = panel.AddComponent<VerticalLayoutGroup>();
        layout.padding = new RectOffset(34, 34, 34, 34);
        layout.spacing = 20f;
        layout.childControlWidth = true;
        layout.childControlHeight = false;
        layout.childForceExpandWidth = true;
        layout.childForceExpandHeight = false;

        CreateRuntimeLabel(panel.transform, font, "Confirm Transfer", 36, Color.white, TextAnchor.MiddleCenter, FontStyle.Bold);
        Text body = CreateRuntimeLabel(
            panel.transform,
            font,
            "",
            28,
            new Color32(235, 210, 180, 255),
            TextAnchor.UpperLeft
        );
        body.name = "Confirm-Body";
        body.horizontalOverflow = HorizontalWrapMode.Wrap;
        body.verticalOverflow = VerticalWrapMode.Overflow;
        LayoutElement bodyLayout = body.gameObject.AddComponent<LayoutElement>();
        bodyLayout.preferredHeight = 300f;
        bodyLayout.minHeight = 300f;

        GameObject spacer = new GameObject("Spacer", typeof(RectTransform), typeof(LayoutElement));
        spacer.transform.SetParent(panel.transform, false);
        LayoutElement spacerLayout = spacer.GetComponent<LayoutElement>();
        spacerLayout.preferredHeight = 18f;
        spacerLayout.minHeight = 18f;

        GameObject actions = CreateRuntimeRow(panel.transform, 20f, 92f);
        Button cancelButton = CreateRuntimeButton(actions.transform, font, "Cancel", 290f, 92f, new Color32(90, 12, 22, 255), Color.white, ResolveButtonSprite());
        cancelButton.onClick.AddListener(() =>
        {
            if (_transferConfirmPopupRoot != null)
                _transferConfirmPopupRoot.SetActive(false);
        });
        Button confirmButton = CreateRuntimeButton(actions.transform, font, "Confirm", 290f, 92f, new Color32(28, 128, 42, 255), Color.white, ResolveButtonSprite());
        confirmButton.onClick.AddListener(() => _ = SubmitTransfer());

        _transferConfirmPopupRoot.SetActive(false);
    }

    private void EnsureTransferPopupVisibleParent()
    {
        if (_transferPopupRoot == null)
            return;

        Transform preferredParent = FindPreferredPopupParent();
        if (preferredParent == null)
            return;

        if (_transferPopupRoot.transform.parent != preferredParent)
        {
            _transferPopupRoot.transform.SetParent(preferredParent, false);
            RectTransform rootRect = _transferPopupRoot.transform as RectTransform;
            if (rootRect != null)
            {
                rootRect.anchorMin = Vector2.zero;
                rootRect.anchorMax = Vector2.one;
                rootRect.offsetMin = Vector2.zero;
                rootRect.offsetMax = Vector2.zero;
            }

            if (_transferConfirmPopupRoot != null)
                _transferConfirmPopupRoot.transform.SetParent(_transferPopupRoot.transform, false);

            CommonUtil.CheckLog("PAY_TRACE Transfer popup reparented to visible host: " + preferredParent.name);
        }
    }

    private void ResetTransferForm()
    {
        if (_transferQueryInput != null)
            _transferQueryInput.text = string.Empty;
        if (_transferAmountInput != null)
            _transferAmountInput.text = string.Empty;

        ClearTransferSelection(true);
    }

    private void ClearTransferSelection(bool resetMessage)
    {
        _selectedTransferPlayer = null;

        if (_transferLookupSummaryText == null)
            return;

        if (resetMessage)
        {
            _transferLookupSummaryText.text = "Player details will appear here.";
        }
        else
        {
            _transferLookupSummaryText.text = "Player changed. Tap Find again to confirm the receiver.";
        }
    }

    private async Task LookupTransferPlayer()
    {
        if (_isTransferLookupRunning)
            return;

        string query = _transferQueryInput != null ? _transferQueryInput.text.Trim() : string.Empty;
        if (string.IsNullOrWhiteSpace(query))
        {
            SafeShowToast("Please enter player ID, mobile, email or username.");
            return;
        }

        _isTransferLookupRunning = true;
        CommonUtil.CheckLog("PAY_TRACE TransferLookup request query=" + query);

        try
        {
            var formData = new Dictionary<string, string>
            {
                { "user_id", Configuration.GetId() },
                { "token", Configuration.GetToken() },
                { "query", query },
            };

            TransferLookupResponse response = await APIManager.Instance.Post<TransferLookupResponse>(Configuration.WalletTransferLookup, formData);
            if (response == null)
            {
                _selectedTransferPlayer = null;
                if (_transferLookupSummaryText != null)
                    _transferLookupSummaryText.text = "Player lookup failed. Please try again.";
                SafeShowToast("Player lookup failed. Please try again.");
                return;
            }
            CommonUtil.CheckLog("PAY_TRACE TransferLookup response code=" + response.code + " message=" + (response.message ?? string.Empty));

            if (response.code == 200 && response.player != null)
            {
                _selectedTransferPlayer = response.player;
                if (_transferLookupSummaryText != null)
                {
                    _transferLookupSummaryText.text =
                        "Player ID: " + SafeTransferValue(response.player.user_id) + "\n"
                        + "Username: " + SafeTransferValue(response.player.username) + "\n"
                        + "Name: " + SafeTransferValue(response.player.name) + "\n"
                        + "Mobile: " + SafeTransferValue(response.player.mobile) + "\n"
                        + "Email: " + SafeTransferValue(response.player.email);
                }
            }
            else
            {
                _selectedTransferPlayer = null;
                if (_transferLookupSummaryText != null)
                    _transferLookupSummaryText.text = GetFriendlyTransferApiMessage(response.message, "Player not found or server unavailable.");
                SafeShowToast(GetFriendlyTransferApiMessage(response.message, "Player not found or server unavailable."));
            }
        }
        catch (Exception exception)
        {
            Debug.LogError("Transfer lookup failed: " + exception.Message);
            _selectedTransferPlayer = null;
            if (_transferLookupSummaryText != null)
                _transferLookupSummaryText.text = "Server is offline. Please try again shortly.";
            SafeShowToast("Server is offline. Please try again shortly.");
        }
        finally
        {
            _isTransferLookupRunning = false;
        }
    }

    private void OpenTransferConfirmPopup()
    {
        if (_selectedTransferPlayer == null)
        {
            SafeShowToast("Find the receiver first.");
            return;
        }

        if (!TryGetTransferAmount(out float amount))
        {
            SafeShowToast("Please enter a valid transfer amount.");
            return;
        }

        EnsureTransferPopup();
        if (_transferConfirmPopupRoot == null)
            return;

        Text body = FindDeepChild(_transferConfirmPopupRoot.transform, "Confirm-Body")?.GetComponent<Text>();
        if (body != null)
        {
            body.text =
                "You are sending " + amount.ToString("0.##", CultureInfo.InvariantCulture) + " chips to:\n\n"
                + "Player ID: " + SafeTransferValue(_selectedTransferPlayer.user_id) + "\n"
                + "Username: " + SafeTransferValue(_selectedTransferPlayer.username) + "\n"
                + "Name: " + SafeTransferValue(_selectedTransferPlayer.name);
        }

        _transferConfirmPopupRoot.SetActive(true);
        _transferConfirmPopupRoot.transform.SetAsLastSibling();
    }

    private async Task SubmitTransfer()
    {
        if (_isTransferSubmitRunning)
            return;

        if (_selectedTransferPlayer == null)
        {
            SafeShowToast("Find the receiver first.");
            return;
        }

        if (!TryGetTransferAmount(out float amount))
        {
            SafeShowToast("Please enter a valid transfer amount.");
            return;
        }

        _isTransferSubmitRunning = true;
        CommonUtil.CheckLog("PAY_TRACE TransferWallet request receiver=" + SafeTransferValue(_selectedTransferPlayer.user_id) + " amount=" + amount.ToString("0.##", CultureInfo.InvariantCulture));

        try
        {
            var formData = new Dictionary<string, string>
            {
                { "user_id", Configuration.GetId() },
                { "token", Configuration.GetToken() },
                { "receiver_user_id", _selectedTransferPlayer.user_id ?? string.Empty },
                { "amount", amount.ToString("0.##", CultureInfo.InvariantCulture) },
            };

            TransferWalletResponse response = await APIManager.Instance.Post<TransferWalletResponse>(Configuration.WalletTransfer, formData);
            if (response == null)
            {
                SafeShowToast("Transfer failed. Please try again.");
                return;
            }
            CommonUtil.CheckLog("PAY_TRACE TransferWallet response code=" + response.code + " message=" + (response.message ?? string.Empty));
            SafeShowToast(GetFriendlyTransferApiMessage(response.message, "Transfer failed. Please try again."));

            if (response.code == 200)
            {
                if (!string.IsNullOrWhiteSpace(response.wallet))
                {
                    PlayerPrefs.SetString("wallet", response.wallet);
                    PlayerPrefs.Save();
                }

                if (_transferConfirmPopupRoot != null)
                    _transferConfirmPopupRoot.SetActive(false);

                ResetTransferForm();
                await LoadTransferHistory();
                Configuration.GetProfileWallet();
            }
        }
        catch (Exception exception)
        {
            Debug.LogError("Transfer submit failed: " + exception.Message);
            SafeShowToast("Server is offline. Transfer could not be completed.");
        }
        finally
        {
            _isTransferSubmitRunning = false;
        }
    }

    private async Task LoadTransferHistory()
    {
        if (_transferHistoryContent == null)
            return;

        foreach (Transform child in _transferHistoryContent)
            RemoveGeneratedObject(child.gameObject);

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        TransferHistoryResponse response = await APIManager.Instance.Post<TransferHistoryResponse>(Configuration.WalletTransferHistory, formData);
        if (response == null)
        {
            if (_transferHistoryEmptyText != null)
            {
                _transferHistoryEmptyText.gameObject.SetActive(true);
                _transferHistoryEmptyText.text = "Transfer history could not be loaded.";
            }
            return;
        }
        CommonUtil.CheckLog("PAY_TRACE TransferHistory response code=" + response.code + " count=" + (response.transfer_history == null ? 0 : response.transfer_history.Length));

        if (response.code == 0)
        {
            if (_transferHistoryEmptyText != null)
            {
                _transferHistoryEmptyText.gameObject.SetActive(true);
                _transferHistoryEmptyText.text = "Server is offline. Transfer history is unavailable.";
            }
            return;
        }

        TransferHistoryEntry[] history = response.transfer_history ?? Array.Empty<TransferHistoryEntry>();
        if (_transferHistoryEmptyText != null)
        {
            _transferHistoryEmptyText.gameObject.SetActive(history.Length == 0);
            _transferHistoryEmptyText.text = history.Length == 0 ? "No transfer records found yet." : string.Empty;
        }

        if (response.code != 200 || history.Length == 0)
            return;

        Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        for (int index = 0; index < history.Length; index++)
        {
            CreateTransferHistoryRow(_transferHistoryContent, font, history[index]);
        }

        LayoutRebuilder.ForceRebuildLayoutImmediate(_transferHistoryContent);
        if (_transferHistoryContent.parent is RectTransform viewportRect)
        {
            LayoutRebuilder.ForceRebuildLayoutImmediate(viewportRect);
        }
        Canvas.ForceUpdateCanvases();
    }

    private static string GetFriendlyTransferApiMessage(string rawMessage, string fallback)
    {
        string message = string.IsNullOrWhiteSpace(rawMessage) ? string.Empty : rawMessage.Trim();
        if (string.IsNullOrEmpty(message))
            return fallback;

        string normalized = message.ToLowerInvariant();
        if (normalized.Contains("actively refused")
            || normalized.Contains("connection could not be made")
            || normalized.Contains("connection refused")
            || normalized.Contains("target machine")
            || normalized.Contains("request finished with error"))
        {
            return "Server is offline. Please try again shortly.";
        }

        return message;
    }

    private void CreateTransferHistoryRow(Transform parent, Font font, TransferHistoryEntry entry)
    {
        GameObject card = new GameObject(
            "Transfer-History-Row",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(LayoutElement),
            typeof(VerticalLayoutGroup)
        );
        card.transform.SetParent(parent, false);

        Image image = card.GetComponent<Image>();
        Sprite sprite = popupBgSprite ?? ResolveButtonSprite();
        if (sprite != null)
        {
            image.sprite = sprite;
            image.type = Image.Type.Sliced;
        }
        image.color = new Color32(78, 12, 18, 255);

        LayoutElement layout = card.GetComponent<LayoutElement>();
        layout.preferredHeight = 112f;
        layout.minHeight = 112f;
        layout.flexibleWidth = 1f;

        VerticalLayoutGroup group = card.GetComponent<VerticalLayoutGroup>();
        group.padding = new RectOffset(18, 18, 12, 12);
        group.spacing = 4f;
        group.childControlWidth = true;
        group.childControlHeight = false;
        group.childForceExpandWidth = true;
        group.childForceExpandHeight = false;

        RectTransform cardRect = card.GetComponent<RectTransform>();
        cardRect.localScale = Vector3.one;
        cardRect.anchorMin = new Vector2(0f, 1f);
        cardRect.anchorMax = new Vector2(1f, 1f);
        cardRect.pivot = new Vector2(0.5f, 1f);
        cardRect.sizeDelta = new Vector2(0f, 112f);

        string direction = (entry.direction ?? string.Empty).Equals("received", StringComparison.OrdinalIgnoreCase) ? "Received" : "Sent";
        string amount = SafeTransferValue(entry.amount) + " " + SafeTransferValue(entry.currency);
        string partner = SafeTransferValue(entry.username) + " (" + SafeTransferValue(entry.user_id) + ")";
        string meta = SafeTransferValue(entry.status) + "  |  " + SafeTransferValue(entry.added_date);

        CreateRuntimeLabel(card.transform, font, direction + "  " + amount, 24, Color.white, TextAnchor.MiddleLeft, FontStyle.Bold);
        CreateRuntimeLabel(card.transform, font, partner, 22, new Color32(255, 210, 170, 255), TextAnchor.MiddleLeft);
        CreateRuntimeLabel(card.transform, font, meta, 19, new Color32(220, 180, 180, 255), TextAnchor.MiddleLeft);
    }

    private bool TryGetTransferAmount(out float amount)
    {
        amount = 0f;

        string raw = _transferAmountInput != null ? _transferAmountInput.text.Trim() : string.Empty;
        if (string.IsNullOrWhiteSpace(raw))
            return false;

        return float.TryParse(raw, NumberStyles.Float, CultureInfo.InvariantCulture, out amount) && amount > 0f;
    }

    private static string SafeTransferValue(string value)
    {
        return string.IsNullOrWhiteSpace(value) ? "-" : value.Trim();
    }

    private static GameObject CreateRuntimeRow(Transform parent, float spacing, float height)
    {
        GameObject row = new GameObject(
            "Row",
            typeof(RectTransform),
            typeof(HorizontalLayoutGroup),
            typeof(LayoutElement)
        );
        row.transform.SetParent(parent, false);

        HorizontalLayoutGroup layout = row.GetComponent<HorizontalLayoutGroup>();
        layout.spacing = spacing;
        layout.childControlWidth = true;
        layout.childControlHeight = true;
        layout.childForceExpandWidth = false;
        layout.childForceExpandHeight = false;
        layout.childAlignment = TextAnchor.MiddleCenter;

        LayoutElement element = row.GetComponent<LayoutElement>();
        element.preferredHeight = height;
        element.minHeight = height;

        return row;
    }

    private static Text CreateRuntimeLabel(
        Transform parent,
        Font font,
        string value,
        int fontSize,
        Color color,
        TextAnchor alignment,
        FontStyle fontStyle = FontStyle.Normal
    )
    {
        GameObject label = new GameObject(
            "Label",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Text),
            typeof(LayoutElement)
        );
        label.transform.SetParent(parent, false);

        Text text = label.GetComponent<Text>();
        text.font = font;
        text.text = value;
        text.fontSize = fontSize;
        text.fontStyle = fontStyle;
        text.color = color;
        text.alignment = alignment;
        text.resizeTextForBestFit = false;
        text.horizontalOverflow = HorizontalWrapMode.Wrap;
        text.verticalOverflow = VerticalWrapMode.Overflow;
        text.raycastTarget = false;

        LayoutElement element = label.GetComponent<LayoutElement>();
        element.flexibleWidth = 1f;
        element.minHeight = fontSize + 18f;
        element.preferredHeight = fontSize + 22f;

        return text;
    }

    private static Button CreateRuntimeButton(
        Transform parent,
        Font font,
        string label,
        float width,
        float height,
        Color color,
        Color textColor,
        Sprite sprite
    )
    {
        GameObject buttonObject = new GameObject(
            label + "-Button",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Button),
            typeof(LayoutElement)
        );
        buttonObject.transform.SetParent(parent, false);

        LayoutElement element = buttonObject.GetComponent<LayoutElement>();
        element.preferredWidth = width;
        element.minWidth = width;
        element.preferredHeight = height;
        element.minHeight = height;

        Image image = buttonObject.GetComponent<Image>();
        if (sprite != null)
        {
            image.sprite = sprite;
            image.type = Image.Type.Sliced;
        }
        image.color = color;

        Button button = buttonObject.GetComponent<Button>();
        button.targetGraphic = image;

        GameObject textObject = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        textObject.transform.SetParent(buttonObject.transform, false);
        Text text = textObject.GetComponent<Text>();
        text.font = font;
        text.text = label;
        text.fontSize = 30;
        text.fontStyle = FontStyle.Bold;
        text.alignment = TextAnchor.MiddleCenter;
        text.color = textColor;
        text.resizeTextForBestFit = false;
        text.raycastTarget = false;

        RectTransform textRect = textObject.GetComponent<RectTransform>();
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = Vector2.zero;
        textRect.offsetMax = Vector2.zero;

        return button;
    }

    private static InputField CreateRuntimeInputField(
        Transform parent,
        Font font,
        string placeholder,
        float width,
        float height,
        InputField.ContentType contentType = InputField.ContentType.Standard
    )
    {
        GameObject inputObject = new GameObject(
            "Input",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(InputField),
            typeof(LayoutElement)
        );
        inputObject.transform.SetParent(parent, false);

        LayoutElement element = inputObject.GetComponent<LayoutElement>();
        element.preferredWidth = width;
        element.minWidth = width;
        element.preferredHeight = height;
        element.minHeight = height;
        element.flexibleWidth = 1f;

        Image image = inputObject.GetComponent<Image>();
        image.color = new Color32(38, 18, 18, 240);

        GameObject placeholderObject = new GameObject("Placeholder", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        placeholderObject.transform.SetParent(inputObject.transform, false);
        Text placeholderText = placeholderObject.GetComponent<Text>();
        placeholderText.font = font;
        placeholderText.text = placeholder;
        placeholderText.fontSize = 28;
        placeholderText.fontStyle = FontStyle.Normal;
        placeholderText.color = new Color32(215, 195, 195, 255);
        placeholderText.alignment = TextAnchor.MiddleLeft;
        placeholderText.resizeTextForBestFit = false;
        placeholderText.raycastTarget = false;

        GameObject textObject = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        textObject.transform.SetParent(inputObject.transform, false);
        Text valueText = textObject.GetComponent<Text>();
        valueText.font = font;
        valueText.text = string.Empty;
        valueText.fontSize = 30;
        valueText.fontStyle = FontStyle.Bold;
        valueText.color = Color.white;
        valueText.alignment = TextAnchor.MiddleLeft;
        valueText.resizeTextForBestFit = false;
        valueText.raycastTarget = false;

        RectTransform placeholderRect = placeholderObject.GetComponent<RectTransform>();
        placeholderRect.anchorMin = Vector2.zero;
        placeholderRect.anchorMax = Vector2.one;
        placeholderRect.offsetMin = new Vector2(18f, 0f);
        placeholderRect.offsetMax = new Vector2(-18f, 0f);

        RectTransform textRect = textObject.GetComponent<RectTransform>();
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = new Vector2(18f, 0f);
        textRect.offsetMax = new Vector2(-18f, 0f);

        InputField input = inputObject.GetComponent<InputField>();
        input.textComponent = valueText;
        input.placeholder = placeholderText;
        input.contentType = contentType;
        input.lineType = InputField.LineType.SingleLine;

        return input;
    }

    private static ScrollRect CreateRuntimeScroll(
        Transform parent,
        out RectTransform content,
        out Text emptyText,
        Font font
    )
    {
        GameObject scrollObject = new GameObject(
            "History-Scroll",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(ScrollRect),
            typeof(LayoutElement)
        );
        scrollObject.transform.SetParent(parent, false);
        Image image = scrollObject.GetComponent<Image>();
        image.color = new Color32(28, 8, 12, 150);

        GameObject viewport = new GameObject(
            "Viewport",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Mask)
        );
        viewport.transform.SetParent(scrollObject.transform, false);
        RectTransform viewportRect = viewport.GetComponent<RectTransform>();
        viewportRect.anchorMin = Vector2.zero;
        viewportRect.anchorMax = Vector2.one;
        viewportRect.offsetMin = new Vector2(10f, 10f);
        viewportRect.offsetMax = new Vector2(-10f, -10f);
        Image viewportImage = viewport.GetComponent<Image>();
        viewportImage.color = new Color(0f, 0f, 0f, 0f);
        viewport.GetComponent<Mask>().showMaskGraphic = false;

        GameObject contentObject = new GameObject(
            "Content",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(VerticalLayoutGroup),
            typeof(ContentSizeFitter)
        );
        contentObject.transform.SetParent(viewport.transform, false);
        content = contentObject.GetComponent<RectTransform>();
        content.anchorMin = new Vector2(0f, 1f);
        content.anchorMax = new Vector2(1f, 1f);
        content.pivot = new Vector2(0.5f, 1f);
        content.anchoredPosition = Vector2.zero;
        content.sizeDelta = new Vector2(0f, 0f);

        VerticalLayoutGroup group = contentObject.GetComponent<VerticalLayoutGroup>();
        group.spacing = 10f;
        group.padding = new RectOffset(0, 0, 0, 0);
        group.childControlWidth = true;
        group.childControlHeight = false;
        group.childForceExpandWidth = true;
        group.childForceExpandHeight = false;

        ContentSizeFitter fitter = contentObject.GetComponent<ContentSizeFitter>();
        fitter.verticalFit = ContentSizeFitter.FitMode.PreferredSize;
        fitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;

        GameObject emptyObject = new GameObject("Empty", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        emptyObject.transform.SetParent(viewport.transform, false);
        RectTransform emptyRect = emptyObject.GetComponent<RectTransform>();
        emptyRect.anchorMin = Vector2.zero;
        emptyRect.anchorMax = Vector2.one;
        emptyRect.offsetMin = new Vector2(18f, 18f);
        emptyRect.offsetMax = new Vector2(-18f, -18f);
        emptyText = emptyObject.GetComponent<Text>();
        emptyText.font = font;
        emptyText.fontSize = 22;
        emptyText.color = new Color32(220, 180, 180, 255);
        emptyText.alignment = TextAnchor.MiddleCenter;
        emptyText.text = "No transfer records found yet.";
        emptyText.raycastTarget = false;

        ScrollRect scroll = scrollObject.GetComponent<ScrollRect>();
        scroll.viewport = viewportRect;
        scroll.content = content;
        scroll.horizontal = false;
        scroll.vertical = true;
        scroll.movementType = ScrollRect.MovementType.Clamped;
        scroll.scrollSensitivity = 30f;

        return scroll;
    }

    private Transform FindChildRecursive(Transform parent, string childName)
    {
        if (parent == null)
            return null;

        for (int index = 0; index < parent.childCount; index++)
        {
            Transform child = parent.GetChild(index);
            if (child.name == childName)
                return child;

            Transform nested = FindChildRecursive(child, childName);
            if (nested != null)
                return nested;
        }

        return null;
    }

    private void ApplyResponsiveAddCashLayout()
    {
        RectTransform root = transform as RectTransform;
        Canvas canvas = GetComponentInParent<Canvas>();
        RectTransform canvasRect = canvas != null ? canvas.transform as RectTransform : null;
        Vector2 canvasSize = canvasRect != null && canvasRect.rect.width > 0f && canvasRect.rect.height > 0f
            ? new Vector2(canvasRect.rect.width, canvasRect.rect.height)
            : new Vector2(Screen.width, Screen.height);
        bool portrait = IsPortraitLayout(canvasSize);

        _lastLayoutCanvasSize = canvasSize;
        _lastLayoutScreenSize = new Vector2(Screen.width, Screen.height);
        _lastLayoutOrientation = Screen.orientation;
        _lastLayoutPortrait = portrait;
        _layoutInitialized = true;

        // Always write the active orientation layout. Otherwise a previous
        // landscape rect can remain on the object when the view returns portrait.
            // We preserve the manual layout (size, anchors, pivot) of the root object to respect your Inspector design.
            // root.sizeDelta = new Vector2(rootWidth, rootHeight);
            /*
            // root.anchorMin = new Vector2(0.5f, 0.5f);
            // root.anchorMax = new Vector2(0.5f, 0.5f);
            // root.pivot = new Vector2(0.5f, 0.5f);
            // root.anchoredPosition = Vector2.zero;
            // root.localScale = Vector3.one;
            // */

            // LayoutAddCashRoot(root.sizeDelta.x, root.sizeDelta.y, portrait);
            // LayoutPaymentFlowPanels(root.sizeDelta.x, root.sizeDelta.y, portrait);

            // ApplyInputReadability(transform);
            // ApplyCustomAmountInputReadability();
    }

    private bool IsPortraitLayout(Vector2 canvasSize)
    {
        if (canvasSize.x > 1f && canvasSize.y > 1f && Mathf.Abs(canvasSize.y - canvasSize.x) > 1f)
            return canvasSize.y > canvasSize.x;

        if (Screen.width > 1 && Screen.height > 1 && Mathf.Abs(Screen.height - Screen.width) > 1)
            return Screen.height > Screen.width;

        Rect safeArea = Screen.safeArea;
        if (safeArea.width > 1f && safeArea.height > 1f && Mathf.Abs(safeArea.height - safeArea.width) > 1f)
            return safeArea.height > safeArea.width;

        bool orientationLandscape = Screen.orientation == ScreenOrientation.LandscapeLeft
            || Screen.orientation == ScreenOrientation.LandscapeRight;
        bool orientationPortrait = Screen.orientation == ScreenOrientation.Portrait
            || Screen.orientation == ScreenOrientation.PortraitUpsideDown;

        if (orientationPortrait)
            return true;
        if (orientationLandscape)
            return false;

        bool devicePortrait = Input.deviceOrientation == DeviceOrientation.Portrait
            || Input.deviceOrientation == DeviceOrientation.PortraitUpsideDown;
        if (devicePortrait)
            return true;

        bool deviceLandscape = Input.deviceOrientation == DeviceOrientation.LandscapeLeft
            || Input.deviceOrientation == DeviceOrientation.LandscapeRight;
        if (deviceLandscape)
            return false;

        return true;
    }

    private static Rect GetLayoutBounds(Rect rawBounds, bool portrait)
    {
        float width = Mathf.Abs(rawBounds.width);
        float height = Mathf.Abs(rawBounds.height);

        if (!portrait && height > width)
        {
            float temp = width;
            width = height;
            height = temp;
        }

        if (portrait && width > height)
        {
            float temp = width;
            width = height;
            height = temp;
        }

        return new Rect(0f, 0f, width, height);
    }

    private void LayoutAddCashRoot(float rootWidth, float rootHeight, bool portrait)
    {
        RectTransform panelBg = FindDirectChild(transform, "panel-bg");
        StretchToParent(panelBg);

        RectTransform automatic = FindDirectChild(transform, "Automatic");
        StretchToParent(automatic);

        RectTransform close = FindDeepChild(transform, "close") as RectTransform;
        SetTopRight(close, new Vector2(portrait ? 58f : 64f, portrait ? 58f : 64f), new Vector2(36f, -36f));

        RectTransform title = FindDirectTextChild(automatic, "Add Cash");
        SetTopCenter(title, new Vector2(rootWidth * 0.55f, portrait ? 58f : 62f), new Vector2(0f, portrait ? -58f : -52f));
        ConfigureAnyText(title, portrait ? 34f : 38f, Color.white, true);

        RectTransform tabs = FindDirectChild(automatic, "bbtn");
        float tabWidth = portrait ? Mathf.Clamp(rootWidth * 0.2f, 145f, 180f) : Mathf.Clamp(rootWidth * 0.18f, 170f, 230f);
        float tabTop = portrait ? -135f : -145f;
        SetLeftStretch(tabs, tabWidth, new Vector2(24f, tabTop), rootHeight - 190f);
        LayoutAddCashTabs(tabs, tabWidth, portrait);

        float sideMargin = portrait ? 28f : 34f;
        float gutter = portrait ? 18f : 26f;
        float contentX = sideMargin + tabWidth + gutter;
        float contentWidth = rootWidth - sideMargin * 2f - tabWidth - gutter;
        float contentHeight = rootHeight - (portrait ? 175f : 155f);

        RectTransform details = FindDeepChild(automatic, "AddCashDetails") as RectTransform;
        SetTopLeft(details, new Vector2(contentWidth, contentHeight), new Vector2(contentX, portrait ? -128f : -120f));
        LayoutAutomaticAddCashDetails(details, contentWidth, contentHeight, portrait);

        RectTransform recent = FindDeepChild(automatic, "Recent Transaction") as RectTransform;
        SetTopLeft(recent, new Vector2(contentWidth, contentHeight), new Vector2(contentX, portrait ? -128f : -120f));
    }

    private void LayoutPaymentFlowPanels(float rootWidth, float rootHeight, bool portrait)
    {
        LayoutPaymentOptionDialog(rootWidth, rootHeight, portrait);
        LayoutManualPaymentPanel("Manual", rootWidth, rootHeight, portrait);
        LayoutManualPaymentPanel("USDT Auto", rootWidth, rootHeight, portrait);
        LayoutManualPaymentPanel("USDT-Manual", rootWidth, rootHeight, portrait);
    }

    private void LayoutPaymentOptionDialog(float rootWidth, float rootHeight, bool portrait)
    {
        RectTransform dialog = FindDirectChild(transform, "Dialogue");
        if (dialog == null) return;

        StretchToParent(dialog);
        Image dialogImage = dialog.GetComponent<Image>();
        if (dialogImage != null)
            dialogImage.color = Color.clear;

        RectTransform table = FindDirectChild(dialog, "Table-Bg");
        StretchToParent(table);
        ApplyPopupBackground(table);

        RectTransform close = FindDirectChild(table, "close");
        SetTopRight(close, new Vector2(portrait ? 58f : 64f, portrait ? 58f : 64f), new Vector2(28f, -28f));

        RectTransform title = FindDirectChild(table, "Text (TMP)");
        SetTopCenter(title, new Vector2(rootWidth * 0.75f, portrait ? 62f : 70f), new Vector2(0f, portrait ? -42f : -34f));
        ConfigureAnyText(title, portrait ? 34f : 38f, Color.white, true);

        float buttonWidth = Mathf.Min(rootWidth * (portrait ? 0.72f : 0.46f), portrait ? 560f : 620f);
        float buttonHeight = portrait ? 86f : 82f;
        float spacing = portrait ? 18f : 14f;

        // Count only active option buttons so layout adjusts when admin disables options
        int activeCount = 0;
        for (int i = 0; i < table.childCount; i++)
        {
            var rt = table.GetChild(i) as RectTransform;
            if (rt != null && rt.name == "unselected" && rt.gameObject.activeSelf)
                activeCount++;
        }
        if (activeCount == 0) activeCount = 4; // fallback

        float totalHeight = buttonHeight * activeCount + spacing * (activeCount - 1);
        float firstY = totalHeight * 0.5f - buttonHeight * 0.5f;
        int index = 0;

        for (int i = 0; i < table.childCount; i++)
        {
            RectTransform option = table.GetChild(i) as RectTransform;
            if (option == null || option.name != "unselected") continue;
            if (!option.gameObject.activeSelf) continue;  // skip hidden options

            SetCentered(option, new Vector2(buttonWidth, buttonHeight), new Vector2(0f, firstY - index * (buttonHeight + spacing)));
            ApplyButtonBackground(option);
            ConfigureAnyText(option, portrait ? 25f : 30f, Color.white, true);
            index++;
        }
    }

    private void LayoutManualPaymentPanel(string panelName, float rootWidth, float rootHeight, bool portrait)
    {
        RectTransform panel = FindDirectChild(transform, panelName);
        if (panel == null) return;

        StretchToParent(panel);
        Image panelImage = panel.GetComponent<Image>();
        if (panelImage != null)
        {
            Color color = panelImage.color;
            color.a = 0f;
            panelImage.color = color;
        }

        RectTransform bg = FindDirectChild(panel, "Table-Bg");
        if (bg == null)
            bg = FindDirectChild(panel, "BG");
        if (bg == null) return;

        StretchToParent(bg);
        ApplyPopupBackground(bg);

        RectTransform close = FindDirectChild(bg, "close") ?? FindDirectChild(bg, "Cancel");
        SetTopRight(close, new Vector2(portrait ? 58f : 64f, portrait ? 58f : 64f), new Vector2(28f, -28f));

        RectTransform heading = FindDirectChild(bg, "History");
        SetTopCenter(heading, new Vector2(rootWidth * 0.72f, portrait ? 62f : 70f), new Vector2(0f, portrait ? -42f : -34f));
        ConfigureAnyText(heading, portrait ? 32f : 38f, Color.white, true);

        RectTransform title = FindDirectChild(bg, "Title");
        SetTopCenter(title, new Vector2(rootWidth * 0.82f, 52f), new Vector2(0f, portrait ? -132f : -112f));
        ConfigureAnyText(title, portrait ? 24f : 30f, new Color32(255, 180, 40, 255), false);

        bool usdtAuto = panelName == "USDT Auto";
        bool usdtManual = panelName == "USDT-Manual";
        float inputWidth = portrait ? rootWidth * 0.72f : rootWidth * 0.42f;
        float inputHeight = portrait ? 78f : 76f;
        float leftX = portrait ? 0f : -rootWidth * 0.2f;
        float inputY = portrait ? -205f : -185f;

        RectTransform amountInput = FindDirectChild(bg, "Enter Amount");
        SetTopCenter(amountInput, new Vector2(inputWidth, inputHeight), new Vector2(leftX, inputY));
        ConfigureInput(amountInput, portrait ? 26f : 30f);

        RectTransform utrInput = FindDirectChild(bg, "homepage-input-field") ?? FindDirectChild(bg, "Enter UTR");
        SetTopCenter(utrInput, new Vector2(inputWidth, inputHeight), new Vector2(leftX, inputY - (portrait ? 96f : 90f)));
        ConfigureInput(utrInput, portrait ? 26f : 30f);

        RectTransform submit = FindDirectChild(bg, "submit") ?? FindDirectChild(bg, "Submit Button") ?? FindDirectChild(bg, "UPI");
        SetTopCenter(submit, new Vector2(portrait ? 280f : 330f, portrait ? 82f : 86f), new Vector2(leftX, inputY - (portrait ? 204f : 188f)));
        ApplyButtonBackground(submit);
        ConfigureAnyText(submit, portrait ? 26f : 30f, Color.white, true);

        RectTransform scanner = FindDirectChild(bg, "Scanner");
        float scannerEdge = portrait ? Mathf.Clamp(rootWidth * 0.3f, 150f, 260f) : Mathf.Clamp(rootHeight * 0.34f, 170f, 260f);
        Vector2 scannerSize = new Vector2(scannerEdge, scannerEdge);
        Vector2 scannerPos = portrait
            ? new Vector2(-rootWidth * 0.16f, inputY - 265f)
            : new Vector2(rootWidth * 0.25f, -rootHeight * 0.08f);
        SetTopCenter(scanner, scannerSize, scannerPos);
        LayoutScannerChildren(scanner, portrait);

        RectTransform upload = FindDirectChild(bg, "upload-ss") ?? FindDirectChild(bg, "SS_Image");
        if (upload != null)
        {
            float uploadEdge = portrait ? Mathf.Clamp(rootWidth * 0.25f, 135f, 230f) : Mathf.Clamp(rootHeight * 0.26f, 150f, 230f);
            Vector2 uploadSize = new Vector2(uploadEdge, uploadEdge);
            Vector2 uploadPos = portrait
                ? new Vector2(rootWidth * 0.18f, inputY - 265f)
                : new Vector2(-rootWidth * 0.25f, -rootHeight * 0.08f);
            SetTopCenter(upload, uploadSize, uploadPos);
            ConfigureAnyText(upload, portrait ? 20f : 24f, Color.white, false);

            RectTransform uploadLogo = FindDirectChild(upload, "logo") ?? FindDirectChild(upload, "Image");
            SetCentered(uploadLogo, uploadSize * 0.45f, Vector2.zero);
        }

        if (usdtAuto || usdtManual)
        {
            RectTransform amount = FindDirectChild(bg, "Enter Amount");
            if (amount != null && amount.gameObject.activeSelf)
                ConfigureInput(amount, portrait ? 26f : 30f);
        }
    }

    private void LayoutScannerChildren(RectTransform scanner, bool portrait)
    {
        if (scanner == null) return;

        RectTransform qrImage = FindDirectChild(scanner, "QR_Code_Image");
        if (qrImage != null)
        {
            qrImage.anchorMin = Vector2.zero;
            qrImage.anchorMax = Vector2.one;
            qrImage.offsetMin = new Vector2(10f, 10f);
            qrImage.offsetMax = new Vector2(-10f, -10f);
        }

        RectTransform payHere = FindDirectChild(scanner, "PayHere");
        if (payHere != null)
        {
            SetTopCenter(payHere, new Vector2(scanner.rect.width * 1.5f, portrait ? 90f : 76f), new Vector2(0f, -scanner.rect.height - 14f));
            ConfigureAnyText(payHere, portrait ? 20f : 24f, Color.white, false);
        }
    }

    private void LayoutAddCashTabs(RectTransform tabs, float tabWidth, bool portrait)
    {
        if (tabs == null) return;

        float tabHeight = portrait ? 84f : 92f;
        float fontSize = portrait ? 24f : 30f;
        int visibleIndex = 0;

        for (int i = 0; i < tabs.childCount; i++)
        {
            RectTransform tab = tabs.GetChild(i) as RectTransform;
            if (tab == null || !tab.gameObject.activeSelf) continue;

            SetTopLeft(tab, new Vector2(tabWidth, tabHeight), new Vector2(0f, -visibleIndex * tabHeight));
            ConfigureAnyText(tab, fontSize, Color.white, true);
            visibleIndex++;
        }
    }

    private void LayoutAutomaticAddCashDetails(RectTransform details, float width, float height, bool portrait)
    {
        if (details == null) return;

        float rowWidth = width;
        float chipTop = portrait ? -105f : -100f;
        float inputTop = portrait ? -300f : -285f;
        float inputHeight = portrait ? 78f : 82f;
        float finalHeight = portrait ? 220f : 205f;

        RectTransform selectLabel = FindDeepChild(details, "Add_Money") as RectTransform;
        SetTopCenter(selectLabel, new Vector2(rowWidth, portrait ? 56f : 64f), new Vector2(0f, portrait ? -20f : -18f));
        ConfigureAnyText(selectLabel, portrait ? 28f : 34f, Color.white, false);

        RectTransform scrollView = FindDirectChild(details, "Scroll View");
        SetTopCenter(scrollView, new Vector2(rowWidth, portrait ? 105f : 110f), new Vector2(0f, chipTop));
        ConfigureAmountScroll(scrollView, rowWidth, portrait);

        RectTransform customLabel = FindDirectChild(details, "Custom");
        SetTopCenter(customLabel, new Vector2(rowWidth, 48f), new Vector2(0f, inputTop + 62f));
        ConfigureAnyText(customLabel, portrait ? 25f : 30f, new Color32(235, 120, 135, 255), true);

        RectTransform input = FindDirectChild(details, "homepage-input-field");
        float addButtonWidth = portrait ? Mathf.Clamp(width * 0.23f, 150f, 190f) : Mathf.Clamp(width * 0.18f, 160f, 210f);
        float inputWidth = width - addButtonWidth - 12f;
        SetTopLeft(input, new Vector2(inputWidth, inputHeight), new Vector2(0f, inputTop));
        ConfigureInput(input, portrait ? 28f : 34f);

        RectTransform addButton = FindDirectChild(details, "add-button");
        SetTopLeft(addButton, new Vector2(addButtonWidth, inputHeight), new Vector2(inputWidth + 12f, inputTop));
        ConfigureAnyText(addButton, portrait ? 27f : 32f, Color.white, true);
        EnsureTransferQuickButton(details, rowWidth, inputWidth, addButtonWidth, inputTop, portrait);

        RectTransform panel = finalpanel != null ? finalpanel.transform as RectTransform : FindDirectChild(details, "Panel");
        SetBottomStretch(panel, width, finalHeight, Vector2.zero);
        LayoutConfirmationPanel(panel, width, finalHeight, portrait);
    }

    private void ConfigureAmountScroll(RectTransform scrollView, float width, bool portrait)
    {
        if (scrollView == null) return;

        ScrollRect scroll = scrollView.GetComponent<ScrollRect>();
        if (scroll != null)
        {
            scroll.horizontal = true;
            scroll.vertical = false;
            scroll.movementType = ScrollRect.MovementType.Clamped;
            scroll.scrollSensitivity = 55f;
        }

        RectTransform viewport = FindDirectChild(scrollView, "Viewport");
        if (viewport != null)
        {
            viewport.anchorMin = Vector2.zero;
            viewport.anchorMax = Vector2.one;
            viewport.offsetMin = Vector2.zero;
            viewport.offsetMax = Vector2.zero;
        }

        LayoutChipList(width, portrait);
    }

    private static void StretchToParent(RectTransform rect)
    {
        if (rect == null) return;

        rect.anchorMin = Vector2.zero;
        rect.anchorMax = Vector2.one;
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.offsetMin = Vector2.zero;
        rect.offsetMax = Vector2.zero;
        rect.localScale = Vector3.one;
    }

    private static void SetTopRight(RectTransform rect, Vector2 size, Vector2 offset)
    {
        if (rect == null) return;

        rect.anchorMin = new Vector2(1f, 1f);
        rect.anchorMax = new Vector2(1f, 1f);
        rect.pivot = new Vector2(1f, 1f);
        rect.anchoredPosition = offset;
        rect.sizeDelta = size;
        rect.localScale = Vector3.one;
    }

    private static void SetTopCenter(RectTransform rect, Vector2 size, Vector2 offset)
    {
        if (rect == null) return;

        rect.anchorMin = new Vector2(0.5f, 1f);
        rect.anchorMax = new Vector2(0.5f, 1f);
        rect.pivot = new Vector2(0.5f, 1f);
        rect.anchoredPosition = offset;
        rect.sizeDelta = size;
        rect.localScale = Vector3.one;
    }

    private static void SetTopLeft(RectTransform rect, Vector2 size, Vector2 offset)
    {
        if (rect == null) return;

        rect.anchorMin = new Vector2(0f, 1f);
        rect.anchorMax = new Vector2(0f, 1f);
        rect.pivot = new Vector2(0f, 1f);
        rect.anchoredPosition = offset;
        rect.sizeDelta = size;
        rect.localScale = Vector3.one;
    }

    private static void SetCentered(RectTransform rect, Vector2 size, Vector2 offset)
    {
        if (rect == null) return;

        rect.anchorMin = new Vector2(0.5f, 0.5f);
        rect.anchorMax = new Vector2(0.5f, 0.5f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = offset;
        rect.sizeDelta = size;
        rect.localScale = Vector3.one;
    }

    private static void SetLeftStretch(RectTransform rect, float width, Vector2 offset, float height)
    {
        if (rect == null) return;

        rect.anchorMin = new Vector2(0f, 1f);
        rect.anchorMax = new Vector2(0f, 1f);
        rect.pivot = new Vector2(0f, 1f);
        rect.anchoredPosition = offset;
        rect.sizeDelta = new Vector2(width, height);
        rect.localScale = Vector3.one;
    }

    private static void SetBottomStretch(RectTransform rect, float width, float height, Vector2 offset)
    {
        if (rect == null) return;

        rect.anchorMin = new Vector2(0.5f, 0f);
        rect.anchorMax = new Vector2(0.5f, 0f);
        rect.pivot = new Vector2(0.5f, 0f);
        rect.anchoredPosition = offset;
        rect.sizeDelta = new Vector2(width, height);
        rect.localScale = Vector3.one;
    }

    private static void ConfigureAnyText(RectTransform root, float fontSize, Color color, bool noWrap)
    {
        // Text configuration set in the Inspector is preserved.
    }

    private static void ConfigureInput(RectTransform inputRect, float fontSize)
    {
        if (inputRect == null) return;

        TMP_InputField input = inputRect.GetComponent<TMP_InputField>();
        if (input == null) return;

        bool lightBackground = IsLightInputBackground(inputRect);
        Color textColor = lightBackground ? Color.black : Color.white;
        Color placeholderColor = lightBackground
            ? new Color32(80, 35, 45, 190)
            : new Color32(230, 150, 160, 190);

        if (input.textComponent != null)
        {
            input.textComponent.enableAutoSizing = false;
            input.textComponent.fontSize = fontSize;
            input.textComponent.color = textColor;
            input.textComponent.alignment = TextAlignmentOptions.MidlineLeft;
        }

        input.caretColor = textColor;
        input.selectionColor = lightBackground
            ? new Color32(40, 40, 40, 90)
            : new Color32(255, 255, 255, 90);

        TMP_Text placeholder = input.placeholder as TMP_Text;
        if (placeholder != null)
        {
            placeholder.enableAutoSizing = false;
            placeholder.fontSize = Mathf.Max(20f, fontSize - 3f);
            placeholder.color = placeholderColor;
            placeholder.alignment = TextAlignmentOptions.MidlineLeft;
        }
    }

    private static void ApplyInputReadability(Transform root)
    {
        if (root == null) return;

        foreach (TMP_InputField input in root.GetComponentsInChildren<TMP_InputField>(true))
        {
            RectTransform inputRect = input.transform as RectTransform;
            bool lightBackground = IsLightInputBackground(inputRect);
            Color textColor = lightBackground ? Color.black : Color.white;

            if (input.textComponent != null)
                input.textComponent.color = textColor;

            input.caretColor = textColor;
            input.selectionColor = lightBackground
                ? new Color32(40, 40, 40, 90)
                : new Color32(255, 255, 255, 90);

            TMP_Text placeholder = input.placeholder as TMP_Text;
            if (placeholder != null)
            {
                placeholder.color = lightBackground
                    ? new Color32(80, 35, 45, 190)
                    : new Color32(230, 150, 160, 190);
            }
        }
    }

    private static bool IsLightInputBackground(RectTransform inputRect)
    {
        Image image = inputRect.GetComponent<Image>();
        if (image == null)
            return false;

        Color color = image.color;
        float brightness = (color.r * 0.299f) + (color.g * 0.587f) + (color.b * 0.114f);
        return color.a > 0.2f && brightness > 0.65f;
    }

    private void ApplyCustomAmountInputReadability()
    {
        if (custom == null) return;

        if (custom.textComponent != null)
            custom.textComponent.color = Color.white;

        custom.caretColor = Color.white;
        custom.selectionColor = new Color32(255, 255, 255, 90);

        TMP_Text placeholder = custom.placeholder as TMP_Text;
        if (placeholder != null)
            placeholder.color = new Color32(230, 150, 160, 190);
    }

    private void BindPresetAmountButtons()
    {
        Transform searchRoot = content != null ? content : transform;
        foreach (Button button in searchRoot.GetComponentsInChildren<Button>(true))
        {
            TMP_Text label = button.GetComponentInChildren<TMP_Text>(true);
            if (label == null) continue;

            string amount = label.text.Trim();
            int parsedAmount;
            if (!int.TryParse(amount, out parsedAmount))
                continue;

            button.onClick.RemoveAllListeners();
            string capturedAmount = amount;
            button.onClick.AddListener(() => SelectPresetAmount(capturedAmount, capturedAmount, capturedAmount));
        }
    }

    private void SelectPresetAmount(string id, string amount, string coin)
    {
        if (custom != null)
            custom.text = amount;

        PlaceOrder(id, amount, coin);
        ApplyCustomAmountInputReadability();
    }

    private void ApplyPopupBackground(RectTransform rect)
    {
        // Popup backgrounds set in Inspector are preserved.
    }

    private void ApplyButtonBackground(RectTransform rect)
    {
        // Button backgrounds set in the Inspector are preserved.
    }

    private Sprite ResolveButtonSprite()
    {
        if (amountBtnSprite != null)
            return amountBtnSprite;

        RectTransform addButton = FindDeepChild(transform, "add-button") as RectTransform;
        Image image = addButton != null ? addButton.GetComponent<Image>() : null;
        return image != null ? image.sprite : null;
    }

    private Sprite ResolvePopupSprite()
    {
        if (popupBgSprite != null)
            return popupBgSprite;

        RectTransform panelBg = FindDirectChild(transform, "panel-bg");
        Image image = panelBg != null ? panelBg.GetComponent<Image>() : null;
        return image != null ? image.sprite : null;
    }

    private Transform FindPreferredPopupParent()
    {
        Canvas canvas = GetComponentInParent<Canvas>(true);
        if (canvas != null)
        {
            Canvas rootCanvas = canvas.rootCanvas != null ? canvas.rootCanvas : canvas;
            if (rootCanvas != null)
                return rootCanvas.transform;
        }

        return transform.parent != null ? transform.parent : transform;
    }

    private void LayoutChipList(float visibleWidth, bool portrait)
    {
        RectTransform contentRect = content as RectTransform;
        if (contentRect == null) return;

        float buttonWidth = portrait ? Mathf.Clamp((visibleWidth - 32f) / 3f, 142f, 190f) : 160f;
        float buttonHeight = portrait ? 70f : 78f;
        float gap = portrait ? 12f : 16f;
        float x = buttonWidth * 0.5f;

        contentRect.anchorMin = new Vector2(0f, 0.5f);
        contentRect.anchorMax = new Vector2(0f, 0.5f);
        contentRect.pivot = new Vector2(0f, 0.5f);
        contentRect.anchoredPosition = Vector2.zero;
        contentRect.sizeDelta = new Vector2(Mathf.Max(visibleWidth, content.childCount * (buttonWidth + gap)), buttonHeight);

        HorizontalLayoutGroup horizontal = contentRect.GetComponent<HorizontalLayoutGroup>();
        if (horizontal != null)
        {
            horizontal.enabled = false;
        }

        for (int i = 0; i < content.childCount; i++)
        {
            RectTransform child = content.GetChild(i) as RectTransform;
            if (child == null) continue;

            child.anchorMin = new Vector2(0f, 0.5f);
            child.anchorMax = new Vector2(0f, 0.5f);
            child.pivot = new Vector2(0.5f, 0.5f);
            child.anchoredPosition = new Vector2(x + i * (buttonWidth + gap), 0f);
            child.sizeDelta = new Vector2(buttonWidth, buttonHeight);
            child.localScale = Vector3.one;

            ChipUI chipUI = child.GetComponent<ChipUI>();
            ConfigureChipButton(chipUI, portrait ? 30f : 32f);
        }
    }

    private void LayoutConfirmationPanel(RectTransform panel, float width, float height, bool portrait)
    {
        if (panel == null) return;

        RectTransform final = FindDirectChild(panel, "Final");
        StretchToParent(final);

        float labelFont = portrait ? 23f : 26f;
        float valueFont = portrait ? 28f : 30f;
        float buttonFont = portrait ? 27f : 30f;
        float topY = -16f;
        float valueY = -58f;
        float buttonY = -132f;

        SetTopLeft(FindDirectChild(final, "Text (TMP) (1)"), new Vector2(width * 0.25f, 38f), new Vector2(0f, topY));
        SetTopLeft(FindDirectChild(final, "Text (TMP) (2)"), new Vector2(width * 0.18f, 38f), new Vector2(width * 0.25f, topY));
        SetTopLeft(FindDirectChild(final, "Text (TMP) (3)"), new Vector2(width * 0.07f, 38f), new Vector2(width * 0.43f, valueY));
        SetTopLeft(FindDirectChild(final, "Text (TMP) (4)"), new Vector2(width * 0.25f, 38f), new Vector2(width * 0.5f, topY));
        ConfigureAnyText(final, labelFont, new Color32(210, 170, 20, 255), true);

        RectTransform principalBox = FindDirectChild(final, "Principal");
        SetTopLeft(principalBox, new Vector2(width * 0.43f, 62f), new Vector2(0f, valueY));

        RectTransform newBox = FindDirectChild(final, "New");
        SetTopLeft(newBox, new Vector2(width * 0.25f, 62f), new Vector2(width * 0.5f, valueY));

        SetTopLeft(principal != null ? principal.transform as RectTransform : null, new Vector2(width * 0.17f, 62f), new Vector2(width * 0.03f, 0f));
        SetTopLeft(bonus != null ? bonus.transform as RectTransform : null, new Vector2(width * 0.17f, 62f), new Vector2(width * 0.25f, 0f));
        SetTopLeft(FindDirectChild(principalBox, "Text (TMP) (1)"), new Vector2(width * 0.06f, 62f), new Vector2(width * 0.2f, 0f));
        SetTopLeft(newamount != null ? newamount.transform as RectTransform : null, new Vector2(width * 0.25f, 62f), Vector2.zero);

        ConfigureValueText(principal, valueFont);
        ConfigureValueText(bonus, valueFont);
        ConfigureValueText(newamount, valueFont);
        ConfigureAnyText(FindDirectChild(principalBox, "Text (TMP) (1)"), valueFont, new Color32(210, 170, 20, 255), true);

        RectTransform addButton = FindDirectChild(final, "Add Cash");
        SetTopCenter(addButton, new Vector2(portrait ? 220f : 250f, 70f), new Vector2(0f, buttonY));
        ConfigureAnyText(addButton, buttonFont, Color.white, true);

        LayoutRebuilder.ForceRebuildLayoutImmediate(panel);
    }

    private void ApplyAddCashInnerPanelLayout(bool portrait)
    {
        Vector2 panelSize = portrait ? new Vector2(900f, 1260f) : new Vector2(1320f, 780f);
        SetCenteredChildRect(transform, "panel-bg", panelSize);
        SetCenteredChildRect(transform, "Automatic", panelSize);
        SetCenteredChildRect(transform, "Dialogue", panelSize);
        SetCenteredChildRect(transform, "Manual", panelSize);
        SetCenteredChildRect(transform, "USDT Auto", panelSize);
        SetCenteredChildRect(transform, "USDT-Manual", panelSize);

        SetCenteredChildRect(transform, "Table-Bg", panelSize);
        SetCenteredChildRect(transform, "BG", panelSize);

        SetChildRectByName(transform, "AddCashDetails", portrait ? new Vector2(760f, 980f) : new Vector2(920f, 560f));
        SetChildRectByName(transform, "Recent Transaction", portrait ? new Vector2(820f, 980f) : new Vector2(1120f, 620f));
        SetChildRectByName(transform, "Scroll View", portrait ? new Vector2(760f, 520f) : new Vector2(940f, 420f));
        SetChildRectByName(transform, "homepage-input-field", portrait ? new Vector2(660f, 108f) : new Vector2(560f, 82f));
        SetChildRectByName(transform, "Enter Amount", portrait ? new Vector2(720f, 108f) : new Vector2(620f, 82f));
        SetChildRectByName(transform, "Enter UTR", portrait ? new Vector2(720f, 108f) : new Vector2(620f, 82f));
        SetChildRectByName(transform, "submit", portrait ? new Vector2(420f, 110f) : new Vector2(320f, 88f));
        SetChildRectByName(transform, "Submit Button", portrait ? new Vector2(460f, 110f) : new Vector2(340f, 88f));
        SetChildRectByName(transform, "upload-ss", portrait ? new Vector2(230f, 230f) : new Vector2(180f, 180f));
        SetChildRectByName(transform, "SS_Image", portrait ? new Vector2(230f, 230f) : new Vector2(180f, 180f));
        SetChildRectByName(transform, "Scanner", portrait ? new Vector2(340f, 320f) : new Vector2(260f, 250f));

        LayoutAutomaticAddCashPanel(portrait);
    }

    private void LayoutAutomaticAddCashPanel(bool portrait)
    {
        RectTransform automatic = FindDeepChild(transform, "Automatic") as RectTransform;
        if (automatic == null)
        {
            return;
        }

        SetRect(FindDirectTextChild(automatic, "Add Cash"), portrait ? new Vector2(420f, 86f) : new Vector2(360f, 64f), portrait ? new Vector2(0f, 545f) : new Vector2(0f, 325f));

        RectTransform details = FindDeepChild(automatic, "AddCashDetails") as RectTransform;
        SetRect(details, portrait ? new Vector2(760f, 980f) : new Vector2(980f, 560f), portrait ? new Vector2(0f, -50f) : new Vector2(0f, -35f));
        if (details == null)
        {
            return;
        }

        SetRect(FindDirectChild(details, "homepage-input-field"), portrait ? new Vector2(660f, 108f) : new Vector2(560f, 82f), portrait ? new Vector2(0f, 360f) : new Vector2(-170f, 205f));
        SetRect(FindDirectChild(details, "Scroll View"), portrait ? new Vector2(720f, 500f) : new Vector2(920f, 320f), portrait ? new Vector2(0f, 35f) : new Vector2(0f, -10f));

        RectTransform final = FindDeepChild(details, "Final") as RectTransform;
        SetRect(final, portrait ? new Vector2(460f, 110f) : new Vector2(340f, 82f), portrait ? new Vector2(0f, -410f) : new Vector2(315f, 205f));
        if (final != null)
        {
            SetRect(FindDirectChild(final, "Add Cash"), portrait ? new Vector2(420f, 96f) : new Vector2(320f, 74f), Vector2.zero);
        }
    }

    private static void SetRect(RectTransform rect, Vector2 size, Vector2 anchoredPosition)
    {
        if (rect == null)
        {
            return;
        }

        rect.anchorMin = new Vector2(0.5f, 0.5f);
        rect.anchorMax = new Vector2(0.5f, 0.5f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = anchoredPosition;
        rect.localScale = Vector3.one;
        rect.sizeDelta = size;

        LayoutElement layout = rect.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.minWidth = size.x;
            layout.preferredWidth = size.x;
            layout.minHeight = size.y;
            layout.preferredHeight = size.y;
        }
    }

    private static RectTransform FindDirectChild(Transform parent, string childName)
    {
        if (parent == null)
        {
            return null;
        }

        for (int i = 0; i < parent.childCount; i++)
        {
            Transform child = parent.GetChild(i);
            if (string.Equals(child.name, childName, StringComparison.OrdinalIgnoreCase))
            {
                return child as RectTransform;
            }
        }

        return null;
    }

    private static RectTransform FindDirectTextChild(Transform parent, string textContains)
    {
        if (parent == null)
        {
            return null;
        }

        for (int i = 0; i < parent.childCount; i++)
        {
            Transform child = parent.GetChild(i);
            TMP_Text tmp = child.GetComponent<TMP_Text>();
            if (tmp != null && tmp.text != null && tmp.text.IndexOf(textContains, StringComparison.OrdinalIgnoreCase) >= 0)
            {
                return child as RectTransform;
            }

            Text text = child.GetComponent<Text>();
            if (text != null && text.text != null && text.text.IndexOf(textContains, StringComparison.OrdinalIgnoreCase) >= 0)
            {
                return child as RectTransform;
            }
        }

        return null;
    }

    private static void SetCenteredChildRect(Transform root, string childName, Vector2 size)
    {
        RectTransform rect = FindDeepChild(root, childName) as RectTransform;
        if (rect == null)
        {
            return;
        }

        rect.anchorMin = new Vector2(0.5f, 0.5f);
        rect.anchorMax = new Vector2(0.5f, 0.5f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = Vector2.zero;
        rect.sizeDelta = size;
    }

    private static void SetChildRectByName(Transform root, string childName, Vector2 size)
    {
        RectTransform rect = FindDeepChild(root, childName) as RectTransform;
        if (rect == null)
        {
            return;
        }

        Vector2 anchorCenter = (rect.anchorMin + rect.anchorMax) * 0.5f;
        rect.anchorMin = anchorCenter;
        rect.anchorMax = anchorCenter;
        rect.sizeDelta = size;
        LayoutElement layout = rect.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.minWidth = size.x;
            layout.preferredWidth = size.x;
            layout.minHeight = size.y;
            layout.preferredHeight = size.y;
        }
    }

    private static Transform FindDeepChild(Transform root, string childName)
    {
        if (root == null)
        {
            return null;
        }

        if (root.name == childName)
        {
            return root;
        }

        for (int i = 0; i < root.childCount; i++)
        {
            Transform found = FindDeepChild(root.GetChild(i), childName);
            if (found != null)
            {
                return found;
            }
        }

        return null;
    }

    private static void StylePopupTexts(Transform root, int minTextSize, int minTitleSize)
    {
        foreach (Text text in root.GetComponentsInChildren<Text>(true))
        {
            if (text == null) continue;
            bool title = text.text != null && (text.text.IndexOf("Add Cash", StringComparison.OrdinalIgnoreCase) >= 0
                || text.text.IndexOf("Payment", StringComparison.OrdinalIgnoreCase) >= 0
                || text.text.IndexOf("Select", StringComparison.OrdinalIgnoreCase) >= 0);
            int size = title ? minTitleSize : minTextSize;
            text.fontSize = Mathf.Max(text.fontSize, size);
            text.resizeTextForBestFit = true;
            text.resizeTextMinSize = Mathf.Max(18, size - 8);
            text.resizeTextMaxSize = Mathf.Max(text.fontSize, size);
            text.verticalOverflow = VerticalWrapMode.Overflow;
            text.horizontalOverflow = HorizontalWrapMode.Wrap;
        }

        foreach (TMP_Text text in root.GetComponentsInChildren<TMP_Text>(true))
        {
            if (text == null) continue;
            bool title = text.text != null && (text.text.IndexOf("Add Cash", StringComparison.OrdinalIgnoreCase) >= 0
                || text.text.IndexOf("Payment", StringComparison.OrdinalIgnoreCase) >= 0
                || text.text.IndexOf("Select", StringComparison.OrdinalIgnoreCase) >= 0);
            float size = title ? minTitleSize : minTextSize;
            text.fontSize = Mathf.Max(text.fontSize, size);
            text.enableAutoSizing = true;
            text.fontSizeMin = Mathf.Max(18f, size - 8f);
            text.fontSizeMax = Mathf.Max(text.fontSize, size);
            text.overflowMode = TextOverflowModes.Overflow;
        }
    }

    private static void StylePopupInputs(Transform root, int fontSize, float height)
    {
        foreach (TMP_InputField input in root.GetComponentsInChildren<TMP_InputField>(true))
        {
            if (input == null) continue;
            LayoutElement layout = input.GetComponent<LayoutElement>() ?? input.gameObject.AddComponent<LayoutElement>();
            layout.minHeight = height;
            layout.preferredHeight = height;
            if (input.textComponent != null)
            {
                input.textComponent.fontSize = fontSize;
                input.textComponent.enableAutoSizing = false;
            }
            TMP_Text placeholder = input.placeholder as TMP_Text;
            if (placeholder != null)
            {
                placeholder.fontSize = Mathf.Max(24, fontSize - 4);
                placeholder.enableAutoSizing = false;
            }
        }
    }

    private static void StylePopupButtons(Transform root, int fontSize, float height)
    {
        foreach (Button button in root.GetComponentsInChildren<Button>(true))
        {
            if (button == null) continue;
            LayoutElement layout = button.GetComponent<LayoutElement>() ?? button.gameObject.AddComponent<LayoutElement>();
            layout.minHeight = Mathf.Max(layout.minHeight, height);
            layout.preferredHeight = Mathf.Max(layout.preferredHeight, height);
            Text label = button.GetComponentInChildren<Text>(true);
            if (label != null)
            {
                label.fontSize = Mathf.Max(label.fontSize, fontSize);
                label.resizeTextForBestFit = true;
                label.resizeTextMinSize = Mathf.Max(18, fontSize - 8);
                label.resizeTextMaxSize = Mathf.Max(label.fontSize, fontSize);
            }
            TMP_Text tmpLabel = button.GetComponentInChildren<TMP_Text>(true);
            if (tmpLabel != null)
            {
                tmpLabel.fontSize = Mathf.Max(tmpLabel.fontSize, fontSize);
                tmpLabel.enableAutoSizing = true;
                tmpLabel.fontSizeMin = Mathf.Max(18, fontSize - 8);
                tmpLabel.fontSizeMax = Mathf.Max(tmpLabel.fontSize, fontSize);
            }
        }
    }

    private static void StylePopupScrollRects(Transform root, float minContentWidth)
    {
        foreach (ScrollRect scrollRect in root.GetComponentsInChildren<ScrollRect>(true))
        {
            if (scrollRect == null) continue;
            scrollRect.vertical = true;
            scrollRect.horizontal = true;
            scrollRect.movementType = ScrollRect.MovementType.Elastic;
            scrollRect.scrollSensitivity = 70f;
            RectTransform contentRect = scrollRect.content;
            if (contentRect != null)
            {
                contentRect.anchorMin = new Vector2(0f, 1f);
                contentRect.anchorMax = new Vector2(0f, 1f);
                contentRect.pivot = new Vector2(0f, 1f);
                contentRect.sizeDelta = new Vector2(Mathf.Max(contentRect.sizeDelta.x, minContentWidth), contentRect.sizeDelta.y);
            }
        }
    }

    private GameObject CreateNewChip(PlanDetailchip coin)
    {
        GameObject go = Instantiate(prefab, content.transform);

        ChipUI chipUI = go.GetComponent<ChipUI>();
        chipUI.coinText.text = coin.price;
        ConfigureChipButton(chipUI);
        buttonsobjs.Add(chipUI.chipButton.gameObject);
        spawned_objects[coin.coin] = go;

        return go;
    }

    private static void ConfigureChipButton(ChipUI chipUI)
    {
        ConfigureChipButton(chipUI, 36f);
    }

    private static void ConfigureChipButton(ChipUI chipUI, float fontSize)
    {
        // Manual Inspector configuration for font sizes and percentage visibility is preserved.
    }

    private static void RemoveGeneratedObject(GameObject obj)
    {
        if (obj == null) return;

        obj.SetActive(false);
        if (Application.isPlaying)
            Destroy(obj);
        else
            DestroyImmediate(obj);
    }

    public void ShowChips(PlanDetailsWrapper details)
    {
        List<PlanDetailchip> planDetails = details != null && details.PlanDetails != null
            ? details.PlanDetails
            : new List<PlanDetailchip>();

        if (planDetails.Count == 0)
        {
            CommonUtil.CheckLog("Add Cash plans are empty or unavailable.");
            return;
        }

        var sortedPlanDetails = planDetails
            .Where(p => p != null && !string.IsNullOrWhiteSpace(p.coin))
            .OrderBy(p =>
            {
                int parsedCoin;
                return int.TryParse(p.coin, out parsedCoin) ? parsedCoin : int.MaxValue;
            })
            .ToList();

        spawned_objects.Clear();
        buttonsobjs.Clear();

        if (content == null)
            return;

        int childIndex = 0;
        foreach (var detail in sortedPlanDetails)
        {
            ChipUI chipUI = null;
            while (childIndex < content.childCount && chipUI == null)
            {
                chipUI = content.GetChild(childIndex).GetComponent<ChipUI>();
                childIndex++;
            }

            if (chipUI == null)
            {
                CommonUtil.CheckLog("Add Cash hierarchy has fewer chip buttons than API plans. Runtime creation is disabled.");
                break;
            }

            GameObject go = chipUI.gameObject;
            go.SetActive(true);
            chipUI.coinText.text = detail.price;
            ConfigureChipButton(chipUI);

            chipUI.chipButton.onClick.RemoveAllListeners();

            string capturedId = detail.id;
            string capturedPrice = detail.price;
            string capturedCoin = detail.coin;
            chipUI.chipButton.onClick.AddListener(
                () => SelectPresetAmount(capturedId, capturedPrice, capturedCoin)
            );

            chipUI.chipButton.onClick.AddListener(() => changebuttonui(chipUI.chipButton));
        }

        for (int i = childIndex; i < content.childCount; i++)
        {
            content.GetChild(i).gameObject.SetActive(false);
        }

        if (content is RectTransform contentRect)
        {
            LayoutRebuilder.ForceRebuildLayoutImmediate(contentRect);
        }

        ApplyResponsiveAddCashLayout();
        BindPresetAmountButtons();
    }

    private static List<PlanDetailchip> BuildFallbackPlanDetails()
    {
        int[] fallbackAmounts = { 100, 500, 2500, 5000, 10000 };
        List<PlanDetailchip> fallbackPlans = new List<PlanDetailchip>(fallbackAmounts.Length);

        foreach (int amount in fallbackAmounts)
        {
            string amountText = amount.ToString();
            fallbackPlans.Add(new PlanDetailchip
            {
                id = "",
                coin = amountText,
                price = amountText,
                title = amountText,
                isDeleted = "0",
            });
        }

        return fallbackPlans;
    }

    public static float CalculatePercentage(int coin, int price)
    {
        int amount = coin - price;
        float divide = (float)amount / price; // Convert amount to float to get correct division
        float percentage = divide * 100f;
        Debug.Log("RES_Check + Percent " + percentage);
        return percentage;
    }

    public void changebuttonui(Button btn)
    {
        // Keep the prefab/scene-authored button visuals. The old code changed
        // selected chips to gray and every other chip to white, breaking design.
    }

    private Color HexToColor(string hex)
    {
        Color color;
        if (ColorUtility.TryParseHtmlString(hex, out color))
        {
            return color;
        }
        return Color.white; // Default color if parsing fails
    }

    public void PlaceOrder(string id, string amount, string coin)
    {
        _selectedAutomaticPlanId = id ?? string.Empty;
        _selectedAutomaticAmount = amount ?? string.Empty;
        automatic_button.onClick.RemoveAllListeners();
        automatic_button.onClick.AddListener(async () => await PlaceOrderAPI(id, amount));
        automatic_amount = int.Parse(amount);
        manual_amount = int.Parse(amount);
        uSDTManual.amount = int.Parse(amount);
        if (custom != null)
            custom.text = amount;
        ShowNewUI(id, amount, coin);
        //popup.buttonclick(dialogue);
        //PopUpUtil.ButtonClick(dialogue);
    }

    public void ShowNewUI(string id, string amount, string coin)
    {
        finalpanel.SetActive(true);
        principal.text = amount;
        if (int.Parse(coin) > int.Parse(amount))
            bonus.text = int.Parse(coin) - int.Parse(amount) + "";
        else
            bonus.text = "0";
        newamount.text = coin;
        ApplyAddCashConfirmationLayout();

        // automatic_button.onClick.RemoveAllListeners();
        // automatic_button.onClick.AddListener(async () => await PlaceOrderAPI(id, coin));
    }

    private void ApplyAddCashConfirmationLayout()
    {
        // Manual Inspector layout for the confirmation panel is preserved.
    }

    private void SetTmpLayout(string relativePath, Vector2 position, Vector2 size, float fontSize)
    {
        RectTransform rect = finalpanel.transform.Find(relativePath) as RectTransform;
        if (rect == null) return;

        rect.anchorMin = new Vector2(0f, 1f);
        rect.anchorMax = new Vector2(0f, 1f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = position;
        rect.sizeDelta = size;

        TextMeshProUGUI text = rect.GetComponent<TextMeshProUGUI>();
        if (text == null) return;

        text.enableAutoSizing = false;
        text.fontSize = fontSize;
        text.textWrappingMode = TextWrappingModes.NoWrap;
        text.overflowMode = TextOverflowModes.Overflow;
        text.alignment = TextAlignmentOptions.Center;
    }

    private void SetTmpChildLayout(string relativePath, Vector2 position, Vector2 size, float fontSize)
    {
        RectTransform rect = finalpanel.transform.Find(relativePath) as RectTransform;
        if (rect == null) return;

        SetBottomAnchored(rect, position, size);

        TextMeshProUGUI text = rect.GetComponent<TextMeshProUGUI>();
        if (text == null) return;

        text.enableAutoSizing = false;
        text.fontSize = fontSize;
        text.textWrappingMode = TextWrappingModes.NoWrap;
        text.overflowMode = TextOverflowModes.Overflow;
        text.alignment = TextAlignmentOptions.Center;
    }

    private static void SetBottomAnchored(RectTransform rect, Vector2 position, Vector2 size)
    {
        if (rect == null) return;

        rect.anchorMin = Vector2.zero;
        rect.anchorMax = Vector2.zero;
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = position;
        rect.sizeDelta = size;
    }

    private static void ConfigureValueText(TextMeshProUGUI text)
    {
        ConfigureValueText(text, 34f);
    }

    private static void ConfigureValueText(TextMeshProUGUI text, float fontSize)
    {
        if (text == null) return;

        text.enableAutoSizing = false;
        text.fontSize = fontSize;
        text.textWrappingMode = TextWrappingModes.NoWrap;
        text.overflowMode = TextOverflowModes.Overflow;
        text.alignment = TextAlignmentOptions.Center;
        text.color = Color.white;
    }

    public void OpenPopUP()
    {
        ShowPaymentOptionsPanel();
    }

    #endregion

    #region Automatic Payment

    public void OpenURLInBrowser(string url)
    {
        CommonUtil.CheckLog("RES_Check + url open " + url);
        CommonUtil.CheckLog("PAY_TRACE OpenURLInBrowser " + (url ?? string.Empty));
        if (string.IsNullOrWhiteSpace(url))
        {
            CommonUtil.CheckLog("PAY_TRACE OpenURLInBrowser blocked: empty url");
            SafeShowToast("Payment URL is not available right now.");
            return;
        }

        if (Application.isEditor)
        {
            CommonUtil.CheckLog("PAY_TRACE OpenURLInBrowser editor fallback -> Application.OpenURL");
            OpenURLInWeb(url);
            return;
        }

#if UNITY_ANDROID || UNITY_IOS
        OpenUrlInWebView(url);
#else
        if (Application.platform == RuntimePlatform.Android)
        {
            OpenURLInAndroid(url);
        }
        else
        {
            OpenURLInWeb(url);
        }
#endif
    }

    private void OpenAutomaticPaymentUrl(string url, int orderId, string transactionId)
    {
        CommonUtil.CheckLog(
            "PAY_TRACE OpenAutomaticPaymentUrl order_id="
            + orderId
            + " transaction_id="
            + (transactionId ?? string.Empty)
            + " url="
            + (url ?? string.Empty)
        );
        _pendingGatewayOrderId = orderId;
        _pendingGatewayTransactionId = transactionId ?? string.Empty;
        _automaticGatewayFlowOpen = true;
        OpenURLInBrowser(url);
    }

#if UNITY_ANDROID || UNITY_IOS
    private void OpenUrlInWebView(string url)
    {
        try
        {
            var configuration = new GpmWebViewRequest.Configuration
            {
                style = GpmWebViewStyle.FULLSCREEN,
                orientation = GpmOrientation.UNSPECIFIED,
                isClearCookie = false,
                isClearCache = false,
                isNavigationBarVisible = true,
                navigationBarColor = "#111111",
                title = "Deposit",
                isBackButtonVisible = true,
                isForwardButtonVisible = false,
                isCloseButtonVisible = true,
                supportMultipleWindows = true,
                isBackButtonCloseCallbackUsed = true,
#if UNITY_IOS
                contentMode = GpmWebViewContentMode.RECOMMENDED,
                isMaskViewVisible = true,
                isAutoRotation = true,
#endif
            };

            GpmWebView.ShowUrl(url, configuration, OnAutomaticPaymentWebViewCallback, null);
        }
        catch (Exception exception)
        {
            Debug.LogError("Failed to open payment webview: " + exception.Message);
            OpenURLInWeb(url);
        }
    }

    private void OnAutomaticPaymentWebViewCallback(
        GpmWebViewCallback.CallbackType callbackType,
        string data,
        GpmWebViewError error
    )
    {
        if (error != null)
        {
            Debug.LogWarning("Payment WebView callback error: " + error);
        }

        switch (callbackType)
        {
            case GpmWebViewCallback.CallbackType.Close:
#if UNITY_ANDROID
            case GpmWebViewCallback.CallbackType.BackButtonClose:
#endif
                HandleAutomaticPaymentFlowClosed();
                break;
        }
    }
#endif

    private void HandleAutomaticPaymentFlowClosed()
    {
        if (!_automaticGatewayFlowOpen)
        {
            return;
        }

        _automaticGatewayFlowOpen = false;
        _ = PollAutomaticPaymentStatusAfterClose();
    }

    private void OpenURLInAndroid(string url)
    {
        try
        {
            using (var unityPlayer = new AndroidJavaClass("com.unity3d.player.UnityPlayer"))
            using (
                var currentActivity = unityPlayer.GetStatic<AndroidJavaObject>("currentActivity")
            )
            {
                using (
                    var intent = new AndroidJavaObject(
                        "android.content.Intent",
                        "android.intent.action.VIEW"
                    )
                )
                using (
                    var uri = new AndroidJavaClass("android.net.Uri").CallStatic<AndroidJavaObject>(
                        "parse",
                        url
                    )
                )
                {
                    intent.Call<AndroidJavaObject>("setData", uri);
                    currentActivity.Call("startActivity", intent);
                }
            }
        }
        catch (AndroidJavaException e)
        {
            if (e.Message.Contains("ActivityNotFoundException"))
            {
                Debug.LogError($"No application can handle this intent. URL: {url}");
            }
            else
            {
                Debug.LogError($"Failed to launch intent: {e.Message}");
            }
        }
    }

    [System.Obsolete]
    private void OpenURLInWeb(string url)
    {
        Debug.Log("Open URL Online");
        Application.OpenURL(url);
    }

    private async Task PollAutomaticPaymentStatusAfterClose()
    {
        if (_isAutomaticStatusCheckRunning)
        {
            return;
        }

        if (_pendingGatewayOrderId <= 0 && string.IsNullOrEmpty(_pendingGatewayTransactionId))
        {
            return;
        }

        _isAutomaticStatusCheckRunning = true;

        try
        {
            const int maxAttempts = 6;
            for (int attempt = 0; attempt < maxAttempts; attempt++)
            {
                AutomaticPaymentStatusResponse response = await FetchAutomaticPaymentStatus();
                if (response != null && response.code == 200)
                {
                    string normalizedStatus = NormalizeGatewayStatus(response.status_label, response.status);
                    if (normalizedStatus == "success")
                    {
                        Configuration.GetProfileWallet();
                        SafeShowToast("Deposit approved. Wallet updated.");
                        ClearAutomaticPaymentTracking();
                        return;
                    }

                    if (normalizedStatus == "rejected")
                    {
                        SafeShowToast("Deposit rejected.");
                        ClearAutomaticPaymentTracking();
                        return;
                    }
                }

                if (attempt < maxAttempts - 1)
                {
                    await Task.Delay(3000);
                }
            }

            SafeShowToast("Payment request submitted. Approval status will update shortly.");
        }
        catch (Exception exception)
        {
            Debug.LogError("Automatic payment status check failed: " + exception.Message);
            SafeShowToast("Payment request submitted. Check status in transaction history.");
        }
        finally
        {
            _isAutomaticStatusCheckRunning = false;
        }
    }

    private async Task<AutomaticPaymentStatusResponse> FetchAutomaticPaymentStatus()
    {
        string url = Configuration.AutomaticPaymentStatus;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        if (_pendingGatewayOrderId > 0)
        {
            formData["order_id"] = _pendingGatewayOrderId.ToString();
        }

        if (!string.IsNullOrEmpty(_pendingGatewayTransactionId))
        {
            formData["transaction_id"] = _pendingGatewayTransactionId;
        }

        return await APIManager.Instance.Post<AutomaticPaymentStatusResponse>(url, formData);
    }

    private string NormalizeGatewayStatus(string statusLabel, string statusCode)
    {
        string normalized = (statusLabel ?? string.Empty).Trim().ToLowerInvariant();
        if (string.IsNullOrEmpty(normalized))
        {
            normalized = (statusCode ?? string.Empty).Trim();
            if (normalized == "1")
            {
                return "success";
            }

            if (normalized == "2")
            {
                return "rejected";
            }

            return "pending";
        }

        if (normalized == "1")
        {
            return "success";
        }

        if (normalized == "2")
        {
            return "rejected";
        }

        return normalized;
    }

    private void ClearAutomaticPaymentTracking()
    {
        _pendingGatewayOrderId = 0;
        _pendingGatewayTransactionId = string.Empty;
        _automaticGatewayFlowOpen = false;
    }

    #endregion

    #region  Custom Payment

    public void CustomPayment()
    {
        if (custom.text != string.Empty)
        {
            if (int.Parse(custom.text) == 0)
            {
                CommonUtil.ShowToast("Please enter a number greater than 0");
            }
            else
            {
                _selectedAutomaticPlanId = string.Empty;
                _selectedAutomaticAmount = custom.text;
                automatic_button.onClick.RemoveAllListeners();
                automatic_button.onClick.AddListener(
                    async () => await PlaceOrderAPI("", custom.text)
                );
                manual_amount = int.Parse(custom.text);
                automatic_amount = int.Parse(custom.text);
                ShowNewUI("", custom.text, custom.text);
                ShowPaymentOptionsPanel();
            }
        }
        else
            LoaderUtil.instance.ShowToast("Please enter a valid amount");
    }

    #endregion

    #region Manual Payment

    public async void OpenAutomaticGatewayFromOption()
    {
        HidePaymentFlowPanels();
        string amount = !string.IsNullOrWhiteSpace(_selectedAutomaticAmount)
            ? _selectedAutomaticAmount
            : custom != null
                ? custom.text
                : string.Empty;

        CommonUtil.CheckLog(
            "PAY_TRACE OpenAutomaticGatewayFromOption plan_id="
            + (_selectedAutomaticPlanId ?? string.Empty)
            + " amount="
            + (amount ?? string.Empty)
        );

        if (string.IsNullOrWhiteSpace(amount) || amount == "0")
        {
            CommonUtil.CheckLog("PAY_TRACE OpenAutomaticGatewayFromOption blocked: invalid amount");
            CommonUtil.ShowToast("Please enter a valid amount");
            return;
        }

        await PlaceOrderAPI(_selectedAutomaticPlanId, amount);
    }

    public void OpenUsdtManualOption()
    {
        CommonUtil.CheckLog("PAY_TRACE OpenUsdtManualOption");
        HidePaymentFlowPanels();
        RectTransform manualUsdt = FindDirectChild(transform, "USDT-Manual");
        if (manualUsdt == null)
        {
            CommonUtil.CheckLog("PAY_TRACE OpenUsdtManualOption missing USDT-Manual panel");
            return;
        }

        manualUsdt.gameObject.SetActive(true);
        manualUsdt.SetAsLastSibling();
        transform.SetAsLastSibling();
        ApplyResponsiveAddCashLayout();
    }

    public void OpenUsdtAutoOption()
    {
        CommonUtil.CheckLog("PAY_TRACE OpenUsdtAutoOption");
        HidePaymentFlowPanels();
        GameObject autoPanel = ResolveUsdtAutoPanel();
        if (autoPanel == null)
        {
            CommonUtil.CheckLog("PAY_TRACE OpenUsdtAutoOption missing USDT Auto panel");
            return;
        }

        autoPanel.SetActive(true);
        autoPanel.transform.SetAsLastSibling();
        transform.SetAsLastSibling();
        ApplyResponsiveAddCashLayout();
    }

    public void HideManualPanel()
    {
        if (manual_panel != null)
            manual_panel.SetActive(false);
    }

    public async void OpenManual()
    {
        HidePaymentFlowPanels();

        if (manual_panel != null)
        {
            manual_panel.SetActive(true);
            manual_panel.transform.SetAsLastSibling();
            FixManualPanelLayout();
        }
        transform.SetAsLastSibling();
        ApplyResponsiveAddCashLayout();
        await QR_API();
        ApplyResponsiveAddCashLayout();
    }

    private void FixManualPanelLayout()
    {
        if (manual_panel == null) return;

        // Ensure SSPreviewImage color is white so uploaded sprite is visible
        if (manual_ss_img != null)
            manual_ss_img.color = Color.white;

        // Ensure SubmitButton has correct height (creation bug sets sizeDelta.y=0)
        var content = manual_panel.transform.Find("Card/ScrollView/Viewport/Content");
        if (content != null)
        {
            var submitRT = content.Find("SubmitButton")?.GetComponent<RectTransform>();
            if (submitRT != null && submitRT.sizeDelta.y < 80f)
                submitRT.sizeDelta = new Vector2(submitRT.sizeDelta.x, 100f);

            // Ensure content is tall enough to contain all items including submit button
            var cntRT = content.GetComponent<RectTransform>();
            if (cntRT != null)
            {
                var submitAP = submitRT?.anchoredPosition.y ?? -1550f;
                float minHeight = Mathf.Abs(submitAP) + 120f;
                if (cntRT.sizeDelta.y < minHeight)
                    cntRT.sizeDelta = new Vector2(cntRT.sizeDelta.x, minHeight);
            }
        }
    }

    // public async void StartDownloadQR(string qr_imag_url)
    // {
    //     await DownloadQRAsync(qr_imag_url);
    // }
    public IEnumerator DownloadQR(string qrImageUrl)
    {
        if (!TryGetValidQrUrl(qrImageUrl, out string resolvedUrl))
        {
            Debug.LogWarning("QR image skipped because backend returned an invalid URL: " + qrImageUrl);
            SafeShowToast("QR image is not available. Please try another payment option.");
            yield break;
        }

        // Send a web request to download the image
        using (UnityWebRequest request = UnityWebRequestTexture.GetTexture(resolvedUrl))
        {
            yield return request.SendWebRequest();

            // Check for network errors
            if (request.result != UnityWebRequest.Result.Success)
            {
                Debug.LogError("Error downloading QR image: " + request.error);
                SafeShowToast("QR image could not be loaded. Please try again.");
                yield break;
            }

            // Get the downloaded texture
            Texture2D texture = DownloadHandlerTexture.GetContent(request);

            // Convert Texture2D to Sprite
            Sprite sprite = Sprite.Create(
                texture,
                new Rect(0, 0, texture.width, texture.height),
                Vector2.zero
            );

            // Assign the sprite to the Image component
            qr_code_image.sprite = sprite;
            Debug.Log("QR code updated successfully.");
        }
    }

    private bool TryGetValidQrUrl(string qrImageUrl, out string resolvedUrl)
    {
        resolvedUrl = null;
        if (string.IsNullOrWhiteSpace(qrImageUrl))
            return false;

        string trimmed = qrImageUrl.Trim();
        if (trimmed.IndexOf("access denied", StringComparison.OrdinalIgnoreCase) >= 0)
            return false;

        if (trimmed.StartsWith("//", StringComparison.Ordinal))
            trimmed = "https:" + trimmed;

        Uri absoluteUri;
        if (Uri.TryCreate(trimmed, UriKind.Absolute, out absoluteUri)
            && (absoluteUri.Scheme == Uri.UriSchemeHttp || absoluteUri.Scheme == Uri.UriSchemeHttps))
        {
            resolvedUrl = absoluteUri.AbsoluteUri;
            return true;
        }

        if (!string.IsNullOrWhiteSpace(Configuration.BaseUrl)
            && Uri.TryCreate(Configuration.BaseUrl, UriKind.Absolute, out Uri baseUri)
            && Uri.TryCreate(baseUri, trimmed, out absoluteUri)
            && (absoluteUri.Scheme == Uri.UriSchemeHttp || absoluteUri.Scheme == Uri.UriSchemeHttps))
        {
            resolvedUrl = absoluteUri.AbsoluteUri;
            return true;
        }

        return false;
    }

    private static void SafeShowToast(string message)
    {
        if (LoaderUtil.instance != null)
            LoaderUtil.instance.ShowToast(message);
        else
            CommonUtil.ShowToast(message);
    }

    // public async Task DownloadQRAsync(string qrImageUrl)
    // {
    //     try
    //     {
    //         HttpWebRequest request = (HttpWebRequest)WebRequest.Create(qrImageUrl);
    //         request.Method = "GET";

    //         using (WebResponse response = await request.GetResponseAsync())
    //         using (Stream stream = response.GetResponseStream())
    //         {
    //             if (stream != null)
    //             {
    //                 // Load the image from the stream into a Texture2D
    //                 using (MemoryStream memoryStream = new MemoryStream())
    //                 {
    //                     await stream.CopyToAsync(memoryStream);
    //                     memoryStream.Seek(0, SeekOrigin.Begin);

    //                     // Create Texture2D from the downloaded data
    //                     byte[] imageData = memoryStream.ToArray();
    //                     Texture2D texture = new Texture2D(2, 2);
    //                     texture.LoadImage(imageData);

    //                     // Convert Texture2D to Sprite
    //                     Sprite sprite = Sprite.Create(
    //                         texture,
    //                         new Rect(0, 0, texture.width, texture.height),
    //                         Vector2.zero
    //                     );

    //                     // Assign the sprite to the Image component
    //                     Debug.Log("Update QR Successfuly..");
    //                     qr_code_image.sprite = sprite;
    //                 }
    //             }
    //         }
    //     }
    //     catch (Exception ex)
    //     {
    //         Debug.LogError("Error downloading QR image: " + ex.Message);
    //     }
    // }

    public void OnUpdateScreenShotButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery(target, manual_ss_img, manual_ss_logo);
    }

    public async Task UpdateScreenShot(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    public async void SubmitManualPayment()
    {
        if (_isManualPaymentSubmitting)
        {
            CommonUtil.ShowStyledMessage(
                "Your manual deposit request is already being submitted. Please wait for the response.",
                "Request In Progress",
                isError: true
            );
            return;
        }

        if (string.IsNullOrEmpty(utr_inputfield.text))
        {
            CommonUtil.ShowStyledMessage("Please enter UTR / Transaction ID before submitting.", "Missing UTR", isError: true);
            return;
        }

        if (string.IsNullOrEmpty(SpriteManager.Instance.base64forimgmanualss))
        {
            CommonUtil.ShowStyledMessage("Please upload a screenshot of your payment before submitting.", "Screenshot Required", isError: true);
            return;
        }

        await Manual_Payment_API();
    }

    #endregion

    #region Transaction History

    public async void ClickPurchaseTransactionsButton()
    {
        foreach (var obj in transaction_panel_obj) obj.SetActive(true);
        foreach (var obj in add_cash_panel_obj)    obj.SetActive(false);
        StyleTab(selectedRecentTransaction, active: true);
        StyleTab(selectedAddcash,           active: false);
        await PurchaseHistoryAPI();
    }

    public void ShowTransactions(PurchaseHistoryData purchaseHistoryData)
    {
        CommonUtil.CheckLog(
            "RES_check + transactions count " + purchaseHistoryData.purchase_history.Count
        );
        if (purchaseHistoryData.purchase_history.Count > 0)
        {
            instantiatedHistory.ForEach(x => Destroy(x));
            instantiatedHistory.Clear();

            for (int i = 0; i < purchaseHistoryData.purchase_history.Count; i++)
            {
                var purchase = purchaseHistoryData.purchase_history[i];

                /*  if (!instantiatedHistory.ContainsKey(purchase.id))
                 { */
                // Instantiate the prefab and set its values
                GameObject go = Instantiate(transaction_prefab, transaction_parent);
                TransactionUI historyUI = go.GetComponent<TransactionUI>();

                historyUI.id.text = purchase.id;
                historyUI.pricce.text = purchase.price;

                historyUI.date.text = historyUI.FormatDateTime(purchase.added_date);

                if (purchase.status == "0")
                {
                    //pending
                    historyUI.status.text = "Pending";
                }
                else if (purchase.status == "1")
                {
                    historyUI.status.text = "Success";
                    //success
                }
                else
                {
                    historyUI.status.text = "Rejected";
                    //rejected
                }

                // Add the instantiated object to the dictionary
                instantiatedHistory.Add(go); // Store the GameObject in the dictionary
                //}
                /*     else
                        {
                            CommonUtil.CheckLog(transaction_parent.childCount.ToString());
                        } */
            }
        }
    }

    #endregion

    #region  API

    public async Task AvailableChips()
    {
        string Url = Configuration.PlanChips;
        CommonUtil.CheckLog("RES_Check + API-Call + AvailableChips");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        PlanDetailsWrapper details = new PlanDetailsWrapper();
        details = await APIManager.Instance.Post<PlanDetailsWrapper>(Url, formData);

        if (details == null)
        {
            CommonUtil.CheckLog("Add Cash API returned null response.");
            return;
        }

        ShowChips(details);
    }

    public async Task PlaceOrderAPI(string plan_id, string amount)
    {
        if (amount == "0")
        {
            CommonUtil.CheckLog("PAY_TRACE PlaceOrderAPI blocked: zero amount");
            CommonUtil.ShowToast("Please Enter Amount Greater than 0");
            return;
        }
        string Url = Configuration.UpiGateway;
        CommonUtil.CheckLog("RES_Check + API-Call + PlaceOrder " + plan_id + " , " + amount);
        CommonUtil.CheckLog("PAY_TRACE PlaceOrderAPI request url=" + Url + " plan_id=" + (plan_id ?? string.Empty) + " amount=" + amount);

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "plan_id", plan_id },
            { "amount", amount },
        };
        OrderDetails details = new OrderDetails();
        details = await APIManager.Instance.Post<OrderDetails>(Url, formData);
        CommonUtil.CheckLog(
            "PAY_TRACE PlaceOrderAPI response code="
            + details.code
            + " order_id="
            + details.order_id
            + " transaction_id="
            + (details.transaction_id ?? string.Empty)
            + " intentData="
            + (string.IsNullOrWhiteSpace(details.intentData) ? "<empty>" : details.intentData)
        );
        if (details.code == 200)
            OpenAutomaticPaymentUrl(details.intentData, details.order_id, details.transaction_id);
        else
            CommonUtil.ShowToast(details.message);
    }

    public async Task QR_API()
    {
        string Url = Configuration.addcashgetQR;
        CommonUtil.CheckLog("RES_Check + API-Call + QR_API");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        GetQRApiResponse response = await APIManager.Instance.Post<GetQRApiResponse>(Url, formData);

        if (response == null)
        {
            SafeShowToast("QR details are not available right now.");
            return;
        }

        if (response.code == 503 || response.gateway_enabled == false)
        {
            if (manual_panel != null) manual_panel.SetActive(false);
            CommonUtil.ShowStyledMessage("Manual payment is currently unavailable. Please use Automatic Gateway.", "Unavailable", isError: true);
            return;
        }

        // Gateway name
        if (manualGatewayNameText != null)
            manualGatewayNameText.text = string.IsNullOrEmpty(response.gateway_name) ? "UPI / Bank Transfer" : response.gateway_name;

        bool isUpi = string.IsNullOrEmpty(response.type) || response.type == "upi";
        bool hasUpiId = !string.IsNullOrEmpty(response.upi_id);

        // UPI section: show for UPI type, or for Bank type if upi_id is also provided
        if (manualUpiSection != null) manualUpiSection.SetActive(isUpi || hasUpiId);
        if (manualBankSection != null) manualBankSection.SetActive(!isUpi);

        // Always populate UPI ID if available
        if (manualUpiIdText != null)
            manualUpiIdText.text = hasUpiId ? response.upi_id : "—";

        if (!isUpi)
        {
            if (manualBankNameText != null)
                manualBankNameText.text = string.IsNullOrEmpty(response.bank_name) ? "—" : response.bank_name;
            if (manualAccountHolderText != null)
                manualAccountHolderText.text = string.IsNullOrEmpty(response.account_holder) ? "—" : response.account_holder;
            if (manualAccountNumberText != null)
                manualAccountNumberText.text = string.IsNullOrEmpty(response.account_number) ? "—" : response.account_number;
            if (manualIfscCodeText != null)
                manualIfscCodeText.text = string.IsNullOrEmpty(response.ifsc_code) ? "—" : response.ifsc_code;
        }

        if (!string.IsNullOrEmpty(response.qr_image))
            StartCoroutine(DownloadQR(response.qr_image));

        // Update amount display
        if (manual_panel != null)
        {
            var amountVal = manual_panel.transform.Find("Card/Body/AmountRow/AmountValue")
                ?.GetComponent<TMPro.TextMeshProUGUI>();
            if (amountVal != null)
                amountVal.text = "₹ " + manual_amount;
        }

        // Wire copy buttons (safe — buttons already exist in hierarchy)
        WireManualCopyButtons(response, isUpi);
    }

    private void WireManualCopyButtons(GetQRApiResponse resp, bool isUpi)
    {
        if (manual_panel == null) return;
        Transform body = manual_panel.transform.Find("Card/Body");
        if (body == null) return;

        if (isUpi)
        {
            var copyUpi = body.Find("UPISection/UPIRow/CopyUPIButton")?.GetComponent<Button>();
            if (copyUpi != null)
            {
                string upiToCopy = resp.upi_id ?? "";
                copyUpi.onClick.RemoveAllListeners();
                copyUpi.onClick.AddListener(() => {
                    GUIUtility.systemCopyBuffer = upiToCopy;
                    CommonUtil.ShowToast("UPI ID copied!");
                });
            }
        }
        else
        {
            var copyAcc = body.Find("BankSection/AccountNumberTextRow/CopyAccountNumberTextButton")?.GetComponent<Button>();
            if (copyAcc != null)
            {
                string accToCopy = resp.account_number ?? "";
                copyAcc.onClick.RemoveAllListeners();
                copyAcc.onClick.AddListener(() => {
                    GUIUtility.systemCopyBuffer = accToCopy;
                    CommonUtil.ShowToast("Account number copied!");
                });
            }
            var copyIfsc = body.Find("BankSection/IfscCodeTextRow/CopyIfscCodeTextButton")?.GetComponent<Button>();
            if (copyIfsc != null)
            {
                string ifscToCopy = resp.ifsc_code ?? "";
                copyIfsc.onClick.RemoveAllListeners();
                copyIfsc.onClick.AddListener(() => {
                    GUIUtility.systemCopyBuffer = ifscToCopy;
                    CommonUtil.ShowToast("IFSC copied!");
                });
            }
        }
    }

    public async Task Manual_Payment_API()
    {
        string Url = Configuration.addcash;
        CommonUtil.CheckLog("RES_Check + API-Call + Manual_Payment_API");

        _isManualPaymentSubmitting = true;

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "utr", utr_inputfield.text },
            { "price", manual_amount.ToString() },
            { "ss_image", SpriteManager.Instance.base64forimgmanualss },
            { "type", "0" },
        };
        try
        {
            UPISuccessResponse response = new UPISuccessResponse();
            response = await APIManager.Instance.Post<UPISuccessResponse>(Url, formData);

            manual_panel.SetActive(false);

            if (response.code == 200)
            {
                utr_inputfield.text = "";
                manual_ss_logo.SetActive(true);
                manual_ss_img.sprite = UploadScreenshort;
                SpriteManager.Instance.base64forimgmanualss = string.Empty;

                string utr = string.IsNullOrEmpty(response.Utr) ? "" : response.Utr;
                string successMsg = "Your deposit request has been submitted successfully!\n\nUTR: " + utr + "\n\nAdmin will review and approve within 24 hours.\nCheck status in Recent Transactions tab.";
                CommonUtil.ShowStyledMessage(successMsg, "Request Submitted", isError: false);

                // Refresh recent transactions so user can see pending status immediately
                await PurchaseHistoryAPI();
                ClickPurchaseTransactionsButton();
            }
            else
            {
                string errMsg = GetFriendlyTransferApiMessage(response.message, "Submission failed. Please try again.");
                CommonUtil.ShowStyledMessage(errMsg, "Submission Failed", isError: true);
            }
        }
        finally
        {
            _isManualPaymentSubmitting = false;
        }
    }

    public async Task PurchaseHistoryAPI()
    {
        string Url = Configuration.purchasehistory;
        CommonUtil.CheckLog("RES_Check + API-Call + PurchaseHistoryAPI");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        PurchaseHistoryData response = new PurchaseHistoryData();
        response = await APIManager.Instance.Post<PurchaseHistoryData>(Url, formData);
        CommonUtil.CheckLog(response.message);
        Debug.Log("PurchaseHistoryAPI" + response.message);
        if (response.code == 200)
        {
            ShowTransactions(response);
        }
        else
        {
            Debug.Log("RES_CHECK:" + response.message + "CODE:" + response.code);
        }
    }

    private System.Collections.IEnumerator ApplyPopupFallbackSkinDelayed()
    {
        yield return null;
        yield return null;
        ApplyPopupFallbackSkin();
    }

    private void ApplyPopupFallbackSkin()
    {
        Image[] images = GetComponentsInChildren<Image>(true);
        for (int i = 0; i < images.Length; i++)
        {
            Image image = images[i];
            if (image == null) continue;

            // Never recolor images that have a meaningful custom sprite (icons, decorations)
            if (image.sprite != null && !IsDefaultUiSprite(image.sprite)) continue;

            RectTransform rect = image.rectTransform;
            float width  = rect != null ? Mathf.Abs(rect.rect.width)  : 0f;
            float height = rect != null ? Mathf.Abs(rect.rect.height) : 0f;
            string n = image.gameObject.name.ToLowerInvariant();

            bool isLarge = width >= 300f && height >= 80f;
            bool namedSurface =
                n.Contains("bg") || n.Contains("panel") || n.Contains("card")
                || n.Contains("popup") || n.Contains("content") || n.Contains("body")
                || n.Contains("add cash") || n.Contains("addcash")
                || n.Contains("withdraw") || n.Contains("head and tail");

            if (!isLarge && !namedSurface) continue;

            if (n.Contains("head and tail"))   { image.color = new Color32(125, 30, 36, 255); continue; }
            if (n.Contains("card"))            { image.color = new Color32( 48, 10, 18, 245); continue; }
            // Narrow side-panel / header strip
            if (width < 320f && height >= 80f) { image.color = new Color32(118, 18, 28, 255); continue; }

            image.color = new Color32(44, 8, 16, 245);
        }
    }

    private bool IsDefaultUiSprite(Sprite sprite)
    {
        if (sprite == null) return true;
        string s = sprite.name;
        return s == "Background" || s == "UISprite"
            || s == "InputFieldBackground" || s == "UIMask" || s == "Knob";
    }

    #endregion
}
