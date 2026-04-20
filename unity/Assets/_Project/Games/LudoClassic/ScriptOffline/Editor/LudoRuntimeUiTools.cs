using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;

namespace LudoClassicOffline
{
    public static class LudoRuntimeUiTools
    {
        [MenuItem("Tools/Ludo Classic/Rebuild HomePage Runtime UI")]
        private static void RebuildHomePageRuntimeUi()
        {
            if (EditorApplication.isPlaying)
            {
                Debug.LogWarning("Stop play mode before rebuilding the HomePage runtime UI.");
                return;
            }

            DashBoardManagerOffline dashboard = Object.FindObjectOfType<DashBoardManagerOffline>(true);
            if (dashboard == null)
            {
                Debug.LogError("DashBoardManagerOffline not found in the open scene.");
                return;
            }

            LudoTournamentPanelOffline tournamentPanel = dashboard.GetComponent<LudoTournamentPanelOffline>();
            if (tournamentPanel == null)
            {
                tournamentPanel = dashboard.gameObject.AddComponent<LudoTournamentPanelOffline>();
            }

            tournamentPanel.RebuildPersistentUiInEditor(dashboard);

            LudoFriendPanelController friendPanel = Object.FindObjectOfType<LudoFriendPanelController>(true);
            if (friendPanel == null)
            {
                GameObject bootstrap = GameObject.Find("HomePageFriendBootstrap");
                if (bootstrap == null)
                {
                    bootstrap = new GameObject("HomePageFriendBootstrap");
                }

                friendPanel = bootstrap.GetComponent<LudoFriendPanelController>();
                if (friendPanel == null)
                {
                    friendPanel = bootstrap.AddComponent<LudoFriendPanelController>();
                }
            }

            friendPanel.RebuildPersistentUiInEditor();
            EditorSceneManager.MarkSceneDirty(dashboard.gameObject.scene);
            Debug.Log("HomePage runtime UI rebuilt into the scene hierarchy.");
        }
    }
}
