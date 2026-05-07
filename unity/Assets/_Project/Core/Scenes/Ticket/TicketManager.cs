using System;
using System.Collections;
using System.Collections.Generic;
using System.Globalization;
using System.Threading.Tasks;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class TicketManager : MonoBehaviour
{
    public Transform content;
    public GameObject prefab,
        generateTicketPanel;
    public List<GameObject> ticketPrefabs = new List<GameObject>();
    public TMP_InputField descriptionField;
    public TextMeshProUGUI categoryLabel;
    public Image ticketImage;
    public GameObject ticketlogoImage;
    public Sprite defaultImage;
    private string categoryNumber;
    private GameObject _supportRoot;

    private void OnEnable()
    {
        ResetTicketImage();
        OpenTelegramSupportAndClose();
    }

    public void OnUpdateTicketImageButtonClick(string target)
    {
        OpenTelegramSupportAndClose();
    }

    public async Task UpdateTicketImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    public void GenerateTicket()
    {
        OpenTelegramSupportAndClose();
    }

    private async void GenerateTicketAsync()
    {
        OpenTelegramSupport();
    }

    private async void DisplayTicketsAsync()
    {
        OpenTelegramSupportAndClose();
    }

    public void ResetTicketImage()
    {
        if (descriptionField != null)
        {
            descriptionField.text = "";
        }
        if (ticketImage != null)
        {
            ticketImage.sprite = defaultImage;
            if (ticketImage.transform.parent != null)
            {
                ticketImage.transform.parent.gameObject.SetActive(false);
            }
        }
        if (ticketlogoImage != null)
        {
            ticketlogoImage.SetActive(true);
        }
    }

    private void ShowTelegramSupportView()
    {
        ClearExistingTickets();
        if (generateTicketPanel != null)
        {
            generateTicketPanel.SetActive(false);
        }
        if (content != null && content.parent != null)
        {
            content.parent.gameObject.SetActive(false);
        }

        EnsureSupportView();
        if (_supportRoot != null)
        {
            _supportRoot.SetActive(true);
            _supportRoot.transform.SetAsLastSibling();
        }
    }

    private void EnsureSupportView()
    {
        if (_supportRoot != null)
        {
            return;
        }

        Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        Transform panelRoot = transform;

        _supportRoot = new GameObject(
            "Telegram-Support-View",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        _supportRoot.transform.SetParent(panelRoot, false);
        RectTransform rootRect = _supportRoot.GetComponent<RectTransform>();
        rootRect.anchorMin = new Vector2(0.5f, 0.5f);
        rootRect.anchorMax = new Vector2(0.5f, 0.5f);
        rootRect.pivot = new Vector2(0.5f, 0.5f);
        rootRect.sizeDelta = new Vector2(760f, 520f);
        rootRect.anchoredPosition = new Vector2(0f, -20f);
        Image rootImage = _supportRoot.GetComponent<Image>();
        rootImage.color = new Color32(60, 10, 18, 220);

        GameObject title = CreateSupportText(_supportRoot.transform, font, "Support Chat", 36, Color.white, TextAnchor.MiddleCenter, FontStyle.Bold);
        RectTransform titleRect = title.GetComponent<RectTransform>();
        titleRect.anchorMin = new Vector2(0.5f, 1f);
        titleRect.anchorMax = new Vector2(0.5f, 1f);
        titleRect.pivot = new Vector2(0.5f, 1f);
        titleRect.sizeDelta = new Vector2(420f, 60f);
        titleRect.anchoredPosition = new Vector2(0f, -40f);

        GameObject subtitle = CreateSupportText(_supportRoot.transform, font, "For any issue, contact support directly on Telegram.", 28, new Color32(255, 228, 190, 255), TextAnchor.MiddleCenter, FontStyle.Normal);
        RectTransform subtitleRect = subtitle.GetComponent<RectTransform>();
        subtitleRect.anchorMin = new Vector2(0.5f, 1f);
        subtitleRect.anchorMax = new Vector2(0.5f, 1f);
        subtitleRect.pivot = new Vector2(0.5f, 1f);
        subtitleRect.sizeDelta = new Vector2(640f, 90f);
        subtitleRect.anchoredPosition = new Vector2(0f, -120f);

        GameObject handle = CreateSupportText(_supportRoot.transform, font, "@John_bz1122", 32, new Color32(255, 196, 90, 255), TextAnchor.MiddleCenter, FontStyle.Bold);
        RectTransform handleRect = handle.GetComponent<RectTransform>();
        handleRect.anchorMin = new Vector2(0.5f, 1f);
        handleRect.anchorMax = new Vector2(0.5f, 1f);
        handleRect.pivot = new Vector2(0.5f, 1f);
        handleRect.sizeDelta = new Vector2(360f, 54f);
        handleRect.anchoredPosition = new Vector2(0f, -210f);

        Button contactButton = CreateSupportButton(_supportRoot.transform, font, "Contact Here", new Vector2(360f, 92f), new Vector2(0f, -300f), new Color32(24, 128, 42, 255));
        contactButton.onClick.AddListener(OpenTelegramSupport);

        Button supportButton = CreateSupportButton(_supportRoot.transform, font, "Support Chat", new Vector2(360f, 92f), new Vector2(0f, -410f), new Color32(118, 18, 28, 255));
        supportButton.onClick.AddListener(OpenTelegramSupport);

        _supportRoot.SetActive(false);
    }

    private void OpenTelegramSupport()
    {
        CommonUtil.CheckLog("Opening Telegram support " + Configuration.TelegramSupportHandle);
        Application.OpenURL(Configuration.TelegramSupportUrl);
    }

    private void OpenTelegramSupportAndClose()
    {
        OpenTelegramSupport();
        PopUpUtil.ButtonCancel(gameObject);
    }

    private static GameObject CreateSupportText(Transform parent, Font font, string value, int fontSize, Color color, TextAnchor anchor, FontStyle style)
    {
        GameObject go = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(TextMeshProUGUI));
        go.transform.SetParent(parent, false);
        TextMeshProUGUI text = go.GetComponent<TextMeshProUGUI>();
        text.text = value;
        text.fontSize = fontSize;
        text.color = color;
        text.alignment = anchor switch
        {
            TextAnchor.MiddleCenter => TextAlignmentOptions.Center,
            TextAnchor.MiddleLeft => TextAlignmentOptions.Left,
            _ => TextAlignmentOptions.Center
        };
        text.fontStyle = style == FontStyle.Bold ? FontStyles.Bold : FontStyles.Normal;
        text.enableWordWrapping = true;
        return go;
    }

    private static Button CreateSupportButton(Transform parent, Font font, string label, Vector2 size, Vector2 anchoredPosition, Color32 color)
    {
        GameObject buttonGo = new GameObject(
            label.Replace(" ", string.Empty) + "-Button",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Button),
            typeof(Outline)
        );
        buttonGo.transform.SetParent(parent, false);
        RectTransform rect = buttonGo.GetComponent<RectTransform>();
        rect.anchorMin = new Vector2(0.5f, 1f);
        rect.anchorMax = new Vector2(0.5f, 1f);
        rect.pivot = new Vector2(0.5f, 1f);
        rect.sizeDelta = size;
        rect.anchoredPosition = anchoredPosition;

        Image image = buttonGo.GetComponent<Image>();
        image.color = color;
        image.type = Image.Type.Sliced;

        Outline outline = buttonGo.GetComponent<Outline>();
        outline.effectColor = new Color32(255, 190, 90, 120);
        outline.effectDistance = new Vector2(2f, -2f);

        GameObject textObj = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(TextMeshProUGUI));
        textObj.transform.SetParent(buttonGo.transform, false);
        RectTransform textRect = textObj.GetComponent<RectTransform>();
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = Vector2.zero;
        textRect.offsetMax = Vector2.zero;

        TextMeshProUGUI text = textObj.GetComponent<TextMeshProUGUI>();
        text.text = label;
        text.fontSize = 30;
        text.color = Color.white;
        text.alignment = TextAlignmentOptions.Center;
        text.fontStyle = FontStyles.Bold;
        text.raycastTarget = false;

        return buttonGo.GetComponent<Button>();
    }

    private void ClearExistingTickets()
    {
        foreach (var prefab in ticketPrefabs)
        {
            Destroy(prefab);
        }
        ticketPrefabs.Clear();
    }

    private void CreateTicketPrefab(Ticket ticket)
    {
        GameObject go = Instantiate(prefab, content);

        go.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = (
            ticketPrefabs.Count + 1
        ).ToString();
        go.transform.GetChild(1).GetComponent<TextMeshProUGUI>().text = ticket.description;
        go.transform.GetChild(2).GetComponent<TextMeshProUGUI>().text = GetTicketStatus(
            ticket.status
        );
        go.transform.GetChild(3).GetComponent<TextMeshProUGUI>().text = GetCategoryName(
            ticket.category
        );
        go.transform.GetChild(4).GetComponent<TextMeshProUGUI>().text = FormatDate(
            ticket.added_date
        );

        ticketPrefabs.Add(go);
    }

    private string GetCategoryNumber(string categoryName)
    {
        return categoryName switch
        {
            "Withdraw" => "1",
            "Deposit" => "2",
            "Others" => "3",
            _ => string.Empty,
        };
    }

    private string GetCategoryName(string categoryNumber)
    {
        return categoryNumber switch
        {
            "1" => "Withdraw",
            "2" => "Deposit",
            "3" => "Others",
            _ => "Unknown",
        };
    }

    private string GetTicketStatus(string status)
    {
        return status switch
        {
            "0" => "Pending",
            "1" => "Processing",
            "2" => "Resolved",
            _ => "Unknown",
        };
    }

    private string FormatDate(string inputDate)
    {
        if (
            DateTime.TryParseExact(
                inputDate,
                "yyyy-MM-dd HH:mm:ss",
                CultureInfo.InvariantCulture,
                DateTimeStyles.None,
                out DateTime dateTime
            )
        )
        {
            return $"{dateTime:dd-MMM-yy}\n{dateTime:hh:mm tt}";
        }
        return "Invalid Date";
    }
}
