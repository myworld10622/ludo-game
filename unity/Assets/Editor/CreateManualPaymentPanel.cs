// Tools > Create Manual Payment Panel
// Run once from Unity Editor — creates full UI hierarchy in scene, NOT at runtime.
// After running: assign references in PaymentManager Inspector, then customize freely.

#if UNITY_EDITOR
using UnityEditor;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

public static class CreateManualPaymentPanel
{
    // ── Colour palette — same as CommonUtil styled popup ──────────────────────
    static readonly Color32 COL_OVERLAY     = new Color32(8,   6,  10, 210);
    static readonly Color32 COL_CARD_BG     = new Color32(44,  10,  18, 252);
    static readonly Color32 COL_TITLEBAR    = new Color32(118, 18,  28, 255);
    static readonly Color32 COL_CLOSE_BTN   = new Color32(165, 36,  47, 255);
    static readonly Color32 COL_OUTLINE     = new Color32(255,186,  92, 120);
    static readonly Color32 COL_DIVIDER     = new Color32(255,186,  92,  60);
    static readonly Color32 COL_SECTION_BG  = new Color32(60,  12,  22, 200);
    static readonly Color32 COL_COPY_BTN    = new Color32(180, 50,  60, 255);
    static readonly Color32 COL_SUBMIT_BTN  = new Color32(180, 40,  50, 255);
    static readonly Color32 COL_INPUT_BG    = new Color32(30,   8,  14, 220);
    static readonly Color32 COL_WHITE       = new Color32(255,255, 255, 255);
    static readonly Color32 COL_GOLD        = new Color32(255,213, 120, 255);
    static readonly Color32 COL_GREY_TEXT   = new Color32(190,160, 165, 255);
    static readonly Color32 COL_SS_BTN      = new Color32(70,  20,  30, 230);

    [MenuItem("Tools/Create Manual Payment Panel")]
    public static void CreatePanel()
    {
        // ── Find canvas in scene ────────────────────────────────────────────
        Canvas canvas = Object.FindFirstObjectByType<Canvas>();
        if (canvas == null)
        {
            EditorUtility.DisplayDialog("Error", "No Canvas found in scene.\nOpen the HomePage scene first.", "OK");
            return;
        }

        // Remove existing if re-running
        Transform existing = canvas.transform.Find("ManualPaymentPanel");
        if (existing != null)
        {
            Object.DestroyImmediate(existing.gameObject);
            Debug.Log("[ManualPayment] Removed old panel.");
        }

        // ── Root overlay (fullscreen dark) ──────────────────────────────────
        GameObject root = MakeGO("ManualPaymentPanel", canvas.transform);
        SetStretch(root);
        AddImage(root, COL_OVERLAY);

        // ── Card ────────────────────────────────────────────────────────────
        GameObject card = MakeGO("Card", root.transform);
        RectTransform cardRT = card.GetComponent<RectTransform>();
        cardRT.anchorMin = cardRT.anchorMax = cardRT.pivot = new Vector2(0.5f, 0.5f);
        cardRT.sizeDelta = new Vector2(680f, 900f);
        cardRT.anchoredPosition = Vector2.zero;
        var cardImg = card.AddComponent<Image>();
        cardImg.color = COL_CARD_BG;
        var cardOutline = card.AddComponent<Outline>();
        cardOutline.effectColor = COL_OUTLINE;
        cardOutline.effectDistance = new Vector2(2f, -2f);

        // ── Title bar ───────────────────────────────────────────────────────
        GameObject titleBar = MakeGO("TitleBar", card.transform);
        RectTransform tbRT = titleBar.GetComponent<RectTransform>();
        tbRT.anchorMin = new Vector2(0f, 1f); tbRT.anchorMax = new Vector2(1f, 1f);
        tbRT.pivot = new Vector2(0.5f, 1f);
        tbRT.sizeDelta = new Vector2(0f, 76f);
        tbRT.anchoredPosition = Vector2.zero;
        AddImage(titleBar, COL_TITLEBAR);

        // Title text
        var titleTMP = MakeTMP("TitleText", titleBar.transform,
            "UPI / Bank Transfer", 28, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, COL_WHITE);
        SetAnchors(titleTMP.rectTransform, 0f, 0f, 1f, 1f, 28f, 0f, -80f, 0f);

        // Close button  [X]
        GameObject closeBtn = MakeGO("CloseButton", titleBar.transform);
        RectTransform cbRT = closeBtn.GetComponent<RectTransform>();
        cbRT.anchorMin = cbRT.anchorMax = new Vector2(1f, 0.5f);
        cbRT.pivot = new Vector2(1f, 0.5f);
        cbRT.sizeDelta = new Vector2(52f, 52f);
        cbRT.anchoredPosition = new Vector2(-14f, 0f);
        AddImage(closeBtn, COL_CLOSE_BTN);
        closeBtn.AddComponent<Button>();
        MakeTMP("X", closeBtn.transform, "✕", 24, FontStyles.Bold, TextAlignmentOptions.Center, COL_WHITE,
            stretch: true);

        // ── Body scroll area ────────────────────────────────────────────────
        GameObject body = MakeGO("Body", card.transform);
        RectTransform bodyRT = body.GetComponent<RectTransform>();
        bodyRT.anchorMin = new Vector2(0f, 0f); bodyRT.anchorMax = new Vector2(1f, 1f);
        bodyRT.offsetMin = new Vector2(0f, 0f); bodyRT.offsetMax = new Vector2(0f, -76f);

        // Vertical layout for body content
        var bodyVL = body.AddComponent<VerticalLayoutGroup>();
        bodyVL.padding = new RectOffset(24, 24, 18, 18);
        bodyVL.spacing = 14f;
        bodyVL.childAlignment = TextAnchor.UpperCenter;
        bodyVL.childForceExpandWidth = true;
        bodyVL.childForceExpandHeight = false;
        body.AddComponent<ContentSizeFitter>().verticalFit = ContentSizeFitter.FitMode.PreferredSize;

        // ── Gateway name label ──────────────────────────────────────────────
        var gwNameTMP = MakeTMP("GatewayNameText", body.transform,
            "UPI / Bank Transfer", 24, FontStyles.Bold, TextAlignmentOptions.Center, COL_GOLD);
        AddLayoutElement(gwNameTMP.gameObject, minHeight: 34f);

        // ── Divider ─────────────────────────────────────────────────────────
        MakeDivider(body.transform);

        // ── QR Code section ─────────────────────────────────────────────────
        GameObject qrSection = MakeSection("QRSection", body.transform);
        var qrVL = qrSection.AddComponent<VerticalLayoutGroup>();
        qrVL.childAlignment = TextAnchor.UpperCenter;
        qrVL.childForceExpandWidth = false;
        qrVL.childForceExpandHeight = false;
        qrVL.spacing = 10f;
        qrVL.padding = new RectOffset(12, 12, 12, 12);
        qrSection.AddComponent<ContentSizeFitter>().verticalFit = ContentSizeFitter.FitMode.PreferredSize;

        MakeTMP("QRLabel", qrSection.transform, "Scan QR to Pay",
            18, FontStyles.Normal, TextAlignmentOptions.Center, COL_GREY_TEXT);

        // QR image — big and clear
        GameObject qrImageGO = MakeGO("QRCodeImage", qrSection.transform);
        var qrLE = qrImageGO.AddComponent<LayoutElement>();
        qrLE.preferredWidth = 240f; qrLE.preferredHeight = 240f;
        qrLE.minWidth = 240f; qrLE.minHeight = 240f;
        var qrImg = qrImageGO.AddComponent<Image>();
        qrImg.color = Color.white;
        qrImg.preserveAspect = true;

        // ── UPI section ─────────────────────────────────────────────────────
        GameObject upiSection = MakeGO("UPISection", body.transform);
        upiSection.AddComponent<Image>().color = COL_SECTION_BG;
        var upiVL = upiSection.AddComponent<VerticalLayoutGroup>();
        upiVL.padding = new RectOffset(16, 16, 12, 12);
        upiVL.spacing = 8f;
        upiVL.childForceExpandWidth = true;
        upiVL.childForceExpandHeight = false;
        upiSection.AddComponent<ContentSizeFitter>().verticalFit = ContentSizeFitter.FitMode.PreferredSize;

        MakeTMP("UPILabel", upiSection.transform, "UPI ID",
            16, FontStyles.Normal, TextAlignmentOptions.Left, COL_GREY_TEXT);

        // UPI ID row (text + copy button)
        GameObject upiRow = MakeGO("UPIRow", upiSection.transform);
        var upiRowHL = upiRow.AddComponent<HorizontalLayoutGroup>();
        upiRowHL.spacing = 10f;
        upiRowHL.childAlignment = TextAnchor.MiddleLeft;
        upiRowHL.childForceExpandWidth = false;
        upiRowHL.childForceExpandHeight = false;
        upiRow.AddComponent<ContentSizeFitter>().verticalFit = ContentSizeFitter.FitMode.PreferredSize;
        AddLayoutElement(upiRow, minHeight: 40f);

        var upiIdTMP = MakeTMP("UpiIdText", upiRow.transform,
            "—", 20, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, COL_WHITE);
        upiIdTMP.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1f;

        GameObject upiCopyBtn = MakeCopyButton("CopyUPIButton", upiRow.transform, "Copy");

        // ── Bank section ────────────────────────────────────────────────────
        GameObject bankSection = MakeGO("BankSection", body.transform);
        bankSection.AddComponent<Image>().color = COL_SECTION_BG;
        var bankVL = bankSection.AddComponent<VerticalLayoutGroup>();
        bankVL.padding = new RectOffset(16, 16, 12, 12);
        bankVL.spacing = 8f;
        bankVL.childForceExpandWidth = true;
        bankVL.childForceExpandHeight = false;
        bankSection.AddComponent<ContentSizeFitter>().verticalFit = ContentSizeFitter.FitMode.PreferredSize;

        MakeTMP("BankLabel", bankSection.transform, "Bank Transfer Details",
            16, FontStyles.Bold, TextAlignmentOptions.Left, COL_GOLD);

        var bankNameTMP    = MakeBankRow(bankSection.transform, "BankNameText",    "Bank Name",    "—");
        var acHolderTMP    = MakeBankRow(bankSection.transform, "AccountHolderText","Account Holder","—");
        var acNumberTMP    = MakeBankRowWithCopy(bankSection.transform, "AccountNumberText", "Account Number", "—");
        var ifscTMP        = MakeBankRowWithCopy(bankSection.transform, "IfscCodeText",      "IFSC Code",      "—");

        // ── Divider ─────────────────────────────────────────────────────────
        MakeDivider(body.transform);

        // ── Amount display ──────────────────────────────────────────────────
        GameObject amountRow = MakeGO("AmountRow", body.transform);
        var amountHL = amountRow.AddComponent<HorizontalLayoutGroup>();
        amountHL.childAlignment = TextAnchor.MiddleCenter;
        amountHL.spacing = 10f;
        amountHL.childForceExpandWidth = false;
        amountHL.childForceExpandHeight = false;
        AddLayoutElement(amountRow, minHeight: 44f);

        MakeTMP("AmountLabel", amountRow.transform, "Amount:",
            20, FontStyles.Normal, TextAlignmentOptions.MidlineLeft, COL_GREY_TEXT);
        var amountTMP = MakeTMP("AmountValue", amountRow.transform,
            "₹ 0", 24, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, COL_GOLD);
        amountTMP.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1f;

        // ── UTR input ────────────────────────────────────────────────────────
        MakeTMP("UTRLabel", body.transform, "Enter UTR / Transaction ID *",
            16, FontStyles.Normal, TextAlignmentOptions.Left, COL_GREY_TEXT);

        GameObject utrInputGO = MakeGO("UTRInputField", body.transform);
        AddLayoutElement(utrInputGO, minHeight: 60f);
        var utrBG = utrInputGO.AddComponent<Image>();
        utrBG.color = COL_INPUT_BG;
        var utrInput = utrInputGO.AddComponent<TMP_InputField>();

        GameObject utrTextArea = MakeGO("Text Area", utrInputGO.transform);
        SetStretch(utrTextArea);
        utrTextArea.AddComponent<RectMask2D>();

        GameObject utrPlaceholder = MakeGO("Placeholder", utrTextArea.transform);
        SetStretch(utrPlaceholder);
        var utrPlaceholderTMP = utrPlaceholder.AddComponent<TextMeshProUGUI>();
        utrPlaceholderTMP.text = "Paste UTR / Transaction Reference here...";
        utrPlaceholderTMP.color = COL_GREY_TEXT;
        utrPlaceholderTMP.fontSize = 18f;
        utrPlaceholderTMP.margin = new Vector4(14, 0, 14, 0);

        GameObject utrTextGO = MakeGO("Text", utrTextArea.transform);
        SetStretch(utrTextGO);
        var utrTextTMP = utrTextGO.AddComponent<TextMeshProUGUI>();
        utrTextTMP.color = COL_WHITE;
        utrTextTMP.fontSize = 18f;
        utrTextTMP.margin = new Vector4(14, 0, 14, 0);

        utrInput.textViewport = utrTextArea.GetComponent<RectTransform>();
        utrInput.textComponent = utrTextTMP;
        utrInput.placeholder = utrPlaceholderTMP;

        // ── Screenshot upload ────────────────────────────────────────────────
        MakeTMP("SSLabel", body.transform, "Upload Payment Screenshot *",
            16, FontStyles.Normal, TextAlignmentOptions.Left, COL_GREY_TEXT);

        GameObject ssRow = MakeGO("ScreenshotRow", body.transform);
        var ssHL = ssRow.AddComponent<HorizontalLayoutGroup>();
        ssHL.spacing = 14f;
        ssHL.childAlignment = TextAnchor.MiddleLeft;
        ssHL.childForceExpandWidth = false;
        ssHL.childForceExpandHeight = false;
        ssHL.padding = new RectOffset(0, 0, 4, 4);
        AddLayoutElement(ssRow, minHeight: 100f);

        // Screenshot preview image box
        GameObject ssPreview = MakeGO("SSPreviewImage", ssRow.transform);
        var ssPreviewLE = ssPreview.AddComponent<LayoutElement>();
        ssPreviewLE.preferredWidth = 100f; ssPreviewLE.preferredHeight = 100f;
        ssPreviewLE.minWidth = 100f; ssPreviewLE.minHeight = 100f;
        var ssPreviewImg = ssPreview.AddComponent<Image>();
        ssPreviewImg.color = COL_INPUT_BG;
        ssPreview.AddComponent<Outline>().effectColor = COL_OUTLINE;

        // Upload icon text inside preview
        var ssIconTMP = MakeTMP("UploadIcon", ssPreview.transform,
            "📷\nTap to\nUpload", 14, FontStyles.Normal, TextAlignmentOptions.Center, COL_GREY_TEXT,
            stretch: true);

        // Upload button
        GameObject ssUploadBtn = MakeGO("UploadSSButton", ssRow.transform);
        ssUploadBtn.AddComponent<LayoutElement>().flexibleWidth = 1f;
        var ssUploadBtnImg = ssUploadBtn.AddComponent<Image>();
        ssUploadBtnImg.color = COL_SS_BTN;
        ssUploadBtnImg.GetComponent<RectTransform>().sizeDelta = new Vector2(0f, 60f);
        ssUploadBtn.AddComponent<Button>();
        ssUploadBtn.AddComponent<Outline>().effectColor = COL_OUTLINE;
        MakeTMP("UploadBtnText", ssUploadBtn.transform,
            "Choose Screenshot", 18, FontStyles.Bold, TextAlignmentOptions.Center, COL_WHITE,
            stretch: true);

        // ── Submit button ───────────────────────────────────────────────────
        MakeDivider(body.transform);

        GameObject submitBtn = MakeGO("SubmitButton", body.transform);
        AddLayoutElement(submitBtn, minHeight: 68f);
        var submitImg = submitBtn.AddComponent<Image>();
        submitImg.color = COL_SUBMIT_BTN;
        submitBtn.AddComponent<Button>();
        submitBtn.AddComponent<Outline>().effectColor = COL_OUTLINE;
        MakeTMP("SubmitText", submitBtn.transform,
            "Submit Payment", 22, FontStyles.Bold, TextAlignmentOptions.Center, COL_WHITE,
            stretch: true);

        // ── Deactivate by default ────────────────────────────────────────────
        root.SetActive(false);

        // ── Mark scene dirty so it saves ────────────────────────────────────
        UnityEditor.SceneManagement.EditorSceneManager.MarkSceneDirty(
            UnityEditor.SceneManagement.EditorSceneManager.GetActiveScene());

        Debug.Log("[ManualPayment] Panel created! Now:\n" +
            "1. Assign 'ManualPaymentPanel' root to PaymentManager.manual_panel\n" +
            "2. Assign 'QRCodeImage' Image to PaymentManager.qr_code_image\n" +
            "3. Assign 'GatewayNameText' TMP to PaymentManager.manualGatewayNameText\n" +
            "4. Assign 'UpiIdText' TMP to PaymentManager.manualUpiIdText\n" +
            "5. Assign 'BankNameText' TMP to PaymentManager.manualBankNameText\n" +
            "6. Assign 'AccountHolderText' TMP to PaymentManager.manualAccountHolderText\n" +
            "7. Assign 'AccountNumberText' TMP to PaymentManager.manualAccountNumberText\n" +
            "8. Assign 'IfscCodeText' TMP to PaymentManager.manualIfscCodeText\n" +
            "9. Assign 'UPISection' GO to PaymentManager.manualUpiSection\n" +
            "10. Assign 'BankSection' GO to PaymentManager.manualBankSection\n" +
            "11. Assign 'UTRInputField' TMP_InputField to PaymentManager.utr_inputfield\n" +
            "12. Assign 'SSPreviewImage' Image to PaymentManager.manual_ss_img\n" +
            "13. Assign 'UploadIcon' GO to PaymentManager.manual_ss_logo\n" +
            "14. Wire CloseButton.onClick → PaymentManager.HideManualPanel()\n" +
            "15. Wire SubmitButton.onClick → PaymentManager.SubmitManualPayment()\n" +
            "16. Wire UploadSSButton.onClick → SpriteManager screenshot picker\n" +
            "Save scene: Ctrl+S");

        Selection.activeGameObject = root;
        EditorUtility.DisplayDialog("Done!",
            "ManualPaymentPanel created in hierarchy!\n\n" +
            "Check Console for the 16-step wiring guide.\n" +
            "Save scene with Ctrl+S.", "OK");
    }

    // ─────────────────────────── helpers ─────────────────────────────────────

    static GameObject MakeGO(string name, Transform parent)
    {
        var go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        return go;
    }

    static void SetStretch(GameObject go)
    {
        var rt = go.GetComponent<RectTransform>();
        rt.anchorMin = Vector2.zero; rt.anchorMax = Vector2.one;
        rt.offsetMin = rt.offsetMax = Vector2.zero;
    }

    static Image AddImage(GameObject go, Color32 color)
    {
        var img = go.AddComponent<Image>();
        img.color = color;
        return img;
    }

    static void SetAnchors(RectTransform rt,
        float aMinX, float aMinY, float aMaxX, float aMaxY,
        float offMinX = 0, float offMinY = 0, float offMaxX = 0, float offMaxY = 0)
    {
        rt.anchorMin = new Vector2(aMinX, aMinY);
        rt.anchorMax = new Vector2(aMaxX, aMaxY);
        rt.offsetMin = new Vector2(offMinX, offMinY);
        rt.offsetMax = new Vector2(offMaxX, offMaxY);
    }

    static TextMeshProUGUI MakeTMP(string name, Transform parent,
        string text, float size, FontStyles style,
        TextAlignmentOptions align, Color32 color, bool stretch = false)
    {
        var go = MakeGO(name, parent);
        if (stretch) SetStretch(go);
        var tmp = go.AddComponent<TextMeshProUGUI>();
        tmp.text = text;
        tmp.fontSize = size;
        tmp.fontStyle = style;
        tmp.alignment = align;
        tmp.color = color;
        tmp.enableWordWrapping = false;
        return tmp;
    }

    static void AddLayoutElement(GameObject go, float minHeight = 0, float minWidth = 0)
    {
        var le = go.AddComponent<LayoutElement>();
        if (minHeight > 0) le.minHeight = minHeight;
        if (minWidth > 0) le.minWidth = minWidth;
    }

    static void MakeDivider(Transform parent)
    {
        var div = MakeGO("Divider", parent);
        AddLayoutElement(div, minHeight: 1f);
        AddImage(div, COL_DIVIDER);
    }

    static GameObject MakeSection(string name, Transform parent)
    {
        var sec = MakeGO(name, parent);
        sec.AddComponent<Image>().color = COL_SECTION_BG;
        return sec;
    }

    static GameObject MakeCopyButton(string name, Transform parent, string label)
    {
        var btn = MakeGO(name, parent);
        var le = btn.AddComponent<LayoutElement>();
        le.minWidth = 80f; le.minHeight = 36f;
        le.preferredWidth = 80f; le.preferredHeight = 36f;
        AddImage(btn, COL_COPY_BTN);
        btn.AddComponent<Button>();
        MakeTMP("Label", btn.transform, label, 16, FontStyles.Bold,
            TextAlignmentOptions.Center, COL_WHITE, stretch: true);
        return btn;
    }

    // Row: label + value (read-only display)
    static TextMeshProUGUI MakeBankRow(Transform parent, string goName, string label, string val)
    {
        var row = MakeGO(goName + "Row", parent);
        var hl = row.AddComponent<HorizontalLayoutGroup>();
        hl.spacing = 8f;
        hl.childAlignment = TextAnchor.MiddleLeft;
        hl.childForceExpandWidth = false;
        hl.childForceExpandHeight = false;
        AddLayoutElement(row, minHeight: 34f);

        var labelTMP = MakeTMP(goName + "_Label", row.transform,
            label + ":", 16, FontStyles.Normal, TextAlignmentOptions.MidlineLeft, COL_GREY_TEXT);
        labelTMP.gameObject.AddComponent<LayoutElement>().minWidth = 170f;

        var valTMP = MakeTMP(goName, row.transform,
            val, 17, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, COL_WHITE);
        valTMP.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1f;

        return valTMP;
    }

    // Row: label + value + copy button
    static TextMeshProUGUI MakeBankRowWithCopy(Transform parent, string goName, string label, string val)
    {
        var row = MakeGO(goName + "Row", parent);
        var hl = row.AddComponent<HorizontalLayoutGroup>();
        hl.spacing = 8f;
        hl.childAlignment = TextAnchor.MiddleLeft;
        hl.childForceExpandWidth = false;
        hl.childForceExpandHeight = false;
        AddLayoutElement(row, minHeight: 42f);

        var labelTMP = MakeTMP(goName + "_Label", row.transform,
            label + ":", 16, FontStyles.Normal, TextAlignmentOptions.MidlineLeft, COL_GREY_TEXT);
        labelTMP.gameObject.AddComponent<LayoutElement>().minWidth = 170f;

        var valTMP = MakeTMP(goName, row.transform,
            val, 17, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, COL_WHITE);
        valTMP.gameObject.AddComponent<LayoutElement>().flexibleWidth = 1f;

        MakeCopyButton("Copy" + goName + "Button", row.transform, "Copy");

        return valTMP;
    }
}
#endif
