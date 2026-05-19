using UnityEngine;
using UnityEngine.UI;

[RequireComponent(typeof(CanvasScaler))]
public class UniversalUIScaler : MonoBehaviour
{
    private static readonly Vector2 PortraitReference = new Vector2(1080f, 1920f);
    private static readonly Vector2 LandscapeReference = new Vector2(1920f, 1080f);
    private static readonly Vector2 DesktopWebPortraitReference = new Vector2(1800f, 3200f);
    private static readonly Vector2 DesktopWebLandscapeReference = new Vector2(3200f, 1800f);

    private int lastScreenWidth;
    private int lastScreenHeight;

    private void Awake()
    {
        ApplyScale();
    }

    private void OnEnable()
    {
        ApplyScale();
    }

    private void Update()
    {
        if (lastScreenWidth == Screen.width && lastScreenHeight == Screen.height)
        {
            return;
        }

        ApplyScale();
    }

    private void OnRectTransformDimensionsChange()
    {
        ApplyScale();
    }

    private void ApplyScale()
    {
        CanvasScaler scaler = GetComponent<CanvasScaler>();
        if (scaler == null)
        {
            return;
        }

        float width = Screen.width > 0 ? Screen.width : 1080f;
        float height = Screen.height > 0 ? Screen.height : 1920f;
        bool landscape = width > height;
        float aspect = width / Mathf.Max(1f, height);

        lastScreenWidth = Screen.width;
        lastScreenHeight = Screen.height;

        scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;

        if (UseDesktopWebCompactLayout())
        {
            scaler.referenceResolution = landscape ? DesktopWebLandscapeReference : DesktopWebPortraitReference;
            scaler.matchWidthOrHeight = 1f;
            return;
        }

        scaler.referenceResolution = landscape ? LandscapeReference : PortraitReference;

        if (landscape)
        {
            scaler.matchWidthOrHeight = 0.5f;
        }
        else
        {
            scaler.matchWidthOrHeight = aspect > 0.68f ? 1f : 0f;
        }
    }

    private static bool UseDesktopWebCompactLayout()
    {
#if UNITY_WEBGL && !UNITY_EDITOR
        return !Application.isMobilePlatform;
#else
        return false;
#endif
    }
}
