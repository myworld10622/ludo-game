using System.IO;
using EasyUI.Toast;
using UnityEngine;

public static class CommonUtil
{
    public static void CheckLog(string text)
    {
        Debug.Log($"RES_Check {text}");
    }

    public static void LogError(string text)
    {
        Debug.LogError($"RES_Error {text}");
    }

    public static void ValueLog(string text)
    {
        Debug.Log($"RES_Value {text}");
    }

    public static void ShowToast(string message)
    {
        Toast.Show(message, 3f);
    }

    public static void ShowToastDebug(string message)
    {
        Toast.Show(message, 3f);
    }

    public static string GetFormattedWallet(string wallet = "")
    {
        /*  string walletString = wallet != string.Empty ? wallet : Configuration.GetWallet();

         if (decimal.TryParse(walletString, out decimal userCoins))
         {
             if (userCoins >= 1000)
             {
                 return (userCoins / 1000).ToString(userCoins < 10000 ? "0.0" : "0.#") + "k";
             }
         }
         return Configuration.GetWallet(); 
         */

        string walletString = !string.IsNullOrEmpty(wallet) ? wallet : Configuration.GetWallet();

        if (decimal.TryParse(walletString, out decimal userCoins))
        {
            /*  if (userCoins >= 1000)
             {
                 return (userCoins / 1000).ToString(userCoins < 10000 ? "0.0" : "0.#") + "k";
             } */
            // Format wallet amount to 2 decimal places
            return userCoins.ToString("F2");
        }

        return "0.00"; // Default return value if parsing fails
    }

    public static async void OpenTandC()
    {
        Application.OpenURL(Configuration.TermsAndCondititon);
    }

    public static async void OpenPrivacyPolicy()
    {
        Application.OpenURL(Configuration.PrivacyAndpolicy);
    }
}
// Usage
//  UserWalletText.text = GetFormattedWallet();
