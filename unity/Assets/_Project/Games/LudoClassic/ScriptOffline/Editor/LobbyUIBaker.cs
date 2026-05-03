using UnityEditor;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

namespace LudoClassicOffline.Editor
{
    public class LobbyUIBaker : EditorWindow
    {
        [MenuItem("Tools/Bake Classic Lobby UI")]
        public static void BakeUI()
        {
            DashBoardManagerOffline manager = Object.FindObjectOfType<DashBoardManagerOffline>();
            if (manager == null)
            {
                Debug.LogError("DashBoardManagerOffline not found in the active scene!");
                return;
            }

            if (manager.player2 == null || manager.player4 == null)
            {
                Debug.LogError("Player2 or Player4 tabs are not assigned in DashBoardManagerOffline!");
                return;
            }

            Transform parent = manager.player4.parent;
            Transform existingTab = parent.Find("TournamentTab");
            if (existingTab != null)
            {
                Debug.Log("Tournament tab already exists in the hierarchy.");
                return;
            }

            // Create the clone
            GameObject clone = Instantiate(manager.player4.gameObject, parent);
            clone.name = "TournamentTab";

            // Replace Text
            ReplaceTabText(clone.transform, "4 PLAYER", "TOURNAMENT");
            ReplaceTabText(clone.transform, "4 Player", "Tournament");
            ReplaceTabText(clone.transform, "4PLAYER", "TOURNAMENT");
            ReplaceTabText(clone.transform, "PLAYER", "TOURNAMENT");

            // Calculate Positions
            Vector2 cachedPlayer2Position = manager.player2.anchoredPosition;
            Vector2 cachedPlayer4Position = manager.player4.anchoredPosition;
            Vector2 cachedPlayer2Size = manager.player2.sizeDelta;
            Vector2 cachedPlayer4Size = manager.player4.sizeDelta;

            float centerX = (cachedPlayer2Position.x + cachedPlayer4Position.x) * 0.5f;
            float tabWidth = Mathf.Min(cachedPlayer2Size.x, cachedPlayer4Size.x) * 0.82f;
            float tabHeight = Mathf.Min(cachedPlayer2Size.y, cachedPlayer4Size.y) * 1.35f;
            float gap = 22f;
            float step = tabWidth + gap;

            Vector2 newSize = new Vector2(tabWidth, tabHeight);

            // Apply Sizes and Positions
            manager.player2.sizeDelta = newSize;
            manager.player4.sizeDelta = newSize;
            manager.player2.anchoredPosition = new Vector2(centerX - step, cachedPlayer2Position.y);
            manager.player4.anchoredPosition = new Vector2(centerX + step, cachedPlayer4Position.y);

            RectTransform tournamentTab = clone.GetComponent<RectTransform>();
            tournamentTab.SetAsLastSibling();
            tournamentTab.sizeDelta = new Vector2(tabWidth, tabHeight * 1.08f);
            tournamentTab.anchoredPosition = new Vector2(centerX, cachedPlayer2Position.y);

            // Register Undo so changes can be reverted and are marked as dirty
            Undo.RegisterCreatedObjectUndo(clone, "Bake Tournament Tab");
            Undo.RecordObject(manager.player2, "Update Player2 Tab Size/Pos");
            Undo.RecordObject(manager.player4, "Update Player4 Tab Size/Pos");
            
            EditorUtility.SetDirty(manager.player2);
            EditorUtility.SetDirty(manager.player4);
            EditorUtility.SetDirty(clone);

            Debug.Log("Successfully baked the Tournament Tab into the Hierarchy!");
        }

        private static void ReplaceTabText(Transform root, string source, string target)
        {
            Text[] labels = root.GetComponentsInChildren<Text>(true);
            for (int i = 0; i < labels.Length; i++)
            {
                if (!string.IsNullOrWhiteSpace(labels[i].text) && labels[i].text.Contains(source))
                {
                    Undo.RecordObject(labels[i], "Change Text");
                    labels[i].text = labels[i].text.Replace(source, target);
                    EditorUtility.SetDirty(labels[i]);
                }
            }

            TextMeshProUGUI[] tmpLabels = root.GetComponentsInChildren<TextMeshProUGUI>(true);
            for (int i = 0; i < tmpLabels.Length; i++)
            {
                if (!string.IsNullOrWhiteSpace(tmpLabels[i].text) && tmpLabels[i].text.Contains(source))
                {
                    Undo.RecordObject(tmpLabels[i], "Change TMP Text");
                    tmpLabels[i].text = tmpLabels[i].text.Replace(source, target);
                    EditorUtility.SetDirty(tmpLabels[i]);
                }
            }
        }
    }
}
