using System.Collections.Generic;

namespace LudoClassicOffline
{
    public class SignUpResponceClass
    {
        [System.Serializable]
        public class SignUpResponceData
        {
            public bool isAbleToReconnect;
            public string roomName;
            public int activePlayer;
            public int numberOfPlayers;
            public string tableState;
            public List<object> leftPlayerInfo;
            public List<PlayerInfo> playerInfo;
            public int movesLeft;
            public int thisPlayerSeatIndex;
            public List<int> playerMoves;
            public UserTurnDetails userTurnDetails;
            public int turnTimer;
            public int extraTimer;
            public int gameTimer;
            public double mainGameTimer;
            public bool isSix;
            public int userTurnCount;
        }
        [System.Serializable]
        public class Metrics
        {
            public string uuid;
            public string ctst;
            public string srct;
            public long srpt;
            public string crst;
            public string userId;
            public int apkVersion;
            public string tableId;
        }
        [System.Serializable]
        public class PlayerInfo
        {
            public int seatIndex;
            public string userId;
            public string username;
            public string avatar;
            public List<int> tokenDetails;
            public int score;
            public int missedTurnCount;
            public int highestToken;
            public int remainingTimer;
        }
        [System.Serializable]
        public class SignUpResponce
        {
            public SignUpResponceData data;
            public Metrics metrics;
            public string userId;
            public string tableId;
        }
        [System.Serializable]
        public class UserTurnDetails
        {
            public int currentTurnSeatIndex;
            public bool isExtraTurn;
            public float remainingTimer;
            public bool isExtraTime;
            public int diceValue;
            public bool isDiceAnimated;
        }
    }
}
