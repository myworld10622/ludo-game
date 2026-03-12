using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyUI.Toast;
using Mkey;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class HTManager : MonoBehaviour
{
    private string CustomNamespace = "/head_tail";
    private SocketManager Manager;

    [Header("Status data")]
    public HTRootObject HTData;

    [Header("Round Timer Text")]
    public TextMeshProUGUI timertext;

    [Header("Game Users")]
    public GameObject Userprofile;
    public List<GameObject> profiles;
    public List<BotsUser> m_Bots = new List<BotsUser>();
    public Animator onlineuser;

    [Header("HT Last Winning Data")]
    public List<Sprite> HT_Listsprites;
    public List<string> lastwinning;
    public List<Image> lastwinningdata;

    public List<GameObject> headobj,
        tailobj;

    [Header("Coins")]
    public List<GameObject> coins;

    [Header("Buttons")]
    public List<GameObject> btns;

    [Header("put bet json variables")]
    public RBPutBetResponse betresp;
    public bool putbetbool;
    public string resultString;

    [Header("Win Objects")]
    public GameObject head;
    public GameObject tail;

    [Header("Coin Animator")]
    public Animator anim;
    public GameObject dummycoin,
        dummycointail;

    [Header("my bet texts")]
    public TextMeshProUGUI headamount;
    public TextMeshProUGUI tailamount;
    public int headint,
        tailint;

    private bool gamestart,
        GameEnded,
        click,
        showrecord,
        online;
    public int num;
    public string betamount;
    public string bet;
    public GameObject buttonclicked;
    private int totalcoinsleft;
    public GameObject Reconn_Canvas;
    public HeadAndtailBetResult HeadTailsdata;
    public Button[] buttons;
    public GameObject showstop;
    public Text stopbet;
    public bool showlastdata,
        reconnected,
        invoke = true;
    public int timetoinvoke;
    public Transform m_coinMoveposition;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;
    public Toggle musicToggle;
    public Toggle soundToggle;

    private void OnEnable()
    {
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();
        UserWalletText.text =
            decimal.TryParse(Configuration.GetWallet(), out decimal userCoins) && userCoins > 10000
                ? FormatNumber(walletString)
                : walletString;
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("head_tail_timer", OnHeadTail_TimerResponse);
        customNamespace.On<string>("head_tail_status", OnHeadTail_statusResponse);
        Manager.Open();

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

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
        foreach (ParticleSystem particle in particleSystems)
        {
            if (particle.isPlaying)
            {
                particle.Stop();
            }
        }
        OnButtonClickPartical(0);
        GameBetUtil.UpdateButtonInteractability(
            Configuration.GetWallet(),
            buttons.ToList(),
            particleSystems.ToList(),
            ref buttonclicked,
            ref currentParticle,
            gamestart,
            buttonclick,
            clickedbutton,
            coininstantate,
            ref betamount
        );
    }

    string FormatNumber(string number)
    {
        if (float.Parse(number) >= 1000 && float.Parse(number) < 10000)
        {
            return (float.Parse(number) / 1000f).ToString("0.0") + "k";
        }
        else if (float.Parse(number) >= 10000)
        {
            return (float.Parse(number) / 1000).ToString("0.#") + "k";
        }
        else
        {
            return number.ToString();
        }
    }

    #region Socket Connection/DisConnection
    void OnApplicationPause(bool pauseStatus)
    {
        if (!pauseStatus)
        {
            Debug.Log("RES_Check + resume");
            RequestGameStateUpdate();
        }
        else
        {
            // StopCoroutine(aibet());
            // StopCoroutine(onlinebet());
        }
    }

    private void RequestGameStateUpdate()
    {
        invoke = false;
        Manager.Close();
        // StopCoroutine(aibet());
        //StopCoroutine(onlinebet());
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("head_tail_timer", OnHeadTail_TimerResponse);
        customNamespace.On<string>("head_tail_status", OnHeadTail_statusResponse);
        Manager.Open();
        GetTimer();
        reconnected = true;
        StartCoroutine(invokefortime());

        GameBetUtil.UpdateButtonInteractability(
            Configuration.GetWallet(),
            buttons.ToList(),
            particleSystems.ToList(),
            ref buttonclicked,
            ref currentParticle,
            gamestart,
            buttonclick,
            clickedbutton,
            coininstantate,
            ref betamount
        );
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

    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("Connect : " + resp.sid);
        //Reconn_Canvas.SetActive(false);
        GetTimer();
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
        StopAllCoroutines();
        Debug.Log("Disconnected: ");
        AudioManager._instance.StopEffect();
        SceneLoader.Instance.LoadScene("HomePage"); // 2 mean homepage
    }
    #endregion
    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("head_tail_timer", "head_tail_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-head_tail_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnHeadTail_TimerResponse(string args)
    {
        Debug.Log("RES_Value + Timer Game Json :" + args);
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
        //showwaitanim.SetActive(false);
        //putbetanim.SetActive(true);
        if (!gamestart)
        {
            //  StartCoroutine(startdHT());
            displaylastwin();
            foreach (var btn in buttons)
            {
                btn.interactable = true;
            }
            gamestart = true;
            GameBetUtil.UpdateButtonInteractability(
                Configuration.GetWallet(),
                buttons.ToList(),
                particleSystems.ToList(),
                ref buttonclicked,
                ref currentParticle,
                gamestart,
                buttonclick,
                clickedbutton,
                coininstantate,
                ref betamount
            );
        }
        // online = false;
    }
    #endregion
    #region Head_Tail_Status
    private void OnHeadTail_statusResponse(string args)
    {
        Debug.Log("RES_Check + OnHeadTail_statusResponse: " + args);
        try
        {
            HTData = JsonUtility.FromJson<HTRootObject>(args);
            displayprofiles();
            if (HTData == null)
            {
                Debug.LogError("HTData is null");
                return;
            }

            if (!showlastdata)
            {
                displaylastwin();
                showlastdata = true;
            }

            if (HTData.game_data[0].status == "0")
            {
                string game_id = Configuration.gethntid();
                bool isSameGame = game_id == HTData.game_data[0].id;

                if (reconnected)
                {
                    //ResetAmounts();
                    ClearGameObjects();
                    reconnected = false;
                    GameEnded = false;
                }
                PlayerPrefs.SetString("htid", HTData.game_data[0].id);
                headamount.text = HTData.my_head_bet.ToString();
                tailamount.text = HTData.my_tail_bet.ToString();
                if (!online)
                {
                    online = true;
                    StartCoroutine(startdHT());
                    Debug.Log(online + "status" + HTData.game_data[0].status);
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
            }
            else
            {
                Debug.Log(online + "Status:" + HTData.game_data[0].status);
                if (online)
                {
                    online = false;
                    GameBetUtil.StopBet();
                    GameBetUtil.UpdateButtonInteractability(
                        Configuration.GetWallet(),
                        buttons.ToList(),
                        particleSystems.ToList(),
                        ref buttonclicked,
                        ref currentParticle,
                        gamestart,
                        buttonclick,
                        clickedbutton,
                        coininstantate,
                        ref betamount
                    );
                    GameEnded = true;
                    StartCoroutine(stopdHT());
                }
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError($"Exception: {ex.Message}\nStack Trace: {ex.StackTrace}");
        }
    }

    IEnumerator stopdHT()
    {
        AudioManager._instance.PlayStopBetSound();
        stopbet.text = "STOP BET";
        showstop.SetActive(true);
        yield return new WaitForSeconds(2.1f);
        showstop.SetActive(false);
        CommonUtil.ShowToast(
            "winning: " + HTData.game_data[0].winning + " , Game Id: " + HTData.game_data[0].id
        );
        StartCoroutine(Diplaywinanim());
        StartCoroutine(gamewin());
    }

    IEnumerator startdHT()
    {
        AudioManager._instance.PlayPlaceBetSound();
        stopbet.text = "PLACE BET";
        showstop.SetActive(true);
        yield return new WaitForSeconds(2.1f);
        showstop.SetActive(false);
    }

    IEnumerator gamewin()
    {
        yield return StartCoroutine(win());
    }

    private void ClearGameObjects()
    {
        ClearObjectList(headobj);
        ClearObjectList(tailobj);

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
            string walletString = Configuration.GetWallet();
            UserWalletText.text =
                decimal.TryParse(Configuration.GetWallet(), out decimal userCoins)
                && userCoins > 10000
                    ? FormatNumber(walletString)
                    : walletString;
        }
        else
        {
            Debug.Log("Error_new:" + myResponse.message);
        }
        Debug.Log("RES+Message" + myResponse.message);
        Debug.Log("RES+Code" + myResponse.code);
    }
    /*   public void showtoastmessage(string message)
      {
          Toast.Show(message, 3f);
      } */
    #endregion
    #region Head_Tail Status related functions
    public void displaylastwin()
    {
        lastwinning.Clear();
        if (HTData.last_winning.Length > 0)
        {
            for (int i = 0; i < HTData.last_winning.Length; i++)
            {
                lastwinning.Add(HTData.last_winning[i].winning);
            }

            for (int i = 0; i < lastwinningdata.Count; i++)
            {
                if (lastwinning.Count >= i)
                {
                    if (lastwinning[i] == "0")
                        lastwinningdata[i].GetComponent<Image>().sprite = HT_Listsprites[0];
                    else
                        lastwinningdata[i].GetComponent<Image>().sprite = HT_Listsprites[1];
                }
            }
        }
    }

    public async void displayprofiles()
    {
        if (!click)
        {
            UserNameText.text = Configuration.GetName();

            string walletString = Configuration.GetWallet();
            UserWalletText.text =
                decimal.TryParse(Configuration.GetWallet(), out decimal userCoins)
                && userCoins > 10000
                    ? FormatNumber(walletString)
                    : walletString;
            click = true;
        }
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].BotName.text = HTData.bot_user[i].name;
            m_Bots[i].BotCoin.text = FormatNumber(HTData.bot_user[i].coin);
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + HTData.bot_user[i].avatar
            );
        }
    }
    #endregion
    #region Aibet

    public List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<TextMeshProUGUI> m_colidertext = new List<TextMeshProUGUI> { };

    #endregion
    #region Put_Bet
    async void PlaceBet()
    {
        string url = HeadTailConfig.HeadTailPutBet;

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", HTData.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        Debug.Log(Configuration.GetId() + " PutBet ID Data");
        Debug.Log(HTData.game_data[0].id + " PutBet GameID Data");

        betresp = await APIManager.Instance.Post<RBPutBetResponse>(url, formData);
        Debug.Log("RES_Check + PlaceBet :" + betresp.message);
        Debug.Log("RES_Check + PlaceBet :" + betresp.code);
        if (betresp.code == 406)
        {
            headamount.text = HTData.my_head_bet.ToString();
            tailamount.text = HTData.my_tail_bet.ToString();
            if (bet == "0")
            {
                Debug.Log("RES_Check + insufficient balance for head");
            }
            else if (bet == "1") { }
            Debug.Log("RES_Check + PlaceBet : 406" + betresp);
            CommonUtil.ShowToast(betresp.message);
            putbetbool = false;
        }
        else
        {
            if (betresp.code == 200)
            {
                PlayerPrefs.SetString("wallet", betresp.wallet);
                PlayerPrefs.Save();
                putbetbool = true;
                string walletString = Configuration.GetWallet();
                UserWalletText.text =
                    decimal.TryParse(Configuration.GetWallet(), out decimal userCoins)
                    && userCoins > 10000
                        ? FormatNumber(walletString)
                        : walletString;
            }
            GameBetUtil.UpdateButtonInteractability(
                Configuration.GetWallet(),
                buttons.ToList(),
                particleSystems.ToList(),
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

    public void buttonclick(int num)
    {
        click = true;
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
        num = index;
    }

    public void ClickedHead()
    {
        if (betamount != "0")
        {
            if (gamestart)
            {
                if (HTData.game_data[0].status == "0")
                {
                    if (float.Parse(Configuration.GetWallet()) >= float.Parse(betamount))
                    {
                        bet = "0";
                        if (betamount != null)
                            PlaceBet();

                        if (bet == "0")
                        {
                            headint += int.Parse(betamount);
                            m_colidertext[0].text =
                                int.Parse(m_colidertext[0].text) + int.Parse(betamount) + "";
                            headamount.text = headint.ToString();
                        }
                        else if (bet == "1")
                        {
                            tailint += int.Parse(betamount);
                            m_colidertext[1].text =
                                int.Parse(m_colidertext[1].text) + int.Parse(betamount) + "";
                            tailamount.text = tailint.ToString();
                        }

                        // NewAudioManager.instance.betaudiosource.clip = NewAudioManager.instance.coinsoundclip;
                        // NewAudioManager.instance.betaudiosource.Play();
                        var RandomCollider = m_ColliderList[0]; // means Dragon
                        var coin = Instantiate(
                            coins[num],
                            Userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                        );

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
                        GameBetUtil.UpdateButtonInteractability(
                            Configuration.GetWallet(),
                            buttons.ToList(),
                            particleSystems.ToList(),
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
                else
                {
                    GameBetUtil.UpdateButtonInteractability(
                        Configuration.GetWallet(),
                        buttons.ToList(),
                        particleSystems.ToList(),
                        ref buttonclicked,
                        ref currentParticle,
                        gamestart,
                        buttonclick,
                        clickedbutton,
                        coininstantate,
                        ref betamount
                    );
                    // CommonUtil.UpdateButtonInteractability(Configuration.wallet);
                    // showtoastmessage("Insufficient Balance");
                }
            }
        }
        else
        {
            // UpdateButtonInteractability(Configuration.wallet);
            // showtoastmessage("Insufficient Balance");
        }
    }

    public void ClickedTail()
    {
        if (betamount != "0")
        {
            if (gamestart)
            {
                if (HTData.game_data[0].status == "0")
                {
                    if (float.Parse(Configuration.GetWallet()) >= float.Parse(betamount))
                    {
                        bet = "1";
                        if (betamount != null)
                            PlaceBet();

                        if (bet == "0")
                        {
                            headint += int.Parse(betamount);
                            m_colidertext[0].text =
                                int.Parse(m_colidertext[0].text) + int.Parse(betamount) + "";
                            headamount.text = headint.ToString();
                        }
                        else if (bet == "1")
                        {
                            tailint += int.Parse(betamount);
                            m_colidertext[1].text =
                                int.Parse(m_colidertext[1].text) + int.Parse(betamount) + "";
                            tailamount.text = tailint.ToString();
                        }

                        var RandomCollider = m_ColliderList[1]; // means tiger
                        var coin = Instantiate(
                            coins[num],
                            Userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                        );

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
                        GameBetUtil.UpdateButtonInteractability(
                            Configuration.GetWallet(),
                            buttons.ToList(),
                            particleSystems.ToList(),
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
            }
            else
            {
                GameBetUtil.UpdateButtonInteractability(
                    Configuration.GetWallet(),
                    buttons.ToList(),
                    particleSystems.ToList(),
                    ref buttonclicked,
                    ref currentParticle,
                    gamestart,
                    buttonclick,
                    clickedbutton,
                    coininstantate,
                    ref betamount
                );
                //   UpdateButtonInteractability(Configuration.wallet);
            }
        }
    }
    #endregion
    #region win related functions
    public IEnumerator Diplaywinanim()
    {
        anim.gameObject.SetActive(true);

        AudioManager._instance.PlayCoinFlipSound();
        dummycoin.SetActive(false);
        dummycointail.SetActive(false);
        if (HTData.game_data[0].winning == "0")
            anim.SetBool("Head", true);
        else
            anim.SetBool("Head", false);
        yield return new WaitForSeconds(3f);

        AudioManager._instance.PlayHighlightWinSound();
        /* gameaudio.clip = roundwinner;
        gameaudio.Play(); */
        if (HTData.game_data[0].winning == "0")
        {
            StartCoroutine(HighlightWinner(head));
            anim.gameObject.SetActive(false);
            dummycoin.SetActive(true);
        }
        else if (HTData.game_data[0].winning == "1")
        {
            StartCoroutine(HighlightWinner(tail));
            anim.gameObject.SetActive(false);
            dummycointail.SetActive(true);
        }
    }

    IEnumerator HighlightWinner(GameObject obj)
    {
        obj.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(false);
    }

    IEnumerator win()
    {
        timertext.text = "0";
        yield return new WaitForSeconds(1f); //this was above timer text
        onlineuser.enabled = false;
        yield return new WaitForSeconds(4f);
        GetResult();
        totalcoinsleft = 0;
        //MoveAllcoinsintoTop();
        GameBetUtil.MoveAllCoinsIntoTop(
            m_DummyObjects,
            m_coinMoveposition.transform,
            m_ColliderList,
            HTData.game_data[0].winning,
            headint,
            tailint,
            Userprofile,
            profiles,
            () => { }
        );
        headamount.text = "0";
        tailamount.text = "0";
        m_colidertext[0].text = "0";
        m_colidertext[1].text = "0";

        yield return new WaitForSeconds(2f);

        headint = 0;
        tailint = 0;
        showrecord = false;
        //showwaitanim.SetActive(true);
        displaylastwin();
        GetTimer();
    }
    #endregion
    #region result
    public async void GetResult()
    {
        string Url = HeadTailConfig.HeadTailResult;
        Debug.Log("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", HTData.game_data[0].id },
        };
        Debug.Log(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + HTData.game_data[0].id
        );
        HeadTailsdata = await APIManager.Instance.Post<HeadAndtailBetResult>(Url, formData);
        Debug.Log($"Head Tail Result {HeadTailsdata.message}");

        if (HeadTailsdata.code == 102)
        {
            AudioManager._instance.PlayWinSound();
            CommonUtil.ShowToast("congratulation!! You Won " + HeadTailsdata.win_amount);
        }
        else if (HeadTailsdata.code == 103)
        {
            AudioManager._instance.PlayLoseSound();
            CommonUtil.ShowToast("Better Luck Next Time, You Lost " + HeadTailsdata.diff_amount);
        }
        updatedata();
    }
    #endregion
    public void PlayerWinAnimation()
    {
        if (HTData.game_data[0].winning == "0")
        {
            if (headint > 0)
            {
                headobj.ForEach(x => x.transform.SetParent(Userprofile.transform));
                headobj.ForEach(x =>
                    x.transform.DOLocalMove(Vector3.zero, .6f)
                        .OnComplete(() =>
                        {
                            Destroy(x);
                            headobj.Remove(x);
                        })
                );
            }
        }
        if (HTData.game_data[0].winning == "1")
        {
            if (tailint > 0)
            {
                tailobj.ForEach(x => x.transform.SetParent(Userprofile.transform));
                tailobj.ForEach(x =>
                    x.transform.DOLocalMove(Vector3.zero, .6f)
                        .OnComplete(() =>
                        {
                            Destroy(x);
                            tailobj.Remove(x);
                        })
                );
                // for (int i = 0; i < tailobj.Count; i++)
                // {
                //     tailobj[i].GetComponent<GetCoinBack>().endPositions = Userprofile.transform;
                //     tailobj[i].GetComponent<GetCoinBack>().won();
                // }
            }
        }
    }

    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;

    public void OnButtonClickPartical(int buttonIndex)
    {
        ParticleSystem newParticle = particleSystems[buttonIndex];
        if (currentParticle != null)
        {
            currentParticle.Stop();
        }
        newParticle.Play();
        currentParticle = newParticle;
    }

    #endregion
    #region  disablebutton


    #endregion
}
