using System.Collections;
using System.Collections.Generic;
using EasyUI.Toast;
using Mkey;

// using Gpm.WebView;
using UnityEngine;
using UnityEngine.SceneManagement;

public class LoaderUtil : MonoBehaviour
{
    public static LoaderUtil instance;

    /// <summary>
    /// Awake is called when the script instance is being loaded.
    /// </summary>
    void Awake()
    {
        Application.runInBackground = true;
        Application.targetFrameRate = 60;
        QualitySettings.vSyncCount = 0;

        if (instance == null)
        {
            instance = this;
            DontDestroyOnLoad(this.gameObject);
        }
        else
        {
            Destroy(this.gameObject);
        }

        Screen.sleepTimeout = SleepTimeout.NeverSleep;

    }

    void OnApplicationQuit()
    {
        Screen.sleepTimeout = SleepTimeout.SystemSetting;
    }

    public void ShowToast(string message)
    {
        Toast.Show(message, 3f);
    }

    public void LoadScene(string scene_name)
    {
        //SceneManager.LoadSceneAsync(scene_name + ".unity");
        /// Addressables.LoadSceneAsync(scene_name + ".unity", LoadSceneMode.Single);
        /// 
        SceneLoader.Instance.LoadScene(scene_name);

    }

    #region loader beautify



    #endregion
}
