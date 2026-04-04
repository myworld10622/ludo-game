using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoNumbersAcknowledgementHandlerOffline : MonoBehaviour
    {
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public LevaeTable levaeTable = new LevaeTable();
        public LudoNumberPlayerControlOffline[] ludoNumberPlayerControl;
        public LudoNumberUiManagerOffline ludoNumberUiManager;
        public LudoNumberTostMessageOffline ludoNumberTostMessage;
        public LudoNumberGsNewOffline ludoNumberGsNew;
        public List<LudoNumberPlayerControlOffline> ludoNumberPlayer;
        public LeaveTableE leaveTableEData;
        public int mySeatIndex = 0;
        public string tableId;
        public int moveLeftTotalMove;
        public EmojiHandlerOffline emojiHandler;
        public TokenMove tokenMove;
        public Button yesBtn;//TODO
        public Button noBtn;//TODO

        private void OnDisable()
        {
            CancelInvoke();
            KillRejoinTweens();
        }

        private void OnDestroy()
        {
            CancelInvoke();
            KillRejoinTweens();
        }

        private void KillRejoinTweens()
        {
            if (ludoNumberUiManager != null)
            {
                if (ludoNumberUiManager.timerCountScreen != null && ludoNumberUiManager.timerCountScreen.transform != null)
                {
                    ludoNumberUiManager.timerCountScreen.transform.DOKill();
                }

                if (ludoNumberUiManager.startPanel != null && ludoNumberUiManager.startPanel.transform != null)
                {
                    ludoNumberUiManager.startPanel.transform.DOKill();
                }
            }
        }

        public string playerOwnId
        {
            get
            {
                return PlayerPrefs.GetString("playerOwnId");
            }
            set
            {
                PlayerPrefs.SetString("playerOwnId", "0");
            }
        }
        private void OnApplicationPause(bool pause)
        {
            if (pause)
            {

                for (int i = 0; i < ludoNumberPlayer.Count; i++)
                {
                    ludoNumberPlayer[i].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.GetComponent<CoockieMovementOffline>().StopMovement());
                }
            }
        }
        public void SignUpAcknowledged(string data)
        {
            if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
            {

                ludoNumberUiManager.MoveLeftGameObject.SetActive(true);
                ludoNumberUiManager.timerGameObject.SetActive(false);

                for (int i = 0; i < ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                {
                    ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(false);
                }
                /* Debug.Log("ENTER");
                 ludoNumberUiManager.timerGameObject.SetActive(false);
                 for (int i = 0; i < ludoNumberUiManager.scoreGameObjectList.Count; i++)
                 {
                     ludoNumberUiManager.scoreGameObjectList[i].SetActive(true);
                 }
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
                ludoNumberUiManager.MoveLeftGameObject.SetActive(false);
                ludoNumberUiManager.timerGameObject.SetActive(true);
                ludoNumberUiManager.numberViewScreenGameObject.SetActive(false);
                for (int i = 0; i < ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                {
                    ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(false);
                }
            }
            else
            {
                Debug.Log("Closed");
                ludoNumberUiManager.timerGameObject.SetActive(false);

                ludoNumberUiManager.MoveLeftGameObject.SetActive(false);
                ludoNumberUiManager.numberViewScreenGameObject.SetActive(false);
                for (int i = 0; i < ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                {
                    ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(true);
                }

            }


            if (socketNumberEventReceiver.socketConnection.isSignUpRepeatRunning)
            {
                socketNumberEventReceiver.socketConnection.CancelResendSignUp();
                ludoNumberUiManager.StopReconntionAnimation();
                socketNumberEventReceiver.socketConnection.isSignUpRepeatRunning = false;
                socketNumberEventReceiver.socketConnection.isAcknowledgementRecd = true;
            }
            Debug.LogError(data);
            socketNumberEventReceiver.signUpResponce = JsonUtility.FromJson<SignUpResponceClass.SignUpResponce>(data);
            playerOwnId = socketNumberEventReceiver.signUpResponce.userId;
            ludoNumberUiManager.NumberGeneration(socketNumberEventReceiver.signUpResponce.data.playerMoves);//TODO
            socketNumberEventReceiver.userStartIndex = socketNumberEventReceiver.signUpResponce.data.userTurnDetails.currentTurnSeatIndex;
            ReconntionData(data);
        }
        public LudoNumberPlayerControlOffline leftPlayerData = null;	//TODO
        public void ReconntionData(string data)
        {
            if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
            {

                ludoNumberUiManager.MoveLeftGameObject.SetActive(true);
                ludoNumberUiManager.timerGameObject.SetActive(false);
                ludoNumberUiManager.numberViewScreenGameObject.SetActive(true);
                for (int i = 0; i < ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                {
                    ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(false);
                }
                /* Debug.Log("ENTER");
                 ludoNumberUiManager.timerGameObject.SetActive(false);
                 for (int i = 0; i < ludoNumberUiManager.scoreGameObjectList.Count; i++)
                 {
                     ludoNumberUiManager.scoreGameObjectList[i].SetActive(true);
                 }
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
                ludoNumberUiManager.MoveLeftGameObject.SetActive(false);
                ludoNumberUiManager.timerGameObject.SetActive(true);
                ludoNumberUiManager.numberViewScreenGameObject.SetActive(false);
                for (int i = 0; i < ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                {
                    ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(false);
                }
            }
            else
            {
                Debug.Log("Closed");
                ludoNumberUiManager.timerGameObject.SetActive(false);

                ludoNumberUiManager.MoveLeftGameObject.SetActive(false);
                ludoNumberUiManager.numberViewScreenGameObject.SetActive(false);

                for (int i = 0; i < ludoNumberUiManager.smallBoxGameObjectList.Count; i++)
                {
                    ludoNumberUiManager.smallBoxGameObjectList[i].SetActive(true);
                }
            }

            Debug.Log("Recnooion data =>" + data);
            socketNumberEventReceiver.signUpResponce = JsonUtility.FromJson<SignUpResponceClass.SignUpResponce>(data);
            JSONObject jsonData = new JSONObject(data);
            if (jsonData.HasField("data") && jsonData.GetField("data").HasField("isAbleToReconnect") && bool.Parse(jsonData.GetField("data").GetField("isAbleToReconnect").ToString()))
            {

                socketNumberEventReceiver.diceValue = socketNumberEventReceiver.signUpResponce.data.userTurnDetails.diceValue;
                socketNumberEventReceiver.maxPlayer = socketNumberEventReceiver.signUpResponce.data.numberOfPlayers;
                socketNumberEventReceiver.isSix = socketNumberEventReceiver.signUpResponce.data.isSix;
                emojiHandler.tabelId = socketNumberEventReceiver.signUpResponce.tableId;
                SoundManagerOffline.instance.soundAudioSource.Stop();
                for (int i = 0; i < ludoNumberUiManager.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
                {
                    for (int k = 0; k < ludoNumberUiManager.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count; k++)
                    {
                        ludoNumberUiManager.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[k].GetComponent<CoockieMovementOffline>().StopMovement();
                    }
                }
                List<PlayerInfoData> playerInfo = new List<PlayerInfoData>();
                for (int i = 0; i < socketNumberEventReceiver.signUpResponce.data.playerInfo.Count; i++)
                {
                    PlayerInfoData playerInfoData = new PlayerInfoData();
                    playerInfoData.userId = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].userId;
                    playerInfoData.username = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].username;
                    playerInfoData.playerSeatIndex = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].seatIndex;
                    playerInfoData.userProfile = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].avatar;
                    playerInfo.Add(playerInfoData);
                }
                tableId = socketNumberEventReceiver.signUpResponce.data.tableState;
                RejoinArrangePlayer(playerInfo);
                switch (tableId)
                {
                    case "WAITING_FOR_PLAYERS":
                        break;
                    case "GAME_TIMER_STARTED":
                        ludoNumberUiManager.timerCountScreen.SetActive(true);
                        ludoNumberTostMessage.HideWaitForPlayerToast(true);
                        ludoNumberUiManager.CountDownStart(socketNumberEventReceiver.signUpResponce.data.gameTimer);
                        ludoNumberGsNew.settingBtn.interactable = false;
                        break;
                    case "PLAYING":
                        if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
                        {
                            socketNumberEventReceiver.isExtraTurn = socketNumberEventReceiver.signUpResponce.data.userTurnDetails.isExtraTurn;
                            socketNumberEventReceiver.userStartIndex =
                                socketNumberEventReceiver.signUpResponce.data.userTurnDetails.currentTurnSeatIndex;
                            socketNumberEventReceiver.userTurnCount = socketNumberEventReceiver.signUpResponce.data.userTurnCount;
                            if (socketNumberEventReceiver.isExtraTurn)
                                socketNumberEventReceiver.moveNumberAlltime = socketNumberEventReceiver.signUpResponce.data.movesLeft - 1;
                            else
                            {
                                socketNumberEventReceiver.moveNumberAlltime = socketNumberEventReceiver.signUpResponce.data.movesLeft;
                                ludoNumberGsNew.moveText.text = socketNumberEventReceiver.moveNumberAlltime.ToString();
                            }

                            ludoNumberUiManager.viewPort.DOAnchorPosX((socketNumberEventReceiver.userTurnCount) * (-115f), 0.5f);
                            ludoNumberUiManager.NumberGeneration(socketNumberEventReceiver.signUpResponce.data.playerMoves);
                            ludoNumberUiManager.DisablePreviousNumbersWhenReconnect();
                            socketNumberEventReceiver.coockieMovement.CoockieManage();
                            ludoNumberUiManager.timerCountScreen.SetActive(false);
                            ludoNumberUiManager.startPanel.SetActive(false);
                            ludoNumberUiManager.StopReconntionAnimation();
                            ludoNumberTostMessage.WaitingForPlayerMessage.SetActive(false);
                            ludoNumberGsNew.MoveTokenRejoin(socketNumberEventReceiver.signUpResponce);
                            ludoNumberGsNew.UserTurnStartReJoin(socketNumberEventReceiver.signUpResponce);
                            ludoNumberGsNew.TurnMissReJoin(socketNumberEventReceiver.signUpResponce);
                            if (socketNumberEventReceiver.signUpResponce.data.userTurnDetails.isExtraTime == true)
                                ludoNumberGsNew.ExtraTimerReJoin(socketNumberEventReceiver.signUpResponce);
                            if (socketNumberEventReceiver.signUpResponce.data.numberOfPlayers == 4)
                            {
                                ludoNumberPlayer = new List<LudoNumberPlayerControlOffline>();

                                for (int j = 0; j < ludoNumberPlayerControl.Length; j++)
                                    ludoNumberPlayer.Add(ludoNumberPlayerControl[j]);


                                for (int i = 0; i < socketNumberEventReceiver.signUpResponce.data.playerInfo.Count; i++)
                                    ludoNumberPlayer = ludoNumberPlayer.FindAll(item =>
                                        (item.playerInfoData.playerSeatIndex !=
                                         socketNumberEventReceiver.signUpResponce.data.playerInfo[i].seatIndex));

                                for (int i = 0; i < ludoNumberPlayer.Count; i++)
                                {
                                    ludoNumberPlayer[i].ludoNumbersUserData.leaveTableImage.SetActive(true);
                                    ludoNumberPlayer[i].gameObject.SetActive(false);
                                    ludoNumberPlayer[i].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.transform.SetParent(ludoNumberGsNew.TokenKill.transform));
                                    ludoNumberPlayer[i].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.SetActive(false));
                                    ludoNumberPlayer[i].ludoNumbersUserData.animatorOnTurn.gameObject.SetActive(false);
                                    ludoNumberPlayer[i].ludoNumbersUserData.scoreBox.SetActive(false);
                                }
                            }
                        }
                        else
                        {

                            Debug.Log("Playing");
                            ludoNumberGsNew.CancleMainTimer();
                            ludoNumberGsNew.MainTimer(socketNumberEventReceiver.signUpResponce.data.mainGameTimer);
                            socketNumberEventReceiver.coockieMovement.CoockieManage();
                            ludoNumberUiManager.timerCountScreen.SetActive(false);
                            ludoNumberUiManager.startPanel.SetActive(false);
                            ludoNumberUiManager.StopReconntionAnimation();
                            ludoNumberTostMessage.WaitingForPlayerMessage.SetActive(false);
                            ludoNumberGsNew.MoveTokenRejoin(socketNumberEventReceiver.signUpResponce);
                            socketNumberEventReceiver.userStartIndex =
                                socketNumberEventReceiver.signUpResponce.data.userTurnDetails.currentTurnSeatIndex;
                            ludoNumberGsNew.UserTurnStartReJoin(socketNumberEventReceiver.signUpResponce);

                            ludoNumberGsNew.TurnMissReJoin(socketNumberEventReceiver.signUpResponce);

                            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                            {
                                for (int j = 0; j < ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie.Count; j++)
                                {
                                    ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovementOffline>().tokenAwayBGRight
                                        .DOScale(Vector3.zero, 0.2f);
                                    ludoNumberPlayerControl[i].ludoNumbersUserData.playerCoockie[j].GetComponent<CoockieMovementOffline>().tokenAwayBG
                                        .DOScale(Vector3.zero, 0.2f);
                                }
                            }

                            if (socketNumberEventReceiver.signUpResponce.data.userTurnDetails.isDiceAnimated == true)
                            {
                                for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
                                {
                                    ludoNumberPlayerControl[i].ludoNumbersUserData.diceAnimation.DiceAnimationStart(
                                        socketNumberEventReceiver.signUpResponce.data.userTurnDetails.diceValue,
                                        socketNumberEventReceiver.signUpResponce.data.userTurnDetails.currentTurnSeatIndex);
                                    ludoNumberPlayerControl[i].ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                                }
                            }

                            if (socketNumberEventReceiver.signUpResponce.data.userTurnDetails.isExtraTime == true)
                                ludoNumberGsNew.ExtraTimerReJoin(socketNumberEventReceiver.signUpResponce);
                            if (socketNumberEventReceiver.signUpResponce.data.numberOfPlayers == 4)
                            {
                                ludoNumberPlayer = new List<LudoNumberPlayerControlOffline>();

                                for (int j = 0; j < ludoNumberPlayerControl.Length; j++)
                                    ludoNumberPlayer.Add(ludoNumberPlayerControl[j]);

                                for (int i = 0; i < socketNumberEventReceiver.signUpResponce.data.playerInfo.Count; i++)
                                    ludoNumberPlayer = ludoNumberPlayer.FindAll(item =>
                                        (item.playerInfoData.playerSeatIndex !=
                                         socketNumberEventReceiver.signUpResponce.data.playerInfo[i].seatIndex));

                                for (int i = 0; i < ludoNumberPlayer.Count; i++)
                                {
                                    ludoNumberPlayer[i].ludoNumbersUserData.leaveTableImage.SetActive(true);
                                    ludoNumberPlayer[i].ludoNumbersUserData.smallRoundImage.SetActive(false);
                                    ludoNumberPlayer[i].gameObject.SetActive(false);
                                    ludoNumberPlayer[i].ludoNumbersUserData.playerCoockie.ForEach((cookie) =>
                                        cookie.transform.SetParent(ludoNumberGsNew.TokenKill.transform));
                                    ludoNumberPlayer[i].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.SetActive(false));
                                    ludoNumberPlayer[i].ludoNumbersUserData.turnProfileBlink.SetActive(false);
                                    ludoNumberPlayer[i].ludoNumbersUserData.scoreBox.SetActive(false);
                                }
                            }
                        }

                        break;
                    case "WINNER_DECLARED":
                        break;
                    default:
                        break;
                }

            }
        }
        public void ReJoinGameMainTimer(int waitTimer)
        {
            ludoNumberGsNew.settingBtn.interactable = false;
            ludoNumberTostMessage.HideWaitForPlayerToast(true);
            ludoNumberUiManager.timerCountScreen.SetActive(true);
            CountDownStartReJoin(waitTimer);
        }
        public void CountDownStartReJoin(int waitingTime)
        {
            ludoNumberUiManager.waitingTimer = waitingTime;
            if (ludoNumberUiManager.waitingTimer <= 1)
            {
                CancelInvoke(nameof(DecreaseCounterReJoin));
                ludoNumberUiManager.timerCountScreen.transform.DOScale(Vector3.zero, 0.5f);
                ludoNumberUiManager.startPanel.transform.DOScale(Vector3.zero, 1f);
                ludoNumberUiManager.StopReconntionAnimation();
                ludoNumberTostMessage.WaitingForPlayerMessage.SetActive(false);
            }
            else
                InvokeRepeating(nameof(DecreaseCounterReJoin), 1, 1);
        }
        private void DecreaseCounterReJoin()
        {
            ludoNumberUiManager.waitingTimer--;
            ludoNumberUiManager.timerMessageText.text = ludoNumberUiManager.waitingTimer.ToString();
            if (ludoNumberUiManager.waitingTimer <= 0)
            {
                CancelInvoke(nameof(DecreaseCounterReJoin));
                StopDecreaseCounterReJoin();
                StartCoroutine(TimeReJoin());
                return;
            }
        }
        public void TokenMove(string data)
        {
            Debug.Log("TokenMove Table => " + data);
        }
        IEnumerator TimeReJoin()
        {
            yield return new WaitForSeconds(0.5f);
            if (ludoNumberUiManager.waitingTimer == 0)
            {
                ludoNumberUiManager.timerCountScreen.transform.DOScale(Vector3.zero, 0.5f).OnComplete(() =>
                {
                    ludoNumberUiManager.startPanel.transform.DOScale(Vector3.one, 1f);
                });
            }
        }
        public void StopDecreaseCounterReJoin() => StopCoroutine(nameof(DecreaseCounterReJoin));
        public void DiceAnimationStart(string data) => Debug.Log(data);

        public void LevaeTable(string data)
        {
            leaveTableEData = JsonUtility.FromJson<LeaveTableE>(data);
            LeaveTable(leaveTableEData);
        }
        public void ReconnectAcknowledgement(string data) => ReconntionData(data);
        public void ScoreViewAcknowledgement(string data) => Debug.Log("Score View Table => " + data);
        public void LeaveTable(LeaveTableE leaveTableEData)
        {
            if (socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex == leaveTableEData.data.playerSeatIndex)
            {
                if (leaveTableEData.data.success)
                    GameManagerOffline.instace.OnClickExit();
            }
        }
        //    internal void HeartBeatReceived(string data) => socketNumberEventReceiver.ludoNumberGsNew.heartBeatManager.OnReceiveHB(new JSONObject(data));
        public void JoinTableAcknowledged()
        {
            ResetAllPlayerSlots();
            ArrangePlayer(socketNumberEventReceiver.joinTableResponse.data);
            moveLeftTotalMove = socketNumberEventReceiver.joinTableResponse.data.playerMoves.Count;
            socketNumberEventReceiver.maxPlayer = socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount;

            if (socketNumberEventReceiver.joinTableResponse.data.playerInfo.Count <= (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount - 1))
                ludoNumberTostMessage.ShowToastMessages(ToastMessage.WAITFORPLAYER);
            if (socketNumberEventReceiver.joinTableResponse.data.playerInfo.Count == socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount)
            {
                //yesBtn.GetComponent<Button>().interactable = false;
                // yesBtn.GetComponent<Image>().raycastTarget = false;
                //  noBtn.GetComponent<Image>().raycastTarget = false;
                ludoNumberUiManager.leavePanel.SetActive(false);
            }
        }
        public void RejoinArrangePlayer(List<PlayerInfoData> data)
        {
            ResetAllPlayerSlots();
            int mySeatIndex = 0;
            for (int i = 0; i < data.Count; i++)
            {
                if (playerOwnId == data[i].userId)
                {
                    PlayerDataSetRejoin(data[i].playerSeatIndex, 0);
                    mySeatIndex = data[i].playerSeatIndex;
                    break;
                }
            }
            switch (mySeatIndex)
            {
                case 0:
                    {
                        PlayerDataSetRejoin(1, 1);
                        PlayerDataSetRejoin(2, 2);
                        PlayerDataSetRejoin(3, 3);

                        break;
                    }
                case 1:
                    {
                        PlayerDataSetRejoin(2, 1);
                        PlayerDataSetRejoin(3, 2);
                        PlayerDataSetRejoin(0, 3);

                        break;
                    }
                case 2:
                    {
                        PlayerDataSetRejoin(3, 1);
                        PlayerDataSetRejoin(0, 2);
                        PlayerDataSetRejoin(1, 3);
                        break;
                    }
                case 3:
                    {
                        PlayerDataSetRejoin(0, 1);
                        PlayerDataSetRejoin(1, 2);
                        PlayerDataSetRejoin(2, 3);
                        break;
                    }
            }
        }
        public void ArrangePlayer(JoinTableResponseData data)
        {
            PlayerDataSet(data.playerInfo[0].playerSeatIndex, 0);
            mySeatIndex = 0;

            if (socketNumberEventReceiver.joinTableResponse.data.maxPlayerCount == 2)
            {
                PlayerDataSet(1, 2);
            }
            else
            {
                PlayerDataSet(1, 1);
                PlayerDataSet(2, 2);
                PlayerDataSet(3, 3);
            }
        }






        public void PlayerDataSetRejoin(int seatIndex, int refernceIndex)
        {

            Debug.Log("User ID => " + GameManagerOffline.instace.selfUserID);
            /*if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("NUMBER"))
                ludoNumberUiManager.moveLeft.SetActive(true);*/
            emojiHandler.senderId = GameManagerOffline.instace.selfUserID;
            for (int i = 0; i < socketNumberEventReceiver.signUpResponce.data.playerInfo.Count; i++)
            {
                if (seatIndex == socketNumberEventReceiver.signUpResponce.data.playerInfo[i].seatIndex)
                {
                    string displayName = LudoDisplayNameUtility.ResolveDisplayName(
                        socketNumberEventReceiver.signUpResponce.data.playerInfo[i].userId,
                        socketNumberEventReceiver.signUpResponce.data.playerInfo[i].username,
                        seatIndex
                    );
                    ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.username = displayName;
                    ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userId = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].userId;
                    ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userProfile = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].avatar;
                    ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userNameText.text = displayName;
                    ludoNumberPlayerControl[refernceIndex].gameObject.SetActive(true);
                    SetPlayerSlotVisualState(ludoNumberPlayerControl[refernceIndex], true);
                    emojiHandler.tabelId = socketNumberEventReceiver.signUpResponce.data.roomName;
                    //myUserId = socketNumberEvnetReceiver.signUpResponce.data.playerInfo[i].userId;
                    //emojiHandler.senderId = ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userId;
                    // ludoNumberUiManager.SpriteLoder(ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userImage, socketNumberEventReceiver.signUpResponce.data.playerInfo[i].avatar);
                    if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                    {
                        Debug.Log("Check");
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.scoreBox.SetActive(true);
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.SetActive(true));
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.transform.SetParent(ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.tokenParent.transform));
                    }
                    else
                    {
                        Debug.Log("Check 1");
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((cookie) => cookie.SetActive(true));
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((cookie) => cookie.transform.SetParent(ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.tokenParentForClassicMode.transform));
                    }
                    ludoNumberPlayerControl[refernceIndex].playerInfoData.playerSeatIndex = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].seatIndex;
                    ludoNumberPlayerControl[refernceIndex].playerInfoData.userId = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].userId;
                    ludoNumberPlayerControl[refernceIndex].playerInfoData.username = displayName;
                    ludoNumberPlayerControl[refernceIndex].playerInfoData.userProfile = socketNumberEventReceiver.signUpResponce.data.playerInfo[i].avatar;
                    LudoFriendPanelController.RefreshRoomPlayerActionsIfPresent();
                }
            }
        }
        public void PlayerDataSet(int seatIndex, int refernceIndex)
        {
            ludoNumberUiManager.gameManager.gameState = GameState.run;

            emojiHandler.tabelId = socketNumberEventReceiver.signUpResponce.tableId;
            /* if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                 ludoNumberUiManager.moveLeft.SetActive(true);*/
            emojiHandler.senderId = GameManagerOffline.instace.selfUserID;
            try
            {

                for (int i = 0; i < socketNumberEventReceiver.joinTableResponse.data.playerInfo.Count; i++)
                {
                    if (seatIndex == socketNumberEventReceiver.joinTableResponse.data.playerInfo[i].playerSeatIndex)
                    {
                        ludoNumberUiManager.reconnationPanel.gameObject.SetActive(false);
                        string displayName = LudoDisplayNameUtility.ResolveDisplayName(
                            socketNumberEventReceiver.joinTableResponse.data.playerInfo[i].userId,
                            socketNumberEventReceiver.joinTableResponse.data.playerInfo[i].username,
                            seatIndex
                        );
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.username = displayName;
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userId = socketNumberEventReceiver.joinTableResponse.data.playerInfo[i].userId;
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userProfile = socketNumberEventReceiver.joinTableResponse.data.playerInfo[i].userProfile;
                        tableId = socketNumberEventReceiver.joinTableResponse.data.tableId;
                        Debug.Log("Join table data" + ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.username);
                        //emojiHandler.senderId = ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userId;
                        //emojiHandler.tabelId = socketNumberEvnetReceiver.signUpResponce.tableId;
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userNameText.text = displayName;
                        ludoNumberPlayerControl[refernceIndex].gameObject.SetActive(true);
                        SetPlayerSlotVisualState(ludoNumberPlayerControl[refernceIndex], true);
                        Debug.Log("Token On => " + ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockie.Count);

                        if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                        {
                            ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.SetActive(true));
                            ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockie.ForEach((cookie) => cookie.transform.SetParent(ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.tokenParent.transform));
                        }
                        else
                        {
                            ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((cookie) => cookie.SetActive(true));
                            ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.playerCoockieForClassicMode.ForEach((cookie) => cookie.transform.SetParent(ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.tokenParentForClassicMode.transform));
                        }
                        ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.leaveTableImage.SetActive(false);
                        if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                            ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.smallRoundImage.SetActive(true);
                        //   ludoNumberUiManager.SpriteLoder(ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.userImage, socketNumberEventReceiver.joinTableResponse.data.playerInfo[i].userProfile);
                        if (!MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                        {
                            Debug.Log("Not Classic mode =>");
                            ludoNumberPlayerControl[refernceIndex].ludoNumbersUserData.scoreBox.SetActive(true);
                        }
                        ludoNumberPlayerControl[refernceIndex].playerInfoData = socketNumberEventReceiver.joinTableResponse.data.playerInfo[i];
                        ludoNumberPlayerControl[refernceIndex].playerInfoData.username = displayName;
                        LudoFriendPanelController.RefreshRoomPlayerActionsIfPresent();
                    }
                }
            }
            catch (System.Exception ex)
            {
                Debug.Log("Ex => " + ex.ToString());
                throw;
            }
        }
        #region ResetGame
        public void ResetGame()
        {
            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                ludoNumberPlayerControl[i].ludoNumbersUserData.scoreText.text = 0.ToString();
            }
        }

        private void ResetAllPlayerSlots()
        {
            if (ludoNumberPlayerControl == null)
            {
                return;
            }

            for (int i = 0; i < ludoNumberPlayerControl.Length; i++)
            {
                ResetPlayerSlot(ludoNumberPlayerControl[i]);
            }
        }

        private void ResetPlayerSlot(LudoNumberPlayerControlOffline playerControl)
        {
            if (playerControl == null)
            {
                return;
            }

            playerControl.playerInfoData = new PlayerInfoData { playerSeatIndex = -1, userId = string.Empty, username = string.Empty, userProfile = string.Empty };
            playerControl.ludoNumbersUserData.username = string.Empty;
            playerControl.ludoNumbersUserData.userId = string.Empty;
            playerControl.ludoNumbersUserData.userProfile = string.Empty;

            if (playerControl.ludoNumbersUserData.userNameText != null)
            {
                playerControl.ludoNumbersUserData.userNameText.text = string.Empty;
            }

            SetPlayerSlotVisualState(playerControl, false);

            if (playerControl.ludoNumbersUserData.leaveTableImage != null)
            {
                playerControl.ludoNumbersUserData.leaveTableImage.SetActive(true);
            }

            if (playerControl.ludoNumbersUserData.smallRoundImage != null)
            {
                playerControl.ludoNumbersUserData.smallRoundImage.SetActive(false);
            }

            if (playerControl.ludoNumbersUserData.scoreBox != null)
            {
                playerControl.ludoNumbersUserData.scoreBox.SetActive(false);
            }

            if (playerControl.ludoNumbersUserData.turnProfileBlink != null)
            {
                playerControl.ludoNumbersUserData.turnProfileBlink.SetActive(false);
            }

            if (playerControl.ludoNumbersUserData.infoBtn != null)
            {
                playerControl.ludoNumbersUserData.infoBtn.SetActive(false);
                playerControl.ludoNumbersUserData.infoBtn.transform.localScale = Vector3.zero;
            }

            playerControl.gameObject.SetActive(false);
            LudoFriendPanelController.RefreshRoomPlayerActionsIfPresent();
        }

        private void SetPlayerSlotVisualState(LudoNumberPlayerControlOffline playerControl, bool isOccupied)
        {
            if (playerControl == null)
            {
                return;
            }

            if (playerControl.playerProfile != null)
            {
                playerControl.playerProfile.enabled = isOccupied;
            }

            if (playerControl.ludoNumbersUserData.userImage != null)
            {
                playerControl.ludoNumbersUserData.userImage.enabled = isOccupied;
            }
        }
        #endregion
    }
}
