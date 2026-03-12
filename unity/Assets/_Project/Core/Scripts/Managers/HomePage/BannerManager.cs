using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;
using UnityEngine.UI;

public class BannerManager : MonoBehaviour
{
    public Image banner_img;
    public GameObject banner_obj;
    public GameObject app_banner_prefab;
    public Transform bannerparent;
    public List<GameObject> app_banner;

    private bool isEnableFalse = false;

    async void OnEnable()
    {
        if (isEnableFalse)
        {
            isEnableFalse = true;
            return;
        }
        app_banner.ForEach(banner => Destroy(banner));
        app_banner.Clear();
        if (!ImageUtil.Instance.bannerloaded)
        {
            PopUpUtil.ButtonClick(banner_obj);
            banner_img.sprite = SpriteManager.Instance.welcome_app_banner;
            Debug.Log("RES_check + banner open");
            ImageUtil.Instance.bannerloaded = true;
        }
        for (int i = 0; i < SpriteManager.Instance.app_banner.Count; i++)
        {
            GameObject banner = Instantiate(app_banner_prefab, bannerparent);
            app_banner.Add(banner);
            banner.GetComponent<Image>().sprite = SpriteManager.Instance.app_banner[i];
        }
        Invoke(nameof(Delay), 0.2f);
    }
    void Awake()
    {
        app_banner.ForEach(banner => Destroy(banner));
        app_banner.Clear();
        for (int i = 0; i < SpriteManager.Instance.app_banner.Count; i++)
        {
            GameObject banner = Instantiate(app_banner_prefab, bannerparent);
            app_banner.Add(banner);
            banner.GetComponent<Image>().sprite = SpriteManager.Instance.app_banner[i];
        }
        Invoke(nameof(Delay), 0.2f);
    }
    public void Delay()
    {
        bannerparent.GetComponentInParent<ScrollRect>().verticalNormalizedPosition = 0;
    }
}
