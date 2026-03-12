using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyButtons;
using EasyUI.Toast;
using Mkey;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class AnimalRoulletManager : MonoBehaviour
{
    [Header("Controller Details")]
    public string CustomNamespace = "/animal_roulette";
    private SocketManager Manager;

    [Header("Status data")]
    public ARRootObject ARData;

    [Header("Round Timer Text")]
    public TextMeshProUGUI timertext;

    [Header("Game Users")]
    public GameObject Userprofile;
    public List<GameObject> profiles;
    public List<BotsUser> m_Bots = new List<BotsUser>();
    public Animator onlineuser;

    [Header("Animals Last Winning Data")]
    public List<Sprite> gameroulette_Listsprites;
    public List<string> lastwinning;
    public List<Image> lastwinningdata;

    [Header("Coins")]
    public List<GameObject> coins;

    [Header("Win related GameObjects")]
    public float interval = 0.1f;
    public string resultString;
    public List<Transform> gameObjects;

    [Header("Buttons")]
    public List<GameObject> btns;

    [Header("put bet json variables")]
    public SevenUPPutBetResponse betresp;
    public bool putbetbool;

    [Header("my bet texts")]
    public TextMeshProUGUI tigeramount;
    public TextMeshProUGUI snakeamount,
        sharkamount,
        wolfamount,
        leopardamount,
        bearamount,
        dolphinamount,
        lionamount;
    public int tigerint,
        snakeint,
        sharkint,
        wolfint,
        leopardint,
        bearint,
        dolphinint,
        lionint;

    public GameObject Reconn_Canvas;
    public string betamount;
    public string bet;
    public GameObject buttonclicked;
    public int num;
    public List<GameObject> highlights = new List<GameObject>();

    private int currentIndex = 0;
    private bool stopAtResult = false,
        online;
    private bool GameEnded,
        gamestart,
        click,
        showrecord,
        timeshow;
    public AnimalRouletteBetResult animalrouletteresultdata;
    public Button[] buttons;

    public bool reconnected = false;
    public GameObject showstop;
    public Text stoptext;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;
    public Toggle soundToggle;
    public Toggle musicToggle;
    public bool IsConnection;
    public Transform m_UserCoinPos;

    private void OnEnable()
    {
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;
        UserNameText.text = Configuration.GetName();
        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("animal_roulette_timer", Onanimalroulette_TimerResponse);
        customNamespace.On<string>("animal_roulette_status", OnanimalrouletteStatus_statusResponse);
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
            AudioManager._instance?.PlayBackgroundAudio();
        }
        else
        {
            AudioManager._instance?.StopBackgroundAudio();
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

    void OnApplicationPause(bool pauseStatus)
    {
        if (!pauseStatus)
        {
            Debug.Log("RES_Check + resume");
            RequestGameStateUpdate();
        }
    }

    private void RequestGameStateUpdate()
    {
        //  StopCoroutine(aibet());
        // StopCoroutine(onlinebet());
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("animal_roulette_timer", Onanimalroulette_TimerResponse);
        customNamespace.On<string>("animal_roulette_status", OnanimalrouletteStatus_statusResponse);
        Manager.Open();
        reconnected = true;
    }

    #region Socket Connection/DisConnection

    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("Connect : " + resp.sid);
        IsConnection = true;
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

    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("animal_roulette_timer", "animal_roulette_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-animal_roulette_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void Onanimalroulette_TimerResponse(string args)
    {
        Debug.Log("Timer Game Json :" + args);
        if (!timeshow)
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
    #endregion

    #region animalroulette_Status
    private void OnanimalrouletteStatus_statusResponse(string args)
    {
        Debug.Log("JSON: " + args);
        try
        {
            ARData = JsonUtility.FromJson<ARRootObject>(args);
            if (ARData == null)
                return;
            displayprofiles();
            if (!showrecord)
            {
                DisplayLastWin();
                showrecord = true;
            }

            if (ARData.game_data[0].status == "0")
            {
                if (!online)
                {
                    for (int i = 0; i < m_Bots.Count; i++)
                    {
                        m_Bots[i].NoUser.SetActive(false);
                        m_Bots[i].UserCome.SetActive(true);
                    }
                    online = true;
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
                string game_id = PlayerPrefs.GetString("Animalrouletteid");
                bool isSameGame = game_id == ARData.game_data[0].id;
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
                PlayerPrefs.SetString("Animalrouletteid", ARData.game_data[0].id);
                tigeramount.text = tigerint.ToString();
                snakeamount.text = snakeint.ToString();
                sharkamount.text = sharkint.ToString();
                wolfamount.text = wolfint.ToString();
                leopardamount.text = leopardint.ToString();
                bearamount.text = bearint.ToString();
                dolphinamount.text = dolphinint.ToString();
                lionamount.text = lionint.ToString();

                /*  if (ARData.game_data[0].status == "1" && !GameEnded)
                 {
                     //putbetanim.SetActive(false);
                     gamestart = false;
                     GameEnded = true;

                     foreach (var obj in buttons)
                     {
                         obj.interactable = false;
                     }
                     gameaudio.clip = stopbet;
                     gameaudio.Play();
                     stoptext.text = "STOP BET";

                     showstop.SetActive(true);
                     DOVirtual.DelayedCall(
                         2f,
                         () =>
                         {
                             wininitializing();
                             // StartCoroutine(win());
                             showstop.SetActive(false);
                         }
                     );
                 } */
            }
            else
            {
                if (online)
                {
                    CommonUtil.ShowToast(
                        "winning: "
                            + ARData.game_data[0].winning
                            + " , Game Id: "
                            + ARData.game_data[0].id
                    );
                    online = false;
                    GameBetUtil.StopBet();

                    gamestart = false;
                    GameEnded = true;

                    foreach (var obj in buttons)
                    {
                        obj.interactable = false;
                    }
                    AudioManager._instance?.PlayStopBetSound();
                    stoptext.text = "STOP BET";

                    showstop.SetActive(true);
                    DOVirtual.DelayedCall(
                        2f,
                        () =>
                        {
                            wininitializing();
                            // StartCoroutine(win());
                            showstop.SetActive(false);
                        }
                    );
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
            tigeramount.text = tigerint.ToString() + "";
            snakeamount.text = snakeint.ToString() + "";
            sharkamount.text = sharkint.ToString() + "";
            wolfamount.text = wolfint.ToString() + "";
            leopardamount.text = leopardint.ToString() + "";
            bearamount.text = bearint.ToString() + "";
            dolphinamount.text = dolphinint.ToString() + "";
            lionamount.text = lionint.ToString() + "";
        }
        else
        {
            tigerint =
                snakeint =
                sharkint =
                wolfint =
                leopardint =
                bearint =
                dolphinint =
                lionint =
                    0;
            tigeramount.text = tigerint.ToString();
            snakeamount.text = snakeint.ToString();
            sharkamount.text = sharkint.ToString();
            wolfamount.text = wolfint.ToString();
            leopardamount.text = leopardint.ToString();
            bearamount.text = bearint.ToString();
            dolphinamount.text = dolphinint.ToString();
            lionamount.text = lionint.ToString();
            for (int i = 0; i < m_colidertext.Count; i++)
            {
                m_colidertext[i].text = "0";
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

    #region animal_roulette Status related functions
    public void Stopshowwait()
    {
        gamestart = true;
        //showwaitanim.SetActive(false);
        StartCoroutine(Showplacebet());
        online = false;
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

    IEnumerator Showplacebet()
    {
        timeshow = true;

        AudioManager._instance?.PlayPlaceBetSound();
        stoptext.text = "PLACE BET";
        showstop.SetActive(true);
        foreach (var obj in buttons)
        {
            obj.interactable = true;
        }
        yield return new WaitForSeconds(2);
        showstop.SetActive(false);
        // putbetanim.SetActive(true);
        // yield return new WaitForSeconds(2);
        // putbetanim.SetActive(false);
    }

    /*  public void displaylastwin()
     {
         lastwinning.Clear();
         if (ARData.last_winning.Count > 0)
         {
             for (int i = 0; i < ARData.last_winning.Count; i++)
             {
                 lastwinning.Add(ARData.last_winning[i].winning);
             }

             for (int i = 0; i < lastwinningdata.Count; i++)
             {
                 if (lastwinning.Count >= i)
                 {
                     if (lastwinning[i] == "1")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[0];
                     else if (lastwinning[i] == "2")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[1];
                     else if (lastwinning[i] == "3")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[2];
                     else if (lastwinning[i] == "4")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[3];
                     else if (lastwinning[i] == "5")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[4];
                     else if (lastwinning[i] == "6")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[5];
                     else if (lastwinning[i] == "7")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[6];
                     else if (lastwinning[i] == "8")
                         lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[7];
                 }
             }
         }
     } */
    public void DisplayLastWin()
    {
        lastwinning.Clear();

        if (ARData.last_winning.Count > 0)
        {
            for (int i = 0; i < lastwinningdata.Count; i++)
            {
                if (i < ARData.last_winning.Count && i < gameroulette_Listsprites.Count)
                {
                    if (
                        int.TryParse(ARData.last_winning[i].winning, out int winIndex)
                        && winIndex > 0
                        && winIndex <= gameroulette_Listsprites.Count
                    )
                    {
                        lastwinningdata[i].GetComponent<Image>().sprite = gameroulette_Listsprites[
                            winIndex - 1
                        ];
                    }
                }
            }
        }
    }

    public async void displayprofiles()
    {
        /*   if (!click)
          {
              Userprofile.transform.GetChild(0).gameObject.SetActive(true);
              UserNameText.text = Configuration.GetName();
              UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
              click = true;
          } */
        /*  for (int i = 0; i > profiles.Count; i++)
         {
             profiles[i].transform.GetChild(0).gameObject.SetActive(true);
             profiles[i].transform.GetChild(2).gameObject.SetActive(false);
         } */
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].UserCome.SetActive(true);
            m_Bots[i].NoUser.SetActive(false);
        }
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].BotName.text = ARData.bot_user[i].name;
            m_Bots[i].BotCoin.text = ARData.bot_user[i].coin;
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + ARData.bot_user[i].avatar
            );
        }
    }

    #endregion
    #region Aibet
    private List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<TextMeshProUGUI> m_colidertext = new List<TextMeshProUGUI>();

    #endregion

    #region Put_Bet
    async void PlaceBet()
    {
        string url = AnimalRoulletConfig.AnimalRoulletePutBet;
        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", ARData.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        SevenUPPutBetResponse betresp = new SevenUPPutBetResponse();
        betresp = await APIManager.Instance.Post<SevenUPPutBetResponse>(url, formData);

        if (betresp.code == 406)
        {
            CommonUtil.ShowToast(betresp.message);
        }
        else
        {
            putbetbool = true;
            Debug.Log("RES_Check + Bet Placed: " + int.Parse(bet));
            var RandomCollider = m_ColliderList[int.Parse(bet)];
            // Debug.Log("RES_Check + Object nane i want:" + Userprofile.transform.GetChild(0).GetChild(0).GetChild(2).gameObject.transform.parent.gameObject.name + "");
            var coin = Instantiate(coins[num], m_UserCoinPos);
            coin.transform.localPosition = Vector3.zero;
            coin.transform.localScale = GameBetUtil.initialScale;
            coin.transform.SetParent(RandomCollider.transform);
            AudioManager._instance?.PlayCoinDrop();
            m_DummyObjects.Add(coin);
            coin.transform.DOLocalMove(
                    GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                    0.8f
                )
                .OnComplete(() =>
                {
                    coin.transform.DOScale(GameBetUtil.targetScale, 0.2f);
                });
            PlayerPrefs.SetString("wallet", betresp.wallet);
            //save into wallet
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            UpdateBetUI(bet, betamount);
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
            putbetbool = true;
        }
        void UpdateBetUI(string bet, string betAmount)
        {
            int amount = int.Parse(betAmount);
            switch (bet)
            {
                case "1":
                    tigerint += amount;
                    m_colidertext[0].text = int.Parse(m_colidertext[0].text) + amount + "";
                    tigeramount.text = $"{tigerint}";
                    break;

                case "2":
                    snakeint += amount;
                    m_colidertext[2].text = int.Parse(m_colidertext[2].text) + amount + "";
                    snakeamount.text = $"{snakeint}";
                    break;

                case "3":
                    sharkint += amount;
                    m_colidertext[3].text = int.Parse(m_colidertext[3].text) + amount + "";
                    sharkamount.text = $"{sharkint}";
                    break;

                case "4":
                    wolfint += amount;
                    m_colidertext[4].text = int.Parse(m_colidertext[4].text) + amount + "";
                    wolfamount.text = $"{wolfint}";
                    break;

                case "5":
                    leopardint += amount;
                    m_colidertext[5].text = int.Parse(m_colidertext[5].text) + amount + "";
                    leopardamount.text = $"{leopardint}";
                    break;

                case "6":
                    bearint += amount;
                    m_colidertext[6].text = int.Parse(m_colidertext[6].text) + amount + "";
                    bearamount.text = $"{bearint}";
                    break;

                case "7":
                    dolphinint += amount;
                    m_colidertext[7].text = int.Parse(m_colidertext[7].text) + amount + "";
                    dolphinamount.text = $"{dolphinint}";
                    break;

                case "8":
                    lionint += amount;
                    m_colidertext[8].text = int.Parse(m_colidertext[8].text) + amount + "";
                    lionamount.text = $"{lionint}";
                    break;

                default:
                    Debug.LogWarning("Invalid bet type");
                    break;
            }
        }
    }

    public void ClickedAnimal(string animalBet)
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

        if (ARData.game_data[0].status != "0")
        {
            CommonUtil.ShowToast("Game is not active");
            return;
        }

        Debug.Log($"RES_Check + Clicked {animalBet}");
        bet = animalBet;
        PlaceBet();
    }

    public void ClickedTiger() => ClickedAnimal("1");

    public void ClickedSnake() => ClickedAnimal("2");

    public void ClickedShark() => ClickedAnimal("3");

    public void ClickedWolf() => ClickedAnimal("4");

    public void ClickedCheeta() => ClickedAnimal("5");

    public void ClickedBear() => ClickedAnimal("6");

    public void ClickedWhale() => ClickedAnimal("7");

    public void ClickedLion() => ClickedAnimal("8");

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

    #endregion

    #region win related functions
    void wininitializing()
    {
        StartCoroutine(HighlightSequence());
    }

    IEnumerator HighlightWinner(GameObject obj)
    {
        AudioManager._instance?.PlayHighlightWinSound();
        StartCoroutine(win());

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
    }

    // IEnumerator HighlightSequence()
    // {
    //     timertext.text = "0";
    //     yield return new WaitForSeconds(1f);
    //     onlineuser.enabled = false;
    //     resultString = ARData.game_cards[0].card;
    //     Debug.Log("WIN RESULT STRING::" + resultString);
    //     currentIndex = 0;
    //     int count = 0;
    //     while (true)
    //     {
    //         if (stopAtResult && currentIndex == int.Parse(resultString) - 1)
    //         {
    //             for (int i = 0; i < gameObjects.Count; i++)
    //             {
    //                 gameObjects[i].gameObject.SetActive(i == currentIndex);
    //                 stopAtResult = false;
    //             }
    //             yield break;
    //         }

    //         for (int i = 0; i < gameObjects.Count; i++)
    //         {
    //             if (i == currentIndex)
    //             {
    //                 gameObjects[i].gameObject.SetActive(true);
    //             }
    //             else
    //             {
    //                 gameObjects[i].gameObject.SetActive(false);
    //             }
    //         }
    //         /*   gameaudio.clip = beep;
    //           gameaudio.Play(); */
    //         yield return new WaitForSeconds(0.1f);

    //         currentIndex++;

    //         if (currentIndex >= gameObjects.Count)
    //         {
    //             currentIndex = 0;
    //             count++;
    //             if (count == 1)
    //             {
    //                 stopAtResult = true;
    //                 DOVirtual.DelayedCall(
    //                     0.6f,
    //                     () =>
    //                     {
    //                         StartCoroutine(
    //                             HighlightWinner(highlights[int.Parse(ARData.game_data[0].winning)])
    //                         );
    //                     }
    //                 );
    //             }
    //         }
    //     }
    // }
    IEnumerator HighlightSequence()
    {
        timertext.text = "0";
        yield return new WaitForSeconds(1f);

        onlineuser.enabled = false;
        resultString = ARData.game_cards[0].card;
        Debug.Log("WIN RESULT STRING::" + resultString);

        int resultIndex = int.Parse(resultString) - 1;
        currentIndex = 0;
        int loopCount = 0;

        while (true)
        {
            // Update active game objects
            for (int i = 0; i < gameObjects.Count; i++)
            {
                gameObjects[i].gameObject.SetActive(i == currentIndex);
            }

            // Stop if the current index matches the result
            if (loopCount >= 1 && currentIndex == resultIndex)
            {
                yield return new WaitForSeconds(0.6f);
                StartCoroutine(HighlightWinner(highlights[int.Parse(ARData.game_data[0].winning)]));
                yield break;
            }

            yield return new WaitForSeconds(0.1f);

            // Increment the current index and manage looping
            currentIndex = (currentIndex + 1) % gameObjects.Count;
            if (currentIndex == 0)
            {
                loopCount++;
            }
        }
    }

    private int userwincoins = 0;

    private int winning;

    IEnumerator win()
    {
        yield return new WaitForSeconds(1.2f);

        //var hightlitedobjed = highlights[int.Parse(ARData.game_data[0].winning)].transform.parent.name;
        //Debug.Log("WINBOBJECT NAME::" + hightlitedobjed);
        //  var maincollider = m_ColliderList.Find(x => x.transform.parent.name == hightlitedobjed);
        // Debug.Log("Finded Colider NAME::" + hightlitedobjed);
        Debug.Log("Winning::" + ARData.game_data[0].winning);
        var maincollider = m_ColliderList[int.Parse(ARData.game_data[0].winning)];
        var topborderhighlight = gameObjects.Find(x => x.gameObject.activeSelf);
        GetResult();
        yield return new WaitForSeconds(0.5f);
        AudioManager._instance?.PlayCoinDrop();
        Debug.Log("resultString:" + resultString);
        userwincoins = 0;
        GameBetUtil.MoveAllCoinsIntoTop(
            m_DummyObjects,
            topborderhighlight.transform,
            m_ColliderList,
            ARData.game_data[0].winning,
            System.Convert.ToInt32(winamount),
            System.Convert.ToInt32(winamount),
            Userprofile,
            profiles,
            () => { }
        );
        /*     m_DummyObjects.ForEach(x =>
            {
                x.transform.SetParent(topborderhighlight.transform);
                x.transform.DOLocalMove(Vector3.zero, 0.7f)
                    .OnComplete(() =>
                    {
                        x.transform.SetParent(maincollider.transform);
                        x.transform.DOLocalMove(GameBetUtil.GetRandomPositionInCollider(maincollider), 0.7f)
                            .OnComplete(() =>
                            {
                                // Debug.LogError("winamount" + winamount);
                                if (winamount > 0 && userwincoins < 10)
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

                                    tigerint = 0;
                                    snakeint = 0;
                                    sharkint = 0;
                                    wolfint = 0;
                                    leopardint = 0;
                                    bearint = 0;
                                    dolphinint = 0;
                                    lionint = 0;
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
                    });
            }); */


        tigeramount.text = "0";
        snakeamount.text = "0";
        sharkamount.text = "0";
        wolfamount.text = "0";
        leopardamount.text = "0";
        bearamount.text = "0";
        dolphinamount.text = "0";
        lionamount.text = "0";
        for (int i = 0; i < m_colidertext.Count; i++)
        {
            m_colidertext[i].text = "0";
        }
        gameObjects.ForEach(x => x.gameObject.SetActive(false));
        yield return new WaitForSeconds(2f);
        ClearGameObjects();
        updatedata();
        showrecord = false;
        timeshow = false;
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

    #endregion

    #region result
    public float winamount = 0;

    public async void GetResult()
    {
        Task.Delay(500);
        winamount = 0;
        Debug.Log("Login");
        string Url = AnimalRoulletConfig.AnimalRouletteResult;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", ARData.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + ARData.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        animalrouletteresultdata = await APIManager.Instance.Post<AnimalRouletteBetResult>(
            Url,
            formData
        );

        CommonUtil.CheckLog("Result Message" + animalrouletteresultdata.message);
        if (animalrouletteresultdata.code == 101)
        {
            Task.Delay(100);
        }
        else if (animalrouletteresultdata.code == 102)
        {
            /* gameaudio.clip = winaudio;
            gameaudio.Play(); */
            AudioManager._instance?.PlayWinSound();
            winamount = animalrouletteresultdata.win_amount;
            winning = 1;
            CommonUtil.ShowToast("congratulation!! You Won " + animalrouletteresultdata.win_amount);
            //winamount = 0;
        }
        else if (animalrouletteresultdata.code == 103)
        {
            winamount = 0;
            /* gameaudio.clip = looseaudio;
            gameaudio.Play(); */
            winning = 0;
            AudioManager._instance?.PlayLoseSound();
            CommonUtil.ShowToast(
                "Better Luck Next Time, You Lost " + animalrouletteresultdata.diff_amount
            );
        }
    }
    #endregion

    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;

    public void OnButtonClickPartical(int buttonIndex)
    {
        GameBetUtil.OnButtonClickParticle(
            buttonIndex,
            particleSystems.ToList(),
            ref currentParticle
        );
    }
    #endregion

    #region  AnimalRouletteCoinsSet

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
    #endregion
}
