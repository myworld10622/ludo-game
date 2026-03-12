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
using Mkey;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class sevenupdownmanager : MonoBehaviour
{
    private string CustomNamespace = "/seven_up_down";
    private SocketManager Manager;

    [Header("Status data")]
    public sevenupdownRootObject SevenUDData;

    /*    [Header("Text Anim")]
       public GameObject showwaitanim,
           putbetanim,
           increaseamount; */

    [Header("Round Timer Text")]
    public TextMeshProUGUI timertext;

    [Header("Game Users")]
    public GameObject Userprofile;
    public List<GameObject> profiles;
    public List<BotsUser> m_Bots = new List<BotsUser>();
    public Animator onlineuser;

    [Header("Last Winning Data")]
    public List<string> lastwinning;
    public List<Image> lastwinningdata;

    [Header("Coins")]
    public List<GameObject> coins;

    [Header("Buttons")]
    public List<GameObject> btns;

    [Header("winner")]
    public int winner;
    public List<Sprite> lastwindiceimage;
    public Animator seven,
        sevenup,
        sevendown;

    [Header("Dice Jar")]
    public GameObject jar;

    [Header("put bet json variables")]
    public SevenUPPutBetResponse betresp;
    public bool putbetbool;

    [Header("my bet texts")]
    public TextMeshProUGUI sevendownamount;
    public TextMeshProUGUI sevenupamount,
        sevenamount;
    public int sevenupint,
        sevendownint,
        sevenint;

    private bool gamestart,
        GameEnded,
        click,
        online;
    public newLogInOutputs LogInOutput;
    public int num;
    public string betamount;
    public string bet;
    public GameObject buttonclicked;
    private int totalcoinsleft;
    public GameObject Reconn_Canvas;
    public float inititalamount;
    public SevenUpDownBetResult sevenupanddownresult;
    public bool reconnected = false;

    [Header("Model For the Coins")]
    public Transform m_ModelForAnimation;

    public AudioSource gameaudio;
    public Button[] buttons;
    public GameObject stop;
    public Transform targetPoint,
        targetPoint2;

    public Animator m_JarAnimatoin;

    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;
    public Transform m_UserCoinPos;

    public Toggle soundToggle;
    public Toggle musicToggle;

    public Text stoptext;

    private List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<TextMeshProUGUI> m_colidertext = new List<TextMeshProUGUI>();

    private void OnEnable()
    {
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;
        DOTween.SetTweensCapacity(tweenersCapacity: 1250, sequencesCapacity: 50);
        UserNameText.text = Configuration.GetName();
        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("seven_up_down_timer", OnSevenUPDown_TimerResponse);
        customNamespace.On<string>("seven_up_down_status", Onsevenupdownstatus_statusResponse);
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
        foreach (ParticleSystem particle in particleSystems)
        {
            if (particle.isPlaying)
            {
                particle.Stop();
            }
        }
        foreach (var btn in buttons)
        {
            btn.interactable = false;
        }
        particleSystems[0].Play();
        //GameBetUtil.OnButtonClickParticle(num, particleSystems.ToList(), ref currentParticle);
    }

    void OnApplicationPause(bool pauseStatus)
    {
        if (!pauseStatus)
        {
            Debug.Log("RES_Check + resume");
            // RequestGameStateUpdate();
        }
    }

    private void RequestGameStateUpdate()
    {
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("seven_up_down_timer", OnSevenUPDown_TimerResponse);
        customNamespace.On<string>("seven_up_down_status", Onsevenupdownstatus_statusResponse);
        Manager.Open();
        reconnected = true;
    }

    #region Socket Connection/DisConnection
    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("RES_Check Connect : " + resp.sid);
        //ControllerDetail.IsConnection = true;
        //Reconn_Canvas.SetActive(false);
        GetTimer();
    }

    public void OnDisconnected()
    {
        Debug.Log("RES_Check + Reconnecting");
    }

    public void Disconnect()
    {
        Manager.Close();
        var customNamespaceSocket = Manager.GetSocket(CustomNamespace);
        customNamespaceSocket.Disconnect();
        StopAllCoroutines(); //
        AudioManager._instance.StopEffect();
        //SceneManager.LoadSceneAsync("HomePage");
        //SceneLoader.Instance.LoadScene(2);
        AudioManager._instance.StopEffect();
        SceneLoader.Instance.LoadScene("HomePage");
    }

    #endregion

    #region Timer Functions
    private void GetTimer()
    {
        GameEnded = false;
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            //customNamespace.Emit("roulette_timer", "roulette_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-roulette_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnSevenUPDown_TimerResponse(string args)
    {
        Debug.Log("Timer Game Json :" + args);
        //  Stopshowwait();
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


    #region Seven_Up_Down_Status
    private void Onsevenupdownstatus_statusResponse(string args)
    {
        Debug.Log("JSON: " + args);
        try
        {
            SevenUDData = JsonUtility.FromJson<sevenupdownRootObject>(args);
            displayprofiles();

            Debug.LogError("STATUS RESPONSE:" + SevenUDData.game_data[0].status);

            if (SevenUDData.game_data[0].status == "0")
            {
                string game_id = Configuration.getsevenupdownid();
                bool isSameGame = game_id == SevenUDData.game_data[0].id;

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

                PlayerPrefs.SetString("sevenupdownid", SevenUDData.game_data[0].id);

                if (!online)
                {
                    StartCoroutine(PlaceBetPopUp());
                    for (int i = 0; i < m_Bots.Count; i++)
                    {
                        m_Bots[i].NoUser.SetActive(false);
                        m_Bots[i].UserCome.SetActive(true);
                    }
                    Stopshowwait();
                    online = true;
                    DOVirtual.DelayedCall(
                        2f,
                        () =>
                        {
                            m_DummyObjects.Clear();
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
                    );
                }

                //StartCoroutine(endgame());
            }
            else
            {
                if (online)
                {
                    CommonUtil.ShowToast(
                        "winning: "
                            + SevenUDData.game_data[0].winning
                            + " , Game Id: "
                            + SevenUDData.game_data[0].id
                    );
                    online = false;
                    GameBetUtil.StopBet();
                    StartCoroutine(endgame());
                }
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }


    }

    //

    private void ResetAmounts(bool isSameGame)
    {
        if (isSameGame)
        {
            sevenupamount.text = sevenupint + "";
            sevendownamount.text = sevendownint + "";
            sevenamount.text = sevenint + "";
        }
        else
        {
            sevenupint = sevendownint = sevenint = 0;
            sevenupamount.text = "0";
            sevendownamount.text = "0";
            sevenamount.text = "0";
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

    IEnumerator endgame()
    {
        foreach (var btn in buttons)
        {
            btn.interactable = false;
        }
        timertext.text = "0";
        StartCoroutine(StopSevenupDown()); //1.5f
        yield return new WaitForSeconds(1f); //1f
        gamestart = false;
        GameEnded = true;
        //timertext.text = "0";
        yield return StartCoroutine(animatedice()); //10f
        yield return StartCoroutine(win());
        yield return new WaitForSeconds(2f);

        //m_DummyObjects.Clear();
        //StartCoroutine(GetResult());
        updatedata();

        sevenupamount.text = "0";
        sevenamount.text = "0";
        sevendownamount.text = "0";
        sevenupint = 0;
        sevendownint = 0;
        sevenint = 0;
        for (int i = 0; i < m_colidertext.Count; i++)
        {
            m_colidertext[i].text = "0";
        }
        GameEnded = false;
        //   showwaitanim.SetActive(true);
        GetTimer();

    }

    public IEnumerator StopSevenupDown()
    {
        AudioManager._instance.PlayStopBetSound();
        stoptext.text = "STOP BET";
        stop.SetActive(true);
        yield return new WaitForSeconds(1.5f);
        stop.SetActive(false);
    }

    public IEnumerator PlaceBetPopUp()
    {
        AudioManager._instance.PlayPlaceBetSound();
        stoptext.text = "PLACE BET";
        stop.SetActive(true);
        yield return new WaitForSeconds(1.5f);
        stop.SetActive(false);
    }
    #endregion

    #region Seve_Up_Down Status related functions

    public void Stopshowwait()
    {
        //   showwaitanim.SetActive(false);
        //putbetanim.SetActive(true);
        if (!gamestart)
        {
            foreach (var btn in buttons)
            {
                btn.interactable = true;
            }

            gamestart = true;

            //UpdateButtonInteractability(Configuration.GetWallet());

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

    public void ShowLast10Win()
    {
        lastwinning.Clear();
        if (SevenUDData.last_winning.Count > 0)
        {
            for (int i = 0; i < SevenUDData.last_winning.Count; i++)
            {
                lastwinning.Add(SevenUDData.last_winning[i].winning);
            }

            for (int i = 0; i < lastwinningdata.Count; i++)
            {
                if (lastwinning.Count >= i)
                {
                    if (lastwinning[i] == "0")
                        lastwinningdata[i]
                            .transform.GetChild(0)
                            .GetComponent<TextMeshProUGUI>()
                            .text = "2-6";
                    else if (lastwinning[i] == "1")
                        lastwinningdata[i]
                            .transform.GetChild(0)
                            .GetComponent<TextMeshProUGUI>()
                            .text = "8-12";
                    else if (lastwinning[i] == "2")
                        lastwinningdata[i]
                            .transform.GetChild(0)
                            .GetComponent<TextMeshProUGUI>()
                            .text = "7";
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
            //Userprofile.transform.GetChild(0).GetChild(0).GetChild(0).GetComponent<Text>().text = FormatNumber(Configuration.GetWallet());
            click = true;
        }
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].UserCome.SetActive(true);
            m_Bots[i].NoUser.SetActive(false);
        }
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].BotName.text = SevenUDData.bot_user[i].name;
            m_Bots[i].BotCoin.text = SevenUDData.bot_user[i].coin;
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + SevenUDData.bot_user[i].avatar
            );
        }
    }

    public IEnumerator animatedice()
    {
        // gameaudio.clip = Dice;
        // gameaudio.Play();
        AudioManager._instance.PlayDiceSound();

        winner = int.Parse(SevenUDData.game_cards[0].card);

        //jar.transform.position = jar.GetComponent<JarAnim>().targetPoint.position;

        jar.transform.DOMove(targetPoint.position, 0.5f)
            .OnComplete(() =>
            {
                // gameaudio.clip = Dice;
                // gameaudio.Play();
                //AudioManager._instance.PlayDiceSound();
                m_JarAnimatoin.enabled = true;
                m_JarAnimatoin.SetBool("start", true);
                // DOVirtual.DelayedCall(1f, () =>
                // {
                //     jar.GetComponent<Animator>().SetBool("start", false);
                //     jar.GetComponent<Animator>().enabled = false;

                // }).OnComplete(() => { jar.GetComponent<SpriteRenderer>().sprite = lastwindiceimage[winner - 2]; });
            });
        yield return new WaitForSeconds(1.8f);
        m_JarAnimatoin.SetBool("start", false);
        m_JarAnimatoin.enabled = false;
        jar.GetComponent<SpriteRenderer>().sprite = lastwindiceimage[winner - 2];
        //m_JarAnimatoin.enabled = false;
        yield return new WaitForSeconds(1f);
        jar.transform.DOMove(targetPoint2.position, 0.5f)
            .OnComplete(() =>
            {
                m_JarAnimatoin.enabled = true;
            });
        //jar.transform.position = jar.GetComponent<JarAnim>().targetPoint2.position;
    }

    #endregion

    #region Put_Bet

    async void PlaceBet()
    {
        string url = SevenupdownConfig.SevenupDownPlacebet;
        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", SevenUDData.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        betresp = await APIManager.Instance.Post<SevenUPPutBetResponse>(url, formData);

        CommonUtil.CheckLog("PlaceBet" + betresp.message);
        if (betresp.code == 406)
        {
            putbetbool = false;
        }
        else if (betresp.code == 200)
        {
            putbetbool = true;
            HandleBetPlacement();
            PlayerPrefs.SetString("wallet", betresp.wallet);
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
        else
        {
            CommonUtil.ShowToast(betresp.message);
        }
    }

    void HandleBetPlacement()
    {
        GameObject coin = Instantiate(coins[num], m_UserCoinPos);
        var ColiiderTOMOve = m_ColliderList[int.Parse(bet)]; // means andar

        AudioManager._instance.PlayCoinDrop();
        coin.transform.localPosition = Vector3.zero;

        m_DummyObjects.Add(coin.gameObject);
        coin.transform.SetParent(ColiiderTOMOve.transform);
        coin.transform.localScale = new Vector3(0.4f, 0.4f, 0.4f);
        coin.transform.DOLocalMove(GameBetUtil.GetRandomPositionInCollider(ColiiderTOMOve), 0.8f)
            .OnComplete(() =>
            {
                coin.transform.DOScale(Vector3.one * 0.3f, 0.2f);
            });

        switch (bet)
        {
            case "1":
                sevenupint += int.Parse(betamount);
                m_colidertext[0].text =
                    int.Parse(m_colidertext[0].text) + int.Parse(betamount) + "";
                sevenupamount.text = $"{sevenupint}";
                break;

            case "0":
                sevendownint += int.Parse(betamount);
                m_colidertext[1].text =
                    int.Parse(m_colidertext[1].text) + int.Parse(betamount) + "";
                sevendownamount.text = $"{sevendownint}";
                break;

            case "2":
                sevenint += int.Parse(betamount);
                m_colidertext[2].text =
                    int.Parse(m_colidertext[2].text) + int.Parse(betamount) + "";
                sevenamount.text = $"{sevenint}";
                break;
        }

        Debug.Log($"Res_Check + PutBet: {betresp}");
    }

    void HandleBetClick(string betType)
    {
        if (betamount == "0")
        {
            CommonUtil.ShowToast("Insufficient balance");
            return;
        }

        if (gamestart && SevenUDData.game_data[0].status == "0")
        {
            bet = betType;
            PlaceBet();
        }
    }

    public void ClickedSevenUp() => HandleBetClick("1");

    public void ClickedSeveDown() => HandleBetClick("0");

    public void ClickedTie() => HandleBetClick("2");

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
        }
        button.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
        buttonclicked = button;
    }

    public void coininstantate(int index)
    {
        num = index;
        foreach (ParticleSystem particle in particleSystems)
        {
            if (particle.isPlaying)
            {
                particle.Stop();
            }
        }
        particleSystems[index].Play();
    }

    #region Seven_UP_Down Win

    IEnumerator win()
    {
        var totalcalulate = sevendownint + sevenupint + sevenint;
        Debug.Log("RES_Check + Win");
        yield return new WaitForSeconds(0.1f);
        onlineuser.enabled = false;
        GetResult();
        Debug.Log("Winner call in win" + winner);
        if (winner >= 8)
            sevendown.gameObject.SetActive(true);
        else if (winner <= 6)
            sevenup.gameObject.SetActive(true);
        else
            seven.gameObject.SetActive(true);

        yield return new WaitForSeconds(0.5f);
        seven.gameObject.SetActive(false);
        sevenup.gameObject.SetActive(false);
        sevendown.gameObject.SetActive(false);
        yield return new WaitForSeconds(1f);
        GameBetUtil.MoveAllCoinsIntoTop(
                   m_DummyObjects,
                   m_ModelForAnimation,
                   m_ColliderList,
                   SevenUDData.game_data[0].winning,
                   totalcalulate,
                   totalcalulate,
                   Userprofile,
                   profiles,
                   () => { }
               );

        totalcoinsleft = 0;
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

    string winnerfor = "12";
    public async void GetResult()
    {
        winnerfor = "10";
        string Url = Configuration.SevenUpDownResult;
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", SevenUDData.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + SevenUDData.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        sevenupanddownresult = new SevenUpDownBetResult();
        sevenupanddownresult = await APIManager.Instance.Post<SevenUpDownBetResult>(Url, formData);

        CommonUtil.CheckLog("Result Message" + sevenupanddownresult.message);
        /* Debug.Log("RES_Check + API-Call + Seven up down Result");
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("game_id", SevenUDData.game_data[0].id);
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);
        Debug.Log(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + SevenUDData.game_data[0].id
        );
        Debug.Log("RES_Check + URL + " + Url);
        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + Result: " + responseText);
            sevenupanddownresult = new SevenUpDownBetResult();
            sevenupanddownresult = JsonConvert.DeserializeObject<SevenUpDownBetResult>(responseText); */
        if (sevenupanddownresult.code == 101)
        {
            return;
        }
        else if (sevenupanddownresult.code == 102)
        {
            winnerfor = "0";
            //AudioManager._instance.PlayWinSound();
            AudioManager._instance.PlayHighlightWinSound();
            CommonUtil.ShowToast("congratulation!! You Won " + sevenupanddownresult.win_amount);
        }
        else if (sevenupanddownresult.code == 103)
        {
            /*  gameaudio.clip = looseaudio;
             gameaudio.Play(); */
            winnerfor = "10";
            AudioManager._instance.PlayLoseSound();
            CommonUtil.ShowToast(
                "Better Luck Next Time, You Lost " + sevenupanddownresult.diff_amount
            );
        }
        Debug.Log("GetResult" + winnerfor);
        ShowLast10Win();

    }

    #endregion

    public void MoveAllcoinsintoTop()
    {
        // gameaudio.clip = coindistribute;
        // gameaudio.Play();
        int userwincoins = 0;

        for (int i = 0; i < m_DummyObjects.Count / 2; i++)
        {
            Destroy(m_DummyObjects[i]);
            m_DummyObjects.Remove(m_DummyObjects[i]);
            m_DummyObjects.RemoveAll(item => item == null);
        }

        m_DummyObjects.ForEach(x => x.transform.SetParent(m_ModelForAnimation));
        m_DummyObjects.ForEach(x =>
            x.transform.DOLocalMove(Vector3.zero, 0.6f)
                .OnComplete(() =>
                {
                    if (winner <= 6) //Won 0-6
                    {
                        var RandomCollider = m_ColliderList[0]; // first
                        x.transform.SetParent(RandomCollider.transform);
                        x.transform.DOLocalMove(
                                GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                                0.6f
                            )
                            .OnComplete(() =>
                            {
                                if (sevendownint > 0 && userwincoins < 10)
                                {
                                    userwincoins++;
                                    x.transform.SetParent(
                                        Userprofile
                                            .transform.GetChild(0)
                                            .GetChild(0)
                                            .GetChild(2)
                                            .transform
                                    );
                                    x.transform.DOLocalMove(Vector3.zero, 0.7f)
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
                                    x.transform.DOLocalMove(Vector3.zero, 0.7f)
                                        .OnComplete(() =>
                                        {
                                            Destroy(x);
                                            m_DummyObjects.Remove(x);
                                            m_DummyObjects.RemoveAll(item => item == null);
                                        });
                                }
                            });
                    }
                    else if (winner >= 8) //Won 7-7
                    {
                        var RandomCollider = m_ColliderList[1]; //last
                        x.transform.SetParent(RandomCollider.transform);
                        x.transform.DOLocalMove(
                                GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                                0.6f
                            )
                            .OnComplete(() =>
                            {
                                if (sevenupint > 0 && userwincoins < 10)
                                {
                                    userwincoins++;
                                    x.transform.SetParent(
                                        Userprofile
                                            .transform.GetChild(0)
                                            .GetChild(0)
                                            .GetChild(2)
                                            .transform
                                    );
                                    x.transform.DOLocalMove(Vector3.zero, 0.7f)
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
                                    x.transform.DOLocalMove(Vector3.zero, 0.7f)
                                        .OnComplete(() =>
                                        {
                                            Destroy(x);
                                            m_DummyObjects.RemoveAll(item => item == null);
                                            m_DummyObjects.Remove(x);
                                        });
                                }
                            });
                    }
                    else //Center //Won 7
                    {
                        var RandomCollider = m_ColliderList[2]; //center table
                        x.transform.SetParent(RandomCollider.transform);

                        x.transform.DOLocalMove(
                                GameBetUtil.GetRandomPositionInCollider(RandomCollider),
                                0.6f
                            )
                            .OnComplete(() =>
                            {
                                if (sevenint > 0 && userwincoins < 10)
                                {
                                    userwincoins++;
                                    x.transform.SetParent(
                                        Userprofile
                                            .transform.GetChild(0)
                                            .GetChild(0)
                                            .GetChild(2)
                                            .transform
                                    );
                                    x.transform.DOLocalMove(Vector3.zero, 0.7f)
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
                                    x.transform.DOLocalMove(Vector3.zero, 0.7f)
                                        .OnComplete(() =>
                                        {
                                            Destroy(x);
                                            m_DummyObjects.RemoveAll(item => item == null);
                                            m_DummyObjects.Remove(x);
                                        });
                                }
                            });
                    }
                })
        );
        //PlayerWinAnimation();
    }

    public void PlayerWinAnimation()
    {
        //var newList = m_DummyObjects;

        // Debug.Log("PlayerWinAnimation:" + newList.Count);
        if (winner >= 8)
        {
            Debug.Log("RES_Check + Game Over won 1");
            if (totalcoinsleft == 0)
            {
                if (sevenupint > 0)
                {
                    Debug.LogError("sevenupobjects" + m_DummyObjects.Count);
                    //sevenupobjects.ForEach(x => x.transform.localScale = Vector3.one * .3f);
                    m_DummyObjects.ForEach(x => x.transform.SetParent(Userprofile.transform));
                    m_DummyObjects.ForEach(x =>
                        x.transform.DOLocalMove(Vector3.zero, 0.6f)
                            .OnComplete(() =>
                            {
                                Destroy(x);
                                m_DummyObjects.Remove(x);
                            })
                    );
                }
            }
        }
        else if (winner <= 6)
        {
            Debug.Log("RES_Check + Game Over won 2");
            if (totalcoinsleft == 0)
            {
                if (sevendownint > 0)
                {
                    Debug.LogError("sevendownobjects" + m_DummyObjects.Count);
                    m_DummyObjects.ForEach(x => x.transform.SetParent(Userprofile.transform));
                    m_DummyObjects.ForEach(x =>
                        x.transform.DOLocalMove(Vector3.zero, 0.6f)
                            .OnComplete(() =>
                            {
                                Destroy(x);
                                m_DummyObjects.Remove(x);
                            })
                    );
                }
            }
        }
        else
        {
            Debug.Log("RES_Check + Game Over won 3");

            if (totalcoinsleft == 0)
            {
                if (sevenint > 0)
                {
                    Debug.LogError("sevenobjects" + m_DummyObjects.Count);
                    m_DummyObjects.ForEach(x => x.transform.SetParent(Userprofile.transform));
                    m_DummyObjects.ForEach(x =>
                        x.transform.DOLocalMove(Vector3.zero, 0.6f)
                            .OnComplete(() =>
                            {
                                Destroy(x);
                                m_DummyObjects.Remove(x);
                            })
                    );
                }
            }
        }
    }

    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;

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
