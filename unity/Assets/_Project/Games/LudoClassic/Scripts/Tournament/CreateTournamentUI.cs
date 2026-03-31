using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using UnityEngine.Networking;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;

/// <summary>
/// Form UI for creating a new tournament (user-created).
/// Section 14 of document.html: "Create Tournament" feature.
///
/// Fields: Name, Type (Public/Private), Entry Fee, Max Players,
/// Prize Distribution (1st-5th must sum 100%), Terms, Registration dates.
/// </summary>
public class CreateTournamentUI : MonoBehaviour
{
    [Header("Form Fields")]
    [SerializeField] private TMP_InputField nameInput;
    [SerializeField] private TMP_InputField descriptionInput;
    [SerializeField] private Toggle         publicToggle;
    [SerializeField] private Toggle         privateToggle;
    [SerializeField] private TMP_Dropdown   formatDropdown;    // knockout, round_robin, etc.
    [SerializeField] private TMP_Dropdown   maxPlayersDropdown; // 4,8,16,32,64
    [SerializeField] private TMP_InputField entryFeeInput;
    [SerializeField] private TMP_InputField termsInput;

    [Header("Prize Distribution (must sum to 100%)")]
    [SerializeField] private TMP_InputField prize1Pct;  // 1st place %
    [SerializeField] private TMP_InputField prize2Pct;  // 2nd place %
    [SerializeField] private TMP_InputField prize3Pct;  // 3rd place %
    [SerializeField] private TMP_InputField prize4Pct;  // 4th place %
    [SerializeField] private TMP_InputField prize5Pct;  // 5th place %
    [SerializeField] private TextMeshProUGUI prizesTotalText;  // "Total: 100%"

    [Header("Prize Preview (live calc)")]
    [SerializeField] private TextMeshProUGUI prize1AmountText;
    [SerializeField] private TextMeshProUGUI prize2AmountText;
    [SerializeField] private TextMeshProUGUI prize3AmountText;
    [SerializeField] private TextMeshProUGUI prize4AmountText;
    [SerializeField] private TextMeshProUGUI prize5AmountText;

    [Header("Private Tournament")]
    [SerializeField] private GameObject     privateOptionsPanel;
    [SerializeField] private TMP_InputField passwordInput;

    [Header("Buttons")]
    [SerializeField] private Button          submitBtn;
    [SerializeField] private TextMeshProUGUI submitBtnText;
    [SerializeField] private Button          cancelBtn;

    [Header("Result")]
    [SerializeField] private GameObject      successPanel;
    [SerializeField] private TextMeshProUGUI inviteCodeDisplay;
    [SerializeField] private TextMeshProUGUI successMessage;

    [Header("Error")]
    [SerializeField] private TextMeshProUGUI errorText;

    private static readonly int[] MaxPlayerOptions = { 4, 8, 16, 32, 64 };
    private static readonly string[] FormatValues  = { "knockout", "round_robin", "double_elim", "group_knockout" };

    private string ApiBaseUrl => $"https://{PlayerPrefs.GetString("api_domain", "yourdomain.com")}/api/v1";
    private string AuthToken  => PlayerPrefs.GetString("auth_token", "");

    private void Start()
    {
        // Defaults
        prize1Pct.text = "50";
        prize2Pct.text = "25";
        prize3Pct.text = "12.5";
        prize4Pct.text = "7.5";
        prize5Pct.text = "5";

        // Listeners
        publicToggle.onValueChanged.AddListener(v  => { if (v) privateOptionsPanel.SetActive(false); });
        privateToggle.onValueChanged.AddListener(v => privateOptionsPanel.SetActive(v));

        foreach (var field in new[] { prize1Pct, prize2Pct, prize3Pct, prize4Pct, prize5Pct })
            field.onValueChanged.AddListener(_ => UpdatePrizePreview());

        entryFeeInput.onValueChanged.AddListener(_ => UpdatePrizePreview());
        maxPlayersDropdown.onValueChanged.AddListener(_ => UpdatePrizePreview());

        submitBtn.onClick.AddListener(OnSubmit);
        cancelBtn.onClick.AddListener(() => gameObject.SetActive(false));

        UpdatePrizePreview();
    }

    // ── Live Prize Preview ────────────────────────────────────────────────────

    private void UpdatePrizePreview()
    {
        float entryFee  = ParseFloat(entryFeeInput.text);
        int   maxPlayers = MaxPlayerOptions[Mathf.Clamp(maxPlayersDropdown.value, 0, MaxPlayerOptions.Length - 1)];

        float totalPool  = entryFee * maxPlayers;
        float prizePool  = totalPool * 0.80f; // 80% after 20% platform fee

        float p1 = ParseFloat(prize1Pct.text);
        float p2 = ParseFloat(prize2Pct.text);
        float p3 = ParseFloat(prize3Pct.text);
        float p4 = ParseFloat(prize4Pct.text);
        float p5 = ParseFloat(prize5Pct.text);
        float total = p1 + p2 + p3 + p4 + p5;

        prizesTotalText.text  = $"Total: {total:F1}% {(Mathf.Abs(total - 100f) < 0.1f ? "✓" : "(must be 100%)")}";
        prizesTotalText.color = Mathf.Abs(total - 100f) < 0.1f ? Color.green : Color.red;

        SetPrizeText(prize1AmountText, prizePool * p1 / 100f);
        SetPrizeText(prize2AmountText, prizePool * p2 / 100f);
        SetPrizeText(prize3AmountText, prizePool * p3 / 100f);
        SetPrizeText(prize4AmountText, prizePool * p4 / 100f);
        SetPrizeText(prize5AmountText, prizePool * p5 / 100f);
    }

    private static void SetPrizeText(TextMeshProUGUI label, float amount)
    {
        if (label != null) label.text = $"≈ ₹{amount:F0}";
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    private void OnSubmit()
    {
        errorText.text = "";

        // Validate
        if (string.IsNullOrWhiteSpace(nameInput.text))
        {
            ShowError("Tournament name is required.");
            return;
        }

        float p1 = ParseFloat(prize1Pct.text), p2 = ParseFloat(prize2Pct.text),
              p3 = ParseFloat(prize3Pct.text), p4 = ParseFloat(prize4Pct.text),
              p5 = ParseFloat(prize5Pct.text);

        if (Mathf.Abs((p1 + p2 + p3 + p4 + p5) - 100f) > 0.1f)
        {
            ShowError("Prize percentages must sum to exactly 100%.");
            return;
        }

        StartCoroutine(SubmitTournament());
    }

    private IEnumerator SubmitTournament()
    {
        submitBtn.interactable = false;
        submitBtnText.text     = "Creating...";

        bool isPrivate = privateToggle.isOn;
        int  maxPlayers = MaxPlayerOptions[Mathf.Clamp(maxPlayersDropdown.value, 0, MaxPlayerOptions.Length - 1)];
        string format   = FormatValues[Mathf.Clamp(formatDropdown.value, 0, FormatValues.Length - 1)];

        var prizes = new List<object>
        {
            new { position = 1, prize_pct = ParseFloat(prize1Pct.text) },
            new { position = 2, prize_pct = ParseFloat(prize2Pct.text) },
            new { position = 3, prize_pct = ParseFloat(prize3Pct.text) },
            new { position = 4, prize_pct = ParseFloat(prize4Pct.text) },
            new { position = 5, prize_pct = ParseFloat(prize5Pct.text) },
        };

        var body = new
        {
            name                  = nameInput.text.Trim(),
            description           = descriptionInput.text.Trim(),
            type                  = isPrivate ? "private" : "public",
            format,
            bracket_mode          = "auto",
            entry_fee             = ParseFloat(entryFeeInput.text),
            max_players           = maxPlayers,
            terms_conditions      = termsInput.text.Trim(),
            invite_password       = isPrivate ? passwordInput.text.Trim() : null,
            registration_start_at = DateTime.UtcNow.AddHours(1).ToString("yyyy-MM-ddTHH:mm:ssZ"),
            registration_end_at   = DateTime.UtcNow.AddHours(25).ToString("yyyy-MM-ddTHH:mm:ssZ"),
            tournament_start_at   = DateTime.UtcNow.AddHours(26).ToString("yyyy-MM-ddTHH:mm:ssZ"),
            prizes,
        };

        string bodyJson = JsonConvert.SerializeObject(body);
        using var req   = new UnityWebRequest($"{ApiBaseUrl}/tournaments", "POST");
        req.uploadHandler   = new UploadHandlerRaw(System.Text.Encoding.UTF8.GetBytes(bodyJson));
        req.downloadHandler = new DownloadHandlerBuffer();
        req.SetRequestHeader("Accept", "application/json");
        req.SetRequestHeader("Content-Type", "application/json");
        req.SetRequestHeader("Authorization", $"Bearer {AuthToken}");

        yield return req.SendWebRequest();

        submitBtn.interactable = true;
        submitBtnText.text     = "CREATE TOURNAMENT";

        var json    = JObject.Parse(req.downloadHandler.text ?? "{}");
        bool success = req.result == UnityWebRequest.Result.Success;

        if (success)
        {
            string msg        = json["message"]?.ToString() ?? "Tournament created!";
            string inviteCode = json["invite_code"]?.ToString();

            successMessage.text = msg;

            if (!string.IsNullOrEmpty(inviteCode))
            {
                inviteCodeDisplay.gameObject.SetActive(true);
                inviteCodeDisplay.text = $"Invite Code: {inviteCode}";
            }

            successPanel.SetActive(true);
        }
        else
        {
            string msg = json["message"]?.ToString() ?? req.error;
            ShowError(msg);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private void ShowError(string message)
    {
        errorText.text = message;
    }

    private static float ParseFloat(string s)
    {
        return float.TryParse(s, out float v) ? v : 0f;
    }
}
