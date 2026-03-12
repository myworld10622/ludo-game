using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using AndroApps;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyButtons;
using EasyUI.Toast;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class CarRouletteManager : MonoBehaviour
{
    [Header("Controller Details")]
    private string CustomNamespace = "/car_roulette";
    private SocketManager Manager;

    [Header("Status data")]
    public CRRootObject CRData;

    [Header("Round Timer Text")]
    public Text timertext;

    [Header("Game Users")]
    public GameObject Userprofile;
    public List<GameObject> profiles;
    public List<BotsUser> m_Bots = new List<BotsUser>();
    public Animator onlineuser;

    [Header("Car Last Winning Data")]
    public List<Sprite> carroulette_Listsprites;
    public List<string> lastwinning;
    public List<Image> lastwinningdata;

    [Header("Coins")]
    public List<GameObject> coins;

    [Header("Win related GameObjects")]
    public float interval = 0.1f;
    public string resultString;
    public List<Transform> Highlited;

    [Header("Buttons")]
    public List<GameObject> btns;

    [Header("put bet json variables")]
    public CarRouletteBetResponse betresp;
    public bool putbetbool;

    [Header("my bet texts")]
    public TextMeshProUGUI bmwamount;
    public TextMeshProUGUI toyotaamount,
        audiamount,
        porcheamount,
        ferrariamount,
        mercamount,
        lamboamount,
        mahindraamount;
    public int bmwint,
        toyotaint,
        audiint,
        porcheint,
        ferrariint,
        mercint,
        lamboint,
        mahindraint;

    public List<GameObject> highlights = new List<GameObject>();
    public GameObject Reconn_Canvas;
    public string betamount;
    public string bet;
    public GameObject buttonclicked;
    public int num;
    private int currentIndex = 0;
    private bool stopAtResult = false,
        online;
    private bool GameEnded,
        gamestart,
        click,
        showrecord,
        timeshow;
    public CarRouletteBetResult carrouletteresultdata;
    public List<GameObject> cariconsforcoins;
    public ManageCar car;

    public List<List<Transform>> allpositions = new List<List<Transform>>();
    public Button[] buttons;
    public GameObject PlacebetAni;
    public GameObject stopbetAni;
    public bool reconnected = false;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;

    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;

    public Toggle musicToggle;
    public Toggle soundToggle;
    #endregion
    private void OnEnable()
    {
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();

        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("car_roulette_timer", OnCarroulette_TimerResponse);
        customNamespace.On<string>("car_roulette_status", OncarrouletteStatus_statusResponse);
        Manager.Open();

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);
    }

    private void Start()
    {
        foreach (var coin in coins)
        {
            var pool = coin.AddComponent<ObjectPoolUtil>();
            pool.InitializePool(coin, 10);
            pool.SetLocalScale(22);
        }
        GameBetUtil.initialScale = Vector3.one * 25;
        GameBetUtil.targetScale = Vector3.one * 23;

        updatedata();
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

    void OnApplicationPause(bool pauseStatus)
    {
        /* if (!pauseStatus)
        {
            Debug.Log("RES_Check + resume");
            RequestGameStateUpdate();
        } */
    }

    private void RequestGameStateUpdate()
    {
        Manager.Close();
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("car_roulette_timer", OnCarroulette_TimerResponse);
        customNamespace.On<string>("car_roulette_status", OncarrouletteStatus_statusResponse);
        Manager.Open();
        reconnected = true;
    }

    #region Socket Connection/DisConnection
    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("Connect : " + resp.sid);
        //ControllerDetail.IsConnection = true;
        Reconn_Canvas.SetActive(false);
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
        StopAllCoroutines(); //
        Debug.Log("Disconnected: ");
        this.gameObject.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
    }

    #endregion

    #region PlaceBet_Animator
    private bool FirstTime_Ani = false;
    private bool StopTime_Ani = false;

    public void StartAni()
    {
        PlacebetAni.SetActive(true);
    }

    public void StopAni()
    {
        PlacebetAni.SetActive(false);
    }

    public void StopBet_AniStart()
    {
        AudioManager._instance.PlayStopBetSound();
        // stoptext.text = "STOP BET";
        stopbetAni.SetActive(true);
        DOVirtual.DelayedCall(
            2F,
            () =>
            {
                StopBet_AniStop();
            }
        );
    }

    public void StopBet_AniStop()
    {
        stopbetAni.SetActive(false);
    }
    #endregion


    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("car_roulette_timer", "animal_roulette_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-animal_roulette_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnCarroulette_TimerResponse(string args)
    {
        //        Debug.Log("Timer Game Json :" + args);
        if (!timeshow)
        {
            Stopshowwait();
            //  timertext.transform.parent.gameObject.SetActive(false);
        }
        try
        {
            if (!FirstTime_Ani)
            {
                StartAni();
            }
            else
            {
                StopAni();
            }
            FirstTime_Ani = true;
            timertext.transform.parent.gameObject.SetActive(true);
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
            AudioManager._instance.PlayPlaceBetSound();
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
        //online = false;
    }

    #endregion

    #region Carroulette_Status
    private void OncarrouletteStatus_statusResponse(string args)
    {
        Debug.Log("JSON: " + args);
        try
        {
            CRData = JsonUtility.FromJson<CRRootObject>(args);
            displayprofiles();
            if (!showrecord)
            {
                displaylastwin();
                showrecord = true;
            }
            if (CRData.game_data[0].status == "0")
            {
                string game_id = PlayerPrefs.GetString("CarRouletteid");
                bool isSameGame = game_id == CRData.game_data[0].id;

                if (reconnected)
                {
                    ResetAmounts(isSameGame);
                    ClearGameObjects();
                    reconnected = false;
                    GameEnded = false;
                }
                else
                {
                    ResetAmounts(isSameGame);
                }
                PlayerPrefs.SetString("CarRouletteid", CRData.game_data[0].id);

                bmwamount.text = bmwint + "";
                lamboamount.text = lamboint + "";
                porcheamount.text = porcheint + "";
                mercamount.text = mercint + "";
                ferrariamount.text = ferrariint + "";
                audiamount.text = audiint + "";
                toyotaamount.text = toyotaint + "";
                mahindraamount.text = mahindraint + "";

                if (!online)
                {
                    online = true;
                    StartCoroutine(
                        GameBetUtil.StartBet(
                            coins,
                            m_ColliderList,
                            profiles,
                            onlineuser,
                            m_DummyObjects,
                            m_coliderText,
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
                            m_coliderText,
                            isAI: false
                        )
                    );
                }

                /*  if (CRData.game_data[0].status == "1" && !GameEnded)
                 {
                     foreach (var btn in buttons)
                     {
                         btn.interactable = false;
                     }
                     //  stop.SetActive(true);
                     // DOVirtual.DelayedCall(2.5f, () =>
                     // {
                     //     stop.SetActive(false);
                     // });
                     StopTime_Ani = false;
                     timertext.transform.parent.gameObject.SetActive(false);
                     FirstTime_Ani = false;
                     // putbetanim.SetActive(false);
                     gamestart = false;
                     GameEnded = true;

                     if (!StopTime_Ani)
                     {
                         StopBet_AniStart();
                     }
                     else
                     {
                         StopBet_AniStop();
                     }
                     StopTime_Ani = true;

                     wininitializing();
                     StartCoroutine(win());
                 } */
            }
            else
            {
                Debug.Log("Status" + CRData.game_data[0].status);
                if (online)
                {
                    online = false;
                    GameBetUtil.StopBet();
                    foreach (var btn in buttons)
                    {
                        btn.interactable = false;
                    }
                    //  stop.SetActive(true);
                    // DOVirtual.DelayedCall(2.5f, () =>
                    // {
                    //     stop.SetActive(false);
                    // });
                    StopTime_Ani = false;
                    timertext.transform.parent.gameObject.SetActive(false);
                    FirstTime_Ani = false;
                    //putbetanim.SetActive(false);
                    gamestart = false;
                    GameEnded = true;

                    if (!StopTime_Ani)
                    {
                        StopBet_AniStart();
                    }
                    else
                    {
                        StopBet_AniStop();
                    }
                    StopTime_Ani = true;

                    wininitializing();
                    StartCoroutine(win());
                }
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }
    #endregion
    private void ResetAmounts(bool isSameGame)
    {
        if (isSameGame)
        {
            bmwamount.text = bmwint + "";
            lamboamount.text = lamboint + "";
            porcheamount.text = porcheint + "";
            mercamount.text = mercint + "";
            ferrariamount.text = ferrariint + "";
            audiamount.text = audiint + "";
            toyotaamount.text = toyotaint + "";
            mahindraamount.text = mahindraint + "";
        }
        else
        {
            bmwint =
                lamboint =
                porcheint =
                mercint =
                ferrariint =
                audiint =
                toyotaint =
                mahindraint =
                    0;
            bmwamount.text = bmwint + "";
            lamboamount.text = lamboint + "";
            porcheamount.text = porcheint + "";
            mercamount.text = mercint + "";
            ferrariamount.text = ferrariint + "";
            audiamount.text = audiint + "";
            toyotaamount.text = toyotaint + "";
            mahindraamount.text = mahindraint + "";
            for (int i = 0; i < m_coliderText.Count; i++)
            {
                m_coliderText[i].text = "0";
            }
        }
    }

    private void ClearGameObjects()
    {
        ClearObjectList(m_DummyObjects);
    }

    private void ClearObjectList(List<GameObject> objectList)
    {
        foreach (GameObject obj in objectList)
        {
            Destroy(obj);
        }
        objectList.Clear();
    }

    #region Car_roulette Status related functions
    public void displaylastwin()
    {
        Debug.Log("displaylastwin Call");
        lastwinning.Clear();
        if (CRData.last_winning.Count > 0)
        {
            for (int i = 0; i < CRData.last_winning.Count; i++)
            {
                lastwinning.Add(CRData.last_winning[i].winning);
            }

            for (int i = 0; i < lastwinningdata.Count; i++)
            {
                if (lastwinning.Count >= i)
                {
                    if (lastwinning[i] == "1")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            0
                        ];
                    else if (lastwinning[i] == "2")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            1
                        ];
                    else if (lastwinning[i] == "3")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            2
                        ];
                    else if (lastwinning[i] == "4")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            3
                        ];
                    else if (lastwinning[i] == "5")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            4
                        ];
                    else if (lastwinning[i] == "6")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            5
                        ];
                    else if (lastwinning[i] == "7")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            6
                        ];
                    else if (lastwinning[i] == "8")
                        lastwinningdata[i].GetComponent<Image>().sprite = carroulette_Listsprites[
                            7
                        ];
                }
            }
        }
    }

    public async void displayprofiles()
    {
        if (!click)
        {
            UserNameText.text = Configuration.GetName();
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
            click = true;
        }
        /*  for (int i = 0; i < profiles.Count; i++)
         {
             profiles[i].transform.GetChild(0).gameObject.SetActive(true);
             profiles[i].transform.GetChild(2).gameObject.SetActive(false);
             profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text = CRData.bot_user[i].name;
         } */
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].UserCome.SetActive(true);
            m_Bots[i].NoUser.SetActive(false);
        }
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].BotName.text = CRData.bot_user[i].name;
            m_Bots[i].BotCoin.text = CommonUtil.GetFormattedWallet(CRData.bot_user[i].coin);
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + CRData.bot_user[i].avatar
            );
        }
    }

    #endregion

    #region Aibet
    private List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<TextMeshProUGUI> m_coliderText = new List<TextMeshProUGUI>();
    #endregion
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
            buttons.transform.GetChild(1).gameObject.SetActive(false);
        }
        button.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
        buttonclicked = button;
        button.transform.GetChild(1).gameObject.SetActive(true);
    }

    public void coininstantate(int index)
    {
        num = index;
    }

    #region Put_Bet
    async void PutBet()
    {
        string url = CarRoulleteConfig.PlaceBet;
        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", CRData.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        JsonResponse jsonResponse = new JsonResponse();
        jsonResponse = await APIManager.Instance.Post<JsonResponse>(url, formData);

        if (jsonResponse.code == 406)
        {
            CommonUtil.ShowToast(jsonResponse.message);
        }
        else
        {
            string walletString = jsonResponse.wallet;
            PlayerPrefs.SetString("wallet", jsonResponse.wallet);
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            if (jsonResponse.code == 406)
            {
                putbetbool = false;
                CommonUtil.CheckLog("" + jsonResponse.message);
                CommonUtil.ShowToast(jsonResponse.message);
            }
            else
            {
                putbetbool = true;
                Debug.Log("RES_Check + Bet Placed: " + int.Parse(bet));

                var RandomCollider = m_ColliderList[int.Parse(bet) - 1];
                var coin = Instantiate(
                    coins[num],
                    Userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );

                coin.transform.localPosition = Vector3.zero;
                coin.transform.localScale = Vector3.one * 20;
                coin.transform.SetParent(RandomCollider.transform);

                AudioManager._instance?.PlayCoinDrop();

                m_DummyObjects.Add(coin.gameObject);

                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 19, 0.2f);
                    });
                PlayerPrefs.SetString("wallet", jsonResponse.wallet);

                UserWalletText.text = CommonUtil.GetFormattedWallet();

                UpdateBetUI(bet, betamount);
                CommonUtil.GetFormattedWallet();
                UserWalletText.text = CommonUtil.GetFormattedWallet();

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
                //  UpdateButtonInteractability(Configuration.GetWallet());
            }
        }
    }

    void UpdateBetUI(string bet, string betAmount)
    {
        int amount = int.Parse(betAmount);
        switch (bet)
        {
            case "1":
                toyotaint += amount;
                m_coliderText[0].text = int.Parse(m_coliderText[0].text) + amount + "";
                toyotaamount.text = $"{toyotaint}";
                break;
            case "2":
                mahindraint += amount;
                m_coliderText[1].text = int.Parse(m_coliderText[1].text) + amount + "";
                mahindraamount.text = $"{mahindraint}";
                break;
            case "3":
                audiint += amount;
                m_coliderText[2].text = int.Parse(m_coliderText[2].text) + amount + "";
                audiamount.text = $"{audiint}";
                break;
            case "4":
                bmwint += amount;
                m_coliderText[3].text = int.Parse(m_coliderText[3].text) + amount + "";
                bmwamount.text = $"{bmwint}";
                break;
            case "5":
                mercint += amount;
                m_coliderText[4].text = int.Parse(m_coliderText[4].text) + amount + "";
                mercamount.text = $"{mercint}";
                break;
            case "6":
                porcheint += amount;
                m_coliderText[5].text = int.Parse(m_coliderText[5].text) + amount + "";
                porcheamount.text = $"{porcheint}";
                break;
            case "7":
                lamboint += amount;
                m_coliderText[6].text = int.Parse(m_coliderText[6].text) + amount + "";
                lamboamount.text = $"{lamboint}";
                break;
            case "8":
                ferrariint += amount;
                m_coliderText[7].text = int.Parse(m_coliderText[7].text) + amount + "";
                ferrariamount.text = $"{ferrariint}";
                break;
            default:
                Debug.LogWarning("Invalid bet type");
                break;
        }
    }

    public void ClickedCar(string carBet)
    {
        if (betamount == "0")
        {
            CommonUtil.ShowToast("Insufficient balance");
            return;
        }

        if (!gamestart)
        {
            CommonUtil.ShowToast("Please wait for the round to start");
            return;
        }

        if (CRData.game_data[0].status != "0")
        {
            CommonUtil.ShowToast("Game is not active");
            return;
        }

        Debug.Log($"RES_Check + Clicked {carBet}");
        bet = carBet;
        PutBet();
    }

    public void ClickedToyota() => ClickedCar("1");

    public void ClickedMahindra() => ClickedCar("2");

    public void ClickedAudi() => ClickedCar("3");

    public void ClickedBMW() => ClickedCar("4");

    public void ClickedMerc() => ClickedCar("5");

    public void ClickedPorche() => ClickedCar("6");

    public void ClickedLamborghini() => ClickedCar("7");

    public void ClickedFerrari() => ClickedCar("8");

    #endregion


    #region win related functions

    void wininitializing()
    {
        for (int i = 0; i < Highlited.Count; i++)
        {
            Highlited[i].gameObject.SetActive(false);
        }
        StartCoroutine(HighlightSequence());
    }

    IEnumerator HighlightSequence()
    {
        StopTime_Ani = false;
        timertext.text = "0";
        AudioManager._instance.PlayCarRacingSound();
        yield return new WaitForSeconds(1f);
        onlineuser.enabled = false;
        resultString = CRData.game_cards[0].card;
        currentIndex = 0;
        int count = 0;
        car.gameObject.SetActive(true);

        car.GetComponent<ManageCar>().stopCar = int.Parse(CRData.game_data[0].winning);
        DG.Tweening.DOVirtual.DelayedCall(0f, car.GetComponent<ManageCar>().RestartPath);

        while (true)
        {
            if (stopAtResult && currentIndex == int.Parse(resultString) - 1)
            {
                for (int i = 0; i < Highlited.Count; i++)
                {
                    Highlited[i].gameObject.SetActive(i == currentIndex);
                    stopAtResult = false;
                }
                yield break;
            }

            for (int i = 0; i < Highlited.Count; i++)
            {
                if (i == currentIndex)
                {
                    Highlited[i].gameObject.SetActive(true);
                }
                else
                {
                    Highlited[i].gameObject.SetActive(false);
                }
            }

            yield return new WaitForSeconds(0.05f);

            currentIndex++;

            if (currentIndex >= Highlited.Count)
            {
                currentIndex = 0;
                count++;
                if (count == 3)
                    stopAtResult = true;
            }
        }
    }

    IEnumerator HighlightWinner(GameObject obj)
    {
        AudioManager._instance?.PlayHighlightWinSound();
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
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(false);
        car.gameObject.SetActive(false);
    }

    int userwincoins = 0;

    private IEnumerator win()
    {
        CommonUtil.ShowToast(
            "winning: " + CRData.game_data[0].winning + " , Game Id: " + CRData.game_data[0].id
        );
        yield return new WaitForSeconds(5f);

        StartCoroutine(HighlightWinner(highlights[int.Parse(CRData.game_data[0].winning) - 1]));
        yield return new WaitForSeconds(2f);

        Debug.Log("WINBOBJECT NAME::" + int.Parse(CRData.game_data[0].winning));
        var hightlitedobjed = highlights[int.Parse(CRData.game_data[0].winning) - 1]
            .transform
            .parent
            .name;
        Debug.Log("WINBOBJECT NAME::" + hightlitedobjed);
        var maincollider = m_ColliderList.Find(x => x.transform.parent.name == hightlitedobjed);
        Debug.Log("Finded Colider NAME::" + hightlitedobjed);
        GetResult();
        #endregion

        userwincoins = 0;
        Transform nextpos = highlights[int.Parse(CRData.game_data[0].winning) - 1].transform;

        var oppos = cariconsforcoins[int.Parse(CRData.game_data[0].winning) - 1].transform;

        AudioManager._instance.PlayCoinDrop();

        m_DummyObjects.ForEach(x =>
        {
            x.transform.localScale = new Vector3(21, 21, 21);
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
                            if (carrouletteresultdata.win_amount > 0 && userwincoins < 10)
                            {
                                userwincoins++;
                                x.transform.SetParent(
                                    Userprofile
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

        bmwamount.text = "0";
        toyotaamount.text = "0";
        porcheamount.text = "0";
        lamboamount.text = "0";
        ferrariamount.text = "0";
        mercamount.text = "0";
        audiamount.text = "0";
        mahindraamount.text = "0";
        bmwint = 0;
        lamboint = 0;
        porcheint = 0;
        audiint = 0;
        ferrariint = 0;
        mercint = 0;
        toyotaint = 0;
        mahindraint = 0;
        for (int i = 0; i < m_coliderText.Count; i++)
        {
            m_coliderText[i].text = "0";
        }
        yield return new WaitForSeconds(3.5f);

        ClearGameObjects();
        allpositions.Clear();
        showrecord = false;
        timeshow = false;
        for (int i = 0; i < Highlited.Count; i++)
        {
            Highlited[i].gameObject.SetActive(false);
        }
        //showwaitanim.SetActive(true);
        GetTimer();
    }

    public async void updatedata() //string Token)
    {
        string url = Configuration.Url + Configuration.wallet;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        Wallet myResponse = await APIManager.Instance.Post<Wallet>(url, formData);
        if (myResponse != null)
        {
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
        }
        /*       CommonUtil.CheckLog("RES+Message" + myResponse.message);
              CommonUtil.CheckLog("RES+Code" + myResponse.code); */
    }

    //
    public async void GetResult()
    {
        string Url = CarRoulleteConfig.CarRouletteResult;

        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", CRData.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + CRData.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        carrouletteresultdata = await APIManager.Instance.Post<CarRouletteBetResult>(Url, formData);

        CommonUtil.CheckLog("Result Message" + carrouletteresultdata.message);

        if (carrouletteresultdata.code == 102)
        {
            AudioManager._instance.PlayWinSound();
            CommonUtil.ShowToast("congratulation!! You Won " + carrouletteresultdata.win_amount);
        }
        else if (carrouletteresultdata.code == 103)
        {
            AudioManager._instance.PlayStopBetSound();
            CommonUtil.ShowToast(
                "Better Luck Next Time, You Lost " + carrouletteresultdata.diff_amount
            );
        }
        PlayerPrefs.SetString("wallet", carrouletteresultdata.wallet);
        PlayerPrefs.Save();
        UserWalletText.text = CommonUtil.GetFormattedWallet();

        /*    Debug.Log("RES_Check + API-Call + Car Roulette Result");
           WWWForm form = new WWWForm();
           form.AddField("user_id", Configuration.GetId());
           form.AddField("token", Configuration.GetToken());
           form.AddField("game_id", CRData.game_data[0].id);
           UnityWebRequest www = UnityWebRequest.Post(Url, form);
           www.SetRequestHeader("Token", Configuration.TokenLoginHeader);
           Debug.Log(
               "RES_Check + userid + "
                   + Configuration.GetId()
                   + " token "
                   + Configuration.GetToken()
                   + " "
                   + " gameid "
                   + CRData.game_data[0].id
           );
           Debug.Log("RES_Check + URL + " + Url);
           yield return www.SendWebRequest();
           if (www.result == UnityWebRequest.Result.Success)
           {
               var responseText = www.downloadHandler.text;
               Debug.Log("Res_Value + Result: " + responseText);
               carrouletteresultdata = new CarRouletteBetResult();
               carrouletteresultdata = JsonConvert.DeserializeObject<CarRouletteBetResult>(
                   responseText
               ); */

        /*       UserWalletText.text = CommonUtil.GetFormattedWallet();
          }
              else
              {
                  Debug.Log("RES_Check + Result + " + www.result);
              } */
    }

    /* #region coinsSystem
    public void UpdateButtonInteractability(string walletAmountStr)
    {
        Debug.Log("RES_Check + Amount");
        if (float.TryParse(walletAmountStr, out float walletAmount))
        {
            Debug.Log("RES_Check + Amount Coin");
            int i = 0;
            if (gamestart)
            {
                foreach (var button in buttons)
                {
                    Debug.Log("RES_Check + Amount Coin " + button.name);
                    Debug.Log("RES_Check + Amount wallet " + walletAmountStr);
                    if (float.TryParse(button.name, out float buttonValue))
                    {
                        button.interactable = walletAmount >= buttonValue;
                    }
                    if (!button.interactable)
                    {
                        foreach (var btn in buttons)
                        {
                            btn.transform.localScale = Vector3.one;
                        }
                        Debug.Log("RES_check + buttonclicked " + buttonclicked);
                        Debug.Log("RES_check + buttonclicked button " + button);
                        if (buttonclicked.name == button.name)
                        {
                            Debug.Log("RES_check + particle stop");
                            betamount = "0";
                            if (i == 0)
                            {
                                buttonclick(0);
                                clickedbutton(buttons[0].gameObject);
                                coininstantate(0);
                                button.interactable = true;
                                Debug.Log("RES_Check + I " + i);
                                for (int j = 1; j < buttons.Count(); j++)
                                {
                                    buttons[j].interactable = false;
                                    buttons[j].transform.localScale = Vector3.one;
                                }
                                break;
                            }
                            else
                            {
                                Debug.Log("RES_Check + I " + i);
                                int num = i - 1;
                                while (num >= 0)
                                {
                                    if (int.TryParse(buttons[num].name, out int buttonval))
                                    {
                                        if (walletAmount >= buttonval)
                                        {
                                            Debug.Log("RES_Check + buttonval amount " + buttonval);
                                            Debug.Log("RES_Check + buttonval amount i " + i);
                                            buttonclick(buttonval);
                                            clickedbutton(buttons[num].gameObject);
                                            coininstantate(num);
                                            Debug.Log(
                                                "RES_Check + buttonval amount wallet" + walletAmount
                                            );
                                            if (walletAmount < 10)
                                            {
                                                Debug.Log(
                                                    "RES_Check + buttonval amount wallet"
                                                        + walletAmount
                                                );
                                                betamount = "0";
                                                break;
                                            }
                                            break;
                                        }
                                    }
                                    num--;
                                }
                                if (betamount == "0")
                                {
                                    buttonclick(0);
                                    clickedbutton(buttons[0].gameObject);
                                    coininstantate(0);
                                    buttons[0].interactable = true;
                                    Debug.Log("RES_Check + this I " + i);
                                    betamount = "0";
                                    break;
                                }
                                if (i == 0)
                                {
                                    buttonclick(0);
                                    clickedbutton(buttons[0].gameObject);
                                    coininstantate(0);
                                    buttons[0].interactable = true;
                                    Debug.Log("RES_Check + this I " + i);
                                    if (walletAmount < 10)
                                        betamount = "0";
                                    break;
                                }
                            }
                            Debug.Log("RES_Check + break");
                        }
                    }
                    i++;
                }
                for (int j = 0; j < buttons.Count(); j++)
                {
                    if (buttons[j].gameObject == buttonclicked)
                    {
                        buttonclick(int.Parse(buttons[j].name));
                        clickedbutton(buttons[j].gameObject);
                        coininstantate(j);
                        if (j == 0 && walletAmount < 10)
                        {
                            betamount = "0";
                        }
                    }
                }
            }
            else
            {
                Debug.Log("RES_check + game not started");
                foreach (var btn in buttons)
                {
                    btn.interactable = false;
                }
            }
        }
    }

    #endregion */

    public void AddBot(
        GameObject userCome,
        GameObject noUser,
        Text botName,
        Text botCoin,
        Image profileImage
    )
    {
        // Create a new instance of BotsUser
        BotsUser newBot = new BotsUser
        {
            UserCome = userCome,
            NoUser = noUser,
            BotName = botName,
            BotCoin = botCoin,
            ProfileImage = profileImage,
        };

        // Add the new bot to the list
        m_Bots.Add(newBot);
    }

    [Button]
    public void SetProfiles()
    {
        m_Bots.Clear();
        for (int i = 0; i < profiles.Count; i++)
        {
            AddBot(
                profiles[i].transform.GetChild(0).gameObject,
                profiles[i].transform.GetChild(2).gameObject,
                profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>(),
                profiles[i].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>(),
                profiles[i].transform.GetChild(0).GetChild(1).GetChild(0).GetComponent<Image>()
            );
        }
        // public List<BotsUser> m_Bots = new List<BotsUser>();
    }
}
