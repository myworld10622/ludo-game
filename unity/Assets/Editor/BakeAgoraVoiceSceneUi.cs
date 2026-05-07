using LudoClassicOffline;
using UnityEditor;
using UnityEngine;

public static class BakeAgoraVoiceSceneUi
{
    [MenuItem("Tools/Live Rox Ludo/Bake Agora Voice UI Into Scene")]
    public static void Bake()
    {
        DashBoardManagerOffline dashboard = Object.FindObjectOfType<DashBoardManagerOffline>(true);
        if (dashboard == null)
        {
            Debug.LogError("DashBoardManagerOffline not found in the open scene.");
            return;
        }

        AgoraVoiceManager voiceManager = dashboard.GetComponent<AgoraVoiceManager>();
        if (voiceManager == null)
        {
            voiceManager = Undo.AddComponent<AgoraVoiceManager>(dashboard.gameObject);
        }

        voiceManager.BakeSceneAnchoredUiForEditor();
        Debug.Log("Agora voice UI baked into the current scene hierarchy.");
    }
}
