using System;
using System.Collections.Generic;
using System.Globalization;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

/// <summary>
/// End-of-tournament result screen.
/// Shows final rankings (1st–5th), prizes won, and "Next Match" info.
/// Triggered by TournamentMatchConnector.OnMatchEnd or OnPrizeCredited.
/// </summary>
public class TournamentResultUI : MonoBehaviour
{
    public static TournamentResultUI Instance { get; private set; }

    [Header("Panel")]
    [SerializeField] private GameObject panel;
    [SerializeField] private Animator   panelAnimator;

    [Header("Header")]
    [SerializeField] private TextMeshProUGUI tournamentNameText;
    [SerializeField] private TextMeshProUGUI resultTitleText;   // "YOU WON!" / "Round Complete" / "Eliminated"

    [Header("My Result")]
    [SerializeField] private TextMeshProUGUI myPositionText;    // "#1 Place"
    [SerializeField] private TextMeshProUGUI myPrizeText;       // "₹1,280 Won!"
    [SerializeField] private Image           myMedalImage;
    [SerializeField] private Sprite[]        medalSprites;      // 0=gold, 1=silver, 2=bronze

    [Header("Leaderboard Rows (Top 5)")]
    [SerializeField] private ResultRowUI[]   resultRows;        // 5 rows

    [Header("Next Match")]
    [SerializeField] private GameObject      nextMatchPanel;
    [SerializeField] private TextMeshProUGUI nextMatchTimeText;
    [SerializeField] private TextMeshProUGUI nextMatchOpponentText;
    [SerializeField] private Button          nextMatchReadyBtn;

    [Header("Buttons")]
    [SerializeField] private Button          claimPrizeBtn;
    [SerializeField] private TextMeshProUGUI claimPrizeBtnText;
    [SerializeField] private Button          lobbyBtn;
    [SerializeField] private Button          shareBtn;

    private int _myUserId;

    private void Awake()
    {
        if (Instance != null && Instance != this) { Destroy(gameObject); return; }
        Instance = this;
        panel.SetActive(false);
    }

    private void OnEnable()
    {
        TournamentMatchConnector.OnMatchEnd     += HandleMatchEnd;
        TournamentMatchConnector.OnPrizeCredited += HandlePrizeCredited;
        TournamentMatchConnector.OnNextMatchScheduled += HandleNextMatch;
    }

    private void OnDisable()
    {
        TournamentMatchConnector.OnMatchEnd     -= HandleMatchEnd;
        TournamentMatchConnector.OnPrizeCredited -= HandlePrizeCredited;
        TournamentMatchConnector.OnNextMatchScheduled -= HandleNextMatch;
    }

    private void Start()
    {
        _myUserId = TournamentMatchConnector.Instance != null
            ? TournamentMatchConnector.Instance.MyUserId
            : 0;

        claimPrizeBtn.onClick.AddListener(OnClaimPrize);
        lobbyBtn.onClick.AddListener(GoToLobby);
        shareBtn.onClick.AddListener(ShareResult);
        nextMatchReadyBtn.onClick.AddListener(OnNextMatchReady);
    }

    // ── Show Result ───────────────────────────────────────────────────────────

    public void ShowResult(MatchEndData data, string tournamentName = "")
    {
        panel.SetActive(true);
        panelAnimator?.Play("SlideIn");
        nextMatchPanel.SetActive(false);
        claimPrizeBtn.gameObject.SetActive(false);

        tournamentNameText.text = tournamentName;

        // Find my position
        var myEntry = data.Positions?.Find(p => p.UserId == _myUserId);
        int myPos   = myEntry?.Position ?? 0;

        // Result title
        resultTitleText.text = myPos switch
        {
            1 => "YOU WON!",
            2 => "Runner Up!",
            3 => "3rd Place!",
            _ when myPos > 0 => "Round Complete",
            _ => "Eliminated"
        };

        // My position & medal
        myPositionText.text = myPos > 0 ? OrdinalPosition(myPos) : "--";
        if (myMedalImage != null && medalSprites != null)
        {
            int medalIdx = myPos - 1;
            myMedalImage.gameObject.SetActive(medalIdx >= 0 && medalIdx < medalSprites.Length);
            if (medalIdx >= 0 && medalIdx < medalSprites.Length)
                myMedalImage.sprite = medalSprites[medalIdx];
        }

        myPrizeText.text  = ""; // Filled when prize_credited arrives
        myPrizeText.gameObject.SetActive(false);

        // Leaderboard
        for (int i = 0; i < resultRows.Length; i++)
        {
            if (data.Positions != null && i < data.Positions.Count)
            {
                var entry = data.Positions[i];
                resultRows[i].gameObject.SetActive(true);
                resultRows[i].Populate(
                    position : entry.Position,
                    name     : data.Scores != null && data.Scores.ContainsKey(entry.UserId.ToString())
                                ? $"Player {entry.UserId}" : $"Player {entry.UserId}",
                    score    : data.Scores != null && data.Scores.TryGetValue(entry.UserId.ToString(), out int sc) ? sc : 0,
                    isMe     : entry.UserId == _myUserId
                );
            }
            else
            {
                resultRows[i].gameObject.SetActive(false);
            }
        }
    }

    // ── Event Handlers ────────────────────────────────────────────────────────

    private void HandleMatchEnd(MatchEndData data)
    {
        ShowResult(data);
    }

    private void HandlePrizeCredited(PrizeCreditedData data)
    {
        myPrizeText.text = $"₹{data.Amount:F0} Credited!";
        myPrizeText.gameObject.SetActive(true);
        myPrizeText.color = new Color(1f, 0.85f, 0f); // Gold color

        claimPrizeBtn.gameObject.SetActive(false); // Auto-credited, no manual claim needed
    }

    private void HandleNextMatch(string raw)
    {
        nextMatchPanel.SetActive(true);
        // Parse scheduled_at and opponent from raw JSON
        try
        {
            var json = Newtonsoft.Json.Linq.JObject.Parse(raw);
            string time     = json["scheduled_at"]?.ToString() ?? "";
            string opponent = json["opponent"]?.ToString() ?? "TBD";

            nextMatchTimeText.text     = $"Next Match: {FormatDateTime(time)}";
            nextMatchOpponentText.text = $"vs {opponent}";
        }
        catch
        {
            nextMatchTimeText.text = "Next match scheduled";
        }
    }

    // ── Button Handlers ───────────────────────────────────────────────────────

    private void OnClaimPrize()
    {
        // Prizes are auto-credited — this is just a UI acknowledgement
        claimPrizeBtn.interactable = false;
        claimPrizeBtnText.text     = "Credited!";
    }

    private void GoToLobby()
    {
        panel.SetActive(false);
        // Load lobby scene
        UnityEngine.SceneManagement.SceneManager.LoadScene("LudoClassicLobby");
    }

    private void ShareResult()
    {
        string text = $"I just played in a Ludo tournament! Check out the game!";
        new NativeShare().SetText(text).Share();
    }

    private void OnNextMatchReady()
    {
        // Rejoin the socket for next match
        panel.SetActive(false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static string OrdinalPosition(int pos) => pos switch
    {
        1 => "1st Place",
        2 => "2nd Place",
        3 => "3rd Place",
        4 => "4th Place",
        5 => "5th Place",
        _ => $"{pos}th Place"
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

/// <summary>Single row in the result leaderboard.</summary>
public class ResultRowUI : MonoBehaviour
{
    [SerializeField] private TextMeshProUGUI positionText;
    [SerializeField] private TextMeshProUGUI nameText;
    [SerializeField] private TextMeshProUGUI scoreText;
    [SerializeField] private Image           rowBackground;
    [SerializeField] private Color           myRowColor = new Color(1f, 0.95f, 0.7f);

    public void Populate(int position, string name, int score, bool isMe)
    {
        positionText.text = OrdinalPosition(position);
        nameText.text     = isMe ? $"{name} (You)" : name;
        scoreText.text    = $"{score} tokens home";

        if (rowBackground != null)
            rowBackground.color = isMe ? myRowColor : Color.white;
    }

    private static string OrdinalPosition(int p) => p switch
    {
        1 => "🥇 1st", 2 => "🥈 2nd", 3 => "🥉 3rd",
        _ => $"{p}th"
    };
}

// NativeShare plugin is defined in:
// Assets/Plugins/NativeShare/NativeShare.cs
