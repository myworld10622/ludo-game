using UnityEngine;
using UnityEngine.UI;

public class PassNPlayPopup : MonoBehaviour
{
    public Text TxtTitle;
    public Text TxtMessage;
    public GameObject ObjePrivateTable;
    internal static int Index = 0;

    private void OnEnable()
    {
        if(Index == 1)
        {
            TxtTitle.text = "Private Table";
            TxtMessage.text = "Select how many players will join this Private Table match.";
            ObjePrivateTable.SetActive(true);
        }
        else
        {
            TxtTitle.text = "Pass N Play";
            TxtMessage.text = "Select how many players will join this Pass N Play match.";
            ObjePrivateTable.SetActive(false);
        }
    }

    private void OnDisable()
    {
        Index = 0;
    }
}
