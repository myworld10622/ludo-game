using System;
using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

public class GameHistory : MonoBehaviour
{
    public List<GameObject> myhistoryobjects;

    public List<Transform> m_allChild;

    public GameObject ColorPreTime;

    public TextMeshProUGUI GameTitle;

    public Text bettext;

    public GameObject NodataPenal;

    public ScrollRect scrollRect;
    public GameObject Prefab,
        parent;

    public ScrollRect m_Gameselection;

    private void Start()
    {
        Transform abButton = m_allChild.Find(button => button.gameObject.name == "andar_bahar");
        if (abButton != null)
        {
            ClickButton(abButton);
        }
        m_allChild.ForEach(x => x.GetComponent<Button>().onClick.AddListener(() => ClickButton(x)));
        // PostGetPointRummy(Configuration.AviatorMyHistory);
        PostGetABPlus(Configuration.AndarBaharPlusHistory);

#if UNITY_WEBGL
        m_allChild
            .Find(x => x.gameObject.name == "color_prediction_vertical")
            .gameObject.SetActive(false);
#endif
        ////PostGetHNT(Configuration.HeadAndTileGameHistory);
        ///
    }

    void OnEnable()
    {
        Invoke(nameof(delay), 0.2f);
    }

    private void delay()
    {
        m_Gameselection.verticalNormalizedPosition = 1f;
        //GetComponent<ScrollRect>().verticalNormalizedPosition = 0;
    }

    private void ClickButton(Transform transform)
    {
        GameTitle.text = "";
        //m_allChild.ForEach(x => x.GetComponent<Image>().color = Color.white);
        //transform.GetComponent<Image>().color = ButtonSelectionColor;

        LayoutRebuilder.ForceRebuildLayoutImmediate(scrollRect.content);
        scrollRect.verticalNormalizedPosition = 1f;

        if (transform.gameObject.name == "color_prediction_vertical")
        {
            ColorPreTime.SetActive(true);
        }
        else
        {
            ColorPreTime.SetActive(false);
        }
        if (myhistoryobjects.Count > 0)
        {
            for (int i = 0; i < myhistoryobjects.Count; i++)
            {
                Destroy(myhistoryobjects[i]);
            }
            myhistoryobjects.Clear();
        }

        //increase image scale size
        m_allChild.ForEach(button =>
        {
            if (button != transform)
            {
                GameObject obj = button.transform.GetChild(0).gameObject;
                Color color = obj.GetComponent<Image>().color;
                color.a = 0.5f;
                obj.GetComponent<Image>().color = color;
                for (int i = 0; i < obj.transform.childCount; i++)
                {
                    Color color2 = obj.transform.GetChild(i).GetComponent<Image>().color;
                    color2.a = 0.5f;
                    obj.transform.GetChild(i).GetComponent<Image>().color = color2;
                }
                button.localScale = Vector3.one * 0.9f;
            }
            else
            {
                GameObject obj = button.transform.GetChild(0).gameObject;
                Color color = obj.GetComponent<Image>().color;
                color.a = 1f;
                obj.GetComponent<Image>().color = color;
                for (int i = 0; i < obj.transform.childCount; i++)
                {
                    Color color2 = obj.transform.GetChild(i).GetComponent<Image>().color;
                    color2.a = 1f;
                    obj.transform.GetChild(i).GetComponent<Image>().color = color2;
                }
                button.localScale = Vector3.one * 1.05f;
            }
        });

        switch (transform.gameObject.name)
        {
            case "head_tails":
                bettext.text = "Bet";
                PostGetHNT(Configuration.HeadAndTileGameHistory);
                break;
            case "roulette":
                bettext.text = "Bet";
                PostGetRoulette(Configuration.RouletteGameHistory);
                break;
            case "Aviator":
                bettext.text = "Bet";
                PostGetAviator(Configuration.AviatorMyHistory);
                break;
            case "color_prediction_vertical":
                bettext.text = "Bet";
                PostGetCPV(Configuration.Color_PredicationGameHistory);
                break;
            case "teen_patti":
                bettext.text = "Bet";
                PostGetTP(Configuration.TeenPattiHistory);
                break;
            case "andar_bahar":
                bettext.text = "Bet";
                PostGetAB(Configuration.AndarBaharHistory);
                break;
            case "andar_bahar_plus":
                bettext.text = "Bet";
                PostGetABPlus(Configuration.AndarBaharPlusHistory);
                break;
            case "dragon_tiger":
                bettext.text = "Bet";
                PostGetDNT(Configuration.DragonTigerHistory);
                break;
            case "seven_up_down":
                bettext.text = "Bet";
                PostGetSUP(Configuration.sevenupdownHistory);
                break;
            case "car_roulette":
                bettext.text = "Bet";
                PostGetCarRoulette(Configuration.CarRoulletGameHistory);
                break;
            case "animal_roulette":
                bettext.text = "Bet";
                PostGetAnimalRoulette(Configuration.AnimalRoulletGameHistory);
                break;
            case "point_rummy":
                bettext.text = "Game ID";
                PostGetPointRummy(Configuration.Rummy_PointGameHistory);
                break;
            case "deal_rummy":
                bettext.text = "Table ID";
                PostGetDealRummy(Configuration.Rummy_dealGameHistory);
                break;
            case "pool_rummy":
                bettext.text = "Table ID";
                PostGetPoolRummy(Configuration.Rummy_poolGameHistory);
                break;
            case "bacarate":
                bettext.text = "Bet";
                PostGetBacarate(Configuration.BaccaratGameHistory);
                break;
            case "jackpot_teen_patti":
                bettext.text = "Bet";
                PostGetJackPotTP(Configuration.JackPotGameHistory);
                break;
            case "red_vs_black":
                bettext.text = "Bet";
                PostGetRB(Configuration.RedAndBlackGameHistory);
                break;
            case "jhandi_munda":
                bettext.text = "Bet";
                PostGetJhandiMunda(Configuration.JhandiMundaGameHistory);
                break;
            case "poker":
                bettext.text = "Bet";
                PostGetPoker(Configuration.PockerGameHistory);
                break;
            case "color_prediction":
                bettext.text = "Bet";
                PostGetCP(Configuration.Color_PredicationGameHistory);
                break;
            default:
                CommonUtil.CheckLog("Working Game Name::" + transform.gameObject.name);
                break;
        }
    }

    #region Head and tails

    public async Task PostGetHNT(string url)
    {
        GameTitle.text = "Head & Tail";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        HTGameDataHistory output = new HTGameDataHistory();
        output = await APIManager.Instance.Post<HTGameDataHistory>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Aviator

    public async Task PostGetAviator(string url)
    {
        GameTitle.text = "Aviator";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        AvaitorGameHistoryDataList output = new AvaitorGameHistoryDataList();
        output = await APIManager.Instance.Post<AvaitorGameHistoryDataList>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.game_data.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.game_data.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.game_data[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.game_data[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .game_data[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.game_data[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.game_data[i].user_amount} ({output.game_data[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.game_data[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Color Prediction

    public async Task PostGetCP(string url)
    {
        GameTitle.text = "Color Prediction";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        ColorPredictionGameLogResponse output = new ColorPredictionGameLogResponse();
        output = await APIManager.Instance.Post<ColorPredictionGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    //GameObject go = Instantiate(Prefab, parent.transform);
                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Poker

    public async Task PostGetPoker(string url)
    {
        GameTitle.text = "Poker";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        PokerLogResponse output = new PokerLogResponse();
        output = await APIManager.Instance.Post<PokerLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.Pokerlog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.Pokerlog.Count; i++)
                {
                    // GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.Pokerlog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.Pokerlog[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output.Pokerlog[i].amount;
                    // myhistoryobjects.Add(go);
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.Pokerlog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.Pokerlog[i].user_amount} ({output.Pokerlog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.Pokerlog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Jhandi Munda

    public async Task PostGetJhandiMunda(string url)
    {
        GameTitle.text = "Jhandi Munda";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        JhandiMundaLogResponse output = new JhandiMundaLogResponse();
        output = await APIManager.Instance.Post<JhandiMundaLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.JhandiMundalog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.JhandiMundalog.Count; i++)
                {
                    // GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.JhandiMundalog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output
                    //     .JhandiMundalog[i]
                    //     .invest;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .JhandiMundalog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output
                        .JhandiMundalog[i]
                        .amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.JhandiMundalog[i].user_amount} ({output.JhandiMundalog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.JhandiMundalog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Red Black

    public async Task PostGetRB(string url)
    {
        GameTitle.text = "Red & Black";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        BaccaratGameLogResponse output = new BaccaratGameLogResponse();
        output = await APIManager.Instance.Post<BaccaratGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    // GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Jackpot TP

    public async Task PostGetJackPotTP(string url)
    {
        GameTitle.text = "Jackpot Teen Patti";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        BaccaratGameLogResponse output = new BaccaratGameLogResponse();
        output = await APIManager.Instance.Post<BaccaratGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                    // GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Bacarate

    public async Task PostGetBacarate(string url)
    {
        GameTitle.text = "Baccarat";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        BaccaratGameLogResponse output = new BaccaratGameLogResponse();
        output = await APIManager.Instance.Post<BaccaratGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    //     GameObject go = Instantiate(Prefab, parent.transform);

                    //     go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    //     go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //         output.GameLog[i].added_date
                    //     );
                    //     go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                    //     go.transform.GetChild(4).GetComponent<Text>().text = output
                    //         .GameLog[i]
                    //         .winning_amount;
                    //     myhistoryobjects.Add(go);
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Pool Rummy

    public async Task PostGetPoolRummy(string url)
    {
        GameTitle.text = "Pool Rummy";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        PoolGameLogResponse output = new PoolGameLogResponse();
        output = await APIManager.Instance.Post<PoolGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    // GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].winning_amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output.GameLog[i].user_amount;
                    // myhistoryobjects.Add(go);

                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].game_id;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].commission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Deal Rummy

    public async Task PostGetDealRummy(string url)
    {
        GameTitle.text = "Deal Rummy";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        PoolGameLogResponse output = new PoolGameLogResponse();
        output = await APIManager.Instance.Post<PoolGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    // GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output.GameLog[i].amount;
                    // myhistoryobjects.Add(go);

                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].game_id;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].commission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Point Rummy

    public async Task PostGetPointRummy(string url)
    {
        GameTitle.text = "Point Rummy";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        PointRummyGameLogResponse output = new PointRummyGameLogResponse();
        output = await APIManager.Instance.Post<PointRummyGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.RummyGameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.RummyGameLog.Count; i++)
                {
                    /*  GameObject go = Instantiate(Prefab, parent.transform);

                     go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                     go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                         output.RummyGameLog[i].added_date
                     );
                     go.transform.GetChild(3).GetComponent<Text>().text = output
                         .RummyGameLog[i]
                         .amount;
                     go.transform.GetChild(4).GetComponent<Text>().text = output
                         .RummyGameLog[i]
                         .user_amount;
                     myhistoryobjects.Add(go); */


                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output
                        .RummyGameLog[i]
                        .game_id;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.RummyGameLog[i].user_amount} ({output.RummyGameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.RummyGameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Animal Roulette

    public async Task PostGetAnimalRoulette(string url)
    {
        GameTitle.text = "Animal Roulette";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        AnimalRouletteGameLogResponse output = new AnimalRouletteGameLogResponse();
        output = await APIManager.Instance.Post<AnimalRouletteGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region car Roulette

    public async Task PostGetCarRoulette(string url)
    {
        GameTitle.text = "Car Roulette";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        ABGameLogData output = new ABGameLogData();
        output = await APIManager.Instance.Post<ABGameLogData>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Seven UpDown

    public async Task PostGetSUP(string url)
    {
        GameTitle.text = "Seven Up Down";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        sevenRootObject output = new sevenRootObject();
        output = await APIManager.Instance.Post<sevenRootObject>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Length > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Length; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Dragon Tiger

    public async Task PostGetDNT(string url)
    {
        GameTitle.text = "Dragon vs Tiger";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        DNTRootObject output = new DNTRootObject();
        output = await APIManager.Instance.Post<DNTRootObject>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.GameLog.Length > 0)
            {
                NodataPenal.SetActive(false);
            }
            else
            {
                NodataPenal.SetActive(true);
            }

            for (int i = 0; i < output.GameLog.Length; i++)
            {
                GameObject go = Instantiate(Prefab, parent.transform);

                /*     go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    go.transform.GetChild(3).GetComponent<Text>().text = output
                        .GameLog[i]
                        .amount;
                    go.transform.GetChild(4).GetComponent<Text>().text = output
                        .GameLog[i]
                        .winning_amount;
                    myhistoryobjects.Add(go); */
                go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                go.transform.GetChild(3).GetComponent<Text>().text =
                    $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                    output.GameLog[i].added_date
                );
                myhistoryobjects.Add(go);
            }
        }
    }

    #endregion

    #region Andar Bahar

    public async Task PostGetAB(string url)
    {
        GameTitle.text = "Andar Bahar";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        ABGameLogData output = new ABGameLogData();
        output = await APIManager.Instance.Post<ABGameLogData>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    public async Task PostGetABPlus(string url)
    {
        GameTitle.text = "Andar Bahar Plus";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        ABGameLogData output = new ABGameLogData();
        output = await APIManager.Instance.Post<ABGameLogData>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Teen Patti

    public async Task PostGetTP(string url)
    {
        GameTitle.text = "Teen Patti";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        TPRootObject output = new TPRootObject();
        output = await APIManager.Instance.Post<TPRootObject>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.TeenPattiGameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.TeenPattiGameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.TeenPattiGameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output
                    //     .TeenPattiGameLog[i]
                    //     .invest;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .TeenPattiGameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output
                        .TeenPattiGameLog[i]
                        .invest;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.TeenPattiGameLog[i].winning_amount} ({output.TeenPattiGameLog[i].admin_commission})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.TeenPattiGameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region CPV

    public async Task PostGetCPV(string url)
    {
        GameTitle.text = "Color Prediction Vertical";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        ColorPredictionGameLogResponse output = new ColorPredictionGameLogResponse();
        output = await APIManager.Instance.Post<ColorPredictionGameLogResponse>(url, formData);
        if (output.code == 200)
        {
            if (myhistoryobjects.Count > 0)
            {
                for (int i = 0; i < myhistoryobjects.Count; i++)
                {
                    Destroy(myhistoryobjects[i]);
                }
            }

            if (output.code == 200)
            {
                if (output.GameLog.Count > 0)
                {
                    NodataPenal.SetActive(false);
                }

                for (int i = 0; i < output.GameLog.Count; i++)
                {
                    GameObject go = Instantiate(Prefab, parent.transform);

                    // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(
                    //     output.GameLog[i].added_date
                    // );
                    // go.transform.GetChild(3).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .user_amount;
                    // go.transform.GetChild(4).GetComponent<Text>().text = output
                    //     .GameLog[i]
                    //     .winning_amount;
                    // myhistoryobjects.Add(go);
                    go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                    go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                    go.transform.GetChild(3).GetComponent<Text>().text =
                        $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                    go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                        output.GameLog[i].added_date
                    );
                    myhistoryobjects.Add(go);
                }
            }
            else
            {
                NodataPenal.SetActive(true);
            }
        }
    }

    #endregion

    #region Roulette

    public async Task PostGetRoulette(string url)
    {
        GameTitle.text = "Roulette";
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        RouletteGameHis output = new RouletteGameHis();
        output = await APIManager.Instance.Post<RouletteGameHis>(url, formData);
        CommonUtil.CheckLog("Gamelog Count" + output.GameLog.Count);
        CommonUtil.CheckLog("Code" + output.code);

        if (myhistoryobjects.Count > 0)
        {
            for (int i = 0; i < myhistoryobjects.Count; i++)
            {
                Destroy(myhistoryobjects[i]);
            }
        }

        if (output.code == 200)
        {
            if (output.GameLog.Count > 0)
            {
                NodataPenal.SetActive(false);
            }
            CommonUtil.CheckLog("Gamelog Count Code 200" + output.GameLog.Count);

            for (int i = 0; i < output.GameLog.Count; i++)
            {
                GameObject go = Instantiate(Prefab, parent.transform);

                // go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                // go.transform.GetChild(2).GetComponent<Text>().text = FormatDateTime(output.GameLog[i].added_date
                // );
                // go.transform.GetChild(3).GetComponent<Text>().text = output.GameLog[i].amount;
                // go.transform.GetChild(4).GetComponent<Text>().text = output.GameLog[i].winning_amount;
                // myhistoryobjects.Add(go);
                go.transform.GetChild(1).GetComponent<Text>().text = (i + 1).ToString();
                go.transform.GetChild(2).GetComponent<Text>().text = output.GameLog[i].amount;
                go.transform.GetChild(3).GetComponent<Text>().text =
                    $"{output.GameLog[i].user_amount} ({output.GameLog[i].comission_amount})";
                go.transform.GetChild(4).GetComponent<Text>().text = FormatDateTime(
                    output.GameLog[i].added_date
                );
                myhistoryobjects.Add(go);
            }
            CommonUtil.CheckLog("Myhistoryobjects.Count:" + myhistoryobjects.Count);
        }
        else
        {
            NodataPenal.SetActive(true);
        }
    }

    #endregion

    #region Format Date

    public string FormatDateTime(string inputDateTime)
    {
        // Parse input date time string
        DateTime dateTime = DateTime.ParseExact(
            inputDateTime,
            "yyyy-MM-dd HH:mm:ss",
            System.Globalization.CultureInfo.InvariantCulture
        );

        // Format date part (dd-mmm-yy)
        string formattedDate =
            dateTime.ToString("dd")
            + "-"
            + GetMonthAbbreviation(dateTime.Month)
            + "-"
            + dateTime.ToString("yy");

        // Format time part (hh.mm AM/PM)
        string formattedTime =
            dateTime.ToString("hh:mm") + " " + (dateTime.Hour >= 12 ? "PM" : "AM");

        return formattedDate + "\n" + formattedTime;
        // return formattedDate + "\n" + formattedTime;
    }

    private string GetMonthAbbreviation(int month)
    {
        switch (month)
        {
            case 1:
                return "Jan";
            case 2:
                return "Feb";
            case 3:
                return "Mar";
            case 4:
                return "Apr";
            case 5:
                return "May";
            case 6:
                return "Jun";
            case 7:
                return "Jul";
            case 8:
                return "Aug";
            case 9:
                return "Sep";
            case 10:
                return "Oct";
            case 11:
                return "Nov";
            case 12:
                return "Dec";
            default:
                return "";
        }
    }

    #endregion

    #region API

    public async Task<object> PostGetHistory(string url, Type type)
    {
        // Create the form data dictionary
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        // Make the API call and get the JSON response as a string
        string jsonResponse = await APIManager.Instance.Post<string>(url, formData);

        // Deserialize the JSON string into the object of the provided type
        var deserializedResponse = JsonUtility.FromJson(jsonResponse, type);

        return deserializedResponse;
    }

    #endregion
}
