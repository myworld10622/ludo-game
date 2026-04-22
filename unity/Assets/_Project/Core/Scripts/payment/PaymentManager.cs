using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Threading.Tasks;
using Mkey;
using TMPro;
using Unity.Burst.Intrinsics;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

[ExecuteAlways]
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
    // payment method: 0 = UPI/Bank, 1 = Crypto
    private int _paymentMethod = 0;
    private GameObject _upiTab, _cryptoTab;
    private GameObject _extrasRoot;   // holds injected UI — destroyed on disable
    private Vector2 _lastLayoutCanvasSize = new Vector2(-1f, -1f);
    private Vector2 _lastLayoutScreenSize = new Vector2(-1f, -1f);
    private ScreenOrientation _lastLayoutOrientation = ScreenOrientation.Unknown;
    private bool _lastLayoutPortrait = true;

    void OnDisable()
    {
        if (_extrasRoot != null) { RemoveGeneratedObject(_extrasRoot); _extrasRoot = null; }
        _upiTab = null; _cryptoTab = null;
    }

    async void OnEnable()
    {
#if UNITY_EDITOR
        if (!Application.isPlaying)
        {
            ApplyDirectSkin();
            ApplyResponsiveAddCashLayout();
            return;
        }
#endif
#if UNITY_WEBGL
        USDT_AUTO.SetActive(false);
#endif
        ApplyDirectSkin();
        ApplyResponsiveAddCashLayout();
        await AvailableChips();
        DefaultSet();
        ApplyResponsiveAddCashLayout();
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

        if (finalpanel != null)
            finalpanel.SetActive(false);

        ApplyResponsiveAddCashLayout();
        BindPresetAmountButtons();
    }

    private void HidePaymentFlowPanels()
    {
        if (dialogue != null)
            dialogue.SetActive(false);
        if (manual_panel != null)
            manual_panel.SetActive(false);

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

    private void ShowPaymentOptionsPanel()
    {
        if (dialogue == null) return;

        HidePaymentFlowPanels();
        dialogue.SetActive(true);
        dialogue.transform.SetAsLastSibling();
        transform.SetAsLastSibling();
        ApplyResponsiveAddCashLayout();
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

        // Always write the active orientation layout. Otherwise a previous
        // landscape rect can remain on the object when the view returns portrait.
        if (root != null)
        {
            Rect bounds = GetLayoutBounds(new Rect(0f, 0f, canvasSize.x, canvasSize.y), portrait);
            float rootWidth = portrait
                ? Mathf.Min(980f, bounds.width * 0.92f)
                : Mathf.Min(1540f, bounds.width * 0.92f);
            float rootHeight = portrait
                ? Mathf.Min(1420f, bounds.height * 0.82f)
                : Mathf.Min(900f, bounds.height * 0.90f);

            root.anchorMin = new Vector2(0.5f, 0.5f);
            root.anchorMax = new Vector2(0.5f, 0.5f);
            root.pivot = new Vector2(0.5f, 0.5f);
            root.anchoredPosition = Vector2.zero;
            root.localScale = Vector3.one;
            root.sizeDelta = new Vector2(rootWidth, rootHeight);

            LayoutAddCashRoot(rootWidth, rootHeight, portrait);
            LayoutPaymentFlowPanels(rootWidth, rootHeight, portrait);
        }

        ApplyInputReadability(transform);
        ApplyCustomAmountInputReadability();
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
        float totalHeight = buttonHeight * 4f + spacing * 3f;
        float firstY = totalHeight * 0.5f - buttonHeight * 0.5f;
        int index = 0;

        for (int i = 0; i < table.childCount; i++)
        {
            RectTransform option = table.GetChild(i) as RectTransform;
            if (option == null || option.name != "unselected") continue;

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
        if (root == null) return;

        foreach (Text text in root.GetComponentsInChildren<Text>(true))
        {
            if (text == null) continue;
            text.fontSize = Mathf.RoundToInt(fontSize);
            text.color = color;
            text.alignment = TextAnchor.MiddleCenter;
            text.horizontalOverflow = noWrap ? HorizontalWrapMode.Overflow : HorizontalWrapMode.Wrap;
            text.verticalOverflow = VerticalWrapMode.Overflow;
            text.resizeTextForBestFit = false;
        }

        foreach (TMP_Text text in root.GetComponentsInChildren<TMP_Text>(true))
        {
            if (text == null) continue;
            text.enableAutoSizing = false;
            text.fontSize = fontSize;
            text.color = color;
            text.alignment = TextAlignmentOptions.Center;
            text.textWrappingMode = noWrap ? TextWrappingModes.NoWrap : TextWrappingModes.Normal;
            text.overflowMode = TextOverflowModes.Overflow;
        }
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
        if (rect == null) return;

        Image image = rect.GetComponent<Image>();
        if (image == null) return;

        bool hasAuthoredSprite = image.sprite != null;
        Sprite popupSprite = !hasAuthoredSprite ? ResolvePopupSprite() : null;
        if (!hasAuthoredSprite && popupSprite != null)
        {
            image.sprite = popupSprite;
            image.color = Color.white;
        }

        image.type = Image.Type.Sliced;
    }

    private void ApplyButtonBackground(RectTransform rect)
    {
        if (rect == null) return;

        Image image = rect.GetComponent<Image>();
        if (image != null)
        {
            Sprite buttonSprite = ResolveButtonSprite();
            if (buttonSprite != null)
                image.sprite = buttonSprite;
            image.type = Image.Type.Sliced;
            image.color = Color.white;
        }
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
        if (chipUI == null) return;

        if (chipUI.coinText != null)
        {
            chipUI.coinText.enableAutoSizing = false;
            chipUI.coinText.fontSize = fontSize;
            chipUI.coinText.textWrappingMode = TextWrappingModes.NoWrap;
            chipUI.coinText.overflowMode = TextOverflowModes.Overflow;
            chipUI.coinText.alignment = TextAlignmentOptions.Center;
            chipUI.coinText.margin = Vector4.zero;
        }

        if (chipUI.percentage != null)
        {
            chipUI.percentage.text = string.Empty;
            chipUI.percentage.gameObject.SetActive(false);
        }

        if (chipUI.percentageobj != null)
        {
            chipUI.percentageobj.SetActive(false);
        }
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
        if (finalpanel == null) return;

        RectTransform panel = finalpanel.transform as RectTransform;
        RectTransform details = panel != null ? panel.parent as RectTransform : null;
        float width = details != null && details.rect.width > 0f ? details.rect.width : 720f;
        Canvas canvas = GetComponentInParent<Canvas>();
        RectTransform canvasRect = canvas != null ? canvas.transform as RectTransform : null;
        bool portrait = canvasRect == null || canvasRect.rect.height >= canvasRect.rect.width;
        float height = portrait ? 220f : 205f;

        SetBottomStretch(panel, width, height, Vector2.zero);
        LayoutConfirmationPanel(panel, width, height, portrait);
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
        if (Application.platform == RuntimePlatform.Android)
        {
            OpenURLInAndroid(url);
        }
        else
        {
            OpenURLInWeb(url);
        }
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

    public async void OpenManual()
    {
        HidePaymentFlowPanels();

        if (manual_panel != null)
        {
            manual_panel.SetActive(true);
            manual_panel.transform.SetAsLastSibling();
        }
        transform.SetAsLastSibling();
        ApplyResponsiveAddCashLayout();
        await QR_API();
        ApplyResponsiveAddCashLayout();
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
        if (string.IsNullOrEmpty(utr_inputfield.text))
        {
            LoaderUtil.instance.ShowToast("Please enter UTR Address");
            return;
        }

        // Check if payment screenshot is missing
        if (string.IsNullOrEmpty(SpriteManager.Instance.base64forimgmanualss))
        {
            LoaderUtil.instance.ShowToast("Please upload the Screen Shot of your payment");
            return;
        }

        // Proceed with API call if all checks are passed
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
            CommonUtil.ShowToast("Please Enter Amount Greater than 0");
            return;
        }
        string Url = Configuration.UpiGateway;
        CommonUtil.CheckLog("RES_Check + API-Call + PlaceOrder " + plan_id + " , " + amount);

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "plan_id", plan_id },
            { "amount", amount },
        };
        OrderDetails details = new OrderDetails();
        details = await APIManager.Instance.Post<OrderDetails>(Url, formData);
        if (details.code == 200)
            OpenURLInBrowser(details.intentData);
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
        GetQRApiResponse response = new GetQRApiResponse();
        response = await APIManager.Instance.Post<GetQRApiResponse>(Url, formData);

        if (response == null)
        {
            SafeShowToast("QR details are not available right now.");
            return;
        }

        Debug.Log(response.message);
        Debug.Log(response.qr_image);
        //StartDownloadQR(response.qr_image);
        StartCoroutine(DownloadQR(response.qr_image));
    }

    public async Task Manual_Payment_API()
    {
        string Url = Configuration.addcash;
        CommonUtil.CheckLog("RES_Check + API-Call + Manual_Payment_API");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "utr", utr_inputfield.text },
            { "price", manual_amount.ToString() },
            { "ss_image", SpriteManager.Instance.base64forimgmanualss },
            { "type", "0" },
        };
        UPISuccessResponse response = new UPISuccessResponse();
        response = await APIManager.Instance.Post<UPISuccessResponse>(Url, formData);

        SafeShowToast(response.message);
        manual_panel.SetActive(false);

        if (response.code == 200)
        {
            utr_inputfield.text = "";
            manual_ss_logo.SetActive(true);

            manual_ss_img.sprite = UploadScreenshort;
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
