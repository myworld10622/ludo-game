using UnityEngine;
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
                SetCancelButtonText(WaitingIsCreator ? "Copy Code & Close" : "Close");
                if (WaitingIsCreator) GUIUtility.systemCopyBuffer = WaitingCode;
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
}
