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

public class BaccaratManager : MonoBehaviour
{
    private string CustomNamespace = "/baccarat";

    [Header("last winning Sprite Data")]
    public List<string> lastwinning;
    public List<GameObject> highlights;
    public List<GameObject> highlights2;
    public List<Sprite> lastwinningsprite;
    public List<GameObject> lastwinningimagestoshow;
    private SocketManager Manager;

    public BacRoot baccarat_data;
    public List<Sprite> cards;
    public List<SpriteRenderer> main_cards;
    public Sprite back_card;
    public List<Sprite> allcards;
    public TextMeshProUGUI timertext;
    public SpriteRenderer maincard;
    public SpriteRenderer maincard2;
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
    public Image andar,
        bahar;
    public int totalcoinsleft,
        randomecount,
        left1,
        left2;
    public List<Sprite> profileimages;
    public List<GameObject> cariconsforcoins;
    public TextMeshProUGUI b_pair,
        p_pair,
        player,
        tie,
        banker;
    public int bpairamountint,
        ppairamountint,
        playeramountint,
        tieamountint,
        bankeramountint;
    public bool check,
        showrecord,
        online;
    public GameObject bl2;
    public Animator onlineuser;
    public AndarBaharBetResult bacresultdata;
    public GameObject historyprediction;
    public List<string> lastwinningprediction;
    public List<GameObject> lastwinningpredictionimagestoshow;
    public float andarint,
        baharint;
    public TextMeshProUGUI andaramounttext,
        baharamounttext,
        andarpredictiontext,
        baharpredictiontext;
    public Slider abpredictionslider;
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

    public Transform startPosition;
    public Transform[] endPositions;
    public List<Transform> cardsRotateList;
    public bool show_history;
    private int poolSize = 50;
    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    public ParticleSystem currentParticle;
    #endregion

    private void OnEnable()
    {
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();

        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;

        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        CommonUtil.CheckLog("RES_CHECK Socket URL Andar bahar+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("baccarat_timer", OnBaccarat_timerResponse);
        customNamespace.On<string>("baccarat_status", OnBaccarat_statusResponse);

        Manager.Open();

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);
        show_history = false;
        particleSystems[0].gameObject.SetActive(true);
        particleSystems[0].Play();
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
        // buttonclick(0);
        clickedbutton(buttons[0].gameObject);
        coininstantate(0);
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
            // StopCoroutine(GameBetUtil.OnlineBet(coins, m_ColliderList, onlineuser, baccarat_data.game_data[0].status, timertext.text, m_DummyObjects));
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
        customNamespace.On<string>("baccarat_timer", OnBaccarat_timerResponse);
        customNamespace.On<string>("baccarat_status", OnBaccarat_statusResponse);
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

    private void ClearGameObjects()
    {
        foreach (GameObject coin in m_DummyObjects)
        {
            Destroy(coin);
        }
        m_DummyObjects.Clear();
    }

    #endregion

    void OnConnected(ConnectResponse resp)
    {
        invoke = true;
        CommonUtil.CheckLog("RES_CHECK On - Connected + " + resp.sid);
        GetTimer();
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
        for (int i = 0; i < baccarat_data.last_winning.Count; i++)
        {
            lastwinning.Add(baccarat_data.last_winning[i].winning);
        }

        for (int i = 0; i < lastwinning.Count; i++)
        {
            //CommonUtil.CheckLog("RES_Check + Last winning");
            if (i < lastwinningimagestoshow.Count)
            {
                if (lastwinning[i] == "0")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[0];
                else if (lastwinning[i] == "1")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[1];
                else if (lastwinning[i] == "2")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[2];
            }
        }
    }

    private void ResetAmounts(bool isSameGame)
    {
        if (isSameGame)
        {
            CommonUtil.CheckLog("My Amount true");
            // textamount[int.Parse(bettype)].text = amount[int.Parse(bettype)].ToString();
            p_pair.text = ppairamountint.ToString();
            b_pair.text = bpairamountint.ToString();
            player.text = playeramountint.ToString();
            tie.text = tieamountint.ToString();
            banker.text = bankeramountint.ToString();
        }
        else
        {
            CommonUtil.CheckLog("My Amount false");
            ppairamountint = 0;
            bpairamountint = 0;
            playeramountint = 0;
            tieamountint = 0;
            bankeramountint = 0;
            p_pair.text = "0";
            b_pair.text = "0";
            player.text = "0";
            tie.text = "0";
            banker.text = "0";
            for (int i = 0; i < m_colidertext.Count; i++)
            {
                m_colidertext[i].text = "0";
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
            m_Bots[i].BotName.text = baccarat_data.bot_user[i].name;
            m_Bots[i].BotCoin.text = baccarat_data.bot_user[i].coin;
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + baccarat_data.bot_user[i].avatar
            );
        }

        /* for (int i = 0; i < profiles.Count; i++)
        {
            profiles[i].transform.gameObject.SetActive(true);
            //CommonUtil.CheckLog("Res_Check 1");
            profiles[i].transform.GetChild(0).gameObject.SetActive(true);
            profiles[i].transform.GetChild(2).gameObject.SetActive(false);


            profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = baccarat_data.bot_user[i].name;
            profiles[i].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(baccarat_data.bot_user[i].coin);
            Image img = profiles[i].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>();
            img.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(Configuration.ProfileImage + baccarat_data.bot_user[i].avatar);
            //StartCoroutine(DownloadImage(baccarat_data.bot_user[i].avatar, img));
        } */
    }

    #endregion

    #region ABHistoryPrediction

    public void ShowHistoryPrediction()
    {
        lastwinningprediction.Clear();
        andarint = 0;
        baharint = 0;
        for (int i = 0; i < baccarat_data.last_winning.Count; i++)
        {
            //lastwinningprediction.Add(baccarat_data.last_winning[i].winning);
            if (baccarat_data.last_winning[i].winning == "0")
            {
                lastwinningpredictionimagestoshow[i].transform.GetComponent<Image>().sprite =
                    lastwinningsprite[0];
                andarint++;
            }
            else if (baccarat_data.last_winning[i].winning == "1")
            {
                lastwinningpredictionimagestoshow[i].transform.GetComponent<Image>().sprite =
                    lastwinningsprite[1];
                baharint++;
            }
        }

        andaramounttext.text = andarint.ToString();
        baharamounttext.text = baharint.ToString();

        float value = (andarint / 20);
        float percentage = value * 100;

        CommonUtil.CheckLog("RES_Check + Andar amount " + andarint);
        CommonUtil.CheckLog("RES_Check + Andar value " + value);
        CommonUtil.CheckLog("RES_Check + Andar Percentage " + percentage);

        abpredictionslider.value = percentage;

        andarpredictiontext.text = percentage.ToString() + "%";
        baharpredictiontext.text = (100 - percentage).ToString() + "%";

        historyprediction.SetActive(true);
    }

    #endregion

    #region socket status
    private void OnBaccarat_statusResponse(string args)
    {
        //      CommonUtil.CheckLog("RES_CHECK Onander_bahar_statusResponse ");
        //        CommonUtil.CheckLog("RES_VALUE Status Game Json :" + args);
        try
        {
            baccarat_data = JsonUtility.FromJson<BacRoot>(args);
            if (!show_history)
            {
                show_history = true;
                ShowLast10Win();
            }
            updateprofile();
            if (baccarat_data.game_data[0].status == "0")
            {
                //   Debug.Log("status" + baccarat_data.game_data[0].status);
                string game_id = Configuration.getbacid();
                bool isSameGame = game_id == baccarat_data.game_data[0].id;
                ResetAmounts(isSameGame);
                if (!online)
                {
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
                PlayerPrefs.SetString("bacid", baccarat_data.game_data[0].id);
            }
            else
            {
                /*  CommonUtil.ShowToast(
                     "winning: "
                         + baccarat_data.game_data[0].winning
                         + " , Game Id: "
                         + baccarat_data.game_data[0].id
                 ); */
                //  Debug.Log("Status" + baccarat_data.game_data[0].status);
                Debug.Log("STOP BET:");
                if (online)
                {
                    online = false;
                    GameBetUtil.StopBet();
                }
            }

            if (timertext.text == "1" && invoke)
            {
                cards.Clear();
                timertext.text = "0";
                if (baccarat_data.game_cards.Count != 0)
                {
                    for (int i = 0; i < baccarat_data.game_cards.Count; i++)
                    {
                        string cardname = baccarat_data.game_cards[i].card.ToLower();
                        bool found = false;
                        for (int j = 0; j < allcards.Count; j++)
                        {
                            if (allcards[j].name.ToLower() == cardname) // Comparing names in a case-insensitive manner
                            {
                                cards.Add(allcards[j]);
                                found = true; // Set flag to true if a matching card is found
                            }
                        }
                    }
                    int num = 0;
                }
                onlineuser.enabled = false; //(onlineuser is Animator)
                if (baccarat_data.game_data[0].status == "1")
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

        StartCoroutine(highlightwin());
        for (int i = 0; i < main_cards.Count; i++)
        {
            StartCoroutine(
                CardUtil.AnimateFlipCard(
                    main_cards[i].transform,
                    main_cards[i],
                    cards[i],
                    0.5f,
                    -2,
                    180
                )
            );
        }
        // //StartCoroutine(patti.MoveAllCards());
        // StartCoroutine(startcards());
    }

    public IEnumerator changetext()
    {
        //yield return new WaitForSeconds(1);
        timertext.text = "0";
        yield return null;
    }

    IEnumerator anim()
    {
        maincard.gameObject.SetActive(false);
        maincard2.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.3f);
        maincard.gameObject.SetActive(true);
        maincard2.gameObject.SetActive(false);
    }
    #endregion

    #region Timer
    private void GetTimer()
    {
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("ander_bahar_timer", "ander_bahar_timer");

            // CommonUtil.CheckLog("RES_CHECK" + " EMIT-ander_bahar_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnBaccarat_timerResponse(string args)
    {
        //        CommonUtil.CheckLog("RES_CHECK Timmer:" + args);
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

            DOVirtual.DelayedCall(
                .5f,
                () =>
                {
                    /*   gameaudio.clip = placebet;
                      gameaudio.Play(); */
                    AudioManager._instance.PlayPlaceBetSound();
                    //StartCoroutine(CommonUtil.LoadAndPlayAudio(placebet, gameaudio));
                    stoptext.text = "PLACE BET";
                    showstop.SetActive(true);
                    DOVirtual.DelayedCall(
                        2f,
                        () =>
                        {
                            showstop.SetActive(false);
                        }
                    );
                }
            );

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
            //UpdateButtonInteractability(Configuration.GetWallet());
        }
    }
    #endregion

    #region win functions
    IEnumerator highlightwin()
    {
        yield return new WaitForSeconds(2.5f);
        updatedata();
        totalcoinsleft = 0;

        StartCoroutine(HighlightWinning(int.Parse(baccarat_data.game_data[0].winning)));
        if (int.Parse(baccarat_data.game_data[0].player_pair) == 1)
            StartCoroutine(HighlightWinning2(0));
        if (int.Parse(baccarat_data.game_data[0].banker_pair) == 1)
            StartCoroutine(HighlightWinning2(1));

        for (int i = 0; i < main_cards.Count; i++)
        {
            StartCoroutine(
                CardUtil.AnimateFlipCard(
                    main_cards[i].transform,
                    main_cards[i],
                    back_card,
                    0.5f,
                    2,
                    0
                )
            );
        }
        GetResult();
        b_pair.text = "0";
        p_pair.text = "0";
        player.text = "0";
        tie.text = "0";
        banker.text = "0";
        for (int i = 0; i < m_colidertext.Count; i++)
        {
            m_colidertext[i].text = "0";
        }
        showrecord = false;
        check = false;
        yield return new WaitForSeconds(2f);
        ppairamountint = 0;
        bpairamountint = 0;
        playeramountint = 0;
        tieamountint = 0;
        bankeramountint = 0;
        ShowLast10Win();
    }

    public IEnumerator HighlightWinning(int num)
    {
        AudioManager._instance.PlayHighlightWinSound();
        highlights[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights[num].gameObject.SetActive(false);
    }

    public IEnumerator HighlightWinning2(int num)
    {
        AudioManager._instance.PlayHighlightWinSound();
        highlights2[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        highlights2[num].gameObject.SetActive(false);
    }

    IEnumerator startcards()
    {
        yield return new WaitForSeconds(8);
        if (cardsRotateList.Count != 0)
        {
            CommonUtil.CheckLog("Enter 2");
            //foreach (Transform cards in patti.cards)
            //    patti.gameObject.GetComponent<ReversePatti>().cards.Add(cards);
            //StartCoroutine(patti.gameObject.GetComponent<ReversePatti>().MoveAllCards());
            //yield return new WaitForSeconds(2);
            foreach (Transform cards in cardsRotateList)
                cards.gameObject.SetActive(false);
            cardsRotateList.Clear();
            foreach (GameObject coin in m_DummyObjects)
            {
                Destroy(coin);
            }
            m_DummyObjects.Clear();
        }
    }

    #endregion

    #region buttons_Functionality
    public async void PlaceBet()
    {
        string url = BacarratConfig.BaccaratPutBet;

        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", baccarat_data.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        JsonResponse jsonResponse = new JsonResponse();
        jsonResponse = await APIManager.Instance.Post<JsonResponse>(url, formData);

        Debug.Log("BetAmount:" + betamount);
        if (jsonResponse.code == 406)
        {
            CommonUtil.CheckLog("Putbet:" + jsonResponse.message);
        }
        else
        {
            string walletString = jsonResponse.wallet;
            PlayerPrefs.SetString("wallet", jsonResponse.wallet);
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            if (jsonResponse.code == 200)
            {
                if (bet == "0")
                {
                    playeramountint += int.Parse(betamount);
                    // m_colidertext[0].text =
                    //     int.Parse(m_colidertext[0].text) + int.Parse(betamount) + "";
                    player.text = playeramountint.ToString();
                }
                else if (bet == "1")
                {
                    bankeramountint += int.Parse(betamount);
                    // m_colidertext[1].text =
                    //     int.Parse(m_colidertext[1].text) + int.Parse(betamount) + "";
                    banker.text = bankeramountint.ToString();
                }
                else if (bet == "2")
                {
                    tieamountint += int.Parse(betamount);
                    // m_colidertext[2].text =
                    //     int.Parse(m_colidertext[2].text) + int.Parse(betamount) + "";
                    tie.text = tieamountint.ToString();
                }
                else if (bet == "3")
                {
                    ppairamountint += int.Parse(betamount);
                    // m_colidertext[3].text =
                    //     int.Parse(m_colidertext[3].text) + int.Parse(betamount) + "";
                    p_pair.text = ppairamountint.ToString();
                    CommonUtil.CheckLog("my bet " + ppairamountint);
                }
                else if (bet == "4")
                {
                    bpairamountint += int.Parse(betamount);
                    // m_colidertext[4].text =
                    //     int.Parse(m_colidertext[4].text) + int.Parse(betamount) + "";
                    b_pair.text = bpairamountint.ToString();
                }
                Debug.Log("PutbetSuccess:");
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

    public void buttonclick(int ClickNumber)
    {
        //num = ClickNumber;
        betamount = ClickNumber.ToString();
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
        num = index;
        foreach (ParticleSystem buttons in particleSystems)
        {
            buttons.gameObject.SetActive(false);
            buttons.Stop();
        }
        particleSystems[index].gameObject.SetActive(true);
        particleSystems[index].Play();
        //betamount = index.ToString();
    }

    public void ClickedPPair()
    {
        CommonUtil.CheckLog("RES Check " + baccarat_data.game_data[0].status);
        if (betamount != "0")
        {
            if (baccarat_data.game_data[0].status == "0")
            {
                bet = "3";
                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[0];

                    /*   var poolManager = coins[num].GetComponent<ObjectPoolUtil>();
                      var coin = poolManager.GetObject(); */
                    CommonUtil.CheckLog("Check Coins " + coins[num]);
                    CommonUtil.CheckLog(
                        "userprofile " + userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                    );
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
                }
                else
                {
                    CommonUtil.ShowToast("Insufficient Balance");
                }
                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            CommonUtil.ShowToast("Insufficient balance");
        }
    }

    public void ClickedBPair()
    {
        CommonUtil.CheckLog("RES Check " + baccarat_data.game_data[0].status);
        if (betamount != "0")
        {
            if (baccarat_data.game_data[0].status == "0")
            {
                bet = "4";
                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[1];

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
                }
                else
                {
                    CommonUtil.ShowToast("Insufficient Balance");
                }
                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            CommonUtil.ShowToast("Insufficient balance");
        }
    }

    public void ClickedPlayer()
    {
        CommonUtil.CheckLog("RES Check " + baccarat_data.game_data[0].status);
        if (betamount != "0")
        {
            if (baccarat_data.game_data[0].status == "0")
            {
                bet = "0";
                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[2];

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
                }
                else
                {
                    CommonUtil.ShowToast("Insufficient Balance");
                }
                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            CommonUtil.ShowToast("Insufficient balance");
        }
    }

    public void ClickedTie()
    {
        CommonUtil.CheckLog("RES Check " + baccarat_data.game_data[0].status);
        if (betamount != "2")
        {
            if (baccarat_data.game_data[0].status == "0")
            {
                bet = "2";
                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[3];

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
                }
                else
                {
                    CommonUtil.ShowToast("Insufficient Balance");
                }
                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            CommonUtil.ShowToast("Insufficient balance");
        }
    }

    public void ClickedBanker()
    {
        CommonUtil.CheckLog("RES Check " + baccarat_data.game_data[0].status);
        if (betamount != "0")
        {
            if (baccarat_data.game_data[0].status == "0")
            {
                bet = "1";
                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[4];

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
                }
                else
                {
                    CommonUtil.ShowToast("Insufficient Balance");
                }
                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            CommonUtil.ShowToast("Insufficient balance");
        }
    }
    #endregion

    #region result

    public async void GetResult()
    {
        string Url = BacarratConfig.BaccaratResult;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", baccarat_data.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + baccarat_data.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        BaccaretBetResult bacresultdata = await APIManager.Instance.Post<BaccaretBetResult>(
            Url,
            formData
        );

        CommonUtil.CheckLog("Result Message" + bacresultdata.message);

        if (bacresultdata.code == 102)
        {
            AudioManager._instance.PlayWinSound();
            CommonUtil.ShowToast("congratulation!! You Won " + bacresultdata.win_amount);
            CoinAnim(true);
        }
        else if (bacresultdata.code == 103)
        {
            if (bacresultdata.win_amount > 0)
            {
                AudioManager._instance.PlayWinSound();
                CommonUtil.ShowToast("congratulation!! You Won " + bacresultdata.win_amount);
                CoinAnim(true);
            }
            else
            {
                AudioManager._instance.PlayLoseSound();
                CommonUtil.ShowToast(
                    "Better Luck Next Time, You Lost " + bacresultdata.diff_amount
                );
                CoinAnim(false);
            }
        }
        else
        {
            CoinAnim(false);
            ///CommonUtil.ShowToast(bacresultdata.message);
        }
    }

    public void CoinAnim(bool won)
    {
        int userwincoins = 0;
        var hightlitedobjed = highlights[int.Parse(baccarat_data.game_data[0].winning)]
            .transform
            .parent
            .name;
        Debug.Log("WINBOBJECT NAME::" + hightlitedobjed);
        var maincollider = m_ColliderList.Find(x => x.transform.parent.name == hightlitedobjed);
        Debug.Log("Finded Colider NAME::" + hightlitedobjed);
        m_DummyObjects.ForEach(x =>
        {
            x.transform.localScale = new Vector3(0.3f, 0.3f, 0.3f);
            var oppos = cariconsforcoins[int.Parse(baccarat_data.game_data[0].winning)].transform;
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
