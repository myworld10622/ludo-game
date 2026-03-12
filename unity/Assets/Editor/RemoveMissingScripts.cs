using UnityEditor;
using UnityEngine;

public class RemoveMissingScripts : Editor
{
    [MenuItem("Tools/Remove Missing Scripts in Scene")]
    private static void RemoveMissingScriptsInScene()
    {
        int count = 0;
        foreach (GameObject go in GameObject.FindObjectsOfType<GameObject>())
        {
            count += GameObjectUtility.RemoveMonoBehavioursWithMissingScript(go);
        }

        Debug.Log($"Removed {count} missing scripts from the scene.");
    }

    [MenuItem("Tools/Remove Missing Scripts in Selected Prefabs")]
    private static void RemoveMissingScriptsInSelectedPrefabs()
    {
        int count = 0;
        foreach (GameObject go in Selection.gameObjects)
        {
            count += GameObjectUtility.RemoveMonoBehavioursWithMissingScript(go);
            EditorUtility.SetDirty(go); // Mark prefab as dirty to save changes
        }

        AssetDatabase.SaveAssets();
        Debug.Log($"Removed {count} missing scripts from selected prefabs.");
    }
}
