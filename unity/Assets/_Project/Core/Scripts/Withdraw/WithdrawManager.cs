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
    private GameObject _transferButton;
    private GameObject _withdrawLogsPopupRoot;
    private RectTransform _withdrawLogsPopupContent;
    private Text _withdrawLogsPopupEmptyText;
    private bool _isWithdrawSubmitting;

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
            total.text = FormatWalletDisplay(Configuration.GetWallet());
        }
        if (bonus != null)
        {
            bonus.text = FormatWalletDisplay(Configuration.GetBonus());
        }
        if (winning_wallet != null)
        {
            winning_wallet.text = FormatWalletDisplay(Configuration.GetWinning());
        }
        if (unutilized_wallet != null)
        {
            unutilized_wallet.text = FormatWalletDisplay(Configuration.GetUnutilized());
        }

        HideUnutilizedWalletUi();


        SetDefault();
        ApplyResponsiveWithdrawLayout();
    }

    void OnDisable()
    {
        if (_transferButton != null)
        {
            _transferButton.SetActive(false);
        }

        if (_withdrawLogsPopupRoot != null)
        {
            _withdrawLogsPopupRoot.SetActive(false);
        }
    }

    public void SetDefault()
    {
        if (withdraw != null)        withdraw.SetActive(true);
        if (withdrawSelect != null)  withdrawSelect.SetActive(true);
        if (Withdrawlogs != null)    Withdrawlogs.SetActive(false);
        if (WithdrawlogSelect != null) WithdrawlogSelect.SetActive(false);

        StyleTabButton(withdrawSelect,    active: true);
        StyleTabButton(WithdrawlogSelect, active: false);
        EnsureTransferButton();
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
        EnsureWithdrawLogsPopup();
        if (_withdrawLogsPopupRoot != null)
        {
            if (_withdrawLogsPopupContent != null)
            {
                foreach (Transform child in _withdrawLogsPopupContent)
                {
                    Destroy(child.gameObject);
                }
            }
            if (_withdrawLogsPopupEmptyText != null)
            {
                _withdrawLogsPopupEmptyText.text = "Loading...";
                _withdrawLogsPopupEmptyText.gameObject.SetActive(true);
            }
            _withdrawLogsPopupRoot.SetActive(true);
            _withdrawLogsPopupRoot.transform.SetAsLastSibling();
        }
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
        CloseWithdrawLogsPopup();
        foreach (var trans in transactionsobj)  trans.SetActive(false);
        foreach (var withdraw in withdrawobjs)  withdraw.SetActive(true);

        StyleTabButton(withdrawSelect,    active: true);
        StyleTabButton(WithdrawlogSelect, active: false);
        EnsureTransferButton();
        ApplyResponsiveWithdrawLayout();
    }

    private void EnsureTransferButton()
    {
        RectTransform panel = FindDeepChild(transform, "Withdraw") as RectTransform;
        RectTransform submit = panel != null ? FindDirectChild(panel, "button") : null;
        if (panel == null || submit == null)
        {
            CommonUtil.CheckLog("PAY_TRACE Withdraw transfer button skipped: panel/button not found");
            return;
        }

        RectTransform existingTransfer = FindTransferButtonCandidate(panel);

        if (existingTransfer != null)
        {
            if (_transferButton != null && _transferButton != existingTransfer.gameObject)
            {
                _transferButton.SetActive(false);
            }

            _transferButton = existingTransfer.gameObject;
            _transferButton.SetActive(true);
            _transferButton.transform.SetAsLastSibling();

            Button existingButton = _transferButton.GetComponent<Button>();
            if (existingButton != null)
            {
                existingButton.onClick = new Button.ButtonClickedEvent();
                existingButton.onClick.AddListener(OpenTransferPopupFromWithdraw);
            }

            Text existingText = _transferButton.GetComponentInChildren<Text>(true);
            if (existingText != null)
            {
                existingText.text = "Transfer";
                existingText.color = Color.white;
            }

            CommonUtil.CheckLog("PAY_TRACE Withdraw transfer button bound to existing scene button: " + GetTransformPath(_transferButton.transform));
            return;
        }

        if (_transferButton == null)
        {
            _transferButton = new GameObject(
                "Transfer-Button",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image),
                typeof(Button)
            );
            _transferButton.transform.SetParent(panel, false);

            Image image = _transferButton.GetComponent<Image>();
            Image submitImage = submit.GetComponent<Image>();
            if (submitImage != null && submitImage.sprite != null)
            {
                image.sprite = submitImage.sprite;
                image.type = submitImage.type == Image.Type.Simple ? Image.Type.Sliced : submitImage.type;
            }
            image.color = new Color32(90, 14, 24, 255);

            Button button = _transferButton.GetComponent<Button>();
            button.onClick.AddListener(OpenTransferPopupFromWithdraw);

            GameObject label = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            label.transform.SetParent(_transferButton.transform, false);
            Text text = label.GetComponent<Text>();
            text.font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            text.text = "Transfer";
            text.fontSize = 24;
            text.fontStyle = FontStyle.Bold;
            text.alignment = TextAnchor.MiddleCenter;
            text.color = Color.white;
            text.raycastTarget = false;

            RectTransform labelRect = label.GetComponent<RectTransform>();
            labelRect.anchorMin = Vector2.zero;
            labelRect.anchorMax = Vector2.one;
            labelRect.offsetMin = Vector2.zero;
            labelRect.offsetMax = Vector2.zero;
        }

        _transferButton.SetActive(true);
        _transferButton.transform.SetAsLastSibling();

        RectTransform transferRect = _transferButton.transform as RectTransform;
        bool portrait = Mathf.Abs(panel.rect.height) >= Mathf.Abs(panel.rect.width);
        transferRect.anchorMin = new Vector2(0.5f, 0.5f);
        transferRect.anchorMax = new Vector2(0.5f, 0.5f);
        transferRect.pivot = new Vector2(0.5f, 0.5f);
        transferRect.sizeDelta = portrait ? new Vector2(220f, 64f) : new Vector2(210f, 58f);
        transferRect.anchoredPosition = portrait
            ? new Vector2(300f, -485f)
            : new Vector2(360f, -325f);

        CommonUtil.CheckLog("PAY_TRACE Withdraw transfer button positioned at " + transferRect.anchoredPosition);
    }

    private void OpenTransferPopupFromWithdraw()
    {
        PaymentManager paymentManager = FindObjectOfType<PaymentManager>(true);
        if (paymentManager == null)
        {
            CommonUtil.ShowToast("Transfer popup is not available right now.");
            return;
        }

        paymentManager.OpenTransferPopup();
    }

    private static RectTransform FindTransferButtonCandidate(Transform panel)
    {
        if (panel == null)
            return null;

        string[] candidates =
        {
            "Transfer",
            "button (1)",
            "Button (1)",
            "Transfer Button"
        };

        foreach (string candidate in candidates)
        {
            RectTransform direct = FindDirectChild(panel, candidate);
            if (direct != null)
                return direct;
        }

        foreach (string candidate in candidates)
        {
            RectTransform deep = FindDeepChild(panel, candidate) as RectTransform;
            if (deep != null)
                return deep;
        }

        return null;
    }

    private static string GetTransformPath(Transform target)
    {
        if (target == null)
            return "<null>";

        string path = target.name;
        while (target.parent != null)
        {
            target = target.parent;
            path = target.name + "/" + path;
        }

        return path;
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
        if (_isWithdrawSubmitting)
        {
            CommonUtil.ShowStyledMessage(
                "Your withdrawal request is already being submitted. Please wait for the response.",
                "Request In Progress",
                isError: true
            );
            return;
        }

        if (custominput != null && !string.IsNullOrWhiteSpace(custominput.text))
        {
            string amount = custominput.text.Trim();
            if (!decimal.TryParse(amount, out decimal parsedAmount) || parsedAmount <= 0)
            {
                CommonUtil.ShowStyledMessage(
                    "Please enter a valid withdrawal amount.",
                    "Invalid Amount",
                    isError: true
                );
                return;
            }

            await WithdrawCustomAPI(amount);
        }
        else
        {
            CommonUtil.ShowStyledMessage(
                "Please enter amount",
                "Missing Amount",
                isError: true
            );
        }
    }

    #endregion

    #region Show Transaction

    public void HandleLogs(WithDrawalLogsOutputs logs)
    {
        RectTransform renderContent = ResolveWithdrawLogsRenderContent();
        GameObject emptyState = ResolveWithdrawLogsEmptyStateObject();

        if (renderContent is RectTransform boundContent)
        {
            CommonUtil.CheckLog(
                "PAY_TRACE Withdraw logs content bound -> "
                    + GetTransformPath(boundContent)
                    + " size="
                    + boundContent.rect.width.ToString("0.##")
                    + "x"
                    + boundContent.rect.height.ToString("0.##")
                    + " sizeDelta="
                    + boundContent.sizeDelta
            );
        }

        if (renderContent != null)
        {
            renderContent.gameObject.SetActive(true);
        }

        if (renderContent != null)
        {
            foreach (Transform child in renderContent)
            {
                Destroy(child.gameObject);
            }
        }
        instantiatedLogs.Clear();

        List<Datum> logItems = logs != null && logs.data != null ? logs.data : new List<Datum>();

        if (logItems.Count > 0 && renderContent != null)
        {
            float contentWidth = ResolveWithdrawLogContentWidth(renderContent, 900f);
            PrepareWithdrawLogContent(renderContent, contentWidth, 92f, 10f);
            float rowSpacing = 10f;
            float rowHeight = 92f;

            if (emptyState != null)
            {
                emptyState.SetActive(false);
            }
            SetWithdrawLogsEmptyState(false);
            HideWithdrawLogsEmptyArtifacts();
            // Check for new logs
            for (int j = logItems.Count - 1; j >= 0; j--)
            {
                var log = logItems[j];
                if (log == null)
                {
                    continue;
                }
                GameObject go = CreateWithdrawLogRow(
                    renderContent,
                    (j + 1).ToString(),
                    log,
                    contentWidth,
                    rowHeight
                );
                PositionWithdrawLogRow(go.transform as RectTransform, contentWidth, rowHeight, rowSpacing, logItems.Count - 1 - j);
                go.transform.SetSiblingIndex(0); // Add to the top of the parent
                go.SetActive(true);
                Debug.Log("RES_Check + id contains 2");
                Debug.Log("RES_Check + id " + log.id);

                if (!string.IsNullOrWhiteSpace(log.id) && !instantiatedLogs.ContainsKey(log.id))
                {
                    instantiatedLogs.Add(log.id, go);
                }
                if (emptyState != null)
                {
                    emptyState.SetActive(false);
                }
                SetWithdrawLogsEmptyState(false);
            }

            RectTransform contentRect = renderContent;
            if (contentRect != null)
            {
                float totalHeight = (logItems.Count * rowHeight) + (Mathf.Max(0, logItems.Count - 1) * rowSpacing) + 24f;
                EnsureWithdrawLogContentHeight(contentRect, totalHeight);
                EnsureContentWidth(contentRect, contentWidth);
                LayoutRebuilder.ForceRebuildLayoutImmediate(contentRect);
            }
            if (renderContent.parent is RectTransform viewportRect)
            {
                LayoutRebuilder.ForceRebuildLayoutImmediate(viewportRect);
            }
            Canvas.ForceUpdateCanvases();
            CommonUtil.CheckLog(
                "PAY_TRACE Withdraw logs rows rendered -> childCount="
                    + renderContent.childCount
            );
        }
        else
        {
            if (emptyState != null)
            {
                emptyState.SetActive(true);
                Text emptyText = emptyState.GetComponent<Text>();
                if (emptyText != null)
                {
                    emptyText.text = "No Logs found, Please Apply redeem";
                }
            }
            SetWithdrawLogsEmptyState(true);
        }
    }

    #endregion

    #region  API

    public async Task ShowWithdrawTransactionsAPI()
    {
        if (!IsRuntimeWithdrawLogsPopupActive())
        {
            EnsureVisibleTransactionBindings();
        }

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
        if (_isWithdrawSubmitting)
        {
            CommonUtil.ShowStyledMessage(
                "Your withdrawal request is already being submitted. Please wait for the response.",
                "Request In Progress",
                isError: true
            );
            return;
        }

        _isWithdrawSubmitting = true;
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
        try
        {
            output = await APIManager.Instance.Post<messageprint>(Url, formData);
            CommonUtil.CheckLog(
                "PAY_TRACE WithdrawAPI response code="
                    + output.code
                    + " message="
                    + (output.message ?? string.Empty)
            );

            string message = string.IsNullOrWhiteSpace(output.message)
                ? "Withdrawal request submitted."
                : output.message;

            if (output.code == 200)
            {
                CommonUtil.ShowStyledMessage(
                    message,
                    "Withdrawal Requested",
                    isError: false
                );
                PopUpUtil.ButtonCancel(this.gameObject);
                StartCoroutine(Profile.UpdateWallet());

                DOVirtual.DelayedCall(0.2f, () =>
                {
                    total.text = FormatWalletDisplay(Configuration.GetWallet());
                    bonus.text = FormatWalletDisplay(Configuration.GetBonus());
                    winning_wallet.text = FormatWalletDisplay(Configuration.GetWinning());
                    unutilized_wallet.text = FormatWalletDisplay(Configuration.GetUnutilized());
                });

                // Refresh logs so new entry appears immediately
                DOVirtual.DelayedCall(0.5f, async () => await ShowWithdrawTransactionsAPI());
            }
            else
            {
                CommonUtil.ShowStyledMessage(
                    message,
                    "Withdrawal Failed",
                    isError: true
                );
            }
        }
        finally
        {
            _isWithdrawSubmitting = false;
        }
    }

    public async Task WithdrawCustomAPI(string amount)
    {
        _isWithdrawSubmitting = true;
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
        try
        {
            messageprint output = await APIManager.Instance.Post<messageprint>(Url, formData);
            CommonUtil.CheckLog(
                "PAY_TRACE WithdrawCustomAPI response code="
                    + output.code
                    + " message="
                    + (output.message ?? string.Empty)
            );

            if (output == null)
            {
                CommonUtil.ShowStyledMessage(
                    "Withdrawal request failed. Please try again.",
                    "Withdrawal Failed",
                    isError: true
                );
                return;
            }

            string msg = string.IsNullOrWhiteSpace(output.message) ? "Request submitted." : output.message;

            if (output.code == 200)
            {
                CommonUtil.ShowStyledMessage(
                    msg,
                    "Withdrawal Requested",
                    isError: false
                );
                PopUpUtil.ButtonCancel(this.gameObject);
                StartCoroutine(Profile.UpdateWallet());
                DOVirtual.DelayedCall(0.5f, async () => await ShowWithdrawTransactionsAPI());
            }
            else
            {
                CommonUtil.ShowStyledMessage(
                    msg,
                    "Withdrawal Failed",
                    isError: true
                );
            }
        }
        finally
        {
            _isWithdrawSubmitting = false;
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
        // Withdrawal visuals are authored in the HomePage scene. Do not overwrite
        // sprites/colors here, otherwise Play Mode diverges from the hierarchy.
    }

    private static string FormatWalletDisplay(string raw)
    {
        if (string.IsNullOrWhiteSpace(raw))
        {
            return "0.00";
        }

        if (decimal.TryParse(raw, System.Globalization.NumberStyles.Any, System.Globalization.CultureInfo.InvariantCulture, out decimal parsed))
        {
            return parsed.ToString("0.00", System.Globalization.CultureInfo.InvariantCulture);
        }

        return raw;
    }

    private static void SafeShowToast(string message)
    {
        if (string.IsNullOrWhiteSpace(message))
            return;

        bool isError = IsErrorStyleMessage(message);
        CommonUtil.ShowStyledMessage(
            message,
            isError ? "Error" : "Success",
            isError
        );
    }

    private static bool IsErrorStyleMessage(string message)
    {
        string lowered = message.Trim().ToLowerInvariant();
        return lowered.Contains("please ")
            || lowered.Contains("invalid")
            || lowered.Contains("failed")
            || lowered.Contains("error")
            || lowered.Contains("unable")
            || lowered.Contains("required");
    }

    private void HideUnutilizedWalletUi()
    {
        HideNamedNode("UNUTILIZED WALLET");
        HideNamedNode("UNUTILIZED WALLET_");
    }

    private void HideNamedNode(string nodeName)
    {
        Transform target = FindDeepChild(transform, nodeName);
        if (target != null)
        {
            target.gameObject.SetActive(false);
        }
    }

    private void SetWithdrawLogsEmptyState(bool show)
    {
        string[] candidates =
        {
            "No Logs",
            "No Logs Found, Please Apply Redeem",
            "No Logs found!",
            "Apply Redeem"
        };

        foreach (string candidate in candidates)
        {
            Transform target = FindDeepChild(transform, candidate);
            if (target != null)
            {
                target.gameObject.SetActive(show);
            }
        }
    }

    private void EnsureVisibleTransactionBindings()
    {
        if (IsRuntimeWithdrawLogsPopupActive())
        {
            return;
        }

        RectTransform transactionsPanel = FindDeepChild(transform, "Transactions") as RectTransform;
        if (transactionsPanel == null)
        {
            transactionsPanel = Withdrawlogs != null ? Withdrawlogs.transform as RectTransform : null;
        }

        if (transactionsPanel == null)
        {
            return;
        }

        // The authored Withdraw Logs popup has its own visible ScrollRect on the
        // "Transactions" branch contains multiple Scroll Views with identical names.
        // We must prefer the direct body scroll, not a nested legacy child.
        ScrollRect scrollRect = ResolveVisibleWithdrawLogsScrollRect(transactionsPanel);
        if (scrollRect != null && scrollRect.content != null)
        {
            NormalizeWithdrawLogsViewport(scrollRect);
            if (transaction_parent != scrollRect.content)
            {
                transaction_parent = scrollRect.content;
            }
            CommonUtil.CheckLog("PAY_TRACE Rebound withdraw transaction_parent -> " + GetTransformPath(scrollRect.content));
        }

        RectTransform noLogsText = FindDirectTextChild(transactionsPanel, "No Logs");
        if (noLogsText != null && no_data != noLogsText.gameObject)
        {
            no_data = noLogsText.gameObject;
            CommonUtil.CheckLog("PAY_TRACE Rebound withdraw no_data -> " + GetTransformPath(noLogsText));
        }
    }

    private bool IsRuntimeWithdrawLogsPopupActive()
    {
        return _withdrawLogsPopupRoot != null
            && _withdrawLogsPopupRoot.activeInHierarchy
            && _withdrawLogsPopupContent != null;
    }

    private RectTransform ResolveWithdrawLogsRenderContent()
    {
        if (IsRuntimeWithdrawLogsPopupActive())
        {
            return _withdrawLogsPopupContent;
        }

        EnsureVisibleTransactionBindings();
        return transaction_parent as RectTransform;
    }

    private GameObject ResolveWithdrawLogsEmptyStateObject()
    {
        if (IsRuntimeWithdrawLogsPopupActive())
        {
            return _withdrawLogsPopupEmptyText != null ? _withdrawLogsPopupEmptyText.gameObject : null;
        }

        return no_data;
    }

    private void EnsureWithdrawLogsPopup()
    {
        if (_withdrawLogsPopupRoot != null)
        {
            return;
        }

        Canvas canvas = GetComponentInParent<Canvas>(true);
        if (canvas == null)
        {
            Canvas[] canvases = Resources.FindObjectsOfTypeAll<Canvas>();
            if (canvases != null && canvases.Length > 0)
            {
                canvas = canvases[0];
            }
        }

        if (canvas == null)
        {
            CommonUtil.LogError("Withdraw logs popup canvas not found");
            return;
        }

        Font font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
        _withdrawLogsPopupRoot = new GameObject(
            "Withdraw-Logs-Popup",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        _withdrawLogsPopupRoot.transform.SetParent(canvas.rootCanvas.transform, false);

        RectTransform rootRect = _withdrawLogsPopupRoot.GetComponent<RectTransform>();
        rootRect.anchorMin = Vector2.zero;
        rootRect.anchorMax = Vector2.one;
        rootRect.offsetMin = Vector2.zero;
        rootRect.offsetMax = Vector2.zero;
        _withdrawLogsPopupRoot.GetComponent<Image>().color = new Color(0f, 0f, 0f, 0.78f);

        GameObject panel = new GameObject(
            "Panel",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image)
        );
        panel.transform.SetParent(_withdrawLogsPopupRoot.transform, false);
        RectTransform panelRect = panel.GetComponent<RectTransform>();
        panelRect.anchorMin = new Vector2(0.5f, 0.5f);
        panelRect.anchorMax = new Vector2(0.5f, 0.5f);
        panelRect.pivot = new Vector2(0.5f, 0.5f);
        panelRect.sizeDelta = new Vector2(1120f, 760f);
        Image panelImage = panel.GetComponent<Image>();
        if (popupBgSprite != null)
        {
            panelImage.sprite = popupBgSprite;
            panelImage.type = Image.Type.Sliced;
            panelImage.color = Color.white;
        }
        else
        {
            panelImage.color = new Color32(88, 16, 22, 255);
        }

        GameObject titleObj = CreateWithdrawLogsText(panel.transform, font, "Withdraw Logs", 34, Color.white, TextAnchor.MiddleCenter, FontStyle.Bold);
        RectTransform titleRect = titleObj.GetComponent<RectTransform>();
        titleRect.anchorMin = new Vector2(0.5f, 1f);
        titleRect.anchorMax = new Vector2(0.5f, 1f);
        titleRect.pivot = new Vector2(0.5f, 1f);
        titleRect.sizeDelta = new Vector2(460f, 70f);
        titleRect.anchoredPosition = new Vector2(0f, -24f);

        Button closeButton = CreateWithdrawLogsButton(panel.transform, font, "X", new Vector2(100f, 100f), new Vector2(-14f, -14f));
        closeButton.onClick.AddListener(CloseWithdrawLogsPopup);
        closeButton.transform.SetAsLastSibling();

        GameObject header = new GameObject(
            "Header",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(HorizontalLayoutGroup)
        );
        header.transform.SetParent(panel.transform, false);
        RectTransform headerRect = header.GetComponent<RectTransform>();
        headerRect.anchorMin = new Vector2(0.5f, 1f);
        headerRect.anchorMax = new Vector2(0.5f, 1f);
        headerRect.pivot = new Vector2(0.5f, 1f);
        headerRect.sizeDelta = new Vector2(980f, 64f);
        headerRect.anchoredPosition = new Vector2(0f, -110f);
        header.GetComponent<Image>().color = new Color32(92, 38, 30, 255);
        HorizontalLayoutGroup headerLayout = header.GetComponent<HorizontalLayoutGroup>();
        headerLayout.padding = new RectOffset(16, 16, 6, 6);
        headerLayout.spacing = 8f;
        headerLayout.childControlWidth = true;
        headerLayout.childControlHeight = true;
        headerLayout.childForceExpandWidth = false;
        headerLayout.childForceExpandHeight = false;
        CreateWithdrawLogsHeaderCell(header.transform, font, "Sr.No", 0.14f);
        CreateWithdrawLogsHeaderCell(header.transform, font, "Coin", 0.23f);
        CreateWithdrawLogsHeaderCell(header.transform, font, "Status", 0.23f);
        CreateWithdrawLogsHeaderCell(header.transform, font, "Created Date", 0.40f);

        GameObject scrollGo = new GameObject(
            "Scroll View",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(ScrollRect)
        );
        scrollGo.transform.SetParent(panel.transform, false);
        RectTransform scrollRect = scrollGo.GetComponent<RectTransform>();
        scrollRect.anchorMin = new Vector2(0.5f, 0.5f);
        scrollRect.anchorMax = new Vector2(0.5f, 0.5f);
        scrollRect.pivot = new Vector2(0.5f, 0.5f);
        scrollRect.sizeDelta = new Vector2(980f, 520f);
        scrollRect.anchoredPosition = new Vector2(0f, -80f);
        scrollGo.GetComponent<Image>().color = new Color32(70, 14, 18, 235);

        GameObject viewport = new GameObject(
            "Viewport",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Mask)
        );
        viewport.transform.SetParent(scrollGo.transform, false);
        RectTransform viewportRect = viewport.GetComponent<RectTransform>();
        viewportRect.anchorMin = Vector2.zero;
        viewportRect.anchorMax = Vector2.one;
        viewportRect.offsetMin = new Vector2(12f, 12f);
        viewportRect.offsetMax = new Vector2(-12f, -12f);
        viewport.GetComponent<Image>().color = new Color32(84, 12, 18, 1);
        viewport.GetComponent<Mask>().showMaskGraphic = false;

        GameObject content = new GameObject("Content", typeof(RectTransform));
        content.transform.SetParent(viewport.transform, false);
        _withdrawLogsPopupContent = content.GetComponent<RectTransform>();
        _withdrawLogsPopupContent.anchorMin = new Vector2(0f, 1f);
        _withdrawLogsPopupContent.anchorMax = new Vector2(1f, 1f);
        _withdrawLogsPopupContent.pivot = new Vector2(0.5f, 1f);
        _withdrawLogsPopupContent.anchoredPosition = Vector2.zero;
        _withdrawLogsPopupContent.sizeDelta = new Vector2(0f, 0f);

        GameObject emptyObj = CreateWithdrawLogsText(viewport.transform, font, "Loading...", 28, new Color32(255, 225, 190, 255), TextAnchor.MiddleCenter, FontStyle.Normal);
        RectTransform emptyRect = emptyObj.GetComponent<RectTransform>();
        emptyRect.anchorMin = Vector2.zero;
        emptyRect.anchorMax = Vector2.one;
        emptyRect.offsetMin = new Vector2(24f, 24f);
        emptyRect.offsetMax = new Vector2(-24f, -24f);
        _withdrawLogsPopupEmptyText = emptyObj.GetComponent<Text>();

        ScrollRect scroll = scrollGo.GetComponent<ScrollRect>();
        scroll.viewport = viewportRect;
        scroll.content = _withdrawLogsPopupContent;
        scroll.horizontal = false;
        scroll.vertical = true;
        scroll.movementType = ScrollRect.MovementType.Clamped;
        scroll.scrollSensitivity = 35f;

        _withdrawLogsPopupRoot.SetActive(false);
    }

    private void CloseWithdrawLogsPopup()
    {
        if (_withdrawLogsPopupRoot != null)
        {
            _withdrawLogsPopupRoot.SetActive(false);
        }
    }

    private static GameObject CreateWithdrawLogsText(Transform parent, Font font, string value, int fontSize, Color color, TextAnchor anchor, FontStyle style)
    {
        GameObject go = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
        go.layer = 5;
        go.transform.SetParent(parent, false);
        Text text = go.GetComponent<Text>();
        text.font = font;
        text.text = value;
        text.fontSize = fontSize;
        text.color = color;
        text.alignment = anchor;
        text.fontStyle = style;
        text.horizontalOverflow = HorizontalWrapMode.Wrap;
        text.verticalOverflow = VerticalWrapMode.Overflow;
        return go;
    }

    private static Button CreateWithdrawLogsButton(Transform parent, Font font, string label, Vector2 size, Vector2 anchoredPosition)
    {
        GameObject buttonGo = new GameObject(
            "Button",
            typeof(RectTransform),
            typeof(CanvasRenderer),
            typeof(Image),
            typeof(Button)
        );
        buttonGo.layer = 5;
        buttonGo.transform.SetParent(parent, false);
        RectTransform rect = buttonGo.GetComponent<RectTransform>();
        rect.anchorMin = new Vector2(1f, 1f);
        rect.anchorMax = new Vector2(1f, 1f);
        rect.pivot = new Vector2(1f, 1f);
        rect.sizeDelta = size;
        rect.anchoredPosition = anchoredPosition;
        Image image = buttonGo.GetComponent<Image>();
        image.color = new Color32(96, 18, 28, 255);
        image.type = Image.Type.Sliced;
        image.raycastTarget = true;

        Outline outline = buttonGo.AddComponent<Outline>();
        outline.effectColor = new Color32(255, 190, 90, 150);
        outline.effectDistance = new Vector2(2f, -2f);

        GameObject textObj = CreateWithdrawLogsText(buttonGo.transform, font, label, 34, Color.white, TextAnchor.MiddleCenter, FontStyle.Bold);
        RectTransform textRect = textObj.GetComponent<RectTransform>();
        textRect.anchorMin = Vector2.zero;
        textRect.anchorMax = Vector2.one;
        textRect.offsetMin = Vector2.zero;
        textRect.offsetMax = Vector2.zero;
        textObj.GetComponent<Text>().raycastTarget = false;

        return buttonGo.GetComponent<Button>();
    }

    private static void CreateWithdrawLogsHeaderCell(Transform parent, Font font, string label, float flexibleWidth)
    {
        GameObject cell = CreateWithdrawLogsText(parent, font, label, 24, Color.white, TextAnchor.MiddleCenter, FontStyle.Bold);
        cell.name = label;
        LayoutElement layout = cell.AddComponent<LayoutElement>();
        layout.flexibleWidth = flexibleWidth;
        layout.minWidth = 100f;
        layout.preferredHeight = 50f;
    }

    private static ScrollRect ResolveVisibleWithdrawLogsScrollRect(RectTransform transactionsPanel)
    {
        if (transactionsPanel == null)
        {
            return null;
        }

        ScrollRect bestDirectScroll = null;
        float bestScore = float.MinValue;

        for (int i = 0; i < transactionsPanel.childCount; i++)
        {
            RectTransform child = transactionsPanel.GetChild(i) as RectTransform;
            if (child == null || !child.gameObject.activeInHierarchy)
            {
                continue;
            }

            ScrollRect directScroll = child.GetComponent<ScrollRect>();
            if (directScroll == null || directScroll.content == null)
            {
                continue;
            }

            RectTransform content = directScroll.content;
            float viewportArea = Mathf.Max(0f, child.rect.width) * Mathf.Max(0f, child.rect.height);
            float contentWidthHint = Mathf.Max(content.rect.width, content.sizeDelta.x);
            float score = viewportArea + (contentWidthHint * 10f);

            if (score > bestScore)
            {
                bestScore = score;
                bestDirectScroll = directScroll;
            }
        }

        if (bestDirectScroll != null)
        {
            return bestDirectScroll;
        }

        return transactionsPanel.GetComponentInChildren<ScrollRect>(true);
    }

    private static void NormalizeWithdrawLogsViewport(ScrollRect scrollRect)
    {
        if (scrollRect == null)
        {
            return;
        }

        RectTransform scrollRectTransform = scrollRect.transform as RectTransform;
        RectTransform viewport = scrollRect.viewport;
        if (scrollRectTransform == null || viewport == null)
        {
            return;
        }

        // Some authored Withdraw Logs branches have a zero-sized viewport in scene data.
        // Stretch it to the visible scroll area so runtime rows are not clipped away.
        viewport.anchorMin = Vector2.zero;
        viewport.anchorMax = Vector2.one;
        viewport.pivot = new Vector2(0.5f, 0.5f);
        viewport.anchoredPosition = Vector2.zero;
        viewport.sizeDelta = new Vector2(-18f, -18f);
        viewport.localScale = Vector3.one;
        viewport.localRotation = Quaternion.identity;

        if (scrollRect.content != null)
        {
            scrollRect.content.localScale = Vector3.one;
            scrollRect.content.localRotation = Quaternion.identity;
        }

        LayoutRebuilder.ForceRebuildLayoutImmediate(viewport);
    }

    private void HideWithdrawLogsEmptyArtifacts()
    {
        string[] candidates =
        {
            "No Logs",
            "No Logs Found, Please Apply Redeem",
            "No Logs found!",
            "Apply Redeem"
        };

        foreach (string candidate in candidates)
        {
            Transform target = FindDeepChild(transform, candidate);
            if (target == null)
            {
                continue;
            }

            target.gameObject.SetActive(false);

            Transform parent = target.parent;
            if (parent != null && parent.childCount <= 3)
            {
                parent.gameObject.SetActive(false);
            }
        }
    }

    private void ApplyResponsiveWithdrawLayout()
    {
        // Layout is controlled by the saved scene hierarchy. Runtime resizing
        // caused the Withdrawal popup/logs to look different in Play Mode.
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

    private static void PrepareWithdrawLogContent(RectTransform content, float rowWidth, float rowHeight, float spacing)
    {
        if (content == null)
        {
            return;
        }

        DisableWithdrawLogAutoLayout(content);
        content.anchorMin = new Vector2(0f, 1f);
        content.anchorMax = new Vector2(1f, 1f);
        content.pivot = new Vector2(0.5f, 1f);
        content.anchoredPosition = Vector2.zero;
        EnsureContentWidth(content, rowWidth);
        EnsureWithdrawLogContentHeight(content, rowHeight + spacing + 24f);
    }

    private static float ResolveWithdrawLogContentWidth(RectTransform content, float fallback)
    {
        if (content == null)
        {
            return fallback;
        }

        RectTransform viewport = content.parent as RectTransform;
        float width = viewport != null ? viewport.rect.width : 0f;
        if (width <= 0f)
        {
            width = content.rect.width;
        }

        if (width <= 0f)
        {
            width = fallback;
        }

        return Mathf.Max(width - 8f, 640f);
    }

    private static GameObject CreateWithdrawLogRow(
        RectTransform parent,
        string serialNumber,
        Datum log,
        float width,
        float height)
    {
        GameObject row = new GameObject("WithdrawalLogRow", typeof(RectTransform), typeof(Image), typeof(LayoutElement), typeof(HorizontalLayoutGroup));
        row.layer = 5;
        row.transform.SetParent(parent, false);

        RectTransform rowRect = row.transform as RectTransform;
        if (rowRect != null)
        {
            rowRect.anchorMin = new Vector2(0f, 1f);
            rowRect.anchorMax = new Vector2(0f, 1f);
            rowRect.pivot = new Vector2(0f, 1f);
            rowRect.anchoredPosition = Vector2.zero;
            rowRect.sizeDelta = new Vector2(width, height);
            rowRect.localScale = Vector3.one;
            rowRect.localRotation = Quaternion.identity;
        }

        Image background = row.GetComponent<Image>();
        if (background != null)
        {
            background.color = new Color32(127, 29, 24, 235);
        }

        LayoutElement rowLayout = row.GetComponent<LayoutElement>();
        if (rowLayout != null)
        {
            rowLayout.minWidth = width;
            rowLayout.preferredWidth = width;
            rowLayout.minHeight = height;
            rowLayout.preferredHeight = height;
        }

        HorizontalLayoutGroup horizontal = row.GetComponent<HorizontalLayoutGroup>();
        if (horizontal != null)
        {
            horizontal.childAlignment = TextAnchor.MiddleLeft;
            horizontal.childControlWidth = true;
            horizontal.childControlHeight = true;
            horizontal.childForceExpandWidth = false;
            horizontal.childForceExpandHeight = false;
            horizontal.spacing = 8f;
            horizontal.padding = new RectOffset(16, 16, 8, 8);
        }

        bool isTransferRow = string.Equals(log.redeem_id, "wallet_transfer", StringComparison.OrdinalIgnoreCase);
        string coinText = isTransferRow
            ? ((string.Equals(log.status, "received", StringComparison.OrdinalIgnoreCase) ? "+ " : "- ") + log.coin)
            : log.coin;

        string statusText = isTransferRow
            ? (string.Equals(log.status, "received", StringComparison.OrdinalIgnoreCase) ? "Transfer In" : "Transfer Out")
            : log.status == "0" ? "Pending"
            : log.status == "1" ? "Approve"
            : "Reject";

        CreateWithdrawLogCell(row.transform, serialNumber, 0.14f, TextAnchor.MiddleCenter);
        CreateWithdrawLogCell(row.transform, coinText, 0.23f, TextAnchor.MiddleCenter);
        CreateWithdrawLogCell(row.transform, statusText, 0.23f, TextAnchor.MiddleCenter);
        CreateWithdrawLogCell(row.transform, FormatLogDateTime(log.created_date), 0.40f, TextAnchor.MiddleCenter);

        return row;
    }

    private static void PositionWithdrawLogRow(RectTransform rowRect, float width, float height, float spacing, int index)
    {
        if (rowRect == null)
        {
            return;
        }

        float y = index * (height + spacing);
        rowRect.anchorMin = new Vector2(0f, 1f);
        rowRect.anchorMax = new Vector2(1f, 1f);
        rowRect.pivot = new Vector2(0.5f, 1f);
        rowRect.anchoredPosition = new Vector2(0f, -y);
        rowRect.sizeDelta = new Vector2(-8f, height);
        rowRect.localScale = Vector3.one;
        rowRect.localRotation = Quaternion.identity;
    }

    private static void CreateWithdrawLogCell(Transform parent, string value, float widthRatio, TextAnchor alignment)
    {
        GameObject cell = new GameObject("Cell", typeof(RectTransform), typeof(LayoutElement), typeof(Text));
        cell.layer = 5;
        cell.transform.SetParent(parent, false);

        LayoutElement layout = cell.GetComponent<LayoutElement>();
        if (layout != null)
        {
            layout.flexibleWidth = Mathf.Max(0.1f, widthRatio);
            layout.minWidth = 60f;
            layout.minHeight = 56f;
            layout.preferredHeight = 56f;
        }

        Text text = cell.GetComponent<Text>();
        if (text != null)
        {
            text.text = value ?? "-";
            text.color = Color.white;
            text.alignment = alignment;
            text.font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            if (text.font == null)
            {
                text.font = Resources.GetBuiltinResource<Font>("Arial.ttf");
            }
            text.fontSize = 24;
            text.horizontalOverflow = HorizontalWrapMode.Wrap;
            text.verticalOverflow = VerticalWrapMode.Overflow;
            text.supportRichText = false;
        }
    }

    private static string FormatLogDateTime(string raw)
    {
        if (string.IsNullOrWhiteSpace(raw))
        {
            return "-";
        }

        if (DateTime.TryParse(raw, out DateTime parsed))
        {
            return parsed.ToString("dd MMM yyyy\nhh:mm tt");
        }

        return raw;
    }

    private static void NormalizeWithdrawLogRowVisuals(GameObject row)
    {
        if (row == null)
        {
            return;
        }

        RectTransform rect = row.transform as RectTransform;
        if (rect != null)
        {
            rect.anchorMin = new Vector2(0f, 1f);
            rect.anchorMax = new Vector2(0f, 1f);
            rect.pivot = new Vector2(0f, 1f);
            rect.anchoredPosition = Vector2.zero;
            rect.localScale = Vector3.one;
            rect.localRotation = Quaternion.identity;
        }

        Image rowImage = row.GetComponent<Image>();
        if (rowImage != null)
        {
            rowImage.color = new Color32(135, 28, 22, 255);
        }

        foreach (Text text in row.GetComponentsInChildren<Text>(true))
        {
            if (text == null)
            {
                continue;
            }

            text.color = Color.white;
            text.resizeTextForBestFit = false;
            text.fontSize = Mathf.Max(text.fontSize, 24);
            text.horizontalOverflow = HorizontalWrapMode.Overflow;
            text.verticalOverflow = VerticalWrapMode.Overflow;
        }
    }

    private static void EnsureContentWidth(RectTransform content, float width)
    {
        if (content == null)
        {
            return;
        }

        content.anchorMin = new Vector2(0f, 1f);
        content.anchorMax = new Vector2(1f, 1f);
        content.pivot = new Vector2(0.5f, 1f);
        content.sizeDelta = new Vector2(0f, content.sizeDelta.y);
    }

    private static void EnsureWithdrawLogContentHeight(RectTransform content, float height)
    {
        if (content == null)
        {
            return;
        }

        content.sizeDelta = new Vector2(content.sizeDelta.x, Mathf.Max(0f, height));
    }

    private static void DisableWithdrawLogAutoLayout(RectTransform content)
    {
        if (content == null)
        {
            return;
        }

        foreach (LayoutGroup layoutGroup in content.GetComponents<LayoutGroup>())
        {
            if (layoutGroup != null)
            {
                layoutGroup.enabled = false;
            }
        }

        foreach (ContentSizeFitter fitter in content.GetComponents<ContentSizeFitter>())
        {
            if (fitter != null)
            {
                fitter.enabled = false;
            }
        }
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
