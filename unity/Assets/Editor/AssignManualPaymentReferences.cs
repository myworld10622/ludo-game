// Tools > Assign Manual Payment References
// Run this ONCE after creating the panel — auto-wires all Inspector fields on PaymentManager.

#if UNITY_EDITOR
using UnityEditor;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

public static class AssignManualPaymentReferences
{
    [MenuItem("Tools/Assign Manual Payment References")]
    public static void Assign()
    {
        // ── Auto-open HomePage scene if PaymentManager not found ─────────────
        // FindObjectsInactive.Include — finds even inactive GameObjects (ADD Chip is inactive by default)
        PaymentManager pm = Object.FindFirstObjectByType<PaymentManager>(FindObjectsInactive.Include);
        if (pm == null)
        {
            string[] guids = AssetDatabase.FindAssets("HomePage t:Scene");
            string scenePath = null;
            foreach (var guid in guids)
            {
                string p = AssetDatabase.GUIDToAssetPath(guid);
                if (p.Contains("_Project") || p.Contains("Core"))
                { scenePath = p; break; }
            }
            if (scenePath == null && guids.Length > 0)
                scenePath = AssetDatabase.GUIDToAssetPath(guids[0]);

            if (scenePath == null)
            {
                EditorUtility.DisplayDialog("Error",
                    "HomePage scene not found in project!\nPlease open the scene manually and retry.", "OK");
                return;
            }

            bool save = EditorUtility.DisplayDialog("Open Scene?",
                "PaymentManager is in:\n" + scenePath +
                "\n\nOpen it now? (Unsaved changes in current scene will be prompted to save.)", "Open", "Cancel");
            if (!save) return;

            UnityEditor.SceneManagement.EditorSceneManager.SaveCurrentModifiedScenesIfUserWantsTo();
            UnityEditor.SceneManagement.EditorSceneManager.OpenScene(scenePath);
            pm = Object.FindFirstObjectByType<PaymentManager>(FindObjectsInactive.Include);
        }

        if (pm == null)
        {
            EditorUtility.DisplayDialog("Error",
                "PaymentManager still not found after opening HomePage scene.\nCheck the scene is correct.", "OK");
            return;
        }

        // ── Find panel root — search ALL objects including inactive ───────────
        Transform panelRoot = null;
        // Search via canvas children (panel is child of canvas)
        Canvas[] allCanvases = Object.FindObjectsByType<Canvas>(
            FindObjectsInactive.Include, FindObjectsSortMode.None);
        foreach (var c in allCanvases)
        {
            panelRoot = FindDeep(c.transform, "ManualPaymentPanel");
            if (panelRoot != null) break;
        }
        // Fallback: search entire scene
        if (panelRoot == null)
        {
            foreach (var root in UnityEngine.SceneManagement.SceneManager
                         .GetActiveScene().GetRootGameObjects())
            {
                panelRoot = FindDeep(root.transform, "ManualPaymentPanel");
                if (panelRoot != null) break;
            }
        }

        if (panelRoot == null)
        {
            EditorUtility.DisplayDialog("Error",
                "ManualPaymentPanel not found!\nRun 'Tools > Create Manual Payment Panel' first.", "OK");
            return;
        }

        // Body can be Card/Body (old) or Card/ScrollView/Viewport/Content (new)
        Transform body = panelRoot.Find("Card/ScrollView/Viewport/Content")
                      ?? panelRoot.Find("Card/Body");
        if (body == null)
        {
            EditorUtility.DisplayDialog("Error",
                "Content area not found inside ManualPaymentPanel.\nTry recreating the panel.", "OK");
            return;
        }

        // ── Use SerializedObject so Undo works and Unity marks scene dirty ───
        var so = new SerializedObject(pm);

        // manual_panel
        SetRef(so, "manual_panel", panelRoot.gameObject);

        // qr_code_image (Image on QRCodeImage)
        var qrGO = body.Find("QRSection/QRCodeImage");
        SetRef(so, "qr_code_image", qrGO?.GetComponent<Image>());

        // manualGatewayNameText
        SetRef(so, "manualGatewayNameText",
            body.Find("GatewayNameText")?.GetComponent<TextMeshProUGUI>());

        // manualUpiIdText
        SetRef(so, "manualUpiIdText",
            body.Find("UPISection/UPIRow/UpiIdText")?.GetComponent<TextMeshProUGUI>());

        // manualBankNameText  (new: BankNameTextRow, old: BankNameTextRow)
        SetRef(so, "manualBankNameText",
            (body.Find("BankSection/BankNameTextRow/BankNameText")
          ?? body.Find("BankSection/BankNameText_LblRow/BankNameText"))
            ?.GetComponent<TextMeshProUGUI>());

        // manualAccountHolderText
        SetRef(so, "manualAccountHolderText",
            (body.Find("BankSection/AccountHolderTextRow/AccountHolderText")
          ?? body.Find("BankSection/AccountHolderText_LblRow/AccountHolderText"))
            ?.GetComponent<TextMeshProUGUI>());

        // manualAccountNumberText
        SetRef(so, "manualAccountNumberText",
            (body.Find("BankSection/AccountNumberTextRow/AccountNumberText")
          ?? body.Find("BankSection/AccountNumberText_LblRow/AccountNumberText"))
            ?.GetComponent<TextMeshProUGUI>());

        // manualIfscCodeText
        SetRef(so, "manualIfscCodeText",
            (body.Find("BankSection/IfscCodeTextRow/IfscCodeText")
          ?? body.Find("BankSection/IfscCodeText_LblRow/IfscCodeText"))
            ?.GetComponent<TextMeshProUGUI>());

        // manualUpiSection
        SetRef(so, "manualUpiSection", body.Find("UPISection")?.gameObject);

        // manualBankSection
        SetRef(so, "manualBankSection", body.Find("BankSection")?.gameObject);

        // utr_inputfield
        SetRef(so, "utr_inputfield",
            body.Find("UTRInputField")?.GetComponent<TMP_InputField>());

        // manual_ss_img (Image on SSPreviewImage)
        var ssPreview = body.Find("ScreenshotRow/SSPreviewImage");
        SetRef(so, "manual_ss_img", ssPreview?.GetComponent<Image>());

        // manual_ss_logo (the UploadIcon GO shown before screenshot is picked)
        SetRef(so, "manual_ss_logo",
            ssPreview?.Find("UploadIcon")?.gameObject);

        so.ApplyModifiedProperties();

        // ── Wire Button onClick events via serialized events ──────────────────
        WireCloseButton(panelRoot, pm);
        WireSubmitButton(body, pm);
        WireUploadButton(body, pm);

        // ── Mark scene dirty ──────────────────────────────────────────────────
        UnityEditor.SceneManagement.EditorSceneManager.MarkSceneDirty(
            UnityEditor.SceneManagement.EditorSceneManager.GetActiveScene());

        // ── Report ────────────────────────────────────────────────────────────
        var missing = CollectMissing(pm, panelRoot, body);
        if (missing.Count == 0)
        {
            EditorUtility.DisplayDialog("Done! ✓",
                "All references assigned successfully!\n\nSave scene with Ctrl+S.", "OK");
            Debug.Log("[AssignManualPayment] All references assigned on " + pm.gameObject.name);
        }
        else
        {
            string msg = "Assigned what was found, but these are still null:\n• " +
                         string.Join("\n• ", missing) +
                         "\n\nCheck Console for details. Save scene with Ctrl+S.";
            EditorUtility.DisplayDialog("Partial ⚠", msg, "OK");
            foreach (var m in missing)
                Debug.LogWarning("[AssignManualPayment] Still null: " + m);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    static void SetRef(SerializedObject so, string fieldName, Object value)
    {
        var prop = so.FindProperty(fieldName);
        if (prop == null)
        {
            Debug.LogWarning("[AssignManualPayment] Field not found on PaymentManager: " + fieldName);
            return;
        }
        prop.objectReferenceValue = value;
        if (value == null)
            Debug.LogWarning("[AssignManualPayment] Value is null for field: " + fieldName);
        else
            Debug.Log("[AssignManualPayment] ✓ " + fieldName + " → " + value.name);
    }

    static void WireCloseButton(Transform panelRoot, PaymentManager pm)
    {
        var closeBtn = (panelRoot.Find("Card/TitleBar/CloseButton")
                     ?? panelRoot.Find("Card/CloseButton"))?.GetComponent<Button>();
        if (closeBtn == null) { Debug.LogWarning("[AssignManualPayment] CloseButton not found"); return; }

        var so = new SerializedObject(closeBtn);
        var onClick = so.FindProperty("m_OnClick.m_PersistentCalls.m_Calls");
        onClick.ClearArray();
        onClick.arraySize = 1;
        var call = onClick.GetArrayElementAtIndex(0);
        call.FindPropertyRelative("m_Target").objectReferenceValue = pm;
        call.FindPropertyRelative("m_MethodName").stringValue = "HideManualPanel";
        call.FindPropertyRelative("m_Mode").intValue = 1; // void
        call.FindPropertyRelative("m_CallState").intValue = 2; // RuntimeOnly
        so.ApplyModifiedProperties();
        Debug.Log("[AssignManualPayment] ✓ CloseButton → HideManualPanel");
    }

    static void WireSubmitButton(Transform body, PaymentManager pm)
    {
        var submitBtn = body.Find("SubmitButton")?.GetComponent<Button>();
        if (submitBtn == null) { Debug.LogWarning("[AssignManualPayment] SubmitButton not found"); return; }

        var so = new SerializedObject(submitBtn);
        var onClick = so.FindProperty("m_OnClick.m_PersistentCalls.m_Calls");
        onClick.ClearArray();
        onClick.arraySize = 1;
        var call = onClick.GetArrayElementAtIndex(0);
        call.FindPropertyRelative("m_Target").objectReferenceValue = pm;
        call.FindPropertyRelative("m_MethodName").stringValue = "SubmitManualPayment";
        call.FindPropertyRelative("m_Mode").intValue = 1;
        call.FindPropertyRelative("m_CallState").intValue = 2;
        so.ApplyModifiedProperties();
        Debug.Log("[AssignManualPayment] ✓ SubmitButton → SubmitManualPayment");
    }

    static void WireUploadButton(Transform body, PaymentManager pm)
    {
        var uploadBtn = body.Find("ScreenshotRow/UploadSSButton")?.GetComponent<Button>();
        if (uploadBtn == null) { Debug.LogWarning("[AssignManualPayment] UploadSSButton not found"); return; }

        // PaymentManager.OnUpdateScreenShotButtonClick(string) takes a string arg
        var so = new SerializedObject(uploadBtn);
        var onClick = so.FindProperty("m_OnClick.m_PersistentCalls.m_Calls");
        onClick.ClearArray();
        onClick.arraySize = 1;
        var call = onClick.GetArrayElementAtIndex(0);
        call.FindPropertyRelative("m_Target").objectReferenceValue = pm;
        call.FindPropertyRelative("m_MethodName").stringValue = "OnUpdateScreenShotButtonClick";
        call.FindPropertyRelative("m_Mode").intValue = 5; // string mode
        call.FindPropertyRelative("m_Arguments.m_StringArgument").stringValue = "manual_screenshort";
        call.FindPropertyRelative("m_CallState").intValue = 2; // RuntimeOnly
        so.ApplyModifiedProperties();
        Debug.Log("[AssignManualPayment] ✓ UploadSSButton → PaymentManager.OnUpdateScreenShotButtonClick(\"manual_screenshort\")");
    }

    static System.Collections.Generic.List<string> CollectMissing(
        PaymentManager pm, Transform panelRoot, Transform body)
    {
        var missing = new System.Collections.Generic.List<string>();
        if (pm.manual_panel == null)            missing.Add("manual_panel");
        if (pm.qr_code_image == null)           missing.Add("qr_code_image");
        if (pm.manualGatewayNameText == null)   missing.Add("manualGatewayNameText");
        if (pm.manualUpiIdText == null)         missing.Add("manualUpiIdText");
        if (pm.manualBankNameText == null)      missing.Add("manualBankNameText");
        if (pm.manualAccountHolderText == null) missing.Add("manualAccountHolderText");
        if (pm.manualAccountNumberText == null) missing.Add("manualAccountNumberText");
        if (pm.manualIfscCodeText == null)      missing.Add("manualIfscCodeText");
        if (pm.manualUpiSection == null)        missing.Add("manualUpiSection");
        if (pm.manualBankSection == null)       missing.Add("manualBankSection");
        if (pm.utr_inputfield == null)          missing.Add("utr_inputfield");
        if (pm.manual_ss_img == null)           missing.Add("manual_ss_img");
        if (pm.manual_ss_logo == null)          missing.Add("manual_ss_logo");
        return missing;
    }

    static Transform FindDeep(Transform parent, string name)
    {
        if (parent.name == name) return parent;
        foreach (Transform child in parent)
        {
            var found = FindDeep(child, name);
            if (found != null) return found;
        }
        return null;
    }
}
#endif
