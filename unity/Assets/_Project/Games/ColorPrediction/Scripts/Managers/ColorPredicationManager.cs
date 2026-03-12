//using SocketIOClient;
using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using AndroApps;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyUI.Toast;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Experimental.Rendering;
using UnityEngine.Networking;
using UnityEngine.Profiling;
using UnityEngine.SceneManagement;
using UnityEngine.SocialPlatforms.Impl;
using UnityEngine.UI;

public class ColorPredicationManager : MonoBehaviour
{
    [Header("Controller Details")]
    private string CustomNamespace = "/color_prediction";
    private bool IsConnection = false;
    private SocketManager Manager;

    [Header("Game Users")]
    public GameObject Userprofile;

    //public List<GameObject> profiles;
    public bool click;

    [Header("Game Timer Text")]
    public TextMeshProUGUI timertext;

    [Header("Game Data")]
    public bool GameEnded = false;
    public bool gamestart = false;
    public CPRoot game_data;

    [Header("Winning Animation")]
    public Animator wheelanim;
    public Animator wheelHighlightAnim;
    public List<GameObject> winHighlight;
    public List<GameObject> zeropos,
        onepos,
        twopos,
        threepos,
        fourpos,
        fivepos,
        sixpos,
        sevenpos,
        eightpos,
        ninepos,
        greenobj,
        redobj,
        violetobj;
    public List<int> violetnum,
        rednum,
        greennum;
    public string betamount;
    public int num;

    [Header("Last Winning")]
    public List<string> lastwinningdata;
    public List<Sprite> lastwinningimages;
    public List<Image> lastwinningimagestoshow;
    public List<TextMeshProUGUI> lastwinning;

    [Header("Buttons")]
    public List<GameObject> btns;
    public GameObject buttonclicked;
    public ColorPredictionBetResult cpresultdata;
    public bool reconnected,
        invoke;
    public int timetoinvoke;

    public Button[] buttons;

    public Text stoptext;
    public GameObject showstop;
    public AudioSource coinbetsound;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;
    public Toggle soundToggle;
    public Toggle musicToggle;

    public List<GameObject> coins = new List<GameObject>();
    private List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<BotsUser> m_Bots = new List<BotsUser>();
    private bool show_history;

    private void OnEnable()
    {
        UserNameText.text = Configuration.GetName();
        UserNameText.text = Configuration.GetName();
        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("color_prediction_timer", CP_TimerResponse);
        customNamespace.On<string>("color_prediction_status", OnCP_statusResponse);
        Manager.Open();

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);

        show_history = false;

        foreach (Button button in buttons)
        {
            button.interactable = false;
        }
        OnButtonClickPartical(0);
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

    #region Socket Connect/Disconnect

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
        gamestart = false;
    }

    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("RES_Check + Connect");
        IsConnection = true;
        invoke = true;
        GetTimer();
    }

    public void OnDisconnected()
    {
        AudioManager._instance.StopAudio();
        AudioManager._instance.StopEffect();
        Manager.Close();
        Debug.Log("RES_Check + Disconnected");
    }

    public void Disconnect()
    {
        Manager.Close();
        AudioManager._instance.StopAudio();
        AudioManager._instance.StopEffect();
        var customNamespaceSocket = Manager.GetSocket(CustomNamespace);
        customNamespaceSocket.Disconnect();
        StopAllCoroutines(); //
        Debug.Log("Disconnected: ");
        this.gameObject.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
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
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("color_prediction_timer", CP_TimerResponse);
        customNamespace.On<string>("color_prediction_status", OnCP_statusResponse);
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
        ClearObjectList(zeropos);
        ClearObjectList(onepos);
        ClearObjectList(twopos);
        ClearObjectList(threepos);
        ClearObjectList(fourpos);
        ClearObjectList(fivepos);
        ClearObjectList(sixpos);
        ClearObjectList(sevenpos);
        ClearObjectList(eightpos);
        ClearObjectList(ninepos);
        ClearObjectList(greenobj);
        ClearObjectList(redobj);
        ClearObjectList(violetobj);
        ClearObjectList(m_DummyObjects);
    }

    #endregion

    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("color_prediction_timer", "color_prediction_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-color_prediction_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void CP_TimerResponse(string args)
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
        if (!gamestart)
        {
            Debug.Log("RES_Check + Entered Stopshowwait");
            //showwaitanim.SetActive(false);
            gamestart = true;
            stoptext.text = "PLACE BET";
            AudioManager._instance.PlayPlaceBetSound();
            StartCoroutine(Showplacebet());
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
            //UpdateButtonInteractability(Configuration.GetWallet());
        }
    }

    IEnumerator Showplacebet()
    {
        showstop.SetActive(true);
        AudioManager._instance.PlayPlaceBetSound();
        yield return new WaitForSeconds(2f);
        showstop.SetActive(false);
    }
    #endregion

    #region CP Status

    private void OnCP_statusResponse(string args)
    {
        Debug.Log("RES_Value Status: " + args);

        try
        {
            GetTimer();

            game_data = JsonUtility.FromJson<CPRoot>(args);
            displayprofiles();

            string gameStatus = game_data.game_data[0].status;
            string currentGameId = game_data.game_data[0].id;
            string savedGameId = Configuration.getcpid();

            if (gameStatus == "0")
            {
                HandleOngoingGame(currentGameId, savedGameId);
            }
            else
            {
                CommonUtil.ShowToast(
                    "winning: "
                        + game_data.game_data[0].winning
                        + " , Game Id: "
                        + game_data.game_data[0].id
                );
                HandleGameEnd();
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private void HandleOngoingGame(string currentGameId, string savedGameId)
    {
        bool isSameGame = savedGameId == currentGameId;

        if (reconnected)
        {
            reconnected = false;
            GameEnded = false;

            if (!isSameGame)
            {
                ClearGameObjects();
            }
        }

        PlayerPrefs.SetString("cpid", currentGameId);
    }

    private void HandleGameEnd()
    {
        timertext.text = "0";
        gamestart = false;
        GameEnded = true;
        stoptext.text = "STOP BET";
        AudioManager._instance.PlayStopBetSound();

        showstop.SetActive(true);
        DOVirtual.DelayedCall(2f, () => showstop.SetActive(false));
        StartCoroutine(WheelWinAnim());
    }

    #endregion

    #region winning history

    public void showhistory(GameObject obj)
    {
        if (lastwinningdata.Count > 0)
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
        lastwinningdata.Clear();
        for (int i = 0; i < game_data.last_winning.Count; i++)
        {
            lastwinningdata.Add(game_data.last_winning[i].winning);
        }
        for (int i = 0; i < lastwinning.Count; i++)
        {
            if (game_data.last_winning[i] != null)
            {
                lastwinningimagestoshow[i].sprite = lastwinningimages[
                    int.Parse(game_data.last_winning[i].winning)
                ];
                lastwinning[i].text = lastwinningdata[i];
            }
        }
    }

    #endregion

    #region placebet

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

    public void placebet(string bettype)
    {
        Debug.Log("RES_Check + Place Bet");
        if (betamount != "0")
        {
            if (gamestart)
            {
                PlaceBet(bettype);
                //StartCoroutine(Putbet(bettype));
            }
            else
            {
                CommonUtil.ShowToast("Please wait for the round to start");
            }
        }
        else
        {
            CommonUtil.ShowToast("Insufficient Balance");
        }
    }

    public void coininstantate(int index)
    {
        num = index;
    }

    async void PlaceBet(string bettype)
    {
        string url = Configuration.JColor_Prediction_PutBet;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", game_data.game_data[0].id },
            { "bet", bettype },
            { "amount", betamount },
        };
        CPTPutBetResponse betresp = new CPTPutBetResponse();
        betresp = await APIManager.Instance.PostRaw<CPTPutBetResponse>(url, formData);

        CommonUtil.CheckLog($"Message::{betresp.message}Code::{betresp}");
        if (betresp.code == 406)
        {
            CommonUtil.ShowToast(betresp.message);
        }
        else
        {
            if (betresp.code == 200)
            {
                string walletString = betresp.wallet;
                PlayerPrefs.SetString("wallet", betresp.wallet);
                UserWalletText.text = CommonUtil.GetFormattedWallet();
                var RandomCollider = m_ColliderList[int.Parse(bettype)]; // means Dragon
                var coin = Instantiate(
                    coins[num],
                    Userprofile.transform.GetChild(0).GetChild(0).GetChild(2)
                );
                AudioManager._instance.PlayCoinDrop();

                coin.transform.localPosition = Vector3.zero;

                m_DummyObjects.Add(coin.gameObject);
                coin.transform.SetParent(RandomCollider.transform);
                coin.transform.localScale = Vector3.one;
                coin.transform.DOLocalMove(
                        GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                        0.8f
                    )
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(Vector3.one * 0.8f, 0.2f);
                    });

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
                //   UpdateButtonInteractability(Configuration.GetWallet());
            }
            else
            {
                if (betresp.code == 226)
                {
                    CommonUtil.ShowToast("Already Added Bet On The Same Color");
                }
                else
                    CommonUtil.ShowToast(betresp.message);
            }
        }
    }

    #endregion
    #region DisplayWinner
    IEnumerator WheelWinAnim()
    {
        timertext.text = "0";
        yield return new WaitForSeconds(2);
        AudioManager._instance.PlayWheelSound();
        wheelanim.SetInteger("winning", int.Parse(game_data.game_data[0].winning));
        yield return new WaitForSeconds(2);
        wheelanim.speed = 0;
        StartCoroutine(HighlightWinner(winHighlight[int.Parse(game_data.game_data[0].winning)]));
        StartCoroutine(win());
        ShowHistoryPrediction();
        /*    wheelHighlightAnim.gameObject.SetActive(true);
           yield return new WaitForSeconds(4);
           wheelHighlightAnim.gameObject.SetActive(false); */
        // showwaitanim.SetActive(true);
        GameEnded = false;
        yield return new WaitForSeconds(4);
        wheelanim.SetInteger("winning", -1);
        wheelanim.speed = 1;
    }

    IEnumerator HighlightWinner(GameObject obj)
    {
        Debug.Log("HightlightObject Name" + obj.name);
        AudioManager._instance.PlayHighlightWinSound();
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
        yield return new WaitForSeconds(3f);
        updatedata();
        yield return new WaitForSeconds(1f);
        ClearGameObjects();
    }

    private void ClearObjectList(List<GameObject> objectList)
    {
        foreach (GameObject obj in objectList)
        {
            Destroy(obj);
        }
        objectList.Clear();
    }

    public async void updatedata() //string Token)
    {
        StartCoroutine(GetResult());
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
        CommonUtil.CheckLog("RES+Message" + myResponse.message);
        CommonUtil.CheckLog("RES+Code" + myResponse.code);
    }
    #endregion
    #region displaybots
    public async void displayprofiles()
    {
        /*  if (!click)
         {
             Userprofile.transform.GetChild(0).gameObject.SetActive(true);
             Userprofile.transform.GetChild(1).gameObject.SetActive(false);

             UserNameText.text = Configuration.GetName();
             UserWalletText.text = CommonUtil.GetFormattedWallet();
             UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
             click = true;
         }
    */
        /*    for (int i = 0; i < profiles.Count; i++)
           {
               Debug.Log("Res_Check 1");
               profiles[i].transform.GetChild(0).gameObject.SetActive(true);
               profiles[i].transform.GetChild(1).gameObject.SetActive(false);
               profiles[i].transform.GetChild(0).GetChild(0).GetChild(1).GetComponent<Text>().text =
                   game_data.bot_user[i].name;
               profiles[i].transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text =
                   FormatNumber(game_data.bot_user[i].coin);
               Image img = profiles[i]
                   .transform.GetChild(0)
                   .GetChild(1)
                   .GetChild(0)
                   .GetComponent<Image>();
               img.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(Configuration.ProfileImage + game_data.bot_user[i].avatar);
               //  StartCoroutine(DownloadImage(game_data.bot_user[i].avatar, img));
           } */

        if (!click)
        {
            UserNameText.text = Configuration.GetName();
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
        }
        /*    for (int i = 0; i < m_Bots.Count; i++)
           {
               m_Bots[i].BotName.text = game_data.bot_user[i].name;
               m_Bots[i].BotCoin.text = CommonUtil.GetFormattedWallet(game_data.bot_user[i].coin);
               m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(Configuration.ProfileImage + game_data.bot_user[i].avatar);
           } */
    }
    #endregion
    #region downloadimage
    #endregion
    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;

    public void OnButtonClickPartical(int buttonIndex)
    {
        Debug.Log("RES_Check + PArticle called");
        ParticleSystem newParticle = particleSystems[buttonIndex];
        if (currentParticle != null)
        {
            currentParticle.Stop();
        }
        if (newParticle != null)
        {
            Debug.Log("RES_Check + Play");
            newParticle.Play();
            currentParticle = newParticle;
        }
    }
    #endregion
    #region result
    public IEnumerator GetResult()
    {
        Debug.Log("Login");
        string Url = Configuration.ColorPredictionResult;
        Debug.Log("RES_Check + API-Call + Andar bahar Result");
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
            cpresultdata = new ColorPredictionBetResult();
            cpresultdata = JsonConvert.DeserializeObject<ColorPredictionBetResult>(responseText);
            if (cpresultdata.code == 101)
            {
                yield return null;
            }
            else if (cpresultdata.code == 102)
            {
                AudioManager._instance.PlayWinSound();
                CommonUtil.ShowToast("congratulation!! You Won " + cpresultdata.win_amount);
            }
            else if (cpresultdata.code == 103)
            {
                AudioManager._instance.PlayLoseSound();
                CommonUtil.ShowToast("Better Luck Next Time, You Lost " + cpresultdata.diff_amount);
            }
        }
        else
        {
            Debug.Log("RES_Check + Result + " + www.result);
        }
    }
    /*  public async Task GetResult()
     {
         Debug.Log("GET RESULT COMMING..");
         string Url = Configuration.ColorPredictionResult;
         var formData = new Dictionary<string, string>
         {
             { "user_id", Configuration.GetId() },
             { "token", Configuration.GetToken() },
             { "game_id", game_data.game_data[0].id },
         };

         CommonUtil.ValueLog($"Color_Prediction:{Url}");

         // Call your custom Post<T> method to send the request
         cpresultdata = await APIManager.Instance.Post<ColorPredictionBetResult>(Url, formData);
         // Check if the response is valid

         // Handle the response based on the code
         if (game_data.code == 102)
         {
             AudioManager._instance.PlayWinSound();
             CommonUtil.ShowToast("congratulation!! You Won " + cpresultdata.win_amount);
         }
         else if (game_data.code == 103)
         {
             if (cpresultdata.win_amount > 0)
             {
                 AudioManager._instance.PlayWinSound();
                 CommonUtil.ShowToast("congratulation!! You Won " + cpresultdata.win_amount);
             }
             else
             {
                 AudioManager._instance.PlayLoseSound();
                 CommonUtil.ShowToast(
                     "Better Luck Next Time, You Lost " + cpresultdata.diff_amount
                 );
             }
         }
         // Move all coins into the top (no need to await this since it's a synchronous operation)
         //GameBetUtil.MoveAllCoinsIntoTop(m_DummyObjects, timertext.transform, m_ColliderList, game_data.game_data[0].winning, dragonint, tigerint, Userprofile, profiles, () => { });

     } */
    #endregion
    #region  disablebutton
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
                                OnButtonClickPartical(0);
                                button.interactable = true;
                                Debug.Log("RES_Check + I " + i);
                                for (int j = 1; j < buttons.Length; j++)
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
                                            OnButtonClickPartical(num);
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
                                    OnButtonClickPartical(0);
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
                                    OnButtonClickPartical(0);
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
                for (int j = 0; j < buttons.Length; j++)
                {
                    if (buttons[j].gameObject == buttonclicked)
                    {
                        buttonclick(int.Parse(buttons[j].name));
                        clickedbutton(buttons[j].gameObject);
                        coininstantate(j);
                        OnButtonClickPartical(j);
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

    #endregion
}
