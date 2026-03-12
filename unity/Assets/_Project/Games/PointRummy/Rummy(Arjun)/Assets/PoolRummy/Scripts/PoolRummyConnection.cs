using System;
using System.Collections;
using System.Collections.Generic;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class PoolRummyConnection : MonoBehaviour
{
    [Header("Controller details")]
    public string name_space;
    private SocketManager Manager;
    public Text connectiontext;

    public RummyScriptable rum;
    public SocketResponse socketResponse;

    //private SocketIOUnity socket;
    public List<CachetaGameData> gameDataList;
    public List<PointRummyGameData> gameDataListFinal;
    public RummyGameData gameData;
    public List<TableUser> activeusers;
    public List<string> imageid;
    public List<GameObject> profiles;

    //public BackScene back;
    public RummyScriptable rummyobject;
    public PoolrummyData poolrummyobject;
    public List<string> seat;
    public List<JsonCardData> mycarddata;
    public List<string> cardNames;
    public GameObject rummymanager;
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
        get_card;
    public TextMeshProUGUI join,
        packed,
        info;
    public GameObject join2;
    public Transform location;
    public Sprite circle;
    public bool countdownbool;
    public TextMeshProUGUI gameidtext;
    public List<finalcarddata> finalCardsList = new List<finalcarddata>();
    public List<Pointss> roundpoints = new List<Pointss>();
    public GameObject pointshowprefab;
    public Transform pointscontent;
    public GameObject showpointpanel;
    public string currentchaalid;
    public GameObject backcard;
    public bool firsttime;
    public string gameid;
    public TextMeshProUGUI sockettimertext;
    public int chaaltimer;
    public List<Image> profileimage;
    public Sprite maskimage;
    public Toggle soundToggle;
    public Toggle musicToggle;

    [Header("Cards")]
    public List<GameObject> deck;

    [System.Serializable]
    public class DataList
    {
        public List<finalcarddata> dataList;
    }

    public void openclosepanel(GameObject obj)
    {
        DOVirtual.DelayedCall(
            .1f,
            () =>
            {
                if (!obj.activeSelf)
                    obj.SetActive(true);
                else
                    obj.SetActive(false);
            }
        );
    }

    private void OnEnable()
    {

        if (Configuration.Getpoolplayer() == "2")
        {
            for (int i = 0; i < profiles.Count; i++)
            {
                if (i > 1)
                {
                    profiles[i].SetActive(false);
                }
            }

            profiles[1].transform.position = new Vector3(-0.15f, 4.10f, 0);
        }

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
                profiles[0].transform.GetChild(0).GetChild(1).GetComponent<Image>()
            )
        );
        profiles[0].transform.GetChild(0).GetChild(1).GetComponent<Image>().sprite = circle;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        join.gameObject.SetActive(true);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(name_space);
        get_card = true;
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        Manager.Open();
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("get-table", OnGetTableResponse);
        customNamespace.On<string>("my-card", OnGetCardResponse);
        customNamespace.On<string>("get-card", OnGetOneCardResponse);
        customNamespace.On<string>("drop-card", OnDropResponse);
        customNamespace.On<string>("get-drop-card", OnGetDropCardResponse);
        customNamespace.On<string>("pack-game", OnGetDropGameResponse);
        customNamespace.On<string>("declare-back", OnGetDeclareBackGameResponse);
        customNamespace.On<string>("declare", OnGetDeclareGameResponse);
        customNamespace.On<string>("wrong-delclare", OnGetDeclareGameResponse);
        customNamespace.On<string>("leave-table", OnLeaveTableResponse);
        customNamespace.On<string>("trigger", OnTriggerResponse);
        customNamespace.On<string>("start-game", OnStartGameResponse);
        customNamespace.On<string>("rummy_timer", OnRummyTimerResponse);

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
    void Start()
    {
        for (int i = 0; i < profileimage.Count; i++)
        {
            profileimage[i].sprite = maskimage;
        }

        CommonUtil.CheckLog("music " + Configuration.GetMusic());

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

    void OnConnected(ConnectResponse resp)
    {
        Debug.Log("Connected + " + resp.sid);
        API_CALL_get_table();
    }

    private void OnRummyTimerResponse(string args)
    {
        Debug.Log("socket timer Json :" + args);
        sockettimertext.text = "socket timer: " + args;
        chaaltimer = int.Parse(args);
    }

    private void OnStartGameResponse(string args)
    {
        Debug.Log("Start Game Json :" + args);
        try
        {
            GameResponse resp = JsonUtility.FromJson<GameResponse>(args);
            gameid = resp.game_id.ToString();
            if (resp.code == 406)
            {
                CommonUtil.ShowToast(resp.message);
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    public void showpoints()
    {
        for (int i = 0; i < gameDataList[0].game_users.Count; i++)
        {
            showpointpanel
                .transform.GetChild(3)
                .GetChild(i + 1)
                .transform.gameObject.SetActive(true);
        }
        if (gameDataList[0].round > 1)
        {
            showpointpanel.SetActive(true);
            for (int i = 0; i < gameDataList[0].game_users.Count; i++)
            {
                showpointpanel
                    .transform.GetChild(3)
                    .GetChild(i + 1)
                    .transform.gameObject.SetActive(true);
            }
            for (int i = 0; i < gameDataList[0].round - 1; i++)
            {
                GameObject roundpoint = Instantiate(pointshowprefab);
                roundpoint.transform.SetParent(pointscontent);
                for (int j = 0; j < gameDataList[0].game_users.Count; j++)
                {
                    roundpoint.transform.GetChild(j + 1).gameObject.SetActive(true);
                }
                roundpoint.transform.localScale = new Vector3(1.0f, 1.0f, 1.0f);
                roundpoint.transform.GetChild(0).GetComponent<Text>().text = i + 1 + "";
            }

            int usernum = 0;
            for (int i = 0; i < gameDataList[0].game_users.Count; i++)
            {
                for (int j = 0; j < gameDataList[0].points.Count; j++)
                {
                    if (gameDataList[0].game_users[i].user_id == gameDataList[0].points[j].user_id)
                    {
                        showpointpanel
                            .transform.GetChild(3)
                            .GetChild(i + 1)
                            .GetComponent<Text>()
                            .text = "Player " + (i + 1) + "\n" + gameDataList[0].points[j].name;
                        pointscontent.GetChild(usernum).GetChild(i + 1).GetComponent<Text>().text =
                            gameDataList[0].points[j].total_points
                            + " ( + "
                            + gameDataList[0].points[j].points
                            + ")";
                        usernum++;
                    }
                }

                usernum = 0;
            }
        }
        else
        {
            rummymanager
                .GetComponent<GameManager_Pool>()
                .showtoastmessage("Please play atleast one round to see the points");
        }
    }

    public void closeshowpoints()
    {
        foreach (Transform child in pointscontent.transform)
        {
            Destroy(child.gameObject);
        }
        showpointpanel.SetActive(false);
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
        //     return number.ToString();
        // }
    }

    private void OnGetTableResponse(string args)
    {
        for (int i = 0; i < profileimage.Count; i++)
        {
            profileimage[i].sprite = maskimage;
        }
        Debug.Log("RES_Value + get-table Json :" + args);
        try
        {
            Debug.Log("Called");
            socketResponse = JsonUtility.FromJson<SocketResponse>(args);
            if (socketResponse.code == 406)
            {
                rummymanager
                    .GetComponent<GameManager_Pool>()
                    .showtoastmessage(socketResponse.message);
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

    private void OnGetDeclareGameResponse(string args)
    {
        Debug.Log("GetDeclare-Game Json :" + args);
        try
        {
            Debug.Log("Called");
            rummymanager.GetComponent<GameManager_Pool>().CheckAfterEveryMove();
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
            Debug.Log("RES_Check + get-Drop-Card");
            DropCardResponse card = JsonUtility.FromJson<DropCardResponse>(args);
            Debug.Log("RES_Value + get-Drop-Card: " + card.card[0].card);
            askedCard = card.card[0].card;
            StartCoroutine(
                rummymanager.GetComponent<GameManager_Pool>().InatntiateSingleCard("Discard_Area")
            );
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    //when we drop a card
    private void OnDropResponse(string args)
    {
        try
        {
            Debug.Log("RES_Value + On Response for drop card " + args);
            rummymanager.GetComponent<GameManager_Pool>().dropcardresponse = true;
            OnDropCardData jsonData = JsonUtility.FromJson<OnDropCardData>(args);

            if (jsonData.code == 200)
            {
                if (rummymanager.GetComponent<GameManager_Pool>().isdeclared)
                {
                    Debug.Log("RES_Check + API_CALL_declare");
                    rummymanager
                        .GetComponent<GameManager_Pool>()
                        .DiscardAfterONResponse(
                            rummymanager.GetComponent<GameManager_Pool>().finishdeskcard,
                            "FinishDesk"
                        );
                }
                else
                {
                    rummymanager
                        .GetComponent<GameManager_Pool>()
                        .DiscardAfterONResponse(
                            rummymanager.GetComponent<GameManager_Pool>().discardCard,
                            "Discard_Area"
                        );
                }
            }
            else
            {
                StartCoroutine(
                    rummymanager
                        .GetComponent<GameManager_Pool>()
                        .MoveCardBack(
                            rummymanager.GetComponent<GameManager_Pool>().discardCard,
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .draggedCardOriginalPosition,
                            0.5f
                        )
                );

                rummymanager.GetComponent<GameManager_Pool>().showtoastmessage(jsonData.message);
            }
        }
        catch (System.Exception ex)
        {
            //StartCoroutine(rummymanager.GetComponent<GameManager_Pool>().MoveCardBack(rummymanager.GetComponent<GameManager_Pool>().discardCard,
            //        rummymanager.GetComponent<GameManager_Pool>().draggedCardOriginalPosition, 0.5f));
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
            Debug.Log("RES_Check + get-Card");
            OneCard card = JsonUtility.FromJson<OneCard>(args);
            Debug.Log("RES_Check + get-Card: " + card.card[0].cards);
            askedCard = card.card[0].cards;
            StartCoroutine(
                rummymanager
                    .GetComponent<GameManager_Pool>()
                    .InatntiateSingleCard("placeholderCard")
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
#if UNITY_ANDROID
            this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
#elif UNITY_WEBGL
            //SceneManager.LoadScene("HomePage");
            DOVirtual.DelayedCall(.2f, () =>
            {
                this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
            });
#endif
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnTriggerResponse(string args)
    {
        Debug.Log("Called");
        Debug.Log("Res_Value + trigger Json :" + args);

        if (args == "call_status")
        {
            API_CALL_status(gameid);
        }
        else
        {
            gameid = args;
            API_CALL_status(gameid);
        }
    }

    public void API_CALL_status(string gamedata)
    {
        Debug.Log("status Called api");
        StartCoroutine(Request(gamedata));
    }

    private void StartCountdown()
    {
        rummymanager.GetComponent<GameManager_Pool>().groupButton.SetActive(false);
        rummymanager.GetComponent<GameManager_Pool>().declare.SetActive(false);
        rummymanager.GetComponent<GameManager_Pool>().declareback.SetActive(false);
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
        var customNamespace = Manager.GetSocket(name_space);

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
        join.gameObject.SetActive(true);
        for (int i = 0; i < profiles.Count; i++)
        {
            profiles[i].GetComponent<PoolRummyChaalSlider>().start = false;
            profiles[i].transform.GetChild(0).GetChild(3).gameObject.SetActive(true);
            profiles[i].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
        }

        if (rummymanager.GetComponent<GameManager_Pool>().list1.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().list1)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager_Pool>().list1.Clear();

        if (rummymanager.GetComponent<GameManager_Pool>().list2.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().list2)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager_Pool>().list2.Clear();

        if (rummymanager.GetComponent<GameManager_Pool>().list3.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().list3)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager_Pool>().list3.Clear();

        if (rummymanager.GetComponent<GameManager_Pool>().list4.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().list4)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager_Pool>().list4.Clear();

        if (rummymanager.GetComponent<GameManager_Pool>().list5.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().list5)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager_Pool>().list5.Clear();

        if (rummymanager.GetComponent<GameManager_Pool>().list6.Count != 0)
        {
            foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().list6)
                Destroy(obj);
        }
        rummymanager.GetComponent<GameManager_Pool>().list6.Clear();

        //if (rummymanager.GetComponent<GameManager_Pool>().discardedlist.Count != 0)
        //{
        //    foreach (GameObject obj in rummymanager.GetComponent<GameManager_Pool>().discardedlist)
        //        Destroy(obj);
        //}
        //rummymanager.GetComponent<GameManager_Pool>().discardedlist.Clear();

        if (rummymanager.GetComponent<GameManager_Pool>().finishdeskcard != null)
        {
            Destroy(rummymanager.GetComponent<GameManager_Pool>().finishdeskcard);
            rummymanager.GetComponent<GameManager_Pool>().finishdeskcardogpos = Vector3.zero;
        }

        Destroy(rummymanager.GetComponent<GameManager_Pool>().wildcard);

        rummymanager.GetComponent<GameManager_Pool>().declareback.SetActive(false);

        rummymanager.GetComponent<GameManager_Pool>().names.Clear();
        rummymanager.GetComponent<GameManager_Pool>().setFromAPI.Clear();
        rummymanager.GetComponent<GameManager_Pool>().spawnedCards.Clear();
        rummymanager.GetComponent<GameManager_Pool>().check1 = false;
        rummymanager.GetComponent<GameManager_Pool>().check2 = false;
        rummymanager.GetComponent<GameManager_Pool>().check3 = false;
        rummymanager.GetComponent<GameManager_Pool>().check4 = false;
        rummymanager.GetComponent<GameManager_Pool>().check5 = false;
        rummymanager.GetComponent<GameManager_Pool>().check6 = false;

        rummymanager.GetComponent<GameManager_Pool>().canplay = false;

        rummymanager.GetComponent<GameManager_Pool>().isdeclared = false;

        rummymanager.GetComponent<GameManager_Pool>().numoflist = 0;
        rummymanager.GetComponent<GameManager_Pool>().numofmatch = 0;
        rummymanager.GetComponent<GameManager_Pool>().moveup = false;
        rummymanager.GetComponent<GameManager_Pool>().clicktime = 0;

        rummymanager.GetComponent<GameManager_Pool>().uplist.Clear();
        rummymanager.GetComponent<GameManager_Pool>().wildcardrank = 0;
        rummymanager.GetComponent<GameManager_Pool>().ischaal = false;

        rummymanager.GetComponent<GameManager_Pool>().spawned = false;
        rummymanager.GetComponent<GameManager_Pool>().gamestart = false;

        rummymanager.GetComponent<GameManager_Pool>().drop.SetActive(true);

        for (int i = 0; i < rummymanager.GetComponent<GameManager_Pool>().textcavas.Count; i++)
        {
            Destroy(rummymanager.GetComponent<GameManager_Pool>().textcavas[i]);
        }

        rummymanager.GetComponent<GameManager_Pool>().textcavas.Clear();

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
        mycarddata.Clear();
        cardNames.Clear();
        Idropped = null;
        Done = false;
        jokertoshow = null;
        jokername = string.Empty;
        groupcount = 0;
        cardData.Clear();
        groupcount = 0;
        countdownTime = 5;
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
        StartGame();
        yield return new WaitForSeconds(2);
    }

    IEnumerator countdown(TextMeshProUGUI wait)
    {
        int countdownTime = 8;

        // Start the countdown loop
        while (countdownTime >= 0)
        {
            wait.fontSize = 150;
            wait.GetComponent<Animator>().enabled = true;
            wait.text = countdownTime.ToString();
            yield return new WaitForSeconds(1);
            countdownTime--;
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

    IEnumerator Request(string gamedata)
    {
        string url = Configuration.GamePoolRummyStatus;
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        Debug.Log("RES_Value + user_id = " + Configuration.GetId());
        if (socketResponse.table_data[0].table_id != null)
        {
            form.AddField("table_id", socketResponse.table_data[0].table_id);
            Debug.Log("RES_Value + Table id = " + socketResponse.table_data[0].table_id);
        }
        else
        {
            form.AddField("table_id", "");
            Debug.Log("RES_Value + Table id = empty");
        }
        if (gameid != string.Empty)
            form.AddField("game_id", gameid);
        else
            form.AddField("game_id", "");

        //form.AddField("game_id", "");
        form.AddField("token", Configuration.GetToken());
        Debug.Log("RES_Value + token = " + Configuration.GetToken());

        Debug.Log(
            "RES_Check + call request trigger  + gameid: "
                + gameid
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
                gameDataList.Clear();
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
                Debug.Log("RES_Value + Trigger Response: " + request.downloadHandler.text);
                string cachetadata = request.downloadHandler.text;
                CachetaGameData List = JsonUtility.FromJson<CachetaGameData>(cachetadata);
                PointRummyGameData RootObject = JsonUtility.FromJson<PointRummyGameData>(
                    cachetadata
                );

                gameDataList.Add(List);
                gameidtext.text = "Game_ID: " + gameDataList[0].active_game_id;

                if (gameDataList[0].active_game_id != string.Empty)
                    gameid = gameDataList[0].active_game_id;

                info.text =
                    "#"
                    + gameDataList[0].active_game_id
                    + "    "
                    + " Pool Rummy    "
                    + gameDataList[0].table_detail.boot_value;

                //InternetCheck.Instance.checkinvalid(RootObject.code);
                //InternetCheck.Instance.checkinvalid(List.code);

                if (profiles[0].GetComponent<PoolRummyChaalSlider>().start)
                {
                    if (
                        gameDataList[0].last_card.isDeleted == "0"
                        && currentchaalid != gameDataList[0].last_card.user_id
                    )
                    {
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
                                    profiles[i].GetComponent<PoolRummyChaalSlider>().id
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

                showTableUsers();

                if (gameDataList[0].game_users.Count > 1)
                    startgamedatabool = true;
                else
                    startgamedatabool = false;

                if (gameDataList[0].game_users.Count > 1)
                {
                    //  rummymanager.SetActive(true);
                    join.gameObject.SetActive(false);
                    join.fontSize = 40;
                    join.GetComponent<Animator>().enabled = false;
                    join2.SetActive(false);

                    for (int i = 0; i < gameDataList[0].game_users.Count; i++)
                    {
                        if (gameDataList[0].game_users[i].packed == "1")
                        {
                            for (int j = 0; j < profiles.Count; j++)
                            {
                                if (
                                    gameDataList[0].game_users[i].user_id
                                    == profiles[j].GetComponent<PoolRummyChaalSlider>().id
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
                                == profiles[i].GetComponent<PoolRummyChaalSlider>().id
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
                                    .GetComponent<GameManager_Pool>()
                                    .declareback.SetActive(true);
                                rummymanager.GetComponent<GameManager_Pool>().drop.SetActive(false);
                            }
                        }
                    }

                    if (gameDataList[0].game_status == 2 && Done == false)
                    {
                        gameDataListFinal.Add(RootObject);
                        gameData = JsonUtility.FromJson<RummyGameData>(
                            request.downloadHandler.text
                        );
                        int points = 0;
                        for (int i = 0; i < gameDataListFinal[0].points.Count; i++)
                        {
                            if (gameDataListFinal[0].points[i].user_id == Configuration.GetId())
                                points = int.Parse(gameDataListFinal[0].points[i].total_points);
                        }
                        Debug.Log("RES_Check + my points + " + points);
                        rummymanager.GetComponent<GameManager_Pool>().declareback.SetActive(false);
                        if (points < 101)
                        {
                            if (gameDataListFinal[0].game_users.Count == 2)
                            {
                                int opponentpoint = 0;
                                for (int i = gameDataListFinal[0].points.Count - 1; i >= 0; i--)
                                {
                                    if (
                                        gameDataListFinal[0].points[i].user_id
                                        != Configuration.GetId()
                                    )
                                        opponentpoint = int.Parse(
                                            gameDataListFinal[0].points[i].total_points
                                        );
                                }
                                Debug.Log("RES_Value + oppenenetpoints + " + opponentpoint);
                                if (opponentpoint < 101)
                                {
                                    countdownTime = 14;
                                    StartCountdown();
                                    panel.SetActive(true);
                                    //panel.transform.GetChild(1).GetChild(0).GetComponent<Text>().text = "Result | Game ID : " + gameData.game_log[0].game_id;
                                    jokername = gameData.joker;

                                    foreach (GameObject obj in jokercards)
                                    {
                                        obj.name = obj.name.ToUpper();
                                        if (obj.name == jokername)
                                            jokertoshow = obj.GetComponent<SpriteRenderer>().sprite;
                                    }

                                    panel
                                        .transform.GetChild(5)
                                        .GetChild(0)
                                        .GetComponent<Image>()
                                        .sprite = jokertoshow;

                                    int finalusercount = gameDataListFinal[0].game_users.Count;

                                    gameData.flattened_user_cards = new List<FlattenedUserCards>();

                                    for (
                                        int i = 0;
                                        i < gameDataListFinal[0].game_users_cards.Count;
                                        i++
                                    )
                                    {
                                        FlattenedUserCards flattenedUserCards =
                                            new FlattenedUserCards();
                                        flattenedUserCards.user_id = gameDataListFinal[0]
                                            .game_users_cards[i]
                                            .user
                                            .user_id;

                                        if (
                                            gameDataListFinal[0]
                                                .game_users_cards[i]
                                                .user
                                                .cards
                                                .Count > 0
                                        )
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
                                                    Debug.Log("RES_Check + j + " + j);
                                                    Debug.Log("RES_Check + i + " + i);
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
                                        userdetails.transform.localScale = new Vector3(
                                            1.0f,
                                            1.0f,
                                            1.0f
                                        );
                                        userdetails
                                            .transform.GetChild(0)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].name;
                                        userdetails
                                            .transform.GetChild(2)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].score;
                                        userdetails
                                            .transform.GetChild(3)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].win;
                                        userdetails
                                            .transform.GetChild(4)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].total;

                                        if (
                                            gameDataListFinal[0].game_users[i].user_id
                                            == gameDataListFinal[0].winner_user_id
                                        )
                                            userdetails
                                                .transform.GetChild(1)
                                                .GetComponent<Text>()
                                                .text = "Winner";
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
                                                j
                                                    < gameData
                                                        .flattened_user_cards[i]
                                                        .all_cards
                                                        .Count;
                                                j++
                                            )
                                            {
                                                if (j <= 18)
                                                {
                                                    if (
                                                        gameData
                                                            .flattened_user_cards[i]
                                                            .all_cards
                                                            .Count == 0
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
                                                        gameData.flattened_user_cards[i].all_cards[
                                                            j
                                                        ] == ""
                                                    )
                                                    {
                                                        userdetails
                                                            .transform.GetChild(5)
                                                            .GetChild(j)
                                                            .GetComponent<Image>()
                                                            .enabled = false;
                                                    }

                                                    if (
                                                        gameData
                                                            .flattened_user_cards[i]
                                                            .all_cards
                                                            .Count == 0
                                                    )
                                                    {
                                                        userdetails
                                                            .transform.GetChild(5)
                                                            .GetChild(j)
                                                            .GetComponent<Image>()
                                                            .sprite = defaultsprite;
                                                    }
                                                    else if (
                                                        gameData.flattened_user_cards[i].all_cards[
                                                            j
                                                        ] == "Empty"
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
                                                                gameData
                                                                    .flattened_user_cards[i]
                                                                    .all_cards[j] == obj.name
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
                                                                        .GetComponent<GameManager_Pool>()
                                                                        .handlewildcardforresult(
                                                                            gameData
                                                                                .flattened_user_cards[
                                                                                    i
                                                                                ]
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
                                else
                                {
                                    panel.SetActive(true);
                                    panel.transform.GetChild(7).gameObject.SetActive(false);
                                    panel.transform.GetChild(8).gameObject.SetActive(true);

                                    countdownText.text = "Congratulations!! You Won the Game";

                                    //panel.transform.GetChild(1).GetChild(0).GetComponent<Text>().text = "Result | Game ID : " + gameData.game_log[0].game_id;
                                    jokername = gameData.joker;

                                    foreach (GameObject obj in jokercards)
                                    {
                                        obj.name = obj.name.ToUpper();
                                        if (obj.name == jokername)
                                            jokertoshow = obj.GetComponent<SpriteRenderer>().sprite;
                                    }

                                    panel
                                        .transform.GetChild(5)
                                        .GetChild(0)
                                        .GetComponent<Image>()
                                        .sprite = jokertoshow;

                                    int finalusercount = gameDataListFinal[0].game_users.Count;

                                    gameData.flattened_user_cards = new List<FlattenedUserCards>();

                                    for (
                                        int i = 0;
                                        i < gameDataListFinal[0].game_users_cards.Count;
                                        i++
                                    )
                                    {
                                        FlattenedUserCards flattenedUserCards =
                                            new FlattenedUserCards();
                                        flattenedUserCards.user_id = gameDataListFinal[0]
                                            .game_users_cards[i]
                                            .user
                                            .user_id;

                                        if (
                                            gameDataListFinal[0]
                                                .game_users_cards[i]
                                                .user
                                                .cards
                                                .Count > 0
                                        )
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
                                                    Debug.Log("RES_Check + j + " + j);
                                                    Debug.Log("RES_Check + i + " + i);
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
                                        userdetails.transform.localScale = new Vector3(
                                            1.0f,
                                            1.0f,
                                            1.0f
                                        );
                                        userdetails
                                            .transform.GetChild(0)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].name;
                                        userdetails
                                            .transform.GetChild(2)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].score;
                                        userdetails
                                            .transform.GetChild(3)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].win;
                                        userdetails
                                            .transform.GetChild(4)
                                            .GetComponent<Text>()
                                            .text = gameData.game_users[i].total;

                                        if (
                                            gameDataListFinal[0].game_users[i].user_id
                                            == gameDataListFinal[0].winner_user_id
                                        )
                                            userdetails
                                                .transform.GetChild(1)
                                                .GetComponent<Text>()
                                                .text = "Winner";
                                        else
                                            userdetails
                                                .transform.GetChild(1)
                                                .GetComponent<Text>()
                                                .text = "Dropped";

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
                                                j
                                                    < gameData
                                                        .flattened_user_cards[i]
                                                        .all_cards
                                                        .Count;
                                                j++
                                            )
                                            {
                                                if (j <= 18)
                                                {
                                                    if (
                                                        gameData
                                                            .flattened_user_cards[i]
                                                            .all_cards
                                                            .Count == 0
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
                                                        gameData.flattened_user_cards[i].all_cards[
                                                            j
                                                        ] == ""
                                                    )
                                                    {
                                                        userdetails
                                                            .transform.GetChild(5)
                                                            .GetChild(j)
                                                            .GetComponent<Image>()
                                                            .enabled = false;
                                                    }

                                                    if (
                                                        gameData
                                                            .flattened_user_cards[i]
                                                            .all_cards
                                                            .Count == 0
                                                    )
                                                    {
                                                        userdetails
                                                            .transform.GetChild(5)
                                                            .GetChild(j)
                                                            .GetComponent<Image>()
                                                            .sprite = defaultsprite;
                                                    }
                                                    else if (
                                                        gameData.flattened_user_cards[i].all_cards[
                                                            j
                                                        ] == "Empty"
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
                                                                gameData
                                                                    .flattened_user_cards[i]
                                                                    .all_cards[j] == obj.name
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
                                                                        .GetComponent<GameManager_Pool>()
                                                                        .handlewildcardforresult(
                                                                            gameData
                                                                                .flattened_user_cards[
                                                                                    i
                                                                                ]
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
                            }
                            else
                            {
                                countdownTime = 14;
                                StartCountdown();
                                panel.SetActive(true);
                                //panel.transform.GetChild(1).GetChild(0).GetComponent<Text>().text = "Result | Game ID : " + gameData.game_log[0].game_id;
                                jokername = gameData.joker;

                                foreach (GameObject obj in jokercards)
                                {
                                    obj.name = obj.name.ToUpper();
                                    if (obj.name == jokername)
                                        jokertoshow = obj.GetComponent<SpriteRenderer>().sprite;
                                }

                                panel
                                    .transform.GetChild(5)
                                    .GetChild(0)
                                    .GetComponent<Image>()
                                    .sprite = jokertoshow;

                                int finalusercount = gameDataListFinal[0].game_users.Count;

                                gameData.flattened_user_cards = new List<FlattenedUserCards>();

                                for (
                                    int i = 0;
                                    i < gameDataListFinal[0].game_users_cards.Count;
                                    i++
                                )
                                {
                                    FlattenedUserCards flattenedUserCards =
                                        new FlattenedUserCards();
                                    flattenedUserCards.user_id = gameDataListFinal[0]
                                        .game_users_cards[i]
                                        .user
                                        .user_id;

                                    if (
                                        gameDataListFinal[0].game_users_cards[i].user.cards.Count
                                        > 0
                                    )
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
                                                Debug.Log("RES_Check + j + " + j);
                                                Debug.Log("RES_Check + i + " + i);
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
                                    userdetails.transform.localScale = new Vector3(
                                        1.0f,
                                        1.0f,
                                        1.0f
                                    );
                                    userdetails.transform.GetChild(0).GetComponent<Text>().text =
                                        gameData.game_users[i].name;
                                    userdetails.transform.GetChild(2).GetComponent<Text>().text =
                                        gameData.game_users[i].score;
                                    userdetails.transform.GetChild(3).GetComponent<Text>().text =
                                        gameData.game_users[i].win;
                                    userdetails.transform.GetChild(4).GetComponent<Text>().text =
                                        gameData.game_users[i].total;

                                    if (
                                        gameDataListFinal[0].game_users[i].user_id
                                        == gameDataListFinal[0].winner_user_id
                                    )
                                        userdetails
                                            .transform.GetChild(1)
                                            .GetComponent<Text>()
                                            .text = "Winner";
                                    else
                                        userdetails
                                            .transform.GetChild(1)
                                            .GetComponent<Text>()
                                            .text = "Dropped";

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
                                                    gameData.flattened_user_cards[i].all_cards[j]
                                                    == ""
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
                                                            gameData
                                                                .flattened_user_cards[i]
                                                                .all_cards[j] == obj.name
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
                                                                    .GetComponent<GameManager_Pool>()
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
                        }
                        else
                        {
                            panel.SetActive(true);
                            panel.transform.GetChild(7).gameObject.SetActive(false);
                            panel.transform.GetChild(8).gameObject.SetActive(true);

                            countdownText.text = "Better Luck Next Time!! You Exceeded 100 points";

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
                                            Debug.Log("RES_Check + j + " + j);
                                            Debug.Log("RES_Check + i + " + i);
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
                                userdetails.transform.GetChild(4).GetComponent<Text>().text =
                                    gameData.game_users[i].total;

                                if (
                                    gameDataListFinal[0].game_users[i].user_id
                                    == gameDataListFinal[0].winner_user_id
                                )
                                    userdetails.transform.GetChild(1).GetComponent<Text>().text =
                                        "Winner";
                                else
                                    userdetails.transform.GetChild(1).GetComponent<Text>().text =
                                        "Dropped";

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
                                                                .GetComponent<GameManager_Pool>()
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
                    }

                    if (!gamestarted && gameDataList[0].game_status == 1)
                    {
                        API_CALL_getCardList();
                        gamestarted = true;
                        waitforchaal = true;
                    }

                    string currentchaal = gameDataList[0].chaal;

                    for (int i = 0; i < profiles.Count; i++)
                    {
                        if (profiles[i].GetComponent<PoolRummyChaalSlider>().id == currentchaal)
                        {
                            profiles[i].GetComponent<PoolRummyChaalSlider>().StartChaal();
                            profiles[i].GetComponent<PoolRummyChaalSlider>().ischaal = true;
                        }
                        else
                        {
                            profiles[i].GetComponent<PoolRummyChaalSlider>().StopChaal();
                        }
                    }

                    if (currentchaal == Configuration.GetId())
                    {
                        rummymanager.GetComponent<GameManager_Pool>().ischaal = true;
                        rummymanager.GetComponent<GameManager_Pool>().getcard = true;
                        if (get_card)
                        {
                            GameObject
                                .Find("ProfilePic")
                                .transform.GetChild(0)
                                .GetComponent<PoolRummyChaalSlider>()
                                .placeholderglow.SetActive(true);
                            GameObject
                                .Find("ProfilePic")
                                .transform.GetChild(0)
                                .GetComponent<PoolRummyChaalSlider>()
                                .discardglow.SetActive(true);
                        }
                    }
                    else
                    {
                        rummymanager.GetComponent<GameManager_Pool>().ischaal = false;
                        get_card = true;
                    }

                    Debug.Log(gameDataList[0].chaal + " chaal");

                    if (rummymanager.GetComponent<GameManager_Pool>().placeholderclicked == false)
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
                                    rummymanager.GetComponent<GameManager_Pool>().discardedcard =
                                        droppedcard;
                                    //rummymanager.GetComponent<GameManager_Pool>().adddiscardedcardsfromserver(droppedcard);
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

        string formattednewcard = gameDataList[0].drop_card[0].card.ToLower();

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

        int jokerrank = rummymanager
            .GetComponent<GameManager_Pool>()
            .GetCardRankname(rummymanager.GetComponent<GameManager_Pool>().wildcard.name);

        int dropcardrank = rummymanager
            .GetComponent<GameManager_Pool>()
            .GetCardRankname(gameDataList[0].drop_card[0].card.ToLower());
        if (dropcardrank == jokerrank)
        {
            rummymanager.GetComponent<GameManager_Pool>().ShowJokerForDiscard(spawnedcard);
        }
    }

    public GameObject FindGameObjectByName(string nameToFind)
    {
        foreach (GameObject go in deck)
        {
            Debug.Log("RES_Check + go + " + go.name.ToLower());
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
        //if (index == -1)
        //{
        //    SceneManager.LoadScene("Main");
        //}

        for (int i = 0; i < placementdata.Count; i++)
        {
            if (placementdata[i].user_id == "0")
            {
                Debug.Log("I am null");
                profiles[i].transform.GetChild(0).gameObject.SetActive(false);
                profiles[i].transform.GetChild(1).gameObject.SetActive(true);
                profiles[i].GetComponent<PoolRummyChaalSlider>().enabled = false;
                //profiles[i].SetActive(false);
            }
            else
            {
                profiles[i].GetComponent<PoolRummyChaalSlider>().enabled = true;
                profiles[i].GetComponent<PoolRummyChaalSlider>().id = placementdata[i].user_id;
                profiles[i].transform.GetChild(0).gameObject.SetActive(true);
                profiles[i].transform.GetChild(1).gameObject.SetActive(false);
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
        //        profiles[i].GetComponent<PoolRummyChaalSlider>().id = activeusers[i].user_id;
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
        //            profiles[0].GetComponent<PoolRummyChaalSlider>().id = activeusers[i].user_id;
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
        //                profiles[i + 1].GetComponent<PoolRummyChaalSlider>().id = activeusers[i].user_id;
        //                profiles[i + 1].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = activeusers[i].name;
        //                profiles[i + 1].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = activeusers[i].wallet;
        //                Image img = profiles[i + 1].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        //                StartCoroutine(DownloadImage(activeusers[i].profile_pic, img, profiles[i + 1].GetComponent<ProfileDetails>()));
        //            }
        //            else
        //            {
        //                profiles[i].gameObject.SetActive(true);
        //                profiles[i].GetComponent<PoolRummyChaalSlider>().id = activeusers[i].user_id;
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

            long UsedMemory;
            UsedMemory = System.Diagnostics.Process.GetCurrentProcess().PagedMemorySize64;
            if (UsedMemory > 1073741824)
            {
                GC.Collect();
            }
            yield return null;
        }
    }

    //private void OnConnected(ConnectResponse resp)
    //{
    //    Debug.Log("RES_Value + OnConnect : " + resp.sid);
    //    ControllerDetail.IsConnection = true;
    //    connectiontext.text = "connected: " + resp.sid;
    //    API_CALL_get_table();
    //}

    private void API_CALL_get_table()
    {
        var customNamespace = Manager.GetSocket(name_space);
        // socket start game
        PointRummyTableData jsonData = new PointRummyTableData();
        try
        {
            //jsonData.user_id = "1";
            jsonData.user_id = Configuration.GetId();
            // jsonData.token = "7534b47e2ffdbb3e3a13f49cb5205e4f";
            jsonData.token = Configuration.GetToken();

            // jsonData.no_of_players = poolrummyobject.no_of_players;

            // jsonData.boot_value = poolrummyobject.boot_value;

            // jsonData.Id = poolrummyobject.Id;


            jsonData.no_of_players = Configuration.Getpoolplayer();

            jsonData.boot_value = Configuration.Getpoolboot();

            jsonData.Id = Configuration.Getpooltableid();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("get-table", jsonData);

            Debug.Log("RES_CHECK +  EMIT-get-table");
            Debug.Log("RES_VALUE" + " EMIT-get-table " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_get_drop_card()
    {
        var customNamespace = Manager.GetSocket(name_space);

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
        var customNamespace = Manager.GetSocket(name_space);
        basicID jsonData = new basicID();
        try
        {
            jsonData.user_id = Configuration.GetId();
            jsonData.token = Configuration.GetToken();
            get_card = false;
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
        var customNamespace = Manager.GetSocket(name_space);
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
        list1 = rummymanager.GetComponent<GameManager_Pool>().list1;
        list2 = rummymanager.GetComponent<GameManager_Pool>().list2;
        list3 = rummymanager.GetComponent<GameManager_Pool>().list3;
        list4 = rummymanager.GetComponent<GameManager_Pool>().list4;
        list5 = rummymanager.GetComponent<GameManager_Pool>().list5;
        list6 = rummymanager.GetComponent<GameManager_Pool>().list6;
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
                if (rummymanager.GetComponent<GameManager_Pool>().list1.Count > 0)
                {
                    Debug.Log("Res_Check + list1 + " + i);
                    foreach (GameObject obj in list1)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager_Pool>().check1 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager_Pool>().list1,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[0]
                                )
                            && rummymanager.GetComponent<GameManager_Pool>().numberofpuresequence
                                > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager_Pool>().check1 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager_Pool>().list1,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[0]
                                ) && rummymanager.GetComponent<GameManager_Pool>().isValid
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
                if (rummymanager.GetComponent<GameManager_Pool>().list2.Count > 0)
                {
                    Debug.Log("Res_Check + list2 + " + i);
                    foreach (GameObject obj in list2)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager_Pool>().check2 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager_Pool>().list2,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[1]
                                )
                            && rummymanager.GetComponent<GameManager_Pool>().numberofpuresequence
                                > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager_Pool>().check2 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager_Pool>().list2,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[1]
                                ) && rummymanager.GetComponent<GameManager_Pool>().isValid
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
                if (rummymanager.GetComponent<GameManager_Pool>().list3.Count > 0)
                {
                    Debug.Log("Res_Check + list3 + " + i);
                    foreach (GameObject obj in list3)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager_Pool>().check3 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager_Pool>().list3,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[2]
                                )
                            && rummymanager.GetComponent<GameManager_Pool>().numberofpuresequence
                                > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager_Pool>().check3 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager_Pool>().list3,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[2]
                                ) && rummymanager.GetComponent<GameManager_Pool>().isValid
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
                if (rummymanager.GetComponent<GameManager_Pool>().list4.Count > 0)
                {
                    Debug.Log("Res_Check + list4 + " + i);
                    foreach (GameObject obj in list4)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager_Pool>().check4 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager_Pool>().list4,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[3]
                                )
                            && rummymanager.GetComponent<GameManager_Pool>().numberofpuresequence
                                > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager_Pool>().check4 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager_Pool>().list4,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[3]
                                ) && rummymanager.GetComponent<GameManager_Pool>().isValid
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
                if (rummymanager.GetComponent<GameManager_Pool>().list5.Count > 0)
                {
                    Debug.Log("Res_Check + list5 + " + i);
                    foreach (GameObject obj in list5)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager_Pool>().check5 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager_Pool>().list5,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[4]
                                )
                            && rummymanager.GetComponent<GameManager_Pool>().numberofpuresequence
                                > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager_Pool>().check5 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager_Pool>().list5,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[4]
                                ) && rummymanager.GetComponent<GameManager_Pool>().isValid
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
                if (rummymanager.GetComponent<GameManager_Pool>().list6.Count > 0)
                {
                    Debug.Log("Res_Check + list6 " + i);
                    foreach (GameObject obj in list6)
                    {
                        cards.Add(obj.GetComponent<Card>().name.ToUpper());
                        Debug.Log("Res_Value + Card " + obj.GetComponent<Card>().name.ToUpper());
                    }
                    if (
                        rummymanager.GetComponent<GameManager_Pool>().check6 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .IsSequence(
                                    rummymanager.GetComponent<GameManager_Pool>().list6,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[5]
                                )
                            && rummymanager.GetComponent<GameManager_Pool>().numberofpuresequence
                                > 0
                    )
                        carddata = "5"; // Change int to string
                    else if (
                        rummymanager.GetComponent<GameManager_Pool>().check6 =
                            rummymanager
                                .GetComponent<GameManager_Pool>()
                                .AreAllCardsSameRank(
                                    rummymanager.GetComponent<GameManager_Pool>().list6,
                                    rummymanager.GetComponent<GameManager_Pool>().textcavas[5]
                                ) && rummymanager.GetComponent<GameManager_Pool>().isValid
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
        var customNamespace = Manager.GetSocket(name_space);
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
        var customNamespace = Manager.GetSocket(name_space);
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
        var customNamespace = Manager.GetSocket(name_space);
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
            Debug.Log("RES_CHECK");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void API_CALL_Wrongdeclare()
    {
        var customNamespace = Manager.GetSocket(name_space);
        GamePackData jsonObjectPackGame = new GamePackData();
        Debug.Log("RES_Check + Declare wrong ");
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

    public void API_CALL_declareBack()
    {
        var customNamespace = Manager.GetSocket(name_space);
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
        var customNamespace = Manager.GetSocket(name_space);
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
        StopAllCoroutines(); //
        Debug.Log("DisConnect : ");
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
