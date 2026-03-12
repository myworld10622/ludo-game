using System.Collections.Generic;

namespace LudoClassicOffline
{
    [System.Serializable]
    public class TokenMoveData
    {
        public string en ;
        public int tokenMove ;
        public int movementValue ;
        public bool isCapturedToken ;
        public int capturedTokenIndex ;
        public int capturedSeatIndex ;
        public List<UpdatedScore> updatedScore ;
        public bool isExtraScore ;
        public int extraScorePlayerIndex ;
        public int extraScore ;
        public int captureTokenDecScore ;
        public int killedTokenHomePosition ;
        public List<PlayerFurthestTokenIndex> playerFurthestTokenIndex ;
    }
    [System.Serializable]
    public class PlayerFurthestTokenIndex
    {
        public int seatIndex ;
        public int highestToken ;
    }
    [System.Serializable]
    public class TokenMove
    {
        public TokenMoveData data ;
    }
    [System.Serializable]
    public class UpdatedScore
    {
        public int seatIndex ;
        public int score ;
    }


}