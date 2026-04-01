using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.Serialization;
using UnityEngine.UI;
using static LudoClassicOffline.SignUpResponceClass;
using Random = UnityEngine.Random;

namespace LudoClassicOffline
{
    public class LudoNumberGsNewOffline : MonoBehaviour
    {
        private readonly HashSet<int> eliminatedSeatIndices = new HashSet<int>();

        #region VARIABLES

        [Header("Script")]
        public LudoNumbersAcknowledgementHandlerOffline ludoNumbersAcknowledgementHandler;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public LudoNumberTostMessageOffline ludoNumberTostMessage;
        public LudoNumberUiManagerOffline ludoNumberUiManager;

        // public HeartBeatManagerOffline heartBeatManager;
        public LudoNumberWinnerDataOffline ludoNumberWinnerData;
        public EmojiAnimateOffline emojiAnimate;

        [FormerlySerializedAs("player_list")]
        [Header("List")]
        public List<LudoNumberPlayerControlOffline> playerList;

        public List<RectTransform> movePositon;
        public List<GameObject> tokenToolTipsImageList;
        public List<GameObject> tokenToolTipsImageListForClassicMode;

        [Header("Array")]
        public UserTimerOffline[] userTimer;
        public LudoNumberPlayerControlOffline[] ludoNumberPlayerControl;
        public ExtraTime extraTime;

        [SerializeField]
        private Button[] serverCommonPopupButtons;

        [SerializeField]
        private Text[] serverCommonPopupButtonsText;

        [FormerlySerializedAs("WinPanel")]
        [Header("GameObject")]
        public GameObject winPanel;

        public GameObject board;

        public GameObject extraMove;
        public GameObject pointPanel; // TODO
        public GameObject lastMove;
        public GameObject waitPanel;
        public GameObject alertPanel;
        public GameObject youWin;
        public GameObject youLose;
        public GameObject gameTie;
        public GameObject logoImage;
        public GameObject networkIndicator;
        public GameObject serverTopToast;
        public GameObject serverCenterToast;
        public GameObject serverCommonPopup;
        public GameObject serverCommonPopupContent;
        public GameObject serverCommonPopupLoader;

        [FormerlySerializedAs("tieBreakerBGNumberMode")]
        public GameObject tieBreakerBgNumberMode;

        public GameObject plusPoint; //TODO

        [FormerlySerializedAs("TokenKill")]
        public GameObject tokenKill;

        [SerializeField]
        private GameObject extraScore;

        [SerializeField]
        private GameObject tokenHome;
        public GameObject timerFor30SecLeft; //TODO
        public GameObject whenTimeFinishEndPlayerLastMove; //TODO
        public GameObject firstSix;
        public GameObject secondSix;
        public GameObject lastSix;

        [Header("Int")]
        public int moveNumber;
        public float configTime;

        [Header("Text")]
        public Text alertText;
        public Text lableText;
        public Text serverTopToastText;
        public Text serverCenterToastText;
        public Text serverCommonPopupTitle;
        public Text serverCommonPopupMessage;
        public Text extraScoreText;
        public TextMeshProUGUI moveText;
        public Text timerText;

        public GameObject TokenKill; //TODO

        [Header("Sprite")]
        [FormerlySerializedAs("GreenBtn")]
        [SerializeField]
        private Sprite greenBtn;

        [SerializeField]
        private Sprite RedBtn;
        public Sprite diceValue;

        [Header("String")]
        public string alert;
        public string userId;

        [Header("Button")]
        public Button settingBtn;

        [Header("Partical")]
        public ParticleSystem killPratical;
        public ParticleSystem homePartical;
        public ParticleSystem winningPartical;

        public bool isAnimation;
        public Image emojiBtn;
        public bool tokenMovement;
        public bool isTokenReachHome;
        public int winnerId;
        public bool isCukiKillNumberMode;
        public bool isTie;
        public BattleFinishData battleFinishData;

        public List<int> list;

        #endregion

        #region GAMETIMERSTART

        public void GameTimerStart(int waitingTimer)
        {
            // ludoNumbersAcknowledgementHandler.yesBtn.GetComponent<Image>().raycastTarget = false;
            //  ludoNumbersAcknowledgementHandler.noBtn.GetComponent<Image>().raycastTarget = false;
            ludoNumberUiManager.gameManager.gameState = GameState.run;
            ludoNumberUiManager
                .settingPanel.transform.GetChild(0)
                .transform.DOScale(Vector3.zero, 0.25f)
                .OnComplete(() =>
                {
                    ludoNumberUiManager.settingPanel.SetActive(false);
                    ludoNumberUiManager.settingPanel.GetComponent<Image>().enabled = false;
                });
            ludoNumberUiManager
                .leavePanel.transform.GetChild(0)
                .transform.DOScale(Vector3.zero, 0.25f)
                .OnComplete(() =>
                {
                    ludoNumberUiManager.leavePanel.SetActive(false);
                    ludoNumberUiManager.leavePanel.GetComponent<Image>().enabled = false;
                });
            settingBtn.interactable = false;
            ludoNumberTostMessage.HideWaitForPlayerToast(true);
            ludoNumberUiManager.timerCountScreen.SetActive(true);
            ludoNumberUiManager.CountDownStart(waitingTimer);
        }

        public void MainTimer(double configMainTime)
        {
            timerText.gameObject.SetActive(true);
            configTime = (float)configMainTime;
            InvokeRepeating(nameof(GameMainTimer), 0.1f, 1f);
        }

        public void CancleMainTimer() => CancelInvoke(nameof(GameMainTimer));

        public void GameMainTimer()
        {
            configTime -= 1;
            float minutes = Mathf.FloorToInt(configTime / 60);
            float second = Mathf.FloorToInt(configTime - minutes * 60f);
            string textTime = string.Format("{00:00}:{01:00}", minutes, second);
            if (minutes >= 0)
                timerText.text = textTime;

            if (configTime <= 0)
            {
                CheckWinner();
                CancelInvoke(nameof(GameMainTimer));
            }
        }

        #endregion

        #region TOKENMOVE

        public void TokenMove()
        {
            UpdateUserScore();

            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "CLASSIC"
                    )
                )
                {
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockie.ForEach(
                            (coockie) =>
                                coockie.transform.GetComponent<Image>().raycastTarget = false
                        );
                    if (
                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == socketNumberEventReceiver.userStartIndex
                    )
                    {
                        for (
                            int j = 0;
                            j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                            j++
                        )
                        {
                            if (
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .cookieStaticIndex
                                == socketNumberEventReceiver.moveToken.data.tokenMove
                            )
                            {
                                //  ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovement>().CoockieManage();

                                if (
                                    socketNumberEventReceiver.moveToken.data.movementValue == 6
                                    && ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .myLastBoxIndex == -1
                                )
                                {
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .CoockieMove(1);
                                }
                                else
                                {
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .CoockieMove(
                                            socketNumberEventReceiver.moveToken.data.movementValue
                                        );
                                }

                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .CoockieRing();
                            }
                        }
                    }
                    for (
                        int j = 0;
                        j < socketNumberEventReceiver.moveToken.data.updatedScore.Count;
                        j++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex
                            == socketNumberEventReceiver.moveToken.data.updatedScore[j].seatIndex
                        )
                        {
                            Debug.Log(
                                "Score Update"
                                    + ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[i]
                                        .playerInfoData
                                        .playerSeatIndex
                                    + " :::"
                                    + socketNumberEventReceiver.moveToken.data.updatedScore[j].score
                            );
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .scoreText
                                .text = socketNumberEventReceiver
                                .moveToken.data.updatedScore[j]
                                .score.ToString();
                        }
                    }
                }
                else
                {
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockieForClassicMode.ForEach(
                            (coockie) =>
                                coockie.transform.GetComponent<Image>().raycastTarget = false
                        );
                    if (
                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == socketNumberEventReceiver.userStartIndex
                    )
                    {
                        for (
                            int j = 0;
                            j
                                < ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .playerCoockieForClassicMode
                                    .Count;
                            j++
                        )
                        {
                            if (
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .cookieStaticIndex
                                == socketNumberEventReceiver.moveToken.data.tokenMove
                            )
                            {
                                //   ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode[j].GetComponent<CoockieMovement>().CoockieManage();

                                if (
                                    socketNumberEventReceiver.moveToken.data.movementValue == 6
                                    && ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .myLastBoxIndex == -1
                                )
                                {
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .CoockieMove(1);
                                }
                                else
                                {
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .CoockieMove(
                                            socketNumberEventReceiver.moveToken.data.movementValue
                                        );
                                }

                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .CoockieRing();
                            }
                        }
                    }
                    for (
                        int j = 0;
                        j < socketNumberEventReceiver.moveToken.data.updatedScore.Count;
                        j++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex
                            == socketNumberEventReceiver.moveToken.data.updatedScore[j].seatIndex
                        )
                        {
                            Debug.Log("Score Update");
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .scoreText
                                .text = socketNumberEventReceiver
                                .moveToken.data.updatedScore[j]
                                .score.ToString();
                        }
                    }
                }
            }

            //Invoke(nameof(ChangeUserTurn), 0.7f);
        }

        public void UpdateUserScore()
        {
            try
            {
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    if (
                        socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                        == ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                    )
                    {
                        socketNumberEventReceiver
                            .moveToken
                            .data
                            .updatedScore[
                                socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                            ]
                            .seatIndex = socketNumberEventReceiver
                            .userTurnStart
                            .data
                            .startTurnSeatIndex;
                        socketNumberEventReceiver
                            .moveToken
                            .data
                            .updatedScore[
                                socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                            ]
                            .score += socketNumberEventReceiver.diceValue;
                    }
                    else
                    {
                        socketNumberEventReceiver.moveToken.data.updatedScore[i].seatIndex = i;
                    }
                }
                //if (socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex == 0)
                //{
                //    socketNumberEventReceiver.moveToken.data.updatedScore[0].seatIndex = socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex;
                //    socketNumberEventReceiver.moveToken.data.updatedScore[0].score += socketNumberEventReceiver.diceValue;
                //}
                //else
                //{
                //    socketNumberEventReceiver.moveToken.data.updatedScore[1].seatIndex = socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex;
                //    socketNumberEventReceiver.moveToken.data.updatedScore[1].score += socketNumberEventReceiver.diceValue;
                //}
            }
            catch (Exception ex)
            {
                Debug.Log("ERROR" + ex);
            }
        }

        public void ChangeUserTurn()
        {
            Debug.Log("check 1");
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "NUMBER"
                )
            )
            {
                if (socketNumberEventReceiver.moveToken.data.isCapturedToken || isTokenReachHome)
                {
                    isCukiKillNumberMode = true;
                    extraMove.transform.DOScale(Vector3.one, 0.1f);
                    socketNumberEventReceiver.Invoke(
                        nameof(socketNumberEventReceiver.StartUserTurn),
                        0.7f
                    );
                    //  ludoNumberUiManager.Invoke(nameof(ludoNumberUiManager.DisableMyBox), 0.8f);
                    Debug.Log("check 2");
                }
                else
                {
                    ChangeTurnSeatIndex();
                }
            }
            else
            {
                if (
                    socketNumberEventReceiver.diceValue == 6
                    || socketNumberEventReceiver.moveToken.data.isCapturedToken
                    || isTokenReachHome
                )
                {
                    extraMove.transform.DOScale(Vector3.one, 0.1f);
                    socketNumberEventReceiver.Invoke(
                        nameof(socketNumberEventReceiver.StartUserTurn),
                        0.7f
                    );
                    Debug.Log("check 2");
                }
                else
                {
                    ChangeTurnSeatIndex();
                }
            }
            socketNumberEventReceiver.moveToken.data.capturedSeatIndex = -1;
            socketNumberEventReceiver.moveToken.data.capturedTokenIndex = -1;
            tokenMovement = false;
        }

        #endregion

        #region SCORECHECK

        public void ScoreCheck()
        {
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                if (
                    ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                    == socketNumberEventReceiver.scoreViewRes.data.seatIndex
                )
                {
                    for (
                        int j = 0;
                        j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                        j++
                    )
                    {
                        if (
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockie[j]
                                .GetComponent<CoockieMovementOffline>()
                                .cookieStaticIndex
                            == socketNumberEventReceiver.scoreViewRes.data.tokenIndex
                        )
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockie[j]
                                .GetComponent<CoockieMovementOffline>()
                                .ScoreView(socketNumberEventReceiver.scoreViewRes);
                    }
                }
            }
        }

        #endregion

        #region TIEANIMATION

        internal void ShowExtraScoreAnim(string userName)
        {
            extraScore.SetActive(true);
            extraScore.transform.localScale = Vector3.zero;
            extraScoreText.text = userName + " GETS +5 POINTS";
            extraScore
                .transform.DOScale(1f, 0.75f)
                .SetEase(Ease.OutBack)
                .OnComplete(() =>
                {
                    extraScore
                        .transform.DOScale(0, 0.3f)
                        .SetEase(Ease.InBack)
                        .SetDelay(4f)
                        .OnComplete(() =>
                        {
                            extraScore.gameObject.SetActive(false);
                        });
                });
        }

        internal void ShowTokenHomeAnim()
        {
            tokenHome.SetActive(true);
            tokenHome.transform.localScale = Vector3.zero;
            tokenHome.GetComponent<Image>().DOFade(1, 0f);
            tokenHome
                .transform.DOScale(1f, 0.75f)
                .SetEase(Ease.OutBack)
                .OnComplete(() =>
                {
                    tokenHome
                        .GetComponent<Image>()
                        .DOFade(0f, 0.75f)
                        .SetEase(Ease.OutBack)
                        .SetDelay(4f)
                        .OnComplete(() =>
                        {
                            tokenHome.gameObject.SetActive(false);
                        });
                });
        }

        internal LudoNumberPlayerControlOffline ReturnPlayerFromSeatIndex(int seatIndex)
        {
            return playerList
                .Where(player => player.playerInfoData.playerSeatIndex == seatIndex)
                .Single();
        }

        #endregion

        #region BATTLEFINISH

        public bool CheckFinishBattle()
        {
            foreach (var t in ludoNumberPlayerControl)
            {
                if (
                    t.playerInfoData.playerSeatIndex
                    == socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                )
                {
                    foreach (var t1 in t.ludoNumbersUserData.playerCoockieForClassicMode)
                    {
                        if (t1.GetComponent<CoockieMovementOffline>().myLastBoxIndex != 56)
                            return false;
                    }
                }
            }
            return true;
        }

        public int maxScore;
        public int duplicateCount = 0;

        public void CheckWinner()
        {
            bool isConvert;
            int score0 = 0;
            int score1 = 0;
            Debug.Log(
                ludoNumbersAcknowledgementHandler
                    .ludoNumberPlayerControl[0]
                    .ludoNumbersUserData
                    .scoreText
                    .text
            );
            Debug.Log(
                ludoNumbersAcknowledgementHandler
                    .ludoNumberPlayerControl[2]
                    .ludoNumbersUserData
                    .scoreText
                    .text
            );
            isConvert = int.TryParse(
                ludoNumbersAcknowledgementHandler
                    .ludoNumberPlayerControl[0]
                    .ludoNumbersUserData.scoreText.text.ToString(),
                out score0
            );
            isConvert = int.TryParse(
                ludoNumbersAcknowledgementHandler
                    .ludoNumberPlayerControl[2]
                    .ludoNumbersUserData.scoreText.text.ToString(),
                out score1
            );

            List<int> scoreList;
            scoreList = new List<int>();
            for (
                int i = 0;
                i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                i++
            )
                scoreList.Add(
                    int.Parse(
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData
                            .scoreText
                            .text
                    )
                );
            maxScore = scoreList.Max();

            if (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount == 4)
            {
                for (
                    int i = 0;
                    i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                    i++
                )
                {
                    if (scoreList[i] == maxScore)
                        duplicateCount++;
                }

                if (duplicateCount > 1)
                {
                    SetDataForTieBrakerForFourPlayer();
                    Invoke(nameof(BattleFinishUserData), 25f);
                }
                else
                {
                    for (
                        int i = 0;
                        i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                        i++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .scoreText
                                .text == maxScore.ToString()
                        )
                            winnerId = ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex;
                    }
                    BattleFinishUserData();
                }
            }
            else
            {
                if (score0 == score1)
                {
                    SetDataForTieBraker();
                    Invoke(nameof(BattleFinishUserData), 10f);
                }
                else
                {
                    for (
                        int i = 0;
                        i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                        i++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .scoreText
                                .text == maxScore.ToString()
                        )
                            winnerId = ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex;
                    }
                    BattleFinishUserData();
                }
            }

            Debug.Log("Duplicat Count" + duplicateCount);

            //  var duplicates = ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.GroupBy(x => x.ludoNumbersUserData.scoreText.text)
            //.Where(g => g.Count() > 1)
            //.Select(y => new { Element = y.Key, Counter = y.Count() })
            //.ToList();

            //  Debug.LogError(" duplicates => COUNT => " + duplicates.Count);
            //  foreach (var t in duplicates)
            //  {
            //      Debug.LogError(" duplicates => Counter => " + t.Counter);
            //      Debug.LogError(" duplicates => Element => " + t.Element);
            //      Debug.LogError(" duplicates => Max => " + ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Max(x => x.ludoNumbersUserData.scoreText.text));
            //  }




            Debug.Log("MAx score" + maxScore);

            //Debug.Log(score0 + "AND" + score1);
            //if (score0 == score1)
            //{
            //    SetDataForTieBraker();
            //    Invoke(nameof(BattleFinishUserData), 10f);
            //}
            //else
            //{
            //    for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
            //    {
            //        if (ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.scoreText.text == maxScore.ToString())
            //            winnerId = ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex;
            //    }
            //    BattleFinishUserData();
            //}
        }

        public void SetDataForTieBrakerForFourPlayer()
        {
            try
            {
                int dataNo = 0;
                socketNumberEventReceiver.tiePlayerCount = 0;
                for (int i = 0; i < 4; i++)
                {
                    int count;
                    if (
                        ludoNumberPlayerControl[i].ludoNumbersUserData.scoreText.text
                        == maxScore.ToString()
                    )
                    {
                        socketNumberEventReceiver.tiePlayerCount++;
                        Debug.Log("Data no => " + dataNo);
                        socketNumberEventReceiver.tieBreaker.data.userData[dataNo].seatIndex = i;
                        for (
                            int j = 0;
                            j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                            j++
                        )
                        {
                            count = 0;
                            for (
                                int k = 0;
                                k
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                k++
                            )
                            {
                                if (k != j)
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                        >= ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[k]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                    )
                                    {
                                        count++;
                                    }
                                }
                            }
                            if (count == 3)
                            {
                                socketNumberEventReceiver
                                    .tieBreaker
                                    .data
                                    .userData[dataNo]
                                    .furthestToken = ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex;
                                socketNumberEventReceiver
                                    .tieBreaker
                                    .data
                                    .userData[dataNo]
                                    .tokenIndex = ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .cookieStaticIndex;
                            }
                        }
                        dataNo++;
                    }
                    socketNumberEventReceiver.maxPlayer = dataNo - 1;
                }

                socketNumberEventReceiver.TieBreaker();
            }
            catch (Exception ex)
            {
                Debug.Log(ex);
            }
        }

        public void SetDataForTieBraker()
        {
            try
            {
                socketNumberEventReceiver.tieBreaker.data.userData[0].seatIndex = 0;
                socketNumberEventReceiver.tieBreaker.data.userData[1].seatIndex = 1;
                int count = 0;
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    if (ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex == 0)
                    {
                        for (
                            int j = 0;
                            j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                            j++
                        )
                        {
                            count = 0;
                            for (
                                int k = 0;
                                k
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                k++
                            )
                            {
                                if (k != j)
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                        >= ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[k]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                    )
                                    {
                                        count++;
                                    }
                                }
                            }
                            if (count == 3)
                            {
                                socketNumberEventReceiver
                                    .tieBreaker
                                    .data
                                    .userData[0]
                                    .furthestToken = ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex;
                                socketNumberEventReceiver.tieBreaker.data.userData[0].tokenIndex =
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .cookieStaticIndex;
                            }
                        }
                    }
                    else if (ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex == 1)
                    {
                        for (
                            int j = 0;
                            j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                            j++
                        )
                        {
                            count = 0;
                            for (
                                int k = 0;
                                k
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                k++
                            )
                            {
                                if (k != j)
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                        >= ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[k]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                    )
                                    {
                                        count++;
                                    }
                                }
                            }
                            if (count == 3)
                            {
                                socketNumberEventReceiver
                                    .tieBreaker
                                    .data
                                    .userData[1]
                                    .furthestToken = ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex;
                                socketNumberEventReceiver.tieBreaker.data.userData[1].tokenIndex =
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .cookieStaticIndex;
                                Debug.Log(
                                    "furthestToken=>"
                                        + ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                );
                            }
                        }
                    }
                }
                if (
                    socketNumberEventReceiver.tieBreaker.data.userData[1].furthestToken
                    > socketNumberEventReceiver.tieBreaker.data.userData[0].furthestToken
                )
                {
                    winnerId = 1;
                }
                else
                {
                    winnerId = 0;
                }
                Debug.Log(
                    "furthestToken=>"
                        + socketNumberEventReceiver.tieBreaker.data.userData[1].furthestToken
                );
                socketNumberEventReceiver.TieBreaker();
            }
            catch (Exception ex)
            {
                Debug.Log(ex);
            }
        }

        public void BattleFinishUserData()
        {
            Debug.Log("BattleFinishUserData => istie " + isTie);
            if (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount == 4)
            {
                socketNumberEventReceiver.battleFinish.data.payload.players[0].userId = "0";
                socketNumberEventReceiver.battleFinish.data.payload.players[0].username =
                    LudoDisplayNameUtility.LocalPlayerLabel();
                socketNumberEventReceiver.battleFinish.data.payload.players[0].seatIndex = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[0].isPlaying = true;
                socketNumberEventReceiver.battleFinish.data.payload.players[0].avatar =
                    "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                socketNumberEventReceiver.battleFinish.data.payload.players[0].score = 1;
                socketNumberEventReceiver.battleFinish.data.payload.players[0].winAmount = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].userId = "1";
                socketNumberEventReceiver.battleFinish.data.payload.players[1].username = LudoDisplayNameUtility.NeutralSeatLabel(1);
                socketNumberEventReceiver.battleFinish.data.payload.players[1].seatIndex = 1;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].isPlaying = false;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].avatar =
                    "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                socketNumberEventReceiver.battleFinish.data.payload.players[1].score = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].winAmount = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[2].userId = "2";
                socketNumberEventReceiver.battleFinish.data.payload.players[2].username = LudoDisplayNameUtility.NeutralSeatLabel(2);
                socketNumberEventReceiver.battleFinish.data.payload.players[2].seatIndex = 2;
                socketNumberEventReceiver.battleFinish.data.payload.players[2].isPlaying = false;
                socketNumberEventReceiver.battleFinish.data.payload.players[2].avatar =
                    "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                socketNumberEventReceiver.battleFinish.data.payload.players[2].score = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[2].winAmount = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[3].userId = "3";
                socketNumberEventReceiver.battleFinish.data.payload.players[3].username = LudoDisplayNameUtility.NeutralSeatLabel(3);
                socketNumberEventReceiver.battleFinish.data.payload.players[3].seatIndex = 3;
                socketNumberEventReceiver.battleFinish.data.payload.players[3].isPlaying = false;
                socketNumberEventReceiver.battleFinish.data.payload.players[3].avatar =
                    "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                socketNumberEventReceiver.battleFinish.data.payload.players[3].score = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[3].winAmount = 0;
            }
            else
            {
                socketNumberEventReceiver.battleFinish.data.payload.players[0].userId = "0";
                socketNumberEventReceiver.battleFinish.data.payload.players[0].username =
                    LudoDisplayNameUtility.LocalPlayerLabel();
                socketNumberEventReceiver.battleFinish.data.payload.players[0].seatIndex = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[0].isPlaying = true;
                socketNumberEventReceiver.battleFinish.data.payload.players[0].avatar =
                    "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                socketNumberEventReceiver.battleFinish.data.payload.players[0].score = 1;
                socketNumberEventReceiver.battleFinish.data.payload.players[0].winAmount = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].userId = "1";
                socketNumberEventReceiver.battleFinish.data.payload.players[1].username = LudoDisplayNameUtility.NeutralSeatLabel(1);
                socketNumberEventReceiver.battleFinish.data.payload.players[1].seatIndex = 1;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].isPlaying = false;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].avatar =
                    "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                socketNumberEventReceiver.battleFinish.data.payload.players[1].score = 0;
                socketNumberEventReceiver.battleFinish.data.payload.players[1].winAmount = 0;
            }
            if (!isTie)
                switch (winnerId)
                {
                    case 0:
                        SetWinType(0);
                        break;
                    case 1:
                        SetWinType(1);
                        break;
                    case 2:
                        SetWinType(2);
                        break;
                    case 3:
                        SetWinType(3);
                        break;
                    default:
                        break;
                }
            else
            {
                SetWinTypeForTie();
            }
            //if (winnerId == 0)
            //{
            //    socketNumberEventReceiver.battleFinish.data.payload.players[0].winType = "win";
            //    socketNumberEventReceiver.battleFinish.data.payload.players[1].winType = "lost";
            //}
            //else
            //{
            //    socketNumberEventReceiver.battleFinish.data.payload.players[0].winType = "lost";
            //    socketNumberEventReceiver.battleFinish.data.payload.players[1].winType = "win";
            //}
            Battle();
        }

        public void SetWinType(int no)
        {
            Debug.Log("SetWinType =>");
            for (
                int i = 0;
                i < socketNumberEventReceiver.battleFinish.data.payload.players.Count;
                i++
            )
            {
                if (socketNumberEventReceiver.battleFinish.data.payload.players[i].seatIndex == no)
                {
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winType = "win";
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winAmount =
                        socketNumberEventReceiver.winAmt;
                }
                else
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winType = "loss";
            }
        }

        public void SetWinTypeForTie()
        {
            Debug.Log("SetWinType for tie braker =>");
            for (
                int i = 0;
                i < socketNumberEventReceiver.battleFinish.data.payload.players.Count;
                i++
            )
            {
                socketNumberEventReceiver.battleFinish.data.payload.players[i].winType = "tie";
                if (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount == 2)
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winAmount =
                        socketNumberEventReceiver.winAmt / 2;
                else
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winAmount =
                        socketNumberEventReceiver.winAmt / 4;
            }
        }

        public void Battle() => StartCoroutine(BattleFinish());

        IEnumerator BattleFinish()
        {
            Debug.Log("BattleFinish =>");
            ludoNumberUiManager.ResetGame();
            waitPanel.SetActive(true);
            extraMove.SetActive(false);
            ludoNumbersAcknowledgementHandler.ludoNumberUiManager.MoveLeftGameObject.SetActive(
                false
            );
            ludoNumbersAcknowledgementHandler.ludoNumberUiManager.timerGameObject.SetActive(false);
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.ring.ForEach(
                        (coockie) => coockie.gameObject.SetActive(false)
                    );
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.playerCoockie.ForEach(
                        (coockie) => coockie.gameObject.SetActive(false)
                    );
                userTimer[i].TurnDataReset();

                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                    ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(false);
                else
                    ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumber.SetActive(false);

                ludoNumberPlayerControl[i].ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                ludoNumberPlayerControl[i].ludoNumbersUserData.arrowAnimationOnTurnTime.enabled =
                    false;
                ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.SetActive(true);
                ludoNumberPlayerControl[i].ludoNumbersUserData.turnProfileBlink.SetActive(false);
                ludoNumberPlayerControl[i].ludoNumbersUserData.animatorOnTurn.enabled = false;
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.diceNumberText.gameObject.SetActive(false);
                //                logoImage.SetActive(false);
                networkIndicator.SetActive(false);
            }

            yield return new WaitForSeconds(1f);
            BattleFinishBorad();
        }

        void BattleFinishBorad()
        {
            Debug.Log("BattleFinishBorad =>");
            // #if UNITY_ANDROID
            //             if (PlayerPrefs.GetInt("removeAds") == 0)
            //             {
            //                 ADManagerOffline.instance.DestroyAd();
            //                 ADManagerOffline.instance.ShowInterstitialAd();
            //             }
            // #endif
            SoundManagerOffline.instance.musicAudioSource.Stop();
            SoundManagerOffline.instance.soundAudioSource.Stop();
            SoundManagerOffline.instance.timeAudioSource.Stop();
            winPanel.SetActive(true);
            waitPanel.SetActive(false);
            board.SetActive(false);
            winningPartical.gameObject.SetActive(false);
            ludoNumberUiManager.moveLeft.SetActive(false); //TODO
            if (LudoV2MatchmakingBridge.Instance != null)
            {
                LudoV2MatchmakingBridge.Instance.ReportMatchCompleted(
                    socketNumberEventReceiver.battleFinish.data
                );
            }
            ludoNumberWinnerData.SetWinnerData(socketNumberEventReceiver.battleFinish.data);
            for (
                int i = 0;
                i < socketNumberEventReceiver.battleFinish.data.payload.players.Count;
                i++
            )
            {
                Debug.Log(
                    "Winner => "
                        + socketNumberEventReceiver.battleFinish.data.payload.players[i].winType
                        + "seat index"
                        + socketNumberEventReceiver.battleFinish.data.payload.players[i].userId
                );
                if (
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winType == "win"
                    && socketNumberEventReceiver.battleFinish.data.payload.players[i].userId == "0"
                ) //check with my user Id
                {
                    winningPartical.gameObject.SetActive(true);
                    winningPartical.Play();
                    youWin.SetActive(true);
                    SoundManagerOffline.instance.SoundPlay(SoundManagerOffline.instance.winAudio);
                    if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                    {
                        int chips = PlayerPrefs.GetInt("Totalchips");
                        chips = chips + socketNumberEventReceiver.winAmt;
                        PlayerPrefs.SetInt("Totalchips", chips);
                        GameManagerOffline.instace.dashBoardManager.UpdateChips(chips);
                    }
                    int gamePlayed = PlayerPrefs.GetInt("gamePlayed");
                    int gameWon = PlayerPrefs.GetInt("gameWon");
                    int gameLoss = PlayerPrefs.GetInt("gameLoss");
                    DashBoardManagerOffline.instance.UpdateGameStatistics(
                        gamePlayed,
                        gameWon + 1,
                        gameLoss
                    );
                }
                else if (
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winType == "loss"
                    && socketNumberEventReceiver.battleFinish.data.payload.players[i].userId == "0"
                )
                {
                    youLose.SetActive(true);
                    SoundManagerOffline.instance.SoundPlay(SoundManagerOffline.instance.loseAudio);

                    int gamePlayed = PlayerPrefs.GetInt("gamePlayed");
                    int gameWon = PlayerPrefs.GetInt("gameWon");
                    int gameLoss = PlayerPrefs.GetInt("gameLoss");
                    DashBoardManagerOffline.instance.UpdateGameStatistics(
                        gamePlayed,
                        gameWon,
                        gameLoss + 1
                    );
                }
                else if (
                    socketNumberEventReceiver.battleFinish.data.payload.players[i].winType == "tie"
                    && socketNumberEventReceiver.battleFinish.data.payload.players[i].userId == "0"
                )
                {
                    gameTie.SetActive(true);
                    SoundManagerOffline.instance.SoundPlay(SoundManagerOffline.instance.loseAudio);
                    if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                    {
                        int chips = PlayerPrefs.GetInt("Totalchips");
                        if (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount == 2)
                            chips = chips + socketNumberEventReceiver.winAmt / 2;
                        else
                            chips = chips + socketNumberEventReceiver.winAmt / 4;
                        PlayerPrefs.SetInt("Totalchips", chips);
                        GameManagerOffline.instace.dashBoardManager.UpdateChips(chips);
                    }
                }
            }

            //else
            //{
            //    youLose.SetActive(true);
            //}
        }

        #endregion

        #region SHOWPOPUP

        public void ShowPopUp(PopUp dataobject)
        {
            string popupType = dataobject.data.popupType;
            switch (popupType)
            {
                case "topToastPopup":
                    ShowServer_TopToast(dataobject.data);
                    break;
                case "toastPopup":
                    ShowServer_CenterToast(dataobject.data);
                    break;
                case "commonPopup":
                    ShowServer_CommonPopup(dataobject.data);
                    break;
                default:
                    break;
            }
        }

        private void ShowServer_TopToast(PopUpData popupData, bool isAutoHide = true)
        {
            serverTopToastText.text = popupData.message;
            RectTransform rect = serverTopToast.GetComponent<RectTransform>();
            rect.localScale = Vector2.one;
            serverTopToast.transform.GetComponent<Canvas>().enabled = true;
            if (isAutoHide)
            {
                rect.DOScale(1f, 0.3f)
                    .SetEase(Ease.OutBack)
                    .OnComplete(() =>
                    {
                        this.WaitforTime(
                            6.5f,
                            () =>
                            {
                                HideServer_TopToast(0.3f);
                            }
                        );
                    });
            }
            else
                rect.DOScale(1f, 0.3f).SetEase(Ease.OutBack);
        }

        public void HideServer_TopToast(float time)
        {
            RectTransform topToastRect = serverTopToast.GetComponent<RectTransform>();
            topToastRect
                .DOScale(0, time)
                .OnComplete(() => serverTopToast.transform.GetComponent<Canvas>().enabled = false);
        }

        private void ShowServer_CenterToast(PopUpData popupData, bool isAutoHide = true)
        {
            serverCenterToastText.text = popupData.message;
            RectTransform rect = serverCenterToast.GetComponent<RectTransform>();
            HideServer_CenterToast();
            serverCenterToast.GetComponent<Canvas>().enabled = true;
            if (isAutoHide)
            {
                rect.DOScale(1, .4f)
                    .SetEase(Ease.OutElastic)
                    .OnComplete(() =>
                    {
                        this.WaitforTime(
                            6f,
                            () =>
                            {
                                HideServer_CenterToast();
                            }
                        );
                    });
            }
            else
                rect.DOScale(1f, .4f).SetEase(Ease.OutElastic);
        }

        public void HideServer_CenterToast()
        {
            RectTransform toastRect = serverCenterToast.GetComponent<RectTransform>();
            toastRect.DOScale(0, .2f);
            serverCenterToast.GetComponent<Canvas>().enabled = false;
        }

        internal void ShowServer_CommonPopup(PopUpData popupData)
        {
            try
            {
                string title = popupData.title;
                string message = popupData.message;

                serverCommonPopupTitle.text = title;

                serverCommonPopupMessage.text = message;

                for (int i = 0; i < serverCommonPopupButtons.Length; i++)
                {
                    serverCommonPopupButtons[i].onClick.RemoveAllListeners();
                    serverCommonPopupButtons[i].gameObject.SetActive(false);
                }

                int buttonCount = popupData.buttonCounts;
                Debug.Log("buttonCount:" + popupData.buttonCounts);
                for (int i = 0; i < popupData.buttonCounts; i++)
                {
                    serverCommonPopupButtons[i].gameObject.SetActive(true);
                    serverCommonPopupButtonsText[i].text = popupData.button_text[i];
                }

                if (buttonCount == 1)
                {
                    serverCommonPopupButtons[0].transform.DOLocalMove(new Vector2(0, -163.6f), 0);
                    SetButtonSprite(
                        serverCommonPopupButtons[0].transform.GetComponent<Image>(),
                        popupData.button_color == null ? "red" : popupData.button_color[0]
                    );
                    serverCommonPopupButtons[0]
                        .onClick.AddListener(() => PopupButtonClick(popupData.button_methods[0]));
                }

                if (buttonCount == 2)
                {
                    serverCommonPopupButtons[0]
                        .transform.DOLocalMove(new Vector2(-150f, -163.6f), 0);
                    SetButtonSprite(
                        serverCommonPopupButtons[0].transform.GetComponent<Image>(),
                        popupData.button_color == null ? "red" : popupData.button_color[0]
                    );
                    serverCommonPopupButtons[0]
                        .onClick.AddListener(() => PopupButtonClick(popupData.button_methods[0]));
                    SetButtonSprite(
                        serverCommonPopupButtons[1].transform.GetComponent<Image>(),
                        popupData.button_color == null ? "green" : popupData.button_color[1]
                    );
                    serverCommonPopupButtons[1]
                        .onClick.AddListener(() => PopupButtonClick(popupData.button_methods[1]));
                }

                serverCommonPopup.GetComponent<Canvas>().enabled = true;
                RectTransform contentRect = serverCommonPopupContent.GetComponent<RectTransform>();
                contentRect.DOScale(0, 0);
                contentRect
                    .DOScale(1f, 0.5f)
                    .SetEase(Ease.OutBack)
                    .OnComplete(() =>
                    {
                        serverCommonPopupLoader.SetActive(popupData.isPopup);
                    });
            }
            catch (System.Exception e)
            {
                Debug.LogError(e.ToString());
            }

            ludoNumberUiManager.StopReconntionAnimation();
        }

        void SetButtonSprite(Image Button, string buttonSpriteColor)
        {
            if (buttonSpriteColor == "green")
                Button.sprite = greenBtn;
            else if (buttonSpriteColor == "red")
                Button.sprite = RedBtn;
        }

        private void PopupButtonClick(string method) => Invoke(method, 0);

        #endregion

        #region ALERT

        public void Alert()
        {
            CancelInvoke(nameof(GameMainTimer));
            ludoNumberUiManager.startPanel.SetActive(false);
            ludoNumberUiManager
                .settingPanel.transform.GetChild(0)
                .transform.DOScale(Vector3.zero, 0.25f)
                .OnComplete(() =>
                {
                    ludoNumberUiManager.settingPanel.SetActive(false);
                    ludoNumberUiManager.settingPanel.GetComponent<Image>().enabled = false;
                });
            alertPanel.SetActive(true);
            alertPanel.GetComponent<Image>().enabled = true;
            alertPanel
                .transform.GetChild(0)
                .transform.DOScale(Vector3.one, 0.25f)
                .OnComplete(() =>
                {
                    alert = socketNumberEventReceiver.alertMessage.data.message;
                    alertText.text = alert;
                });
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                var userData = ludoNumberPlayerControl[i].ludoNumbersUserData;
                userData.animatorOnTurn.gameObject.SetActive(false);
                userData.timeImage.gameObject.SetActive(false);
                userData.artoonLogo.SetActive(true);

                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                    userData.dice.SetActive(false);
                else
                    userData.diceNumber.SetActive(false);

                userData.turnTimeShowArrow.SetActive(false);
                userData.arrowAnimationOnTurnTime.enabled = false;
                userData.turnProfileBlink.SetActive(false);
                userData.animatorOnTurn.enabled = false;
                userData.infoBtn.SetActive(false);
            }
        }

        #endregion

        public void EmojiSet()
        {
            ludoNumberUiManager.EmojiClose();

            emojiAnimate.EmojiAnimation(
                socketNumberEventReceiver.emojiResponse.Data.emoji,
                socketNumberEventReceiver.emojiResponse.Data.seatIndex
            );
        }

        #region COOCKIEKILL

        public void CoockieKill()
        {
            for (
                int i = 0;
                i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                i++
            )
            {
                for (
                    int j = 0;
                    j
                        < ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData
                            .playerCoockie
                            .Count;
                    j++
                )
                {
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockie[j]
                        .GetComponent<CoockieMovementOffline>()
                        .CoockieManage();
                }
            }

            Debug.Log(
                "CookieKill userStartIndex => "
                    + socketNumberEventReceiver.userStartIndex
                    + " SelfPlayerSeatIndex => "
                    + socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
            );

            if (
                socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                == socketNumberEventReceiver.userStartIndex
            )
            {
                if (
                    !socketNumberEventReceiver.isExtraTurn
                    && MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                {
                    Debug.Log("Token move");
                    ludoNumberUiManager.NumberAnimation();
                    moveNumber = socketNumberEventReceiver.moveNumberAlltime - 1;
                    moveText.text = moveNumber.ToString();
                }

                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    for (
                        int j = 0;
                        j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                        j++
                    )
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.playerCoockie[j]
                            .GetComponent<CoockieMovementOffline>()
                            .CoockieManage();
                }
            }

            if (socketNumberEventReceiver.moveToken.data.isCapturedToken)
            {
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    if (
                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == socketNumberEventReceiver.moveToken.data.capturedSeatIndex
                    )
                    {
                        if (
                            !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                "CLASSIC"
                            )
                        )
                        {
                            for (
                                int j = 0;
                                j
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                j++
                            )
                            {
                                if (
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .cookieStaticIndex
                                    == socketNumberEventReceiver.moveToken.data.capturedTokenIndex
                                )
                                {
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .KillMove();
                                    CoockieMovementOffline coockieMovement =
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>();
                                    Vector3 targetPosition = coockieMovement
                                        .ludoNumbersPlayerHome.way_Point[
                                            coockieMovement.myLastBoxIndex + 1
                                        ]
                                        .transform.GetChild(0)
                                        .position;
                                    killPratical.transform.position = targetPosition;
                                    killPratical.Play();
                                    SoundManagerOffline.instance.soundAudioSource.Stop();
                                    SoundManagerOffline.instance.TokenKill(
                                        SoundManagerOffline.instance.killAudio
                                    );
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie.ForEach(
                                            (coockie) => coockie.transform.localScale = Vector3.one
                                        );
                                }
                            }
                        }
                        else
                        {
                            for (
                                int j = 0;
                                j
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockieForClassicMode
                                        .Count;
                                j++
                            )
                            {
                                if (
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .cookieStaticIndex
                                    == socketNumberEventReceiver.moveToken.data.capturedTokenIndex
                                )
                                {
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .KillMove();
                                    CoockieMovementOffline coockieMovement =
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>();
                                    Vector3 targetPosition = coockieMovement
                                        .ludoNumbersPlayerHome.way_Point[
                                            coockieMovement.myLastBoxIndex + 1
                                        ]
                                        .transform.GetChild(0)
                                        .position;
                                    killPratical.transform.position = targetPosition;
                                    killPratical.Play();
                                    SoundManagerOffline.instance.soundAudioSource.Stop();
                                    SoundManagerOffline.instance.TokenKill(
                                        SoundManagerOffline.instance.killAudio
                                    );
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockieForClassicMode.ForEach(
                                            (coockie) => coockie.transform.localScale = Vector3.one
                                        );
                                }
                            }
                        }
                    }
                }
            }
        }

        #endregion

        #region MOVETOKENREJOIN

        public void MoveTokenRejoin(SignUpResponce signUpResponce)
        {
            if (signUpResponce.data.thisPlayerSeatIndex == socketNumberEventReceiver.userStartIndex)
            {
                socketNumberEventReceiver.userTurnStart.data.movesLeft = signUpResponce
                    .data
                    .movesLeft;
                if (!socketNumberEventReceiver.signUpResponce.data.userTurnDetails.isExtraTurn)
                    moveText.text = signUpResponce.data.movesLeft.ToString();
            }

            for (int k = 0; k < signUpResponce.data.playerInfo.Count; k++)
            {
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    if (
                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == signUpResponce.data.playerInfo[k].seatIndex
                    )
                    {
                        if (
                            MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                "CLASSIC"
                            )
                        )
                        {
                            for (
                                int j = 0;
                                j
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockieForClassicMode
                                        .Count;
                                j++
                            )
                            {
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex = (
                                    signUpResponce.data.playerInfo[k].tokenDetails[j]
                                );
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .TokenMoveOnRejoin();
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .SetActive(false);
                            }
                        }
                        else
                        {
                            for (
                                int j = 0;
                                j
                                    < ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                j++
                            )
                            {
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex = (
                                    signUpResponce.data.playerInfo[k].tokenDetails[j]
                                );
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .scoreText
                                    .text = signUpResponce.data.playerInfo[k].score.ToString();
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .TokenMoveOnRejoin();
                            }
                        }
                    }
                }
            }
        }

        #endregion

        #region DICEANIMATION

        public void DiceAnimationStart(int diceValue, int startTurnSeatIndex)
        {
            try
            {
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    ludoNumberUiManager.gameManager.gameState = GameState.playing;
                    if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                    {
                        if (
                            ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                                == startTurnSeatIndex
                            && startTurnSeatIndex
                                == socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                        )
                        {
                            int number = diceValue;
                            Debug.Log("Number => " + number);
                            Debug.Log(
                                "Dice Amination Start || IsBot || SIX Count =>"
                                    + socketNumberEventReceiver.sixCount
                            );
                            for (int j = 0; j < number; j++)
                                movePositon.Add(
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .ludoNumbersPlayerHome
                                        .way_Point[i + 1]
                                );

                            if (socketNumberEventReceiver.isSix)
                            {
                                if (socketNumberEventReceiver.sixCount == 1)
                                {
                                    firstSix.SetActive(true);
                                }
                                else if (socketNumberEventReceiver.sixCount == 2)
                                {
                                    firstSix.SetActive(true);
                                    secondSix.SetActive(true);
                                }
                                else if (socketNumberEventReceiver.sixCount == 3)
                                {
                                    firstSix.SetActive(true);
                                    secondSix.SetActive(true);
                                    lastSix.SetActive(true);
                                    Invoke(nameof(OffSixNumberShow), 0.5f);
                                    Invoke(nameof(ChangeTurnSeatIndex), 0f);
                                }
                            }
                            if (
                                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                    "CLASSIC"
                                )
                            )
                            {
                                Debug.Log("classic mode Number => " + number);
                                for (
                                    int j = 0;
                                    j
                                        < ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockieForClassicMode
                                            .Count;
                                    j++
                                )
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex == -1
                                        && socketNumberEventReceiver.diceValue == 6
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                    }
                                    else if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex != -1
                                        && ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex + diceValue
                                            <= 56
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockieForClassicMode[j]
                                            .transform
                                            .localScale = new Vector3(1.1f, 1.1f, 1.1f);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                    }
                                    else
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((coockie) => coockie.transform.localScale = new Vector3(1f, 1f, 1f));
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode[j].GetComponent<CoockieMovement>().CoockieManage();
                                    }
                                }
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Canvas>().sortingOrder +=
                                                5
                                    );
                            }
                            else
                            {
                                Debug.Log("Number => " + number);
                                for (
                                    int j = 0;
                                    j
                                        < ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie
                                            .Count;
                                    j++
                                )
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex == -1
                                        && socketNumberEventReceiver.diceValue == 6
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                    }
                                    else if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex != -1
                                        && ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex + diceValue
                                            <= 56
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie[j]
                                            .transform
                                            .localScale = new Vector3(1.1f, 1.1f, 1.1f);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                        Debug.Log(
                                            "SortingLayer Order Number Mode => "
                                                + ludoNumberPlayerControl[i]
                                                    .ludoNumbersUserData.playerCoockie[j]
                                                    .GetComponent<Canvas>()
                                                    .sortingOrder
                                        );
                                    }
                                    else
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        //   ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.ForEach((coockie) => coockie.transform.localScale = new Vector3(1f, 1f, 1f));
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovement>().CoockieManage();
                                    }
                                }
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Canvas>().sortingOrder +=
                                                5
                                    );
                            }
                        }
                        else
                        {
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.ring.ForEach(
                                    (coockie) => coockie.gameObject.SetActive(false)
                                );
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockie.ForEach(
                                    (coockie) =>
                                        coockie.transform.GetChild(0).gameObject.SetActive(false)
                                );
                        }
                    }
                    else
                    {
                        if (
                            ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                            == startTurnSeatIndex
                        )
                        {
                            int number = diceValue;
                            Debug.Log("Number => " + number);
                            Debug.Log(
                                "Dice Amination Start || Pass N Play || SIX Count =>"
                                    + socketNumberEventReceiver.sixCount
                            );
                            for (int j = 0; j < number; j++)
                                movePositon.Add(
                                    ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .ludoNumbersPlayerHome
                                        .way_Point[i + 1]
                                );

                            if (socketNumberEventReceiver.isSix)
                            {
                                if (socketNumberEventReceiver.sixCount == 1)
                                {
                                    if (
                                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                                        == 0
                                    )
                                    {
                                        firstSix.SetActive(true);
                                    }
                                }
                                else if (socketNumberEventReceiver.sixCount == 2)
                                {
                                    if (
                                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                                        == 0
                                    )
                                    {
                                        firstSix.SetActive(true);
                                        secondSix.SetActive(true);
                                    }
                                }
                                else if (socketNumberEventReceiver.sixCount == 3)
                                {
                                    if (
                                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                                        == 0
                                    )
                                    {
                                        firstSix.SetActive(true);
                                        secondSix.SetActive(true);
                                        lastSix.SetActive(true);
                                        Invoke(nameof(OffSixNumberShow), 0.5f);
                                        Invoke(nameof(ChangeTurnSeatIndex), 0f);
                                    }
                                }
                            }
                            if (
                                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                    "CLASSIC"
                                )
                            )
                            {
                                Debug.Log("classic mode Number => " + number);
                                for (
                                    int j = 0;
                                    j
                                        < ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockieForClassicMode
                                            .Count;
                                    j++
                                )
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex == -1
                                        && socketNumberEventReceiver.diceValue == 6
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                    }
                                    else if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex != -1
                                        && ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex + diceValue
                                            <= 56
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockieForClassicMode[j]
                                            .transform
                                            .localScale = new Vector3(1.1f, 1.1f, 1.1f);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                    }
                                    else
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((coockie) => coockie.transform.localScale = new Vector3(1f, 1f, 1f));
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode[j].GetComponent<CoockieMovement>().CoockieManage();
                                    }
                                }
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Canvas>().sortingOrder +=
                                                5
                                    );
                            }
                            else
                            {
                                Debug.Log("Number => " + number);
                                for (
                                    int j = 0;
                                    j
                                        < ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie
                                            .Count;
                                    j++
                                )
                                {
                                    if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex == -1
                                        && socketNumberEventReceiver.diceValue == 6
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                    }
                                    else if (
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex != -1
                                        && ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex + diceValue
                                            <= 56
                                    )
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie[j]
                                            .transform
                                            .localScale = new Vector3(1.1f, 1.1f, 1.1f);
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                        Debug.Log(
                                            "SortingLayer Order Number Mode => "
                                                + ludoNumberPlayerControl[i]
                                                    .ludoNumbersUserData.playerCoockie[j]
                                                    .GetComponent<Canvas>()
                                                    .sortingOrder
                                        );
                                    }
                                    else
                                    {
                                        ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        //   ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.ForEach((coockie) => coockie.transform.localScale = new Vector3(1f, 1f, 1f));
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovement>().CoockieManage();
                                    }
                                }
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Canvas>().sortingOrder +=
                                                5
                                    );
                            }
                        }
                        else
                        {
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.ring.ForEach(
                                    (coockie) => coockie.gameObject.SetActive(false)
                                );
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockie.ForEach(
                                    (coockie) =>
                                        coockie.transform.GetChild(0).gameObject.SetActive(false)
                                );
                        }
                    }
                }
                Invoke(nameof(TurnChange), 0.2f);
            }
            catch (System.Exception ex)
            {
                Debug.LogError("Exception" + ex);
            }
        }

        public void TurnChange()
        {
            Debug.Log(
                "TurnChange startTurnSeatIndex => "
                    + socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
            );
            Debug.Log("CheckPossibleTokenMove :-> " + CheckPossibleTokenMove());
            Debug.Log("CheckPossibleTokenMove sixCount :-> " + socketNumberEventReceiver.sixCount);
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "CLASSIC"
                )
            )
            {
                if (CheckAllCookieAtHome() && socketNumberEventReceiver.diceValue != 6)
                {
                    ChangeTurnSeatIndex();
                }
                else
                {
                    if (socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex != 0)
                    {
                        if (socketNumberEventReceiver.sixCount == 3)
                        {
                            Debug.Log(
                                "Turn Change || Classic || SIX Count =>"
                                    + socketNumberEventReceiver.sixCount
                            );
                            ChangeTurnSeatIndex();
                        }
                        else
                        {
                            if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                                BotMoveToken();
                            else
                            {
                                if (
                                    !CheckPossibleTokenMove()
                                    && !CheckAllCookieAtHome()
                                    && socketNumberEventReceiver.diceValue != 6
                                )
                                    ChangeTurnSeatIndex();
                                else
                                {
                                    if (
                                        socketNumberEventReceiver.diceValue == 6
                                        && CheckOneCookieAtHome()
                                    )
                                    {
                                        Debug.Log("Auto move not possible");
                                    }
                                    else
                                    {
                                        int index;
                                        if (
                                            socketNumberEventReceiver
                                                .joinTableResponse
                                                .data
                                                .maxPlayerCount == 4
                                        )
                                        {
                                            index = socketNumberEventReceiver
                                                .userTurnStart
                                                .data
                                                .startTurnSeatIndex;
                                        }
                                        else
                                        {
                                            index = 2;
                                        }

                                        List<CoockieMovementOffline> cookieMoveList =
                                            new List<CoockieMovementOffline>();
                                        foreach (
                                            var t in ludoNumberPlayerControl[index]
                                                .ludoNumbersUserData
                                                .playerCoockieForClassicMode
                                        )
                                        {
                                            if (
                                                t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                                    != -1
                                                && t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                                    + socketNumberEventReceiver.diceValue
                                                    <= 56 /*&&
                                        socketNumberEventReceiver.diceValue != 6*/
                                            )
                                            {
                                                cookieMoveList.Add(
                                                    t.GetComponent<CoockieMovementOffline>()
                                                );
                                            }
                                        }

                                        Debug.Log(
                                            "Self user turn cookie move count :-> "
                                                + cookieMoveList.Count
                                        );
                                        if (cookieMoveList.Count == 1)
                                        {
                                            cookieMoveList[0]
                                                .transform.GetComponent<Image>()
                                                .raycastTarget = false;
                                            cookieMoveList[0]
                                                .transform.GetChild(0)
                                                .gameObject.SetActive(false);
                                            cookieMoveList[0].MoveToken();
                                        }
                                        if (cookieMoveList.Count == 0)
                                        {
                                            ChangeTurnSeatIndex();
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        if (
                            !CheckPossibleTokenMove()
                            && !CheckAllCookieAtHome()
                            && socketNumberEventReceiver.diceValue != 6
                        )
                            ChangeTurnSeatIndex();
                        else
                        {
                            if (socketNumberEventReceiver.diceValue == 6 && CheckOneCookieAtHome())
                            {
                                Debug.Log("Auto move not possible");
                            }
                            else
                            {
                                List<CoockieMovementOffline> cookieMoveList =
                                    new List<CoockieMovementOffline>();
                                foreach (
                                    var t in ludoNumberPlayerControl[0]
                                        .ludoNumbersUserData
                                        .playerCoockieForClassicMode
                                )
                                {
                                    if (
                                        t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                            != -1
                                        && t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                            + socketNumberEventReceiver.diceValue
                                            <= 56 /*&&
                                        socketNumberEventReceiver.diceValue != 6*/
                                    )
                                    {
                                        cookieMoveList.Add(
                                            t.GetComponent<CoockieMovementOffline>()
                                        );
                                    }
                                }

                                Debug.Log(
                                    "Self user turn cookie move count :-> " + cookieMoveList.Count
                                );
                                if (cookieMoveList.Count == 1)
                                {
                                    cookieMoveList[0]
                                        .transform.GetComponent<Image>()
                                        .raycastTarget = false;
                                    cookieMoveList[0]
                                        .transform.GetChild(0)
                                        .gameObject.SetActive(false);
                                    cookieMoveList[0].MoveToken();
                                }
                                if (cookieMoveList.Count == 0)
                                {
                                    ChangeTurnSeatIndex();
                                }
                            }
                        }
                    }
                }
            }
            else
            {
                if (socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex != 0)
                {
                    if (socketNumberEventReceiver.sixCount == 3)
                    {
                        Debug.Log(
                            "Turn Change || Dice || SIX Count =>"
                                + socketNumberEventReceiver.sixCount
                        );
                        ChangeTurnSeatIndex();
                    }
                    else
                    {
                        if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                            BotMoveToken();
                        else
                        {
                            if (
                                !CheckPossibleTokenMove()
                                && !CheckAllCookieAtHome()
                                && socketNumberEventReceiver.diceValue != 6
                            )
                                ChangeTurnSeatIndex();
                            else
                            {
                                if (
                                    socketNumberEventReceiver.diceValue == 6
                                    && CheckOneCookieAtHome()
                                )
                                {
                                    Debug.Log("Auto move not possible");
                                }
                                else
                                {
                                    List<CoockieMovementOffline> cookieMoveList =
                                        new List<CoockieMovementOffline>();
                                    foreach (
                                        var t in ludoNumberPlayerControl[2]
                                            .ludoNumbersUserData
                                            .playerCoockieForClassicMode
                                    )
                                    {
                                        if (
                                            t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                                != -1
                                            && t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                                + socketNumberEventReceiver.diceValue
                                                <= 56 /*&&
                                        socketNumberEventReceiver.diceValue != 6*/
                                        )
                                        {
                                            cookieMoveList.Add(
                                                t.GetComponent<CoockieMovementOffline>()
                                            );
                                        }
                                    }

                                    Debug.Log(
                                        "Self user turn cookie move count :-> "
                                            + cookieMoveList.Count
                                    );
                                    if (cookieMoveList.Count == 1)
                                    {
                                        cookieMoveList[0]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = false;
                                        cookieMoveList[0]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        cookieMoveList[0].MoveToken();
                                    }
                                }
                            }
                        }
                    }
                }
                else
                {
                    if (
                        !CheckPossibleTokenMove()
                        && !CheckAllCookieAtHome()
                        && socketNumberEventReceiver.diceValue != 6
                    )
                        ChangeTurnSeatIndex();
                    else
                    {
                        if (socketNumberEventReceiver.diceValue == 6 && CheckOneCookieAtHome())
                        {
                            Debug.Log("Auto move not possible");
                        }
                        else
                        {
                            List<CoockieMovementOffline> cookieMoveList =
                                new List<CoockieMovementOffline>();
                            foreach (
                                var t in ludoNumberPlayerControl[0]
                                    .ludoNumbersUserData
                                    .playerCoockieForClassicMode
                            )
                            {
                                if (
                                    t.GetComponent<CoockieMovementOffline>().myLastBoxIndex != -1
                                    && t.GetComponent<CoockieMovementOffline>().myLastBoxIndex
                                        + socketNumberEventReceiver.diceValue
                                        <= 56 /*&&
                                        socketNumberEventReceiver.diceValue != 6*/
                                )
                                {
                                    cookieMoveList.Add(t.GetComponent<CoockieMovementOffline>());
                                }
                            }

                            Debug.Log(
                                "Self user turn cookie move count :-> " + cookieMoveList.Count
                            );
                            if (cookieMoveList.Count == 1)
                            {
                                cookieMoveList[0].transform.GetComponent<Image>().raycastTarget =
                                    false;
                                cookieMoveList[0].transform.GetChild(0).gameObject.SetActive(false);
                                cookieMoveList[0].MoveToken();
                            }
                        }
                    }
                }
            }
        }

        bool CheckPossibleTokenMove()
        {
            foreach (var t in ludoNumberPlayerControl)
            {
                if (
                    t.playerInfoData.playerSeatIndex
                    == socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                )
                {
                    foreach (var t1 in t.ludoNumbersUserData.playerCoockieForClassicMode)
                    {
                        if (t1.GetComponent<CoockieMovementOffline>().TokenRemainingBoxCount())
                            return true;
                    }
                }
            }
            return false;
        }

        private List<GameObject> _botToken = new List<GameObject>();

        void BotMoveToken()
        {
            Debug.Log("Move token :=> " + socketNumberEventReceiver.userStartIndex);

            //Move bot cookie
            if (socketNumberEventReceiver.diceValue == 6 && CheckOneCookieAtHome())
            {
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    if (
                        ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == socketNumberEventReceiver.userStartIndex
                    )
                    {
                        for (
                            int j = 0;
                            j
                                < ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .playerCoockieForClassicMode
                                    .Count;
                            j++
                        )
                        {
                            if (
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex == -1
                            )
                            {
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .MoveToken();
                                break;
                            }
                        }
                    }
                }
            }
            else
                BotTokenMovement();
        }

        void BotTokenMovement()
        {
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                if (
                    ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                    == socketNumberEventReceiver.userStartIndex
                )
                {
                    Debug.Log("enter :=> " + _botToken.Count);

                    for (
                        int j = 0;
                        j
                            < ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .playerCoockieForClassicMode
                                .Count;
                        j++
                    )
                    {
                        if (
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .GetComponent<CoockieMovementOffline>()
                                .myLastBoxIndex != -1
                            && ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .GetComponent<CoockieMovementOffline>()
                                .myLastBoxIndex != 56
                            && ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .GetComponent<CoockieMovementOffline>()
                                .TokenRemainingBoxCount()
                        )
                        {
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .GetComponent<CoockieMovementOffline>()
                                .cookieStaticIndex = j;
                            _botToken.Add(
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .playerCoockieForClassicMode[j]
                            );
                        }
                    }
                }
            }

            Debug.Log("Bot possible token count :=> " + _botToken.Count);
            if (_botToken.Count > 0)
            {
                GameObject botMoveToken = _botToken[Random.Range(0, _botToken.Count)];
                botMoveToken.GetComponent<CoockieMovementOffline>().MoveToken();
            }
            else
            {
                ChangeTurnSeatIndex();
            }
            _botToken.Clear();
        }

        public bool CheckAllCookieAtHome()
        {
            foreach (var t in ludoNumberPlayerControl)
            {
                if (
                    t.playerInfoData.playerSeatIndex
                    == socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                )
                {
                    foreach (var t1 in t.ludoNumbersUserData.playerCoockieForClassicMode)
                    {
                        if (t1.GetComponent<CoockieMovementOffline>().myLastBoxIndex != -1)
                            return false;
                    }
                }
            }
            return true;
        }

        bool CheckOneCookieAtHome()
        {
            foreach (var t in ludoNumberPlayerControl)
            {
                if (
                    t.playerInfoData.playerSeatIndex
                    == socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
                )
                {
                    foreach (var t1 in t.ludoNumbersUserData.playerCoockieForClassicMode)
                    {
                        if (t1.GetComponent<CoockieMovementOffline>().myLastBoxIndex == -1)
                            return true;
                    }
                }
            }
            return false;
        }

        public void ChangeTurnSeatIndex()
        {
            socketNumberEventReceiver.sixCount = 0;
            socketNumberEventReceiver.sixValueCount = 0;
            Debug.Log("ChangeTurnSeatIndex || sixCount => " + socketNumberEventReceiver.sixCount);
            Debug.Log(
                "ChangeTurnSeatIndex || sixValueCount => " + socketNumberEventReceiver.sixValueCount
            );
            Debug.Log("maxPlayer => " + socketNumberEventReceiver.maxPlayer);
            if (socketNumberEventReceiver.maxPlayer == 4)
                ChangeIndex();
            else
                socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex =
                    GetNextActiveSeatIndex(socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex);
            Debug.Log(
                "ChangeTurnSeatIndex => "
                    + socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex
            );
            socketNumberEventReceiver.StartUserTurn();
        }

        public void ChangeIndex()
        {
            socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex =
                GetNextActiveSeatIndex(socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex);
        }

        private int GetNextActiveSeatIndex(int currentSeatIndex)
        {
            int maxSeats = Mathf.Max(2, socketNumberEventReceiver.maxPlayer);
            int nextSeatIndex = currentSeatIndex;

            for (int i = 0; i < maxSeats; i++)
            {
                nextSeatIndex = (nextSeatIndex + 1) % maxSeats;
                if (!eliminatedSeatIndices.Contains(nextSeatIndex))
                {
                    return nextSeatIndex;
                }
            }

            return currentSeatIndex;
        }

        private void EliminateSeat(int seatIndex)
        {
            if (eliminatedSeatIndices.Contains(seatIndex))
            {
                return;
            }

            eliminatedSeatIndices.Add(seatIndex);

            for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
            {
                var playerControl = ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i];
                if (playerControl == null || playerControl.playerInfoData == null)
                {
                    continue;
                }

                if (playerControl.playerInfoData.playerSeatIndex != seatIndex)
                {
                    continue;
                }

                playerControl.gameObject.SetActive(false);
                playerControl.ludoNumbersUserData.leaveTableImage.SetActive(true);
                playerControl.ludoNumbersUserData.smallRoundImage.SetActive(false);
                playerControl.ludoNumbersUserData.scoreBox.SetActive(false);
                playerControl.ludoNumbersUserData.turnProfileBlink.SetActive(false);
                playerControl.ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                playerControl.ludoNumbersUserData.arrowAnimationOnTurnTime.enabled = false;
                playerControl.ludoNumbersUserData.animatorOnTurn.enabled = false;

                playerControl.ludoNumbersUserData.playerCoockie.ForEach((cookie) =>
                {
                    if (cookie != null)
                    {
                        cookie.transform.SetParent(tokenKill.transform);
                        cookie.SetActive(false);
                    }
                });

                playerControl.ludoNumbersUserData.playerCoockieForClassicMode.ForEach((cookie) =>
                {
                    if (cookie != null)
                    {
                        cookie.transform.SetParent(tokenKill.transform);
                        cookie.SetActive(false);
                    }
                });

                break;
            }
        }

        public void OffSixNumberShow()
        {
            firstSix.SetActive(false);
            secondSix.SetActive(false);
            lastSix.SetActive(false);
        }

        public void AcknowledgementTokenMove(string data) =>
            Debug.Log("AcknowledgementTokenMove || => " + data);

        #endregion

        #region USERTURNSTART

        public void UserTurnStart(UserTimeStart userTurnStart)
        {
            try
            {
                Debug.Log(
                    "Moves Left = > " + socketNumberEventReceiver.userTurnStart.data.movesLeft
                );

                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                {
                    StartCoroutine(InfoPanelTime());
                    movePositon.Clear();
                    ludoNumberUiManager.startPanel.transform.DOScale(Vector3.zero, 1f);
                    extraMove.transform.DOScale(Vector3.zero, 1f);
                    SoundManagerOffline.instance.TimeSoundStop(
                        SoundManagerOffline.instance.timerAudio
                    );
                    ludoNumberTostMessage.WaitingForPlayerMessage.SetActive(false);
                    if (
                        !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                            "CLASSIC"
                        )
                    )
                        plusPoint.transform.DOScale(Vector3.zero, 1f);

                    settingBtn.interactable = true;
                    ludoNumberUiManager.timerCountScreen.SetActive(false);
                    for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                    {
                        if (
                            !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                "NUMBER"
                            )
                        )
                            ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(false);
                        else
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.diceNumber.SetActive(false);

                        ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.SetActive(true);
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.turnProfileBlink.SetActive(false);
                        ludoNumberPlayerControl[i].ludoNumbersUserData.animatorOnTurn.enabled =
                            false;
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.extraTimerImage.gameObject.SetActive(false);
                        userTimer[i].AllPlayerTimerImage.GetComponent<Image>().fillAmount = 1;
                        userTimer[i].TurnDataReset();
                        OffSixNumberShow();
                        for (
                            int j = 0;
                            j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                            j++
                        )
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockie[j]
                                .transform.GetChild(0)
                                .gameObject.SetActive(false);
                        for (
                            int j = 0;
                            j
                                < ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .playerCoockieForClassicMode
                                    .Count;
                            j++
                        )
                        {
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .transform.GetChild(0)
                                .gameObject.SetActive(false);
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .GetComponent<CoockieMovementOffline>()
                                .transform.GetComponent<Image>()
                                .raycastTarget = false;
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.playerCoockieForClassicMode[j]
                                .GetComponent<CoockieMovementOffline>()
                                .CoockieManage();
                        }

                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex != -1
                        )
                            userTimer[i].TimeCountStop();
                        if (
                            ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                            == userTurnStart.data.startTurnSeatIndex
                        )
                        {
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.timeImage.gameObject.SetActive(true);
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.timeImage.GetComponent<UserTimerOffline>()
                                .Reapet(10, 10);
                            // ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.gameObject.SetActive(false);
                            if (
                                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                                    "NUMBER"
                                )
                            )
                            {
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.diceNumber.SetActive(true);
                                // ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumber.GetComponent<Image>().sprite = diceValue;
                            }
                            else
                            {
                                ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(true);
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.dice.GetComponent<Image>()
                                    .sprite = diceValue;
                            }
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.diceAnimation.dice.gameObject.GetComponent<RectTransform>()
                                .sizeDelta = new Vector3(85, 85);
                            var profileBlink = ludoNumberPlayerControl[i].ludoNumbersUserData.turnProfileBlink;
                            profileBlink.SetActive(true);
                            // Punch scale on turn start for premium "your turn" feel
                            profileBlink.transform.DOKill();
                            profileBlink.transform.localScale = Vector3.one;
                            profileBlink.transform.DOPunchScale(new Vector3(0.3f, 0.3f, 0), 0.45f, 6, 0.35f);
                            ludoNumberPlayerControl[i].ludoNumbersUserData.animatorOnTurn.enabled =
                                true;
                            //if (socketNumberEvnetReceiver.userTurnStart.data.isExtraTurn == true)
                            //extraMove.transform.DOScale(Vector3.one, 0.5f);
                        }
                        if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                        {
                            if (
                                ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                                    == userTurnStart.data.startTurnSeatIndex
                                && userTurnStart.data.startTurnSeatIndex
                                    == socketNumberEventReceiver
                                        .signUpResponce
                                        .data
                                        .thisPlayerSeatIndex
                            )
                            {
                                SoundManagerOffline.instance.SoundPlay(
                                    SoundManagerOffline.instance.trunAudio
                                );
                                SoundManagerOffline.instance.Vibration();
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.turnTimeShowArrow.SetActive(true);
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .arrowAnimationOnTurnTime
                                    .enabled = true;
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.diceAnimation.dice.transform.GetComponent<Image>()
                                    .raycastTarget = true;
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Image>().raycastTarget =
                                                false
                                    );
                            }
                        }
                        else
                        {
                            if (
                                ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                                == userTurnStart.data.startTurnSeatIndex
                            )
                            {
                                SoundManagerOffline.instance.SoundPlay(
                                    SoundManagerOffline.instance.trunAudio
                                );
                                SoundManagerOffline.instance.Vibration();
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.turnTimeShowArrow.SetActive(true);
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .arrowAnimationOnTurnTime
                                    .enabled = true;
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.diceAnimation.dice.transform.GetComponent<Image>()
                                    .raycastTarget = true;
                                ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Image>().raycastTarget =
                                                false
                                    );
                            }
                        }
                    }
                }
                else
                {
                    ludoNumbersAcknowledgementHandler.yesBtn.GetComponent<Button>().interactable =
                        true;
                    ludoNumberUiManager.StopDecreaseCounter();
                    emojiBtn.raycastTarget = true;
                    ludoNumbersAcknowledgementHandler.yesBtn.GetComponent<Image>().raycastTarget =
                        true;
                    ludoNumbersAcknowledgementHandler.noBtn.GetComponent<Image>().raycastTarget =
                        true;
                    StartCoroutine(InfoPanelTime());
                    movePositon.Clear();
                    ludoNumberUiManager.startPanel.transform.DOScale(Vector3.zero, 1f);
                    ludoNumberTostMessage.WaitingForPlayerMessage.SetActive(false);
                    ludoNumberUiManager.timerCountScreen.SetActive(false);
                    //extraMove.transform.DOScale(Vector3.zero, 1f);
                    settingBtn.interactable = true;
                    if (
                        socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                        != userTurnStart.data.startTurnSeatIndex
                    )
                        ludoNumberUiManager.DisableMyBox();

                    if (
                        socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                        == userTurnStart.data.startTurnSeatIndex
                    )
                        if (!isCukiKillNumberMode)
                            ludoNumberUiManager.HighlightCurrentMoveNumber();

                    for (
                        int i = 0;
                        i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                        i++
                    )
                    {
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.extraTimerImage.gameObject.SetActive(false);
                        //ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.animator.gameObject.GetComponent<Animator>()
                        // .enabled = true;
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .userId == ludoNumbersAcknowledgementHandler.playerOwnId
                        )
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.numberView.SetActive(true);

                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.diceNumber.SetActive(false);
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.animatorOnTurn.gameObject.SetActive(false);
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.diceNumberText.gameObject.SetActive(false);
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.extraTimerImage.gameObject.SetActive(false);
                        userTimer[i].AllPlayerTimerImage.GetComponent<Image>().fillAmount = 1;
                        userTimer[i].TurnDataReset();
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex != -1
                        )
                            userTimer[i].TimeCountStop();

                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex == userTurnStart.data.startTurnSeatIndex
                        )
                        {
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.timeImage.gameObject.SetActive(true);
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.timeImage.GetComponent<UserTimerOffline>()
                                .Reapet(
                                    socketNumberEventReceiver.signUpResponce.data.turnTimer,
                                    socketNumberEventReceiver.signUpResponce.data.turnTimer
                                );
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.diceNumber.SetActive(true);
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.diceNumberText.gameObject.SetActive(true);
                            ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.diceAnimation.dice.gameObject.GetComponent<RectTransform>()
                                .sizeDelta = new Vector3(85, 85);
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .diceNumberText
                                .text = userTurnStart.data.diceValue.ToString();
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .ludoNumbersUserData.animatorOnTurn.gameObject.SetActive(true);
                        }

                        if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                        {
                            if (
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .playerInfoData
                                    .playerSeatIndex == userTurnStart.data.startTurnSeatIndex
                                && userTurnStart.data.startTurnSeatIndex
                                    == socketNumberEventReceiver
                                        .signUpResponce
                                        .data
                                        .thisPlayerSeatIndex
                            )
                            {
                                ludoNumberUiManager
                                    .leavePanel.transform.GetChild(0)
                                    .transform.DOScale(Vector3.zero, 0.25f)
                                    .OnComplete(() =>
                                    {
                                        ludoNumberUiManager.leavePanel.SetActive(false);
                                        ludoNumberUiManager
                                            .leavePanel.GetComponent<Image>()
                                            .enabled = false;
                                    });

                                ludoNumberUiManager
                                    .helpPanel.transform.GetChild(0)
                                    .transform.DOScale(Vector3.zero, 0.25f)
                                    .OnComplete(() =>
                                    {
                                        ludoNumberUiManager.helpPanel.SetActive(false);
                                        ludoNumberUiManager
                                            .helpPanel.GetComponent<Image>()
                                            .enabled = false;
                                    });

                                Debug.Log(
                                    "userTurnStart.data.movesLeft = > "
                                        + userTurnStart.data.movesLeft
                                );
                                Debug.Log("move Number   = > " + moveNumber);

                                if (moveNumber == 1)
                                {
                                    if (
                                        socketNumberEventReceiver.userTurnStart.data.isExtraTurn
                                        == false
                                    )
                                    {
                                        lastMove
                                            .transform.DOScale(Vector3.one, 1f)
                                            .OnComplete(() =>
                                            {
                                                lastMove.transform.DOScale(Vector3.zero, 1f);
                                            });
                                    }
                                }
                                SoundManagerOffline.instance.SoundPlay(
                                    SoundManagerOffline.instance.trunAudio
                                );
                                SoundManagerOffline.instance.Vibration();
                                int number = userTurnStart.data.diceValue;
                                for (int j = 0; j < number; j++)
                                    movePositon.Add(
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .ludoNumbersPlayerHome
                                            .way_Point[i + 1]
                                    );

                                for (
                                    int j = 0;
                                    j
                                        < ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie
                                            .Count;
                                    j++
                                )
                                {
                                    ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .tokenAwayBG.gameObject.SetActive(false);
                                    if (
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                            + socketNumberEventReceiver
                                                .signUpResponce
                                                .data
                                                .playerMoves[
                                                24
                                                    - socketNumberEventReceiver
                                                        .userTurnStart
                                                        .data
                                                        .movesLeft
                                            ]
                                        <= 56
                                    )
                                    {
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie[j]
                                            .transform
                                            .localScale = new Vector3(1.1f, 1.1f, 1.1f);
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Canvas>()
                                            .sortingOrder += 5;
                                    }
                                    else
                                    {
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovement>().CoockieManage();
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie[j]
                                            .transform
                                            .localScale = new Vector3(1f, 1f, 1f);
                                    }
                                }
                            }
                            else
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.ring.ForEach(
                                        (coockie) => coockie.gameObject.SetActive(false)
                                    );
                        }
                        else
                        {
                            if (
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .playerInfoData
                                    .playerSeatIndex == userTurnStart.data.startTurnSeatIndex
                            )
                            {
                                ludoNumberUiManager
                                    .leavePanel.transform.GetChild(0)
                                    .transform.DOScale(Vector3.zero, 0.25f)
                                    .OnComplete(() =>
                                    {
                                        ludoNumberUiManager.leavePanel.SetActive(false);
                                        ludoNumberUiManager
                                            .leavePanel.GetComponent<Image>()
                                            .enabled = false;
                                    });

                                ludoNumberUiManager
                                    .helpPanel.transform.GetChild(0)
                                    .transform.DOScale(Vector3.zero, 0.25f)
                                    .OnComplete(() =>
                                    {
                                        ludoNumberUiManager.helpPanel.SetActive(false);
                                        ludoNumberUiManager
                                            .helpPanel.GetComponent<Image>()
                                            .enabled = false;
                                    });

                                if (
                                    moveNumber == 1
                                    && userTurnStart.data.startTurnSeatIndex
                                        == socketNumberEventReceiver
                                            .signUpResponce
                                            .data
                                            .thisPlayerSeatIndex
                                )
                                {
                                    if (
                                        socketNumberEventReceiver.userTurnStart.data.isExtraTurn
                                        == false
                                    )
                                    {
                                        lastMove
                                            .transform.DOScale(Vector3.one, 1f)
                                            .OnComplete(() =>
                                            {
                                                lastMove.transform.DOScale(Vector3.zero, 1f);
                                            });
                                    }
                                }

                                SoundManagerOffline.instance.SoundPlay(
                                    SoundManagerOffline.instance.trunAudio
                                );
                                SoundManagerOffline.instance.Vibration();
                                int number = userTurnStart.data.diceValue;
                                for (int j = 0; j < number; j++)
                                    movePositon.Add(
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .ludoNumbersPlayerHome
                                            .way_Point[i + 1]
                                    );

                                for (
                                    int j = 0;
                                    j
                                        < ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie
                                            .Count;
                                    j++
                                )
                                {
                                    ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData.playerCoockie[j]
                                        .GetComponent<CoockieMovementOffline>()
                                        .tokenAwayBG.gameObject.SetActive(false);

                                    switch (
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .playerInfoData
                                            .playerSeatIndex
                                    )
                                    {
                                        case 0:
                                            list = socketNumberEventReceiver
                                                .signUpResponce
                                                .data
                                                .playerMoves;
                                            break;
                                        case 1:
                                            list = socketNumberEventReceiver.botMovesList;
                                            break;
                                        case 2:
                                            list = socketNumberEventReceiver.botMovesList1;
                                            break;
                                        case 3:
                                            list = socketNumberEventReceiver.botMovesList2;
                                            break;

                                        default:
                                            break;
                                    }

                                    if (
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .GetComponent<CoockieMovementOffline>()
                                            .myLastBoxIndex
                                            + list[
                                                24
                                                    - socketNumberEventReceiver
                                                        .userTurnStart
                                                        .data
                                                        .movesLeft
                                            ]
                                        <= 56
                                    )
                                    {
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.DOKill();
                                        Debug.Log(
                                            "Cukii name User turn = > "
                                                + ludoNumbersAcknowledgementHandler
                                                    .ludoNumberPlayerControl[i]
                                                    .ludoNumbersUserData
                                                    .playerCoockie[j]
                                                    .name
                                        );
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Image>()
                                            .raycastTarget = true;
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(true);
                                        Debug.Log(
                                            "Cukii name User turn before Scale = > "
                                                + ludoNumbersAcknowledgementHandler
                                                    .ludoNumberPlayerControl[i]
                                                    .ludoNumbersUserData
                                                    .playerCoockie[j]
                                                    .transform
                                                    .localScale
                                        );
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie[j]
                                            .transform
                                            .localScale = new Vector3(1.1f, 1.1f, 1.1f);
                                        Debug.Log(
                                            "Cukii name User turn after Scale = > "
                                                + ludoNumbersAcknowledgementHandler
                                                    .ludoNumberPlayerControl[i]
                                                    .ludoNumbersUserData
                                                    .playerCoockie[j]
                                                    .transform
                                                    .localScale
                                        );
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetComponent<Canvas>()
                                            .sortingOrder += 5;
                                    }
                                    else
                                    {
                                        Debug.Log(
                                            "Cukii name User turn not possible = > "
                                                + ludoNumbersAcknowledgementHandler
                                                    .ludoNumberPlayerControl[i]
                                                    .ludoNumbersUserData
                                                    .playerCoockie[j]
                                                    .name
                                        );
                                        //  ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovement>().CoockieManage();
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData.playerCoockie[j]
                                            .transform.GetChild(0)
                                            .gameObject.SetActive(false);
                                        ludoNumbersAcknowledgementHandler
                                            .ludoNumberPlayerControl[i]
                                            .ludoNumbersUserData
                                            .playerCoockie[j]
                                            .transform
                                            .localScale = new Vector3(1f, 1f, 1f);
                                    }
                                }
                            }
                            else
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.ring.ForEach(
                                        (coockie) => coockie.gameObject.SetActive(false)
                                    );
                        }
                    }
                    isCukiKillNumberMode = false;
                }
                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                {
                    if (!DashBoardManagerOffline.instance.IsPassAndPlay)
                        BotTurnDiceAnimation();
                }
                else
                    Invoke(nameof(TurnChange), 1f);
            }
            catch (Exception ex)
            {
                Debug.Log(ex);
            }
        }

        private int[] timeArray = new int[] { 1, 2 };

        void BotTurnDiceAnimation()
        {
            int diceAnimationTime = timeArray[UnityEngine.Random.Range(0, timeArray.Length)];
            if (socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex != 0)
            {
                socketNumberEventReceiver.InvokeDiceAnimationFunction(diceAnimationTime);
            }
        }

        IEnumerator InfoPanelTime()
        {
            yield return new WaitForSeconds(2f);
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.infoBtn.transform.DOScale(Vector2.zero, 0.5f);
        }

        #endregion

        #region USERTURNSTARTREJOIN

        public void UserTurnStartReJoin(SignUpResponce signUpResponce)
        {
            settingBtn.interactable = true;
            ludoNumberUiManager.StopDecreaseCounter();
            ludoNumberUiManager.gameManager.gameState = GameState.playing;
            movePositon.Clear();
            //extraMove.transform.DOScale(Vector3.zero, 1f);
            if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("DICE"))
                plusPoint.transform.DOScale(Vector3.zero, 1f);
            if (
                moveNumber == 1
                && MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "NUMBER"
                )
            )
            {
                if (signUpResponce.data.userTurnDetails.isExtraTurn == false)
                {
                    lastMove
                        .transform.DOScale(Vector3.one, 1f)
                        .OnComplete(() =>
                        {
                            lastMove.transform.DOScale(Vector3.zero, 1f);
                            lastMove.SetActive(false);
                        });
                }
            }

            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.extraTimerImage.gameObject.SetActive(false);
                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                {
                    ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(false);
                }
                else
                {
                    ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumber.SetActive(false);
                }
                ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.SetActive(true);
                ludoNumberPlayerControl[i].ludoNumbersUserData.turnProfileBlink.SetActive(false);
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.diceAnimation.dice.gameObject.GetComponent<RectTransform>()
                    .sizeDelta = new Vector3(85, 85);
                ludoNumberPlayerControl[i].ludoNumbersUserData.animatorOnTurn.enabled = false;
                ludoNumberPlayerControl[i].ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                userTimer[i].AllPlayerTimerImage.GetComponent<Image>().fillAmount = 1;
                userTimer[i].TurnDataReset();
                OffSixNumberShow();
                if (
                    ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                    == signUpResponce.data.userTurnDetails.currentTurnSeatIndex
                )
                {
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.timeImage.gameObject.SetActive(true);
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.timeImage.GetComponent<UserTimerOffline>()
                        .Reapet(
                            signUpResponce.data.userTurnDetails.remainingTimer,
                            signUpResponce.data.turnTimer
                        );
                    if (
                        !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                            "NUMBER"
                        )
                    )
                    {
                        ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(true);
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.dice.GetComponent<Image>()
                            .sprite = diceValue;
                    }
                    else
                    {
                        ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumber.SetActive(true);
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.diceNumberText.gameObject.SetActive(true);
                        ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumberText.text =
                            signUpResponce.data.userTurnDetails.diceValue.ToString();
                    }
                    // ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.SetActive(false);
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.turnProfileBlink.SetActive(true);
                    ludoNumberPlayerControl[i].ludoNumbersUserData.animatorOnTurn.enabled = true;
                }

                if (
                    ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == signUpResponce.data.userTurnDetails.currentTurnSeatIndex
                    && ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == signUpResponce.data.thisPlayerSeatIndex
                )
                {
                    if (
                        !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                            "NUMBER"
                        )
                    )
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.turnTimeShowArrow.SetActive(true);
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData
                        .arrowAnimationOnTurnTime
                        .enabled = true;
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.diceAnimation.dice.transform.GetComponent<Image>()
                        .raycastTarget = true;
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockie.ForEach(
                            (coockie) =>
                                coockie.transform.GetComponent<Image>().raycastTarget = false
                        );
                }

                if (
                    MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                {
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.numberView.SetActive(true);
                    if (
                        socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                        == signUpResponce.data.userTurnDetails.currentTurnSeatIndex
                    )
                        if (
                            !socketNumberEventReceiver
                                .signUpResponce
                                .data
                                .userTurnDetails
                                .isExtraTurn
                        )
                            ludoNumberUiManager.HighlightCurrentMoveNumber();

                    if (
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .playerInfoData
                            .playerSeatIndex
                            == signUpResponce.data.userTurnDetails.currentTurnSeatIndex
                        && ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .playerInfoData
                            .playerSeatIndex == signUpResponce.data.thisPlayerSeatIndex
                    )
                    {
                        for (
                            int j = 0;
                            j
                                < ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData
                                    .playerCoockie
                                    .Count;
                            j++
                        )
                        {
                            if (
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .myLastBoxIndex
                                    + signUpResponce.data.playerMoves[
                                        24 - signUpResponce.data.movesLeft
                                    ]
                                <= 56
                            )
                            {
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Canvas>().sortingOrder +=
                                                5
                                    );
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.localScale = new Vector3(
                                                1.1f,
                                                1.1f,
                                                1.1f
                                            )
                                    );
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.GetComponent<Image>().raycastTarget =
                                                true
                                    );
                            }
                            else
                            {
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .GetComponent<CoockieMovementOffline>()
                                    .CoockieManage();
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie[j]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(false);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.playerCoockie.ForEach(
                                        (coockie) =>
                                            coockie.transform.localScale = new Vector3(1f, 1f, 1f)
                                    );
                            }
                        }
                    }
                }
            }
        }

        #endregion

        #region TURNMISS

        public void TurnMiss(TurnMiss turnMiss)
        {
            socketNumberEventReceiver.coockieMovement.CoockieManage();
            SoundManagerOffline.instance.TimeSoundStop(SoundManagerOffline.instance.timerAudio);
            if (
                MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                    "NUMBER"
                )
            )
            {
                if (
                    turnMiss.data.playerSeatIndex
                    == socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                )
                {
                    moveNumber = socketNumberEventReceiver.moveNumberAlltime - 1;
                    moveText.text = moveNumber.ToString();

                    if (!socketNumberEventReceiver.isExtraTurn)
                        ludoNumberUiManager.NumberAnimation();
                }
            }
            else
            {
                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                {
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData
                        .arrowAnimationOnTurnTime
                        .enabled = false;
                }
            }

            TurnMissCommon(turnMiss.data.totalTurnMissCounter);
            BattleFinish();
        }

        public void TurnMissCommon(int lives)
        {
            for (
                int i = 0;
                i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                i++
            )
            {
                if (
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .playerInfoData
                        .playerSeatIndex == socketNumberEventReceiver.turnMiss.data.playerSeatIndex
                )
                {
                    int abc = ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData
                        .lives
                        .Count;
                    for (int j = 0; j < abc; j++)
                    {
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.lives[j]
                            .transform.GetChild(0)
                            .gameObject.SetActive(j < lives);
                        ludoNumbersAcknowledgementHandler
                            .ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.boxList[j]
                            .SetActive(j < lives);
                    }
                    for (
                        int j = 0;
                        j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count;
                        j++
                    )
                    {
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.playerCoockie[j]
                            .transform.GetChild(0)
                            .gameObject.SetActive(false);
                    }
                    for (
                        int j = 0;
                        j
                            < ludoNumberPlayerControl[i]
                                .ludoNumbersUserData
                                .playerCoockieForClassicMode
                                .Count;
                        j++
                    )
                    {
                        ludoNumberPlayerControl[i]
                            .ludoNumbersUserData.playerCoockieForClassicMode[j]
                            .transform.GetChild(0)
                            .gameObject.SetActive(false);
                    }

                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.infoBtn.transform.DOScale(Vector2.one, 0.2f);

                    if (lives >= 3)
                    {
                        EliminateSeat(socketNumberEventReceiver.turnMiss.data.playerSeatIndex);
                    }
                }
            }
        }

        #endregion

        #region TURNMISSREJOIN

        public void TurnMissReJoin(SignUpResponce signUpResponce)
        {
            SoundManagerOffline.instance.TimeSoundStop(SoundManagerOffline.instance.timerAudio);
            for (int j = 0; j < signUpResponce.data.playerInfo.Count; j++)
            {
                if (signUpResponce.data.playerInfo[j].missedTurnCount == 1)
                {
                    for (
                        int i = 0;
                        i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                        i++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex == signUpResponce.data.playerInfo[j].seatIndex
                        )
                        {
                            for (
                                int a = 0;
                                a
                                    < ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                a++
                            )
                            {
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[0]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[1]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(false);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[2]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(false);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.fristBox.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.secondBox.SetActive(false);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.thirdBox.SetActive(false);
                            }
                        }
                    }
                }
                else if (signUpResponce.data.playerInfo[j].missedTurnCount == 2)
                {
                    for (
                        int i = 0;
                        i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                        i++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex == signUpResponce.data.playerInfo[j].seatIndex
                        )
                        {
                            for (
                                int a = 0;
                                a
                                    < ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                a++
                            )
                            {
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[0]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[1]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[2]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(false);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.fristBox.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.secondBox.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.thirdBox.SetActive(false);
                            }
                        }
                    }
                }
                else if (signUpResponce.data.playerInfo[j].missedTurnCount == 3)
                {
                    for (
                        int i = 0;
                        i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                        i++
                    )
                    {
                        if (
                            ludoNumbersAcknowledgementHandler
                                .ludoNumberPlayerControl[i]
                                .playerInfoData
                                .playerSeatIndex == signUpResponce.data.playerInfo[j].seatIndex
                        )
                        {
                            for (
                                int a = 0;
                                a
                                    < ludoNumbersAcknowledgementHandler
                                        .ludoNumberPlayerControl[i]
                                        .ludoNumbersUserData
                                        .playerCoockie
                                        .Count;
                                a++
                            )
                            {
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[0]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[1]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.lives[2]
                                    .transform.GetChild(0)
                                    .gameObject.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.fristBox.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.secondBox.SetActive(true);
                                ludoNumbersAcknowledgementHandler
                                    .ludoNumberPlayerControl[i]
                                    .ludoNumbersUserData.thirdBox.SetActive(true);
                            }
                        }
                    }
                }
            }
        }

        #endregion

        #region LEAVETABLE

        public void LeaveTable(LeaveTableEvent leaveTableEvent)
        {
            for (
                int i = 0;
                i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length;
                i++
            )
            {
                if (
                    leaveTableEvent.data.playerSeatIndex
                    == ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .playerInfoData
                        .playerSeatIndex
                )
                {
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .gameObject.SetActive(false);
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockie.ForEach(
                            (cookie) => cookie.transform.SetParent(tokenKill.transform)
                        );
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockie.ForEach(
                            (cookie) => cookie.SetActive(false)
                        );
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.playerCoockieForClassicMode.ForEach(
                            (cookie) => cookie.SetActive(false)
                        );
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.turnProfileBlink.SetActive(false);
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.leaveTableImage.SetActive(true);
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.smallRoundImage.SetActive(false);
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.scoreBox.SetActive(false);
                }

                if (
                    ludoNumbersAcknowledgementHandler
                        .ludoNumberPlayerControl[i]
                        .playerInfoData
                        .playerSeatIndex == leaveTableEvent.data.playerSeatIndex
                    && leaveTableEvent.data.playerSeatIndex
                        == socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex
                )
                    GameManagerOffline.instace.OnClickExit();
            }
        }

        #endregion

        #region EXTRATIME

        public void ExtraTimer(string stringResponce)
        {
            SoundManagerOffline.instance.TimeSoundStop(SoundManagerOffline.instance.timerAudio);
            extraTime = JsonUtility.FromJson<ExtraTime>(stringResponce);
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                userTimer[i].TurnDataReset();
                userTimer[i].AllPlayerTimerImage.GetComponent<Image>().fillAmount = 1;
                if (
                    ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == extraTime.data.startTurnSeatIndex
                    && ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex != -1
                )
                {
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.extraTimerImage.gameObject.SetActive(true);
                    userTimer[i]
                        .Reapet(
                            extraTime.data.remainingTimer,
                            socketNumberEventReceiver.signUpResponce.data.extraTimer
                        );
                }
            }
        }

        #endregion

        #region EXTRATIMEREJOIN

        public void ExtraTimerReJoin(SignUpResponce signUpResponce)
        {
            SoundManagerOffline.instance.TimeSoundStop(SoundManagerOffline.instance.timerAudio);
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                userTimer[i].TurnDataReset();
                userTimer[i].AllPlayerTimerImage.GetComponent<Image>().fillAmount = 1;
                if (
                    ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex
                        == socketNumberEventReceiver
                            .signUpResponce
                            .data
                            .userTurnDetails
                            .currentTurnSeatIndex
                    && ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex != -1
                )
                {
                    ludoNumberPlayerControl[i]
                        .ludoNumbersUserData.extraTimerImage.gameObject.SetActive(true);
                    userTimer[i]
                        .Reapet(
                            signUpResponce.data.userTurnDetails.remainingTimer,
                            signUpResponce.data.extraTimer
                        );
                }
            }
        }

        #endregion

        #region ResetGamne
        public void ResetGame()
        {
            eliminatedSeatIndices.Clear();
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.ring.ForEach(
                        (coockie) => coockie.gameObject.SetActive(false)
                    );
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.playerCoockie.ForEach(
                        (coockie) => coockie.gameObject.SetActive(false)
                    );
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.ring.ForEach(
                        (coockie) => coockie.gameObject.SetActive(false)
                    );
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.playerCoockieForClassicMode.ForEach(
                        (coockie) => coockie.gameObject.SetActive(false)
                    );
                userTimer[i].TurnDataReset();

                if (
                    !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals(
                        "NUMBER"
                    )
                )
                    ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(false);
                else
                    ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumber.SetActive(false);

                ludoNumberPlayerControl[i].ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                ludoNumberPlayerControl[i].ludoNumbersUserData.arrowAnimationOnTurnTime.enabled =
                    false;
                ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.SetActive(true);
                ludoNumberPlayerControl[i].ludoNumbersUserData.turnProfileBlink.SetActive(false);
                ludoNumberPlayerControl[i].ludoNumbersUserData.animatorOnTurn.enabled = false;
                ludoNumberPlayerControl[i]
                    .ludoNumbersUserData.diceNumberText.gameObject.SetActive(false);
                // logoImage.SetActive(false);
                networkIndicator.SetActive(false);
            }
        }
        #endregion
    }
}
