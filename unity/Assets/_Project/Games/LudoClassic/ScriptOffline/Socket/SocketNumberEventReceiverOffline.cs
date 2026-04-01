using Best.SocketIO;
using Best.SocketIO.Events;
using DG.Tweening;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using UnityEngine;
using UnityEngine.UI;
using static LudoClassicOffline.LudoNumberEmojiDataClass;
using static LudoClassicOffline.SignUpResponceClass;

namespace LudoClassicOffline
{
    public class SocketNumberEventReceiverOffline : MonoBehaviour
    {
        public List<int> selfPlayerMovesList;
        public List<int> botMovesList, botMovesList1, botMovesList2;
        public bool isMovesOver;

        public SocketConnectionOffline socketConnection;
        public LudoNumberGsNewOffline ludoNumberGsNew;
        public CoockieMovementOffline coockieMovement;
        public LudoNumbersAcknowledgementHandlerOffline ludoNumbersAcknowledgementHandler;
        public SignUpResponce signUpResponce;
        public JoinTableResponse joinTableResponse;
        public GameTimeStartData gameTimeStartData;
        public UserTimeStart userTurnStart;
        public TokenMove moveToken;
        public AlertMessage alertMessage;
        public BattleFinish battleFinish;
        public AbleToReconnect ableToReconnect;
        public LudoNumbersUserData ludoNumbersUserData;
        public TieBreakerModel tieBreaker;
        public TurnMiss turnMiss;
        public List<LudoNumberBattleFinish> ludoNumberBattleFinish = new List<LudoNumberBattleFinish>();
        public LeaveTableEvent leaveTableEvent;
        public PopUp popUp;
        public ScoreViewRes scoreViewRes;
        public int userStartIndex;
        public GameMainTimer gameMainTimer;
        public DiceAnimationResponce diceAnimationResponce;
        public int maxPlayer;
        public int diceValue;
        public int sixCount = 0;
        public bool isSix;
        public int userTurnCount;
        public EmojiResponse emojiResponse;
        public bool isExtraTurn;
        public int moveNumberAlltime;

        public int entryFee;
        public int winAmt;

        public int sixValueCount;
        public int tiePlayerCount;
        public bool isTurnsStart;

        public void ReciveData(string responseJsonString)
        {
            JObject responseJsonData = JObject.Parse(responseJsonString.ToString());
            string en = responseJsonData.GetValue("en").ToString();
            Debug.Log("<color><b>" + en + "</b></color><color=blue> || On Response : </color> " +
                      responseJsonString.ToString());

            switch (en)
            {
                case "CONNECTION_SUCCESS":
                    socketConnection.OnSocketConnected();
                    if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                    {
                        SetCookiePosition();
                    }
                    break;
                case "JOIN_TABLE":
                    joinTableResponse = JsonUtility.FromJson<JoinTableResponse>(responseJsonString);
                    maxPlayer = joinTableResponse.data.maxPlayerCount;
                    ludoNumbersAcknowledgementHandler.JoinTableAcknowledged();
                    break;
                case "GAME_TIMER_START":
                    gameTimeStartData = JsonUtility.FromJson<GameTimeStartData>(responseJsonString);
                    //ludoNumberGsNew.GameTimerStart(responseJsonString);
                    break;
                case "MAIN_GAME_TIMER_START":
                    gameMainTimer = JsonUtility.FromJson<GameMainTimer>(responseJsonString);
                    ludoNumberGsNew.MainTimer(gameMainTimer.data.waitingTimer);
                    break;
                case "USER_TURN_START":
                    userTurnStart = JsonUtility.FromJson<UserTimeStart>(responseJsonString);
                    userTurnCount = userTurnStart.data.userTurnCount;
                    ludoNumberGsNew.UserTurnStart(userTurnStart);
                    userStartIndex = userTurnStart.data.startTurnSeatIndex;
                    diceValue = userTurnStart.data.diceValue;
                    moveNumberAlltime = userTurnStart.data.movesLeft;
                    isExtraTurn = userTurnStart.data.isExtraTurn;
                    break;
                case "USER_EXTRA_TIME_START":
                    ludoNumberGsNew.ExtraTimer(responseJsonData.ToString());
                    break;
                case "TURN_MISSED":
                    turnMiss = JsonUtility.FromJson<TurnMiss>(responseJsonString);
                    ludoNumberGsNew.TurnMiss(turnMiss);
                    break;
                case "BATTLE_FINISH":
                    battleFinish = JsonUtility.FromJson<BattleFinish>(responseJsonString);
                    ludoNumberGsNew.Battle();
                    break;
                case "ALERT_POPUP":
                    alertMessage = JsonUtility.FromJson<AlertMessage>(responseJsonString);
                    ludoNumberGsNew.Alert();
                    break;
                case "LEAVE_TABLE":
                    leaveTableEvent = JsonUtility.FromJson<LeaveTableEvent>(responseJsonString);
                    ludoNumberGsNew.LeaveTable(leaveTableEvent);
                    break;
                case "MOVE_TOKEN":
                    moveToken = JsonUtility.FromJson<TokenMove>(responseJsonString);
                    ludoNumberGsNew.TokenMove();
                    break;
                case "TIE_BREAKER":
                    //  TieBreaker(responseJsonString);
                    break;
                case "HEART_BEAT":
                    //ludoNumberGsNew.heartBeatManager.OnReceiveHB(new JSONObject(responseJsonString));
                    break;
                case "HEART_BEAT_CLIENT":
                    //ludoNumberGsNew.heartBeatManager.OnReceiveHBClient(new JSONObject(responseJsonString));
                    break;
                case "SCORE_CHECK":
                    scoreViewRes = JsonUtility.FromJson<ScoreViewRes>(responseJsonString);
                    ludoNumberGsNew.ScoreCheck();
                    break;
                case "SHOW_POPUP":
                    popUp = JsonUtility.FromJson<PopUp>(responseJsonString);
                    ludoNumberGsNew.ShowPopUp(popUp);
                    break;
                case "EMOJI":
                    emojiResponse = JsonUtility.FromJson<EmojiResponse>(responseJsonString);
                    // ludoNumberGsNew.EmojiSet(emojiResponse);
                    Debug.Log("Emoji Added => " + responseJsonString);
                    break;
                case "DICE_ANIMATION_STARTED":
                    diceAnimationResponce = JsonUtility.FromJson<DiceAnimationResponce>(responseJsonString);
                    isSix = diceAnimationResponce.data.isSix;
                    for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
                    {
                        if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
                        {
                            ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.ForEach((coockie) => coockie.transform.GetComponent<Image>().raycastTarget = false);
                        }

                        if (diceAnimationResponce.data.startTurnSeatIndex == ludoNumberGsNew.ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex)
                            ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.diceAnimation.DiceAnimationStart(diceAnimationResponce.data.diceValue, diceAnimationResponce.data.startTurnSeatIndex);
                    }

                    break;
                default:
                    break;
            }
        }

        void SetCookiePosition()
        {
            ludoNumberGsNew.ludoNumberPlayerControl[0].ludoNumbersUserData.playerCoockie[0].transform.localPosition = new Vector3(-20, 15, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[0].ludoNumbersUserData.playerCoockie[1].transform.localPosition = new Vector3(-7, 15, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[0].ludoNumbersUserData.playerCoockie[2].transform.localPosition = new Vector3(7, 15, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[0].ludoNumbersUserData.playerCoockie[3].transform.localPosition = new Vector3(20, 15, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[1].ludoNumbersUserData.playerCoockie[0].transform.localPosition = new Vector3(-363.3f, 502.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[1].ludoNumbersUserData.playerCoockie[1].transform.localPosition = new Vector3(-350.3f, 502.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[1].ludoNumbersUserData.playerCoockie[2].transform.localPosition = new Vector3(-336.3f, 502.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[1].ludoNumbersUserData.playerCoockie[3].transform.localPosition = new Vector3(-323.3f, 502.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[2].ludoNumbersUserData.playerCoockie[0].transform.localPosition = new Vector3(118.7f, 846.95f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[2].ludoNumbersUserData.playerCoockie[1].transform.localPosition = new Vector3(131.7f, 846.95f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[2].ludoNumbersUserData.playerCoockie[2].transform.localPosition = new Vector3(145.7f, 846.95f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[2].ludoNumbersUserData.playerCoockie[3].transform.localPosition = new Vector3(158.7f, 846.95f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[3].ludoNumbersUserData.playerCoockie[0].transform.localPosition = new Vector3(465.2f, 357.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[3].ludoNumbersUserData.playerCoockie[1].transform.localPosition = new Vector3(478.2f, 357.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[3].ludoNumbersUserData.playerCoockie[2].transform.localPosition = new Vector3(492.2f, 357.25f, 0);
            ludoNumberGsNew.ludoNumberPlayerControl[3].ludoNumbersUserData.playerCoockie[3].transform.localPosition = new Vector3(505.2f, 357.25f, 0);

            for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
            {
                for (int j = 0; j < ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count; j++)
                {
                    ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].transform.localScale = new Vector3(0.6f, 0.6f, 1);
                }
            }
        }

        public void PlayerJoinData()
        {
            try
            {


                if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
                {
                    for (int i = 0; i < 24; i++)
                    {
                        selfPlayerMovesList.Add(GenerateList());
                    }
                    botMovesList = selfPlayerMovesList.OrderBy(a => Guid.NewGuid()).ToList();
                    botMovesList1 = selfPlayerMovesList.OrderBy(a => Guid.NewGuid()).ToList();
                    botMovesList2 = selfPlayerMovesList.OrderBy(a => Guid.NewGuid()).ToList();
                    signUpResponce.data.playerMoves = selfPlayerMovesList;
                    userTurnStart.data.movesLeft = 24;

                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.MoveLeftGameObject.SetActive(true);
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.timerGameObject.SetActive(false);
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.numberViewScreenGameObject.SetActive(true);
                    for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                    {
                        ludoNumbersAcknowledgementHandler.ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(false);
                    }
                    for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
                    {
                        ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.ring.ForEach((coockie) => coockie.gameObject.SetActive(false));
                        ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((coockie) => coockie.gameObject.SetActive(false));
                    }
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.NumberGeneration(selfPlayerMovesList);
                    /* Debug.Log("ENTER");
                     for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberUiManager.scoreGameObjectList.Count; i++)
                     {
                        ludoNumbersAcknowledgementHandler.ludoNumberUiManager.scoreGameObjectList[i].SetActive(true);
                     }
                     ludoNumberUiManager.timerGameObject.SetActive(false);
                     for (int i = 0; i < ludoNumberUiManager.diesGameObjectList.Count; i++)
                     {
                         ludoNumberUiManager.diesGameObjectList[i].SetActive(false);
                     }
                     //for (int i = 0; i < ludoNumberUiManager.moveShowGameObjectList.Count; i++)
                     //{
                     //    ludoNumberUiManager.moveShowGameObjectList[i].SetActive(true);
                     //}*/
                }
                else if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("DICE"))
                {



                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.MoveLeftGameObject.SetActive(false);
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.timerGameObject.SetActive(true);
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.numberViewScreenGameObject.SetActive(false);
                    for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                    {
                        ludoNumbersAcknowledgementHandler.ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(false);
                    }
                    for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
                    {
                        ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.ring.ForEach((coockie) => coockie.gameObject.SetActive(false));
                        ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((coockie) => coockie.gameObject.SetActive(false));
                    }
                }
                else
                {
                    Debug.Log("Closed");
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.timerGameObject.SetActive(false);

                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.MoveLeftGameObject.SetActive(false);
                    ludoNumbersAcknowledgementHandler.ludoNumberUiManager.numberViewScreenGameObject.SetActive(false);
                    for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                    {
                        ludoNumbersAcknowledgementHandler.ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(true);
                    }
                    for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberUiManager.scoreGameObjectList.Count; i++)
                    {
                        ludoNumbersAcknowledgementHandler.ludoNumberUiManager.scoreGameObjectList[i].SetActive(false);
                    }
                    for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
                    {
                        ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.ring.ForEach((coockie) => coockie.gameObject.SetActive(false));
                        ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.ForEach((coockie) => coockie.gameObject.SetActive(false));
                    }

                }

                if (joinTableResponse.data.maxPlayerCount == 4)
                {
                    joinTableResponse.data.playerInfo[0].playerSeatIndex = 0;
                    joinTableResponse.data.playerInfo[0].userId = "0";
                    joinTableResponse.data.playerInfo[0].username = LudoDisplayNameUtility.LocalPlayerLabel();
                    joinTableResponse.data.playerInfo[0].userProfile = "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                    signUpResponce.data.thisPlayerSeatIndex = joinTableResponse.data.playerInfo[0].playerSeatIndex;

                    joinTableResponse.data.playerInfo[1].playerSeatIndex = 1;
                    joinTableResponse.data.playerInfo[1].userId = "1";
                    joinTableResponse.data.playerInfo[1].username = LudoDisplayNameUtility.NeutralSeatLabel(1);
                    joinTableResponse.data.playerInfo[1].userProfile = "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";

                    joinTableResponse.data.playerInfo[2].playerSeatIndex = 2;
                    joinTableResponse.data.playerInfo[2].userId = "2";
                    joinTableResponse.data.playerInfo[2].username = LudoDisplayNameUtility.NeutralSeatLabel(2);
                    joinTableResponse.data.playerInfo[2].userProfile = "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";

                    joinTableResponse.data.playerInfo[3].playerSeatIndex = 3;
                    joinTableResponse.data.playerInfo[3].userId = "3";
                    joinTableResponse.data.playerInfo[3].username = LudoDisplayNameUtility.NeutralSeatLabel(3);
                    joinTableResponse.data.playerInfo[3].userProfile = "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                }
                else
                {

                    joinTableResponse.data.playerInfo[0].playerSeatIndex = 0;
                    joinTableResponse.data.playerInfo[0].userId = "0";
                    joinTableResponse.data.playerInfo[0].username = LudoDisplayNameUtility.LocalPlayerLabel();
                    joinTableResponse.data.playerInfo[0].userProfile = "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                    signUpResponce.data.thisPlayerSeatIndex = joinTableResponse.data.playerInfo[0].playerSeatIndex;

                    joinTableResponse.data.playerInfo[1].playerSeatIndex = 1;
                    joinTableResponse.data.playerInfo[1].userId = "1";
                    joinTableResponse.data.playerInfo[1].username = LudoDisplayNameUtility.NeutralSeatLabel(1);
                    joinTableResponse.data.playerInfo[1].userProfile = "https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png";
                }
                ChangeCukiSeatIndex();
                Debug.Log("Radhe Radhe => " + joinTableResponse.data.maxPlayerCount);
                maxPlayer = joinTableResponse.data.maxPlayerCount;
                ludoNumbersAcknowledgementHandler.JoinTableAcknowledged();
                ludoNumberGsNew.GameTimerStart(5); //6
                userTurnStart.data.startTurnSeatIndex = 0;// UnityEngine.Random.Range(0, 2);
            }
            catch (Exception ex)
            {

                Debug.Log(ex);
            }
        }

        public void ChangeCukiSeatIndex()
        {
            if (joinTableResponse.data.maxPlayerCount == 4)
            {
                for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
                {
                    for (int j = 0; j < 4; j++)
                    {
                        if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                            ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockieForClassicMode[j].GetComponent<CoockieMovementOffline>().seatIndex = i;
                        else
                            ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovementOffline>().seatIndex = i;
                    }
                }
            }
            else
            {

                for (int j = 0; j < 4; j++)
                {
                    if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                        ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[2].ludoNumbersUserData.playerCoockieForClassicMode[j].GetComponent<CoockieMovementOffline>().seatIndex = 1;
                    else
                        ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[2].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovementOffline>().seatIndex = 1;
                }

            }
        }

        public int GenerateList()
        {
            int no = UnityEngine.Random.Range(1, 7);
            return no;
        }


        public void StartUserTurn()
        {
            ludoNumberGsNew.extraMove.transform.DOScale(Vector3.zero, 0.1f);
            userTurnStart.data.isExtraTurn = false;
            userStartIndex = userTurnStart.data.startTurnSeatIndex;
            DiceValueGenerate();
            diceValue = userTurnStart.data.diceValue;
            moveNumberAlltime = userTurnStart.data.movesLeft;
            isExtraTurn = userTurnStart.data.isExtraTurn;
            ludoNumberGsNew.UserTurnStart(userTurnStart);
        }

        public List<int> diceNumber = new List<int>();
        private object aiController;

        private void DiceValueGenerate()
        {
            if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
            {
                if (moveToken.data.isCapturedToken || ludoNumberGsNew.isTokenReachHome)
                {
                    Debug.Log("Enter cuki kill");
                    userTurnStart.data.diceValue = UnityEngine.Random.Range(1, 7);
                }
                else
                {
                    if (joinTableResponse.data.maxPlayerCount == 4)
                    {

                        switch (userStartIndex)
                        {
                            case 0:
                                userTurnCount++;
                                userTurnStart.data.movesLeft = 24 - userTurnCount;
                                userTurnStart.data.diceValue = selfPlayerMovesList[userTurnCount];
                                break;

                            case 1:
                                userTurnStart.data.diceValue = botMovesList[userTurnCount];
                                break;

                            case 2:
                                userTurnStart.data.diceValue = botMovesList1[userTurnCount];
                                break;

                            case 3:
                                userTurnStart.data.diceValue = botMovesList2[userTurnCount];
                                if (userTurnStart.data.movesLeft == 1)
                                    isMovesOver = true;
                                break;
                            default:
                                break;
                        }

                        //if (userStartIndex == 0)
                        //{
                        //    userTurnCount++;
                        //    userTurnStart.data.movesLeft = 24 - userTurnCount;
                        //    userTurnStart.data.diceValue = selfPlayerMovesList[userTurnCount];
                        //}
                        //else if (userStartIndex == 3)
                        //{
                        //    userTurnStart.data.diceValue = botMovesList[userTurnCount];
                        //    if (userTurnStart.data.movesLeft == 1)
                        //        isMovesOver = true;
                        //}
                        //else
                        //{
                        //    userTurnStart.data.diceValue = botMovesList[userTurnCount];
                        //}
                    }
                    else
                    {
                        if (userStartIndex == 0)
                        {
                            userTurnCount++;
                            userTurnStart.data.movesLeft = 24 - userTurnCount;
                            userTurnStart.data.diceValue = selfPlayerMovesList[userTurnCount];
                        }
                        else
                        {
                            userTurnStart.data.diceValue = botMovesList[userTurnCount];
                            if (userTurnStart.data.movesLeft == 1)
                                isMovesOver = true;
                        }
                    }
                }
            }
            else
            {
                if (diceNumber.Count == 10)
                {
                    if (!diceNumber.Contains(6)) userTurnStart.data.diceValue = 6;
                    diceNumber.Clear();
                }
                else
                {
                    if (sixValueCount < 1)
                        userTurnStart.data.diceValue = UnityEngine.Random.Range(1, 7);
                    else
                        userTurnStart.data.diceValue = UnityEngine.Random.Range(1, 6);

                    if (userTurnStart.data.diceValue == 6)
                        sixValueCount++;
                }
                diceNumber.Add(userTurnStart.data.diceValue);
            }
            moveToken.data.isCapturedToken = false;
            ludoNumberGsNew.isTokenReachHome = false;
        }

        public void DiceAnimationStart()
        {
            isSix = (diceValue == 6) ? true : false;

            Debug.Log(" DiceAnimation Start ||   DiceVAlue  => " + diceValue);
            Debug.Log(" DiceAnimation Start || Before || SIX  => " + sixCount);
            if (isSix)
            {
                sixCount = sixCount + 1;
            }
            else
            {
                sixCount = 0;
            }
            Debug.Log(" DiceAnimation Start || After || SIX  => " + sixCount);
            for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
            {
                if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
                {
                    ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie
                        .ForEach((coockie) => coockie.transform.GetComponent<Image>().raycastTarget = false);
                }

                if (userTurnStart.data.startTurnSeatIndex == ludoNumberGsNew.ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex)
                    ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.diceAnimation
                        .DiceAnimationStart(diceValue, userTurnStart.data.startTurnSeatIndex);
            }
        }

        public void InvokeDiceAnimationFunction(int time)
        {
            Debug.Log("Call dice Animation function = > InvokeDiceAnimationFunction || From Bot");
            Invoke(nameof(DiceAnimationStart), time);
        }

        #region TIEBREAKER

        public void TieBreaker()
        {
            //  Debug.Log("-----> DATA OF TIE BREAKER " + data);


            ludoNumberGsNew.tieBreakerBgNumberMode.SetActive(true);

            for (int i = 0; i < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
            {
                for (int j = 0; j < ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count; j++)
                {
                    ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.turnProfileBlink.SetActive(false);
                    ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.timeImage.gameObject.SetActive(false);

                    if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
                        ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.dice.SetActive(false);
                    else
                        ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.diceNumber.SetActive(false);

                    ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                    ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.artoonLogo.SetActive(true);
                    ludoNumberGsNew.userTimer[i].TurnDataReset();
                    ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].transform.GetChild(0).gameObject.SetActive(false);
                }
            }


            var player = ludoNumberGsNew.ReturnPlayerFromSeatIndex(tieBreaker.data.userData[0].seatIndex);
            var player2 = ludoNumberGsNew.ReturnPlayerFromSeatIndex(tieBreaker.data.userData[1].seatIndex);

            //for 4 player
            Debug.Log("Max Player Count => " + maxPlayer);
            if (maxPlayer == 3 && tieBreaker.data.userData.Count > 2)
            {
                Debug.Log("Max Player Count => " + maxPlayer);

                LudoNumberPlayerHomeOffline home4 = null;
                var player3 =
                    ludoNumberGsNew.ReturnPlayerFromSeatIndex(tieBreaker.data.userData[2].seatIndex);
                if (tieBreaker.data.userData.Count > 3)
                {
                    var player4 =
                        ludoNumberGsNew.ReturnPlayerFromSeatIndex(tieBreaker.data.userData[3].seatIndex);
                    home4 = player4.PlayerHome;
                }

                var home = player.PlayerHome;
                var home2 = player2.PlayerHome;
                var home3 = player3.PlayerHome;

                List<int> userTokenIndex = new List<int>();
                List<int> userTokenPosition = new List<int>();

                for (int i = 0; i < tieBreaker.data.userData.Count; i++)
                {
                    userTokenIndex.Add(tieBreaker.data.userData[i].tokenIndex);
                    userTokenPosition.Add(tieBreaker.data.userData[i].furthestToken);
                }

                var isCancelToken = tieBreaker.data.isCancelToken;
                var isPlayer1FurthestTokenLeft = false;
                var isPlayer2FurthestTokenLeft = false;
                var isPlayer3FurthestTokenLeft = false;
                var isPlayer4FurthestTokenLeft = false;


                if (home.gameObject.name == "1")
                {
                    isPlayer1FurthestTokenLeft = (userTokenIndex[0] > 8 && userTokenIndex[0] < 14);
                }
                else if (home.gameObject.name == "2")
                {
                    isPlayer2FurthestTokenLeft = (userTokenIndex[1] > 47 && userTokenIndex[1] < 52);
                }

                if (home2.gameObject.name == "3")
                {
                    isPlayer3FurthestTokenLeft = (userTokenIndex[2] > 34 && userTokenIndex[2] < 40);
                }
                else if (home2.gameObject.name == "4")
                {
                    isPlayer4FurthestTokenLeft = (userTokenIndex[3] > 21 && userTokenIndex[3] < 27);
                }

                var winplayerhome = ludoNumberGsNew.ReturnPlayerFromSeatIndex(tieBreaker.data.winnerIndex);

                if (tieBreaker.data.userData.Count == 3)
                {
                    home.AnimateLine(userTokenPosition[0], userTokenIndex[0], isPlayer1FurthestTokenLeft,
                        () =>
                        {
                            home2.AnimateLine(userTokenPosition[1], userTokenIndex[1], isPlayer2FurthestTokenLeft,
                                () =>
                                {
                                    home3.AnimateLine(userTokenPosition[2], userTokenIndex[2],
                                        isPlayer3FurthestTokenLeft,
                                        () =>
                                        {
                                            winplayerhome.PlayerHome.BlinkLine(() =>
                                            {
                                                ludoNumberGsNew.ShowExtraScoreAnim(winplayerhome.userName.text);
                                            });
                                        });
                                });
                        });
                }
                else
                {
                    var tokenIDPlayer1 = tieBreaker.data.userData[0].tokenIndex;
                    var tokenIDPlayer2 = tieBreaker.data.userData[1].tokenIndex;
                    var tokenIDPlayer3 = tieBreaker.data.userData[2].tokenIndex;
                    var tokenIDPlayer4 = tieBreaker.data.userData[3].tokenIndex;



                    home.AnimateLine(userTokenPosition[0], userTokenIndex[0], isPlayer1FurthestTokenLeft,
                        () =>
                        {
                            home2.AnimateLine(userTokenPosition[1], userTokenIndex[1], isPlayer2FurthestTokenLeft,
                                () =>
                                {
                                    home3.AnimateLine(userTokenPosition[2], userTokenIndex[2],
                                        isPlayer3FurthestTokenLeft,
                                        () =>
                                        {
                                            home4.AnimateLine(userTokenPosition[3], userTokenIndex[3],
                                                isPlayer4FurthestTokenLeft,
                                                () =>
                                                {
                                                    winplayerhome.PlayerHome.BlinkLine(() =>
                                                    {
                                                        if (tokenIDPlayer1 == tokenIDPlayer2 && tokenIDPlayer3 == tokenIDPlayer4 && tokenIDPlayer1 == tokenIDPlayer3)
                                                        {

                                                            ludoNumberGsNew.isTie = true;
                                                            ludoNumberGsNew.BattleFinishUserData();
                                                        }
                                                        else
                                                            ludoNumberGsNew.ShowExtraScoreAnim(winplayerhome.userName
                                                              .text);
                                                    });
                                                });
                                        });
                                });
                        });
                }
            }
            else
            {
                var home = player.PlayerHome;
                var home2 = player2.PlayerHome;
                var tokenIDPlayer1 = tieBreaker.data.userData[0].tokenIndex;
                var tokenIDPlayer2 = tieBreaker.data.userData[1].tokenIndex;
                var tokenPositionPlayer1 = tieBreaker.data.userData[0].furthestToken;
                var tokenPositionPlayer2 = tieBreaker.data.userData[1].furthestToken;
                var isCancelToken = tieBreaker.data.isCancelToken;
                var isPlayer1FurthestTokenLeft = false;
                var isPlayer2FurthestTokenLeft = false;
                Debug.Log("tokenPositionPlayer1 = >" + tieBreaker.data.userData[0].furthestToken);
                Debug.Log("tokenPositionPlayer2 = >" + tieBreaker.data.userData[1].furthestToken);
                if (home.gameObject.name == "1")
                {
                    isPlayer1FurthestTokenLeft = (tokenPositionPlayer1 > 8 && tokenPositionPlayer1 < 14);
                }
                else if (home.gameObject.name == "2")
                {
                    isPlayer1FurthestTokenLeft = (tokenPositionPlayer1 > 47 && tokenPositionPlayer1 < 52);
                }

                else if (home2.gameObject.name == "3")
                {
                    isPlayer2FurthestTokenLeft = (tokenPositionPlayer2 > 34 && tokenPositionPlayer2 < 40);
                }
                else if (home.gameObject.name == "4")
                {
                    isPlayer2FurthestTokenLeft = (tokenPositionPlayer2 > 21 && tokenPositionPlayer2 < 27);
                }

                if (home2.gameObject.name == "3")
                {
                    isPlayer2FurthestTokenLeft = (tokenPositionPlayer2 > 34 && tokenPositionPlayer2 < 40);
                }
                else if (home2.gameObject.name == "1")
                {
                    isPlayer2FurthestTokenLeft = (tokenPositionPlayer2 > 8 && tokenPositionPlayer2 < 14);
                }
                else if (home2.gameObject.name == "2")
                {
                    isPlayer2FurthestTokenLeft = (tokenPositionPlayer2 > 47 && tokenPositionPlayer2 < 52);
                }
                else if (home2.gameObject.name == "4")
                {
                    isPlayer2FurthestTokenLeft = (tokenPositionPlayer2 > 21 && tokenPositionPlayer2 < 27);
                }

                if (isCancelToken)
                {
                    ludoNumberGsNew.ShowTokenHomeAnim();
                }
                if (tokenPositionPlayer1 == tokenPositionPlayer2)
                {

                    home.AnimateLine(tokenPositionPlayer1, tokenIDPlayer1, isPlayer1FurthestTokenLeft,
                      () =>
                      {
                          home2.AnimateLine(tokenPositionPlayer2, tokenIDPlayer2, isPlayer2FurthestTokenLeft,
                              () =>
                              {
                                  home.BlinkLine(() =>
                                  {
                                      Debug.Log("TIE");
                                      ludoNumberGsNew.isTie = true;
                                      ludoNumberGsNew.BattleFinishUserData();
                                  });
                              });
                      });

                }
                else if (tokenPositionPlayer1 > tokenPositionPlayer2)
                {
                    Debug.Log("Player 1 Won");
                    home.AnimateLine(tokenPositionPlayer1, tokenIDPlayer1, isPlayer1FurthestTokenLeft,
                        () =>
                        {
                            home2.AnimateLine(tokenPositionPlayer2, tokenIDPlayer2, isPlayer2FurthestTokenLeft,
                                () =>
                                {
                                    home.BlinkLine(() => { ludoNumberGsNew.ShowExtraScoreAnim(player.userName.text); });
                                });
                        });
                }
                else
                {
                    Debug.Log("Player 2 Won");
                    home.AnimateLine(tokenPositionPlayer1, tokenIDPlayer1, isPlayer1FurthestTokenLeft,
                        () =>
                        {
                            home2.AnimateLine(tokenPositionPlayer2, tokenIDPlayer2, isPlayer2FurthestTokenLeft,
                                () =>
                                {
                                    home2.BlinkLine(() =>
                                    {
                                        ludoNumberGsNew.ShowExtraScoreAnim(player2.userName.text);
                                    });
                                });
                        });
                }
            }
        }

        #endregion

        #region ResetGame
        public void ResetGame()
        {
            ludoNumberGsNew.winPanel.SetActive(false);
            ludoNumberGsNew.board.SetActive(true);
            for (int j = 0; j < moveToken.data.updatedScore.Count; j++)
            {

                moveToken.data.updatedScore[j].score = 0;

            }
        }
        #endregion
    }
}
