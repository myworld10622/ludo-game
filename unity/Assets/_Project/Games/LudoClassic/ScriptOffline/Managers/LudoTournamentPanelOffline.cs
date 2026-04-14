using System;
using System.Collections.Generic;
using System.Globalization;
using System.IO;
using System.Linq;
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
        private GridLayoutGroup tournamentGrid;
        private RectTransform tournamentViewport;
        private GameObject _popupLayer;        // Canvas at 32850 — detail popup layer
        private GameObject _privatePopupLayer; // Canvas at 33000 — private popup layer (separate)
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
            UpdateTournamentGridSizing();
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
            if (dashboard != null)
            {
                dashboard.SetTournamentSideMenuSuppressed(true);
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
            dashboard.SetTournamentSideMenuSuppressed(false);
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
            // both popups are standalone (not children of panelRoot), hide them explicitly
            if (privatePopup != null) privatePopup.SetActive(false);
            if (detailPopup  != null) detailPopup.SetActive(false);
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
            try
            {
                if (privatePopup != null)
                {
                    Destroy(privatePopup);
                    privatePopup      = null;
                    inviteCodeField   = null;
                    invitePasswordField = null;
                    privateStatusText = null;
                }

                EnsurePrivatePopup();

                if (privatePopup == null)
                {
                    Debug.LogError("[Tournament] EnsurePrivatePopup failed — popup is null");
                    return;
                }

                privatePopup.SetActive(true);
                if (inviteCodeField != null)   inviteCodeField.text     = string.Empty;
                if (invitePasswordField != null) invitePasswordField.text = string.Empty;
                if (privateStatusText != null) privateStatusText.text   = string.Empty;
            }
            catch (System.Exception ex)
            {
                Debug.LogError("[Tournament] OpenPrivatePopup error: " + ex);
            }
        }

        private void EnsurePrivatePopup()
        {
            if (privatePopup != null) return;

            // ── Fully independent Screen Space Overlay Canvas ─────────────────
            // Parented to null (scene root) so it is NOT inside any other Canvas.
            // renderMode=ScreenSpaceOverlay + sortingOrder=33500 guarantees it renders
            // on top of everything in the game, regardless of other canvases.
            privatePopup = new GameObject("PrivateJoinPopup",
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster),
                typeof(CanvasRenderer), typeof(Image));
            privatePopup.transform.SetParent(null);  // scene root — NOT inside HomeScreen
            var ppCanvas = privatePopup.GetComponent<Canvas>();
            ppCanvas.renderMode   = RenderMode.ScreenSpaceOverlay;
            ppCanvas.sortingOrder = 33500;
            privatePopup.GetComponent<Image>().color = new Color32(6, 1, 2, 215);

            // ── Copy CanvasScaler from the scene's root canvas so UI scales correctly ──
            // Without this, elements render at raw device pixels (too large on small screens).
            var srcScaler = (dashboard?.dashBordPanal != null)
                ? dashboard.dashBordPanal.GetComponentInParent<CanvasScaler>(true)
                : null;
            if (srcScaler != null)
            {
                var scaler = privatePopup.AddComponent<CanvasScaler>();
                scaler.uiScaleMode          = srcScaler.uiScaleMode;
                scaler.referenceResolution  = srcScaler.referenceResolution;
                scaler.screenMatchMode      = srcScaler.screenMatchMode;
                scaler.matchWidthOrHeight   = srcScaler.matchWidthOrHeight;
                scaler.referencePixelsPerUnit = srcScaler.referencePixelsPerUnit;
            }

            // ── Card — wide portrait layout, centered vertically ─────────────
            // anchorMin.x=0.05 anchorMax.x=0.95 → 90% screen width
            // anchorMin.y=0.15 anchorMax.y=0.85 → 70% screen height
            GameObject card = new GameObject("Card",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup));
            card.transform.SetParent(privatePopup.transform, false);
            card.GetComponent<Image>().color = new Color32(48, 10, 18, 255);
            RectTransform cardRect = card.GetComponent<RectTransform>();
            // Center card and auto-size height to content — no blank space at bottom
            cardRect.anchorMin = new Vector2(0.5f, 0.5f);
            cardRect.anchorMax = new Vector2(0.5f, 0.5f);
            cardRect.pivot     = new Vector2(0.5f, 0.5f);
            cardRect.sizeDelta = new Vector2(0f, 0f); // width set below, height by ContentSizeFitter
            ContentSizeFitter cardFitter = card.AddComponent<ContentSizeFitter>();
            cardFitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
            cardFitter.verticalFit   = ContentSizeFitter.FitMode.PreferredSize;
            // Width: 90% of screen
            cardRect.anchorMin = new Vector2(0.05f, 0.5f);
            cardRect.anchorMax = new Vector2(0.95f, 0.5f);
            cardRect.pivot     = new Vector2(0.5f, 0.5f);
            cardRect.offsetMin = new Vector2(0f, 0f);
            cardRect.offsetMax = new Vector2(0f, 0f);

            VerticalLayoutGroup vl = card.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(52, 52, 40, 40);
            vl.spacing = 24;
            vl.childControlHeight     = true;
            vl.childControlWidth      = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth  = true;

            // ── Top accent strip ─────────────────────────────────────────────
            GameObject accentStrip = new GameObject("AccentStrip",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            accentStrip.transform.SetParent(card.transform, false);
            accentStrip.GetComponent<Image>().color = new Color32(218, 165, 32, 255);
            LayoutElement accentLE = accentStrip.GetComponent<LayoutElement>();
            accentLE.preferredHeight = 7f;
            accentLE.minHeight = 7f;

            // ── Title row: icon label + close button ─────────────────────────
            GameObject titleRow = new GameObject("TitleRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            titleRow.transform.SetParent(card.transform, false);
            titleRow.GetComponent<LayoutElement>().preferredHeight = 130f;
            HorizontalLayoutGroup thl = titleRow.GetComponent<HorizontalLayoutGroup>();
            thl.childControlHeight     = true;
            thl.childControlWidth      = true;
            thl.childForceExpandHeight = false;
            thl.childForceExpandWidth  = false;
            thl.childAlignment         = TextAnchor.MiddleLeft;
            thl.spacing = 18;

            // Icon circle
            GameObject iconCircle = new GameObject("IconCircle",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            iconCircle.transform.SetParent(titleRow.transform, false);
            iconCircle.GetComponent<Image>().color = new Color32(175, 130, 18, 255);
            LayoutElement iconLE = iconCircle.GetComponent<LayoutElement>();
            iconLE.preferredWidth  = 120f;
            iconLE.minWidth        = 120f;
            iconLE.preferredHeight = 120f;
            iconLE.minHeight       = 120f;
            iconLE.flexibleWidth   = 0f;
            Text iconText = CreateLabel(iconCircle.transform, "🔒", 68, FontStyle.Normal, Color.white);
            iconText.alignment = TextAnchor.MiddleCenter;
            iconText.raycastTarget = false;
            RectTransform iconTR = iconText.GetComponent<RectTransform>();
            iconTR.anchorMin = Vector2.zero; iconTR.anchorMax = Vector2.one;
            iconTR.offsetMin = iconTR.offsetMax = Vector2.zero;

            // Title label
            GameObject titleLblGo = new GameObject("Title",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text), typeof(LayoutElement));
            titleLblGo.transform.SetParent(titleRow.transform, false);
            Text titleLbl = titleLblGo.GetComponent<Text>();
            titleLbl.font = GetRuntimeFont();
            titleLbl.text = "Join Private Tournament";
            titleLbl.fontSize = 70;
            titleLbl.fontStyle = FontStyle.Bold;
            titleLbl.color = Color.white;
            titleLbl.horizontalOverflow = HorizontalWrapMode.Wrap;
            titleLbl.verticalOverflow   = VerticalWrapMode.Overflow;
            titleLbl.alignment = TextAnchor.MiddleLeft;
            titleLblGo.GetComponent<LayoutElement>().flexibleWidth = 1f;

            // Close button — large for easy tap
            Button popupCloseBtn = CreateButton(titleRow.transform, "✕", new Color32(205, 38, 58, 255));
            LayoutElement closeLE = popupCloseBtn.GetComponent<LayoutElement>();
            closeLE.preferredWidth  = 120f;
            closeLE.minWidth        = 120f;
            closeLE.preferredHeight = 112f;
            closeLE.minHeight       = 112f;
            closeLE.flexibleWidth   = 0f;
            popupCloseBtn.GetComponentInChildren<Text>().fontSize = 48;
            popupCloseBtn.onClick.AddListener(() => privatePopup.SetActive(false));

            // ── Subtitle / hint line ─────────────────────────────────────────
            Text hintL = CreateLabel(card.transform,
                "Private tournaments require an invite code shared by the organiser.",
                46, FontStyle.Normal, new Color32(218, 175, 180, 200));
            hintL.horizontalOverflow = HorizontalWrapMode.Wrap;
            hintL.alignment = TextAnchor.MiddleLeft;
            LayoutElement hintLE = hintL.gameObject.AddComponent<LayoutElement>();
            hintLE.preferredHeight = 72f;
            hintLE.minHeight = 72f;

            // ── Invite code field with label ─────────────────────────────────
            Text codeLabel = CreateLabel(card.transform,
                "INVITE CODE", 46, FontStyle.Bold, new Color32(218, 165, 32, 255));
            codeLabel.alignment = TextAnchor.MiddleLeft;
            LayoutElement codeLabelLE = codeLabel.gameObject.AddComponent<LayoutElement>();
            codeLabelLE.preferredHeight = 62f;
            codeLabelLE.minHeight = 62f;

            inviteCodeField = CreateInputField(card.transform, "e.g.  YAFWGH");
            LayoutElement codeFieldLE = inviteCodeField.GetComponent<LayoutElement>();
            codeFieldLE.preferredHeight = 120f;
            codeFieldLE.minHeight       = 120f;
            ((Text)inviteCodeField.placeholder).fontSize = 50;
            ((Text)inviteCodeField.placeholder).color = new Color32(210, 165, 170, 200);
            inviteCodeField.textComponent.fontSize = 56;
            inviteCodeField.textComponent.color = new Color32(255, 230, 130, 255);

            // ── Password field with label ────────────────────────────────────
            Text passLabel = CreateLabel(card.transform,
                "PASSWORD  (leave blank if none)", 46, FontStyle.Bold, new Color32(218, 165, 32, 255));
            passLabel.alignment = TextAnchor.MiddleLeft;
            LayoutElement passLabelLE = passLabel.gameObject.AddComponent<LayoutElement>();
            passLabelLE.preferredHeight = 62f;
            passLabelLE.minHeight = 62f;

            invitePasswordField = CreateInputField(card.transform, "optional");
            LayoutElement passFieldLE = invitePasswordField.GetComponent<LayoutElement>();
            passFieldLE.preferredHeight = 120f;
            passFieldLE.minHeight       = 120f;
            ((Text)invitePasswordField.placeholder).fontSize = 50;
            ((Text)invitePasswordField.placeholder).color = new Color32(210, 165, 170, 200);
            invitePasswordField.textComponent.fontSize = 56;
            invitePasswordField.textComponent.color = new Color32(255, 230, 130, 255);
            invitePasswordField.inputType = InputField.InputType.Password;

            // ── Status / error text ──────────────────────────────────────────
            privateStatusText = CreateLabel(card.transform, string.Empty,
                48, FontStyle.Italic, new Color32(255, 110, 110, 255));
            privateStatusText.alignment = TextAnchor.MiddleLeft;
            privateStatusText.horizontalOverflow = HorizontalWrapMode.Wrap;
            LayoutElement statusLE = privateStatusText.gameObject.AddComponent<LayoutElement>();
            statusLE.preferredHeight = 62f;
            statusLE.minHeight = 62f;

            // ── Button row: Confirm (big) + Cancel ───────────────────────────
            GameObject btnRow = new GameObject("BtnRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            btnRow.transform.SetParent(card.transform, false);
            btnRow.GetComponent<LayoutElement>().preferredHeight = 128f;
            HorizontalLayoutGroup hl = btnRow.GetComponent<HorizontalLayoutGroup>();
            hl.spacing                = 24;
            hl.childControlHeight     = true;
            hl.childControlWidth      = true;
            hl.childForceExpandWidth  = true;
            hl.childForceExpandHeight = false;

            Button confirmBtn = CreateButton(btnRow.transform, "🔍  FIND TOURNAMENT", new Color32(175, 130, 18, 255));
            LayoutElement confirmLayout = confirmBtn.GetComponent<LayoutElement>();
            confirmLayout.preferredHeight = 120f;
            confirmLayout.minHeight       = 120f;
            confirmBtn.GetComponentInChildren<Text>().fontSize = 52;
            confirmBtn.onClick.AddListener(ConfirmPrivateJoin);

            Button cancelBtn = CreateButton(btnRow.transform, "CANCEL", new Color32(130, 22, 40, 255));
            LayoutElement cancelLayout = cancelBtn.GetComponent<LayoutElement>();
            cancelLayout.preferredHeight = 120f;
            cancelLayout.minHeight       = 120f;
            cancelLayout.preferredWidth  = 300f;
            cancelLayout.minWidth        = 300f;
            cancelLayout.flexibleWidth   = 0f;
            cancelBtn.GetComponentInChildren<Text>().fontSize = 52;
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
                // keep _popupLayer active — OpenTournamentDetails will show detail popup in it
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
            go.GetComponent<Image>().color = new Color32(72, 12, 24, 255);
            go.GetComponent<LayoutElement>().preferredHeight = 86f;

            InputField field = go.GetComponent<InputField>();
            field.targetGraphic = go.GetComponent<Image>();

            GameObject phGo = new GameObject("Placeholder", typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            phGo.transform.SetParent(go.transform, false);
            Text ph = phGo.GetComponent<Text>();
            ph.font      = GetRuntimeFont();
            ph.text      = placeholder;
            ph.fontSize  = 24;
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
            txt.fontSize  = 24;
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

            // Debug: log first 600 chars of response so we can see actual field names
            Debug.Log("[Tournament] Response (first 600): " + (json?.Length > 600 ? json.Substring(0, 600) : json));

            try
            {
                JToken root = JToken.Parse(json);

                // Try multiple common API response shapes
                JArray items = root["data"] as JArray;                          // { data: [...] }
                if (items == null) items = root["data"]?["data"] as JArray;     // { data: { data: [...] } }
                if (items == null) items = root["data"]?["tournaments"] as JArray; // { data: { tournaments: [...] } }
                if (items == null) items = root["tournaments"] as JArray;        // { tournaments: [...] }
                if (items == null) items = root["list"] as JArray;               // { list: [...] }
                if (items == null) items = root["result"] as JArray;             // { result: [...] }
                if (items == null) items = root["results"] as JArray;            // { results: [...] }
                if (items == null && root is JArray rootArr) items = rootArr;    // plain array

                Debug.Log("[Tournament] Items found: " + (items?.Count.ToString() ?? "NULL"));

                if (items == null || items.Count == 0)
                {
                    string rootKeys = root is JObject ro ? string.Join(", ", ro.Properties().Select(p => p.Name)) : root.Type.ToString();
                    Debug.LogWarning("[Tournament] No items. Root keys: " + rootKeys);
                    SetStatus("No tournaments available right now.");
                    return;
                }

                SetStatus(string.Empty);
                int rowsCreated = 0;
                foreach (JToken item in items)
                {
                    try
                    {
                        var t = ParseTournament(item);
                        Debug.Log($"[Tournament] Parsed uuid={t.TournamentUuid} title={t.Title} status={t.Status}");
                        if (string.IsNullOrWhiteSpace(t.TournamentUuid))
                        {
                            Debug.LogWarning("[Tournament] Skipped row — uuid empty. Item keys: " + (item is JObject io ? string.Join(", ", io.Properties().Select(p => p.Name)) : "?"));
                            continue;
                        }
                        CreateTournamentRow(t);
                        rowsCreated++;
                    }
                    catch (Exception rowEx)
                    {
                        Debug.LogError("[Tournament] Row create failed: " + rowEx);
                    }
                }

                Debug.Log($"[Tournament] Rows created: {rowsCreated}");
                RefreshScrollLayout();
            }
            catch (Exception ex)
            {
                Debug.LogError("[Tournament] Parse failed: " + ex);
                SetStatus("Unable to read tournaments.");
            }
        }

        private LudoTournamentListItem ParseTournament(JToken item)
        {
            string uuid = ReadString(item, "uuid", "tournament_uuid", "id");
            string title = ReadString(item, "name", "title", "tournament_name");
            string status = ReadString(item, "status", "state");
            string startTimeRaw = ReadString(item, "tournament_start_at", "start_at", "start_time", "starts_at", "startAt");
            string entryCloseRaw = ReadString(item, "registration_end_at", "entry_close_at", "entryCloseAt");
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
                StartTime = FormatLocalTimeWithZone(startTimeRaw),
                RegistrationEndAt = FormatLocalTimeWithZone(entryCloseRaw),
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

            // Accent / status color — Ludo King gold/teal premium palette
            Color32 accentColor = tournament.Status == "in_progress"
                ? new Color32(0, 210, 180, 255)      // teal — match live
                : tournament.Status == "registration_open"
                    ? new Color32(218, 165, 32, 255)  // gold — open for registration
                    : new Color32(160, 148, 200, 255); // muted lavender — others

            string statusLabel = tournament.Status.Replace("_", " ").ToUpperInvariant();
            string playersStr = tournament.JoinedPlayers + " / " + tournament.MaxPlayers + " PLAYERS";
            string timingText = string.IsNullOrWhiteSpace(tournament.StartTime)
                ? "Start time will be announced soon"
                : "Starts " + tournament.StartTime;
            string feeValue = tournament.EntryFee > 0 ? "₹" + tournament.EntryFee : "FREE";
            string prizeValue = tournament.PrizePool > 0f ? "₹" + Mathf.RoundToInt(tournament.PrizePool) : "TBA";

            // ── Card — full-width single-column premium layout ─────────────
            var card = new GameObject("TCard",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup), typeof(LayoutElement));
            card.transform.SetParent(listContent, false);
            runtimeRows.Add(card);
            card.GetComponent<Image>().color = new Color32(72, 20, 32, 255);  // lighter red so content is readable
            var cardLE = card.GetComponent<LayoutElement>();
            cardLE.preferredHeight = 460f;
            cardLE.minHeight       = 460f;
            var cardVL = card.GetComponent<VerticalLayoutGroup>();
            cardVL.padding = new RectOffset(0, 0, 0, 0);
            cardVL.spacing = 0;
            cardVL.childControlHeight = true; cardVL.childControlWidth = true;
            cardVL.childForceExpandHeight = false; cardVL.childForceExpandWidth = true;

            // ── Top accent strip (thicker for visibility) ──────────────────
            var accent = new GameObject("AccentTop",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            accent.transform.SetParent(card.transform, false);
            accent.GetComponent<Image>().color = accentColor;
            var acLE = accent.GetComponent<LayoutElement>();
            acLE.preferredHeight = 7f;
            acLE.minHeight = 7f;

            var content = new GameObject("ContentArea",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            content.transform.SetParent(card.transform, false);
            var contentLE = content.GetComponent<LayoutElement>();
            contentLE.preferredHeight = 453f;
            contentLE.minHeight = 453f;
            var contentVL = content.GetComponent<VerticalLayoutGroup>();
            contentVL.padding = new RectOffset(28, 28, 20, 18);
            contentVL.spacing = 14;
            contentVL.childControlHeight = true;
            contentVL.childControlWidth = true;
            contentVL.childForceExpandHeight = false;
            contentVL.childForceExpandWidth = true;
            contentVL.childAlignment = TextAnchor.UpperLeft;

            // ── Tournament name + timing ──────────────────────────────────
            var headerCol = new GameObject("HeaderCol",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            headerCol.transform.SetParent(content.transform, false);
            headerCol.GetComponent<LayoutElement>().preferredHeight = 148f;
            var headerVL = headerCol.GetComponent<VerticalLayoutGroup>();
            headerVL.spacing = 6;
            headerVL.childControlHeight = true;
            headerVL.childControlWidth = true;
            headerVL.childForceExpandHeight = false;
            headerVL.childForceExpandWidth = true;

            var nameL = CreateLabel(headerCol.transform, tournament.Title, 48, FontStyle.Bold, new Color32(255, 255, 255, 255));
            nameL.horizontalOverflow = HorizontalWrapMode.Wrap;
            nameL.alignment = TextAnchor.MiddleLeft;
            var nameLayout = nameL.gameObject.AddComponent<LayoutElement>();
            nameLayout.preferredHeight = 80f;
            nameLayout.minHeight = 80f;

            var timingL = CreateLabel(headerCol.transform, timingText, 34, FontStyle.Normal,
                new Color32(215, 178, 183, 255));
            timingL.alignment = TextAnchor.MiddleLeft;
            var timingLayout = timingL.gameObject.AddComponent<LayoutElement>();
            timingLayout.preferredHeight = 46f;
            timingLayout.minHeight = 46f;

            // ── Status pill + player count on same row ────────────────────
            var badgeRow = new GameObject("BadgeRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            badgeRow.transform.SetParent(content.transform, false);
            badgeRow.GetComponent<LayoutElement>().preferredHeight = 72f;
            var brHL = badgeRow.GetComponent<HorizontalLayoutGroup>();
            brHL.spacing = 16; brHL.childControlHeight = true; brHL.childControlWidth = true;
            brHL.childForceExpandHeight = false; brHL.childForceExpandWidth = false;
            brHL.childAlignment = TextAnchor.MiddleLeft;

            var pillGO = new GameObject("StatusPill",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            pillGO.transform.SetParent(badgeRow.transform, false);
            Color32 pillBg = new Color32(
                (byte)Mathf.Clamp(accentColor.r / 5, 0, 255),
                (byte)Mathf.Clamp(accentColor.g / 5, 0, 255),
                (byte)Mathf.Clamp(accentColor.b / 5, 0, 255), 220);
            pillGO.GetComponent<Image>().color = pillBg;
            var pillHL = pillGO.GetComponent<HorizontalLayoutGroup>();
            pillHL.padding = new RectOffset(18, 18, 8, 8);
            pillHL.childControlHeight = true; pillHL.childControlWidth = true;
            pillHL.childForceExpandHeight = false; pillHL.childForceExpandWidth = false;
            var pillLE = pillGO.GetComponent<LayoutElement>();
            pillLE.preferredHeight = 62f;
            pillLE.minHeight = 62f;
            var statusL = CreateLabel(pillGO.transform, statusLabel, 32, FontStyle.Bold, accentColor);
            statusL.verticalOverflow = VerticalWrapMode.Overflow;
            statusL.alignment = TextAnchor.MiddleCenter;

            var playersL = CreateLabel(badgeRow.transform, playersStr, 38,
                FontStyle.Normal, new Color32(220, 188, 193, 255));
            playersL.verticalOverflow = VerticalWrapMode.Overflow;
            playersL.alignment = TextAnchor.MiddleLeft;

            // ── Entry fee + Prize chips ───────────────────────────────────
            var chipsRow = new GameObject("MetricsRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            chipsRow.transform.SetParent(content.transform, false);
            chipsRow.GetComponent<LayoutElement>().preferredHeight = 96f;
            var chipsHL = chipsRow.GetComponent<HorizontalLayoutGroup>();
            chipsHL.spacing = 16;
            chipsHL.childControlHeight = true;
            chipsHL.childControlWidth = true;
            chipsHL.childForceExpandHeight = false;
            chipsHL.childForceExpandWidth = false;
            chipsHL.childAlignment = TextAnchor.MiddleLeft;

            MakeMetricChip(chipsRow.transform, "ENTRY FEE", feeValue, new Color32(100, 28, 50, 255), new Color32(255, 200, 60, 255));
            MakeMetricChip(chipsRow.transform, "PRIZE POOL", prizeValue, new Color32(28, 72, 52, 255), new Color32(80, 230, 130, 255));

            // ── Footer: note text left, action button right ───────────────
            var footerRow = new GameObject("FooterRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            footerRow.transform.SetParent(content.transform, false);
            footerRow.GetComponent<LayoutElement>().preferredHeight = 112f;
            var footerHL = footerRow.GetComponent<HorizontalLayoutGroup>();
            footerHL.spacing = 18;
            footerHL.childControlHeight = true;
            footerHL.childControlWidth = true;
            footerHL.childForceExpandHeight = false;
            footerHL.childForceExpandWidth = false;
            footerHL.childAlignment = TextAnchor.MiddleLeft;

            var noteWrap = new GameObject("NoteWrap",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            noteWrap.transform.SetParent(footerRow.transform, false);
            var noteWrapLE = noteWrap.GetComponent<LayoutElement>();
            noteWrapLE.flexibleWidth = 1f;
            var noteWrapVL = noteWrap.GetComponent<VerticalLayoutGroup>();
            noteWrapVL.childControlHeight = true;
            noteWrapVL.childControlWidth = true;
            noteWrapVL.childForceExpandHeight = false;
            noteWrapVL.childForceExpandWidth = true;

            var noteL = CreateLabel(noteWrap.transform,
                playable ? "Match is LIVE — tap Play to enter the table now!" : "Tap Details to see prize breakdown, slots and schedule.",
                34,
                FontStyle.Normal,
                playable ? new Color32(60, 215, 110, 255) : new Color32(215, 178, 183, 255));
            noteL.alignment = TextAnchor.MiddleLeft;
            noteL.horizontalOverflow = HorizontalWrapMode.Wrap;

            var actCol = new GameObject("ActCol",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            actCol.transform.SetParent(footerRow.transform, false);
            var actLE = actCol.GetComponent<LayoutElement>();
            actLE.preferredWidth = 290f;
            actLE.minWidth = 290f;
            actLE.flexibleWidth = 0f;
            var actVL = actCol.GetComponent<VerticalLayoutGroup>();
            actVL.spacing = 4;
            actVL.childControlHeight = true;
            actVL.childControlWidth = true;
            actVL.childForceExpandHeight = false;
            actVL.childForceExpandWidth = true;
            actVL.childAlignment = TextAnchor.MiddleCenter;

            Color32 btnColor = playable
                ? new Color32(0, 175, 155, 255)      // teal — play now
                : tournament.Status == "registration_open"
                    ? new Color32(175, 130, 18, 255)  // gold — details/register
                    : new Color32(48, 38, 90, 255);   // indigo — inactive
            string btnLabel = playable ? "▶  PLAY NOW" : "  " + GetDetailsButtonLabel(tournament) + "  ";
            var joinBtn = CreateButton(actCol.transform, btnLabel, btnColor);
            var joinBtnLayout = joinBtn.GetComponent<LayoutElement>();
            joinBtnLayout.preferredHeight = 88f;
            joinBtnLayout.minHeight = 88f;
            joinBtnLayout.preferredWidth = 280f;
            joinBtnLayout.minWidth = 260f;
            joinBtnLayout.flexibleWidth = 0f;
            joinBtn.GetComponentInChildren<Text>().fontSize = 38;
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
            var boxLayout = box.GetComponent<LayoutElement>();
            boxLayout.preferredHeight = 68f;
            boxLayout.minHeight = 68f;
            var vl = box.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(10, 10, 6, 8);
            vl.spacing = 2;
            vl.childControlHeight = true; vl.childControlWidth = true;
            vl.childForceExpandHeight = false; vl.childForceExpandWidth = true;

            var lbl = CreateLabel(box.transform, label, 16, FontStyle.Bold,
                new Color32(160, 180, 210, 255));
            lbl.alignment = TextAnchor.MiddleCenter;
            lbl.verticalOverflow = VerticalWrapMode.Overflow;

            var val = CreateLabel(box.transform, value, 28, FontStyle.Bold, valueColor);
            val.alignment = TextAnchor.MiddleCenter;
            val.verticalOverflow = VerticalWrapMode.Overflow;

            return box;
        }

        private GameObject MakeMetricChip(Transform parent, string label, string value, Color32 bgColor, Color32 valueColor)
        {
            var chip = new GameObject(label + "Chip",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup), typeof(LayoutElement));
            chip.transform.SetParent(parent, false);
            chip.GetComponent<Image>().color = bgColor;
            var chipLayout = chip.GetComponent<LayoutElement>();
            chipLayout.preferredWidth = 290f;
            chipLayout.minWidth = 240f;
            chipLayout.preferredHeight = 92f;
            chipLayout.minHeight = 92f;

            var vl = chip.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(14, 14, 8, 8);
            vl.spacing = 2;
            vl.childControlHeight = true;
            vl.childControlWidth = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth = true;
            vl.childAlignment = TextAnchor.MiddleCenter;

            var labelText = CreateLabel(chip.transform, label, 30, FontStyle.Bold, new Color32(230, 200, 210, 255));
            labelText.alignment = TextAnchor.MiddleCenter;
            labelText.horizontalOverflow = HorizontalWrapMode.Overflow;
            labelText.verticalOverflow = VerticalWrapMode.Overflow;
            var labelLE = labelText.gameObject.AddComponent<LayoutElement>();
            labelLE.preferredHeight = 36f;
            labelLE.minHeight = 36f;

            var valueText = CreateLabel(chip.transform, value, 48, FontStyle.Bold, valueColor);
            valueText.alignment = TextAnchor.MiddleCenter;
            valueText.horizontalOverflow = HorizontalWrapMode.Overflow;
            valueText.verticalOverflow = VerticalWrapMode.Overflow;
            var valueLE = valueText.gameObject.AddComponent<LayoutElement>();
            valueLE.preferredHeight = 52f;
            valueLE.minHeight = 52f;

            return chip;
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

            if (panelRoot    != null) { Destroy(panelRoot);             panelRoot    = null; }
            if (openButton   != null) { Destroy(openButton.gameObject); openButton   = null; }
            if (privatePopup != null) { Destroy(privatePopup);          privatePopup = null; }
            if (detailPopup  != null) { Destroy(detailPopup);           detailPopup  = null; }
            listContent = null; titleText = null; statusText = null;
            closeButton = null; refreshButton = null; joinPrivateBtn = null;
            tournamentScroll = null; _popupLayer = null; _privatePopupLayer = null;
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
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster), typeof(CanvasRenderer), typeof(Image));
            panelRoot.transform.SetParent(parent, false);
            Canvas panelCanvas = panelRoot.GetComponent<Canvas>();
            panelCanvas.overrideSorting = true;
            panelCanvas.sortingOrder = 32600;
            panelRoot.GetComponent<Image>().color = new Color32(14, 3, 7, 255);
            var panelRect = panelRoot.GetComponent<RectTransform>();
            panelRect.anchorMin = Vector2.zero; panelRect.anchorMax = Vector2.one;
            // Add safe inset padding from all edges so nothing is hidden under device corners
            panelRect.offsetMin = new Vector2(16f, 16f);
            panelRect.offsetMax = new Vector2(-16f, -16f);

            // ── HD layered background ──────────────────────────────────────────
            var topGlow = new GameObject("BgTopGlow",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            topGlow.transform.SetParent(panelRoot.transform, false);
            topGlow.GetComponent<Image>().color = new Color32(110, 16, 30, 110);
            var tgR = topGlow.GetComponent<RectTransform>();
            tgR.anchorMin = new Vector2(0f, 0.65f); tgR.anchorMax = Vector2.one;
            tgR.offsetMin = tgR.offsetMax = Vector2.zero;
            var bottomVig = new GameObject("BgBottomVig",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            bottomVig.transform.SetParent(panelRoot.transform, false);
            bottomVig.GetComponent<Image>().color = new Color32(0, 0, 0, 130);
            var bvR = bottomVig.GetComponent<RectTransform>();
            bvR.anchorMin = Vector2.zero; bvR.anchorMax = new Vector2(1f, 0.28f);
            bvR.offsetMin = bvR.offsetMax = Vector2.zero;
            var accentBand = new GameObject("BgAccentBand",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            accentBand.transform.SetParent(panelRoot.transform, false);
            accentBand.GetComponent<Image>().color = new Color32(175, 120, 12, 55);
            var abR2 = accentBand.GetComponent<RectTransform>();
            abR2.anchorMin = new Vector2(0f, 0.91f); abR2.anchorMax = Vector2.one;
            abR2.offsetMin = abR2.offsetMax = Vector2.zero;

            // ── Detail popup layer (sortingOrder 32850) ──────────────────────────
            _popupLayer = new GameObject("DetailPopupLayer",
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster));
            _popupLayer.transform.SetParent(panelRoot.transform, false);
            var plCanvas = _popupLayer.GetComponent<Canvas>();
            plCanvas.overrideSorting = true;
            plCanvas.sortingOrder = 32850;
            var plRect = _popupLayer.GetComponent<RectTransform>();
            plRect.anchorMin = Vector2.zero; plRect.anchorMax = Vector2.one;
            plRect.offsetMin = plRect.offsetMax = Vector2.zero;

            // ── Private popup layer (sortingOrder 33000) — separate from detail layer ──
            _privatePopupLayer = new GameObject("PrivatePopupLayer",
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster));
            _privatePopupLayer.transform.SetParent(panelRoot.transform, false);
            var pplCanvas = _privatePopupLayer.GetComponent<Canvas>();
            pplCanvas.overrideSorting = true;
            pplCanvas.sortingOrder = 33000;
            var pplRect = _privatePopupLayer.GetComponent<RectTransform>();
            pplRect.anchorMin = Vector2.zero; pplRect.anchorMax = Vector2.one;
            pplRect.offsetMin = pplRect.offsetMax = Vector2.zero;

            var headerLayer = new GameObject("HeaderLayer",
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster), typeof(CanvasRenderer));
            headerLayer.transform.SetParent(panelRoot.transform, false);
            var headerLayerRect = headerLayer.GetComponent<RectTransform>();
            headerLayerRect.anchorMin = Vector2.zero;
            headerLayerRect.anchorMax = Vector2.one;
            headerLayerRect.offsetMin = Vector2.zero;
            headerLayerRect.offsetMax = Vector2.zero;
            var headerLayerCanvas = headerLayer.GetComponent<Canvas>();
            headerLayerCanvas.overrideSorting = true;
            headerLayerCanvas.sortingOrder = 32740;

            var touchShield = new GameObject("TopRightTouchShield",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Button));
            touchShield.transform.SetParent(headerLayer.transform, false);
            var shieldImage = touchShield.GetComponent<Image>();
            shieldImage.color = new Color(0f, 0f, 0f, 0.01f);
            shieldImage.raycastTarget = true;
            var shieldRect = touchShield.GetComponent<RectTransform>();
            shieldRect.anchorMin = new Vector2(1f, 1f);
            shieldRect.anchorMax = new Vector2(1f, 1f);
            shieldRect.pivot = new Vector2(1f, 1f);
            shieldRect.anchoredPosition = new Vector2(-8f, -6f);
            shieldRect.sizeDelta = new Vector2(180f, 120f);
            var shieldBtn = touchShield.GetComponent<Button>();
            shieldBtn.transition = Selectable.Transition.None;

            // ── Header bar (full-width, fixed height) ─────────────────────────
            const float headerH = 180f;   // header bar height
            const float actionH = 128f;   // action bar for PRIVATE/CREATE/HISTORY buttons
            const float statusH = 52f;
            const float topTotal = headerH + actionH + statusH;

            var headerBg = new GameObject("HeaderBg",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            headerBg.transform.SetParent(headerLayer.transform, false);
            headerBg.GetComponent<Image>().color = new Color32(62, 12, 22, 255);
            var hbR = headerBg.GetComponent<RectTransform>();
            hbR.anchorMin = new Vector2(0f, 1f); hbR.anchorMax = new Vector2(1f, 1f);
            hbR.pivot = new Vector2(0.5f, 1f);
            hbR.anchoredPosition = Vector2.zero;
            hbR.sizeDelta = new Vector2(0f, headerH);

            // Title text (left side of header)
            titleText = CreateLabel(headerBg.transform, "🏆  TOURNAMENTS", 62, FontStyle.Bold, new Color32(255, 220, 80, 255));
            titleText.alignment = TextAnchor.MiddleLeft;
            var titleR = titleText.GetComponent<RectTransform>();
            titleR.anchorMin = Vector2.zero; titleR.anchorMax = Vector2.one;
            titleR.offsetMin = new Vector2(52f, 8f); titleR.offsetMax = new Vector2(-580f, -8f);

            // Refresh button — centred vertically in header, pulled well away from top-right corner
            refreshButton = MakeHeaderBtn(headerBg.transform, "↺  REFRESH", new Color32(40, 28, 90, 255));
            var rfR = refreshButton.GetComponent<RectTransform>();
            rfR.anchorMin = new Vector2(1f, 0.5f); rfR.anchorMax = new Vector2(1f, 0.5f);
            rfR.pivot = new Vector2(1f, 0.5f);
            rfR.anchoredPosition = new Vector2(-308f, 0f);
            rfR.sizeDelta = new Vector2(270f, 112f);
            refreshButton.onClick.AddListener(RefreshTournaments);

            // Close button — centred vertically, right side of header
            closeButton = MakeHeaderBtn(headerBg.transform, "✕  CLOSE", new Color32(200, 38, 58, 255));
            var cbR = closeButton.GetComponent<RectTransform>();
            cbR.anchorMin = new Vector2(1f, 0.5f); cbR.anchorMax = new Vector2(1f, 0.5f);
            cbR.pivot = new Vector2(1f, 0.5f);
            cbR.anchoredPosition = new Vector2(-28f, 0f);
            cbR.sizeDelta = new Vector2(270f, 112f);
            closeButton.onClick.AddListener(ClosePanel);

            // ── Action bar (below header) ─────────────────────────────────────
            var actionBar = new GameObject("ActionBar",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup));
            actionBar.transform.SetParent(headerLayer.transform, false);
            actionBar.GetComponent<Image>().color = new Color32(42, 8, 16, 255);
            var abR = actionBar.GetComponent<RectTransform>();
            abR.anchorMin = new Vector2(0f, 1f); abR.anchorMax = new Vector2(1f, 1f);
            abR.pivot = new Vector2(0.5f, 1f);
            abR.anchoredPosition = new Vector2(0f, -headerH);
            abR.sizeDelta = new Vector2(0f, actionH);

            var abHL = actionBar.GetComponent<HorizontalLayoutGroup>();
            abHL.padding = new RectOffset(22, 22, 16, 16);
            abHL.spacing = 16;
            abHL.childControlHeight = true; abHL.childControlWidth = true;
            abHL.childForceExpandHeight = true; abHL.childForceExpandWidth = true;

            joinPrivateBtn = CreateButton(actionBar.transform, "🔒  PRIVATE", new Color32(140, 22, 44, 255));
            joinPrivateBtn.GetComponentInChildren<Text>().fontSize = 46;
            joinPrivateBtn.onClick.AddListener(OpenPrivatePopup);

            var createBtn = CreateButton(actionBar.transform, "＋  CREATE", new Color32(185, 140, 20, 255));
            createBtn.GetComponentInChildren<Text>().fontSize = 46;
            createBtn.onClick.AddListener(() => dashboard?.OpenCreateTournamentPanel());

            var histBtn = CreateButton(actionBar.transform, "📋  HISTORY", new Color32(120, 18, 36, 255));
            histBtn.GetComponentInChildren<Text>().fontSize = 46;
            histBtn.onClick.AddListener(() => dashboard?.OpenMyTournamentsPanel());

            // Status text (below action bar)
            statusText = CreateLabel(headerLayer.transform, string.Empty, 30, FontStyle.Normal,
                new Color32(218, 165, 32, 255));
            statusText.alignment = TextAnchor.MiddleCenter;
            var stR = statusText.GetComponent<RectTransform>();
            stR.anchorMin = new Vector2(0f, 1f); stR.anchorMax = new Vector2(1f, 1f);
            stR.pivot = new Vector2(0.5f, 1f);
            stR.anchoredPosition = new Vector2(0f, -(headerH + actionH + 8f));
            stR.sizeDelta = new Vector2(-60f, statusH);

            // ── Scroll view (fills remaining area) ───────────────────────────
            var scrollRoot = new GameObject("ScrollView",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(ScrollRect));
            scrollRoot.transform.SetParent(panelRoot.transform, false);
            scrollRoot.GetComponent<Image>().color = Color.clear;
            var scrollRect = scrollRoot.GetComponent<RectTransform>();
            // From just below the action bar (top offset) to bottom — near full-width for portrait
            scrollRect.anchorMin = new Vector2(0.01f, 0f);
            scrollRect.anchorMax = new Vector2(0.99f, 1f);
            scrollRect.offsetMin = new Vector2(0f, 18f);
            scrollRect.offsetMax = new Vector2(0f, -(topTotal + 12f));

            // Viewport
            var viewport = new GameObject("Viewport",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Mask));
            viewport.transform.SetParent(scrollRoot.transform, false);
            var viewportRect = viewport.GetComponent<RectTransform>();
            viewportRect.anchorMin = Vector2.zero; viewportRect.anchorMax = Vector2.one;
            viewportRect.offsetMin = viewportRect.offsetMax = Vector2.zero;
            tournamentViewport = viewportRect;
            viewport.GetComponent<Image>().color = new Color32(0, 0, 0, 1); // alpha=1: stencil writes, graphic invisible
            viewport.GetComponent<Mask>().showMaskGraphic = false;

            // Content
            var contentRoot = new GameObject("Content",
                typeof(RectTransform), typeof(GridLayoutGroup));
            contentRoot.transform.SetParent(viewport.transform, false);
            listContent = contentRoot.GetComponent<RectTransform>();
            listContent.anchorMin = new Vector2(0f, 1f); listContent.anchorMax = new Vector2(1f, 1f);
            listContent.pivot = new Vector2(0.5f, 1f);
            listContent.anchoredPosition = Vector2.zero;
            listContent.sizeDelta = new Vector2(0f, 0f);

            var contentLayout = contentRoot.GetComponent<GridLayoutGroup>();
            tournamentGrid = contentLayout;
            contentLayout.padding = new RectOffset(16, 16, 18, 24);
            contentLayout.spacing = new Vector2(16f, 24f);
            contentLayout.cellSize = new Vector2(700f, 460f);
            contentLayout.startAxis = GridLayoutGroup.Axis.Horizontal;
            contentLayout.startCorner = GridLayoutGroup.Corner.UpperLeft;
            contentLayout.childAlignment = TextAnchor.UpperCenter;
            contentLayout.constraint = GridLayoutGroup.Constraint.FixedColumnCount;
            contentLayout.constraintCount = 1;

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
            if (detailPopup != null) return;

            // ── Standalone root SSO Canvas — same pattern as privatePopup ─────
            detailPopup = new GameObject("TournamentDetailPopup",
                typeof(RectTransform), typeof(Canvas), typeof(GraphicRaycaster),
                typeof(CanvasRenderer), typeof(Image));
            detailPopup.transform.SetParent(null);
            var dpCanvas = detailPopup.GetComponent<Canvas>();
            dpCanvas.renderMode   = RenderMode.ScreenSpaceOverlay;
            dpCanvas.sortingOrder = 33000;  // below privatePopup (33500)
            detailPopup.GetComponent<Image>().color = new Color32(6, 1, 2, 215);

            // Copy CanvasScaler so UI scales correctly on all devices
            var srcScaler = (dashboard?.dashBordPanal != null)
                ? dashboard.dashBordPanal.GetComponentInParent<CanvasScaler>(true)
                : null;
            if (srcScaler != null)
            {
                var scaler = detailPopup.AddComponent<CanvasScaler>();
                scaler.uiScaleMode            = srcScaler.uiScaleMode;
                scaler.referenceResolution    = srcScaler.referenceResolution;
                scaler.screenMatchMode        = srcScaler.screenMatchMode;
                scaler.matchWidthOrHeight     = srcScaler.matchWidthOrHeight;
                scaler.referencePixelsPerUnit = srcScaler.referencePixelsPerUnit;
            }

            GameObject card = new GameObject("DetailCard", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup));
            card.transform.SetParent(detailPopup.transform, false);
            card.GetComponent<Image>().color = new Color32(48, 10, 18, 255);
            RectTransform cardRect = card.GetComponent<RectTransform>();
            // Centered card, auto-height — no blank space
            cardRect.anchorMin = new Vector2(0.05f, 0.5f);
            cardRect.anchorMax = new Vector2(0.95f, 0.5f);
            cardRect.pivot     = new Vector2(0.5f, 0.5f);
            cardRect.offsetMin = cardRect.offsetMax = Vector2.zero;
            ContentSizeFitter detailCardFitter = card.AddComponent<ContentSizeFitter>();
            detailCardFitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
            detailCardFitter.verticalFit   = ContentSizeFitter.FitMode.PreferredSize;

            VerticalLayoutGroup vl = card.GetComponent<VerticalLayoutGroup>();
            vl.padding = new RectOffset(48, 48, 36, 36);
            vl.spacing = 20;
            vl.childControlHeight     = true;
            vl.childControlWidth      = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth  = true;

            // ── Head row: title + X button ────────────────────────────────────
            GameObject headRow = new GameObject("HeadRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            headRow.transform.SetParent(card.transform, false);
            headRow.GetComponent<LayoutElement>().preferredHeight = 130f;
            HorizontalLayoutGroup headLayout = headRow.GetComponent<HorizontalLayoutGroup>();
            headLayout.spacing                = 14;
            headLayout.childControlHeight     = true;
            headLayout.childControlWidth      = true;
            headLayout.childForceExpandHeight = false;
            headLayout.childForceExpandWidth  = false;
            headLayout.childAlignment         = TextAnchor.MiddleCenter;

            GameObject titleWrap = new GameObject("TitleWrap",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            titleWrap.transform.SetParent(headRow.transform, false);
            titleWrap.GetComponent<LayoutElement>().flexibleWidth = 1f;
            VerticalLayoutGroup titleVL = titleWrap.GetComponent<VerticalLayoutGroup>();
            titleVL.spacing               = 6;
            titleVL.childControlHeight    = true;
            titleVL.childControlWidth     = true;
            titleVL.childForceExpandHeight = false;
            titleVL.childForceExpandWidth  = true;

            detailTitleText = CreateLabel(titleWrap.transform, "Tournament", 72, FontStyle.Bold, Color.white);
            detailTitleText.alignment          = TextAnchor.MiddleCenter;
            detailTitleText.horizontalOverflow = HorizontalWrapMode.Wrap;
            detailMetaText  = CreateLabel(titleWrap.transform, string.Empty, 48, FontStyle.Normal, new Color32(215, 178, 183, 255));
            detailMetaText.alignment           = TextAnchor.MiddleCenter;

            detailCloseButton = CreateButton(headRow.transform, "✕", new Color32(205, 38, 58, 255));
            LayoutElement closeLE = detailCloseButton.GetComponent<LayoutElement>();
            closeLE.preferredWidth = 120f; closeLE.minWidth  = 120f;
            closeLE.preferredHeight = 120f; closeLE.minHeight = 120f;
            closeLE.flexibleWidth  = 0f;
            detailCloseButton.GetComponent<Image>().color = new Color32(205, 38, 58, 255);
            detailCloseButton.GetComponentInChildren<Text>().fontSize = 52;
            detailCloseButton.onClick.AddListener(() => detailPopup.SetActive(false));

            // ── Stats panel: status + wallet ──────────────────────────────────
            GameObject statsPanel = new GameObject("StatsPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup), typeof(LayoutElement));
            statsPanel.transform.SetParent(card.transform, false);
            statsPanel.GetComponent<Image>().color = new Color32(72, 16, 28, 255);
            statsPanel.GetComponent<LayoutElement>().preferredHeight = 210f;
            VerticalLayoutGroup statsVL = statsPanel.GetComponent<VerticalLayoutGroup>();
            statsVL.padding               = new RectOffset(24, 24, 20, 20);
            statsVL.spacing               = 10;
            statsVL.childControlHeight    = true;
            statsVL.childControlWidth     = true;
            statsVL.childForceExpandHeight = false;
            statsVL.childForceExpandWidth  = true;
            detailStatsText  = CreateLabel(statsPanel.transform, string.Empty, 54, FontStyle.Bold, new Color32(218, 165, 32, 255));
            detailWalletText = CreateLabel(statsPanel.transform, string.Empty, 48, FontStyle.Bold, new Color32(120, 220, 160, 255));
            detailStatsText.alignment  = TextAnchor.MiddleCenter;
            detailWalletText.alignment = TextAnchor.MiddleCenter;
            ConfigureDetailBlockLabel(detailStatsText,  100f, false);
            ConfigureDetailBlockLabel(detailWalletText,  82f, false);

            // ── Info panel: prize / entry details ─────────────────────────────
            GameObject infoPanel = new GameObject("InfoPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup), typeof(LayoutElement));
            infoPanel.transform.SetParent(card.transform, false);
            infoPanel.GetComponent<Image>().color = new Color32(42, 7, 16, 255);
            var infoLE = infoPanel.GetComponent<LayoutElement>();
            infoLE.preferredHeight = 320f;
            infoLE.minHeight       = 260f;
            VerticalLayoutGroup infoVL = infoPanel.GetComponent<VerticalLayoutGroup>();
            infoVL.padding               = new RectOffset(28, 28, 22, 22);
            infoVL.spacing               = 0;
            infoVL.childControlHeight    = true;
            infoVL.childControlWidth     = true;
            infoVL.childForceExpandHeight = true;
            infoVL.childForceExpandWidth  = true;
            detailPrizeText = CreateLabel(infoPanel.transform, string.Empty, 48, FontStyle.Normal, Color.white);
            detailPrizeText.lineSpacing        = 1.4f;
            detailPrizeText.alignment          = TextAnchor.UpperLeft;
            detailPrizeText.horizontalOverflow = HorizontalWrapMode.Wrap;
            detailPrizeText.verticalOverflow   = VerticalWrapMode.Overflow;

            // ── Hint panel: green banner ──────────────────────────────────────
            GameObject hintPanel = new GameObject("HintPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(VerticalLayoutGroup), typeof(LayoutElement));
            hintPanel.transform.SetParent(card.transform, false);
            hintPanel.GetComponent<Image>().color = new Color32(24, 58, 46, 255);
            hintPanel.GetComponent<LayoutElement>().preferredHeight = 160f;
            VerticalLayoutGroup hintVL = hintPanel.GetComponent<VerticalLayoutGroup>();
            hintVL.padding               = new RectOffset(24, 24, 20, 20);
            hintVL.childControlHeight    = true;
            hintVL.childControlWidth     = true;
            hintVL.childForceExpandHeight = true;
            hintVL.childForceExpandWidth  = true;
            detailHintText = CreateLabel(hintPanel.transform, string.Empty, 50, FontStyle.Italic, new Color32(216, 244, 232, 255));
            detailHintText.alignment          = TextAnchor.MiddleCenter;
            detailHintText.horizontalOverflow = HorizontalWrapMode.Wrap;
            detailHintText.verticalOverflow   = VerticalWrapMode.Overflow;

            // ── Button row ────────────────────────────────────────────────────
            GameObject buttonRow = new GameObject("ButtonRow",
                typeof(RectTransform), typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            buttonRow.transform.SetParent(card.transform, false);
            buttonRow.GetComponent<LayoutElement>().preferredHeight = 128f;
            HorizontalLayoutGroup btnLayout = buttonRow.GetComponent<HorizontalLayoutGroup>();
            btnLayout.spacing                = 20;
            btnLayout.childControlHeight     = true;
            btnLayout.childControlWidth      = true;
            btnLayout.childForceExpandHeight = false;
            btnLayout.childForceExpandWidth  = false;
            btnLayout.childAlignment         = TextAnchor.MiddleCenter;

            detailPrimaryButton     = CreateButton(buttonRow.transform, "Register", new Color32(175, 130, 18, 255));
            detailPrimaryButtonText = detailPrimaryButton.GetComponentInChildren<Text>();
            detailPrimaryButtonText.fontSize = 52;
            LayoutElement primaryLE = detailPrimaryButton.GetComponent<LayoutElement>();
            primaryLE.preferredWidth = 400f; primaryLE.minWidth      = 320f;
            primaryLE.preferredHeight = 118f; primaryLE.flexibleWidth = 0f;
            detailPrimaryButton.onClick.AddListener(OnDetailPrimaryButtonPressed);

            Button secondaryCloseButton = CreateButton(buttonRow.transform, "Close", new Color32(130, 22, 40, 255));
            secondaryCloseButton.GetComponentInChildren<Text>().fontSize = 52;
            LayoutElement secondaryLE = secondaryCloseButton.GetComponent<LayoutElement>();
            secondaryLE.preferredWidth = 290f; secondaryLE.minWidth      = 240f;
            secondaryLE.preferredHeight = 118f; secondaryLE.flexibleWidth = 0f;
            secondaryCloseButton.onClick.AddListener(() => detailPopup.SetActive(false));

            detailPopup.SetActive(false);
        }

        private void OpenTournamentDetails(LudoTournamentListItem tournament)
        {
            if (tournament == null) return;
            try
            {
                EnsureDetailPopup();
                if (detailPopup == null) { Debug.LogError("[Tournament] detail popup null"); return; }
                selectedTournament = tournament;
                detailPopup.SetActive(true);
                RefreshDetailPopupState();
            }
            catch (Exception ex)
            {
                Debug.LogError("[Tournament] OpenTournamentDetails error: " + ex);
            }
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
            ColorBlock cb = btn.colors;
            cb.normalColor    = color;
            cb.highlightedColor = new Color32((byte)Mathf.Clamp(color.r + 22, 0, 255), (byte)Mathf.Clamp(color.g + 22, 0, 255), (byte)Mathf.Clamp(color.b + 22, 0, 255), 255);
            cb.pressedColor   = new Color32((byte)Mathf.Clamp(color.r - 22, 0, 255), (byte)Mathf.Clamp(color.g - 22, 0, 255), (byte)Mathf.Clamp(color.b - 22, 0, 255), 255);
            btn.colors = cb;
            var t = CreateLabel(go.transform, label, 36, FontStyle.Bold, Color.white);
            t.alignment = TextAnchor.MiddleCenter; t.raycastTarget = false;
            var r = t.GetComponent<RectTransform>();
            r.anchorMin = Vector2.zero; r.anchorMax = Vector2.one;
            r.offsetMin = new Vector2(12f, 0f);
            r.offsetMax = new Vector2(-12f, 0f);
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

            // Force canvas first so viewport has correct size, THEN update grid sizing
            Canvas.ForceUpdateCanvases();
            UpdateTournamentGridSizing();
            LayoutRebuilder.ForceRebuildLayoutImmediate(listContent);
            Canvas.ForceUpdateCanvases();
            LayoutRebuilder.ForceRebuildLayoutImmediate(listContent);
            if (tournamentScroll != null)
            {
                tournamentScroll.verticalNormalizedPosition = 1f;
            }
        }

        private void UpdateTournamentGridSizing()
        {
            if (tournamentGrid == null || tournamentViewport == null || listContent == null)
            {
                return;
            }

            float viewportWidth = tournamentViewport.rect.width;
            if (viewportWidth <= 0f)
            {
                return;
            }

            float viewportHeight = tournamentViewport.rect.height;
            // Landscape if width > height (game screen is always landscape)
            bool isLandscape = viewportWidth > viewportHeight;

            if (isLandscape)
            {
                // 2 cards per row in landscape — wide cards with comfortable height
                tournamentGrid.constraintCount = 2;
                float spacing = tournamentGrid.spacing.x;
                float usableWidth = viewportWidth - tournamentGrid.padding.left - tournamentGrid.padding.right - spacing;
                float cardWidth = Mathf.Max(usableWidth / 2f, 200f);
                listContent.SetSizeWithCurrentAnchors(RectTransform.Axis.Horizontal, viewportWidth);
                tournamentGrid.cellSize = new Vector2(cardWidth, 460f);
            }
            else
            {
                // Portrait: single column
                tournamentGrid.constraintCount = 1;
                float usableWidth = viewportWidth - tournamentGrid.padding.left - tournamentGrid.padding.right;
                float cardWidth = Mathf.Max(usableWidth, 200f);
                listContent.SetSizeWithCurrentAnchors(RectTransform.Axis.Horizontal, viewportWidth);
                tournamentGrid.cellSize = new Vector2(cardWidth, 480f);
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
            layout.preferredHeight = 76f;
            layout.minHeight = 66f;
            layout.flexibleWidth = 1f;

            Text labelText = CreateLabel(buttonObject.transform, label, 28, FontStyle.Bold, Color.white);
            labelText.alignment = TextAnchor.MiddleCenter;
            labelText.raycastTarget = false;
            RectTransform labelRect = labelText.GetComponent<RectTransform>();
            labelRect.anchorMin = Vector2.zero;
            labelRect.anchorMax = Vector2.one;
            labelRect.offsetMin = new Vector2(14f, 8f);
            labelRect.offsetMax = new Vector2(-14f, -8f);

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

                string localStart = FormatLocalTimeWithZone(startAt, includeDate: false);
                string localEnd = FormatLocalTimeWithZone(endAt, includeDate: false);
                if (!string.IsNullOrWhiteSpace(localStart) && !string.IsNullOrWhiteSpace(localEnd))
                {
                    display += $": {localStart} - {localEnd}";
                }
                else if (!string.IsNullOrWhiteSpace(startAt) || !string.IsNullOrWhiteSpace(endAt))
                {
                    display += $": {startAt} - {endAt}";
                }

                result.Add(display);
            }

            return result;
        }

        private static string FormatLocalTimeWithZone(string iso, bool includeDate = true)
        {
            if (string.IsNullOrWhiteSpace(iso)) return string.Empty;

            if (DateTimeOffset.TryParse(
                    iso,
                    CultureInfo.InvariantCulture,
                    DateTimeStyles.AssumeUniversal | DateTimeStyles.AdjustToUniversal,
                    out DateTimeOffset dto))
            {
                DateTimeOffset local = dto.ToLocalTime();
                TimeSpan offset = TimeZoneInfo.Local.GetUtcOffset(local.DateTime);
                string tz = FormatOffset(offset);
                string fmt = includeDate ? "dd MMM yyyy, hh:mm tt" : "hh:mm tt";
                return $"{local:fmt} ({tz})";
            }

            if (DateTime.TryParse(iso, out DateTime dt))
            {
                DateTime local = dt.Kind == DateTimeKind.Utc ? dt.ToLocalTime() : dt;
                TimeSpan offset = TimeZoneInfo.Local.GetUtcOffset(local);
                string tz = FormatOffset(offset);
                string fmt = includeDate ? "dd MMM yyyy, hh:mm tt" : "hh:mm tt";
                return $"{local.ToString(fmt)} ({tz})";
            }

            return iso;
        }

        private static string FormatOffset(TimeSpan offset)
        {
            string sign = offset < TimeSpan.Zero ? "-" : "+";
            offset = offset.Duration();
            return $"GMT{sign}{offset.Hours:00}:{offset.Minutes:00}";
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
