using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class JPTSocketManager : MonoBehaviour
{
    private SocketManager Manager;
    public Button[] buttons;
    public Toggle soundToggle;
    public Toggle musicToggle;
    public GameObject showstop;

    [SerializeField]
    private bool gamestart,
        GameEnded;
    private bool reconnected,
        invoke;
    public Text stoptext;
    public Image UserProfilePic;
    public Text UserWalletText;
    public Text UserNameText;
    public Transform startPosition;
    public Transform[] endPositions;
    public List<Transform> cardsRotateList;
    public Sprite back_Card;
    public List<Sprite> cardsSpriteList;
    public ParticleSystem[] particleSystems;
    private ParticleSystem currentParticle;
    public GameObject buttonclicked;
    public int num;

    [SerializeField]
    private jktRoot game_data;

    [Header("Game Timer Text")]
    public TextMeshProUGUI timertext;

    public string betamount;
    public List<GameObject> btns;
    private List<string> lastwinning;
    public List<GameObject> lastwinningimagestoshow;
    public List<int> amount = new List<int>();
    public List<TextMeshProUGUI> textamount = new List<TextMeshProUGUI>();
    public List<TextMeshProUGUI> previoustextamount = new List<TextMeshProUGUI>();
    public GameObject putbetanim;
    public List<Sprite> allcards;
    public List<GameObject> highlights = new List<GameObject>();

    #region Partical Syatems
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

    public void CALL_Leave_table()
    {
        Manager.Close();
        StopAllCoroutines();
        this.gameObject.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
    }

    private void OnEnable()
    {
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();

        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        CommonUtil.CheckLog("RES_CHECK Socket URL JackPot Teen Patti: " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(JackpotTeenPattiConfig.CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("jackpot_timer", On_jack_pot_timerResponse);
        customNamespace.On<string>("jackpot_status", OnJackpot_TeenPatti_statusResponse);
        Manager.Open();

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);
    }

    void Start()
    {
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
            null,
            ref betamount
        );
        buttonclick(0);
        clickedbutton(buttons[0].gameObject);
        OnButtonClickPartical(0);
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;
    }

    #region  Music and Sound Toggle
    private void OnMusicToggleChanged(bool isOn)
    {
        CommonUtil.CheckLog($"Music: {isOn}");

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
    #endregion

    #region Connection

    void OnConnected(ConnectResponse resp)
    {
        invoke = true;
        CommonUtil.CheckLog("RES_CHECK On - Connected + " + resp.sid);
        GetTimer();
    }

    #endregion

    #region  Disconnect

    public void OnDisconnected()
    {
        //Reconn_Canvas.SetActive(true);
    }

    #endregion

    #region Timer
    private void GetTimer()
    {
        var customNamespace = Manager.GetSocket(JackpotTeenPattiConfig.CustomNamespace);
        try
        {
            customNamespace.Emit("jackpot_timer", "jackpot_timer");

            CommonUtil.CheckLog("jackpot_timer" + " EMIT-jackpot_timer ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }

    private void On_jack_pot_timerResponse(string args)
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
                null,
                ref betamount
            );
            //UpdateButtonInteractability(Configuration.GetWallet());
        }
    }
    #endregion

    #region socket status
    private void OnJackpot_TeenPatti_statusResponse(string args)
    {
        CommonUtil.CheckLog("RES_CHECK OnJackpot_TeenPAtti_statusResponse ");
        CommonUtil.CheckLog("RES_VALUE Status Game Json :" + args);

        try
        {
            game_data = new jktRoot();
            game_data = JsonUtility.FromJson<jktRoot>(args);
            //  ShowLast10Win();

            if (gamestart)
            {
                string game_id = Configuration.getjptid();
                bool isSameGame = game_id == game_data.game_data[0].id;

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

                PlayerPrefs.SetString("jptid", game_data.game_data[0].id);

                if (game_data.game_data[0].status == "1" && invoke)
                {
                    CommonUtil.ShowToast(
                        "winning: "
                            + game_data.game_data[0].winning
                            + " , Game Id: "
                            + game_data.game_data[0].id
                    );
                    putbetanim.SetActive(false);
                    gamestart = false;
                    GameEnded = true;
                    StartCoroutine(gameover());
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
        timertext.text = "0";
        stoptext.text = "STOP BET";
        showstop.SetActive(true);
        yield return new WaitForSeconds(2);
        showstop.SetActive(false);
        for (int i = 0; i < cardsRotateList.Count; i++)
        {
            CommonUtil.CheckLog("My Card " + game_data.game_cards[i].card.ToLower());
            for (int j = 0; j < allcards.Count; j++)
            {
                if (allcards[j].name.ToLower() == game_data.game_cards[i].card.ToLower()) // Comparing names in a case-insensitive manner
                {
                    CommonUtil.CheckLog("My Card " + allcards[j].name.ToLower());
                    cardsSpriteList.Add(allcards[j]);
                }
            }
            StartCoroutine(
                CardUtil.AnimateFlipCard(
                    cardsRotateList[i],
                    cardsRotateList[i].GetComponent<SpriteRenderer>(),
                    cardsSpriteList[i],
                    0.3f,
                    -2,
                    180
                )
            );
        }
        StartCoroutine(highlightwin(highlights[int.Parse(game_data.game_data[0].winning) - 1]));
        // StartCoroutine(CardUtil.MoveAllCards(cardsRotateList, endPositions, startPosition));
        // //StartCoroutine(patti.MoveAllCards());
        // StartCoroutine(startcards());
    }

    public IEnumerator changetext()
    {
        //yield return new WaitForSeconds(1);
        timertext.text = "0";
        yield return null;
    }

    // IEnumerator anim()
    // {
    //     maincard.gameObject.SetActive(false);
    //     maincard2.gameObject.SetActive(true);
    //     yield return new WaitForSeconds(0.3f);
    //     maincard.gameObject.SetActive(true);
    //     maincard2.gameObject.SetActive(false);
    // }
    #endregion

    #region win functions
    IEnumerator highlightwin(GameObject obj)
    {
        yield return new WaitForSeconds(2.5f);
        updatedata();

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
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(true);
        yield return new WaitForSeconds(0.5f);
        obj.SetActive(false);

        GetResult();
        foreach (var texts in textamount)
        {
            texts.text = "0";
        }
        foreach (var texts in previoustextamount)
        {
            texts.text = "0";
        }
        cardsSpriteList.Clear();
        yield return new WaitForSeconds(2f);
        for (int i = 0; i < cardsRotateList.Count; i++)
        {
            StartCoroutine(
                CardUtil.AnimateFlipCard(
                    cardsRotateList[i],
                    cardsRotateList[i].GetComponent<SpriteRenderer>(),
                    back_Card,
                    0.3f,
                    +2,
                    0
                )
            );
        }
        ShowLast10Win();
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

    public async void GetResult()
    {
        string Url = JackpotTeenPattiConfig.JackpotTeenPattiResult;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", game_data.game_data[0].id },
        };
        CommonUtil.CheckLog(
            "RES_Check + userid + "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " "
                + " gameid "
                + game_data.game_data[0].id
        );
        //AndarBaharBetResult andarbaharresult = new AndarBaharBetResult();
        JTPBetResult jptresultdata = await APIManager.Instance.Post<JTPBetResult>(Url, formData);

        CommonUtil.CheckLog("Result Message" + jptresultdata.message);

        if (jptresultdata.code == 102)
        {
            AudioManager._instance.PlayWinSound();
            CommonUtil.ShowToast("congratulation!! You Won " + jptresultdata.win_amount);
        }
        else if (jptresultdata.code == 103)
        {
            if (jptresultdata.win_amount > 0)
            {
                AudioManager._instance.PlayWinSound();
                CommonUtil.ShowToast("congratulation!! You Won " + jptresultdata.win_amount);
            }
            else
            {
                AudioManager._instance.PlayLoseSound();
                CommonUtil.ShowToast(
                    "Better Luck Next Time, You Lost " + jptresultdata.diff_amount
                );
            }
        }
        else
        {
            ///showtoastmessage(abresultdata.message);
        }
        ShowLast10Win();
    }
    #endregion

    #region showpreviousresult

    public void ShowLast10Win()
    {
        for (int i = 0; i < game_data.last_winning.Count; i++)
        {
            if (i < lastwinningimagestoshow.Count)
            {
                ShowText(
                    int.Parse(game_data.last_winning[i].winning),
                    lastwinningimagestoshow[i]
                        .gameObject.transform.GetChild(0)
                        .GetComponent<TextMeshProUGUI>()
                );
            }
        }
    }

    public void ShowText(int value, TextMeshProUGUI win_text)
    {
        // switch (value)
        // {
        //     case 1:
        //         win_text.text = "SET";
        //         break;
        //     case 2:
        //         win_text.text = "PURE SEQ";
        //         break;
        //     case 3:
        //         win_text.text = "SEQ";
        //         break;
        //     case 4:
        //         win_text.text = "COLOR";
        //         break;
        //     case 5:
        //         win_text.text = "PAIR";
        //         break;
        //     case 6:
        //         win_text.text = "HIGH";
        //         break;
        //     default:
        //         Debug.Log("Default: Value is out of range");
        //         break;
        // }
        switch (value)
        {
            case 1:
                win_text.text = "HIGH";
                break;
            case 2:
                win_text.text = "PAIR";
                break;
            case 3:
                win_text.text = "COLOR";
                break;
            case 4:
                win_text.text = "SEQ";
                break;
            case 5:
                win_text.text = "PURE SEQ";
                break;
            case 6:
                win_text.text = "SET";
                break;
            default:
                Debug.Log("Default: Value is out of range");
                break;
        }
    }

    #endregion

    #region Button Click Actions

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

    public async void PlaceBet(string bet)
    {
        string url = JackpotTeenPattiConfig.JackpotTeenPattiPutBet;

        CommonUtil.CheckLog("RES_Check + API-Call + PlaceBet");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", game_data.game_data[0].id },
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
            if (jsonResponse.code == 200)
            {
                amount[int.Parse(bet) - 1] += int.Parse(betamount);
                textamount[int.Parse(bet) - 1].text = amount[int.Parse(bet) - 1].ToString();
            }
            AudioManager._instance.PlayCoinDrop();
            GameBetUtil.UpdateButtonInteractability(
                Configuration.GetWallet(),
                buttons.ToList(),
                particleSystems.ToList(),
                ref buttonclicked,
                ref currentParticle,
                gamestart,
                buttonclick,
                clickedbutton,
                null,
                ref betamount
            );
            //UpdateButtonInteractability(Configuration.GetWallet());
        }
    }

    public void placebetanim(GameObject btn)
    {
        if (betamount != "0")
        {
            if (gamestart)
            {
                btn.GetComponent<Animator>().enabled = true;
                btn.GetComponent<Animator>().Play("button", 0, 0);
            }
            else
                CommonUtil.ShowToast("Please wait for the round to start");
        }
        else
        {
            CommonUtil.ShowToast("Insuffiecient Balance");
        }
    }

    #endregion

    #region  Reset

    private void ResetAmounts(bool isSameGame)
    {
        if (isSameGame)
        {
            if (game_data.high_card_amount != string.Empty)
                previoustextamount[0].text = game_data.high_card_amount.ToString();
            else
                previoustextamount[0].text = "0";
            if (game_data.pair_amount != string.Empty)
                previoustextamount[1].text = game_data.pair_amount.ToString();
            else
                previoustextamount[1].text = "0";
            if (game_data.color_amount != string.Empty)
                previoustextamount[2].text = game_data.color_amount.ToString();
            else
                previoustextamount[2].text = "0";
            if (game_data.sequence_amount != string.Empty)
                previoustextamount[3].text = game_data.sequence_amount.ToString();
            else
                previoustextamount[3].text = "0";
            if (game_data.pure_sequence_amount != string.Empty)
                previoustextamount[4].text = game_data.pure_sequence_amount.ToString();
            else
                previoustextamount[4].text = "0";
            if (game_data.set_amount != string.Empty)
                previoustextamount[5].text = game_data.set_amount.ToString();
            else
                previoustextamount[5].text = "0";
        }
        else
        {
            for (int i = 0; i < amount.Count; i++)
            {
                amount[i] = 0;
            }

            foreach (TextMeshProUGUI text in textamount)
                text.text = "0";

            foreach (TextMeshProUGUI text in previoustextamount)
                text.text = "0";
        }
    }

    private void ClearGameObjects()
    {
        for (int i = 0; i < amount.Count; i++)
        {
            amount[i] = 0;
        }

        foreach (TextMeshProUGUI text in textamount)
            text.text = "0";

        foreach (TextMeshProUGUI text in previoustextamount)
            text.text = "0";
    }

    #endregion
}
