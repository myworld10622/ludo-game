using System;
using System.Collections.Generic;
using Best.HTTP;
using Newtonsoft.Json.Linq;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    /// <summary>
    /// Shows a knockout bracket for a specific tournament.
    /// Fetches GET /api/v1/tournaments/{id}/bracket and renders
    /// each round with match cards (player names, status, winner).
    /// Opened from MyTournaments panel via "View Bracket" button.
    /// </summary>
    public class LudoBracketViewerPanelOffline : MonoBehaviour
    {
        private DashBoardManagerOffline dashboard;
        private GameObject panelRoot;
        private RectTransform listContent;
        private Text statusText;
        private Text titleText;
        private bool isLoading;
        private bool hasBuiltUi;
        private const string PanelName = "BracketViewerPanel";
        private Font runtimeFont;

        // Stored so the refresh button can re-fetch
        private string currentTournamentId;
        private string currentTournamentName;

        // ── Initialization ────────────────────────────────────────────────────

        public void Initialize(DashBoardManagerOffline owner)
        {
            dashboard = owner;
            EnsureUi();
        }

        public void OpenPanel(string tournamentId, string tournamentName)
        {
            EnsureUi();
            currentTournamentId   = tournamentId;
            currentTournamentName = tournamentName;
            if (titleText != null)
                titleText.text = "Bracket: " + tournamentName;
            panelRoot.transform.SetAsLastSibling();
            panelRoot.SetActive(true);
            FetchBracket();
        }

        public void ClosePanel()
        {
            if (panelRoot != null)
                panelRoot.SetActive(false);
            dashboard?.OpenMyTournamentsPanel();
        }

        // ── Fetch bracket ─────────────────────────────────────────────────────

        private async void FetchBracket()
        {
            if (isLoading || string.IsNullOrWhiteSpace(currentTournamentId)) return;
            isLoading = true;
            ClearRows();
            SetStatus("Loading bracket...");

            string url = Configuration.LudoTournamentInfoUrl + currentTournamentId + "/bracket";
            var req = new HTTPRequest(new Uri(url), HTTPMethods.Get);
            req.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            req.SetHeader("Accept", "application/json");

            try
            {
                var response = await req.GetHTTPResponseAsync();
                if (!response.IsSuccess)
                {
                    SetStatus("Failed to load bracket.");
                    return;
                }

                var root = JToken.Parse(response.DataAsText ?? string.Empty);
                var data = root["data"];

                if (data == null)
                {
                    SetStatus("Bracket data not available.");
                    return;
                }

                var roundsNode = data["rounds"] as JObject;
                if (roundsNode == null || !roundsNode.HasValues)
                {
                    SetStatus("Bracket not generated yet.\nWait for admin to start the tournament.");
                    return;
                }

                SetStatus(string.Empty);

                // Sort round numbers ascending
                var roundKeys = new List<string>();
                foreach (var prop in roundsNode.Properties())
                    roundKeys.Add(prop.Name);
                roundKeys.Sort((a, b) =>
                {
                    int.TryParse(a, out int ia);
                    int.TryParse(b, out int ib);
                    return ia.CompareTo(ib);
                });

                foreach (string roundKey in roundKeys)
                {
                    var matches = roundsNode[roundKey] as JArray;
                    if (matches == null) continue;
                    BuildRoundHeader(roundKey, matches.Count);
                    foreach (JToken match in matches)
                        BuildMatchCard(match);
                }
            }
            catch (Exception ex)
            {
                Debug.LogWarning("[BracketViewer] Error: " + ex.Message);
                SetStatus("Network error. Please try again.");
            }
            finally
            {
                isLoading = false;
            }
        }

        // ── Row builders ──────────────────────────────────────────────────────

        private void BuildRoundHeader(string round, int matchCount)
        {
            int.TryParse(round, out int roundNum);
            string label = "Round " + roundNum
                + "  (" + matchCount + " match" + (matchCount != 1 ? "es" : "") + ")";

            GameObject go = new GameObject("RoundHeader",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            go.transform.SetParent(listContent, false);
            go.GetComponent<Image>().color = new Color32(22, 42, 70, 220);
            go.GetComponent<LayoutElement>().preferredHeight = 64f;

            Text t = MakeLabel(go.transform, label, 36, FontStyle.Bold, new Color32(110, 180, 255, 255));
            RectTransform tr = t.GetComponent<RectTransform>();
            tr.anchorMin = Vector2.zero; tr.anchorMax = Vector2.one;
            tr.offsetMin = new Vector2(18, 0); tr.offsetMax = Vector2.zero;
            t.alignment = TextAnchor.MiddleLeft;
        }

        private void BuildMatchCard(JToken match)
        {
            string matchStatus = match["status"]?.ToString() ?? "scheduled";
            string winner      = match["winner"]?.ToString();
            var players        = match["players"] as JArray;

            // Parse player slots (up to 2 for knockout display)
            string p1Name = "TBD", p2Name = "TBD";
            bool   p1Wins = false, p2Wins = false;

            if (players != null)
            {
                for (int i = 0; i < players.Count; i++)
                {
                    string name   = players[i]["name"]?.ToString() ?? "Player";
                    bool   isBot  = players[i]["is_bot"]?.ToObject<bool>() ?? false;
                    string result = players[i]["result"]?.ToString() ?? "";
                    name = isBot
                        ? LudoDisplayNameUtility.NeutralSeatLabel(i)
                        : LudoDisplayNameUtility.ResolveDisplayName(players[i]["user_id"]?.ToString(), name, i);

                    if (i == 0) { p1Name = name; p1Wins = result == "winner"; }
                    else        { p2Name = name; p2Wins = result == "winner"; }
                }
            }

            bool isCompleted = matchStatus == "completed";

            // Card container
            GameObject card = new GameObject("MatchCard",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            card.transform.SetParent(listContent, false);
            card.GetComponent<Image>().color = isCompleted
                ? new Color32(14, 30, 22, 230)
                : new Color32(14, 24, 40, 230);

            HorizontalLayoutGroup hl = card.GetComponent<HorizontalLayoutGroup>();
            hl.padding               = new RectOffset(16, 16, 12, 12);
            hl.spacing               = 8;
            hl.childControlHeight    = true;
            hl.childControlWidth     = true;
            hl.childForceExpandHeight = true;
            hl.childForceExpandWidth  = false;
            card.GetComponent<LayoutElement>().preferredHeight = 110f;

            // P1 column (left)
            GameObject p1Col = MakeVStack(card.transform, 1f, TextAnchor.MiddleLeft);
            Text p1Lbl = MakeLabel(p1Col.transform, p1Name, 36,
                p1Wins ? FontStyle.Bold : FontStyle.Normal,
                p1Wins ? new Color32(80, 220, 100, 255) : new Color32(200, 210, 225, 255));
            p1Lbl.alignment = TextAnchor.MiddleLeft;

            // VS center
            GameObject vsCol = MakeVStack(card.transform, 0f, TextAnchor.MiddleCenter);
            vsCol.GetComponent<LayoutElement>().preferredWidth = 64f;
            Text vsLbl = MakeLabel(vsCol.transform, "vs", 32, FontStyle.Bold,
                new Color32(140, 150, 170, 180));
            vsLbl.alignment = TextAnchor.MiddleCenter;

            // P2 column (right)
            GameObject p2Col = MakeVStack(card.transform, 1f, TextAnchor.MiddleRight);
            Text p2Lbl = MakeLabel(p2Col.transform, p2Name, 36,
                p2Wins ? FontStyle.Bold : FontStyle.Normal,
                p2Wins ? new Color32(80, 220, 100, 255) : new Color32(200, 210, 225, 255));
            p2Lbl.alignment = TextAnchor.MiddleRight;

            // Status badge column
            GameObject statusCol = MakeVStack(card.transform, 0f, TextAnchor.MiddleCenter);
            statusCol.GetComponent<LayoutElement>().preferredWidth = 170f;

            Color32 statusClr;
            switch (matchStatus)
            {
                case "completed":   statusClr = new Color32(60,  210, 100, 255); break;
                case "in_progress": statusClr = new Color32(90,  180, 255, 255); break;
                case "waiting":     statusClr = new Color32(255, 200,  60, 255); break;
                default:            statusClr = new Color32(150, 160, 180, 200); break;
            }

            Text stLbl = MakeLabel(statusCol.transform,
                matchStatus.Replace("_", " ").ToUpper(), 28, FontStyle.Normal, statusClr);
            stLbl.alignment = TextAnchor.MiddleCenter;

            if (!string.IsNullOrEmpty(winner))
            {
                Text winLbl = MakeLabel(statusCol.transform,
                    "🏆 " + winner, 26, FontStyle.Bold, new Color32(255, 215, 40, 255));
                winLbl.alignment         = TextAnchor.MiddleCenter;
                winLbl.horizontalOverflow = HorizontalWrapMode.Wrap;
            }

            // Thin divider below card
            GameObject div = new GameObject("Div",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(LayoutElement));
            div.transform.SetParent(listContent, false);
            div.GetComponent<Image>().color = new Color32(30, 50, 75, 180);
            div.GetComponent<LayoutElement>().preferredHeight = 2f;
        }

        // ── UI helpers ────────────────────────────────────────────────────────

        private GameObject MakeVStack(Transform parent, float flex, TextAnchor childAlign)
        {
            GameObject go = new GameObject("Col",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            var vl = go.GetComponent<VerticalLayoutGroup>();
            vl.childControlHeight     = true;
            vl.childControlWidth      = true;
            vl.childForceExpandHeight = true;
            vl.childForceExpandWidth  = true;
            vl.childAlignment         = childAlign;
            go.GetComponent<LayoutElement>().flexibleWidth = flex;
            return go;
        }

        private Text MakeLabel(Transform parent, string text, int size, FontStyle style, Color color)
        {
            GameObject go = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            Text t = go.GetComponent<Text>();
            t.font              = GetFont();
            t.text              = text;
            t.fontSize          = size;
            t.fontStyle         = style;
            t.color             = color;
            t.horizontalOverflow = HorizontalWrapMode.Wrap;
            t.verticalOverflow   = VerticalWrapMode.Overflow;
            go.GetComponent<LayoutElement>().preferredHeight = size + 10f;
            return t;
        }

        private Button CreateButton(Transform parent, string label, Color32 color)
        {
            GameObject go = new GameObject(label + "Btn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Button));
            go.transform.SetParent(parent, false);
            Image img = go.GetComponent<Image>();
            img.color        = color;
            img.raycastTarget = true;
            Button btn = go.GetComponent<Button>();
            btn.targetGraphic = img;
            Text lbl = MakeLabel(go.transform, label, 36, FontStyle.Bold, Color.white);
            lbl.alignment    = TextAnchor.MiddleCenter;
            lbl.raycastTarget = false;
            RectTransform lr = lbl.GetComponent<RectTransform>();
            lr.anchorMin = Vector2.zero; lr.anchorMax = Vector2.one;
            lr.offsetMin = lr.offsetMax = Vector2.zero;
            return btn;
        }

        private void SetStatus(string msg)
        {
            if (statusText != null) statusText.text = msg;
        }

        private void ClearRows()
        {
            if (listContent == null) return;
            for (int i = listContent.childCount - 1; i >= 0; i--)
                Destroy(listContent.GetChild(i).gameObject);
        }

        private Font GetFont()
        {
            if (runtimeFont != null) return runtimeFont;
            runtimeFont = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            return runtimeFont;
        }

        // ── UI Build ──────────────────────────────────────────────────────────

        private void EnsureUi()
        {
            if (hasBuiltUi) return;

            if (dashboard == null)
                dashboard = GetComponent<DashBoardManagerOffline>();

            Transform parent = dashboard?.dashBordPanal != null
                ? dashboard.dashBordPanal.transform
                : transform;

            if (TryBindExistingUi(parent))
            {
                hasBuiltUi = true;
                panelRoot.SetActive(false);
                return;
            }

            // Full-screen panel
            panelRoot = new GameObject(PanelName,
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            panelRoot.transform.SetParent(parent, false);
            panelRoot.GetComponent<Image>().color = new Color32(7, 14, 26, 252);
            RectTransform pr = panelRoot.GetComponent<RectTransform>();
            pr.anchorMin = Vector2.zero; pr.anchorMax = Vector2.one;
            pr.offsetMin = pr.offsetMax = Vector2.zero;

            // Title
            titleText = MakeLabel(panelRoot.transform, "Tournament Bracket", 52, FontStyle.Bold, Color.white);
            RectTransform hRect = titleText.GetComponent<RectTransform>();
            hRect.anchorMin         = new Vector2(0.03f, 1f);
            hRect.anchorMax         = new Vector2(0.72f, 1f);
            hRect.pivot             = new Vector2(0f, 1f);
            hRect.anchoredPosition  = new Vector2(0f, -28f);
            hRect.sizeDelta         = new Vector2(0f, 80f);

            // Refresh button
            Button refreshBtn = CreateButton(panelRoot.transform, "↻ Refresh", new Color32(40, 80, 130, 255));
            RectTransform rfRect = refreshBtn.GetComponent<RectTransform>();
            rfRect.anchorMin        = new Vector2(1f, 1f); rfRect.anchorMax = new Vector2(1f, 1f);
            rfRect.pivot            = new Vector2(1f, 1f);
            rfRect.anchoredPosition = new Vector2(-310f, -24f);
            rfRect.sizeDelta        = new Vector2(280f, 90f);
            refreshBtn.onClick.AddListener(FetchBracket);

            // Close / Back button
            Button closeBtn = CreateButton(panelRoot.transform, "✕ Back", new Color32(160, 47, 47, 255));
            RectTransform cbRect = closeBtn.GetComponent<RectTransform>();
            cbRect.anchorMin        = new Vector2(1f, 1f); cbRect.anchorMax = new Vector2(1f, 1f);
            cbRect.pivot            = new Vector2(1f, 1f);
            cbRect.anchoredPosition = new Vector2(-24f, -24f);
            cbRect.sizeDelta        = new Vector2(270f, 90f);
            closeBtn.onClick.AddListener(ClosePanel);

            // Scroll area
            GameObject scrollRoot = new GameObject("Scroll",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(ScrollRect));
            scrollRoot.transform.SetParent(panelRoot.transform, false);
            scrollRoot.GetComponent<Image>().color = Color.clear;
            RectTransform sr = scrollRoot.GetComponent<RectTransform>();
            sr.anchorMin = new Vector2(0.02f, 0.04f);
            sr.anchorMax = new Vector2(0.98f, 0.82f);
            sr.offsetMin = sr.offsetMax = Vector2.zero;

            // Viewport — alpha=1 required for Mask stencil
            GameObject viewport = new GameObject("Viewport",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Mask));
            viewport.transform.SetParent(scrollRoot.transform, false);
            viewport.GetComponent<Image>().color = new Color32(0, 0, 0, 1);
            viewport.GetComponent<Mask>().showMaskGraphic = false;
            RectTransform vr = viewport.GetComponent<RectTransform>();
            vr.anchorMin = Vector2.zero; vr.anchorMax = Vector2.one;
            vr.offsetMin = vr.offsetMax = Vector2.zero;

            // Content — fixed height avoids ContentSizeFitter blank-scroll bug
            GameObject content = new GameObject("Content", typeof(RectTransform), typeof(VerticalLayoutGroup));
            content.transform.SetParent(viewport.transform, false);
            listContent = content.GetComponent<RectTransform>();
            listContent.anchorMin        = new Vector2(0f, 1f);
            listContent.anchorMax        = new Vector2(1f, 1f);
            listContent.pivot            = new Vector2(0.5f, 1f);
            listContent.anchoredPosition = Vector2.zero;
            listContent.sizeDelta        = new Vector2(0f, 3000f);

            VerticalLayoutGroup vl = content.GetComponent<VerticalLayoutGroup>();
            vl.padding               = new RectOffset(8, 8, 8, 20);
            vl.spacing               = 8;
            vl.childControlHeight    = true;
            vl.childControlWidth     = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth  = true;

            ScrollRect scroll = scrollRoot.GetComponent<ScrollRect>();
            scroll.content          = listContent;
            scroll.viewport         = vr;
            scroll.horizontal       = false;
            scroll.vertical         = true;
            scroll.movementType     = ScrollRect.MovementType.Clamped;
            scroll.scrollSensitivity = 70f;

            // Status / empty state label
            statusText = MakeLabel(panelRoot.transform, string.Empty, 42, FontStyle.Normal,
                new Color32(160, 175, 200, 200));
            RectTransform stRect = statusText.GetComponent<RectTransform>();
            stRect.anchorMin = new Vector2(0.1f, 0.35f); stRect.anchorMax = new Vector2(0.9f, 0.65f);
            stRect.offsetMin = stRect.offsetMax = Vector2.zero;
            statusText.alignment = TextAnchor.MiddleCenter;

            panelRoot.SetActive(false);
            hasBuiltUi = true;
        }

        private bool TryBindExistingUi(Transform preferredParent)
        {
            GameObject existing = FindChildByName(preferredParent, PanelName);
            if (existing == null)
            {
                existing = FindSceneObjectByName(PanelName);
            }

            if (existing == null)
            {
                return false;
            }

            panelRoot = existing;
            listContent = FindChildRect(panelRoot.transform, "Content");
            titleText = FindTextContaining(panelRoot.transform, "Bracket") ?? FindFirstText(panelRoot.transform);
            statusText = FindStatusText(panelRoot.transform, listContent);

            Button[] buttons = panelRoot.GetComponentsInChildren<Button>(true);
            for (int i = 0; i < buttons.Length; i++)
            {
                Button button = buttons[i];
                string label = GetButtonLabel(button);
                if (label.Contains("Refresh") || button.name.Contains("Refresh"))
                {
                    button.onClick.RemoveAllListeners();
                    button.onClick.AddListener(FetchBracket);
                }
                else if (label.Contains("Close") || label.Contains("Back") || label.Contains("X") || label.Contains("x") || label.Contains("✕") || button.name.Contains("Close") || button.name.Contains("Back") || button.name.Contains("✕"))
                {
                    button.onClick.RemoveAllListeners();
                    button.onClick.AddListener(ClosePanel);
                }
            }

            if (listContent == null)
            {
                Debug.LogWarning("BracketViewerPanel found in scene, but Content was not found. Dynamic bracket rows cannot render until Content exists.");
            }

            return true;
        }

        private static GameObject FindChildByName(Transform root, string objectName)
        {
            if (root == null)
            {
                return null;
            }

            Transform[] children = root.GetComponentsInChildren<Transform>(true);
            for (int i = 0; i < children.Length; i++)
            {
                if (children[i].name == objectName)
                {
                    return children[i].gameObject;
                }
            }

            return null;
        }

        private static RectTransform FindChildRect(Transform root, string objectName)
        {
            GameObject child = FindChildByName(root, objectName);
            return child != null ? child.GetComponent<RectTransform>() : null;
        }

        private static GameObject FindSceneObjectByName(string objectName)
        {
            GameObject activeObject = GameObject.Find(objectName);
            if (activeObject != null)
            {
                return activeObject;
            }

            GameObject[] allObjects = Resources.FindObjectsOfTypeAll<GameObject>();
            for (int i = 0; i < allObjects.Length; i++)
            {
                GameObject candidate = allObjects[i];
                if (candidate != null && candidate.name == objectName && candidate.scene.IsValid())
                {
                    return candidate;
                }
            }

            return null;
        }

        private static Text FindTextContaining(Transform root, string value)
        {
            if (root == null || string.IsNullOrEmpty(value))
            {
                return null;
            }

            Text[] labels = root.GetComponentsInChildren<Text>(true);
            for (int i = 0; i < labels.Length; i++)
            {
                if (labels[i] != null && !string.IsNullOrEmpty(labels[i].text) && labels[i].text.Contains(value))
                {
                    return labels[i];
                }
            }

            return null;
        }

        private static Text FindFirstText(Transform root)
        {
            if (root == null)
            {
                return null;
            }

            Text[] labels = root.GetComponentsInChildren<Text>(true);
            return labels.Length > 0 ? labels[0] : null;
        }

        private static Text FindStatusText(Transform root, Transform content)
        {
            if (root == null)
            {
                return null;
            }

            Text[] labels = root.GetComponentsInChildren<Text>(true);
            for (int i = 0; i < labels.Length; i++)
            {
                Text label = labels[i];
                if (label != null && !IsChildOf(label.transform, content) && string.IsNullOrEmpty(label.text))
                {
                    return label;
                }
            }

            return labels.Length > 0 ? labels[labels.Length - 1] : null;
        }

        private static bool IsChildOf(Transform child, Transform parent)
        {
            if (child == null || parent == null)
            {
                return false;
            }

            Transform current = child;
            while (current != null)
            {
                if (current == parent)
                {
                    return true;
                }

                current = current.parent;
            }

            return false;
        }

        private static string GetButtonLabel(Button button)
        {
            if (button == null)
            {
                return string.Empty;
            }

            Text label = button.GetComponentInChildren<Text>(true);
            return label != null ? label.text : button.name;
        }
    }
}
