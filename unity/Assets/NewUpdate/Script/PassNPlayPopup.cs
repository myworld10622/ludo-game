using System.Text.RegularExpressions;
using System.Runtime.InteropServices;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;
using TouchScreenKeyboardType = UnityEngine.TouchScreenKeyboardType;

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
#if UNITY_WEBGL && !UNITY_EDITOR
    [DllImport("__Internal")] private static extern void RoxCopyTextToClipboard(string text);
    [DllImport("__Internal")] private static extern void RoxPrepareWebGlTextInput(string mode);
#endif

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
                TxtMessage.text = "Enter a 6-digit code to join a table.";
                ObjePrivateTable.SetActive(true);
                SetChildActive("TxtFee",  false);
                SetChildActive("TxtCode", true);
                ClearInput("TxtCode");
                ConfigureInput("TxtCode", InputField.ContentType.IntegerNumber, TouchScreenKeyboardType.NumberPad, 6);
                SetInputPlaceholder("TxtCode", "Please fill 6-digit code here");
                PrepareWebGlTextInput(true);
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
                SetCancelButtonText(WaitingIsCreator ? "Share Invite" : "Close");
                // Make the code text tappable — tap it to copy just the bare code
                MakeTextTappable(TxtMessage, () =>
                {
                    CopyToClipboard(WaitingCode);
                    RefreshWaitingMessage("Code copied");
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
                ConfigureInput("TxtFee", InputField.ContentType.IntegerNumber, TouchScreenKeyboardType.NumberPad, 6);
                PrepareWebGlTextInput(true);
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
        PrepareWebGlTextInput(false);
        Index = 0;
    }

    internal void RefreshWaitingMessage()
    {
        RefreshWaitingMessage(null);
    }

    internal void RefreshWaitingMessage(string statusLine)
    {
        if (TxtMessage == null) return;
        TxtMessage.text =
            $"Room Code\n{WaitingCode}\n" +
            "Tap code to copy\n" +
            $"{WaitingPrizeInfo}\n" +
            $"Players joined: {WaitingCurrentPlayers}/{WaitingMaxPlayers}\n" +
            (!string.IsNullOrEmpty(statusLine) ? $"{statusLine}\n" : "") +
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

    private void ConfigureInput(string childName, InputField.ContentType contentType, TouchScreenKeyboardType keyboardType, int characterLimit)
    {
        Transform t = FindDeep(transform, childName);
        if (t == null) return;

        InputField inp = t.GetComponent<InputField>();
        if (inp == null) return;

        inp.contentType = contentType;
        inp.keyboardType = keyboardType;
        inp.characterLimit = characterLimit;
        inp.lineType = InputField.LineType.SingleLine;
        inp.ForceLabelUpdate();
    }

    private void SetInputPlaceholder(string childName, string placeholderText)
    {
        Transform t = FindDeep(transform, childName);
        if (t == null) return;
        InputField inp = t.GetComponent<InputField>();
        if (inp == null || inp.placeholder == null) return;

        if (inp.placeholder is Text textPlaceholder)
            textPlaceholder.text = placeholderText;
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
#elif UNITY_WEBGL && !UNITY_EDITOR
        try
        {
            RoxCopyTextToClipboard(text ?? string.Empty);
        }
        catch (System.Exception e)
        {
            Debug.LogWarning("[Clipboard] WebGL copy failed: " + e.Message);
            GUIUtility.systemCopyBuffer = text;
        }
#else
        GUIUtility.systemCopyBuffer = text;
#endif
    }

    private static void PrepareWebGlTextInput(bool numericOnly)
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        try
        {
            RoxPrepareWebGlTextInput(numericOnly ? "numeric" : "default");
        }
        catch (System.Exception e)
        {
            Debug.LogWarning("[WebGLInput] prepare failed: " + e.Message);
        }
#endif
    }

    private static string BuildShareMessage(string code)
        => $"Play Rox Ludo with me!\nRoom Code: {code}\nPlay at roxludo.com";

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
