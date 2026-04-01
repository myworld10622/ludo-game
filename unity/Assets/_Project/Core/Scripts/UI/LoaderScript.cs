using System;
using System.Collections;
using System.Collections.Generic;
using EasyButtons;
using Mkey;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

using UnityEngine.SceneManagement;

public class LoaderScript : MonoBehaviour
{
    public GameObject Panel;
    public Image slider;
    public TextMeshProUGUI text;

    public List<Image> AllImages = new List<Image>();

    /// <summary>
    /// Start is called on the frame when a script is enabled just before
    /// any of the Update methods is called the first time.
    /// </summary>
    /// 

    void OnEnable()
    {
        if (transform.childCount > 2)
        {
            Panel = transform.GetChild(2).gameObject;
        }
        else
        {
            Panel = transform.GetChild(0).gameObject;
        }
        slider = Panel.transform.GetChild(1).GetComponent<Image>();
        text = Panel.transform.GetChild(2).GetComponent<TextMeshProUGUI>();

        foreach (Transform image in transform.GetChild(0).transform)
        {
            AllImages.Add(image.GetComponent<Image>());
        }
    }



    private int Count = 0;




    void UpdateProgress(float progress)
    {
        text.color = Color.white;
        Panel.SetActive(true);
        int percent = Mathf.RoundToInt(progress * 100);
        slider.fillAmount = progress;
        Debug.Log("Percent %:" + percent + "Progress %:" + progress);
        text.text = "Downloading.. " + percent + "%";
    }
    private void LoadDynamic(string scene)
    {
        if (slider != null)
        {
            SetTransparency(255);
            Panel.gameObject.SetActive(false); // Hide progress UI
        }

        if (scene == "point")
        {
            loaddynamicscenebyname("PointTable.unity");
        }
        else if (scene == "pool")
        {
            loaddynamicscenebyname("Join_Table(Pool).unity");
        }
        else if (scene == "deal")
        {
            loaddynamicscenebyname("Join_Table(Deal).unity");
        }
        else if (scene == "teenpatti")
        {
            loaddynamicscenebyname("TeenPatti_GamePlay.unity");
        }
        else
        {
            Debug.Log("Name of Load Scene:" + scene);
            loaddynamicscenebyname(scene);
        }
    }

    public void loaddynamicscenebyname(string scenename)
    {
        if (SceneLoader.Instance != null)
        {
            SceneLoader.Instance.LoadScene(scenename);
            return;
        }

        if (!string.IsNullOrWhiteSpace(scenename) && Application.CanStreamedLevelBeLoaded(scenename))
        {
            SceneManager.LoadScene(scenename);
            return;
        }

        Debug.Log($"Unable to load scene: {scenename}");
    }
    [Button]
    public void SetTransparency(float alphaValue)
    {
        Debug.Log("Alpha" + alphaValue + "allimages" + AllImages);
        float alpha = Mathf.Clamp(alphaValue, 0, 255) / 255f; // Convert 0-255 to 0-1
        foreach (var img in AllImages)
        {
            if (img != null)
            {
                Color color = img.color;
                color.a = alpha; // Ensure alpha is between 0 and 1
                img.color = color;
            }
        }
    }
}
