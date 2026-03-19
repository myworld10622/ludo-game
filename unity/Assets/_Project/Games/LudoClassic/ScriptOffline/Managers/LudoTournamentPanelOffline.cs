using System;
using System.Collections.Generic;
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
        private ScrollRect tournamentScroll;
        private bool isLoading;
        private bool hasBuiltUi;
        private Font runtimeFont;

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

        public void ClosePanel()
        {
            if (panelRoot != null)
            {
                panelRoot.SetActive(false);
            }

            if (dashboard != null && dashboard.lobbySelectPanal != null)
            {
                dashboard.lobbySelectPanal.SetActive(true);
            }
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

        private async System.Threading.Tasks.Task<string> JoinTournamentAsync(string tournamentUuid)
        {
            string joinUrl = Configuration.LudoTournamentInfoUrl + tournamentUuid + "/join";
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
            int entryFee = ReadInt(item, "entry_fee", "entryFee");
            int maxPlayers = ReadInt(item, "max_total_entries", "max_players", "maxPlayers");
            int joinedPlayers = ReadInt(item, "current_active_entries", "current_total_entries", "joined_players", "current_players", "currentPlayers");
            string entryUuid = ReadNestedString(item, "entry", "uuid");
            bool canJoin = ReadBool(item, "can_join", "canJoin");

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
                EntryFee = entryFee,
                MaxPlayers = Mathf.Max(2, maxPlayers == 0 ? 2 : maxPlayers),
                JoinedPlayers = joinedPlayers,
                EntryUuid = entryUuid,
                CanJoin = canJoin,
            };
        }

        private void CreateTournamentRow(LudoTournamentListItem tournament)
        {
            if (string.IsNullOrWhiteSpace(tournament.TournamentUuid))
            {
                return;
            }

            bool canJoin = IsTournamentJoinable(tournament);

            GameObject row = new GameObject("TournamentRow", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            row.transform.SetParent(listContent, false);
            runtimeRows.Add(row);

            Image rowImage = row.GetComponent<Image>();
            rowImage.color = new Color32(24, 36, 54, 235);

            VerticalLayoutGroup rowLayout = row.GetComponent<VerticalLayoutGroup>();
            rowLayout.padding = new RectOffset(28, 28, 24, 24);
            rowLayout.spacing = 16;
            rowLayout.childControlHeight = true;
            rowLayout.childControlWidth = true;
            rowLayout.childForceExpandHeight = false;
            rowLayout.childForceExpandWidth = true;

            LayoutElement layoutElement = row.GetComponent<LayoutElement>();
            layoutElement.preferredHeight = 360f;

            CreateLabel(row.transform, tournament.Title, 58, FontStyle.Bold, Color.white);
            CreateLabel(
                row.transform,
                $"Entry Fee: {tournament.EntryFee}  |  Players: {tournament.JoinedPlayers}/{tournament.MaxPlayers}",
                46,
                FontStyle.Normal,
                new Color32(226, 232, 240, 255)
            );
            CreateLabel(
                row.transform,
                $"Status: {tournament.Status}{(string.IsNullOrWhiteSpace(tournament.StartTime) ? string.Empty : "  |  Starts: " + tournament.StartTime)}",
                42,
                FontStyle.Italic,
                new Color32(255, 210, 90, 255)
            );

            Button joinButton = CreateButton(
                row.transform,
                canJoin
                    ? (string.IsNullOrWhiteSpace(tournament.EntryUuid) ? "Join Tournament" : "Play Tournament")
                    : "Entries Closed",
                canJoin ? new Color32(227, 165, 55, 255) : new Color32(110, 120, 140, 255)
            );
            RectTransform joinRect = joinButton.GetComponent<RectTransform>();
            joinRect.sizeDelta = new Vector2(0f, 110f);
            LayoutElement joinLayout = joinButton.GetComponent<LayoutElement>();
            if (joinLayout == null)
            {
                joinLayout = joinButton.gameObject.AddComponent<LayoutElement>();
            }
            joinLayout.preferredHeight = 110f;
            joinLayout.minHeight = 110f;
            joinLayout.flexibleWidth = 1f;
            joinButton.interactable = canJoin;
            if (canJoin)
            {
                joinButton.onClick.AddListener(() => JoinTournament(tournament));
            }
        }

        private bool IsTournamentJoinable(LudoTournamentListItem tournament)
        {
            return tournament != null && tournament.CanJoin;
        }

        private void EnsureRuntimeUi()
        {
            if (hasBuiltUi)
            {
                return;
            }

            if (dashboard == null)
            {
                dashboard = GetComponent<DashBoardManagerOffline>();
            }

            Transform parent = dashboard != null && dashboard.dashBordPanal != null
                ? dashboard.dashBordPanal.transform
                : transform;

            openButton = CreateButton(parent, "Tournaments", new Color32(200, 112, 44, 255));
            RectTransform openRect = openButton.GetComponent<RectTransform>();
            openRect.anchorMin = new Vector2(0.5f, 1f);
            openRect.anchorMax = new Vector2(0.5f, 1f);
            openRect.pivot = new Vector2(0.5f, 1f);
            openRect.anchoredPosition = new Vector2(0f, -175f);
            openRect.sizeDelta = new Vector2(360f, 110f);
            openButton.onClick.AddListener(OpenPanel);
            openButton.transform.SetAsLastSibling();
            openButton.GetComponent<Image>().raycastTarget = true;

            panelRoot = new GameObject("TournamentPanel", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            panelRoot.transform.SetParent(parent, false);
            Image panelImage = panelRoot.GetComponent<Image>();
            panelImage.color = new Color32(9, 18, 31, 245);
            RectTransform panelRect = panelRoot.GetComponent<RectTransform>();
            panelRect.anchorMin = new Vector2(0.04f, 0.04f);
            panelRect.anchorMax = new Vector2(0.96f, 0.96f);
            panelRect.offsetMin = Vector2.zero;
            panelRect.offsetMax = Vector2.zero;

            GameObject header = new GameObject("Header", typeof(RectTransform), typeof(VerticalLayoutGroup));
            header.transform.SetParent(panelRoot.transform, false);
            RectTransform headerRect = header.GetComponent<RectTransform>();
            headerRect.anchorMin = new Vector2(0f, 1f);
            headerRect.anchorMax = new Vector2(1f, 1f);
            headerRect.pivot = new Vector2(0.5f, 1f);
            headerRect.anchoredPosition = new Vector2(0f, -24f);
            headerRect.sizeDelta = new Vector2(0f, 220f);

            titleText = CreateLabel(header.transform, "Ludo Tournaments", 68, FontStyle.Bold, Color.white);
            statusText = CreateLabel(header.transform, string.Empty, 40, FontStyle.Normal, new Color32(255, 210, 90, 255));

            closeButton = CreateButton(panelRoot.transform, "Close", new Color32(160, 47, 47, 255));
            RectTransform closeRect = closeButton.GetComponent<RectTransform>();
            closeRect.anchorMin = new Vector2(1f, 1f);
            closeRect.anchorMax = new Vector2(1f, 1f);
            closeRect.pivot = new Vector2(1f, 1f);
            closeRect.anchoredPosition = new Vector2(-26f, -24f);
            closeRect.sizeDelta = new Vector2(280f, 96f);
            closeButton.onClick.AddListener(ClosePanel);

            refreshButton = CreateButton(panelRoot.transform, "Refresh", new Color32(39, 116, 155, 255));
            RectTransform refreshRect = refreshButton.GetComponent<RectTransform>();
            refreshRect.anchorMin = new Vector2(1f, 1f);
            refreshRect.anchorMax = new Vector2(1f, 1f);
            refreshRect.pivot = new Vector2(1f, 1f);
            refreshRect.anchoredPosition = new Vector2(-320f, -24f);
            refreshRect.sizeDelta = new Vector2(280f, 96f);
            refreshButton.onClick.AddListener(RefreshTournaments);

            GameObject scrollRoot = new GameObject("ScrollView", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Mask), typeof(ScrollRect));
            scrollRoot.transform.SetParent(panelRoot.transform, false);
            RectTransform scrollRect = scrollRoot.GetComponent<RectTransform>();
            scrollRect.anchorMin = new Vector2(0.04f, 0.06f);
            scrollRect.anchorMax = new Vector2(0.96f, 0.78f);
            scrollRect.offsetMin = Vector2.zero;
            scrollRect.offsetMax = Vector2.zero;

            Image scrollImage = scrollRoot.GetComponent<Image>();
            scrollImage.color = new Color32(13, 24, 40, 215);
            scrollRoot.GetComponent<Mask>().showMaskGraphic = false;

            GameObject viewport = new GameObject("Viewport", typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Mask));
            viewport.transform.SetParent(scrollRoot.transform, false);
            RectTransform viewportRect = viewport.GetComponent<RectTransform>();
            viewportRect.anchorMin = Vector2.zero;
            viewportRect.anchorMax = Vector2.one;
            viewportRect.offsetMin = Vector2.zero;
            viewportRect.offsetMax = Vector2.zero;
            Image viewportImage = viewport.GetComponent<Image>();
            viewportImage.color = new Color32(255, 255, 255, 8);
            viewport.GetComponent<Mask>().showMaskGraphic = false;

            GameObject contentRoot = new GameObject("Content", typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(ContentSizeFitter));
            contentRoot.transform.SetParent(viewport.transform, false);
            listContent = contentRoot.GetComponent<RectTransform>();
            listContent.anchorMin = new Vector2(0f, 1f);
            listContent.anchorMax = new Vector2(1f, 1f);
            listContent.pivot = new Vector2(0.5f, 1f);
            listContent.anchoredPosition = Vector2.zero;
            listContent.sizeDelta = new Vector2(0f, 0f);

            VerticalLayoutGroup contentLayout = contentRoot.GetComponent<VerticalLayoutGroup>();
            contentLayout.padding = new RectOffset(30, 30, 30, 30);
            contentLayout.spacing = 28;
            contentLayout.childAlignment = TextAnchor.UpperCenter;
            contentLayout.childControlHeight = true;
            contentLayout.childControlWidth = true;
            contentLayout.childForceExpandHeight = false;
            contentLayout.childForceExpandWidth = true;

            ContentSizeFitter fitter = contentRoot.GetComponent<ContentSizeFitter>();
            fitter.verticalFit = ContentSizeFitter.FitMode.PreferredSize;
            fitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;

            tournamentScroll = scrollRoot.GetComponent<ScrollRect>();
            tournamentScroll.content = listContent;
            tournamentScroll.horizontal = false;
            tournamentScroll.vertical = true;
            tournamentScroll.viewport = viewportRect;
            tournamentScroll.movementType = ScrollRect.MovementType.Clamped;
            tournamentScroll.inertia = true;
            tournamentScroll.scrollSensitivity = 80f;

            panelRoot.SetActive(false);
            openButton.gameObject.SetActive(false);
            hasBuiltUi = true;
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
            layout.preferredHeight = 96f;
            layout.minHeight = 96f;
            layout.flexibleWidth = 1f;

            Text labelText = CreateLabel(buttonObject.transform, label, 40, FontStyle.Bold, Color.white);
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
                return ReadString(entryNode, "uuid", "entry_uuid", "tournament_entry_uuid");
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

        private sealed class LudoTournamentListItem
        {
            public string TournamentUuid;
            public string EntryUuid;
            public string Title;
            public string Status;
            public string StartTime;
            public int EntryFee;
            public int MaxPlayers;
            public int JoinedPlayers;
            public bool CanJoin;
        }
    }
}
