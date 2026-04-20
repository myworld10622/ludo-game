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

    void OnDisable()
    {
        if (_extrasRoot != null) { Destroy(_extrasRoot); _extrasRoot = null; }
        _upiTab = null; _cryptoTab = null;
    }

    async void OnEnable()
    {
#if UNITY_WEBGL
        USDT_AUTO.SetActive(false);
#endif
        ApplyDirectSkin();
        ApplyResponsiveAddCashLayout();
        await AvailableChips();
        DefaultSet();
        ApplyResponsiveAddCashLayout();
    }

    private void ApplyDirectSkin()
    {
        Image rootImg = GetComponent<Image>();
        if (rootImg != null)
        {
            rootImg.sprite = null;
            rootImg.type = Image.Type.Simple;
            rootImg.color = new Color32(44, 8, 16, 245);
        }

        foreach (Image img in GetComponentsInChildren<Image>(true))
        {
            if (img == null) continue;
            // Skip close/X buttons and small icons by name
            string n = img.gameObject.name.ToLowerInvariant();
            if (n.Contains("close") || n.Contains("exit") || n.Contains("btn")
                || n.Contains("button") || n.Contains("icon") || n.Contains("logo")
                || n.Contains("chip") || n.Contains("coin") || n.Contains("toggle")) continue;
            // Skip small images
            RectTransform rt = img.rectTransform;
            float w = rt != null ? Mathf.Abs(rt.rect.width)  : 0f;
            float h = rt != null ? Mathf.Abs(rt.rect.height) : 0f;
            if (w < 100f || h < 100f) continue;

            Color c = img.color;
            if (c.r > 0.85f && c.g > 0.85f && c.b > 0.85f && c.a > 0.5f)
                img.color = new Color32(44, 8, 16, 245);
        }

        if (backgroundImages != null)
            foreach (var img in backgroundImages)
                if (img != null) img.color = new Color32(44, 8, 16, 245);
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
        if (selectedAddcash != null)           selectedAddcash.SetActive(true);
        if (selectedRecentTransaction != null) selectedRecentTransaction.SetActive(false);

        StyleTab(selectedAddcash,           active: true);
        StyleTab(selectedRecentTransaction, active: false);

        ClickAddCashButton();

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

        ApplyResponsiveAddCashLayout();
    }

    #region Add Chips

    public async void ClickAddCashButton()
    {
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

        ApplyResponsiveAddCashLayout();
    }

    private void ApplyResponsiveAddCashLayout()
    {
        RectTransform root = transform as RectTransform;
        Canvas canvas = GetComponentInParent<Canvas>();
        RectTransform canvasRect = canvas != null ? canvas.transform as RectTransform : null;
        Rect bounds = canvasRect != null ? canvasRect.rect : new Rect(0f, 0f, Screen.width, Screen.height);
        bool portrait = bounds.height >= bounds.width;

        if (root != null)
        {
            root.anchorMin = new Vector2(0.5f, 0.5f);
            root.anchorMax = new Vector2(0.5f, 0.5f);
            root.pivot = new Vector2(0.5f, 0.5f);
            root.anchoredPosition = Vector2.zero;
            root.localScale = Vector3.one;
            root.sizeDelta = portrait ? new Vector2(980f, 1420f) : new Vector2(1540f, 900f);
        }

        if (_extrasRoot != null)
        {
            RectTransform extrasRect = _extrasRoot.transform as RectTransform;
            if (extrasRect != null)
            {
                extrasRect.anchorMin = portrait ? new Vector2(0.04f, 0.05f) : new Vector2(0.02f, 0.04f);
                extrasRect.anchorMax = portrait ? new Vector2(0.96f, 0.82f) : new Vector2(0.98f, 0.82f);
                extrasRect.offsetMin = Vector2.zero;
                extrasRect.offsetMax = Vector2.zero;
            }

            VerticalLayoutGroup extrasLayout = _extrasRoot.GetComponent<VerticalLayoutGroup>();
            if (extrasLayout != null)
            {
                extrasLayout.spacing = portrait ? 18f : 14f;
                extrasLayout.padding = portrait ? new RectOffset(18, 18, 18, 18) : new RectOffset(12, 12, 12, 12);
            }
        }

        ApplyAddCashInnerPanelLayout(portrait);
        StylePopupTexts(transform, portrait ? 32 : 26, portrait ? 36 : 30);
        StylePopupInputs(transform, portrait ? 42 : 34, portrait ? 108f : 82f);
        StylePopupButtons(transform, portrait ? 34 : 28, portrait ? 92f : 72f);
        StylePopupScrollRects(transform, portrait ? 920f : 1280f);

        if (root != null)
        {
            Canvas.ForceUpdateCanvases();
            LayoutRebuilder.ForceRebuildLayoutImmediate(root);
        }
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
        buttonsobjs.Add(chipUI.chipButton.gameObject);
        spawned_objects[coin.coin] = go;

        return go;
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

        foreach (var detail in sortedPlanDetails)
        {
            GameObject go = spawned_objects.TryGetValue(detail.coin, out GameObject existingObject)
                ? existingObject
                : CreateNewChip(detail);

            go.SetActive(true);
            ChipUI chipUI = go.GetComponent<ChipUI>();

            chipUI.coinText.text = detail.price;

            if (detail.coin == detail.price)
            {
                chipUI.percentageobj.SetActive(false);
            }
            else
            {
                chipUI.percentageobj.SetActive(true);
                float value = CalculatePercentage(int.Parse(detail.coin), int.Parse(detail.price));
                chipUI.percentage.text = value + "%";
            }

            chipUI.chipButton.onClick.RemoveAllListeners();

            chipUI.chipButton.onClick.AddListener(
                () => PlaceOrder(detail.id, detail.price, detail.coin)
            );

            chipUI.chipButton.onClick.AddListener(() => changebuttonui(chipUI.chipButton));
        }
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
        for (int i = 0; i < buttonsobjs.Count; i++)
        {
            if (buttonsobjs[i] == btn.gameObject)
            {
                Debug.Log("buttonobj " + buttonsobjs[i].gameObject);
                buttonsobjs[i].GetComponent<Image>().color = HexToColor("#5A5A5AFF");
            }
            else
            {
                buttonsobjs[i].GetComponent<Image>().color = HexToColor("#FFFFFFFF");
            }
        }
        if (!buttonsobjs.Contains(btn.gameObject))
        {
            Debug.LogError("The button is NOT in the list!");
        }
        else
        {
            Debug.Log("The button IS in the list.");
        }
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

        // automatic_button.onClick.RemoveAllListeners();
        // automatic_button.onClick.AddListener(async () => await PlaceOrderAPI(id, coin));
    }

    public void OpenPopUP()
    {
        PopUpUtil.ButtonClick(dialogue);
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
                PopUpUtil.ButtonClick(dialogue);
            }
        }
        else
            LoaderUtil.instance.ShowToast("Please enter a valid amount");
    }

    #endregion

    #region Manual Payment

    public async void OpenManual()
    {
        await QR_API();
        manual_panel.SetActive(true);
    }

    // public async void StartDownloadQR(string qr_imag_url)
    // {
    //     await DownloadQRAsync(qr_imag_url);
    // }
    public IEnumerator DownloadQR(string qrImageUrl)
    {
        // Send a web request to download the image
        using (UnityWebRequest request = UnityWebRequestTexture.GetTexture(qrImageUrl))
        {
            yield return request.SendWebRequest();

            // Check for network errors
            if (request.result != UnityWebRequest.Result.Success)
            {
                Debug.LogError("Error downloading QR image: " + request.error);
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

        LoaderUtil.instance.ShowToast(response.message);
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
