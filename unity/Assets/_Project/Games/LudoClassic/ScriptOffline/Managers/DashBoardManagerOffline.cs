using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using DG.Tweening;
using Mkey;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using Object = UnityEngine.Object;

namespace LudoClassicOffline
{
public class DashBoardManagerOffline : MonoBehaviour
{
        public ProfileAvtar avatar;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public LudoNumberGsNewOffline ludoNumberGsNew;
        public static DashBoardManagerOffline instance;
        public string baseUrl;

        [Header("GamePlay Header")]
        public Image playerProfile;
        public TextMeshProUGUI userName;
        public GameObject backButton;
        public TextMeshProUGUI chipsAmt;
        public Text realAmt;
        public Image gamePlayProfile;
        public Image player2WinProfile,
            player4WinProfile;

        [Header("Select Mode Practice/Cash")]
        public GameObject dashBordPanal;
        public GameObject gamePlayPanal;

        [Header("Select Game Mode")]
        public GameObject selectGameModePanal;

        [Header("Player Info")]
        public GameObject infoPanal;
        public Text userInfoName;
        public Image playerInfoProfile;
        public Image userNameEditButton;
        public Sprite edit;
        public InputField userNameEditField;
        public Text userInfoChips;
        public Text gamePlayedText;
        public Text gameWonText;
        public Text gameLossText;
        public int gamePlayed;
        public int gameWon;
        public int gameLoss;

        [Header("User Register")]
        public GameObject userRagisterPanal;

        //   public RegisterUserAPIRequest registerUserAPIRequest;
        //    public DashBoardAPIRequestHandler dashBoardAPIRequestHandler;
        public InputField userNameField;
        public string userNameInput = "";

        [Header("Alert PopUp")]
        public GameObject alertPopUp;
        public Text alertPopUpText;

        [Header("Lobby Select")]
        public GameObject lobbySelectPanal;
        public GameObject onlineLobbySelectionPanel;

        //   public LobbyHandler lobyPrefab;
        public GameObject lobyHolder;

        //  public List<LobbyHandler> lobyList;
        public RectTransform player2,
            player4;
        public RectTransform player2Online,
            player4Online;
        Vector2 maxSize = new Vector2(428, 120);
        Vector2 smallSize = new Vector2(428, 98);
        float oldPostion = -50;
        float newPostion = -38;

        [SerializeField]
        Sprite selectSprite,
            unSelectSprite;

        [SerializeField]
        Image player2Button,
            player4Button;

        [SerializeField]
        Image player2ButtonOnline,
            player4ButtonOnline;
        private GameObject passNPlayPlayerCountPopup;
        private int selectedPassNPlayPlayerCount = 2;
        public GameObject twoPlayerLobby,
            fourPlayerLobby;

        [Header("Wallet")]
        public GameObject walletPopUp;

        [Header("Other")]
        public int buttonClickCount = 0;

        [Header("Script Object")]
        public LudoNumberUiManagerOffline ludoNumberUiManager;
        public SocketConnectionOffline SocketConnection;

        public GameObject storePanal;
        public List<Sprite> playerAvtarList;
        public int playerAvtarNO;
        public string playerUserNameStore;
        public float totalChipsStore;
        public Transform cukiHolder;
        public GameObject storPanal;
        public GameObject settinPanal;

        public bool isShow;
        public bool IsPassAndPlay;

        public GameObject alertPopUpForBalance;

        public string iOSAppID = "your_ios_app_id"; // Replace with your iOS app ID
        public string androidPackageName = "your_android_package_name"; // Replace with your Android package name
        private float rewardTime = 30;
        private float timeAfterReward;

        public GameObject alertButton;
        public GameObject alertText;
        public GameObject fTUEPanal;
        public GameObject fTUEManager;
        public Text timeText;

        public string classicFTUE;
        public string diceFTUE;
        public string numberFTUE;
        public Button specialOfferBtn;
        private LudoV2MatchmakingBridge ludoV2Bridge;
        private LudoFriendPanelController ludoFriendPanelController;
        private LudoTournamentPanelOffline ludoTournamentPanel;
        private LudoCreateTournamentPanelOffline ludoCreateTournamentPanel;
        private LudoMyTournamentsPanelOffline ludoMyTournamentsPanel;
        private LudoBracketViewerPanelOffline ludoBracketViewerPanel;
        private LudoTournamentMatchNotificationOffline ludoMatchNotification;
        private RectTransform tournamentTab;
        private Button tournamentTabButton;
        private Image tournamentTabImage;
        private bool hasCachedTabPositions;
        private Vector2 _tabCustomSize;   // computed once in EnsureTournamentClassicTab, reused on every click
        private bool _tabCustomSizeReady;
        private Vector2 cachedPlayer2Position;
        private Vector2 cachedPlayer4Position;
        private Vector2 cachedPlayer2Size;
        private Vector2 cachedPlayer4Size;
        private Coroutine classicLobbyTablesCoroutine;
        private int classicLobbyTablesRequestVersion;
        private readonly Dictionary<int, HashSet<int>> classicLobbyAllowedFees =
            new Dictionary<int, HashSet<int>>()
            {
                { 2, new HashSet<int>() },
                { 4, new HashSet<int>() },
            };
        private readonly List<GameObject> hiddenTournamentMenuObjects = new List<GameObject>();
        private bool suppressTournamentSideMenu;

        private void Awake()
        {
            try
            {
                instance = this;
                ResolveFriendPanelController();

                // string Json;
                //  Debug.Log("DashBoardManagerz || Awake =>" + Application.dataPath + "/Resources/" + "newTextFile.txt");
                //string path = Application.dataPath + "/Resources/" + "newTextFile.txt";
                //if (File.Exists(path))
                //{
                //    Json = File.ReadAllText(path);
                //    avatar = JsonUtility.FromJson<ProfileAvtar>(Json);
                //    for (int i = 0; i < avatar.players.Count; i++)
                //    {
                //        Debug.Log("Json i Awake  = >" + i + " || IsActive => " + avatar.players[i].isActive);
                //    }
                //}



                if (PlayerPrefs.HasKey("AvatarDetails"))
                    avatar = JsonUtility.FromJson<ProfileAvtar>(
                        PlayerPrefs.GetString("AvatarDetails")
                    );

                if (PlayerPrefs.HasKey("userName"))
                {
                    //playerUserNameStore = Configuration.GetName();
                    playerUserNameStore = PlayerPrefs.GetString("userName");
                }
                else
                {
                    PlayerPrefs.SetString("userName", Configuration.GetName());
                }
                if (PlayerPrefs.HasKey("Totalchips"))
                {
                    //totalChipsStore = PlayerPrefs.GetInt("Totalchips");
                    totalChipsStore = float.Parse(PlayerPrefs.GetString("wallet"));
                    PlayerPrefs.SetInt("Totalchips", (int)totalChipsStore);
                    Debug.Log(" Chips" + totalChipsStore);
                    Debug.Log(" Chips -- 1" + float.Parse(PlayerPrefs.GetString("wallet")));
                }
                else
                {
                    //PlayerPrefs.SetInt("Totalchips", 5000);
                    //totalChipsStore = PlayerPrefs.GetInt("Totalchips");
                    totalChipsStore = float.Parse(PlayerPrefs.GetString("wallet"));
                    PlayerPrefs.SetInt("Totalchips", (int)totalChipsStore);
                    //PlayerPrefs.SetInt("Totalchips", 5000);
                    Debug.Log(" Chips" + totalChipsStore);
                    Debug.Log(" Chips -- 1" + float.Parse(PlayerPrefs.GetString("wallet")));
                }
                if (totalChipsStore <= 0)
                {
                    storPanal.SetActive(true);
                }
                if (PlayerPrefs.HasKey("avtarNo"))
                {
                    playerAvtarNO = PlayerPrefs.GetInt("avtarNo");
                }
                else
                {
                    PlayerPrefs.GetInt("avtarNo", 0);
                }

                if (PlayerPrefs.HasKey("classicFTUE"))
                {
                    classicFTUE = PlayerPrefs.GetString("classicFTUE");
                }
                else
                {
                    PlayerPrefs.SetString("classicFTUE", "false");
                    classicFTUE = PlayerPrefs.GetString("classicFTUE");
                }
                if (PlayerPrefs.HasKey("diceFTUE"))
                {
                    diceFTUE = PlayerPrefs.GetString("diceFTUE");
                }
                else
                {
                    PlayerPrefs.SetString("diceFTUE", "false");
                    diceFTUE = PlayerPrefs.GetString("diceFTUE");
                }
                if (PlayerPrefs.HasKey("numberFTUE"))
                {
                    numberFTUE = PlayerPrefs.GetString("numberFTUE");
                }
                else
                {
                    PlayerPrefs.SetString("numberFTUE", "false");
                    numberFTUE = PlayerPrefs.GetString("numberFTUE");
                }

                if (PlayerPrefs.HasKey("gamePlayed"))
                {
                    gamePlayed = PlayerPrefs.GetInt("gamePlayed");
                    gameWon = PlayerPrefs.GetInt("gameWon");
                    gameLoss = PlayerPrefs.GetInt("gameLoss");
                    UpdateGameStatistics(gamePlayed, gameWon, gameLoss);
                }
                else
                {
                    PlayerPrefs.SetInt("gamePlayed", 0);
                    PlayerPrefs.SetInt("gameWon", 0);
                    PlayerPrefs.SetInt("gameLoss", 0);
                    UpdateGameStatistics(
                        PlayerPrefs.GetInt("gamePlayed"),
                        PlayerPrefs.GetInt("gameWon"),
                        PlayerPrefs.GetInt("gameLoss")
                    );
                }

                totalChipsStore = PlayerPrefs.GetInt("Totalchips");
                playerUserNameStore = PlayerPrefs.GetString("userName");
                playerAvtarNO = PlayerPrefs.GetInt("avtarNo");
                UpdateUserName(playerUserNameStore);
                UpdateChips(totalChipsStore);
                UpdateProfilePic(playerAvtarNO);
                Invoke(nameof(LoaderOff), 1f);
                Debug.Log("Awake Call");
            }
            catch (System.Exception ex)
            {
                Debug.Log("Awake Call Try Carch => " + ex);
            }
        }

        public void Start()
        {
            if (
                MGPSDK.MGPGameManager.instance != null
                && MGPSDK.MGPGameManager.instance.sdkConfig != null
                && MGPSDK.MGPGameManager.instance.sdkConfig.data != null
                && MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails != null
            )
            {
                baseUrl =
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.hostURL
                    + ":"
                    + MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.portNumber
                    + "/ludogame/";
            }
            else
            {
                Debug.LogWarning("MGPGameManager socket config is unavailable. Using empty Ludo baseUrl.");
                baseUrl = string.Empty;
            }

            string s = PlayerPrefs.GetString("TimeAfterReward");
            if (s == "")
            {
                isShow = true;
                return;
            }
            DateTime dt = DateTime.Parse(s);
            Debug.Log("Current Time : " + DateTime.Now);
            Debug.Log("Save Time : " + dt);
            float f = (float)(DateTime.Now - dt).TotalSeconds;
            Debug.Log("Resume Second : " + f);
            if (f > rewardTime)
            {
                Debug.Log("make watch btn on");
                isShow = true;
                alertButton.SetActive(true);
                alertText.SetActive(false);
                //  adTimerTxtStore.SetActive(false);
                //  adBtnOfAlertPanel.SetActive(true);
            }
            else
            {
                RewarderAddtimer(rewardTime - f);
            }

            if (PlayerPrefs.GetInt("removeAds") == 0)
            {
                specialOfferBtn.interactable = false;
            }

            // Start tournament match-ready notification polling
            ResolveMatchNotification();

            // CheckUSBDebugging();
            //  dashBoardAPIRequestHandler.RunningGameAPI();
        }

        private void Update()
        {
            if (suppressTournamentSideMenu)
            {
                HideTournamentSideMenu();
            }
        }

        #region Chack Debug Mode
        public void CheckUSBDebugging()
        {
#if UNITY_EDITOR
#elif UNITY_ANDROID && !UNITY_EDITOR
            if (IsAdbEnabled() == 1)
            {
                OpenAlertPopUp("App Running On Developer Mode");
                //  usbDebuggingController.OpenUSBDebuggingScreen();
                return;
            }
            else
            {
                //   OpenAlertPopUp("App Running On Developer Mode");
                // usbDebuggingController.CloseScreen();
            }
#elif UNITY_IOS && !UNITY_EDITOR


#endif
        }

        public int IsAdbEnabled()
        {
            using (var actClass = new AndroidJavaClass("com.unity3d.player.UnityPlayer"))
            {
                var context = actClass.GetStatic<AndroidJavaObject>("currentActivity");
                AndroidJavaClass systemGlobal = new AndroidJavaClass(
                    "android.provider.Settings$Global"
                );
                var adbFlag = systemGlobal.CallStatic<int>(
                    "getInt",
                    context.Call<AndroidJavaObject>("getContentResolver"),
                    "adb_enabled"
                );
                Debug.Log("adbEnabled Mode is Now = " + adbFlag);
                return adbFlag;
            }
        }

        #endregion

        public void ClickOnLudoGameExitBtn()
        {
            DOTween.KillAll(false);
            GetComponent<LudoRoomChatController>()?.SetChatAvailability(false);
            GetComponent<LudoRoomChatController>()?.ClearMessages();
            ResolveFriendPanelController().SetRoomActionAvailability(false);
            ResolveFriendPanelController().SetHomeShortcutAvailability(false);

            if (SceneLoader.Instance != null)
            {
                SceneLoader.Instance.LoadScene("HomePage");
                return;
            }

            if (Application.CanStreamedLevelBeLoaded("HomePage"))
            {
                SceneManager.LoadScene("HomePage");
            }
        }

        #region Select Mode Practice/Cash

        public void ClickOnPracticeORRealButton(string mode)
        {
            switch (mode)
            {
                case "CASH":
                    //     MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.isCash = true;
                    break;
                case "PRACTICE":
                    //    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.isCash = false;
                    break;
            }
            gamePlayPanal.SetActive(false);
            backButton.SetActive(true);
            selectGameModePanal.SetActive(true);
        }

        #endregion

        #region Select Game Mode Button

        public void LoaderOff()
        {
            ludoNumberUiManager.reconnationPanel.SetActive(false);
        }

        public void ClickOnGameModeButton(string gameMode)
        {
            //ADManagerOffline.instance.HideBanner(true);
            SetLobbyUiBlocking(true);
            switch (gameMode)
            {
                case "CLASSIC":
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName =
                        "CLASSIC";
                    break;
                case "NUMBER":
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName = "NUMBER";
                    break;
                case "DICE":
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName = "DICE";
                    break;
                case "ONLINE":
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName =
                        "CLASSIC";
                    break;
            }
            backButton.SetActive(true);
            selectGameModePanal.SetActive(false);
            settinPanal.SetActive(false);
            if (gameMode == "ONLINE")
            {
                onlineLobbySelectionPanel.SetActive(true);
                CloseTournamentPanel();
                HideTournamentClassicTab();
            }
            else
            {
                lobbySelectPanal.SetActive(true);
                if (gameMode == "CLASSIC")
                {
                    PrepareClassicLobbyVisibility();
                    RefreshClassicLobbyVisibilityFromAdmin();
                    ResolveTournamentPanel().ShowLauncherButton(false);
                    EnsureTournamentClassicTab();
                }
                else
                {
                    ResolveTournamentPanel().ShowLauncherButton(false);
                    CloseTournamentPanel();
                    HideTournamentClassicTab();
                }
            }
            //  MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.noOfPlayer = 4;
            //    ClickOnPlayerButton(4);
            //  dashBoardAPIRequestHandler.LobyyRequestData();
        }

        #endregion

        #region Handle BackButton

        public void ClickOnBackButton()
        {
            //ADManagerOffline.instance.HideBanner(false);
            CloseTournamentPanel();
            HideTournamentClassicTab();
            backButton.SetActive(false);
            selectGameModePanal.SetActive(true);
            lobbySelectPanal.SetActive(false);
            onlineLobbySelectionPanel.SetActive(false);
        }

        //public void ClickOnButton(int no)
        //{
        //    buttonClickCount = no;
        //}
        //public void ClcikOnBackButton()
        //{

        //    switch (buttonClickCount)
        //    {
        //        case 0:
        //            selectGameModePanal.SetActive(false);
        //            gamePlayPanal.SetActive(true);
        //            backButton.SetActive(false);
        //            buttonClickCount = 0;
        //            break;
        //        case 1:
        //            selectGameModePanal.SetActive(true);
        //            lobbySelectPanal.SetActive(false);
        //            buttonClickCount = 0;
        //            break;
        //    }
        //}

        #endregion

        #region UserInfo
        public void ClickOnInfoButton()
        {
            Debug.Log("Click On Info button");
            infoPanal.SetActive(true);
        }

        public string fileName = "newTextFile.txt";
        private const string ProfileInitialLabelName = "ProfileInitialLabel";
        private static Sprite cachedNeutralProfileAvatarSprite;

        private static readonly Color NeutralAvatarFillColor = new Color32(0x2F, 0x3A, 0x4A, 0xFF);
        private static readonly Color NeutralAvatarRingColor = new Color32(0xF2, 0xC9, 0x4C, 0xFF);

        private Sprite GetNeutralProfileAvatarSprite()
        {
            if (cachedNeutralProfileAvatarSprite != null)
            {
                return cachedNeutralProfileAvatarSprite;
            }

            const int textureSize = 128;
            const float outerRadius = 61f;
            const float innerRadius = 53f;

            Texture2D texture = new Texture2D(textureSize, textureSize, TextureFormat.ARGB32, false);
            texture.name = "NeutralProfileAvatar";
            texture.filterMode = FilterMode.Bilinear;
            texture.wrapMode = TextureWrapMode.Clamp;

            Vector2 center = new Vector2((textureSize - 1) * 0.5f, (textureSize - 1) * 0.5f);
            Color clear = new Color(0f, 0f, 0f, 0f);

            for (int y = 0; y < textureSize; y++)
            {
                for (int x = 0; x < textureSize; x++)
                {
                    float distance = Vector2.Distance(new Vector2(x, y), center);
                    Color pixelColor = clear;

                    if (distance <= outerRadius)
                    {
                        pixelColor = distance >= innerRadius ? NeutralAvatarRingColor : NeutralAvatarFillColor;
                    }

                    texture.SetPixel(x, y, pixelColor);
                }
            }

            texture.Apply(false, false);

            cachedNeutralProfileAvatarSprite = Sprite.Create(
                texture,
                new Rect(0, 0, texture.width, texture.height),
                new Vector2(0.5f, 0.5f),
                100f
            );
            cachedNeutralProfileAvatarSprite.name = "NeutralProfileAvatarSprite";
            return cachedNeutralProfileAvatarSprite;
        }

        private string ResolveProfileDisplayName()
        {
            if (userName != null && !string.IsNullOrWhiteSpace(userName.text))
            {
                return userName.text.Trim();
            }

            if (userInfoName != null && !string.IsNullOrWhiteSpace(userInfoName.text))
            {
                return userInfoName.text.Trim();
            }

            if (!string.IsNullOrWhiteSpace(playerUserNameStore))
            {
                return playerUserNameStore.Trim();
            }

            string savedUserName = PlayerPrefs.GetString("userName", string.Empty);
            if (!string.IsNullOrWhiteSpace(savedUserName))
            {
                return savedUserName.Trim();
            }

            return "Player";
        }

        private string ResolveProfileInitial(string displayName)
        {
            if (!string.IsNullOrWhiteSpace(displayName))
            {
                for (int i = 0; i < displayName.Length; i++)
                {
                    char currentChar = displayName[i];
                    if (char.IsLetterOrDigit(currentChar))
                    {
                        return currentChar.ToString().ToUpperInvariant();
                    }
                }
            }

            return "P";
        }

        private TextMeshProUGUI EnsureProfileInitialLabel(Image targetImage)
        {
            if (targetImage == null)
            {
                return null;
            }

            Transform existingLabelTransform = targetImage.transform.Find(ProfileInitialLabelName);
            TextMeshProUGUI label =
                existingLabelTransform != null
                    ? existingLabelTransform.GetComponent<TextMeshProUGUI>()
                    : null;

            if (label != null)
            {
                return label;
            }

            GameObject labelObject = new GameObject(ProfileInitialLabelName, typeof(RectTransform));
            RectTransform labelRect = labelObject.GetComponent<RectTransform>();
            labelRect.SetParent(targetImage.transform, false);
            labelRect.anchorMin = Vector2.zero;
            labelRect.anchorMax = Vector2.one;
            labelRect.offsetMin = Vector2.zero;
            labelRect.offsetMax = Vector2.zero;

            label = labelObject.AddComponent<TextMeshProUGUI>();
            label.raycastTarget = false;
            label.alignment = TextAlignmentOptions.Center;
            label.enableAutoSizing = true;
            label.fontSizeMin = 18f;
            label.fontSizeMax = 64f;
            label.fontSize = 48f;
            label.color = Color.white;
            label.fontStyle = FontStyles.Bold;
            label.textWrappingMode = TextWrappingModes.NoWrap;

            if (TMP_Settings.defaultFontAsset != null)
            {
                label.font = TMP_Settings.defaultFontAsset;
            }

            return label;
        }

        private void ApplyInitialAvatarToImage(Image targetImage, string initial)
        {
            if (targetImage == null)
            {
                return;
            }

            targetImage.sprite = GetNeutralProfileAvatarSprite();
            targetImage.color = Color.white;
            targetImage.preserveAspect = true;

            TextMeshProUGUI label = EnsureProfileInitialLabel(targetImage);
            if (label != null)
            {
                label.text = initial;
            }
        }

        private void ApplyProfileAvatar(string displayName)
        {
            string initial = ResolveProfileInitial(displayName);
            Sprite neutralAvatar = GetNeutralProfileAvatarSprite();

            ApplyInitialAvatarToImage(playerProfile, initial);
            ApplyInitialAvatarToImage(playerInfoProfile, initial);
            ApplyInitialAvatarToImage(gamePlayProfile, initial);
            ApplyInitialAvatarToImage(player2WinProfile, initial);
            ApplyInitialAvatarToImage(player4WinProfile, initial);

            if (SpriteManager.Instance != null)
            {
                SpriteManager.Instance.profile_image = neutralAvatar;
            }
        }

        public void UpdateProfilePic(int no)
        {
            try
            {
                if (avatar != null && avatar.players != null)
                {
                    for (int i = 0; i < avatar.players.Count; i++)
                    {
                        avatar.players[i].isActive = (no == i);
                    }
                }

                string Json = JsonUtility.ToJson(avatar, true);

                PlayerPrefs.SetString("AvatarDetails", Json);
                //string filePath = Path.Combine(Application.dataPath + "/Resources", fileName);

                // The content you want to write to the file

                //// Create and write to the new text file
                //File.WriteAllText(filePath, Json);


                //Debug.Log("Text file created at: " + filePath);
                ApplyProfileAvatar(ResolveProfileDisplayName());

                PlayerPrefs.SetInt("avtarNo", no);
            }
            catch (System.Exception ex)
            {
                Debug.Log(ex);
            }
        }

        public void UpdateGameStatistics(int gamePlayed, int gameWon, int gameLoss)
        {
            gamePlayedText.text = gamePlayed.ToString();
            gameWonText.text = gameWon.ToString();
            gameLossText.text = gameLoss.ToString();

            PlayerPrefs.SetInt("gamePlayed", gamePlayed);
            PlayerPrefs.SetInt("gameWon", gameWon);
            PlayerPrefs.SetInt("gameLoss", gameLoss);
        }

        public void UpdateUserName(string name)
        {
            userName.text = name;
            userInfoName.text = name;
            playerUserNameStore = name;
            ApplyProfileAvatar(ResolveProfileDisplayName());
        }

        public void UpdateChips(float chips)
        {
            chipsAmt.text = chips.ToString();
            userInfoChips.text = chips.ToString();
            //  ludoNumberUiManager.reconnationPanel.SetActive(false);
        }

        public void ClickOnProfileEditButton()
        {
            userNameEditField.gameObject.SetActive(true);
            userInfoName.gameObject.SetActive(false);
        }

        public void ChangeUserName()
        {
            //if (userNameEditField.text != string.Empty)
            //{
            //    playerUserNameStore = userNameEditField.text;
            //    UpdateUserName(playerUserNameStore);
            //    PlayerPrefs.SetString("userName", playerUserNameStore);
            //}
            //else
            //    userNameEditField.text = PlayerPrefs.GetString("userName");



            //if (userNameEditField.text != string.Empty)
            //{
            //   // userNameEditField.gameObject.SetActive(false);
            //    playerUserNameStore = userNameEditField.text;
            //    PlayerPrefs.SetString("userName", playerUserNameStore);
            //    UpdateUserName(playerUserNameStore);
            //}
            //else
            //{
            //    userNameEditField.text = PlayerPrefs.GetString("userName");
            //}
        }

        public void OnValueChange()
        {
            //if (userNameEditField.text != string.Empty)
            //{
            //    playerUserNameStore = userNameEditField.text;
            //    UpdateUserName(playerUserNameStore);
            //    PlayerPrefs.SetString("userName", playerUserNameStore);
            //}
            //else
            //    userNameEditField.text = PlayerPrefs.GetString("userName");
        }

        public void OnEndEdit()
        {
            if (userNameEditField.text != string.Empty)
            {
                userNameEditField.gameObject.SetActive(false);
                userInfoName.gameObject.SetActive(true);

                playerUserNameStore = userNameEditField.text;
                UpdateUserName(playerUserNameStore);
                PlayerPrefs.SetString("userName", playerUserNameStore);
            }
            else
            {
                userNameEditField.gameObject.SetActive(false);
                userInfoName.gameObject.SetActive(true);

                userNameEditField.text = PlayerPrefs.GetString("userName");
            }
        }

        #endregion

        #region User Wallet
        public void ClickOnWalletButoon()
        {
            walletPopUp.SetActive(true);
        }

        public void UpdateUSerWallte(int coin)
        {
            chipsAmt.text = coin.ToString();
        }
        #endregion

        #region RegisterUserAPI and SetData


        #endregion

        #region SetLobbyData



        public void ClickOnPlayerButton(int no)
        {
            ResetButton();
            switch (no)
            {
                case 2:
                    player2.sizeDelta = (_tabCustomSizeReady && tournamentTab != null && tournamentTab.gameObject.activeSelf)
                        ? _tabCustomSize : maxSize;
                    player2Button.sprite = selectSprite;
                    socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = 2;
                    fourPlayerLobby.SetActive(false);
                    twoPlayerLobby.SetActive(true);
                    ApplyClassicLobbyVisibility(2);
                    UpdateClassicModeTabSelection(2);
                    Debug.Log(socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount);
                    break;

                case 4:
                    player4.sizeDelta = (_tabCustomSizeReady && tournamentTab != null && tournamentTab.gameObject.activeSelf)
                        ? _tabCustomSize : maxSize;
                    player4Button.sprite = selectSprite;
                    socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = 4;
                    fourPlayerLobby.SetActive(true);
                    twoPlayerLobby.SetActive(false);
                    ApplyClassicLobbyVisibility(4);
                    UpdateClassicModeTabSelection(4);
                    Debug.Log(socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount);
                    break;
            }
            // dashBoardAPIRequestHandler.LobyyRequestData();
        }

        public void ClickOnOnlinePlayerButton(int no)
        {
            player2Online.DOAnchorPosY(oldPostion, 0f);
            player2Online.sizeDelta = smallSize;
            player2ButtonOnline.sprite = unSelectSprite;
            player4Online.DOAnchorPosY(oldPostion, 0f);
            player4Online.sizeDelta = smallSize;
            player4ButtonOnline.sprite = unSelectSprite;
            switch (no)
            {
                case 2:
                    player2Online.DOAnchorPosY(newPostion, 0f);
                    player2Online.sizeDelta = maxSize;
                    player2ButtonOnline.sprite = selectSprite;
                    socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = 2;
                    //fourPlayerLobby.SetActive(false);
                    //twoPlayerLobby.SetActive(true);
                    Debug.Log(socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount);
                    break;

                case 4:
                    player4Online.DOAnchorPosY(newPostion, 0f);
                    player4Online.sizeDelta = maxSize;
                    player4ButtonOnline.sprite = selectSprite;
                    socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = 4;
                    //fourPlayerLobby.SetActive(true);
                    //twoPlayerLobby.SetActive(false);
                    Debug.Log(socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount);
                    break;
            }
            // dashBoardAPIRequestHandler.LobyyRequestData();
        }

        public void ResetButton()
        {
            // When tournament tab is active use custom size so spacing never breaks
            Vector2 resetSize = (_tabCustomSizeReady && tournamentTab != null && tournamentTab.gameObject.activeSelf)
                ? _tabCustomSize
                : smallSize;

            player2.sizeDelta    = resetSize;
            player2Button.sprite = unSelectSprite;
            player4.sizeDelta    = resetSize;
            player4Button.sprite = unSelectSprite;
        }

        private void PrepareClassicLobbyVisibility()
        {
            HideClassicLobbyFeeCards(twoPlayerLobby);
            HideClassicLobbyFeeCards(fourPlayerLobby);
            WirePassNPlayLobbyCardButtons(twoPlayerLobby);
            WirePassNPlayLobbyCardButtons(fourPlayerLobby);
        }

        private void RefreshClassicLobbyVisibilityFromAdmin()
        {
            classicLobbyTablesRequestVersion++;

            if (classicLobbyTablesCoroutine != null)
            {
                StopCoroutine(classicLobbyTablesCoroutine);
            }

            classicLobbyTablesCoroutine = StartCoroutine(
                LoadClassicLobbyVisibilityFromAdmin(classicLobbyTablesRequestVersion)
            );
        }

        private IEnumerator LoadClassicLobbyVisibilityFromAdmin(int requestVersion)
        {
            yield return FetchClassicLobbyFees(2, requestVersion);
            yield return FetchClassicLobbyFees(4, requestVersion);

            if (requestVersion != classicLobbyTablesRequestVersion)
            {
                yield break;
            }

            ApplyClassicLobbyVisibility(ResolveSelectedPlayerCount());
            classicLobbyTablesCoroutine = null;
        }

        private IEnumerator FetchClassicLobbyFees(int playerCount, int requestVersion)
        {
            string userId = Configuration.GetId();
            string userToken = Configuration.GetToken();

            if (string.IsNullOrWhiteSpace(userId) || string.IsNullOrWhiteSpace(userToken))
            {
                Debug.LogWarning("Classic Ludo table visibility skipped: missing user session.");
                yield break;
            }

            WWWForm form = new WWWForm();
            form.AddField("user_id", userId);
            form.AddField("no_of_players", playerCount.ToString());
            form.AddField("token", userToken);

            using (UnityWebRequest request = UnityWebRequest.Post(Configuration.LudoGettablemaster, form))
            {
                if (!string.IsNullOrWhiteSpace(Configuration.TokenLoginHeader))
                {
                    request.SetRequestHeader("Token", Configuration.TokenLoginHeader);
                }

                yield return request.SendWebRequest();

                if (requestVersion != classicLobbyTablesRequestVersion)
                {
                    yield break;
                }

                if (request.result == UnityWebRequest.Result.ConnectionError
                    || request.result == UnityWebRequest.Result.ProtocolError)
                {
                    Debug.LogWarning(
                        "Classic Ludo table visibility request failed for "
                        + playerCount
                        + "P: "
                        + request.error
                    );
                    yield break;
                }

                string response = request.downloadHandler.text;
                ClassicLobbyTableResponse payload = null;

                try
                {
                    payload = JsonConvert.DeserializeObject<ClassicLobbyTableResponse>(response);
                }
                catch (Exception ex)
                {
                    Debug.LogWarning(
                        "Classic Ludo table visibility parse failed for "
                        + playerCount
                        + "P: "
                        + ex.Message
                    );
                }

                if (payload?.table_data == null)
                {
                    Debug.LogWarning(
                        "Classic Ludo table visibility returned no table_data for "
                        + playerCount
                        + "P."
                    );
                    yield break;
                }

                HashSet<int> allowedFees = new HashSet<int>();
                for (int i = 0; i < payload.table_data.Count; i++)
                {
                    if (TryParseLobbyFeeValue(payload.table_data[i]?.boot_value, out int fee))
                    {
                        allowedFees.Add(fee);
                    }
                }

                classicLobbyAllowedFees[playerCount] = allowedFees;
                ApplyClassicLobbyVisibility(playerCount);
                Debug.Log("Classic Ludo active " + playerCount + "P fees: " + string.Join(",", allowedFees));
            }
        }

        private void ApplyClassicLobbyVisibility(int playerCount)
        {
            GameObject lobbyRoot = playerCount == 4 ? fourPlayerLobby : twoPlayerLobby;
            if (lobbyRoot == null)
            {
                return;
            }

            HashSet<int> allowedFees = classicLobbyAllowedFees.ContainsKey(playerCount)
                ? classicLobbyAllowedFees[playerCount]
                : null;

            bool hasServerConfig = allowedFees != null && allowedFees.Count > 0;
            foreach (GameObject card in ResolveClassicLobbyCards(lobbyRoot))
            {
                if (card == null)
                {
                    continue;
                }

                if (CardContainsText(card.transform, "Pass N Play"))
                {
                    card.SetActive(true);
                    WirePassNPlayLobbyCardButtons(card);
                    continue;
                }

                int fee = ResolveClassicLobbyCardFee(card.transform);
                if (fee <= 0)
                {
                    continue;
                }

                bool shouldShow = hasServerConfig && allowedFees.Contains(fee);
                card.SetActive(shouldShow);
            }

            ApplyClassicLobbyResponsiveLayout(lobbyRoot);
            WirePassNPlayLobbyCardButtons(lobbyRoot);
        }

        private void HideClassicLobbyFeeCards(GameObject lobbyRoot)
        {
            if (lobbyRoot == null)
            {
                return;
            }

            foreach (GameObject card in ResolveClassicLobbyCards(lobbyRoot))
            {
                if (card == null)
                {
                    continue;
                }

                if (CardContainsText(card.transform, "Pass N Play"))
                {
                    card.SetActive(true);
                    WirePassNPlayLobbyCardButtons(card);
                    continue;
                }

                card.SetActive(false);
            }

            ApplyClassicLobbyResponsiveLayout(lobbyRoot);
            WirePassNPlayLobbyCardButtons(lobbyRoot);
        }

        private void WirePassNPlayLobbyCardButtons(GameObject lobbyRoot)
        {
            if (lobbyRoot == null)
            {
                return;
            }

            foreach (GameObject card in ResolveClassicLobbyCards(lobbyRoot))
            {
                if (card == null || !CardContainsText(card.transform, "Pass N Play"))
                {
                    continue;
                }

                Button[] buttons = card.GetComponentsInChildren<Button>(true);
                for (int i = 0; i < buttons.Length; i++)
                {
                    Button button = buttons[i];
                    if (button == null)
                    {
                        continue;
                    }

                    button.interactable = true;
                    button.onClick.RemoveListener(CLickOnPassNPlayButton);
                    button.onClick.AddListener(CLickOnPassNPlayButton);
                }
            }
        }

        private void ApplyClassicLobbyResponsiveLayout(GameObject lobbyRoot)
        {
            if (lobbyRoot == null)
            {
                return;
            }

            bool portrait = Screen.height >= Screen.width;

            ScrollRect[] scrollRects = lobbyRoot.GetComponentsInChildren<ScrollRect>(true);
            for (int i = 0; i < scrollRects.Length; i++)
            {
                ScrollRect scroll = scrollRects[i];
                if (scroll == null)
                {
                    continue;
                }

                scroll.horizontal = false;
                scroll.vertical = true;
                scroll.inertia = true;
                scroll.movementType = ScrollRect.MovementType.Clamped;
                scroll.scrollSensitivity = portrait ? 75f : 55f;

                RectTransform scrollRect = scroll.GetComponent<RectTransform>();
                if (scrollRect != null && portrait)
                {
                    scrollRect.anchorMin = new Vector2(0f, 0f);
                    scrollRect.anchorMax = new Vector2(1f, 1f);
                    scrollRect.offsetMin = new Vector2(0f, 8f);
                    scrollRect.offsetMax = new Vector2(0f, -8f);
                }
            }

            GridLayoutGroup[] grids = lobbyRoot.GetComponentsInChildren<GridLayoutGroup>(true);
            for (int i = 0; i < grids.Length; i++)
            {
                GridLayoutGroup grid = grids[i];
                if (grid == null)
                {
                    continue;
                }

                RectTransform gridRect = grid.GetComponent<RectTransform>();
                float width = gridRect != null ? gridRect.rect.width : 0f;
                RectTransform parentRect = gridRect != null ? gridRect.parent as RectTransform : null;
                if (width <= 0f && parentRect != null)
                {
                    width = parentRect.rect.width;
                }
                if (width <= 0f)
                {
                    width = Screen.width;
                }

                int columns = portrait ? 1 : 2;
                grid.constraint = GridLayoutGroup.Constraint.FixedColumnCount;
                grid.constraintCount = columns;
                grid.padding = portrait ? new RectOffset(14, 14, 12, 28) : new RectOffset(20, 20, 18, 32);
                grid.spacing = portrait ? new Vector2(0f, 16f) : new Vector2(18f, 20f);

                float usableWidth = width - grid.padding.left - grid.padding.right - ((columns - 1) * grid.spacing.x);
                float cardWidth = Mathf.Max(usableWidth / columns, 220f);
                float cardHeight = portrait ? Mathf.Clamp(cardWidth * 0.48f, 210f, 260f) : Mathf.Clamp(cardWidth * 0.36f, 190f, 240f);
                grid.cellSize = new Vector2(cardWidth, cardHeight);

                int activeCards = 0;
                for (int j = 0; j < grid.transform.childCount; j++)
                {
                    Transform child = grid.transform.GetChild(j);
                    if (child == null || !child.gameObject.activeSelf)
                    {
                        continue;
                    }

                    activeCards++;
                    LayoutElement element = child.GetComponent<LayoutElement>();
                    if (element == null)
                    {
                        element = child.gameObject.AddComponent<LayoutElement>();
                    }
                    element.preferredWidth = cardWidth;
                    element.preferredHeight = cardHeight;
                }

                if (gridRect != null)
                {
                    int rows = Mathf.CeilToInt(activeCards / (float)columns);
                    float contentHeight = grid.padding.top + grid.padding.bottom
                        + rows * cardHeight
                        + Mathf.Max(0, rows - 1) * grid.spacing.y;
                    gridRect.SetSizeWithCurrentAnchors(RectTransform.Axis.Horizontal, width);
                    gridRect.SetSizeWithCurrentAnchors(RectTransform.Axis.Vertical, Mathf.Max(contentHeight, gridRect.rect.height));
                    LayoutRebuilder.ForceRebuildLayoutImmediate(gridRect);
                }
            }
        }

        private List<GameObject> ResolveClassicLobbyCards(GameObject lobbyRoot)
        {
            List<GameObject> cards = new List<GameObject>();
            if (lobbyRoot == null)
            {
                return cards;
            }

            GridLayoutGroup[] grids = lobbyRoot.GetComponentsInChildren<GridLayoutGroup>(true);
            for (int i = 0; i < grids.Length; i++)
            {
                GridLayoutGroup grid = grids[i];
                if (grid == null || grid.transform == null)
                {
                    continue;
                }

                for (int j = 0; j < grid.transform.childCount; j++)
                {
                    Transform child = grid.transform.GetChild(j);
                    if (child == null)
                    {
                        continue;
                    }

                    if (CardContainsText(child, "Pass N Play") || CardContainsText(child, "Entry Fees"))
                    {
                        cards.Add(child.gameObject);
                    }
                }
            }

            return cards;
        }

        private bool TryParseLobbyFeeValue(string rawValue, out int fee)
        {
            fee = 0;
            if (string.IsNullOrWhiteSpace(rawValue))
            {
                return false;
            }

            string sanitized = rawValue
                .Replace("₹", string.Empty)
                .Replace(",", string.Empty)
                .Trim();

            if (int.TryParse(sanitized, out fee))
            {
                return fee > 0;
            }

            if (float.TryParse(sanitized, out float floatFee))
            {
                fee = Mathf.RoundToInt(floatFee);
                return fee > 0;
            }

            return false;
        }

        private bool CardContainsText(Transform root, string value)
        {
            if (root == null || string.IsNullOrWhiteSpace(value))
            {
                return false;
            }

            Text[] texts = root.GetComponentsInChildren<Text>(true);
            for (int i = 0; i < texts.Length; i++)
            {
                if (!string.IsNullOrWhiteSpace(texts[i].text)
                    && texts[i].text.IndexOf(value, StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    return true;
                }
            }

            TextMeshProUGUI[] tmpTexts = root.GetComponentsInChildren<TextMeshProUGUI>(true);
            for (int i = 0; i < tmpTexts.Length; i++)
            {
                if (!string.IsNullOrWhiteSpace(tmpTexts[i].text)
                    && tmpTexts[i].text.IndexOf(value, StringComparison.OrdinalIgnoreCase) >= 0)
                {
                    return true;
                }
            }

            return false;
        }

        private int ResolveClassicLobbyCardFee(Transform root)
        {
            List<int> numericValues = new List<int>();

            Text[] texts = root.GetComponentsInChildren<Text>(true);
            for (int i = 0; i < texts.Length; i++)
            {
                if (TryParseLobbyFeeValue(texts[i].text, out int value))
                {
                    return value;
                }
            }

            TextMeshProUGUI[] tmpTexts = root.GetComponentsInChildren<TextMeshProUGUI>(true);
            for (int i = 0; i < tmpTexts.Length; i++)
            {
                if (TryParseLobbyFeeValue(tmpTexts[i].text, out int value))
                {
                    return value;
                }
            }

            for (int i = 0; i < texts.Length; i++)
            {
                if (TryParseLobbyFeeValue(texts[i].text, out int value))
                {
                    numericValues.Add(value);
                }
            }

            for (int i = 0; i < tmpTexts.Length; i++)
            {
                if (TryParseLobbyFeeValue(tmpTexts[i].text, out int value))
                {
                    numericValues.Add(value);
                }
            }

            if (numericValues.Count == 0)
            {
                return 0;
            }

            numericValues.Sort();
            return numericValues[numericValues.Count - 1];
        }

        public void ClickOnPLayButton(int value)
        {
            int chips = PlayerPrefs.GetInt("Totalchips");
            if (value <= chips)
            {
                backButton.SetActive(false);
                lobbySelectPanal.SetActive(false);
                string currentGameMode = GetCurrentLobbyGameModeName();

                if (
                    string.Equals(currentGameMode, "CLASSIC", StringComparison.OrdinalIgnoreCase)
                    && classicFTUE == "true"
                )
                {
                    dashBordPanal.GetComponent<Canvas>().enabled = false;
                    fTUEManager.SetActive(true);
                    fTUEPanal.SetActive(true);
                    FTUEManagerOffline.Instance.value = value;
                }
                else if (
                    string.Equals(currentGameMode, "NUMBER", StringComparison.OrdinalIgnoreCase)
                    && numberFTUE == "true"
                )
                {
                    dashBordPanal.GetComponent<Canvas>().enabled = false;
                    fTUEManager.SetActive(true);
                    fTUEPanal.SetActive(true);
                    FTUEManagerOffline.Instance.value = value;
                }
                else if (
                    string.Equals(currentGameMode, "DICE", StringComparison.OrdinalIgnoreCase)
                    && diceFTUE == "true"
                )
                {
                    dashBordPanal.GetComponent<Canvas>().enabled = false;
                    fTUEManager.SetActive(true);
                    fTUEPanal.SetActive(true);
                    FTUEManagerOffline.Instance.value = value;
                }
                else
                {
                    if (Configuration.IsLudoV2Enabled() && ResolveLudoV2Bridge().TryStartMatchmaking(value, ResolveSelectedPlayerCount()))
                    {
                        SetLobbyUiBlocking(false);
                        return;
                    }

                    fTUEPanal.SetActive(false);
                    fTUEManager.SetActive(false);
                    socketNumberEventReceiver.entryFee = value;
                    if (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount == 2)
                        socketNumberEventReceiver.winAmt = value * 2;
                    else
                        socketNumberEventReceiver.winAmt = value * 4;
                    chips = chips - value;
                    PlayerPrefs.SetInt("Totalchips", chips);
                    UpdateChips(chips);

                    dashBordPanal.SetActive(false);
                    SetLobbyUiBlocking(false);

                    ChangeLobbyId();
                    //SocketConnectionOffline.CreateSocket();
                    if (
                        !string.Equals(
                            GetCurrentLobbyGameModeName(),
                            "CLASSIC",
                            StringComparison.OrdinalIgnoreCase
                        )
                    )
                    {
                        SetCookiePosition();
                    }
                    socketNumberEventReceiver.PlayerJoinData();
                    gamePlayed = PlayerPrefs.GetInt("gamePlayed");
                    gameWon = PlayerPrefs.GetInt("gameWon");
                    gameLoss = PlayerPrefs.GetInt("gameLoss");
                    UpdateGameStatistics(gamePlayed + 1, gameWon, gameLoss);
                    //ADManagerOffline.instance.LoadBanner(false);
                }
            }
            else
            {
                alertPopUpForBalance.SetActive(true);
            }
        }

        public bool TryStartTournamentMatch(string tournamentUuid, string tournamentEntryUuid, int maxPlayers = 2)
        {
            if (string.IsNullOrWhiteSpace(tournamentUuid) || string.IsNullOrWhiteSpace(tournamentEntryUuid))
            {
                CommonUtil.ShowToast("Tournament room details are missing");
                return false;
            }

            if (socketNumberEventReceiver != null
                && socketNumberEventReceiver.joinTableResponse != null
                && socketNumberEventReceiver.joinTableResponse.data != null)
            {
                socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = Mathf.Max(2, maxPlayers);
                socketNumberEventReceiver.entryFee = 0;
                socketNumberEventReceiver.winAmt = 0;
            }

            IsPassAndPlay = false;
            backButton.SetActive(false);
            lobbySelectPanal.SetActive(false);
            onlineLobbySelectionPanel.SetActive(false);
            fTUEPanal.SetActive(false);
            fTUEManager.SetActive(false);

            return ResolveLudoV2Bridge().TryStartTournamentMatchmaking(tournamentUuid, tournamentEntryUuid);
        }

        private int ResolveSelectedPlayerCount()
        {
            if (socketNumberEventReceiver != null && socketNumberEventReceiver.joinTableResponse != null && socketNumberEventReceiver.joinTableResponse.data != null && socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount > 0)
            {
                return socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount;
            }

            return fourPlayerLobby != null && fourPlayerLobby.activeSelf ? 4 : 2;
        }

        private LudoV2MatchmakingBridge ResolveLudoV2Bridge()
        {
            if (ludoV2Bridge != null)
            {
                return ludoV2Bridge;
            }

            ludoV2Bridge = GetComponent<LudoV2MatchmakingBridge>();
            if (ludoV2Bridge == null)
            {
                ludoV2Bridge = gameObject.AddComponent<LudoV2MatchmakingBridge>();
            }

            ludoV2Bridge.dashBoardManager = this;
            ludoV2Bridge.socketNumberEventReceiver = socketNumberEventReceiver;
            return ludoV2Bridge;
        }

        private LudoFriendPanelController ResolveFriendPanelController()
        {
            if (ludoFriendPanelController != null)
            {
                return ludoFriendPanelController;
            }

            ludoFriendPanelController = GetComponent<LudoFriendPanelController>();
            if (ludoFriendPanelController == null)
            {
                ludoFriendPanelController = gameObject.AddComponent<LudoFriendPanelController>();
            }

            return ludoFriendPanelController;
        }

        private LudoTournamentPanelOffline ResolveTournamentPanel()
        {
            if (ludoTournamentPanel != null)
            {
                return ludoTournamentPanel;
            }

            ludoTournamentPanel = GetComponent<LudoTournamentPanelOffline>();
            if (ludoTournamentPanel == null)
            {
                ludoTournamentPanel = gameObject.AddComponent<LudoTournamentPanelOffline>();
            }

            ludoTournamentPanel.Initialize(this);
            return ludoTournamentPanel;
        }

        public void OpenTournamentPanel()
        {
            // Hide game-mode overlay buttons (☰ and ✕) so they don't bleed through
            selectGameModePanal?.SetActive(false);
            backButton?.SetActive(false);
            suppressTournamentSideMenu = true;
            HideTournamentSideMenu();
            ludoMatchNotification?.Unsuppress();
            UpdateClassicModeTabSelection(0);
            ResolveTournamentPanel().OpenPanel();
        }

        public void CloseTournamentPanel()
        {
            if (ludoTournamentPanel != null)
                ludoTournamentPanel.ClosePanel();
            // Restore the overlay panel that contains ☰ / ✕ buttons
            suppressTournamentSideMenu = false;
            RestoreTournamentSideMenu();
            selectGameModePanal?.SetActive(true);
        }

        // ── Create Tournament Panel ───────────────────────────────────────────

        private LudoCreateTournamentPanelOffline ResolveCreateTournamentPanel()
        {
            if (ludoCreateTournamentPanel != null)
            {
                return ludoCreateTournamentPanel;
            }

            ludoCreateTournamentPanel = GetComponent<LudoCreateTournamentPanelOffline>();
            if (ludoCreateTournamentPanel == null)
            {
                ludoCreateTournamentPanel = gameObject.AddComponent<LudoCreateTournamentPanelOffline>();
            }

            ludoCreateTournamentPanel.Initialize(this);
            return ludoCreateTournamentPanel;
        }

        public void OpenCreateTournamentPanel()
        {
            selectGameModePanal?.SetActive(false);
            backButton?.SetActive(false);
            suppressTournamentSideMenu = true;
            HideTournamentSideMenu();
            ludoMatchNotification?.Suppress();
            if (ludoTournamentPanel != null)
                ludoTournamentPanel.HidePanel();
            ResolveCreateTournamentPanel().OpenPanel();
        }

        public void CloseCreateTournamentPanel()
        {
            if (ludoCreateTournamentPanel != null)
            {
                ludoCreateTournamentPanel.ClosePanel();
            }
        }

        public void SetTournamentSideMenuSuppressed(bool suppressed)
        {
            suppressTournamentSideMenu = suppressed;

            if (suppressed)
            {
                HideTournamentSideMenu();
            }
            else
            {
                RestoreTournamentSideMenu();
            }
        }

        // ── My Tournaments (history) ──────────────────────────────────────────

        private LudoMyTournamentsPanelOffline ResolveMyTournamentsPanel()
        {
            if (ludoMyTournamentsPanel != null)
                return ludoMyTournamentsPanel;

            ludoMyTournamentsPanel = GetComponent<LudoMyTournamentsPanelOffline>();
            if (ludoMyTournamentsPanel == null)
                ludoMyTournamentsPanel = gameObject.AddComponent<LudoMyTournamentsPanelOffline>();

            ludoMyTournamentsPanel.Initialize(this);
            return ludoMyTournamentsPanel;
        }

        public void OpenMyTournamentsPanel()
        {
            selectGameModePanal?.SetActive(false);
            backButton?.SetActive(false);
            ludoMatchNotification?.Suppress();
            if (ludoTournamentPanel != null)
                ludoTournamentPanel.HidePanel();
            ResolveMyTournamentsPanel().OpenPanel();
        }

        public void CloseMyTournamentsPanel()
        {
            if (ludoMyTournamentsPanel != null)
                ludoMyTournamentsPanel.ClosePanel();
        }

        // ── Bracket Viewer ────────────────────────────────────────────────────

        private LudoBracketViewerPanelOffline ResolveBracketViewerPanel()
        {
            if (ludoBracketViewerPanel != null)
                return ludoBracketViewerPanel;

            ludoBracketViewerPanel = GetComponent<LudoBracketViewerPanelOffline>();
            if (ludoBracketViewerPanel == null)
                ludoBracketViewerPanel = gameObject.AddComponent<LudoBracketViewerPanelOffline>();

            ludoBracketViewerPanel.Initialize(this);
            return ludoBracketViewerPanel;
        }

        public void OpenBracketViewerPanel(string tournamentId, string tournamentName)
        {
            selectGameModePanal?.SetActive(false);
            backButton?.SetActive(false);
            ludoMatchNotification?.Suppress();
            if (ludoMyTournamentsPanel != null)
                ludoMyTournamentsPanel.ClosePanel();
            ResolveBracketViewerPanel().OpenPanel(tournamentId, tournamentName);
        }

        // ── Match Notification ────────────────────────────────────────────────

        private LudoTournamentMatchNotificationOffline ResolveMatchNotification()
        {
            if (ludoMatchNotification != null)
                return ludoMatchNotification;

            ludoMatchNotification = GetComponent<LudoTournamentMatchNotificationOffline>();
            if (ludoMatchNotification == null)
                ludoMatchNotification = gameObject.AddComponent<LudoTournamentMatchNotificationOffline>();

            ludoMatchNotification.Initialize(this);
            return ludoMatchNotification;
        }

        private void EnsureTournamentClassicTab()
        {
            if (player2 == null || player4 == null || player4.gameObject == null)
            {
                return;
            }

            if (!hasCachedTabPositions)
            {
                cachedPlayer2Position = player2.anchoredPosition;
                cachedPlayer4Position = player4.anchoredPosition;
                cachedPlayer2Size = player2.sizeDelta;
                cachedPlayer4Size = player4.sizeDelta;
                hasCachedTabPositions = true;
            }

            if (tournamentTab == null)
            {
                BindOrCreateTournamentClassicTab();
            }

            float centerX  = (cachedPlayer2Position.x + cachedPlayer4Position.x) * 0.5f;
            float tabWidth  = Mathf.Min(cachedPlayer2Size.x, cachedPlayer4Size.x) * 0.82f;
            float tabHeight = Mathf.Min(cachedPlayer2Size.y, cachedPlayer4Size.y) * 1.35f;
            float gap  = 22f;
            float step = tabWidth + gap;

            // Cache custom size so ResetButton/ClickOnPlayerButton can reuse it
            _tabCustomSize      = new Vector2(tabWidth, tabHeight);
            _tabCustomSizeReady = true;

            // Side tabs — use same height so row is visually even
            player2.sizeDelta = _tabCustomSize;
            player4.sizeDelta = _tabCustomSize;
            player2.anchoredPosition = new Vector2(centerX - step, cachedPlayer2Position.y);
            player4.anchoredPosition = new Vector2(centerX + step, cachedPlayer4Position.y);

            // Center TOURNAMENT tab — 8% taller for visual prominence
            if (tournamentTab != null)
            {
                tournamentTab.gameObject.SetActive(true);
                tournamentTab.SetAsLastSibling();
                tournamentTab.sizeDelta        = new Vector2(tabWidth, tabHeight * 1.08f);
                tournamentTab.anchoredPosition = new Vector2(centerX, cachedPlayer2Position.y);
            }
            UpdateClassicModeTabSelection(4);
        }

        public void SetLobbyUiBlocking(bool enabled)
        {
            GraphicRaycaster homeRaycaster = GetComponent<GraphicRaycaster>();
            if (homeRaycaster != null)
            {
                homeRaycaster.enabled = enabled;
            }
        }

        private void BindOrCreateTournamentClassicTab()
        {
            Transform parent = player4 != null ? player4.parent : null;
            Transform existingTab = parent != null ? parent.Find("TournamentTab") : null;

            if (existingTab != null)
            {
                BindTournamentClassicTab(existingTab.gameObject);
                return;
            }

            GameObject sceneTab = FindSceneObjectByName("TournamentTab");
            if (sceneTab != null)
            {
                BindTournamentClassicTab(sceneTab);
                return;
            }

            GameObject clone = Object.Instantiate(player4.gameObject, player4.parent);
            clone.name = "TournamentTab";
            BindTournamentClassicTab(clone);

            ReplaceTabText(clone.transform, "4 PLAYER", "TOURNAMENT");
            ReplaceTabText(clone.transform, "4 Player", "Tournament");
            ReplaceTabText(clone.transform, "4PLAYER", "TOURNAMENT");
            ReplaceTabText(clone.transform, "PLAYER", "TOURNAMENT");
        }

        private void BindTournamentClassicTab(GameObject tabObject)
        {
            if (tabObject == null)
            {
                return;
            }

            tournamentTab = tabObject.GetComponent<RectTransform>();
            tournamentTabImage = tabObject.GetComponent<Image>();
            tournamentTabButton = tabObject.GetComponentInChildren<Button>(true);

            if (tournamentTabButton == null)
            {
                tournamentTabButton = tabObject.GetComponent<Button>();
            }

            if (tournamentTabButton != null)
            {
                tournamentTabButton.onClick.RemoveAllListeners();
                tournamentTabButton.onClick.AddListener(OpenTournamentPanel);
            }
        }

        private void HideTournamentClassicTab()
        {
            if (hasCachedTabPositions)
            {
                player2.anchoredPosition = cachedPlayer2Position;
                player4.anchoredPosition = cachedPlayer4Position;
                player2.sizeDelta = cachedPlayer2Size;
                player4.sizeDelta = cachedPlayer4Size;
            }

            if (tournamentTab != null)
            {
                tournamentTab.gameObject.SetActive(false);
            }

            UpdateClassicModeTabSelection(4);
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

        private void ReplaceTabText(Transform root, string source, string target)
        {
            Text[] labels = root.GetComponentsInChildren<Text>(true);
            for (int i = 0; i < labels.Length; i++)
            {
                if (!string.IsNullOrWhiteSpace(labels[i].text) && labels[i].text.Contains(source))
                {
                    labels[i].text = labels[i].text.Replace(source, target);
                }
            }

            TextMeshProUGUI[] tmpLabels = root.GetComponentsInChildren<TextMeshProUGUI>(true);
            for (int i = 0; i < tmpLabels.Length; i++)
            {
                if (!string.IsNullOrWhiteSpace(tmpLabels[i].text) && tmpLabels[i].text.Contains(source))
                {
                    tmpLabels[i].text = tmpLabels[i].text.Replace(source, target);
                }
            }
        }

        private void UpdateClassicModeTabSelection(int selectedMode)
        {
            // Active:   warm gold tint on sprite + dark maroon text — clear "selected" look
            // Inactive: deep maroon tint on sprite + gold text — matches app red theme, fully readable
            Color activeTabColor    = new Color(1f,    0.88f, 0.30f, 1f);  // warm gold
            Color inactiveTabColor  = new Color(0.52f, 0.07f, 0.12f, 1f); // deep maroon
            Color activeTextColor   = new Color(0.28f, 0.04f, 0.06f, 1f); // dark maroon on gold
            Color inactiveTextColor = new Color(1f,    0.88f, 0.45f, 1f); // gold on dark red

            if (player2Button != null)
            {
                bool active = selectedMode == 2;
                player2Button.sprite = active ? selectSprite : unSelectSprite;
                player2Button.color  = active ? activeTabColor : inactiveTabColor;
                ApplyTabTextColor(player2Button.transform, active ? activeTextColor : inactiveTextColor);
            }

            if (player4Button != null)
            {
                bool active = selectedMode == 4;
                player4Button.sprite = active ? selectSprite : unSelectSprite;
                player4Button.color  = active ? activeTabColor : inactiveTabColor;
                ApplyTabTextColor(player4Button.transform, active ? activeTextColor : inactiveTextColor);
            }

            if (tournamentTabImage != null)
            {
                bool active = selectedMode == 0;
                tournamentTabImage.sprite = active ? selectSprite : unSelectSprite;
                tournamentTabImage.color  = active ? activeTabColor : inactiveTabColor;
                ApplyTabTextColor(tournamentTabImage.transform, active ? activeTextColor : inactiveTextColor);
            }
        }

        private void ApplyTabTextColor(Transform root, Color color)
        {
            foreach (Text t in root.GetComponentsInChildren<Text>(true))
                t.color = color;
            foreach (TextMeshProUGUI t in root.GetComponentsInChildren<TextMeshProUGUI>(true))
                t.color = color;
        }

        private void HideTournamentSideMenu()
        {
            RestoreTournamentSideMenu();

            string[] targetNames =
            {
                "SettingBtn",
                "SettingBtnImage",
                "ludo-menu-iconns",
                "Setting",
                "SettingPanel",
                "OptionContent",
                "OnlineLobbySelectionPanel",
                "OptionHolder2Player",
                "OptionHolder4Player",
                "OptionHolderMultiPlayer",
                "Lobby-Option",
            };

            for (int i = 0; i < targetNames.Length; i++)
            {
                Transform target = FindSceneTransformByName(targetNames[i]);
                if (target != null && target.gameObject.activeSelf)
                {
                    hiddenTournamentMenuObjects.Add(target.gameObject);
                    target.gameObject.SetActive(false);
                }
            }

            if (settinPanal != null && settinPanal.activeSelf)
            {
                hiddenTournamentMenuObjects.Add(settinPanal);
                settinPanal.SetActive(false);
            }
        }

        private void RestoreTournamentSideMenu()
        {
            for (int i = 0; i < hiddenTournamentMenuObjects.Count; i++)
            {
                GameObject target = hiddenTournamentMenuObjects[i];
                if (target != null)
                {
                    target.SetActive(true);
                }
            }

            hiddenTournamentMenuObjects.Clear();
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
                Transform candidate = transforms[i];
                if (candidate == null || !candidate.gameObject.scene.IsValid())
                {
                    continue;
                }

                if (string.Equals(candidate.name, objectName, StringComparison.OrdinalIgnoreCase))
                {
                    return candidate;
                }
            }

            return null;
        }

        public void CLickOnPassNPlayButton()
        {
            ShowPassNPlayPlayerCountPopup();
        }

        private void StartPassNPlayMatch(int playerCount)
        {
            selectedPassNPlayPlayerCount = Mathf.Clamp(playerCount, 2, 4);
            HidePassNPlayPlayerCountPopup();

            if (
                socketNumberEventReceiver != null
                && socketNumberEventReceiver.joinTableResponse != null
                && socketNumberEventReceiver.joinTableResponse.data != null
            )
            {
                socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount =
                    selectedPassNPlayPlayerCount;
            }

            backButton.SetActive(false);
            lobbySelectPanal.SetActive(false);
            string currentGameMode = GetCurrentLobbyGameModeName();

            if (
                string.Equals(currentGameMode, "CLASSIC", StringComparison.OrdinalIgnoreCase)
                && classicFTUE == "true"
            )
            {
                dashBordPanal.GetComponent<Canvas>().enabled = false;
                fTUEManager.SetActive(true);
                fTUEPanal.SetActive(true);
                FTUEManagerOffline.Instance.isFromPassNPlay = true;
            }
            else if (
                string.Equals(currentGameMode, "NUMBER", StringComparison.OrdinalIgnoreCase)
                && numberFTUE == "true"
            )
            {
                dashBordPanal.GetComponent<Canvas>().enabled = false;
                fTUEManager.SetActive(true);
                fTUEPanal.SetActive(true);
                FTUEManagerOffline.Instance.isFromPassNPlay = true;
            }
            else if (
                string.Equals(currentGameMode, "DICE", StringComparison.OrdinalIgnoreCase)
                && diceFTUE == "true"
            )
            {
                dashBordPanal.GetComponent<Canvas>().enabled = false;
                fTUEManager.SetActive(true);
                fTUEPanal.SetActive(true);
                FTUEManagerOffline.Instance.isFromPassNPlay = true;
            }
            else
            {
                fTUEPanal.SetActive(false);
                fTUEManager.SetActive(false);
                IsPassAndPlay = true;
                dashBordPanal.SetActive(false);
                SetLobbyUiBlocking(false);
                ChangeLobbyId();
                //SocketConnectionOffline.CreateSocket();
                if (!string.Equals(currentGameMode, "CLASSIC", StringComparison.OrdinalIgnoreCase))
                {
                    SetCookiePosition();
                }
                socketNumberEventReceiver.PlayerJoinData();
                LudoRoomChatController debugChatController = GetComponent<LudoRoomChatController>();
                if (debugChatController == null)
                {
                    debugChatController = gameObject.AddComponent<LudoRoomChatController>();
                }
                debugChatController.EnableDebugPreview();
                ResolveFriendPanelController().SetRoomActionAvailability(true);
            }
        }

        private void ShowPassNPlayPlayerCountPopup()
        {
            EnsurePassNPlayPlayerCountPopup();
            if (passNPlayPlayerCountPopup != null)
            {
                AttachPassNPlayPopupToActiveCanvas();
                WirePassNPlayPlayerCountPopupButtons();
                passNPlayPlayerCountPopup.SetActive(true);
            }
        }

        private void HidePassNPlayPlayerCountPopup()
        {
            if (passNPlayPlayerCountPopup != null)
            {
                passNPlayPlayerCountPopup.SetActive(false);
            }
        }

        private string GetCurrentLobbyGameModeName()
        {
            try
            {
                string gameModeName = MGPSDK.MGPGameManager.instance
                    ?.sdkConfig
                    ?.data
                    ?.lobbyData
                    ?.gameModeName;

                return string.IsNullOrWhiteSpace(gameModeName) ? "CLASSIC" : gameModeName;
            }
            catch (Exception)
            {
                return "CLASSIC";
            }
        }

        private void EnsurePassNPlayPlayerCountPopup()
        {
            if (passNPlayPlayerCountPopup != null)
            {
                AttachPassNPlayPopupToActiveCanvas();
                WirePassNPlayPlayerCountPopupButtons();
                return;
            }

            if (TryBindExistingPassNPlayPlayerCountPopup())
            {
                AttachPassNPlayPopupToActiveCanvas();
                WirePassNPlayPlayerCountPopupButtons();
                return;
            }

            Font popupFont = Resources.GetBuiltinResource<Font>("LegacyRuntime.ttf");
            Canvas rootCanvas =
                dashBordPanal != null
                    ? dashBordPanal.GetComponentInParent<Canvas>(true)
                    : GetComponentInParent<Canvas>(true);
            if (rootCanvas == null)
            {
                return;
            }

            rootCanvas = rootCanvas.rootCanvas != null ? rootCanvas.rootCanvas : rootCanvas;

            passNPlayPlayerCountPopup = new GameObject(
                "PassNPlayPlayerCountPopup",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image),
                typeof(Canvas),
                typeof(GraphicRaycaster)
            );
            passNPlayPlayerCountPopup.transform.SetParent(rootCanvas.transform, false);
            passNPlayPlayerCountPopup.transform.SetAsLastSibling();

            RectTransform overlayRect = passNPlayPlayerCountPopup.GetComponent<RectTransform>();
            overlayRect.anchorMin = Vector2.zero;
            overlayRect.anchorMax = Vector2.one;
            overlayRect.offsetMin = Vector2.zero;
            overlayRect.offsetMax = Vector2.zero;

            Canvas overlayCanvas = passNPlayPlayerCountPopup.GetComponent<Canvas>();
            overlayCanvas.overrideSorting = true;
            overlayCanvas.sortingLayerID = rootCanvas.sortingLayerID;
            overlayCanvas.sortingOrder = 32760;

            Image overlayImage = passNPlayPlayerCountPopup.GetComponent<Image>();
            overlayImage.color = new Color32(8, 6, 10, 190);

            GameObject card = new GameObject(
                "Card",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image),
                typeof(Outline),
                typeof(VerticalLayoutGroup),
                typeof(ContentSizeFitter)
            );
            card.transform.SetParent(passNPlayPlayerCountPopup.transform, false);

            RectTransform cardRect = card.GetComponent<RectTransform>();
            cardRect.anchorMin = new Vector2(0.5f, 0.5f);
            cardRect.anchorMax = new Vector2(0.5f, 0.5f);
            cardRect.pivot = new Vector2(0.5f, 0.5f);
            cardRect.sizeDelta = new Vector2(1120f, 0f);

            Image cardImage = card.GetComponent<Image>();
            cardImage.color = new Color32(44, 10, 18, 245);
            Outline cardOutline = card.GetComponent<Outline>();
            cardOutline.effectColor = new Color32(255, 186, 92, 110);
            cardOutline.effectDistance = new Vector2(2f, -2f);

            VerticalLayoutGroup cardLayout = card.GetComponent<VerticalLayoutGroup>();
            cardLayout.padding = new RectOffset(60, 60, 132, 52);
            cardLayout.spacing = 34f;
            cardLayout.childAlignment = TextAnchor.UpperCenter;
            cardLayout.childControlHeight = false;
            cardLayout.childControlWidth = true;
            cardLayout.childForceExpandHeight = false;
            cardLayout.childForceExpandWidth = true;

            ContentSizeFitter fitter = card.GetComponent<ContentSizeFitter>();
            fitter.verticalFit = ContentSizeFitter.FitMode.PreferredSize;

            GameObject titleBar = new GameObject(
                "TitleBar",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image)
            );
            titleBar.transform.SetParent(card.transform, false);
            titleBar.transform.SetAsFirstSibling();
            RectTransform titleBarRect = titleBar.GetComponent<RectTransform>();
            titleBarRect.anchorMin = new Vector2(0f, 1f);
            titleBarRect.anchorMax = new Vector2(1f, 1f);
            titleBarRect.pivot = new Vector2(0.5f, 1f);
            titleBarRect.sizeDelta = new Vector2(0f, 88f);
            titleBarRect.anchoredPosition = Vector2.zero;
            titleBar.GetComponent<Image>().color = new Color32(118, 18, 28, 255);

            Text title = CreatePopupText(
                "Pass N Play",
                42,
                FontStyle.Bold,
                TextAnchor.MiddleLeft,
                popupFont,
                Color.white
            );
            title.transform.SetParent(titleBar.transform, false);
            RectTransform titleRect = title.GetComponent<RectTransform>();
            titleRect.anchorMin = new Vector2(0f, 0f);
            titleRect.anchorMax = new Vector2(1f, 1f);
            titleRect.offsetMin = new Vector2(28f, 0f);
            titleRect.offsetMax = new Vector2(-84f, 0f);

            GameObject closeObj = new GameObject(
                "CloseButton",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image),
                typeof(Button)
            );
            closeObj.transform.SetParent(titleBar.transform, false);
            RectTransform closeRect = closeObj.GetComponent<RectTransform>();
            closeRect.anchorMin = new Vector2(1f, 0.5f);
            closeRect.anchorMax = new Vector2(1f, 0.5f);
            closeRect.pivot = new Vector2(1f, 0.5f);
            closeRect.sizeDelta = new Vector2(56f, 56f);
            closeRect.anchoredPosition = new Vector2(-22f, 0f);
            Image closeImage = closeObj.GetComponent<Image>();
            closeImage.color = new Color32(165, 36, 47, 255);
            Button closeButton = closeObj.GetComponent<Button>();
            closeButton.onClick.AddListener(HidePassNPlayPlayerCountPopup);

            GameObject closeLabelObj = new GameObject(
                "CloseLabel",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Text)
            );
            closeLabelObj.transform.SetParent(closeObj.transform, false);
            Text closeLabel = closeLabelObj.GetComponent<Text>();
            closeLabel.font = popupFont;
            closeLabel.fontSize = 28;
            closeLabel.fontStyle = FontStyle.Bold;
            closeLabel.alignment = TextAnchor.MiddleCenter;
            closeLabel.color = Color.white;
            closeLabel.text = "X";
            RectTransform closeLabelRect = closeLabelObj.GetComponent<RectTransform>();
            closeLabelRect.anchorMin = Vector2.zero;
            closeLabelRect.anchorMax = Vector2.one;
            closeLabelRect.offsetMin = Vector2.zero;
            closeLabelRect.offsetMax = Vector2.zero;

            Text subtitle = CreatePopupText(
                "Select how many players will join this Pass N Play match.",
                38,
                FontStyle.Normal,
                TextAnchor.MiddleCenter,
                popupFont,
                new Color32(255, 244, 232, 255)
            );
            subtitle.transform.SetParent(card.transform, false);

            GameObject buttonRow = new GameObject(
                "ButtonRow",
                typeof(RectTransform),
                typeof(HorizontalLayoutGroup),
                typeof(LayoutElement)
            );
            buttonRow.transform.SetParent(card.transform, false);
            LayoutElement buttonRowLayoutElement = buttonRow.GetComponent<LayoutElement>();
            buttonRowLayoutElement.minHeight = 140f;

            HorizontalLayoutGroup buttonRowLayout = buttonRow.GetComponent<HorizontalLayoutGroup>();
            buttonRowLayout.spacing = 28f;
            buttonRowLayout.childAlignment = TextAnchor.MiddleCenter;
            buttonRowLayout.childControlWidth = true;
            buttonRowLayout.childControlHeight = false;
            buttonRowLayout.childForceExpandWidth = true;
            buttonRowLayout.childForceExpandHeight = false;

            CreatePopupChoiceButton(
                buttonRow.transform,
                "2 Players",
                new Color32(214, 136, 42, 255),
                popupFont,
                () => StartPassNPlayMatch(2)
            );
            CreatePopupChoiceButton(
                buttonRow.transform,
                "3 Players",
                new Color32(165, 36, 47, 255),
                popupFont,
                () => StartPassNPlayMatch(3)
            );
            CreatePopupChoiceButton(
                buttonRow.transform,
                "4 Players",
                new Color32(214, 136, 42, 255),
                popupFont,
                () => StartPassNPlayMatch(4)
            );

            Button cancelButton = CreatePopupChoiceButton(
                card.transform,
                "Cancel",
                new Color32(88, 88, 96, 255),
                popupFont,
                HidePassNPlayPlayerCountPopup
            );
            RectTransform cancelRect = cancelButton.GetComponent<RectTransform>();
            cancelRect.sizeDelta = new Vector2(360f, 84f);

            WirePassNPlayPlayerCountPopupButtons();
            passNPlayPlayerCountPopup.SetActive(false);
        }

        private void AttachPassNPlayPopupToActiveCanvas()
        {
            if (passNPlayPlayerCountPopup == null)
            {
                return;
            }

            Canvas rootCanvas = ResolvePassNPlayRootCanvas();
            if (rootCanvas != null && passNPlayPlayerCountPopup.transform.parent != rootCanvas.transform)
            {
                passNPlayPlayerCountPopup.transform.SetParent(rootCanvas.transform, false);
            }

            passNPlayPlayerCountPopup.transform.SetAsLastSibling();

            RectTransform overlayRect = passNPlayPlayerCountPopup.GetComponent<RectTransform>();
            if (overlayRect != null)
            {
                overlayRect.anchorMin = Vector2.zero;
                overlayRect.anchorMax = Vector2.one;
                overlayRect.offsetMin = Vector2.zero;
                overlayRect.offsetMax = Vector2.zero;
            }

            Canvas popupCanvas = passNPlayPlayerCountPopup.GetComponent<Canvas>();
            if (popupCanvas == null)
            {
                popupCanvas = passNPlayPlayerCountPopup.AddComponent<Canvas>();
            }
            popupCanvas.overrideSorting = true;
            if (rootCanvas != null)
            {
                popupCanvas.sortingLayerID = rootCanvas.sortingLayerID;
            }
            popupCanvas.sortingOrder = 32760;

            if (passNPlayPlayerCountPopup.GetComponent<GraphicRaycaster>() == null)
            {
                passNPlayPlayerCountPopup.AddComponent<GraphicRaycaster>();
            }
        }

        private Canvas ResolvePassNPlayRootCanvas()
        {
            Canvas rootCanvas =
                dashBordPanal != null
                    ? dashBordPanal.GetComponentInParent<Canvas>(true)
                    : GetComponentInParent<Canvas>(true);

            if (rootCanvas != null && rootCanvas.rootCanvas != null)
            {
                rootCanvas = rootCanvas.rootCanvas;
            }

            if (rootCanvas != null && rootCanvas.gameObject.activeInHierarchy)
            {
                return rootCanvas;
            }

            Canvas activeCanvas = FindObjectOfType<Canvas>();
            if (activeCanvas != null)
            {
                return activeCanvas.rootCanvas != null ? activeCanvas.rootCanvas : activeCanvas;
            }

            if (rootCanvas == null)
            {
                rootCanvas = GetComponentInParent<Canvas>(true);
            }

            if (rootCanvas == null)
            {
                rootCanvas = FindObjectOfType<Canvas>();
            }

            return rootCanvas;
        }

        private bool TryBindExistingPassNPlayPlayerCountPopup()
        {
            GameObject existingPopup = FindSceneObjectByName("PassNPlayPlayerCountPopup");
            if (existingPopup == null)
            {
                return false;
            }

            Button twoPlayerButton = FindChildButton(existingPopup.transform, "2PlayersButton");
            Button threePlayerButton = FindChildButton(existingPopup.transform, "3PlayersButton");
            Button fourPlayerButton = FindChildButton(existingPopup.transform, "4PlayersButton");
            Button cancelButton = FindChildButton(existingPopup.transform, "CancelButton");
            Button closeButton = FindChildButton(existingPopup.transform, "CloseButton");

            if (twoPlayerButton == null || threePlayerButton == null || fourPlayerButton == null)
            {
                return false;
            }

            passNPlayPlayerCountPopup = existingPopup;
            RectTransform overlayRect = passNPlayPlayerCountPopup.GetComponent<RectTransform>();
            if (overlayRect != null)
            {
                overlayRect.anchorMin = Vector2.zero;
                overlayRect.anchorMax = Vector2.one;
                overlayRect.offsetMin = Vector2.zero;
                overlayRect.offsetMax = Vector2.zero;
            }

            Canvas popupCanvas = passNPlayPlayerCountPopup.GetComponent<Canvas>();
            if (popupCanvas != null)
            {
                popupCanvas.overrideSorting = true;
                popupCanvas.sortingOrder = 32760;
            }

            WirePassNPlayPlayerCountPopupButtons();
            if (cancelButton != null || closeButton != null)
            {
                passNPlayPlayerCountPopup.SetActive(false);
            }

            return true;
        }

        private void WirePassNPlayPlayerCountPopupButtons()
        {
            if (passNPlayPlayerCountPopup == null)
            {
                return;
            }

            Button twoPlayerButton = FindChildButton(passNPlayPlayerCountPopup.transform, "2PlayersButton");
            Button threePlayerButton = FindChildButton(passNPlayPlayerCountPopup.transform, "3PlayersButton");
            Button fourPlayerButton = FindChildButton(passNPlayPlayerCountPopup.transform, "4PlayersButton");
            Button cancelButton = FindChildButton(passNPlayPlayerCountPopup.transform, "CancelButton");
            Button closeButton = FindChildButton(passNPlayPlayerCountPopup.transform, "CloseButton");

            if (twoPlayerButton != null)
            {
                twoPlayerButton.onClick.RemoveAllListeners();
                twoPlayerButton.onClick.AddListener(() => StartPassNPlayMatch(2));
            }
            if (threePlayerButton != null)
            {
                threePlayerButton.onClick.RemoveAllListeners();
                threePlayerButton.onClick.AddListener(() => StartPassNPlayMatch(3));
            }
            if (fourPlayerButton != null)
            {
                fourPlayerButton.onClick.RemoveAllListeners();
                fourPlayerButton.onClick.AddListener(() => StartPassNPlayMatch(4));
            }
            if (cancelButton != null)
            {
                cancelButton.onClick.RemoveAllListeners();
                cancelButton.onClick.AddListener(HidePassNPlayPlayerCountPopup);
            }
            if (closeButton != null)
            {
                closeButton.onClick.RemoveAllListeners();
                closeButton.onClick.AddListener(HidePassNPlayPlayerCountPopup);
            }
        }

        private static Button FindChildButton(Transform root, string childName)
        {
            Transform child = FindChildTransform(root, childName);
            return child != null ? child.GetComponent<Button>() : null;
        }

        private static Transform FindChildTransform(Transform root, string childName)
        {
            if (root == null)
            {
                return null;
            }

            if (root.name == childName)
            {
                return root;
            }

            for (int i = 0; i < root.childCount; i++)
            {
                Transform found = FindChildTransform(root.GetChild(i), childName);
                if (found != null)
                {
                    return found;
                }
            }

            return null;
        }

        private Text CreatePopupText(
            string value,
            int fontSize,
            FontStyle style,
            TextAnchor alignment,
            Font font,
            Color color
        )
        {
            GameObject go = new GameObject(
                "Text",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Text),
                typeof(LayoutElement)
            );
            LayoutElement layout = go.GetComponent<LayoutElement>();
            layout.minHeight = fontSize + 18f;

            Text text = go.GetComponent<Text>();
            text.font = font;
            text.text = value;
            text.fontSize = fontSize;
            text.fontStyle = style;
            text.alignment = alignment;
            text.color = color;
            text.horizontalOverflow = HorizontalWrapMode.Wrap;
            text.verticalOverflow = VerticalWrapMode.Overflow;
            return text;
        }

        private Button CreatePopupChoiceButton(
            Transform parent,
            string label,
            Color buttonColor,
            Font font,
            Action onClick
        )
        {
            GameObject buttonObject = new GameObject(
                label.Replace(" ", string.Empty) + "Button",
                typeof(RectTransform),
                typeof(CanvasRenderer),
                typeof(Image),
                typeof(Button),
                typeof(LayoutElement)
            );
            buttonObject.transform.SetParent(parent, false);

            LayoutElement layout = buttonObject.GetComponent<LayoutElement>();
            layout.minWidth = 0f;
            layout.minHeight = 100f;
            layout.flexibleWidth = 1f;

            Image buttonImage = buttonObject.GetComponent<Image>();
            buttonImage.color = buttonColor;

            Button button = buttonObject.GetComponent<Button>();
            button.onClick.AddListener(() => onClick?.Invoke());

            Text labelText = CreatePopupText(
                label,
                42,
                FontStyle.Bold,
                TextAnchor.MiddleCenter,
                font,
                Color.white
            );
            labelText.transform.SetParent(buttonObject.transform, false);
            RectTransform labelRect = labelText.GetComponent<RectTransform>();
            labelRect.anchorMin = Vector2.zero;
            labelRect.anchorMax = Vector2.one;
            labelRect.offsetMin = Vector2.zero;
            labelRect.offsetMax = Vector2.zero;
            return button;
        }

        [Serializable]
        private class ClassicLobbyTableResponse
        {
            public string message;
            public List<ClassicLobbyTableData> table_data;
            public int code;
        }

        [Serializable]
        private class ClassicLobbyTableData
        {
            public string boot_value;
        }

        private void ChangeLobbyId()
        {
            var lobbyData = MGPSDK.MGPGameManager.instance
                ?.sdkConfig
                ?.data
                ?.lobbyData;

            if (lobbyData == null)
            {
                return;
            }

            string currentGameMode = GetCurrentLobbyGameModeName();
            if (string.Equals(currentGameMode, "CLASSIC", StringComparison.OrdinalIgnoreCase))
            {
                lobbyData._id = "6489c74c9573bc98a2ab5d13";
            }
            else if (string.Equals(currentGameMode, "DICE", StringComparison.OrdinalIgnoreCase))
            {
                lobbyData._id = "6489c76f9573bc98a2ab5dab";
            }
            else
            {
                lobbyData._id = "6489c8919573bc98a2ab60f9";
            }
        }

        void SetCookiePosition()
        {
            ludoNumberGsNew
                .ludoNumberPlayerControl[0]
                .ludoNumbersUserData
                .playerCoockie[0]
                .transform
                .localPosition = new Vector3(-20, 15, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[0]
                .ludoNumbersUserData
                .playerCoockie[1]
                .transform
                .localPosition = new Vector3(-7, 15, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[0]
                .ludoNumbersUserData
                .playerCoockie[2]
                .transform
                .localPosition = new Vector3(7, 15, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[0]
                .ludoNumbersUserData
                .playerCoockie[3]
                .transform
                .localPosition = new Vector3(20, 15, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[1]
                .ludoNumbersUserData
                .playerCoockie[0]
                .transform
                .localPosition = new Vector3(-363.3f, 502.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[1]
                .ludoNumbersUserData
                .playerCoockie[1]
                .transform
                .localPosition = new Vector3(-350.3f, 502.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[1]
                .ludoNumbersUserData
                .playerCoockie[2]
                .transform
                .localPosition = new Vector3(-336.3f, 502.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[1]
                .ludoNumbersUserData
                .playerCoockie[3]
                .transform
                .localPosition = new Vector3(-323.3f, 502.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[2]
                .ludoNumbersUserData
                .playerCoockie[0]
                .transform
                .localPosition = new Vector3(118.7f, 846.95f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[2]
                .ludoNumbersUserData
                .playerCoockie[1]
                .transform
                .localPosition = new Vector3(131.7f, 846.95f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[2]
                .ludoNumbersUserData
                .playerCoockie[2]
                .transform
                .localPosition = new Vector3(145.7f, 846.95f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[2]
                .ludoNumbersUserData
                .playerCoockie[3]
                .transform
                .localPosition = new Vector3(158.7f, 846.95f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[3]
                .ludoNumbersUserData
                .playerCoockie[0]
                .transform
                .localPosition = new Vector3(465.2f, 357.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[3]
                .ludoNumbersUserData
                .playerCoockie[1]
                .transform
                .localPosition = new Vector3(478.2f, 357.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[3]
                .ludoNumbersUserData
                .playerCoockie[2]
                .transform
                .localPosition = new Vector3(492.2f, 357.25f, 0);
            ludoNumberGsNew
                .ludoNumberPlayerControl[3]
                .ludoNumbersUserData
                .playerCoockie[3]
                .transform
                .localPosition = new Vector3(505.2f, 357.25f, 0);

            for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
            {
                for (
                    int j = 0;
                    j
                        < ludoNumberGsNew
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData
                            .playerCoockie
                            .Count;
                    j++
                )
                {
                    ludoNumberGsNew
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData
                        .playerCoockie[j]
                        .transform
                        .localScale = new Vector3(0.6f, 0.6f, 1);
                    //  ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovement>().myLastBoxIndex = 0;
                    // ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].transform.SetParent(cukiHolder);
                }
            }
        }

        #endregion

        #region FooterButton
        public void ClickOnStoreButton()
        {
            storPanal.SetActive(true);
            storPanal.transform.DOScale(Vector3.one, 0);
        }

        public void PurchasedComplete()
        {
            int chips = PlayerPrefs.GetInt("Totalchips");
            chips = chips + 50000;
            PlayerPrefs.SetInt("Totalchips", chips);
            UpdateChips(chips);
            storPanal.transform.DOScale(Vector3.zero, 0);
            PlayerPrefs.SetInt("removeAds", 1);
            specialOfferBtn.interactable = false;
            //ADManagerOffline.instance.DestroyAd();
        }

        public void PurchaseCoin(int coin)
        {
            int chips = PlayerPrefs.GetInt("Totalchips");
            chips = chips + coin;
            PlayerPrefs.SetInt("Totalchips", chips);
            UpdateChips(chips);
            storPanal.transform.DOScale(Vector3.zero, 0);
        }

        public void ClickOnStoreclosedButton()
        {
            storPanal.transform.DOScale(Vector3.zero, 0);
        }

        public void OpenRateUs()
        {
#if UNITY_ANDROID
            // Open the Google Play Store for Android
            Application.OpenURL("market://details?id=" + androidPackageName);
#elif UNITY_IOS
            // Open the App Store for iOS
            Application.OpenURL("itms-apps://itunes.apple.com/app/id" + iOSAppID);
#else
            Debug.LogWarning("Rating functionality not supported on this platform.");
#endif
        }

        public void ClickOnSettinButton()
        {
            if (!settinPanal.activeInHierarchy)
                settinPanal.SetActive(true);
            else
                settinPanal.SetActive(false);
        }
        #endregion

        #region Alert PopUp
        public void OpenAlertPopUp(string msg)
        {
            alertPopUpText.text = msg;
            alertPopUp.SetActive(true);
        }

        public void ClickOnAlertPopUpOkbutton()
        {
            if (isShow)
            {
#if UNITY_ANDROID
                //ADManagerOffline.instance.ShowRewardedAd();
#endif
            }
        }

        public void RewarderAddtimer(float f)
        {
            {
                try
                {
                    alertButton.SetActive(false);
                    alertText.SetActive(true);
                    timeAfterReward = f;
                    CancelInvoke(nameof(Timer));
                    InvokeRepeating(nameof(Timer), 0f, 1f);
                }
                catch (Exception ex)
                {
                    Debug.Log("Ex => " + ex.ToString());
                    throw;
                }
            }
        }

        void Timer()
        {
            if (timeAfterReward > -1)
            {
                timeText.text = "Next ad show after " + (int)timeAfterReward + " seconds";
                timeAfterReward--;
                isShow = false;
                Debug.Log("False");
            }
            else
            {
                Debug.Log("True");
                isShow = true;
                CancelInvoke(nameof(Timer));
                alertButton.SetActive(true);
                alertText.SetActive(false);
            }
        }

        public void ClickOnAlertButton()
        {
#if UNITY_EDITOR
            UnityEditor.EditorApplication.isPlaying = false;
#endif
#if UNITY_ANDROID
            Application.Quit();
#endif
        }

        #endregion

        #region ResetGame
        public void ResetGame()
        {
            CloseTournamentPanel();
            HideTournamentClassicTab();
            GetComponent<LudoRoomChatController>()?.SetChatAvailability(false);
            GetComponent<LudoRoomChatController>()?.ClearMessages();
            ResolveFriendPanelController().SetRoomActionAvailability(false);
            ResolveFriendPanelController().SetHomeShortcutAvailability(false);
            selectGameModePanal.SetActive(true);
            backButton.SetActive(false);
            lobbySelectPanal.SetActive(false);
            dashBordPanal.SetActive(true);
            dashBordPanal.GetComponent<Canvas>().enabled = true;
            SetLobbyUiBlocking(true);
        }
        #endregion
    }
}
