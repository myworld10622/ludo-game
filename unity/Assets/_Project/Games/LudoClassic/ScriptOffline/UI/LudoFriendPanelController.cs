using System;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.U2D;
using UnityEngine.UI;
#if UNITY_EDITOR
using UnityEditor;
#endif

namespace LudoClassicOffline
{
    public class LudoFriendPanelController : MonoBehaviour
    {
        private const string OverlayCanvasName = "LudoFriendOverlayCanvas";
        private static LudoFriendPanelController instance;
        private const string HomeFriendIconAssetPath = "Assets/_Project/Core/UI/HomePage/Icons/friends-icon.png";
        private const string HomePopupBackgroundAssetPath = "Assets/_Project/Core/UI/Common/Popups/home-notification-banner-bg.png";
        private const string BoxSpriteAssetPath = "Assets/_Project/Core/UI/HomePage/Icons/Box.png";
        private enum FriendUiMode
        {
            Hidden,
            Home,
        }

        private readonly Dictionary<GameObject, string> boundSlotUsers = new Dictionary<GameObject, string>();

        private Canvas rootCanvas;
        private RectTransform rootPanel;
        private RectTransform roomPopup;
        private RectTransform requestsContent;
        private Button toggleButton;
        private Button addByIdButton;
        private Button requestsTabButton;
        private Button friendsTabButton;
        private Button roomAddFriendButton;
        private TMP_InputField playerIdInput;
        private TextMeshProUGUI resultTitleText;
        private TextMeshProUGUI resultSubtitleText;
        private TextMeshProUGUI listSectionTitleText;
        private TextMeshProUGUI roomPopupTitleText;
        private TextMeshProUGUI requestsStateText;
        private TextMeshProUGUI requestBadgeText;
        private TextMeshProUGUI toggleCaptionText;
        private GameObject requestBadgeObject;
        private GameObject sceneShortcutBadgeObject;
        private Text sceneShortcutBadgeText;
        private bool uiReady;
        private bool roomActionsAvailable;
        private bool homeShortcutAvailable;
        private string searchedPlayerId;
        private LudoFriendUserData searchedUser;
        private string roomPopupUserId;
        private float nextRequestsRefreshAt;
        private bool footerAnchorResolved;
        private bool showingFriendsList;

        private void Awake()
        {
            instance = this;
            TryBuildUi();
            SetRoomActionAvailability(false);
        }

        private void OnDestroy()
        {
            if (instance == this)
            {
                instance = null;
            }
        }

        private void Update()
        {
            if (!uiReady)
            {
                TryBuildUi();
            }

            if (Time.unscaledTime >= nextRequestsRefreshAt)
            {
                nextRequestsRefreshAt = Time.unscaledTime + 15f;
                RefreshActiveList();
            }

            UpdateTogglePlacement();

            if (roomActionsAvailable)
            {
                RefreshRoomActionButtons();
            }
        }

        public static void RefreshRoomPlayerActionsIfPresent()
        {
            instance?.RefreshRoomActionButtons();
        }

        public void SetRoomActionAvailability(bool available)
        {
            roomActionsAvailable = available;
            if (!available)
            {
                HideRoomPopup();
            }

            RefreshRoomActionButtons();
        }

        public void SetHomeShortcutAvailability(bool available)
        {
            homeShortcutAvailable = available;
            TryBuildUi();

            if (toggleButton != null)
            {
                toggleButton.gameObject.SetActive(available);
            }

            if (!available)
            {
                HideRoomPopup();
                SetPanelOpen(false);
            }
        }

        public void OpenHomePanelFromShortcut()
        {
            TryBuildUi();
            SetPanelOpen(true);
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

            EnsureApiService();

            GameObject toggleObject = CreateUiObject("LudoFriendToggle", rootCanvas.transform);
            RectTransform toggleRect = toggleObject.AddComponent<RectTransform>();
            toggleRect.anchorMin = new Vector2(0.5f, 0f);
            toggleRect.anchorMax = new Vector2(0.5f, 0f);
            toggleRect.pivot = new Vector2(0.5f, 0f);
            toggleRect.sizeDelta = new Vector2(132f, 132f);
            toggleRect.anchoredPosition = new Vector2(0f, 88f);
            Image toggleImage = toggleObject.AddComponent<Image>();
            toggleImage.color = new Color32(0, 0, 0, 0);
            toggleButton = toggleObject.AddComponent<Button>();
            toggleButton.onClick.AddListener(() => SetPanelOpen(rootPanel == null || !rootPanel.gameObject.activeSelf));
            BuildToggleIcon(toggleRect);

            requestBadgeObject = CreateUiObject("RequestBadge", toggleRect);
            RectTransform requestBadgeRect = requestBadgeObject.AddComponent<RectTransform>();
            requestBadgeRect.anchorMin = new Vector2(1f, 1f);
            requestBadgeRect.anchorMax = new Vector2(1f, 1f);
            requestBadgeRect.pivot = new Vector2(1f, 1f);
            requestBadgeRect.sizeDelta = new Vector2(40f, 40f);
            requestBadgeRect.anchoredPosition = new Vector2(-8f, -4f);
            Image requestBadgeImage = requestBadgeObject.AddComponent<Image>();
            requestBadgeImage.color = new Color32(214, 71, 82, 255);
            requestBadgeText = CreateTmpLabel(requestBadgeRect, "0", 18, FontStyles.Bold);
            requestBadgeText.alignment = TextAlignmentOptions.Center;
            StretchRect(requestBadgeText.rectTransform, Vector2.zero, Vector2.zero);
            requestBadgeObject.SetActive(false);

            GameObject panelObject = CreateUiObject("LudoFriendPanel", rootCanvas.transform);
            rootPanel = panelObject.AddComponent<RectTransform>();
            rootPanel.anchorMin = new Vector2(0.5f, 0.5f);
            rootPanel.anchorMax = new Vector2(0.5f, 0.5f);
            rootPanel.pivot = new Vector2(0.5f, 0.5f);
            rootPanel.sizeDelta = new Vector2(1200f, 820f);
            rootPanel.anchoredPosition = new Vector2(0f, 10f);
            Image panelImage = panelObject.AddComponent<Image>();
            panelImage.color = new Color32(33, 23, 25, 245);
            Sprite panelBackgroundSprite = ResolveBoxSprite();
            if (panelBackgroundSprite == null)
            {
                panelBackgroundSprite = ResolvePopupBackgroundSprite("home-notification-banner-bg");
            }
            if (panelBackgroundSprite != null)
            {
                panelImage.sprite = panelBackgroundSprite;
                panelImage.type = Image.Type.Simple;
                panelImage.preserveAspect = false;
            }

            VerticalLayoutGroup panelLayout = panelObject.AddComponent<VerticalLayoutGroup>();
            panelLayout.padding = new RectOffset(44, 44, 26, 24);
            panelLayout.spacing = 10;
            panelLayout.childControlHeight = false;
            panelLayout.childControlWidth = true;
            panelLayout.childForceExpandHeight = false;
            panelLayout.childForceExpandWidth = true;

            RectTransform headerRow = CreateUiObject("HeaderRow", rootPanel).AddComponent<RectTransform>();
            headerRow.sizeDelta = new Vector2(0f, 88f);
            // No layout group — children use absolute anchoring within this fixed-height row

            // Centered title badge — matches Referral History popup header style
            GameObject titleBadge = CreateUiObject("TitleBadge", headerRow);
            RectTransform titleBadgeRect = titleBadge.AddComponent<RectTransform>();
            titleBadgeRect.anchorMin = new Vector2(0.5f, 0.5f);
            titleBadgeRect.anchorMax = new Vector2(0.5f, 0.5f);
            titleBadgeRect.pivot = new Vector2(0.5f, 0.5f);
            titleBadgeRect.sizeDelta = new Vector2(380f, 64f);
            titleBadgeRect.anchoredPosition = Vector2.zero;
            Image titleBadgeImage = titleBadge.AddComponent<Image>();
            titleBadgeImage.color = new Color32(130, 80, 10, 230);
            Outline titleBadgeOutline = titleBadge.AddComponent<Outline>();
            titleBadgeOutline.effectColor = new Color32(255, 200, 80, 200);
            titleBadgeOutline.effectDistance = new Vector2(2f, -2f);
            TextMeshProUGUI title = CreateTmpLabel(titleBadgeRect, "Friends", 34, FontStyles.Bold);
            title.alignment = TextAlignmentOptions.Center;
            title.color = new Color32(255, 240, 180, 255);
            StretchRect(title.rectTransform, new Vector2(8f, 4f), new Vector2(-8f, -4f));

            // X close button — anchored top-right corner (red circle like Referral History popup)
            Button closePanelButton = CreateActionButton(headerRow, "X", new Color32(180, 30, 40, 235));
            RectTransform closeRect = closePanelButton.GetComponent<RectTransform>();
            closeRect.anchorMin = new Vector2(1f, 1f);
            closeRect.anchorMax = new Vector2(1f, 1f);
            closeRect.pivot = new Vector2(1f, 1f);
            closeRect.sizeDelta = new Vector2(64f, 64f);
            closeRect.anchoredPosition = new Vector2(-6f, -6f);
            closePanelButton.onClick.AddListener(() => SetPanelOpen(false));

            TextMeshProUGUI subtitle = CreateTmpLabel(rootPanel, "Search by user ID, username, email, or mobile and manage incoming requests.", 24, FontStyles.Normal);
            subtitle.color = new Color32(255, 232, 196, 255);

            RectTransform inputRow = CreateUiObject("InputRow", rootPanel).AddComponent<RectTransform>();
            inputRow.sizeDelta = new Vector2(0f, 88f);
            HorizontalLayoutGroup inputLayout = inputRow.gameObject.AddComponent<HorizontalLayoutGroup>();
            inputLayout.spacing = 10f;
            inputLayout.childControlHeight = true;
            inputLayout.childControlWidth = true;
            inputLayout.childForceExpandHeight = true;
            inputLayout.childForceExpandWidth = false;

            GameObject inputObject = CreateUiObject("PlayerIdInput", inputRow);
            RectTransform inputRect = inputObject.AddComponent<RectTransform>();
            LayoutElement inputRowLayout = inputObject.AddComponent<LayoutElement>();
            inputRowLayout.flexibleWidth = 1f;
            Image inputImage = inputObject.AddComponent<Image>();
            inputImage.color = new Color32(255, 248, 236, 242);
            playerIdInput = inputObject.AddComponent<TMP_InputField>();
            playerIdInput.characterLimit = 20;
            playerIdInput.lineType = TMP_InputField.LineType.SingleLine;

            RectTransform textArea = CreateUiObject("TextArea", inputRect).AddComponent<RectTransform>();
            StretchRect(textArea, new Vector2(12f, 8f), new Vector2(-12f, -8f));

            TextMeshProUGUI placeholder = CreateTmpLabel(textArea, "Enter ID, username, email, or mobile", 24, FontStyles.Italic);
            placeholder.color = new Color32(120, 94, 83, 255);
            StretchRect(placeholder.rectTransform, Vector2.zero, Vector2.zero);

            TextMeshProUGUI inputText = CreateTmpLabel(textArea, string.Empty, 24, FontStyles.Normal);
            inputText.color = new Color32(53, 34, 33, 255);
            StretchRect(inputText.rectTransform, Vector2.zero, Vector2.zero);

            playerIdInput.textViewport = textArea;
            playerIdInput.placeholder = placeholder;
            playerIdInput.textComponent = inputText;

            Button searchButton = CreateActionButton(inputRow, "SEARCH", new Color32(139, 40, 46, 235));
            LayoutElement searchLayout = searchButton.gameObject.AddComponent<LayoutElement>();
            searchLayout.preferredWidth = 180f;
            searchLayout.preferredHeight = 60f;
            searchButton.onClick.AddListener(HandleSearchClicked);

            RectTransform resultCard = CreateUiObject("ResultCard", rootPanel).AddComponent<RectTransform>();
            resultCard.sizeDelta = new Vector2(0f, 108f);
            Image resultCardImage = resultCard.gameObject.AddComponent<Image>();
            resultCardImage.color = new Color32(112, 34, 42, 170);
            VerticalLayoutGroup resultLayout = resultCard.gameObject.AddComponent<VerticalLayoutGroup>();
            resultLayout.padding = new RectOffset(20, 20, 18, 18);
            resultLayout.spacing = 8f;
            resultLayout.childControlHeight = false;
            resultLayout.childControlWidth = true;
            resultLayout.childForceExpandHeight = false;
            resultLayout.childForceExpandWidth = true;

            resultTitleText = CreateTmpLabel(resultCard, "Player search ready", 26, FontStyles.Bold);
            resultTitleText.color = new Color32(255, 241, 214, 255);
            resultSubtitleText = CreateTmpLabel(resultCard, "Find a player and send a friend request.", 22, FontStyles.Normal);
            resultSubtitleText.color = new Color32(255, 223, 196, 255);

            addByIdButton = CreateActionButton(rootPanel, "SEND REQUEST", new Color32(54, 126, 72, 235));
            LayoutElement addByIdLayout = addByIdButton.gameObject.AddComponent<LayoutElement>();
            addByIdLayout.preferredHeight = 62f;
            addByIdButton.onClick.AddListener(HandleAddByIdClicked);
            addByIdButton.interactable = false;

            RectTransform tabRow = CreateUiObject("TabRow", rootPanel).AddComponent<RectTransform>();
            tabRow.sizeDelta = new Vector2(0f, 56f);
            HorizontalLayoutGroup tabLayout = tabRow.gameObject.AddComponent<HorizontalLayoutGroup>();
            tabLayout.spacing = 12f;
            tabLayout.childControlHeight = true;
            tabLayout.childControlWidth = true;
            tabLayout.childForceExpandHeight = true;
            tabLayout.childForceExpandWidth = false;
            tabLayout.childAlignment = TextAnchor.MiddleCenter;

            requestsTabButton = CreateActionButton(tabRow, "REQUESTS", new Color32(139, 40, 46, 235));
            LayoutElement requestsTabLayout = requestsTabButton.gameObject.AddComponent<LayoutElement>();
            requestsTabLayout.minWidth = 250f;
            requestsTabLayout.preferredWidth = 250f;
            requestsTabLayout.preferredHeight = 54f;
            requestsTabButton.onClick.AddListener(() => SetListMode(false));

            friendsTabButton = CreateActionButton(tabRow, "FRIENDS", new Color32(95, 54, 31, 220));
            LayoutElement friendsTabLayout = friendsTabButton.gameObject.AddComponent<LayoutElement>();
            friendsTabLayout.minWidth = 250f;
            friendsTabLayout.preferredWidth = 250f;
            friendsTabLayout.preferredHeight = 54f;
            friendsTabButton.onClick.AddListener(() => SetListMode(true));

            RectTransform requestsHeader = CreateUiObject("RequestsHeader", rootPanel).AddComponent<RectTransform>();
            requestsHeader.sizeDelta = new Vector2(0f, 52f);
            HorizontalLayoutGroup requestsHeaderLayout = requestsHeader.gameObject.AddComponent<HorizontalLayoutGroup>();
            requestsHeaderLayout.spacing = 10f;
            requestsHeaderLayout.childControlHeight = true;
            requestsHeaderLayout.childControlWidth = true;
            requestsHeaderLayout.childForceExpandHeight = true;
            requestsHeaderLayout.childForceExpandWidth = false;

            listSectionTitleText = CreateTmpLabel(requestsHeader, "Incoming Requests", 26, FontStyles.Bold);
            listSectionTitleText.color = new Color32(255, 240, 204, 255);
            LayoutElement requestsTitleLayout = listSectionTitleText.gameObject.AddComponent<LayoutElement>();
            requestsTitleLayout.flexibleWidth = 1f;

            Button refreshButton = CreateActionButton(requestsHeader, "REFRESH", new Color32(139, 40, 46, 235));
            LayoutElement refreshLayout = refreshButton.gameObject.AddComponent<LayoutElement>();
            refreshLayout.minWidth = 190f;
            refreshLayout.preferredWidth = 190f;
            refreshLayout.preferredHeight = 56f;
            refreshButton.onClick.AddListener(RefreshActiveList);

            RectTransform requestsScrollRoot = CreateUiObject("RequestsScrollRoot", rootPanel).AddComponent<RectTransform>();
            LayoutElement requestsScrollLayout = requestsScrollRoot.gameObject.AddComponent<LayoutElement>();
            requestsScrollLayout.preferredHeight = 380f;
            Image requestsScrollImage = requestsScrollRoot.gameObject.AddComponent<Image>();
            requestsScrollImage.color = new Color32(59, 18, 25, 150);
            Mask requestsMask = requestsScrollRoot.gameObject.AddComponent<Mask>();
            requestsMask.showMaskGraphic = false;
            ScrollRect requestsScrollRect = requestsScrollRoot.gameObject.AddComponent<ScrollRect>();
            requestsScrollRect.horizontal = false;
            requestsScrollRect.movementType = ScrollRect.MovementType.Elastic;
            requestsScrollRect.inertia = true;
            requestsScrollRect.decelerationRate = 0.08f;
            requestsScrollRect.scrollSensitivity = 120f;

            requestsContent = CreateUiObject("RequestsContent", requestsScrollRoot).AddComponent<RectTransform>();
            VerticalLayoutGroup requestsContentLayout = requestsContent.gameObject.AddComponent<VerticalLayoutGroup>();
            requestsContentLayout.spacing = 8f;
            requestsContentLayout.padding = new RectOffset(8, 8, 8, 8);
            requestsContentLayout.childControlHeight = true;
            requestsContentLayout.childControlWidth = true;
            requestsContentLayout.childForceExpandHeight = false;
            requestsContentLayout.childForceExpandWidth = true;
            ContentSizeFitter requestsContentFitter = requestsContent.gameObject.AddComponent<ContentSizeFitter>();
            requestsContentFitter.verticalFit = ContentSizeFitter.FitMode.PreferredSize;
            requestsContentFitter.horizontalFit = ContentSizeFitter.FitMode.Unconstrained;
            requestsContent.anchorMin = new Vector2(0f, 1f);
            requestsContent.anchorMax = new Vector2(1f, 1f);
            requestsContent.pivot = new Vector2(0.5f, 1f);
            requestsContent.anchoredPosition = Vector2.zero;
            requestsContent.sizeDelta = new Vector2(0f, 0f);
            requestsScrollRect.viewport = requestsScrollRoot;
            requestsScrollRect.content = requestsContent;

            requestsStateText = CreateTmpLabel(requestsContent, "Loading requests...", 22, FontStyles.Italic);
            requestsStateText.color = new Color32(255, 226, 194, 255);

            BuildRoomPopup(rootCanvas.transform);

            uiReady = true;
            if (toggleButton != null)
            {
                toggleButton.gameObject.SetActive(false);
            }
            SetPanelOpen(false);
            UpdateTogglePlacement();
            nextRequestsRefreshAt = 0f;
            SetListMode(false);
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
            canvas.sortingOrder = 32715;

            CanvasScaler scaler = canvasObject.GetComponent<CanvasScaler>();
            scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
            scaler.referenceResolution = new Vector2(1080f, 1920f);
            scaler.matchWidthOrHeight = 0.5f;

            return canvas;
        }

        private void BuildRoomPopup(Transform parent)
        {
            GameObject popupObject = CreateUiObject("LudoFriendRoomPopup", parent);
            roomPopup = popupObject.AddComponent<RectTransform>();
            roomPopup.anchorMin = new Vector2(0.5f, 0.5f);
            roomPopup.anchorMax = new Vector2(0.5f, 0.5f);
            roomPopup.pivot = new Vector2(0.5f, 0.5f);
            roomPopup.sizeDelta = new Vector2(360f, 220f);
            Image popupImage = popupObject.AddComponent<Image>();
            popupImage.color = new Color32(16, 23, 30, 238);
            popupObject.SetActive(false);

            VerticalLayoutGroup popupLayout = popupObject.AddComponent<VerticalLayoutGroup>();
            popupLayout.padding = new RectOffset(18, 18, 18, 18);
            popupLayout.spacing = 12f;
            popupLayout.childControlHeight = false;
            popupLayout.childControlWidth = true;
            popupLayout.childForceExpandHeight = false;
            popupLayout.childForceExpandWidth = true;

            roomPopupTitleText = CreateTmpLabel(roomPopup, "Player", 26, FontStyles.Bold);
            TextMeshProUGUI popupSubtitle = CreateTmpLabel(roomPopup, "Send friend request from this room.", 20, FontStyles.Normal);
            popupSubtitle.color = new Color32(219, 224, 229, 255);

            roomAddFriendButton = CreateActionButton(roomPopup, "ADD FRIEND", new Color32(64, 144, 90, 255));
            roomAddFriendButton.onClick.AddListener(HandleRoomAddFriendClicked);

            Button closeButton = CreateActionButton(roomPopup, "CLOSE", new Color32(97, 103, 112, 255));
            closeButton.onClick.AddListener(HideRoomPopup);
        }

        private void BuildToggleIcon(RectTransform toggleRect)
        {
            RectTransform iconPlate = CreateUiObject("IconPlate", toggleRect).AddComponent<RectTransform>();
            iconPlate.anchorMin = new Vector2(0.5f, 1f);
            iconPlate.anchorMax = new Vector2(0.5f, 1f);
            iconPlate.pivot = new Vector2(0.5f, 1f);
            iconPlate.sizeDelta = new Vector2(88f, 88f);
            iconPlate.anchoredPosition = new Vector2(0f, -6f);
            Image iconPlateImage = iconPlate.gameObject.AddComponent<Image>();
            iconPlateImage.color = new Color32(27, 49, 78, 235);

            Sprite friendSprite = ResolveHomepageIconSprite("friends-icon");
            if (friendSprite != null)
            {
                iconPlateImage.sprite = friendSprite;
                iconPlateImage.type = Image.Type.Simple;
                iconPlateImage.preserveAspect = true;
            }
            else
            {
                iconPlateImage.color = new Color32(255, 255, 255, 0);
            }

            toggleCaptionText = CreateTmpLabel(toggleRect, "Friends", 18, FontStyles.Bold);
            toggleCaptionText.alignment = TextAlignmentOptions.Center;
            RectTransform captionRect = toggleCaptionText.rectTransform;
            captionRect.anchorMin = new Vector2(0f, 0f);
            captionRect.anchorMax = new Vector2(1f, 0f);
            captionRect.pivot = new Vector2(0.5f, 0f);
            captionRect.sizeDelta = new Vector2(0f, 28f);
            captionRect.anchoredPosition = new Vector2(0f, 6f);
            toggleCaptionText.color = new Color32(243, 247, 251, 255);
        }

        private void HandleSearchClicked()
        {
            string playerId = playerIdInput != null ? playerIdInput.text?.Trim() : string.Empty;
            if (string.IsNullOrWhiteSpace(playerId))
            {
                CommonUtil.ShowStyledMessage("Please enter ID, username, email, or mobile", "Friend Request", true);
                return;
            }

            searchedPlayerId = playerId;
            searchedUser = null;
            resultTitleText.text = "Searching...";
            resultSubtitleText.text = "Checking player profile.";
            addByIdButton.interactable = false;

            LudoFriendApiService.Instance?.SearchUserByPlayerId(
                playerId,
                result =>
                {
                    RunOnMainThread(() =>
                    {
                        if (result == null || !result.success || result.data == null)
                        {
                            resultTitleText.text = "Player not found";
                            resultSubtitleText.text = result?.message ?? "No player found with this ID.";
                            CommonUtil.ShowStyledMessage(result?.message ?? "Player not found", "Friend Request", true);
                            return;
                        }

                        searchedUser = result.data;
                        resultTitleText.text = ResolveDisplayName(result.data);
                        resultSubtitleText.text = "Player ID: " + (result.data.user_code ?? searchedPlayerId);
                        addByIdButton.interactable = true;
                    });
                }
            );
        }

        private void HandleAddByIdClicked()
        {
            string playerId = searchedUser != null && !string.IsNullOrWhiteSpace(searchedUser.user_code)
                ? searchedUser.user_code
                : searchedPlayerId;

            if (string.IsNullOrWhiteSpace(playerId))
            {
                CommonUtil.ShowStyledMessage("Search a player first", "Friend Request", true);
                return;
            }

            addByIdButton.interactable = false;
            LudoFriendApiService.Instance?.SendFriendRequestByPlayerId(
                playerId,
                "lobby_search",
                ResolveSourceRoomUuid(),
                result =>
                {
                    RunOnMainThread(() =>
                    {
                        addByIdButton.interactable = true;
                        CommonUtil.ShowStyledMessage(
                            result?.message ?? "Unable to send friend request",
                            result != null && result.success ? "Friend Request Sent" : "Friend Request",
                            !(result != null && result.success)
                        );
                    });
                }
            );
        }

        private void HandleRoomAddFriendClicked()
        {
            if (string.IsNullOrWhiteSpace(roomPopupUserId))
            {
                CommonUtil.ShowStyledMessage("Player details unavailable", "Friend Request", true);
                return;
            }

            roomAddFriendButton.interactable = false;
            LudoFriendApiService.Instance?.SendFriendRequestToUser(
                roomPopupUserId,
                "room",
                ResolveSourceRoomUuid(),
                result =>
                {
                    RunOnMainThread(() =>
                    {
                        roomAddFriendButton.interactable = true;
                        CommonUtil.ShowStyledMessage(
                            result?.message ?? "Unable to send friend request",
                            result != null && result.success ? "Friend Request Sent" : "Friend Request",
                            !(result != null && result.success)
                        );
                        if (result != null && result.success)
                        {
                            HideRoomPopup();
                        }
                    });
                }
            );
        }

        private void RefreshRoomActionButtons()
        {
            LudoNumbersAcknowledgementHandlerOffline acknowledgementHandler = ResolveAcknowledgementHandler();
            if (acknowledgementHandler == null || acknowledgementHandler.ludoNumberPlayerControl == null)
            {
                return;
            }

            string localUserId = Configuration.GetId();
            var activeButtons = new HashSet<GameObject>();

            for (int i = 0; i < acknowledgementHandler.ludoNumberPlayerControl.Length; i++)
            {
                LudoNumberPlayerControlOffline playerControl = acknowledgementHandler.ludoNumberPlayerControl[i];
                if (playerControl == null || playerControl.ludoNumbersUserData == null || playerControl.ludoNumbersUserData.infoBtn == null)
                {
                    continue;
                }

                GameObject infoButtonObject = playerControl.ludoNumbersUserData.infoBtn;
                Image buttonImage = infoButtonObject.GetComponent<Image>();
                if (buttonImage == null)
                {
                    buttonImage = infoButtonObject.AddComponent<Image>();
                    buttonImage.color = new Color(1f, 1f, 1f, 0.01f);
                }

                Button button = infoButtonObject.GetComponent<Button>() ?? infoButtonObject.AddComponent<Button>();
                PlayerInfoData playerInfo = playerControl.playerInfoData;
                bool shouldShow = roomActionsAvailable
                    && playerControl.gameObject.activeInHierarchy
                    && playerInfo != null
                    && playerInfo.playerSeatIndex >= 0
                    && !string.IsNullOrWhiteSpace(playerInfo.userId)
                    && !string.Equals(playerInfo.userId, localUserId, StringComparison.OrdinalIgnoreCase);

                if (!shouldShow)
                {
                    infoButtonObject.SetActive(false);
                    infoButtonObject.transform.localScale = Vector3.zero;
                    continue;
                }

                activeButtons.Add(infoButtonObject);
                infoButtonObject.SetActive(true);
                infoButtonObject.transform.localScale = Vector3.one;

                string boundUserId;
                if (!boundSlotUsers.TryGetValue(infoButtonObject, out boundUserId)
                    || !string.Equals(boundUserId, playerInfo.userId, StringComparison.Ordinal))
                {
                    button.onClick.RemoveAllListeners();
                    string capturedUserId = playerInfo.userId;
                    string capturedDisplayName = string.IsNullOrWhiteSpace(playerInfo.username)
                        ? "Player " + (playerInfo.playerSeatIndex + 1)
                        : playerInfo.username;
                    button.onClick.AddListener(() => ShowRoomPopup(capturedUserId, capturedDisplayName));
                    boundSlotUsers[infoButtonObject] = capturedUserId;
                }
            }

            var staleButtons = new List<GameObject>();
            foreach (var item in boundSlotUsers)
            {
                if (!activeButtons.Contains(item.Key))
                {
                    staleButtons.Add(item.Key);
                }
            }

            for (int i = 0; i < staleButtons.Count; i++)
            {
                boundSlotUsers.Remove(staleButtons[i]);
            }
        }

        private void SetPanelOpen(bool shouldOpen)
        {
            if (rootPanel != null)
            {
                rootPanel.gameObject.SetActive(shouldOpen);
            }

            if (shouldOpen)
            {
                RefreshActiveList();
            }
        }

        private void UpdateTogglePlacement()
        {
            if (toggleButton == null)
            {
                return;
            }

            RectTransform toggleRect = toggleButton.GetComponent<RectTransform>();
            if (toggleRect == null)
            {
                return;
            }

            FriendUiMode mode = ResolveUiMode();
            bool shouldShow = mode != FriendUiMode.Hidden;
            Scene activeScene = SceneManager.GetActiveScene();
            bool isHomeScene = activeScene.IsValid() && string.Equals(activeScene.name, "HomePage", StringComparison.OrdinalIgnoreCase);
            if (toggleButton.gameObject.activeSelf != shouldShow)
            {
                toggleButton.gameObject.SetActive(shouldShow);
            }

            if (!shouldShow)
            {
                if (!isHomeScene && rootPanel != null && rootPanel.gameObject.activeSelf)
                {
                    rootPanel.gameObject.SetActive(false);
                }
                return;
            }

            Transform addCashAnchor = FindSceneTransformByName("add-chip-button") ?? FindSceneAnchor("Add Cash");
            Transform referralAnchor = FindSceneTransformByName("history-icon") ?? FindSceneAnchor("Referral History");
            if (addCashAnchor == null || referralAnchor == null)
            {
                footerAnchorResolved = false;
                toggleButton.gameObject.SetActive(false);
                if (rootPanel != null && rootPanel.gameObject.activeSelf)
                {
                    rootPanel.gameObject.SetActive(false);
                }
                toggleRect.anchorMin = new Vector2(0.5f, 0f);
                toggleRect.anchorMax = new Vector2(0.5f, 0f);
                toggleRect.pivot = new Vector2(0.5f, 0f);
                toggleRect.anchoredPosition = new Vector2(210f, 72f);
                return;
            }

            if (!toggleButton.gameObject.activeSelf)
            {
                toggleButton.gameObject.SetActive(true);
            }

            footerAnchorResolved = true;
            Vector2 addCashScreen = RectTransformUtility.WorldToScreenPoint(null, addCashAnchor.position);
            Vector2 referralScreen = RectTransformUtility.WorldToScreenPoint(null, referralAnchor.position);
            Vector2 midpoint = Vector2.Lerp(addCashScreen, referralScreen, 0.52f);

            RectTransform canvasRect = rootCanvas != null ? rootCanvas.GetComponent<RectTransform>() : null;
            Vector2 localPoint;
            if (canvasRect == null || !RectTransformUtility.ScreenPointToLocalPointInRectangle(canvasRect, midpoint, null, out localPoint))
            {
                return;
            }

            toggleRect.anchorMin = new Vector2(0.5f, 0.5f);
            toggleRect.anchorMax = new Vector2(0.5f, 0.5f);
            toggleRect.pivot = new Vector2(0.5f, 0.5f);
            toggleRect.anchoredPosition = new Vector2(localPoint.x + 4f, localPoint.y + 10f);

            // Keep panel centered (same as Referral History popup — fixed center of screen)
            if (rootPanel != null)
            {
                rootPanel.anchorMin = new Vector2(0.5f, 0.5f);
                rootPanel.anchorMax = new Vector2(0.5f, 0.5f);
                rootPanel.pivot = new Vector2(0.5f, 0.5f);
                rootPanel.anchoredPosition = new Vector2(0f, 10f);
            }
        }

        private FriendUiMode ResolveUiMode()
        {
            if (!homeShortcutAvailable)
            {
                return FriendUiMode.Hidden;
            }

            Scene activeScene = SceneManager.GetActiveScene();
            if (!activeScene.IsValid() || !string.Equals(activeScene.name, "HomePage", StringComparison.OrdinalIgnoreCase))
            {
                return FriendUiMode.Hidden;
            }

            return FriendUiMode.Home;
        }

        private void ShowRoomPopup(string userId, string displayName)
        {
            if (roomPopup == null || string.IsNullOrWhiteSpace(userId))
            {
                return;
            }

            roomPopupUserId = userId;
            roomPopupTitleText.text = displayName;
            roomAddFriendButton.interactable = true;
            roomPopup.gameObject.SetActive(true);
        }

        private void HideRoomPopup()
        {
            if (roomPopup != null)
            {
                roomPopup.gameObject.SetActive(false);
            }

            roomPopupUserId = null;
        }

        private LudoNumbersAcknowledgementHandlerOffline ResolveAcknowledgementHandler()
        {
            LudoV2MatchmakingBridge bridge = LudoV2MatchmakingBridge.Instance;
            if (bridge != null && bridge.socketNumberEventReceiver != null)
            {
                return bridge.socketNumberEventReceiver.ludoNumbersAcknowledgementHandler;
            }

            SocketNumberEventReceiverOffline receiver = FindObjectOfType<SocketNumberEventReceiverOffline>();
            return receiver != null ? receiver.ludoNumbersAcknowledgementHandler : null;
        }

        private void EnsureApiService()
        {
            if (LudoFriendApiService.Instance != null)
            {
                return;
            }

            if (GetComponent<LudoFriendApiService>() == null)
            {
                gameObject.AddComponent<LudoFriendApiService>();
            }
        }

        private string ResolveSourceRoomUuid()
        {
            return LudoV2MatchmakingBridge.Instance != null
                ? LudoV2MatchmakingBridge.Instance.GetActiveRoomId()
                : null;
        }

        private void RefreshRequests()
        {
            if (!uiReady)
            {
                return;
            }

            EnsureRequestsStateLabel();
            requestsStateText.gameObject.SetActive(true);
            requestsStateText.text = "Loading requests...";

            LudoFriendApiService.Instance?.ListFriendRequests(
                result =>
                {
                    RunOnMainThread(() => RenderRequests(result));
                }
            );
        }

        private void RefreshFriends()
        {
            if (!uiReady)
            {
                return;
            }

            EnsureRequestsStateLabel();
            requestsStateText.gameObject.SetActive(true);
            requestsStateText.text = "Loading friends...";

            LudoFriendApiService.Instance?.ListFriends(
                result =>
                {
                    RunOnMainThread(() => RenderFriends(result));
                }
            );
        }

        private void RefreshActiveList()
        {
            if (showingFriendsList)
            {
                RefreshFriends();
                return;
            }

            RefreshRequests();
        }

        private void SetListMode(bool showFriends)
        {
            showingFriendsList = showFriends;

            if (listSectionTitleText != null)
            {
                listSectionTitleText.text = showFriends ? "My Friends" : "Incoming Requests";
            }

            SetTabVisualState(requestsTabButton, !showFriends);
            SetTabVisualState(friendsTabButton, showFriends);
            RefreshActiveList();
        }

        private void RenderRequests(LudoFriendApiResult<List<LudoFriendRequestData>> result)
        {
            if (requestsContent == null)
            {
                return;
            }

            for (int i = requestsContent.childCount - 1; i >= 0; i--)
            {
                Destroy(requestsContent.GetChild(i).gameObject);
            }

            requestsStateText = null;

            int pendingIncomingCount = 0;
            List<LudoFriendRequestData> items = result != null && result.success && result.data != null
                ? result.data
                : new List<LudoFriendRequestData>();

            string localUserId = Configuration.GetId();

            for (int i = 0; i < items.Count; i++)
            {
                LudoFriendRequestData item = items[i];
                if (item == null)
                {
                    continue;
                }

                bool isIncoming = IsLocalUser(item.receiver, localUserId);

                bool isPending = string.Equals(item.status, "pending", StringComparison.OrdinalIgnoreCase);
                if (isIncoming && isPending)
                {
                    pendingIncomingCount++;
                }

                CreateRequestRow(item, isIncoming, isPending);
            }

            if (items.Count == 0)
            {
                EnsureRequestsStateLabel();
                requestsStateText.text = "No friend requests yet.";
                requestsStateText.color = new Color32(198, 205, 213, 255);
            }

            if (requestBadgeObject != null && requestBadgeText != null)
            {
                requestBadgeObject.SetActive(pendingIncomingCount > 0);
                requestBadgeText.text = pendingIncomingCount > 99 ? "99+" : pendingIncomingCount.ToString();
            }

            UpdateSceneShortcutBadge(pendingIncomingCount);
        }

        private void RenderFriends(LudoFriendApiResult<List<LudoFriendListItemData>> result)
        {
            if (requestsContent == null)
            {
                return;
            }

            for (int i = requestsContent.childCount - 1; i >= 0; i--)
            {
                Destroy(requestsContent.GetChild(i).gameObject);
            }

            requestsStateText = null;

            List<LudoFriendListItemData> items = result != null && result.success && result.data != null
                ? result.data
                : new List<LudoFriendListItemData>();

            if (items.Count == 0)
            {
                EnsureRequestsStateLabel();
                requestsStateText.text = "No friends added yet.";
                requestsStateText.color = new Color32(198, 205, 213, 255);
                return;
            }

            for (int i = 0; i < items.Count; i++)
            {
                LudoFriendListItemData item = items[i];
                if (item == null || item.friend == null)
                {
                    continue;
                }

                CreateFriendRow(item);
            }
        }

        private void CreateRequestRow(LudoFriendRequestData request, bool isIncoming, bool isPending)
        {
            RectTransform card = CreateUiObject("RequestCard", requestsContent).AddComponent<RectTransform>();
            Image cardImage = card.gameObject.AddComponent<Image>();
            cardImage.color = new Color32(119, 35, 44, 175);
            VerticalLayoutGroup cardLayout = card.gameObject.AddComponent<VerticalLayoutGroup>();
            cardLayout.padding = new RectOffset(16, 16, 12, 12);
            cardLayout.spacing = 6f;
            cardLayout.childControlHeight = true;
            cardLayout.childControlWidth = true;
            cardLayout.childForceExpandHeight = false;
            cardLayout.childForceExpandWidth = true;
            LayoutElement cardElement = card.gameObject.AddComponent<LayoutElement>();
            cardElement.minHeight = isIncoming && isPending ? 132f : 82f;

            string actorName = isIncoming
                ? ResolveDisplayName(request.sender)
                : ResolveDisplayName(request.receiver);

            TextMeshProUGUI title = CreateTmpLabel(card, actorName, 18, FontStyles.Bold);
            title.color = new Color32(255, 241, 214, 255);
            TextMeshProUGUI subtitle = CreateTmpLabel(
                card,
                isIncoming
                    ? "sent you a friend request"
                    : "request status: " + (request.status ?? "pending"),
                14,
                FontStyles.Normal
            );
            subtitle.color = new Color32(255, 223, 196, 255);

            string identifier = isIncoming
                ? (request.sender?.user_code ?? request.sender?.username)
                : (request.receiver?.user_code ?? request.receiver?.username);
            TextMeshProUGUI meta = CreateTmpLabel(card, "ID: " + (identifier ?? "-"), 13, FontStyles.Italic);
            meta.color = new Color32(240, 199, 177, 255);

            if (isIncoming && isPending)
            {
                RectTransform actions = CreateUiObject("Actions", card).AddComponent<RectTransform>();
                LayoutElement actionsElement = actions.gameObject.AddComponent<LayoutElement>();
                actionsElement.preferredHeight = 52f;
                actionsElement.minHeight = 52f;
                HorizontalLayoutGroup actionsLayout = actions.gameObject.AddComponent<HorizontalLayoutGroup>();
                actionsLayout.spacing = 16f;
                actionsLayout.childControlHeight = true;
                actionsLayout.childControlWidth = false;
                actionsLayout.childForceExpandHeight = false;
                actionsLayout.childForceExpandWidth = false;
                actionsLayout.childAlignment = TextAnchor.MiddleCenter;

                Button acceptButton = CreateActionButton(actions, "ACCEPT", new Color32(54, 126, 72, 235));
                Button rejectButton = CreateActionButton(actions, "REJECT", new Color32(145, 45, 53, 235));
                LayoutElement acceptLayout = acceptButton.gameObject.AddComponent<LayoutElement>();
                acceptLayout.minWidth = 180f;
                acceptLayout.preferredWidth = 180f;
                acceptLayout.minHeight = 52f;
                acceptLayout.preferredHeight = 52f;
                LayoutElement rejectLayout = rejectButton.gameObject.AddComponent<LayoutElement>();
                rejectLayout.minWidth = 180f;
                rejectLayout.preferredWidth = 180f;
                rejectLayout.minHeight = 52f;
                rejectLayout.preferredHeight = 52f;

                acceptButton.onClick.AddListener(() => RespondToRequest(request.request_uuid, "accept"));
                rejectButton.onClick.AddListener(() => RespondToRequest(request.request_uuid, "reject"));
            }
        }

        private void CreateFriendRow(LudoFriendListItemData friendItem)
        {
            RectTransform card = CreateUiObject("FriendCard", requestsContent).AddComponent<RectTransform>();
            Image cardImage = card.gameObject.AddComponent<Image>();
            cardImage.color = new Color32(119, 35, 44, 175);
            VerticalLayoutGroup cardLayout = card.gameObject.AddComponent<VerticalLayoutGroup>();
            cardLayout.padding = new RectOffset(16, 16, 14, 14);
            cardLayout.spacing = 8f;
            cardLayout.childControlHeight = true;
            cardLayout.childControlWidth = true;
            cardLayout.childForceExpandHeight = false;
            cardLayout.childForceExpandWidth = true;
            LayoutElement cardElement = card.gameObject.AddComponent<LayoutElement>();
            cardElement.minHeight = 122f;

            RectTransform topRow = CreateUiObject("FriendTopRow", card).AddComponent<RectTransform>();
            topRow.sizeDelta = new Vector2(0f, 40f);
            HorizontalLayoutGroup topRowLayout = topRow.gameObject.AddComponent<HorizontalLayoutGroup>();
            topRowLayout.spacing = 12f;
            topRowLayout.childControlHeight = true;
            topRowLayout.childControlWidth = true;
            topRowLayout.childForceExpandHeight = false;
            topRowLayout.childForceExpandWidth = false;

            string identifier = !string.IsNullOrWhiteSpace(friendItem.friend.user_code)
                ? friendItem.friend.user_code
                : friendItem.friend.id.ToString();

            TextMeshProUGUI title = CreateTmpLabel(topRow, identifier, 24, FontStyles.Bold);
            title.color = new Color32(255, 241, 214, 255);
            LayoutElement titleLayout = title.gameObject.AddComponent<LayoutElement>();
            titleLayout.flexibleWidth = 1f;

            bool isLive = IsUserRecentlyOnline(friendItem.friend.last_login_at);
            GameObject liveBadgeObject = CreateUiObject("LiveBadge", topRow);
            RectTransform liveBadgeRect = liveBadgeObject.AddComponent<RectTransform>();
            LayoutElement badgeLayout = liveBadgeObject.AddComponent<LayoutElement>();
            badgeLayout.minWidth = 120f;
            badgeLayout.preferredWidth = 120f;
            badgeLayout.minHeight = 32f;
            badgeLayout.preferredHeight = 32f;
            Image liveBadgeBackground = liveBadgeObject.AddComponent<Image>();
            liveBadgeBackground.color = isLive ? new Color32(50, 133, 69, 225) : new Color32(92, 68, 47, 215);

            TextMeshProUGUI liveBadge = CreateTmpLabel(liveBadgeRect, isLive ? "LIVE" : "OFFLINE", 14, FontStyles.Bold);
            liveBadge.alignment = TextAlignmentOptions.Center;
            liveBadge.color = isLive ? new Color32(204, 255, 213, 255) : new Color32(255, 225, 194, 255);
            StretchRect(liveBadge.rectTransform, Vector2.zero, Vector2.zero);

            string displayName = ResolveDisplayName(friendItem.friend);
            if (!string.IsNullOrWhiteSpace(displayName) && !string.Equals(displayName, identifier, StringComparison.OrdinalIgnoreCase))
            {
                TextMeshProUGUI nameText = CreateTmpLabel(card, displayName, 17, FontStyles.Normal);
                nameText.color = new Color32(255, 223, 196, 255);
            }

            string lastSeenText = isLive
                ? "Currently active"
                : "Last seen: " + FormatLastSeen(friendItem.friend.last_login_at);
            TextMeshProUGUI metaText = CreateTmpLabel(card, lastSeenText, 14, FontStyles.Italic);
            metaText.color = new Color32(240, 199, 177, 255);
        }

        private void RespondToRequest(string requestUuid, string action)
        {
            LudoFriendApiService.Instance?.RespondToFriendRequest(
                requestUuid,
                action,
                result =>
                {
                    RunOnMainThread(() =>
                    {
                        CommonUtil.ShowStyledMessage(
                            result?.message ?? "Unable to update request",
                            result != null && result.success
                                ? (string.Equals(action, "accept", StringComparison.OrdinalIgnoreCase) ? "Request Accepted" : "Request Rejected")
                                : "Friend Request",
                            !(result != null && result.success)
                        );
                        RefreshActiveList();
                    });
                }
            );
        }

        private void EnsureRequestsStateLabel()
        {
            if (requestsContent == null || requestsStateText != null)
            {
                return;
            }

            requestsStateText = CreateTmpLabel(requestsContent, string.Empty, 18, FontStyles.Italic);
            requestsStateText.color = new Color32(198, 205, 213, 255);
        }

        private static string ResolveDisplayName(LudoFriendUserData user)
        {
            if (user == null)
            {
                return "Unknown Player";
            }

            if (!string.IsNullOrWhiteSpace(user.username))
            {
                return user.username;
            }

            if (!string.IsNullOrWhiteSpace(user.user_code))
            {
                return user.user_code;
            }

            return "Unknown Player";
        }

        private bool IsLocalUser(LudoFriendUserData user, string localUserId)
        {
            if (user == null || string.IsNullOrWhiteSpace(localUserId))
            {
                return false;
            }

            string trimmedLocalId = localUserId.Trim();
            return string.Equals(user.id.ToString(), trimmedLocalId, StringComparison.OrdinalIgnoreCase)
                || (!string.IsNullOrWhiteSpace(user.user_code) && string.Equals(user.user_code.Trim(), trimmedLocalId, StringComparison.OrdinalIgnoreCase))
                || (!string.IsNullOrWhiteSpace(user.uuid) && string.Equals(user.uuid.Trim(), trimmedLocalId, StringComparison.OrdinalIgnoreCase));
        }

        private bool IsUserRecentlyOnline(string lastLoginAt)
        {
            if (string.IsNullOrWhiteSpace(lastLoginAt))
            {
                return false;
            }

            if (!DateTime.TryParse(lastLoginAt, null, System.Globalization.DateTimeStyles.RoundtripKind, out DateTime parsedAt))
            {
                return false;
            }

            return (DateTime.UtcNow - parsedAt.ToUniversalTime()).TotalMinutes <= 10d;
        }

        private string FormatLastSeen(string lastLoginAt)
        {
            if (string.IsNullOrWhiteSpace(lastLoginAt))
            {
                return "Not available";
            }

            if (!DateTime.TryParse(lastLoginAt, null, System.Globalization.DateTimeStyles.RoundtripKind, out DateTime parsedAt))
            {
                return "Recently";
            }

            TimeSpan elapsed = DateTime.UtcNow - parsedAt.ToUniversalTime();
            if (elapsed.TotalMinutes < 1d)
            {
                return "just now";
            }

            if (elapsed.TotalHours < 1d)
            {
                return Mathf.Max(1, Mathf.RoundToInt((float)elapsed.TotalMinutes)) + " min ago";
            }

            if (elapsed.TotalDays < 1d)
            {
                return Mathf.Max(1, Mathf.RoundToInt((float)elapsed.TotalHours)) + " hr ago";
            }

            return Mathf.Max(1, Mathf.RoundToInt((float)elapsed.TotalDays)) + " day ago";
        }

        private void SetTabVisualState(Button tabButton, bool active)
        {
            if (tabButton == null)
            {
                return;
            }

            Image image = tabButton.GetComponent<Image>();
            if (image != null)
            {
                image.color = active ? new Color32(139, 40, 46, 235) : new Color32(95, 54, 31, 220);
            }
        }

        private void UpdateSceneShortcutBadge(int pendingIncomingCount)
        {
            Transform shortcut = FindSceneTransformByName("Friends-icon");
            if (shortcut == null)
            {
                shortcut = FindSceneTransformByName("friends-icon");
            }

            if (shortcut == null)
            {
                Transform background = FindSceneTransformByName("icon-bg (2)");
                if (background != null && background.childCount > 0)
                {
                    shortcut = background.GetChild(0);
                }
            }

            if (shortcut == null)
            {
                return;
            }

            if (sceneShortcutBadgeObject == null)
            {
                Transform existing = shortcut.Find("FriendRequestBadge");
                if (existing != null)
                {
                    sceneShortcutBadgeObject = existing.gameObject;
                    sceneShortcutBadgeText = existing.GetComponentInChildren<Text>(true);
                }
            }

            if (sceneShortcutBadgeObject == null)
            {
                GameObject badgeObject = new GameObject(
                    "FriendRequestBadge",
                    typeof(RectTransform),
                    typeof(CanvasRenderer),
                    typeof(Image)
                );
                badgeObject.transform.SetParent(shortcut, false);
                RectTransform badgeRect = badgeObject.GetComponent<RectTransform>();
                badgeRect.anchorMin = new Vector2(1f, 1f);
                badgeRect.anchorMax = new Vector2(1f, 1f);
                badgeRect.pivot = new Vector2(1f, 1f);
                badgeRect.sizeDelta = new Vector2(36f, 36f);
                badgeRect.anchoredPosition = new Vector2(8f, 8f);
                Image badgeImage = badgeObject.GetComponent<Image>();
                badgeImage.color = new Color32(220, 51, 66, 255);

                GameObject labelObject = new GameObject(
                    "BadgeLabel",
                    typeof(RectTransform),
                    typeof(CanvasRenderer),
                    typeof(Text)
                );
                labelObject.transform.SetParent(badgeObject.transform, false);
                RectTransform labelRect = labelObject.GetComponent<RectTransform>();
                labelRect.anchorMin = Vector2.zero;
                labelRect.anchorMax = Vector2.one;
                labelRect.offsetMin = Vector2.zero;
                labelRect.offsetMax = Vector2.zero;
                Text badgeText = labelObject.GetComponent<Text>();
                badgeText.font = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
                badgeText.fontSize = 18;
                badgeText.fontStyle = FontStyle.Bold;
                badgeText.alignment = TextAnchor.MiddleCenter;
                badgeText.color = Color.white;

                sceneShortcutBadgeObject = badgeObject;
                sceneShortcutBadgeText = badgeText;
            }

            if (sceneShortcutBadgeObject != null)
            {
                sceneShortcutBadgeObject.SetActive(pendingIncomingCount > 0);
            }

            if (sceneShortcutBadgeText != null)
            {
                sceneShortcutBadgeText.text = pendingIncomingCount > 99 ? "99+" : pendingIncomingCount.ToString();
            }
        }

        private void RunOnMainThread(Action action)
        {
            if (action == null)
            {
                return;
            }

            UnityMainThreadDispatcher.Instance?.Enqueue(action);
        }

        private Transform FindSceneAnchor(string labelText)
        {
            if (string.IsNullOrWhiteSpace(labelText))
            {
                return null;
            }

            TextMeshProUGUI[] tmpLabels = Resources.FindObjectsOfTypeAll<TextMeshProUGUI>();
            for (int i = 0; i < tmpLabels.Length; i++)
            {
                TextMeshProUGUI label = tmpLabels[i];
                if (label == null || !label.gameObject.scene.IsValid() || string.IsNullOrWhiteSpace(label.text))
                {
                    continue;
                }

                if (label.text.IndexOf(labelText, StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    Button button = label.GetComponentInParent<Button>(true);
                    return button != null ? button.transform : label.transform;
                }
            }

            Text[] legacyLabels = Resources.FindObjectsOfTypeAll<Text>();
            for (int i = 0; i < legacyLabels.Length; i++)
            {
                Text label = legacyLabels[i];
                if (label == null || !label.gameObject.scene.IsValid() || string.IsNullOrWhiteSpace(label.text))
                {
                    continue;
                }

                if (label.text.IndexOf(labelText, StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    Button button = label.GetComponentInParent<Button>(true);
                    return button != null ? button.transform : label.transform;
                }
            }

            return null;
        }

        private Transform FindSceneTransformByName(string objectName)
        {
            if (string.IsNullOrWhiteSpace(objectName))
            {
                return null;
            }

            Transform[] transforms = Resources.FindObjectsOfTypeAll<Transform>();
            for (int i = 0; i < transforms.Length; i++)
            {
                Transform transform = transforms[i];
                if (transform == null || !transform.gameObject.scene.IsValid())
                {
                    continue;
                }

                if (string.Equals(transform.name, objectName, StringComparison.OrdinalIgnoreCase))
                {
                    return transform;
                }
            }

            return null;
        }

        private Sprite ResolveBoxSprite()
        {
#if UNITY_EDITOR
            Sprite editorSprite = AssetDatabase.LoadAssetAtPath<Sprite>(BoxSpriteAssetPath);
            if (editorSprite != null)
            {
                return editorSprite;
            }
#endif
            Sprite[] loadedSprites = Resources.FindObjectsOfTypeAll<Sprite>();
            for (int i = 0; i < loadedSprites.Length; i++)
            {
                Sprite sprite = loadedSprites[i];
                if (sprite != null && string.Equals(sprite.name, "Box", StringComparison.OrdinalIgnoreCase))
                {
                    return sprite;
                }
            }

            return null;
        }

        private Sprite ResolveHomepageIconSprite(string spriteName)
        {
#if UNITY_EDITOR
            Sprite directAssetSprite = AssetDatabase.LoadAssetAtPath<Sprite>(HomeFriendIconAssetPath);
            if (directAssetSprite != null)
            {
                return directAssetSprite;
            }
#endif

            if (string.IsNullOrWhiteSpace(spriteName))
            {
                return null;
            }

            Sprite[] loadedSprites = Resources.FindObjectsOfTypeAll<Sprite>();
            for (int i = 0; i < loadedSprites.Length; i++)
            {
                Sprite sprite = loadedSprites[i];
                if (sprite != null && string.Equals(sprite.name, spriteName, StringComparison.OrdinalIgnoreCase))
                {
                    return sprite;
                }
            }

            SpriteAtlas[] atlases = Resources.FindObjectsOfTypeAll<SpriteAtlas>();
            for (int i = 0; i < atlases.Length; i++)
            {
                SpriteAtlas atlas = atlases[i];
                if (atlas == null)
                {
                    continue;
                }

                Sprite sprite = atlas.GetSprite(spriteName);
                if (sprite != null)
                {
                    return sprite;
                }
            }

            return null;
        }

        private Sprite ResolvePopupBackgroundSprite(string spriteName)
        {
#if UNITY_EDITOR
            Sprite directAssetSprite = AssetDatabase.LoadAssetAtPath<Sprite>(HomePopupBackgroundAssetPath);
            if (directAssetSprite != null)
            {
                return directAssetSprite;
            }
#endif

            if (string.IsNullOrWhiteSpace(spriteName))
            {
                return null;
            }

            Image[] sceneImages = Resources.FindObjectsOfTypeAll<Image>();
            for (int i = 0; i < sceneImages.Length; i++)
            {
                Image image = sceneImages[i];
                if (
                    image != null
                    && image.sprite != null
                    && string.Equals(image.sprite.name, spriteName, StringComparison.OrdinalIgnoreCase)
                )
                {
                    return image.sprite;
                }
            }

            return null;
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

        private static TextMeshProUGUI CreateTmpLabel(Transform parent, string value, float size, FontStyles style)
        {
            GameObject labelObject = CreateUiObject("TMPLabel", parent);
            TextMeshProUGUI label = labelObject.AddComponent<TextMeshProUGUI>();
            label.text = value;
            label.fontSize = size;
            label.fontStyle = style;
            label.color = Color.white;
            return label;
        }


        private static Button CreateActionButton(Transform parent, string label, Color32 backgroundColor)
        {
            GameObject buttonObject = CreateUiObject(label.Replace(" ", string.Empty) + "Button", parent);
            RectTransform rect = buttonObject.AddComponent<RectTransform>();
            rect.sizeDelta = new Vector2(0f, 44f);
            Image image = buttonObject.AddComponent<Image>();
            image.color = backgroundColor;
            Outline outline = buttonObject.AddComponent<Outline>();
            outline.effectColor = new Color32(255, 216, 170, 70);
            outline.effectDistance = new Vector2(1.5f, -1.5f);
            Button button = buttonObject.AddComponent<Button>();
            TextMeshProUGUI labelText = CreateTmpLabel(rect, label, 16, FontStyles.Bold);
            labelText.alignment = TextAlignmentOptions.Center;
            labelText.color = new Color32(255, 245, 228, 255);
            StretchRect(labelText.rectTransform, Vector2.zero, Vector2.zero);
            return button;
        }
    }
}
