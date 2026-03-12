using System;
using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using System.Transactions;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class WithdrawManager : MonoBehaviour
{
    public GameObject transaction_prefab,
        redeem_prefab;
    public Transform transaction_parent,
        redeem_parent;
    public GameObject no_data,
        no_redeem_data;
    public TMP_InputField custominput;
    private Dictionary<string, GameObject> instantiatedLogs = new Dictionary<string, GameObject>();
    private Dictionary<string, GameObject> instantiatedredeemLogs =
        new Dictionary<string, GameObject>();

    public List<GameObject> transactionsobj,
        withdrawobjs;

    public Text total,
        bonus,
        winning_wallet,
        unutilized_wallet;


    public GameObject Crypto, Bank;

    public Profile Profile;

    public GameObject withdraw;
    public GameObject Withdrawlogs;

    public GameObject withdrawSelect;
    public GameObject WithdrawlogSelect;


    async void OnEnable()
    {
        custominput.text = "";
        await ShowWithdrawAPI();
        total.text = Configuration.GetWallet();
        bonus.text = Configuration.GetBonus();
        winning_wallet.text = Configuration.GetWinning();
        unutilized_wallet.text = Configuration.GetUnutilized();


        SetDefault();
    }

    public void SetDefault()
    {
        withdraw.SetActive(true);
        withdrawSelect.SetActive(true);
        Withdrawlogs.SetActive(false);
        WithdrawlogSelect.SetActive(false);
    }

    #region switch between buttons

    public async void ShowTransactions()
    {
        foreach (var trans in transactionsobj)
        {
            trans.SetActive(true);
        }

        foreach (var withdraw in withdrawobjs)
        {
            withdraw.SetActive(false);
        }

        await ShowWithdrawTransactionsAPI();
    }

    private string setOption = "0";
    public void SetWithdrawlOption(int set)
    {
        if (set == 0)
        {
            setOption = "0";
            Bank.SetActive(true);
            Crypto.SetActive(false);
        }
        else
        {
            setOption = "1";
            Bank.SetActive(false);
            Crypto.SetActive(true);
        }
    }
    public void ShowWithdraw()
    {
        foreach (var trans in transactionsobj)
        {
            trans.SetActive(false);
        }

        foreach (var withdraw in withdrawobjs)
        {
            withdraw.SetActive(true);
        }
    }

    #endregion

    #region  Show Withdraw

    public void ShowRedeem(Redeem_Outputs output)
    {
        foreach (Transform child in redeem_parent)
        {
            Destroy(child.gameObject);
        }
        instantiatedredeemLogs.Clear();
        if (output.List.Count > 0)
        {
            // Check for new logs
            foreach (var log in output.List)
            {
                GameObject go = Instantiate(redeem_prefab, redeem_parent);
                go.transform.SetSiblingIndex(0);

                go.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = log.amount;

                go.transform.GetComponent<Button>()
                    .onClick.AddListener(() => ApplyWithdraw(log.id));

                instantiatedredeemLogs.Add(log.id, go);
                no_redeem_data.SetActive(false);
            }
        }
        else
        {
            no_redeem_data.SetActive(true);
        }
    }

    #endregion

    #region  Apply Withdraw

    public async void ApplyWithdraw(string id)
    {
        await WithdrawAPI(id);
    }

    public async void ApplyCustomWithdraw()
    {
        if (custominput.text != "")
        {
            await WithdrawCustomAPI(custominput.text);
        }
        else
        {
            LoaderUtil.instance.ShowToast("Please enter amount");
        }
    }

    #endregion

    #region Show Transaction

    public void HandleLogs(WithDrawalLogsOutputs logs)
    {
        foreach (Transform child in transaction_parent)
        {
            Destroy(child.gameObject);
        }
        instantiatedLogs.Clear();

        if (logs.data.Count > 0)
        {
            no_data.SetActive(false);
            // Check for new logs
            for (int j = logs.data.Count - 1; j >= 0; j--)
            {
                var log = logs.data[j];
                GameObject go = Instantiate(transaction_prefab, transaction_parent);
                go.transform.SetSiblingIndex(0); // Add to the top of the parent
                WithdrawtransactionsUI ui = go.GetComponent<WithdrawtransactionsUI>();
                Debug.Log("RES_Check + id contains 2");
                ui.sr_no.text = (j + 1).ToString();
                Debug.Log("RES_Check + id " + log.id);
                ui.coin.text = log.coin;

                string statusText =
                    log.status == "0" ? "Pending"
                    : log.status == "1" ? "Approve"
                    : "Reject";

                ui.status.text = statusText;
                //ui.added_date.text = log.created_date;
                ui.added_date.text = FormatDateTime(log.created_date);

                instantiatedLogs.Add(log.id, go);
                no_data.SetActive(false);
            }
        }
        else
        {
            no_data.SetActive(true);
        }
    }

    #endregion

    #region  API

    public async Task ShowWithdrawTransactionsAPI()
    {
        string Url = Configuration.Url + Configuration.Withdraw;
        CommonUtil.CheckLog("RES_Check + API-Call + Withdraw");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        WithDrawalLogsOutputs details = new WithDrawalLogsOutputs();
        details = await APIManager.Instance.Post<WithDrawalLogsOutputs>(Url, formData);

        HandleLogs(details);
    }

    public async Task ShowWithdrawAPI()
    {
        string Url = Configuration.Url + Configuration.Redeem_list;
        CommonUtil.CheckLog("RES_Check + API-Call + ShowWithdrawAPI");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        Redeem_Outputs redeem = new Redeem_Outputs();
        redeem = await APIManager.Instance.Post<Redeem_Outputs>(Url, formData);

        ShowRedeem(redeem);
    }

    public async Task WithdrawAPI(string id)
    {
        CommonUtil.CheckLog(id);
        string Url = Configuration.Url + Configuration.Redeem_Withdraw;
        CommonUtil.CheckLog("RES_Check + API-Call + WithdrawAPI");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "mobile", "" },
            { "redeem_id", id },
            { "type", setOption },
        };
        messageprint output = new messageprint();
        output = await APIManager.Instance.Post<messageprint>(Url, formData);

        LoaderUtil.instance.ShowToast(output.message);
        PopUpUtil.ButtonCancel(this.gameObject);

        if (output.code == 200)
        {
            //Debug.Log("SUCCESSFULY WITHDRAWAL");
            StartCoroutine(Profile.UpdateWallet());

            DOVirtual.DelayedCall(0.2f, () =>
            {
                total.text = Configuration.GetWallet();
                bonus.text = Configuration.GetBonus();
                winning_wallet.text = Configuration.GetWinning();
                unutilized_wallet.text = Configuration.GetUnutilized();
            });

        }
    }

    public async Task WithdrawCustomAPI(string amount)
    {
        CommonUtil.CheckLog(amount);
        string Url = Configuration.Url + Configuration.Redeem_Withdraw_custom;
        CommonUtil.CheckLog("RES_Check + API-Call + WithdrawAPI");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "amount", amount },
            { "type", setOption },
        };
        messageprint output = new messageprint();
        output = await APIManager.Instance.Post<messageprint>(Url, formData);

        LoaderUtil.instance.ShowToast(output.message);
        PopUpUtil.ButtonCancel(this.gameObject);
        if (output.code == 200)
        {
            // Debug.Log("SUCCESSFULY WITHDRAWAL");
            StartCoroutine(Profile.UpdateWallet());
        }
    }
    #endregion

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
}

