using System;
using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class NotificationManager : MonoBehaviour
{
    public GameObject prefab;
    public Transform parent;
    public List<GameObject> prefabs;
    public GameObject NOdata;
    public GameObject notificationbannerimg;

    async void OnEnable()
    {
        await ShowNotifications();
    }

    public async Task ShowNotifications()
    {
        string Url = Configuration.Get_Notification;
        Debug.Log("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        ResponseDataNotification data = new ResponseDataNotification();
        data = await APIManager.Instance.Post<ResponseDataNotification>(Url, formData);
        for (int j = 0; j < prefabs.Count; j++)
        {
            Destroy(prefabs[j]);
        }

        prefabs.Clear();

        if (data.code == 200)
        {
            if (data.notification.Length > 0)
            {
                NOdata.SetActive(false);
            }
            else
            {
                NOdata.SetActive(true);
            }
            int index = 0;
            for (int i = 0; i < data.notification.Length; i++)
            {
                index = i;
                GameObject go = Instantiate(prefab, parent);
                go.transform.GetChild(1).GetComponent<TextMeshProUGUI>().text = (i + 1) + "";
                go.transform.GetChild(2).GetComponent<TextMeshProUGUI>().text = data.notification[
                    i
                ].msg;
                go.transform.GetChild(3).GetComponent<TextMeshProUGUI>().text = FormatDateTime(
                    data.notification[i].added_date
                );
                string img = data.notification[index].image;
                string url = data.notification[index].url;
                Debug.Log("RES_Check + notification image " + data.notification[index].image);
                Debug.Log("RES_Check + notification image 2 " + img);
                go.GetComponent<Button>().onClick.AddListener(() => GetImage(img));
                prefabs.Add(go);
            }
        }
        else
        {
            NOdata.SetActive(true);
            LoaderUtil.instance.ShowToast(data.message);
        }
    }

    public async void GetImage(string img)
    {
        string profile_url = Configuration.NotificationBannerImage + img;
        notificationbannerimg.transform.GetChild(0).GetChild(0).GetComponent<Image>().sprite =
            await ImageUtil.Instance.GetSpriteFromURLAsync(profile_url);
        notificationbannerimg.gameObject.SetActive(true);
    }

    public string FormatDateTime(string inputDateTime)
    {
        // Parse input date time string
        DateTime dateTime = DateTime.ParseExact(
            inputDateTime,
            "yyyy-MM-dd HH:mm:ss",
            System.Globalization.CultureInfo.InvariantCulture
        );

        // Format date part (dd-mmm-yy)
        string formattedDate =
            dateTime.ToString("dd")
            + "-"
            + GetMonthAbbreviation(dateTime.Month)
            + "-"
            + dateTime.ToString("yy");

        // Format time part (hh.mm AM/PM)
        string formattedTime =
            dateTime.ToString("hh:mm") + " " + (dateTime.Hour >= 12 ? "PM" : "AM");

        return formattedDate + "\n" + formattedTime;
        // return formattedDate + "\n" + formattedTime;
    }

    private string GetMonthAbbreviation(int month)
    {
        switch (month)
        {
            case 1:
                return "Jan";
            case 2:
                return "Feb";
            case 3:
                return "Mar";
            case 4:
                return "Apr";
            case 5:
                return "May";
            case 6:
                return "Jun";
            case 7:
                return "Jul";
            case 8:
                return "Aug";
            case 9:
                return "Sep";
            case 10:
                return "Oct";
            case 11:
                return "Nov";
            case 12:
                return "Dec";
            default:
                return "";
        }
    }
}
