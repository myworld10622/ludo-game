using System.Collections.Generic;
namespace LudoClassicOffline
{
    [System.Serializable]
    public class JoinTableResponseData
    {
        public List<PlayerInfoData> playerInfo ;
        public int thisPlayerSeatIndex ;
        public int turnTimer ;
        public int extraTimer ;
        public List<int> playerMoves ;
        public string tableId ;
        public string queueKey ;
        public int maxPlayerCount ;

        public JoinTableResponseData()
        {
            maxPlayerCount = 2;
        }
    }
    [System.Serializable]
    public class JoinTableMetrics
    {
        public long srpt ;
        public string tableId ;
        public string userId ;
    }
    [System.Serializable]
    public class PlayerInfoData
    {
        public int playerSeatIndex ;
        public string userId ;
        public string username ;
        public string userProfile ;
    }
    [System.Serializable]
    public class JoinTableResponse
    {
        public JoinTableResponseData data ;
        public JoinTableMetrics metrics ;
        public string userId ;
        public string tableId ;
    }



}