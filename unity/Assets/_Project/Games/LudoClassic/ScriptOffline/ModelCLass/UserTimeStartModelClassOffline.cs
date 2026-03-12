using System.Collections.Generic;

namespace LudoClassicOffline
{
    [System.Serializable]
    public class UserTimeStartData
    {
        public string en ;
        public int startTurnSeatIndex ;
        public int diceValue ;
        public bool isExtraTurn ;
        public int movesLeft ;
        public List<TokenPosition> tokenPosition ;
        public int userTurnCount;
    }
    [System.Serializable]
    public class UserTimeStart
    {
        public UserTimeStartData data ;
    }
    [System.Serializable]
    public class TokenPosition
    {
        public int seatIndex ;
        public List<int> tokenDetails ;
    }
}