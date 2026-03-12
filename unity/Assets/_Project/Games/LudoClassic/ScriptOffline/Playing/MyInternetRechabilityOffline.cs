using System;
using System.Collections;
using System.Collections.Generic;
using System.Net;
using UnityEngine;
namespace LudoClassicOffline
{

    public class MyInternetRechabilityOffline : MonoBehaviour
    {

        #region Internet Availability Checking

        public static bool IsInternetAvailable => CheckForInternetAvailability();

        static string url = "https://ping.mpl.live";
        static string result = "pong";
        private static bool CheckForInternetAvailability()
        {
            return true;
        }

        private static string GetHtmlFromUri(string resource)
        {
            string html = string.Empty;

            HttpWebRequest request = (HttpWebRequest)WebRequest.Create(resource);
            try
            {
                request.Timeout = 2000; 

                using (HttpWebResponse resp = (HttpWebResponse)request.GetResponse())
                {
                    bool isSuccess = (int)resp.StatusCode < 299 && (int)resp.StatusCode >= 200;

                    return isSuccess ? result : "disconnect";
                }
            }
            catch (Exception e)
            {
                html = string.Empty;
            }

            return html;
        }

        #endregion
    }
}
