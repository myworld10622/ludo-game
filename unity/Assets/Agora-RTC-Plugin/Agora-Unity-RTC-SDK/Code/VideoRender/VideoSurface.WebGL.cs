#if UNITY_WEBGL && !UNITY_EDITOR

using UnityEngine;

namespace Agora.Rtc
{
    public enum VideoSurfaceType
    {
        Renderer = 0,
        RawImage = 1,
    }

    public delegate void OnTextureSizeModifyHandler(int width, int height);

    // WebGL production build only needs the type to exist so Agora API-Example
    // scripts can compile. The native video-render path is not available here.
    public sealed class VideoSurface : MonoBehaviour
    {
        public event OnTextureSizeModifyHandler OnTextureSizeModify;

        public void SetForUser(uint uid = 0, string channelId = "",
            VIDEO_SOURCE_TYPE sourceType = VIDEO_SOURCE_TYPE.VIDEO_SOURCE_CAMERA_PRIMARY)
        {
        }

        public void SetEnable(bool enable)
        {
        }

        internal void RaiseTextureSizeModify(int width, int height)
        {
            OnTextureSizeModify?.Invoke(width, height);
        }
    }
}

#endif
