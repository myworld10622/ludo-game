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
    public class CoockieMovementOffline : MonoBehaviour, IPointerDownHandler
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
        #endregion

        private void Awake()
        {
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
            )
            {
                myLastBoxIndex = -1;
            }
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
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
            )
            {
                MoveToken();
            }
            else
            {
                MoveToken();
                //if (ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex != ludoNumberGsNew.socketNumberEventReceiver.userStartIndex)
                //    gameManager.socketConnection.SendDataToSocket(gameManager.ludoNumberEventManager.Score(tokenIndex), ludoNumbersAcknowledgementHandler.ScoreViewAcknowledgement, LudoNumberEventList.SCORE_CHECK.ToString());
                //else
                //{
                //    gameManager.socketConnection.SendDataToSocket(gameManager.ludoNumberEventManager.MoveTokenCoockie(cookieStaticIndex), ludoNumbersAcknowledgementHandler.TokenMove, LudoNumberEventList.MOVE_TOKEN.ToString());
                //}
            }
        }

        public void MoveToken()
        {
            Debug.Log("<==Move Token==>");
            socketNumberEventReceiver.moveToken.data.movementValue =
                socketNumberEventReceiver.diceValue;
            socketNumberEventReceiver.moveToken.data.tokenMove = cookieStaticIndex;
            ludoNumberGsNew.TokenMove();
            // gameManager.socketConnection.SendDataToSocket(gameManager.ludoNumberEventManager.MoveTokenCoockie(cookieStaticIndex),
            //     AcknowledgementTokenMove, LudoNumberEventList.MOVE_TOKEN.ToString());
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

            for (int i = 0; i < movementValue; i++)
            {
                this.myLastBoxIndex++;
                transform.SetParent(
                    ludoNumbersPlayerHome.way_Point[myLastBoxIndex].transform.GetChild(1)
                );
                transform
                    .DOScale(new Vector3(1.4f, 1.4f, 1.4f), moveTime / 2)
                    .SetEase(Ease.Linear)
                    .OnComplete(() =>
                    {
                        if (transform.localScale.x >= 1)
                            transform
                                .DOScale(new Vector3(1f, 1f, 1f), moveTime / 2f)
                                .SetEase(Ease.Linear);
                    });
                //transform.GetComponent<RectTransform>().DOJumpAnchorPos(new Vector3(0, BoardRotateManagerOffline.isRotate ? 0 : 30, BoardRotateManagerOffline.isRotate ? 30 : 0), jumpPower, jumpnumber, moveTime).OnComplete(() =>
                transform
                    .GetComponent<RectTransform>()
                    .DOJumpAnchorPos(new Vector3(0, 0, 30), jumpPower, jumpnumber, moveTime)
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
                        Debug.Log("COMPLETE");
                        CoockieManage();
                    });
                yield return new WaitForSeconds(movedelay);
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
                // CoockieManage();
                gameObject.transform.GetComponent<Image>().raycastTarget = false;
                ludoNumberGsNew.homePartical.gameObject.SetActive(true);
                ParticleSystem.ColorBySpeedModule col = ludoNumberGsNew.homePartical.colorBySpeed;
                ParticleSystem.MinMaxGradient gr = col.color;
                col.color = Color.white;

                ParticleSystem.MainModule main = ludoNumberGsNew.homePartical.main;
                main.startColor = Color.white;

                playerRenderer.material.color = myColor;

                ludoNumberGsNew.homePartical.Play();
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
            for (int i = 0; i < killMove; i++)
            {
                myLastBoxIndex--;
                transform.SetParent(
                    ludoNumbersPlayerHome.way_Point[myLastBoxIndex].transform.GetChild(1)
                );
                transform
                    .GetComponent<RectTransform>()
                    .DOJumpAnchorPos(new Vector3(0, 27, 0), jumpPower, jumpnumber, moveTime)
                    .OnComplete(() =>
                    {
                        if (
                            !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                "CLASSIC"
                            )
                        )
                            CoockieManage();
                    });
                yield return new WaitForSeconds(0.009f);
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
                    transform
                        .GetComponent<RectTransform>()
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
