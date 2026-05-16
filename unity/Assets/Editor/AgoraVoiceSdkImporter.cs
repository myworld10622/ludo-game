using UnityEditor;
using UnityEngine;

public static class AgoraVoiceSdkImporter
{
    private const string PackagePath = @"C:\tmp\AgoraVoiceSdk\Agora-RTC-Plugin.unitypackage";
    private const string ImportRequestPath = @"C:\tmp\agora-voice-sdk-import.request";
    private const string MenuPath = "Tools/Live Rox Ludo/Import Agora Voice SDK";
    private const string AndroidPluginRoot = "Assets/Agora-RTC-Plugin/Agora-Unity-RTC-SDK/Plugins/Android/AgoraRtcEngineKit.plugin";
    private static bool importPendingAfterPlayModeExit;

    [InitializeOnLoadMethod]
    private static void AutoImportIfRequested()
    {
        EditorApplication.delayCall += NormalizeAgoraAndroidPluginImporter;
        if (!System.IO.File.Exists(ImportRequestPath))
        {
            return;
        }

        System.IO.File.Delete(ImportRequestPath);
        EditorApplication.delayCall += ImportAgoraVoiceSdk;
    }

    [MenuItem(MenuPath)]
    public static void ImportAgoraVoiceSdk()
    {
        if (EditorApplication.isPlaying || EditorApplication.isPlayingOrWillChangePlaymode)
        {
            importPendingAfterPlayModeExit = true;
            EditorApplication.playModeStateChanged -= HandlePlayModeStateChanged;
            EditorApplication.playModeStateChanged += HandlePlayModeStateChanged;
            EditorApplication.isPlaying = false;
            Debug.Log("Stopping play mode before Agora SDK import.");
            return;
        }

        importPendingAfterPlayModeExit = false;
        if (!System.IO.File.Exists(PackagePath))
        {
            Debug.LogError("Agora SDK package not found at: " + PackagePath);
            return;
        }

        AssetDatabase.ImportPackage(PackagePath, false);
        NormalizeAgoraAndroidPluginImporter();
        Debug.Log("Agora Voice SDK import triggered from: " + PackagePath);
    }

    [MenuItem("Tools/Live Rox Ludo/Fix Agora Android Plugin Settings")]
    public static void NormalizeAgoraAndroidPluginImporter()
    {
        PluginImporter importer = AssetImporter.GetAtPath(AndroidPluginRoot) as PluginImporter;
        if (importer == null)
        {
            return;
        }

        bool changed = false;

        if (importer.GetCompatibleWithAnyPlatform())
        {
            importer.SetCompatibleWithAnyPlatform(false);
            changed = true;
        }

        if (!importer.GetCompatibleWithPlatform(BuildTarget.Android))
        {
            importer.SetCompatibleWithPlatform(BuildTarget.Android, true);
            changed = true;
        }

        string currentCpu = importer.GetPlatformData(BuildTarget.Android, "CPU");
        if (!string.Equals(currentCpu, "AnyCPU", System.StringComparison.OrdinalIgnoreCase))
        {
            importer.SetPlatformData(BuildTarget.Android, "CPU", "AnyCPU");
            changed = true;
        }

        if (changed)
        {
            importer.SaveAndReimport();
            Debug.Log("Normalized Agora Android plugin importer settings to AnyCPU for APK builds.");
        }
    }

    private static void HandlePlayModeStateChanged(PlayModeStateChange state)
    {
        if (!importPendingAfterPlayModeExit || state != PlayModeStateChange.EnteredEditMode)
        {
            return;
        }

        EditorApplication.playModeStateChanged -= HandlePlayModeStateChanged;
        EditorApplication.delayCall += ImportAgoraVoiceSdk;
    }
}
