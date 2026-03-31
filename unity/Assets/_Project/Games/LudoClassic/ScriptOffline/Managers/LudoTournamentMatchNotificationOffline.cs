using System;
using System.Collections.Generic;
using Best.HTTP;
using Newtonsoft.Json.Linq;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    /// <summary>
    /// Background polling component that monitors the player's tournament registrations.
    /// Every 15 s it calls GET /api/v1/tournaments/me/history and checks whether any
    /// registration has tournament.status == "in_progress" and registration.status is
    /// "registered" or "playing".
    ///
    /// When a match is detected it shows a floating banner:
    ///   "⚔️ Your tournament match is ready! Tap to play."
    ///
    /// Tapping "PLAY" calls TryStartTournamentMatch() on DashBoardManagerOffline,
    /// which routes through LudoV2MatchmakingBridge.
    ///
    /// Usage:
    ///   var notify = gameObject.AddComponent<LudoTournamentMatchNotificationOffline>();
    ///   notify.Initialize(this);   // from DashBoardManagerOffline.Start()
    /// </summary>
    public class LudoTournamentMatchNotificationOffline : MonoBehaviour
    {
        private DashBoardManagerOffline dashboard;

        // ── Polling state ─────────────────────────────────────────────────────
        private const float PollInterval = 15f;
        private float pollTimer = 0f;
        private bool isPolling  = false; // true while HTTP request is in-flight

        // ── Banner UI ─────────────────────────────────────────────────────────
        private GameObject bannerRoot;
        private Text  bannerTitleText;
        private Text  bannerSubText;
        private Button playButton;
        private bool hasBuiltBanner;
        private Font runtimeFont;

        // Active match data (populated when a ready match is found)
        private string pendingTournamentId;
        private string pendingEntryUuid;
        private int    pendingMaxPlayers = 2;

        // ── Initialization ────────────────────────────────────────────────────

        // When true, ShowBanner() is a no-op (e.g. a full-screen sub-panel is open).
        private bool _suppressed = false;

        public void Suppress()
        {
            _suppressed = true;
            HideBanner();
        }

        public void Unsuppress()
        {
            _suppressed = false;
        }

        public void Initialize(DashBoardManagerOffline owner)
        {
            dashboard  = owner;
            pollTimer  = 0f; // wait a full interval before first poll (not immediately)
            EnsureBannerUi();
        }

        // ── Unity loop ────────────────────────────────────────────────────────

        private void Update()
        {
            if (isPolling) return;

            pollTimer += Time.deltaTime;
            if (pollTimer >= PollInterval)
            {
                pollTimer = 0f;
                PollForReadyMatch();
            }
        }

        // ── HTTP poll ─────────────────────────────────────────────────────────

        private async void PollForReadyMatch()
        {
            if (isPolling) return;
            isPolling = true;

            string url = Configuration.LudoTournamentInfoUrl + "me/history";
            var req = new HTTPRequest(new Uri(url), HTTPMethods.Get);
            req.SetHeader("Authorization", "Bearer " + Configuration.GetToken());
            req.SetHeader("Accept", "application/json");

            try
            {
                var response = await req.GetHTTPResponseAsync();
                if (!response.IsSuccess) return;

                var root      = JToken.Parse(response.DataAsText ?? string.Empty);
                JArray items  = null;

                var dataNode = root["data"];
                if (dataNode is JArray arr)        items = arr;
                else if (dataNode?["data"] is JArray arr2) items = arr2;

                if (items == null) return;

                foreach (JToken reg in items)
                {
                    string regStatus  = reg["status"]?.ToString() ?? "";
                    string tStatus    = reg["tournament"]?["status"]?.ToString() ?? "";
                    string tId        = reg["tournament"]?["id"]?.ToString() ?? "";
                    string entryUuid  = reg["id"]?.ToString() ?? "";
                    int    maxPlayers = reg["tournament"]?["players_per_match"]?.ToObject<int>() ?? 2;
                    string tName      = reg["tournament"]?["name"]?.ToString() ?? "Tournament";

                    // Show banner when tournament is in_progress and player is registered/playing
                    bool matchReady = tStatus == "in_progress"
                        && (regStatus == "registered" || regStatus == "playing");

                    if (matchReady && !string.IsNullOrEmpty(tId) && !string.IsNullOrEmpty(entryUuid))
                    {
                        ShowBanner(tName, tId, entryUuid, maxPlayers);
                        return; // show for the first found match only
                    }
                }

                // No ready match — hide banner if it was showing
                HideBanner();
            }
            catch (Exception ex)
            {
                Debug.LogWarning("[MatchNotification] Poll error: " + ex.Message);
            }
            finally
            {
                isPolling = false;
            }
        }

        // ── Banner control ────────────────────────────────────────────────────

        private void ShowBanner(string tournamentName, string tournamentId, string entryUuid, int maxPlayers)
        {
            if (_suppressed) return;
            EnsureBannerUi();

            pendingTournamentId = tournamentId;
            pendingEntryUuid    = entryUuid;
            pendingMaxPlayers   = Mathf.Max(2, maxPlayers);

            if (bannerTitleText != null)
                bannerTitleText.text = "⚔️ Match Ready!";
            if (bannerSubText != null)
                bannerSubText.text = tournamentName;

            bannerRoot.transform.SetAsLastSibling();
            bannerRoot.SetActive(true);
        }

        private void HideBanner()
        {
            if (bannerRoot != null)
                bannerRoot.SetActive(false);
        }

        private void OnPlayPressed()
        {
            HideBanner();

            if (string.IsNullOrEmpty(pendingTournamentId) || string.IsNullOrEmpty(pendingEntryUuid))
            {
                Debug.LogWarning("[MatchNotification] Play pressed but no pending match data.");
                return;
            }

            bool started = dashboard.TryStartTournamentMatch(
                pendingTournamentId,
                pendingEntryUuid,
                pendingMaxPlayers
            );

            if (!started)
            {
                Debug.LogWarning("[MatchNotification] TryStartTournamentMatch returned false.");
            }
        }

        // ── UI Build ──────────────────────────────────────────────────────────

        private void EnsureBannerUi()
        {
            if (hasBuiltBanner) return;

            Transform parent = dashboard?.dashBordPanal != null
                ? dashboard.dashBordPanal.transform
                : transform;

            // Floating banner — anchored to bottom-center of screen
            bannerRoot = new GameObject("MatchReadyBanner",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            bannerRoot.transform.SetParent(parent, false);

            Image bg = bannerRoot.GetComponent<Image>();
            bg.color         = new Color32(12, 55, 28, 245);
            bg.raycastTarget = true;

            RectTransform br = bannerRoot.GetComponent<RectTransform>();
            br.anchorMin        = new Vector2(0.05f, 0f);
            br.anchorMax        = new Vector2(0.95f, 0f);
            br.pivot            = new Vector2(0.5f, 0f);
            br.anchoredPosition = new Vector2(0f, 60f);
            br.sizeDelta        = new Vector2(0f, 200f);

            // Green left accent bar
            GameObject accent = new GameObject("Accent",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Image));
            accent.transform.SetParent(bannerRoot.transform, false);
            accent.GetComponent<Image>().color = new Color32(60, 210, 100, 255);
            RectTransform acRect = accent.GetComponent<RectTransform>();
            acRect.anchorMin = new Vector2(0f, 0f); acRect.anchorMax = new Vector2(0f, 1f);
            acRect.pivot     = new Vector2(0f, 0.5f);
            acRect.offsetMin = Vector2.zero; acRect.offsetMax = Vector2.zero;
            acRect.sizeDelta = new Vector2(10f, 0f);

            // Title label
            bannerTitleText = MakeAbsoluteLabel(bannerRoot.transform,
                "⚔️ Match Ready!", 46, FontStyle.Bold, Color.white,
                new Vector2(24f,  0f), new Vector2(-340f, 0f),
                new Vector2(0f, 0.55f), new Vector2(1f, 1f));
            bannerTitleText.alignment = TextAnchor.MiddleLeft;

            // Sub label (tournament name)
            bannerSubText = MakeAbsoluteLabel(bannerRoot.transform,
                "", 34, FontStyle.Normal, new Color32(140, 220, 160, 255),
                new Vector2(24f, 0f), new Vector2(-340f, 0f),
                new Vector2(0f, 0f), new Vector2(1f, 0.52f));
            bannerSubText.alignment = TextAnchor.MiddleLeft;

            // PLAY button
            playButton = CreateButton(bannerRoot.transform, "PLAY", new Color32(39, 160, 80, 255));
            RectTransform pbRect = playButton.GetComponent<RectTransform>();
            pbRect.anchorMin        = new Vector2(1f, 0.5f); pbRect.anchorMax = new Vector2(1f, 0.5f);
            pbRect.pivot            = new Vector2(1f, 0.5f);
            pbRect.anchoredPosition = new Vector2(-20f, 0f);
            pbRect.sizeDelta        = new Vector2(290f, 140f);
            playButton.onClick.AddListener(OnPlayPressed);

            // Dismiss (✕) button
            Button dismissBtn = CreateButton(bannerRoot.transform, "✕", new Color32(100, 30, 30, 220));
            RectTransform dbRect = dismissBtn.GetComponent<RectTransform>();
            dbRect.anchorMin        = new Vector2(1f, 1f); dbRect.anchorMax = new Vector2(1f, 1f);
            dbRect.pivot            = new Vector2(1f, 1f);
            dbRect.anchoredPosition = new Vector2(-5f, -5f);
            dbRect.sizeDelta        = new Vector2(70f, 70f);
            dismissBtn.onClick.AddListener(HideBanner);

            bannerRoot.SetActive(false);
            hasBuiltBanner = true;
        }

        // ── Label / button helpers ────────────────────────────────────────────

        private Text MakeAbsoluteLabel(Transform parent, string text, int size, FontStyle style, Color color,
            Vector2 offsetMin, Vector2 offsetMax, Vector2 anchorMin, Vector2 anchorMax)
        {
            GameObject go = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            go.transform.SetParent(parent, false);
            Text t = go.GetComponent<Text>();
            t.font              = GetFont();
            t.text              = text;
            t.fontSize          = size;
            t.fontStyle         = style;
            t.color             = color;
            t.horizontalOverflow = HorizontalWrapMode.Wrap;
            t.verticalOverflow   = VerticalWrapMode.Overflow;
            RectTransform r = go.GetComponent<RectTransform>();
            r.anchorMin = anchorMin; r.anchorMax = anchorMax;
            r.offsetMin = offsetMin; r.offsetMax = offsetMax;
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

            GameObject lblGo = new GameObject("Lbl",
                typeof(RectTransform), typeof(CanvasRenderer), typeof(Text));
            lblGo.transform.SetParent(go.transform, false);
            Text lbl = lblGo.GetComponent<Text>();
            lbl.font      = GetFont();
            lbl.text      = label;
            lbl.fontSize  = 38;
            lbl.fontStyle = FontStyle.Bold;
            lbl.color     = Color.white;
            lbl.alignment = TextAnchor.MiddleCenter;
            lbl.raycastTarget = false;
            RectTransform lr = lblGo.GetComponent<RectTransform>();
            lr.anchorMin = Vector2.zero; lr.anchorMax = Vector2.one;
            lr.offsetMin = lr.offsetMax = Vector2.zero;
            return btn;
        }

        private Font GetFont()
        {
            if (runtimeFont != null) return runtimeFont;
            runtimeFont = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            return runtimeFont;
        }
    }
}
