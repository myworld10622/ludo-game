using UnityEngine;
using System.Runtime.InteropServices;

public static class WebGlOrientationBridge
{
#if UNITY_WEBGL && !UNITY_EDITOR
    [DllImport("__Internal")] private static extern void RoxSetDesiredOrientation(string mode);
#endif

    public static void SetPortrait()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        RoxSetDesiredOrientation("portrait");
#endif
    }

    public static void SetLandscape()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        RoxSetDesiredOrientation("landscape");
#endif
    }
}
