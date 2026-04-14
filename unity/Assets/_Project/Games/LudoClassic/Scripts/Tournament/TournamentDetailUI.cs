using System;
using System.Collections.Generic;
using System.Globalization;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using LudoClassic.Tournament;

/// <summary>
/// Detail popup shown when user taps a tournament card.
/// Shows full prize structure, terms, and Join confirmation.
/// </summary>
public class TournamentDetailUI : MonoBehaviour
{
    public static TournamentDetailUI Instance { get; private set; }

    [Header("Panel")]
    [SerializeField] private GameObject       panel;

    [Header("Info")]
    [SerializeField] private TextMeshProUGUI  titleText;
    [SerializeField] private TextMeshProUGUI  formatText;
    [SerializeField] private TextMeshProUGUI  statusText;
    [SerializeField] private TextMeshProUGUI  entryFeeText;
    [SerializeField] private TextMeshProUGUI  prizePoolText;
    [SerializeField] private TextMeshProUGUI  playersText;
    [SerializeField] private TextMeshProUGUI  startTimeText;
    [SerializeField] private TextMeshProUGUI  termsText;

    [Header("Prize Table")]
    [SerializeField] private Transform        prizeRowParent;
    [SerializeField] private PrizeRowUI       prizeRowPrefab;

    [Header("Buttons")]
    [SerializeField] private Button           joinBtn;
    [SerializeField] private TextMeshProUGUI  joinBtnText;
    [SerializeField] private Button           closeBtn;

    [Header("Wallet Confirm Popup")]
    [SerializeField] private GameObject       confirmPopup;
    [SerializeField] private TextMeshProUGUI  confirmText;
    [SerializeField] private Button           confirmYesBtn;
    [SerializeField] private Button           confirmNoBtn;

    [Header("Result Toast")]
    [SerializeField] private TextMeshProUGUI  toastText;
    [SerializeField] private float            toastDuration = 3f;

    private TournamentData _current;

    private void Awake()
    {
        Instance = this;
        panel.SetActive(false);
        confirmPopup.SetActive(false);
    }

    private void Start()
    {
        closeBtn.onClick.AddListener(Hide);
        confirmNoBtn.onClick.AddListener(() => confirmPopup.SetActive(false));
        confirmYesBtn.onClick.AddListener(ConfirmJoin);
    }

    public static void Show(TournamentData data)
    {
        if (Instance == null) return;
        Instance.Populate(data);
    }

    private void Populate(TournamentData data)
    {
        _current = data;
        panel.SetActive(true);

        titleText.text    = data.Name;
        formatText.text   = data.Format;
        statusText.text   = data.Status.Replace("_", " ").ToUpper();
        entryFeeText.text = data.EntryFee > 0 ? $"Entry: ₹{data.EntryFee:F0}" : "Free Entry";
        prizePoolText.text = $"Prize Pool: ₹{data.TotalPrizePool:F0}";
        playersText.text  = $"{data.CurrentPlayers}/{data.MaxPlayers} Players";
        startTimeText.text = FormatDateTime(data.TournamentStartAt);

        // Prize rows
        foreach (Transform child in prizeRowParent)
            Destroy(child.gameObject);

        if (data.Prizes != null)
        {
            foreach (var prize in data.Prizes)
            {
                var row = Instantiate(prizeRowPrefab, prizeRowParent);
                row.Populate(prize);
            }
        }

        // Join button
        bool canJoin       = data.CanJoin;
        joinBtn.interactable = canJoin;
        joinBtnText.text   = canJoin ? $"JOIN — ₹{data.EntryFee:F0}" : "NOT AVAILABLE";

        joinBtn.onClick.RemoveAllListeners();
        joinBtn.onClick.AddListener(OnJoinTapped);
    }

    private void OnJoinTapped()
    {
        if (_current == null) return;

        // Show wallet deduction confirmation
        float balance = float.Parse(PlayerPrefs.GetString("wallet_balance", "0"));
        confirmText.text = $"Deduct ₹{_current.EntryFee:F0} from wallet?\n\nYour balance: ₹{balance:F0}";
        confirmPopup.SetActive(true);
    }

    private void ConfirmJoin()
    {
        confirmPopup.SetActive(false);
        joinBtn.interactable = false;
        joinBtnText.text     = "Joining...";

        TournamentManager.Instance.RegisterForTournament(_current.Id, (success, message) =>
        {
            joinBtn.interactable = !success;
            if (success)
            {
                joinBtnText.text = "JOINED!";
                ShowToast($"Joined {_current.Name}!");
                // Refresh wallet balance
                // WalletManager.Instance.RefreshBalance();
            }
            else
            {
                joinBtnText.text = "JOIN";
                joinBtn.interactable = true;
                ShowToast(message, isError: true);
            }
        });
    }

    public void Hide()
    {
        panel.SetActive(false);
        _current = null;
    }

    private void ShowToast(string message, bool isError = false)
    {
        if (toastText == null) return;
        toastText.text  = message;
        toastText.color = isError ? Color.red : Color.green;
        toastText.gameObject.SetActive(true);
        CancelInvoke(nameof(HideToast));
        Invoke(nameof(HideToast), toastDuration);
    }

    private void HideToast()
    {
        if (toastText != null) toastText.gameObject.SetActive(false);
    }

    private static string FormatDateTime(string iso)
    {
        if (string.IsNullOrEmpty(iso)) return "--";
        if (DateTimeOffset.TryParse(
                iso,
                CultureInfo.InvariantCulture,
                DateTimeStyles.AssumeUniversal | DateTimeStyles.AdjustToUniversal,
                out DateTimeOffset dto))
        {
            DateTimeOffset local = dto.ToLocalTime();
            string tz = FormatOffset(TimeZoneInfo.Local.GetUtcOffset(local.DateTime));
            return $"{local:dd MMM yyyy, hh:mm tt} ({tz})";
        }

        if (System.DateTime.TryParse(iso, out var dt))
        {
            DateTime local = dt.Kind == DateTimeKind.Utc ? dt.ToLocalTime() : dt;
            string tz = FormatOffset(TimeZoneInfo.Local.GetUtcOffset(local));
            return $"{local:dd MMM yyyy, hh:mm tt} ({tz})";
        }

        return iso;
    }

    private static string FormatOffset(TimeSpan offset)
    {
        string sign = offset < TimeSpan.Zero ? "-" : "+";
        offset = offset.Duration();
        return $"GMT{sign}{offset.Hours:00}:{offset.Minutes:00}";
    }
}

/// <summary>Simple prize row component.</summary>
public class PrizeRowUI : MonoBehaviour
{
    [SerializeField] private TextMeshProUGUI positionText;
    [SerializeField] private TextMeshProUGUI pctText;
    [SerializeField] private TextMeshProUGUI amountText;

    private static readonly string[] Medals = { "🥇", "🥈", "🥉", "4th", "5th" };

    public void Populate(PrizeData prize)
    {
        string medal    = prize.Position <= 5 ? Medals[prize.Position - 1] : $"#{prize.Position}";
        positionText.text = $"{medal} Place";
        pctText.text      = $"{prize.PrizePct:F0}%";
        amountText.text   = $"₹{prize.PrizeAmount:F0}";
    }
}
