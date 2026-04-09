using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using DG.Tweening;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.Experimental.GlobalIllumination;
using UnityEngine.SocialPlatforms.Impl;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class CoockieMovementOffline : MonoBehaviour, IPointerDownHandler, IPointerClickHandler
    {
        #region VARIABLES

        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public LudoNumbersPlayerHomeOffline ludoNumbersPlayerHome;
        public LudoNumberGsNewOffline ludoNumberGsNew;
        public LudoNumberUiManagerOffline ludoNumberUiManager; //TODO
        public LudoNumbersAcknowledgementHandlerOffline ludoNumbersAcknowledgementHandler; //TODO
        public UserTimeStart userTimeStart;

        [Range(0f, 1000)]
        public float jumpPower;

        [Range(0f, 1000)]
        public int jumpnumber;

        [Range(0.1f, 1f)]
        public float moveTime;

        [Range(0.1f, 1f)]
        public float movedelay;
        public List<GameObject> playerCoockie;
        public GameManagerOffline gameManager;
        public int myLastBoxIndex = 0,
            tokenIndex; //TODO -1 in classic else 0
        public int cookieStaticIndex;
        public Color myColor;
        public Gradient colorOverSpeed;
        public Transform tokenAwayBGRight;
        public Text tokenAwayTextRight;
        public Text tokenAwayText;
        public Transform tokenAwayBG;
        internal bool isPopupShown;
        public GameObject tokenParent;
        public bool isRightPopup;
        public Coroutine movememtCoroutine;
        public Renderer playerRenderer;
        public int seatIndex;
        public bool isSafeMode;

        private const float TapCooldown = 0.08f;
        private const float MinTapTargetSize = 128f;
        private const float TapTargetPaddingMultiplier = 1.35f;

        private RectTransform _rectTransform;
        private Image _tokenImage;
        private float _lastTapTime = -10f;
        #endregion

        private void Awake()
        {
            _rectTransform = GetComponent<RectTransform>();
            _tokenImage = GetComponent<Image>();
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
            )
            {
                myLastBoxIndex = -1;
            }

            EnsureTapProxy();
        }

        void Start()
        {
            for (int i = 0; i < 0; i++)
            {
                this.myLastBoxIndex++;
                transform.SetParent(
                    ludoNumbersPlayerHome.way_Point[myLastBoxIndex].transform.GetChild(1)
                );
            }
        }

        #region ScoreView
        public void TokenScoreView()
        {
            gameManager.socketConnection.SendDataToSocket(
                gameManager.ludoNumberEventManager.Score(tokenIndex),
                ludoNumbersAcknowledgementHandler.ScoreViewAcknowledgement,
                LudoNumberEventList.SCORE_CHECK.ToString()
            );
        }

        public void ScoreView(ScoreViewRes scoreViewRes)
        {
            bool isActive = tokenAwayBG.gameObject.activeInHierarchy;
            ludoNumberGsNew.tokenToolTipsImageList.ForEach(
                (tokenTool) => tokenTool.gameObject.SetActive(false)
            );
            if (!isActive)
            {
                var seatIndex = scoreViewRes.data.seatIndex;
                var tokenIndex = scoreViewRes.data.tokenIndex;
                var score = scoreViewRes.data.score;
                var player = ludoNumberGsNew.ReturnPlayerFromSeatIndex(seatIndex);
                var home = player.PlayerHome;
                var isRight = false;
                switch (home.gameObject.name)
                {
                    case "1":
                        isRight = (score > 8 && score < 14);
                        break;
                    case "2":
                        isRight = (score > 47 && score < 52 || score == 0);
                        break;
                    case "3":
                        isRight = (score > 34 && score < 40);
                        break;
                    case "4":
                        isRight = (score > 21 && score < 27);
                        break;
                }
                ShowAwayPopup(score, isRight);
            }
            else
            {
                HideAwayPopup(isRightPopup);
            }
        }
        #endregion

        #region ShowAwayPopup
        internal void ShowAwayPopup(int awayPoint, bool isRight)
        {
            Debug.Log("Showed Away Popup Of Token ----> " + awayPoint);
            isRightPopup = isRight;
            if (isRight)
            {
                tokenAwayBGRight.gameObject.SetActive(true);
                tokenAwayBGRight.transform.localScale = Vector3.zero;
                tokenAwayTextRight.text = awayPoint.ToString();
                tokenAwayBGRight.DOScale(Vector3.one, 0.3f).SetEase(Ease.OutBack);
            }
            else
            {
                tokenAwayBG.gameObject.SetActive(true);
                tokenAwayBG.transform.localScale = Vector3.zero;
                tokenAwayText.text = awayPoint.ToString();
                tokenAwayBG.DOScale(Vector3.one, 0.3f).SetEase(Ease.OutBack);
            }
        }
        #endregion

        #region HideAwayPopup
        internal void HideAwayPopup(bool isRight)
        {
            if (isRight)
            {
                tokenAwayBGRight
                    .DOScale(Vector3.zero, 0.2f)
                    .SetEase(Ease.OutBack)
                    .OnComplete(() =>
                    {
                        tokenAwayBGRight.gameObject.SetActive(false);
                        isPopupShown = false;
                    });
            }
            else
            {
                tokenAwayBG
                    .DOScale(Vector3.zero, 0.2f)
                    .SetEase(Ease.OutBack)
                    .OnComplete(() =>
                    {
                        tokenAwayBG.gameObject.SetActive(false);
                        isPopupShown = false;
                    });
            }
        }
        #endregion

        public void OnPointerDown(PointerEventData eventData)
        {
            TryHandleTap();
        }

        public void OnPointerClick(PointerEventData eventData)
        {
            TryHandleTap();
        }

        public bool IsTapEnabled
        {
            get
            {
                return isActiveAndEnabled
                    && gameObject.activeInHierarchy
                    && _tokenImage != null
                    && _tokenImage.raycastTarget
                    && ludoNumberGsNew != null
                    && !ludoNumberGsNew.tokenMovement;
            }
        }

        public bool TryHandleTap()
        {
            if (!IsTapEnabled)
                return false;

            if (Time.unscaledTime - _lastTapTime < TapCooldown)
                return true;

            _lastTapTime = Time.unscaledTime;
            _tokenImage.raycastTarget = false;

            transform.DOKill();
            transform.localScale = Vector3.one;
            transform.DOPunchScale(new Vector3(0.12f, 0.12f, 0f), 0.14f, 4, 0.4f);
            MoveToken();
            return true;
        }

        public void MoveToken()
        {
            Debug.Log("<==Move Token==>");
            if (ludoNumberGsNew != null)
                ludoNumberGsNew.tokenMovement = true;
            socketNumberEventReceiver.moveToken.data.movementValue =
                socketNumberEventReceiver.diceValue;
            socketNumberEventReceiver.moveToken.data.tokenMove = cookieStaticIndex;
            ludoNumberGsNew.TokenMove();
            // gameManager.socketConnection.SendDataToSocket(gameManager.ludoNumberEventManager.MoveTokenCoockie(cookieStaticIndex),
            //     AcknowledgementTokenMove, LudoNumberEventList.MOVE_TOKEN.ToString());
        }

        private void EnsureTapProxy()
        {
            if (_rectTransform == null)
                return;

            Transform proxyTransform = transform.Find("TapProxy");
            if (proxyTransform == null)
            {
                GameObject proxy = new GameObject(
                    "TapProxy",
                    typeof(RectTransform),
                    typeof(CanvasRenderer),
                    typeof(Image),
                    typeof(LudoTokenTapProxyOffline)
                );
                proxyTransform = proxy.transform;
                proxyTransform.SetParent(transform, false);
                proxyTransform.SetAsLastSibling();
            }

            RectTransform proxyRect = (RectTransform)proxyTransform;
            proxyRect.anchorMin = new Vector2(0.5f, 0.5f);
            proxyRect.anchorMax = new Vector2(0.5f, 0.5f);
            proxyRect.pivot = new Vector2(0.5f, 0.5f);
            proxyRect.anchoredPosition = Vector2.zero;
            proxyRect.sizeDelta = new Vector2(
                Mathf.Max(_rectTransform.rect.width * TapTargetPaddingMultiplier, MinTapTargetSize),
                Mathf.Max(_rectTransform.rect.height * TapTargetPaddingMultiplier, MinTapTargetSize)
            );
            proxyRect.localScale = Vector3.one;

            Image proxyImage = proxyTransform.GetComponent<Image>();
            proxyImage.color = new Color(1f, 1f, 1f, 0.001f);
            proxyImage.raycastTarget = false;

            LudoTokenTapProxyOffline proxyComponent =
                proxyTransform.GetComponent<LudoTokenTapProxyOffline>();
            proxyComponent.Initialize(this, proxyImage);
        }

        public void AcknowledgementTokenMove(string data) =>
            Debug.Log("AcknowledgementTokenMove || => " + data);

        public void StopMovement()
        {
            if (movememtCoroutine != null)
            {
                transform.DOKill();
                StopCoroutine(movememtCoroutine);
            }
        }

        public void CoockieMove(int movementValue)
        {
            try
            {
                if (movememtCoroutine != null)
                    StopCoroutine(movememtCoroutine);
                movememtCoroutine = StartCoroutine(Movement(movementValue));
            }
            catch (System.Exception ex)
            {
                Debug.Log("Ex => " + ex.ToString());
                throw;
            }
        }

        IEnumerator Movement(int movementValue)
        {
            Debug.Log("TOKENMOVE");
            ludoNumberGsNew.tokenMovement = true;
            playerCoockie.ForEach(
                (coockie) => coockie.transform.GetChild(0).gameObject.SetActive(false)
            );

            // Dynamic timing: total move ~1.0s regardless of dice value, feels like Ludo King
            float perStepTime = Mathf.Clamp(1.0f / movementValue, 0.13f, 0.30f);

            for (int i = 0; i < movementValue; i++)
            {
                this.myLastBoxIndex++;
                bool isLastStep = (i == movementValue - 1);

                transform.SetParent(
                    ludoNumbersPlayerHome.way_Point[myLastBoxIndex].transform.GetChild(1)
                );

                // Scale up during flight with natural ease, land with bounce on final step
                transform.DOKill();
                transform
                    .DOScale(new Vector3(1.25f, 1.25f, 1.25f), perStepTime * 0.35f)
                    .SetEase(Ease.OutQuad)
                    .OnComplete(() =>
                    {
                        if (transform == null) return;
                        transform
                            .DOScale(Vector3.one, perStepTime * 0.65f)
                            .SetEase(isLastStep ? Ease.OutBounce : Ease.OutQuad);
                    });

                _rectTransform
                    .DOJumpAnchorPos(new Vector3(0, 0, 30), jumpPower, jumpnumber, perStepTime)
                    .OnComplete(() =>
                    {
                        SoundManagerOffline.instance.TimeSoundStop(
                            SoundManagerOffline.instance.timerAudio
                        );
                        ludoNumbersPlayerHome
                            .way_Point[myLastBoxIndex]
                            .GetComponent<LudoNumbersBoxPropertyOffline>()
                            .UpdateMyColor(myColor);
                        SoundManagerOffline.instance.SoundPlay(
                            SoundManagerOffline.instance.tokenMoveAudio
                        );
                        DoFade(
                            ludoNumbersPlayerHome
                                .way_Point[myLastBoxIndex]
                                .GetComponent<LudoNumbersBoxPropertyOffline>()
                                .trail
                        );
                        CoockieManage();
                    });

                yield return new WaitForSeconds(perStepTime);
            }
            if (
                ludoNumbersPlayerHome
                    .way_Point[myLastBoxIndex]
                    .GetComponent<LudoNumbersBoxPropertyOffline>()
                    .boxType == BoxType.Star
            )
            {
                SoundManagerOffline.instance.SoundPlay(
                    SoundManagerOffline.instance.tokenEnterSafeZoneAudio
                );
            }

            if (myLastBoxIndex == 56)
            {
                ludoNumberGsNew.isTokenReachHome = true;
                SoundManagerOffline.instance.SoundPlay(
                    SoundManagerOffline.instance.tokenEnterHomeAudio
                );
                gameObject.transform.GetComponent<Image>().raycastTarget = false;
                ludoNumberGsNew.homePartical.gameObject.SetActive(true);
                ParticleSystem.ColorBySpeedModule col = ludoNumberGsNew.homePartical.colorBySpeed;
                ParticleSystem.MinMaxGradient gr = col.color;
                col.color = Color.white;

                ParticleSystem.MainModule main = ludoNumberGsNew.homePartical.main;
                main.startColor = Color.white;

                playerRenderer.material.color = myColor;

                ludoNumberGsNew.homePartical.Play();

                // Celebratory punch scale when token reaches home
                transform.DOKill();
                transform.localScale = Vector3.one;
                transform.DOPunchScale(new Vector3(0.5f, 0.5f, 0), 0.5f, 8, 0.4f);
                //if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                //{
                //    ludoNumberGsNew.pointPanel.transform.DOScale(Vector3.one, 0.5f).OnComplete(() =>
                //    {
                //        ludoNumberGsNew.pointPanel.transform.DOScale(Vector3.zero, 0.5f);
                //    });
                //}
            }
            int sameColorCookieCount = 0;
            //if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
            //{
            List<CoockieMovementOffline> playerCoockies = new List<CoockieMovementOffline>();
            for (
                int i = 0;
                i
                    < ludoNumbersPlayerHome
                        .way_Point[myLastBoxIndex]
                        .transform.GetChild(1)
                        .childCount;
                i++
            )
                playerCoockies.Add(
                    ludoNumbersPlayerHome
                        .way_Point[myLastBoxIndex]
                        .transform.GetChild(1)
                        .GetChild(i)
                        .GetComponent<CoockieMovementOffline>()
                );

            var query = playerCoockies
                .GroupBy(x => x.myColor)
                .Where(g => g.Count() > 1)
                .Select(y => new { coockies = y.Key, counter = y.Count() })
                .ToList();
            for (int i = 0; i < query.Count; i++)
            {
                if (query[i].counter > 1)
                    sameColorCookieCount++;
            }

            if (sameColorCookieCount >= 1)
                SoundManagerOffline.instance.SoundPlay(
                    SoundManagerOffline.instance.tokenEnterSafeZoneAudio
                );
            //}
            isSafeMode = (
                ludoNumbersPlayerHome
                    .way_Point[myLastBoxIndex]
                    .GetComponent<LudoNumbersBoxPropertyOffline>()
                    .boxType == BoxType.Star
                || sameColorCookieCount >= 1
            );

            Debug.Log(" isSafeMode :-> " + isSafeMode);
            Debug.Log(
                " isSafeMode name :-> "
                    + ludoNumbersPlayerHome
                        .way_Point[myLastBoxIndex]
                        .GetComponent<LudoNumbersBoxPropertyOffline>()
                        .gameObject.name
            );
            CheckTokenKill();

            yield return new WaitForSeconds(0.5f);
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "NUMBER"
                )
            )
            {
                if (socketNumberEventReceiver.isMovesOver)
                {
                    Debug.Log("WINNER");
                    ludoNumberGsNew.CheckWinner();
                }
                else
                    ludoNumberGsNew.ChangeUserTurn();
            }
            else
            {
                if (ludoNumberGsNew.CheckFinishBattle())
                {
                    ludoNumberGsNew.winnerId = socketNumberEventReceiver
                        .userTurnStart
                        .data
                        .startTurnSeatIndex;
                    ludoNumberGsNew.BattleFinishUserData();
                }
                else
                    ludoNumberGsNew.ChangeUserTurn();
            }
        }

        void CheckTokenKill()
        {
            List<CoockieMovementOffline> playerTokenInBox = new List<CoockieMovementOffline>();
            for (
                int i = 0;
                i
                    < ludoNumbersPlayerHome
                        .way_Point[myLastBoxIndex]
                        .transform.GetChild(1)
                        .childCount;
                i++
            )
            {
                playerTokenInBox.Add(
                    ludoNumbersPlayerHome
                        .way_Point[myLastBoxIndex]
                        .transform.GetChild(1)
                        .GetChild(i)
                        .GetComponent<CoockieMovementOffline>()
                );
            }
            Debug.Log("CheckToKill playerTokenInBox count :-> " + playerTokenInBox.Count);
            Debug.Log(
                "User 0 Score Before => "
                    + socketNumberEventReceiver.moveToken.data.updatedScore[0].score
            );
            Debug.Log(
                "User 1 Score Before => "
                    + socketNumberEventReceiver.moveToken.data.updatedScore[1].score
            );
            if (playerTokenInBox.Count > 0)
            {
                for (int i = 0; i < playerTokenInBox.Count; i++)
                {
                    Debug.Log("is Safe Mode =>" + isSafeMode);
                    if (
                        socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                            != playerTokenInBox[i].seatIndex
                        && !isSafeMode
                    )
                    {
                        socketNumberEventReceiver.moveToken.data.isCapturedToken = true;
                        socketNumberEventReceiver.moveToken.data.capturedSeatIndex =
                            playerTokenInBox[i].seatIndex;
                        socketNumberEventReceiver.moveToken.data.capturedTokenIndex =
                            playerTokenInBox[i].cookieStaticIndex;
                        //if (socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex == 0)
                        //{
                        //    socketNumberEventReceiver.moveToken.data.updatedScore[0].score += 5;
                        //    socketNumberEventReceiver.moveToken.data.updatedScore[1].score -= playerTokenInBox[i].myLastBoxIndex;
                        //}
                        //else
                        //{
                        //    socketNumberEventReceiver.moveToken.data.updatedScore[1].score += 5;
                        //    socketNumberEventReceiver.moveToken.data.updatedScore[0].score -= playerTokenInBox[i].myLastBoxIndex;
                        //}

                        socketNumberEventReceiver
                            .moveToken
                            .data
                            .updatedScore[
                                socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                            ]
                            .score += 5;
                        socketNumberEventReceiver
                            .moveToken
                            .data
                            .updatedScore[playerTokenInBox[i].seatIndex]
                            .score -= playerTokenInBox[i].myLastBoxIndex;

                        Debug.Log(
                            "Kuki index value =>"
                                + playerTokenInBox[i].cookieStaticIndex
                                + "Radhe radhe"
                                + playerTokenInBox[i].myLastBoxIndex
                        );
                        Debug.Log(
                            "Kuki index value =>"
                                + playerTokenInBox[i].cookieStaticIndex
                                + "Radhe radhe"
                                + playerTokenInBox[i].seatIndex
                        );
                        Debug.Log(
                            "User 0 Score => "
                                + socketNumberEventReceiver.moveToken.data.updatedScore[0].score
                        );
                        Debug.Log(
                            "User 1 Score => "
                                + socketNumberEventReceiver.moveToken.data.updatedScore[1].score
                        );
                        for (int k = 0; k < ludoNumberGsNew.ludoNumberPlayerControl.Length; k++)
                        {
                            for (
                                int j = 0;
                                j < socketNumberEventReceiver.moveToken.data.updatedScore.Count;
                                j++
                            )
                            {
                                if (
                                    ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[k]
                                        .playerInfoData
                                        .playerSeatIndex
                                    == socketNumberEventReceiver
                                        .moveToken
                                        .data
                                        .updatedScore[j]
                                        .seatIndex
                                )
                                {
                                    ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[k]
                                        .ludoNumbersUserData
                                        .scoreText
                                        .text = socketNumberEventReceiver
                                        .moveToken.data.updatedScore[j]
                                        .score.ToString();
                                }
                            }
                        }

                        break;
                    }
                }
            }

            ludoNumberGsNew.CoockieKill();
        }

        public void CoockieManage()
        {
            for (int i = 0; i < ludoNumbersPlayerHome.way_Point.Count; i++)
            {
                try
                {
                    //   if (!socketNumberEventReceiver.moveToken.data.isCapturedToken)
                    ludoNumbersPlayerHome
                        .way_Point[i]
                        .GetComponent<LudoNumbersBoxPropertyOffline>()
                        .CockieManage();
                    //   else if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                    //       ludoNumbersPlayerHome.way_Point[i].GetComponent<LudoNumbersBoxProperty>().CockieManage();
                }
                catch (Exception ex)
                {
                    //  Debug.Log(ex);
                }
            }
        }

        void DoFade(Image transform) => transform.DOFade(0f, movedelay * 10);

        public void KillMove()
        {
            try
            {
                StartCoroutine(KillMovement());
            }
            catch (System.Exception ex)
            {
                Debug.LogError(ex.ToString());
            }
        }

        public IEnumerator KillMovement()
        {
            int killMove = myLastBoxIndex;
            playerCoockie.ForEach(
                (coockie) => coockie.transform.GetChild(0).gameObject.SetActive(false)
            );
            // Impact shake before returning to base — premium Ludo King-style kill feedback
            transform.DOKill();
            transform.DOPunchScale(new Vector3(0.6f, 0.6f, 0), 0.3f, 8, 0.5f);
            yield return new WaitForSeconds(0.25f);

            // Fast but visible kill-return animation (was 0.009s — nearly invisible)
            float killStepTime = Mathf.Clamp(0.6f / Mathf.Max(killMove, 1), 0.04f, 0.12f);
            for (int i = 0; i < killMove; i++)
            {
                myLastBoxIndex--;
                transform.SetParent(
                    ludoNumbersPlayerHome.way_Point[myLastBoxIndex].transform.GetChild(1)
                );
                _rectTransform
                    .DOJumpAnchorPos(new Vector3(0, 27, 0), jumpPower * 0.6f, 1, killStepTime)
                    .OnComplete(() =>
                    {
                        if (
                            !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                "CLASSIC"
                            )
                        )
                            CoockieManage();
                    });
                yield return new WaitForSeconds(killStepTime);
            }
            CoockieManage();
            if (
                myLastBoxIndex == 0
                && MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
            )
            {
                myLastBoxIndex = -1;
                transform.SetParent(tokenParent.transform);
                transform.GetComponent<RectTransform>().anchoredPosition = new Vector3(0, 30, 0);
                transform.localScale = new Vector2(1, 1);
            }
        }

        public void TokenMoveOnRejoin()
        {
            try
            {
                if (
                    myLastBoxIndex == -1
                    && MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "CLASSIC"
                    )
                )
                {
                    transform.SetParent(tokenParent.transform);
                    transform.GetComponent<RectTransform>().anchoredPosition = new Vector3(
                        0,
                        30,
                        0
                    );
                }
                else
                {
                    playerCoockie.ForEach(
                        (coockie) => coockie.transform.GetChild(0).gameObject.SetActive(false)
                    );
                    transform.SetParent(
                        ludoNumbersPlayerHome.way_Point[myLastBoxIndex].transform.GetChild(1)
                    );
                    _rectTransform
                        .DOJumpAnchorPos(new Vector3(0, 20, 0), jumpPower, jumpnumber, moveTime)
                        .OnComplete(() =>
                        {
                            for (int i = 0; i < ludoNumbersPlayerHome.way_Point.Count; i++)
                                ludoNumbersPlayerHome
                                    .way_Point[i]
                                    .GetComponent<LudoNumbersBoxPropertyOffline>()
                                    .CockieManage();
                            if (
                                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                    "NUMBER"
                                )
                            )
                            {
                                for (
                                    int i = 0;
                                    i
                                        < ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl
                                            .Length;
                                    i++
                                )
                                {
                                    if (
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .playerInfoData
                                            .playerSeatIndex
                                            == ludoNumbersAcknowledgementHandler
                                                .socketNumberEventReceiver
                                                .signUpResponce
                                                .data
                                                .userTurnDetails
                                                .currentTurnSeatIndex
                                        && ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .playerInfoData
                                            .playerSeatIndex
                                            == ludoNumberGsNew
                                                .socketNumberEventReceiver
                                                .signUpResponce
                                                .data
                                                .thisPlayerSeatIndex
                                    )
                                    {
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie.ForEach(
                                                (coockie) =>
                                                    coockie
                                                        .transform.GetComponent<Canvas>()
                                                        .sortingOrder += 5
                                            );
                                    }
                                }
                            }
                        });
                }
            }
            catch (System.Exception ex)
            {
                Debug.Log("Ex => " + ex.ToString());
            }
        }

        public void CoockieRing()
        {
            try
            {
                transform.GetChild(0).gameObject.SetActive(false);
                if (myLastBoxIndex == 56)
                    transform.GetComponent<Image>().raycastTarget = false;
                ludoNumberGsNew.tokenToolTipsImageList.ForEach(
                    (tokenTool) => tokenTool.gameObject.SetActive(false)
                );
            }
            catch (System.Exception ex)
            {
                Debug.Log("EX => " + ex.ToString());
                throw;
            }
        }

        public bool TokenRemainingBoxCount()
        {
            int indexDifference = 56 - myLastBoxIndex;
            if (indexDifference >= socketNumberEventReceiver.diceValue && indexDifference <= 56)
                return true;

            return false;
        }
    }
}
