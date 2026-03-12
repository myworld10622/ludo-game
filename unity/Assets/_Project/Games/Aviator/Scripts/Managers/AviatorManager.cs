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

public class AviatorManager : MonoBehaviour
{
    private string CustomNamespace = "/aviator";

    public Transform m_BgScroll;
    public string game_id;
    public string time;
    public GameObject waiting_text;
    public GameObject plane_wait;
    public List<Sprite> countdown_sprite;
    public Image countdown_image;
    public bool game_start,
        show_wait;

    public Toggle BetToggle;
    public Toggle BetOneToggle;
    public int bet,
        bet2;
    public TextMeshProUGUI bet_text,
        bet_text2,
        put_bet_amount,
        put_bet_amount2,
        into_value;
    private int Initial_Amt1;
    private int Initial_Amt2;
    public List<TextMeshProUGUI> last_blasts;
    public TextMeshProUGUI bet_placed_text,
        bet_placed_text2,
        coins_text,
        coins_text2;
    public int bet_id,
        bet_id2;
    public bool is_bet,
        is_bet2;
    public GameObject cancel,
        redeem,
        bet_button;
    public GameObject cancel2,
        redeem2,
        bet_button2;

    public GameObject text_prefab,
        parent_of_text;
    public GameObject text_prefab2,
        parent_of_text2;
    public TextMeshProUGUI multiply_text,
        amount;
    public TextMeshProUGUI multiply_text2;
    public float multiply_timer,
        multiply_timer2;
    public YAxisMovement y_axis_object;
    public AviatorGameData game_data;
    private SocketManager Manager;
    public bool invoke;
    public Toggle soundToggle;
    public Toggle musicToggle;
    public Image UserProfilePic;
    public TextMeshProUGUI UserWalletText;
    public TextMeshProUGUI UserNameText;

    public bool isFlyStart;
    public AudioListener audioListenerAviator;

    private void OnEnable()
    {
        GameBetUtil.initialScale = Vector3.one * 0.4f;
        GameBetUtil.targetScale = Vector3.one * 0.3f;
        UserNameText.text = Configuration.GetName();
        string walletString = Configuration.GetWallet();

        UserWalletText.text = CommonUtil.GetFormattedWallet();
        UserProfilePic.sprite = SpriteManager.Instance?.profile_image;

        var url = Configuration.BaseSocketUrl;
        CommonUtil.CheckLog("RES_CHECK Socket URL Andar bahar+ " + url);
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(CustomNamespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        customNamespace.On<string>("next_game_id", OnNextGameID);
        customNamespace.On<string>("aviator_timer", OnGetAviatorTimer);
        customNamespace.On<string>("Blast", OnGetAviatorBlast);
        customNamespace.On<object>("Game", onNewMessage);
        Manager.Open();

        musicToggle.isOn = Configuration.GetMusic() == "on";
        soundToggle.isOn = Configuration.GetSound() == "on";

        // Add listeners for toggle changes
        musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
        soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);

        fixedamountsecond(1000);
        fixedamount(10);
    }

    #region Music and Audio

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
        else
        {
            if (isFlyStart)
                AudioManager._instance.PlaneTakeOff();
        }

        //if (isFlyStart)
        //{
        //    audioListenerAviator.enabled = false;
        //}
        //else
        //{
        //    audioListenerAviator.enabled = true;
        //}
    }

    #endregion

    #region next Game ID
    private void OnNextGameID(string args)
    {
        m_BgScroll.DOLocalMoveX(1145f, 0);
        m_BgScroll.DOLocalMoveX(-5200f, 120f);
        Debug.Log("RES_Check + Next game ID :" + args);
        game_id = args;
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }
    #endregion

    #region Timer
    private void OnGetAviatorTimer(string args)
    {
        Debug.Log("RES_Check + Aviator Timer :" + args);
        time = args;
        if (!game_start)
        {
            StartCoroutine(CountdownCoroutine(float.Parse(time)));
            //waitingtext.GetComponent<TextMeshProUGUI>().text = "Waiting For Next Round.... in " + time + "s";
        }
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    private IEnumerator CountdownCoroutine(float time)
    {
        Debug.Log("RES_Check + Time to start" + time);

        countdown_image.sprite = countdown_sprite[(int)time - 1];

        plane_wait.SetActive(true);
        waiting_text.SetActive(true);
        yield return null;
        show_wait = true;
    }
    #endregion

    #region  Plane Blast

    private void OnGetAviatorBlast(string args)
    {
        isFlyStart = false;
        AudioManager._instance.StopEffect();
        audioListenerAviator.enabled = true;
        Debug.Log("RES_Check + OnGetAviatorBlast: Blast :" + args);
        if (BetToggle.isOn)
        {
            putbetbutton();
        }

        if (BetOneToggle.isOn)
        {
            putbetbutton2();
        }
        AudioManager._instance.PlaneCrash();
    }

    #endregion

    #region on new message

    private void onNewMessage(object data)
    {
        Debug.Log("RES_Check + Game_Data " + data);
        try
        {
            var dataDictionary = data as Dictionary<string, object>;

            if (dataDictionary != null)
            {
                game_data = new AviatorGameData
                {
                    time = ConvertToFloat(dataDictionary["time"]),
                    game_id = ConvertToInt(dataDictionary["game_id"]),
                    sec = ConvertToFloat(dataDictionary["sec"]),
                };

                Debug.Log("RES_Check + Blast time + " + game_data.sec);

                if (game_data.sec == 0)
                {
                    Debug.Log("RES_Check + Blast time + Zero");

                    StartCoroutine(timer());
                    burstfromserver();
                    isFlyStart = true;
                    game_start = true;
                    waiting_text.SetActive(false);
                    plane_wait.SetActive(false);
                    AudioManager._instance.PlaneTakeOff();
                    bet_placed_text.gameObject.SetActive(false);
                    bet_placed_text2.gameObject.SetActive(false);

                    if (is_bet)
                    {
                        multiply_text.gameObject.SetActive(true);
                        redeem.SetActive(true);
                        cancel.SetActive(false);
                        bet_button.SetActive(false);
                    }
                    else
                    {
                        multiply_text.gameObject.SetActive(false);
                        redeem.SetActive(false);
                        cancel.SetActive(false);
                        bet_button.SetActive(true);
                    }

                    if (is_bet2)
                    {
                        multiply_text2.gameObject.SetActive(true);
                        redeem2.SetActive(true);
                        cancel2.SetActive(false);
                        bet_button2.SetActive(false);
                    }
                    else
                    {
                        multiply_text2.gameObject.SetActive(false);
                        redeem2.SetActive(false);
                        cancel2.SetActive(false);
                        bet_button2.SetActive(true);
                    }

                    showresult();
                    redeem.SetActive(false);
                    cancel.SetActive(false);
                    bet_button.SetActive(true);
                    redeem2.SetActive(false);
                    cancel2.SetActive(false);
                    bet_button2.SetActive(true);
                    game_start = false;
                }
                else
                {
                    StartCoroutine(timer());
                    Invoke(nameof(burstfromserver), game_data.sec);
                    Invoke(nameof(nextGame), game_data.sec);
                    isFlyStart = true;
                    waiting_text.SetActive(false);
                    plane_wait.SetActive(false);
                    game_start = true;
                    AudioManager._instance.PlaneTakeOff();
                    bet_placed_text.gameObject.SetActive(false);
                    bet_placed_text2.gameObject.SetActive(false);

                    if (is_bet)
                    {
                        multiply_text.gameObject.SetActive(true);
                        redeem.SetActive(true);
                        cancel.SetActive(false);
                        bet_button.SetActive(false);
                    }
                    else
                    {
                        multiply_text.gameObject.SetActive(false);
                        redeem.SetActive(false);
                        cancel.SetActive(false);
                        bet_button.SetActive(true);
                    }

                    if (is_bet2)
                    {
                        multiply_text2.gameObject.SetActive(true);
                        redeem2.SetActive(true);
                        cancel2.SetActive(false);
                        bet_button2.SetActive(false);
                    }
                    else
                    {
                        multiply_text2.gameObject.SetActive(false);
                        redeem2.SetActive(false);
                        cancel2.SetActive(false);
                        bet_button2.SetActive(true);
                    }

                    Debug.Log("RES_Check + Game Time: " + game_data.time);
                    Debug.Log("RES_Check + Game ID: " + game_data.game_id);
                    Debug.Log("RES_Check + Game Sec: " + game_data.sec);
                }
            }
            else
            {
                Debug.LogError("Failed to cast data to Dictionary<string, object>");
            }
        }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    void showresult()
    {
        CommonUtil.ShowToast("You Lost!!! Better Luck next time...");
        multiply_text.gameObject.SetActive(false);
        multiply_text2.gameObject.SetActive(false);
        into_value.gameObject.SetActive(false);
        is_bet = false;
        is_bet2 = false;
    }

    public void burstfromserver()
    {
        into_value.gameObject.SetActive(false);
        y_axis_object.burstanim();
        //isFlyStart = true;
    }

    #endregion

    #region  Calculation

    public IEnumerator Calc()
    {
        Debug.Log("RES_Check + Amount");
        amount.transform.parent.gameObject.SetActive(true);
        float startValue = 1f;
        float elapsedTime = 0f;
        float currentValue;

        while (elapsedTime < game_data.sec)
        {
            elapsedTime += Time.deltaTime;

            float t = elapsedTime / game_data.sec;

            currentValue = Mathf.Lerp(startValue, game_data.time, t);

            currentValue = Mathf.Round(currentValue * 100f) / 100f;
            amount.text = currentValue.ToString("0.00") + "x";

            yield return null;
        }
    }

    #endregion

    #region New Game

    public void nextGame()
    {
        if (redeem.gameObject.activeSelf)
        {
            showresult();
        }
        redeem.SetActive(false);
        if (cancel.gameObject.activeSelf)
        {
            cancel.SetActive(true);
            bet_button.SetActive(false);
        }
        else
        {
            cancel.SetActive(false);
            bet_button.SetActive(true);
        }
        redeem2.SetActive(false);
        if (cancel2.gameObject.activeSelf)
        {
            cancel2.SetActive(true);
            bet_button2.SetActive(false);
        }
        else
        {
            cancel2.SetActive(false);
            bet_button2.SetActive(true);
        }
    }

    #endregion

    #region Timer Function

    public IEnumerator timer()
    {
        float startValue = 1f;
        float elapsedTime = 0f;
        float currentValue;

        while (elapsedTime < game_data.sec)
        {
            elapsedTime += Time.deltaTime;

            float t = elapsedTime / game_data.sec;

            currentValue = Mathf.Lerp(startValue, game_data.time, t);

            currentValue = Mathf.Round(currentValue * 100f) / 100f;
            multiply_text.text = (currentValue * bet).ToString("F1");
            multiply_timer = currentValue;
            multiply_text2.text = (currentValue * bet2).ToString("F1");
            multiply_timer2 = currentValue;
            yield return null;
        }
    }

    #endregion

    #region Conversion

    private float ConvertToFloat(object value)
    {
        if (value is float)
            return (float)value;
        else if (value is double)
            return (float)(double)value;
        else if (value is string)
            return float.Parse((string)value);
        else
            return 0f; // Default value or handle error
    }

    private int ConvertToInt(object value)
    {
        if (value is int)
            return (int)value;
        else if (value is string)
            return int.Parse((string)value);
        else
            return 0; // Default value or handle error
    }

    #endregion

    #region Previous History

    public IEnumerator GetHistoryDetails()
    {
        string Url = Configuration.AviatorHistory;
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("limit", "10");
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        Debug.Log(
            "Res_Value + response send check  user_id: "
                + Configuration.GetId()
                + "Token:"
                + Configuration.GetToken()
                + "limit 10"
        );
        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + Aviator History: " + responseText);
            AviatorBlastRoot Blast_resp = new AviatorBlastRoot();
            Blast_resp = JsonConvert.DeserializeObject<AviatorBlastRoot>(responseText);
            if (Blast_resp.code == 200)
            {
                for (int i = 0; i < last_blasts.Count; i++)
                {
                    Debug.Log("RES_Check + show Text ");
                    if (Blast_resp.game_data.Count > i)
                    {
                        last_blasts[i].text = Blast_resp.game_data[i].blast_time;
                    }
                }
            }
        }
        else
        {
            Debug.Log("Res_Value + Aviator History: " + www.error);
        }
        yield return null;
    }

    #endregion

    #region  Emit Game

    public void gameemit()
    {
        var customNamespace = Manager.GetSocket(CustomNamespace);
        try
        {
            customNamespace.Emit("Game", "Game");

            Debug.Log("RES_CHECK");
            Debug.Log("RES_VALUE" + " EMIT-Game ");
        }
        catch (System.Exception e)
        {
            Debug.LogError(e.ToString());
        }
    }
    #endregion

    #region Place Bet

    public void putbetbutton()
    {
        bet = int.Parse(bet_text.text);
        if (game_id != string.Empty)
        {
            PutBet();
        }
        else
        {
            CommonUtil.ShowToast("Please wait for next round");
        }
    }

    public void putbetbutton2()
    {
        bet2 = int.Parse(bet_text2.text);
        if (game_id != string.Empty)
        {
            PutBet2();
        }
        else
        {
            CommonUtil.ShowToast("Please wait for next round");
        }
    }

    public async void PutBet()
    {
        string Url = AviatorConfig.AviatorAddBet;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", game_id },
            { "amount", bet.ToString() },
        };

        CommonUtil.CheckLog(
            "User_id "
                + Configuration.GetId()
                + " token "
                + Configuration.GetToken()
                + " game_id "
                + game_id
                + " amount "
                + bet
        );

        BetResponse bet_response = await APIManager.Instance.PostRaw<BetResponse>(Url, formData);

        CommonUtil.CheckLog("Result Message" + bet_response.message);

        if (bet_response.code == 200)
        {
            updatedata();
            if (bet_response.code == 102)
            {
                bet_placed_text.text = bet_response.message;
                bet_placed_text.gameObject.SetActive(true);
                await Task.Delay((int)(2 * 1000));
                bet_placed_text.gameObject.SetActive(false);
            }
            else if (bet_response.result.id == 0)
            {
                bet_placed_text.text = "Please wait for the next round";
                bet_placed_text.gameObject.SetActive(true);
                await Task.Delay((int)(2 * 1000));
                bet_placed_text.gameObject.SetActive(false);
            }
            else
            {
                bet_id = bet_response.result.id;
                Debug.Log("RESCheck + Bet_ID " + bet_id);
                float final_amount = float.Parse(Configuration.GetWallet()) - bet;
                coins_text.text = final_amount + "";
                cancel.gameObject.SetActive(true);
                bet_button.gameObject.SetActive(false);
                bet_placed_text.text = "Bet Placed, Please Wait For The Round to Start!";
                bet_placed_text.gameObject.SetActive(true);
                is_bet = true;
            }
        }
        else
        {
            CommonUtil.ShowToast(bet_response.message);
        }
    }

    public async void PutBet2()
    {
        string Url = AviatorConfig.AviatorAddBet;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "game_id", game_id },
            { "amount", bet2.ToString() },
        };

        BetResponse bet_response = await APIManager.Instance.PostRaw<BetResponse>(Url, formData);

        CommonUtil.CheckLog("Result Message" + bet_response.message);

        if (bet_response.code == 200)
        {
            updatedata();
            if (bet_response.code == 102)
            {
                bet_placed_text2.text = bet_response.message;
                bet_placed_text2.gameObject.SetActive(true);
                await Task.Delay((int)(2 * 1000));
                bet_placed_text2.gameObject.SetActive(false);
            }
            else if (bet_response.result.id == 0)
            {
                bet_placed_text2.text = "Please wait for the next round";
                bet_placed_text2.gameObject.SetActive(true);
                await Task.Delay((int)(2 * 1000));
                bet_placed_text2.gameObject.SetActive(false);
            }
            else
            {
                bet_id2 = bet_response.result.id;
                Debug.Log("RESCheck + Bet_ID " + bet_id);
                float final_amount = float.Parse(Configuration.GetWallet()) - bet;
                coins_text2.text = final_amount + "";
                cancel2.gameObject.SetActive(true);
                bet_button2.gameObject.SetActive(false);
                bet_placed_text2.text = "Bet Placed, Please Wait For The Round to Start!";
                bet_placed_text2.gameObject.SetActive(true);
                is_bet2 = true;
            }
        }
        else
        {
            CommonUtil.ShowToast(bet_response.message);
        }
    }

    #endregion

    #region Cancel

    public void cancelbetbutton()
    {
        CancelBet();
        cancel.SetActive(false);
        bet_button.SetActive(true);
    }

    public void cancelbetbutton2()
    {
        CancelBet2();
        cancel2.SetActive(false);
        bet_button2.SetActive(true);
    }

    public async void CancelBet()
    {
        string Url = AviatorConfig.AviatorCancelBet;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "bet_id", bet_id.ToString() },
        };

        bet_id = 0;
        is_bet = false;

        AviatorCancelBet aviator_cancel_bet = await APIManager.Instance.PostRaw<AviatorCancelBet>(
            Url,
            formData
        );

        CommonUtil.CheckLog("Result Message" + aviator_cancel_bet.message);
        updatedata();
        PlayerPrefs.SetString("wallet", aviator_cancel_bet.data.wallet.ToString());
        float final_amount = float.Parse(Configuration.GetWallet());
        coins_text.text = final_amount + "";
        bet_placed_text.gameObject.SetActive(false);
    }

    public async void CancelBet2()
    {
        string Url = AviatorConfig.AviatorCancelBet;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "bet_id", bet_id2.ToString() },
        };

        bet_id2 = 0;
        is_bet2 = false;

        AviatorCancelBet aviator_cancel_bet = await APIManager.Instance.PostRaw<AviatorCancelBet>(
            Url,
            formData
        );

        CommonUtil.CheckLog("Result Message" + aviator_cancel_bet.message);
        updatedata();
        PlayerPrefs.SetString("wallet", aviator_cancel_bet.data.wallet.ToString());
        float final_amount = float.Parse(Configuration.GetWallet());
        coins_text2.text = final_amount + "";
        bet_placed_text2.gameObject.SetActive(false);
    }

    #endregion

    #region Redeem

    public void reddembutton()
    {
        RedeemBet();
    }

    public void reddembutton2()
    {
        RedeemBet2();
    }

    public async void RedeemBet()
    {
        string Url = AviatorConfig.AviatorRedeem;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "amount", multiply_timer.ToString() },
            { "game_id", game_id },
            { "bet_id", bet_id.ToString() },
        };

        multiply_text.gameObject.SetActive(false);
        into_value.gameObject.SetActive(false);
        is_bet = false;

        AviatorRedeemUserData aviator_redeem_user_data =
            await APIManager.Instance.PostRaw<AviatorRedeemUserData>(Url, formData);

        coins_text.text = Configuration.GetWallet();
        redeem.SetActive(false);
        bet_button.SetActive(true);
        updatedata();
        GameObject prefab = Instantiate(text_prefab, parent_of_text.transform);
        StartCoroutine(
            MoveAndFadeCoroutine(
                prefab.GetComponent<Image>(),
                (
                    aviator_redeem_user_data.user_winning_amt
                    + aviator_redeem_user_data.admin_winning_amt
                ).ToString()
            )
        );
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

    public async void RedeemBet2()
    {
        string Url = AviatorConfig.AviatorRedeem;
        CommonUtil.CheckLog("RES_Check + API-Call + Result");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "amount", multiply_timer2.ToString() },
            { "game_id", game_id },
            { "bet_id", bet_id2.ToString() },
        };

        multiply_text2.gameObject.SetActive(false);
        into_value.gameObject.SetActive(false);
        is_bet2 = false;

        AviatorRedeemUserData aviator_redeem_user_data =
            await APIManager.Instance.PostRaw<AviatorRedeemUserData>(Url, formData);

        coins_text2.text = Configuration.GetWallet();
        redeem2.SetActive(false);
        bet_button2.SetActive(true);
        updatedata();
        GameObject prefab = Instantiate(text_prefab2, parent_of_text2.transform);
        StartCoroutine(
            MoveAndFadeCoroutine(
                prefab.GetComponent<Image>(),
                (
                    aviator_redeem_user_data.user_winning_amt
                    + aviator_redeem_user_data.admin_winning_amt
                ).ToString()
            )
        );
    }

    IEnumerator MoveAndFadeCoroutine(Image uiImage, string amount)
    {
        uiImage.gameObject.transform.GetChild(0).GetComponent<Text>().color = Color.green;
        uiImage.gameObject.transform.GetChild(0).GetComponent<Text>().text = "+" + amount;
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

    #endregion

    #region buttons
    public void fixedamount(int amount)
    {
        bet_text.text = amount + "";
        put_bet_amount.text = "Bet " + "\n" + amount + "";
        Initial_Amt1 = amount;
    }

    public void fixedamountsecond(int amount)
    {
        bet_text2.text = amount + "";
        put_bet_amount2.text = "Bet " + "\n" + amount + "";
        Initial_Amt2 = amount;
    }

    public void plus()
    {
        int value = int.Parse(bet_text.text);
        value += Initial_Amt1;
        bet_text.text = value + "";
        put_bet_amount.text = "Bet\n " + value + "";
    }

    public void plusSecond()
    {
        int value = int.Parse(bet_text2.text);
        value += Initial_Amt2;
        CommonUtil.CheckLog(" Value + " + value);
        bet_text2.text = value + "";
        put_bet_amount2.text = "Bet\n " + value + "";
    }

    public void minus(int amount)
    {
        int value = int.Parse(bet_text.text);
        if (value != 10)
            value -= 10;
        if (value > 0)
        {
            bet_text.text = value + "";
            put_bet_amount.text = "Bet\n " + value + "";
        }
    }

    public void minusSecond(int amount)
    {
        int value = int.Parse(bet_text2.text);
        if (value != 1000)
            value -= 1000;
        //value -= 1000;
        if (value > 0)
        {
            bet_text2.text = value + "";
            put_bet_amount2.text = "Bet\n " + value + "";
            Initial_Amt2 = value;
        }
    }

    #endregion

    void OnConnected(ConnectResponse resp)
    {
        invoke = true;
        CommonUtil.CheckLog("RES_CHECK On - Connected + " + resp.sid);
        gameemit();
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

    private void leave(string args)
    {
        CommonUtil.CheckLog("get-table Json :" + args);
        try { }
        catch (System.Exception ex)
        {
            Debug.LogError(ex.ToString());
        }
    }

    public void CALL_Leave_table()
    {
        StopAllCoroutines();
    }

    public void Disconnect()
    {
        Manager.Close();
        var customNamespaceSocket = Manager.GetSocket(CustomNamespace);
        customNamespaceSocket.Disconnect();
        AudioManager._instance.StopEffect();
        SceneLoader.Instance.LoadScene("HomePage");
    }
}
