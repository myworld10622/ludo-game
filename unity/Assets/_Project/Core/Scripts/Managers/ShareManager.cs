using System.Collections;
using System.Collections.Generic;
using System.Text;
using UnityEngine;

public class ShareManager : MonoBehaviour
{
    private string playStoreUrl = Configuration.BaseUrl;

    public void ShareLinkViaWhatsApp()
    {
        string message = BuildShareMessage();
        ShareToWhatsApp(message);
    }

    public void ShareLinkViaTelegram()
    {
        string message = BuildShareMessage();
        ShareToTelegram(message);
    }

    public void ShareLinkViaEmail()
    {
        string subject = "Check out this app!";
        string message = BuildShareMessage();
        ShareToEmail(subject, message);
    }

    private string BuildShareMessage()
    {
        StringBuilder messageBuilder = new StringBuilder();
        messageBuilder
            .Append(PlayerPrefs.GetString("share_text"))
            .Append(" Use Refer code: ")
            .Append(PlayerPrefs.GetString("referral_code"))
            .Append("\n Download the app from: \n")
            .Append(PlayerPrefs.GetString("referral_link")).Append("?ref=777-" + PlayerPrefs.GetString("referral_code"));
        return messageBuilder.ToString();
    }

    private void ShareToWhatsApp(string message)
    {

        string url = "https://api.whatsapp.com/send?text=" + WWW.EscapeURL(message);
        Application.OpenURL(url);

        CommonUtil.CheckLog("Sharing not supported on this platform.");
    }

    private void ShareToTelegram(string message)
    {
        string encodedMessage = WWW.EscapeURL(message);

        string telegramAppUrl = $"tg://msg?text={encodedMessage}";
        string telegramWebUrl = $"https://t.me/share/url?url={encodedMessage}";

        CommonUtil.CheckLog("Telegram URL " + telegramWebUrl);


#if UNITY_ANDROID
        if (IsAppInstalled("org.telegram.messenger"))
        {
            CommonUtil.CheckLog(" Telegram URL android app");

            Application.OpenURL(telegramAppUrl);
        }
        else
        {
            CommonUtil.CheckLog("Telegram app not found. Opening web URL.");
            Application.OpenURL(telegramWebUrl);
        }
#else
        CommonUtil.CheckLog(" Telegram URL Web");
        Application.OpenURL(telegramWebUrl);
#endif
    }

    private void ShareToEmail(string subject, string body)
    {
        string email = "mailto:?subject=" + WWW.EscapeURL(subject) + "&body=" + WWW.EscapeURL(body);
        Application.OpenURL(email);
        /* #if UNITY_ANDROID

        #else */
        CommonUtil.CheckLog("Sharing not supported on this platform." + email);
        //#endif
    }

    private bool IsAppInstalled(string bundleId)
    {
#if UNITY_ANDROID
        AndroidJavaClass unityPlayer = new AndroidJavaClass("com.unity3d.player.UnityPlayer");
        AndroidJavaObject currentActivity = unityPlayer.GetStatic<AndroidJavaObject>("currentActivity");
        AndroidJavaObject packageManager = currentActivity.Call<AndroidJavaObject>("getPackageManager");

        try
        {
            packageManager.Call<AndroidJavaObject>("getPackageInfo", bundleId, 0);
            return true;
        }
        catch
        {
            return false;
        }
#else
        return false;
#endif
    }
}
