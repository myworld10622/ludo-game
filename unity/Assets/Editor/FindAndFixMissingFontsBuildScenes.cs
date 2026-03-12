using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using TMPro;
using UnityEngine.UI;
using UnityEngine.SceneManagement;

public class FindAndFixMissingFontsBuildScenes : EditorWindow
{
    private Font defaultFont;
    private TMP_FontAsset defaultTMPFont;
    private int fixedCount;

    [MenuItem("Tools/Find and Fix Missing Fonts (Build Scenes)")]
    public static void ShowWindow()
    {
        GetWindow<FindAndFixMissingFontsBuildScenes>("Find and Fix Missing Fonts");
    }

    private void OnGUI()
    {
        GUILayout.Label("Find and Fix Missing Fonts (Build Scenes)", EditorStyles.boldLabel);

        GUILayout.Space(10);
        GUILayout.Label("Default Font for Unity Text", EditorStyles.label);
        defaultFont = (Font)EditorGUILayout.ObjectField(defaultFont, typeof(Font), false);

        GUILayout.Label("Default Font for TextMeshPro", EditorStyles.label);
        defaultTMPFont = (TMP_FontAsset)EditorGUILayout.ObjectField(defaultTMPFont, typeof(TMP_FontAsset), false);

        if (GUILayout.Button("Find and Fix Missing Fonts in Build Scenes"))
        {
            FindAndFixMissingFontsInBuildScenes();
        }

        GUILayout.Space(20);
        GUILayout.Label($"Fixed {fixedCount} missing fonts across all build scenes.", EditorStyles.label);
    }

    private void FindAndFixMissingFontsInBuildScenes()
    {
        if (defaultFont == null && defaultTMPFont == null)
        {
            Debug.LogError("Please assign a default font for Unity Text or TextMeshPro before proceeding.");
            return;
        }

        fixedCount = 0;

        // Get scenes in the Build Settings
        EditorBuildSettingsScene[] buildScenes = EditorBuildSettings.scenes;

        foreach (EditorBuildSettingsScene buildScene in buildScenes)
        {
            if (!buildScene.enabled) continue; // Skip disabled scenes in build settings

            string scenePath = buildScene.path;

            // Load the scene
            Scene scene = EditorSceneManager.OpenScene(scenePath, OpenSceneMode.Single);
            Debug.Log($"Scanning and fixing scene: {scenePath}");

            // Process all root GameObjects in the scene
            foreach (GameObject obj in scene.GetRootGameObjects())
            {
                FixMissingFontsRecursive(obj);
            }

            // Save the scene after fixing
            EditorSceneManager.SaveScene(scene);
        }

        Debug.Log($"Total missing fonts fixed: {fixedCount}");
    }

    private void FixMissingFontsRecursive(GameObject obj)
    {
        // Fix missing fonts in Text components
        Text textComponent = obj.GetComponent<Text>();
        if (textComponent != null && textComponent.font == null && defaultFont != null)
        {
            textComponent.font = defaultFont;
            Debug.Log($"Fixed missing font in Text Component on GameObject: {GetGameObjectPath(obj)}", obj);
            fixedCount++;
        }

        // Fix missing fonts in TextMeshPro components
        TextMeshProUGUI tmpComponent = obj.GetComponent<TextMeshProUGUI>();
        if (tmpComponent != null && tmpComponent.font == null && defaultTMPFont != null)
        {
            tmpComponent.font = defaultTMPFont;
            Debug.Log($"Fixed missing font in TextMeshPro Component on GameObject: {GetGameObjectPath(obj)}", obj);
            fixedCount++;
        }

        // Recursively process child objects
        foreach (Transform child in obj.transform)
        {
            FixMissingFontsRecursive(child.gameObject);
        }
    }

    private static string GetGameObjectPath(GameObject obj)
    {
        string path = obj.name;
        while (obj.transform.parent != null)
        {
            obj = obj.transform.parent.gameObject;
            path = obj.name + "/" + path;
        }
        return path;
    }
}
