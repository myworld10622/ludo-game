using UnityEditor;
using UnityEngine;

public static class AgoraVoiceSdkImporter
{
    private const string PackagePath = @"C:\tmp\AgoraVoiceSdk\Agora-RTC-Plugin.unitypackage";
    private const string ImportRequestPath = @"C:\tmp\agora-voice-sdk-import.request";
    private const string MenuPath = "Tools/Live Rox Ludo/Import Agora Voice SDK";
    private static bool importPendingAfterPlayModeExit;

    [InitializeOnLoadMethod]
    private static void AutoImportIfRequested()
    {
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
        Debug.Log("Agora Voice SDK import triggered from: " + PackagePath);
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
