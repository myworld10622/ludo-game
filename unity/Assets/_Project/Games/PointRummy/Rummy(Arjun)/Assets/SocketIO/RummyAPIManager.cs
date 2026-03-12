using System;
using System.Collections;
using System.Collections.Generic;
using AndroApps;
using DG.Tweening;
using EasyUI.Toast;
using Mkey;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

[System.Serializable]
public class CardDatas
{
    public List<CardItem> cards;
    public string message;
    public int code;
}

[System.Serializable]
public class CardItem
{
    public string id;
    public string cards;
}

[System.Serializable]
public class PoolTableData
{
    public string id;
    public string boot_value;
    public string pool_point;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string winning_amount;
    public string name;
    public string founder_id;
    public string max_player;
    public string invitation_code;
    public string password;
    public string viewer_status;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string online_members;
    public string no_of_players;
}

[System.Serializable]
public class PoolResponseData
{
    public string message;
    public List<PoolTableData> table_data;
    public int code;
}

[Serializable]
public class LudoTableData
{
    public string id;
    public string room_id;
    public string boot_value;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string online_members;
    public string no_of_players;
}

[Serializable]
public class LudoRoot
{
    public string message;
    public List<TableData> table_data;
    public int code;
}

public class RummyAPIManager : MonoBehaviour
{
    private string apiUrl = Configuration.GetTableAPIURL;
    private string lobbiesurl = Configuration.RummyGettablemaster;
    private string rummypoollobbiesurl = Configuration.RummyPoolGettablemaster;
    public string[] cardNames;
    public ResponseData responseData;

    public PoolResponseData poolresponseData;
    public PoolrummyData pool_data;

    public RummyScriptable rum;

    //public PoolrummyData pool_data;

    public string name;

    public string lobbyname;

    public string Playernumber = "";

    public GameObject tableprefab;

    public Transform tableparent;

    public List<GameObject> listofroom;

    //public GameObject table, numberUI;

    public List<PlayerData> playerList = new List<PlayerData>();

    public LudoRoot LudoTableList = new LudoRoot();

    public Button[] buttons;

    public GameObject m_teenpatti;
    public GameObject m_DealRummy;
    public GameObject m_PoolRummy;
    public GameObject m_PointRummy;
    public GameObject m_Pocker;

    public GameObject TOPPlayerSelection;

    private void OnEnable()
    {
        playerselectbutton("2");
        // buttons = Resources.FindObjectsOfTypeAll<Button>();
        // if (lobbyname == "Pool")
        //     StartCoroutine(PoolPostRequestcheckfortable(rummypoollobbiesurl));
        // else if (lobbyname == "Rummy")
        //     StartCoroutine(PostRequestCheckOnTable(lobbiesurl));
    }

    public void SendLobbyRequest()
    {
        if (tableparent != null)
        {
            foreach (Transform item in tableparent.transform.transform)
            {
                Destroy(item.gameObject);
            }
        }

        if (Playernumber == string.Empty || Playernumber == "0")
        {
            showtoastmessage("Please Select Players");
        }
        else
        {
            // m_teenpatti.SetActive(false);
            // m_PointRummy.SetActive(false);
            // TOPPlayerSelection.SetActive(false);
            // m_PoolRummy.SetActive(false);
            // m_DealRummy.SetActive(false);
            // m_Pocker.SetActive(false);

            if (lobbyname == "Pool")
            {
                TOPPlayerSelection.SetActive(true);
                //m_PoolRummy.SetActive(true);
                StartCoroutine(PoolPostRequest(rummypoollobbiesurl));
            }
            else if (lobbyname == "Rummy") //rummy means pointrummy
            {
                TOPPlayerSelection.SetActive(true);
                m_PointRummy.SetActive(true);

                StartCoroutine(PostRequest(lobbiesurl));
            }
            else if (lobbyname == "Teenpatti")
            {
                m_teenpatti.SetActive(true);
                TOPPlayerSelection.SetActive(false);
            }
            else if (lobbyname == "Deal")
            {
                m_DealRummy.SetActive(true);
            }
            else if (lobbyname == "Poker")
            {
                m_Pocker.SetActive(true);
            }
            else if (lobbyname == "Ludo")
            {
                StartCoroutine(LudoPostRequest(Configuration.LudoGettablemaster));
            }
        }
        // if (lobbyname == "Pool")
        //     StartCoroutine(PoolPostRequest(rummypoollobbiesurl));
        // else if (lobbyname == "Rummy")
        //     StartCoroutine(PostRequest(lobbiesurl));
        // else if (lobbyname == "Ludo")
        // {
        //     StartCoroutine(LudoPostRequest(Configuration.LudoGettablemaster));
        // }
    }

    IEnumerator LudoPostRequest(string url)
    {
        if (listofroom.Count > 0)
        {
            for (int i = 0; i < listofroom.Count; i++)
            {
                Destroy(listofroom[i]);
            }
            listofroom.Clear();
        }
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("no_of_players", Playernumber);
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
                Debug.LogError("RES_Check + Error: " + request.error);
            }
            else
            {
                string response = request.downloadHandler.text;
                Debug.Log("RES_Check + Ludo table_list Response: " + response);

                LudoTableList = JsonUtility.FromJson<LudoRoot>(response);

                //InternetCheck.Instance.checkinvalid(LudoTableList.code);

                if (LudoTableList.code == 200)
                {
                    int num = LudoTableList.table_data.Count;

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
                            LudoTableList.table_data[i].boot_value;
                        listofroom[i].transform.GetChild(2).GetComponent<Text>().text =
                            LudoTableList.table_data[i].online_members;
                        Debug.Log(
                            "Parent "
                                + listofroom[i]
                                    .transform.GetChild(3)
                                    .GetComponent<Button>()
                                    .transform.parent
                        );
                        //listofroom[i].transform.GetChild(4).GetComponent<Button>().onClick.AddListener(() => rummyscene(listofroom[i].transform.GetChild(4).GetComponent<Button>().transform.parent));
                        listofroom[i]
                            .transform.GetChild(3)
                            .GetComponent<Button>()
                            .onClick.AddListener(
                                () =>
                                    ludoscene(
                                        listofroom[roomindex]
                                            .transform.GetChild(3)
                                            .GetComponent<Button>()
                                            .transform.parent
                                    )
                            );
                        //      numberUI?.SetActive(false);
                        //    table?.SetActive(true);
                    }
                }
            }
        }
    }

    public void ludoscene(Transform parent)
    {
        // newAudioManager.PlayButtonSound();
        // ludo_data.no_of_players = Playernumber;
        // ludo_data.boot_value = parent.GetChild(1).GetComponent<Text>().text;
        // if (float.Parse(ludo_data.boot_value) > float.Parse(Configuration.GetWallet()))
        // {
        //     showtoastmessage("Insufficient Balance");
        // }
        // else
        //     SceneManager.LoadScene("Ludo"); //NewOnline
    }

    IEnumerator PoolPostRequest(string url)
    {
        if (listofroom.Count > 0)
        {
            for (int i = 0; i < listofroom.Count; i++)
            {
                Destroy(listofroom[i]);
            }
            listofroom.Clear();
        }
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("no_of_players", Playernumber);
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

                poolresponseData = JsonUtility.FromJson<PoolResponseData>(response);

                Debug.Log("table_list code: " + poolresponseData.code);

                if (poolresponseData.code == 200)
                {
                    int num = poolresponseData.table_data.Count;

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
                            "Pool-" + poolresponseData.table_data[i].pool_point;
                        listofroom[i].transform.GetChild(2).GetComponent<Text>().text =
                            poolresponseData.table_data[i].boot_value;
                        listofroom[i].transform.GetChild(3).GetComponent<Text>().text =
                            poolresponseData.table_data[i].online_members;
                        Debug.Log(
                            "Parent "
                                + listofroom[i]
                                    .transform.GetChild(4)
                                    .GetComponent<Button>()
                                    .transform.parent
                        );
                        //listofroom[i].transform.GetChild(4).GetComponent<Button>().onClick.AddListener(() => rummyscene(listofroom[i].transform.GetChild(4).GetComponent<Button>().transform.parent));
                        listofroom[i]
                            .transform.GetChild(4)
                            .GetComponent<Button>()
                            .onClick.AddListener(
                                () =>
                                    rummypoolscene(
                                        listofroom[roomindex]
                                            .transform.GetChild(4)
                                            .GetComponent<Button>()
                                            .transform.parent,
                                        poolresponseData.table_data[roomindex].id
                                    )
                            );
                        //      numberUI?.SetActive(false);
                        //     table?.SetActive(true);
                    }
                }
                else if (poolresponseData.code == 205)
                {
                    TableDataifalready tableData = JsonUtility.FromJson<TableDataifalready>(
                        response
                    );
                    Debug.Log("RES_Check + tabledata data" + tableData.no_of_players);
                    //InternetCheck.Instance.checkinvalid(tableData.code);

                    playerList.AddRange(tableData.table_data);

                    // pool_data.no_of_players = tableData.no_of_players;

                    // pool_data.boot_value = tableData.table_data[0].boot_value;
                    // pool_data.Id = tableData.table_data[0].id;

                    PlayerPrefs.SetString("Getpoolplayer", tableData.no_of_players);
                    PlayerPrefs.SetString("Getpoolboot", tableData.table_data[0].boot_value);
                    PlayerPrefs.SetString("Getpooltableid", tableData.table_data[0].id);

                    DOVirtual.DelayedCall(0.4f, () =>
                    {
                        //Addressables.LoadSceneAsync("Pool_Rummy.unity");
                        this.GetComponent<GameSelection>().loaddynamicscenebyname("Pool_Rummy");
                    });
                }
            }
        }
    }

    IEnumerator PoolPostRequestcheckfortable(string url)
    {
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("no_of_players", Playernumber);
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

                poolresponseData = JsonUtility.FromJson<PoolResponseData>(response);

                //InternetCheck.Instance.checkinvalid(poolresponseData.code);

                if (poolresponseData.code == 200) { }
                else if (poolresponseData.code == 205)
                {
                    TableDataifalready tableData = JsonUtility.FromJson<TableDataifalready>(
                        response
                    );

                    //InternetCheck.Instance.checkinvalid(tableData.code);

                    playerList.AddRange(tableData.table_data);

                    // pool_data.no_of_players = tableData.no_of_players;

                    // pool_data.boot_value = tableData.table_data[0].boot_value;

                    // pool_data.Id = tableData.table_data[0].id;

                    PlayerPrefs.SetString("Getpoolplayer", tableData.no_of_players);
                    PlayerPrefs.SetString("Getpoolboot", tableData.table_data[0].boot_value);
                    PlayerPrefs.SetString("Getpooltableid", tableData.table_data[0].id);

                    this.GetComponent<GameSelection>().loaddynamicscenebyname("Pool_Rummy");
                }
            }
        }
    }

    IEnumerator PostRequest(string url)
    {
        if (listofroom.Count > 0)
        {
            for (int i = 0; i < listofroom.Count; i++)
            {
                Destroy(listofroom[i]);
            }
            listofroom.Clear();
        }
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("no_of_players", Playernumber);
        form.AddField("token", Configuration.GetToken());

        Debug.Log(
            "RES_Check + user_id: "
                + Configuration.GetId()
                + " no_of_players: "
                + Playernumber
                + " token: "
                + Configuration.GetToken()
        );

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
                Debug.Log("RES_Chck + table_list Response: " + response);

                responseData = JsonUtility.FromJson<ResponseData>(response);

                if (responseData.code == 200)
                {
                    int num = responseData.table_data.Count;

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
                        listofroom[i].transform.GetChild(0).GetComponent<Text>().text = responseData
                            .table_data[i]
                            .point_value;
                        listofroom[i].transform.GetChild(1).GetComponent<Text>().text = responseData
                            .table_data[i]
                            .boot_value;
                        listofroom[i].transform.GetChild(2).GetComponent<Text>().text =
                            Playernumber;
                        responseData.table_data[i].no_of_players = Playernumber;
                        listofroom[i].transform.GetChild(3).GetComponent<Text>().text = responseData
                            .table_data[i]
                            .online_members;
                        Debug.Log(
                            "Parent "
                                + listofroom[i]
                                    .transform.GetChild(4)
                                    .GetComponent<Button>()
                                    .transform.parent
                        );
                        //listofroom[i].transform.GetChild(4).GetComponent<Button>().onClick.AddListener(() => rummyscene(listofroom[i].transform.GetChild(4).GetComponent<Button>().transform.parent));
                        listofroom[i]
                            .transform.GetChild(4)
                            .GetComponent<Button>()
                            .onClick.AddListener(
                                () =>
                                    rummyscene(
                                        listofroom[roomindex]
                                            .transform.GetChild(4)
                                            .GetComponent<Button>()
                                            .transform.parent
                                    )
                            );
                        //                        numberUI?.SetActive(false);
                        //table?.SetActive(true);
                    }
                    var pos = tableparent.GetComponent<RectTransform>().position;
                    pos.y = 0;
                    tableparent.GetComponent<RectTransform>().position = pos;
                }
                else if (responseData.code == 205)
                {
                    TableDataifalready tableData = JsonUtility.FromJson<TableDataifalready>(
                        response
                    );

                    //InternetCheck.Instance.checkinvalid(tableData.code);

                    playerList.AddRange(tableData.table_data);

                    rum.no_of_players = tableData.table_data[0].no_of_players;
                    rum.boot_value = tableData.table_data[0].boot_value;
                    rum.user_id = tableData.table_data[0].id;

                    Debug.Log(
                        "RES_Check + no_of_players + for code 205 + player number is "
                            + rum.no_of_players
                    );

                    // SceneManager.LoadScene("Rummy_13");
                }
                else if (responseData.code == 406)
                {
                    showtoastmessage(responseData.message);
                }
            }
        }
    }

    IEnumerator PostRequestCheckOnTable(string url)
    {
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("no_of_players", Playernumber);
        form.AddField("token", Configuration.GetToken());

        Debug.Log(
            "RES_Check + user_id: "
                + Configuration.GetId()
                + " no_of_players: "
                + Playernumber
                + " token: "
                + Configuration.GetToken()
        );

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
                Debug.Log("RES_Chck + table_list Response: " + response);

                responseData = JsonUtility.FromJson<ResponseData>(response);

                //InternetCheck.Instance.checkinvalid(responseData.code);

                if (responseData.code == 200) { }
                else if (responseData.code == 205)
                {
                    TableDataifalready tableData = JsonUtility.FromJson<TableDataifalready>(
                        response
                    );

                    //InternetCheck.Instance.checkinvalid(tableData.code);

                    playerList.AddRange(tableData.table_data);

                    rum.no_of_players = tableData.table_data[0].no_of_players;
                    rum.boot_value = tableData.table_data[0].boot_value;

                    Debug.Log(
                        "RES_Check + no_of_players + for code 205 + player number is "
                            + rum.no_of_players
                    );

                    //SceneManager.LoadScene("Rummy_13");
                }
                else if (responseData.code == 406)
                {
                    showtoastmessage(responseData.message);
                }
            }
        }
    }

    public void LeaveTableScene()
    {
        SceneLoader.Instance.LoadScene("HomePage");
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

    public void rummypoolscene(Transform parent, string l_id)
    {
        // pool_data.no_of_players = Playernumber.ToString();
        // pool_data.boot_value = parent.GetChild(2).GetComponent<Text>().text;
        // pool_data.Id = l_id;
        PlayerPrefs.SetString("Getpoolplayer", Playernumber.ToString());
        PlayerPrefs.SetString("Getpoolboot", parent.GetChild(2).GetComponent<Text>().text);
        PlayerPrefs.SetString("Getpooltableid", l_id);
        this.GetComponent<GameSelection>().loaddynamicscenebyname("Pool_Rummy");
    }

    public void rummyscene(Transform parent)
    {
        rum.no_of_players = parent.GetChild(2).GetComponent<Text>().text;
        rum.boot_value = parent.GetChild(1).GetComponent<Text>().text;
        // SceneManager.LoadScene("Rummy_13");
    }

    public void playerselectbutton(string players)
    {
        Playernumber = players;
        SendLobbyRequest();
    }

    public void SetLobbyName(string Name)
    {
        foreach (Transform item in tableparent.transform.transform)
        {
            Destroy(item.gameObject);
        }
        lobbyname = Name;
        if (lobbyname != "Pool")
            Playernumber = "2";
        SendLobbyRequest();
    }
}
