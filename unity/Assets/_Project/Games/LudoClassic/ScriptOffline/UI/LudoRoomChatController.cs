using System;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoRoomChatController : MonoBehaviour
    {
        private const int MaxVisibleMessages = 50;
        private const string OverlayCanvasName = "LudoChatOverlayCanvas";

        private readonly List<LudoV2ChatMessagePayload> messages = new List<LudoV2ChatMessagePayload>();
        private readonly HashSet<string> messageIds = new HashSet<string>();

        private Canvas rootCanvas;
        private RectTransform rootPanel;
        private RectTransform listContent;
        private ScrollRect scrollRect;
        private InputField inputField;
        private Button toggleButton;
        private Button sendButton;
        private Text unreadBadgeText;
        private GameObject unreadBadgeObject;
        private bool isOpen;
        private bool uiReady;
        private int unreadCount;
        private bool isDebugPreviewEnabled;

        private void Awake()
        {
            TryBuildUi();
        }

        private void Update()
        {
            if (!uiReady)
            {
                TryBuildUi();
                return;
            }

            if (isOpen && inputField != null && inputField.isFocused
                && (Input.GetKeyDown(KeyCode.Return) || Input.GetKeyDown(KeyCode.KeypadEnter)))
            {
                SubmitCurrentMessage();
            }
        }

        private void OnEnable()
        {
            LudoV2MatchmakingBridge.OnChatMessageReceived += HandleChatMessage;
            LudoV2MatchmakingBridge.OnChatHistoryReceived += HandleChatHistory;
        }

        private void OnDisable()
        {
            LudoV2MatchmakingBridge.OnChatMessageReceived -= HandleChatMessage;
            LudoV2MatchmakingBridge.OnChatHistoryReceived -= HandleChatHistory;
        }

        public void SetChatAvailability(bool available)
        {
            TryBuildUi();

            if (toggleButton != null)
            {
                toggleButton.gameObject.SetActive(available);
            }

            if (!available)
            {
                SetPanelOpen(false);
                ResetUnread();
            }
        }

        public void ClearMessages()
        {
            messages.Clear();
            messageIds.Clear();
            unreadCount = 0;
            RefreshUnreadBadge();

            if (listContent == null)
            {
                return;
            }

            for (int i = listContent.childCount - 1; i >= 0; i--)
            {
                Destroy(listContent.GetChild(i).gameObject);
            }
        }

        public void EnableDebugPreview()
        {
            TryBuildUi();
            isDebugPreviewEnabled = true;
            SetChatAvailability(true);

            if (messages.Count > 0)
            {
                return;
            }

            AddDebugMessage("preview-1", "Player 1", 1, "Hello! Chat preview ready.");
            AddDebugMessage("preview-2", "Player 2", 2, "Type and press send.");
        }

        private void TryBuildUi()
        {
            if (uiReady)
            {
                return;
            }

            rootCanvas = CreateOverlayCanvas();
            if (rootCanvas == null)
            {
                return;
            }

            BuildToggle();
            BuildPanel();

            SetPanelOpen(false);
            uiReady = true;
            RefreshUnreadBadge();
        }

        // Draws a speech-bubble icon inside the toggle button using plain Images + Text.
        // Emoji glyphs are unreliable in Unity's legacy Text renderer.
        private void BuildChatIcon(RectTransform parent)
        {
            // Bubble body — white rounded rectangle
            GameObject bubble = CreateUiObject("IconBubble", parent);
            RectTransform bubbleR = bubble.AddComponent<RectTransform>();
            bubbleR.anchorMin = new Vector2(0.5f, 0.5f);
            bubbleR.anchorMax = new Vector2(0.5f, 0.5f);
            bubbleR.pivot     = new Vector2(0.5f, 0.5f);
            bubbleR.sizeDelta = new Vector2(52f, 36f);
            bubbleR.anchoredPosition = new Vector2(0f, 6f);
            bubble.AddComponent<Image>().color = Color.white;

            // Three dots inside the bubble
            Text dots = CreateLegacyText(bubbleR, "• • •", 18, FontStyle.Bold);
            dots.alignment = TextAnchor.MiddleCenter;
            dots.color = new Color32(0, 168, 132, 255);
            StretchRect(dots.rectTransform, Vector2.zero, Vector2.zero);

            // Tail — small white square rotated/positioned at bottom-left of bubble
            GameObject tail = CreateUiObject("IconTail", parent);
            RectTransform tailR = tail.AddComponent<RectTransform>();
            tailR.anchorMin = new Vector2(0.5f, 0.5f);
            tailR.anchorMax = new Vector2(0.5f, 0.5f);
            tailR.pivot     = new Vector2(0.5f, 1f);
            tailR.sizeDelta = new Vector2(14f, 14f);
            tailR.anchoredPosition = new Vector2(-14f, -12f);
            tail.AddComponent<Image>().color = Color.white;
        }

        private void BuildToggle()
        {
            GameObject toggleObject = CreateUiObject("LudoChatToggle", rootCanvas.transform);
            RectTransform toggleRect = toggleObject.AddComponent<RectTransform>();
            // Bottom-right corner, above the game HUD
            toggleRect.anchorMin = new Vector2(1f, 0f);
            toggleRect.anchorMax = new Vector2(1f, 0f);
            toggleRect.pivot = new Vector2(1f, 0f);
            toggleRect.sizeDelta = new Vector2(86f, 86f);
            toggleRect.anchoredPosition = new Vector2(-18f, 110f);

            Image toggleImage = toggleObject.AddComponent<Image>();
            toggleImage.color = new Color32(0, 168, 132, 255); // WhatsApp green
            toggleButton = toggleObject.AddComponent<Button>();
            toggleButton.targetGraphic = toggleImage;
            ColorBlock cb = toggleButton.colors;
            cb.normalColor = Color.white;
            cb.highlightedColor = new Color32(255, 255, 255, 210);
            cb.pressedColor = new Color32(200, 200, 200, 255);
            toggleButton.colors = cb;
            toggleButton.onClick.AddListener(() => SetPanelOpen(!isOpen));

            // Chat icon — built from shapes (emoji unreliable in legacy Text)
            BuildChatIcon(toggleRect);

            // Unread badge — top-right of button
            unreadBadgeObject = CreateUiObject("UnreadBadge", toggleRect);
            RectTransform badgeRect = unreadBadgeObject.AddComponent<RectTransform>();
            badgeRect.anchorMin = new Vector2(1f, 1f);
            badgeRect.anchorMax = new Vector2(1f, 1f);
            badgeRect.pivot = new Vector2(1f, 1f);
            badgeRect.sizeDelta = new Vector2(30f, 30f);
            badgeRect.anchoredPosition = new Vector2(4f, 4f);
            Image badgeImage = unreadBadgeObject.AddComponent<Image>();
            badgeImage.color = new Color32(220, 50, 50, 255);
            unreadBadgeText = CreateLegacyText(badgeRect, "0", 16, FontStyle.Bold);
            unreadBadgeText.alignment = TextAnchor.MiddleCenter;
            unreadBadgeText.color = Color.white;
            StretchRect(unreadBadgeText.rectTransform, Vector2.zero, Vector2.zero);
        }

        private void BuildPanel()
        {
            // Panel floats above the toggle button on the right side
            GameObject panelObject = CreateUiObject("LudoChatPanel", rootCanvas.transform);
            rootPanel = panelObject.AddComponent<RectTransform>();
            rootPanel.anchorMin = new Vector2(1f, 0f);
            rootPanel.anchorMax = new Vector2(1f, 0f);
            rootPanel.pivot = new Vector2(1f, 0f);
            rootPanel.sizeDelta = new Vector2(430f, 700f);
            rootPanel.anchoredPosition = new Vector2(-18f, 210f); // sits above the toggle

            Image panelImage = panelObject.AddComponent<Image>();
            panelImage.color = new Color32(11, 20, 26, 252); // WA dark bg

            // ── Header ────────────────────────────────────────────────────────
            RectTransform header = CreateUiObject("Header", rootPanel).AddComponent<RectTransform>();
            header.anchorMin = new Vector2(0f, 1f);
            header.anchorMax = new Vector2(1f, 1f);
            header.pivot = new Vector2(0.5f, 1f);
            header.sizeDelta = new Vector2(0f, 62f);
            header.anchoredPosition = Vector2.zero;
            Image headerImage = header.gameObject.AddComponent<Image>();
            headerImage.color = new Color32(32, 44, 51, 255); // WA header gray

            // Avatar circle
            GameObject avatarCircle = CreateUiObject("Avatar", header);
            RectTransform avatarR = avatarCircle.AddComponent<RectTransform>();
            avatarR.anchorMin = new Vector2(0f, 0.5f);
            avatarR.anchorMax = new Vector2(0f, 0.5f);
            avatarR.pivot = new Vector2(0f, 0.5f);
            avatarR.sizeDelta = new Vector2(42f, 42f);
            avatarR.anchoredPosition = new Vector2(14f, 0f);
            Image avatarImg = avatarCircle.AddComponent<Image>();
            avatarImg.color = new Color32(0, 168, 132, 255);
            Text avatarIcon = CreateLegacyText(avatarR, "👥", 22, FontStyle.Normal);
            avatarIcon.alignment = TextAnchor.MiddleCenter;
            StretchRect(avatarIcon.rectTransform, Vector2.zero, Vector2.zero);

            Text title = CreateLegacyText(header, "Room Chat", 24, FontStyle.Bold);
            title.alignment = TextAnchor.MiddleLeft;
            title.color = new Color32(232, 228, 222, 255);
            RectTransform titleRect = title.rectTransform;
            titleRect.anchorMin = new Vector2(0f, 0f);
            titleRect.anchorMax = new Vector2(1f, 1f);
            titleRect.offsetMin = new Vector2(68f, 0f);
            titleRect.offsetMax = new Vector2(-56f, 0f);

            Button closeButton = CreateButton(header, "✕", new Color32(0, 0, 0, 0), 44f, 44f);
            // Transparent bg, just the X text
            closeButton.GetComponent<Image>().color = new Color32(0, 0, 0, 0);
            Text closeLbl = closeButton.GetComponentInChildren<Text>();
            closeLbl.color = new Color32(180, 180, 180, 255);
            closeLbl.fontSize = 26;
            RectTransform closeRect = closeButton.GetComponent<RectTransform>();
            closeRect.anchorMin = new Vector2(1f, 0.5f);
            closeRect.anchorMax = new Vector2(1f, 0.5f);
            closeRect.pivot = new Vector2(1f, 0.5f);
            closeRect.anchoredPosition = new Vector2(-10f, 0f);
            closeButton.onClick.AddListener(() => SetPanelOpen(false));

            // ── Message list ──────────────────────────────────────────────────
            RectTransform scrollRoot = CreateUiObject("ScrollRoot", rootPanel).AddComponent<RectTransform>();
            scrollRoot.anchorMin = new Vector2(0f, 0f);
            scrollRoot.anchorMax = new Vector2(1f, 1f);
            scrollRoot.offsetMin = new Vector2(0f, 68f);
            scrollRoot.offsetMax = new Vector2(0f, -62f);
            Image scrollImage = scrollRoot.gameObject.AddComponent<Image>();
            scrollImage.color = new Color32(11, 20, 26, 255); // same as panel bg
            Mask mask = scrollRoot.gameObject.AddComponent<Mask>();
            mask.showMaskGraphic = false;
            scrollRect = scrollRoot.gameObject.AddComponent<ScrollRect>();
            scrollRect.horizontal = false;
            scrollRect.movementType = ScrollRect.MovementType.Elastic;

            RectTransform content = CreateUiObject("Content", scrollRoot).AddComponent<RectTransform>();
            content.anchorMin = new Vector2(0f, 1f);
            content.anchorMax = new Vector2(1f, 1f);
            content.pivot = new Vector2(0.5f, 1f);
            content.anchoredPosition = Vector2.zero;
            content.sizeDelta = new Vector2(0f, 0f);
            VerticalLayoutGroup contentLayout = content.gameObject.AddComponent<VerticalLayoutGroup>();
            contentLayout.spacing = 4f;
            contentLayout.padding = new RectOffset(10, 10, 10, 10);
            contentLayout.childAlignment = TextAnchor.UpperLeft;
            contentLayout.childControlWidth = true;
            contentLayout.childControlHeight = false;
            contentLayout.childForceExpandWidth = true;
            contentLayout.childForceExpandHeight = false;
            ContentSizeFitter contentFitter = content.gameObject.AddComponent<ContentSizeFitter>();
            contentFitter.verticalFit = ContentSizeFitter.FitMode.PreferredSize;
            contentFitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
            scrollRect.viewport = scrollRoot;
            scrollRect.content = content;
            listContent = content;

            // ── Composer bar ──────────────────────────────────────────────────
            RectTransform composer = CreateUiObject("Composer", rootPanel).AddComponent<RectTransform>();
            composer.anchorMin = new Vector2(0f, 0f);
            composer.anchorMax = new Vector2(1f, 0f);
            composer.pivot = new Vector2(0.5f, 0f);
            composer.sizeDelta = new Vector2(0f, 68f);
            composer.anchoredPosition = Vector2.zero;
            Image composerBg = composer.gameObject.AddComponent<Image>();
            composerBg.color = new Color32(32, 44, 51, 255); // WA composer bar

            // Input field
            GameObject inputObject = CreateUiObject("Input", composer);
            RectTransform inputRect = inputObject.AddComponent<RectTransform>();
            inputRect.anchorMin = new Vector2(0f, 0.5f);
            inputRect.anchorMax = new Vector2(1f, 0.5f);
            inputRect.pivot = new Vector2(0f, 0.5f);
            inputRect.sizeDelta = new Vector2(-100f, 50f);
            inputRect.anchoredPosition = new Vector2(12f, 0f);
            Image inputImage = inputObject.AddComponent<Image>();
            inputImage.color = new Color32(42, 57, 66, 255); // WA input bg
            inputField = inputObject.AddComponent<InputField>();
            inputField.lineType = InputField.LineType.SingleLine;
            inputField.characterLimit = 200;
            inputField.onEndEdit.AddListener(HandleInputEndEdit);

            RectTransform placeholderRect = CreateUiObject("Placeholder", inputRect).AddComponent<RectTransform>();
            StretchRect(placeholderRect, new Vector2(14f, 6f), new Vector2(-14f, -6f));
            Text placeholder = CreateLegacyText(placeholderRect, "Type a message...", 22, FontStyle.Italic);
            placeholder.color = new Color32(100, 115, 122, 255);
            placeholder.alignment = TextAnchor.MiddleLeft;
            StretchRect(placeholder.rectTransform, Vector2.zero, Vector2.zero);

            RectTransform textRect = CreateUiObject("Text", inputRect).AddComponent<RectTransform>();
            StretchRect(textRect, new Vector2(14f, 6f), new Vector2(-14f, -6f));
            Text inputText = CreateLegacyText(textRect, string.Empty, 22, FontStyle.Normal);
            inputText.color = new Color32(232, 228, 222, 255); // WA message text
            inputText.alignment = TextAnchor.MiddleLeft;
            StretchRect(inputText.rectTransform, Vector2.zero, Vector2.zero);

            inputField.placeholder = placeholder;
            inputField.textComponent = inputText;

            // Send button — WA green circle
            sendButton = CreateButton(composer, "➤", new Color32(0, 168, 132, 255), 56f, 56f);
            RectTransform sendRect = sendButton.GetComponent<RectTransform>();
            sendRect.anchorMin = new Vector2(1f, 0.5f);
            sendRect.anchorMax = new Vector2(1f, 0.5f);
            sendRect.pivot = new Vector2(1f, 0.5f);
            sendRect.anchoredPosition = new Vector2(-12f, 0f);
            Text sendLbl = sendButton.GetComponentInChildren<Text>();
            sendLbl.fontSize = 24;
            sendButton.onClick.AddListener(SubmitCurrentMessage);
        }

        private Canvas CreateOverlayCanvas()
        {
            GameObject existingCanvas = GameObject.Find(OverlayCanvasName);
            if (existingCanvas != null)
            {
                Destroy(existingCanvas);
            }

            GameObject canvasObject = new GameObject(
                OverlayCanvasName,
                typeof(RectTransform),
                typeof(Canvas),
                typeof(CanvasScaler),
                typeof(GraphicRaycaster)
            );

            Canvas canvas = canvasObject.GetComponent<Canvas>();
            canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            canvas.overrideSorting = true;
            canvas.sortingOrder = 32720;

            CanvasScaler scaler = canvasObject.GetComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1080f, 1920f);
            scaler.matchWidthOrHeight = 0.5f;

            return canvas;
        }

        private void HandleChatMessage(LudoV2ChatMessagePayload payload)
        {
            if (payload == null)
            {
                return;
            }

            TryBuildUi();
            AddMessage(payload, false);
        }

        private void HandleChatHistory(List<LudoV2ChatMessagePayload> history)
        {
            TryBuildUi();
            ClearMessages();

            if (history == null)
            {
                return;
            }

            for (int i = 0; i < history.Count; i++)
            {
                AddMessage(history[i], true);
            }

            ScrollToBottom();
        }

        private void AddMessage(LudoV2ChatMessagePayload payload, bool fromHistory)
        {
            if (payload == null)
            {
                return;
            }

            string key = string.IsNullOrWhiteSpace(payload.message_id)
                ? $"{payload.created_at}|{payload.sender?.seat_no}|{payload.message}"
                : payload.message_id;

            if (!messageIds.Add(key))
            {
                return;
            }

            messages.Add(payload);
            if (messages.Count > MaxVisibleMessages)
            {
                messages.RemoveAt(0);
            }

            if (listContent != null)
            {
                if (listContent.childCount >= MaxVisibleMessages)
                {
                    Destroy(listContent.GetChild(0).gameObject);
                }

                CreateMessageView(payload);
                ScrollToBottom();
            }

            if (!fromHistory && !isOpen)
            {
                unreadCount += 1;
                RefreshUnreadBadge();
            }
        }

        private void CreateMessageView(LudoV2ChatMessagePayload payload)
        {
            GameObject row = CreateUiObject("ChatMessage", listContent);
            LayoutElement rowLayout = row.AddComponent<LayoutElement>();
            VerticalLayoutGroup rowGroup = row.AddComponent<VerticalLayoutGroup>();
            rowGroup.spacing = 2f;
            rowGroup.padding = new RectOffset(12, 12, 8, 8);
            rowGroup.childControlWidth = true;
            rowGroup.childControlHeight = true;
            rowGroup.childForceExpandWidth = true;
            rowGroup.childForceExpandHeight = false;

            Image bubble = row.AddComponent<Image>();
            bubble.color = new Color32(32, 44, 51, 255); // default; Bind() overrides

            // Sender name
            Text sender = CreateLegacyText(row.transform, string.Empty, 20, FontStyle.Bold);
            sender.color = new Color32(0, 168, 132, 255); // Bind() overrides
            sender.alignment = TextAnchor.MiddleLeft;
            sender.horizontalOverflow = HorizontalWrapMode.Wrap;

            // Message body
            Text body = CreateLegacyText(row.transform, string.Empty, 22, FontStyle.Normal);
            body.color = new Color32(232, 228, 222, 255); // Bind() overrides
            body.alignment = TextAnchor.UpperLeft;
            body.horizontalOverflow = HorizontalWrapMode.Wrap;
            body.verticalOverflow = VerticalWrapMode.Overflow;

            LudoRoomChatMessageItem item = row.AddComponent<LudoRoomChatMessageItem>();
            item.Initialize(sender, body, bubble, rowLayout);
            item.Bind(payload, IsLocalMessage(payload));
        }

        private bool IsLocalMessage(LudoV2ChatMessagePayload payload)
        {
            if (payload?.sender?.user_id == null)
            {
                return false;
            }

            if (!int.TryParse(Configuration.GetId(), out int localUserId))
            {
                return false;
            }

            return payload.sender.user_id.Value == localUserId;
        }

        private void SubmitCurrentMessage()
        {
            if (inputField == null)
            {
                return;
            }

            string message = (inputField.text ?? string.Empty).Trim();
            if (string.IsNullOrWhiteSpace(message))
            {
                return;
            }

            if (IsLocalPreviewMode())
            {
                AddDebugMessage(Guid.NewGuid().ToString("N"), "You", 0, message);
                inputField.text = string.Empty;
                inputField.ActivateInputField();
                return;
            }

            if (LudoV2MatchmakingBridge.Instance == null || !LudoV2MatchmakingBridge.Instance.TrySendChatMessage(message))
            {
                CommonUtil.ShowToast("Chat is not ready");
                return;
            }

            inputField.text = string.Empty;
            inputField.ActivateInputField();
        }

        private void HandleInputEndEdit(string value)
        {
            if (!isOpen || string.IsNullOrWhiteSpace(value))
            {
                return;
            }

            if (Input.GetKeyDown(KeyCode.Return) || Input.GetKeyDown(KeyCode.KeypadEnter))
            {
                SubmitCurrentMessage();
            }
        }

        private bool IsLocalPreviewMode()
        {
            return isDebugPreviewEnabled
                || (DashBoardManagerOffline.instance != null && DashBoardManagerOffline.instance.IsPassAndPlay);
        }

        private void AddDebugMessage(string messageId, string displayName, int seatNo, string body)
        {
            AddMessage(
                new LudoV2ChatMessagePayload
                {
                    message_id = messageId,
                    room_id = "debug-preview",
                    message_type = "text",
                    sender_type = "human",
                    message = body,
                    sender = new LudoV2ChatSenderPayload
                    {
                        user_id = seatNo == 0 ? ParseLocalUserId() : (int?)null,
                        seat_no = seatNo,
                        display_name = displayName,
                        player_id = seatNo.ToString(),
                    },
                    created_at = DateTime.UtcNow.ToString("o"),
                },
                false
            );
        }

        private int? ParseLocalUserId()
        {
            return int.TryParse(Configuration.GetId(), out int localUserId) ? localUserId : (int?)null;
        }

        private void SetPanelOpen(bool shouldOpen)
        {
            isOpen = shouldOpen;

            if (rootPanel != null)
            {
                rootPanel.gameObject.SetActive(shouldOpen);
            }

            if (shouldOpen)
            {
                ResetUnread();
                ScrollToBottom();
                if (!IsLocalPreviewMode() && LudoV2MatchmakingBridge.Instance != null)
                {
                    LudoV2MatchmakingBridge.Instance.TryRequestChatHistory();
                }
            }
        }

        private void ResetUnread()
        {
            unreadCount = 0;
            RefreshUnreadBadge();
        }

        private void RefreshUnreadBadge()
        {
            if (unreadBadgeObject == null || unreadBadgeText == null)
            {
                return;
            }

            unreadBadgeObject.SetActive(unreadCount > 0);
            unreadBadgeText.text = unreadCount > 99 ? "99+" : unreadCount.ToString();
        }

        private void ScrollToBottom()
        {
            if (scrollRect == null)
            {
                return;
            }

            Canvas.ForceUpdateCanvases();
            scrollRect.verticalNormalizedPosition = 0f;
        }

        private static GameObject CreateUiObject(string name, Transform parent)
        {
            GameObject gameObject = new GameObject(name, typeof(CanvasRenderer));
            gameObject.transform.SetParent(parent, false);
            return gameObject;
        }

        private static void StretchRect(RectTransform rectTransform, Vector2 offsetMin, Vector2 offsetMax)
        {
            rectTransform.anchorMin = Vector2.zero;
            rectTransform.anchorMax = Vector2.one;
            rectTransform.offsetMin = offsetMin;
            rectTransform.offsetMax = offsetMax;
        }

        private static Text CreateLegacyText(Transform parent, string value, int size, FontStyle style)
        {
            GameObject labelObject = CreateUiObject("Text", parent);
            Text text = labelObject.AddComponent<Text>();
            text.text = value;
            text.font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            text.fontSize = size;
            text.fontStyle = style;
            text.color = Color.white;
            return text;
        }

        private static Button CreateButton(Transform parent, string label, Color color, float width, float height)
        {
            GameObject buttonObject = CreateUiObject(label + "Button", parent);
            RectTransform rect = buttonObject.AddComponent<RectTransform>();
            rect.sizeDelta = new Vector2(width, height);
            Image image = buttonObject.AddComponent<Image>();
            image.color = color;
            Button button = buttonObject.AddComponent<Button>();
            Text labelText = CreateLegacyText(buttonObject.transform, label, 18, FontStyle.Bold);
            labelText.alignment = TextAnchor.MiddleCenter;
            StretchRect(labelText.rectTransform, Vector2.zero, Vector2.zero);
            return button;
        }
    }
}
