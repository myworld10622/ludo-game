using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using DG.Tweening;
using Mkey;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

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

        private void Awake()
        {
            try
            {
                instance = this;

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
            baseUrl =
                MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.hostURL
                + ":"
                + MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.portNumber
                + "/ludogame/";

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

            // CheckUSBDebugging();
            //  dashBoardAPIRequestHandler.RunningGameAPI();
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
            SceneLoader.Instance.LoadScene("HomePage");
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
            }
            else
            {
                lobbySelectPanal.SetActive(true);
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

        public void UpdateProfilePic(int no)
        {
            try
            {
                for (int i = 0; i < avatar.players.Count; i++)
                {
                    if (no == i)
                        avatar.players[i].isActive = true;
                }

                string Json = JsonUtility.ToJson(avatar, true);

                PlayerPrefs.SetString("AvatarDetails", Json);
                //string filePath = Path.Combine(Application.dataPath + "/Resources", fileName);

                // The content you want to write to the file

                //// Create and write to the new text file
                //File.WriteAllText(filePath, Json);


                //Debug.Log("Text file created at: " + filePath);



                playerProfile.sprite = SpriteManager.Instance.profile_image;
                playerInfoProfile.sprite = SpriteManager.Instance.profile_image;
                gamePlayProfile.sprite = SpriteManager.Instance.profile_image;
                player2WinProfile.sprite = SpriteManager.Instance.profile_image;
                player4WinProfile.sprite = SpriteManager.Instance.profile_image;

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
                    //player2.DOAnchorPosY(newPostion, 0f);
                    player2.sizeDelta = maxSize;
                    player2Button.sprite = selectSprite;
                    socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = 2;
                    fourPlayerLobby.SetActive(false);
                    twoPlayerLobby.SetActive(true);
                    Debug.Log(socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount);
                    break;

                case 4:
                    //player4.DOAnchorPosY(newPostion, 0f);
                    player4.sizeDelta = maxSize;
                    player4Button.sprite = selectSprite;
                    socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount = 4;
                    fourPlayerLobby.SetActive(true);
                    twoPlayerLobby.SetActive(false);
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
            //player2.DOAnchorPosY(oldPostion, 0f);
            player2.sizeDelta = smallSize;
            player2Button.sprite = unSelectSprite;
            //player4.DOAnchorPosY(oldPostion, 0f);
            player4.sizeDelta = smallSize;
            player4Button.sprite = unSelectSprite;
        }

        public void ClickOnPLayButton(int value)
        {
            backButton.SetActive(false);
            lobbySelectPanal.SetActive(false);
            int chips = PlayerPrefs.GetInt("Totalchips");
            if (value <= chips)
            {
                if (
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "CLASSIC"
                    )
                    && classicFTUE == "true"
                )
                {
                    dashBordPanal.GetComponent<Canvas>().enabled = false;
                    fTUEManager.SetActive(true);
                    fTUEPanal.SetActive(true);
                    FTUEManagerOffline.Instance.value = value;
                }
                else if (
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                    && numberFTUE == "true"
                )
                {
                    dashBordPanal.GetComponent<Canvas>().enabled = false;
                    fTUEManager.SetActive(true);
                    fTUEPanal.SetActive(true);
                    FTUEManagerOffline.Instance.value = value;
                }
                else if (
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "DICE"
                    )
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

                    ChangeLobbyId();
                    //SocketConnectionOffline.CreateSocket();
                    if (
                        !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                            "CLASSIC"
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

        public void CLickOnPassNPlayButton()
        {
            backButton.SetActive(false);
            lobbySelectPanal.SetActive(false);
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
                && classicFTUE == "true"
            )
            {
                dashBordPanal.GetComponent<Canvas>().enabled = false;
                fTUEManager.SetActive(true);
                fTUEPanal.SetActive(true);
                FTUEManagerOffline.Instance.isFromPassNPlay = true;
            }
            else if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "NUMBER"
                )
                && numberFTUE == "true"
            )
            {
                dashBordPanal.GetComponent<Canvas>().enabled = false;
                fTUEManager.SetActive(true);
                fTUEPanal.SetActive(true);
                FTUEManagerOffline.Instance.isFromPassNPlay = true;
            }
            else if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("DICE")
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
                ChangeLobbyId();
                //SocketConnectionOffline.CreateSocket();
                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "CLASSIC"
                    )
                )
                {
                    SetCookiePosition();
                }
                socketNumberEventReceiver.PlayerJoinData();
            }
        }

        private void ChangeLobbyId()
        {
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
            )
            {
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData._id =
                    "6489c74c9573bc98a2ab5d13";
            }
            else if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("DICE")
            )
            {
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData._id =
                    "6489c76f9573bc98a2ab5dab";
            }
            else
            {
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData._id =
                    "6489c8919573bc98a2ab60f9";
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
            selectGameModePanal.SetActive(true);
            backButton.SetActive(false);
            lobbySelectPanal.SetActive(false);
            dashBordPanal.SetActive(true);
            dashBordPanal.GetComponent<Canvas>().enabled = true;
        }
        #endregion
    }
}
