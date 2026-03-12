using System.Collections;
using System.Collections.Generic;
using Mkey;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;

public class GameSelection : MonoBehaviour
{
    public PointRummyScriptable point_rummy_scriptable;

    public void loadscene(int num)
    {
        //SceneLoader.Instance.LoadScene(num);
        SceneLoader.Instance.LoadScene("HomePage");
    }

    public void loadscenebyname(string scenename)
    {
        SceneLoader.Instance.LoadScene(scenename);
    }

    public void loaddynamicscenebyname(string scenename)
    {
        SceneLoader.Instance.LoadScene(scenename);
    }

    public void OpenLudo()
    {
        if (!ProfileManager.instance.ludoloaded)
        {
            CommonUtil.ShowToast("Loading...");
            ProfileManager.instance.ludoloaded = true;
            SceneManager.LoadSceneAsync("LoginSplash");
        }
        else
        {
            // loading.SetActive(true);
            SceneManager.LoadSceneAsync("MenuScene");
        }
    }

    public void PracticeRummy()
    {
        // point_rummy_scriptable.no_of_players = "2";
        PlayerPrefs.SetString("Getpointplayer", "2");
        PlayerPrefs.SetString("Getpointboot", "00");
        // point_rummy_scriptable.boot_value = "0.00";
        SceneLoader.Instance.LoadScene("Rummy_13");
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
