// Tools > Create Manual Payment Panel
// Uses ABSOLUTE pixel positions (same pattern as existing Manual panel in ADD Chip).
// No VerticalLayoutGroup on Content — avoids the layout collapse bug.

#if UNITY_EDITOR
using UnityEditor;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

public static class CreateManualPaymentPanel
{
    static readonly Color32 C_OVERLAY  = new Color32(  0,  0,  0, 200);
    static readonly Color32 C_CARD     = new Color32( 30,  8, 14, 255);
    static readonly Color32 C_TITLEBAR = new Color32(110, 16, 26, 255);
    static readonly Color32 C_CLOSE    = new Color32(155, 28, 38, 255);
    static readonly Color32 C_SECTION  = new Color32( 50, 12, 20, 255);
    static readonly Color32 C_INPUT    = new Color32( 20,  4,  8, 255);
    static readonly Color32 C_SUBMIT   = new Color32(180, 38, 48, 255);
    static readonly Color32 C_COPY     = new Color32(140, 30, 42, 255);
    static readonly Color32 C_DIVIDER  = new Color32(255, 186, 92, 80);
    static readonly Color32 C_WHITE    = new Color32(255, 255, 255, 255);
    static readonly Color32 C_GOLD     = new Color32(255, 210, 100, 255);
    static readonly Color32 C_GREY     = new Color32(170, 140, 148, 255);
    static readonly Color32 C_QRBG     = new Color32(255, 255, 255, 255);
    static readonly Color32 C_TRANS    = new Color32(  0,  0,  0,   0);

    // Cumulative Y offset tracker
    static float _y;

    [MenuItem("Tools/Create Manual Payment Panel")]
    public static void CreatePanel()
    {
        Canvas canvas = Object.FindFirstObjectByType<Canvas>(FindObjectsInactive.Include);
        if (canvas == null) { Debug.LogError("[CreateManualPayment] No Canvas found."); return; }

        var old = FindDeep(canvas.transform, "ManualPaymentPanel");
        if (old != null) Object.DestroyImmediate(old.gameObject);

        // ── Root dark overlay ──────────────────────────────────────────────
        var root = MakeGO("ManualPaymentPanel", canvas.transform);
        Stretch(root); AddImg(root, C_OVERLAY);

        // ── Card: same as existing Manual panel — full width - 50px, tall ─
        var card = MakeGO("Card", root.transform);
        var cardRT = RT(card);
        // Centred vertically, full width -50px (matches existing Manual panel pattern)
        cardRT.anchorMin = new Vector2(0, 0.5f);
        cardRT.anchorMax = new Vector2(1, 0.5f);
        cardRT.pivot = new Vector2(0.5f, 0.5f);
        cardRT.sizeDelta = new Vector2(-50, 1700);
        cardRT.anchoredPosition = Vector2.zero;
        AddImg(card, C_CARD);

        // ── Title Bar — top of card, 100px tall ───────────────────────────
        var tb = MakeGO("TitleBar", card.transform);
        var tbRT = RT(tb);
        tbRT.anchorMin = new Vector2(0, 1); tbRT.anchorMax = new Vector2(1, 1);
        tbRT.pivot = new Vector2(0.5f, 1);
        tbRT.sizeDelta = new Vector2(0, 100);
        tbRT.anchoredPosition = Vector2.zero;
        AddImg(tb, C_TITLEBAR);

        PlaceText("TitleText", tb.transform, "UPI / Bank Transfer",
            34, FontStyles.Bold, TextAlignmentOptions.MidlineLeft, C_WHITE,
            offsetLeft: 24, offsetRight: -100, stretch: true);

        var closeGO = MakeGO("CloseButton", tb.transform);
        var cRT = RT(closeGO);
        cRT.anchorMin = cRT.anchorMax = new Vector2(1, 0.5f);
        cRT.pivot = new Vector2(1, 0.5f);
        cRT.sizeDelta = new Vector2(75, 75);
        cRT.anchoredPosition = new Vector2(-14, 0);
        AddImg(closeGO, C_CLOSE); closeGO.AddComponent<Button>();
        PlaceText("X", closeGO.transform, "✕", 32, FontStyles.Bold,
            TextAlignmentOptions.Center, C_WHITE, stretch: true);

        // ── ScrollView — fills card below title bar ────────────────────────
        var scrollGO = MakeGO("ScrollView", card.transform);
        var sRT = RT(scrollGO);
        sRT.anchorMin = Vector2.zero; sRT.anchorMax = Vector2.one;
        sRT.offsetMin = Vector2.zero; sRT.offsetMax = new Vector2(0, -100);
        var sr = scrollGO.AddComponent<ScrollRect>();
        sr.horizontal = false; sr.scrollSensitivity = 40f;
        sr.movementType = ScrollRect.MovementType.Clamped;
        sr.inertia = true; sr.decelerationRate = 0.135f;

        var vp = MakeGO("Viewport", scrollGO.transform);
        Stretch(vp); vp.AddComponent<RectMask2D>();
        vp.AddComponent<Image>().color = C_TRANS;
        sr.viewport = RT(vp);

        // Content: full width, 1700px tall, anchored top-left
        // ABSOLUTE positioning — NO VerticalLayoutGroup
        var content = MakeGO("Content", vp.transform);
        var cntRT = RT(content);
        cntRT.anchorMin = new Vector2(0, 1);
        cntRT.anchorMax = new Vector2(1, 1);
        cntRT.pivot = new Vector2(0.5f, 1);
        cntRT.anchoredPosition = Vector2.zero;
        cntRT.sizeDelta = new Vector2(0, 1700);
        sr.content = cntRT;

        // ═══════ ITEMS — absolute Y positions from top ═══════
        // All items: anchorMin=(0,1), anchorMax=(1,1), pivot=(0.5,1)
        // anchoredPosition.y = -_y  (negative = below top)
        _y = 20f;

        // Gateway name
        Row("GatewayNameText", content.transform, 55, (row) => {
            FullTMP("GatewayNameText", row.transform,
                "UPI / Bank Transfer", 36, FontStyles.Bold,
                TextAlignmentOptions.Center, C_GOLD);
        });

        HDivider(content.transform);

        // QR Section background
        float qrSecY = _y;
        Row("QRSection", content.transform, 420, (row) => {
            AddImg(row, C_SECTION);
            // QR label — absolute inside QR section
            var lbl = MakeGO("QRLabel", row.transform);
            var lblRT = RT(lbl);
            lblRT.anchorMin = new Vector2(0,1); lblRT.anchorMax = new Vector2(1,1);
            lblRT.pivot = new Vector2(0.5f,1);
            lblRT.sizeDelta = new Vector2(0, 42);
            lblRT.anchoredPosition = new Vector2(0, -16);
            var t = lbl.AddComponent<TextMeshProUGUI>();
            t.text = "Scan QR Code to Pay"; t.fontSize = 28;
            t.alignment = TextAlignmentOptions.Center; t.color = C_GREY;

            // QR image — centred, 310x310
            var qrGO = MakeGO("QRCodeImage", row.transform);
            var qrRT = RT(qrGO);
            qrRT.anchorMin = new Vector2(0.5f,1); qrRT.anchorMax = new Vector2(0.5f,1);
            qrRT.pivot = new Vector2(0.5f,1);
            qrRT.sizeDelta = new Vector2(310, 310);
            qrRT.anchoredPosition = new Vector2(0, -68);
            var qrImg = qrGO.AddComponent<Image>();
            qrImg.color = C_QRBG; qrImg.preserveAspect = true;
        });

        HDivider(content.transform);

        // UPI Section
        Row("UPISection", content.transform, 175, (row) => {
            AddImg(row, C_SECTION);
            // UPI label
            var lbl = MakeGO("UPILabel", row.transform);
            TopItem(RT(lbl), 16, 40);
            var t = lbl.AddComponent<TextMeshProUGUI>();
            t.text = "UPI ID"; t.fontSize = 28; t.fontStyle = FontStyles.Bold;
            t.alignment = TextAlignmentOptions.Left; t.color = C_GREY;
            t.margin = new Vector4(20, 0, 20, 0);
            // UPI row with copy button
            HRow("UPIRow", row.transform, 16+40+8, 80, (hr) => {
                var upiId = MakeGO("UpiIdText", hr.transform);
                var upiRT = RT(upiId);
                upiRT.anchorMin = Vector2.zero; upiRT.anchorMax = new Vector2(1,1);
                upiRT.offsetMin = new Vector2(20, 0); upiRT.offsetMax = new Vector2(-150, 0);
                var u = upiId.AddComponent<TextMeshProUGUI>();
                u.text = "—"; u.fontSize = 28; u.fontStyle = FontStyles.Bold;
                u.alignment = TextAlignmentOptions.MidlineLeft; u.color = C_WHITE;

                var copyBtn = MakeGO("CopyUPIButton", hr.transform);
                var copyRT = RT(copyBtn);
                copyRT.anchorMin = new Vector2(1,0); copyRT.anchorMax = Vector2.one;
                copyRT.offsetMin = new Vector2(-140, 0); copyRT.offsetMax = Vector2.zero;
                AddImg(copyBtn, C_COPY); copyBtn.AddComponent<Button>();
                FullTMP("Label", copyBtn.transform, "Copy", 24, FontStyles.Bold,
                    TextAlignmentOptions.Center, C_WHITE);
            });
        });

        HDivider(content.transform);

        // Bank Section
        Row("BankSection", content.transform, 390, (row) => {
            AddImg(row, C_SECTION);
            // Bank label
            var lbl = MakeGO("BankLabel", row.transform);
            TopItem(RT(lbl), 14, 46);
            var t = lbl.AddComponent<TextMeshProUGUI>();
            t.text = "Bank Transfer Details"; t.fontSize = 28; t.fontStyle = FontStyles.Bold;
            t.alignment = TextAlignmentOptions.Left; t.color = C_GOLD;
            t.margin = new Vector4(20, 0, 20, 0);

            // 4 bank detail rows (each 70px, stacked from y=66)
            BankDetailRow(row.transform, "BankNameText",      "Bank Name",      false, 66);
            BankDetailRow(row.transform, "AccountHolderText", "Account Holder", false, 142);
            BankDetailRow(row.transform, "AccountNumberText", "Account No.",    true,  218);
            BankDetailRow(row.transform, "IfscCodeText",      "IFSC Code",      true,  294);
        });

        HDivider(content.transform);

        // Amount row
        Row("AmountRow", content.transform, 80, (row) => {
            var amtLbl = MakeGO("AmountLabel", row.transform);
            var alRT = RT(amtLbl);
            alRT.anchorMin = Vector2.zero; alRT.anchorMax = Vector2.one;
            alRT.offsetMin = new Vector2(20, 0); alRT.offsetMax = new Vector2(-300, 0);
            var alt = amtLbl.AddComponent<TextMeshProUGUI>();
            alt.text = "Amount:"; alt.fontSize = 30; alt.fontStyle = FontStyles.Bold;
            alt.alignment = TextAlignmentOptions.MidlineLeft; alt.color = C_GREY;

            var amtVal = MakeGO("AmountValue", row.transform);
            var avRT = RT(amtVal);
            avRT.anchorMin = new Vector2(1,0); avRT.anchorMax = Vector2.one;
            avRT.offsetMin = new Vector2(-280, 0); avRT.offsetMax = new Vector2(-20, 0);
            var avt = amtVal.AddComponent<TextMeshProUGUI>();
            avt.text = "₹ 0"; avt.fontSize = 36; avt.fontStyle = FontStyles.Bold;
            avt.alignment = TextAlignmentOptions.MidlineRight; avt.color = C_GOLD;
        });

        HDivider(content.transform);

        // UTR label
        Row("UTRLabel", content.transform, 42, (row) => {
            FullTMP("UTRLabelText", row.transform, "UTR / Transaction ID  *",
                26, FontStyles.Normal, TextAlignmentOptions.MidlineLeft, C_GREY,
                marginLeft: 20);
        });

        // UTR InputField
        Row("UTRInputField", content.transform, 90, (row) => {
            AddImg(row, C_INPUT);
            var utrIF = row.AddComponent<TMP_InputField>();
            var utrTA = MakeGO("Text Area", row.transform);
            Stretch(utrTA); utrTA.AddComponent<RectMask2D>();
            var RT_ta = RT(utrTA);
            RT_ta.offsetMin = new Vector2(16, 0); RT_ta.offsetMax = new Vector2(-16, 0);

            var utrPH = MakeGO("Placeholder", utrTA.transform); Stretch(utrPH);
            var phT = utrPH.AddComponent<TextMeshProUGUI>();
            phT.text = "Paste UTR / Reference number...";
            phT.color = C_GREY; phT.fontSize = 26;
            phT.alignment = TextAlignmentOptions.MidlineLeft;

            var utrTG = MakeGO("Text", utrTA.transform); Stretch(utrTG);
            var utrT = utrTG.AddComponent<TextMeshProUGUI>();
            utrT.color = C_WHITE; utrT.fontSize = 26;
            utrT.alignment = TextAlignmentOptions.MidlineLeft;

            utrIF.textViewport = RT(utrTA); utrIF.textComponent = utrT;
            utrIF.placeholder = phT;
        });

        // Screenshot label
        Row("SSLabel", content.transform, 42, (row) => {
            FullTMP("SSLabelText", row.transform, "Payment Screenshot  *",
                26, FontStyles.Normal, TextAlignmentOptions.MidlineLeft, C_GREY,
                marginLeft: 20);
        });

        // Screenshot row
        Row("ScreenshotRow", content.transform, 140, (row) => {
            var ssPreview = MakeGO("SSPreviewImage", row.transform);
            var spRT = RT(ssPreview);
            spRT.anchorMin = new Vector2(0,0); spRT.anchorMax = new Vector2(0,1);
            spRT.offsetMin = new Vector2(16, 8); spRT.offsetMax = new Vector2(156, -8);
            AddImg(ssPreview, C_INPUT);

            var uploadIcon = MakeGO("UploadIcon", ssPreview.transform);
            Stretch(uploadIcon);
            var uiT = uploadIcon.AddComponent<TextMeshProUGUI>();
            uiT.text = "📷\nUpload"; uiT.color = C_GREY;
            uiT.fontSize = 22; uiT.alignment = TextAlignmentOptions.Center;

            var upBtn = MakeGO("UploadSSButton", row.transform);
            var ubRT = RT(upBtn);
            ubRT.anchorMin = new Vector2(0,0); ubRT.anchorMax = Vector2.one;
            ubRT.offsetMin = new Vector2(176, 10); ubRT.offsetMax = new Vector2(-16, -10);
            AddImg(upBtn, C_SECTION); upBtn.AddComponent<Button>();
            FullTMP("UploadBtnText", upBtn.transform, "Choose\nScreenshot",
                28, FontStyles.Bold, TextAlignmentOptions.Center, C_WHITE);
        });

        HDivider(content.transform);

        // Submit button
        Row("SubmitButton", content.transform, 110, (row) => {
            // Use sizeDelta for horizontal margin — offsetMin/offsetMax would collapse height
            RT(row).sizeDelta = new Vector2(-48, 110);
            AddImg(row, C_SUBMIT); row.AddComponent<Button>();
            FullTMP("SubmitText", row.transform, "Submit Payment",
                36, FontStyles.Bold, TextAlignmentOptions.Center, C_WHITE);
        });

        // Final content height = _y + 30 padding
        cntRT.sizeDelta = new Vector2(0, _y + 30);

        root.SetActive(false);

        UnityEditor.SceneManagement.EditorSceneManager.MarkSceneDirty(
            UnityEditor.SceneManagement.EditorSceneManager.GetActiveScene());
        Selection.activeGameObject = root;
        Debug.Log($"[CreateManualPayment] ✓ Panel created! Content height={_y+30}. Next: Assign References → Ctrl+S");
    }

    // ── Layout helpers ─────────────────────────────────────────────────────

    // Add a full-width row at current _y offset, advance _y
    static void Row(string name, Transform parent, float height,
        System.Action<GameObject> build)
    {
        var go = MakeGO(name, parent);
        var rt = RT(go);
        rt.anchorMin = new Vector2(0, 1); rt.anchorMax = new Vector2(1, 1);
        rt.pivot = new Vector2(0.5f, 1);
        rt.sizeDelta = new Vector2(0, height);
        rt.anchoredPosition = new Vector2(0, -_y);
        build(go);
        _y += height;
    }

    static void HDivider(Transform parent)
    {
        Row("Divider", parent, 3, (go) => AddImg(go, C_DIVIDER));
        _y += 4; // small gap after divider
    }

    // Absolute item within a Row at local Y from top
    static void TopItem(RectTransform rt, float localY, float height)
    {
        rt.anchorMin = new Vector2(0, 1); rt.anchorMax = new Vector2(1, 1);
        rt.pivot = new Vector2(0.5f, 1);
        rt.sizeDelta = new Vector2(0, height);
        rt.anchoredPosition = new Vector2(0, -localY);
    }

    // HRow: a sub-row inside a section at fixed localY
    static void HRow(string name, Transform parent, float localY, float height,
        System.Action<GameObject> build)
    {
        var go = MakeGO(name, parent);
        var rt = RT(go);
        rt.anchorMin = new Vector2(0, 1); rt.anchorMax = new Vector2(1, 1);
        rt.pivot = new Vector2(0.5f, 1);
        rt.sizeDelta = new Vector2(0, height);
        rt.anchoredPosition = new Vector2(0, -localY);
        build(go);
    }

    // A bank detail row inside BankSection at fixed localY
    static void BankDetailRow(Transform parent, string valueName,
        string label, bool withCopy, float localY)
    {
        var row = MakeGO(valueName + "TextRow", parent);
        var rt = RT(row);
        rt.anchorMin = new Vector2(0, 1); rt.anchorMax = new Vector2(1, 1);
        rt.pivot = new Vector2(0.5f, 1);
        rt.sizeDelta = new Vector2(0, 70);
        rt.anchoredPosition = new Vector2(0, -localY);

        // Label
        var lbl = MakeGO(valueName + "_Lbl", row.transform);
        var lblRT = RT(lbl);
        lblRT.anchorMin = new Vector2(0,0); lblRT.anchorMax = new Vector2(0,1);
        lblRT.pivot = new Vector2(0,0.5f);
        lblRT.offsetMin = new Vector2(20, 0); lblRT.offsetMax = new Vector2(240, 0);
        var lt = lbl.AddComponent<TextMeshProUGUI>();
        lt.text = label + ":"; lt.fontSize = 24;
        lt.alignment = TextAlignmentOptions.MidlineLeft; lt.color = C_GREY;

        // Value
        float copyW = withCopy ? 125f : 0f;
        var val = MakeGO(valueName, row.transform);
        var valRT = RT(val);
        valRT.anchorMin = new Vector2(0,0); valRT.anchorMax = Vector2.one;
        valRT.offsetMin = new Vector2(244, 0);
        valRT.offsetMax = new Vector2(-(copyW + (withCopy ? 8 : 16)), 0);
        var vt = val.AddComponent<TextMeshProUGUI>();
        vt.text = "—"; vt.fontSize = 26; vt.fontStyle = FontStyles.Bold;
        vt.alignment = TextAlignmentOptions.MidlineLeft; vt.color = C_WHITE;

        if (withCopy)
        {
            var copyBtn = MakeGO("Copy" + valueName + "Btn", row.transform);
            var cpRT = RT(copyBtn);
            cpRT.anchorMin = new Vector2(1,0); cpRT.anchorMax = Vector2.one;
            cpRT.offsetMin = new Vector2(-133, 8); cpRT.offsetMax = new Vector2(-8, -8);
            AddImg(copyBtn, C_COPY); copyBtn.AddComponent<Button>();
            FullTMP("Label", copyBtn.transform, "Copy", 22, FontStyles.Bold,
                TextAlignmentOptions.Center, C_WHITE);
        }
    }

    static TextMeshProUGUI FullTMP(string name, Transform parent,
        string text, float size, FontStyles style,
        TextAlignmentOptions align, Color32 color,
        float marginLeft = 0, bool stretch = false)
    {
        var go = MakeGO(name, parent);
        if (stretch || marginLeft == 0) Stretch(go);
        var t = go.AddComponent<TextMeshProUGUI>();
        t.text = text; t.fontSize = size; t.fontStyle = style;
        t.alignment = align; t.color = color;
        t.enableWordWrapping = true;
        if (marginLeft > 0) t.margin = new Vector4(marginLeft, 0, 0, 0);
        return t;
    }

    static void PlaceText(string name, Transform parent,
        string text, float size, FontStyles style,
        TextAlignmentOptions align, Color32 color,
        float offsetLeft = 0, float offsetRight = 0, bool stretch = false)
    {
        var go = MakeGO(name, parent);
        if (stretch) { Stretch(go); RT(go).offsetMin = new Vector2(offsetLeft, 0); RT(go).offsetMax = new Vector2(offsetRight, 0); }
        var t = go.AddComponent<TextMeshProUGUI>();
        t.text = text; t.fontSize = size; t.fontStyle = style;
        t.alignment = align; t.color = color;
    }

    static GameObject MakeGO(string name, Transform parent)
    {
        var go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        return go;
    }

    static RectTransform RT(GameObject go) => go.GetComponent<RectTransform>();

    static void Stretch(GameObject go)
    {
        var rt = RT(go);
        rt.anchorMin = Vector2.zero; rt.anchorMax = Vector2.one;
        rt.offsetMin = rt.offsetMax = Vector2.zero;
    }

    static Image AddImg(GameObject go, Color32 c)
    { var img = go.AddComponent<Image>(); img.color = c; return img; }

    static Transform FindDeep(Transform t, string name)
    {
        if (t.name == name) return t;
        foreach (Transform c in t) { var f = FindDeep(c, name); if (f != null) return f; }
        return null;
    }
}
#endif
