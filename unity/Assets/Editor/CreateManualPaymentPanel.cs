// Tools > Create Manual Payment Panel
// Recreates the panel with proper mobile-first design.

#if UNITY_EDITOR
using UnityEditor;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

public static class CreateManualPaymentPanel
{
    // ── Colours — same as app popup style ─────────────────────────────────
    static readonly Color32 C_OVERLAY    = new Color32(0,   0,   0,  200);
    static readonly Color32 C_CARD       = new Color32(44,  10,  18, 255);
    static readonly Color32 C_TITLEBAR   = new Color32(118, 18,  28, 255);
    static readonly Color32 C_CLOSE      = new Color32(160, 30,  40, 255);
    static readonly Color32 C_SECTION    = new Color32(62,  14,  24, 255);
    static readonly Color32 C_INPUT      = new Color32(28,   6,  12, 255);
    static readonly Color32 C_SUBMIT     = new Color32(190, 40,  50, 255);
    static readonly Color32 C_COPY       = new Color32(150, 35,  45, 255);
    static readonly Color32 C_OUTLINE    = new Color32(255,186,  92, 100);
    static readonly Color32 C_DIVIDER    = new Color32(255,186,  92,  50);
    static readonly Color32 C_WHITE      = new Color32(255,255, 255, 255);
    static readonly Color32 C_GOLD       = new Color32(255,210, 100, 255);
    static readonly Color32 C_GREY       = new Color32(180,150, 155, 255);
    static readonly Color32 C_QRBG       = new Color32(255,255, 255, 255);

    [MenuItem("Tools/Create Manual Payment Panel")]
    public static void CreatePanel()
    {
        Canvas canvas = Object.FindFirstObjectByType<Canvas>(FindObjectsInactive.Include);
        if (canvas == null)
        {
            EditorUtility.DisplayDialog("Error", "No Canvas found. Open HomePage scene first.", "OK");
            return;
        }

        // Remove old panel
        var old = FindDeep(canvas.transform, "ManualPaymentPanel");
        if (old != null) { Object.DestroyImmediate(old.gameObject); }

        // ── Root — full screen overlay ─────────────────────────────────────
        var root = NewGO("ManualPaymentPanel", canvas.transform);
        Stretch(root); AddImg(root, C_OVERLAY);
        // Block raycasts so background not clickable
        root.AddComponent<GraphicRaycaster>();

        // ── Card — 92% width, tall ─────────────────────────────────────────
        var card = NewGO("Card", root.transform);
        var cardRT = RT(card);
        cardRT.anchorMin = new Vector2(0.04f, 0.04f);
        cardRT.anchorMax = new Vector2(0.96f, 0.96f);
        cardRT.offsetMin = cardRT.offsetMax = Vector2.zero;
        AddImg(card, C_CARD);
        Outline(card, C_OUTLINE, 2f);

        // ── Title Bar ──────────────────────────────────────────────────────
        var titleBar = NewGO("TitleBar", card.transform);
        var tbRT = RT(titleBar);
        tbRT.anchorMin = new Vector2(0,1); tbRT.anchorMax = new Vector2(1,1);
        tbRT.pivot     = new Vector2(0.5f,1);
        tbRT.sizeDelta = new Vector2(0, 80);
        tbRT.anchoredPosition = Vector2.zero;
        AddImg(titleBar, C_TITLEBAR);

        var titleTMP = TMP("TitleText", titleBar.transform,
            "UPI / Bank Transfer", 26, FontStyles.Bold,
            TextAlignmentOptions.MidlineLeft, C_WHITE);
        var tRT = titleTMP.rectTransform;
        tRT.anchorMin = Vector2.zero; tRT.anchorMax = Vector2.one;
        tRT.offsetMin = new Vector2(20,0); tRT.offsetMax = new Vector2(-70,0);

        // Close [X]
        var closeGO = NewGO("CloseButton", titleBar.transform);
        var cRT = RT(closeGO);
        cRT.anchorMin = cRT.anchorMax = new Vector2(1,0.5f);
        cRT.pivot = new Vector2(1,0.5f);
        cRT.sizeDelta = new Vector2(55,55);
        cRT.anchoredPosition = new Vector2(-12,0);
        AddImg(closeGO, C_CLOSE); closeGO.AddComponent<Button>();
        TMP("X", closeGO.transform, "✕", 22, FontStyles.Bold,
            TextAlignmentOptions.Center, C_WHITE, stretch:true);

        // ── Scroll View for body ───────────────────────────────────────────
        var scrollGO = NewGO("ScrollView", card.transform);
        var scrollRT = RT(scrollGO);
        scrollRT.anchorMin = Vector2.zero; scrollRT.anchorMax = Vector2.one;
        scrollRT.offsetMin = new Vector2(0, 0);
        scrollRT.offsetMax = new Vector2(0, -80);
        var scrollRect = scrollGO.AddComponent<ScrollRect>();
        scrollRect.horizontal = false;
        scrollRect.scrollSensitivity = 30f;

        // Viewport
        var viewport = NewGO("Viewport", scrollGO.transform);
        Stretch(viewport); viewport.AddComponent<RectMask2D>();
        var viewportImg = viewport.AddComponent<Image>();
        viewportImg.color = new Color32(0,0,0,0);
        scrollRect.viewport = RT(viewport);

        // Content
        var content = NewGO("Content", viewport.transform);
        var contentRT = RT(content);
        contentRT.anchorMin = new Vector2(0,1);
        contentRT.anchorMax = new Vector2(1,1);
        contentRT.pivot     = new Vector2(0.5f,1);
        contentRT.anchoredPosition = Vector2.zero;
        contentRT.sizeDelta = new Vector2(0, 0);
        var vlg = content.AddComponent<VerticalLayoutGroup>();
        vlg.padding = new RectOffset(18, 18, 18, 24);
        vlg.spacing = 14;
        vlg.childAlignment = TextAnchor.UpperCenter;
        vlg.childForceExpandWidth  = true;
        vlg.childForceExpandHeight = false;
        var csf = content.AddComponent<ContentSizeFitter>();
        csf.verticalFit = ContentSizeFitter.FitMode.PreferredSize;
        scrollRect.content = contentRT;

        // ════════════ CONTENT ITEMS ════════════

        // 1. Gateway name
        var gwName = TMP("GatewayNameText", content.transform,
            "UPI / Bank Transfer", 22, FontStyles.Bold,
            TextAlignmentOptions.Center, C_GOLD);
        LE(gwName.gameObject, 38);

        Divider(content.transform);

        // 2. QR section
        var qrSec = Section("QRSection", content.transform, 280);
        var qrVLG = qrSec.AddComponent<VerticalLayoutGroup>();
        qrVLG.padding = new RectOffset(0,0,14,14);
        qrVLG.spacing = 10;
        qrVLG.childAlignment     = TextAnchor.UpperCenter;
        qrVLG.childForceExpandWidth  = false;
        qrVLG.childForceExpandHeight = false;
        qrSec.AddComponent<ContentSizeFitter>().verticalFit =
            ContentSizeFitter.FitMode.PreferredSize;

        TMP("QRLabel", qrSec.transform,
            "Scan QR Code to Pay", 17, FontStyles.Normal,
            TextAlignmentOptions.Center, C_GREY);

        // QR Image — big, white background
        var qrGO = NewGO("QRCodeImage", qrSec.transform);
        var qrLE = qrGO.AddComponent<LayoutElement>();
        qrLE.preferredWidth  = 220; qrLE.preferredHeight = 220;
        qrLE.minWidth = 220; qrLE.minHeight = 220;
        var qrImg = qrGO.AddComponent<Image>();
        qrImg.color = C_QRBG;
        qrImg.preserveAspect = true;

        // 3. UPI Section
        var upiSec = Section("UPISection", content.transform, 0);
        upiSec.AddComponent<ContentSizeFitter>().verticalFit =
            ContentSizeFitter.FitMode.PreferredSize;
        var upiVLG = upiSec.AddComponent<VerticalLayoutGroup>();
        upiVLG.padding = new RectOffset(16,16,14,14);
        upiVLG.spacing = 10;
        upiVLG.childForceExpandWidth  = true;
        upiVLG.childForceExpandHeight = false;

        TMP("UPILabel", upiSec.transform,
            "UPI ID", 15, FontStyles.Normal,
            TextAlignmentOptions.Left, C_GREY);

        // UPI row: ID + copy
        var upiRow = Row("UPIRow", upiSec.transform, 50);
        var upiIdTMP = TMP("UpiIdText", upiRow.transform,
            "—", 19, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, C_WHITE);
        upiIdTMP.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1;
        CopyBtn("CopyUPIButton", upiRow.transform);

        // 4. Bank Section
        var bankSec = Section("BankSection", content.transform, 0);
        bankSec.AddComponent<ContentSizeFitter>().verticalFit =
            ContentSizeFitter.FitMode.PreferredSize;
        var bankVLG = bankSec.AddComponent<VerticalLayoutGroup>();
        bankVLG.padding = new RectOffset(16,16,14,14);
        bankVLG.spacing = 10;
        bankVLG.childForceExpandWidth  = true;
        bankVLG.childForceExpandHeight = false;

        TMP("BankLabel", bankSec.transform,
            "Bank Transfer Details", 16, FontStyles.Bold,
            TextAlignmentOptions.Left, C_GOLD);

        BankRow(bankSec.transform, "BankNameText",       "Bank Name",       false);
        BankRow(bankSec.transform, "AccountHolderText",  "Account Holder",  false);
        BankRow(bankSec.transform, "AccountNumberText",  "Account Number",  true);
        BankRow(bankSec.transform, "IfscCodeText",       "IFSC Code",       true);

        Divider(content.transform);

        // 5. Amount row
        var amtRow = Row("AmountRow", content.transform, 46);
        TMP("AmountLabel", amtRow.transform,
            "Amount:", 19, FontStyles.Normal, TextAlignmentOptions.MidlineLeft, C_GREY);
        var amtVal = TMP("AmountValue", amtRow.transform,
            "₹ 0", 22, FontStyles.Bold, TextAlignmentOptions.MidlineRight, C_GOLD);
        amtVal.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1;

        // 6. UTR input
        TMP("UTRLabel", content.transform,
            "UTR / Transaction ID  *", 15, FontStyles.Normal,
            TextAlignmentOptions.Left, C_GREY);

        var utrGO = NewGO("UTRInputField", content.transform);
        LE(utrGO, 64);
        AddImg(utrGO, C_INPUT); Outline(utrGO, C_OUTLINE, 1f);
        var utrIF = utrGO.AddComponent<TMP_InputField>();

        var utrTA = NewGO("Text Area", utrGO.transform);
        Stretch(utrTA); utrTA.AddComponent<RectMask2D>();

        var utrPH = NewGO("Placeholder", utrTA.transform);
        Stretch(utrPH);
        var phTMP = utrPH.AddComponent<TextMeshProUGUI>();
        phTMP.text = "Paste UTR / Reference number...";
        phTMP.color = C_GREY; phTMP.fontSize = 17;
        phTMP.margin = new Vector4(14,0,14,0);

        var utrTxt = NewGO("Text", utrTA.transform);
        Stretch(utrTxt);
        var utrTMP = utrTxt.AddComponent<TextMeshProUGUI>();
        utrTMP.color = C_WHITE; utrTMP.fontSize = 17;
        utrTMP.margin = new Vector4(14,0,14,0);

        utrIF.textViewport = RT(utrTA);
        utrIF.textComponent = utrTMP;
        utrIF.placeholder = phTMP;

        // 7. Screenshot upload
        TMP("SSLabel", content.transform,
            "Payment Screenshot  *", 15, FontStyles.Normal,
            TextAlignmentOptions.Left, C_GREY);

        var ssRow = Row("ScreenshotRow", content.transform, 110);
        var ssHL = ssRow.GetComponent<HorizontalLayoutGroup>();
        ssHL.spacing = 14;

        // Preview box
        var ssPreview = NewGO("SSPreviewImage", ssRow.transform);
        var ssLE = ssPreview.AddComponent<LayoutElement>();
        ssLE.minWidth = 100; ssLE.minHeight = 100;
        ssLE.preferredWidth = 100; ssLE.preferredHeight = 100;
        AddImg(ssPreview, C_INPUT); Outline(ssPreview, C_OUTLINE, 1f);
        TMP("UploadIcon", ssPreview.transform,
            "📷\nUpload", 15, FontStyles.Normal,
            TextAlignmentOptions.Center, C_GREY, stretch:true);

        // Upload button
        var upBtn = NewGO("UploadSSButton", ssRow.transform);
        upBtn.AddComponent<LayoutElement>().flexibleWidth = 1;
        AddImg(upBtn, C_SECTION); Outline(upBtn, C_OUTLINE, 1f);
        upBtn.AddComponent<Button>();
        TMP("UploadBtnText", upBtn.transform,
            "Choose Screenshot", 17, FontStyles.Bold,
            TextAlignmentOptions.Center, C_WHITE, stretch:true);

        Divider(content.transform);

        // 8. Submit
        var subBtn = NewGO("SubmitButton", content.transform);
        LE(subBtn, 70);
        AddImg(subBtn, C_SUBMIT); Outline(subBtn, C_OUTLINE, 2f);
        subBtn.AddComponent<Button>();
        TMP("SubmitText", subBtn.transform,
            "Submit Payment", 21, FontStyles.Bold,
            TextAlignmentOptions.Center, C_WHITE, stretch:true);

        // ── Default off ────────────────────────────────────────────────────
        root.SetActive(false);

        UnityEditor.SceneManagement.EditorSceneManager.MarkSceneDirty(
            UnityEditor.SceneManagement.EditorSceneManager.GetActiveScene());

        Selection.activeGameObject = root;
        EditorUtility.DisplayDialog("Done!",
            "ManualPaymentPanel created!\n\nNow run:\nTools → Assign Manual Payment References\n\nThen Ctrl+S to save scene.", "OK");
    }

    // ─── helpers ───────────────────────────────────────────────────────────

    static GameObject NewGO(string name, Transform parent)
    {
        var go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        return go;
    }

    static RectTransform RT(GameObject go) =>
        go.GetComponent<RectTransform>();

    static void Stretch(GameObject go)
    {
        var rt = RT(go);
        rt.anchorMin = Vector2.zero; rt.anchorMax = Vector2.one;
        rt.offsetMin = rt.offsetMax = Vector2.zero;
    }

    static Image AddImg(GameObject go, Color32 c)
    {
        var img = go.AddComponent<Image>(); img.color = c; return img;
    }

    static void Outline(GameObject go, Color32 c, float dist)
    {
        var o = go.AddComponent<Outline>();
        o.effectColor = c;
        o.effectDistance = new Vector2(dist, -dist);
    }

    static TextMeshProUGUI TMP(string name, Transform parent,
        string text, float size, FontStyles style,
        TextAlignmentOptions align, Color32 color, bool stretch = false)
    {
        var go = NewGO(name, parent);
        if (stretch) Stretch(go);
        var t = go.AddComponent<TextMeshProUGUI>();
        t.text = text; t.fontSize = size; t.fontStyle = style;
        t.alignment = align; t.color = color;
        t.enableWordWrapping = true;
        return t;
    }

    static void LE(GameObject go, float minH = 0, float minW = 0)
    {
        var le = go.AddComponent<LayoutElement>();
        if (minH > 0) le.minHeight = minH;
        if (minW > 0) le.minWidth = minW;
    }

    static void Divider(Transform parent)
    {
        var d = NewGO("Divider", parent); LE(d, 2); AddImg(d, C_DIVIDER);
    }

    static GameObject Section(string name, Transform parent, float minH)
    {
        var go = NewGO(name, parent);
        if (minH > 0) LE(go, minH);
        AddImg(go, C_SECTION);
        return go;
    }

    static GameObject Row(string name, Transform parent, float minH)
    {
        var go = NewGO(name, parent);
        LE(go, minH);
        var hl = go.AddComponent<HorizontalLayoutGroup>();
        hl.spacing = 10;
        hl.childAlignment = TextAnchor.MiddleLeft;
        hl.childForceExpandWidth  = false;
        hl.childForceExpandHeight = true;
        return go;
    }

    static void CopyBtn(string name, Transform parent)
    {
        var go = NewGO(name, parent);
        var le = go.AddComponent<LayoutElement>();
        le.minWidth = 80; le.preferredWidth = 80;
        AddImg(go, C_COPY); go.AddComponent<Button>();
        TMP("Label", go.transform, "Copy", 15, FontStyles.Bold,
            TextAlignmentOptions.Center, C_WHITE, stretch:true);
    }

    // Label + value row, optional copy button
    static void BankRow(Transform parent, string valueName,
        string label, bool withCopy)
    {
        var row = NewGO(valueName + "Row", parent);
        LE(row, 42);
        var hl = row.AddComponent<HorizontalLayoutGroup>();
        hl.spacing = 8;
        hl.childAlignment = TextAnchor.MiddleLeft;
        hl.childForceExpandWidth  = false;
        hl.childForceExpandHeight = true;

        var lbl = TMP(valueName + "_Lbl", row.transform,
            label + ":", 15, FontStyles.Normal,
            TextAlignmentOptions.MidlineLeft, C_GREY);
        lbl.gameObject.AddComponent<LayoutElement>().minWidth = 160;

        var val = TMP(valueName, row.transform,
            "—", 17, FontStyles.Bold,
            TextAlignmentOptions.MidlineLeft, C_WHITE);
        val.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1;

        if (withCopy) CopyBtn("Copy" + valueName + "Btn", row.transform);
    }

    static Transform FindDeep(Transform t, string name)
    {
        if (t.name == name) return t;
        foreach (Transform c in t)
        { var f = FindDeep(c, name); if (f != null) return f; }
        return null;
    }
}
#endif
