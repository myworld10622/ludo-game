using System.Collections;
using System.Collections.Generic;
using Mkey;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;

public class GameSelection : MonoBehaviour
{
    private const string LudoBootstrapScene = "LoginSplash";
    private const string LudoMenuScene = "MenuScene";
    private const string LudoFallbackScene = "LudoClassicModeOffline";

    public PointRummyScriptable point_rummy_scriptable;

    private void LoadSceneSafe(string sceneName)
    {
        if (SceneLoader.Instance != null)
        {
            SceneLoader.Instance.LoadScene(sceneName);
            return;
        }

        Debug.LogWarning($"SceneLoader.Instance is null. Falling back to SceneManager.LoadScene for scene: {sceneName}");
        SceneManager.LoadScene(sceneName);
    }

    public void loadscene(int num)
    {
        //SceneLoader.Instance.LoadScene(num);
        LoadSceneSafe("HomePage");
    }

    public void loadscenebyname(string scenename)
    {
        LoadSceneSafe(scenename);
    }

    public void loaddynamicscenebyname(string scenename)
    {
        LoadSceneSafe(scenename);
    }

    private bool TryLoadFirstAvailableScene(params string[] sceneNames)
    {
        foreach (string sceneName in sceneNames)
        {
            if (string.IsNullOrWhiteSpace(sceneName))
            {
                continue;
            }

            if (!Application.CanStreamedLevelBeLoaded(sceneName))
            {
                continue;
            }

            SceneManager.LoadSceneAsync(sceneName);
            return true;
        }

        return false;
    }

    public void OpenLudo()
    {
        if (ProfileManager.instance == null)
        {
            Debug.LogWarning("ProfileManager.instance is null while opening Ludo.");

            if (!TryLoadFirstAvailableScene(LudoMenuScene, LudoFallbackScene))
            {
                CommonUtil.ShowToast("Ludo scene not available");
            }

            return;
        }

        if (!ProfileManager.instance.ludoloaded)
        {
            CommonUtil.ShowToast("Loading...");
            ProfileManager.instance.ludoloaded = true;

            if (!TryLoadFirstAvailableScene(LudoBootstrapScene, LudoMenuScene, LudoFallbackScene))
            {
                CommonUtil.ShowToast("Ludo scene not available");
            }
        }
        else
        {
            if (!TryLoadFirstAvailableScene(LudoMenuScene, LudoFallbackScene))
            {
                CommonUtil.ShowToast("Ludo scene not available");
            }
        }
    }

    public void PracticeRummy()
    {
        // point_rummy_scriptable.no_of_players = "2";
        PlayerPrefs.SetString("Getpointplayer", "2");
        PlayerPrefs.SetString("Getpointboot", "00");
        // point_rummy_scriptable.boot_value = "0.00";
        LoadSceneSafe("Rummy_13");
    }

    public void ShowComingSoon()
    {
        CommonUtil.ShowToast("Coming Soon");
    }
    // void Start()
    // {
    //     StartCoroutine(DownloadBundle("https://letscard.free.nf/ServerData/Android/5c788a3d5103dc9a97753ad71d6603fe_monoscripts_409cb15da5a40ac9939f5a174f9d732b.bundle"));
    // }
    // IEnumerator DownloadBundle(string bundleUrl)
    // {
    //     using (UnityWebRequest uwr = UnityWebRequestAssetBundle.GetAssetBundle(bundleUrl))
    //     {
    //         yield return uwr.SendWebRequest();

    //         if (uwr.result != UnityWebRequest.Result.Success)
    //         {
    //             Debug.LogError("Failed to download AssetBundle: " + uwr.error);
    //         }
    //         else
    //         {
    //             AssetBundle bundle = DownloadHandlerAssetBundle.GetContent(uwr);
    //             Debug.Log("AssetBundle successfully loaded.");
    //         }
    //     }
    // }
}
