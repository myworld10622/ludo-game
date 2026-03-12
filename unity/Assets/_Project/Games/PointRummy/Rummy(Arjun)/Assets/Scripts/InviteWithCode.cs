using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class InviteWithCode : MonoBehaviour
{
    public string messageToShare = "Check out this awesome app!";
    public RummyScriptable rum;

    //public TeenPattiData tpdata;

    void Start() { }

    // public void OnInviteButtonClickTP()
    // {
    //     string msg = messageToShare + " " + tpdata.tableid + "/n" + "Please download the game Via: " + Configuration.BaseUrl;
    //     ShareText(msg);
    // }

    public void OnInviteButtonClick()
    {
        string msg =
            messageToShare
            + " "
            + rum.tableCode
            + "/n"
            + "Please download the game Via: "
            + Configuration.BaseUrl;
        ShareText(msg);
    }

    void ShareText(string message)
    {
#if UNITY_ANDROID
        AndroidJavaClass intentClass = new AndroidJavaClass("android.content.Intent");
        AndroidJavaObject intentObject = new AndroidJavaObject("android.content.Intent");

        intentObject.Call<AndroidJavaObject>(
            "setAction",
            intentClass.GetStatic<string>("ACTION_SEND")
        );
        intentObject.Call<AndroidJavaObject>(
            "putExtra",
            intentClass.GetStatic<string>("EXTRA_TEXT"),
            message
        );
        intentObject.Call<AndroidJavaObject>("setType", "text/plain");

        AndroidJavaClass unity = new AndroidJavaClass("com.unity3d.player.UnityPlayer");
        AndroidJavaObject currentActivity = unity.GetStatic<AndroidJavaObject>("currentActivity");
        AndroidJavaObject chooser = intentClass.CallStatic<AndroidJavaObject>(
            "createChooser",
            intentObject,
            "Share via"
        );

        currentActivity.Call("startActivity", chooser);
#endif
    }
}
