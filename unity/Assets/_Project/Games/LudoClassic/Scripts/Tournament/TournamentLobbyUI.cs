using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;
using LudoClassic.Tournament;

/// <summary>
/// Controls the Tournament Lobby screen.
/// Has 3 tabs: Browse (public), My Tournaments, Create Tournament.
/// Attach to the TournamentLobbyCanvas.
/// </summary>
public class TournamentLobbyUI : MonoBehaviour
{
    [Header("Tabs")]
    [SerializeField] private Button    tabBrowseBtn;
    [SerializeField] private Button    tabMyBtn;
    [SerializeField] private Button    tabCreateBtn;
    [SerializeField] private GameObject tabBrowsePanel;
    [SerializeField] private GameObject tabMyPanel;
    [SerializeField] private GameObject tabCreatePanel;

    [Header("Browse Tab")]
    [SerializeField] private Transform        tournamentListParent;
    [SerializeField] private TournamentCardUI cardPrefab;
    [SerializeField] private TMP_InputField   searchInput;
    [SerializeField] private TMP_Dropdown     formatFilter;
    [SerializeField] private Button           joinPrivateBtn;
    [SerializeField] private GameObject       loadingSpinner;
    [SerializeField] private TextMeshProUGUI  emptyStateText;

    [Header("Private Join Popup")]
    [SerializeField] private GameObject      privateJoinPopup;
    [SerializeField] private TMP_InputField  inviteCodeInput;
    [SerializeField] private TMP_InputField  invitePasswordInput;
    [SerializeField] private Button          privateJoinConfirmBtn;
    [SerializeField] private Button          privateJoinCancelBtn;

    private List<TournamentData>      _allTournaments = new();
    private List<TournamentCardUI>    _cards          = new();

    private void OnEnable()
    {
        TournamentManager.OnTournamentsLoaded   += PopulateList;
        TournamentManager.OnApiError            += ShowError;
    }

    private void OnDisable()
    {
        TournamentManager.OnTournamentsLoaded   -= PopulateList;
        TournamentManager.OnApiError            -= ShowError;
    }

    private void Start()
    {
        tabBrowseBtn.onClick.AddListener(() => SwitchTab(0));
        tabMyBtn.onClick.AddListener(()     => SwitchTab(1));
        tabCreateBtn.onClick.AddListener(() => SwitchTab(2));

        joinPrivateBtn.onClick.AddListener(OpenPrivatePopup);
        privateJoinConfirmBtn.onClick.AddListener(OnPrivateJoinConfirm);
        privateJoinCancelBtn.onClick.AddListener(() => privateJoinPopup.SetActive(false));

        searchInput.onValueChanged.AddListener(_ => FilterList());
        formatFilter.onValueChanged.AddListener(_ => FilterList());

        SwitchTab(0);
    }

    private void SwitchTab(int index)
    {
        tabBrowsePanel.SetActive(index == 0);
        tabMyPanel.SetActive(index == 1);
        tabCreatePanel.SetActive(index == 2);

        if (index == 0) RefreshBrowse();
        if (index == 1) RefreshMyTournaments();
    }

    // ── Browse Tab ────────────────────────────────────────────────────────────

    public void RefreshBrowse()
    {
        SetLoading(true);
        TournamentManager.Instance.FetchPublicTournaments("registration_open");
    }

    private void PopulateList(List<TournamentData> tournaments)
    {
        SetLoading(false);
        _allTournaments = tournaments;

        // Clear old cards
        foreach (var card in _cards)
            Destroy(card.gameObject);
        _cards.Clear();

        FilterList();
    }

    private void FilterList()
    {
        foreach (var card in _cards)
            Destroy(card.gameObject);
        _cards.Clear();

        string search = searchInput.text.ToLower().Trim();
        string format = formatFilter.options.Count > 0
            ? formatFilter.options[formatFilter.value].text.ToLower()
            : "";

        var filtered = _allTournaments.FindAll(t =>
        {
            bool matchName   = string.IsNullOrEmpty(search) || t.Name.ToLower().Contains(search);
            bool matchFormat = format == "all" || string.IsNullOrEmpty(format) || t.Format == format;
            return matchName && matchFormat;
        });

        emptyStateText.gameObject.SetActive(filtered.Count == 0);
        emptyStateText.text = filtered.Count == 0 ? "No tournaments found." : "";

        foreach (var data in filtered)
        {
            var card = Instantiate(cardPrefab, tournamentListParent);
            card.Populate(data);
            _cards.Add(card);
        }
    }

    // ── Private Tournament Join ───────────────────────────────────────────────

    private void OpenPrivatePopup()
    {
        inviteCodeInput.text     = "";
        invitePasswordInput.text = "";
        privateJoinPopup.SetActive(true);
    }

    private void OnPrivateJoinConfirm()
    {
        string code     = inviteCodeInput.text.Trim().ToUpper();
        string password = invitePasswordInput.text.Trim();

        if (string.IsNullOrEmpty(code))
        {
            ShowError("Please enter an invite code.");
            return;
        }

        privateJoinPopup.SetActive(false);
        TournamentManager.Instance.FetchPrivateTournament(code, password);
        TournamentManager.OnTournamentDetailLoaded += OnPrivateTournamentLoaded;
    }

    private void OnPrivateTournamentLoaded(TournamentData data)
    {
        TournamentManager.OnTournamentDetailLoaded -= OnPrivateTournamentLoaded;
        // Open detail screen for this private tournament
        TournamentDetailUI.Show(data);
    }

    // ── My Tournaments Tab ────────────────────────────────────────────────────

    private void RefreshMyTournaments()
    {
        TournamentManager.Instance.FetchMyHistory(entries =>
        {
            // Populate the "My Tab" panel — handled by MyTournamentListUI if present
            Debug.Log($"[TournamentLobby] My history loaded: {entries.Count} entries");
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private void SetLoading(bool state)
    {
        if (loadingSpinner != null) loadingSpinner.SetActive(state);
    }

    private void ShowError(string error)
    {
        SetLoading(false);
        Debug.LogWarning($"[TournamentLobby] Error: {error}");
        // Show error popup (implement as needed)
    }
}
