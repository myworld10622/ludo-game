using System;
using System.Collections;
using System.Collections.Generic;
using Best.SocketIO;
using Best.SocketIO.Events;
using Mkey;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class CachetaConnection : MonoBehaviour
{
    private SocketManager Manager;

    //private SocketIOUnity socket;
    public Text connectiontext;
    public SocketResponse socketResponse;
    public List<CachetaGameData> gameDataList;
    public List<PointRummyGameData> gameDataListFinal;
    public RummyGameData gameData;
    public List<TableUser> activeusers;
    public List<string> imageid;
    public List<GameObject> profiles;

    //public BackScene back;
    public PointRummyScriptable rummyobject;

    //public DRummy_Data Dealrummyobject;
    public List<string> seat;
    public List<JsonCardData> mycarddata;
    public List<string> cardNames;
    public GameObject rummymanager;
    public bool startrummygame;
    public int num = 0;
    public bool gamestarted = false,
        waitforchaal = false;
    public string askedCard;
    public List<string> cardsdropped;
    public string Idropped;
    public bool Done;
    public GameObject panel;
    public GameObject resultprefab;
    public GameObject resultcontent;
    public List<GameObject> jokercards;
    public Sprite jokertoshow;
    public string jokername;
    public int groupcount;
    public List<string> cardData = new List<string>();
    public List<GameObject> list1,
        list2,
        list3,
        list4,
        list5,
        list6;
    public bool declared;
    public Sprite defaultsprite;
    public Text countdownText;
    public float countdownTime = 5f;
    public List<TableUser> placementdata;
    public bool drawcards;
    public bool iamagameuser = false;
    public bool startgamedatabool,
        isgamestarted;
    public TextMeshProUGUI join,
        packed,
        info;
    public GameObject join2;
    public Transform location;
    public Sprite circle;
    public bool countdownbool;
    public TextMeshProUGUI gameidtext;
    public List<finalcarddata> finalCardsList = new List<finalcarddata>();
    public string currentchaalid;
    public GameObject backcard;
    public bool firsttime;
    public RummyScriptable rummyMainData;
    public bool gotdropresponse = false;
    public TextMeshProUGUI sockettimertext;
    public int chaaltimer;
    public int discardorder = 50;
    public List<Image> profileimage;
    public Sprite maskimage;
    public string gameID;
    public GameObject placeholderglow,
        discardglow,
        finishdeskglow;
    public bool tookcard,
        in_game = true;

    [Header("Cards")]
    public List<GameObject> deck;

    public string custom_namespace = "/rummy";

    [System.Serializable]
    public class DataList
    {
        public List<finalcarddata> dataList;
    }

    public void openclosepanel(GameObject obj)
    {
        if (!obj.activeSelf)
            obj.SetActive(true);
        else
            obj.SetActive(false);
    }

    public Toggle soundToggle;
    public Toggle musicToggle;

    void Start()
    {
        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);
    }

    private void OnMusicToggleChanged(bool isOn)
    {
        Debug.Log($"Music: {isOn}");

        PlayerPrefs.SetString("music", isOn ? "on" : "");
        PlayerPrefs.Save();

        if (isOn)
        {
            AudioManager._instance.PlayBackgroundAudio();
        }
        else
        {
            AudioManager._instance.StopBackgroundAudio();
        }
    }

    private void OnSoundToggleChanged(bool isOn)
    {
        Debug.Log($"Sound: {isOn}");

        PlayerPrefs.SetString("sound", isOn ? "on" : "");
        PlayerPrefs.Save();
        if (!isOn)
            AudioManager._instance.StopEffect();
    }

    private void OnEnable()
    {
        in_game = true;
        // audiomanager = FindObjectOfType<NewAudioManager>();

        // if (audiomanager != null)
        // {
        //     if (PlayerPrefs.GetString("sound") == "on")
        //         toggle.isOn = false;
        //     else
        //         toggle.isOn = true;
        // }


        if (Configuration.Getpointplayer() == "2")
        {
            for (int i = 0; i < profiles.Count; i++)
            {
                if (i > 1)
                {
                    profiles[i].SetActive(false);
                }
            }

            profiles[1].transform.position = new Vector3(-0.15f, 4.07f, 0);
        }
        profiles[0].transform.GetChild(0).gameObject.SetActive(true);
        profiles[0].transform.GetChild(1).gameObject.SetActive(false);
        profiles[0].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<TextMeshProUGUI>().text = Configuration.GetName();

        string walletString = Configuration.GetWallet();
        if (double.TryParse(walletString, out double userCoins))
        {
            if (userCoins > 10000)
            {
                profiles[0]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<TextMeshProUGUI>()
                    .text = FormatNumber(Configuration.GetWallet());
            }
            else
            {
                profiles[0]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<TextMeshProUGUI>()
                    .text = Configuration.GetWallet();
            }
        }

        //profiles[0].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(Configuration.GetWallet());
        Image img2 = profiles[0].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        img2.sprite = SpriteManager.Instance.profile_image;


        // StartCoroutine(
        //     DownloadImage(
        //         Configuration.GetProfilePic(),
        //         img2,
        //         profiles[0].transform.GetChild(0).GetChild(1).GetComponent<Image>()
        //     )
        // );

        profiles[0].transform.GetChild(0).GetChild(1).GetComponent<Image>().sprite = circle;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        //join.gameObject.SetActive(true);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(custom_namespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        Manager.Open();
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On("connect_error", OnDisconnected);
        // customNamespace.On<string>(ControllerDetail.Counter, OnNumber);
        // customNamespace.On<string>(ControllerDetail.Status, onStatus);
        customNamespace.On<string>("get-table", OnGetTableResponse);
        customNamespace.On<string>("my-card", OnGetCardResponse);
        customNamespace.On<string>("get-card", OnGetOneCardResponse);
        customNamespace.On<string>("drop-card", OnDropResponse);
        customNamespace.On<string>("get-drop-card", OnGetDropCardResponse);
        customNamespace.On<string>("pack-game", OnGetDropGameResponse);
        customNamespace.On<string>("declare-back", OnGetDeclareBackGameResponse);
        customNamespace.On<string>("declare", OnGetDeclareGameResponse);
        customNamespace.On<string>("wrong-delclare", OnGetWrongDeclareGameResponse);
        customNamespace.On<string>("leave-table", OnLeaveTableResponse);
        customNamespace.On<string>("trigger", OnTriggerResponse);
        customNamespace.On<string>("start-game", OnStartGameResponse);
        customNamespace.On<string>("rummy_timer", OnRummyTimerResponse);

        //socket = new SocketIOUnity("https://games-socket.androappstech.in/rummy");

        //socket.JsonSerializer = new NewtonsoftJsonSerializer();

        //customNamespace.OnConnected += (sender, e) =>
        //{

        //};

        //socket.OnDisconnected += (sender, e) =>
        //{
        //    Debug.Log("DisConnected");
        //};

        //socket.OnError += (sender, e) =>
        //{
        //    Debug.Log("Error");
        //};

        //socket.OnReconnectError += (sender, e) =>
        //{
        //    Debug.Log("Reconnected Error");
        //};

        //socket.OnUnityThread("get-table", OnGetTableResponse);
        //socket.OnUnityThread("my-card", OnGetCardResponse);
        //socket.OnUnityThread("get-card", OnGetOneCardResponse);
        //socket.OnUnityThread("drop-card", OnDropResponse);
        //socket.OnUnityThread("get-drop-card", OnGetDropCardResponse);
        //socket.OnUnityThread("pack-game", OnGetDropGameResponse);
        //socket.OnUnityThread("declare-back", OnGetDeclareBackGameResponse);
        //socket.OnUnityThread("declare", OnGetDeclareGameResponse);
        //socket.OnUnityThread("leave-table", OnLeaveTableResponse);
        //socket.OnUnityThread("trigger", OnTriggerResponse);
        //socket.OnUnityThread("start-game", OnStartGameResponse);
        //socket.OnUnityThread("rummy_timer", OnRummyTimerResponse);

        //socket.OnReconnectAttempt += (sender, e) =>
        //{
        //    //InternetCheck.Instance.canvas.SetActive(true);
        //    Debug.Log($"{DateTime.Now} Reconnecting: attempt = {e}");
        //};

        //socket.On("disconnect", Onconnect_errorResponse);

        //socket.Connect();

        Button[] buttons = Resources.FindObjectsOfTypeAll<Button>();


        foreach (Button btn in buttons)
        {
            btn.onClick.AddListener(() => PlaySound());
        }
    }
    void PlaySound()
    {
        AudioManager._instance.ButtonClick();
    }
    //public void adioplaypause(UnityEngine.UI.Toggle btn)
    //{
    //    if (audiomanager != null)
    //        audiomanager.togglemusic(btn);
    //}



    void OnConnected(ConnectResponse response)
    {
        Debug.Log("Connected + " + response.sid);
        API_CALL_get_table();
    }

    private void OnRummyTimerResponse(string args)
    {
        CommonUtil.CheckLog("socket timer Json :" + args);
        sockettimertext.text = "socket timer: " + args;
        chaaltimer = int.Parse(args);
        if (chaaltimer == 1 || chaaltimer > 10)
        {
            join.text = "Lets Start!!!";
        }
        else
            join.text = chaaltimer.ToString();
    }

    private void OnStartGameResponse(string args)
    {
        Debug.Log("Start Game Json :" + args);
        try
        {
            GameResponse resp = JsonUtility.FromJson<GameResponse>(args);
            gameID = resp.game_id.ToString();
            if (resp.code != 200)
            {
                CommonUtil.ShowToast(resp.message);
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    string FormatNumber(string number)
    {
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
        return number.ToString();
        //}
    }

    private void OnGetTableResponse(string args)
    {

        Debug.Log("get-table Json :" + args);
        try
        {
            Debug.Log("Called");
            socketResponse = JsonUtility.FromJson<SocketResponse>(args);
            if (socketResponse.code == 406)
            {
                rummymanager.GetComponent<GameManager>().showtoastmessage(socketResponse.message);
                Debug.Log("RES_Check + you dont have minimum coins");
                Invoke("leave", 1.5f);
            }
            //StartCoroutine(Request());
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnGetDeclareBackGameResponse(string args)
    {
        Debug.Log("GetDeclareBack-Game Json :" + args);
        try
        {
            Debug.Log("Called");
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnGetWrongDeclareGameResponse(string args)
    {
        Debug.Log("GetWrong-Declare-Game Json :" + args);
        try
        {
            Debug.Log("Called");
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnGetDeclareGameResponse(string args)
    {
        Debug.Log("GetDeclare-Game Json :" + args);
        try
        {
            Debug.Log("Called");
            rummymanager.GetComponent<GameManager>().CheckAfterEveryMove();
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnGetDropGameResponse(string args)
    {
        Debug.Log("Getdrop-Game Json :" + args);
        try
        {
            Debug.Log("Called");
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnGetDropCardResponse(string args)
    {
        Debug.Log("RES_Check + Get-drop-card Json :" + args);
        try
        {
            tookcard = true;
            Debug.Log("RES_Check + get-Drop-Card");
            DropCardResponse card = JsonUtility.FromJson<DropCardResponse>(args);
            Debug.Log("RES_Value + get-Drop-Card: " + card.card[0].card);
            askedCard = card.card[0].card;
            StartCoroutine(
                rummymanager.GetComponent<GameManager>().InatntiateSingleCard("Discard_Area")
            );
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnDropResponse(string args)
    {
        try
        {
            Debug.Log("RES_Value + On Response for drop card " + args);
            rummymanager.GetComponent<GameManager>().dropcardresponse = true;
            OnDropCardData jsonData = JsonUtility.FromJson<OnDropCardData>(args);

            if (jsonData.code == 200)
            {
                if (rummymanager.GetComponent<GameManager>().isdeclared)
                {
                    Debug.Log("RES_Check + API_CALL_declare");
                    rummymanager
                        .GetComponent<GameManager>()
                        .DiscardAfterONResponse(
                            rummymanager.GetComponent<GameManager>().finishdeskcard,
                            "FinishDesk"
                        );
                }
                else
                {
                    Debug.Log("RES_Check + DiscardAfterONResponse");
                    rummymanager
                        .GetComponent<GameManager>()
                        .DiscardAfterONResponse(
                            rummymanager.GetComponent<GameManager>().discardCard,
                            "Discard_Area"
                        );
                }
            }
            else
            {
                Debug.Log("RES_Check + MoveCardBack");
                StartCoroutine(
                    rummymanager
                        .GetComponent<GameManager>()
                        .MoveCardBack(
                            rummymanager.GetComponent<GameManager>().discardCard,
                            rummymanager.GetComponent<GameManager>().draggedCardOriginalPosition,
                            0.5f
                        )
                );

                rummymanager.GetComponent<GameManager>().showtoastmessage(jsonData.message);
            }
        }
        catch (System.Exception ex)
        {
            StartCoroutine(
                rummymanager
                    .GetComponent<GameManager>()
                    .MoveCardBack(
                        rummymanager.GetComponent<GameManager>().discardCard,
                        rummymanager.GetComponent<GameManager>().draggedCardOriginalPosition,
                        0.5f
                    )
            );
            Debug.LogError(ex.ToString());
        }
    }

    private void OnGetCardResponse(string args)
    {
        Debug.Log("my-Card Json :" + args);
        try
        {
            Debug.Log("Called");
            JsonCardData jsonData = JsonUtility.FromJson<JsonCardData>(args);
            mycarddata.Add(jsonData);
            foreach (MYCardData cardData in jsonData.cards)
            {
                string formattedCardName = cardData.card.ToLower();
                cardNames.Add(formattedCardName);
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }

        rummymanager.SetActive(true);
    }

    private void OnGetOneCardResponse(string args)
    {
        Debug.Log("RES_Check get-Card Json :" + args);
        try
        {
            tookcard = true;
            Debug.Log("RES_Check + get-Card");
            OneCard card = JsonUtility.FromJson<OneCard>(args);
            Debug.Log("RES_Check + get-Card: " + card.card[0].cards);
            askedCard = card.card[0].cards;
            StartCoroutine(
                rummymanager.GetComponent<GameManager>().InatntiateSingleCard("placeholderCard")
            );
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnLeaveTableResponse(string args)
    {
        Debug.Log("Json onleavetable :" + args);
        try
        {
            Debug.Log("Called");
            socketResponse = null;
            socketResponse = JsonUtility.FromJson<SocketResponse>(args);
#if UNITY_ANDROID || UNITY_EDITOR
            this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
#elif UNITY_WEBGL
            this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
#endif
            //this.GetComponent<GameSelection>().loadscene(2);
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnTriggerResponse(string args)
    {
        Debug.Log("Res_check - trigger");
        Debug.Log("Res_Value - trigger Json :" + args);

        if (in_game)
        {
            if (args == "call_status")
            {
                API_CALL_status(gameID);
            }
            else
            {
                gameID = args;
                API_CALL_status(gameID);
            }
        }
    }

    public void API_CALL_status(string gamedata)
    {
        Debug.Log("Res_check - status Called api::" + gamedata);
        StartCoroutine(Request(gamedata));
    }

    private void StartCountdown()
    {
        rummymanager.GetComponent<GameManager>().groupButton.SetActive(false);
        rummymanager.GetComponent<GameManager>().declare.SetActive(false);
        rummymanager.GetComponent<GameManager>().declareback.SetActive(false);
        InvokeRepeating("UpdateCountdown", 1f, 1f);
    }

    private void UpdateCountdown()
    {
        countdownTime -= 1f;

        countdownText.text = "New game starts in " + countdownTime.ToString("F0") + "!";

        if (countdownTime <= 0f)
        {
            CancelInvoke("UpdateCountdown");
            StartCoroutine(resettostart());
        }
    }

    private void StartGame()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);

        basicID jsonData = new basicID();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("start-game", jsonData);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-start-game" + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public IEnumerator resettostart()
    {
        //join.gameObject.SetActive(true);
        for (int i = 0; i < profiles.Count; i++)
        {
            profiles[i].GetComponent<PointRummyChaalSlider>().start = false;
            profiles[i].transform.GetChild(0).GetChild(3).gameObject.SetActive(true);
            profiles[i].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
        }

        if (rummymanager.GetComponent<GameManager>().list1.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager>().list1)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager>().list1.Clear();

        if (rummymanager.GetComponent<GameManager>().list2.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager>().list2)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager>().list2.Clear();

        if (rummymanager.GetComponent<GameManager>().list3.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager>().list3)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager>().list3.Clear();

        if (rummymanager.GetComponent<GameManager>().list4.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager>().list4)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager>().list4.Clear();

        if (rummymanager.GetComponent<GameManager>().list5.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager>().list5)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager>().list5.Clear();

        if (rummymanager.GetComponent<GameManager>().list6.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager>().list6)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager>().list6.Clear();

        //if (rummymanager.GetComponent<GameManager>().discardedlist.Count != 0)
        //{
        //    foreach (GameObject obj in rummymanager.GetComponent<GameManager>().discardedlist)
        //        Destroy(obj);
        //}
        //rummymanager.GetComponent<GameManager>().discardedlist.Clear();

        if (rummymanager.GetComponent<GameManager>().finishdeskcard != null)
        {
            Destroy(rummymanager.GetComponent<GameManager>().finishdeskcard);
            rummymanager.GetComponent<GameManager>().finishdeskcardogpos = Vector3.zero;
        }

        Destroy(rummymanager.GetComponent<GameManager>().wildcard);

        rummymanager.GetComponent<GameManager>().names.Clear();
        rummymanager.GetComponent<GameManager>().setFromAPI.Clear();
        rummymanager.GetComponent<GameManager>().spawnedCards.Clear();
        rummymanager.GetComponent<GameManager>().check1 = false;
        rummymanager.GetComponent<GameManager>().check2 = false;
        rummymanager.GetComponent<GameManager>().check3 = false;
        rummymanager.GetComponent<GameManager>().check4 = false;
        rummymanager.GetComponent<GameManager>().check5 = false;
        rummymanager.GetComponent<GameManager>().check6 = false;

        rummymanager.GetComponent<GameManager>().canplay = false;

        rummymanager.GetComponent<GameManager>().isdeclared = false;

        rummymanager.GetComponent<GameManager>().numoflist = 0;
        rummymanager.GetComponent<GameManager>().numofmatch = 0;
        rummymanager.GetComponent<GameManager>().moveup = false;
        rummymanager.GetComponent<GameManager>().clicktime = 0;

        rummymanager.GetComponent<GameManager>().uplist.Clear();
        rummymanager.GetComponent<GameManager>().wildcardrank = 0;
        rummymanager.GetComponent<GameManager>().ischaal = false;

        rummymanager.GetComponent<GameManager>().spawned = false;
        rummymanager.GetComponent<GameManager>().gamestart = false;

        rummymanager.GetComponent<GameManager>().drop.SetActive(true);

        for (int i = 0; i < rummymanager.GetComponent<GameManager>().textcavas.Count; i++)
        {
            Destroy(rummymanager.GetComponent<GameManager>().textcavas[i]);
        }

        rummymanager.GetComponent<GameManager>().textcavas.Clear();

        //rummymanager.GetComponent<GameManager>().name = string.Empty;
        //if (rummymanager.GetComponent<GameManager>().textcavas.Count != 0)
        //{
        //    foreach (GameObject canvas in rummymanager.GetComponent<RummyManager>().textcavas)
        //        Destroy(canvas);
        //    rummymanager.GetComponent<RummyManager>().textcavas.Clear();
        //}
        //rummymanager.GetComponent<RummyManager>().textcavas.Clear();

        rummymanager.SetActive(false);
        Debug.Log("multiple");
        panel.SetActive(false);

        gamestarted = false;
        waitforchaal = false;

        list1.Clear();
        list2.Clear();
        list3.Clear();
        list4.Clear();
        list5.Clear();
        list6.Clear();
        cardsdropped.Clear();
        askedCard = string.Empty;
        num = 0;
        gamestarted = false;
        waitforchaal = false;
        mycarddata.Clear();
        cardNames.Clear();
        Idropped = null;
        Done = false;
        jokertoshow = null;
        jokername = string.Empty;
        groupcount = 0;
        cardData.Clear();
        groupcount = 0;
        countdownTime = 10;
        placementdata.Clear();
        drawcards = false;
        declared = false;
        CountImage = 0;

        for (int i = 0; i < resultcontent.transform.childCount; i++)
        {
            Destroy(resultcontent.transform.GetChild(i).gameObject);
        }
        gameDataListFinal.Clear();
        gameDataList.Clear();

        gameID = string.Empty;

        StartGame();
        yield return new WaitForSeconds(2);
    }

    void countdown(TextMeshProUGUI wait)
    {
        // Start the countdown loop
        while (chaaltimer >= 0)
        {
            if (chaaltimer == 0)
            {
                wait.text = "Lets Start!!!";
            }
            else
            {
                wait.fontSize = 150;
                wait.GetComponent<Animator>().enabled = true;
                wait.text = chaaltimer.ToString();
            }
        }
    }

    // Custom class to match the JSON structure
    [System.Serializable]
    public class MyJSONObject
    {
        public string message;
        public int code;
    }

    public IEnumerator movecardafterchaal(
        GameObject card,
        float duration,
        Vector3 startPosition,
        Vector3 targetPosition,
        GameObject parent
    )
    {
        GameObject obj = Instantiate(card);
        obj.transform.parent = parent.transform;
        obj.transform.localScale = new Vector3(1.4f, 1.2f, 0);
        obj.transform.position = new Vector3(0, 0, 0);
        obj.transform.localPosition = new Vector3(0, 0, 0);
        obj.GetComponent<SpriteRenderer>().sortingOrder = 20;
        float elapsed = 0f;
        startPosition = obj.transform.position;
        while (elapsed < duration)
        {
            obj.transform.position = Vector3.Lerp(
                startPosition,
                targetPosition,
                elapsed / duration
            );
            elapsed += Time.deltaTime;
            yield return null;
        }

        obj.transform.position = targetPosition;
        Destroy(obj);
    }

    IEnumerator Request(String gamedataid)
    {
        string url = Configuration.GameRummyStatus;
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        if (socketResponse.table_data[0].table_id != null)
            form.AddField("table_id", socketResponse.table_data[0].table_id);
        else
            form.AddField("table_id", "");

        if (gameID != string.Empty)
            form.AddField("game_id", gameID);
        else
            form.AddField("game_id", "");

        //form.AddField("game_id", "");
        form.AddField("token", Configuration.GetToken());

        Debug.Log(
            "RES_Check + call request trigger  + gameid: "
                + gamedataid
                + " tableid: "
                + socketResponse.table_data[0].table_id
                + " user_id: + "
                + Configuration.GetId()
                + " Token: "
                + Configuration.GetToken()
        );

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
                MyJSONObject jsonObject = JsonUtility.FromJson<MyJSONObject>(
                    request.downloadHandler.text
                );

                if (jsonObject.code == 403)
                {
                    Debug.Log("RES_Check + not on table");
                    //InternetCheck.Instance.timeoutfromgame = true;
                    //rummymanager.GetComponent<GameManager>().showtoastmessage("You have been TimedOut from the table");
                    leave();
                }
                else
                {
                    Debug.Log("Res_Value : game_status" + request.downloadHandler.text);
                    string cachetadata = request.downloadHandler.text;
                    CachetaGameData List = JsonUtility.FromJson<CachetaGameData>(cachetadata);
                    PointRummyGameData RootObject = JsonUtility.FromJson<PointRummyGameData>(
                        cachetadata
                    );
                    gameDataList.Clear();
                    gameDataList.Add(List);
                    Debug.Log("RES_Check + Game_Status + " + gameDataList[0].game_status);
                    if (gameDataList[0].game_status == 1)
                        isgamestarted = true;
                    else if (gameDataList[0].game_status == 0)
                        isgamestarted = false;

                    if (!isgamestarted)
                    {
                        join.gameObject.SetActive(true);
                        join2.SetActive(true);
                        join.fontSize = 150;
                        join.GetComponent<Animator>().enabled = true;
                        join.text = chaaltimer.ToString();
                        //countdown(join);
                        countdownbool = true;
                    }
                    else
                    {
                        join.gameObject.SetActive(false);
                        join.fontSize = 40;
                        join.GetComponent<Animator>().enabled = false;
                        join2.SetActive(false);
                    }

                    if (gameDataList[0].active_game_id != string.Empty)
                        gameID = gameDataList[0].active_game_id;

                    gameidtext.text = "Game_ID: " + gameDataList[0].active_game_id;
                    //InternetCheck.Instance.checkinvalid(RootObject.code);
                    //InternetCheck.Instance.checkinvalid(List.code);

                    info.text =
                        "#"
                        + gameDataList[0].active_game_id
                        + "    "
                        + " Point Rummy    "
                        + gameDataList[0].table_detail.boot_value;

                    if (profiles[0].GetComponent<PointRummyChaalSlider>().start)
                    {
                        if (
                            gameDataList[0].last_card.isDeleted == "0"
                            && currentchaalid != gameDataList[0].last_card.user_id
                        )
                        {
                            CommonUtil.CheckLog("Check for animation");
                            if (gameDataList[0].last_card.user_id != Configuration.GetId())
                            {
                                currentchaalid = gameDataList[0].last_card.user_id;
                                GameObject startposbject;
                                if (gameDataList[0].last_card.is_drop_card == "0")
                                    startposbject = GameObject.Find("placeholderCard");
                                else
                                    startposbject = GameObject.Find("Discard_Area");

                                for (int i = 0; i < profiles.Count; i++)
                                {
                                    if (
                                        profiles[i].GetComponent<PointRummyChaalSlider>().id
                                        == gameDataList[0].last_card.user_id
                                    )
                                        StartCoroutine(
                                            movecardafterchaal(
                                                backcard,
                                                0.5f,
                                                startposbject.transform.position,
                                                profiles[i].transform.position,
                                                startposbject
                                            )
                                        );
                                }
                            }
                            else
                            {
                                currentchaalid = gameDataList[0].last_card.user_id;
                            }
                        }
                    }

                    if (gameDataList[0].chaal == Configuration.GetId())
                    {
                        if (!tookcard)
                        {
                            placeholderglow.SetActive(true);
                            discardglow.SetActive(true);
                            finishdeskglow.SetActive(true);
                        }
                        else
                        {
                            placeholderglow.SetActive(false);
                            discardglow.SetActive(true);
                            finishdeskglow.SetActive(true);
                        }
                    }
                    else
                    {
                        tookcard = false;
                        placeholderglow.SetActive(false);
                        discardglow.SetActive(false);
                        finishdeskglow.SetActive(false);
                    }

                    showTableUsers();

                    if (gameDataList[0].game_users.Count > 1)
                        startgamedatabool = true;
                    else
                        startgamedatabool = false;

                    if (startgamedatabool)
                    {
                        //rummymanager.SetActive(true);

                        for (int i = 0; i < gameDataList[0].game_users.Count; i++)
                        {
                            if (gameDataList[0].game_users[i].packed == "1")
                            {
                                for (int j = 0; j < profiles.Count; j++)
                                {
                                    if (
                                        gameDataList[0].game_users[i].user_id
                                        == profiles[j].GetComponent<PointRummyChaalSlider>().id
                                    )
                                        profiles[i]
                                            .transform.GetChild(0)
                                            .GetChild(4)
                                            .gameObject.SetActive(true);
                                }
                            }
                        }

                        for (int i = 0; i < profiles.Count; i++)
                        {
                            for (int j = 0; j < gameDataList[0].game_users.Count; j++)
                            {
                                if (
                                    gameDataList[0].game_users[j].user_id
                                    == profiles[i].GetComponent<PointRummyChaalSlider>().id
                                )
                                    profiles[i]
                                        .transform.GetChild(0)
                                        .GetChild(3)
                                        .gameObject.SetActive(false);
                            }
                        }

                        for (int i = 0; i < gameDataList[0].game_users.Count; i++)
                        {
                            if (Configuration.GetId() == gameDataList[0].game_users[i].user_id)
                                iamagameuser = true;
                            else
                                continue;
                        }

                        if (gameDataList[0].declare && !declared)
                        {
                            for (int i = 0; i < gameDataList[0].game_users.Count; i++)
                            {
                                if (gameDataList[0].declare_user_id != Configuration.GetId())
                                {
                                    rummymanager
                                        .GetComponent<GameManager>()
                                        .declareback.SetActive(true);
                                    rummymanager.GetComponent<GameManager>().drop.SetActive(false);
                                }
                            }
                        }

                        if (gameDataList[0].game_status == 2 && Done == false)
                        {
                            Debug.Log("RES_Check + game completed");
                            StartCountdown();
                            gameDataListFinal.Add(RootObject);
                            gameData = JsonUtility.FromJson<RummyGameData>(
                                request.downloadHandler.text
                            );

                            panel.SetActive(true);
                            //panel.transform.GetChild(1).GetChild(0).GetComponent<Text>().text = "Result | Game ID : " + gameData.game_log[0].game_id;

                            jokername = gameData.joker;

                            foreach (GameObject obj in jokercards)
                            {
                                obj.name = obj.name.ToUpper();
                                if (obj.name == jokername)
                                    jokertoshow = obj.GetComponent<SpriteRenderer>().sprite;
                            }

                            panel.transform.GetChild(5).GetChild(0).GetComponent<Image>().sprite =
                                jokertoshow;

                            int finalusercount = gameDataListFinal[0].game_users.Count;

                            gameData.flattened_user_cards = new List<FlattenedUserCards>();

                            for (int i = 0; i < gameDataListFinal[0].game_users_cards.Count; i++)
                            {
                                FlattenedUserCards flattenedUserCards = new FlattenedUserCards();
                                flattenedUserCards.user_id = gameDataListFinal[0]
                                    .game_users_cards[i]
                                    .user
                                    .user_id;

                                if (gameDataListFinal[0].game_users_cards[i].user.cards.Count > 0)
                                {
                                    for (
                                        int j = 0;
                                        j
                                            < gameDataListFinal[0]
                                                .game_users_cards[i]
                                                .user
                                                .cards
                                                .Count;
                                        j++
                                    )
                                    {
                                        if (j > 0)
                                            flattenedUserCards.all_cards.Add("Empty");
                                        for (
                                            int k = 0;
                                            k
                                                < gameDataListFinal[0]
                                                    .game_users_cards[i]
                                                    .user
                                                    .cards[j]
                                                    .cards
                                                    .Count;
                                            k++
                                        )
                                        {
                                            //Debug.Log("RES_Check + j + " + j);
                                            //Debug.Log("RES_Check + i + " + i);
                                            flattenedUserCards.all_cards.Add(
                                                gameDataListFinal[0]
                                                    .game_users_cards[i]
                                                    .user
                                                    .cards[j]
                                                    .cards[k]
                                            );
                                        }
                                    }
                                }

                                gameData.flattened_user_cards.Add(flattenedUserCards);
                            }

                            for (int i = 0; i < gameData.flattened_user_cards.Count; i++)
                            {
                                GameObject userdetails = Instantiate(resultprefab);
                                userdetails.transform.SetParent(resultcontent.transform);
                                userdetails.transform.localScale = new Vector3(1.0f, 1.0f, 1.0f);
                                userdetails.transform.GetChild(0).GetComponent<Text>().text =
                                    gameData.game_users[i].name;
                                userdetails.transform.GetChild(2).GetComponent<Text>().text =
                                    gameData.game_users[i].score;
                                userdetails.transform.GetChild(3).GetComponent<Text>().text =
                                    gameData.game_users[i].win;
                                userdetails.transform.GetChild(4).GetComponent<Text>().text = "0";

                                if (
                                    gameDataListFinal[0].game_users[i].user_id
                                    == gameDataListFinal[0].winner_user_id
                                )
                                    userdetails.transform.GetChild(1).GetComponent<Text>().text =
                                        "Winner";
                                else
                                {
                                    if (gameDataListFinal[0].game_users[i].packed == "1")
                                        userdetails
                                            .transform.GetChild(1)
                                            .GetComponent<Text>()
                                            .text = "Dropped";
                                    else
                                        userdetails
                                            .transform.GetChild(1)
                                            .GetComponent<Text>()
                                            .text = "Looser";
                                }

                                if (gameData.flattened_user_cards[i].all_cards.Count == 0)
                                {
                                    for (int j = 0; j < 10; j++)
                                    {
                                        userdetails
                                            .transform.GetChild(5)
                                            .GetChild(j)
                                            .gameObject.SetActive(true);
                                        userdetails
                                            .transform.GetChild(5)
                                            .GetChild(j)
                                            .GetComponent<Image>()
                                            .sprite = defaultsprite;
                                    }
                                }
                                else
                                {
                                    for (
                                        int j = 0;
                                        j < gameData.flattened_user_cards[i].all_cards.Count;
                                        j++
                                    )
                                    {
                                        if (j <= 18)
                                        {
                                            if (
                                                gameData.flattened_user_cards[i].all_cards.Count
                                                == 0
                                            )
                                            {
                                                if (j <= 13)
                                                    userdetails
                                                        .transform.GetChild(5)
                                                        .GetChild(j)
                                                        .gameObject.SetActive(true);
                                                userdetails
                                                    .transform.GetChild(5)
                                                    .GetChild(j)
                                                    .GetComponent<Image>()
                                                    .sprite = defaultsprite;
                                            }
                                            else if (
                                                gameData.flattened_user_cards[i].all_cards[j] == ""
                                            )
                                            {
                                                userdetails
                                                    .transform.GetChild(5)
                                                    .GetChild(j)
                                                    .GetComponent<Image>()
                                                    .enabled = false;
                                            }

                                            if (
                                                gameData.flattened_user_cards[i].all_cards.Count
                                                == 0
                                            )
                                            {
                                                userdetails
                                                    .transform.GetChild(5)
                                                    .GetChild(j)
                                                    .GetComponent<Image>()
                                                    .sprite = defaultsprite;
                                            }
                                            else if (
                                                gameData.flattened_user_cards[i].all_cards[j]
                                                == "Empty"
                                            )
                                            {
                                                userdetails
                                                    .transform.GetChild(5)
                                                    .GetChild(j)
                                                    .gameObject.SetActive(true);
                                                userdetails
                                                    .transform.GetChild(5)
                                                    .GetChild(j)
                                                    .GetComponent<Image>()
                                                    .enabled = false;
                                            }
                                            else
                                            {
                                                foreach (GameObject obj in jokercards)
                                                {
                                                    if (
                                                        gameData.flattened_user_cards[i].all_cards[
                                                            j
                                                        ] == obj.name
                                                    )
                                                    {
                                                        userdetails
                                                            .transform.GetChild(5)
                                                            .GetChild(j)
                                                            .gameObject.SetActive(true);
                                                        obj.name = obj.name.ToUpper();
                                                        Debug.Log("Arjun2: " + obj.name);
                                                        userdetails
                                                            .transform.GetChild(5)
                                                            .GetChild(j)
                                                            .GetComponent<Image>()
                                                            .sprite =
                                                            obj.GetComponent<SpriteRenderer>().sprite;
                                                        if (
                                                            rummymanager
                                                                .GetComponent<GameManager>()
                                                                .handlewildcardforresult(
                                                                    gameData
                                                                        .flattened_user_cards[i]
                                                                        .all_cards[j]
                                                                )
                                                        )
                                                        {
                                                            userdetails
                                                                .transform.GetChild(5)
                                                                .GetChild(j)
                                                                .GetChild(0)
                                                                .gameObject.SetActive(true);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            Done = true;
                        }

                        if (!gamestarted && gameDataList[0].game_status == 1)
                        {
                            API_CALL_getCardList();
                            gamestarted = true;
                            waitforchaal = true;
                            CommonUtil.CheckLog("waitforchaal");
                        }

                        string currentchaal = gameDataList[0].chaal;

                        for (int i = 0; i < profiles.Count; i++)
                        {
                            if (
                                profiles[i].GetComponent<PointRummyChaalSlider>().id == currentchaal
                            )
                            {
                                profiles[i].GetComponent<PointRummyChaalSlider>().StartChaal();
                                profiles[i].GetComponent<PointRummyChaalSlider>().ischaal = true;
                            }
                            else
                            {
                                profiles[i].GetComponent<PointRummyChaalSlider>().StopChaal();
                            }
                        }

                        if (currentchaal == Configuration.GetId())
                        {
                            yield return new WaitForSeconds(0.5f);
                            rummymanager.GetComponent<GameManager>().ischaal = true;
                            rummymanager.GetComponent<GameManager>().getcard = true;
                        }
                        else
                            rummymanager.GetComponent<GameManager>().ischaal = false;

                        Debug.Log(gameDataList[0].chaal + " chaal");

                        if (rummymanager.GetComponent<GameManager>().placeholderclicked == false)
                        {
                            if (cardsdropped != null)
                            {
                                int count = cardsdropped.Count;
                                if (count == 0 && gameDataList[0].drop_card.Count != 0)
                                {
                                    cardsdropped.Add(gameDataList[0].drop_card[0].card);
                                    if (drawcards == false && iamagameuser)
                                    {
                                        rummymanager.gameObject.SetActive(true);
                                        drawcards = true;
                                    }
                                }
                                else
                                {
                                    if (gameDataList[0].drop_card.Count != 0)
                                    {
                                        if (
                                            cardsdropped[cardsdropped.Count - 1]
                                            != gameDataList[0].drop_card[0].card
                                        )
                                        {
                                            cardsdropped.Add(gameDataList[0].drop_card[0].card);
                                            if (drawcards == false && iamagameuser)
                                            {
                                                rummymanager.gameObject.SetActive(true);
                                                drawcards = true;
                                            }
                                        }
                                    }
                                }

                                if (cardsdropped.Count != 0)
                                {
                                    Debug.Log("Add drop card");
                                    if (cardsdropped[cardsdropped.Count - 1] != Idropped)
                                    {
                                        Debug.Log("Add drop card 2");
                                        string droppedcard = cardsdropped[cardsdropped.Count - 1]
                                            .ToLower();
                                        rummymanager.GetComponent<GameManager>().discardedcard =
                                            droppedcard;
                                        Debug.Log("RES_Check + dropedcard + " + droppedcard);
                                        //rummymanager.GetComponent<GameManager>().adddiscardedcardsfromserver(droppedcard);
                                    }
                                }
                            }
                            else if (gameDataList[0].drop_card.Count != 0)
                            {
                                cardsdropped.Add(gameDataList[0].drop_card[0].card);
                                if (drawcards == false && iamagameuser)
                                {
                                    rummymanager.gameObject.SetActive(true);
                                    drawcards = true;
                                }
                            }
                            displaylatestdropcardtrigger();
                        }
                    }
                }
            }
        }
    }

    public void displaylatestdropcardtrigger()
    {
        if (GameObject.Find("Discard_Area").transform.childCount > 0)
        {
            int childCount = GameObject.Find("Discard_Area").transform.childCount;
            for (int i = 0; i < childCount; i++)
            {
                Transform child = GameObject.Find("Discard_Area").transform.GetChild(i);
                if (child.gameObject.name != "Glow")
                    Destroy(child.gameObject);
            }
        }

        string formattednewcard = string.Empty;
        if (gameDataList[0].drop_card.Count > 0)
        {
            formattednewcard = gameDataList[0].drop_card[0].card.ToLower();

            Debug.Log("RES_Value + displaylatestdropcardtrigger + " + formattednewcard);

            GameObject cardtodisplay = FindGameObjectByName(formattednewcard);

            if (cardtodisplay == null)
                Debug.Log("RES_Check + displaylatestdropcardtrigger Card isnull");

            Debug.Log("RES_Check + displaylatestdropcardtrigger Card Name + " + cardtodisplay.name);

            GameObject spawnedcard = Instantiate(cardtodisplay);

            spawnedcard.transform.GetComponent<Animator>().enabled = false;

            spawnedcard.transform.SetParent(GameObject.Find("Discard_Area").transform);

            spawnedcard.transform.localScale = new Vector3(1.5f, 1.2f, 0);

            spawnedcard.transform.localPosition = Vector3.zero;

            spawnedcard.GetComponent<BoxCollider>().enabled = false;

            spawnedcard.GetComponent<SpriteRenderer>().sortingOrder = 19;
            Debug.Log("CHECK wildcard.name:" + rummymanager.GetComponent<GameManager>().wildcard.gameObject.name);
            int jokerrank = rummymanager
                .GetComponent<GameManager>()
                .GetCardRankname(rummymanager.GetComponent<GameManager>().wildcard.name);

            Debug.Log("CHECK WHILE CARD:" + jokerrank);
            int dropcardrank = rummymanager
                .GetComponent<GameManager>()
                .GetCardRankname(gameDataList[0].drop_card[0].card.ToLower());
            if (dropcardrank == jokerrank)
            {
                rummymanager.GetComponent<GameManager>().ShowJokerForDiscard(spawnedcard);
            }
        }
    }

    public GameObject FindGameObjectByName(string nameToFind)
    {
        foreach (GameObject go in deck)
        {
            //Debug.Log("RES_Check + go + " + go.name.ToLower());
            if (go.name.ToLower() == nameToFind)
            {
                return go;
            }
        }

        return null;
    }

    List<string> seats()
    {
        activeusers.Clear();
        List<string> list = new List<string>();

        for (int i = 0; i < gameDataList[0].table_users.Count; i++)
        {
            if (gameDataList[0].table_users[i].user_id != "0")
            {
                list.Add(gameDataList[0].table_users[i].user_id);
                activeusers.Add(gameDataList[0].table_users[i]);
            }
        }
        return list;
    }

    public void showTableUsers()
    {
        Debug.Log("Res_Check" + " showTableUsers");
        placementdata.Clear();

        placementdata = gameDataList[0].table_users;

        int index = placementdata.FindIndex(x => x.user_id == Configuration.GetId());

        // Move the item to the first position if it's not already in the first position
        if (index > 0)
        {
            TableUser userData = placementdata[index];
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
                // Debug.Log("Res_Check - I am null");
                profiles[i].transform.GetChild(0).gameObject.SetActive(false);
                profiles[i].transform.GetChild(1).gameObject.SetActive(true);
                profiles[i].GetComponent<PointRummyChaalSlider>().enabled = false;
                //profiles[i].SetActive(false);
            }
            else
            {
                profiles[i].GetComponent<PointRummyChaalSlider>().enabled = true;
                profiles[i].GetComponent<PointRummyChaalSlider>().id = placementdata[i].user_id;
                profiles[i].transform.GetChild(0).gameObject.SetActive(true);
                profiles[i].transform.GetChild(1).gameObject.SetActive(false);
                profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<TextMeshProUGUI>().text = placementdata[i].name;
                profiles[i].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<TextMeshProUGUI>().text = FormatNumber(placementdata[i].wallet);
                Image img = profiles[i].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
                StartCoroutine(
                    DownloadImage(
                        placementdata[i].profile_pic,
                        img,
                        profiles[i].transform.GetChild(0).GetChild(1).GetComponent<Image>()
                    )
                );
                profiles[i].transform.GetChild(0).GetChild(1).GetComponent<Image>().sprite = circle;
            }
        }
        #region previous code
        //imageid = seats();

        //for (int i = 0; i < imageid.Count; i++)
        //{
        //    if (imageid[i] == Configuration.GetId())
        //        num = i;
        //}

        //int count = imageid.Count;
        //for (int i = 0; i < profiles.Count; i++)
        //{
        //    profiles[i].gameObject.SetActive(false);
        //}

        //bool numdone = false;

        //for (int i = 0; i < count; i++)
        //{
        //    if (num == 0)
        //    {
        //        profiles[i].gameObject.SetActive(true);
        //        profiles[i].GetComponent<PointRummyChaalSlider>().id = activeusers[i].user_id;
        //        profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = activeusers[i].name;
        //        profiles[i].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = activeusers[i].wallet;
        //        Image img = profiles[i].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        //        StartCoroutine(DownloadImage(activeusers[i].profile_pic, img, profiles[i].GetComponent<ProfileDetails>()));
        //    }
        //    else
        //    {
        //        if (imageid[i] == Configuration.GetId())
        //        {
        //            numdone = true;
        //            profiles[0].gameObject.SetActive(true);
        //            profiles[0].GetComponent<PointRummyChaalSlider>().id = activeusers[i].user_id;
        //            profiles[0].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = activeusers[i].name;
        //            profiles[0].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = activeusers[i].wallet;
        //            Image img = profiles[0].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        //            StartCoroutine(DownloadImage(activeusers[i].profile_pic, img, profiles[0].GetComponent<ProfileDetails>()));
        //        }
        //        else
        //        {
        //            if (!numdone)
        //            {
        //                profiles[i + 1].gameObject.SetActive(true);
        //                profiles[i + 1].GetComponent<PointRummyChaalSlider>().id = activeusers[i].user_id;
        //                profiles[i + 1].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = activeusers[i].name;
        //                profiles[i + 1].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = activeusers[i].wallet;
        //                Image img = profiles[i + 1].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        //                StartCoroutine(DownloadImage(activeusers[i].profile_pic, img, profiles[i + 1].GetComponent<ProfileDetails>()));
        //            }
        //            else
        //            {
        //                profiles[i].gameObject.SetActive(true);
        //                profiles[i].GetComponent<PointRummyChaalSlider>().id = activeusers[i].user_id;
        //                profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = activeusers[i].name;
        //                profiles[i].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = activeusers[i].wallet;
        //                Image img = profiles[i].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        //                StartCoroutine(DownloadImage(activeusers[i].profile_pic, img, profiles[i + 1].GetComponent<ProfileDetails>()));
        //            }
        //        }
        //    }
        //}
        #endregion
    }

    public int CountImage = 0;

    public IEnumerator DownloadImage(string ProfileAvatar, Image img, Image det)
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
                //  det.sprite = img.sprite;
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

    //private void OnConnected(ConnectResponse resp)
    //{
    //    Debug.Log("RES_Value + Connect : " + resp.sid);
    //    ControllerDetail.IsConnection = true;
    //    connectiontext.text = "connected: " + resp.sid;
    //    API_CALL_get_table();
    //}

    private void API_CALL_get_table()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        // socket start game
        PointRummyTableData jsonData = new PointRummyTableData();
        try
        {
            //jsonData.user_id = "1";
            jsonData.boot_value = Configuration.Getpointboot();
            jsonData.user_id = Configuration.GetId();
            // jsonData.token = "7534b47e2ffdbb3e3a13f49cb5205e4f";
            jsonData.token = Configuration.GetToken();

            jsonData.no_of_players = Configuration.Getpointplayer();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("get-table", jsonData);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-get-table " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_get_drop_card()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);

        basicID jsonData = new basicID();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("get-drop-card", jsonData);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-get-drop-card" + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_get_card()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        basicID jsonData = new basicID();
        try
        {
            jsonData.user_id = Configuration.GetId();
            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("get-card", jsonData);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-get-card " + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void API_CALL_getCardList()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        GameCardData jsonData = new GameCardData();
        try
        {
            jsonData.gameid = gameDataList[0].active_game_id;
            jsonData.user_id = Configuration.GetId();
            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("my-card", jsonData);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-my-card " + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public string cardswithme()
    {
        cardData.Clear();
        groupcount = 0;
        finalCardsList.Clear();
        list1 = rummymanager.GetComponent<GameManager>().list1;
        list2 = rummymanager.GetComponent<GameManager>().list2;
        list3 = rummymanager.GetComponent<GameManager>().list3;
        list4 = rummymanager.GetComponent<GameManager>().list4;
        list5 = rummymanager.GetComponent<GameManager>().list5;
        list6 = rummymanager.GetComponent<GameManager>().list6;
        if (list1.Count > 0)
            groupcount++;
        if (list2.Count > 0)
            groupcount++;
        if (list3.Count > 0)
            groupcount++;
        if (list4.Count > 0)
            groupcount++;
        if (list5.Count > 0)
            groupcount++;
        if (list6.Count > 0)
            groupcount++;

        List<string> finalCardsstringList = new List<string>();
        for (int i = 0; i < groupcount; i++)
        {
            List<string> cards = new List<string>();
            if (i == 0)
            {
                string carddata = "";
                if (rummymanager.GetComponent<GameManager>().list1.Count > 0)
                {
                    Debug.Log("Res_Check + list1 + " + i);
                    foreach (GameObject obj in list1)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager>().check1 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager>().list1,
                                    rummymanager.GetComponent<GameManager>().textcavas[0]
                                )
                            && rummymanager.GetComponent<GameManager>().numberofpuresequence > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager>().check1 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager>().list1,
                                    rummymanager.GetComponent<GameManager>().textcavas[0]
                                ) && rummymanager.GetComponent<GameManager>().isValid
                    )
                        carddata = "6";
                    else
                        carddata = "0";
                    finalcarddata value = new finalcarddata(carddata, cards);
                    finalCardsList.Add(value); // Add finalcarddata object to the list
                    string jsonStr3 = JsonUtility.ToJson(value);
                    finalCardsstringList.Add(jsonStr3);
                    //Debug.Log("Res_Value + CardData ID " + cardData[i]);
                    Debug.Log("Res_Value + CardData " + jsonStr3);
                    Debug.Log("Res_Value + finalCardsList count " + finalCardsList.Count);
                }
            }
            else if (i == 1)
            {
                string carddata = "";
                if (rummymanager.GetComponent<GameManager>().list2.Count > 0)
                {
                    Debug.Log("Res_Check + list2 + " + i);
                    foreach (GameObject obj in list2)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager>().check2 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager>().list2,
                                    rummymanager.GetComponent<GameManager>().textcavas[1]
                                )
                            && rummymanager.GetComponent<GameManager>().numberofpuresequence > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager>().check2 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager>().list2,
                                    rummymanager.GetComponent<GameManager>().textcavas[1]
                                ) && rummymanager.GetComponent<GameManager>().isValid
                    )
                        carddata = "6";
                    else
                        carddata = "0";
                    finalcarddata value = new finalcarddata(carddata, cards);
                    finalCardsList.Add(value); // Add finalcarddata object to the list
                    string jsonStr3 = JsonUtility.ToJson(value);
                    finalCardsstringList.Add(jsonStr3);
                    //Debug.Log("Res_Value + CardData ID " + cardData[i]);
                    Debug.Log("Res_Value + CardData " + jsonStr3);
                    Debug.Log("Res_Value + finalCardsList count " + finalCardsList.Count);
                }
            }
            else if (i == 2)
            {
                string carddata = "";
                if (rummymanager.GetComponent<GameManager>().list3.Count > 0)
                {
                    Debug.Log("Res_Check + list3 + " + i);
                    foreach (GameObject obj in list3)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager>().check3 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager>().list3,
                                    rummymanager.GetComponent<GameManager>().textcavas[2]
                                )
                            && rummymanager.GetComponent<GameManager>().numberofpuresequence > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager>().check3 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager>().list3,
                                    rummymanager.GetComponent<GameManager>().textcavas[2]
                                ) && rummymanager.GetComponent<GameManager>().isValid
                    )
                        carddata = "6";
                    else
                        carddata = "0";
                    finalcarddata value = new finalcarddata(carddata, cards);
                    finalCardsList.Add(value); // Add finalcarddata object to the list
                    string jsonStr3 = JsonUtility.ToJson(value);
                    finalCardsstringList.Add(jsonStr3);
                    //Debug.Log("Res_Value + CardData ID " + cardData[i]);
                    Debug.Log("Res_Value + CardData " + jsonStr3);
                    Debug.Log("Res_Value + finalCardsList count " + finalCardsList.Count);
                }
            }
            else if (i == 3)
            {
                string carddata = "";
                if (rummymanager.GetComponent<GameManager>().list4.Count > 0)
                {
                    Debug.Log("Res_Check + list4 + " + i);
                    foreach (GameObject obj in list4)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager>().check4 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager>().list4,
                                    rummymanager.GetComponent<GameManager>().textcavas[3]
                                )
                            && rummymanager.GetComponent<GameManager>().numberofpuresequence > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager>().check4 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager>().list4,
                                    rummymanager.GetComponent<GameManager>().textcavas[3]
                                ) && rummymanager.GetComponent<GameManager>().isValid
                    )
                        carddata = "6";
                    else
                        carddata = "0";
                    finalcarddata value = new finalcarddata(carddata, cards);
                    finalCardsList.Add(value); // Add finalcarddata object to the list
                    string jsonStr3 = JsonUtility.ToJson(value);
                    finalCardsstringList.Add(jsonStr3);
                    //Debug.Log("Res_Value + CardData ID " + cardData[i]);
                    Debug.Log("Res_Value + CardData " + jsonStr3);
                    Debug.Log("Res_Value + finalCardsList count " + finalCardsList.Count);
                }
            }
            else if (i == 4)
            {
                string carddata = "";
                if (rummymanager.GetComponent<GameManager>().list5.Count > 0)
                {
                    Debug.Log("Res_Check + list5 + " + i);
                    foreach (GameObject obj in list5)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager>().check5 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager>().list5,
                                    rummymanager.GetComponent<GameManager>().textcavas[4]
                                )
                            && rummymanager.GetComponent<GameManager>().numberofpuresequence > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager>().check5 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager>().list5,
                                    rummymanager.GetComponent<GameManager>().textcavas[4]
                                ) && rummymanager.GetComponent<GameManager>().isValid
                    )
                        carddata = "6";
                    else
                        carddata = "0";
                    finalcarddata value = new finalcarddata(carddata, cards);
                    finalCardsList.Add(value); // Add finalcarddata object to the list
                    string jsonStr3 = JsonUtility.ToJson(value);
                    finalCardsstringList.Add(jsonStr3);
                    //Debug.Log("Res_Value + CardData ID " + cardData[i]);
                    Debug.Log("Res_Value + CardData " + jsonStr3);
                    Debug.Log("Res_Value + finalCardsList count " + finalCardsList.Count);
                }
            }
            else if (i == 5)
            {
                string carddata = "";
                if (rummymanager.GetComponent<GameManager>().list6.Count > 0)
                {
                    Debug.Log("Res_Check + list6 " + i);
                    foreach (GameObject obj in list6)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager>().check6 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager>().list6,
                                    rummymanager.GetComponent<GameManager>().textcavas[5]
                                )
                            && rummymanager.GetComponent<GameManager>().numberofpuresequence > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager>().check6 =
                            rummymanager
                                .GetComponent<GameManager>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager>().list6,
                                    rummymanager.GetComponent<GameManager>().textcavas[5]
                                ) && rummymanager.GetComponent<GameManager>().isValid
                    )
                        carddata = "6";
                    else
                        carddata = "0";
                    finalcarddata value = new finalcarddata(carddata, cards);
                    finalCardsList.Add(value); // Add finalcarddata object to the list
                    string jsonStr3 = JsonUtility.ToJson(value);
                    finalCardsstringList.Add(jsonStr3);
                    //Debug.Log("Res_Value + CardData ID " + cardData[i]);
                    Debug.Log("Res_Value + CardData " + jsonStr3);
                    Debug.Log("Res_Value + finalCardsList count " + finalCardsList.Count);
                }
            }
        }

        DataList dataListWrapper = new DataList();
        dataListWrapper.dataList = finalCardsList;

        string jsonString = JsonUtility.ToJson(finalCardsList, true);

        string jsonArrayString = "[" + string.Join(",", finalCardsstringList.ToArray()) + "]";
        Debug.Log("Res_Value + finalCardsList data " + jsonArrayString);

        Debug.Log("RES_Value + EntireJSON: " + jsonArrayString);
        Debug.Log("RES_Value + EntireJSONCount: " + finalCardsList.Count);
        return jsonArrayString;
    }

    public void API_CALL_drop_card(string dropcard)
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        GameCardDropData jsonObjectDropCard = new GameCardDropData();
        try
        {
            dropcard = dropcard.ToUpper();
            jsonObjectDropCard.card = dropcard;
            jsonObjectDropCard.user_id = Configuration.GetId();
            jsonObjectDropCard.token = Configuration.GetToken();
            string jsonStr2 = cardswithme();
            jsonObjectDropCard.json = jsonStr2;
            string jsonStr = JsonUtility.ToJson(jsonObjectDropCard);

            customNamespace.Emit("drop-card", jsonObjectDropCard);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-drop-card " + jsonObjectDropCard);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_pack_game()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        GamePackData jsonObjectPackGame = new GamePackData();
        try
        {
            jsonObjectPackGame.user_id = Configuration.GetId();
            jsonObjectPackGame.token = Configuration.GetToken();
            string jsonStr2 = cardswithme();
            jsonObjectPackGame.json = jsonStr2;
            Debug.Log("RES_VALUE" + " Array of cards " + jsonStr2);
            string jsonStr = JsonUtility.ToJson(jsonObjectPackGame);
            customNamespace.Emit("pack-game", jsonObjectPackGame);

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-pack-game " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }

        //dropbutton.SetActive(false);
    }

    public void API_CALL_declare()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        GamePackData jsonObjectPackGame = new GamePackData();

        try
        {
            declared = true;
            jsonObjectPackGame.user_id = Configuration.GetId();
            jsonObjectPackGame.token = Configuration.GetToken();
            string jsonStr2 = cardswithme();
            jsonObjectPackGame.json = jsonStr2;
            string jsonStr = JsonUtility.ToJson(jsonObjectPackGame);
            customNamespace.Emit("declare", jsonObjectPackGame);
            Debug.Log("RES_CHECK + Correct Declare Emit");
            Debug.Log("RES_Valu + Correct Declare Emit Values: " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_Wrongdeclare()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        GamePackData jsonObjectPackGame = new GamePackData();

        try
        {
            declared = true;
            jsonObjectPackGame.user_id = Configuration.GetId();
            jsonObjectPackGame.token = Configuration.GetToken();
            string jsonStr2 = cardswithme();
            jsonObjectPackGame.json = jsonStr2;
            string jsonStr = JsonUtility.ToJson(jsonObjectPackGame);
            customNamespace.Emit("wrong-delclare", jsonObjectPackGame);
            Debug.Log("RES_CHECK + wrong Declare Emit");
            Debug.Log("RES_Valu + wrong Declare Emit Values: " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_declareBack()
    {
        var customNamespace = Manager.GetSocket(custom_namespace);
        GamePackData jsonObjectPackGame = new GamePackData();

        try
        {
            jsonObjectPackGame.user_id = Configuration.GetId();
            jsonObjectPackGame.token = Configuration.GetToken();
            string jsonStr2 = cardswithme();
            jsonObjectPackGame.json = jsonStr2;
            string jsonStr = JsonUtility.ToJson(jsonObjectPackGame);
            customNamespace.Emit("declare-back", jsonObjectPackGame);
            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE + DeclareBack json " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void CALL_Leave_table()
    {
        in_game = false;
        var customNamespace = Manager.GetSocket(custom_namespace);
        // socket start game
        PointRummyTableData jsonData = new PointRummyTableData();
        try
        {
            jsonData.user_id = Configuration.GetId();
            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("leave-table", jsonData);
            Debug.Log("Leave Table");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    //private void OnDisable()
    //{
    //    Manager.Close();
    //}

    private void OnDisconnected()
    {
        //StopAllCoroutines();
        connectiontext.text = "Disconnected";
    }

    public void leave()
    {
        CALL_Leave_table();
    }

    public void onStatus(string resposeText)
    {
        Debug.Log("Socket " + resposeText);
    }

    public void OnNumber(string resposeText)
    {
        Debug.Log("Socket 2 " + resposeText);
    }
}
