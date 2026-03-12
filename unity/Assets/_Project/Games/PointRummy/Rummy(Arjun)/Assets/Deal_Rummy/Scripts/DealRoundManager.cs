using System;
using System.Collections;
using System.Collections.Generic;
using AndroApps;
using DG.Tweening;
using EasyUI.Toast;
using Mkey;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

[Serializable]
public class DealTableEntry
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string total_points;
    public string card;
    public string card_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
    public string user_type;
    public string fcm;
    public string boot_value;
    public string online_members;
}

[Serializable]
public class DealTableData
{
    public string message;
    public List<DealTableEntry> table_data;
    public int code;
}

public class DealRoundManager : MonoBehaviour
{
    // public string boot;
    // public string playerNo;
    public DRummy_Data data;

    public Transform tableparent;
    public GameObject tableprefab;
    public List<GameObject> listofroom = new List<GameObject>();
    private string rummydealobbiesurl = Configuration.RummyDealGettablemaster;

    Button[] buttons;

    void Start()
    {
        StartCoroutine(DealPostRequestcheckfortable(rummydealobbiesurl));
    }

    IEnumerator DealPostRequestcheckfortable(string url)
    {
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());

        using (UnityWebRequest request = UnityWebRequest.Post(url, form))
        {
            if (Configuration.TokenLoginHeader != null)
            {
                request.SetRequestHeader("Token", Configuration.TokenLoginHeader);
            }
            else
            {
                Debug.LogError("Error: TokenLogIn is null.");
                yield break;
            }

            yield return request.SendWebRequest();

            if (
                request.result == UnityWebRequest.Result.ConnectionError
                || request.result == UnityWebRequest.Result.ProtocolError
            )
            {
                Debug.LogError("Error: " + request.error);
            }
            else
            {
                string response = request.downloadHandler.text;
                Debug.Log("table_list Response: " + response);
                DealTableData dealresponseData = JsonUtility.FromJson<DealTableData>(response);

                if (dealresponseData.code == 200)
                {
                    // data.Id = dealresponseData.table_data[0].id;
                    PlayerPrefs.SetString("Getdealid", dealresponseData.table_data[0].id);
                    int num = dealresponseData.table_data.Count;

                    for (int i = 0; i < num; i++)
                    {
                        GameObject data = Instantiate(tableprefab);
                        data.transform.parent = tableparent;
                        data.transform.localScale = new Vector3(1, 1, 1);
                        listofroom.Add(data);
                    }

                    for (int i = 0; i < listofroom.Count; i++)
                    {
                        int roomindex = i;
                        listofroom[i].transform.GetChild(0).GetComponent<Text>().text = (
                            i + 1
                        ).ToString();
                        listofroom[i].transform.GetChild(1).GetComponent<Text>().text =
                            dealresponseData.table_data[i].boot_value;
                        listofroom[i].transform.GetChild(2).GetComponent<Text>().text =
                            dealresponseData.table_data[i].online_members;
                        listofroom[i]
                            .transform.GetChild(3)
                            .GetComponent<Button>()
                            .onClick.AddListener(
                                () => DealRummyClickButton(dealresponseData.table_data[roomindex])
                            );
                    }
                }
                if (dealresponseData.code == 205) // you are already on table
                {
                    // data.boot_value = dealresponseData.table_data[0].boot_value;
                    // data.Id = dealresponseData.table_data[0].id;
                    // data.no_of_players = dealresponseData.table_data[0].online_members;
                    PlayerPrefs.SetString("Getdealboot", dealresponseData.table_data[0].boot_value);
                    PlayerPrefs.SetString("Getdealid", dealresponseData.table_data[0].id);
                    PlayerPrefs.SetString(
                        "Getdealplayer",
                        dealresponseData.table_data[0].online_members
                    );
                    DOVirtual.DelayedCall(.3f, () =>
                    {
                        //Addressables.LoadSceneAsync("DealRummy.unity");
                        SceneLoader.Instance.LoadScene("DealRummy");

                    });
                    //this.GetComponent<GameSelection>().loaddynamicscenebyname("DealRummy.unity");
                }
            }
        }
    }

    public void DealRummyClickButton(DealTableEntry tableEntry)
    {
        // data.boot_value = tableEntry.boot_value;
        // data.Id = tableEntry.id;
        // data.no_of_players = tableEntry.online_members;
        // data.Id = tableEntry.id;
        PlayerPrefs.SetString("Getdealboot", tableEntry.boot_value);
        PlayerPrefs.SetString("Getdealid", tableEntry.id);
        PlayerPrefs.SetString("Getdealplayer", tableEntry.online_members);
        this.GetComponent<GameSelection>().loaddynamicscenebyname("DealRummy");
    }

    // public void DealSelectButton(string bootvalue)
    // {
    //     boot = bootvalue;
    //     data.boot_value = boot;
    //     Debug.Log(data.boot_value);
    //     Debug.Log(boot);
    // }

    public void DealSelectnoofplayersButton(string playernumber)
    {
        // playerNo = playernumber;
        // data.no_of_players = playerNo;
        // Debug.Log(data.no_of_players);

        // DOVirtual.DelayedCall(
        //     .2f,
        //     () =>
        //     {
        //         startdealrummy();
        //     }
        // );
    }

    public void startdealrummy()
    {
        // if (
        //     boot == string.Empty
        //     || data.boot_value == string.Empty
        //     || playerNo == string.Empty
        //     || data.no_of_players == string.Empty
        // )
        // {
        //     showtoastmessage("Please Select Rounds");
        // }
        // else
        //     this.GetComponent<GameSelection>().loaddynamicscenebyname("DealRummy.unity");
    }

    public void LoadHomeScene()
    {
        this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }
}
