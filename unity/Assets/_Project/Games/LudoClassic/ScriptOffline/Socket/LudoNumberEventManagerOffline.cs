
using UnityEngine;
using static LudoClassicOffline.LudoNumberEmojiDataClass;
using static LudoClassicOffline.SignUpResponceClass;


namespace LudoClassicOffline
{
    public class LudoNumberEventManagerOffline : MonoBehaviour
    {

        public LudoNumberGsNewOffline ludoNumberGsNew;
        public string SignUpRequstData()
        {
            string json;
            if (!GameManagerOffline.instace.gameRunOnSdk)
            {
                SignRequest signRequest = new SignRequest();
                SignRequestData signRequestData = new SignRequestData();

                signRequestData.lobbyId = "6396db836decffbf59c3652e";
                signRequestData.winning_amount = 0;
                signRequestData.username = "Player" + Random.Range(0, 100);
                signRequestData.userId = SystemInfo.deviceUniqueIdentifier;
                signRequestData.maxPlayer = int.Parse(ludoNumberGsNew.lableText.text);
                signRequestData.userProfile = "https://artoon-pinochle.s3.us-east-1.amazonaws.com/320465.png";
                signRequestData.entryFee = 0;
                signRequestData.gameType = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName;
                signRequest.data = signRequestData;
                json = JsonUtility.ToJson(signRequest);
            }
            else
            {
                SignRequestSDk signRequest = new SignRequestSDk();
                SignRequestDataSDK signRequestData = new SignRequestDataSDK();

                signRequestData.acessToken = MGPSDK.MGPGameManager.instance.sdkConfig.data.accessToken;
                signRequestData.deviceId = SystemInfo.deviceUniqueIdentifier;
                signRequestData.entryFee = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.entryFee.ToString();
                signRequestData.gameId = MGPSDK.MGPGameManager.instance.sdkConfig.data.gameData.gameId;
                signRequestData.isFTUE = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.IsFTUE;
                signRequestData.isUseBot = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.isUseBot;
                signRequestData.lobbyId = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData._id;
                signRequestData.minPlayer = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.minPlayer;
                signRequestData.maxPlayer = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.noOfPlayer;
                signRequestData.userProfile = MGPSDK.MGPGameManager.instance.sdkConfig.data.selfUserDetails.avatar;
                signRequestData.userId = MGPSDK.MGPGameManager.instance.sdkConfig.data.selfUserDetails.userID;
                signRequestData.username = MGPSDK.MGPGameManager.instance.sdkConfig.data.selfUserDetails.displayName;
                signRequestData.winningAmount = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.winningAmount.ToString();
                signRequestData.projectType = MGPSDK.MGPGameManager.instance.sdkConfig.data.projectType;
                signRequestData.gameModeId = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeId;
                signRequestData.gameType = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName;
                signRequestData.gameModeName = MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName;

                GameManagerOffline.instace.selfUserID = signRequestData.userId;
                ludoNumberGsNew.ludoNumbersAcknowledgementHandler.emojiHandler.senderId = GameManagerOffline.instace.selfUserID;
                Debug.Log("Sent id => " + signRequestData.userId);
                signRequest.data = signRequestData;

                json = JsonUtility.ToJson(signRequest);
            }

            return json;
        }

        public string MoveTokenCoockie(int coockieIndex)
        {
            MoveToken moveToken = new MoveToken();
            moveToken.data.tokenMove = coockieIndex;
            string json = JsonUtility.ToJson(moveToken);
            Debug.Log("Json Of Move Token" + json);
            return json;
        }

        public string SendLeaveTable()
        {
            LevaeTable levaeTable = new LevaeTable();
            Metrics metrics = new Metrics();
            LevaeTableData levaeTableData = new LevaeTableData();
            metrics.uuid = "caf09baf-faea-4849-8ee0-933db032bf18";
            metrics.ctst = "1677497513839";
            metrics.srct = "";
            metrics.srpt = "";
            metrics.crst = "1.2";
            metrics.userId = "";
            metrics.apkVersion = 101;
            metrics.tableId = "";
            levaeTableData.userSelfLeave = true;
            levaeTable.data = levaeTableData;
            levaeTable.metrics = metrics;
            string json = JsonUtility.ToJson(levaeTable);
            Debug.Log("Json Of LEAVEn" + json);
            return json;

        }

        public string Reconnect()
        {
            Reconnect reconnect = new Reconnect();
            MetricsReconnect metricsReconnect = new MetricsReconnect();
            ReconnectData reconnectData = new ReconnectData();

            metricsReconnect.uuid = "0bad5c0a-e8c0-45c6-b771-eb3a6e15b4ff";
            metricsReconnect.ctst = "1678694306412";
            metricsReconnect.srct = "";
            metricsReconnect.srpt = "";
            metricsReconnect.crst = "1.2";
            metricsReconnect.userId = "";
            metricsReconnect.apkVersion = 101;
            metricsReconnect.tableId = "";

            if (ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.data.isAbleToReconnect)
            {
                reconnectData.roomName = ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.data.roomName;
                reconnectData.userId = ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.userId;
            }
            else
            {
                reconnectData.roomName = ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.tableId;
                reconnectData.userId = ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.userId;
            }

            reconnect.data = reconnectData;
            string json = JsonUtility.ToJson(reconnect);
            return json;
        }

        public string Score(int seat)
        {
            ScoreView scoreView = new ScoreView();
            ScoreViewData scoreViewData = new ScoreViewData();

            scoreViewData.userID = ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.userId;
            scoreViewData.seatIndex = ludoNumberGsNew.socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex;
            scoreViewData.tokenIndex = seat;

            scoreView.data = scoreViewData;

            string json = JsonUtility.ToJson(scoreView);
            Debug.LogError("String Json => " + json);

            return json;
        }
        public string EmojiSendReq(string sendID, int emojiNumber, string tableId)
        {

            Debug.Log("emoji Request " + sendID);
            EmojiRequest emojiRequest = new EmojiRequest();
            EmojiRequestData emojiRequestData = new EmojiRequestData();

            emojiRequest.en = "EMOJI";
            emojiRequestData.userId = sendID;
            emojiRequestData.tableId = tableId;
            emojiRequestData.emoji = emojiNumber;
            emojiRequest.data = emojiRequestData;
            string json = JsonUtility.ToJson(emojiRequest);
            return json;
        }

        public string SendDiceAnimation()
        {
            DiceAnimationSend diceAnimationSend = new DiceAnimationSend();
            diceAnimationSend.data = "";
            string diceAnimationSendJson = JsonUtility.ToJson(diceAnimationSend);
            Debug.Log("Json Of LEAVEn" + diceAnimationSendJson);
            return diceAnimationSendJson;
        }

    }
}
