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
using Gpm.Common.ThirdParty.MessagePack;
using Mkey;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

public class DragonTigerManager : MonoBehaviour
{
    private string CustomNamespace = "/dragon_tiger";
    public bool IsConnection;
    private SocketManager Manager;

    [Header("Timer related GameObjects")]
    public TextMeshProUGUI timertext;

    //public GameObject showwaitanim;

    [Header("Socket Status Data")]
    public DTSocketResponse DragonTigerData;

    [Header("last winning Sprite Data")]
    public List<string> lastwinning;
    public List<Sprite> lastwinningsprite;
    public List<GameObject> lastwinningimagestoshow;

    [Header("Profiles")]
    public GameObject Userprofile;
    public List<GameObject> profiles;
    public List<BotsUser> m_Bots = new List<BotsUser>();
    public Animator onlineuser;

    /*    [Header("Dragon_Tiger Final Cards")]
       public List<string> cardNames; */
    public List<Sprite> finalCards;
    public Sprite backcard;

    [Header("All Cards Available In Deck")]
    public List<Sprite> allcards;

    [Header("Game Cards")]
    public GameObject Dragoncard,
        TigerCard;

    [Header("Coins")]
    public List<GameObject> coins;
    public List<GameObject> dragonobjects,
        tigerobjects,
        tieobjects;

    [Header("Buttons")]
    public List<GameObject> btns;

    [Header("win anim")]
    public GameObject tigerwin,
        dragonwin,
        tieWin;

    /*     [Header("Bet Place Text")]
        public TextMeshProUGUI DText,
            TieText,
            TText; */

    [Header("my bet texts")]
    public TextMeshProUGUI dragonamount;
    public TextMeshProUGUI tigeramount,
        tieamount;
    public int dragonint,
        tigerint,
        tieint;
    public int num;
    private bool GameEnded = false,
        gamestart = false;
    public string betamount;
    public string bet;
    public GameObject buttonclicked;
    private bool click = false,
        online;
    public TextMeshProUGUI gameid;
    public GameObject Reconn_Canvas;
    public DragonVsTigerResult dragonvstigerresult;
    public GameObject historyprediction;
    public List<string> lastwinningprediction;
    public List<GameObject> lastwinningpredictionimagestoshow;
    public float Dragonint,
        Tigerint,
        Tieint;
    public TextMeshProUGUI dragonamounttext,
        tigeramounttext,
        tieamounttext,
        dragonpredictiontext,
        tigerpredictiontext,
        tiepredictiontext;
    public Slider DTpredictionslider,
        Tiepredictionslider;
    Dictionary<string, Sprite> cardDictionary = new Dictionary<string, Sprite>();
    public bool reconnected;
    public bool invoke = true;
    public int timetoinvoke = 0;
    public GameObject show;
    public GameObject stop;
    public Button[] buttons;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;
    public Toggle soundToggle;
    public Toggle musicToggle;

    public GameObject DraginHighlitLine;
    public GameObject TigerHighlitLine;
    public GameObject TieHighlitLine;
    public bool firstHistory;

    IEnumerator stopdragontiger()
    {
        AudioManager._instance.PlayStopBetSound();
        stop.SetActive(true);
        yield return new WaitForSeconds(2.1f);
        stop.SetActive(false);
    }

    private void OnEnable()
    {
        firstHistory = false;
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;

        DOTween.SetTweensCapacity(tweenersCapacity: 1250, sequencesCapacity: 50);

        UserNameText.text = Configuration.GetName();
        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance.profile_image;
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);

        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("dragon_tiger_timer", OnDragonTiger_TimerResponse);
        customNamespace.On<string>("dragon_tiger_status", OnDragonTiger_statusResponse);
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
        UserProfilePic.sprite = SpriteManager.Instance.profile_image;

        // UpdateButtonInteractability(Configuration.GetWallet());
    }

    void OnApplicationPause(bool pauseStatus)
    {
        if (!pauseStatus)
        {
            Debug.Log("RES_Check + resume");
            //  RequestGameStateUpdate();
        }
        else
        {
            // StopCoroutine(aibet());
            // StopCoroutine(onlinebet());
        }
    }

    #region Socket Connection/DisConnection

    private void RequestGameStateUpdate()
    {
        Manager.Close();
        invoke = false;
        //  StopCoroutine(aibet());
        //  StopCoroutine(onlinebet());
        var url = Configuration.BaseSocketUrl;
        Debug.Log("URL+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("dragon_tiger_timer", OnDragonTiger_TimerResponse);
        customNamespace.On<string>("dragon_tiger_status", OnDragonTiger_statusResponse);
        Manager.Open();
        GetTimer();
        reconnected = true;
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

    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("Connect : " + resp.sid);
        IsConnection = true;
        Reconn_Canvas.SetActive(false);

        foreach (Sprite sprite in allcards)
        {
            cardDictionary[sprite.name] = sprite;
        }

        GetTimer();
    }

    public void OnDisconnected()
    {
        //Reconn_Canvas.SetActive(true);
        AudioManager._instance.StopAudio();
    }

    public void Disconnect()
    {
        Manager.Close();
        var customNamespaceSocket = Manager.GetSocket(CustomNamespace);
        customNamespaceSocket.Disconnect();
        StopAllCoroutines(); //
        Debug.Log("Disconnected: ");
        AudioManager._instance.StopAudio();

        AudioManager._instance.StopEffect();
        //LoaderUtil.instance.LoadScene("HomePage");
        SceneLoader.Instance.LoadScene("HomePage");
        //SceneManager.LoadSceneAsync("HomePage");
    }

    #endregion

    #region Timer Functions
    private void GetTimer()
    {
        var customNamespace = Manager.GetSocket(CustomNamespace);
        GameEnded = false;
        try
        {
            customNamespace.Emit("dragon_tiger_timer", "dragon_tiger_timer");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-dragon_tiger_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnDragonTiger_TimerResponse(string args)
    {
        Debug.Log("Timer Game Json :" + args);
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

    #region Dragon_Tiger_Status
    private void OnDragonTiger_statusResponse(string args)
    {
        Debug.Log("RES_Value Status JSON: " + args);
        try
        {
            DragonTigerData = null;
            DragonTigerData = JsonUtility.FromJson<DTSocketResponse>(args);
            if (!firstHistory)
            {
                firstHistory = true;
                ShowLast10Win();
            }
            displayprofiles();

            if (DragonTigerData.game_data[0].status == "0") //gamestart
            {
                string game_id = DragonTigerConfig.GetDragonTigerId();
                bool isSameGame = game_id == DragonTigerData.game_data[0].id;

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

                PlayerPrefs.SetString("dntid", DragonTigerData.game_data[0].id);

                if (!online)
                {
                    online = true;
                    Debug.Log(online + "status" + DragonTigerData.game_data[0].status);
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
                            m_collidertext,
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
                            m_collidertext,
                            isAI: false
                        )
                    );
                }

                /*   if (isSameGame && invoke)
                  {
                      Debug.Log("RES_Check + Called engame");
                     // StartCoroutine(endgame());
                  } */
            }
            else
            {
                CommonUtil.ShowToast(
                    "winning: "
                        + DragonTigerData.game_data[0].winning
                        + " , Game Id: "
                        + DragonTigerData.game_data[0].id
                );
                Debug.Log(online + "Status:" + DragonTigerData.game_data[0].status);
                if (online)
                {
                    online = false;
                    ///StartCoroutine(callendgame());
                    StartCoroutine(EndGame());
                    GameBetUtil.StopBet();
                }
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    /* public IEnumerator callendgame()
    {
        yield return AsyncToCoroutineHelper(EndGame());
    } */

    private IEnumerator AsyncToCoroutineHelper(Task task)
    {
        while (!task.IsCompleted)
        {
            yield return null;
        }

        if (task.IsFaulted)
        {
            throw task.Exception; // Handle any exceptions from the async method
        }
    }

    //  public async Task EndGame()
    // {
    //     if (DragonTigerData.game_data[0].status != "1")
    //         return;
    //     StartCoroutine(stopdragontiger());
    //     // Disable buttons and stop particle systems
    //     SetInteractableState(buttons, false);
    //     StopAllParticles();

    //     Debug.Log("RES_Check + Called endgame 1");
    //     Debug.Log("RES_Check + Game Over");

    //     // Start animations and delays
    //     await Task.Delay(2100);
    //     FetchFinalCards();
    //     timertext.text = "0";

    //     await Task.Delay(1000);
    //     onlineuser.enabled = false;

    //     // Handle winning logic
    //     HandleWinningState();

    //     await Task.Delay(2500);
    //     GameEnded = true;
    //     gamestart = false;

    //     ShowLast10Win();
    //     ResetWinningStates();

    //     // Fetch results and reset values
    //     StartCoroutine(GetResult());
    //     ResetAmounts();

    //     //  StartCoroutine(animateandshowbackcards());
    //     //Animate and show back cards
    //     /*   StartCoroutine(
    //           CardUtil.AnimateFlipCard(
    //               Dragoncard.transform.GetChild(0).transform,
    //               Dragoncard.transform.GetChild(0).GetComponent<SpriteRenderer>(),
    //               backcard,
    //               0.2f,
    //               +1.3f,
    //               0
    //           )
    //       );

    //       StartCoroutine(
    //           CardUtil.AnimateFlipCard(
    //               TigerCard.transform.GetChild(0).transform,
    //               TigerCard.transform.GetChild(0).GetComponent<SpriteRenderer>(),
    //               backcard,
    //               0.2f,
    //               +1.3f,
    //               0
    //           )
    //       ); */
    //     await AnimateAndShowBackCards();
    //     //  StartCoroutine(animateandshowbackcards());
    //     ResetGameValues();
    //     GetTimer();
    // }

    public IEnumerator EndGame()
    {
        if (DragonTigerData.game_data[0].status != "1")
            yield break;
        StartCoroutine(stopdragontiger());
        // Disable buttons and stop particle systems
        SetInteractableState(buttons, false);
        StopAllParticles();

        Debug.Log("RES_Check + Called endgame 1");
        Debug.Log("RES_Check + Game Over");

        // Start animations and delays
        yield return new WaitForSeconds(2.1f);

        FetchFinalCards();
        timertext.text = "0";

        yield return new WaitForSeconds(1f);
        onlineuser.enabled = false;

        // Handle winning logic
        HandleWinningState();

        yield return new WaitForSeconds(2.5f);
        GameEnded = true;
        gamestart = false;

        ShowLast10Win();
        ResetWinningStates();

        // Fetch results and reset values
        StartCoroutine(GetResult());
        ResetAmounts();

        //  StartCoroutine(animateandshowbackcards());
        //Animate and show back cards
        /*   StartCoroutine(
              CardUtil.AnimateFlipCard(
                  Dragoncard.transform.GetChild(0).transform,
                  Dragoncard.transform.GetChild(0).GetComponent<SpriteRenderer>(),
                  backcard,
                  0.2f,
                  +1.3f,
                  0
              )
          );

          StartCoroutine(
              CardUtil.AnimateFlipCard(
                  TigerCard.transform.GetChild(0).transform,
                  TigerCard.transform.GetChild(0).GetComponent<SpriteRenderer>(),
                  backcard,
                  0.2f,
                  +1.3f,
                  0
              )
          ); */

        yield return StartCoroutine(animateandshowbackcards());
        //AnimateAndShowBackCards();
        //  StartCoroutine(animateandshowbackcards());
        ResetGameValues();
        GetTimer();
    }

    public IEnumerator GetResult()
    {
        string Url = DragonTigerConfig.DragonTigerResult;
        CommonUtil.CheckLog("API-Call + DNT Result");
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("game_id", DragonTigerData.game_data[0].id);
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);
        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            CommonUtil.CheckLog("+ Result: " + responseText);
            dragonvstigerresult = new DragonVsTigerResult();
            dragonvstigerresult = JsonConvert.DeserializeObject<DragonVsTigerResult>(responseText);
            if (dragonvstigerresult.code == 101)
            {
                yield return null;
            }
            else if (dragonvstigerresult.code == 102)
            {
                AudioManager._instance.PlayWinSound();
                showtoastmessage("congratulation!! You Won " + dragonvstigerresult.win_amount);
            }
            else if (dragonvstigerresult.code == 103)
            {
                AudioManager._instance.PlayLoseSound();
                showtoastmessage(
                    "Better Luck Next Time, You Lost " + dragonvstigerresult.diff_amount
                );
            }
            GameBetUtil.MoveAllCoinsIntoTop(
                m_DummyObjects,
                timertext.transform,
                m_ColliderList,
                DragonTigerData.game_data[0].winning,
                dragonint,
                tigerint,
                Userprofile,
                profiles,
                () =>
                {
                    updatedata();
                }
            );
        }
    }

    /*  public async Task GetResult()
     {
         string Url = DragonTigerConfig.DragonTigerResult;
         var formData = new Dictionary<string, string>
     {
         { "user_id", Configuration.GetId() },
         { "token", Configuration.GetToken() },
         { "game_id", DragonTigerData.game_data[0].id }
     };

         CommonUtil.ValueLog($"DragonTigerMessage:{Url}");

         // Call your custom Post<T> method to send the request
         dragonvstigerresult = await APIManager.Instance.Post<DragonVsTigerResult>(Url, formData);

         // Check if the response is valid
         if (dragonvstigerresult != null)
         {
             // Handle the response based on the code
             if (dragonvstigerresult.code == 102)
             {
                 gameaudio.clip = winaudio;
                 gameaudio.Play();
                 showtoastmessage("congratulation!! You Won " + dragonvstigerresult.win_amount);
             }
             else if (dragonvstigerresult.code == 103)
             {
                 gameaudio.clip = looseaudio;
                 gameaudio.Play();
                 showtoastmessage("Better Luck Next Time, You Lost " + dragonvstigerresult.diff_amount);
             }

             // Move all coins into the top (no need to await this since it's a synchronous operation)
             GameBetUtil.MoveAllCoinsIntoTop(m_DummyObjects, timertext.transform, m_ColliderList, DragonTigerData.game_data[0].winning, dragonint, tigerint, Userprofile, profiles, () => { });
         }
         else
         {
             Debug.LogError("Failed to get a valid response from the server.");
         }
     } */

    private void SetInteractableState(IEnumerable<Button> buttons, bool state)
    {
        foreach (var btn in buttons)
        {
            btn.interactable = state;
        }
    }

    private void StopAllParticles()
    {
        foreach (var particle in particleSystems)
        {
            particle.Stop();
        }
    }

    IEnumerator BlinkImage(GameObject target_image)
    {
        int blinkCount = 0;

        while (blinkCount < 6)
        {
            target_image.SetActive(true);
            yield return new WaitForSeconds(0.4f);

            target_image.SetActive(false);
            yield return new WaitForSeconds(0.4f);
            blinkCount++;
        }
    }

    private void HandleWinningState()
    {
        Debug.Log("RES_Check + winning " + DragonTigerData.game_data[0].winning);

        switch (DragonTigerData.game_data[0].winning)
        {
            case "0":
                AudioManager._instance.PlayDragonHightligtWin();
                dragonwin.SetActive(true);
                StartCoroutine(BlinkImage(DraginHighlitLine));
                break;
            case "1":
                AudioManager._instance.PlayTigerHighlightWin();
                tigerwin.SetActive(true);
                StartCoroutine(BlinkImage(TigerHighlitLine));
                break;
            default:
                AudioManager._instance.PlayHighlightWinSound();
                StartCoroutine(BlinkImage(TieHighlitLine));
                tieWin.SetActive(true);
                break;
        }
    }

    private void ResetWinningStates()
    {
        dragonwin.SetActive(false);
        tigerwin.SetActive(false);
        tieWin.SetActive(false);
    }

    private void ResetAmounts()
    {
        dragonamount.text = "0";
        tigeramount.text = "0";
        tieamount.text = "0";
    }

    private async Task AnimateAndShowBackCards()
    {
        StartCoroutine(animateandshowbackcards());
        // await Task.Delay(2000); // Replace yield with Task-based delay
        // await Task.Delay(2000); // Simulating the second delay
    }

    private void ResetGameValues()
    {
        dragonint = 0;
        tigerint = 0;
        tieint = 0;
    }

    private void ResetAmounts(bool isSameGame)
    {
        if (isSameGame)
        {
            dragonamount.text = dragonint + "";
            tigeramount.text = tigerint + "";
            tieamount.text = tieint + "";
        }
        else
        {
            dragonint = tigerint = tieint = 0;
            dragonamount.text = dragonint + "";
            tigeramount.text = tigerint + "";
            tieamount.text = tieint + "";
            for (int i = 0; i < m_collidertext.Count; i++)
            {
                m_collidertext[i].text = "0";
            }
        }
    }

    private void ClearGameObjects()
    {
        ClearObjectList(dragonobjects);
        ClearObjectList(tigerobjects);
        ClearObjectList(tieobjects);
        ClearObjectList(m_DummyObjects);

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

    #region DNTPredictionHistory

    public void ShowHistoryPrediction()
    {
        lastwinningprediction.Clear();
        Dragonint = 0;
        Tigerint = 0;
        Tieint = 0;
        for (int i = 0; i < DragonTigerData.last_winning.Count; i++)
        {
            //lastwinningprediction.Add(ABdata.last_winning[i].winning);
            if (DragonTigerData.last_winning[i].winning == "0")
            {
                lastwinningpredictionimagestoshow[i].transform.GetComponent<Image>().sprite =
                    lastwinningsprite[0];
                Dragonint++;
            }
            else if (DragonTigerData.last_winning[i].winning == "1")
            {
                lastwinningpredictionimagestoshow[i].transform.GetComponent<Image>().sprite =
                    lastwinningsprite[1];
                Tigerint++;
            }
            else if (DragonTigerData.last_winning[i].winning == "2")
            {
                lastwinningpredictionimagestoshow[i].transform.GetComponent<Image>().sprite =
                    lastwinningsprite[2];
                Tieint++;
            }
        }

        tigeramounttext.text = Tigerint.ToString();
        dragonamounttext.text = Dragonint.ToString();
        tieamounttext.text = Tieint.ToString();

        float value = (Dragonint / 20);
        float percentage = value * 100;

        Debug.Log("RES_Check + Dragon amount " + Dragonint);
        Debug.Log("RES_Check + Dragon value " + value);
        Debug.Log("RES_Check + Dragon Percentage " + percentage);

        float Tievalue = (Tieint / 20);
        float Tiepercentage = Tievalue * 100;

        Debug.Log("RES_Check + Tie amount " + tieamount);
        Debug.Log("RES_Check + Tie value " + Tievalue);
        Debug.Log("RES_Check + Tie Percentage " + Tiepercentage);

        DTpredictionslider.value = percentage;
        Tiepredictionslider.value = Tiepercentage + percentage;

        dragonpredictiontext.text = percentage + "%";
        tiepredictiontext.text = Tiepercentage + "%";
        tigerpredictiontext.text = (100 - (percentage + Tiepercentage)) + "%";

        historyprediction.SetActive(true);
    }

    #endregion

    #region Dragon_Tiger Status related functions
    public void Stopshowwait()
    {
        // showwaitanim.SetActive(false);
        //putbetanim.SetActive(true);
        if (!gamestart)
        {
            ShowLast10Win();
            StartCoroutine(showanim());
            gamestart = true;
            //  UpdateButtonInteractability(Configuration.GetWallet());
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

    IEnumerator showanim()
    {
        AudioManager._instance.PlayPlaceBetSound();
        show.SetActive(true);
        yield return new WaitForSeconds(2);
        show.SetActive(false);
    }

    public void ShowLast10Win()
    {
        lastwinning.Clear();
        if (DragonTigerData.last_winning.Count > 0)
        {
            for (int i = 0; i < DragonTigerData.last_winning.Count; i++)
            {
                lastwinning.Add(DragonTigerData.last_winning[i].winning);
            }

            for (int i = 0; i < lastwinning.Count; i++)
            {
                if (lastwinningimagestoshow.Count > i)
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
    }

    public async void displayprofiles()
    {
        if (!click)
        {
            UserNameText.text = Configuration.GetName();
            UserWalletText.text = CommonUtil.GetFormattedWallet();
            UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
        }
        for (int i = 0; i < m_Bots.Count; i++)
        {
            m_Bots[i].BotName.text = DragonTigerData.bot_user[i].name;
            m_Bots[i].BotCoin.text = CommonUtil.GetFormattedWallet(
                DragonTigerData.bot_user[i].coin
            );
            m_Bots[i].ProfileImage.sprite = await ImageUtil.Instance.GetSpriteFromURLAsync(
                Configuration.ProfileImage + DragonTigerData.bot_user[i].avatar
            );
        }
    }
    #endregion

    #region Dragon_Tiger Result Related Functions

    public void FetchFinalCards()
    {
        Debug.LogWarning("FetchFinalCards");
        finalCards.Clear();
        if (DragonTigerData.game_cards.Count > 0)
        {
            for (int i = 0; i < DragonTigerData.game_cards.Count; i++)
            {
                string cardname = DragonTigerData.game_cards[i].card.ToLower();
                if (cardDictionary.TryGetValue(cardname, out Sprite sprite))
                {
                    Debug.Log("RES_check + my cards " + sprite.name);
                    finalCards.Add(sprite);
                }
                else
                {
                    Debug.Log("Card not found: " + cardname);
                }
            }

            StartCoroutine(animateandshowcards());
        }
    }

    public IEnumerator animateandshowcards()
    {
        AudioManager._instance?.PlayCardFlipSound();

        Dragoncard.transform.localScale = Vector3.one;
        TigerCard.transform.localScale = Vector3.one;

        Debug.Log("Animateandshowcards DOLocalRotate");
        Dragoncard
            .transform.DOLocalRotate(Vector3.up * 180, 0.8f)
            .OnUpdate(() =>
            {
                // Get the current rotation value of the object
                float currentYRotation = Dragoncard.transform.localEulerAngles.y;

                // Check if the rotation has reached or exceeded 100
                if (currentYRotation >= 82.6)
                {
                    Dragoncard.transform.localScale = new Vector3(-1, 1, 1);
                    Dragoncard.transform.GetChild(0).GetComponent<SpriteRenderer>().sprite =
                        finalCards[0];
                }
            });

        TigerCard
            .transform.DOLocalRotate(Vector3.up * 180, 0.8f)
            .OnUpdate(() =>
            {
                // Get the current rotation value of the object
                float currentYRotation = Dragoncard.transform.localEulerAngles.y;

                // Check if the rotation has reached or exceeded 100
                if (currentYRotation >= 82.6)
                {
                    TigerCard.transform.localScale = new Vector3(-1, 1, 1);
                    TigerCard.transform.GetChild(0).GetComponent<SpriteRenderer>().sprite =
                        finalCards[1];
                    //  Debug.Log("Rotation reached 100 degrees!");
                }
            });

        yield return new WaitForSeconds(1);
    }

    public IEnumerator animateandshowbackcards()
    {
        Debug.Log("AnimateandShowBackCards DOLocalRotate");
        Dragoncard
            .transform.DOLocalRotate(Vector3.zero, 0.8f)
            .OnUpdate(() =>
            {
                // Get the current rotation value of the object
                float currentYRotation = Dragoncard.transform.localEulerAngles.y;

                // Check if the rotation has reached or exceeded 100
                if (currentYRotation <= 83.13)
                {
                    Dragoncard.transform.localScale = Vector3.one;
                    Dragoncard.transform.GetChild(0).GetComponent<SpriteRenderer>().sprite =
                        backcard;
                }
            });
        ;
        TigerCard
            .transform.DOLocalRotate(Vector3.zero, 0.8f)
            .OnUpdate(() =>
            {
                // Get the current rotation value of the object
                float currentYRotation = Dragoncard.transform.localEulerAngles.y;

                // Check if the rotation has reached or exceeded 100
                if (currentYRotation <= 83.13)
                {
                    TigerCard.transform.localScale = Vector3.one;
                    TigerCard.transform.GetChild(0).GetComponent<SpriteRenderer>().sprite =
                        backcard;
                }
            });

        yield return new WaitForSeconds(1);
    }

    #endregion

    #region Aibet

    private List<GameObject> m_DummyObjects = new List<GameObject>();
    public List<Collider2D> m_ColliderList = new List<Collider2D>();
    public List<TextMeshProUGUI> m_collidertext = new List<TextMeshProUGUI> { };

    #endregion

    #region Put_Bet

    async void PlaceBet()
    {
        string url = DragonTigerConfig.DragonTigerPutBet;

        Debug.Log("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", DragonTigerData.game_data[0].id },
            { "bet", bet },
            { "amount", betamount },
        };
        JsonResponse jsonResponse = new JsonResponse();
        jsonResponse = await APIManager.Instance.Post<JsonResponse>(url, formData);

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
                if (bet == "0")
                {
                    dragonint += int.Parse(betamount);
                    m_collidertext[0].text = int.Parse(m_collidertext[0].text) + betamount + "";
                    dragonamount.text = dragonint + "";
                }
                else if (bet == "1")
                {
                    tigerint += int.Parse(betamount);
                    //m_collidertext[1].text = int.Parse(m_collidertext[1].text) + betamount + "";
                    tigeramount.text = tigerint + "";
                }
                else
                {
                    tieint += int.Parse(betamount);
                    //m_collidertext[2].text = int.Parse(m_collidertext[2].text) + betamount + "";
                    tieamount.text = tieint + "";
                }
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

            // UpdateButtonInteractability(Configuration.GetWallet());
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

    public void ClickedDragon()
    {
        if (betamount != "0")
        {
            if (DragonTigerData.game_data[0].status == "0")
            {
                if (float.Parse(Configuration.GetWallet()) >= float.Parse(betamount))
                {
                    bet = "0";
                    if (betamount != null)
                        PlaceBet();

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
                    // NewAudioManager.instance.betaudiosource.clip = NewAudioManager.instance.coinsoundclip;
                    // NewAudioManager.instance.betaudiosource.Play();
                }
                else
                {
                    showtoastmessage("Insufficient Balance");
                }
            }
        }
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedTiger()
    {
        if (betamount != "0")
        {
            if (DragonTigerData.game_data[0].status == "0")
            {
                if (float.Parse(Configuration.GetWallet()) >= float.Parse(betamount))
                {
                    bet = "1";
                    if (betamount != null)
                        PlaceBet();

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
                    showtoastmessage("Insufficient Balance");
                }
            }
        }
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedTie()
    {
        if (betamount != "0")
        {
            if (DragonTigerData.game_data[0].status == "0")
            {
                if (float.Parse(Configuration.GetWallet()) >= float.Parse(betamount))
                {
                    bet = "2";
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[2]; // means tie
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
                    showtoastmessage("Insufficient Balance");
                }
            }
        }
        else
        {
            Debug.Log("RES_Check + add cash");
            showtoastmessage("Insufficient balance");
        }
    }

    #endregion

    #region Dragon_Tiger Win

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
        CommonUtil.CheckLog("RES+Message" + myResponse.message);
        CommonUtil.CheckLog("RES+Code" + myResponse.code);
    }

    public void showtoastmessage(string message)
    {
        LoaderUtil.instance.ShowToast(message);
    }
    #endregion

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

    #region  disablebutton

    #endregion
}
