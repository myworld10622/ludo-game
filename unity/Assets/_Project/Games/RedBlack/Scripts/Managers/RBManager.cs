using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using AndroApps;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyUI.Toast;
using Mkey;
using Newtonsoft.Json;
using TMPro;
using Unity.Burst.Intrinsics;
using UnityEditor;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.Profiling;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class RBManager : MonoBehaviour
{
    private string CustomNamespace = "/red_black";

    [Header("last winning Sprite Data")]
    public List<string> lastwinning;
    public List<Sprite> lastwinningsprite;
    public Sprite back_card;
    public List<GameObject> lastwinningimagestoshow;
    public List<GameObject> highlightlist;
    private SocketManager Manager;

    public RBRootObject RBdata;
    public List<Sprite> cards;
    public List<Sprite> allcards;
    public TextMeshProUGUI timertext;
    public bool start;
    public GameObject userprofile;
    public List<GameObject> profiles;
    public List<BotsUser> m_Bots = new List<BotsUser>();
    public string betamount;
    public string bet;
    public List<GameObject> btns;
    public GameObject buttonclicked;
    public List<GameObject> coins;
    public int num;
    public Image red,
        black,
        pair,
        color,
        pure_seq,
        seq,
        set;
    public int totalcoinsleft,
        randomecount,
        left1,
        left2;
    public List<Sprite> profileimages;
    public GameObject Reconn_Canvas;
    public newLogInOutputs LogInOutput;
    public TextMeshProUGUI red_amount,
        black_amount,
        pair_amount,
        color_amount,
        pure_seq_amount,
        seq_amount,
        set_amount;
    public int red_amount_int,
        black_amount_int,
        pair_amount_int,
        color_amount_int,
        pure_seq_amount_int,
        seq_amount_int,
        set_amount_int;
    public bool check,
        showrecord,
        online;
    public GameObject bl2;
    public Animator onlineuser;
    public RedAndBlackBetResult rbresultdata;
    public bool reconnected,
        invoke;
    public int timetoinvoke;

    #region music and sounds

    public Button[] buttons;

    #endregion
    public Toggle soundToggle;
    public Toggle musicToggle;

    public GameObject showstop;
    private bool gamestart;
    public Text stoptext;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;

    public List<Transform> cardsRotateList;
    private int poolSize = 50;
    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;
    #endregion

    private void OnEnable()
    {
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();

        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        CommonUtil.CheckLog("RES_CHECK Socket URL Red Black " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("red_black_timer", OnRed_Black_timerResponse);
        customNamespace.On<string>("red_black_status", OnRed_Black_statusResponse);
        customNamespace.On<string>("leave-table", leave);
        Manager.Open();

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

    void Start()
    {
        foreach (var coin in coins)
        {
            var pool = coin.AddComponent<ObjectPoolUtil>();
            pool.InitializePool(coin, 10);
        }
        //buttonclick(0);
        clickedbutton(buttons[0].gameObject);
        /*      coininstantate(10);
             GameBetUtil.OnButtonClickParticle(num, particleSystems.ToList(), ref currentParticle); */
        GameBetUtil.UpdateButtonInteractability(
            Configuration.GetWallet(),
            buttons.ToList(),
            null,
            ref buttonclicked,
            ref currentParticle,
            gamestart,
            buttonclick,
            clickedbutton,
            coininstantate,
            ref betamount
        );
        particleSystems[0].gameObject.SetActive(true);
        particleSystems[0].Play();
    }

    #region  minimize

    void OnApplicationPause(bool pauseStatus)
    {
        if (!pauseStatus)
        {
            CommonUtil.CheckLog("RES_Check + resume");
            RequestGameStateUpdate();
        }
        else
        {
            //StopCoroutine(aibet());
            // StopCoroutine(GameBetUtil.OnlineBet(coins, m_ColliderList, onlineuser, ABdata.game_data[0].status, timertext.text, m_DummyObjects));
        }
    }

    private void RequestGameStateUpdate()
    {
        invoke = false;
        var url = Configuration.BaseSocketUrl;
        CommonUtil.CheckLog("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("red_black_timer", OnRed_Black_timerResponse);
        customNamespace.On<string>("red_black_status", OnRed_Black_statusResponse);
        customNamespace.On<string>("leave-table", leave);
        Manager.Open();
        GetTimer();
        reconnected = true;
        timetoinvoke = 0;
        StartCoroutine(invokefortime());
    }

    IEnumerator invokefortime()
    {
        for (int i = 0; i < 2; i++)
        {
            timetoinvoke++;
            yield return new WaitForSeconds(1);
        }

        invoke = true;
    }

    private void ResetAmounts(bool isSameGame)
    {
        if (isSameGame)
        {
            red_amount.text = red_amount_int.ToString();
            black_amount.text = black_amount_int.ToString();
            pair_amount.text = pair_amount_int.ToString();
            color_amount.text = color_amount_int.ToString();
            pure_seq_amount.text = pure_seq_amount_int.ToString();
            seq_amount.text = seq_amount_int.ToString();
            set_amount.text = set_amount_int.ToString();
        }
        else
        {
            red_amount_int =
                black_amount_int =
                pair_amount_int =
                color_amount_int =
                pure_seq_amount_int =
                seq_amount_int =
                set_amount_int =
                    0;
            red_amount.text = red_amount_int.ToString();
            black_amount.text = black_amount_int.ToString();
            pair_amount.text = pair_amount_int.ToString();
            color_amount.text = color_amount_int.ToString();
            pure_seq_amount.text = pure_seq_amount_int.ToString();
            seq_amount.text = seq_amount_int.ToString();
            set_amount.text = set_amount_int.ToString();
            for (int i = 0; i < m_colidertext.Count; i++)
            {
                m_colidertext[i].text = "0";
            }
        }
    }

    private void ClearGameObjects()
    {
        foreach (GameObject coin in m_DummyObjects)
        {
            Destroy(coin);
        }
        m_DummyObjects.Clear();
    }

    private void ClearObjectList(List<GameObject> objectList)
    {
        foreach (GameObject obj in objectList)
        {
            Destroy(obj);
        }
        objectList.Clear();
    }

    #endregion

    void OnConnected(ConnectResponse resp)
    {
        invoke = true;
        CommonUtil.CheckLog("RES_CHECK On - Connected + " + resp.sid);
        GetTimer();
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

    #region Connection/Disconnection Socket

    private void leave(string args)
    {
        CommonUtil.CheckLog("get-table Json :" + args);
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    public void disconn()
    {
        //CALL_Leave_table();

        SceneManager.LoadSceneAsync("Main");
    }

    public void CALL_Leave_table()
    {
        Manager.Close();
        StopAllCoroutines();
        this.gameObject.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
    }

    public void OnDisconnected()
    {
        //Reconn_Canvas.SetActive(true);
    }

    public void Disconnect()
    {
        Manager.Close();
        var customNamespaceSocket = Manager.GetSocket(CustomNamespace);
        customNamespaceSocket.Disconnect();
        AudioManager._instance.StopEffect();
        SceneLoader.Instance.LoadScene("HomePage");
        //LoaderUtil.instance.LoadScene("HomePage");
    }

    #endregion

    private List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<TextMeshProUGUI> m_colidertext = new List<TextMeshProUGUI>();

    #region status related functions
    public void ShowLast10Win()
    {
        lastwinning.Clear();
        for (int i = 0; i < RBdata.last_winning.Count; i++)
        {
            lastwinning.Add(RBdata.last_winning[i].winning);
        }

        for (int i = 0; i < lastwinning.Count; i++)
        {
            //CommonUtil.CheckLog("RES_Check + Last winning");
            if (i < lastwinningimagestoshow.Count)
            {
                if (lastwinning[i] == "1")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[0];
                else if (lastwinning[i] == "2")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[1];
            }
        }
    }

    public async void updateprofile()
    {
        if (!check)
        {
            UserNameText.text = Configuration.GetName();
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            check = true;
        }

        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].BotName.text = RBdata.bot_user[i].name;
            //m_Bots[i].BotCoin.text = CommonUtil.GetFormattedWallet(RBdata.bot_user[i].coin);
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + RBdata.bot_user[i].avatar
            );
        }
    }

    #endregion

    #region socket status
    private void OnRed_Black_statusResponse(string args)
    {
        CommonUtil.CheckLog("RES_CHECK OnRed_Black_statusResponse ");
        CommonUtil.CheckLog("RES_VALUE Status Game Json :" + args);

        try
        {
            RBdata = JsonUtility.FromJson<RBRootObject>(args);
            ShowLast10Win();
            updateprofile();
            cards.Clear();
            if (RBdata.game_data[0].status == "0")
            {
                Debug.Log("status" + RBdata.game_data[0].status);
                if (!online)
                {
                    AudioManager._instance.PlayPlaceBetSound();
                    stoptext.text = "PLACE BET";
                    showstop.SetActive(true);
                    DOVirtual.DelayedCall(
                        2f,
                        () =>
                        {
                            showstop.SetActive(false);
                        }
                    );
                    online = true;
                    for (int i = 0; i < m_Bots.Count; i++)
                    {
                        m_Bots[i].NoUser.SetActive(false);
                        m_Bots[i].UserCome.SetActive(true);
                    }
                    StartCoroutine(
                        GameBetUtil.StartBet(
                            coins,
                            m_ColliderList,
                            profiles,
                            onlineuser,
                            m_DummyObjects,
                            m_colidertext,
                            isAI: true
                        )
                    );
                    StartCoroutine(
                        GameBetUtil.StartBet(
                            coins,
                            m_ColliderList,
                            profiles,
                            onlineuser,
                            m_DummyObjects,
                            m_colidertext,
                            isAI: false
                        )
                    );
                }

                string game_id = Configuration.getrbid();
                bool isSameGame = game_id == RBdata.game_data[0].id;

                if (reconnected)
                {
                    ResetAmounts(isSameGame);
                    ClearGameObjects();
                    reconnected = false;
                }
                else
                {
                    ResetAmounts(isSameGame);
                }
                PlayerPrefs.SetString("rbid", RBdata.game_data[0].id);
            }
            else
            {
                Debug.Log("status" + RBdata.game_data[0].status);
                Debug.Log("STOP BET:");
                if (online)
                {
                    online = false;
                    GameBetUtil.StopBet();
                }
            }
            if (RBdata.game_cards.Count != 0)
            {
                for (int i = 0; i < RBdata.game_cards.Count; i++)
                {
                    string cardname = RBdata.game_cards[i].card.ToLower();
                    bool found = false;
                    for (int j = 0; j < allcards.Count; j++)
                    {
                        if (allcards[j].name.ToLower() == cardname) // Comparing names in a case-insensitive manner
                        {
                            cards.Add(allcards[j]);
                            found = true; // Set flag to true if a matching card is found
                        }
                    }
                    if (!found) // Check if a matching card was not found
                        CommonUtil.CheckLog("RES_CHECK Card not found: " + cardname);
                }
                int num = 0;
                if (timertext.text == "1" && invoke)
                {
                    CommonUtil.ShowToast(
                        "winning: "
                            + RBdata.game_data[0].winning
                            + " , Game Id: "
                            + RBdata.game_data[0].id
                    );
                    timertext.text = "0";
                    onlineuser.enabled = false; //(onlineuser is Animator)
                    if (RBdata.game_data[0].status == "1")
                    {
                        StartCoroutine(gameover());
                        gamestart = false;
                        GameBetUtil.UpdateButtonInteractability(
                            Configuration.GetWallet(),
                            buttons.ToList(),
                            null,
                            ref buttonclicked,
                            ref currentParticle,
                            gamestart,
                            buttonclick,
                            clickedbutton,
                            coininstantate,
                            ref betamount
                        );

                        start = false;
                    }
                }
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    IEnumerator gameover()
    {
        foreach (var obj in buttons)
        {
            obj.interactable = false;
        }
        AudioManager._instance.PlayStopBetSound();
        stoptext.text = "STOP BET";
        showstop.SetActive(true);
        yield return new WaitForSeconds(2);
        showstop.SetActive(false);
        for (int i = 0; i < cardsRotateList.Count; i++)
        {
            CommonUtil.CheckLog("My Card " + RBdata.game_cards[i].card.ToLower());
            StartCoroutine(
                CardUtil.AnimateFlipCard(
                    cardsRotateList[i],
                    cardsRotateList[i].GetComponent<SpriteRenderer>(),
                    cards[i],
                    0.4f,
                    -2,
                    180
                )
            );
        }
        SetHighlighter();
        // StartCoroutine(CardUtil.MoveAllCards(cardsRotateList, endPositions, startPosition));
        // //StartCoroutine(patti.MoveAllCards());
        StartCoroutine(startDestroy());
    }

    public IEnumerator changetext()
    {
        //yield return new WaitForSeconds(1);
        timertext.text = "0";
        yield return null;
    }
    #endregion

    #region Timer
    private void GetTimer()
    {
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("red_black_timer", "red_black_timer");

            CommonUtil.CheckLog("RES_CHECK" + " EMIT-red_black_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnRed_Black_timerResponse(string args)
    {
        CommonUtil.CheckLog("RES_CHECK Timmer:" + args);
        Stopshowwait();
        try
        {
            timertext.text = args;
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    public void Stopshowwait()
    {
        if (!gamestart)
        {
            foreach (var btn in buttons)
            {
                btn.interactable = true;
            }

            gamestart = true;
            GameBetUtil.UpdateButtonInteractability(
                Configuration.GetWallet(),
                buttons.ToList(),
                null,
                ref buttonclicked,
                ref currentParticle,
                gamestart,
                buttonclick,
                clickedbutton,
                coininstantate,
                ref betamount
            );
        }
    }
    #endregion

    #region win functions

    public void SetHighlighter()
    {
        Image one = null;
        Image two = null;
        if (RBdata.game_data[0].winning == "1")
        {
            one = red;
        }
        else if (RBdata.game_data[0].winning == "2")
        {
            one = black;
        }

        if (RBdata.game_data[0].winning_rule == "2")
        {
            two = pair;
        }
        else if (RBdata.game_data[0].winning_rule == "3")
        {
            two = color;
        }
        else if (RBdata.game_data[0].winning_rule == "4")
        {
            two = seq;
        }
        else if (RBdata.game_data[0].winning_rule == "5")
        {
            two = pure_seq;
        }
        else if (RBdata.game_data[0].winning_rule == "6")
        {
            two = set;
        }
        StartCoroutine(highlightwin(one, two));
    }

    IEnumerator highlightwin(Image one, Image two)
    {
        yield return new WaitForSeconds(2.5f);
        updatedata();
        AudioManager._instance.PlayHighlightWinSound();
        one.gameObject.SetActive(true);
        two.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(false);
        two.gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(true);
        two.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(false);
        two.gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(true);
        two.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(false);
        two.gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(true);
        two.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        one.gameObject.SetActive(false);
        two.gameObject.SetActive(false);
        totalcoinsleft = 0;

        for (int i = 0; i < cardsRotateList.Count; i++)
        {
            StartCoroutine(
                CardUtil.AnimateFlipCard(
                    cardsRotateList[i].transform,
                    cardsRotateList[i].GetComponent<SpriteRenderer>(),
                    back_card,
                    0.5f,
                    2,
                    0
                )
            );
        }
        GetResult();
        red_amount.text = "0";
        black_amount.text = "0";
        pair_amount.text = "0";
        color_amount.text = "0";
        pure_seq_amount.text = "0";
        seq_amount.text = "0";
        set_amount.text = "0";
        for (int i = 0; i < m_colidertext.Count; i++)
        {
            m_colidertext[i].text = "0";
        }
        showrecord = false;
        check = false;
        yield return new WaitForSeconds(2f);
        red_amount_int = 0;
        black_amount_int = 0;
        pair_amount_int = 0;
        color_amount_int = 0;
        pure_seq_amount_int = 0;
        seq_amount_int = 0;
        set_amount_int = 0;
        ShowLast10Win();
    }

    IEnumerator startDestroy()
    {
        yield return new WaitForSeconds(8);
        foreach (GameObject coin in m_DummyObjects)
        {
            Destroy(coin);
        }
        m_DummyObjects.Clear();
    }

    #endregion

    #region buttons_Functionality
    public async void PlaceBet()
    {
        string url = RBConfig.RedAndBlackPutBet;

        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", RBdata.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        JsonResponse jsonResponse = new JsonResponse();
        jsonResponse = await APIManager.Instance.Post<JsonResponse>(url, formData);

        Debug.Log(betamount);

        if (jsonResponse.code == 406)
        {
            showtoastmessage(jsonResponse.message);
        }
        else
        {
            string walletString = jsonResponse.wallet;
            PlayerPrefs.SetString("wallet", jsonResponse.wallet);
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            if (jsonResponse.code == 200)
            {
                if (bet == "1")
                {
                    red_amount_int += int.Parse(betamount);
                    // m_colidertext[0].text =
                    //     int.Parse(m_colidertext[0].text) + int.Parse(betamount) + "";
                    red_amount.text = red_amount_int.ToString();
                }
                else if (bet == "2")
                {
                    black_amount_int += int.Parse(betamount);
                    // m_colidertext[1].text =
                    //     int.Parse(m_colidertext[1].text) + int.Parse(betamount) + "";
                    black_amount.text = black_amount_int.ToString();
                }
                else if (bet == "3")
                {
                    pair_amount_int += int.Parse(betamount);
                    // m_colidertext[2].text =
                    //     int.Parse(m_colidertext[2].text) + int.Parse(betamount) + "";
                    pair_amount.text = pair_amount_int.ToString();
                }
                else if (bet == "4")
                {
                    color_amount_int += int.Parse(betamount);
                    // m_colidertext[3].text =
                    //     int.Parse(m_colidertext[3].text) + int.Parse(betamount) + "";
                    color_amount.text = color_amount_int.ToString();
                }
                else if (bet == "5")
                {
                    seq_amount_int += int.Parse(betamount);
                    // m_colidertext[4].text =
                    //     int.Parse(m_colidertext[4].text) + int.Parse(betamount) + "";
                    seq_amount.text = seq_amount_int.ToString();
                }
                else if (bet == "6")
                {
                    pure_seq_amount_int += int.Parse(betamount);
                    // m_colidertext[5].text =
                    //     int.Parse(m_colidertext[5].text) + int.Parse(betamount) + "";
                    pure_seq_amount.text = pure_seq_amount_int.ToString();
                }
                else if (bet == "7")
                {
                    set_amount_int += int.Parse(betamount);
                    // m_colidertext[6].text =
                    //     int.Parse(m_colidertext[6].text) + int.Parse(betamount) + "";
                    set_amount.text = set_amount_int.ToString();
                }
            }
            GameBetUtil.UpdateButtonInteractability(
                Configuration.GetWallet(),
                buttons.ToList(),
                null,
                ref buttonclicked,
                ref currentParticle,
                gamestart,
                buttonclick,
                clickedbutton,
                coininstantate,
                ref betamount
            );
            //UpdateButtonInteractability(Configuration.GetWallet());
        }
    }

    public async void updatedata()
    {
        string url = Configuration.Url + Configuration.wallet;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        Wallet myResponse = await APIManager.Instance.Post<Wallet>(url, formData);
        if (myResponse.code == 200)
        {
            PlayerPrefs.SetString("wallet", myResponse.wallet);
            PlayerPrefs.Save();
            UserWalletText.text = CommonUtil.GetFormattedWallet();
        }
        else
        {
            CommonUtil.CheckLog("Error_new:" + myResponse.message);
        }
        /*       CommonUtil.CheckLog("RES+Message" + myResponse.message);
              CommonUtil.CheckLog("RES+Code" + myResponse.code); */
    }

    public void buttonclick(int num)
    {
        betamount = num.ToString();
    }

    public void clickedbutton(GameObject button)
    {
        foreach (GameObject buttons in btns)
        {
            buttons.transform.localScale = new Vector3(1, 1, 1);
        }
        button.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
        buttonclicked = button;
    }

    public void coininstantate(int index)
    {
        //CommonUtil.ShowToast("INDEX CLICKED:" + index);
        //betamount = index.ToString();
        num = index;

        foreach (ParticleSystem buttons in particleSystems)
        {
            buttons.gameObject.SetActive(false);
            buttons.Stop();
        }
        particleSystems[index].gameObject.SetActive(true);
        particleSystems[index].Play();
    }

    public void ClickedRed()
    {
        CommonUtil.CheckLog("RES Check " + RBdata.game_data[0].status);
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "1";

                if (betamount != null)
                    PlaceBet();

                var RandomCollider = m_ColliderList[0]; // means andar

                /*   var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                  var coin = poolManager.GetObject(); */
                var coin = Instantiate(
                    coins[num],
                    userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );

                AudioManager._instance.PlayCoinDrop();
                coin.transform.localPosition = Vector3.zero;

                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                    });

                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedBlack()
    {
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "2";
                if (betamount != null)
                    PlaceBet();

                var RandomCollider = m_ColliderList[1]; // means bahar
                var coin = Instantiate(
                    coins[num],
                    userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );
                /*    var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                   var coin = poolManager.GetObject(); */

                coin.transform.localPosition = Vector3.zero;
                AudioManager._instance.PlayCoinDrop();
                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                    });
            }
        }
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedPair()
    {
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "3";

                if (betamount != null)
                    PlaceBet();

                var RandomCollider = m_ColliderList[2]; // means pair
                var coin = Instantiate(
                    coins[num],
                    userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );
                /*    var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                   var coin = poolManager.GetObject(); */

                coin.transform.localPosition = Vector3.zero;
                AudioManager._instance.PlayCoinDrop();
                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                    });
            }
            else
            {
                showtoastmessage("Insufficient Balance");
            }
        }
    }

    public void Clickedcolor()
    {
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "4";

                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[3]; // means bahar
                    var coin = Instantiate(
                        coins[num],
                        userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                    );
                    /*    var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                       var coin = poolManager.GetObject(); */

                    coin.transform.localPosition = Vector3.zero;
                    AudioManager._instance.PlayCoinDrop();
                    m_DummyObjects.Add(coin.gameObject);
                    coin.transform.SetParent(RandomCollider.transform);
                    coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                    coin.transform.DOLocalMove(
                            GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                            0.8f
                        )
                        .OnComplete(() =>
                        {
                            coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                        });
                }
                else
                {
                    showtoastmessage("Insufficient Balance");
                }
            }
        }
        else
        {
            showtoastmessage("Insufficient balance" + betamount);
        }
    }

    public void ClickedSeq()
    {
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "5";

                if (betamount != null)
                    PlaceBet();

                var RandomCollider = m_ColliderList[5]; // means bahar
                var coin = Instantiate(
                    coins[num],
                    userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );
                /*    var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                   var coin = poolManager.GetObject(); */

                coin.transform.localPosition = Vector3.zero;
                AudioManager._instance.PlayCoinDrop();
                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                    });
            }
            else
            {
                showtoastmessage("Insufficient Balance");
            }
        }
    }

    public void ClickedPureSeq()
    {
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "6";

                if (betamount != null)
                    PlaceBet();

                var RandomCollider = m_ColliderList[4]; // means bahar
                var coin = Instantiate(
                    coins[num],
                    userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );
                /*    var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                   var coin = poolManager.GetObject(); */

                coin.transform.localPosition = Vector3.zero;
                AudioManager._instance.PlayCoinDrop();
                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                    });
            }
            else
            {
                showtoastmessage("Insufficient Balance");
            }
        }
    }

    public void ClickedSet()
    {
        if (betamount != "0")
        {
            if (RBdata.game_data[0].status == "0")
            {
                bet = "7";

                if (betamount != null)
                    PlaceBet();

                var RandomCollider = m_ColliderList[6]; // means bahar
                var coin = Instantiate(
                    coins[num],
                    userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );
                /*    var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                   var coin = poolManager.GetObject(); */

                coin.transform.localPosition = Vector3.zero;
                AudioManager._instance.PlayCoinDrop();
                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
                    });
            }
        }
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }
    #endregion

    #region result

    public async void GetResult()
    {
        string Url = RBConfig.RedAndBlackResult;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", RBdata.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + RBdata.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        rbresultdata = await APIManager.Instance.Post<RedAndBlackBetResult>(Url, formData);

        CommonUtil.CheckLog("Result Message" + rbresultdata.message);

        if (rbresultdata.code == 102)
        {
            AudioManager._instance.PlayWinSound();
            showtoastmessage("congratulation!! You Won " + rbresultdata.win_amount);
            CoinAnim(true);
        }
        else if (rbresultdata.code == 103)
        {
            if (rbresultdata.win_amount > 0)
            {
                AudioManager._instance.PlayWinSound();
                showtoastmessage("congratulation!! You Won " + rbresultdata.win_amount);
                CoinAnim(true);
            }
            else
            {
                AudioManager._instance.PlayLoseSound();
                showtoastmessage("Better Luck Next Time, You Lost " + rbresultdata.diff_amount);
                CoinAnim(false);
            }
        }
        else
        {
            CoinAnim(false);
        }
    }

    public void CoinAnim(bool won)
    {
        int userwincoins = 0;
        var hightlitedobjed = highlightlist[int.Parse(RBdata.game_data[0].winning_rule) - 2]
            .transform
            .parent
            .name;
        Debug.Log("WINBOBJECT NAME::" + hightlitedobjed);
        var maincollider = m_ColliderList.Find(x => x.transform.parent.name == hightlitedobjed);
        Debug.Log("Finded Colider NAME::" + hightlitedobjed);
        m_DummyObjects.ForEach(x =>
        {
            x.transform.localScale = new Vector3(0.3f, 0.3f, 0.3f);
            var oppos = m_ColliderList[int.Parse(RBdata.game_data[0].winning_rule) - 2].transform;
            x.transform.SetParent(oppos);
            x.transform.DOLocalMove(Vector3.zero, UnityEngine.Random.Range(0.5f, 0.9f))
                .OnComplete(() =>
                {
                    x.transform.SetParent(maincollider.transform);
                    x.transform.DOLocalMove(
                            GameBetUtil.GetRandomPositionInCollider(maincollider),
                            0.7f
                        )
                        .OnComplete(() =>
                        {
                            //if ((tigerint > 0 || sharkint > 0 || snakeint > 0 || wolfint > 0 || bearint > 0 || lionint > 0 || dolphinint > 0 || leopardint > 0) && userwincoins < 10)
                            if (won && userwincoins < 10)
                            {
                                userwincoins++;
                                x.transform.SetParent(
                                    userprofile
                                        .transform.GetChild(0)
                                        .GetChild(0)
                                        .GetChild(2)
                                        .transform
                                );
                                x.transform.DOLocalMove(Vector3.zero, .7f)
                                    .OnComplete(() =>
                                    {
                                        Destroy(x);
                                        m_DummyObjects.Remove(x);
                                        m_DummyObjects.RemoveAll(item => item == null);
                                    });
                            }
                            else
                            {
                                x.transform.SetParent(
                                    profiles[UnityEngine.Random.Range(0, profiles.Count)]
                                        .transform.GetChild(1)
                                        .transform
                                );
                                x.transform.DOLocalMove(Vector3.zero, .7f)
                                    .OnComplete(() =>
                                    {
                                        Destroy(x);
                                        m_DummyObjects.Remove(x);
                                        m_DummyObjects.RemoveAll(item => item == null);
                                    });
                            }
                        });

                    // x.transform.SetParent(profiles[UnityEngine.Random.Range(0, profiles.Count)].transform);
                    // x.transform.DOLocalMove(Vector3.zero, UnityEngine.Random.Range(0.5f, 0.9f));
                });
        });
    }

    public Transform m_FirstMovePosition;

    #endregion
}
