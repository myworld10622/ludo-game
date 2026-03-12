using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using AndroApps;
//using UnityEngineInternal;
//using UnityEngine.SocialPlatforms.Impl;
//using UnityEngine.SocialPlatforms;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyUI.Toast;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using UnityEngine.XR;

public class TeenPattiSocketManager : MonoBehaviour
{
    [Header("Controller details")]
    public string name_space = "/teenpatti";

    [Header("Table Details")]
    public string TableID;

    [Header("Game Details")]
    // public string GameID;
    private SocketManager Manager;

    //private SocketIOUnity socket;

    public TeenPattiData TPData;
    public ApiResponse response;
    public List<TPUserData> activeusers;
    public List<string> imageid;
    private int num = 0;
    public List<GameObject> profiles;
    public TPGameResponse gameresponse;
    public TPGameResponseData TPresponseData;
    public string amountontable;
    public List<GameObject> active = new List<GameObject>();
    public bool start;
    public GameObject canvas;
    public Animator canvasanim;
    public int value;
    public List<string> mycards;
    public List<Sprite> allcards;
    public GameObject myself;
    public string gameID;
    public Text amounttext;
    public bool mention = false;
    public TextMeshProUGUI useramount;
    public float UserAmount;
    public float UserAmout2;
    public bool added = false,
        amountshown = false;
    public GameObject cardsanim;
    public string previousid;
    public GameObject show,
        slideshow;
    public List<string> IDtoplay;
    public Animator anim;
    public int currentprofilecount = 0;
    public GameObject slideshowcanvas;
    public List<GameObject> waitingusers;
    public List<GameObject> waitingusers2;
    public List<GameObject> playingusers;
    public bool clickedpack = false,
        seenbool = false,
        slideShowBool = false,
        seecardanimbool = false;
    public GameObject chaalbtn,
        blindbtn,
        chaal2x,
        blind2x;
    public TextMeshProUGUI newamounttext;
    public GameObject showfrom,
        showto;
    public int showcount;
    private string showcanvasid,
        showcardid;
    public bool shownamount;
    public TextMeshProUGUI waittext;
    public List<Sprite> waitimages;
    public GameObject waittextanim;
    public List<GameObject> coinlisttodestroy;
    public bool gameover,
        gamestart,
        amountcaptured;
    public Sprite maincard;
    public List<TPUserData> placementdata;
    public Image knob;
    public VibrationController vibrator;
    public bool donevibrate;
    public float amount;
    public string numtext;
    public GameObject Reconn_Canvas;
    public GameObject chaaltextanim,
        chaal2xtextanim,
        blindtextanim,
        blind2xtextanim;
    public AudioSource loosesound,
        winsound;
    public bool firstbetcoin;
    public TextMeshProUGUI sockettimertext;
    public int chaaltimer;
    public TextMeshProUGUI info;
    public Text boot,
        pot,
        max;
    public GameObject infopanel;
    public Text maintabletext;
    public GameObject Wallet_paenl;
    public TextMeshProUGUI tableID;
    public Image waitimage;

    [Header("Show")]
    public Image myplayerimage;
    public RectTransform mine;
    public float moveSpeed = 5000f;
    public List<GameObject> mineobjs;
    public List<GameObject> oppobjs;
    public Animator vs;
    public GameObject myanimobj,
        oppanimobj;
    public RectTransform opponent;
    public Image mycard1;
    public Image mycard2;
    public Image mycard3;
    public TextMeshProUGUI myplayername;
    public Image opponentplayerimage;
    public Image opponentcard1;
    public Image opponentcard2;
    public Image opponentcard3;
    public TextMeshProUGUI opponentplayername;
    public GameObject showpanel;
    public GameObject winnerprofile;
    public bool showblind;
    public bool startinganim;

    public List<string> evaluatecards = new List<string>();
    public List<int> mycardvalues = new List<int>();
    public List<string> mycardsuits = new List<string>();
    public TextMeshProUGUI typetext;
    public int role;

    public List<Sprite> movesprite;
    public string player_Move;
    public bool showmove;
    public string user_type;
    public GameObject coin;
    public List<GameObject> coinslist;

    #region music and sounds

    public Button[] buttons;
    public string moveid;

    #endregion

    public void add()
    {
        value = 1;
    }

    public void substract()
    {
        value = 0;
    }

    private void OnEnable()
    {
        // DontDestroyOnLoad(mine.gameObject);
        showblind = true;
        seenbool = false;
        if (PlayerPrefs.HasKey("table_id"))
        {
            tableID.text = "Table ID: " + Configuration.GetTableID();
        }
        profiles[0].transform.GetChild(0).gameObject.SetActive(true);
        profiles[0].transform.GetChild(1).gameObject.SetActive(false);
        //profiles[0].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<TextMeshProUGUI>().text = Configuration.GetName();

        string walletString = Configuration.GetWallet();
        if (decimal.TryParse(walletString, out decimal userCoins))
        {
            if (userCoins > 10000)
            {
                Wallet_paenl.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                    FormatNumber(Configuration.GetWallet());
            }
            else
            {
                Wallet_paenl.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                    Configuration.GetWallet();
            }
        }

        //profiles[0].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(Configuration.GetWallet());
        Image img2 = profiles[0]
            .transform.GetChild(0)
            .GetChild(1)
            .GetChild(0)
            .GetComponent<Image>();

        img2.sprite = SpriteManager.Instance.profile_image;
        // StartCoroutine(
        //     DownloadImage(
        //         Configuration.GetProfilePic(),
        //         img2,
        //         profiles[0].transform.GetChild(0).GetChild(1).GetComponent<Image>(),
        //         profiles[0]
        //     )
        // );
        var url = Configuration.BaseSocketUrl;
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(name_space);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("get-table", OnGetTableResponse);
        customNamespace.On<string>("start-game", OnStartGameResponse);
        customNamespace.On<string>("trigger", OnTriggerResponse);
        customNamespace.On<string>("chaal", OnChaalResponse);
        customNamespace.On<string>("see-card", OnSeeCardResponse);
        customNamespace.On<string>("pack-game", OnPackResponse);
        customNamespace.On<string>("slide-show", OnSlideResponse);
        customNamespace.On<string>("do-slide-show", OnSlideEmitResponse);
        customNamespace.On<string>("timer", OnTimerResponse);
        customNamespace.On<string>("leave-table", OnLeaveTableResponse);
        Manager.Open();

        for (int i = 0; i < profiles.Count; i++)
        {
            profiles[i].transform.GetChild(2).gameObject.SetActive(false);
        }
    }

    public Toggle musicToggle;
    public Toggle soundToggle;

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

    #region Leave Table

    private void OnLeaveTableResponse(string args)
    {
        Debug.Log("RES_Value+ Json onleavetable :" + args);
        try
        {

            this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
            Manager.Close();
            //this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
            return;
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }
    #endregion
    void OnConnected(ConnectResponse resp)
    {
        GetTable();
        // if (PlayerPrefs.HasKey("create") || PlayerPrefs.HasKey("join"))
        // {
        //     if (PlayerPrefs.HasKey("create"))
        //     {
        //         GetTable();
        //     }
        //     else if (PlayerPrefs.HasKey("join"))
        //     {
        //         TableID = Configuration.GetTableID();
        //         API_CALL_status();
        //         StartGame();
        //     }
        // }
        // else
        // {
        //     GetTable();
        // }
    }

    private void OnTimerResponse(string args)
    {
        //Debug.Log("socket timer Json :" + args);
        sockettimertext.text = "socket timer: " + args;
        chaaltimer = int.Parse(args);
        //for (int i = 0; i < maskprofile.Count; i++)
        //{
        //    maskprofile[i].sprite = maskimage;
        //}
    }

    private void OnDisconnected()
    {
        Reconn_Canvas.SetActive(true);
    }

    public void back()
    {
        StartCoroutine(disconn());
    }

    private void OnSlideEmitResponse(string args)
    {
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnSlideResponse(string args)
    {
        slideShowBool = false;
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnPackResponse(string args)
    {
        //StartCoroutine(restartgame());
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    [Serializable]
    public class CardData
    {
        public string card1;
        public string card2;
        public string card3;
    }

    [Serializable]
    public class SeeJsonResponse
    {
        public string message;
        public List<CardData> cards;
        public List<int> CardValue;
        public int code;
    }

    private void OnSeeCardResponse(string args)
    {
        Debug.Log("RES_Check + See_Card Json :" + args);
        API_CALL_status();
        try
        {
            // SeeJsonResponse gameresponse = JsonUtility.FromJson<SeeJsonResponse>(args);
            // StartCoroutine(wait_See(gameresponse.cards[0].card1, gameresponse.cards[0].card2, gameresponse.cards[0].card3));
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void OnChaalResponse(string args)
    {
        Debug.Log("RES_Value + On_Chaal Json :" + args);
        //try
        //{
        //    gameresponse = JsonUtility.FromJson<GameResponse>(args);
        //}
        //catch (System.Exception ex)
        //{
        //    Debug.LogError(ex.ToString());
        //}
    }

    private void OnStartGameResponse(string args)
    {
        Debug.Log("RES_Value + Start Game Json :" + args);
        waittext.gameObject.SetActive(false);
        gameresponse = JsonUtility.FromJson<TPGameResponse>(args);
        if (gameresponse.code == 409)
        {
            Debug.Log("API_CALL_status game_id" + gameresponse.game_id);
            gameID = gameresponse.game_id.ToString();
            API_CALL_status();
        }
        else if (gameresponse.code == 406)
        {
            CALL_Leave_table();
        }
        else
        {
            showtoastmessage(gameresponse.message);
        }

        //showtoastmessage(gameresponse.message);
        //try
        //{
        //    gameresponse = JsonUtility.FromJson<GameResponse>(args);
        //}
        //catch (System.Exception ex)
        //{
        //    Debug.LogError(ex.ToString());
        //}
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
                tableID.text = "Table ID: " + response.table_data[0].table_id;
                StartCoroutine(startgamedelay());
            }
            //for (int i = 0; i < maskprofile.Count; i++)
            //{
            //    maskprofile[i].sprite = maskimage;
            //}
            //showTableUsers();
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

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
        //     return number.ToString();
        // }
    }

    IEnumerator startgamedelay()
    {
        yield return new WaitForSeconds(2);
        // waittextanim.SetActive(false);
        // waittext.gameObject.SetActive(true);
        // waittext.text = "5";
        if (Configuration.GetSound() == "on")
        {
            waittext.GetComponent<AudioSource>().volume = 1f;
        }
        else
        {
            waittext.GetComponent<AudioSource>().volume = 0f;
        }

        // waittext.GetComponent<AudioSource>().Play();
        // yield return new WaitForSeconds(1);
        // waittext.text = "4";
        // waittext.GetComponent<AudioSource>().Play();
        // yield return new WaitForSeconds(1);
        // waittext.text = "3";
        // waittext.GetComponent<AudioSource>().Play();
        // yield return new WaitForSeconds(1);
        // waittext.text = "2";
        // waittext.GetComponent<AudioSource>().Play();
        // yield return new WaitForSeconds(1);
        // waittext.text = "1";
        // waittext.GetComponent<AudioSource>().Play();
        // yield return new WaitForSeconds(0.3f);
        // waittext.GetComponent<AudioSource>().Play();
        // yield return new WaitForSeconds(0.7f);
        waitimage.gameObject.SetActive(true);
        yield return new WaitForSeconds(5);
        waitimage.gameObject.SetActive(false);
        StartGame();
    }

    IEnumerator restartgamedelay()
    {
        if (gameover)
        {
            user_type = "0";
            for (int i = 0; i < coinslist.Count; i++)
            {
                Destroy(coinslist[i]);
            }
            coinslist.Clear();
            moveid = string.Empty;
            typetext.transform.parent.gameObject.SetActive(false);
            cardsanim.SetActive(false);
            startinganim = false;
            anim.enabled = false;
            amounttext.text = "0.0";
            gameover = false;
            firstbetcoin = false;
            gamestart = false;

            foreach (GameObject obj in profiles)
            {
                obj.GetComponent<ChaalSlider>().check = true;
                obj.transform.GetChild(2).gameObject.SetActive(false);
                obj.transform.GetChild(0).GetChild(2).gameObject.SetActive(false);
                obj.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                obj.transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
                obj.transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
                obj.transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
                obj.transform.GetChild(1).GetChild(0).GetChild(0).gameObject.SetActive(false);
                obj.transform.GetChild(1).GetChild(1).GetChild(0).gameObject.SetActive(false);
                obj.transform.GetChild(1).GetChild(2).GetChild(0).gameObject.SetActive(false);
                obj
                    .transform.GetChild(1)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = maincard;
                obj
                    .transform.GetChild(1)
                    .GetChild(1)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = maincard;
                obj
                    .transform.GetChild(1)
                    .GetChild(2)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = maincard;
                if (obj.GetComponent<ChaalSlider>().id == Configuration.GetId())
                {
                    obj.transform.GetChild(0).GetChild(2).gameObject.SetActive(false);
                    obj.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                    obj.transform.GetChild(0).GetChild(7).gameObject.SetActive(false);
                }
            }
            for (int j = 0; j < profiles.Count; j++)
            {
                profiles[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
                profiles[j]
                    .transform.GetChild(0)
                    .GetChild(1)
                    .GetChild(0)
                    .GetComponent<Image>()
                    .color = Color.white;
                profiles[j]
                    .transform.GetChild(0)
                    .GetChild(1)
                    .GetChild(1)
                    .GetComponent<SpriteRenderer>()
                    .color = Color.white;
            }
            profiles[0].transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
            profiles[0].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>().color =
                Color.white;
            profiles[0]
                .transform.GetChild(0)
                .GetChild(1)
                .GetChild(1)
                .GetComponent<SpriteRenderer>()
                .color = Color.white;
            showblind = true;
            IDtoplay.Clear();
            GetCoin coin = GameObject.Find("pngegg (1)").GetComponent<GetCoin>();
            for (int i = 0; i < coin.coinstodelete.Count; i++)
            {
                coin.coinstodelete[i]
                    .transform.SetParent(winnerprofile.transform.GetChild(0).GetChild(1));
                coin.coinstodelete[i]
                    .transform.DOLocalMove(
                        winnerprofile.transform.GetChild(0).GetChild(1).position,
                        0.9f
                    );
                yield return new WaitForSeconds(0.1f);
            }
            yield return new WaitForSeconds(0.8f);
            for (int i = 0; i < coin.coinstodelete.Count; i++)
            {
                Destroy(coin.coinstodelete[i]);
            }
            coin.coinstodelete.Clear();
            clickedpack = false;
            yield return new WaitForSeconds(1);
            waittext.gameObject.SetActive(true);

            if (Configuration.GetSound() == "on")
            {
                waittext.GetComponent<AudioSource>().volume = 1f;
            }
            else
            {
                waittext.GetComponent<AudioSource>().volume = 0f;
            }

            waittext.transform.GetChild(1).GetChild(1).GetComponent<Image>().sprite = waitimages[0];
            waittext.GetComponent<AudioSource>().Play();
            yield return new WaitForSeconds(1);
            waittext.transform.GetChild(1).GetChild(1).GetComponent<Image>().sprite = waitimages[1];
            waittext.GetComponent<AudioSource>().Play();
            yield return new WaitForSeconds(1);
            waittext.transform.GetChild(1).GetChild(1).GetComponent<Image>().sprite = waitimages[2];
            waittext.GetComponent<AudioSource>().Play();
            yield return new WaitForSeconds(1);
            waittext.transform.GetChild(1).GetChild(1).GetComponent<Image>().sprite = waitimages[3];
            waittext.GetComponent<AudioSource>().Play();
            yield return new WaitForSeconds(1);
            waittext.transform.GetChild(1).GetChild(1).GetComponent<Image>().sprite = waitimages[4];
            waittext.GetComponent<AudioSource>().Play();
            yield return new WaitForSeconds(0.3f);
            waittext.GetComponent<AudioSource>().Play();
            yield return new WaitForSeconds(0.7f);
            seenbool = false;
            showto = null;
            showfrom = null;
            foreach (GameObject obj in profiles)
            {
                if (obj.GetComponent<ChaalSlider>().id == Configuration.GetId())
                {
                    obj.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                }
            }

            StartGame();
        }
    }

    IEnumerator destroycoin(GameObject ob)
    {
        yield return new WaitForSeconds(2);
        Destroy(ob);
    }

    private void OnTriggerResponse(string args)
    {
        Debug.Log("RES_Check + On_trigger");
        // Debug.Log("RES_Value + trigger Json :" + args);
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

    public void showmoveanim(GameObject player)
    {
        int move = 0;

        if (TPresponseData.game_log[0].plus == "0" && TPresponseData.game_log[0].seen == "0")
        {
            move = 0;
        }
        else if (TPresponseData.game_log[0].plus == "1" && TPresponseData.game_log[0].seen == "0")
        {
            move = 1;
        }
        else if (TPresponseData.game_log[0].plus == "0" && TPresponseData.game_log[0].seen == "1")
        {
            move = 2;
        }
        else if (TPresponseData.game_log[0].plus == "1" && TPresponseData.game_log[0].seen == "1")
        {
            move = 3;
        }
        StartCoroutine(closemoveanim(player, move));
    }

    public IEnumerator closemoveanim(GameObject player, int move)
    {
        if (player.GetComponent<ChaalSlider>().id == Configuration.GetId())
        {
            player.transform.GetChild(0).GetChild(10).GetComponent<Animator>().enabled = true;
            Vector3 trans = player.transform.GetChild(0).GetChild(10).transform.localPosition;
            player.transform.GetChild(0).GetChild(10).GetComponent<Image>().sprite = movesprite[
                move
            ];
            player.transform.GetChild(0).GetChild(10).gameObject.SetActive(true);
            yield return new WaitForSeconds(2);
            player.transform.GetChild(0).GetChild(10).gameObject.transform.localPosition = trans;
            player.transform.GetChild(0).GetChild(10).gameObject.SetActive(false);
        }
        else
        {
            player.transform.GetChild(0).GetChild(8).GetComponent<Animator>().enabled = true;
            Vector3 trans = player.transform.GetChild(0).GetChild(8).transform.localPosition;
            player.transform.GetChild(0).GetChild(8).GetComponent<Image>().sprite = movesprite[
                move
            ];
            player.transform.GetChild(0).GetChild(8).gameObject.SetActive(true);
            yield return new WaitForSeconds(2);
            player.transform.GetChild(0).GetChild(8).gameObject.transform.localPosition = trans;
            player.transform.GetChild(0).GetChild(8).gameObject.SetActive(false);
        }
    }

    public void API_CALL_status()
    {
        StartCoroutine(Request());
    }

    public void UpdateUI()
    {
        GameUsers();
        waitingusers.Clear();
        playingusers.Clear();
        waitingusers2.Clear();
        Debug.Log("Waiting Clean");

        foreach (GameObject obj in profiles)
        {
            if (obj.activeSelf)
            {
                waitingusers.Add(obj);
                waitingusers2.Add(obj);
                Debug.Log(obj.GetComponent<ChaalSlider>().id + " Waiting Users ID");
            }
        }

        Debug.Log(waitingusers.Count + " Waiting Users ID count");

        for (int i = 0; i < waitingusers2.Count; i++)
        {
            Debug.Log(waitingusers[i].GetComponent<ChaalSlider>().id + " waiting IDS before clean");
            for (int j = 0; j < TPresponseData.game_users.Count; j++)
            {
                Debug.Log(TPresponseData.game_users[j].user_id + " Game IDS before clean");
                if (
                    waitingusers2[i].GetComponent<ChaalSlider>().id
                    == TPresponseData.game_users[j].user_id
                )
                {
                    playingusers.Add(waitingusers2[i]);
                    //Debug.Log(waitingusers[i].GetComponent<ChaalSlider>().id + " after clean");
                }
            }
        }

        HashSet<GameObject> waitlist = new HashSet<GameObject>(playingusers);

        for (int i = waitingusers.Count - 1; i >= 0; i--)
        {
            GameObject gameObjectA = waitingusers[i];

            if (waitlist.Contains(gameObjectA))
            {
                waitingusers.RemoveAt(i);
            }
        }

        foreach (GameObject obj in waitingusers)
        {
            obj.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
            obj.transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
            obj.transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
            obj.transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
            if (obj.GetComponent<ChaalSlider>().id == Configuration.GetId())
                obj.transform.GetChild(0).GetChild(7).gameObject.SetActive(true);
            else
                obj.transform.GetChild(0).GetChild(6).gameObject.SetActive(true);
        }

        foreach (GameObject obj in playingusers)
        {
            obj.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
            obj.transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
            obj.transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
            obj.transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
            if (obj.GetComponent<ChaalSlider>().id == Configuration.GetId())
            {
                obj.transform.GetChild(0).GetChild(3).gameObject.SetActive(true);
                obj.transform.GetChild(0).GetChild(7).gameObject.SetActive(false);
            }
        }

        showpack();
        showsee();

        #region previous
        //for (int i = 0; i < TPresponseData.game_users.Count; i++)
        //{
        //    for (int j = 0; j < playingusers.Count; j++)
        //    {
        //        if (playingusers[j].GetComponent<ChaalSlider>().id == TPresponseData.game_users[i].user_id)
        //        {
        //            if (TPresponseData.game_users[i].packed == "1")
        //                playingusers[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(true);
        //            else
        //                playingusers[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
        //            if (TPresponseData.game_users[i].seen == "0")
        //            {
        //                playingusers[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(true);
        //                playingusers[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
        //            }
        //            else
        //            {
        //                playingusers[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
        //                playingusers[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(true);
        //            }
        //        }

        //        if (playingusers[j].GetComponent<ChaalSlider>().id == Configuration.GetId())
        //        {
        //            playingusers[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
        //            playingusers[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
        //            playingusers[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
        //            playingusers[j].transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
        //            playingusers[j].transform.GetChild(0).GetChild(7).gameObject.SetActive(false);
        //            if (!seenbool)
        //                playingusers[j].transform.GetChild(0).GetChild(7).gameObject.SetActive(true);
        //            if (TPresponseData.game_users[i].packed == "1")
        //                playingusers[j].transform.GetChild(0).GetChild(6).gameObject.SetActive(true);
        //            else
        //                playingusers[j].transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
        //        }
        //    }
        //}
        #endregion
    }

    public void showpack()
    {
        for (int i = 0; i < TPresponseData.game_users.Count; i++)
        {
            for (int j = 0; j < profiles.Count; j++)
            {
                if (
                    profiles[j].GetComponent<ChaalSlider>().id
                    == TPresponseData.game_users[i].user_id
                )
                {
                    if (TPresponseData.game_users[i].packed == "1")
                    {
                        profiles[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(true);
                        profiles[j]
                            .transform.GetChild(0)
                            .GetChild(1)
                            .GetChild(0)
                            .GetComponent<Image>()
                            .color = Color.gray;
                        profiles[j]
                            .transform.GetChild(0)
                            .GetChild(1)
                            .GetChild(1)
                            .GetComponent<SpriteRenderer>()
                            .color = Color.gray;
                    }
                    else
                    {
                        profiles[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
                        profiles[j]
                            .transform.GetChild(0)
                            .GetChild(1)
                            .GetChild(0)
                            .GetComponent<Image>()
                            .color = Color.white;
                        profiles[j]
                            .transform.GetChild(0)
                            .GetChild(1)
                            .GetChild(1)
                            .GetComponent<SpriteRenderer>()
                            .color = Color.white;
                    }
                }
            }
        }

        for (int i = 0; i < TPresponseData.game_users.Count; i++)
        {
            if (profiles[0].GetComponent<ChaalSlider>().id == TPresponseData.game_users[i].user_id)
            {
                profiles[0].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                profiles[0].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
                profiles[0].transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
                if (TPresponseData.game_users[i].packed == "1")
                {
                    profiles[0].transform.GetChild(0).GetChild(6).gameObject.SetActive(true);
                    profiles[0]
                        .transform.GetChild(0)
                        .GetChild(1)
                        .GetChild(0)
                        .GetComponent<Image>()
                        .color = Color.gray;
                    profiles[0]
                        .transform.GetChild(0)
                        .GetChild(1)
                        .GetChild(1)
                        .GetComponent<SpriteRenderer>()
                        .color = Color.gray;
                }
                else
                {
                    profiles[0].transform.GetChild(0).GetChild(6).gameObject.SetActive(false);
                    profiles[0]
                        .transform.GetChild(0)
                        .GetChild(1)
                        .GetChild(0)
                        .GetComponent<Image>()
                        .color = Color.white;
                    profiles[0]
                        .transform.GetChild(0)
                        .GetChild(1)
                        .GetChild(1)
                        .GetComponent<SpriteRenderer>()
                        .color = Color.white;
                }
            }
        }
    }

    public void showsee()
    {
        for (int i = 0; i < TPresponseData.game_users.Count; i++)
        {
            for (int j = 0; j < profiles.Count; j++)
            {
                if (
                    profiles[j].GetComponent<ChaalSlider>().id
                    == TPresponseData.game_users[i].user_id
                )
                {
                    if (TPresponseData.game_users[i].seen == "0")
                    {
                        if (showblind)
                        {
                            profiles[j]
                                .transform.GetChild(0)
                                .GetChild(3)
                                .gameObject.SetActive(true);
                            profiles[j]
                                .transform.GetChild(0)
                                .GetChild(4)
                                .gameObject.SetActive(false);
                        }
                    }
                    else
                    {
                        profiles[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                        profiles[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(true);
                    }
                }
            }
        }

        for (int j = 0; j < profiles.Count; j++)
        {
            if (profiles[j].GetComponent<ChaalSlider>().id == Configuration.GetId())
            {
                profiles[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                profiles[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
                profiles[j].transform.GetChild(0).GetChild(5).gameObject.SetActive(false);
                if (!seenbool && showblind == true)
                {
                    Debug.Log("RES_Check + showblind");
                    profiles[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(true);
                    profiles[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(false);
                }
                else
                {
                    profiles[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                    profiles[j].transform.GetChild(0).GetChild(4).gameObject.SetActive(true);
                    Debug.Log("RES_Check + Dont showblind");
                }
            }
        }
    }

    public void showfromtocard()
    {
        if (TPresponseData.slide_show_from_cards.Count > 0)
        {
            for (int i = 0; i < playingusers.Count; i++)
            {
                if (
                    TPresponseData.slide_show_from_cards[0].user_id
                    == playingusers[i].GetComponent<ChaalSlider>().id
                )
                {
                    showfrom = playingusers[i];
                }
            }
        }

        if (TPresponseData.slide_show_to_cards.Count > 0)
        {
            for (int i = 0; i < playingusers.Count; i++)
            {
                if (
                    TPresponseData.slide_show_to_cards[0].user_id
                    == playingusers[i].GetComponent<ChaalSlider>().id
                )
                {
                    showto = playingusers[i];
                }
            }
        }

        if (showto != null && showfrom != null)
        {
            if (showcardid != TPresponseData.slide_show[TPresponseData.slide_show.Count - 1].id)
            {
                if (
                    showto.GetComponent<ChaalSlider>().id == Configuration.GetId()
                    || showfrom.GetComponent<ChaalSlider>().id == Configuration.GetId()
                )
                {
                    myself.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);

                    seenbool = true;

                    foreach (Sprite name in allcards)
                    {
                        if (TPresponseData.slide_show_to_cards[0].card1.ToLower() == name.name)
                        {
                            showto
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetChild(0)
                                .GetComponent<SpriteRenderer>()
                                .sprite = name;
                        }
                    }
                    foreach (Sprite name in allcards)
                    {
                        if (TPresponseData.slide_show_to_cards[0].card2.ToLower() == name.name)
                        {
                            showto
                                .transform.GetChild(1)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<SpriteRenderer>()
                                .sprite = name;
                        }
                    }
                    foreach (Sprite name in allcards)
                    {
                        if (TPresponseData.slide_show_to_cards[0].card3.ToLower() == name.name)
                        {
                            showto
                                .transform.GetChild(1)
                                .GetChild(2)
                                .GetChild(0)
                                .GetComponent<SpriteRenderer>()
                                .sprite = name;
                        }
                    }

                    foreach (Sprite name in allcards)
                    {
                        if (TPresponseData.slide_show_from_cards[0].card1.ToLower() == name.name)
                        {
                            showfrom
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetChild(0)
                                .GetComponent<SpriteRenderer>()
                                .sprite = name;
                        }
                    }
                    foreach (Sprite name in allcards)
                    {
                        if (TPresponseData.slide_show_from_cards[0].card2.ToLower() == name.name)
                        {
                            showfrom
                                .transform.GetChild(1)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<SpriteRenderer>()
                                .sprite = name;
                        }
                    }
                    foreach (Sprite name in allcards)
                    {
                        if (TPresponseData.slide_show_from_cards[0].card3.ToLower() == name.name)
                        {
                            showfrom
                                .transform.GetChild(1)
                                .GetChild(2)
                                .GetChild(0)
                                .GetComponent<SpriteRenderer>()
                                .sprite = name;
                        }
                    }

                    showcardid = TPresponseData.slide_show[TPresponseData.slide_show.Count - 1].id;

                    showto = null;
                    showfrom = null;
                }
            }
        }
    }

    public void Blind2x()
    {
        amount = amount * 2;
    }

    public void Chaal2x()
    {
        amount = amount * 2;
    }

    public void showandsideshowbtn()
    {
        //if (!profiles[0].GetComponent<ChaalSlider>().stopupdategameamount)
        //    amounttext.text = TPresponseData.game_amount;
        for (int i = 0; i < TPresponseData.game_users.Count; i++)
        {
            if (TPresponseData.game_users[i].user_id == Configuration.GetId())
            {
                if (TPresponseData.game_users[i].seen == "0")
                {
                    float num = float.Parse(TPresponseData.table_amount);
                    amount = num;
                    blindbtn.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = num + "";
                    blind2x.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        num * 2 + "";
                    chaalbtn.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = num + "";
                    chaal2x.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        num * 2 + "";
                    chaalbtn.SetActive(false);
                    chaal2x.SetActive(false);
                    blindbtn.SetActive(true);
                    blind2x.SetActive(true);
                }
                else
                {
                    float num = float.Parse(TPresponseData.table_amount);
                    blindbtn.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = num + "";
                    blind2x.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        num * 2 + "";
                    chaalbtn.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = num + "";
                    chaal2x.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        num * 2 + "";
                    amount = num;
                    chaalbtn.SetActive(true);
                    chaal2x.SetActive(true);
                    blindbtn.SetActive(false);
                    blindbtn.SetActive(false);
                }
            }
        }

        if (TPresponseData.slide_show.Count != 0 && !slideShowBool)
        {
            if (TPresponseData.slide_show[0].prev_id == Configuration.GetId())
            {
                slideshowcanvas.SetActive(true);
                slideShowBool = true;
                showcanvasid = TPresponseData.slide_show[0].prev_id;
            }
        }

        if (TPresponseData.game_users.Count > 2)
        {
            showcount = 0;
            for (int i = 0; i < TPresponseData.game_users.Count; i++)
            {
                if (TPresponseData.game_users[i].packed == "0")
                    showcount++;
            }
            if (showcount > 2)
            {
                slideshow.SetActive(true);
                show.SetActive(false);
            }
            else
            {
                slideshow.SetActive(false);
                show.SetActive(true);
            }
        }
        else
        {
            show.SetActive(true);
            slideshow.SetActive(false);
        }
    }

    public void ChaalAnim()
    {
        Debug.Log("RES_Check + amount " + TPresponseData.game_log[0].amount);
        if (moveid != TPresponseData.game_log[0].user_id)
        {
            for (int i = 0; i < profiles.Count; i++)
            {
                if (
                    profiles[i].GetComponent<ChaalSlider>().id == TPresponseData.game_log[0].user_id
                )
                {
                    moveid = TPresponseData.game_log[0].user_id;
                    Debug.Log("RES_Check + Coin Spawned " + TPresponseData.game_log[0].user_id);
                    Transform endpos = GameObject
                        .Find("pngegg (1)")
                        .GetComponent<GetCoin>()
                        .coin()
                        .transform;
                    GameObject go = Instantiate(coin, profiles[i].gameObject.transform);
                    AudioManager._instance.PlayCoinDrop();
                    coinslist.Add(go);
                    go.transform.localPosition = new Vector3(0, 0, 0);
                    go.transform.localScale = new Vector3(0f, 0f, 0f);
                    go.GetComponent<CoinAmount>().coinamount.text = TPresponseData
                        .game_log[0]
                        .amount;
                    go.transform.DOMove(endpos.position, 0.5f).SetEase(Ease.Linear);
                    if (TPresponseData.game_log[0].user_id == Configuration.GetId())
                    {
                        go.transform.DOScale(new Vector3(0.03f, 0.03f, 0.03f), 0.5f)
                            .SetEase(Ease.Linear);
                    }
                    else
                    {
                        go.transform.DOScale(new Vector3(0.04f, 0.04f, 0.04f), 0.5f)
                            .SetEase(Ease.Linear);
                    }
                    //profiles[i].GetComponent<ChaalSlider>().chaalanim();
                    if (showmove)
                    {
                        showmove = false;
                        showmoveanim(profiles[i]);
                    }
                }
            }
            int order = 0;
            for (int i = 0; i < coinslist.Count; i++)
            {
                coinslist[i].transform.GetChild(0).GetComponent<SpriteRenderer>().sortingOrder =
                    order;
                order++;
                coinslist[i]
                    .transform.GetChild(0)
                    .GetChild(0)
                    .GetComponent<TextMeshPro>()
                    .sortingOrder = order;
                order++;
            }
        }
    }

    void Update()
    {
        if (gameover)
        {
            profiles[0].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
        }
    }

    IEnumerator Request()
    {
        string url = Configuration.GameTeenPattiStatus;
        WWWForm form = new WWWForm();
        Debug.Log("RES_Check + Table ID " + TableID);
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
                gameID = TPresponseData.active_game_id;
                showTableUsers();
                info.text =
                    "#"
                    + TPresponseData.active_game_id
                    + "    "
                    + " Teen Patti    "
                    + TPresponseData.table_detail.boot_value;
                //tableID.text = "Table ID: " + response.table_data[0].table_id + " Amount: " + TPresponseData.table_detail.boot_value;
                boot.text = "1. Boot Value - " + TPresponseData.table_detail.boot_value;
                pot.text = "2. Pot Value - " + TPresponseData.table_detail.pot_limit;
                max.text = "3. Max Blind - " + TPresponseData.table_detail.maximum_blind;

                if (!amountcaptured)
                {
                    amountontable = TPresponseData.game_amount;
                    amountcaptured = true;
                }
                if (TPresponseData.game_log.Count <= 0)
                    yield break;

                if (player_Move == TPresponseData.game_log[0].user_id)
                {
                    showmove = false;
                }
                else
                {
                    showmove = true;
                }

                player_Move = TPresponseData.game_log[0].user_id;

                //InternetCheck.Instance.checkinvalid(TPresponseData.code);

                showandsideshowbtn();

                // showTableUsers();

                if (TPresponseData.game_users.Count != 0)
                    UpdateUI();

                // && Configuration.GetId() != TPresponseData.game_log[0].user_id
                if (TPresponseData.game_log.Count != 0 && startinganim)
                {
                    Debug.Log("RES_Check + Coin Spawned Called");
                    ChaalAnim();
                }

                if (TPresponseData.game_status == 1 && !startinganim)
                {
                    for (int i = 0; i < coinslist.Count; i++)
                    {
                        Destroy(coinslist[i]);
                    }

                    Debug.Log("RES_Check + Game started");
                    cardsanim.SetActive(true);
                    StartCoroutine(cardsanim.GetComponent<PattiAnimScript>().MoveAllCards());
                    gamestart = true;
                    firstbetcoin = true;
                    startinganim = true;
                }

                if (TPresponseData.game_status == 2)
                {
                    StartCoroutine(gamestatus2());
                }
                gameID = TPresponseData.active_game_id;
                if (!gameover)
                {
                    showfromtocard();

                    //if (TPresponseData.active_game_id == "0")
                    //{
                    //    yield return new WaitForSeconds(3);
                    //    StartGame();
                    //}

                    maintabletext.text = TPresponseData.game_amount;

                    if (TPresponseData.game_status == 1 && !start)
                    {
                        foreach (GameObject obj in active)
                        {
                            for (int i = 0; i < obj.transform.GetChild(1).childCount; i++)
                            {
                                obj.transform.GetChild(1).GetChild(i).gameObject.SetActive(true);
                            }
                        }
                        start = true;
                    }

                    string currentchaal = TPresponseData.chaal;

                    for (int i = 0; i < profiles.Count; i++)
                    {
                        for (int j = 0; j < TPresponseData.all_users.Count; j++)
                        {
                            if (
                                profiles[i].GetComponent<ChaalSlider>().id
                                == TPresponseData.all_users[j].user_id
                            )
                            {
                                if (i == 0)
                                    Wallet_paenl
                                        .transform.GetChild(0)
                                        .GetComponent<TextMeshProUGUI>()
                                        .text =
                                        FormatNumber(TPresponseData.all_users[j].wallet) + "";
                                else
                                    profiles[i]
                                        .transform.GetChild(0)
                                        .GetChild(0)
                                        .GetChild(0)
                                        .GetComponent<TextMeshProUGUI>()
                                        .text =
                                        FormatNumber(TPresponseData.all_users[j].wallet) + "";
                            }
                        }
                    }

                    for (int i = 0; i < profiles.Count; i++)
                    {
                        if (profiles[i].GetComponent<ChaalSlider>().id == currentchaal)
                        {
                            yield return new WaitForSeconds(1);
                            profiles[i].GetComponent<ChaalSlider>().ischaal = true;
                            if (profiles[i].GetComponent<ChaalSlider>().id == Configuration.GetId())
                            {
                                canvasstart();
                            }
                        }
                        else
                        {
                            profiles[i].GetComponent<ChaalSlider>().ischaal = false;
                        }
                    }

                    if (!added)
                    {
                        float Number = float.Parse(TPresponseData.table_amount);

                        UserAmount = Number;

                        added = true;
                    }

                    if (TPresponseData.cards.Count != 0)
                    {
                        seenbool = true;
                        chaalbtn.SetActive(true);
                        chaal2x.SetActive(true);
                        blindbtn.SetActive(false);
                        StartCoroutine(wait());
                    }
                    //newamounttext.text = TPresponseData.table_amount;
                    //UserAmount = float.Parse(TPresponseData.game_log[0].amount);
                    //slideshowcanvas.SetActive(false);
                }
            }
        }
    }

    public void stopcanvas()
    {
        StartCoroutine(canvasstop());
    }

    public void canvasstart()
    {
        canvasanim.SetBool("Close", false);
        canvas.SetActive(true);
        Debug.Log("Activate");
        vibrator.enabled = true;
    }

    public IEnumerator canvasstop()
    {
        canvasanim.SetBool("Close", true);
        yield return new WaitForSeconds(0.5f);
        canvas.SetActive(false);
        shownamount = false;
        vibrator.enabled = false;
    }

    IEnumerator gamestatus2()
    {
        canvasanim.SetBool("Close", true);
        yield return new WaitForSeconds(0.5f);
        canvas.SetActive(false);
        Debug.Log("Winner called");
        gameover = true;
        for (int i = 0; i < profiles.Count; i++)
        {
            profiles[i].GetComponent<ChaalSlider>().timer = 0;
            profiles[i].GetComponent<ChaalSlider>().ischaal = false;
        }
        if (TPresponseData.winner_user_id != string.Empty)
        {
            int count = TPresponseData.game_users.Count;

            int activeplayerscount = new int();

            for (int i = 0; i < count; i++)
            {
                if (TPresponseData.game_users[i].packed != "1")
                {
                    activeplayerscount++;
                }
            }

            if (activeplayerscount >= 2)
            {
                for (int i = 0; i < count; i++)
                {
                    Debug.Log("Enter " + count);

                    for (int j = 0; j < playingusers.Count; j++)
                    {
                        profiles[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
                        Debug.Log("Enter " + TPresponseData.game_users[i].user_id);

                        #region show anim

                        myself.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);

                        foreach (GameObject chaal in profiles)
                        {
                            chaal.GetComponent<ChaalSlider>().ischaal = false;
                            chaal.GetComponent<ChaalSlider>().enabled = false;
                        }

                        if (TPresponseData.game_users[i].card1 != null)
                        {
                            if (TPresponseData.game_users[i].user_id == Configuration.GetId())
                            {
                                StartCoroutine(
                                    DownloadShowImage(Configuration.GetProfilePic(), myplayerimage)
                                );
                                myplayername.text = TPresponseData.game_users[i].name;
                                foreach (Sprite name in allcards)
                                {
                                    if (TPresponseData.game_users[i].card1.ToLower() == name.name)
                                    {
                                        mycard1.sprite = name;
                                    }
                                }
                                foreach (Sprite name in allcards)
                                {
                                    if (TPresponseData.game_users[i].card2.ToLower() == name.name)
                                    {
                                        mycard2.sprite = name;
                                    }
                                }
                                foreach (Sprite name in allcards)
                                {
                                    if (TPresponseData.game_users[i].card3.ToLower() == name.name)
                                    {
                                        mycard3.sprite = name;
                                    }
                                }
                            }
                            else
                            {
                                StartCoroutine(
                                    DownloadShowImage(
                                        TPresponseData.game_users[i].profile_pic,
                                        opponentplayerimage
                                    )
                                );
                                opponentplayername.text = TPresponseData.game_users[i].name;
                                foreach (Sprite name in allcards)
                                {
                                    if (TPresponseData.game_users[i].card1.ToLower() == name.name)
                                    {
                                        opponentcard1.sprite = name;
                                    }
                                }
                                foreach (Sprite name in allcards)
                                {
                                    if (TPresponseData.game_users[i].card2.ToLower() == name.name)
                                    {
                                        opponentcard2.sprite = name;
                                    }
                                }
                                foreach (Sprite name in allcards)
                                {
                                    if (TPresponseData.game_users[i].card3.ToLower() == name.name)
                                    {
                                        opponentcard3.sprite = name;
                                    }
                                }
                            }
                            showpanel.SetActive(true);
                            GameObject myobj = GameObject.Find("My");
                            GameObject oppobj = GameObject.Find("Other");
                            myobj.GetComponent<Animator>().enabled = true;
                            oppobj.GetComponent<Animator>().enabled = true;
                            vs.enabled = true;
                            #endregion
                        }
                    }
                }
            }

            yield return new WaitForSeconds(2);
            StartCoroutine(ShowWinner());
        }

        //SceneManager.LoadScene("TeenPatti_GamePlay");
    }

    public IEnumerator ShowWinner()
    {
        if (showpanel.activeSelf)
        {
            mine = GameObject.Find("My").GetComponent<RectTransform>();
            opponent = GameObject.Find("Other").GetComponent<RectTransform>();
            bool iwinner = false;
            if (TPresponseData.winner_user_id == Configuration.GetId())
            {
                iwinner = true;
                Debug.Log("RES_Check + i am the Wiiner");
            }
            else
            {
                iwinner = false;
                Debug.Log("RES_Check + my opponent is the winner");
            }
            Vector2 wintargetPosition = new Vector2(0, 100);
            Vector2 losstargetPosition = new Vector2(0, -100);
            if (iwinner)
            {
                StartCoroutine(MoveoppImage(losstargetPosition));
                StartCoroutine(MovemyImage(wintargetPosition));
                foreach (GameObject obj in oppobjs)
                {
                    Image img = obj.GetComponent<Image>();
                    if (img != null)
                    {
                        img.color = Color.gray;
                    }
                }

                AudioManager._instance.PlayWinSound();
            }
            else
            {
                StartCoroutine(MoveoppImage(wintargetPosition));
                StartCoroutine(MovemyImage(losstargetPosition));
                foreach (GameObject obj in mineobjs)
                {
                    Image img = obj.GetComponent<Image>();
                    if (img != null)
                    {
                        img.color = Color.gray;
                    }
                }
                AudioManager._instance.PlayLoseSound();
            }
            yield return new WaitForSeconds(2);

            // Vector2 currentPosition = opponent.anchoredPosition;
            // currentPosition.y = 0; // Set the y value to 0
            // opponent.anchoredPosition = currentPosition;
            // Vector2 currentPosition2 = mine.anchoredPosition;
            // currentPosition2.y = 0; // Set the y value to 0
            // mine.anchoredPosition = currentPosition2;
            myanimobj.transform.position = Vector3.zero;
            mine.GetComponent<Animator>().enabled = false;
            opponent.GetComponent<Animator>().enabled = false;
            vs.enabled = false;
            showpanel.SetActive(false);
            foreach (GameObject obj in mineobjs)
            {
                Image img = obj.GetComponent<Image>();
                if (img != null)
                {
                    img.color = Color.white;
                }
            }
            foreach (GameObject obj in oppobjs)
            {
                Image img = obj.GetComponent<Image>();
                if (img != null)
                {
                    img.color = Color.white;
                }
            }
        }
        int count = TPresponseData.game_users.Count;
        for (int i = 0; i < count; i++)
        {
            for (int j = 0; j < playingusers.Count; j++)
            {
                if (
                    TPresponseData.game_users[i].user_id
                    == playingusers[j].GetComponent<ChaalSlider>().id
                )
                {
                    Debug.Log("Enter " + playingusers[j].GetComponent<ChaalSlider>().id);
                    if (!string.IsNullOrEmpty(TPresponseData.game_users[i].card1))
                    {
                        Debug.Log("ShowCads " + TPresponseData.game_users[i].card1);
                        playingusers[j].transform.GetChild(1).GetComponent<Animator>().enabled =
                            true;
                        playingusers[j].transform.GetChild(1).GetComponent<Animator>().speed = 1f;
                        yield return new WaitForSeconds(0.10f);
                        foreach (Sprite name in allcards)
                        {
                            if (TPresponseData.game_users[i].card1.ToLower() == name.name)
                            {
                                playingusers[j]
                                    .transform.GetChild(1)
                                    .GetChild(0)
                                    .GetChild(0)
                                    .GetComponent<SpriteRenderer>()
                                    .sprite = name;
                            }
                        }
                        yield return new WaitForSeconds(0.10f);
                        foreach (Sprite name in allcards)
                        {
                            if (TPresponseData.game_users[i].card2.ToLower() == name.name)
                            {
                                playingusers[j]
                                    .transform.GetChild(1)
                                    .GetChild(1)
                                    .GetChild(0)
                                    .GetComponent<SpriteRenderer>()
                                    .sprite = name;
                            }
                        }
                        yield return new WaitForSeconds(0.10f);
                        foreach (Sprite name in allcards)
                        {
                            if (TPresponseData.game_users[i].card3.ToLower() == name.name)
                            {
                                playingusers[j]
                                    .transform.GetChild(1)
                                    .GetChild(2)
                                    .GetChild(0)
                                    .GetComponent<SpriteRenderer>()
                                    .sprite = name;
                            }
                        }
                    }
                }
            }
            for (int j = 0; j < playingusers.Count; j++)
            {
                if (playingusers[j].GetComponent<ChaalSlider>().id == Configuration.GetId())
                {
                    playingusers[j]
                        .transform.GetChild(1)
                        .GetComponent<Animator>()
                        .Play("ShowAnim", 0, 0f); // Reset the animation to the start without playing
                    playingusers[j].transform.GetChild(1).GetComponent<Animator>().speed = 0;
                }
                else
                {
                    playingusers[j]
                        .transform.GetChild(1)
                        .GetComponent<Animator>()
                        .Play("ShowAnim 1", 0, 0f); // Reset the animation to the start without playing
                    playingusers[j].transform.GetChild(1).GetComponent<Animator>().speed = 0;
                }
            }
        }
        if (TPresponseData.winner_user_id != string.Empty)
        {
            for (int i = 0; i < playingusers.Count; i++)
            {
                if (TPresponseData.winner_user_id == playingusers[i].GetComponent<ChaalSlider>().id)
                {
                    playingusers[i].transform.GetChild(2).gameObject.SetActive(true);
                    CommonUtil.CheckLog(
                        "coin distribute Winner " + playingusers[i].GetComponent<ChaalSlider>().id
                    );
                    coindistribute(playingusers[i].transform);
                    winnerprofile = playingusers[i];
                    Debug.Log(playingusers[i].transform.GetChild(2).gameObject.name + "win");
                    if (TPresponseData.winner_user_id == Configuration.GetId())
                    {
                        if (Configuration.GetSound() == "on")
                        {
                            winsound.volume = 1f;
                            winsound.Play();
                        }
                        else
                        {
                            winsound.volume = 0f;
                        }
                    }
                    else
                    {
                        if (Configuration.GetSound() == "on")
                        {
                            loosesound.volume = 1f;
                            loosesound.Play();
                        }
                        else
                        {
                            loosesound.volume = 0f;
                        }
                    }
                }
            }
        }
        for (int i = 0; i < profiles.Count; i++)
        {
            if (Configuration.GetId() == profiles[i].GetComponent<ChaalSlider>().id)
            {
                profiles[i].transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
            }
        }
        yield return new WaitForSeconds(4);
        if (TPresponseData.winner_user_id != string.Empty)
        {
            for (int i = 0; i < playingusers.Count; i++)
            {
                if (TPresponseData.winner_user_id == playingusers[i].GetComponent<ChaalSlider>().id)
                    playingusers[i].transform.GetChild(2).gameObject.SetActive(false);
            }
        }

        waitingusers.Clear();
        playingusers.Clear();
        waitingusers2.Clear();
        typetext.transform.parent.gameObject.SetActive(false);
        StartCoroutine(restartgamedelay());
    }

    public void coindistribute(Transform profile)
    {
        CommonUtil.CheckLog("coin distribute");
        for (int i = 0; i < coinslist.Count; i++)
        {
            coinslist[i].transform.DOMove(profile.position, 1f).SetEase(Ease.Linear);
            coinslist[i].transform.DOScale(new Vector3(0.3f, 0.3f, 0.3f), 1f).SetEase(Ease.Linear);
        }

        for (int i = 0; i < coinslist.Count; i++)
        {
            Destroy(coinslist[i]);
        }

        coinslist.Clear();
    }

    IEnumerator MovemyImage(Vector2 targetPosition)
    {
        while (mine.anchoredPosition != targetPosition)
        {
            Debug.Log("RES_Check + MovemyImage Called");
            mine.anchoredPosition = Vector2.MoveTowards(
                mine.anchoredPosition,
                targetPosition,
                moveSpeed * Time.deltaTime
            );
            yield return null; // Wait for the next frame before continuing
        }
        yield return new WaitForSeconds(2);
        mine.anchoredPosition = new Vector2(0, 0);
    }

    IEnumerator MoveoppImage(Vector2 targetPosition)
    {
        // Continue moving the image until it reaches the target position
        while (opponent.anchoredPosition != targetPosition)
        {
            opponent.anchoredPosition = Vector2.MoveTowards(
                opponent.anchoredPosition,
                targetPosition,
                moveSpeed * Time.deltaTime
            );
            yield return null; // Wait for the next frame before continuing
        }
        yield return new WaitForSeconds(2);
        opponent.anchoredPosition = new Vector2(0, 0);
    }

    public void clickedPack()
    {
        clickedpack = true;
    }

    IEnumerator wait()
    {
        mycards.Clear();
        mycards.Add(TPresponseData.cards[0].card1.ToLower());
        mycards.Add(TPresponseData.cards[0].card2.ToLower());
        mycards.Add(TPresponseData.cards[0].card3.ToLower());
        evaluatemycards();
        anim.enabled = true;
        anim.speed = 1;
        yield return new WaitForSeconds(0.10f);
        foreach (Sprite name in allcards)
        {
            if (mycards[0] == name.name)
            {
                myself
                    .transform.GetChild(1)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = name;
            }
        }
        yield return new WaitForSeconds(0.20f);
        foreach (Sprite name in allcards)
        {
            if (mycards[1] == name.name)
            {
                myself
                    .transform.GetChild(1)
                    .GetChild(1)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = name;
            }
        }
        yield return new WaitForSeconds(0.20f);
        foreach (Sprite name in allcards)
        {
            if (mycards[2] == name.name)
            {
                myself
                    .transform.GetChild(1)
                    .GetChild(2)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = name;
            }
        }
        myself.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
        yield return new WaitForSeconds(1.5f);
        //anim.enabled = false;
    }

    IEnumerator wait_See(string firstcard, string secondcard, string thirdcard)
    {
        mycards.Clear();
        mycards.Add(firstcard.ToLower());
        mycards.Add(secondcard.ToLower());
        mycards.Add(thirdcard.ToLower());
        evaluatemycards();
        anim.enabled = true;
        anim.speed = 1;
        yield return new WaitForSeconds(0.10f);
        foreach (Sprite name in allcards)
        {
            if (mycards[0] == name.name)
            {
                myself
                    .transform.GetChild(1)
                    .GetChild(0)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = name;
            }
        }
        yield return new WaitForSeconds(0.20f);
        foreach (Sprite name in allcards)
        {
            if (mycards[1] == name.name)
            {
                myself
                    .transform.GetChild(1)
                    .GetChild(1)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = name;
            }
        }
        yield return new WaitForSeconds(0.20f);
        foreach (Sprite name in allcards)
        {
            if (mycards[2] == name.name)
            {
                myself
                    .transform.GetChild(1)
                    .GetChild(2)
                    .GetChild(0)
                    .GetComponent<SpriteRenderer>()
                    .sprite = name;
            }
        }
        myself.transform.GetChild(0).GetChild(3).gameObject.SetActive(false);
        yield return new WaitForSeconds(1.5f);
        //anim.enabled = false;
    }

    public void Chaal()
    {
        var customNamespace = Manager.GetSocket(name_space);

        TPUserChaalData jsonData = new TPUserChaalData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.plus = value.ToString();

            jsonData.bot = user_type;

            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);

            value = 0;

            customNamespace.Emit("chaal", jsonData);
            profiles[0].GetComponent<ChaalSlider>().ischaal = false;
            Debug.Log("RES_CHECK + Emit-Chaal");
            Debug.Log("RES_VALUE" + "Emit-chaal " + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void slideshowyes()
    {
        var customNamespace = Manager.GetSocket(name_space);

        TPemitslideshowData jsonData = new TPemitslideshowData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            jsonData.slide_id = TPresponseData.slide_show[0].id;

            jsonData.type = "1";

            string jsonStr = JsonUtility.ToJson(jsonData);
            Debug.Log("Res_Check do-slide-show " + jsonStr);
            customNamespace.Emit("do-slide-show", jsonData);
            slideshowcanvas.SetActive(false);
            showcanvasid = "";
            Debug.Log("RES_CHECK");
            Debug.Log(Configuration.GetId() + " user ID for Silde yes Emit");
            Debug.Log(Configuration.GetToken() + " token for Silde yes Emit");
            Debug.Log(TPresponseData.slide_show[0].user_id + " slide ID for Silde yes Emit");
            Debug.Log(jsonData.type + " type for Silde yes Emit");
            Debug.Log("RES_VALUE" + "do-slide-show" + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void slideshowno()
    {
        var customNamespace = Manager.GetSocket(name_space);

        TPemitslideshowData jsonData = new TPemitslideshowData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            jsonData.slide_id = TPresponseData.slide_show[0].id;

            jsonData.type = "0";

            string jsonStr = JsonUtility.ToJson(jsonData);

            customNamespace.Emit("do-slide-show", jsonData);
            slideshowcanvas.SetActive(false);
            showcanvasid = "";
            Debug.Log("RES_CHECK + Emit-do-slide-show");
            Debug.Log("RES_VALUE" + "do-slide-show" + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void GameSideShow()
    {
        var customNamespace = Manager.GetSocket(name_space);
        TPSlideShowData jsonObjectSlideshow = new TPSlideShowData();
        try
        {
            jsonObjectSlideshow.user_id = Configuration.GetId();

            int seatNo = 0;

            for (int i = 0; i < TPresponseData.game_users.Count; i++)
            {
                if (TPresponseData.game_users[i].user_id == Configuration.GetId())
                {
                    seatNo = i;
                }
            }

            if (seatNo == 0)
            {
                previousid = TPresponseData.game_users[TPresponseData.game_users.Count - 1].user_id;
            }
            else
            {
                previousid = TPresponseData.game_users[seatNo - 1].user_id;
            }

            jsonObjectSlideshow.prev_user_id = previousid;

            jsonObjectSlideshow.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonObjectSlideshow);
            customNamespace.Emit("slide-show", jsonObjectSlideshow);
            Debug.Log("RES_CHECK + Emit-slide-show");
            Debug.Log("RES_VALUE" + "slide-show" + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void ShowGame()
    {
        var customNamespace = Manager.GetSocket(name_space);

        TPTableuserData jsonData = new TPTableuserData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("show-game", jsonData);

            Debug.Log("RES_CHECK + Emit-show-game");
            Debug.Log("RES_VALUE" + "show-game" + jsonStr);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    //private void OnConnected(ConnectResponse resp)
    //{
    //    Debug.Log("Connect : " + resp.sid);
    //    ControllerDetail.IsConnection = true;
    //    Reconn_Canvas.SetActive(false);
    //}

    IEnumerator join()
    {
        yield return new WaitForSeconds(2);
    }

    private void GetTable()
    {
        var customNamespace = Manager.GetSocket(name_space);
        Debug.Log("RES_Check + Call Get Table");
        TPTableData jsonData = new TPTableData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            jsonData.boot_value = Configuration.Gettpboot();

            string jsonStr = JsonUtility.ToJson(jsonData);

            Debug.Log("RES_Check + Call Get Table 2");

            customNamespace.Emit("get-table", jsonData);

            Debug.Log("RES_CHECK + Emit-get-table");
            Debug.Log("RES_VALUE" + " EMIT-get-table " + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void StartGame()
    {
        var customNamespace = Manager.GetSocket(name_space);

        TPTableuserData jsonData = new TPTableuserData();
        try
        {
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("start-game", jsonData);

            Debug.Log("RES_CHECK + Emit-start-game");
            Debug.Log("RES_VALUE" + " EMIT-start-game" + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    public void SeeGame()
    {
        var customNamespace = Manager.GetSocket(name_space);

        TPTableuserData jsonData = new TPTableuserData();
        try
        {
            Debug.Log("RES_Check + See_Card Json emit");
            jsonData.user_id = Configuration.GetId();

            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("see-card", jsonData);

            Debug.Log("RES_CHECK + Emit-see-card");
            Debug.Log("RES_VALUE" + " EMIT-see-card" + jsonData);
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

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
            this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
            return;
        }

        placementdata.Clear();

        placementdata = TPresponseData.table_users;

        int index = placementdata.FindIndex(x => x.user_id == Configuration.GetId());

        // Move the item to the first position if it's not already in the first position
        if (index > 0)
        {
            TPUserData userData = placementdata[index];
            placementdata.RemoveAt(index);
            placementdata.Insert(0, userData);
        }
        if (index == -1)
        {
            this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
            return;
        }

        for (int i = 0; i < placementdata.Count; i++)
        {
            if (placementdata[i].user_id == "0")
            {
                Debug.Log("I am null");
                profiles[i].transform.GetChild(0).gameObject.SetActive(false);
                profiles[i].transform.GetChild(4).gameObject.SetActive(true);
                profiles[i].transform.GetChild(1).gameObject.SetActive(false);
                profiles[i].GetComponent<ChaalSlider>().enabled = false;
                //profiles[i].SetActive(false);
            }
            else
            {
                active.Add(profiles[i]);
                if (placementdata[i].user_type == "1")
                {
                    user_type = "1";
                }
                profiles[i].GetComponent<ChaalSlider>().enabled = true;
                profiles[i].GetComponent<ChaalSlider>().id = placementdata[i].user_id;
                profiles[i].transform.GetChild(0).gameObject.SetActive(true);
                profiles[i].transform.GetChild(4).gameObject.SetActive(false);
                if (i != 0)
                    profiles[i]
                        .transform.GetChild(0)
                        .GetChild(0)
                        .GetChild(1)
                        .GetComponent<TextMeshProUGUI>()
                        .text = placementdata[i].name;
                if (i == 0)
                    Wallet_paenl.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        FormatNumber(placementdata[i].wallet);
                else
                    profiles[i]
                        .transform.GetChild(0)
                        .GetChild(0)
                        .GetChild(0)
                        .GetComponent<TextMeshProUGUI>()
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
                // if (i != 0)
                // {
                //     Image img2 = profiles[i].transform.GetChild(0).GetChild(1).GetComponent<Image>();
                //     StartCoroutine(DownloadImage(placementdata[i].profile_pic, img2, profiles[i].GetComponent<ProfileDetails>(), profiles[i]));
                // }
            }
        }

        if (TPresponseData.winner_user_id != string.Empty)
        {
            for (int i = 0; i < playingusers.Count; i++)
            {
                if (TPresponseData.winner_user_id == playingusers[i].GetComponent<ChaalSlider>().id)
                    playingusers[i].transform.GetChild(2).gameObject.SetActive(false);
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
        //    Debug.Log("Called Start Game 2");
        //}

        //bool numdone = false;

        //for (int i = 0; i < count; i++)
        //{

        //    Debug.Log("Called Start Game 3 " + count + " num");
        //    if (num == 0)
        //    {
        //        profiles[i].gameObject.SetActive(true);
        //        active.Add(profiles[i]);
        //        profiles[i].GetComponent<ChaalSlider>().id = activeusers[i].user_id;
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
        //            active.Add(profiles[i]);
        //            profiles[0].GetComponent<ChaalSlider>().id = activeusers[i].user_id;
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
        //                active.Add(profiles[i]);
        //                profiles[i + 1].GetComponent<ChaalSlider>().id = activeusers[i].user_id;
        //                profiles[i + 1].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = activeusers[i].name;
        //                profiles[i + 1].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = activeusers[i].wallet;
        //                Image img = profiles[i + 1].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
        //                StartCoroutine(DownloadImage(activeusers[i].profile_pic, img, profiles[i + 1].GetComponent<ProfileDetails>()));
        //            }
        //            else
        //            {
        //                profiles[i].gameObject.SetActive(true);
        //                active.Add(profiles[i]);
        //                profiles[i].GetComponent<ChaalSlider>().id = activeusers[i].user_id;
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

    public void GameUsers()
    {
        if (TPresponseData.game_users.Count != 0)
        {
            for (int i = 0; i < profiles.Count; i++)
            {
                for (int j = 0; j < TPresponseData.game_users.Count; j++)
                {
                    if (placementdata[i].user_id == "0")
                    {
                        profiles[i].GetComponent<ChaalSlider>().id = "0";
                        profiles[i].transform.GetChild(0).gameObject.SetActive(false);
                        profiles[i].transform.GetChild(4).gameObject.SetActive(true);
                        profiles[i].transform.GetChild(1).gameObject.SetActive(false);
                    }
                    else
                    {
                        if (
                            profiles[i].GetComponent<ChaalSlider>().id
                            == TPresponseData.game_users[j].user_id
                        )
                        {
                            profiles[i].transform.GetChild(0).gameObject.SetActive(true);
                            profiles[i].transform.GetChild(4).gameObject.SetActive(false);
                            profiles[i].transform.GetChild(1).gameObject.SetActive(true);
                            Debug.Log("ID to active " + profiles[i].GetComponent<ChaalSlider>().id);
                        }
                    }
                }
            }
        }
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

    public IEnumerator DownloadShowImage(string ProfileAvatar, Image img)
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

                // det.sSprite = img.sprite;
                // if (CountImage == 0)
                // {
                //     det.IsProfileImage = false;
                // }
                // CountImage--;
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

    List<string> seats()
    {
        activeusers.Clear();

        //int count = response.table_data.Count;

        List<string> list = new List<string>();

        //for (int i = 0; i < count; i++)
        //{
        //    if (response.table_data[i].user_id != "0")
        //    {
        //        activeusers.Add(response.table_data[i]);
        //    }
        //}

        for (int i = 0; i < TPresponseData.table_users.Count; i++)
        {
            if (TPresponseData.table_users[i].id != "0")
            {
                activeusers.Add(TPresponseData.table_users[i]);
                list.Add(TPresponseData.table_users[i].user_id);
            }
        }

        return list;
    }

    public void leave()
    {
        StartCoroutine(disconn());
    }

    IEnumerator disconn()
    {
        CALL_Leave_table();

        yield return new WaitForSeconds(1);
    }

    public void CALL_Pack()
    {
        var customNamespace = Manager.GetSocket(name_space);

        // socket start game
        PackData jsonData = new PackData();
        try
        {
            jsonData.user_id = Configuration.GetId();
            jsonData.token = Configuration.GetToken();
            jsonData.timeout = "0";

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("pack-game", jsonData);
            Debug.Log("RES_Check + Emit-pack-game");
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
        TabbleData jsonData = new TabbleData();
        try
        {
            PlayerPrefs.DeleteKey("create");
            PlayerPrefs.DeleteKey("join");
            jsonData.user_id = Configuration.GetId();
            jsonData.token = Configuration.GetToken();

            string jsonStr = JsonUtility.ToJson(jsonData);
            customNamespace.Emit("leave-table", jsonData);
            Debug.Log("RES_Check + Emit-Leave-table");

            PlayerPrefs.DeleteKey("table_id");

            //GetComponent<GameSelection>().
            // this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
            // Manager.Close();

        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    #region My card type

    public void evaluatemycards()
    {
        Debug.Log("RES_Check + Evaluate");
        getmyallcards();
        role = Evaluatecard(evaluatecards);
        typetext.transform.parent.gameObject.SetActive(true);
    }

    public void getmyallcards()
    {
        evaluatecards.Clear();
        Debug.Log("RES_Check + Card1 " + TPresponseData.cards[0].card1.ToLower());
        string card1 = RemoveUnderscores(TPresponseData.cards[0].card1.ToLower());
        evaluatecards.Add(card1);
        string card2 = RemoveUnderscores(TPresponseData.cards[0].card2.ToLower());
        evaluatecards.Add(card2);
        string card3 = RemoveUnderscores(TPresponseData.cards[0].card3.ToLower());
        evaluatecards.Add(card3);
    }

    public string RemoveUnderscores(string input)
    {
        return input.Replace("_", "");
    }

    private static readonly Dictionary<string, int> cardValues = new Dictionary<string, int>
    {
        { "a", 14 },
        { "k", 13 },
        { "q", 12 },
        { "j", 11 },
        { "10", 10 },
        { "9", 9 },
        { "8", 8 },
        { "7", 7 },
        { "6", 6 },
        { "5", 5 },
        { "4", 4 },
        { "3", 3 },
        { "2", 2 },
    };

    public int Evaluatecard(List<string> cards)
    {
        mycardvalues.Clear();
        mycardsuits.Clear();

        int bestType = Configuration.HIGH_CARD;

        bestType = EvaluateMyThreeCard(cards);

        return bestType;
    }

    public int EvaluateMyThreeCard(List<string> cards)
    {
        foreach (string card in cards)
        {
            int cardValue = GetCardValue(card);
            mycardvalues.Add(cardValue);

            string cardSuit = GetCardSuit(card);
            mycardsuits.Add(cardSuit);
        }

        mycardvalues.Sort();

        if (IsSet())
        {
            typetext.text = "SET";
            return Configuration.SET;
        }

        if (IsPureSequence())
        {
            typetext.text = "PURE SEQUENCE";
            return Configuration.PURE_SEQUENCE;
        }

        if (IsInSequence())
        {
            typetext.text = "SEQUENCE";
            return Configuration.SEQUENCE;
        }

        if (IsColor())
        {
            typetext.text = "COLOR";
            return Configuration.COLOR;
        }

        if (IsPair())
        {
            typetext.text = "PAIR";
            return Configuration.TPPAIR;
        }

        typetext.text = "HIGH CARD";
        return Configuration.TPHIGH_CARD;
    }

    private bool IsSet()
    {
        return mycardvalues[0] == mycardvalues[1] && mycardvalues[1] == mycardvalues[2];
    }

    private bool IsPureSequence()
    {
        return IsInSequence() && IsColor();
    }

    private bool IsInSequence()
    {
        mycardvalues.Sort();

        bool regularSequence =
            mycardvalues[1] == mycardvalues[0] + 1 && mycardvalues[2] == mycardvalues[1] + 1;

        bool aceLowSequence =
            mycardvalues.Contains(14) && mycardvalues.Contains(2) && mycardvalues.Contains(3);
        bool aceHighSequence =
            mycardvalues.Contains(14) && mycardvalues.Contains(13) && mycardvalues.Contains(12);

        return regularSequence || aceLowSequence || aceHighSequence;
    }

    private bool IsColor()
    {
        return mycardsuits[0] == mycardsuits[1] && mycardsuits[1] == mycardsuits[2];
    }

    private bool IsPair()
    {
        return mycardvalues[0] == mycardvalues[1]
            || mycardvalues[1] == mycardvalues[2]
            || mycardvalues[0] == mycardvalues[2];
    }

    private static int GetCardValue(string card)
    {
        string rank = card.Substring(2);
        if (cardValues.TryGetValue(rank, out int value))
        {
            return value;
        }
        throw new ArgumentException($"Invalid card rank: {rank}");
    }

    private static string GetCardSuit(string card)
    {
        return card.Substring(0, Mathf.Min(2, card.Length));
    }

    #endregion
}
