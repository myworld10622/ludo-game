using System;
using System.Collections;
using System.Collections.Generic;
using Agora.Rtc;
using Newtonsoft.Json;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

#if UNITY_EDITOR
using UnityEditor;
using UnityEditor.SceneManagement;
#endif

#if UNITY_ANDROID
using UnityEngine.Android;
#endif

namespace LudoClassicOffline
{
    public class AgoraVoiceManager : MonoBehaviour
    {
        private const string VoicePanelRootName = "AgoraVoicePanelRoot";
        private const string VoiceToggleLabelName = "AgoraVoiceToggleLabel";
        private const string VoiceStatusLabelName = "AgoraVoiceStatusLabel";
        private const string VoiceUsersLabelName = "AgoraVoiceUsersLabel";
        private const string VoiceOverlayCanvasName = "AgoraVoiceOverlayCanvas";
        private const string VoiceOverlayToggleName = "AgoraVoiceOverlayToggle";
        private const string VoiceOverlayPanelName = "AgoraVoiceOverlayPanel";

        private DashBoardManagerOffline dashBoardManager;
        private LudoV2MatchmakingBridge matchmakingBridge;
        private LudoRoomChatController roomChatController;
        private bool usingSceneAnchoredUi;

        private Canvas rootCanvas;
        private GameObject manualToggle;
        private GameObject manualPanel;
        private RectTransform panelRoot;
        private GameObject overlayPanelObject;
        private CanvasGroup overlayPanelCanvasGroup;
        private Button toggleButton;
        private Button micButton;
        private Button speakerButton;
        private Button leaveButton;
        private Button closeButton;
        private Text toggleLabel;
        private Text statusLabel;
        private Text usersLabel;
        private Image toggleStatusDot;
        private Coroutine pulseCoroutine;
        private Coroutine panelAnimationCoroutine;

        private IRtcEngine rtcEngine;
        private VoiceEventHandler voiceEventHandler;
        private Coroutine joinCoroutine;
        private Coroutine reconnectCoroutine;

        private bool uiReady;
        private bool isVoiceAvailable;
        private bool isPanelOpen;
        private bool isJoining;
        private bool isJoined;
        private bool isMuted;
        private bool isSpeakerEnabled = true;
        private bool engineInitialized;

        private string currentChannelName;
        private string currentToken;
        private string currentAppId;
        private uint currentUid;

        private readonly HashSet<uint> connectedRemoteUsers = new HashSet<uint>();
        private readonly HashSet<uint> mutedRemoteUsers = new HashSet<uint>();
        private readonly HashSet<uint> speakingRemoteUsers = new HashSet<uint>();
        private Font cachedUiFont;

        private void Awake()
        {
            dashBoardManager = GetComponent<DashBoardManagerOffline>() ?? DashBoardManagerOffline.instance;
            matchmakingBridge = GetComponent<LudoV2MatchmakingBridge>() ?? LudoV2MatchmakingBridge.Instance;
            roomChatController = GetComponent<LudoRoomChatController>();
            TryBuildUi();
            SetVoiceAvailability(false);
            RequestMicrophonePermissionIfNeeded();
        }

#if UNITY_EDITOR
        public void BakeSceneAnchoredUiForEditor()
        {
            dashBoardManager = GetComponent<DashBoardManagerOffline>() ?? DashBoardManagerOffline.instance;
            roomChatController = GetComponent<LudoRoomChatController>();
            uiReady = false;
            usingSceneAnchoredUi = false;
            statusLabel = null;
            usersLabel = null;
            toggleLabel = null;
            toggleStatusDot = null;
            panelRoot = null;
            overlayPanelObject = null;
            overlayPanelCanvasGroup = null;
            TryBuildUi();

            if (overlayPanelObject != null)
            {
                overlayPanelObject.SetActive(false);
            }

            EditorUtility.SetDirty(gameObject);
            if (manualToggle != null)
            {
                EditorUtility.SetDirty(manualToggle);
            }

            if (manualPanel != null)
            {
                EditorUtility.SetDirty(manualPanel);
            }

            EditorSceneManager.MarkSceneDirty(gameObject.scene);
        }
#endif

        private void OnDestroy()
        {
            StopJoinCoroutine();
            StopReconnectCoroutine();
            DisposeRtcEngine();
        }

        public void SetVoiceAvailability(bool available)
        {
            isVoiceAvailable = available;
            TryBuildUi();

            // Always keep button visible — visual state communicates availability
            if (toggleButton != null)
            {
                toggleButton.gameObject.SetActive(true);
            }

            if (!available)
            {
                SetPanelOpen(false);
                UpdateStatus("Voice: online rooms only");
            }

            UpdateButtonStates();
        }

        public void HandleRoomOpened()
        {
            SetVoiceAvailability(true);
            JoinVoiceForActiveRoom();
        }

        public void HandleRoomClosed()
        {
            SetVoiceAvailability(false);
            LeaveVoice();
        }

        public void JoinVoiceForActiveRoom()
        {
            if (!Configuration.IsLudoV2Enabled())
            {
                return;
            }

            string channelName = matchmakingBridge != null
                ? matchmakingBridge.GetAgoraVoiceChannelName()
                : null;

            if (string.IsNullOrWhiteSpace(channelName))
            {
                UpdateStatus("Voice channel missing");
                return;
            }

            if (isJoined && string.Equals(currentChannelName, channelName, StringComparison.Ordinal))
            {
                UpdateStatus("Voice connected");
                return;
            }

#if UNITY_EDITOR
            if (channelName.StartsWith("ludo_room_editor-local-", StringComparison.Ordinal)
                || channelName.StartsWith("ludo_private_editor-local-", StringComparison.Ordinal)
                || channelName.StartsWith("ludo_tournament_editor-local-", StringComparison.Ordinal))
            {
                UpdateStatus("Voice disabled in editor preview");
                return;
            }
#endif

            StopJoinCoroutine();
            joinCoroutine = StartCoroutine(FetchTokenAndJoin(channelName, renewOnly: false));
        }

        public void LeaveVoice()
        {
            StopJoinCoroutine();
            StopReconnectCoroutine();

            if (rtcEngine != null && (isJoined || !string.IsNullOrWhiteSpace(currentChannelName)))
            {
                rtcEngine.LeaveChannel();
            }

            ResetVoiceState(preserveAvailability: true);
            UpdateStatus(isVoiceAvailable ? "Voice disconnected" : "Voice unavailable");
        }

        private void ToggleMic()
        {
            isMuted = !isMuted;
            if (rtcEngine != null)
            {
                rtcEngine.MuteLocalAudioStream(isMuted);
            }

            UpdateButtonStates();
            UpdateStatus(isMuted ? "Mic muted" : "Mic live");
        }

        private void ToggleSpeaker()
        {
            isSpeakerEnabled = !isSpeakerEnabled;
            if (rtcEngine != null)
            {
                rtcEngine.SetEnableSpeakerphone(isSpeakerEnabled);
            }

            UpdateButtonStates();
            UpdateStatus(isSpeakerEnabled ? "Speaker on" : "Speaker off");
        }

        private void TryBuildUi()
        {
            if (uiReady)
            {
                return;
            }

            if (dashBoardManager == null)
            {
                dashBoardManager = GetComponent<DashBoardManagerOffline>() ?? DashBoardManagerOffline.instance;
            }

            if (roomChatController != null)
            {
                roomChatController.SetChatAvailability(false);
                roomChatController.enabled = false;
            }

            // Always use dedicated overlay canvas for voice — never depend on chat canvas visibility
            usingSceneAnchoredUi = false;
            rootCanvas = CreateOverlayCanvas();
            if (rootCanvas == null)
            {
                return;
            }

            manualToggle = EnsureOverlayToggle(rootCanvas.transform);
            if (manualToggle == null)
            {
                return;
            }

            toggleButton = manualToggle.GetComponent<Button>();
            toggleButton.onClick.RemoveAllListeners();
            toggleButton.onClick.AddListener(() => SetPanelOpen(!isPanelOpen));

            panelRoot = EnsureOverlayPanel(rootCanvas.transform);
            if (panelRoot == null)
            {
                return;
            }

            if (statusLabel == null)
            {
                BuildVoicePanel(panelRoot);
            }

            uiReady = true;
            UpdateButtonStates();
            UpdateUsersLabel();
            UpdateStatus("Voice ready");
            SetPanelOpen(false);
        }

        private bool TryBuildSceneAnchoredUi()
        {
            if (dashBoardManager == null)
            {
                return false;
            }

            manualToggle = dashBoardManager.LudoChatToggle;
            manualPanel = dashBoardManager.LudoChatPanel;
            rootCanvas = dashBoardManager.ludoChatCanvas;

            if (manualToggle == null || manualPanel == null)
            {
                return false;
            }

            overlayPanelObject = manualPanel;
            overlayPanelCanvasGroup = manualPanel.GetComponent<CanvasGroup>();
            if (overlayPanelCanvasGroup == null)
            {
                overlayPanelCanvasGroup = manualPanel.AddComponent<CanvasGroup>();
            }

            ApplyToggleStyle(manualToggle);

            Button button = manualToggle.GetComponent<Button>();
            if (button == null) button = manualToggle.AddComponent<Button>();
            toggleButton = button;
            toggleButton.onClick.RemoveAllListeners();
            toggleButton.onClick.AddListener(() => SetPanelOpen(!isPanelOpen));

            HideNonVoiceChildren(manualToggle.transform);
            BuildToggleDecorations(manualToggle.transform);

            RectTransform panelRect = manualPanel.GetComponent<RectTransform>();
            if (panelRect == null) return false;

            ApplyPanelStyle(manualPanel);
            HideNonVoiceChildren(manualPanel.transform);

            Transform existingRoot = manualPanel.transform.Find(VoicePanelRootName);
            if (existingRoot != null)
            {
                panelRoot = existingRoot as RectTransform;
            }
            else
            {
                panelRoot = CreatePanelRootLayout(manualPanel.transform);
            }

            return true;
        }

        // ─── Shared style helpers ───────────────────────────────────────────

        private void ApplyToggleStyle(GameObject toggleObj)
        {
            RectTransform rect = toggleObj.GetComponent<RectTransform>();
            if (rect != null)
            {
                rect.sizeDelta = new Vector2(100f, 100f);
            }

            Image img = toggleObj.GetComponent<Image>();
            if (img == null) img = toggleObj.AddComponent<Image>();
            img.color = new Color(0.05f, 0.38f, 0.28f, 0.97f);

            Outline outline = toggleObj.GetComponent<Outline>();
            if (outline == null) outline = toggleObj.AddComponent<Outline>();
            outline.effectColor = new Color(0.25f, 0.95f, 0.70f, 0.22f);
            outline.effectDistance = new Vector2(2f, -2f);

            Shadow shadow = toggleObj.GetComponent<Shadow>();
            if (shadow == null) shadow = toggleObj.AddComponent<Shadow>();
            shadow.effectColor = new Color(0f, 0f, 0f, 0.55f);
            shadow.effectDistance = new Vector2(0f, -8f);
        }

        private void BuildToggleDecorations(Transform parent)
        {
            // Outer glow ring
            EnsureDecorativeCircle(parent, "AgoraVoiceToggleRing", new Vector2(84f, 84f), new Color(0.18f, 0.88f, 0.62f, 0.12f));
            // Inner highlight circle
            EnsureDecorativeCircle(parent, "AgoraVoiceToggleInner", new Vector2(66f, 66f), new Color(1f, 1f, 1f, 0.06f));

            // Mic icon label (center-top)
            Text iconText = FindOrCreateText(parent, "AgoraVoiceToggleIcon", new Vector2(0f, 10f), 26, TextAnchor.MiddleCenter);
            iconText.text = "MIC";
            iconText.fontStyle = FontStyle.Bold;
            iconText.color = new Color(0.86f, 1f, 0.94f, 1f);
            iconText.rectTransform.sizeDelta = new Vector2(90f, 34f);

            // Status label (center-bottom)
            toggleLabel = FindOrCreateText(parent, VoiceToggleLabelName, new Vector2(0f, -24f), 15, TextAnchor.MiddleCenter);
            toggleLabel.fontStyle = FontStyle.Bold;
            toggleLabel.color = new Color(0.72f, 0.98f, 0.86f, 0.94f);
            toggleLabel.rectTransform.sizeDelta = new Vector2(90f, 24f);

            // Live status dot (top-right corner)
            toggleStatusDot = EnsureStatusDot(parent);
        }

        private void ApplyPanelStyle(GameObject panelObj)
        {
            Image panelImage = panelObj.GetComponent<Image>();
            if (panelImage == null) panelImage = panelObj.AddComponent<Image>();
            panelImage.color = new Color(0.06f, 0.09f, 0.14f, 0.96f);

            Outline panelOutline = panelObj.GetComponent<Outline>();
            if (panelOutline == null) panelOutline = panelObj.AddComponent<Outline>();
            panelOutline.effectColor = new Color(0.18f, 0.88f, 0.60f, 0.18f);
            panelOutline.effectDistance = new Vector2(2f, -2f);

            Shadow panelShadow = panelObj.GetComponent<Shadow>();
            if (panelShadow == null) panelShadow = panelObj.AddComponent<Shadow>();
            panelShadow.effectColor = new Color(0f, 0f, 0f, 0.55f);
            panelShadow.effectDistance = new Vector2(0f, -14f);
        }

        private RectTransform CreatePanelRootLayout(Transform parent)
        {
            GameObject obj = new GameObject(VoicePanelRootName, typeof(RectTransform), typeof(VerticalLayoutGroup), typeof(ContentSizeFitter));
            obj.transform.SetParent(parent, false);
            RectTransform rect = obj.GetComponent<RectTransform>();
            rect.anchorMin = Vector2.zero;
            rect.anchorMax = Vector2.one;
            rect.offsetMin = new Vector2(20f, 20f);
            rect.offsetMax = new Vector2(-20f, -20f);

            VerticalLayoutGroup layout = obj.GetComponent<VerticalLayoutGroup>();
            layout.spacing = 16f;
            layout.padding = new RectOffset(0, 0, 0, 0);
            layout.childAlignment = TextAnchor.UpperCenter;
            layout.childControlHeight = false;
            layout.childControlWidth = true;
            layout.childForceExpandHeight = false;
            layout.childForceExpandWidth = true;

            ContentSizeFitter fitter = obj.GetComponent<ContentSizeFitter>();
            fitter.verticalFit = ContentSizeFitter.FitMode.Unconstrained;
            fitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
            return rect;
        }

        // ────────────────────────────────────────────────────────────────────

        private void HideNonVoiceChildren(Transform parent)
        {
            foreach (Transform child in parent)
            {
                if (child.name.StartsWith("AgoraVoice", StringComparison.Ordinal))
                {
                    child.gameObject.SetActive(true);
                    continue;
                }

                child.gameObject.SetActive(false);
            }
        }

        private void EnsureDecorativeCircle(Transform parent, string name, Vector2 size, Color color)
        {
            Transform existing = parent.Find(name);
            GameObject circleObject = existing != null
                ? existing.gameObject
                : new GameObject(name, typeof(RectTransform), typeof(Image));

            if (existing == null)
            {
                circleObject.transform.SetParent(parent, false);
            }

            RectTransform rect = circleObject.GetComponent<RectTransform>();
            rect.anchorMin = new Vector2(0.5f, 0.5f);
            rect.anchorMax = new Vector2(0.5f, 0.5f);
            rect.pivot = new Vector2(0.5f, 0.5f);
            rect.anchoredPosition = Vector2.zero;
            rect.sizeDelta = size;

            Image image = circleObject.GetComponent<Image>();
            image.color = color;
        }

        private Image EnsureStatusDot(Transform parent)
        {
            Transform existing = parent.Find("AgoraVoiceStatusDot");
            GameObject dotObject = existing != null
                ? existing.gameObject
                : new GameObject("AgoraVoiceStatusDot", typeof(RectTransform), typeof(Image));

            if (existing == null)
            {
                dotObject.transform.SetParent(parent, false);
            }

            RectTransform dotRect = dotObject.GetComponent<RectTransform>();
            dotRect.anchorMin = new Vector2(1f, 1f);
            dotRect.anchorMax = new Vector2(1f, 1f);
            dotRect.pivot = new Vector2(1f, 1f);
            dotRect.anchoredPosition = new Vector2(-10f, -10f);
            dotRect.sizeDelta = new Vector2(14f, 14f);

            Image dotImage = dotObject.GetComponent<Image>();
            dotImage.color = new Color(0.88f, 0.22f, 0.22f, 1f);
            return dotImage;
        }

        private Canvas CreateOverlayCanvas()
        {
            if (rootCanvas != null && rootCanvas.name == VoiceOverlayCanvasName)
            {
                return rootCanvas;
            }

            GameObject existing = GameObject.Find(VoiceOverlayCanvasName);
            if (existing != null)
            {
                rootCanvas = existing.GetComponent<Canvas>();
                return rootCanvas;
            }

            GameObject canvasObject = new GameObject(
                VoiceOverlayCanvasName,
                typeof(RectTransform),
                typeof(Canvas),
                typeof(CanvasScaler),
                typeof(GraphicRaycaster)
            );

            rootCanvas = canvasObject.GetComponent<Canvas>();
            rootCanvas.renderMode = RenderMode.ScreenSpaceOverlay;
            rootCanvas.sortingOrder = 500;

            CanvasScaler scaler = canvasObject.GetComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1080f, 1920f);
            scaler.matchWidthOrHeight = 0.5f;

            return rootCanvas;
        }

        private GameObject EnsureOverlayToggle(Transform parent)
        {
            Transform existing = parent.Find(VoiceOverlayToggleName);
            GameObject toggleObject = existing != null
                ? existing.gameObject
                : new GameObject(VoiceOverlayToggleName, typeof(RectTransform), typeof(Image), typeof(Button));

            if (existing == null)
            {
                toggleObject.transform.SetParent(parent, false);
            }

            RectTransform rect = toggleObject.GetComponent<RectTransform>();
            rect.anchorMin = new Vector2(1f, 0f);
            rect.anchorMax = new Vector2(1f, 0f);
            rect.pivot = new Vector2(1f, 0f);
            rect.anchoredPosition = new Vector2(-28f, 180f);
            rect.sizeDelta = new Vector2(100f, 100f);

            ApplyToggleStyle(toggleObject);
            BuildToggleDecorations(toggleObject.transform);

            return toggleObject;
        }

        private RectTransform EnsureOverlayPanel(Transform parent)
        {
            Transform existing = parent.Find(VoiceOverlayPanelName);
            if (existing != null)
            {
                overlayPanelObject = existing.gameObject;
                return existing as RectTransform;
            }

            GameObject panelObject = new GameObject(VoiceOverlayPanelName, typeof(RectTransform), typeof(Image));
            panelObject.transform.SetParent(parent, false);
            overlayPanelObject = panelObject;

            RectTransform rect = panelObject.GetComponent<RectTransform>();
            rect.anchorMin = new Vector2(1f, 0f);
            rect.anchorMax = new Vector2(1f, 0f);
            rect.pivot = new Vector2(1f, 0f);
            rect.anchoredPosition = new Vector2(-24f, 296f);
            rect.sizeDelta = new Vector2(400f, 340f);

            ApplyPanelStyle(panelObject);

            overlayPanelCanvasGroup = panelObject.AddComponent<CanvasGroup>();
            overlayPanelCanvasGroup.alpha = 0f;
            overlayPanelCanvasGroup.interactable = false;
            overlayPanelCanvasGroup.blocksRaycasts = false;

            panelRoot = CreatePanelRootLayout(panelObject.transform);
            return panelRoot;
        }

        private RectTransform EnsurePanelRoot()
        {
            foreach (Transform child in manualPanel.transform)
            {
                if (child.name == VoicePanelRootName)
                {
                    return child as RectTransform;
                }
            }

            GameObject panelRootObject = new GameObject(VoicePanelRootName, typeof(RectTransform), typeof(Image), typeof(VerticalLayoutGroup), typeof(ContentSizeFitter));
            panelRootObject.transform.SetParent(manualPanel.transform, false);
            RectTransform rect = panelRootObject.GetComponent<RectTransform>();
            rect.anchorMin = Vector2.zero;
            rect.anchorMax = Vector2.one;
            rect.offsetMin = new Vector2(20f, 20f);
            rect.offsetMax = new Vector2(-20f, -20f);

            Image background = panelRootObject.GetComponent<Image>();
            background.color = new Color(0.08f, 0.11f, 0.16f, 0.92f);

            VerticalLayoutGroup layout = panelRootObject.GetComponent<VerticalLayoutGroup>();
            layout.spacing = 14f;
            layout.padding = new RectOffset(18, 18, 18, 18);
            layout.childAlignment = TextAnchor.UpperCenter;
            layout.childControlHeight = false;
            layout.childControlWidth = true;
            layout.childForceExpandHeight = false;
            layout.childForceExpandWidth = true;

            ContentSizeFitter fitter = panelRootObject.GetComponent<ContentSizeFitter>();
            fitter.verticalFit = ContentSizeFitter.FitMode.Unconstrained;
            fitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;

            return rect;
        }

        private void BuildVoicePanel(RectTransform parent)
        {
            // ── Header bar: title + close button side by side ──────────────
            RectTransform headerRow = CreateRow(parent, "AgoraVoiceHeaderRow");
            LayoutElement headerLayout = headerRow.gameObject.AddComponent<LayoutElement>();
            headerLayout.minHeight = 52f;
            headerLayout.preferredHeight = 52f;

            // Accent strip behind header
            Image headerBg = headerRow.gameObject.AddComponent<Image>();
            headerBg.color = new Color(0.05f, 0.38f, 0.27f, 0.55f);

            Text titleLabel = CreateText(headerRow, "AgoraVoiceTitleLabel", 22, FontStyle.Bold, TextAnchor.MiddleLeft);
            titleLabel.text = "  VOICE CHAT";
            titleLabel.color = new Color(0.72f, 1f, 0.88f, 1f);
            LayoutElement titleEl = titleLabel.gameObject.AddComponent<LayoutElement>();
            titleEl.flexibleWidth = 1f;
            titleEl.minHeight = 52f;

            closeButton = CreateIconButton(headerRow, "AgoraVoiceCloseBtn", "X", new Color(0.62f, 0.14f, 0.18f, 0.92f), 18);
            LayoutElement closeEl = closeButton.gameObject.AddComponent<LayoutElement>();
            closeEl.minWidth = 48f;
            closeEl.preferredWidth = 48f;
            closeEl.minHeight = 44f;
            closeButton.onClick.AddListener(() => SetPanelOpen(false));

            // ── Status pill ────────────────────────────────────────────────
            RectTransform statusRow = CreateRow(parent, "AgoraVoiceStatusRow");
            LayoutElement statusRowEl = statusRow.gameObject.AddComponent<LayoutElement>();
            statusRowEl.minHeight = 38f;
            statusRowEl.preferredHeight = 38f;
            Image statusBg = statusRow.gameObject.AddComponent<Image>();
            statusBg.color = new Color(1f, 1f, 1f, 0.05f);

            Text statusDotIcon = CreateText(statusRow, "AgoraVoiceStatusIcon", 20, FontStyle.Bold, TextAnchor.MiddleCenter);
            statusDotIcon.text = "●";
            statusDotIcon.color = new Color(0.88f, 0.22f, 0.22f, 1f);
            LayoutElement dotEl = statusDotIcon.gameObject.AddComponent<LayoutElement>();
            dotEl.minWidth = 32f;
            dotEl.preferredWidth = 32f;
            dotEl.minHeight = 38f;

            statusLabel = CreateText(statusRow, VoiceStatusLabelName, 18, FontStyle.Normal, TextAnchor.MiddleLeft);
            statusLabel.color = new Color(0.78f, 0.93f, 0.88f, 1f);
            LayoutElement statusEl = statusLabel.gameObject.AddComponent<LayoutElement>();
            statusEl.flexibleWidth = 1f;
            statusEl.minHeight = 38f;

            // ── Users list ─────────────────────────────────────────────────
            usersLabel = CreateText(parent, VoiceUsersLabelName, 17, FontStyle.Normal, TextAnchor.UpperLeft);
            usersLabel.color = new Color(0.80f, 0.88f, 0.95f, 0.90f);
            usersLabel.horizontalOverflow = HorizontalWrapMode.Wrap;
            usersLabel.verticalOverflow = VerticalWrapMode.Overflow;
            LayoutElement usersEl = usersLabel.gameObject.AddComponent<LayoutElement>();
            usersEl.minHeight = 72f;
            usersEl.preferredHeight = 72f;

            // ── Divider line ───────────────────────────────────────────────
            GameObject divider = new GameObject("AgoraVoiceDivider", typeof(RectTransform), typeof(Image));
            divider.transform.SetParent(parent, false);
            divider.GetComponent<Image>().color = new Color(1f, 1f, 1f, 0.08f);
            LayoutElement divEl = divider.AddComponent<LayoutElement>();
            divEl.minHeight = 1f;
            divEl.preferredHeight = 1f;

            // ── Action buttons row ─────────────────────────────────────────
            RectTransform buttonsRow = CreateRow(parent, "AgoraVoiceButtons");
            LayoutElement btnRowEl = buttonsRow.gameObject.AddComponent<LayoutElement>();
            btnRowEl.minHeight = 58f;
            btnRowEl.preferredHeight = 58f;

            micButton = CreateIconButton(buttonsRow, "AgoraMicBtn", "MUTE", new Color(0.10f, 0.52f, 0.38f, 1f), 17);
            speakerButton = CreateIconButton(buttonsRow, "AgoraSpeakerBtn", "SPEAKER", new Color(0.16f, 0.34f, 0.60f, 1f), 17);
            leaveButton = CreateIconButton(buttonsRow, "AgoraLeaveBtn", "LEAVE", new Color(0.65f, 0.14f, 0.18f, 1f), 17);

            micButton.onClick.AddListener(ToggleMic);
            speakerButton.onClick.AddListener(ToggleSpeaker);
            leaveButton.onClick.AddListener(() =>
            {
                LeaveVoice();
                SetPanelOpen(false);
            });
        }

        private RectTransform CreateRow(Transform parent, string name)
        {
            GameObject rowObject = new GameObject(name, typeof(RectTransform), typeof(HorizontalLayoutGroup));
            rowObject.transform.SetParent(parent, false);

            HorizontalLayoutGroup layout = rowObject.GetComponent<HorizontalLayoutGroup>();
            layout.spacing = 12f;
            layout.childAlignment = TextAnchor.MiddleCenter;
            layout.childControlWidth = true;
            layout.childControlHeight = false;
            layout.childForceExpandWidth = true;
            layout.childForceExpandHeight = false;

            return rowObject.GetComponent<RectTransform>();
        }

        private Button CreateActionButton(Transform parent, string label, Color backgroundColor)
        {
            return CreateIconButton(parent, label + "Button", label, backgroundColor, 18);
        }

        private Button CreateIconButton(Transform parent, string objName, string label, Color backgroundColor, int fontSize)
        {
            GameObject buttonObject = new GameObject(objName, typeof(RectTransform), typeof(Image), typeof(Button), typeof(LayoutElement));
            buttonObject.transform.SetParent(parent, false);

            Image image = buttonObject.GetComponent<Image>();
            image.color = backgroundColor;

            Outline outline = buttonObject.AddComponent<Outline>();
            outline.effectColor = new Color(1f, 1f, 1f, 0.10f);
            outline.effectDistance = new Vector2(1f, -1f);

            Shadow shadow = buttonObject.AddComponent<Shadow>();
            shadow.effectColor = new Color(0f, 0f, 0f, 0.35f);
            shadow.effectDistance = new Vector2(0f, -4f);

            LayoutElement layout = buttonObject.GetComponent<LayoutElement>();
            layout.preferredHeight = 52f;
            layout.minHeight = 52f;
            layout.preferredWidth = 0f;
            layout.flexibleWidth = 1f;

            Text text = CreateText(buttonObject.transform, objName + "Label", fontSize, FontStyle.Bold, TextAnchor.MiddleCenter);
            text.text = label;
            text.color = new Color(0.94f, 0.98f, 1f, 1f);

            RectTransform textRect = text.rectTransform;
            textRect.anchorMin = Vector2.zero;
            textRect.anchorMax = Vector2.one;
            textRect.offsetMin = Vector2.zero;
            textRect.offsetMax = Vector2.zero;

            return buttonObject.GetComponent<Button>();
        }

        private Text CreateText(Transform parent, string name, int fontSize, FontStyle fontStyle, TextAnchor alignment)
        {
            GameObject textObject = new GameObject(name, typeof(RectTransform), typeof(Text));
            textObject.transform.SetParent(parent, false);

            Text text = textObject.GetComponent<Text>();
            text.font = GetUiFont();
            text.fontSize = fontSize;
            text.fontStyle = fontStyle;
            text.alignment = alignment;
            text.color = Color.white;
            text.horizontalOverflow = HorizontalWrapMode.Wrap;
            text.verticalOverflow = VerticalWrapMode.Overflow;
            text.text = string.Empty;
            return text;
        }

        private Text FindOrCreateText(Transform parent, string name, Vector2 anchoredPosition, int fontSize, TextAnchor alignment)
        {
            foreach (Transform child in parent)
            {
                if (child.name == name)
                {
                    Text existing = child.GetComponent<Text>();
                    if (existing != null)
                    {
                        return existing;
                    }
                }
            }

            GameObject labelObject = new GameObject(name, typeof(RectTransform), typeof(Text));
            labelObject.transform.SetParent(parent, false);
            RectTransform rect = labelObject.GetComponent<RectTransform>();
            rect.anchorMin = new Vector2(0.5f, 0.5f);
            rect.anchorMax = new Vector2(0.5f, 0.5f);
            rect.pivot = new Vector2(0.5f, 0.5f);
            rect.anchoredPosition = anchoredPosition;
            rect.sizeDelta = new Vector2(180f, 34f);

            Text text = labelObject.GetComponent<Text>();
            text.font = GetUiFont();
            text.fontSize = fontSize;
            text.alignment = alignment;
            text.color = Color.white;
            return text;
        }

        private Font GetUiFont()
        {
            if (cachedUiFont != null)
            {
                return cachedUiFont;
            }

            cachedUiFont = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            return cachedUiFont;
        }

        private void SetPanelOpen(bool open)
        {
            isPanelOpen = open && isVoiceAvailable;
            if (overlayPanelObject == null || panelRoot == null)
            {
                return;
            }

            if (panelAnimationCoroutine != null)
            {
                StopCoroutine(panelAnimationCoroutine);
            }

            panelAnimationCoroutine = StartCoroutine(AnimatePanelVisibility(isPanelOpen));
        }

        private IEnumerator AnimatePanelVisibility(bool show)
        {
            overlayPanelObject.SetActive(true);
            panelRoot.gameObject.SetActive(true);

            RectTransform panelRect = overlayPanelObject.GetComponent<RectTransform>();
            Vector3 hiddenScale = new Vector3(0.92f, 0.92f, 1f);
            Vector3 shownScale = Vector3.one;
            float fromAlpha = overlayPanelCanvasGroup != null ? overlayPanelCanvasGroup.alpha : (show ? 0f : 1f);
            float toAlpha = show ? 1f : 0f;
            Vector3 fromScale = show ? hiddenScale : panelRect.localScale;
            Vector3 toScale = show ? shownScale : hiddenScale;
            float duration = 0.16f;
            float elapsed = 0f;

            if (show)
            {
                panelRect.localScale = hiddenScale;
            }

            while (elapsed < duration)
            {
                elapsed += Time.unscaledDeltaTime;
                float t = Mathf.Clamp01(elapsed / duration);
                float eased = 1f - Mathf.Pow(1f - t, 3f);

                if (overlayPanelCanvasGroup != null)
                {
                    overlayPanelCanvasGroup.alpha = Mathf.Lerp(fromAlpha, toAlpha, eased);
                }

                panelRect.localScale = Vector3.Lerp(fromScale, toScale, eased);
                yield return null;
            }

            if (overlayPanelCanvasGroup != null)
            {
                overlayPanelCanvasGroup.alpha = toAlpha;
                overlayPanelCanvasGroup.interactable = show;
                overlayPanelCanvasGroup.blocksRaycasts = show;
            }

            panelRect.localScale = toScale;
            overlayPanelObject.SetActive(show);
            panelRoot.gameObject.SetActive(show);
            panelAnimationCoroutine = null;
        }

        private IEnumerator FetchTokenAndJoin(string channelName, bool renewOnly)
        {
            isJoining = !renewOnly;
            UpdateStatus(renewOnly ? "Refreshing voice token..." : "Connecting voice...");
            RequestMicrophonePermissionIfNeeded();

            if (!TryGetLocalUid(out uint localUid))
            {
                isJoining = false;
                UpdateStatus("Voice UID invalid");
                yield break;
            }

            string url = Configuration.AgoraTokenUrl
                + "?channel="
                + UnityWebRequest.EscapeURL(channelName)
                + "&uid="
                + localUid;

            using (UnityWebRequest request = UnityWebRequest.Get(url))
            {
                request.SetRequestHeader("Authorization", "Bearer " + Configuration.GetToken());
                yield return request.SendWebRequest();

                if (request.result != UnityWebRequest.Result.Success)
                {
                    isJoining = false;
                    UpdateStatus("Voice token request failed");
                    ScheduleReconnect();
                    yield break;
                }

                AgoraTokenApiResponse response = null;
                try
                {
                    response = JsonConvert.DeserializeObject<AgoraTokenApiResponse>(request.downloadHandler.text);
                }
                catch (Exception ex)
                {
                    string rawPreview = request.downloadHandler.text;
                    if (!string.IsNullOrEmpty(rawPreview) && rawPreview.Length > 180)
                    {
                        rawPreview = rawPreview.Substring(0, 180);
                    }

                    Debug.LogWarning("Agora token parse failed: " + ex.Message + " | response: " + rawPreview);
                }

                if (response?.data == null || string.IsNullOrWhiteSpace(response.data.token) || string.IsNullOrWhiteSpace(response.data.appId))
                {
                    isJoining = false;
                    UpdateStatus("Voice token invalid");
                    ScheduleReconnect();
                    yield break;
                }

                currentChannelName = response.data.channel;
                currentToken = response.data.token;
                currentAppId = response.data.appId;
                currentUid = response.data.uid;

                EnsureRtcEngine();
                if (rtcEngine == null)
                {
                    isJoining = false;
                    UpdateStatus("Voice engine init failed");
                    yield break;
                }

                if (renewOnly && isJoined)
                {
                    rtcEngine.RenewToken(currentToken);
                    UpdateStatus("Voice token renewed");
                    yield break;
                }

                ChannelMediaOptions options = new ChannelMediaOptions();
                options.autoSubscribeAudio.SetValue(true);
                options.autoSubscribeVideo.SetValue(false);
                options.publishMicrophoneTrack.SetValue(true);
                options.clientRoleType.SetValue(CLIENT_ROLE_TYPE.CLIENT_ROLE_BROADCASTER);
                options.channelProfile.SetValue(CHANNEL_PROFILE_TYPE.CHANNEL_PROFILE_COMMUNICATION);

                rtcEngine.EnableAudio();
                rtcEngine.SetChannelProfile(CHANNEL_PROFILE_TYPE.CHANNEL_PROFILE_COMMUNICATION);
                rtcEngine.SetClientRole(CLIENT_ROLE_TYPE.CLIENT_ROLE_BROADCASTER);
                rtcEngine.SetAudioScenario(AUDIO_SCENARIO_TYPE.AUDIO_SCENARIO_GAME_STREAMING);
                rtcEngine.SetEnableSpeakerphone(isSpeakerEnabled);
                rtcEngine.MuteLocalAudioStream(isMuted);
                rtcEngine.EnableAudioVolumeIndication(400, 3, true);
                rtcEngine.JoinChannel(currentToken, currentChannelName, currentUid, options);
            }
        }

        private void EnsureRtcEngine()
        {
            if (engineInitialized && rtcEngine != null)
            {
                return;
            }

            if (string.IsNullOrWhiteSpace(currentAppId))
            {
                return;
            }

            rtcEngine = RtcEngine.CreateAgoraRtcEngine();
            voiceEventHandler = new VoiceEventHandler(this);

            RtcEngineContext context = new RtcEngineContext(
                currentAppId,
                0,
                CHANNEL_PROFILE_TYPE.CHANNEL_PROFILE_COMMUNICATION,
                AUDIO_SCENARIO_TYPE.AUDIO_SCENARIO_GAME_STREAMING
            );

            rtcEngine.Initialize(context);
            rtcEngine.InitEventHandler(voiceEventHandler);
            engineInitialized = true;
        }

        private void DisposeRtcEngine()
        {
            if (rtcEngine == null)
            {
                return;
            }

            rtcEngine.InitEventHandler(null);
            rtcEngine.LeaveChannel();
            rtcEngine.Dispose();
            rtcEngine = null;
            voiceEventHandler = null;
            engineInitialized = false;
        }

        private void RequestMicrophonePermissionIfNeeded()
        {
#if UNITY_ANDROID && !UNITY_EDITOR
            if (!Permission.HasUserAuthorizedPermission(Permission.Microphone))
            {
                Permission.RequestUserPermission(Permission.Microphone);
            }
#endif
        }

        private bool TryGetLocalUid(out uint uid)
        {
            uid = 0;
            if (!int.TryParse(Configuration.GetId(), out int localUserId) || localUserId <= 0)
            {
                return false;
            }

            uid = (uint)localUserId;
            return true;
        }

        private void HandleJoinSuccess(RtcConnection connection)
        {
            isJoining = false;
            isJoined = true;
            currentUid = connection.localUid;
            StopReconnectCoroutine();
            UpdateButtonStates();
            UpdateStatus("Voice connected");
        }

        private void HandleLeave()
        {
            isJoining = false;
            isJoined = false;
            connectedRemoteUsers.Clear();
            mutedRemoteUsers.Clear();
            speakingRemoteUsers.Clear();
            UpdateUsersLabel();
            UpdateButtonStates();
        }

        private void HandleRemoteUserJoined(uint uid)
        {
            connectedRemoteUsers.Add(uid);
            UpdateUsersLabel();
        }

        private void HandleRemoteUserLeft(uint uid)
        {
            connectedRemoteUsers.Remove(uid);
            mutedRemoteUsers.Remove(uid);
            speakingRemoteUsers.Remove(uid);
            UpdateUsersLabel();
        }

        private void HandleRemoteUserMute(uint uid, bool muted)
        {
            if (muted)
            {
                mutedRemoteUsers.Add(uid);
                speakingRemoteUsers.Remove(uid);
            }
            else
            {
                mutedRemoteUsers.Remove(uid);
            }

            UpdateUsersLabel();
        }

        private void HandleVolumeIndication(AudioVolumeInfo[] speakers)
        {
            speakingRemoteUsers.Clear();

            if (speakers != null)
            {
                foreach (AudioVolumeInfo speaker in speakers)
                {
                    if (speaker.uid == 0 || speaker.volume <= 0)
                    {
                        continue;
                    }

                    if (mutedRemoteUsers.Contains(speaker.uid))
                    {
                        continue;
                    }

                    speakingRemoteUsers.Add(speaker.uid);
                }
            }

            UpdateUsersLabel();
        }

        private void HandleConnectionStateChanged(CONNECTION_STATE_TYPE state, CONNECTION_CHANGED_REASON_TYPE reason)
        {
            if (state == CONNECTION_STATE_TYPE.CONNECTION_STATE_CONNECTED)
            {
                UpdateStatus("Voice connected");
                StopReconnectCoroutine();
                return;
            }

            if (state == CONNECTION_STATE_TYPE.CONNECTION_STATE_RECONNECTING)
            {
                UpdateStatus("Voice reconnecting...");
                return;
            }

            if (state == CONNECTION_STATE_TYPE.CONNECTION_STATE_FAILED
                || reason == CONNECTION_CHANGED_REASON_TYPE.CONNECTION_CHANGED_INTERRUPTED
                || reason == CONNECTION_CHANGED_REASON_TYPE.CONNECTION_CHANGED_JOIN_FAILED
                || reason == CONNECTION_CHANGED_REASON_TYPE.CONNECTION_CHANGED_TOKEN_EXPIRED)
            {
                UpdateStatus("Voice connection lost");
                ScheduleReconnect();
            }
        }

        private void ScheduleReconnect()
        {
            if (!isVoiceAvailable || string.IsNullOrWhiteSpace(currentChannelName))
            {
                return;
            }

            if (reconnectCoroutine != null)
            {
                return;
            }

            reconnectCoroutine = StartCoroutine(ReconnectAfterDelay());
        }

        private IEnumerator ReconnectAfterDelay()
        {
            yield return new WaitForSeconds(2f);

            reconnectCoroutine = null;
            if (!isVoiceAvailable || string.IsNullOrWhiteSpace(currentChannelName))
            {
                yield break;
            }

            joinCoroutine = StartCoroutine(FetchTokenAndJoin(currentChannelName, renewOnly: false));
        }

        private void StopJoinCoroutine()
        {
            if (joinCoroutine == null)
            {
                return;
            }

            StopCoroutine(joinCoroutine);
            joinCoroutine = null;
            isJoining = false;
        }

        private void StopReconnectCoroutine()
        {
            if (reconnectCoroutine == null)
            {
                return;
            }

            StopCoroutine(reconnectCoroutine);
            reconnectCoroutine = null;
        }

        private void ResetVoiceState(bool preserveAvailability)
        {
            isJoining = false;
            isJoined = false;
            currentChannelName = null;
            currentToken = null;
            currentUid = 0;
            connectedRemoteUsers.Clear();
            mutedRemoteUsers.Clear();
            speakingRemoteUsers.Clear();
            UpdateUsersLabel();
            UpdateButtonStates();

            if (!preserveAvailability)
            {
                isVoiceAvailable = false;
            }
        }

        private void UpdateButtonStates()
        {
            if (toggleLabel != null)
            {
                toggleLabel.text = !isVoiceAvailable ? "OFF" : (isJoined ? "LIVE" : (isJoining ? "..." : "VOICE"));
            }

            if (toggleButton != null)
            {
                Image toggleImage = toggleButton.GetComponent<Image>();
                if (toggleImage != null)
                {
                    if (!isVoiceAvailable)
                        toggleImage.color = new Color(0.22f, 0.22f, 0.25f, 0.80f); // dim grey
                    else if (isJoined)
                        toggleImage.color = new Color(0.04f, 0.55f, 0.37f, 0.98f); // bright green
                    else if (isJoining)
                        toggleImage.color = new Color(0.26f, 0.43f, 0.16f, 0.98f); // olive
                    else
                        toggleImage.color = new Color(0.05f, 0.38f, 0.28f, 0.97f); // dark teal
                }
            }

            if (toggleStatusDot != null)
            {
                if (!isVoiceAvailable)
                    toggleStatusDot.color = new Color(0.45f, 0.45f, 0.48f, 0.80f); // grey
                else if (isJoined)
                    toggleStatusDot.color = new Color(0.21f, 0.95f, 0.54f, 1f);    // green
                else if (isJoining)
                    toggleStatusDot.color = new Color(1f, 0.78f, 0.18f, 1f);       // yellow
                else
                    toggleStatusDot.color = new Color(0.88f, 0.22f, 0.22f, 1f);    // red
            }

            if (micButton != null)
            {
                Text micText = micButton.GetComponentInChildren<Text>();
                if (micText != null)
                {
                    micText.text = isMuted ? "Unmute" : "Mute";
                }
            }

            if (speakerButton != null)
            {
                Text speakerText = speakerButton.GetComponentInChildren<Text>();
                if (speakerText != null)
                {
                    speakerText.text = isSpeakerEnabled ? "Speaker On" : "Speaker Off";
                }
            }

            UpdatePulseState();
        }

        private void UpdateStatus(string message)
        {
            if (statusLabel != null)
            {
                statusLabel.text = message;
            }
        }

        private void UpdateUsersLabel()
        {
            if (usersLabel == null)
            {
                return;
            }

            if (connectedRemoteUsers.Count == 0)
            {
                usersLabel.text = "Connected users: 0";
                return;
            }

            List<uint> sortedUsers = new List<uint>(connectedRemoteUsers);
            sortedUsers.Sort();

            List<string> lines = new List<string>
            {
                "Connected users: " + connectedRemoteUsers.Count
            };

            foreach (uint uid in sortedUsers)
            {
                string state = mutedRemoteUsers.Contains(uid)
                    ? "muted"
                    : (speakingRemoteUsers.Contains(uid) ? "speaking" : "listening");
                lines.Add("UID " + uid + " • " + state);
            }

            usersLabel.text = string.Join("\n", lines);
        }

        private void UpdatePulseState()
        {
            if (toggleStatusDot == null)
            {
                return;
            }

            if (isJoined)
            {
                if (pulseCoroutine == null)
                {
                    pulseCoroutine = StartCoroutine(PulseLiveDot());
                }

                return;
            }

            if (pulseCoroutine != null)
            {
                StopCoroutine(pulseCoroutine);
                pulseCoroutine = null;
            }

            toggleStatusDot.rectTransform.localScale = Vector3.one;
        }

        private IEnumerator PulseLiveDot()
        {
            RectTransform dotRect = toggleStatusDot.rectTransform;
            while (isJoined && toggleStatusDot != null)
            {
                float pulse = 0.85f + Mathf.PingPong(Time.unscaledTime * 1.6f, 0.45f);
                dotRect.localScale = new Vector3(pulse, pulse, 1f);
                Color color = toggleStatusDot.color;
                color.a = 0.78f + Mathf.PingPong(Time.unscaledTime * 1.4f, 0.22f);
                toggleStatusDot.color = color;
                yield return null;
            }

            if (toggleStatusDot != null)
            {
                dotRect.localScale = Vector3.one;
                Color color = toggleStatusDot.color;
                color.a = 1f;
                toggleStatusDot.color = color;
            }

            pulseCoroutine = null;
        }

        private sealed class VoiceEventHandler : IRtcEngineEventHandler
        {
            private readonly AgoraVoiceManager owner;

            public VoiceEventHandler(AgoraVoiceManager owner)
            {
                this.owner = owner;
            }

            public override void OnJoinChannelSuccess(RtcConnection connection, int elapsed)
            {
                owner.HandleJoinSuccess(connection);
            }

            public override void OnLeaveChannel(RtcConnection connection, RtcStats stats)
            {
                owner.HandleLeave();
            }

            public override void OnUserJoined(RtcConnection connection, uint uid, int elapsed)
            {
                owner.HandleRemoteUserJoined(uid);
            }

            public override void OnUserOffline(RtcConnection connection, uint uid, USER_OFFLINE_REASON_TYPE reason)
            {
                owner.HandleRemoteUserLeft(uid);
            }

            [Obsolete]
            public override void OnUserMuteAudio(RtcConnection connection, uint remoteUid, bool muted)
            {
                owner.HandleRemoteUserMute(remoteUid, muted);
            }

            public override void OnAudioVolumeIndication(RtcConnection connection, AudioVolumeInfo[] speakers, uint speakerNumber, int totalVolume)
            {
                owner.HandleVolumeIndication(speakers);
            }

            public override void OnTokenPrivilegeWillExpire(RtcConnection connection, string token)
            {
                owner.StopJoinCoroutine();
                owner.joinCoroutine = owner.StartCoroutine(owner.FetchTokenAndJoin(owner.currentChannelName, renewOnly: true));
            }

            public override void OnConnectionStateChanged(RtcConnection connection, CONNECTION_STATE_TYPE state, CONNECTION_CHANGED_REASON_TYPE reason)
            {
                owner.HandleConnectionStateChanged(state, reason);
            }

            public override void OnError(int err, string msg)
            {
                Debug.LogWarning("Agora voice error " + err + ": " + msg);
                owner.UpdateStatus("Voice error: " + err);
                owner.ScheduleReconnect();
            }
        }

        [Serializable]
        private class AgoraTokenApiResponse
        {
            public bool success;
            public string message;
            public AgoraTokenPayload data;
        }

        [Serializable]
        private class AgoraTokenPayload
        {
            public string appId;
            public string token;
            public string channel;
            public uint uid;
            public int expiresIn;
        }
    }
}
