// Tools > Assign Manual Payment References
// Run ONCE after creating the panel. Runs silently (no dialogs) — check Console for results.

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
        // Find PaymentManager — includes inactive GOs (ADD Chip is inactive by default)
        PaymentManager pm = Object.FindFirstObjectByType<PaymentManager>(FindObjectsInactive.Include);

        if (pm == null)
        {
            // Try to open HomePage scene automatically
            string[] guids = AssetDatabase.FindAssets("HomePage t:Scene");
            string scenePath = null;
            foreach (var guid in guids)
            {
                string p = AssetDatabase.GUIDToAssetPath(guid);
                if (p.Contains("_Project") || p.Contains("Core")) { scenePath = p; break; }
            }
            if (scenePath == null && guids.Length > 0)
                scenePath = AssetDatabase.GUIDToAssetPath(guids[0]);

            if (scenePath == null)
            {
                Debug.LogError("[AssignManualPayment] HomePage scene not found. Open it manually and retry.");
                return;
            }

            UnityEditor.SceneManagement.EditorSceneManager.SaveCurrentModifiedScenesIfUserWantsTo();
            UnityEditor.SceneManagement.EditorSceneManager.OpenScene(scenePath);
            pm = Object.FindFirstObjectByType<PaymentManager>(FindObjectsInactive.Include);
        }

        if (pm == null)
        {
            Debug.LogError("[AssignManualPayment] PaymentManager not found. Is HomePage scene loaded?");
            return;
        }

        // Find ManualPaymentPanel in all canvases (including inactive)
        Transform panelRoot = null;
        Canvas[] allCanvases = Object.FindObjectsByType<Canvas>(
            FindObjectsInactive.Include, FindObjectsSortMode.None);
        foreach (var c in allCanvases)
        {
            panelRoot = FindDeep(c.transform, "ManualPaymentPanel");
            if (panelRoot != null) break;
        }
        // Fallback: search entire scene hierarchy
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
            Debug.LogError("[AssignManualPayment] ManualPaymentPanel not found. Run 'Tools > Create Manual Payment Panel' first.");
            return;
        }

        Debug.Log("[AssignManualPayment] Found panel at: " + GetPath(panelRoot));

        // Body: new layout uses Card/ScrollView/Viewport/Content, old used Card/Body
        Transform body = panelRoot.Find("Card/ScrollView/Viewport/Content")
                      ?? panelRoot.Find("Card/Body");
        if (body == null)
        {
            Debug.LogError("[AssignManualPayment] Content area not found inside ManualPaymentPanel.");
            return;
        }

        // Assign all fields via SerializedObject (supports Undo + marks scene dirty)
        var so = new SerializedObject(pm);

        SetRef(so, "manual_panel",           panelRoot.gameObject);

        var qrGO = body.Find("QRSection/QRCodeImage");
        SetRef(so, "qr_code_image",          qrGO?.GetComponent<Image>());

        SetRef(so, "manualGatewayNameText",
            body.Find("GatewayNameText")?.GetComponent<TextMeshProUGUI>());

        SetRef(so, "manualUpiIdText",
            body.Find("UPISection/UPIRow/UpiIdText")?.GetComponent<TextMeshProUGUI>());

        SetRef(so, "manualBankNameText",
            (body.Find("BankSection/BankNameTextRow/BankNameText")
          ?? body.Find("BankSection/BankNameText_LblRow/BankNameText"))
            ?.GetComponent<TextMeshProUGUI>());

        SetRef(so, "manualAccountHolderText",
            (body.Find("BankSection/AccountHolderTextRow/AccountHolderText")
          ?? body.Find("BankSection/AccountHolderText_LblRow/AccountHolderText"))
            ?.GetComponent<TextMeshProUGUI>());

        SetRef(so, "manualAccountNumberText",
            (body.Find("BankSection/AccountNumberTextRow/AccountNumberText")
          ?? body.Find("BankSection/AccountNumberText_LblRow/AccountNumberText"))
            ?.GetComponent<TextMeshProUGUI>());

        SetRef(so, "manualIfscCodeText",
            (body.Find("BankSection/IfscCodeTextRow/IfscCodeText")
          ?? body.Find("BankSection/IfscCodeText_LblRow/IfscCodeText"))
            ?.GetComponent<TextMeshProUGUI>());

        SetRef(so, "manualUpiSection",  body.Find("UPISection")?.gameObject);
        SetRef(so, "manualBankSection", body.Find("BankSection")?.gameObject);

        SetRef(so, "utr_inputfield",
            body.Find("UTRInputField")?.GetComponent<TMP_InputField>());

        var ssPreview = body.Find("ScreenshotRow/SSPreviewImage");
        SetRef(so, "manual_ss_img",  ssPreview?.GetComponent<Image>());
        SetRef(so, "manual_ss_logo", ssPreview?.Find("UploadIcon")?.gameObject);

        so.ApplyModifiedProperties();

        // Wire buttons
        WireCloseButton(panelRoot, pm);
        WireSubmitButton(body, pm);
        WireUploadButton(body, pm);

        UnityEditor.SceneManagement.EditorSceneManager.MarkSceneDirty(
            UnityEditor.SceneManagement.EditorSceneManager.GetActiveScene());

        // Report results
        var missing = CollectMissing(pm);
        if (missing.Count == 0)
            Debug.Log("[AssignManualPayment] ✓ ALL references assigned successfully! Save scene with Ctrl+S.");
        else
        {
            Debug.LogWarning("[AssignManualPayment] Assigned with missing: " + string.Join(", ", missing));
            foreach (var m in missing)
                Debug.LogWarning("[AssignManualPayment] Still null: " + m);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    static void SetRef(SerializedObject so, string field, Object value)
    {
        var prop = so.FindProperty(field);
        if (prop == null) { Debug.LogWarning("[AssignManualPayment] Field not found: " + field); return; }
        prop.objectReferenceValue = value;
        if (value == null) Debug.LogWarning("[AssignManualPayment] Null value for: " + field);
        else Debug.Log("[AssignManualPayment] ✓ " + field + " → " + value.name);
    }

    static void WireCloseButton(Transform panelRoot, PaymentManager pm)
    {
        var btn = (panelRoot.Find("Card/TitleBar/CloseButton")
                ?? panelRoot.Find("Card/CloseButton"))?.GetComponent<Button>();
        if (btn == null) { Debug.LogWarning("[AssignManualPayment] CloseButton not found"); return; }
        WireButton(btn, pm, "HideManualPanel", 1, "");
        Debug.Log("[AssignManualPayment] ✓ CloseButton → HideManualPanel");
    }

    static void WireSubmitButton(Transform body, PaymentManager pm)
    {
        var btn = body.Find("SubmitButton")?.GetComponent<Button>();
        if (btn == null) { Debug.LogWarning("[AssignManualPayment] SubmitButton not found"); return; }
        WireButton(btn, pm, "SubmitManualPayment", 1, "");
        Debug.Log("[AssignManualPayment] ✓ SubmitButton → SubmitManualPayment");
    }

    static void WireUploadButton(Transform body, PaymentManager pm)
    {
        var btn = body.Find("ScreenshotRow/UploadSSButton")?.GetComponent<Button>();
        if (btn == null) { Debug.LogWarning("[AssignManualPayment] UploadSSButton not found"); return; }
        WireButton(btn, pm, "OnUpdateScreenShotButtonClick", 5, "manual_screenshort");
        Debug.Log("[AssignManualPayment] ✓ UploadSSButton → OnUpdateScreenShotButtonClick");
    }

    static void WireButton(Button btn, PaymentManager pm,
        string method, int mode, string strArg)
    {
        var so = new SerializedObject(btn);
        var calls = so.FindProperty("m_OnClick.m_PersistentCalls.m_Calls");
        calls.ClearArray();
        calls.arraySize = 1;
        var call = calls.GetArrayElementAtIndex(0);
        call.FindPropertyRelative("m_Target").objectReferenceValue = pm;
        call.FindPropertyRelative("m_MethodName").stringValue = method;
        call.FindPropertyRelative("m_Mode").intValue = mode;
        call.FindPropertyRelative("m_CallState").intValue = 2;
        if (!string.IsNullOrEmpty(strArg))
            call.FindPropertyRelative("m_Arguments.m_StringArgument").stringValue = strArg;
        so.ApplyModifiedProperties();
    }

    static System.Collections.Generic.List<string> CollectMissing(PaymentManager pm)
    {
        var m = new System.Collections.Generic.List<string>();
        if (pm.manual_panel == null)            m.Add("manual_panel");
        if (pm.qr_code_image == null)           m.Add("qr_code_image");
        if (pm.manualGatewayNameText == null)   m.Add("manualGatewayNameText");
        if (pm.manualUpiIdText == null)         m.Add("manualUpiIdText");
        if (pm.manualBankNameText == null)      m.Add("manualBankNameText");
        if (pm.manualAccountHolderText == null) m.Add("manualAccountHolderText");
        if (pm.manualAccountNumberText == null) m.Add("manualAccountNumberText");
        if (pm.manualIfscCodeText == null)      m.Add("manualIfscCodeText");
        if (pm.manualUpiSection == null)        m.Add("manualUpiSection");
        if (pm.manualBankSection == null)       m.Add("manualBankSection");
        if (pm.utr_inputfield == null)          m.Add("utr_inputfield");
        if (pm.manual_ss_img == null)           m.Add("manual_ss_img");
        if (pm.manual_ss_logo == null)          m.Add("manual_ss_logo");
        return m;
    }

    static string GetPath(Transform t)
    {
        if (t.parent == null) return t.name;
        return GetPath(t.parent) + "/" + t.name;
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
