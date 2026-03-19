using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using Mkey;

//using Profile;
using UnityEngine;
using UnityEngine.Assertions.Must;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.U2D;
using UnityEngine.UI;

public class LoadingManager : MonoBehaviour
{
    public Slider slider;
    public float duration = 2f;

    private float startTime;
    private float startValue;
    private float targetValue;

    void Awake()
    {
#if UNITY_ANDROID
        Screen.orientation = ScreenOrientation.LandscapeLeft;
#endif
    }

    void Start()
    {
        startTime = Time.time;
        startValue = slider.minValue;
        targetValue = slider.maxValue;
        StartCoroutine(TransitionAfterDelay());
    }

    IEnumerator TransitionAfterDelay()
    {
        while (Time.time - startTime < duration)
        {
            // Calculate the current progress of the movement
            float progress = (Time.time - startTime) / duration;

            // Interpolate between start and target values
            float currentValue = Mathf.Lerp(startValue, targetValue, progress);

            // Set the slider value
            slider.value = currentValue;

            // Wait for the next frame
            yield return null;
        }

        slider.value = targetValue;

        string id = Configuration.GetId();
        string token = Configuration.GetToken();
        bool hasSession = !string.IsNullOrWhiteSpace(id) && !string.IsNullOrWhiteSpace(token);

        CommonUtil.CheckLog("RES_Check + name " + Configuration.GetName());
        CommonUtil.CheckLog("RES_Check + startup session id: " + id + " token_exists: " + (!string.IsNullOrWhiteSpace(token)));

        if (hasSession)
        {
            // LogUtil.CheckLog("RES_check + Profile image download + " + Configuration.GetProfilePic());
            // DownloadProfileImage();
            // LoaderUtil.instance.LoadScene("HomePage");
            SceneLoader.Instance.LoadScene("HomePage");
        }
        else
        {
            if (!string.IsNullOrWhiteSpace(id) || !string.IsNullOrWhiteSpace(token))
            {
                PlayerPrefs.DeleteKey("id");
                PlayerPrefs.DeleteKey("token");
                PlayerPrefs.Save();
            }

            SceneLoader.Instance.LoadScene("LoginRegister");
            // LoaderUtil.instance.LoadScene("LoginRegister");
        }
    }

    void OnDestroy()
    {
        // Clear cached sprites when the scene or object is destroyed
        SpriteAtlasUtil.ClearCache();
    }
}
