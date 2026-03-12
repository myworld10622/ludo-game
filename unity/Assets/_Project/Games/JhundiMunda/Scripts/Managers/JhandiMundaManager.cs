using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using AndroApps;
using Best.HTTP;
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

public class JhandiMundaManager : MonoBehaviour
{
    private string CustomNamespace = "/jhandi_munda";

    [Header("last winning Sprite Data")]
    public List<string> lastwinning;
    public List<Sprite> lastwinningsprite;
    public List<GameObject> lastwinningimagestoshow;
    private SocketManager Manager;

    public JMRootObject jm_data;
    public List<GameObject> cards;
    public Sprite back_card;
    public List<GameObject> allcards;
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
    public Image heart,
        spade,
        diamond,
        club,
        face,
        flag;
    public int totalcoinsleft;
    public TextMeshProUGUI heart_amount,
        spade_amount,
        diamond_amount,
        club_amount,
        face_amount,
        flag_amount;
    public int heart_amount_int,
        spade_Amount_int,
        diamond_amount_int,
        club_amount_int,
        face_amount_int,
        flag_amount_int;
    public bool check,
        showrecord,
        online;
    public GameObject bl2;
    public Animator onlineuser;
    public JMBetResult jm_resultdata;
    public GameObject historyprediction;
    public List<string> lastwinningprediction;
    public List<GameObject> lastwinningpredictionimagestoshow;
    public List<int> winners;
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
    private int poolSize = 50;
    #region Partical Syatems
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;
    public List<Button> coinbtns;
    #endregion

    private void OnEnable()
    {
        buttonclick(10);
        clickedbutton(btns[0]);
        coininstantate(0);
        particleSystems[0].Play();
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();

        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        CommonUtil.CheckLog("RES_CHECK Socket URL jhandi munda + " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("jhandi_munda_timer", OnJhandi_Munda_timerResponse);
        customNamespace.On<string>("jhandi_munda_status", OnJhandi_Munda_statusResponse);
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

    void Start()
    {
        foreach (var coin in coins)
        {
            var pool = coin.AddComponent<ObjectPoolUtil>();
            pool.InitializePool(coin, 10);
        }
        GameBetUtil.OnButtonClickParticle(num, particleSystems.ToList(), ref currentParticle);
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

        FindLastInteractableButton(float.Parse(Configuration.GetWallet()), coinbtns);
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
        customNamespace.On<string>("jhandi_munda_timer", OnJhandi_Munda_timerResponse);
        customNamespace.On<string>("jhandi_munda_status", OnJhandi_Munda_statusResponse);
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
            heart_amount.text = heart_amount_int.ToString();
            spade_amount.text = spade_Amount_int.ToString();
            diamond_amount.text = diamond_amount_int.ToString();
            club_amount.text = club_amount_int.ToString();
            face_amount.text = face_amount_int.ToString();
            flag_amount.text = flag_amount_int.ToString();
        }
        else
        {
            CommonUtil.CheckLog("is my data getting reset");
            heart_amount_int =
                spade_Amount_int =
                diamond_amount_int =
                club_amount_int =
                face_amount_int =
                flag_amount_int =
                    0;
            heart_amount.text = heart_amount_int.ToString();
            spade_amount.text = spade_Amount_int.ToString();
            diamond_amount.text = diamond_amount_int.ToString();
            club_amount.text = club_amount_int.ToString();
            face_amount.text = face_amount_int.ToString();
            flag_amount.text = flag_amount_int.ToString();
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
        for (int i = 0; i < jm_data.last_winning.Count; i++)
        {
            lastwinning.Add(jm_data.last_winning[i].winning);
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
                else if (lastwinning[i] == "3")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[2];
                else if (lastwinning[i] == "4")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[3];
                else if (lastwinning[i] == "5")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[4];
                else if (lastwinning[i] == "6")
                    lastwinningimagestoshow[i].transform.GetComponent<Image>().sprite =
                        lastwinningsprite[5];
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
            m_Bots[i].BotName.text = jm_data.bot_user[i].name;
            m_Bots[i].BotCoin.text = jm_data.bot_user[i].coin;
            Sprite bot_sprite = await GetSpriteFromURLAsync(
                Configuration.ProfileImage + jm_data.bot_user[i].avatar
            );
            m_Bots[i].ProfileImage.sprite = bot_sprite;
        }
    }

    public async Task<Sprite> GetSpriteFromURLAsync(string url)
    {
        try
        {
            // Download the image as a Texture2D
            Sprite texture = await DownloadImageToTextureAsync(url);

            // Convert the Texture2D to a Sprite
            return texture;
        }
        catch (System.Exception ex)
        {
            Debug.LogError($"Error downloading image from {url}: {ex.Message}");
            return null;
        }
    }

    private async Task<Sprite> DownloadImageToTextureAsync(string url)
    {
        var tcs = new TaskCompletionSource<Sprite>();

        // Configure the request
        HTTPRequest request = new HTTPRequest(
            new System.Uri(url),
            HTTPMethods.Get,
            (req, res) =>
            {
                if (res.IsSuccess && res.Data != null)
                {
                    try
                    {
                        Debug.Log("Image data received. Processing...");

                        // Create a texture from the raw byte data
                        Texture2D texture = new Texture2D(2, 2);
                        if (texture.LoadImage(res.Data)) // Load the image into the texture
                        {
                            Debug.Log("Image loaded successfully.");
                            Sprite sprite = Sprite.Create(
                                texture,
                                new Rect(0, 0, texture.width, texture.height),
                                new Vector2(0.5f, 0.5f)
                            );
                            tcs.SetResult(sprite);
                        }
                        else
                        {
                            throw new Exception("Failed to load texture from image data.");
                        }
                    }
                    catch (Exception ex)
                    {
                        Debug.LogError($"Error processing image: {ex.Message}");
                        tcs.SetException(ex);
                    }
                }
                else
                {
                    Debug.LogError($"Download failed: {res?.Message ?? "Unknown error"}");
                    tcs.SetException(new Exception(res?.Message ?? "Unknown error"));
                }
            }
        );

        // Increase the buffer size to accommodate larger images
        request.DownloadSettings.ContentStreamMaxBuffered = 10 * 1024 * 1024; // 10 MB

        // Send the request
        request.Send();

        return await tcs.Task;
    }

    #endregion

    #region socket status
    private void OnJhandi_Munda_statusResponse(string args)
    {
        CommonUtil.CheckLog("RES_CHECK On_Jandi_Munda_statusResponse ");
        CommonUtil.CheckLog("RES_VALUE Status Game Json :" + args);
        jm_data = JsonUtility.FromJson<JMRootObject>(args);
        ShowLast10Win();
        updateprofile();
        if (jm_data.game_data[0].status == "0")
        {
            CommonUtil.CheckLog("status" + jm_data.game_data[0].status);
            if (!online)
            {
                CommonUtil.CheckLog("Online false");
                online = true;
                for (int i = 0; i < m_Bots.Count; i++)
                {
                    CommonUtil.CheckLog("user true");
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

            string game_id = Configuration.getjmid();
            bool isSameGame = game_id == jm_data.game_data[0].id;

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
            PlayerPrefs.SetString("jmid", jm_data.game_data[0].id);
        }
        else
        {
            CommonUtil.ShowToast(
                "winning: "
                    + jm_data.game_data[0].winning
                    + " , Game Id: "
                    + jm_data.game_data[0].id
            );
            Debug.Log("status" + jm_data.game_data[0].status);
            Debug.Log("STOP BET:");
            if (online)
            {
                online = false;
                GameBetUtil.StopBet();
            }
        }

        if (jm_data.game_cards.Count != 0)
        {
            int num = 0;
            if (timertext.text == "1" && invoke)
            {
                CommonUtil.CheckLog("stop bet " + jm_data.game_data[0].status);
                timertext.text = "0";
                onlineuser.enabled = false; //(onlineuser is Animator)
                if (jm_data.game_data[0].status == "1")
                {
                    CommonUtil.CheckLog("game completed");
                    StartCoroutine(gameover());
                    gamestart = false;
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

                    start = false;
                }
            }
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

        StartCoroutine(cardanim());
    }

    IEnumerator cardanim() // was public void 0107
    {
        winners.Clear();
        float movetime = 1f;
        m_alldice.ForEach(x => x.gameObject.SetActive(true));
        m_alldice.ForEach(x =>
        {
            x.transform.DOLocalRotate(
                    new Vector3(
                        UnityEngine.Random.Range(0f, 360f),
                        UnityEngine.Random.Range(0f, 360f),
                        UnityEngine.Random.Range(0f, 360f)
                    ),
                    0.5f
                )
                .SetLoops(2);
        });
        yield return new WaitForSeconds(1f);

        for (int i = 0; i < jm_data.game_cards.Count; i++)
        {
            if (jm_data.game_cards[i].card == "1") //heartcard
            {
                winners.Add(1);
            }
            if (jm_data.game_cards[i].card == "2") //spadescard
            {
                winners.Add(2);
            }
            if (jm_data.game_cards[i].card == "3") //diamondcard
            {
                winners.Add(3);
            }
            if (jm_data.game_cards[i].card == "4") //clubscard
            {
                winners.Add(4);
            }
            if (jm_data.game_cards[i].card == "5") //facecard
            {
                winners.Add(5);
            }
            if (jm_data.game_cards[i].card == "6") //flagcard
            {
                winners.Add(6);
            }
        }

        for (int k = 0; k < winners.Count(); k++)
        {
            if (k < m_alldice.Count)
            {
                m_alldice[k].gameObject.SetActive(true);

                if (winners[k] == 1)
                {
                    m_alldice[k]
                        .transform.SetParent(
                            heartcardslocation[
                                UnityEngine.Random.Range(0, heartcardslocation.Count)
                            ]
                        );
                }
                else if (winners[k] == 2)
                {
                    m_alldice[k]
                        .transform.SetParent(
                            spadescardslocation[
                                UnityEngine.Random.Range(0, spadescardslocation.Count)
                            ]
                        );
                }
                else if (winners[k] == 3)
                {
                    m_alldice[k]
                        .transform.SetParent(
                            diamondcardslocation[
                                UnityEngine.Random.Range(0, diamondcardslocation.Count)
                            ]
                        );
                }
                else if (winners[k] == 4)
                {
                    m_alldice[k]
                        .transform.SetParent(
                            clubscardslocation[
                                UnityEngine.Random.Range(0, clubscardslocation.Count)
                            ]
                        );
                }
                else if (winners[k] == 5)
                {
                    m_alldice[k]
                        .transform.SetParent(
                            facecardslocation[UnityEngine.Random.Range(0, facecardslocation.Count)]
                        );
                }
                else if (winners[k] == 6)
                {
                    m_alldice[k]
                        .transform.SetParent(
                            flagcardslocation[UnityEngine.Random.Range(0, flagcardslocation.Count)]
                        );
                }
                m_alldice[k].transform.DOLocalMove(Vector3.zero, movetime);
                m_alldice[k].SetDiceSide(winners[k]);
            }
        }

        DOVirtual.DelayedCall(1f, () => SetHighlighter());
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
            customNamespace.Emit("jhandi_munda_timer", "jhandi_munda_timer");

            CommonUtil.CheckLog("RES_CHECK" + " EMIT-jhandi_munda_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void OnJhandi_Munda_timerResponse(string args)
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
    #endregion

    #region win functions
    public void SetHighlighter()
    {
        List<Image> imagestohighlight = new List<Image>();
        for (int i = 0; i < jm_data.game_cards.Count; i++)
        {
            if (jm_data.game_cards[i].card == "1")
            {
                imagestohighlight.Add(heart);
            }
            if (jm_data.game_cards[i].card == "2")
            {
                imagestohighlight.Add(spade);
            }
            if (jm_data.game_cards[i].card == "3")
            {
                imagestohighlight.Add(diamond);
            }
            if (jm_data.game_cards[i].card == "4")
            {
                imagestohighlight.Add(club);
            }
            if (jm_data.game_cards[i].card == "5")
            {
                imagestohighlight.Add(face);
            }
            if (jm_data.game_cards[i].card == "6")
            {
                imagestohighlight.Add(flag);
            }
        }
        StartCoroutine(highlightwin(imagestohighlight));
    }

    IEnumerator highlightwin(List<Image> one)
    {
        updatedata();
        AudioManager._instance.PlayHighlightWinSound();
        foreach (Image image in one)
            image.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        foreach (Image image in one)
            image.gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        //   AudioManager._instance.PlayHighlightWinSound();
        foreach (Image image in one)
            image.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        foreach (Image image in one)
            image.gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        //  AudioManager._instance.PlayHighlightWinSound();
        foreach (Image image in one)
            image.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        foreach (Image image in one)
            image.gameObject.SetActive(false);
        yield return new WaitForSeconds(0.5f);
        //   AudioManager._instance.PlayHighlightWinSound();
        foreach (Image image in one)
            image.gameObject.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        foreach (Image image in one)
            image.gameObject.SetActive(false);
        totalcoinsleft = 0;
        GetResult();
        heart_amount.text = "0";
        spade_amount.text = "0";
        diamond_amount.text = "0";
        club_amount.text = "0";
        face_amount.text = "0";
        flag_amount.text = "0";
        showrecord = false;
        for (int i = 0; i < m_colidertext.Count; i++)
        {
            m_colidertext[i].text = "0";
        }
        check = false;
        yield return new WaitForSeconds(2f);
        heart_amount_int = 0;
        spade_Amount_int = 0;
        diamond_amount_int = 0;
        club_amount_int = 0;
        face_amount_int = 0;
        flag_amount_int = 0;
        ShowLast10Win();
    }

    #endregion

    #region buttons_Functionality
    public async void PlaceBet()
    {
        string url = JhandiMundaConfig.JhundiMunda_Prediction_PutBet;

        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", jm_data.game_data[0].id },
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
                if (bet == "1")
                {
                    heart_amount_int += int.Parse(betamount);
                    m_colidertext[0].text =
                        int.Parse(m_colidertext[0].text) + int.Parse(betamount) + "";
                    heart_amount.text = heart_amount_int.ToString();
                }
                else if (bet == "2")
                {
                    spade_Amount_int += int.Parse(betamount);
                    m_colidertext[1].text =
                        int.Parse(m_colidertext[1].text) + int.Parse(betamount) + "";
                    spade_amount.text = spade_Amount_int.ToString();
                }
                else if (bet == "3")
                {
                    diamond_amount_int += int.Parse(betamount);
                    m_colidertext[2].text =
                        int.Parse(m_colidertext[2].text) + int.Parse(betamount) + "";
                    diamond_amount.text = diamond_amount_int.ToString();
                }
                else if (bet == "4")
                {
                    club_amount_int += int.Parse(betamount);
                    m_colidertext[3].text =
                        int.Parse(m_colidertext[3].text) + int.Parse(betamount) + "";
                    club_amount.text = club_amount_int.ToString();
                }
                else if (bet == "5")
                {
                    face_amount_int += int.Parse(betamount);
                    m_colidertext[4].text =
                        int.Parse(m_colidertext[4].text) + int.Parse(betamount) + "";
                    face_amount.text = face_amount_int.ToString();
                }
                else if (bet == "6")
                {
                    flag_amount_int += int.Parse(betamount);
                    m_colidertext[5].text =
                        int.Parse(m_colidertext[5].text) + int.Parse(betamount) + "";
                    flag_amount.text = flag_amount_int.ToString();
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
            FindLastInteractableButton(float.Parse(Configuration.GetWallet()), coinbtns);
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
        num = index;

        particleSystems.ToList().ForEach(x => x.Stop());
        particleSystems[index].Play();
    }

    public void ClickedHeart()
    {
        CommonUtil.CheckLog("RES Check " + jm_data.game_data[0].status);
        if (betamount != "0")
        {
            if (jm_data.game_data[0].status == "0")
            {
                bet = "1";
                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
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
                }
                else
                {
                    showtoastmessage("Insufficient Balance");
                }
                // newAudioManager.PlayClip(newAudioManager.coinsoundclip);
            }
        }
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedSpade()
    {
        if (betamount != "0")
        {
            if (jm_data.game_data[0].status == "0")
            {
                bet = "2";

                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
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

    public void ClickedDiamond()
    {
        if (betamount != "0")
        {
            if (jm_data.game_data[0].status == "0")
            {
                bet = "3";

                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
                    if (betamount != null)
                        PlaceBet();

                    var RandomCollider = m_ColliderList[2]; // means bahar
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
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedClub()
    {
        if (betamount != "0")
        {
            if (jm_data.game_data[0].status == "0")
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
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedFace()
    {
        if (betamount != "0")
        {
            if (jm_data.game_data[0].status == "0")
            {
                bet = "5";

                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
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
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }

    public void ClickedFlag()
    {
        if (betamount != "0")
        {
            if (jm_data.game_data[0].status == "0")
            {
                bet = "6";

                if (float.Parse(Configuration.GetWallet()) > float.Parse(betamount))
                {
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
        else
        {
            showtoastmessage("Insufficient balance");
        }
    }
    #endregion

    #region result

    public async void GetResult()
    {
        string Url = JhandiMundaConfig.JhandimundaResult;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", jm_data.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + jm_data.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        jm_resultdata = await APIManager.Instance.Post<JMBetResult>(Url, formData);

        CommonUtil.CheckLog("Result Message" + jm_resultdata.message);
        foreach (var obj in m_alldice)
        {
            obj.SetOriginalPosition();
            obj.gameObject.SetActive(false);
        }
        if (jm_resultdata.code == 102)
        {
            AudioManager._instance.PlayWinSound();
            showtoastmessage("congratulation!! You Won " + jm_resultdata.win_amount);
            CoinAnim(true);
        }
        else if (jm_resultdata.code == 103)
        {
            if (jm_resultdata.win_amount > 0)
            {
                AudioManager._instance.PlayWinSound();
                showtoastmessage("congratulation!! You Won " + jm_resultdata.win_amount);
                CoinAnim(true);
            }
            else
            {
                AudioManager._instance.PlayLoseSound();
                showtoastmessage("Better Luck Next Time, You Lost " + jm_resultdata.diff_amount);
                CoinAnim(false);
            }
        }
        else
        {
            CoinAnim(false);
            ///showtoastmessage(abresultdata.message);
        }
    }

    public void CoinAnim(bool won)
    {
        int userwincoins = 0;
        var hightlitedobjed = model;
        Debug.Log("WINBOBJECT NAME::" + hightlitedobjed);
        Debug.Log("Finded Colider NAME::" + hightlitedobjed);
        m_DummyObjects.ForEach(x =>
        {
            x.transform.localScale = new Vector3(0.3f, 0.3f, 0.3f);
            x.transform.SetParent(model.transform);
            x.transform.DOLocalMove(Vector3.zero, UnityEngine.Random.Range(0.5f, 0.9f))
                .OnComplete(() =>
                {
                    x.transform.SetParent(hightlitedobjed.transform);
                    x.transform.DOLocalMove(model.transform.localPosition, 0.7f)
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
    public GameObject model;
    public List<Transform> heartcardslocation;
    public List<Transform> spadescardslocation;
    public List<Transform> diamondcardslocation;
    public List<Transform> clubscardslocation;
    public List<Transform> facecardslocation;
    public List<Transform> flagcardslocation;
    public List<DiceController> m_alldice;

    #endregion
}
