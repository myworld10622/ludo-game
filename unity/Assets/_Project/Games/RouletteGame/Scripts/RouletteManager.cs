using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using AndroApps;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
//using DG.Tweening;
using EasyUI.Toast;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class RouletteManager : MonoBehaviour
{
    public string custom_namespace = "/roulette";
    public GameObject m_histroyPenal;

    [Header("Controller Details")]
    private SocketManager Manager;

    [Header("Status data")]
    public RouletteRootObject RouletteData;
    public bool placed_previous_bet = false;

    [Header("Text Anim")]
    public GameObject putbetanim,
        increaseamount;

    [Header("Round Timer Text")]
    public GameObject timertext;

    [Header("Game Users")]
    public GameObject Userprofile;

    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;

    [Header("Animals Last Winning Data")]
    public List<Sprite> gameroulette_Listsprites;
    public List<string> lastwinning;
    public List<Image> lastwinningdata;

    public NumberDetails RedNumbersDetails;
    public NumberDetails BlackNumbersDetails;

    [Header("Win related GameObjects")]
    public float interval = 0.1f;
    public string resultString;
    public List<Transform> gameObjects;

    [Header("Buttons")]
    public List<GameObject> btns;

    [Header("put bet json variables")]
    public RoulettePutBetResponse betresp;
    public bool putbetbool;

    public List<MeshRenderer> meshes;

    public BetSpace[] betspace;

    public SceneRoulette roul;

    public TextMeshProUGUI resulttext;

    public GameObject Reconn_Canvas;
    public newLogInOutputs LogInOutput;
    public RouletteBetResult rouletteResult;
    public string betamount;
    public int bet;
    public GameObject buttonclicked;
    public int num;

    private int currentIndex = 0;
    private bool stopAtResult = false,
        online;
    private int totalcoinsleft,
        left1,
        left2;
    private bool GameEnded,
        gamestart,
        click,
        showrecord,
        timeshow;
    public float amount;

    public List<Button> coinbtns;

    public TextMeshProUGUI resultText;

    public WinSequence sequence;

    List<BetSpace> objectsWithScript = new List<BetSpace>();

    public static bool canbet = false;

    public static bool destroychip = false;

    public float oldwallet;

    public GameObject textprefab,
        parentoftext;

    public Image toastimage;

    public List<string> lastwinningprediction;
    public List<TextMeshProUGUI> lastwinningpredictiontext;

    public GameObject historyprediction;

    public Button[] buttons;

    public static List<GameObject> coinsinstantiated;

    public GameObject exitPanel,
        splitsbet;

    private Dictionary<string, int> betMappings;

    public GameObject Stop;

    public Text stoptext;

    public Toggle soundToggle;
    public Toggle musicToggle;

    public GameObject WinPopup;
    public Text WinPopupText;
    public Animator WinAnimator;
    public int total_bet;
    public TextMeshPro total_text;
    public bool show_history;
    public TextMeshProUGUI winresulttext;
    public List<GameObject> bets;

    private void OnEnable()
    {
        #region Assign Dictionary for bet value

        buttonclick(1);

        clickedbutton(btns[0]);

        btns[0].GetComponent<Chip>().OnClick();

        var buttonsz = Resources.FindObjectsOfTypeAll<Button>();
        betspace = FindObjectsOfType<BetSpace>();
        foreach (Button button in buttonsz)
        {
            button.onClick.AddListener(() => AudioManager._instance.ButtonClick());
        }

        betMappings = new Dictionary<string, int>()
        {
            { "00", -1 },
            { "1st 12", 37 },
            { "2nd 12", 38 },
            { "3rd 12", 39 },
            { "1to18", 40 },
            { "19to36", 41 },
            { "even", 42 },
            { "odd", 43 },
            { "red", 44 },
            { "black", 45 },
            { "2to1row1", 46 },
            { "2to1row2", 47 },
            { "2to1row3", 48 },
            { "1and2", 49 },
            { "2and3", 50 },
            { "4and5", 51 },
            { "5and6", 52 },
            { "7and8", 53 },
            { "8and9", 54 },
            { "10and11", 55 },
            { "11and12", 56 },
            { "13and14", 57 },
            { "14and15", 58 },
            { "16and17", 59 },
            { "17and18", 60 },
            { "19and20", 61 },
            { "20and21", 62 },
            { "22and23", 63 },
            { "23and24", 64 },
            { "25and26", 65 },
            { "26and27", 66 },
            { "28and29", 67 },
            { "29and30", 68 },
            { "31and32", 69 },
            { "32and33", 70 },
            { "34and35", 71 },
            { "35and36", 72 },
            { "0and1", 73 },
            { "0and2", 74 },
            { "0and3", 75 },
            { "1and4", 76 },
            { "2and5", 77 },
            { "3and6", 78 },
            { "4and7", 79 },
            { "5and8", 80 },
            { "6and9", 81 },
            { "7and10", 82 },
            { "8and11", 83 },
            { "9and12", 84 },
            { "10and13", 85 },
            { "11and14", 86 },
            { "12and15", 87 },
            { "13and16", 88 },
            { "14and17", 89 },
            { "15and18", 90 },
            { "16and19", 91 },
            { "17and20", 92 },
            { "18and21", 93 },
            { "19and22", 94 },
            { "20and23", 95 },
            { "21and24", 96 },
            { "22and25", 97 },
            { "23and26", 98 },
            { "24and27", 99 },
            { "25and28", 100 },
            { "26and29", 101 },
            { "27and30", 102 },
            { "28and31", 103 },
            { "29and32", 104 },
            { "30and33", 105 },
            { "31and34", 106 },
            { "32and35", 107 },
            { "33and36", 108 },
            { "01and2", 109 },
            { "02and3", 110 },
            { "124n5", 111 },
            { "235n6", 112 },
            { "457n8", 113 },
            { "568n9", 114 },
            { "7810n11", 115 },
            { "8911n12", 116 },
            { "10to14", 117 },
            { "11to15", 118 },
            { "13to17", 119 },
            { "14to18", 120 },
            { "16to20", 121 },
            { "17to21", 122 },
            { "19to23", 123 },
            { "20to24", 124 },
            { "22to26", 125 },
            { "23to27", 126 },
            { "25to29", 127 },
            { "26to30", 128 },
            { "28to32", 129 },
            { "29to33", 130 },
            { "31to35", 131 },
            { "32to36", 132 },
        };

        #endregion
        //placed_previous_bet = false;
        UserNameText.text = Configuration.GetName();
        UserWalletText.text = FormatNumber(Configuration.GetWallet());
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
        var url = Configuration.BaseSocketUrl;
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(custom_namespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        Manager.Open();
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("roulette_timer", Onroulette_TimerResponse);
        customNamespace.On<string>("roulette_status", OnrouletteStatus_statusResponse);

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);

        FindLastInteractableButton(float.Parse(Configuration.GetWallet()), coinbtns);
    }

    private void FindLastInteractableButton(float walletAmount, List<Button> buttons)
    {
        for (int i = buttons.Count - 1; i >= 0; i--)
        {
            if (
                float.TryParse(buttons[i].name, out float buttonValue)
                && walletAmount >= buttonValue
            )
            {
                buttons[i].interactable = true;
            }
            else
            {
                buttons[i].interactable = false;
            }
        }
    }

    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;

    public void OnButtonClickPartical(int buttonIndex)
    {
        ParticleSystem newParticle = particleSystems[buttonIndex];
        if (currentParticle == newParticle && currentParticle.isPlaying)
        {
            currentParticle.Stop();
            currentParticle = null;
        }
        else
        {
            if (currentParticle != null)
            {
                currentParticle.Stop();
            }
            if (newParticle != null)
            {
                newParticle.Play();
                currentParticle = newParticle;
            }
        }
    }
    #endregion
    #region Socket Connection/DisConnection
    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("RES_Check + Connect : " + resp.sid);
        //ControllerDetail.IsConnection = true;
        //Reconn_Canvas.SetActive(false);
        Debug.Log("RES_Check + timer event call");
        splitsbet.SetActive(true);
        GetTimer();
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
            RouletteAudioManager.MusicStop();
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

    public void getbetint(int bet) { }

    int ExtractNumber(string numberString)
    {
        string numberSubstring = "";

        for (int i = 0; i < numberString.Length; i++)
        {
            if (char.IsDigit(numberString[i]))
            {
                numberSubstring = numberString.Substring(i);
                break;
            }
        }

        int result = 0;

        if (int.TryParse(numberSubstring, out result))
        {
            if (result >= 0 && result <= 36)
            {
                return result;
            }
        }

        return -1;
    }

    private int TotalBetAmount;

    private void Update()
    {
        if (Input.GetMouseButtonDown(0))
        {
            if (Input.touchCount > 1)
                return;

            Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);
            RaycastHit hit;

            if (Physics.Raycast(ray, out hit))
            {
                BoxCollider boxCollider = hit.collider.GetComponent<BoxCollider>();

                if (boxCollider != null)
                {
                    float amount =
                        float.Parse(betamount) + boxCollider.GetComponent<BetSpace>().total_bet;

                    if (amount > 3000)
                    {
                        showtoastmessage("Bet more than 3000 cannot be placed on a single number");
                        return;
                    }
                    string objectName = boxCollider.gameObject.name;

                    Debug.Log("RES_Check + Bet gameobject name " + objectName);

                    if (objectName == "high00")
                    {
                        bet = -1;
                    }
                    else
                    {
                        // Check if the object name exists in the dictionary
                        if (betMappings.TryGetValue(objectName, out int betValue))
                        {
                            bet = betValue;
                        }
                        else
                        {
                            bet = ExtractNumber(objectName); // Handle the case for numbers
                        }
                    }

                    if (RouletteData.game_data[0].status == "0")
                    {
                        if (betamount != string.Empty && canbet)
                        {
                            Debug.Log("Wallet::" + Configuration.GetWallet());
                            if (float.Parse(Configuration.GetWallet()) <= float.Parse(betamount))
                            {
                                showtoastmessage("Insufficient Balance");
                                ChipManager.IsBetSuccess = false;
                            }
                            else
                            {
                                ChipManager.IsBetSuccess = true;
                                boxCollider.GetComponent<BetSpace>().ApplyBetFromManager();
                                GameObject prefab = Instantiate(textprefab, parentoftext.transform);
                                prefab.gameObject.SetActive(true);
                                //newAudioManager.PlayClip(newAudioManager.coinsoundclip);
                                StartCoroutine(PutBet(prefab));
                                TotalBetAmount += int.Parse(betamount);
                            }
                        }
                        else
                        {
                            if (betamount == string.Empty)
                                showtoastmessage("Please select your amount");
                        }
                    }
                }
                else
                {
                    Debug.Log("Clicked on collider, but not a BoxCollider.");
                }
            }
        }
    }

    public void OnDisconnected()
    {
        Reconn_Canvas.SetActive(true);
    }

    public void Disconnect()
    {
        //Manager.Close();
        //var customNamespaceSocket = Manager.GetSocket(ControllerDetail.CustomNamespace);
        //customNamespaceSocket.Disconnect();
        //StopAllCoroutines(); //
        //Debug.Log("Disconnected: ");
        SceneManager.LoadScene("Main");
    }

    #endregion

    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;
        //Debug.Log("RES_Check" + " EMIT-roulette_timer 1");
        var customNamespace = Manager.GetSocket(custom_namespace);
        Debug.Log("RES_Check" + " EMIT-roulette_timer 2");
        try
        {
            //Debug.Log("RES_Check" + " EMIT-roulette_timer 3");
            //customNamespace.Emit("roulette_timer", "roulette_timer");

            //Debug.Log("RES_CHECK");
            //Debug.Log("RES_CHECK" + " EMIT-roulette_timer 4");
        }
        catch (System.Exception e)
        {
            Debug.LogError("RES_Check + Try error " + e.ToString());
        }
    }

    private void Onroulette_TimerResponse(string args)
    {
        Debug.Log("RES_Value + Timer :" + args);
        if (!timeshow)
            Stopshowwait();

        timertext.GetComponent<TextMeshPro>().text = args;
    }
    #endregion

    #region History Prediction

    public void showhistory(GameObject obj)
    {
        if (lastwinningprediction.Count > 0)
        {
            obj.SetActive(true);
        }
        else
        {
            Toast.Show("Please play atleast 1 round to view winning history", 1.5f);
        }
    }

    public void ShowHistoryPrediction()
    {
        lastwinningprediction.Clear();
        for (int i = 0; i < RouletteData.last_winning.Count; i++)
        {
            lastwinningprediction.Add(RouletteData.last_winning[i].winning);
        }
        for (int i = 0; i < lastwinningpredictiontext.Count; i++)
        {
            Debug.Log(" Roulette winning count " + RouletteData.last_winning.Count);
            if (RouletteData.last_winning.Count > i)
            {
                if (lastwinningprediction[i] == "-1")
                {
                    lastwinningpredictiontext[i].text = "00";
                    string hexColor = "#157F32"; // Example Hex Code
                    if (ColorUtility.TryParseHtmlString(hexColor, out Color newColor))
                    {
                        lastwinningpredictiontext[i].transform.parent.GetComponent<Image>().color =
                            newColor;
                    }
                    else
                    {
                        Debug.LogError("Invalid Hex Color!");
                    }
                }
                else
                {
                    lastwinningpredictiontext[i].text = lastwinningprediction[i];
                    if (RedNumbersDetails.NumbersList.Contains(lastwinningprediction[i]))
                    {
                        lastwinningpredictiontext[i].transform.parent.GetComponent<Image>().color =
                            RedNumbersDetails.ColorSet;
                    }
                    else if (BlackNumbersDetails.NumbersList.Contains(lastwinningprediction[i]))
                    {
                        lastwinningpredictiontext[i].transform.parent.GetComponent<Image>().color =
                            BlackNumbersDetails.ColorSet;
                    }
                    else if (lastwinningprediction[i] == "0")
                    {
                        string hexColor = "#157F32"; // Example Hex Code
                        if (ColorUtility.TryParseHtmlString(hexColor, out Color newColor))
                        {
                            lastwinningpredictiontext[i]
                                .transform.parent.GetComponent<Image>()
                                .color = newColor;
                        }
                        else
                        {
                            Debug.LogError("Invalid Hex Color!");
                        }
                    }
                }
            }
        }
    }

    public IEnumerator winninghighlight(string obj)
    {
        string objtofind = "high" + obj;
        yield return new WaitForSeconds(1);
        Debug.Log("RES_Check + obj to highlight " + objtofind);
        GameObject objtohighlight = GameObject.Find(objtofind);
        objtohighlight.GetComponent<MeshRenderer>().enabled = true;
        yield return new WaitForSeconds(0.5f);
        objtohighlight.GetComponent<MeshRenderer>().enabled = false;
        yield return new WaitForSeconds(0.5f);
        objtohighlight.GetComponent<MeshRenderer>().enabled = true;
        yield return new WaitForSeconds(0.5f);
        objtohighlight.GetComponent<MeshRenderer>().enabled = false;
        yield return new WaitForSeconds(0.5f);
        objtohighlight.GetComponent<MeshRenderer>().enabled = true;
        yield return new WaitForSeconds(0.5f);
        objtohighlight.GetComponent<MeshRenderer>().enabled = false;
    }

    #endregion

    #region roulette_Status

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

    private void OnrouletteStatus_statusResponse(string args)
    {
        Debug.Log("RES_Value + Roulette Status JSON: " + args);
        RouletteData = JsonUtility.FromJson<RouletteRootObject>(args);
        displayprofiles();
        Debug.Log("RES_Value + Game Status " + RouletteData.game_data[0].status);
        if (!show_history)
        {
            show_history = true;
            ShowHistoryPrediction();
        }
        if (gamestart)
        {
            if (RouletteData.game_data[0].status == "1")
            {
                Debug.Log("RES_Check + Game Ended");
                Debug.Log("RES_Value + Game Status 1");
                timertext.GetComponent<TextMeshPro>().text = "0";
                //if (betresp.wallet != string.Empty)
                //    amount = float.Parse(betresp.wallet);
                //else
                //{
                //    oldwallet = float.Parse(Configuration.GetWallet());
                //    amount = 0;
                //}
                putbetanim.SetActive(false);
                gamestart = false;
                GameEnded = true;
                roul.OnButtonRoll();
                StartCoroutine(restart());
                //wininitializing();
                //StartCoroutine(win());
            }
        }
    }

    IEnumerator restart()
    {
        AudioManager._instance.PlayStopBetSound();
        stoptext.text = "STOP BET";
        Stop.SetActive(true);
        DOVirtual.DelayedCall(
            2f,
            () =>
            {
                Stop.SetActive(false);
            }
        );

        yield return new WaitForSeconds(16);
        StartCoroutine(GetResult());
        yield return new WaitForSeconds(1);
        canbet = false;
        for (int i = 0; i < betspace.Length; i++)
        {
            betspace[i].betnum = 0;
        }
        //showwaitanim.SetActive(true);
        SceneRoulette._Instance.clearButton.interactable = true;
        SceneRoulette._Instance.undoButton.interactable = true;
        timeshow = false;
        GameEnded = false;
        StartCoroutine(updatedata());
        ShowHistoryPrediction();
        placed_previous_bet = false;
        foreach (MeshRenderer mesh in meshes)
            mesh.enabled = false;
        //SceneManager.LoadScene("EuropeanRoulette_mobile");
    }

    public IEnumerator updatedata() //string Token)
    {
        Debug.Log("Login");
        string Url = Configuration.Url + Configuration.profile;
        Debug.Log("RES_Check + API-Call + profile");
        WWWForm form = new WWWForm();
        form.AddField("fcm", "");
        form.AddField("app_version", "1");
        form.AddField("id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + Login: " + responseText);
            LogInOutput = new newLogInOutputs();
            //logInModule.LogInOutput = JsonUtility.FromJson<LogInOutputs>(responseText.ToString());
            LogInOutput = JsonConvert.DeserializeObject<newLogInOutputs>(responseText);
            if (LogInOutput.code == 200)
            {
                for (int i = 0; i < LogInOutput.user_data.Count; i++)
                {
                    Debug.Log(" Data : " + LogInOutput.user_data[i]);
                    PlayerPrefs.SetString("wallet", LogInOutput.user_data[i].wallet);
                    PlayerPrefs.Save();
                    Debug.Log("RES_Check + oldwallet + " + oldwallet);
                    Debug.Log("RES_Check + new wallet + " + LogInOutput.user_data[i].wallet);
                    //float newwallet = float.Parse(LogInOutput.user_data[i].wallet);
                    //float won = newwallet - oldwallet;
                    //if (won == 0)
                    //{
                    //    Debug.Log("RES_Check + Bet not placed");
                    //}
                    //else if (won > 0)
                    //{
                    //    Debug.Log("RES_Check + Amount Won " + won);
                    //    showtoastmessage("congratulation!! You won " + won);
                    //}
                    //else
                    //{
                    //    Debug.Log("RES_Check + Amount Lost " + won);
                    //    showtoastmessage("Better luck next time! You Lost " + won);
                    //}

                    Debug.Log("RES_Check + New Amount " + Configuration.GetWallet());
                    //oldwallet = float.Parse(LogInOutput.user_data[i].wallet);
                    string walletString = Configuration.GetWallet();
                    UserWalletText.text = CommonUtil.GetFormattedWallet();
                    //Userprofile.transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(Configuration.GetWallet()); ;
                }
            }
            else
            {
                Debug.Log("error" + www.error);
            }
        }
        yield return null;
    }

    public void CALL_Leave_table()
    {
        Manager.Close();
        StopAllCoroutines();
        this.gameObject.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
    }

    public IEnumerator GetResult()
    {
        Debug.Log("Login");
        string Url = Configuration.rouletteresult;
        Debug.Log("RES_Check + API-Call + Roluette Result");
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("game_id", RouletteData.game_data[0].id);
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + Result: " + responseText);
            rouletteResult = new RouletteBetResult();
            rouletteResult = JsonConvert.DeserializeObject<RouletteBetResult>(responseText);
            if (rouletteResult.code == 101)
            {
                yield return null;
            }
            else if (rouletteResult.code == 102)
            {
                AudioManager._instance.PlayWinSound();
                showtoastmessage("congratulation!! You Won " + rouletteResult.win_amount);
                winresulttext.text = rouletteResult.win_amount + "";
                WinPopup.transform.DOScale(Vector3.one, 0.5f);
                WinPopup.SetActive(true);
                WinPopupText.text = rouletteResult.win_amount.ToString();
                WinAnimator.Play("Refreshwallet");
                showtoastmessage("congratulation!! You Won " + rouletteResult.win_amount);

                DOVirtual.DelayedCall(
                    4f,
                    () =>
                    {
                        WinPopup.SetActive(false);
                        WinPopup.transform.transform.localScale = Vector3.zero;
                    }
                );
            }
            else if (rouletteResult.code == 103)
            {
                if (rouletteResult.win_amount > 0)
                {
                    AudioManager._instance.PlayWinSound();
                    WinPopup.transform.DOScale(Vector3.one, 0.5f);
                    WinPopup.SetActive(true);
                    WinPopupText.text = rouletteResult.win_amount.ToString();
                    winresulttext.text = rouletteResult.win_amount + "";
                    WinAnimator.Play("Refreshwallet");
                    showtoastmessage("congratulation!! You Won " + rouletteResult.win_amount);

                    DOVirtual.DelayedCall(
                        4f,
                        () =>
                        {
                            WinPopup.SetActive(false);
                            WinPopup.transform.transform.localScale = Vector3.zero;
                        }
                    );
                }
                else
                {
                    winresulttext.text = "-" + total_bet;
                    AudioManager._instance.PlayWinSound();
                    showtoastmessage(
                        "Better Luck Next Time, You Lost " + rouletteResult.diff_amount
                    );
                }
            }
            total_bet = 0;
            total_text.text = "Total: " + total_bet;
        }
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

    #endregion

    #region exit

    public void exit()
    {
        canbet = false;
        if (exitPanel)
            exitPanel.SetActive(true);
        OpenPopup.instance.OnDisable();
    }

    public void OpenSetting()
    {
        canbet = false;
    }

    public void backtogame()
    {
        canbet = true;
        if (exitPanel)
            exitPanel.SetActive(false);
        OpenPopup.instance.OnDisable();
    }

    #endregion

    #region roulette Status related functions
    public void Stopshowwait()
    {
        for (int i = 0; i < objectsWithScript.Count; i++)
        {
            objectsWithScript[i].GetComponent<BetSpace>().enabled = false;
        }
        resulttext.text = "";
        StartCoroutine(sequence.EnableBets());
        resultText.text = "";
        Debug.Log("RES_Check + Start Roulette Game");
        gamestart = true;

        //showwaitanim.SetActive(false);
        canbet = true;
        StartCoroutine(Showplacebet());
        //UpdateButtonInteractability(Configuration.GetWallet());
    }

    public void cannotbet()
    {
        canbet = false;
    }

    public void Canbet()
    {
        canbet = true;
    }

    IEnumerator Showplacebet()
    {
        AudioManager._instance.PlayPlaceBetSound();
        stoptext.text = "PLACE BET";
        Stop.SetActive(true);

        for (int i = 0; i < objectsWithScript.Count; i++)
        {
            objectsWithScript[i].GetComponent<BetSpace>().enabled = true;
        }
        timeshow = true;
        //UpdateButtonInteractability(Configuration.GetWallet());
        //putbetanim.SetActive(true);
        yield return new WaitForSeconds(2);
        Stop.SetActive(false);
        //putbetanim.SetActive(false);
    }

    public void displayprofiles()
    {
        if (!click)
        {
            Userprofile.transform.GetChild(0).gameObject.SetActive(true);
            Userprofile.transform.GetChild(1).gameObject.SetActive(false);
            UserNameText.text = Configuration.GetName();
            UserWalletText.text = FormatNumber(Configuration.GetWallet());
            UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
            click = true;
        }
    }

    public void CanBetRoulette(bool bet)
    {
        if (gamestart)
            canbet = bet;
    }

    IEnumerator PutBet(GameObject prefab)
    {
        if (canbet)
        {
            /*string url = Configuration.BaseUrl + "api/Roulette/place_bet";*/
            string url = Configuration.RoulettePlaceBet;
            WWWForm form = new WWWForm();

            form.AddField("user_id", Configuration.GetId());
            form.AddField("token", Configuration.GetToken());
            form.AddField("game_id", RouletteData.game_data[0].id);
            form.AddField("bet", bet);
            form.AddField("amount", betamount);
            Debug.Log("RES_Check + id: " + Configuration.GetId());
            Debug.Log("RES_Check + bet: " + bet);
            Debug.Log("RES_Check + Token: " + Configuration.GetToken());
            Debug.Log("RES_Check + game_data_id: " + RouletteData.game_data[0].id);

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
                    betresp = JsonUtility.FromJson<RoulettePutBetResponse>(
                        request.downloadHandler.text
                    );
                    Debug.Log("RES_Check + betresp + " + request.downloadHandler.text);
                    if (betresp.code == 402)
                    {
                        putbetbool = false;
                        Destroy(prefab.gameObject);
                        destroychip = true;
                        //Destroy(coinsinstantiated[coinsinstantiated.Count - 1]);
                        showtoastmessage(betresp.message);
                        //increaseamount.SetActive(true);
                        //yield return new WaitForSeconds(1);
                        //increaseamount.SetActive(false);
                    }
                    else if (betresp.code == 226)
                    {
                        putbetbool = false;
                        Destroy(prefab.gameObject);
                        showtoastmessage(betresp.message);
                    }
                    else if (betresp.code == 204)
                    {
                        putbetbool = false;
                        Destroy(prefab.gameObject);
                        showtoastmessage(betresp.message);
                    }
                    else
                    {
                        StartCoroutine(
                            MoveAndFadeCoroutine(prefab.GetComponent<Image>(), betamount)
                        );
                        AudioManager._instance.PlayCoinDrop();
                        string walletString = betresp.wallet;
                        //string walletString = Configuration.GetWallet();
                        if (double.TryParse(walletString, out double userCoins))
                        {
                            PlayerPrefs.SetString("wallet", userCoins.ToString("F2"));
                            PlayerPrefs.Save();
                            UserWalletText.text = FormatNumber(Configuration.GetWallet());
                        }
                        total_bet += int.Parse(betamount);
                        total_text.text = "Total: " + total_bet;
                        //UpdateButtonInteractability(Configuration.GetWallet());

                        //Userprofile.transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(betresp.wallet);
                        //oldwallet = float.Parse(betresp.wallet);
                        putbetbool = true;
                    }

                    FindLastInteractableButton(float.Parse(Configuration.GetWallet()), coinbtns);
                }
            }
        }
    }

    public void Place_Previous_Bet()
    {
        if (RouletteData.game_data[0].status == "0" && !placed_previous_bet)
        {
            Debug.Log($"VRAJ || == Place Bet = {placed_previous_bet}");
            StartCoroutine(PreviousBet());
        }
        else
        {
            if (placed_previous_bet)
                showtoastmessage("Previous bets already placed");
            else
                showtoastmessage("Game not started");
        }
    }

    public void Cancel_Bet()
    {
        if (RouletteData.game_data[0].status == "0")
        {
            StartCoroutine(CancelBet());
        }
        else
        {
            showtoastmessage("Game not started");
        }
    }

    IEnumerator PreviousBet()
    {
        string url = Configuration.RoulettePlacePreviousBet;
        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("game_id", RouletteData.game_data[0].id);
        Debug.Log("RES_Check + id: " + Configuration.GetId());
        Debug.Log("RES_Check + Token: " + Configuration.GetToken());
        Debug.Log("RES_Check + game_data_id: " + RouletteData.game_data[0].id);
        placed_previous_bet = true;

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
                RouletteBetResponse betresp = JsonUtility.FromJson<RouletteBetResponse>(
                    request.downloadHandler.text
                );
                if (betresp.code == 200)
                {
                    placed_previous_bet = true;
                    Debug.Log("RES_Check + Previous + " + request.downloadHandler.text);
                    GameObject prefab = Instantiate(textprefab, parentoftext.transform);
                    prefab.gameObject.SetActive(true);
                    StartCoroutine(
                        MoveAndFadeCoroutine(
                            prefab.GetComponent<Image>(),
                            betresp.amount.ToString()
                        )
                    );
                    AudioManager._instance.PlayCoinDrop();
                    string walletString = betresp.wallet;
                    //string walletString = Configuration.GetWallet();
                    if (double.TryParse(walletString, out double userCoins))
                    {
                        PlayerPrefs.SetString("wallet", userCoins.ToString("F2"));
                        PlayerPrefs.Save();
                        UserWalletText.text = FormatNumber(Configuration.GetWallet());
                    }
                    total_bet += int.Parse(betresp.amount.ToString());
                    total_text.text = "Total: " + total_bet;

                    PlaceAllPreviousBet(betresp);
                    //UpdateButtonInteractability(Configuration.GetWallet());

                    //Userprofile.transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(betresp.wallet);
                    //oldwallet = float.Parse(betresp.wallet);
                    putbetbool = true;

                    FindLastInteractableButton(float.Parse(Configuration.GetWallet()), coinbtns);
                }
                else
                {
                    placed_previous_bet = false;
                    showtoastmessage("No bets to be placed");
                }
            }
        }
    }

    public void PlaceAllPreviousBet(RouletteBetResponse resp)
    {
        for (int i = 0; i < resp.bet.Count; i++)
        {
            string betKey = resp.bet[i].bet.ToString();

            // Check if the bet ID is in the dictionary
            if (betMappings.ContainsValue(int.Parse(resp.bet[i].bet)))
            {
                betKey = betMappings.FirstOrDefault(x => x.Value == int.Parse(resp.bet[i].bet)).Key;
            }
            else
            {
                betKey = "high" + betKey; // Default case for numbers 0-36
            }

            foreach (var bet in bets)
            {
                if (betKey == bet.name)
                {
                    bet.GetComponent<BetSpace>().ApplyPreviousBet(int.Parse(resp.bet[i].amount));
                }
            }
        }
    }

    IEnumerator CancelBet()
    {
        string url = Configuration.RouletteCancelBet;
        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("game_id", RouletteData.game_data[0].id);
        Debug.Log("RES_Check + id: " + Configuration.GetId());
        Debug.Log("RES_Check + Token: " + Configuration.GetToken());
        Debug.Log("RES_Check + game_data_id: " + RouletteData.game_data[0].id);

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
                betresp = JsonUtility.FromJson<RoulettePutBetResponse>(
                    request.downloadHandler.text
                );
                if (betresp.code == 200)
                {
                    Debug.Log("RES_Check + Cancel + " + request.downloadHandler.text);
                    showtoastmessage(betresp.message);
                    string walletString = betresp.wallet;
                    //string walletString = Configuration.GetWallet();
                    if (double.TryParse(walletString, out double userCoins))
                    {
                        PlayerPrefs.SetString("wallet", userCoins.ToString("F2"));
                        PlayerPrefs.Save();
                        UserWalletText.text = FormatNumber(Configuration.GetWallet());
                    }
                    total_bet = 0;
                    total_text.text = "Total: " + total_bet;
                    // foreach (BetSpace bet in BetPool.Instance._BetsList)
                    //     bet.Clear();

                    BetPool.Instance.Clear();
                }
                else
                {
                    showtoastmessage("No bet to cancel");
                }
            }
        }
    }

    IEnumerator MoveAndFadeCoroutine(Image uiImage, string amount)
    {
        uiImage.gameObject.transform.GetChild(0).GetComponent<Text>().color = Color.red;
        uiImage.gameObject.transform.GetChild(0).GetComponent<Text>().text = "-" + amount;
        float elapsedTime = 0f;
        float duration = 1f;
        Vector3 startPos = uiImage.rectTransform.localPosition;
        Color startColor = uiImage.color;
        Color endColor = new Color(startColor.r, startColor.g, startColor.b, 0f);
        float startY = -90f;
        float endY = 250f;

        while (elapsedTime < duration)
        {
            float t = elapsedTime / duration;
            // Move
            float newY = Mathf.Lerp(startY, endY, t);
            uiImage.rectTransform.localPosition = new Vector3(startPos.x, newY, startPos.z);
            // Fade
            uiImage.color = Color.Lerp(startColor, endColor, t);

            elapsedTime += Time.deltaTime;
            yield return null;
        }

        // Ensure final position and color
        uiImage.rectTransform.localPosition = new Vector3(startPos.x, endY, startPos.z);
        uiImage.color = endColor;

        Destroy(uiImage.gameObject);
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
    #endregion

    #region win related functions

    void wininitializing()
    {
        for (int i = 0; i < gameObjects.Count; i++)
        {
            gameObjects[i].gameObject.SetActive(false);
        }
    }
    #endregion

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
                            if (currentParticle != null)
                            {
                                currentParticle.Stop();
                                currentParticle = null;
                            }
                            foreach (var particle in particleSystems)
                            {
                                particle.Stop();
                            }
                            betamount = "0";
                            if (i == 0)
                            {
                                buttonclick(0);
                                clickedbutton(buttons[0].gameObject);
                                coininstantate(0);
                                buttons[0].GetComponent<Chip>().OnClick();
                                //OnButtonClickPartical(0);
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
                                            buttons[num].GetComponent<Chip>().OnClick();
                                            //OnButtonClickPartical(num);
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
                                    buttons[0].GetComponent<Chip>().OnClick();
                                    //OnButtonClickPartical(0);
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
                                    buttons[0].GetComponent<Chip>().OnClick();
                                    //OnButtonClickPartical(0);
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
                        Debug.Log("RES_Check + buttons[j].name " + buttons[j].name);
                        buttonclick(int.Parse(buttons[j].name));
                        clickedbutton(buttons[j].gameObject);
                        coininstantate(j);
                        buttons[j].GetComponent<Chip>().OnClick();
                        //OnButtonClickPartical(j);
                        if (j == 0 && walletAmount < 10)
                        {
                            betamount = "0";
                        }
                    }
                }
            }
            else
            {
                foreach (var btn in buttons)
                {
                    btn.interactable = false;
                }
            }
        }
    }
}
