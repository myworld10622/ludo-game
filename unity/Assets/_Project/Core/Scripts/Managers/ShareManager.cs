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

        // Try the deep-link first; Android will fall back to browser if Telegram isn't installed
        Application.OpenURL(telegramAppUrl);
        CommonUtil.CheckLog("Telegram share URL: " + telegramWebUrl);
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

}
