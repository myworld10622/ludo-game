using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;
using static LudoClassicOffline.SocketConnectionOffline;

namespace LudoClassicOffline
{
    public class LudoNumberUiManagerOffline : MonoBehaviour
    {
        [Header("Script")]
        public SocketConnectionOffline socketConnection;
        public LudoNumbersAcknowledgementHandlerOffline ludoNumbersAcknowledgementHandler;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public LudoNumberGsNewOffline ludoNumberGsNew;
        public GameManagerOffline gameManager;
        public FTUEManagerOffline FTUEmanager;

        [Header("GameObject")]
        public GameObject SignUpPopup;
        public GameObject settingPanel;
        public GameObject leavePanel;
        public GameObject helpPanel;
        public GameObject startPanel;
        public GameObject FTUEReconnationPanel;
        public GameObject greenInfoPanel;
        public GameObject yellowInfoPanel;
        public GameObject blueInfoPanel;
        public GameObject redInfoPanel;
        public GameObject reconnationPanel;
        public GameObject timerCountScreen;
        public GameObject helpPanelContant;
        public GameObject bigViewPart; //TODO
        public GameObject smallViewPart; //TODO
        public GameObject emojiPanel;
        public Button emojiCloseBtn;

        [Header("Bool")]
        public bool isTieBreaker;
        public bool isFTUEWatch;
        internal bool isShowResult;

        [Header("Animator")]
        public Animator reconnationAnimator;

        [Header("int")]
        public int waitingTimer;
        public int FTUECount;

        [Header("Text")]
        public TextMeshProUGUI timerMessageText;

        [Header("List")]
        public List<Sprite> profilePicSpriteList;

        [Header("Coroutine")]
        public Coroutine coroutine;

        [Header("JSONObject")]
        //private JSONObject battleFinshData;

        [Header(("Dice Mode"))] //TODO
        public GameObject movePerFab;
        public Transform perFabParent,
            perFabParents;
        private List<Image> numberListObjects = new List<Image>();
        internal List<Image> viewMoreListObjects = new List<Image>();
        public Sprite normalImageForNumbersBG;
        public int totalTurnTaken;
        public RectTransform viewPort;
        public Sprite highLightImage;
        public GameObject moveLeft;

        public GameObject MoveLeftGameObject;
        public GameObject numberViewScreenGameObject;
        public GameObject timerGameObject;
        public List<GameObject> scoreGameObjectList;
        public List<GameObject> diesGameObjectList;
        public List<GameObject> smallBoxGameObjectList;
        public List<GameObject> moveShowGameObjectList;

        private void Start() => Screen.sleepTimeout = SleepTimeout.NeverSleep;

        public void StopReconntionAnimation()
        {
            reconnationPanel.SetActive(false);
            reconnationAnimator.enabled = false;
            FTUEReconnationPanel.SetActive(false);
        }

        public void ReconntionAnimation()
        {
            // reconnationPanel.SetActive(true);
            reconnationAnimator.enabled = true;
            //  FTUEReconnationPanel.SetActive(true);
        }

        public void GreenInfoBtn()
        {
            emojiPanel.transform.DOScale(Vector3.zero, 0f);
            emojiCloseBtn.gameObject.SetActive(false);
            ludoNumberGsNew.emojiBtn.gameObject.SetActive(true);
            greenInfoPanel.transform.DOScale(Vector2.one, 0f);
        }

        public void YellowInfoBtn() => yellowInfoPanel.transform.DOScale(Vector2.one, 0.5f);

        public void BlueInfoBtn() => blueInfoPanel.transform.DOScale(Vector2.one, 0.5f);

        public void RedInfoBtn() => redInfoPanel.transform.DOScale(Vector2.one, 0.5f);

        public void GreenCloseBtn() => greenInfoPanel.transform.DOScale(Vector2.zero, 0.5f);

        public void YellowCloseBtn() => yellowInfoPanel.transform.DOScale(Vector2.zero, 0.5f);

        public void BLueCloseBtn() => blueInfoPanel.transform.DOScale(Vector2.zero, 0.5f);

        public void RedCloseBtn() => redInfoPanel.transform.DOScale(Vector2.zero, 0.5f);

        public void BigToSmall()
        {
            bigViewPart.SetActive(false);
            smallViewPart.SetActive(true);
        }

        public void SmallToBig()
        {
            bigViewPart.SetActive(true);
            smallViewPart.SetActive(false);
        }

        public void SendSignUp()
        {
            reconnationAnimator.enabled = true;
            if (socketConnection.socketState == SocketState.Connect)
            {
                SignUpPopup.SetActive(false);
                //  reconnationPanel.gameObject.SetActive(true);
                Debug.Log("SignUp if Continue Button Click Done ");
                gameManager.Signup();
            }
        }

        public void EmojiOpen()
        {
            greenInfoPanel.transform.DOScale(Vector2.zero, 0f);
            emojiPanel.transform.DOScale(Vector3.one, 0f);
            emojiCloseBtn.gameObject.SetActive(true);
            ludoNumberGsNew.emojiBtn.gameObject.SetActive(false);
        }

        public void EmojiClose()
        {
            emojiPanel.transform.DOScale(Vector3.zero, 0f);
            emojiCloseBtn.gameObject.SetActive(false);
            ludoNumberGsNew.emojiBtn.gameObject.SetActive(true);
        }

        public void LeaveTable()
        {
            gameManager.LeaveTable();
            //             if (PlayerPrefs.GetInt("removeAds") == 1)
            //             {

            //             }
            //             else
            //             {
            // #if UNITY_ANDROID
            //                 ADManagerOffline.instance.DestroyAd();
            //                 ADManagerOffline.instance.interstialAdString = "back";
            //                 ADManagerOffline.instance.ShowInterstitialAd();
            // #endif
            //             }
        }

        public void HideOnServerExitPanel()
        {
            if (isShowResult)
            {
                BattleFinishAlert();
                isShowResult = false;
            }
            ludoNumberGsNew.alertPanel.SetActive(false);
            gameManager.OnClickExit();
        }

        internal void BattleFinishAlert()
        {
            // if (battleFinshData == null)
            //     return;
            ludoNumberGsNew.Battle();
        }

        public void Help()
        {
            helpPanel.GetComponent<Image>().enabled = true;
            helpPanelContant.transform.localPosition = Vector3.zero;
            helpPanel.SetActive(true);
            helpPanel.transform.GetChild(0).transform.DOScale(Vector3.one, 0.25f);
        }

        public void HelpCloseBtn()
        {
            helpPanel
                .transform.GetChild(0)
                .transform.DOScale(Vector3.zero, 0.25f)
                .OnComplete(() =>
                {
                    helpPanel.SetActive(false);
                    helpPanel.GetComponent<Image>().enabled = false;
                });
        }

        //public void SpriteLoder(Image winner, string url)
        //{
        //    Sprite profilePic = profilePicSpriteList.Find(profilePic => profilePic.name == url);
        //    if (profilePic != null)
        //        winner.sprite = profilePic;
        //    else
        //        StartCoroutine(GetTexture(winner, url));
        //}
        IEnumerator GetTexture(Image profile, string imageUrl)
        {
            UnityWebRequest req = UnityWebRequestTexture.GetTexture(imageUrl);
            yield return req.SendWebRequest();
            Texture2D tex = DownloadHandlerTexture.GetContent(req);
            Sprite mySprite = Sprite.Create(
                tex,
                new Rect(0.0f, 0.0f, tex.width, tex.height),
                new Vector2(0.5f, 0.5f),
                100.0f
            );
            profile.sprite = mySprite;
            mySprite.name = imageUrl;
            profilePicSpriteList.Add(mySprite);
        }

        public void ExitNoBtn()
        {
            settingPanel
                .transform.GetChild(0)
                .transform.DOScale(Vector3.zero, 0.25f)
                .OnComplete(() =>
                {
                    settingPanel.SetActive(false);
                    settingPanel.GetComponent<Image>().enabled = false;
                });
        }

        public void ExitYesBtn()
        {
            leavePanel.GetComponent<Image>().enabled = true;
            leavePanel.SetActive(true);

            leavePanel.transform.GetChild(0).transform.DOScale(Vector3.one, 0.25f);
            //});
        }

        public void LeaveNoBtn()
        {
            leavePanel
                .transform.GetChild(0)
                .transform.DOScale(Vector3.zero, 0.25f)
                .OnComplete(() =>
                {
                    settingPanel.transform.GetChild(0).transform.DOScale(Vector3.one, 0.25f);
                    leavePanel.SetActive(false);
                    leavePanel.GetComponent<Image>().enabled = false;
                });
        }

        public void CountDownStart(int waitingTime)
        {
            waitingTimer = waitingTime;
            if (waitingTimer <= 0)
            {
                if (coroutine != null)
                    StopCoroutine(coroutine);

                CancelInvoke(nameof(DecreaseCounter));
            }
            else
                InvokeRepeating(nameof(DecreaseCounter), 1, 1);
        }

        private void DecreaseCounter()
        {
            waitingTimer--;
            timerMessageText.text = waitingTimer.ToString();
            if (waitingTimer <= 0)
            {
                StopDecreaseCounter();
                coroutine = StartCoroutine(Time());
                return;
            }
        }

        IEnumerator Time()
        {
            yield return new WaitForSeconds(0.5f);
            if (waitingTimer == 0)
            {
                timerCountScreen
                    .transform.DOScale(Vector3.zero, 0.5f)
                    .OnComplete(() =>
                    {
                        startPanel
                            .transform.DOScale(Vector3.one, 1f)
                            .OnComplete(() =>
                            {
                                socketNumberEventReceiver.StartUserTurn();
                                if (
                                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                        "DICE"
                                    )
                                )
                                    ludoNumberGsNew.MainTimer(300);
                            });
                    });
            }
        }

        public void StopDecreaseCounter() => CancelInvoke(nameof(DecreaseCounter));

        public void QuitButton() => gameManager.OnClickExit();

        public void OnClickBackBtn()
        {
            settingPanel.GetComponent<Image>().enabled = true;
            settingPanel.SetActive(true);
            settingPanel.transform.GetChild(0).transform.DOScale(Vector3.one, 0.25f);
        }

        public void NumberGeneration(List<int> Numbers)
        {
            Debug.Log("IN setting numbers" + Numbers.Count);
            DestroyNumberObjects();
            for (var i = 0; i < Numbers.Count; i++)
            {
                var x = 10f + (i * 110f);
                var num = Numbers[i];

                #region Numbers

                var numberView = Instantiate(movePerFab, perFabParent.transform);
                numberView.name = i.ToString();
                numberView.transform.GetChild(0).gameObject.GetComponent<Text>().text =
                    num.ToString();
                numberView.GetComponent<Image>().sprite = normalImageForNumbersBG;
                numberListObjects.Add(numberView.GetComponent<Image>());
                #endregion

                #region Numbers View More

                var column = i % 8;
                var row = (int)(i / 8);

                var number = Instantiate(movePerFab, perFabParents.transform);
                number.name = i.ToString();
                number.transform.GetChild(0).gameObject.GetComponent<Text>().text = num.ToString();

                x = -380f + (column * 110f);
                var y = -60f + (row * -110f);
                number.GetComponent<Image>().sprite = normalImageForNumbersBG;
                viewMoreListObjects.Add(number.GetComponent<Image>());

                if (i < socketNumberEventReceiver.userTurnCount)
                {
                    viewMoreListObjects[i].DOFade(0.3f, 0f);
                    viewMoreListObjects[i]
                        .transform.GetChild(0)
                        .GetComponent<Text>()
                        .DOColor(Color.black, 0f);
                    viewMoreListObjects[i]
                        .transform.GetChild(0)
                        .GetComponent<Text>()
                        .DOFade(0.3f, 0.2f);

                    numberListObjects[i]
                        .transform.GetChild(0)
                        .GetComponent<Text>()
                        .DOColor(Color.black, 0f);
                }
                #endregion
            }

            #region View More Content Size and scrollbar

            // var height = (((int)((Numbers.Count - 1) / 8) + 1) * 110f) + 10f;
            // var content_RT = perFabParent.GetComponent<RectTransform>();
            // content_RT.sizeDelta = new Vector2(content_RT.sizeDelta.x, height);
            #endregion
        }

        public void DestroyNumberObjects()
        {
            for (int i = 0; i < numberListObjects.Count; i++)
            {
                Destroy(numberListObjects[i].gameObject);
            }

            for (int i = 0; i < viewMoreListObjects.Count; i++)
            {
                Destroy(viewMoreListObjects[i].gameObject);
            }

            numberListObjects.Clear();
            viewMoreListObjects.Clear();
        }

        public void NumberAnimation()
        {
            viewPort.DOAnchorPosX((socketNumberEventReceiver.userTurnCount + 1) * (-115f), 0.5f);
            DisableTakenMoveNumber(socketNumberEventReceiver.userTurnCount);
        }

        public void DisablePreviousNumbersWhenReconnect()
        {
            for (int i = 0; i < socketNumberEventReceiver.userTurnCount; i++)
            {
                viewMoreListObjects[i].sprite = normalImageForNumbersBG;
                viewMoreListObjects[i].DOFade(0.3f, 0.2f);
                viewMoreListObjects[i]
                    .transform.GetChild(0)
                    .GetComponent<Text>()
                    .DOColor(Color.black, 0f);
                viewMoreListObjects[i]
                    .transform.GetChild(0)
                    .GetComponent<Text>()
                    .DOFade(0.3f, 0.2f);

                numberListObjects[i].sprite = normalImageForNumbersBG;
                numberListObjects[i].DOFade(0.3f, 0.2f);
                numberListObjects[i]
                    .transform.GetChild(0)
                    .GetComponent<Text>()
                    .DOColor(Color.black, 0f);
                numberListObjects[i].transform.GetChild(0).GetComponent<Text>().DOFade(0.3f, 0.2f);
            }
        }

        public void HighlightCurrentMoveNumber()
        {
            Debug.Log("User Turn Count =>" + socketNumberEventReceiver.userTurnCount);
            numberListObjects[socketNumberEventReceiver.userTurnCount].sprite = highLightImage;

            if (socketNumberEventReceiver.userTurnCount > 0)
            {
                viewMoreListObjects[socketNumberEventReceiver.userTurnCount - 1].sprite =
                    normalImageForNumbersBG;
            }
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount].sprite = highLightImage;
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);

            numberListObjects[socketNumberEventReceiver.userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
        }

        public void NormalExtraMoveNumber()
        {
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount - 1].sprite =
                normalImageForNumbersBG;
            numberListObjects[socketNumberEventReceiver.userTurnCount - 1].sprite =
                normalImageForNumbersBG;

            viewMoreListObjects[socketNumberEventReceiver.userTurnCount - 1]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOFade(1f, 0f);
            numberListObjects[socketNumberEventReceiver.userTurnCount - 1]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOFade(1f, 0f);
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount - 1]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
            numberListObjects[socketNumberEventReceiver.userTurnCount - 1]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount - 1].DOFade(1f, 0.2f);
            numberListObjects[socketNumberEventReceiver.userTurnCount - 1].DOFade(1f, 0.2f);
        }

        public void NormalExtraMoveNumberReconnect()
        {
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount].sprite =
                normalImageForNumbersBG;
            numberListObjects[socketNumberEventReceiver.userTurnCount].sprite =
                normalImageForNumbersBG;

            viewMoreListObjects[socketNumberEventReceiver.userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOFade(1f, 0f);
            numberListObjects[socketNumberEventReceiver.userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOFade(1f, 0f);
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
            numberListObjects[socketNumberEventReceiver.userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
            viewMoreListObjects[socketNumberEventReceiver.userTurnCount].DOFade(1f, 0.2f);
            numberListObjects[socketNumberEventReceiver.userTurnCount].DOFade(1f, 0.2f);
        }

        public void DisableTakenMoveNumber(int userTurnCount)
        {
            Debug.Log("Disable taken move => " + userTurnCount);

            viewMoreListObjects[userTurnCount].sprite = normalImageForNumbersBG;
            viewMoreListObjects[userTurnCount].DOFade(0.3f, 0f);
            viewMoreListObjects[userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
            viewMoreListObjects[userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOFade(0.3f, 0f);

            numberListObjects[userTurnCount].sprite = normalImageForNumbersBG;
            numberListObjects[userTurnCount].DOFade(0.3f, 0f);
            numberListObjects[userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOColor(Color.black, 0f);
            numberListObjects[userTurnCount]
                .transform.GetChild(0)
                .GetComponent<Text>()
                .DOFade(0.3f, 1f);
        }

        public void DisableMyBox()
        {
            for (int i = 0; i < viewMoreListObjects.Count; i++)
            {
                viewMoreListObjects[i].sprite = normalImageForNumbersBG;
            }
            for (int i = 0; i < numberListObjects.Count; i++)
            {
                numberListObjects[i].sprite = normalImageForNumbersBG;
            }
        }

        public void ResetGame()
        {
            emojiPanel.SetActive(false);
            bigViewPart.SetActive(false);
            settingPanel.SetActive(false);
            leavePanel.SetActive(false);
            helpPanel.SetActive(false);
            redInfoPanel.SetActive(false);
            blueInfoPanel.SetActive(false);
            yellowInfoPanel.SetActive(false);
            greenInfoPanel.SetActive(false);
        }
    }
}
