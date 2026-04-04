using System;
using Best.HTTP;
using Newtonsoft.Json.Linq;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    /// <summary>
    /// "My Tournaments" history panel.
    /// Shows the authenticated user's tournament registration history:
    /// tournament name, their status (winner/eliminated/etc.), entry fee, prize won, and position.
    /// </summary>
    public class LudoMyTournamentsPanelOffline : MonoBehaviour
    {
        private DashBoardManagerOffline dashboard;
        private GameObject panelRoot;
        private RectTransform listContent;
        private Text statusText;
        private bool isLoading;
        private bool hasBuiltUi;
        private Font runtimeFont;

        // ── Initialization ────────────────────────────────────────────────────

        public void Initialize(DashBoardManagerOffline owner)
        {
            dashboard = owner;
            EnsureUi();
        }

        public void OpenPanel()
        {
            EnsureUi();
            panelRoot.transform.SetAsLastSibling();
            panelRoot.SetActive(true);
            Refresh();
        }

        public void ClosePanel()
        {
            if (panelRoot != null)
                panelRoot.SetActive(false);
            dashboard?.OpenTournamentPanel();
        }

        // ── Fetch history ─────────────────────────────────────────────────────

        private async void Refresh()
        {
            if (isLoading) return;
            isLoading = true;
            ClearRows();
            SetStatus("Loading your tournament history...", neutral: true);

            string url = Configuration.LudoTournamentInfoUrl + "me/history";
            var req = new HTTPRequest(new Uri(url), HTTPMethods.Get);
            req.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            req.SetHeader("Accept", "application/json");

            try
            {
                var response = await req.GetHTTPResponseAsync();
                string body  = response.DataAsText ?? string.Empty;

                if (!response.IsSuccess)
                {
                    SetStatus("Failed to load history. Please try again.");
                    return;
                }

                var root        = JToken.Parse(body);
                var dataNode    = root["data"];
                JArray items    = null;

                if (dataNode is JArray arr)
                    items = arr;
                else if (dataNode?["data"] is JArray arr2)
                    items = arr2;

                if (items == null || items.Count == 0)
                {
                    SetStatus("You haven't joined any tournaments yet.");
                    return;
                }

                SetStatus(string.Empty);
                foreach (JToken item in items)
                    CreateHistoryRow(item);
            }
            catch (Exception ex)
            {
                Debug.LogWarning("[MyTournaments] Error: " + ex.Message);
                SetStatus("Network error. Please try again.");
            }
            finally
            {
                isLoading = false;
            }
        }

        // ── Row builder ───────────────────────────────────────────────────────

        private void CreateHistoryRow(JToken reg)
        {
            string tName      = reg["tournament"]?["name"]?.ToString() ?? "Tournament";
            string status     = reg["status"]?.ToString() ?? "—";
            string tStatus    = reg["tournament"]?["status"]?.ToString() ?? "";
            string tId        = reg["tournament"]?["id"]?.ToString() ?? "";
            float  feePaid    = reg["entry_fee_paid"]?.ToObject<float>() ?? 0f;
            float  prizeWon   = reg["prize_won"]?.ToObject<float>() ?? 0f;
            int?   position   = reg["final_position"]?.Type == JTokenType.Null ? (int?)null
                                : reg["final_position"]?.ToObject<int>();

            bool canViewBracket = !string.IsNullOrEmpty(tId)
                && (tStatus == "in_progress" || tStatus == "completed");

            // Card
            GameObject row = new GameObject("HistoryRow",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image),
                typeof(HorizontalLayoutGroup), typeof(LayoutElement));
            row.transform.SetParent(listContent, false);

            Image rowImg = row.GetComponent<Image>();
            rowImg.color = new Color32(60, 12, 22, 235);

            HorizontalLayoutGroup hl = row.GetComponent<HorizontalLayoutGroup>();
            hl.padding              = new RectOffset(18, 18, 14, 14);
            hl.spacing              = 12;
            hl.childControlHeight   = true;
            hl.childControlWidth    = true;
            hl.childForceExpandHeight = true;
            hl.childForceExpandWidth  = false;

            LayoutElement rowLE = row.GetComponent<LayoutElement>();
            rowLE.preferredHeight = canViewBracket ? 170f : 140f;
            rowLE.minHeight       = canViewBracket ? 150f : 120f;

            // ── Left column: name + status + optional bracket btn ──────────────
            GameObject left = MakeVStack(row.transform, flexWidth: 2f);
            MakeLabel(left.transform, tName, 38, FontStyle.Bold, Color.white);
            MakeLabel(left.transform, StatusLabel(status), 32, FontStyle.Normal, StatusColor(status));

            if (canViewBracket)
            {
                // Capture loop vars for closure
                string capturedId   = tId;
                string capturedName = tName;

                Button bracketBtn = CreateButton(left.transform, "📋 View Bracket",
                    new Color32(175, 130, 18, 255));
                LayoutElement btnLE = bracketBtn.GetComponent<LayoutElement>();
                btnLE.preferredHeight = 56f;
                btnLE.minHeight       = 50f;
                bracketBtn.GetComponentInChildren<Text>().fontSize = 30;
                bracketBtn.onClick.AddListener(() =>
                    dashboard?.OpenBracketViewerPanel(capturedId, capturedName));
            }

            // ── Center column: fee / prize ─────────────────────────────────────
            GameObject center = MakeVStack(row.transform, flexWidth: 1f);
            MakeLabel(center.transform, feePaid > 0 ? $"₹{feePaid:0} paid" : "Free", 32, FontStyle.Normal, new Color32(230, 205, 210, 255));
            if (prizeWon > 0)
                MakeLabel(center.transform, $"🏆 ₹{prizeWon:0}", 36, FontStyle.Bold, new Color32(255, 210, 40, 255));

            // ── Right column: position ─────────────────────────────────────────
            GameObject right = MakeVStack(row.transform, flexWidth: 0.7f);
            if (position.HasValue)
                MakeLabel(right.transform, $"#{position.Value}", 44, FontStyle.Bold, PositionColor(position.Value));
            else
                MakeLabel(right.transform, "—", 38, FontStyle.Normal, new Color32(190, 155, 160, 200));
        }

        // ── UI layout helpers ─────────────────────────────────────────────────

        private static GameObject MakeVStack(Transform parent, float flexWidth)
        {
            GameObject go = new GameObject("Col",
                typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            var vl = go.GetComponent<VerticalLayoutGroup>();
            vl.childControlHeight     = true;
            vl.childControlWidth      = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth  = true;
            vl.spacing = 4;
            go.GetComponent<LayoutElement>().flexibleWidth = flexWidth;
            return go;
        }

        private Text MakeLabel(Transform parent, string text, int size, FontStyle style, Color color,
            float flexWidth = 0f)
        {
            GameObject go = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text), typeof(LayoutElement));
            go.transform.SetParent(parent, false);
            Text t = go.GetComponent<Text>();
            t.font             = GetFont();
            t.text             = text;
            t.fontSize         = size;
            t.fontStyle        = style;
            t.color            = color;
            t.horizontalOverflow = HorizontalWrapMode.Wrap;
            t.verticalOverflow   = VerticalWrapMode.Overflow;
            var le = go.GetComponent<LayoutElement>();
            if (flexWidth > 0f)
                le.flexibleWidth = flexWidth;
            else
                le.preferredHeight = size + 10f;
            return t;
        }

        private static string StatusLabel(string status) => status switch
        {
            "winner"     => "WINNER",
            "playing"    => "In Progress",
            "eliminated" => "Eliminated",
            "registered" => "Registered",
            "refunded"   => "Refunded",
            _            => status,
        };

        private static Color StatusColor(string status) => status switch
        {
            "winner"     => new Color32(80,  230, 120, 255),  // bright green
            "playing"    => new Color32(0,   210, 185, 255),  // teal — visible on dark red
            "eliminated" => new Color32(255, 100,  90, 255),  // bright red-orange
            "refunded"   => new Color32(215, 185, 190, 255),  // warm muted
            _            => new Color32(225, 200, 205, 255),  // warm light
        };

        private static Color PositionColor(int pos) => pos switch
        {
            1 => new Color32(255, 215, 0, 255),   // gold
            2 => new Color32(192, 192, 200, 255),  // silver
            3 => new Color32(205, 127, 50, 255),   // bronze
            _ => new Color32(180, 190, 210, 255),
        };

        // ── UI Build ──────────────────────────────────────────────────────────

        private void EnsureUi()
        {
            if (hasBuiltUi) return;

            if (dashboard == null)
                dashboard = GetComponent<DashBoardManagerOffline>();

            Transform parent = dashboard?.dashBordPanal != null
                ? dashboard.dashBordPanal.transform
                : transform;

            // Full-screen panel (own Canvas overrides sorting to cover game overlay buttons)
            panelRoot = new GameObject("MyTournamentsPanel",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            panelRoot.transform.SetParent(parent, false);
            panelRoot.GetComponent<Image>().color = new Color32(28, 5, 10, 255);
            RectTransform pr = panelRoot.GetComponent<RectTransform>();
            pr.anchorMin = Vector2.zero;
            pr.anchorMax = Vector2.one;
            pr.offsetMin = pr.offsetMax = Vector2.zero;

            // ── Header: title + close btn ─────────────────────────────────────
            Text header = MakeLabel(panelRoot.transform, "My Tournaments", 58, FontStyle.Bold, Color.white);
            RectTransform hr = header.GetComponent<RectTransform>();
            hr.anchorMin = new Vector2(0.05f, 1f); hr.anchorMax = new Vector2(0.75f, 1f);
            hr.pivot     = new Vector2(0f, 1f);
            hr.anchoredPosition = new Vector2(0f, -28f);
            hr.sizeDelta        = new Vector2(0f, 80f);

            Button refreshBtn = CreateButton(panelRoot.transform, "↻ Refresh", new Color32(80, 12, 22, 255));
            RectTransform rfRect = refreshBtn.GetComponent<RectTransform>();
            rfRect.anchorMin = new Vector2(1f, 1f); rfRect.anchorMax = new Vector2(1f, 1f);
            rfRect.pivot     = new Vector2(1f, 1f);
            rfRect.anchoredPosition = new Vector2(-320f, -24f);
            rfRect.sizeDelta        = new Vector2(280f, 90f);
            refreshBtn.onClick.AddListener(Refresh);

            Button closeBtn = CreateButton(panelRoot.transform, "✕ Close", new Color32(180, 40, 55, 255));
            RectTransform cbRect = closeBtn.GetComponent<RectTransform>();
            cbRect.anchorMin = new Vector2(1f, 1f); cbRect.anchorMax = new Vector2(1f, 1f);
            cbRect.pivot     = new Vector2(1f, 1f);
            cbRect.anchoredPosition = new Vector2(-24f, -24f);
            cbRect.sizeDelta        = new Vector2(260f, 90f);
            closeBtn.onClick.AddListener(ClosePanel);

            // ── Column headers ────────────────────────────────────────────────
            GameObject colHeader = new GameObject("ColHeader",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(HorizontalLayoutGroup));
            colHeader.transform.SetParent(panelRoot.transform, false);
            colHeader.GetComponent<Image>().color = new Color32(118, 18, 28, 220);
            RectTransform ch = colHeader.GetComponent<RectTransform>();
            ch.anchorMin = new Vector2(0.02f, 1f); ch.anchorMax = new Vector2(0.98f, 1f);
            ch.pivot     = new Vector2(0.5f, 1f);
            ch.anchoredPosition = new Vector2(0f, -118f);
            ch.sizeDelta        = new Vector2(0f, 56f);
            HorizontalLayoutGroup chl = colHeader.GetComponent<HorizontalLayoutGroup>();
            chl.padding  = new RectOffset(18, 18, 6, 6);
            chl.spacing  = 12;
            chl.childControlHeight = chl.childControlWidth = true;
            chl.childForceExpandHeight = false; chl.childForceExpandWidth = false;
            MakeLabel(colHeader.transform, "Tournament", 30, FontStyle.Bold, new Color32(255, 210, 70, 255), 2f).alignment = TextAnchor.MiddleLeft;
            MakeLabel(colHeader.transform, "Fee / Prize", 30, FontStyle.Bold, new Color32(255, 210, 70, 255), 1f).alignment = TextAnchor.MiddleLeft;
            MakeLabel(colHeader.transform, "Rank",        30, FontStyle.Bold, new Color32(255, 210, 70, 255), 0.7f).alignment = TextAnchor.MiddleLeft;

            // ── Scroll list ───────────────────────────────────────────────────
            // NO Mask on scrollRoot — only viewport gets Mask (alpha=0 on Mask Image discards all children)
            GameObject scrollRoot = new GameObject("Scroll",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(ScrollRect));
            scrollRoot.transform.SetParent(panelRoot.transform, false);
            scrollRoot.GetComponent<Image>().color = Color.clear;
            RectTransform sr = scrollRoot.GetComponent<RectTransform>();
            sr.anchorMin = new Vector2(0.02f, 0.06f);
            sr.anchorMax = new Vector2(0.98f, 0.80f);
            sr.offsetMin = sr.offsetMax = Vector2.zero;

            // Viewport alpha MUST be > 0 for Mask stencil to clip children correctly
            GameObject viewport = new GameObject("Viewport",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Mask));
            viewport.transform.SetParent(scrollRoot.transform, false);
            viewport.GetComponent<Image>().color = new Color32(0, 0, 0, 1); // alpha=1: stencil writes, graphic invisible
            viewport.GetComponent<Mask>().showMaskGraphic = false;
            RectTransform vr = viewport.GetComponent<RectTransform>();
            vr.anchorMin = Vector2.zero; vr.anchorMax = Vector2.one;
            vr.offsetMin = vr.offsetMax = Vector2.zero;

            // Content — fixed sizeDelta instead of ContentSizeFitter (avoids deferred layout blank scroll)
            GameObject content = new GameObject("Content",
                typeof(RectTransform), typeof(VerticalLayoutGroup));
            content.transform.SetParent(viewport.transform, false);
            listContent = content.GetComponent<RectTransform>();
            listContent.anchorMin = new Vector2(0f, 1f);
            listContent.anchorMax = new Vector2(1f, 1f);
            listContent.pivot     = new Vector2(0.5f, 1f);
            listContent.anchoredPosition = Vector2.zero;
            listContent.sizeDelta        = new Vector2(0f, 2400f); // tall enough for ~15 history rows

            VerticalLayoutGroup vl = content.GetComponent<VerticalLayoutGroup>();
            vl.padding          = new RectOffset(8, 8, 8, 20);
            vl.spacing          = 10;
            vl.childControlHeight     = true;
            vl.childControlWidth      = true;
            vl.childForceExpandHeight = false;
            vl.childForceExpandWidth  = true;

            ScrollRect scroll = scrollRoot.GetComponent<ScrollRect>();
            scroll.content         = listContent;
            scroll.viewport        = vr;
            scroll.horizontal      = false;
            scroll.vertical        = true;
            scroll.movementType    = ScrollRect.MovementType.Clamped;
            scroll.scrollSensitivity = 70f;

            // Status text — centered in scroll area when empty
            statusText = MakeLabel(panelRoot.transform, string.Empty, 42, FontStyle.Normal, new Color32(220, 185, 190, 200));
            RectTransform stRect = statusText.GetComponent<RectTransform>();
            stRect.anchorMin = new Vector2(0.1f, 0.35f); stRect.anchorMax = new Vector2(0.9f, 0.65f);
            stRect.offsetMin = stRect.offsetMax = Vector2.zero;
            statusText.alignment = TextAnchor.MiddleCenter;

            panelRoot.SetActive(false);
            hasBuiltUi = true;
        }

        private Button CreateButton(Transform parent, string label, Color32 color)
        {
            GameObject go = new GameObject(label + "Btn",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image), typeof(Button));
            go.transform.SetParent(parent, false);
            Image img = go.GetComponent<Image>();
            img.color = color;
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

        private void SetStatus(string msg, bool neutral = false)
        {
            if (statusText == null) return;
            statusText.text = msg;
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
    }
}
