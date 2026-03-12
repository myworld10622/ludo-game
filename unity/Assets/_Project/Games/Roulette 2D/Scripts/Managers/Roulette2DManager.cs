using System;
using System.Collections;
using System.Collections.Generic;
using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using EasyUI.Toast;
using Mkey;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

public class Roulette2DManager : MonoBehaviour
{
    public string custom_namespace = "/roulette";
    public RouletteRootObject RouletteData;

    [Header("Controller Details")]
    private SocketManager Manager;

    [Header("Wallet Text")]
    public TextMeshProUGUI UserWalletText;

    [Header("Timer Objs")]
    public TextMeshProUGUI timertext;

    [Header("History objs")]
    public List<string> lastwinningprediction;
    public List<TextMeshProUGUI> lastwinningpredictiontext;

    [Header("Game play related variables")]
    public bool can_bet;
    public int bet_amount;
    public List<string> amount_placed;
    public List<Transform> amount_button;
    public List<GameObject> coin_prefab;
    public List<GameObject> instantiated_coins;
    public Text stoptext;
    public GameObject stop;
    bool show;
    public RotateRoulette wheel;

    [Header("highlight")]
    public List<GameObject> twelve_win;
    public List<GameObject> half_win;
    public List<GameObject> even_odd_win;
    public List<GameObject> win;
    public List<GameObject> red_black;

    [Header("Coins Buttons")]
    public List<GameObject> coin_button;
    public GameObject selected_coin;
    public int coin_to_instantiate;

    void Awake()
    {
        SelectCoin(coin_button[0]);
        foreach (var btn in coin_button)
            btn.GetComponent<Button>().interactable = false;
        UserWalletText.text = FormatNumber(Configuration.GetWallet());
        var url = Configuration.BaseSocketUrl;
        Manager = new SocketManager(new Uri(url));
        var customNamespace = Manager.GetSocket(custom_namespace);
        customNamespace.On<ConnectResponse>(SocketIOEventTypes.Connect, OnConnected);
        Manager.Open();
        customNamespace.On(SocketIOEventTypes.Disconnect, OnDisconnected);
        customNamespace.On<string>("roulette_timer", Onroulette_TimerResponse);
        customNamespace.On<string>("roulette_status", OnrouletteStatus_statusResponse);
    }

    #region Socket Connection/DisConnection
    private void OnConnected(ConnectResponse resp)
    {
        Debug.Log("RES_Check + Connect : " + resp.sid);
        // Debug.Log("RES_Check + timer event call");
        // splitsbet.SetActive(true);
        // GetTimer();
    }

    public void OnDisconnected()
    {
        Manager.Close();
    }

    public void ClickOnExitGame()
    {
        Manager.Close();
        SceneLoader.Instance.LoadScene("HomePage");
    }

    #endregion

    #region Timer Functions

    private void Onroulette_TimerResponse(string args)
    {
        Debug.Log("RES_Value + Timer :" + args);
        if (!show)
            StartCoroutine(Showplacebet());

        timertext.text = args;
        can_bet = true;
    }

    IEnumerator Showplacebet()
    {
        AudioManager._instance.PlayPlaceBetSound();
        stoptext.text = "PLACE BET";
        stop.SetActive(true);
        foreach (var btn in coin_button)
            btn.GetComponent<Button>().interactable = true;
        show = true;
        //UpdateButtonInteractability(Configuration.GetWallet());
        //putbetanim.SetActive(true);
        yield return new WaitForSeconds(2);
        stop.SetActive(false);
        //putbetanim.SetActive(false);
    }
    #endregion

    #region Status Function

    private void OnrouletteStatus_statusResponse(string args)
    {
        Debug.Log("RES_Value + Roulette Status JSON: " + args);
        RouletteData = JsonUtility.FromJson<RouletteRootObject>(args);
        ShowHistoryPrediction();
        CheckForGameOver();
    }

    #endregion

    #region Winning History

    public void ShowHistoryPrediction()
    {
        lastwinningprediction.Clear();
        for (int i = 0; i < RouletteData.last_winning.Count; i++)
        {
            lastwinningprediction.Add(RouletteData.last_winning[i].winning);
        }
        for (int i = 0; i < lastwinningpredictiontext.Count; i++)
        {
            if (RouletteData.last_winning.Count > i)
            {
                lastwinningpredictiontext[i].text = lastwinningprediction[i];
            }
        }
    }

    #endregion

    #region place bet

    public void PlceBet(string bet)
    {
        StartCoroutine(PutBet(bet));
    }

    IEnumerator PutBet(string bet)
    {
        if (can_bet)
        {
            /*string url = Configuration.BaseUrl + "api/Roulette/place_bet";*/
            string url = Configuration.RoulettePlaceBet;
            WWWForm form = new WWWForm();

            form.AddField("user_id", Configuration.GetId());
            form.AddField("token", Configuration.GetToken());
            form.AddField("game_id", RouletteData.game_data[0].id);
            form.AddField("bet", bet);
            form.AddField("amount", bet_amount);
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
                    RoulettePutBetResponse bet_resp = JsonUtility.FromJson<RoulettePutBetResponse>(
                        request.downloadHandler.text
                    );
                    Debug.Log("RES_Check + betresp + " + request.downloadHandler.text);
                    if (bet_resp.code == 200)
                    {
                        AudioManager._instance.PlayCoinDrop();
                        amount_placed[int.Parse(bet)] = amount_placed[int.Parse(bet)] + bet_amount;
                        GameObject instance = Instantiate(
                            coin_prefab[coin_to_instantiate].gameObject,
                            selected_coin.transform.position,
                            Quaternion.identity
                        );

                        instantiated_coins.Add(instance);

                        // Set parent to selected_coin
                        instance.transform.SetParent(selected_coin.transform);

                        // Remove the SpriteRenderer and add a UI.Image
                        SpriteRenderer spriteRenderer = instance.GetComponent<SpriteRenderer>();
                        if (spriteRenderer != null)
                        {
                            Destroy(spriteRenderer); // Remove SpriteRenderer
                        }

                        // Add the UI.Image component to the instance
                        Image image = instance.AddComponent<Image>();

                        // Set the sprite for the Image component
                        image.sprite = coin_prefab[coin_to_instantiate]
                            .GetComponent<SpriteRenderer>()
                            .sprite;

                        // Set the RectTransform for positioning (since it's now UI)
                        RectTransform rectTransform = instance.GetComponent<RectTransform>();
                        rectTransform.sizeDelta = new Vector2(100, 100); // Adjust size if needed
                        rectTransform.anchoredPosition = Vector2.zero; // Position the image in the parent

                        instance.transform.localScale = new Vector3(0.5f, 0.5f, 0.5f);

                        // Animate the movement and scaling
                        instance
                            .transform.DOMove(amount_button[int.Parse(bet)].position, 0.5f)
                            .OnComplete(() =>
                            {
                                // After move, change the parent to the target and scale the object
                                instance.transform.SetParent(amount_button[int.Parse(bet)]);
                                rectTransform.anchoredPosition = Vector2.zero;
                                instance.transform.DOScale(0.48f, 0.3f); // Scale to 0.95 in 0.3 seconds
                            });
                    }
                    else
                    {
                        //CommonUtil.ShowToast(bet_resp.message);
                        Toast.Show(bet_resp.message, 3f);
                    }
                }
            }
        }
    }

    #endregion

    #region Select Coins

    public void SelectCoin(GameObject selected)
    {
        selected_coin = selected;

        for (int i = 0; i < coin_button.Count; i++)
        {
            if (coin_button[i] == selected_coin)
            {
                coin_button[i].transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
                coin_to_instantiate = i;
            }
            else
                coin_button[i].transform.localScale = new Vector3(1f, 1f, 1f);
        }

        bet_amount = int.Parse(selected_coin.name);
    }

    #endregion

    #region Winning Condition

    public void CheckForGameOver()
    {
        if (RouletteData.game_data[0].status == "1")
        {
            foreach (var btn in coin_button)
                btn.GetComponent<Button>().interactable = false;
            AudioManager._instance.PlayStopBetSound();
            stoptext.text = "STOP BET";
            stop.SetActive(true);
            show = false;
            DOVirtual.DelayedCall(
                2f,
                () =>
                {
                    stop.SetActive(false);
                }
            );
            Debug.Log("RES_Check + Game Ended");
            Debug.Log("RES_Value + Game Status 1");
            timertext.text = "0";
            StartCoroutine(ShowWinner());
        }
    }

    public IEnumerator ShowWinner()
    {
        yield return new WaitForSeconds(2);
        wheel.enabled = true;
        yield return new WaitForSeconds(9);
        //CommonUtil.CheckLog("winning amount " + RouletteData.game_data[0].winning);
        //Toast.Show("congratulation!! You Won " + RouletteData.game_data[0].winning, 3f);
        Debug.Log($"VRAJ || Win from this 3");
        AudioManager._instance.PlayHighlightWinSound();
        StartCoroutine(ReStart());
        StartCoroutine(HighlighWinner(Get12Range(int.Parse(RouletteData.game_data[0].winning))));
        StartCoroutine(HighlighWinner(GethalfRange(int.Parse(RouletteData.game_data[0].winning))));
        StartCoroutine(HighlighWinner(GetevenRange(int.Parse(RouletteData.game_data[0].winning))));
        StartCoroutine(HighlighWinner(GetRedRange(int.Parse(RouletteData.game_data[0].winning))));
        StartCoroutine(HighlighWinner(GetNumRange(int.Parse(RouletteData.game_data[0].winning))));
    }

    public IEnumerator HighlighWinner(GameObject obj)
    {
        for (int i = 0; i < 5; i++)
        {
            // Toggle the object (enable it)
            obj.SetActive(true);
            yield return new WaitForSeconds(0.5f);

            // Toggle the object (disable it)
            obj.SetActive(false);
            yield return new WaitForSeconds(0.5f);
        }
    }

    GameObject Get12Range(int num)
    {
        // Using integer division to calculate the range
        int range = num / 12;

        switch (range)
        {
            case 0:
                return twelve_win[0]; // First 12
            case 1:
                return twelve_win[1]; // Second 12
            case 2:
                return twelve_win[2]; // Third 12
            default:
                return null; // Out of range
        }
    }

    GameObject GethalfRange(int num)
    {
        if (num < 19)
            return half_win[0];
        else
            return half_win[1];
    }

    GameObject GetevenRange(int num)
    {
        // Using integer division to calculate the range
        bool isEven = num % 2 == 0;

        if (isEven)
            return even_odd_win[0];
        else
            return even_odd_win[1];
    }

    GameObject GetRedRange(int num)
    {
        foreach (var obj in amount_button)
        {
            if (int.Parse(obj.name) == num)
            {
                if (obj.GetComponent<Color_Holder>().color == "red")
                {
                    return red_black[0];
                }
                else if (obj.GetComponent<Color_Holder>().color == "black")
                {
                    return red_black[1];
                }
            }
        }
        return null;
    }

    GameObject GetNumRange(int num)
    {
        foreach (var obj in win)
        {
            if (int.Parse(obj.name) == num)
            {
                return obj;
            }
        }
        return null;
    }

    public IEnumerator ReStart()
    {
        yield return new WaitForSeconds(2.5f);
        StartCoroutine(GetResult());
        yield return new WaitForSeconds(1);
        ClearCoins();
    }

    public void ClearCoins()
    {
        foreach (var coin in instantiated_coins)
            Destroy(coin.gameObject);

        instantiated_coins.Clear();
    }

    #endregion

    #region Fetch Result

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
            RouletteBetResult rouletteResult = new RouletteBetResult();
            rouletteResult = JsonConvert.DeserializeObject<RouletteBetResult>(responseText);
            if (rouletteResult.code == 101)
            {
                yield return null;
            }
            else if (rouletteResult.code == 102)
            {
                AudioManager._instance.PlayWinSound();
                //CommonUtil.ShowToast("congratulation!! You Won " + rouletteResult.win_amount);
                Toast.Show("congratulation!! You Won " + rouletteResult.win_amount, 3f);
                Debug.Log($"VRAJ || Win from this 1");
            }
            else if (rouletteResult.code == 103)
            {
                if (rouletteResult.win_amount > 0)
                {
                    AudioManager._instance.PlayWinSound();
                    //CommonUtil.ShowToast("congratulation!! You Won " + rouletteResult.win_amount);
                    Toast.Show("congratulation!! You Won " + rouletteResult.win_amount, 3f);
                    Debug.Log($"VRAJ || Win from this 1");
                }
                else
                {
                    AudioManager._instance.PlayLoseSound();
                    //CommonUtil.ShowToast(
                    //    "Better Luck Next Time, You Lost " + rouletteResult.diff_amount
                    //);
                    Toast.Show("Better Luck Next Time, You Lost " + rouletteResult.diff_amount, 3f);
                }
            }
        }
    }

    #endregion

    #region wallet related functions

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
}
