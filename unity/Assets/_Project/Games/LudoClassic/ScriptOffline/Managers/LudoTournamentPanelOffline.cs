using System;
using System.Collections.Generic;
using System.Globalization;
using System.IO;
using Best.HTTP;
using Newtonsoft.Json.Linq;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoTournamentPanelOffline : MonoBehaviour
    {
        private readonly List<GameObject> runtimeRows = new List<GameObject>();

        private DashBoardManagerOffline dashboard;
        private GameObject panelRoot;
        private RectTransform listContent;
        private Text titleText;
        private Text statusText;
        private Button openButton;
        private Button refreshButton;
        private Button closeButton;
        private Button joinPrivateBtn;
        private ScrollRect tournamentScroll;
        private bool isLoading;
        private bool hasBuiltUi;
        private Font runtimeFont;

        // Auto-refresh every 30s while panel is open
        private const float AutoRefreshInterval = 30f;
        private float autoRefreshTimer = 0f;
        private bool isPanelOpen = false;

        private void Update()
        {
            if (!isPanelOpen || isLoading) return;
            autoRefreshTimer += Time.deltaTime;
            if (autoRefreshTimer >= AutoRefreshInterval)
            {
                autoRefreshTimer = 0f;
                RefreshTournaments();
            }
        }

        // ── Private Join Popup ────────────────────────────────────────────────
        private GameObject privatePopup;
        private InputField inviteCodeField;
        private InputField invitePasswordField;
        private Text privateStatusText;
        private GameObject detailPopup;
        private Text detailTitleText;
        private Text detailMetaText;
        private Text detailStatsText;
        private Text detailPrizeText;
        private Text detailWalletText;
        private Text detailHintText;
        private Button detailPrimaryButton;
        private Text detailPrimaryButtonText;
        private Button detailCloseButton;
        private LudoTournamentListItem selectedTournament;

        public void Initialize(DashBoardManagerOffline owner)
        {
            dashboard = owner;
            EnsureRuntimeUi();
        }

        public void OpenPanel()
        {
            EnsureRuntimeUi();
            if (dashboard != null && dashboard.lobbySelectPanal != null)
            {
                dashboard.lobbySelectPanal.SetActive(false);
            }
            panelRoot.transform.SetAsLastSibling();
            panelRoot.SetActive(true);
            isPanelOpen = true;
            autoRefreshTimer = 0f;
            Debug.Log("Tournament panel opened");
            RefreshTournaments();
        }

        public void ShowLauncherButton(bool visible)
        {
            EnsureRuntimeUi();
            if (openButton != null)
            {
                openButton.gameObject.SetActive(visible);
            }
        }

        /// Full close — hides this panel and restores the lobby.
        public void ClosePanel()
        {
            HidePanel();
            if (dashboard == null) return;
            dashboard.lobbySelectPanal?.SetActive(true);
            dashboard.selectGameModePanal?.SetActive(true);
        }

        /// Only hides this panel; does NOT restore the lobby.
        /// Use this when switching to another tournament sub-panel (Create / My History).
        public void HidePanel()
        {
            isPanelOpen = false;
            if (panelRoot != null)
                panelRoot.SetActive(false);
        }

        public async void RefreshTournaments()
        {
            if (isLoading)
            {
                return;
            }

            EnsureRuntimeUi();
            isLoading = true;
            SetStatus("Loading tournaments...");
            ClearRows();

            var request = new HTTPRequest(new Uri(Configuration.LudoTournamentListUrl), HTTPMethods.Get);
            request.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            request.SetHeader("Accept", "application/json");

            try
            {
                var response = await request.GetHTTPResponseAsync();
                if (!response.IsSuccess)
                {
                    SetStatus("Unable to load tournaments.");
                    return;
                }

                RenderTournamentList(response.DataAsText);
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Tournament list load failed: " + ex.Message);
                SetStatus("Unable to load tournaments.");
            }
            finally
            {
                isLoading = false;
            }
        }

        // ── Private Tournament Join ───────────────────────────────────────────

        private void OpenPrivatePopup()
        {
            EnsurePrivatePopup();
            inviteCodeField.text     = string.Empty;
            invitePasswordField.text = string.Empty;
            privateStatusText.text   = string.Empty;
            privatePopup.SetActive(true);
        }

        private void EnsurePrivatePopup()
        {
            if (privatePopup != null) return;

            // Full-screen overlay
            privatePopup = new GameObject("PrivateJoinPopup", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            privatePopup.transform.SetParent(panelRoot.transform, false);
            privatePopup.GetComponent<Image>().color = new Color32(0, 0, 0, 210);
            RectTransform overlayRect = privatePopup.GetComponent<RectTransform>();
            overlayRect.anchorMin = Vector2.zero;
            overlayRect.anchorMax = Vector2.one;
            overlayRect.offsetMin = overlayRect.offsetMax = Vector2.zero;

            // Centered card — anchored to cover most of the panel
            GameObject card = new GameObject("Card", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup));
            card.transform.SetParent(privatePopup.transform, false);
            card.GetComponent<Image>().color = new Color32(16, 28, 46, 255);
            RectTransform cardRect = card.GetComponent<RectTransform>();
            cardRect.anchorMin = new Vector2(0.10f, 0.15f);
            cardRect.anchorMax = new Vector2(0.90f, 0.88f);
            cardRect.offsetMin = cardRect.offsetMax = Vector2.zero;

            VerticalLayoutGroup vl = card.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(40, 40, 40, 40);
            vl.spacing = 18;
            vl.childControlHeight     = true;
            vl.childControlWidth      = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth  = true;

            // Title row: label + ✕ close button side by side
            GameObject titleRow = new GameObject("TitleRow", typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            titleRow.transform.SetParent(card.transform, false);
            titleRow.GetComponent<LayoutElement>().preferredHeight = 80f;
            HorizontalLayoutGroup thl = titleRow.GetComponent<HorizontalLayoutGroup>();
            thl.childControlHeight = true; thl.childControlWidth = true;
            thl.childForceExpandHeight = true; thl.childForceExpandWidth = false;
            thl.spacing = 10;

            GameObject titleLblGo = new GameObject("Title", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text), typeof(LayoutElement));
            titleLblGo.transform.SetParent(titleRow.transform, false);
            Text titleLbl = titleLblGo.GetComponent<Text>();
            titleLbl.font = GetRuntimeFont(); titleLbl.text = "Join Private Tournament";
            titleLbl.fontSize = 52; titleLbl.fontStyle = FontStyle.Bold; titleLbl.color = Color.white;
            titleLbl.horizontalOverflow = HorizontalWrapMode.Wrap;
            titleLblGo.GetComponent<LayoutElement>().flexibleWidth = 1f;

            Button popupCloseBtn = CreateButton(titleRow.transform, "✕", new Color32(160, 47, 47, 255));
            popupCloseBtn.GetComponent<LayoutElement>().preferredWidth = 90f;
            popupCloseBtn.onClick.AddListener(() => privatePopup.SetActive(false));

            // Input fields
            inviteCodeField = CreateInputField(card.transform, "Invite Code  (e.g. YAFWGH)");
            inviteCodeField.GetComponent<LayoutElement>().preferredHeight = 100f;
            ((Text)inviteCodeField.placeholder).fontSize = 42;
            inviteCodeField.textComponent.fontSize = 42;

            invitePasswordField = CreateInputField(card.transform, "Password  (leave blank if none)");
            invitePasswordField.GetComponent<LayoutElement>().preferredHeight = 100f;
            ((Text)invitePasswordField.placeholder).fontSize = 42;
            invitePasswordField.textComponent.fontSize = 42;

            // Status / error line
            privateStatusText = CreateLabel(card.transform, string.Empty, 40, FontStyle.Italic, new Color32(255, 110, 110, 255));

            // Button row
            GameObject btnRow = new GameObject("BtnRow", typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            btnRow.transform.SetParent(card.transform, false);
            btnRow.GetComponent<LayoutElement>().preferredHeight = 110f;
            HorizontalLayoutGroup hl = btnRow.GetComponent<HorizontalLayoutGroup>();
            hl.spacing                = 24;
            hl.childControlHeight     = true;
            hl.childControlWidth      = true;
            hl.childForceExpandWidth  = true;
            hl.childForceExpandHeight = true;

            Button confirmBtn = CreateButton(btnRow.transform, "Confirm", new Color32(39, 160, 80, 255));
            confirmBtn.onClick.AddListener(ConfirmPrivateJoin);

            Button cancelBtn = CreateButton(btnRow.transform, "Cancel", new Color32(120, 40, 40, 255));
            cancelBtn.onClick.AddListener(() => privatePopup.SetActive(false));

            privatePopup.SetActive(false);
        }

        private async void ConfirmPrivateJoin()
        {
            string code     = inviteCodeField.text.Trim().ToUpper();
            string password = invitePasswordField.text.Trim();

            if (string.IsNullOrEmpty(code))
            {
                privateStatusText.text = "Please enter an invite code.";
                return;
            }

            privateStatusText.text = "Looking up tournament...";

            string url = Configuration.LudoTournamentInfoUrl + "private/" + code;
            if (!string.IsNullOrEmpty(password))
            {
                url += "?password=" + Uri.EscapeDataString(password);
            }

            HTTPRequest req = new HTTPRequest(new Uri(url), HTTPMethods.Get);
            req.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            req.SetHeader("Accept", "application/json");

            try
            {
                var response = await req.GetHTTPResponseAsync();
                if (!response.IsSuccess)
                {
                    string err = ExtractErrorMessage(response.DataAsText) ?? "Tournament not found.";
                    privateStatusText.text = err;
                    return;
                }

                JToken root = JToken.Parse(response.DataAsText);
                LudoTournamentListItem tournament = ParseTournament(root["data"] ?? root);

                privatePopup.SetActive(false);
                OpenTournamentDetails(tournament);
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Private tournament lookup failed: " + ex.Message);
                privateStatusText.text = "Unable to find tournament.";
            }
        }

        private InputField CreateInputField(Transform parent, string placeholder)
        {
            GameObject go = new GameObject("InputField", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(InputField), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            go.GetComponent<Image>().color = new Color32(30, 45, 65, 255);
            go.GetComponent<LayoutElement>().preferredHeight = 90f;

            InputField field = go.GetComponent<InputField>();
            field.targetGraphic = go.GetComponent<Image>();

            GameObject phGo = new GameObject("Placeholder", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            phGo.transform.SetParent(go.transform, false);
            Text ph = phGo.GetComponent<Text>();
            ph.font      = GetRuntimeFont();
            ph.text      = placeholder;
            ph.fontSize  = 38;
            ph.color     = new Color32(140, 150, 160, 200);
            ph.alignment = TextAnchor.MiddleLeft;
            RectTransform phRect = phGo.GetComponent<RectTransform>();
            phRect.anchorMin = Vector2.zero;
            phRect.anchorMax = Vector2.one;
            phRect.offsetMin = new Vector2(16, 0);
            phRect.offsetMax = new Vector2(-16, 0);

            GameObject txtGo = new GameObject("Text", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            txtGo.transform.SetParent(go.transform, false);
            Text txt = txtGo.GetComponent<Text>();
            txt.font      = GetRuntimeFont();
            txt.fontSize  = 38;
            txt.color     = Color.white;
            txt.alignment = TextAnchor.MiddleLeft;
            RectTransform txtRect = txtGo.GetComponent<RectTransform>();
            txtRect.anchorMin = Vector2.zero;
            txtRect.anchorMax = Vector2.one;
            txtRect.offsetMin = new Vector2(16, 0);
            txtRect.offsetMax = new Vector2(-16, 0);

            field.textComponent  = txt;
            field.placeholder    = ph;
            return field;
        }

        private async void JoinTournament(LudoTournamentListItem tournament)
        {
            if (tournament == null)
            {
                return;
            }

            string entryUuid = tournament.EntryUuid;
            if (string.IsNullOrWhiteSpace(entryUuid))
            {
                SetStatus("Joining tournament...");
                entryUuid = await JoinTournamentAsync(tournament.TournamentUuid);
            }

            if (string.IsNullOrWhiteSpace(entryUuid))
            {
                SetStatus("Unable to join tournament.");
                return;
            }

            ClosePanel();
            bool started = dashboard.TryStartTournamentMatch(
                tournament.TournamentUuid,
                entryUuid,
                Mathf.Max(2, tournament.MaxPlayers)
            );

            if (!started)
            {
                SetStatus("Unable to start tournament match.");
            }
        }

        private async void RegisterTournament(LudoTournamentListItem tournament)
        {
            if (tournament == null)
            {
                return;
            }

            SetStatus("Registering for tournament...");
            if (detailPrimaryButton != null)
            {
                detailPrimaryButton.interactable = false;
            }

            string entryUuid = tournament.EntryUuid;
            if (string.IsNullOrWhiteSpace(entryUuid))
            {
                entryUuid = await JoinTournamentAsync(tournament.TournamentUuid);
            }

            if (string.IsNullOrWhiteSpace(entryUuid))
            {
                SetStatus("Unable to register for tournament.");
                RefreshDetailPopupState();
                return;
            }

            tournament.EntryUuid = entryUuid;
            tournament.CanJoin = false;
            tournament.JoinedPlayers = Mathf.Min(tournament.MaxPlayers, tournament.JoinedPlayers + 1);

            SetStatus("Tournament registered successfully. Entry fee deducted from wallet.");
            RefreshDetailPopupState();
            RefreshTournaments();
        }

        private async System.Threading.Tasks.Task<string> JoinTournamentAsync(string tournamentUuid)
        {
            string joinUrl = Configuration.LudoTournamentInfoUrl + tournamentUuid + "/register";
            var request = HTTPRequest.CreatePost(joinUrl);
            request.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            request.SetHeader("Accept", "application/json");
            request.SetHeader("Content-Type", "application/json");
            request.UploadSettings.UploadStream = new MemoryStream(System.Text.Encoding.UTF8.GetBytes("{}"));

            try
            {
                var response = await request.GetHTTPResponseAsync();
                if (!response.IsSuccess)
                {
                    string errorMessage = ExtractErrorMessage(response.DataAsText);
                    Debug.LogWarning("Tournament join failed response: " + response.DataAsText);
                    SetStatus(string.IsNullOrWhiteSpace(errorMessage) ? "Unable to join tournament." : errorMessage);
                    return null;
                }

                Debug.Log("Tournament join success response: " + response.DataAsText);
                UpdateWalletFromRegistrationResponse(response.DataAsText);
                string entryUuid = ExtractEntryUuid(response.DataAsText);
                if (string.IsNullOrWhiteSpace(entryUuid))
                {
                    SetStatus("Unable to join tournament.");
                }
                return entryUuid;
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Tournament join failed: " + ex.Message);
                SetStatus("Unable to join tournament.");
                return null;
            }
        }

        private void RenderTournamentList(string json)
        {
            ClearRows();

            try
            {
                JToken root = JToken.Parse(json);
                JArray items = root["data"] as JArray;
                if (items == null)
                {
                    items = root["data"]?["data"] as JArray;
                }
                if (items == null || items.Count == 0)
                {
                    SetStatus("No tournaments available right now.");
                    return;
                }

                SetStatus(string.Empty);
                foreach (JToken item in items)
                {
                    CreateTournamentRow(ParseTournament(item));
                }

                RefreshScrollLayout();
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Tournament list parse failed: " + ex.Message);
                SetStatus("Unable to read tournaments.");
            }
        }

        private LudoTournamentListItem ParseTournament(JToken item)
        {
            string uuid = ReadString(item, "uuid", "tournament_uuid", "id");
            string title = ReadString(item, "name", "title", "tournament_name");
            string status = ReadString(item, "status", "state");
            string startTime = ReadString(item, "start_at", "start_time", "starts_at", "startAt");
            string entryCloseAt = ReadString(item, "entry_close_at", "registration_end_at", "entryCloseAt");
            int entryFee = ReadInt(item, "entry_fee", "entryFee");
            int maxPlayers = ReadInt(item, "max_total_entries", "max_players", "maxPlayers");
            int joinedPlayers = ReadInt(item, "current_active_entries", "current_total_entries", "joined_players", "current_players", "currentPlayers");
            string entryUuid = ReadNestedString(item, "entry", "uuid");
            bool canJoin = ReadBool(item, "can_join", "canJoin");
            string description = ReadString(item, "description", "about", "details");
            string format = ReadString(item, "format", "bracket_mode");
            string type = ReadString(item, "type", "tournament_type");
            List<string> playSlots = ReadPlaySlots(item["play_slots"]);

            // Prize pool from total_prize_pool or prize_pool field
            float prizePool = ReadFloat(item, "total_prize_pool", "prize_pool", "prizePool");
            if (prizePool <= 0f)
            {
                // Estimate: 80% of entryFee * maxPlayers
                prizePool = entryFee * maxPlayers * 0.8f;
            }

            if (string.IsNullOrWhiteSpace(entryUuid))
            {
                entryUuid = ReadString(item, "entry_uuid", "tournament_entry_uuid", "joined_entry_uuid");
            }

            if (maxPlayers <= 0)
            {
                maxPlayers = ReadNestedInt(item, "meta", "max_players");
            }

            if (joinedPlayers <= 0)
            {
                joinedPlayers = ReadNestedInt(item, "meta", "joined_players");
            }

            return new LudoTournamentListItem
            {
                TournamentUuid = uuid,
                Title = string.IsNullOrWhiteSpace(title) ? "Tournament" : title,
                Status = string.IsNullOrWhiteSpace(status) ? "upcoming" : status,
                StartTime = startTime,
                RegistrationEndAt = entryCloseAt,
                EntryFee = entryFee,
                PrizePool = prizePool,
                MaxPlayers = Mathf.Max(2, maxPlayers == 0 ? 2 : maxPlayers),
                JoinedPlayers = joinedPlayers,
                EntryUuid = entryUuid,
                CanJoin = canJoin,
                Description = description,
                Format = format,
                Type = type,
                PlaySlots = playSlots,
            };
        }

        private void CreateTournamentRow(LudoTournamentListItem tournament)
        {
            if (string.IsNullOrWhiteSpace(tournament.TournamentUuid))
                return;

            bool playable = IsTournamentPlayable(tournament);
            bool shouldOpenDetails = !playable;

            // Accent / status color
            Color32 accentColor = tournament.Status == "in_progress"
                ? new Color32(60, 210, 100, 255)
                : tournament.Status == "registration_open"
                    ? new Color32(60, 160, 255, 255)
                    : new Color32(220, 190, 50, 255);

            // ── Card (outer HLG row) ───────────────────────────────────────
            var card = new GameObject("TCard",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            card.transform.SetParent(listContent, false);
            runtimeRows.Add(card);
            card.GetComponent<Image>().color = new Color32(14, 26, 50, 255);
            var cardLE = card.GetComponent<LayoutElement>();
            cardLE.preferredHeight = 148f;
            cardLE.minHeight       = 130f;
            var cardHL = card.GetComponent<HorizontalLayoutGroup>();
            cardHL.padding = new RectOffset(0, 0, 0, 0);
            cardHL.spacing = 0;
            cardHL.childControlHeight = true; cardHL.childControlWidth = true;
            cardHL.childForceExpandHeight = true; cardHL.childForceExpandWidth = false;

            // ── Left accent bar ───────────────────────────────────────────
            var accent = new GameObject("Accent",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            accent.transform.SetParent(card.transform, false);
            accent.GetComponent<Image>().color = accentColor;
            var acLE = accent.GetComponent<LayoutElement>();
            acLE.preferredWidth = 6f; acLE.minWidth = 6f;

            // ── Info col (name + status badge + players) ──────────────────
            var infoCol = new GameObject("InfoCol",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            infoCol.transform.SetParent(card.transform, false);
            infoCol.GetComponent<LayoutElement>().flexibleWidth = 1f;
            var infoVL = infoCol.GetComponent<VerticalLayoutGroup>();
            infoVL.padding = new RectOffset(14, 8, 14, 10);
            infoVL.spacing = 5;
            infoVL.childControlHeight = true; infoVL.childControlWidth = true;
            infoVL.childForceExpandHeight = false; infoVL.childForceExpandWidth = true;
            infoVL.childAlignment = TextAnchor.UpperLeft;

            // Tournament name
            var nameL = CreateLabel(infoCol.transform, tournament.Title, 36, FontStyle.Bold, Color.white);
            nameL.horizontalOverflow = HorizontalWrapMode.Wrap;

            // Badge row: [STATUS PILL]  [4/8 players]
            var badgeRow = new GameObject("BadgeRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            badgeRow.transform.SetParent(infoCol.transform, false);
            badgeRow.GetComponent<LayoutElement>().preferredHeight = 32f;
            var brHL = badgeRow.GetComponent<HorizontalLayoutGroup>();
            brHL.spacing = 10; brHL.childControlHeight = true; brHL.childControlWidth = true;
            brHL.childForceExpandHeight = true; brHL.childForceExpandWidth = false;

            // Status pill background
            var pillGO = new GameObject("StatusPill",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            pillGO.transform.SetParent(badgeRow.transform, false);
            pillGO.GetComponent<Image>().color = new Color32(accentColor.r, accentColor.g, accentColor.b, 40);
            var pillHL = pillGO.GetComponent<HorizontalLayoutGroup>();
            pillHL.padding = new RectOffset(10, 10, 3, 3);
            pillHL.childControlHeight = true; pillHL.childControlWidth = true;
            pillHL.childForceExpandHeight = true; pillHL.childForceExpandWidth = false;
            pillGO.GetComponent<LayoutElement>().preferredHeight = 32f;
            var statusL = CreateLabel(pillGO.transform,
                tournament.Status.Replace("_", " ").ToUpper(), 22, FontStyle.Bold, accentColor);
            statusL.verticalOverflow = VerticalWrapMode.Overflow;

            // Players count
            string playersStr = tournament.JoinedPlayers + " / " + tournament.MaxPlayers + " players";
            var playersL = CreateLabel(badgeRow.transform, playersStr, 26,
                FontStyle.Normal, new Color32(155, 175, 210, 255));
            playersL.verticalOverflow = VerticalWrapMode.Overflow;

            // ── Stats col: ENTRY FEE + WIN PRIZE boxes ────────────────────
            var statsCol = new GameObject("StatsCol",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            statsCol.transform.SetParent(card.transform, false);
            var statsLE = statsCol.GetComponent<LayoutElement>();
            statsLE.preferredWidth = 172f; statsLE.minWidth = 150f;
            var statsVL = statsCol.GetComponent<VerticalLayoutGroup>();
            statsVL.padding = new RectOffset(8, 8, 14, 10);
            statsVL.spacing = 8;
            statsVL.childControlHeight = true; statsVL.childControlWidth = true;
            statsVL.childForceExpandHeight = false; statsVL.childForceExpandWidth = true;
            statsVL.childAlignment = TextAnchor.MiddleCenter;

            // Entry fee box
            MakeStatBox(statsCol.transform, "ENTRY FEE",
                tournament.EntryFee > 0 ? "₹" + tournament.EntryFee : "Free",
                new Color32(20, 38, 70, 220), new Color32(255, 215, 60, 255));

            // Win prize box
            if (tournament.PrizePool > 0f)
            {
                MakeStatBox(statsCol.transform, "WIN PRIZE",
                    "₹" + Mathf.RoundToInt(tournament.PrizePool),
                    new Color32(18, 50, 30, 220), new Color32(80, 230, 120, 255));
            }

            // ── Action col: PLAY / CLOSED button ─────────────────────────
            var actCol = new GameObject("ActCol",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            actCol.transform.SetParent(card.transform, false);
            var actLE = actCol.GetComponent<LayoutElement>();
            actLE.preferredWidth = 155f; actLE.minWidth = 140f;
            var actVL = actCol.GetComponent<VerticalLayoutGroup>();
            actVL.padding = new RectOffset(10, 12, 18, 18);
            actVL.childControlHeight = true; actVL.childControlWidth = true;
            actVL.childForceExpandHeight = true; actVL.childForceExpandWidth = true;

            Color32 btnColor  = playable ? new Color32(34, 155, 70, 255)
                : tournament.Status == "registration_open"
                    ? new Color32(35, 115, 205, 255)
                    : new Color32(55, 65, 85, 255);
            string  btnLabel  = playable ? "▶ PLAY" : GetDetailsButtonLabel(tournament);
            var joinBtn = CreateButton(actCol.transform, btnLabel, btnColor);
            joinBtn.GetComponentInChildren<Text>().fontSize = 30;
            joinBtn.interactable = playable || shouldOpenDetails;
            joinBtn.onClick.AddListener(() =>
            {
                if (IsTournamentPlayable(tournament))
                {
                    JoinTournament(tournament);
                }
                else
                {
                    OpenTournamentDetails(tournament);
                }
            });

            // Thin divider
            var divider = new GameObject("Divider",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            divider.transform.SetParent(listContent, false);
            runtimeRows.Add(divider);
            divider.GetComponent<Image>().color = new Color32(28, 46, 70, 160);
            divider.GetComponent<LayoutElement>().preferredHeight = 3f;
        }

        // Stat box: label + bold value, used for Entry Fee / Win Prize
        private GameObject MakeStatBox(Transform parent, string label, string value,
            Color32 bgColor, Color32 valueColor)
        {
            var box = new GameObject(label + "Box",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup), typeof(LayoutElement));
            box.transform.SetParent(parent, false);
            box.GetComponent<Image>().color = bgColor;
            box.GetComponent<LayoutElement>().preferredHeight = 48f;
            var vl = box.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(6, 6, 4, 4);
            vl.spacing = 2;
            vl.childControlHeight = true; vl.childControlWidth = true;
            vl.childForceExpandHeight = false; vl.childForceExpandWidth = true;

            var lbl = CreateLabel(box.transform, label, 18, FontStyle.Normal,
                new Color32(160, 180, 210, 255));
            lbl.alignment = TextAnchor.MiddleCenter;
            lbl.verticalOverflow = VerticalWrapMode.Overflow;

            var val = CreateLabel(box.transform, value, 30, FontStyle.Bold, valueColor);
            val.alignment = TextAnchor.MiddleCenter;
            val.verticalOverflow = VerticalWrapMode.Overflow;

            return box;
        }

        private bool IsTournamentJoinable(LudoTournamentListItem tournament)
        {
            return tournament != null && tournament.CanJoin;
        }

        private bool IsTournamentPlayable(LudoTournamentListItem tournament)
        {
            return tournament != null
                && tournament.Status == "in_progress"
                && !string.IsNullOrWhiteSpace(tournament.EntryUuid);
        }

        private bool CanRegisterFromDetails(LudoTournamentListItem tournament)
        {
            if (tournament == null)
            {
                return false;
            }

            bool looksOpen = string.Equals(tournament.Status, "registration_open", StringComparison.OrdinalIgnoreCase);
            bool hasSeat = tournament.JoinedPlayers < tournament.MaxPlayers;
            bool alreadyRegistered = !string.IsNullOrWhiteSpace(tournament.EntryUuid);
            return looksOpen && hasSeat && !alreadyRegistered;
        }

        private string GetDetailsButtonLabel(LudoTournamentListItem tournament)
        {
            if (tournament == null)
            {
                return "DETAILS";
            }

            if (CanRegisterFromDetails(tournament))
            {
                return "DETAILS";
            }

            if (string.Equals(tournament.Status, "registration_open", StringComparison.OrdinalIgnoreCase)
                && !string.IsNullOrWhiteSpace(tournament.EntryUuid))
            {
                return "REGISTERED";
            }

            if (string.Equals(tournament.Status, "completed", StringComparison.OrdinalIgnoreCase))
            {
                return "RESULT";
            }

            return "DETAILS";
        }

        private void EnsureRuntimeUi()
        {
            if (hasBuiltUi) return;

            if (panelRoot  != null) { Destroy(panelRoot);              panelRoot  = null; }
            if (openButton != null) { Destroy(openButton.gameObject);  openButton = null; }
            listContent = null; titleText = null; statusText = null;
            closeButton = null; refreshButton = null; joinPrivateBtn = null;
            tournamentScroll = null; privatePopup = null;
            inviteCodeField = null; invitePasswordField = null; privateStatusText = null;
            detailPopup = null; detailTitleText = null; detailMetaText = null;
            detailStatsText = null; detailPrizeText = null; detailWalletText = null;
            detailHintText = null; detailPrimaryButton = null; detailPrimaryButtonText = null;
            detailCloseButton = null; selectedTournament = null;

            if (dashboard == null)
                dashboard = GetComponent<DashBoardManagerOffline>();

            Transform parent = dashboard != null && dashboard.dashBordPanal != null
                ? dashboard.dashBordPanal.transform
                : transform;

            // Small launcher button (hidden by default, managed by DashBoard tab)
            openButton = CreateButton(parent, "Tournaments", new Color32(190, 100, 30, 255));
            var openRect = openButton.GetComponent<RectTransform>();
            openRect.anchorMin = new Vector2(0.5f, 1f); openRect.anchorMax = new Vector2(0.5f, 1f);
            openRect.pivot = new Vector2(0.5f, 1f);
            openRect.anchoredPosition = new Vector2(0f, -170f);
            openRect.sizeDelta = new Vector2(300f, 76f);
            openButton.GetComponentInChildren<Text>().fontSize = 34;
            openButton.onClick.AddListener(OpenPanel);
            openButton.transform.SetAsLastSibling();

            // ── Full-screen panel ─────────────────────────────────────────────
            panelRoot = new GameObject("TournamentPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            panelRoot.transform.SetParent(parent, false);
            panelRoot.GetComponent<Image>().color = new Color32(8, 15, 28, 252);
            var panelRect = panelRoot.GetComponent<RectTransform>();
            panelRect.anchorMin = Vector2.zero; panelRect.anchorMax = Vector2.one;
            panelRect.offsetMin = panelRect.offsetMax = Vector2.zero;

            // ── Header bar (full-width, fixed height) ─────────────────────────
            const float headerH  = 82f;
            const float actionH  = 74f;
            const float topTotal = headerH + actionH;

            var headerBg = new GameObject("HeaderBg",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            headerBg.transform.SetParent(panelRoot.transform, false);
            headerBg.GetComponent<Image>().color = new Color32(10, 20, 40, 255);
            var hbR = headerBg.GetComponent<RectTransform>();
            hbR.anchorMin = new Vector2(0f, 1f); hbR.anchorMax = new Vector2(1f, 1f);
            hbR.pivot = new Vector2(0.5f, 1f);
            hbR.anchoredPosition = Vector2.zero;
            hbR.sizeDelta = new Vector2(0f, headerH);

            // Title text (left side of header)
            titleText = CreateLabel(headerBg.transform, "🏆  Tournaments", 48, FontStyle.Bold, Color.white);
            titleText.alignment = TextAnchor.MiddleLeft;
            var titleR = titleText.GetComponent<RectTransform>();
            titleR.anchorMin = Vector2.zero; titleR.anchorMax = Vector2.one;
            titleR.offsetMin = new Vector2(20f, 0f); titleR.offsetMax = new Vector2(-490f, 0f);

            // Refresh button (header, right side)
            refreshButton = MakeHeaderBtn(headerBg.transform, "↻", new Color32(30, 80, 140, 255));
            var rfR = refreshButton.GetComponent<RectTransform>();
            rfR.anchorMin = new Vector2(1f, 0.5f); rfR.anchorMax = new Vector2(1f, 0.5f);
            rfR.pivot = new Vector2(1f, 0.5f);
            rfR.anchoredPosition = new Vector2(-260f, 0f);
            rfR.sizeDelta = new Vector2(180f, 58f);
            refreshButton.onClick.AddListener(RefreshTournaments);

            // Close button (header, far right)
            closeButton = MakeHeaderBtn(headerBg.transform, "✕ Close", new Color32(155, 38, 38, 255));
            var cbR = closeButton.GetComponent<RectTransform>();
            cbR.anchorMin = new Vector2(1f, 0.5f); cbR.anchorMax = new Vector2(1f, 0.5f);
            cbR.pivot = new Vector2(1f, 0.5f);
            cbR.anchoredPosition = new Vector2(-16f, 0f);
            cbR.sizeDelta = new Vector2(228f, 58f);
            closeButton.onClick.AddListener(ClosePanel);

            // ── Action bar (below header) ─────────────────────────────────────
            var actionBar = new GameObject("ActionBar",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup));
            actionBar.transform.SetParent(panelRoot.transform, false);
            actionBar.GetComponent<Image>().color = new Color32(14, 26, 50, 255);
            var abR = actionBar.GetComponent<RectTransform>();
            abR.anchorMin = new Vector2(0f, 1f); abR.anchorMax = new Vector2(1f, 1f);
            abR.pivot = new Vector2(0.5f, 1f);
            abR.anchoredPosition = new Vector2(0f, -headerH);
            abR.sizeDelta = new Vector2(0f, actionH);

            var abHL = actionBar.GetComponent<HorizontalLayoutGroup>();
            abHL.padding = new RectOffset(16, 16, 10, 10);
            abHL.spacing = 12;
            abHL.childControlHeight = true; abHL.childControlWidth = true;
            abHL.childForceExpandHeight = true; abHL.childForceExpandWidth = true;

            joinPrivateBtn = CreateButton(actionBar.transform, "🔒 Join Private", new Color32(90, 50, 170, 255));
            joinPrivateBtn.GetComponentInChildren<Text>().fontSize = 30;
            joinPrivateBtn.onClick.AddListener(OpenPrivatePopup);

            var createBtn = CreateButton(actionBar.transform, "＋ Create", new Color32(30, 120, 200, 255));
            createBtn.GetComponentInChildren<Text>().fontSize = 30;
            createBtn.onClick.AddListener(() => dashboard?.OpenCreateTournamentPanel());

            var histBtn = CreateButton(actionBar.transform, "📋 My History", new Color32(70, 45, 130, 255));
            histBtn.GetComponentInChildren<Text>().fontSize = 30;
            histBtn.onClick.AddListener(() => dashboard?.OpenMyTournamentsPanel());

            // Status text (below action bar)
            statusText = CreateLabel(panelRoot.transform, string.Empty, 36, FontStyle.Normal,
                new Color32(255, 210, 90, 255));
            statusText.alignment = TextAnchor.MiddleCenter;
            var stR = statusText.GetComponent<RectTransform>();
            stR.anchorMin = new Vector2(0f, 1f); stR.anchorMax = new Vector2(1f, 1f);
            stR.pivot = new Vector2(0.5f, 1f);
            stR.anchoredPosition = new Vector2(0f, -(topTotal + 4f));
            stR.sizeDelta = new Vector2(-40f, 44f);

            // ── Scroll view (fills remaining area) ───────────────────────────
            var scrollRoot = new GameObject("ScrollView",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(ScrollRect));
            scrollRoot.transform.SetParent(panelRoot.transform, false);
            scrollRoot.GetComponent<Image>().color = Color.clear;
            var scrollRect = scrollRoot.GetComponent<RectTransform>();
            // From just below the action bar (top offset) to bottom
            scrollRect.anchorMin = new Vector2(0.01f, 0f);
            scrollRect.anchorMax = new Vector2(0.99f, 1f);
            scrollRect.offsetMin = new Vector2(0f, 12f);
            scrollRect.offsetMax = new Vector2(0f, -(topTotal + 50f));

            // Viewport
            var viewport = new GameObject("Viewport",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Mask));
            viewport.transform.SetParent(scrollRoot.transform, false);
            var viewportRect = viewport.GetComponent<RectTransform>();
            viewportRect.anchorMin = Vector2.zero; viewportRect.anchorMax = Vector2.one;
            viewportRect.offsetMin = viewportRect.offsetMax = Vector2.zero;
            viewport.GetComponent<Image>().color = new Color32(0, 0, 0, 1);
            viewport.GetComponent<Mask>().showMaskGraphic = false;

            // Content
            var contentRoot = new GameObject("Content",
                typeof(RectTransform), typeof(VerticalLayoutGroup));
            contentRoot.transform.SetParent(viewport.transform, false);
            listContent = contentRoot.GetComponent<RectTransform>();
            listContent.anchorMin = new Vector2(0f, 1f); listContent.anchorMax = new Vector2(1f, 1f);
            listContent.pivot = new Vector2(0.5f, 1f);
            listContent.anchoredPosition = Vector2.zero;
            listContent.sizeDelta = new Vector2(0f, 0f);

            var contentLayout = contentRoot.GetComponent<VerticalLayoutGroup>();
            contentLayout.padding = new RectOffset(12, 12, 10, 10);
            contentLayout.spacing = 10;
            contentLayout.childControlHeight = true; contentLayout.childControlWidth = true;
            contentLayout.childForceExpandHeight = false; contentLayout.childForceExpandWidth = true;

            var csf = contentRoot.AddComponent<ContentSizeFitter>();
            csf.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
            csf.verticalFit   = ContentSizeFitter.FitMode.PreferredSize;

            tournamentScroll = scrollRoot.GetComponent<ScrollRect>();
            tournamentScroll.content = listContent;
            tournamentScroll.horizontal = false; tournamentScroll.vertical = true;
            tournamentScroll.viewport = viewportRect;
            tournamentScroll.movementType = ScrollRect.MovementType.Clamped;
            tournamentScroll.inertia = true;
            tournamentScroll.scrollSensitivity = 90f;

            panelRoot.SetActive(false);
            openButton.gameObject.SetActive(false);
            EnsureDetailPopup();
            hasBuiltUi = true;
        }

        private void EnsureDetailPopup()
        {
            if (detailPopup != null || panelRoot == null)
            {
                return;
            }

            detailPopup = new GameObject("TournamentDetailPopup", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            detailPopup.transform.SetParent(panelRoot.transform, false);
            detailPopup.GetComponent<Image>().color = new Color32(0, 0, 0, 215);
            RectTransform overlayRect = detailPopup.GetComponent<RectTransform>();
            overlayRect.anchorMin = Vector2.zero;
            overlayRect.anchorMax = Vector2.one;
            overlayRect.offsetMin = overlayRect.offsetMax = Vector2.zero;

            GameObject card = new GameObject("DetailCard", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup));
            card.transform.SetParent(detailPopup.transform, false);
            card.GetComponent<Image>().color = new Color32(12, 22, 42, 255);
            RectTransform cardRect = card.GetComponent<RectTransform>();
            cardRect.anchorMin = new Vector2(0.08f, 0.12f);
            cardRect.anchorMax = new Vector2(0.92f, 0.9f);
            cardRect.offsetMin = cardRect.offsetMax = Vector2.zero;

            VerticalLayoutGroup vl = card.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(26, 26, 24, 24);
            vl.spacing = 14;
            vl.childControlHeight = true;
            vl.childControlWidth = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth = true;

            GameObject headRow = new GameObject("HeadRow", typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            headRow.transform.SetParent(card.transform, false);
            headRow.GetComponent<LayoutElement>().preferredHeight = 72f;
            HorizontalLayoutGroup headLayout = headRow.GetComponent<HorizontalLayoutGroup>();
            headLayout.spacing = 12;
            headLayout.childControlHeight = true;
            headLayout.childControlWidth = true;
            headLayout.childForceExpandHeight = false;
            headLayout.childForceExpandWidth = false;
            headLayout.childAlignment = TextAnchor.MiddleLeft;

            GameObject titleWrap = new GameObject("TitleWrap", typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            titleWrap.transform.SetParent(headRow.transform, false);
            titleWrap.GetComponent<LayoutElement>().flexibleWidth = 1f;
            VerticalLayoutGroup titleLayout = titleWrap.GetComponent<VerticalLayoutGroup>();
            titleLayout.spacing = 4;
            titleLayout.childControlHeight = true;
            titleLayout.childControlWidth = true;
            titleLayout.childForceExpandHeight = false;
            titleLayout.childForceExpandWidth = true;

            detailTitleText = CreateLabel(titleWrap.transform, "Tournament", 42, FontStyle.Bold, Color.white);
            detailMetaText = CreateLabel(titleWrap.transform, string.Empty, 24, FontStyle.Normal, new Color32(155, 175, 210, 255));

            detailCloseButton = CreateButton(headRow.transform, "✕", new Color32(126, 34, 34, 255));
            LayoutElement closeLayout = detailCloseButton.GetComponent<LayoutElement>();
            closeLayout.preferredWidth = 64f;
            closeLayout.minWidth = 64f;
            closeLayout.flexibleWidth = 0f;
            closeLayout.preferredHeight = 56f;
            closeLayout.minHeight = 56f;
            detailCloseButton.GetComponent<Image>().color = new Color32(126, 34, 34, 255);
            detailCloseButton.onClick.AddListener(() => detailPopup.SetActive(false));

            GameObject statsPanel = new GameObject("StatsPanel", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            statsPanel.transform.SetParent(card.transform, false);
            statsPanel.GetComponent<Image>().color = new Color32(20, 34, 62, 255);
            statsPanel.GetComponent<LayoutElement>().preferredHeight = 120f;
            VerticalLayoutGroup statsLayout = statsPanel.GetComponent<VerticalLayoutGroup>();
            statsLayout.padding = new RectOffset(16, 16, 14, 14);
            statsLayout.spacing = 6;
            statsLayout.childControlHeight = true;
            statsLayout.childControlWidth = true;
            statsLayout.childForceExpandHeight = false;
            statsLayout.childForceExpandWidth = true;
            detailStatsText = CreateLabel(statsPanel.transform, string.Empty, 34, FontStyle.Bold, new Color32(255, 225, 130, 255));
            detailWalletText = CreateLabel(statsPanel.transform, string.Empty, 28, FontStyle.Bold, new Color32(120, 220, 160, 255));
            ConfigureDetailBlockLabel(detailStatsText, 68f, false);
            ConfigureDetailBlockLabel(detailWalletText, 56f, false);

            GameObject infoPanel = new GameObject("InfoPanel", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            infoPanel.transform.SetParent(card.transform, false);
            infoPanel.GetComponent<Image>().color = new Color32(14, 24, 44, 255);
            infoPanel.GetComponent<LayoutElement>().preferredHeight = 380f;
            VerticalLayoutGroup infoLayout = infoPanel.GetComponent<VerticalLayoutGroup>();
            infoLayout.padding = new RectOffset(16, 16, 14, 14);
            infoLayout.spacing = 8;
            infoLayout.childControlHeight = true;
            infoLayout.childControlWidth = true;
            infoLayout.childForceExpandHeight = false;
            infoLayout.childForceExpandWidth = true;
            detailPrizeText = CreateLabel(infoPanel.transform, string.Empty, 30, FontStyle.Normal, Color.white);
            detailPrizeText.lineSpacing = 1.24f;
            ConfigureDetailBlockLabel(detailPrizeText, 320f, true);

            GameObject hintPanel = new GameObject("HintPanel", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            hintPanel.transform.SetParent(card.transform, false);
            hintPanel.GetComponent<Image>().color = new Color32(11, 52, 38, 255);
            hintPanel.GetComponent<LayoutElement>().preferredHeight = 72f;
            VerticalLayoutGroup hintLayout = hintPanel.GetComponent<VerticalLayoutGroup>();
            hintLayout.padding = new RectOffset(16, 16, 12, 12);
            hintLayout.childControlHeight = true;
            hintLayout.childControlWidth = true;
            hintLayout.childForceExpandHeight = false;
            hintLayout.childForceExpandWidth = true;
            detailHintText = CreateLabel(hintPanel.transform, string.Empty, 34, FontStyle.Italic, new Color32(216, 244, 232, 255));
            detailHintText.lineSpacing = 1.12f;
            ConfigureDetailBlockLabel(detailHintText, 44f, true);

            GameObject buttonRow = new GameObject("ButtonRow", typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            buttonRow.transform.SetParent(card.transform, false);
            buttonRow.GetComponent<LayoutElement>().preferredHeight = 74f;
            HorizontalLayoutGroup btnLayout = buttonRow.GetComponent<HorizontalLayoutGroup>();
            btnLayout.spacing = 14;
            btnLayout.childControlHeight = true;
            btnLayout.childControlWidth = true;
            btnLayout.childForceExpandHeight = false;
            btnLayout.childForceExpandWidth = false;
            btnLayout.childAlignment = TextAnchor.MiddleCenter;

            detailPrimaryButton = CreateButton(buttonRow.transform, "Register", new Color32(34, 155, 70, 255));
            detailPrimaryButtonText = detailPrimaryButton.GetComponentInChildren<Text>();
            detailPrimaryButtonText.fontSize = 28;
            LayoutElement primaryLayout = detailPrimaryButton.GetComponent<LayoutElement>();
            primaryLayout.preferredWidth = 250f;
            primaryLayout.minWidth = 220f;
            primaryLayout.flexibleWidth = 0f;
            primaryLayout.preferredHeight = 66f;
            detailPrimaryButton.onClick.AddListener(OnDetailPrimaryButtonPressed);

            Button secondaryCloseButton = CreateButton(buttonRow.transform, "Close", new Color32(55, 65, 85, 255));
            secondaryCloseButton.GetComponentInChildren<Text>().fontSize = 28;
            LayoutElement secondaryLayout = secondaryCloseButton.GetComponent<LayoutElement>();
            secondaryLayout.preferredWidth = 180f;
            secondaryLayout.minWidth = 160f;
            secondaryLayout.flexibleWidth = 0f;
            secondaryLayout.preferredHeight = 66f;
            secondaryCloseButton.onClick.AddListener(() => detailPopup.SetActive(false));

            detailPopup.SetActive(false);
        }

        private void OpenTournamentDetails(LudoTournamentListItem tournament)
        {
            if (tournament == null)
            {
                return;
            }

            EnsureDetailPopup();
            selectedTournament = tournament;
            detailPopup.SetActive(true);
            RefreshDetailPopupState();
        }

        private void RefreshDetailPopupState()
        {
            if (selectedTournament == null || detailPopup == null)
            {
                return;
            }

            detailTitleText.text = selectedTournament.Title;
            string formatLabel = string.IsNullOrWhiteSpace(selectedTournament.Format) ? "Ludo Tournament" : selectedTournament.Format.Replace("_", " ");
            string typeLabel = string.IsNullOrWhiteSpace(selectedTournament.Type) ? "Public" : selectedTournament.Type;
            detailMetaText.text = $"{typeLabel.ToUpperInvariant()} • {formatLabel.ToUpperInvariant()}";
            detailStatsText.text = $"{selectedTournament.Status.Replace("_", " ").ToUpperInvariant()}  |  {selectedTournament.JoinedPlayers}/{selectedTournament.MaxPlayers} PLAYERS";

            string startText = string.IsNullOrWhiteSpace(selectedTournament.StartTime)
                ? "Start time will be announced soon."
                : $"Starts: {selectedTournament.StartTime}";
            string regText = string.IsNullOrWhiteSpace(selectedTournament.RegistrationEndAt)
                ? string.Empty
                : $"\nRegistration closes: {selectedTournament.RegistrationEndAt}";
            string descText = string.IsNullOrWhiteSpace(selectedTournament.Description)
                ? string.Empty
                : $"\n\n{selectedTournament.Description}";
            string slotsText = selectedTournament.PlaySlots != null && selectedTournament.PlaySlots.Count > 0
                ? "\n\nPlay Slots:\n" + string.Join("\n", selectedTournament.PlaySlots)
                : string.Empty;
            detailPrizeText.text = $"Entry Fee: ₹{selectedTournament.EntryFee}\nPrize Pool: ₹{Mathf.RoundToInt(selectedTournament.PrizePool)}{regText}\n{startText}{slotsText}{descText}";

            float walletBalance = ReadWalletBalance();
            detailWalletText.text = $"Wallet Balance: ₹{walletBalance:F0}";

            if (IsTournamentPlayable(selectedTournament))
            {
                detailHintText.text = "You are already registered. Match is live now, so you can enter the tournament table.";
                detailPrimaryButton.gameObject.SetActive(true);
                detailPrimaryButton.interactable = true;
                detailPrimaryButtonText.text = "Play Now";
            }
            else if (CanRegisterFromDetails(selectedTournament))
            {
                detailHintText.text = "Tap register to join this tournament. Entry fee will be deducted from your wallet instantly.";
                detailPrimaryButton.gameObject.SetActive(true);
                detailPrimaryButton.interactable = walletBalance >= selectedTournament.EntryFee;
                detailPrimaryButtonText.text = $"Register - ₹{selectedTournament.EntryFee}";
            }
            else if (string.Equals(selectedTournament.Status, "registration_open", StringComparison.OrdinalIgnoreCase)
                && !string.IsNullOrWhiteSpace(selectedTournament.EntryUuid))
            {
                detailHintText.text = "You are already registered. Wait for the tournament to go live, then the Play button will open your table.";
                detailPrimaryButton.gameObject.SetActive(true);
                detailPrimaryButton.interactable = false;
                detailPrimaryButtonText.text = "Registered";
            }
            else if (selectedTournament.JoinedPlayers >= selectedTournament.MaxPlayers)
            {
                detailHintText.text = "Tournament is full right now. You can still review details here.";
                detailPrimaryButton.gameObject.SetActive(true);
                detailPrimaryButton.interactable = false;
                detailPrimaryButtonText.text = "Tournament Full";
            }
            else
            {
                detailHintText.text = "Registration is not available right now. You can review tournament details from this popup.";
                detailPrimaryButton.gameObject.SetActive(true);
                detailPrimaryButton.interactable = false;
                detailPrimaryButtonText.text = "Not Available";
            }
        }

        private void OnDetailPrimaryButtonPressed()
        {
            if (selectedTournament == null)
            {
                return;
            }

            if (IsTournamentPlayable(selectedTournament))
            {
                detailPopup.SetActive(false);
                JoinTournament(selectedTournament);
                return;
            }

            if (CanRegisterFromDetails(selectedTournament))
            {
                RegisterTournament(selectedTournament);
            }
        }

        private Button MakeHeaderBtn(Transform parent, string label, Color32 color)
        {
            var go = new GameObject(label + "Btn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Button));
            go.transform.SetParent(parent, false);
            var img = go.GetComponent<Image>();
            img.color = color; img.raycastTarget = true;
            var btn = go.GetComponent<Button>();
            btn.targetGraphic = img;
            var t = CreateLabel(go.transform, label, 32, FontStyle.Bold, Color.white);
            t.alignment = TextAnchor.MiddleCenter; t.raycastTarget = false;
            var r = t.GetComponent<RectTransform>();
            r.anchorMin = Vector2.zero; r.anchorMax = Vector2.one;
            r.offsetMin = r.offsetMax = Vector2.zero;
            return btn;
        }

        private void ClearRows()
        {
            foreach (GameObject row in runtimeRows)
            {
                if (row != null)
                {
                    Destroy(row);
                }
            }

            runtimeRows.Clear();
        }

        private void SetStatus(string message)
        {
            if (statusText != null)
            {
                statusText.text = message;
            }

            RefreshScrollLayout();
        }

        private void RefreshScrollLayout()
        {
            if (listContent == null)
            {
                return;
            }

            Canvas.ForceUpdateCanvases();
            LayoutRebuilder.ForceRebuildLayoutImmediate(listContent);
            Canvas.ForceUpdateCanvases();
            LayoutRebuilder.ForceRebuildLayoutImmediate(listContent);
            if (tournamentScroll != null)
            {
                tournamentScroll.verticalNormalizedPosition = 1f;
            }
        }

        private Button CreateButton(Transform parent, string label, Color32 color)
        {
            GameObject buttonObject = new GameObject(label + "Button", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Button), typeof(LayoutElement));
            buttonObject.transform.SetParent(parent, false);

            Image image = buttonObject.GetComponent<Image>();
            image.color = color;
            image.raycastTarget = true;

            Button button = buttonObject.GetComponent<Button>();
            button.targetGraphic = image;
            ColorBlock colors = button.colors;
            colors.normalColor = color;
            colors.highlightedColor = new Color32(
                (byte)Mathf.Clamp(color.r + 18, 0, 255),
                (byte)Mathf.Clamp(color.g + 18, 0, 255),
                (byte)Mathf.Clamp(color.b + 18, 0, 255),
                255
            );
            colors.pressedColor = new Color32(
                (byte)Mathf.Clamp(color.r - 18, 0, 255),
                (byte)Mathf.Clamp(color.g - 18, 0, 255),
                (byte)Mathf.Clamp(color.b - 18, 0, 255),
                255
            );
            button.colors = colors;

            LayoutElement layout = buttonObject.GetComponent<LayoutElement>();
            layout.preferredHeight = 72f;
            layout.minHeight = 60f;
            layout.flexibleWidth = 1f;

            Text labelText = CreateLabel(buttonObject.transform, label, 34, FontStyle.Bold, Color.white);
            labelText.alignment = TextAnchor.MiddleCenter;
            labelText.raycastTarget = false;
            RectTransform labelRect = labelText.GetComponent<RectTransform>();
            labelRect.anchorMin = Vector2.zero;
            labelRect.anchorMax = Vector2.one;
            labelRect.offsetMin = Vector2.zero;
            labelRect.offsetMax = Vector2.zero;

            return button;
        }

        private Text CreateLabel(Transform parent, string value, int fontSize, FontStyle fontStyle, Color color)
        {
            GameObject labelObject = new GameObject("Label", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            labelObject.transform.SetParent(parent, false);

            Text label = labelObject.GetComponent<Text>();
            label.font = GetRuntimeFont();
            label.text = value;
            label.fontSize = fontSize;
            label.fontStyle = fontStyle;
            label.color = color;
            label.lineSpacing = 1.1f;
            label.horizontalOverflow = HorizontalWrapMode.Wrap;
            label.verticalOverflow = VerticalWrapMode.Overflow;
            label.alignment = TextAnchor.MiddleLeft;

            RectTransform rect = label.GetComponent<RectTransform>();
            rect.sizeDelta = new Vector2(0f, fontSize + 24f);
            return label;
        }

        private void ConfigureDetailBlockLabel(Text label, float preferredHeight, bool multiline)
        {
            if (label == null)
            {
                return;
            }

            label.alignment = TextAnchor.UpperLeft;
            label.horizontalOverflow = HorizontalWrapMode.Wrap;
            label.verticalOverflow = VerticalWrapMode.Overflow;
            label.resizeTextForBestFit = false;

            LayoutElement layout = label.gameObject.GetComponent<LayoutElement>();
            if (layout == null)
            {
                layout = label.gameObject.AddComponent<LayoutElement>();
            }

            layout.preferredHeight = preferredHeight;
            layout.minHeight = preferredHeight;
            layout.flexibleHeight = 0f;
        }

        private Font GetRuntimeFont()
        {
            if (runtimeFont != null)
            {
                return runtimeFont;
            }

            runtimeFont = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            return runtimeFont;
        }

        private string ExtractEntryUuid(string json)
        {
            try
            {
                JToken root = JToken.Parse(json);
                string direct = ReadString(root, "entry_uuid", "tournament_entry_uuid", "joined_entry_uuid");
                if (!string.IsNullOrWhiteSpace(direct))
                {
                    return direct;
                }

                string nested = ReadNestedString(root, "data", "entry_uuid");
                if (!string.IsNullOrWhiteSpace(nested))
                {
                    return nested;
                }

                nested = ReadNestedString(root, "data", "tournament_entry_uuid");
                if (!string.IsNullOrWhiteSpace(nested))
                {
                    return nested;
                }

                nested = ReadNestedString(root, "data", "joined_entry_uuid");
                if (!string.IsNullOrWhiteSpace(nested))
                {
                    return nested;
                }

                JToken dataNode = root["data"];
                if (dataNode is JArray dataArray && dataArray.Count > 0)
                {
                    string firstUuid = ReadString(dataArray[0], "uuid", "entry_uuid", "tournament_entry_uuid");
                    if (!string.IsNullOrWhiteSpace(firstUuid))
                    {
                        return firstUuid;
                    }
                }

                if (dataNode?["data"] is JArray nestedArray && nestedArray.Count > 0)
                {
                    string nestedUuid = ReadString(nestedArray[0], "uuid", "entry_uuid", "tournament_entry_uuid");
                    if (!string.IsNullOrWhiteSpace(nestedUuid))
                    {
                        return nestedUuid;
                    }
                }

                JToken entryNode = root["data"]?["entry"] ?? root["entry"];
                string entryStr = ReadString(entryNode, "uuid", "entry_uuid", "tournament_entry_uuid");
                if (!string.IsNullOrWhiteSpace(entryStr)) return entryStr;

                // Fallback: use data.id (integer registration ID) as the entry identifier
                JToken dataId = root["data"]?["id"];
                if (dataId != null) return dataId.ToString();

                return null;
            }
            catch
            {
                return null;
            }
        }

        private string ExtractErrorMessage(string json)
        {
            if (string.IsNullOrWhiteSpace(json))
            {
                return null;
            }

            try
            {
                JToken root = JToken.Parse(json);
                return ReadString(root, "message", "error", "detail");
            }
            catch
            {
                return null;
            }
        }

        private void UpdateWalletFromRegistrationResponse(string json)
        {
            if (string.IsNullOrWhiteSpace(json))
            {
                return;
            }

            try
            {
                JToken root = JToken.Parse(json);
                string walletText = ReadString(root, "new_balance", "balance");
                if (string.IsNullOrWhiteSpace(walletText))
                {
                    walletText = ReadNestedString(root, "data", "new_balance");
                }

                if (string.IsNullOrWhiteSpace(walletText))
                {
                    return;
                }

                if (!float.TryParse(walletText, NumberStyles.Float, CultureInfo.InvariantCulture, out float balance))
                {
                    return;
                }

                PlayerPrefs.SetString("wallet", balance.ToString(CultureInfo.InvariantCulture));
                PlayerPrefs.Save();
            }
            catch (Exception ex)
            {
                Debug.LogWarning("Unable to update wallet from tournament response: " + ex.Message);
            }
        }

        private float ReadWalletBalance()
        {
            string walletRaw = Configuration.GetWallet();
            return float.TryParse(walletRaw, NumberStyles.Float, CultureInfo.InvariantCulture, out float balance)
                ? balance
                : 0f;
        }

        private static string ReadString(JToken token, params string[] keys)
        {
            if (token == null)
            {
                return null;
            }

            foreach (string key in keys)
            {
                JToken value = token[key];
                if (value != null && value.Type != JTokenType.Null)
                {
                    string text = value.ToString();
                    if (!string.IsNullOrWhiteSpace(text))
                    {
                        return text;
                    }
                }
            }

            return null;
        }

        private static string ReadNestedString(JToken token, string parentKey, string childKey)
        {
            return ReadString(token?[parentKey], childKey);
        }

        private static int ReadInt(JToken token, params string[] keys)
        {
            string raw = ReadString(token, keys);
            return int.TryParse(raw, out int value) ? value : 0;
        }

        private static int ReadNestedInt(JToken token, string parentKey, string childKey)
        {
            return ReadInt(token?[parentKey], childKey);
        }

        private static bool ReadBool(JToken token, params string[] keys)
        {
            if (token == null)
            {
                return false;
            }

            foreach (string key in keys)
            {
                JToken value = token[key];
                if (value == null || value.Type == JTokenType.Null)
                {
                    continue;
                }

                if (value.Type == JTokenType.Boolean)
                {
                    return value.Value<bool>();
                }

                string raw = value.ToString();
                if (bool.TryParse(raw, out bool boolValue))
                {
                    return boolValue;
                }

                if (int.TryParse(raw, out int intValue))
                {
                    return intValue != 0;
                }
            }

            return false;
        }

        private static float ReadFloat(JToken token, params string[] keys)
        {
            string raw = ReadString(token, keys);
            return float.TryParse(raw, System.Globalization.NumberStyles.Float,
                System.Globalization.CultureInfo.InvariantCulture, out float value) ? value : 0f;
        }

        private static List<string> ReadPlaySlots(JToken slotsToken)
        {
            List<string> result = new List<string>();
            if (slotsToken is not JArray slotsArray)
            {
                return result;
            }

            foreach (JToken slot in slotsArray)
            {
                string label = ReadString(slot, "label") ?? "Slot";
                string startAt = ReadString(slot, "start_at");
                string endAt = ReadString(slot, "end_at");
                string display = label;

                if (DateTime.TryParse(startAt, out DateTime parsedStart) && DateTime.TryParse(endAt, out DateTime parsedEnd))
                {
                    display += $": {parsedStart:dd MMM, hh:mm tt} - {parsedEnd:hh:mm tt}";
                }
                else if (!string.IsNullOrWhiteSpace(startAt) || !string.IsNullOrWhiteSpace(endAt))
                {
                    display += $": {startAt} - {endAt}";
                }

                result.Add(display);
            }

            return result;
        }

        private sealed class LudoTournamentListItem
        {
            public string TournamentUuid;
            public string EntryUuid;
            public string Title;
            public string Status;
            public string StartTime;
            public string RegistrationEndAt;
            public int EntryFee;
            public float PrizePool;
            public int MaxPlayers;
            public int JoinedPlayers;
            public bool CanJoin;
            public string Description;
            public string Format;
            public string Type;
            public List<string> PlaySlots;
        }
    }
}
