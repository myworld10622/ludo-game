using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;
using UnityEngine.UI;

public class BannerManager : MonoBehaviour
{
    public static BannerManager Instance { get; private set; }
    public Image banner_img;
    public GameObject banner_obj;
    public GameObject app_banner_prefab;
    public Transform bannerparent;
    public List<GameObject> app_banner;

    private bool isEnableFalse = false;

    private void OnEnable()
    {
        Instance = this;
        LoadBannersSafe();
    }

    private void OnDisable()
    {
        if (Instance == this)
        {
            Instance = null;
        }
    }

    private void OnDestroy()
    {
        if (Instance == this)
        {
            Instance = null;
        }
    }

    private void LoadBannersSafe()
    {
        if (isEnableFalse)
        {
            isEnableFalse = true;
            return;
        }

        if (app_banner == null)
        {
            app_banner = new List<GameObject>();
        }

        app_banner.ForEach(banner => Destroy(banner));
        app_banner.Clear();

        if (ImageUtil.Instance != null && !ImageUtil.Instance.bannerloaded)
        {
            PopUpUtil.ButtonClick(banner_obj);
            if (banner_img != null && SpriteManager.Instance != null)
            {
                banner_img.sprite = SpriteManager.Instance.welcome_app_banner;
            }
            Debug.Log("RES_check + banner open");
            ImageUtil.Instance.bannerloaded = true;
        }

        if (SpriteManager.Instance == null || SpriteManager.Instance.app_banner == null)
        {
            return;
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
        if (app_banner == null)
        {
            app_banner = new List<GameObject>();
        }

        app_banner.ForEach(banner => Destroy(banner));
        app_banner.Clear();

        if (SpriteManager.Instance == null || SpriteManager.Instance.app_banner == null)
        {
            return;
        }

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
        if (bannerparent == null)
        {
            return;
        }

        ScrollRect scrollRect = bannerparent.GetComponentInParent<ScrollRect>();
        if (scrollRect != null)
        {
            scrollRect.verticalNormalizedPosition = 0;
        }
    }
}
