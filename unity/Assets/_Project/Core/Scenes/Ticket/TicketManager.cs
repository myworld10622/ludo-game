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

    private void OnEnable()
    {
        ResetTicketImage();
        DisplayTicketsAsync();
    }

    public void OnUpdateTicketImageButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery("ticket", ticketImage, ticketlogoImage);

        ticketImage.transform.parent.gameObject.SetActive(true);
        ticketlogoImage.SetActive(false);
    }

    public async Task UpdateTicketImage(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    public void GenerateTicket()
    {
        if (string.IsNullOrWhiteSpace(descriptionField.text))
        {
            LoaderUtil.instance.ShowToast("Please Enter Description and Screenshot");
            return;
        }
        else if (ticketlogoImage.gameObject.activeInHierarchy)
        {
            LoaderUtil.instance.ShowToast("Please upload Screenshot");
            return;
        }

        GenerateTicketAsync();
    }

    private async void GenerateTicketAsync()
    {
        categoryNumber = GetCategoryNumber(categoryLabel.text);
        if (string.IsNullOrEmpty(categoryNumber))
        {
            LoaderUtil.instance.ShowToast("Invalid Category");
            return;
        }

        string url = Configuration.createticket;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "description", descriptionField.text },
            { "category", categoryNumber },
        };

        try
        {
            var response = await APIManager.Instance.Post<messageprint>(url, formData);
            LoaderUtil.instance.ShowToast(response.message);
            DisplayTicketsAsync();
        }
        catch (Exception ex)
        {
            CommonUtil.CheckLog($"Failed to create ticket: {ex.Message}");
        }
    }

    private async void DisplayTicketsAsync()
    {
        generateTicketPanel.SetActive(false);
        string url = Configuration.getticket;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        try
        {
            var ticketData = await APIManager.Instance.Post<TicketRootObject>(url, formData);
            ClearExistingTickets();

            for (int i = 0; i < ticketData.tickets.Count; i++)
            {
                CreateTicketPrefab(ticketData.tickets[i]);
            }
            //PopUpUtil.ButtonCancel(generateTicketPanel);
        }
        catch (Exception ex)
        {
            CommonUtil.CheckLog($"Failed to fetch tickets: {ex.Message}");
        }
    }

    public void ResetTicketImage()
    {
        descriptionField.text = "";
        ticketImage.sprite = defaultImage;
        ticketlogoImage.SetActive(true);
        ticketImage.transform.parent.gameObject.SetActive(false);
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
