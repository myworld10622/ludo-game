using System;
using System.Globalization;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using LudoClassic.Tournament;

/// <summary>
/// Individual tournament card in the lobby list.
/// Shows: Name, Status badge, Entry Fee, Prize Pool, Players count, Join button.
/// </summary>
public class TournamentCardUI : MonoBehaviour
{
    [Header("Card Fields")]
    [SerializeField] private TextMeshProUGUI nameText;
    [SerializeField] private TextMeshProUGUI statusBadge;
    [SerializeField] private TextMeshProUGUI entryFeeText;
    [SerializeField] private TextMeshProUGUI prizePoolText;
    [SerializeField] private TextMeshProUGUI playersText;
    [SerializeField] private TextMeshProUGUI formatText;
    [SerializeField] private TextMeshProUGUI startTimeText;

    [Header("Prize Slots (1st - 5th)")]
    [SerializeField] private TextMeshProUGUI[] prizePlaceTexts; // 5 elements

    [Header("Join")]
    [SerializeField] private Button          joinButton;
    [SerializeField] private TextMeshProUGUI joinButtonText;
    [SerializeField] private Image           statusIcon;

    [Header("Status Colors")]
    [SerializeField] private Color colorOpen     = new Color(0.18f, 0.80f, 0.44f);
    [SerializeField] private Color colorFull     = new Color(0.91f, 0.30f, 0.24f);
    [SerializeField] private Color colorProgress = new Color(0.20f, 0.60f, 0.86f);
    [SerializeField] private Color colorClosed   = new Color(0.50f, 0.50f, 0.50f);

    private TournamentData _data;

    public void Populate(TournamentData data)
    {
        _data = data;

        nameText.text   = data.Name;
        formatText.text = FormatLabel(data.Format);

        // Entry Fee & Prize Pool
        entryFeeText.text  = data.EntryFee > 0 ? $"₹{data.EntryFee:F0}" : "FREE";
        prizePoolText.text = $"₹{data.TotalPrizePool:F0}";

        // Players
        playersText.text = $"{data.CurrentPlayers}/{data.MaxPlayers} Players";

        // Start time
        startTimeText.text = FormatDateTime(data.TournamentStartAt);

        // Status badge
        UpdateStatusBadge(data);

        // Prize slots (1st–5th)
        if (data.Prizes != null)
        {
            for (int i = 0; i < prizePlaceTexts.Length; i++)
            {
                if (prizePlaceTexts[i] == null) continue;

                var prize = data.Prizes.Find(p => p.Position == i + 1);
                prizePlaceTexts[i].text = prize != null
                    ? $"#{i + 1}: ₹{prize.PrizeAmount:F0}"
                    : $"#{i + 1}: -";
            }
        }

        // Join button
        bool canJoin = data.CanJoin;
        joinButton.interactable = canJoin;
        joinButtonText.text     = canJoin ? "JOIN" : (data.IsFull ? "FULL" : data.Status.ToUpper());
        joinButton.onClick.RemoveAllListeners();
        joinButton.onClick.AddListener(OnJoinClicked);
    }

    private void UpdateStatusBadge(TournamentData data)
    {
        switch (data.Status)
        {
            case "registration_open":
                statusBadge.text  = "OPEN";
                statusBadge.color = colorOpen;
                break;
            case "in_progress":
                statusBadge.text  = "IN PROGRESS";
                statusBadge.color = colorProgress;
                break;
            case "registration_closed":
                statusBadge.text  = "STARTING SOON";
                statusBadge.color = colorClosed;
                break;
            case "completed":
                statusBadge.text  = "COMPLETED";
                statusBadge.color = colorClosed;
                break;
            default:
                statusBadge.text  = data.Status.ToUpper();
                statusBadge.color = colorClosed;
                break;
        }

        if (data.IsFull && data.Status == "registration_open")
        {
            statusBadge.text  = "FULL";
            statusBadge.color = colorFull;
        }
    }

    private void OnJoinClicked()
    {
        if (_data == null) return;

        // Show detail popup with confirm join
        TournamentDetailUI.Show(_data);
    }

    // ── Formatters ────────────────────────────────────────────────────────────

    private static string FormatLabel(string format) => format switch
    {
        "knockout"       => "Knockout",
        "round_robin"    => "Round Robin",
        "double_elim"    => "Double Elim.",
        "group_knockout" => "Group + KO",
        _                => format,
    };

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
            return $"{local:dd MMM, hh:mm tt} ({tz})";
        }

        if (System.DateTime.TryParse(iso, out var dt))
        {
            DateTime local = dt.Kind == DateTimeKind.Utc ? dt.ToLocalTime() : dt;
            string tz = FormatOffset(TimeZoneInfo.Local.GetUtcOffset(local));
            return $"{local:dd MMM, hh:mm tt} ({tz})";
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
