using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using UnityEngine;
using UnityEngine.SceneManagement;

public class SpriteManager : MonoBehaviour
{
    private static SpriteManager _instance;
    public static SpriteManager Instance => _instance;
    public Sprite welcome_app_banner;
    public Sprite profile_image;
    public Sprite passbook_image;
    public Sprite crypto_image;
    public Sprite aadhar_image;
    public Sprite pan_image;
    public List<Sprite> app_banner;
    public List<Sprite> avatar;
    public List<string> app_banner_name;
    public List<string> avatar_name;
    public string base64forimgpassbook;
    public string base64forimgcrypto;
    public string base64forimgmanualss;
    public string base64forimgrofile;
    public string base64forimgpan;
    public string base64forimgaadhar;

    public string base64forimgeforusdt;
    public string base64forimgeforticket;

    private newLogInOutputs LogInOutput;

    private void Awake()
    {
        if (_instance != null && _instance != this)
        {
            Destroy(this.gameObject);
        }
        else
        {
            _instance = this;
            DontDestroyOnLoad(this.gameObject);
        }
        string id = Configuration.GetId();
        Debug.Log("CHECK SAVE OR NOT::" + id);
        if (id != string.Empty)
        {
            SetProfile();
        }
        else
        {
            CommonUtil.CheckLog("New User!");
        }
    }

    void Start()
    {
        InternetMonitor.OnInternetLost += HandleInternetLost;
        InternetMonitor.OnInternetRestored += HandleInternetRestored;
    }

    void HandleInternetLost()
    {
        CommonUtil.ShowToast("No Internet Connection!");
    }

    void HandleInternetRestored()
    {
        CommonUtil.ShowToast("Back To Online!");
    }

    void OnDestroy()
    {
        // Always unsubscribe to avoid memory leaks
        InternetMonitor.OnInternetLost -= HandleInternetLost;
        InternetMonitor.OnInternetRestored -= HandleInternetRestored;
    }

    public void SetProfile()
    {
        UpdateData(Configuration.GetId(), Configuration.GetToken());
    }

    public async Task UpdateData(string id, string token) //string Token)
    {
        string Url = Configuration.Url + Configuration.profile;
        Debug.Log("RES_Check + API-Call + UPDATE Data");

        var formData = new Dictionary<string, string>
        {
            { "fcm", Configuration.getFCMToken() },
            { "app_version", "1" },
            { "id", id },
            { "token", token },
        };
        LogInOutput = new newLogInOutputs();
        LogInOutput = await APIManager.Instance.Post<newLogInOutputs>(Url, formData);
        if (LogInOutput.code == 200)
        {
            await DownloadProfileImage();
            await GetBannerImage(LogInOutput.notification_image);
            PostUserSetting(Configuration.Url + Configuration.Usersetting);
        }
        else if (LogInOutput.code == 411)
        {
            CommonUtil.CheckLog("Already Login Somewhere Devices");
            //Addressables.LoadSceneAsync("LoginRegister", LoadSceneMode.Single);
            LoaderUtil.instance.LoadScene("LoginRegister");
            //   CommonUtil.ShowToastDebug("Already Login Somewhere Devices" + LogInOutput.message);
        }
    }

    public async Task GetBannerImage(string notificationpic)
    {
        string image_url = Configuration.NotificationBannerImage + notificationpic;
        SpriteManager.Instance.welcome_app_banner = await ImageUtil.Instance.GetSpriteFromURLAsync(
            image_url
        );
    }

    public async Task DownloadProfileImage()
    {
        Debug.Log(
            "RES_check + Profile image download 3 "
                + Configuration.ProfileImage
                + Configuration.GetProfilePic()
        );
        string app_avatar_image_url = Configuration.ProfileImage + Configuration.GetProfilePic();
        Debug.Log(
            "RES_check + Avatar " + Configuration.ProfileImage + Configuration.GetProfilePic()
        );
        SpriteManager.Instance.profile_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
            app_avatar_image_url
        );
    }

    public async void PostUserSetting(string url)
    {
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        var UserSettingOutPut = await APIManager.Instance.Post<UserSettingOutPuts>(url, formData);
        Debug.Log($"RES+Message: {UserSettingOutPut.message}\nRES+Code: {UserSettingOutPut.code}");

        app_banner.Clear();
        for (int i = 0; i < UserSettingOutPut.app_banner.Count; i++)
        {
            Debug.Log("RES_Check + getting images");
            string app_banner_image_url =
                Configuration.BannerImage + UserSettingOutPut.app_banner[i].banner;
            app_banner.Add(await ImageUtil.Instance.GetSpriteFromURLAsync(app_banner_image_url));

            app_banner_name.Add(UserSettingOutPut.app_banner[i].banner);
        }
    }
}
