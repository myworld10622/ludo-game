#if UNITY_EDITOR
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;

public static class BakeRecoveryReminderPopup
{
    [MenuItem("Tools/Rox Ludo/Bake Recovery Reminder Popup")]
    private static void Bake()
    {
        DailyRewards dailyRewards = Object.FindObjectOfType<DailyRewards>(true);
        if (dailyRewards == null)
        {
            EditorUtility.DisplayDialog("Recovery Popup", "DailyRewards component not found in the open scene.", "OK");
            return;
        }

        dailyRewards.EditorEnsureRecoveryPopupForDesign();
        EditorUtility.SetDirty(dailyRewards);

        if (dailyRewards.gameObject.scene.IsValid())
        {
            EditorSceneManager.MarkSceneDirty(dailyRewards.gameObject.scene);
        }

        Selection.activeGameObject = dailyRewards.gameObject;
    }
}
#endif
