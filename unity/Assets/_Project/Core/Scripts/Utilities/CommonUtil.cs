using System.IO;
using EasyUI.Toast;
using UnityEngine;
using UnityEngine.UI;

public static class CommonUtil
{
    private static GameObject statusPopup;
    private static Text statusPopupTitleText;
    private static Text statusPopupMessageText;

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

    public static void ShowStyledMessage(string message, string caption = "Notice", bool isError = false)
    {
        string finalMessage = string.IsNullOrWhiteSpace(message)
            ? "Please try again."
            : message.Trim();

        if (EnsureStatusPopup())
        {
            statusPopupTitleText.text = string.IsNullOrWhiteSpace(caption) ? (isError ? "Error" : "Success") : caption.Trim();
            statusPopupMessageText.text = finalMessage;
            statusPopup.transform.SetAsLastSibling();
            statusPopup.SetActive(true);
            return;
        }

        Toast.Show(finalMessage, 3f);
    }

    public static void ShowToastDebug(string message)
    {
        Toast.Show(message, 3f);
    }

    private static bool EnsureStatusPopup()
    {
        if (statusPopup != null)
        {
            return true;
        }

        if (TryBindExistingStatusPopup())
        {
            return true;
        }

        Canvas parentCanvas = FindPreferredPopupCanvas();
        if (parentCanvas == null)
        {
            return false;
        }

        Font popupFont = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");

        statusPopup = new GameObject(
            "CommonStatusPopup",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        statusPopup.transform.SetParent(parentCanvas.transform, false);
        RectTransform overlayRect = statusPopup.GetComponent<RectTransform>();
        overlayRect.anchorMin = Vector2.zero;
        overlayRect.anchorMax = Vector2.one;
        overlayRect.offsetMin = Vector2.zero;
        overlayRect.offsetMax = Vector2.zero;
        Image overlayImage = statusPopup.GetComponent<Image>();
        overlayImage.color = new Color32(8, 6, 10, 190);

        GameObject card = new GameObject(
            "Card",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Outline)
        );
        card.transform.SetParent(statusPopup.transform, false);
        RectTransform cardRect = card.GetComponent<RectTransform>();
        cardRect.anchorMin = new Vector2(0.5f, 0.5f);
        cardRect.anchorMax = new Vector2(0.5f, 0.5f);
        cardRect.pivot = new Vector2(0.5f, 0.5f);
        cardRect.sizeDelta = new Vector2(560f, 320f);
        Image cardImage = card.GetComponent<Image>();
        cardImage.color = new Color32(44, 10, 18, 245);
        Outline cardOutline = card.GetComponent<Outline>();
        cardOutline.effectColor = new Color32(255, 186, 92, 110);
        cardOutline.effectDistance = new Vector2(2f, -2f);

        GameObject titleBar = new GameObject(
            "TitleBar",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        titleBar.transform.SetParent(card.transform, false);
        RectTransform titleBarRect = titleBar.GetComponent<RectTransform>();
        titleBarRect.anchorMin = new Vector2(0f, 1f);
        titleBarRect.anchorMax = new Vector2(1f, 1f);
        titleBarRect.pivot = new Vector2(0.5f, 1f);
        titleBarRect.sizeDelta = new Vector2(0f, 72f);
        titleBarRect.anchoredPosition = Vector2.zero;
        titleBar.GetComponent<Image>().color = new Color32(118, 18, 28, 255);

        GameObject titleObj = new GameObject(
            "Title",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Text)
        );
        titleObj.transform.SetParent(titleBar.transform, false);
        statusPopupTitleText = titleObj.GetComponent<Text>();
        statusPopupTitleText.font = popupFont;
        statusPopupTitleText.fontSize = 28;
        statusPopupTitleText.fontStyle = FontStyle.Bold;
        statusPopupTitleText.alignment = TextAnchor.MiddleLeft;
        statusPopupTitleText.color = Color.white;
        RectTransform titleRect = statusPopupTitleText.GetComponent<RectTransform>();
        titleRect.anchorMin = new Vector2(0f, 0f);
        titleRect.anchorMax = new Vector2(1f, 1f);
        titleRect.offsetMin = new Vector2(28f, 0f);
        titleRect.offsetMax = new Vector2(-84f, 0f);

        GameObject closeObj = new GameObject(
            "CloseButton",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Button)
        );
        closeObj.transform.SetParent(titleBar.transform, false);
        RectTransform closeRect = closeObj.GetComponent<RectTransform>();
        closeRect.anchorMin = new Vector2(1f, 0.5f);
        closeRect.anchorMax = new Vector2(1f, 0.5f);
        closeRect.pivot = new Vector2(1f, 0.5f);
        closeRect.sizeDelta = new Vector2(46f, 46f);
        closeRect.anchoredPosition = new Vector2(-18f, 0f);
        Image closeImage = closeObj.GetComponent<Image>();
        closeImage.color = new Color32(165, 36, 47, 255);
        Button closeButton = closeObj.GetComponent<Button>();
        closeButton.onClick.AddListener(HideStyledMessage);

        GameObject closeLabelObj = new GameObject(
            "CloseLabel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Text)
        );
        closeLabelObj.transform.SetParent(closeObj.transform, false);
        Text closeLabel = closeLabelObj.GetComponent<Text>();
        closeLabel.font = popupFont;
        closeLabel.fontSize = 22;
        closeLabel.fontStyle = FontStyle.Bold;
        closeLabel.alignment = TextAnchor.MiddleCenter;
        closeLabel.color = Color.white;
        closeLabel.text = "X";
        RectTransform closeLabelRect = closeLabel.GetComponent<RectTransform>();
        closeLabelRect.anchorMin = Vector2.zero;
        closeLabelRect.anchorMax = Vector2.one;
        closeLabelRect.offsetMin = Vector2.zero;
        closeLabelRect.offsetMax = Vector2.zero;

        GameObject messageObj = new GameObject(
            "Message",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Text)
        );
        messageObj.transform.SetParent(card.transform, false);
        statusPopupMessageText = messageObj.GetComponent<Text>();
        statusPopupMessageText.font = popupFont;
        statusPopupMessageText.fontSize = 28;
        statusPopupMessageText.fontStyle = FontStyle.Normal;
        statusPopupMessageText.alignment = TextAnchor.MiddleCenter;
        statusPopupMessageText.color = new Color32(255, 244, 232, 255);
        statusPopupMessageText.horizontalOverflow = HorizontalWrapMode.Wrap;
        statusPopupMessageText.verticalOverflow = VerticalWrapMode.Overflow;
        statusPopupMessageText.lineSpacing = 1.15f;
        RectTransform messageRect = statusPopupMessageText.GetComponent<RectTransform>();
        messageRect.anchorMin = new Vector2(0f, 0f);
        messageRect.anchorMax = new Vector2(1f, 1f);
        messageRect.offsetMin = new Vector2(34f, 30f);
        messageRect.offsetMax = new Vector2(-34f, -94f);

        GameObject okObj = new GameObject(
            "OkButton",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Button)
        );
        okObj.transform.SetParent(card.transform, false);
        RectTransform okRect = okObj.GetComponent<RectTransform>();
        okRect.anchorMin = new Vector2(0.5f, 0f);
        okRect.anchorMax = new Vector2(0.5f, 0f);
        okRect.pivot = new Vector2(0.5f, 0f);
        okRect.sizeDelta = new Vector2(164f, 52f);
        okRect.anchoredPosition = new Vector2(0f, 18f);
        Image okImage = okObj.GetComponent<Image>();
        okImage.color = new Color32(165, 36, 47, 255);
        Button okButton = okObj.GetComponent<Button>();
        okButton.onClick.AddListener(HideStyledMessage);

        GameObject okLabelObj = new GameObject(
            "OkLabel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Text)
        );
        okLabelObj.transform.SetParent(okObj.transform, false);
        Text okLabel = okLabelObj.GetComponent<Text>();
        okLabel.font = popupFont;
        okLabel.fontSize = 24;
        okLabel.fontStyle = FontStyle.Bold;
        okLabel.alignment = TextAnchor.MiddleCenter;
        okLabel.color = Color.white;
        okLabel.text = "OK";
        RectTransform okLabelRect = okLabel.GetComponent<RectTransform>();
        okLabelRect.anchorMin = Vector2.zero;
        okLabelRect.anchorMax = Vector2.one;
        okLabelRect.offsetMin = Vector2.zero;
        okLabelRect.offsetMax = Vector2.zero;

        statusPopup.SetActive(false);
        return true;
    }

    private static bool TryBindExistingStatusPopup()
    {
        GameObject existingPopup = FindSceneObjectByName("CommonStatusPopup");
        if (existingPopup == null)
        {
            return false;
        }

        Text title = FindChildText(existingPopup.transform, "Title");
        Text message = FindChildText(existingPopup.transform, "Message");
        Button closeButton = FindChildButton(existingPopup.transform, "CloseButton");
        Button okButton = FindChildButton(existingPopup.transform, "OkButton");

        if (title == null || message == null || closeButton == null || okButton == null)
        {
            return false;
        }

        statusPopup = existingPopup;
        statusPopupTitleText = title;
        statusPopupMessageText = message;
        closeButton.onClick.RemoveAllListeners();
        closeButton.onClick.AddListener(HideStyledMessage);
        okButton.onClick.RemoveAllListeners();
        okButton.onClick.AddListener(HideStyledMessage);

        RectTransform overlayRect = statusPopup.GetComponent<RectTransform>();
        if (overlayRect != null)
        {
            overlayRect.anchorMin = Vector2.zero;
            overlayRect.anchorMax = Vector2.one;
            overlayRect.offsetMin = Vector2.zero;
            overlayRect.offsetMax = Vector2.zero;
        }

        statusPopup.SetActive(false);
        return true;
    }

    private static GameObject FindSceneObjectByName(string objectName)
    {
        GameObject activeObject = GameObject.Find(objectName);
        if (activeObject != null)
        {
            return activeObject;
        }

        GameObject[] allObjects = Resources.FindObjectsOfTypeAll<GameObject>();
        for (int i = 0; i < allObjects.Length; i++)
        {
            GameObject candidate = allObjects[i];
            if (candidate != null && candidate.name == objectName && candidate.scene.IsValid())
            {
                return candidate;
            }
        }

        return null;
    }

    private static Canvas FindPreferredPopupCanvas()
    {
        Canvas[] canvases = Resources.FindObjectsOfTypeAll<Canvas>();
        string[] preferredNames = { "CanvasMain", "Canvas", "HomePageCanvas", "CanvasOverlay(for popups)" };
        for (int p = 0; p < preferredNames.Length; p++)
        {
            for (int i = 0; i < canvases.Length; i++)
            {
                Canvas canvas = canvases[i];
                if (canvas != null && canvas.gameObject.scene.IsValid() && canvas.name == preferredNames[p])
                {
                    return canvas.rootCanvas != null ? canvas.rootCanvas : canvas;
                }
            }
        }

        for (int i = 0; i < canvases.Length; i++)
        {
            Canvas canvas = canvases[i];
            if (canvas != null && canvas.gameObject.scene.IsValid())
            {
                return canvas.rootCanvas != null ? canvas.rootCanvas : canvas;
            }
        }

        return null;
    }

    private static Text FindChildText(Transform root, string childName)
    {
        Transform child = FindChildTransform(root, childName);
        return child != null ? child.GetComponent<Text>() : null;
    }

    private static Button FindChildButton(Transform root, string childName)
    {
        Transform child = FindChildTransform(root, childName);
        return child != null ? child.GetComponent<Button>() : null;
    }

    private static Transform FindChildTransform(Transform root, string childName)
    {
        if (root == null)
        {
            return null;
        }

        if (root.name == childName)
        {
            return root;
        }

        for (int i = 0; i < root.childCount; i++)
        {
            Transform found = FindChildTransform(root.GetChild(i), childName);
            if (found != null)
            {
                return found;
            }
        }

        return null;
    }

    public static void HideStyledMessage()
    {
        if (statusPopup != null)
        {
            statusPopup.SetActive(false);
        }
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
