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

    [Header("Skin")]
    public Image[] backgroundImages;
    public Sprite popupBgSprite;          // withdraw popup background sprite
    public Sprite amountBoxSprite;        // Payment Amount Box.png (for custom input area)

    async void OnEnable()
    {
        ApplyDirectSkin();
        ApplyResponsiveWithdrawLayout();

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
        ApplyResponsiveWithdrawLayout();
    }

    public void SetDefault()
    {
        if (withdraw != null)        withdraw.SetActive(true);
        if (withdrawSelect != null)  withdrawSelect.SetActive(true);
        if (Withdrawlogs != null)    Withdrawlogs.SetActive(false);
        if (WithdrawlogSelect != null) WithdrawlogSelect.SetActive(false);

        StyleTabButton(withdrawSelect,    active: true);
        StyleTabButton(WithdrawlogSelect, active: false);
        ApplyResponsiveWithdrawLayout();
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
        ApplyResponsiveWithdrawLayout();
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
        ApplyResponsiveWithdrawLayout();
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
                ApplyWideRowLayout(go, 1280f, 92f);
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
        ApplyResponsiveWithdrawLayout();
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
        messageprint output = await APIManager.Instance.Post<messageprint>(Url, formData);

        if (output == null)
        {
            EasyUI.Toast.Toast.Show("Withdrawal request failed. Please try again.", 3f);
            return;
        }

        string msg = string.IsNullOrWhiteSpace(output.message) ? "Request submitted." : output.message;
        if (LoaderUtil.instance != null)
            LoaderUtil.instance.ShowToast(msg);
        else
            EasyUI.Toast.Toast.Show(msg, 3f);

        PopUpUtil.ButtonCancel(this.gameObject);
        if (output.code == 200)
        {
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

    private void ApplyDirectSkin()
    {
        Image rootImg = GetComponent<Image>();
        if (rootImg != null)
        {
            rootImg.sprite = null;
            rootImg.type = Image.Type.Simple;
            rootImg.color = new Color32(44, 8, 16, 245);
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

    private void ApplyResponsiveWithdrawLayout()
    {
        RectTransform root = transform as RectTransform;
        Canvas canvas = GetComponentInParent<Canvas>();
        RectTransform canvasRect = canvas != null ? canvas.transform as RectTransform : null;
        Rect bounds = canvasRect != null ? canvasRect.rect : new Rect(0f, 0f, Screen.width, Screen.height);
        bool portrait = bounds.height >= bounds.width;

        if (root != null)
        {
            root.anchorMin = new Vector2(0.5f, 0.5f);
            root.anchorMax = new Vector2(0.5f, 0.5f);
            root.pivot = new Vector2(0.5f, 0.5f);
            root.anchoredPosition = Vector2.zero;
            root.localScale = Vector3.one;
            root.sizeDelta = portrait ? new Vector2(980f, 1420f) : new Vector2(1540f, 900f);
        }

        ApplyWithdrawInnerPanelLayout(portrait);
        StylePopupTexts(transform, portrait ? 32 : 26, portrait ? 38 : 30);
        StylePopupInputs(transform, portrait ? 42 : 34, portrait ? 108f : 82f);
        StylePopupButtons(transform, portrait ? 34 : 28, portrait ? 92f : 72f);
        StylePopupScrollRects(transform, portrait ? 960f : 1280f);

        if (redeem_parent != null)
        {
            EnsureContentWidth(redeem_parent as RectTransform, portrait ? 920f : 1180f);
        }
        if (transaction_parent != null)
        {
            EnsureContentWidth(transaction_parent as RectTransform, 1280f);
        }

        if (root != null)
        {
            Canvas.ForceUpdateCanvases();
            LayoutRebuilder.ForceRebuildLayoutImmediate(root);
        }
    }

    private static void ApplyWideRowLayout(GameObject row, float width, float height)
    {
        if (row == null)
        {
            return;
        }

        RectTransform rect = row.transform as RectTransform;
        if (rect != null)
        {
            rect.sizeDelta = new Vector2(width, height);
        }

        LayoutElement layout = row.GetComponent<LayoutElement>() ?? row.AddComponent<LayoutElement>();
        layout.minWidth = width;
        layout.preferredWidth = width;
        layout.minHeight = height;
        layout.preferredHeight = height;
    }

    private static void EnsureContentWidth(RectTransform content, float width)
    {
        if (content == null)
        {
            return;
        }

        content.anchorMin = new Vector2(0f, 1f);
        content.anchorMax = new Vector2(0f, 1f);
        content.pivot = new Vector2(0f, 1f);
        content.sizeDelta = new Vector2(Mathf.Max(content.sizeDelta.x, width), content.sizeDelta.y);
    }

    private static void StylePopupTexts(Transform root, int minTextSize, int minTitleSize)
    {
        foreach (Text text in root.GetComponentsInChildren<Text>(true))
        {
            if (text == null) continue;
            bool title = text.text != null && text.text.IndexOf("Withdraw", StringComparison.OrdinalIgnoreCase) >= 0;
            int size = title ? minTitleSize : minTextSize;
            text.fontSize = Mathf.Max(text.fontSize, size);
            text.resizeTextForBestFit = true;
            text.resizeTextMinSize = Mathf.Max(18, size - 8);
            text.resizeTextMaxSize = Mathf.Max(text.fontSize, size);
            text.verticalOverflow = VerticalWrapMode.Overflow;
            text.horizontalOverflow = HorizontalWrapMode.Wrap;
        }

        foreach (TMP_Text text in root.GetComponentsInChildren<TMP_Text>(true))
        {
            if (text == null) continue;
            bool title = text.text != null && text.text.IndexOf("Withdraw", StringComparison.OrdinalIgnoreCase) >= 0;
            float size = title ? minTitleSize : minTextSize;
            text.fontSize = Mathf.Max(text.fontSize, size);
            text.enableAutoSizing = true;
            text.fontSizeMin = Mathf.Max(18f, size - 8f);
            text.fontSizeMax = Mathf.Max(text.fontSize, size);
            text.overflowMode = TextOverflowModes.Overflow;
        }
    }

    private static void StylePopupInputs(Transform root, int fontSize, float height)
    {
        foreach (TMP_InputField input in root.GetComponentsInChildren<TMP_InputField>(true))
        {
            if (input == null) continue;
            LayoutElement layout = input.GetComponent<LayoutElement>() ?? input.gameObject.AddComponent<LayoutElement>();
            layout.minHeight = height;
            layout.preferredHeight = height;
            if (input.textComponent != null)
            {
                input.textComponent.fontSize = fontSize;
                input.textComponent.enableAutoSizing = false;
            }
            TMP_Text placeholder = input.placeholder as TMP_Text;
            if (placeholder != null)
            {
                placeholder.fontSize = Mathf.Max(24, fontSize - 4);
                placeholder.enableAutoSizing = false;
            }
        }
    }

    private static void StylePopupButtons(Transform root, int fontSize, float height)
    {
        foreach (Button button in root.GetComponentsInChildren<Button>(true))
        {
            if (button == null) continue;
            LayoutElement layout = button.GetComponent<LayoutElement>() ?? button.gameObject.AddComponent<LayoutElement>();
            layout.minHeight = Mathf.Max(layout.minHeight, height);
            layout.preferredHeight = Mathf.Max(layout.preferredHeight, height);
            Text label = button.GetComponentInChildren<Text>(true);
            if (label != null)
            {
                label.fontSize = Mathf.Max(label.fontSize, fontSize);
                label.resizeTextForBestFit = true;
                label.resizeTextMinSize = Mathf.Max(18, fontSize - 8);
                label.resizeTextMaxSize = Mathf.Max(label.fontSize, fontSize);
            }
            TMP_Text tmpLabel = button.GetComponentInChildren<TMP_Text>(true);
            if (tmpLabel != null)
            {
                tmpLabel.fontSize = Mathf.Max(tmpLabel.fontSize, fontSize);
                tmpLabel.enableAutoSizing = true;
                tmpLabel.fontSizeMin = Mathf.Max(18, fontSize - 8);
                tmpLabel.fontSizeMax = Mathf.Max(tmpLabel.fontSize, fontSize);
            }
        }
    }

    private static void StylePopupScrollRects(Transform root, float minContentWidth)
    {
        foreach (ScrollRect scrollRect in root.GetComponentsInChildren<ScrollRect>(true))
        {
            if (scrollRect == null) continue;
            scrollRect.vertical = true;
            scrollRect.horizontal = true;
            scrollRect.movementType = ScrollRect.MovementType.Elastic;
            scrollRect.scrollSensitivity = 70f;
            RectTransform contentRect = scrollRect.content;
            if (contentRect != null)
            {
                EnsureContentWidth(contentRect, minContentWidth);
            }
        }
    }

    private void ApplyWithdrawInnerPanelLayout(bool portrait)
    {
        Vector2 panelSize = portrait ? new Vector2(900f, 1260f) : new Vector2(1320f, 780f);
        SetCenteredChildRect(transform, "panel-bg", panelSize);
        SetCenteredChildRect(transform, "Withdraw", panelSize);
        SetCenteredChildRect(transform, "Transactions", panelSize);

        LayoutWithdrawTabs(portrait);
        LayoutWithdrawPanel(portrait);
        LayoutWithdrawTransactionsPanel(portrait);
    }

    private void LayoutWithdrawTabs(bool portrait)
    {
        RectTransform tabs = FindDeepChild(transform, "Buttons") as RectTransform;
        if (tabs == null)
        {
            return;
        }

        SetRect(tabs, portrait ? new Vector2(760f, 104f) : new Vector2(820f, 88f), portrait ? new Vector2(0f, 500f) : new Vector2(0f, 315f));

        int visibleIndex = 0;
        for (int i = 0; i < tabs.childCount; i++)
        {
            RectTransform child = tabs.GetChild(i) as RectTransform;
            if (child == null || !child.gameObject.activeSelf)
            {
                continue;
            }

            float x = visibleIndex == 0 ? -195f : 195f;
            SetRect(child, portrait ? new Vector2(340f, 88f) : new Vector2(360f, 72f), new Vector2(x, 0f));
            visibleIndex++;
        }
    }

    private void LayoutWithdrawPanel(bool portrait)
    {
        RectTransform panel = FindDeepChild(transform, "Withdraw") as RectTransform;
        if (panel == null)
        {
            return;
        }

        RectTransform title = null;
        RectTransform walletSummary = null;

        for (int i = 0; i < panel.childCount; i++)
        {
            RectTransform child = panel.GetChild(i) as RectTransform;
            if (child == null)
            {
                continue;
            }

            if (string.Equals(child.name, "redeem-title", StringComparison.OrdinalIgnoreCase))
            {
                if (child.GetComponent<Image>() != null)
                {
                    walletSummary = child;
                }
                else
                {
                    title = child;
                }
            }
        }

        SetRect(title, portrait ? new Vector2(520f, 90f) : new Vector2(520f, 70f), portrait ? new Vector2(0f, 545f) : new Vector2(0f, 335f));
        SetRect(walletSummary, portrait ? new Vector2(820f, 150f) : new Vector2(1040f, 110f), portrait ? new Vector2(0f, 390f) : new Vector2(0f, 245f));
        LayoutWalletSummary(walletSummary, portrait);

        SetRect(FindDirectChild(panel, "Bank"), portrait ? new Vector2(330f, 90f) : new Vector2(280f, 72f), portrait ? new Vector2(-200f, 250f) : new Vector2(-170f, 145f));
        SetRect(FindDirectChild(panel, "Crypto"), portrait ? new Vector2(330f, 90f) : new Vector2(280f, 72f), portrait ? new Vector2(200f, 250f) : new Vector2(170f, 145f));

        RectTransform label = FindDirectTextChild(panel, "Please Select");
        SetRect(label, portrait ? new Vector2(760f, 56f) : new Vector2(940f, 46f), portrait ? new Vector2(0f, 140f) : new Vector2(0f, 75f));

        SetRect(FindDirectChild(panel, "Scroll View"), portrait ? new Vector2(820f, 300f) : new Vector2(1080f, 260f), portrait ? new Vector2(0f, -60f) : new Vector2(0f, -95f));
        SetRect(FindDirectChild(panel, "homepage-input-field"), portrait ? new Vector2(520f, 96f) : new Vector2(520f, 76f), portrait ? new Vector2(-145f, -405f) : new Vector2(-160f, -285f));
        SetRect(FindDirectChild(panel, "button"), portrait ? new Vector2(260f, 96f) : new Vector2(300f, 76f), portrait ? new Vector2(300f, -405f) : new Vector2(310f, -285f));
    }

    private void LayoutWithdrawTransactionsPanel(bool portrait)
    {
        RectTransform panel = FindDeepChild(transform, "Transactions") as RectTransform;
        if (panel == null)
        {
            return;
        }

        SetRect(FindDirectTextChild(panel, "Withdraw Logs"), portrait ? new Vector2(560f, 90f) : new Vector2(560f, 70f), portrait ? new Vector2(0f, 545f) : new Vector2(0f, 335f));

        RectTransform header = FindDirectChild(panel, "Withdrawal Logs");
        SetRect(header, portrait ? new Vector2(820f, 76f) : new Vector2(1120f, 68f), portrait ? new Vector2(0f, 405f) : new Vector2(0f, 240f));
        LayoutHeaderRow(header);

        SetRect(FindDirectChild(panel, "Scroll View"), portrait ? new Vector2(820f, 620f) : new Vector2(1120f, 420f), portrait ? new Vector2(0f, 45f) : new Vector2(0f, -20f));
        SetRect(FindDirectTextChild(panel, "No Logs"), portrait ? new Vector2(760f, 120f) : new Vector2(900f, 90f), Vector2.zero);
    }

    private static void LayoutWalletSummary(RectTransform walletSummary, bool portrait)
    {
        if (walletSummary == null)
        {
            return;
        }

        float[] xs = portrait
            ? new[] { -300f, -100f, 100f, 300f }
            : new[] { -390f, -130f, 130f, 390f };
        string[] keys = { "TOTAL", "BONUS", "WINNING", "UNUTILIZED" };

        for (int i = 0; i < keys.Length; i++)
        {
            RectTransform label = FindChildContaining(walletSummary, keys[i], false);
            RectTransform value = FindChildContaining(walletSummary, keys[i], true);
            float x = xs[i];
            SetRect(label, portrait ? new Vector2(185f, 52f) : new Vector2(240f, 42f), new Vector2(x, 28f));
            SetRect(value, portrait ? new Vector2(185f, 52f) : new Vector2(240f, 42f), new Vector2(x, -35f));
            LayoutWalletValue(value);
        }
    }

    private static void LayoutWalletValue(RectTransform value)
    {
        if (value == null)
        {
            return;
        }

        for (int i = 0; i < value.childCount; i++)
        {
            RectTransform child = value.GetChild(i) as RectTransform;
            if (child == null)
            {
                continue;
            }

            if (child.GetComponent<Image>() != null)
            {
                SetRect(child, new Vector2(32f, 32f), new Vector2(-62f, 0f));
                continue;
            }

            if (child.GetComponent<Text>() != null || child.GetComponent<TMP_Text>() != null)
            {
                SetRect(child, new Vector2(120f, 42f), new Vector2(20f, 0f));
            }
        }
    }

    private static void LayoutHeaderRow(RectTransform header)
    {
        if (header == null)
        {
            return;
        }

        string[] names = { "Sr.N", "Coin", "Status", "Added Date" };
        float[] xs = { -315f, -95f, 125f, 330f };
        float[] widths = { 140f, 180f, 180f, 230f };

        for (int i = 0; i < names.Length; i++)
        {
            RectTransform child = FindDirectChild(header, names[i]);
            SetRect(child, new Vector2(widths[i], 68f), new Vector2(xs[i], 0f));
        }
    }

    private static void SetRect(RectTransform rect, Vector2 size, Vector2 anchoredPosition)
    {
        if (rect == null)
        {
            return;
        }

        rect.anchorMin = new Vector2(0.5f, 0.5f);
        rect.anchorMax = new Vector2(0.5f, 0.5f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = anchoredPosition;
        rect.localScale = Vector3.one;
        rect.sizeDelta = size;

        LayoutElement layout = rect.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.minWidth = size.x;
            layout.preferredWidth = size.x;
            layout.minHeight = size.y;
            layout.preferredHeight = size.y;
        }
    }

    private static RectTransform FindDirectChild(Transform parent, string childName)
    {
        if (parent == null)
        {
            return null;
        }

        for (int i = 0; i < parent.childCount; i++)
        {
            Transform child = parent.GetChild(i);
            if (string.Equals(child.name, childName, StringComparison.OrdinalIgnoreCase))
            {
                return child as RectTransform;
            }
        }

        return null;
    }

    private static RectTransform FindDirectTextChild(Transform parent, string textContains)
    {
        if (parent == null)
        {
            return null;
        }

        for (int i = 0; i < parent.childCount; i++)
        {
            Transform child = parent.GetChild(i);
            string text = GetTextValue(child);
            if (!string.IsNullOrEmpty(text) && text.IndexOf(textContains, StringComparison.OrdinalIgnoreCase) >= 0)
            {
                return child as RectTransform;
            }
        }

        return null;
    }

    private static RectTransform FindChildContaining(Transform parent, string nameContains, bool allowUnderscore)
    {
        if (parent == null)
        {
            return null;
        }

        for (int i = 0; i < parent.childCount; i++)
        {
            Transform child = parent.GetChild(i);
            string name = child.name ?? string.Empty;
            bool nameMatches = name.IndexOf(nameContains, StringComparison.OrdinalIgnoreCase) >= 0;
            bool isValue = name.EndsWith("_", StringComparison.OrdinalIgnoreCase);
            if (allowUnderscore && !isValue)
            {
                nameMatches = false;
            }

            if (!allowUnderscore && isValue)
            {
                nameMatches = false;
            }

            if (nameMatches)
            {
                return child as RectTransform;
            }
        }

        return null;
    }

    private static string GetTextValue(Transform transform)
    {
        if (transform == null)
        {
            return null;
        }

        Text text = transform.GetComponent<Text>();
        if (text != null)
        {
            return text.text;
        }

        TMP_Text tmp = transform.GetComponent<TMP_Text>();
        return tmp != null ? tmp.text : null;
    }

    private static void SetCenteredChildRect(Transform root, string childName, Vector2 size)
    {
        RectTransform rect = FindDeepChild(root, childName) as RectTransform;
        if (rect == null)
        {
            return;
        }

        rect.anchorMin = new Vector2(0.5f, 0.5f);
        rect.anchorMax = new Vector2(0.5f, 0.5f);
        rect.pivot = new Vector2(0.5f, 0.5f);
        rect.anchoredPosition = Vector2.zero;
        rect.sizeDelta = size;
    }

    private static void SetChildRectByName(Transform root, string childName, Vector2 size)
    {
        RectTransform rect = FindDeepChild(root, childName) as RectTransform;
        if (rect == null)
        {
            return;
        }

        Vector2 anchorCenter = (rect.anchorMin + rect.anchorMax) * 0.5f;
        rect.anchorMin = anchorCenter;
        rect.anchorMax = anchorCenter;
        rect.sizeDelta = size;
        LayoutElement layout = rect.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.minWidth = size.x;
            layout.preferredWidth = size.x;
            layout.minHeight = size.y;
            layout.preferredHeight = size.y;
        }
    }

    private static Transform FindDeepChild(Transform root, string childName)
    {
        if (root == null)
        {
            return null;
        }

        if (string.Equals(root.name, childName, StringComparison.OrdinalIgnoreCase))
        {
            return root;
        }

        for (int i = 0; i < root.childCount; i++)
        {
            Transform found = FindDeepChild(root.GetChild(i), childName);
            if (found != null)
            {
                return found;
            }
        }

        return null;
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
