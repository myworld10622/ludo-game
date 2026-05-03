using UnityEditor;
using UnityEngine;
using System.IO;
using System.Reflection;
using System;

public class ScreenshotTool : EditorWindow
{
    private int width = 1080;
    private int height = 1920;
    private float scale = 1f;
    private Camera selectedCamera;
    private bool transparentBackground = false;
    private string savePath = "Screenshots";
    private string lastSavedPath = "";

    [MenuItem("Tools/Screenshot Tool")]
    public static void ShowWindow()
    {
        GetWindow<ScreenshotTool>("Screenshot");
    }

    void OnGUI()
    {
        GUILayout.Label("Resolution", EditorStyles.boldLabel);
        width = EditorGUILayout.IntField("Width", width);
        height = EditorGUILayout.IntField("Height", height);
        scale = EditorGUILayout.Slider("Scale", scale, 0.1f, 5f);
        EditorGUILayout.HelpBox("The default mode of screenshot is crop – so choose a proper width and height. The scale is a factor to enlarge renders without losing quality.", MessageType.Info);

        GUILayout.Space(10);
        GUILayout.Label("Save Path", EditorStyles.boldLabel);
        EditorGUILayout.BeginHorizontal();
        savePath = EditorGUILayout.TextField(savePath);
        if (GUILayout.Button("Browse", GUILayout.Width(70)))
        {
            string selected = EditorUtility.OpenFolderPanel("Choose Save Folder", "", "");
            if (!string.IsNullOrEmpty(selected))
            {
                savePath = selected;
            }
        }
        EditorGUILayout.EndHorizontal();

        GUILayout.Space(10);
        GUILayout.Label("Select Camera", EditorStyles.boldLabel);
        selectedCamera = (Camera)EditorGUILayout.ObjectField("Camera", selectedCamera, typeof(Camera), true);

        transparentBackground = EditorGUILayout.Toggle("Transparent Background", transparentBackground);
        EditorGUILayout.HelpBox("Choose the camera to capture. You can make the background transparent using this option.", MessageType.None);

        GUILayout.Space(10);
        GUILayout.Label("Default Options", EditorStyles.boldLabel);
        if (GUILayout.Button("Set To Screen Size"))
        {
            Vector2 screenSize = GetMainGameViewSize();
            width = (int)screenSize.x;
            height = (int)screenSize.y;
        }

        if (GUILayout.Button("Default Size"))
        {
            width = 1080;
            height = 1920;
            scale = 1f;
        }

        GUILayout.Space(10);
        EditorGUILayout.HelpBox($"Screenshot will be taken at {(int)(width * scale)} x {(int)(height * scale)} px", MessageType.Info);

        if (GUILayout.Button("Take Screenshot", GUILayout.Height(40)))
        {
            TakeScreenshot();
        }

        GUILayout.Space(10);
        EditorGUILayout.BeginHorizontal();
        if (GUILayout.Button("Open Last Screenshot"))
        {
            if (!string.IsNullOrEmpty(lastSavedPath) && File.Exists(lastSavedPath))
                EditorUtility.RevealInFinder(lastSavedPath);
        }

        if (GUILayout.Button("Open Folder"))
        {
            string folderToOpen = string.IsNullOrEmpty(savePath) ? Application.dataPath : savePath;
            EditorUtility.RevealInFinder(folderToOpen);
        }

        if (GUILayout.Button("More Assets"))
        {
            Application.OpenURL("https://assetstore.unity.com/");
        }
        EditorGUILayout.EndHorizontal();

        GUILayout.Space(10);
        EditorGUILayout.HelpBox("In case of any error, make sure you have Unity Pro as the plugin requires it for transparency.", MessageType.Warning);
    }
    private Vector2 GetMainGameViewSize()
    {
        Type T = Type.GetType("UnityEditor.GameView,UnityEditor");
        MethodInfo method = T.GetMethod("GetSizeOfMainGameView", BindingFlags.NonPublic | BindingFlags.Static);
        object result = method.Invoke(null, null);
        return (Vector2)result;
    }
    void TakeScreenshot()
    {
        if (selectedCamera == null)
        {
            selectedCamera = Camera.main;
            if (selectedCamera == null)
            {
                Debug.LogError("No camera selected or found.");
                return;
            }
        }

        int finalWidth = Mathf.RoundToInt(width * scale);
        int finalHeight = Mathf.RoundToInt(height * scale);

        RenderTexture rt = new RenderTexture(finalWidth, finalHeight, 24);
        Texture2D tex = new Texture2D(finalWidth, finalHeight, transparentBackground ? TextureFormat.RGBA32 : TextureFormat.RGB24, false);

        selectedCamera.targetTexture = rt;
        selectedCamera.Render();

        RenderTexture.active = rt;
        tex.ReadPixels(new Rect(0, 0, finalWidth, finalHeight), 0, 0);
        tex.Apply();

        selectedCamera.targetTexture = null;
        RenderTexture.active = null;
        DestroyImmediate(rt);

        if (!Directory.Exists(savePath))
            Directory.CreateDirectory(savePath);

        string fileName = $"Screenshot_{finalWidth}x{finalHeight}_{System.DateTime.Now:yyyyMMdd_HHmmss}.png";
        string path = Path.Combine(savePath, fileName);

        byte[] bytes = tex.EncodeToPNG();
        File.WriteAllBytes(path, bytes);
        lastSavedPath = path;

        Debug.Log($"Screenshot saved: {path}");
    }
}
