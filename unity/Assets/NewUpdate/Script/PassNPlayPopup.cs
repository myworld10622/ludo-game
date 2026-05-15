using System.Text.RegularExpressions;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

/// <summary>
/// Index values:
///   0 = Pass N Play
///   1 = Private Table — JOIN view  (enter code to join)
///   2 = Private Table — WAITING    (table created / joined, showing code)
///   3 = Private Table — ERROR      (error message + OK)
///   4 = Private Table — CREATE view (set fee + choose player count)
/// </summary>
public class PassNPlayPopup : MonoBehaviour
{
    public Text TxtTitle;
    public Text TxtMessage;
    public GameObject ObjePrivateTable;

    internal static int Index = 0;

    // Waiting state data (Index == 2)
    internal static string WaitingCode = "";
    internal static string WaitingPrizeInfo = "";
    internal static int WaitingCurrentPlayers = 1;
    internal static int WaitingMaxPlayers = 2;
    internal static bool WaitingIsCreator = true;

    // Error state data (Index == 3)
    internal static string ErrorTitle = "Error";
    internal static string ErrorMessage = "";

    private void OnEnable()
    {
        switch (Index)
        {
            case 1: // JOIN view
                TxtTitle.text = "Private Table";
                TxtMessage.text = "Enter a 6-character code to join a table.";
                ObjePrivateTable.SetActive(true);
                SetChildActive("TxtFee",  false);
                SetChildActive("TxtCode", true);
                ClearInput("TxtCode");
                TryPasteCodeFromClipboard("TxtCode");  // auto-detect code from copied share message
                SetChildActive("2PlayersButton", true);
                SetChildActive("3PlayersButton", true);
                SetChildActive("4PlayersButton", false);
                SetButtonText("2PlayersButton", "JOIN TABLE");
                SetButtonText("3PlayersButton", "CREATE TABLE");
                SetCancelButtonText("Cancel");
                break;

            case 2: // WAITING view
                TxtTitle.text = WaitingIsCreator ? "Table Created!" : "Table Joined!";
                RefreshWaitingMessage();
                ObjePrivateTable.SetActive(false);
                SetChildActive("2PlayersButton", false);
                SetChildActive("3PlayersButton", false);
                SetChildActive("4PlayersButton", false);
                SetCancelButtonText(WaitingIsCreator ? "📋 Copy Code & Share" : "Close");
                if (WaitingIsCreator) CopyToClipboard(BuildShareMessage(WaitingCode));
                // Make the code text tappable — tap it to copy just the bare code
                MakeTextTappable(TxtMessage, () =>
                {
                    CopyToClipboard(WaitingCode);
                    if (TxtMessage != null) TxtMessage.text = "✅ Code copied!\n" + TxtMessage.text;
                });
                break;

            case 3: // ERROR view
                TxtTitle.text = ErrorTitle;
                TxtMessage.text = ErrorMessage;
                ObjePrivateTable.SetActive(false);
                SetChildActive("2PlayersButton", false);
                SetChildActive("3PlayersButton", false);
                SetChildActive("4PlayersButton", false);
                SetCancelButtonText("OK");
                break;

            case 4: // CREATE view
                TxtTitle.text = "Create Table";
                TxtMessage.text = "Set entry fee (0 = free) then select player count.";
                ObjePrivateTable.SetActive(true);
                SetChildActive("TxtFee",  true);
                SetChildActive("TxtCode", false);
                ClearInput("TxtFee");
                SetChildActive("2PlayersButton", true);
                SetChildActive("3PlayersButton", true);
                SetChildActive("4PlayersButton", true);
                SetButtonText("2PlayersButton", "2 Players");
                SetButtonText("3PlayersButton", "3 Players");
                SetButtonText("4PlayersButton", "4 Players");
                SetCancelButtonText("Back");
                break;

            default: // 0 = Pass N Play
                TxtTitle.text = "Pass N Play";
                TxtMessage.text = "Select how many players will join this Pass N Play match.";
                ObjePrivateTable.SetActive(false);
                SetChildActive("2PlayersButton", true);
                SetChildActive("3PlayersButton", true);
                SetChildActive("4PlayersButton", true);
                SetButtonText("2PlayersButton", "2 Players");
                SetButtonText("3PlayersButton", "3 Players");
                SetButtonText("4PlayersButton", "4 Players");
                SetCancelButtonText("Cancel");
                break;
        }
    }

    private void OnDisable()
    {
        Index = 0;
    }

    internal void RefreshWaitingMessage()
    {
        if (TxtMessage == null) return;
        TxtMessage.text =
            $"Code:  {WaitingCode}\n" +
            $"{WaitingPrizeInfo}\n" +
            $"Players joined: {WaitingCurrentPlayers}/{WaitingMaxPlayers}\n" +
            (WaitingCurrentPlayers >= WaitingMaxPlayers ? "Starting game..." : "Waiting for players...");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private void SetButtonText(string childName, string text)
    {
        Transform t = FindDeep(transform, childName);
        if (t == null) return;
        Text lbl = t.GetComponentInChildren<Text>();
        if (lbl != null) lbl.text = text;
    }

    private void SetCancelButtonText(string text) => SetButtonText("CancelButton", text);

    private void ClearInput(string childName)
    {
        Transform t = FindDeep(transform, childName);
        if (t == null) return;
        InputField inp = t.GetComponent<InputField>();
        if (inp != null) { inp.text = ""; inp.interactable = true; }
    }

    private void SetChildActive(string childName, bool active)
    {
        Transform t = FindDeep(transform, childName);
        if (t != null) t.gameObject.SetActive(active);
    }

    private static Transform FindDeep(Transform root, string name)
    {
        if (root.name == name) return root;
        for (int i = 0; i < root.childCount; i++)
        {
            Transform f = FindDeep(root.GetChild(i), name);
            if (f != null) return f;
        }
        return null;
    }

    // ── Clipboard: works on Android APK + Editor ─────────────────────────────
    public static void CopyToClipboard(string text)
    {
#if UNITY_ANDROID && !UNITY_EDITOR
        try
        {
            AndroidJavaClass unityPlayer = new AndroidJavaClass("com.unity3d.player.UnityPlayer");
            AndroidJavaObject activity   = unityPlayer.GetStatic<AndroidJavaObject>("currentActivity");
            AndroidJavaObject clipboard  = activity.Call<AndroidJavaObject>("getSystemService", "clipboard");
            AndroidJavaClass  clipClass  = new AndroidJavaClass("android.content.ClipData");
            AndroidJavaObject clip       = clipClass.CallStatic<AndroidJavaObject>("newPlainText", "rox_code", text);
            clipboard.Call("setPrimaryClip", clip);
        }
        catch (System.Exception e)
        {
            Debug.LogWarning("[Clipboard] Android copy failed: " + e.Message);
            GUIUtility.systemCopyBuffer = text;
        }
#else
        GUIUtility.systemCopyBuffer = text;
#endif
    }

    private static string BuildShareMessage(string code)
        => $"Play Rox Ludo with me!\nRoom Code:\n👉 {code}\nPlay at roxludo.com";

    // ── Smart paste: scan clipboard for a 6-char uppercase code ─────────────
    private void TryPasteCodeFromClipboard(string inputChildName)
    {
        try
        {
            string clipboard = GUIUtility.systemCopyBuffer;
            if (string.IsNullOrEmpty(clipboard)) return;

            // Match 6-char alphanumeric — handles "👉 P97X38" or "code: P97X38" etc.
            var match = Regex.Match(clipboard, @"\b([A-Z0-9]{6})\b");
            if (!match.Success)
                match = Regex.Match(clipboard.ToUpper(), @"[A-Z0-9]{6}");
            if (!match.Success) return;

            string code = match.Value.ToUpper();
            Transform t = FindDeep(transform, inputChildName);
            if (t == null) return;
            InputField inp = t.GetComponent<InputField>();
            if (inp != null)
            {
                inp.text = code;
                if (TxtMessage != null)
                    TxtMessage.text = $"Code detected: {code}\nTap JOIN TABLE to continue.";
            }
        }
        catch { }
    }

    // ── Make a Text label tappable (adds EventTrigger at runtime) ────────────
    private static void MakeTextTappable(Text label, UnityEngine.Events.UnityAction onClick)
    {
        if (label == null) return;
        var trigger = label.GetComponent<EventTrigger>()
                      ?? label.gameObject.AddComponent<EventTrigger>();
        trigger.triggers.Clear();
        var entry = new EventTrigger.Entry { eventID = EventTriggerType.PointerClick };
        entry.callback.AddListener(_ => onClick?.Invoke());
        trigger.triggers.Add(entry);
    }
}
