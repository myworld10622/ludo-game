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
    public GameObject accountDetailsPopup;

    public GameObject withdraw;
    public GameObject Withdrawlogs;

    public GameObject withdrawSelect;
    public GameObject WithdrawlogSelect;

    [Header("Skin")]
    public Image[] backgroundImages;
    public Sprite popupBgSprite;          // withdraw popup background sprite
    public Sprite amountBoxSprite;        // Payment Amount Box.png (for custom input area)

    async void OnEnable()
    {
        ApplyDirectSkin();

        if (custominput != null)
        {
            custominput.text = "";
        }

        await ShowWithdrawAPI();
        if (total != null)
        {
            total.text = Configuration.GetWallet();
        }
        if (bonus != null)
        {
            bonus.text = Configuration.GetBonus();
        }
        if (winning_wallet != null)
        {
            winning_wallet.text = Configuration.GetWinning();
        }
        if (unutilized_wallet != null)
        {
            unutilized_wallet.text = Configuration.GetUnutilized();
        }


        SetDefault();
    }

    public void SetDefault()
    {
        if (withdraw != null)        withdraw.SetActive(true);
        if (withdrawSelect != null)  withdrawSelect.SetActive(true);
        if (Withdrawlogs != null)    Withdrawlogs.SetActive(false);
        if (WithdrawlogSelect != null) WithdrawlogSelect.SetActive(false);

        StyleTabButton(withdrawSelect,    active: true);
        StyleTabButton(WithdrawlogSelect, active: false);
    }

    // ── Tab button styling ─────────────────────────────────────────────────────
    private static readonly Color32 TabActive   = new Color32(218, 130,  20, 255); // orange
    private static readonly Color32 TabInactive = new Color32( 90,  14,  24, 255); // dark red
    private static readonly Color32 TabTextActive   = new Color32(255, 255, 255, 255);
    private static readonly Color32 TabTextInactive = new Color32(255, 200, 160, 220);

    private static void StyleTabButton(GameObject tab, bool active)
    {
        if (tab == null) return;
        var img = tab.GetComponent<Image>();
        if (img != null) img.color = active ? TabActive : TabInactive;
        foreach (var t in tab.GetComponentsInChildren<Text>(true))
            t.color = active ? TabTextActive : TabTextInactive;
        foreach (var t in tab.GetComponentsInChildren<TMPro.TMP_Text>(true))
            t.color = active ? TabTextActive : TabTextInactive;
    }

    #region switch between buttons

    public async void ShowTransactions()
    {
        foreach (var trans in transactionsobj)  trans.SetActive(true);
        foreach (var withdraw in withdrawobjs)  withdraw.SetActive(false);

        StyleTabButton(WithdrawlogSelect, active: true);
        StyleTabButton(withdrawSelect,    active: false);

        await ShowWithdrawTransactionsAPI();
    }

    private string setOption = "0";
    public void SetWithdrawlOption(int set)
    {
        if (set == 0)
        {
            setOption = "0";
            if (Bank != null)
            {
                Bank.SetActive(true);
            }
            if (Crypto != null)
            {
                Crypto.SetActive(false);
            }
        }
        else
        {
            setOption = "1";
            if (Bank != null)
            {
                Bank.SetActive(false);
            }
            if (Crypto != null)
            {
                Crypto.SetActive(true);
            }
        }
    }
    public void ShowWithdraw()
    {
        foreach (var trans in transactionsobj)  trans.SetActive(false);
        foreach (var withdraw in withdrawobjs)  withdraw.SetActive(true);

        StyleTabButton(withdrawSelect,    active: true);
        StyleTabButton(WithdrawlogSelect, active: false);
    }

    #endregion

    #region  Show Withdraw

    public void ShowRedeem(Redeem_Outputs output)
    {
        if (redeem_parent != null)
        {
            foreach (Transform child in redeem_parent)
            {
                Destroy(child.gameObject);
            }
        }
        instantiatedredeemLogs.Clear();

        List<List> redeemList = output != null && output.List != null
            ? output.List
            : new List<List>();

        if (redeemList.Count > 0 && redeem_prefab != null && redeem_parent != null)
        {
            // Check for new logs
            foreach (var log in redeemList)
            {
                if (log == null)
                {
                    continue;
                }

                GameObject go = Instantiate(redeem_prefab, redeem_parent);
                go.transform.SetSiblingIndex(0);

                if (go.transform.childCount > 0)
                {
                    TextMeshProUGUI amountText = go.transform.GetChild(0).GetComponent<TextMeshProUGUI>();
                    if (amountText != null)
                    {
                        amountText.text = string.IsNullOrWhiteSpace(log.amount) ? "0" : log.amount;
                    }
                }

                Button button = go.transform.GetComponent<Button>();
                if (button != null)
                {
                    string redeemId = log.id;
                    button.onClick.AddListener(() => ApplyWithdraw(redeemId));
                }

                if (!string.IsNullOrWhiteSpace(log.id) && !instantiatedredeemLogs.ContainsKey(log.id))
                {
                    instantiatedredeemLogs.Add(log.id, go);
                }

                if (no_redeem_data != null)
                {
                    no_redeem_data.SetActive(false);
                }
            }
        }
        else
        {
            if (no_redeem_data != null)
            {
                no_redeem_data.SetActive(true);
            }
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
        if (custominput != null && custominput.text != "")
        {
            await WithdrawCustomAPI(custominput.text);
        }
        else
        {
            string msg = "Please enter amount";
            if (LoaderUtil.instance != null)
                LoaderUtil.instance.ShowToast(msg);
            else
                EasyUI.Toast.Toast.Show(msg, 3f);
        }
    }

    #endregion

    #region Show Transaction

    public void HandleLogs(WithDrawalLogsOutputs logs)
    {
        if (transaction_parent != null)
        {
            foreach (Transform child in transaction_parent)
            {
                Destroy(child.gameObject);
            }
        }
        instantiatedLogs.Clear();

        List<Datum> logItems = logs != null && logs.data != null ? logs.data : new List<Datum>();

        if (logItems.Count > 0 && transaction_prefab != null && transaction_parent != null)
        {
            if (no_data != null)
            {
                no_data.SetActive(false);
            }
            // Check for new logs
            for (int j = logItems.Count - 1; j >= 0; j--)
            {
                var log = logItems[j];
                if (log == null)
                {
                    continue;
                }
                GameObject go = Instantiate(transaction_prefab, transaction_parent);
                go.transform.SetSiblingIndex(0); // Add to the top of the parent
                WithdrawtransactionsUI ui = go.GetComponent<WithdrawtransactionsUI>();
                if (ui == null)
                {
                    continue;
                }
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

                if (!string.IsNullOrWhiteSpace(log.id) && !instantiatedLogs.ContainsKey(log.id))
                {
                    instantiatedLogs.Add(log.id, go);
                }
                if (no_data != null)
                {
                    no_data.SetActive(false);
                }
            }
        }
        else
        {
            if (no_data != null)
            {
                no_data.SetActive(true);
            }
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

        ShowRedeem(redeem ?? new Redeem_Outputs());
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
        messageprint output = await APIManager.Instance.Post<messageprint>(Url, formData);

        if (output == null)
        {
            EasyUI.Toast.Toast.Show("Withdrawal request failed. Please try again.", 3f);
            return;
        }

        if (ShouldPromptAccountDetails(output))
        {
            CommonUtil.SetNextStyledMessageAction(() =>
            {
                PopUpUtil.ButtonCancel(this.gameObject);
                OpenAccountDetailsPopup();
            });
            CommonUtil.ShowStyledMessage(
                "Account details missing. Please update your bank or crypto details to request withdrawal.",
                "Account Details Required",
                true
            );
            return;
        }

        CommonUtil.ShowStyledMessage(
            string.IsNullOrWhiteSpace(output.message) ? "Withdrawal request submitted." : output.message,
            output.code == 200 ? "Success" : "Withdrawal",
            output.code != 200
        );
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
        messageprint output = await APIManager.Instance.Post<messageprint>(Url, formData);

        if (output == null)
        {
            EasyUI.Toast.Toast.Show("Withdrawal request failed. Please try again.", 3f);
            return;
        }

        if (ShouldPromptAccountDetails(output))
        {
            CommonUtil.SetNextStyledMessageAction(() =>
            {
                PopUpUtil.ButtonCancel(this.gameObject);
                OpenAccountDetailsPopup();
            });
            CommonUtil.ShowStyledMessage(
                "Account details missing. Please update your bank or crypto details to request withdrawal.",
                "Account Details Required",
                true
            );
            return;
        }

        string msg = string.IsNullOrWhiteSpace(output.message) ? "Request submitted." : output.message;
        CommonUtil.ShowStyledMessage(msg, output.code == 200 ? "Success" : "Withdrawal", output.code != 200);

        PopUpUtil.ButtonCancel(this.gameObject);
        if (output.code == 200)
        {
            StartCoroutine(Profile.UpdateWallet());
            DOVirtual.DelayedCall(0.25f, () =>
            {
                total.text = Configuration.GetWallet();
                bonus.text = Configuration.GetBonus();
                winning_wallet.text = Configuration.GetWinning();
                unutilized_wallet.text = Configuration.GetUnutilized();
            });
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

    private bool ShouldPromptAccountDetails(messageprint output)
    {
        if (output == null || string.IsNullOrWhiteSpace(output.message))
        {
            return false;
        }

        string msg = output.message.Trim();
        if (msg.IndexOf("Account Details", StringComparison.OrdinalIgnoreCase) >= 0)
        {
            return true;
        }

        if (msg.IndexOf("bank details", StringComparison.OrdinalIgnoreCase) >= 0)
        {
            return true;
        }

        if (msg.IndexOf("crypto details", StringComparison.OrdinalIgnoreCase) >= 0)
        {
            return true;
        }

        return false;
    }

    private void OpenAccountDetailsPopup()
    {
        if (Profile == null)
        {
            Profile = FindObjectOfType<Profile>();
        }

        GameObject popup = accountDetailsPopup;
        if (popup == null)
        {
            popup = GameObject.Find("Bank Details") ?? GameObject.Find("Account Details");
        }

        if (popup == null)
        {
            foreach (var go in Resources.FindObjectsOfTypeAll<GameObject>())
            {
                if (!go) continue;
                if (go.name != "Bank Details" && go.name != "Account Details")
                {
                    continue;
                }
                // Prefer the actual popup panel (no Button component).
                if (go.GetComponent<Button>() != null)
                {
                    continue;
                }
                popup = go;
                break;
            }
        }

        if (popup == null)
        {
            Debug.LogWarning("Account Details popup not found. Assign it in WithdrawManager.");
            return;
        }

        var popupTransform = popup.transform;
        var rootCanvas = popup.GetComponentInParent<Canvas>(true);
        if (rootCanvas != null)
        {
            popupTransform.SetParent(rootCanvas.transform, true);
        }

        popup.SetActive(true);
        var popupRect = popup.GetComponent<RectTransform>();
        if (popupRect != null)
        {
            popupRect.SetAsLastSibling();
        }
        var popupCanvas = popup.GetComponentInParent<Canvas>(true);
        if (popupCanvas != null)
        {
            popupCanvas.overrideSorting = true;
            popupCanvas.sortingOrder = 5000;
        }

        if (Profile != null)
        {
            Profile.PopUpPanelOpen(popup);
            Profile.SwitchBankAndCrypto(0);
        }
        else
        {
            PopUpUtil.ButtonClick(popup);
        }
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

    private void ApplyDirectSkin()
    {
        if (popupBgSprite != null)
        {
            var rootImg = GetComponent<Image>();
            if (rootImg != null) rootImg.sprite = popupBgSprite;
        }

        // Recolor large white background Images — skip buttons/icons/close by name
        foreach (Image img in GetComponentsInChildren<Image>(true))
        {
            if (img == null) continue;
            string n = img.gameObject.name.ToLowerInvariant();
            if (n.Contains("close") || n.Contains("exit") || n.Contains("btn")
                || n.Contains("button") || n.Contains("icon") || n.Contains("logo")
                || n.Contains("chip") || n.Contains("coin") || n.Contains("toggle")) continue;
            RectTransform rt = img.rectTransform;
            float w = rt != null ? Mathf.Abs(rt.rect.width)  : 0f;
            float h = rt != null ? Mathf.Abs(rt.rect.height) : 0f;
            if (w < 100f || h < 100f) continue;
            Color c = img.color;
            if (c.r > 0.85f && c.g > 0.85f && c.b > 0.85f && c.a > 0.5f)
                img.color = new Color32(44, 8, 16, 245);
        }

        // Also apply any explicitly-assigned background images
        if (backgroundImages != null)
            foreach (var img in backgroundImages)
                if (img != null) img.color = new Color32(44, 8, 16, 245);
    }

    private void ApplyPopupFallbackSkin()
    {
        Image[] images = GetComponentsInChildren<Image>(true);
        for (int i = 0; i < images.Length; i++)
        {
            Image image = images[i];
            if (image == null) continue;

            // Never recolor meaningful custom sprites (icons, decorations)
            if (image.sprite != null && !IsDefaultUiSprite(image.sprite)) continue;

            RectTransform rect = image.rectTransform;
            float width  = rect != null ? Mathf.Abs(rect.rect.width)  : 0f;
            float height = rect != null ? Mathf.Abs(rect.rect.height) : 0f;
            string n = image.gameObject.name.ToLowerInvariant();

            bool isLarge = width >= 300f && height >= 80f;
            bool namedSurface =
                n.Contains("bg") || n.Contains("panel") || n.Contains("card")
                || n.Contains("popup") || n.Contains("content") || n.Contains("body")
                || n.Contains("withdraw") || n.Contains("head and tail");

            if (!isLarge && !namedSurface) continue;

            if (n.Contains("head and tail"))   { image.color = new Color32(125, 30, 36, 255); continue; }
            if (n.Contains("card"))            { image.color = new Color32( 48, 10, 18, 245); continue; }
            if (width < 320f && height >= 80f) { image.color = new Color32(118, 18, 28, 255); continue; }

            image.color = new Color32(44, 8, 16, 245);
        }
    }

    private bool IsDefaultUiSprite(Sprite sprite)
    {
        if (sprite == null)
        {
            return true;
        }

        string spriteName = sprite.name;
        return spriteName == "Background"
            || spriteName == "UISprite"
            || spriteName == "InputFieldBackground"
            || spriteName == "UIMask"
            || spriteName == "Knob";
    }
}
