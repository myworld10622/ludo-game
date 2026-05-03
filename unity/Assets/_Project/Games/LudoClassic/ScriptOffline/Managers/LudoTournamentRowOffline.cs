using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoTournamentRowOffline : MonoBehaviour
    {
        [Header("Data")]
        public LudoTournamentPanelOffline.LudoTournamentListItem tournamentData;

        [Header("References")]
        public LudoTournamentPanelOffline panelManager;

        public void OnDetailsClicked()
        {
            if (panelManager == null)
            {
                panelManager = FindObjectOfType<LudoTournamentPanelOffline>();
            }

            if (panelManager != null && tournamentData != null)
            {
                panelManager.OpenTournamentDetails(tournamentData);
            }
            else
            {
                Debug.LogWarning("[TournamentRow] Missing Panel Manager or Tournament Data!");
            }
        }
    }
}
