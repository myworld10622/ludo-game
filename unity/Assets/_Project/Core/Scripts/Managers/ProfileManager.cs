using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using System.Threading.Tasks;
using Newtonsoft.Json;
using UniRx;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class ProfileManager : MonoBehaviour
{
    public notificationUserList notifications;
    public bool ludoloaded,
        bannerloaded;
    public static ProfileManager instance;

    void Awake()
    {
        if (instance == null)
        {
            instance = this;
            DontDestroyOnLoad(this.gameObject);
        }
        else
        {
            Destroy(this.gameObject);
        }
    }

    void Start()
    {
        /*    PostUserSetting(Configuration.Url + Configuration.Usersetting);
              GetRandomNotifications(); */
        CommonUtil.CheckLog("TOKEN::" + Configuration.GetToken());
    }

    public async void GetRandomNotifications()
    {
        string url = Configuration.GameNotificcations;
        var formData = new Dictionary<string, string> { { "user_id", "" }, { "token", "" } };
        notifications = await APIManager.Instance.Post<notificationUserList>(url, formData);

        CommonUtil.CheckLog(
            $"RES+Message: {notifications.message}\nRES+Code: {notifications.code}"
        );
    }

    public async void GetProfileDetails()
    {
        string Url = Configuration.Url + Configuration.profile;
        CommonUtil.CheckLog("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "fcm", Configuration.getFCMToken() },
            { "app_version", Application.version },
            { "id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        newLogInOutputs LogInOutput = new newLogInOutputs();
        LogInOutput = await APIManager.Instance.Post<newLogInOutputs>(Url, formData);
        if (LogInOutput.code == 200)
        {
            PlayerPrefs.SetString("id", LogInOutput.user_data[0].id);
            PlayerPrefs.SetString("token", LogInOutput.user_data[0].token);
            PlayerPrefs.SetString("wallet", LogInOutput.user_data[0].wallet);
            PlayerPrefs.SetString("profile_pic", LogInOutput.user_data[0].profile_pic);
            PlayerPrefs.SetString("name", LogInOutput.user_data[0].name);



            if (LogInOutput.user_bank_details.Count > 0)
            {
                CommonUtil.CheckLog(
                    "RES_Check + Passbook " + LogInOutput.user_bank_details[0].passbook_img
                );
                PlayerPrefs.SetString(
                    "passbook_pic",
                    LogInOutput.user_bank_details[0].passbook_img
                );
            }
            if (LogInOutput.user_kyc.Count > 0)
            {
                PlayerPrefs.SetString("adhar_pic", LogInOutput.user_kyc[0].aadhar_img);
                PlayerPrefs.SetString("pan_pic", LogInOutput.user_kyc[0].pan_img);
            }
            PlayerPrefs.Save();
            GetBannerImage(LogInOutput.notification_image);
            GetProfileImage(LogInOutput.user_data[0].profile_pic);
            LoaderUtil.instance.LoadScene("HomePage");
        }
        else if (LogInOutput.code == 411)
        {
            LoaderUtil.instance.LoadScene("LoginRegister");
        }
        else
        {
            CommonUtil.CheckLog(
                $"RES+Message: {LogInOutput.message}\nRES+Code: {LogInOutput.code}"
            );
        }
        CommonUtil.CheckLog($"RES+Message: {LogInOutput.message}\nRES+Code: {LogInOutput.code}");
    }

    public async void GetProfileImage(string profile_pic)
    {
        string profile_url = Configuration.ProfileImage + profile_pic;
        SpriteManager.Instance.profile_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
            profile_url
        );
        // while (SpriteManager.Instance.profile_image == null)
        // {
        //     await Task.Delay(100); // Small delay to avoid blocking the main thread
        // }
        // Debug.Log("ITS WAIT AFTER CALL:::" + profile_url);
        //LoaderUtil.instance.LoadScene("HomePage");
    }

    public async void GetBannerImage(string notificationpic)
    {
        string image_url = Configuration.NotificationBannerImage + notificationpic;
        SpriteManager.Instance.welcome_app_banner = await ImageUtil.Instance.GetSpriteFromURLAsync(
            image_url
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

        CommonUtil.CheckLog(
            $"RES+Message: {UserSettingOutPut.message}\nRES+Code: {UserSettingOutPut.code}"
        );

        CommonUtil.CheckLog("RES_Check + getting images");

        SpriteManager.Instance.app_banner.Clear();
        for (int i = 0; i < UserSettingOutPut.app_banner.Count; i++)
        {
            CommonUtil.CheckLog("RES_Check + getting images");
            string app_banner_image_url =
                Configuration.BannerImage + UserSettingOutPut.app_banner[i].banner;
            SpriteManager.Instance.app_banner.Add(
                await ImageUtil.Instance.GetSpriteFromURLAsync(app_banner_image_url)
            );

            SpriteManager.Instance.app_banner_name.Add(UserSettingOutPut.app_banner[i].banner);
        }
        DownloadProfileImage();
    }

    public async void DownloadProfileImage()
    {
        CommonUtil.CheckLog(
            "RES_check + Profile image download 3 "
                + Configuration.ProfileImage
                + Configuration.GetProfilePic()
        );
        string app_avatar_image_url = Configuration.ProfileImage + Configuration.GetProfilePic();
        CommonUtil.CheckLog(
            "RES_check + Avatar " + Configuration.ProfileImage + Configuration.GetProfilePic()
        );
        SpriteManager.Instance.profile_image = await ImageUtil.Instance.GetSpriteFromURLAsync(
            app_avatar_image_url
        );

        GetProfileDetails();

        CommonUtil.CheckLog("RES_check + Profile image download 3");
    }
}
