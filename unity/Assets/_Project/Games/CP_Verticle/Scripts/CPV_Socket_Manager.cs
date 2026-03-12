using System;
using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using AndroApps;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyUI.Toast;
using Mkey;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.SocialPlatforms;
using UnityEngine.SocialPlatforms.Impl;
using UnityEngine.UI;

public class CPV_Socket_Manager : MonoBehaviour
{
    private SocketManager Manager;

    [Header("Game Users")]
    public TextMeshProUGUI useramount;

    [Header("Text Anim")]
    public GameObject showwaitanim;
    public GameObject putbetanim,
        increaseamount;

    [Header("Game Timer Text")]
    public TextMeshProUGUI timertext;
    public TextMeshProUGUI gametimetext;

    [Header("Game Data")]
    public bool GameEnded = false;
    public bool gamestart = false;
    public int gamemode;
    public int betamount;
    public CPVJSONResponse game_data = new CPVJSONResponse();
    public string bettype;
    public GameObject betpanel;
    public TextMeshProUGUI betamountpaneltext,
        betpaneltimer,
        BetPopUpBetText;
    public TMP_InputField betamountmiddle;
    public List<Transform> coins;
    public List<Transform> winninghighlight;
    public historyGameDataList historygamedata;
    public CPGameHistoryWinningData Gamehistory;
    public GameObject historyprefab;
    public Transform historycontent;
    public List<GameObject> historylist;
    public Sprite selectedsprite,
        normalbutton;
    public Image myhistorybutton,
        gamehistorybutton;
    public bool gamehistorybool;

    public GameObject Gamehistoryprefab;
    public Transform Gamehistorycontent;

    public GameObject fivegamehistoryPrefab;
    public Transform fivegamehistoryContent;

    public List<GameObject> Gamehistorylist;
    public GameObject myhistpanel,
        gamehistpanel;
    public int zerobet,
        onebet,
        twobet,
        threebet,
        fourbet,
        fivebet,
        sixbet,
        sevenbet,
        eightbet,
        ninebet,
        redbet,
        violetbet,
        greenbet,
        upbet,
        downbet;
    public Image toastimage;
    public Sprite selectedtime,
        normaltime;
    public Image sec15,
        min1,
        min3,
        min5,
        min10;
    public TextMeshProUGUI GameID;
    private int InitialAmt = 1;
    public GameObject content,
        history;
    public bool bigbutton,
        smallbutton;

    public GameObject winpanel,
        loosepanel;

    public TextMeshProUGUI result,
        resulttext,
        winamounttext,
        periodtext,
        smallbig,
        autoclose;

    public TextMeshProUGUI result2,
        resulttext2,
        periodtext2,
        smallbig2,
        autoclose2;

    public Image looseimg,
        winimg;

    public Sprite redsprite,
        greensprite;

    public int sec;

    public GameObject fivesecondscountdownpanel;

    public TextMeshProUGUI panelcountdowntext;

    VCPBetResult VCPresultdata;

    public List<GameObject> m_fiveGameDataList;

    public bool reconnected,
        invoke;
    public int timetoinvoke;

    #region music and sounds

    public Button[] buttons;

    public List<Button> m_ContractButton = new List<Button>();

    public GameObject Stop;
    public Text stoptext;
    public Toggle soundToggle;
    public Toggle musicToggle;

    #endregion

    #region Redirect to withdraw and deposit

    public void setwithdraw()
    {
        PlayerPrefs.SetString("withdraw", "true");
        SceneLoader.Instance.LoadScene("HomePage");
    }

    public void setaddcash()
    {
        PlayerPrefs.SetString("addcash", "true");
        SceneLoader.Instance.LoadScene("HomePage");
    }

    public void LeaveGame()
    {
        this.gameObject.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
        Manager.Close();
        StopAllCoroutines();

        Screen.orientation = ScreenOrientation.LandscapeLeft;

    }

    #endregion

    #region win/loose

    public void showwin()
    {
        result.text = game_data.game_data[0].winning;

        if (
            game_data.game_data[0].winning == "0"
            || game_data.game_data[0].winning == "1"
            || game_data.game_data[0].winning == "3"
            || game_data.game_data[0].winning == "7"
            || game_data.game_data[0].winning == "9"
        )
        {
            resulttext.text = "Green";
            winimg.sprite = greensprite;
        }
        else
        {
            resulttext.text = "Red";
            winimg.sprite = redsprite;
        }

        if (int.Parse(game_data.game_data[0].winning) < 5)
        {
            smallbig.text = "Small";
        }
        else
        {
            smallbig.text = "Big";
        }
        winamounttext.text = VCPresultdata.win_amount.ToString();
        sec = 3;
        string time = "";
        if (gamemode == 1)
        {
            time = "1 Min";
        }
        else if (gamemode == 3)
        {
            time = "3 Min";
        }
        else if (gamemode == 0)
        {
            time = "30 Sec";
        }
        else if (gamemode == 6)
        {
            time = "5 Min";
        }
        periodtext.text = "Period: " + time + " " + game_data.game_data[0].id;
        winpanel.SetActive(true);
        InvokeRepeating("countdownpanel", 1, 3);
    }

    public void showlosose()
    {
        result2.text = game_data.game_data[0].winning;

        if (
            game_data.game_data[0].winning == "0"
            || game_data.game_data[0].winning == "1"
            || game_data.game_data[0].winning == "3"
            || game_data.game_data[0].winning == "7"
            || game_data.game_data[0].winning == "9"
        )
        {
            resulttext2.text = "Green";
            looseimg.sprite = greensprite;
        }
        else
        {
            resulttext2.text = "Red";
            looseimg.sprite = redsprite;
        }

        if (int.Parse(game_data.game_data[0].winning) < 5)
        {
            smallbig2.text = "Small";
        }
        else
        {
            smallbig2.text = "Big";
        }
        string time = "";
        if (gamemode == 1)
        {
            time = "1 Min";
        }
        else if (gamemode == 3)
        {
            time = "3 Min";
        }
        else if (gamemode == 0)
        {
            time = "30 Sec";
        }
        else if (gamemode == 6)
        {
            time = "5 Min";
        }
        periodtext2.text = "Period: " + time + " " + game_data.game_data[0].id;
        sec = 3;
        loosepanel.SetActive(true);

        StartCoroutine(countdown());

        //InvokeRepeating("countdownpanel", 1, 4);
    }

    IEnumerator countdown()
    {
        for (int i = 0; i < 3; i++)
        {
            autoclose.text = "AutoClose in " + sec + " Seconds";
            autoclose2.text = "AutoClose in " + sec + " Seconds";

            yield return new WaitForSeconds(1);

            sec--;
        }

        winpanel.SetActive(false);
        loosepanel.SetActive(false);
    }

    public void countdownpanel()
    {
        sec--;
        autoclose.text = "AutoClose in " + sec + " Seconds";
        autoclose2.text = "AutoClose in " + sec + " Seconds";
        if (sec == 0)
        {
            winpanel.SetActive(false);
            loosepanel.SetActive(false);
        }
    }

    #endregion

    public List<Sprite> m_gameHistorySpritesList;

    void SetOrientation(string orientation)
    {
        Application.ExternalCall("setDesiredOrientation", orientation);
    }

    private void OnEnable()
    {
        SetOrientation("portrait");
#if UNITY_ANDROID
        if (SceneManager.GetActiveScene().name == "ColorVerticle")
        {
            Debug.Log("RES_Check + Portrait");
            Screen.orientation = ScreenOrientation.Portrait;
        }
        else
        {
            Screen.orientation = ScreenOrientation.LandscapeLeft;
        }
#endif

        if (SceneManager.GetActiveScene().name == "ColorVerticle")
        {
            Debug.Log("RES_Check + Portrait");
            Screen.orientation = ScreenOrientation.Portrait;
        }
        else
        {
            Screen.orientation = ScreenOrientation.LandscapeLeft;
        }

        string walletString = Configuration.GetWallet();
        if (decimal.TryParse(walletString, out decimal userCoins))
        {
            if (userCoins > 10000)
            {
                useramount.text = (Configuration.GetWallet());
            }
            else
            {
                useramount.text = Configuration.GetWallet();
            }
        }
        Debug.Log($"Scene Name: {SceneManager.GetActiveScene().name}");
        //useramount.text = FormatNumber(Configuration.GetWallet());
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        Socket customNamespace = null;
        if (gamemode == 1)
            customNamespace = Manager.GetSocket("/color_prediction_1min");
        else if (gamemode == 3)
            customNamespace = Manager.GetSocket("/color_prediction_3min");
        else if (gamemode == 0)
            customNamespace = Manager.GetSocket("/color_prediction");
        else if (gamemode == 5)
            customNamespace = Manager.GetSocket("/color_prediction_5min");
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("color_prediction_1min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_3min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_5min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_1min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_3min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_5min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_status", OnVCP_statusResponse);
        if (!gamehistorybool)
            StartCoroutine(MyHistory());
        else
            StartCoroutine(GameHistory());
        Manager.Open();

        StartCoroutine(Fivecoingamehistory());

        m_ContractButton.ForEach(x => x.onClick.AddListener(() => SelectButton(x)));
        //betamountmiddle.onEndEdit.AddListener(OnChangeText);
    }

    public void SelectButton(Button iButton)
    {
        m_ContractButton.ForEach(x => x.GetComponent<Image>().color = Color.white);
        iButton.GetComponent<Image>().color = Color.green;
    }

    #region Socket Connect/Disconnect

    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("Connect : " + resp.sid);
        if (gamemode == 1)
            gametimetext.text = "1_Min";
        else if (gamemode == 3)
            gametimetext.text = "3_Min";
        else if (gamemode == 0)
            gametimetext.text = "30_Sec";
        else if (gamemode == 5)
            gametimetext.text = "5_Min";
        GetTimer();
    }

    public void OnDisconnected()
    {
        AudioManager._instance.StopAudio();
        Debug.Log("RES_Check + Disconnected");
    }

    public void Disconnect()
    {
        Manager.Close();
        AudioManager._instance.StopAudio();
        Screen.orientation = ScreenOrientation.LandscapeLeft;
#if UNITY_ANDROID
        this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
#elif UNITY_WEBGL
            SceneLoader.Instance.LoadScene("HomePage");
#endif
        //SceneManager.LoadScene("HomePage");
    }

    #endregion

    #region Minimize Code

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
        Manager.Close();
        invoke = false;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        Socket customNamespace = null;
        if (gamemode == 1)
            customNamespace = Manager.GetSocket("/color_prediction_1min");
        else if (gamemode == 3)
            customNamespace = Manager.GetSocket("/color_prediction_3min");
        else if (gamemode == 0)
            customNamespace = Manager.GetSocket("/color_prediction");
        else if (gamemode == 5)
            customNamespace = Manager.GetSocket("/color_prediction_5min");
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("color_prediction_1min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_3min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_5min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_1min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_3min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_5min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_status", OnVCP_statusResponse);
        if (!gamehistorybool)
            StartCoroutine(MyHistory());
        else
            StartCoroutine(GameHistory());
        Manager.Open();

        StartCoroutine(Fivecoingamehistory());
        GetTimer();
        reconnected = true;
        timetoinvoke = 0;
        StartCoroutine(invokefortime());

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
        {
            AudioManager._instance.StopEffect();
        }
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
        zerobet = 0;
        onebet = 0;
        twobet = 0;
        threebet = 0;
        fourbet = 0;
        fivebet = 0;
        sixbet = 0;
        sevenbet = 0;
        eightbet = 0;
        ninebet = 0;
        redbet = 0;
        greenbet = 0;
        violetbet = 0;
        upbet = 0;
        downbet = 0;
        for (int i = 0; i < coins.Count; i++)
        {
            coins[i].gameObject.SetActive(false);
        }
    }

    #endregion

    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;

        Socket customNamespace = null;
        if (gamemode == 1)
            customNamespace = Manager.GetSocket("/color_prediction_1min");
        else if (gamemode == 3)
            customNamespace = Manager.GetSocket("/color_prediction_3min");
        else if (gamemode == 0)
            customNamespace = Manager.GetSocket("/color_prediction");
        else if (gamemode == 5)
            customNamespace = Manager.GetSocket("/color_prediction_5min");
        try
        {
            if (gamemode == 1)
                customNamespace.Emit("color_prediction_1min_timer", "color_prediction_1min_timer");
            else if (gamemode == 3)
                customNamespace.Emit("color_prediction_3min_timer", "color_prediction_3min_timer");
            else if (gamemode == 0)
                customNamespace.Emit("color_prediction_timer", "color_prediction_timer");
            else if (gamemode == 5)
                customNamespace.Emit("color_prediction_5min_timer", "color_prediction_5min_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-color_prediction_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void VCP_TimerResponse(string args)
    {
        GameID.text = game_data.game_data[0].id;
        Debug.Log("RES_Value + Timer Game Json :" + args);
        Stopshowwait();
        try
        {
            betpaneltimer.text = args;
            //  timertext.text = args;

            int totalSeconds = int.Parse(args);
            TimeSpan time = TimeSpan.FromSeconds(totalSeconds);

            // Format the output as MM:SS
            string formattedTime = string.Format("{0:D2}:{1:D2}", time.Minutes, time.Seconds);

            timertext.text = formattedTime;

            panelcountdowntext.text = args;

            if (args == "5")
            {
                // if (PlayerPrefs.GetString("sound") == "on")
                // {
                //     NewAudioManager.instance.audioSource.clip = NewAudioManager.instance.countdownClip;

                //     NewAudioManager.instance.audioSource.Play();
                // }
                fivesecondscountdownpanel.SetActive(true);

                stoptext.text = "STOP BET";
                AudioManager._instance.PlayStopBetSound();
                Stop.SetActive(true);

                DOVirtual.DelayedCall(
                    2.1f,
                    () =>
                    {
                        Stop.SetActive(false);
                    }
                );
            }

            if (args == "1")
                StartCoroutine(changetimeto0());
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    IEnumerator changetimeto0()
    {
        yield return new WaitForSeconds(1f);
        timertext.text = "00:00";
        fivesecondscountdownpanel.SetActive(false);
    }

    public void Stopshowwait()
    {
        if (!gamestart)
        {
            AudioManager._instance.PlayPlaceBetSound();
            gamestart = true;
            showwaitanim.SetActive(false);
            StartCoroutine(showputbet());
        }
    }

    IEnumerator showputbet()
    {
        stoptext.text = "PLACE BET";
        Stop.SetActive(true);
        yield return new WaitForSeconds(2.1f);
        Stop.SetActive(false);
        // putbetanim.SetActive(true);
        // yield return new WaitForSeconds(2);
        // putbetanim.SetActive(false);
    }
    #endregion

    #region VCP Status

    private void OnVCP_statusResponse(string args)
    {
        Debug.Log("RES_Value Status: " + args);
        try
        {
            game_data = new CPVJSONResponse();
            game_data = JsonUtility.FromJson<CPVJSONResponse>(args);

            if (gamestart)
            {
                string game_id = Configuration.getcpid();
                bool isSameGame = game_id == game_data.game_data[0].id;

                if (reconnected)
                {
                    reconnected = false;
                    GameEnded = false;
                    if (isSameGame == false)
                        ClearGameObjects();
                    else
                    {
                        showamountonscreen();
                    }
                }

                PlayerPrefs.SetString("cpid", game_data.game_data[0].id);

                //showamountonscreen();
                if (game_data.game_data[0].status == "1" && !GameEnded)
                {
                    // gameaudio.clip = stopbet;
                    // gameaudio.Play();
                    // stoptext.text = "Stop BET";
                    // Stop.SetActive(true);


                    // DOVirtual.DelayedCall(2f, () =>
                    // {
                    //     Stop.SetActive(false);
                    // });


                    putbetanim.SetActive(false);
                    betpanel.SetActive(false);

                    gamestart = false;
                    GameEnded = true;
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

    #region History

    public void clickedmyhistory()
    {
        myhistpanel.SetActive(true);
        gamehistpanel.SetActive(false);
        StartCoroutine(MyHistory());
        myhistorybutton.sprite = selectedsprite;
        gamehistorybutton.sprite = normalbutton;
        gamehistorybool = false;
    }

    public void clickedgamehistory()
    {
        myhistpanel.SetActive(false);
        gamehistpanel.SetActive(true);
        StartCoroutine(GameHistory());
        myhistorybutton.sprite = normalbutton;
        gamehistorybutton.sprite = selectedsprite;
        gamehistorybool = true;
    }

    IEnumerator MyHistory()
    {
        for (int i = 0; i < historylist.Count; i++)
        {
            Destroy(historylist[i]);
        }

        historylist.Clear();

        string url = string.Empty;

        if (gamemode == 0)
            url = Configuration.Color_Prediction_myHistory;
        else if (gamemode == 1)
            url = Configuration.Color_Prediction_1Min_myHistory;
        else if (gamemode == 3)
            url = Configuration.Color_Prediction_3Min_myHistory;
        else if (gamemode == 5)
            url = Configuration.Color_Prediction_5Min_myHistory;

        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
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
                Debug.Log("RES_Check + my history response: " + request.downloadHandler.text);

                historygamedata = new historyGameDataList();

                historygamedata = JsonUtility.FromJson<historyGameDataList>(
                    request.downloadHandler.text
                );

                for (int i = 0; i < historygamedata.game_data.Count; i++)
                {
                    if (i <= 10)
                    {
                        int index = i;
                        Debug.Log("Mgame ID " + historygamedata.game_data[i].id);
                        GameObject go = Instantiate(historyprefab, historycontent);
                        historylist.Add(go);
                        go.transform.GetChild(0).GetChild(0).GetComponent<TextMeshProUGUI>().text =
                            historygamedata.game_data[i].color_prediction_id;
                        if (historygamedata.game_data[i].bet == "10")
                            go
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "Green";
                        else if (historygamedata.game_data[i].bet == "11")
                            go
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "Violet";
                        else if (historygamedata.game_data[i].bet == "12")
                            go
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "Red";
                        else if (historygamedata.game_data[i].bet == "15")
                            go
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "Big";
                        else if (historygamedata.game_data[i].bet == "16")
                            go
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "Small";
                        else
                            go
                                .transform.GetChild(1)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = historygamedata.game_data[i].bet;

                        if (historygamedata.game_data[i].bet == "0")
                        {
                            go.transform.GetChild(2).GetChild(0).gameObject.SetActive(false);
                            go.transform.GetChild(2).GetChild(1).gameObject.SetActive(true);
                            go
                                .transform.GetChild(2)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.red;
                            go
                                .transform.GetChild(2)
                                .GetChild(1)
                                .GetChild(1)
                                .GetComponent<Image>()
                                .color = new Color(108, 0, 255, 255);
                        }
                        else if (
                            historygamedata.game_data[i].bet == "1"
                            || historygamedata.game_data[i].bet == "3"
                            || historygamedata.game_data[i].bet == "7"
                            || historygamedata.game_data[i].bet == "9"
                        )
                        {
                            go.transform.GetChild(2).GetChild(0).gameObject.SetActive(true);
                            go.transform.GetChild(2).GetChild(1).gameObject.SetActive(false);
                            go
                                .transform.GetChild(2)
                                .GetChild(0)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.green;
                        }
                        else if (historygamedata.game_data[i].bet == "5")
                        {
                            go.transform.GetChild(2).GetChild(0).gameObject.SetActive(false);
                            go.transform.GetChild(2).GetChild(1).gameObject.SetActive(true);
                            go
                                .transform.GetChild(2)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.green;
                            go
                                .transform.GetChild(2)
                                .GetChild(1)
                                .GetChild(1)
                                .GetComponent<Image>()
                                .color = new Color(108, 0, 255, 255);
                        }
                        else if (
                            historygamedata.game_data[i].bet == "2"
                            || historygamedata.game_data[i].bet == "4"
                            || historygamedata.game_data[i].bet == "6"
                            || historygamedata.game_data[i].bet == "8"
                        )
                        {
                            go.transform.GetChild(2).GetChild(0).gameObject.SetActive(true);
                            go.transform.GetChild(2).GetChild(1).gameObject.SetActive(false);
                            go
                                .transform.GetChild(2)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.red;
                        }
                        else if (historygamedata.game_data[i].bet == "10")
                        {
                            go.transform.GetChild(2).GetChild(0).gameObject.SetActive(true);
                            go.transform.GetChild(2).GetChild(1).gameObject.SetActive(false);
                            go
                                .transform.GetChild(2)
                                .GetChild(0)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.green;
                        }
                        else if (historygamedata.game_data[i].bet == "11")
                        {
                            go.transform.GetChild(2).GetChild(0).gameObject.SetActive(true);
                            go.transform.GetChild(2).GetChild(1).gameObject.SetActive(false);
                            go
                                .transform.GetChild(2)
                                .GetChild(0)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.blue;
                        }

                        if (int.Parse(historygamedata.game_data[i].bet) < 5)
                        {
                            go
                                .transform.GetChild(3)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "SMALL";
                        }
                        else
                        {
                            if (int.Parse(historygamedata.game_data[i].bet) == 15)
                                go
                                    .transform.GetChild(3)
                                    .GetChild(0)
                                    .GetComponent<TextMeshProUGUI>()
                                    .text = "BIG";
                            else if (int.Parse(historygamedata.game_data[i].bet) == 16)
                                go
                                    .transform.GetChild(3)
                                    .GetChild(0)
                                    .GetComponent<TextMeshProUGUI>()
                                    .text = "Small";
                            else
                                go
                                    .transform.GetChild(3)
                                    .GetChild(0)
                                    .GetComponent<TextMeshProUGUI>()
                                    .text = "BIG";
                        }

                        if (historygamedata.game_data[i].winning_amount == "0.00")
                        {
                            go
                                .transform.GetChild(4)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "FAILED";
                            go
                                .transform.GetChild(4)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .color = Color.red;
                            go
                                .transform.GetChild(4)
                                .GetChild(1)
                                .GetComponent<TextMeshProUGUI>()
                                .color = Color.red;
                            go
                                .transform.GetChild(4)
                                .GetChild(1)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "-" + historygamedata.game_data[i].amount;
                        }
                        else
                        {
                            go
                                .transform.GetChild(4)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "SUCCESS";
                            go
                                .transform.GetChild(4)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .color = Color.green;
                            go
                                .transform.GetChild(4)
                                .GetChild(1)
                                .GetComponent<TextMeshProUGUI>()
                                .color = Color.green;
                            go
                                .transform.GetChild(4)
                                .GetChild(1)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "+" + historygamedata.game_data[i].winning_amount;
                        }

                        go.transform.GetComponent<Button>()
                            .onClick.AddListener(
                                () => ShowDetails(historygamedata.game_data[index].id)
                            );
                    }
                }
            }
        }
    }

    IEnumerator Fivecoingamehistory()
    {
        for (int i = 0; i < m_fiveGameDataList.Count; i++)
        {
            Destroy(m_fiveGameDataList[i]);
        }
        string url = string.Empty;

        if (gamemode == 0)
            url = Configuration.Color_Prediction_GameHistory;
        else if (gamemode == 1)
            url = Configuration.Color_Prediction_1Min_GameHistory;
        else if (gamemode == 3)
            url = Configuration.Color_Prediction_3Min_GameHistory;
        else if (gamemode == 5)
            url = Configuration.Color_Prediction_5Min_GameHistory;

        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
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
                Debug.Log("RES_Check + Game history response: " + request.downloadHandler.text);

                Gamehistory = new CPGameHistoryWinningData();

                Gamehistory = JsonUtility.FromJson<CPGameHistoryWinningData>(
                    request.downloadHandler.text
                );

                for (int i = 0; i < Gamehistory.last_winning.Count; i++)
                {
                    if (i <= 4)
                    {
                        GameObject go = Instantiate(fivegamehistoryPrefab, fivegamehistoryContent);
                        m_fiveGameDataList.Add(go);

                        var CoinImage = go.GetComponent<Image>();
                        var coinText = go.transform.GetChild(0).GetComponent<TextMeshProUGUI>();

                        var WinningText = Gamehistory.last_winning[i].winning;

                        if (
                            WinningText == "1"
                            || WinningText == "3"
                            || WinningText == "7"
                            || WinningText == "9"
                        )
                        {
                            //chipcolor green
                            CoinImage.sprite = m_gameHistorySpritesList[0]; // zero mean green
                            coinText.text = WinningText;
                        }
                        else if (
                            WinningText == "2"
                            || WinningText == "4"
                            || WinningText == "6"
                            || WinningText == "8"
                        )
                        {
                            CoinImage.sprite = m_gameHistorySpritesList[1]; // 1 mean red
                            coinText.text = WinningText;
                            //chipcolor red
                        }
                        else if (WinningText == "5")
                        {
                            CoinImage.sprite = m_gameHistorySpritesList[2]; // 2 mean greenpurple
                            coinText.text = WinningText;
                            // color green purple
                        }
                        else
                        {
                            CoinImage.sprite = m_gameHistorySpritesList[3]; // 3 mean greenpurple
                            coinText.text = WinningText;
                            // color red purple
                        }
                    }
                }
            }
        }
    }

    IEnumerator GameHistory()
    {
        for (int i = 0; i < Gamehistorylist.Count; i++)
        {
            Destroy(Gamehistorylist[i]);
        }

        Gamehistorylist.Clear();

        string url = string.Empty;

        if (gamemode == 0)
            url = Configuration.Color_Prediction_GameHistory;
        else if (gamemode == 1)
            url = Configuration.Color_Prediction_1Min_GameHistory;
        else if (gamemode == 3)
            url = Configuration.Color_Prediction_3Min_GameHistory;
        else if (gamemode == 5)
            url = Configuration.Color_Prediction_5Min_GameHistory;

        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
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
                Debug.Log("RES_Check + Game history response: " + request.downloadHandler.text);

                Gamehistory = new CPGameHistoryWinningData();

                Gamehistory = JsonUtility.FromJson<CPGameHistoryWinningData>(
                    request.downloadHandler.text
                );

                for (int i = 0; i < Gamehistory.last_winning.Count; i++)
                {
                    if (i <= 10)
                    {
                        GameObject go = Instantiate(Gamehistoryprefab, Gamehistorycontent);
                        Gamehistorylist.Add(go);
                        go.transform.GetChild(0).GetChild(0).GetComponent<TextMeshProUGUI>().text =
                            Gamehistory.last_winning[i].id;
                        go.transform.GetChild(1).GetChild(0).GetComponent<TextMeshProUGUI>().text =
                            Gamehistory.last_winning[i].winning;
                        if (Gamehistory.last_winning[i].winning == "0")
                        {
                            go.transform.GetChild(3).GetChild(0).gameObject.SetActive(false);
                            go.transform.GetChild(3).GetChild(1).gameObject.SetActive(true);
                            go
                                .transform.GetChild(3)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.red;
                            go
                                .transform.GetChild(3)
                                .GetChild(1)
                                .GetChild(1)
                                .GetComponent<Image>()
                                .color = new Color(108, 0, 255, 255);
                        }
                        else if (
                            Gamehistory.last_winning[i].winning == "1"
                            || Gamehistory.last_winning[i].winning == "3"
                            || Gamehistory.last_winning[i].winning == "7"
                            || Gamehistory.last_winning[i].winning == "9"
                        )
                        {
                            go.transform.GetChild(3).GetChild(0).gameObject.SetActive(true);
                            go.transform.GetChild(3).GetChild(1).gameObject.SetActive(false);
                            go
                                .transform.GetChild(3)
                                .GetChild(0)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.green;
                        }
                        else if (Gamehistory.last_winning[i].winning == "5")
                        {
                            go.transform.GetChild(3).GetChild(0).gameObject.SetActive(false);
                            go.transform.GetChild(3).GetChild(1).gameObject.SetActive(true);
                            go
                                .transform.GetChild(3)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.green;
                            go
                                .transform.GetChild(3)
                                .GetChild(1)
                                .GetChild(1)
                                .GetComponent<Image>()
                                .color = new Color(108, 0, 255, 255);
                        }
                        else if (
                            Gamehistory.last_winning[i].winning == "2"
                            || Gamehistory.last_winning[i].winning == "4"
                            || Gamehistory.last_winning[i].winning == "6"
                            || Gamehistory.last_winning[i].winning == "8"
                        )
                        {
                            go.transform.GetChild(3).GetChild(0).gameObject.SetActive(true);
                            go.transform.GetChild(3).GetChild(1).gameObject.SetActive(false);
                            go
                                .transform.GetChild(3)
                                .GetChild(1)
                                .GetChild(0)
                                .GetComponent<Image>()
                                .color = Color.red;
                        }

                        if (int.Parse(Gamehistory.last_winning[i].winning) < 5)
                        {
                            go
                                .transform.GetChild(2)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "SMALL";
                        }
                        else
                        {
                            go
                                .transform.GetChild(2)
                                .GetChild(0)
                                .GetComponent<TextMeshProUGUI>()
                                .text = "BIG";
                        }
                    }
                }
            }
        }
    }

    #endregion

    #region placebet

    private int MultiPlayerAmout = 1;

    public void OnChangeText()
    {
        MultiPlayerAmout = int.Parse(betamountmiddle.text);
        betamount = InitialAmt * MultiPlayerAmout;
        betamountpaneltext.text = "Total contract money is: " + InitialAmt * MultiPlayerAmout;
    }

    public void selectamount(int amount)
    {
        OnChangeText();
        //betamountpaneltext.text = "Total Contact money is: " + amount;
        //betamount = amount;
        //betamountmiddle.text = amount.ToString();
        InitialAmt = amount;
        betamount = InitialAmt * MultiPlayerAmout;
        betamountmiddle.text = MultiPlayerAmout.ToString();
        betamountpaneltext.text = "Total contract money is: " + InitialAmt * MultiPlayerAmout;
    }

    public void minus()
    {
        if (MultiPlayerAmout > 0)
        {
            MultiPlayerAmout -= 1;
            betamount = InitialAmt * MultiPlayerAmout;
            betamountpaneltext.text = "Total contract money is: " + betamount;
            //betamount -= InitialAmt;
            betamountmiddle.text = MultiPlayerAmout.ToString();
            //betamountpaneltext.text = "Total Contact money is: " + betamount;
        }
        else
        {
            Debug.Log("you don;t have coins");
        }
    }

    public void plus()
    {
        MultiPlayerAmout += 1;
        betamount = InitialAmt * MultiPlayerAmout;
        betamountpaneltext.text = "Total contract money is: " + betamount;
        betamountmiddle.text = MultiPlayerAmout.ToString();

        //  betamount += InitialAmt;
        //  betamountmiddle.text = betamount.ToString();
        //  betamountpaneltext.text = "Total Contact money is: " + betamount;
    }

    public void bet(string type)
    {
        if (gamestart)
        {
            switch (type)
            {
                case "10":
                    BetPopUpBetText.text = "Join " + "Green";
                    break;

                case "11":
                    BetPopUpBetText.text = "Join " + "Violet";
                    break;

                case "12":
                    BetPopUpBetText.text = "Join " + "Red";
                    break;

                case "15":
                    BetPopUpBetText.text = "Join " + "Big";
                    break;

                case "16":
                    BetPopUpBetText.text = "Join " + "Small";
                    break;

                default:
                    BetPopUpBetText.text = "Join " + type;
                    break;
            }

            bettype = type;
            if (bigbutton != true && smallbutton != true)
            {
                betpanel.SetActive(true);
            }
            else if (bigbutton == true && bettype == "16")
            {
                Debug.Log("RES_Check + Bet Placing on smallbutton");
                betpanel.SetActive(false);
                showtoastmessage("You cannot place the bet on Small", Color.red);
            }
            else if (smallbutton == true && bettype == "15")
            {
                Debug.Log("RES_Check + Bet Placing on bigbutton");
                betpanel.SetActive(false);
                showtoastmessage("You cannot place the bet on Big", Color.red);
            }
            else
            {
                Debug.Log("RES_Check + Bet Placing");
                betpanel.SetActive(true);
            }
        }
        else
            showtoastmessage("Please wait for the round to start", Color.gray);
    }

    public void placebet()
    {
        if (gamestart)
        {
            Debug.Log("RES_Check bet button");
            if (bigbutton == true && bettype == "16")
            {
                Debug.Log("RES_Check bet button 2");
                Debug.Log("RES_Check + BigButton");
                betpanel.SetActive(false);
                showtoastimage("You cannot place the bet as you have already place the bet on Big");
            }
            else if (smallbutton == true && bettype == "15")
            {
                Debug.Log("RES_Check bet button 3");
                Debug.Log("RES_Check + SamllButton");
                betpanel.SetActive(false);
                showtoastimage(
                    "You cannot place the bet as you have already place the bet on Small"
                );
            }
            else
            {
                Debug.Log("RES_Check bet button 4");
                Debug.Log("RES_Check + bets");
                PutBetAsync(bettype);
            }
        }
        else
            showtoastmessage("Please wait for the round to start", Color.gray);
    }

    [Serializable]
    public class CPTPutBetResponse
    {
        public string message;
        public string bet_id;
        public string wallet;
        public int code;
    }

    async void PutBetAsync(string bettype)
    {
        string url = string.Empty;

        if (gamemode == 0)
            url = Configuration.Color_Prediction_PutBet;
        else if (gamemode == 1)
            url = Configuration.Color_Prediction_1Min_PutBet;
        else if (gamemode == 3)
            url = Configuration.Color_Prediction_3Min_PutBet;
        else if (gamemode == 5)
            url = Configuration.Color_Prediction_5Min_PutBet;

        if (betamount == 0)
        {
            ShowToastmessage("Please Select Amount");
            return;
        }

        var form = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", game_data.game_data[0].id },
            { "bet", bettype },
            { "amount", betamount.ToString() },
        };

        Debug.Log(
            "RES_value "
                + betamount
                + " PutBet user_id "
                + Configuration.GetId()
                + " game_id "
                + game_data.game_data[0].id
                + " bet "
                + bettype
                + " amount "
                + betamount
        );

        if (bettype == "16")
        {
            smallbutton = true;
        }

        if (bettype == "15")
        {
            bigbutton = true;
        }

        CPTPutBetResponse betresp = new CPTPutBetResponse();
        betresp = await APIManager.Instance.PostRaw<CPTPutBetResponse>(url, form);

        CommonUtil.CheckLog($"Message::{betresp.message}Code::{betresp}");

        betpanel.SetActive(false);
        if (betresp.code == 200)
        {
            AudioManager._instance.PlayCoinDrop();
            showamountonscreenafterbet(int.Parse(bettype));

            //string walletString = Configuration.GetWallet();
            string walletString = betresp.wallet;
            if (decimal.TryParse(walletString, out decimal userCoins))
            {
                PlayerPrefs.SetString("wallet", userCoins.ToString("F2"));
                PlayerPrefs.Save();
                if (userCoins > 10000)
                {
                    useramount.text = FormatNumber(Configuration.GetWallet());
                }
                else
                {
                    useramount.text = FormatNumber(Configuration.GetWallet());
                    ;
                }
            }

            //useramount.text = FormatNumber(betresp.wallet);
        }
        else
        {
            ShowToastmessage(betresp.message);
        }
    }
    #endregion

    #region Show Bet amount

    public void showamountonscreen()
    {
        if (game_data.my_bet_0 != 0)
        {
            coins[0].gameObject.SetActive(true);
            coins[0].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_0.ToString();
        }
        if (game_data.my_bet_1 != 0)
        {
            coins[1].gameObject.SetActive(true);
            coins[1].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_1.ToString();
        }
        if (game_data.my_bet_2 != 0)
        {
            coins[2].gameObject.SetActive(true);
            coins[2].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_2.ToString();
        }
        if (game_data.my_bet_3 != 0)
        {
            coins[3].gameObject.SetActive(true);
            coins[3].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_3.ToString();
        }
        if (game_data.my_bet_4 != 0)
        {
            coins[4].gameObject.SetActive(true);
            coins[4].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_4.ToString();
        }
        if (game_data.my_bet_5 != 0)
        {
            coins[5].gameObject.SetActive(true);
            coins[5].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_5.ToString();
        }
        if (game_data.my_bet_6 != 0)
        {
            coins[6].gameObject.SetActive(true);
            coins[6].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_6.ToString();
        }
        if (game_data.my_bet_7 != 0)
        {
            coins[7].gameObject.SetActive(true);
            coins[7].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_7.ToString();
        }
        if (game_data.my_bet_8 != 0)
        {
            coins[8].gameObject.SetActive(true);
            coins[8].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_8.ToString();
        }
        if (game_data.my_bet_9 != 0)
        {
            coins[9].gameObject.SetActive(true);
            coins[9].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_9.ToString();
        }
        if (game_data.my_bet_10 != 0)
        {
            coins[10].gameObject.SetActive(true);
            coins[10].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_10.ToString();
        }
        if (game_data.my_bet_11 != 0)
        {
            coins[11].gameObject.SetActive(true);
            coins[11].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_11.ToString();
        }
        if (game_data.my_bet_12 != 0)
        {
            coins[12].gameObject.SetActive(true);
            coins[12].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_12.ToString();
        }
        if (game_data.my_bet_small != 0)
        {
            coins[14].gameObject.SetActive(true);
            coins[14].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_small.ToString();
        }
        if (game_data.my_bet_big != 0)
        {
            coins[13].gameObject.SetActive(true);
            coins[13].GetChild(0).GetComponent<TextMeshProUGUI>().text =
                game_data.my_bet_big.ToString();
        }
    }

    public void showamountonscreenafterbet(int bettype)
    {
        if (bettype == 0)
        {
            coins[0].gameObject.SetActive(true);
            zerobet += betamount;
            coins[0].GetChild(0).GetComponent<TextMeshProUGUI>().text = zerobet.ToString();
        }
        if (bettype == 1)
        {
            coins[1].gameObject.SetActive(true);
            onebet += betamount;
            coins[1].GetChild(0).GetComponent<TextMeshProUGUI>().text = onebet.ToString();
        }
        if (bettype == 2)
        {
            coins[2].gameObject.SetActive(true);
            twobet += betamount;
            coins[2].GetChild(0).GetComponent<TextMeshProUGUI>().text = twobet.ToString();
        }
        if (bettype == 3)
        {
            coins[3].gameObject.SetActive(true);
            threebet += betamount;
            coins[3].GetChild(0).GetComponent<TextMeshProUGUI>().text = threebet.ToString();
        }
        if (bettype == 4)
        {
            coins[4].gameObject.SetActive(true);
            fourbet += betamount;
            coins[4].GetChild(0).GetComponent<TextMeshProUGUI>().text = fourbet.ToString();
        }
        if (bettype == 5)
        {
            coins[5].gameObject.SetActive(true);
            fivebet += betamount;
            coins[5].GetChild(0).GetComponent<TextMeshProUGUI>().text = fivebet.ToString();
        }
        if (bettype == 6)
        {
            coins[6].gameObject.SetActive(true);
            sixbet += betamount;
            coins[6].GetChild(0).GetComponent<TextMeshProUGUI>().text = sixbet.ToString();
        }
        if (bettype == 7)
        {
            coins[7].gameObject.SetActive(true);
            sevenbet += betamount;
            coins[7].GetChild(0).GetComponent<TextMeshProUGUI>().text = sevenbet.ToString();
        }
        if (bettype == 8)
        {
            coins[8].gameObject.SetActive(true);
            eightbet += betamount;
            coins[8].GetChild(0).GetComponent<TextMeshProUGUI>().text = eightbet.ToString();
        }
        if (bettype == 9)
        {
            coins[9].gameObject.SetActive(true);
            ninebet += betamount;
            coins[9].GetChild(0).GetComponent<TextMeshProUGUI>().text = ninebet.ToString();
        }
        if (bettype == 10)
        {
            coins[10].gameObject.SetActive(true);
            greenbet += betamount;
            coins[10].GetChild(0).GetComponent<TextMeshProUGUI>().text = greenbet.ToString();
        }
        if (bettype == 11)
        {
            coins[11].gameObject.SetActive(true);
            violetbet += betamount;
            coins[11].GetChild(0).GetComponent<TextMeshProUGUI>().text = violetbet.ToString();
        }
        if (bettype == 12)
        {
            coins[12].gameObject.SetActive(true);
            redbet += betamount;
            coins[12].GetChild(0).GetComponent<TextMeshProUGUI>().text = redbet.ToString();
        }
        if (bettype == 15)
        {
            coins[14].gameObject.SetActive(true);
            downbet += betamount;
            coins[14].GetChild(0).GetComponent<TextMeshProUGUI>().text = downbet.ToString();
        }
        if (bettype == 16)
        {
            coins[13].gameObject.SetActive(true);
            upbet += betamount;
            coins[13].GetChild(0).GetComponent<TextMeshProUGUI>().text = upbet.ToString();
        }
    }

    #endregion

    #region buttons for time change

    public void changetime(int num)
    {
        gamestart = false;
        timertext.text = "00:00";
        putbetanim.SetActive(false);
        showwaitanim.SetActive(true);
        Manager.Close();
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        Socket customNamespace = null;
        Debug.Log("RES_Check + Checked + " + num);
        if (num == 1)
        {
            Debug.Log("RES_Check + Checked");
            if (gamemode != num)
            {
                showwaitanim.SetActive(true);
                GameEnded = false;
                zerobet = 0;
                onebet = 0;
                twobet = 0;
                threebet = 0;
                fourbet = 0;
                fivebet = 0;
                sixbet = 0;
                sevenbet = 0;
                eightbet = 0;
                ninebet = 0;
                redbet = 0;
                greenbet = 0;
                violetbet = 0;
                upbet = 0;
                downbet = 0;
                if (!gamehistorybool)
                    StartCoroutine(MyHistory());
                else
                    StartCoroutine(GameHistory());
                for (int i = 0; i < coins.Count; i++)
                {
                    coins[i].gameObject.SetActive(false);
                }
                gamemode = num;
                customNamespace = Manager.GetSocket("/color_prediction_1min");
                min1.GetComponent<Image>().enabled = true;
                min1.transform.GetChild(1).gameObject.SetActive(false);
                min3.GetComponent<Image>().enabled = false;
                min3.transform.GetChild(1).gameObject.SetActive(true);
                sec15.GetComponent<Image>().enabled = false;
                sec15.transform.GetChild(1).gameObject.SetActive(true);
                min5.GetComponent<Image>().enabled = false;
                min5.transform.GetChild(1).gameObject.SetActive(true);
                min10.GetComponent<Image>().enabled = false;
                min10.transform.GetChild(1).gameObject.SetActive(true);
            }
        }
        else if (num == 3)
        {
            Debug.Log("RES_Check + Checked");
            if (gamemode != num)
            {
                showwaitanim.SetActive(true);
                GameEnded = false;
                zerobet = 0;
                onebet = 0;
                twobet = 0;
                threebet = 0;
                fourbet = 0;
                fivebet = 0;
                sixbet = 0;
                sevenbet = 0;
                eightbet = 0;
                ninebet = 0;
                redbet = 0;
                greenbet = 0;
                violetbet = 0;
                upbet = 0;
                downbet = 0;
                if (!gamehistorybool)
                    StartCoroutine(MyHistory());
                else
                    StartCoroutine(GameHistory());
                for (int i = 0; i < coins.Count; i++)
                {
                    coins[i].gameObject.SetActive(false);
                }
                gamemode = num;
                customNamespace = Manager.GetSocket("/color_prediction_3min");
                min1.GetComponent<Image>().enabled = false;
                min1.transform.GetChild(1).gameObject.SetActive(true);
                min3.GetComponent<Image>().enabled = true;
                min3.transform.GetChild(1).gameObject.SetActive(false);
                sec15.GetComponent<Image>().enabled = false;
                sec15.transform.GetChild(1).gameObject.SetActive(true);
                min5.GetComponent<Image>().enabled = false;
                min5.transform.GetChild(1).gameObject.SetActive(true);
                min10.GetComponent<Image>().enabled = false;
                min10.transform.GetChild(1).gameObject.SetActive(true);
            }
        }
        else if (num == 0)
        {
            Debug.Log("RES_Check + Checked");
            if (gamemode != num)
            {
                showwaitanim.SetActive(true);
                GameEnded = false;
                zerobet = 0;
                onebet = 0;
                twobet = 0;
                threebet = 0;
                fourbet = 0;
                fivebet = 0;
                sixbet = 0;
                sevenbet = 0;
                eightbet = 0;
                ninebet = 0;
                redbet = 0;
                greenbet = 0;
                violetbet = 0;
                upbet = 0;
                downbet = 0;
                if (!gamehistorybool)
                    StartCoroutine(MyHistory());
                else
                    StartCoroutine(GameHistory());
                for (int i = 0; i < coins.Count; i++)
                {
                    coins[i].gameObject.SetActive(false);
                }
                gamemode = num;
                customNamespace = Manager.GetSocket("/color_prediction");
                min1.GetComponent<Image>().enabled = false;
                min1.transform.GetChild(1).gameObject.SetActive(true);
                min3.GetComponent<Image>().enabled = false;
                min3.transform.GetChild(1).gameObject.SetActive(true);
                sec15.GetComponent<Image>().enabled = true;
                sec15.transform.GetChild(1).gameObject.SetActive(false);
                min5.GetComponent<Image>().enabled = false;
                min5.transform.GetChild(1).gameObject.SetActive(true);
                min10.GetComponent<Image>().enabled = false;
                min10.transform.GetChild(1).gameObject.SetActive(true);
            }
        }
        else if (num == 5)
        {
            Debug.Log("RES_Check + Checked");
            if (gamemode != num)
            {
                showwaitanim.SetActive(true);
                GameEnded = false;
                zerobet = 0;
                onebet = 0;
                twobet = 0;
                threebet = 0;
                fourbet = 0;
                fivebet = 0;
                sixbet = 0;
                sevenbet = 0;
                eightbet = 0;
                ninebet = 0;
                redbet = 0;
                greenbet = 0;
                violetbet = 0;
                upbet = 0;
                downbet = 0;
                if (!gamehistorybool)
                    StartCoroutine(MyHistory());
                else
                    StartCoroutine(GameHistory());
                for (int i = 0; i < coins.Count; i++)
                {
                    coins[i].gameObject.SetActive(false);
                }
                gamemode = num;
                customNamespace = Manager.GetSocket("/color_prediction_5min");
                Debug.Log("RES_Check + Checked 2");
                min1.GetComponent<Image>().enabled = false;
                min1.transform.GetChild(1).gameObject.SetActive(true);
                min3.GetComponent<Image>().enabled = false;

                min3.transform.GetChild(1).gameObject.SetActive(true);
                sec15.GetComponent<Image>().enabled = false;
                sec15.transform.GetChild(1).gameObject.SetActive(true);
                min5.GetComponent<Image>().enabled = true;
                min5.transform.GetChild(1).gameObject.SetActive(false);
                min10.GetComponent<Image>().enabled = false;
                min10.transform.GetChild(1).gameObject.SetActive(true);
                Debug.Log("RES_Check + Checked 3");
            }
        }
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("color_prediction_1min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_3min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_5min_timer", VCP_TimerResponse);
        customNamespace.On<string>("color_prediction_1min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_3min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_5min_status", OnVCP_statusResponse);
        customNamespace.On<string>("color_prediction_status", OnVCP_statusResponse);
        Manager.Open();
        if (!gamehistorybool)
            StartCoroutine(MyHistory());
        else
            StartCoroutine(GameHistory());

        StartCoroutine(Fivecoingamehistory());
    }

    #endregion

    #region Format Number

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

    #endregion

    #region DisplayWinner

    IEnumerator win()
    {
        Debug.Log("RES_Check + winning " + int.Parse(game_data.game_data[0].winning));
        AudioManager._instance.PlayHighlightWinSound();
        winninghighlight[int.Parse(game_data.game_data[0].winning)].gameObject.SetActive(true);
        yield return new WaitForSeconds(3);
        winninghighlight[int.Parse(game_data.game_data[0].winning)].gameObject.SetActive(false);
        showwaitanim.SetActive(true);
        GameEnded = false;
        StartCoroutine(GetResult());
        updatedataAsync(PlayerPrefs.GetString("Mypass"), PlayerPrefs.GetString("Mymobile"));
        for (int i = 0; i < coins.Count; i++)
        {
            coins[i].gameObject.SetActive(false);
        }
        zerobet = 0;
        onebet = 0;
        twobet = 0;
        threebet = 0;
        fourbet = 0;
        fivebet = 0;
        sixbet = 0;
        sevenbet = 0;
        eightbet = 0;
        ninebet = 0;
        redbet = 0;
        greenbet = 0;
        violetbet = 0;
        upbet = 0;
        downbet = 0;
        smallbutton = false;
        bigbutton = false;
        if (!gamehistorybool)
            StartCoroutine(MyHistory());
        else
            StartCoroutine(GameHistory());
    }

    public async void updatedataAsync(string Password, string Id) //string Token)
    {
        Debug.Log("Login");
        string url = Configuration.Url + Configuration.profile;
        Debug.Log("RES_Check + API-Call + profile");
        var form = new Dictionary<string, string>
        {
            { "fcm", "" },
            { "app_version", "1" },
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        newLogInOutputs LogInOutput = await APIManager.Instance.Post<newLogInOutputs>(url, form);

        if (LogInOutput.code == 200)
        {
            for (int i = 0; i < LogInOutput.user_data.Count; i++)
            {
                float oldwallet = float.Parse(Configuration.GetWallet());
                Debug.Log(" Data : " + LogInOutput.user_data[i]);
                PlayerPrefs.SetString("wallet", LogInOutput.user_data[i].wallet);
                PlayerPrefs.Save();
                Debug.Log("RES_Check + New Amount " + Configuration.GetWallet());

                string walletString = Configuration.GetWallet();
                if (decimal.TryParse(walletString, out decimal userCoins))
                {
                    if (userCoins > 10000)
                    {
                        useramount.text = (Configuration.GetWallet());
                    }
                    else
                    {
                        useramount.text = Configuration.GetWallet();
                    }
                }

                //useramount.text = FormatNumber(Configuration.GetWallet());
            }
        }
    }

    public IEnumerator GetResult()
    {
        Debug.Log("Login");
        string Url = string.Empty;
        if (gamemode == 0)
            Url = Configuration.VCPResult;
        else if (gamemode == 1)
            Url = Configuration.VCP1MinResult;
        else if (gamemode == 3)
            Url = Configuration.VCP3MinResult;
        else if (gamemode == 5)
            Url = Configuration.VCP5MinResult;
        Debug.Log("RES_Check + API-Call + VCP Result");
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("game_id", game_data.game_data[0].id);
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);
        Debug.Log(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + game_data.game_data[0].id
        );
        Debug.Log("RES_Check + URL + " + Url);
        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + Result: " + responseText);
            VCPresultdata = JsonConvert.DeserializeObject<VCPBetResult>(responseText);
            if (VCPresultdata.code == 101)
            {
                yield return null;
            }
            else if (VCPresultdata.code == 102)
            {
                AudioManager._instance.PlayWinSound();
                showwin();
                showtoastmessage(
                    "congratulation!! You Won " + VCPresultdata.win_amount,
                    Color.green
                );
            }
            else if (VCPresultdata.code == 103)
            {
                AudioManager._instance.PlayLoseSound();
                showlosose();
                showtoastmessage(
                    "Better Luck Next Time, You Lost " + VCPresultdata.diff_amount,
                    Color.red
                );
            }
        }
        else
        {
            Debug.Log("RES_Check + Result + " + www.result);
        }
        StartCoroutine(Fivecoingamehistory());
    }

    #endregion


    #region Details for each history

    public void ShowDetails(string betid)
    {
        StartCoroutine(DetailHistory(betid));
    }

    IEnumerator DetailHistory(string betid)
    {
        string url = string.Empty;

        if (gamemode == 0)
            url = Configuration.VCPmindetails;
        else if (gamemode == 1)
            url = Configuration.VCP1mindetails;
        else if (gamemode == 3)
            url = Configuration.VCP3mindetails;
        else if (gamemode == 5)
            url = Configuration.VCP5mindetails;

        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
        form.AddField("bet_id", betid);

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
                Debug.Log("RES_Check + history details: " + request.downloadHandler.text);

                BetRootObject obj = new BetRootObject();

                obj = JsonConvert.DeserializeObject<BetRootObject>(request.downloadHandler.text);

                content.transform.GetChild(0).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    obj.bet_id_details.id;
                content.transform.GetChild(1).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    game_data.game_data[0].id;
                content.transform.GetChild(2).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    obj.bet_id_details.amount;
                content.transform.GetChild(3).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    obj.bet_id_details.user_amount;
                content.transform.GetChild(4).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    obj.bet_id_details.comission_amount;
                content.transform.GetChild(5).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    obj.bet_id_details.winning;

                //   content.transform.GetChild(6).GetChild(1).GetComponent<TextMeshProUGUI>().text = obj.bet_id_details.bet;

                Debug.Log("obj.bet_id_details.bet" + obj.bet_id_details.bet);

                if (obj.bet_id_details.winning_amount == "0.00")
                    content.transform.GetChild(6).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "Failed";
                else
                    content.transform.GetChild(6).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "Passed";
                if (obj.bet_id_details.winning_amount == "0.00")
                    content.transform.GetChild(7).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "-0.0";
                else
                    content.transform.GetChild(7).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        obj.bet_id_details.winning_amount;
                content.transform.GetChild(8).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                    obj.bet_id_details.added_date;

                if (obj.bet_id_details.bet == "10")
                {
                    content.transform.GetChild(9).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "green";
                }
                else if (obj.bet_id_details.bet == "11")
                {
                    content.transform.GetChild(9).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "violet";
                }
                else if (obj.bet_id_details.bet == "12")
                {
                    content.transform.GetChild(9).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "red";
                }
                else if (obj.bet_id_details.bet == "15")
                {
                    content.transform.GetChild(9).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "big";
                }
                else if (obj.bet_id_details.bet == "16")
                {
                    content.transform.GetChild(9).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        "small";
                }
                else
                {
                    content.transform.GetChild(9).GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        obj.bet_id_details.bet;
                }

                //   history.transform.localScale = Vector3.zero;
                history.SetActive(true);
                //  history.transform.DOScale(new Vector3(0.5231143f, 0.5231143f, 0.5231143f), .5f).SetEase(Ease.Linear);
            }
        }
    }

    #endregion

    public void showtoastmessage(string message, Color colour)
    {
        //StartCoroutine(showtoastimage(message));
        Toast.Show(message, 3f, colour);
    }

    public void ShowToastmessage(string message)
    {
        //StartCoroutine(showtoastimage(message));
        Toast.Show(message, 3f);
    }

    IEnumerator showtoastimage(string message)
    {
        toastimage.gameObject.SetActive(true);
        toastimage.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = message;
        yield return new WaitForSeconds(2);
        toastimage.gameObject.SetActive(false);
    }
}
