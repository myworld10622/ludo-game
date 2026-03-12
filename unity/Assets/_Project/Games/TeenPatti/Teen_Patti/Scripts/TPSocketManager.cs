using System;
using System.Collections;
using System.Collections.Generic;
using AndroApps;
using Best.SocketIO;
using Best.SocketIO.Events;
using EasyUI.Toast;
using Mkey;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
//using SocketIOClient;
using UnityEngine.Profiling;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
//using Unity.Android.Gradle.Manifest;

#region Teen Patti Classes

[System.Serializable]
public class SlideShowItem
{
    public string id;
    public string user_id;
    public string prev_id;
    public string game_id;
    public string status;
    public string added_date;
    public string updated_date;
    public string name;
}

[Serializable]
public class SlideShowCard
{
    public string id;
    public string game_id;
    public string user_id;
    public string card1;
    public string card2;
    public string card3;
    public string packed;
    public string seen;
    public string added_date;
    public string updated_date;
    public string name;
}

[System.Serializable]
public class TPTableData
{
    public string user_id;
    public string token;
    public string boot_value;
}

[System.Serializable]
public class TPTableuserData
{
    public string user_id;
    public string token;
}

[System.Serializable]
public class TPUserData
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string user_type;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
}

[System.Serializable]
public class TPSlideShowData
{
    public string user_id;
    public string prev_user_id;
    public string token;
}

[System.Serializable]
public class TPUserChaalData
{
    public string user_id;
    public string plus;
    public string bot;
    public string token;
}

[System.Serializable]
public class TPemitslideshowData
{
    public string user_id;
    public string token;
    public string slide_id;
    public string type;
}

[System.Serializable]
public class PackData
{
    public string user_id;
    public string token;
    public string timeout;
}

[System.Serializable]
public class TPTableDataResponse
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string user_type;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
}

[System.Serializable]
public class TPTableDetail
{
    public string id;
    public string boot_value;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string private_value;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class TPGameLog
{
    public string id;
    public string game_id;
    public string user_id;
    public string action;
    public string plus;
    public string seen;
    public string amount;
    public string points;
    public string timeout;
    public string added_date;
}

[System.Serializable]
public class TPAllUsers
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string user_type;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
}

[System.Serializable]
public class TPGameUser
{
    public string id;
    public string game_id;
    public string user_id;
    public string card1;
    public string card2;
    public string card3;
    public string packed;
    public string seen;
    public string added_date;
    public string updated_date;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
}

[System.Serializable]
public class TabbleData
{
    public string Id;
    public string user_id;
    public string token;
    public string no_of_players;
    public string tournament_id;
    public string boot_value;
}

[System.Serializable]
public class TPGameResponseData
{
    public List<TPUserData> table_users;
    public TableDetail table_detail;
    public string active_game_id;
    public int game_status;
    public string table_amount;
    public List<TPGameLog> game_log;
    public List<TPAllUsers> all_users;
    public List<TPGameUser> game_users;
    public string chaal;
    public string game_amount;
    public List<TPCard> cards;
    public List<SlideShowItem> slide_show;
    public List<SlideShowCard> slide_show_from_cards;
    public List<SlideShowCard> slide_show_to_cards;
    public List<string> game_gifts;
    public string message;
    public string winner_user_id;
    public int code;
}

[System.Serializable]
public class TPCard
{
    public string card1;
    public string card2;
    public string card3;
}

[System.Serializable]
public class ApiResponse
{
    public string message;
    public List<TPTableDataResponse> table_data;
    public int code;
}

[System.Serializable]
public class TPGameResponse
{
    public string message;
    public int game_id;
    public string table_amount;
    public int code;
}

#endregion

public class TPSocketManager : MonoBehaviour
{
    [Header("Controller details")]
    public string name_space = "/teenpatti";

    [Header("Table Details")]
    public string TableID;

    [Header("Game Details")]
    public string GameID;
    private SocketManager Manager;

    public TeenPattiData TPData;
    public List<GameObject> profiles;
    public TextMeshProUGUI sockettimertext;
    public int chaaltimer;
    public ApiResponse response;
    public string gameID;
    public TPGameResponseData TPresponseData;
    public List<TPUserData> placementdata;

    #region music and sounds

    public Button[] buttons;

    #endregion

    #region Connection and socket listing

    private void OnEnable()
    {
        profiles[0].transform.GetChild(0).gameObject.SetActive(true);
        profiles[0].transform.GetChild(1).gameObject.SetActive(false);
        profiles[0].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text =
            Configuration.GetName();

        string walletString = Configuration.GetWallet();
        if (decimal.TryParse(walletString, out decimal userCoins))
        {
            if (userCoins > 10000)
            {
                profiles[0]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<Text>()
                    .text = FormatNumber(Configuration.GetWallet());
            }
            else
            {
                profiles[0]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<Text>()
                    .text = Configuration.GetWallet();
            }
        }

        //profiles[0].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(Configuration.GetWallet());
        Image img2 = profiles[0]
            .transform.GetChild(0)
            .GetChild(1)
            .GetChild(0)
            .GetComponent<Image>();
        StartCoroutine(
            DownloadImage(
                Configuration.GetProfilePic(),
                img2,
                profiles[0].transform.GetChild(0).GetChild(1).GetComponent<Image>(),
                profiles[0]
            )
        );
        var url = Configuration.BaseSocketUrl;
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(name_space);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("timer", OnTimerResponse);
        customNamespace.On<string>("get-table", OnGetTableResponse);
        customNamespace.On<string>("trigger", OnTriggerResponse);
        Manager.Open();

        for (int i = 0; i < profiles.Count; i++)
        {
            profiles[i].transform.GetChild(2).gameObject.SetActive(false);
        }
    }

    #endregion

    #region connect and disconnect

    void OnConnected(ConnectResponse resp)
    {
        Debug.Log("RES_Check + Connected");
        GetTable();
    }

    private void OnDisconnected()
    {
        Debug.Log("RES_Check + Disconnected");
        // PlayerPrefs.SetString("wallet", );
        // PlayerPrefs.Save();
    }

    private void OnTimerResponse(string args)
    {
        sockettimertext.text = "socket timer: " + args;
        chaaltimer = int.Parse(args);
    }

    #endregion

    #region Get Table

    private void GetTable()
    {
        var customNamespace = Manager.GetSocket(name_space);
        Debug.Log("RES_Check + Call Get Table");
        TPTableData jsonData = new TPTableData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            //jsonData.boot_value = TPData.boot_value;
            jsonData.boot_value = Configuration.Gettpboot();

            string jsonStr = JsonUtility.ToJson(jsonData);

            Debug.Log("RES_Check + Call Get Table 2");

            customNamespace.Emit("get-table", jsonData);

            Debug.Log("RES_Check + Emit-get-table");
            Debug.Log("RES_Value" + " EMIT-get-table " + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnGetTableResponse(string args)
    {
        Debug.Log("RES_Value + On_get-table Json :" + args);
        try
        {
            response = JsonUtility.FromJson<ApiResponse>(args);
            //InternetCheck.Instance.checkinvalid(response.code);
            if (response.code == 406)
            {
                Debug.Log("RES_Check + you dont have minimum coins");
                showtoastmessage(response.message);
                Invoke("leave", 1.5f);
            }
            else
            {
                List<TPTableDataResponse> tableDataList = response.table_data;
                TableID = response.table_data[0].table_id;
                //StartCoroutine(startgamedelay());
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    #endregion

    #region Trigger

    private void OnTriggerResponse(string args)
    {
        Debug.Log("RES_Check + On_trigger");
        if (args == "call_status")
        {
            API_CALL_status();
        }
        else
        {
            gameID = args;
            Debug.Log("Game ID : " + args);
            API_CALL_status();
        }
    }

    public void API_CALL_status()
    {
        StartCoroutine(Request());
    }

    IEnumerator Request()
    {
        string url = Configuration.GameTeenPattiStatus;
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        if (TableID != null)
            form.AddField("table_id", TableID);
        else
            form.AddField("table_id", "");

        if (gameID.Length != 0)
        {
            form.AddField("game_id", gameID);
        }
        else
            form.AddField("game_id", "");

        form.AddField("token", Configuration.GetToken());

        using (UnityWebRequest request = UnityWebRequest.Post(url, form))
        {
            request.SetRequestHeader("Token", Configuration.TokenLoginHeader);

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
                Debug.Log("RES_Value + API_CallStatus: " + request.downloadHandler.text);

                TPresponseData = JsonUtility.FromJson<TPGameResponseData>(
                    request.downloadHandler.text
                );

                showTableUsers();
            }
        }
    }

    #endregion

    #region Show Table USers

    public void showTableUsers()
    {
        Debug.Log("Res_Check" + " showTableUsers");

        List<TPUserData> userData1 = new List<TPUserData>();

        for (int i = 0; i < TPresponseData.table_users.Count; i++)
        {
            if (TPresponseData.table_users[i].user_id == Configuration.GetId())
                userData1.Add(TPresponseData.table_users[i]);
        }

        if (userData1.Count == 0)
        {
            SceneLoader.Instance.LoadScene("HomePage");
        }

        placementdata.Clear();

        placementdata = TPresponseData.table_users;

        int index = placementdata.FindIndex(x => x.user_id == Configuration.GetId());

        if (index > 0)
        {
            TPUserData userData = placementdata[index];
            placementdata.RemoveAt(index);
            placementdata.Insert(0, userData);
        }
        if (index == -1)
        {
            SceneLoader.Instance.LoadScene("HomePage");
        }

        for (int i = 0; i < placementdata.Count; i++)
        {
            if (placementdata[i].user_id == "0")
            {
                Debug.Log("I am null");
                profiles[i].transform.GetChild(0).gameObject.SetActive(false);
                profiles[i].transform.GetChild(4).gameObject.SetActive(true);
                profiles[i].transform.GetChild(1).gameObject.SetActive(false);
                profiles[i].GetComponent<TPChaalSlider>().enabled = false;
            }
            else
            {
                profiles[i].GetComponent<TPChaalSlider>().enabled = true;
                profiles[i].GetComponent<TPChaalSlider>().id = placementdata[i].user_id;
                profiles[i].transform.GetChild(0).gameObject.SetActive(true);
                profiles[i].transform.GetChild(4).gameObject.SetActive(false);
                profiles[i]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetChild(1)
                    .GetComponent<Text>()
                    .text = placementdata[i].name;
                profiles[i]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<Text>()
                    .text = FormatNumber(placementdata[i].wallet);
                Image img = profiles[i]
                    .transform.GetChild(0)
                    .GetChild(1)
                    .GetChild(0)
                    .GetComponent<Image>();
                StartCoroutine(
                    DownloadImage(
                        placementdata[i].profile_pic,
                        img,
                        profiles[i].transform.GetChild(0).GetChild(1).GetComponent<Image>(),
                        profiles[i]
                    )
                );
            }
        }
    }

    #endregion

    #region Image download and format number

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

    string FormatNumber(string number)
    {
        return number.ToString();
        // if (float.Parse(number) >= 1000 && float.Parse(number) < 10000)
        // {
        //     return (float.Parse(number) / 1000f).ToString("0.0") + "k";
        // }
        // else if (float.Parse(number) >= 10000)
        // {
        //     return (float.Parse(number) / 1000).ToString("0.#") + "k";
        // }
        // else
        // {
        //}
    }

    int CountImage = 0;

    public IEnumerator DownloadImage(string ProfileAvatar, Image img, Image det, GameObject profile)
    {
        string Url = Configuration.ProfileImage;

        using (UnityWebRequest webRequest = UnityWebRequestTexture.GetTexture(Url + ProfileAvatar))
        {
            yield return webRequest.SendWebRequest();
            if (webRequest.result == UnityWebRequest.Result.Success)
            {
                Debug.Log("DownloadImage In ");
                Debug.Log(" DownloadImage In out");
                Texture2D downloadedTexture = DownloadHandlerTexture.GetContent(webRequest);
                img.sprite = Sprite.Create(
                    downloadedTexture,
                    new Rect(0, 0, downloadedTexture.width, downloadedTexture.height),
                    Vector2.zero
                );
                det.sprite = img.sprite;
            }
            else
            {
                Debug.Log("Error: " + webRequest.error);
            }
#if !UNITY_WEBGL
            long UsedMemory;
            UsedMemory = System.Diagnostics.Process.GetCurrentProcess().PagedMemorySize64;
            if (UsedMemory > 1073741824)
            {
                GC.Collect();
            }
#endif
            yield return null;
        }
    }

    #endregion
}
