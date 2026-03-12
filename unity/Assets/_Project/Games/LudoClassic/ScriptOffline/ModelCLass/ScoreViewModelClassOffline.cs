namespace LudoClassicOffline
{
    [System.Serializable]
    public class ScoreViewData
    {
        public string userID ;
        public int seatIndex ;
        public int tokenIndex ;
    }
    [System.Serializable]
    public class ScoreViewMetrics
    {
        public string uuid ;
        public string ctst ;
        public string srct ;
        public string srpt ;
        public string crst ;
        public string userId ;
        public int apkVersion ;
        public string tableId ;
    }
    [System.Serializable]
    public class ScoreView
    {
        public ScoreViewMetrics metrics ;
        public ScoreViewData data ;
    }
    [System.Serializable]
    public class ScoreViewResData
    {
        public int score ;
        public int tokenIndex ;
        public int seatIndex ;
    }
    [System.Serializable]
    public class ScoreViewRes
    {
        public string en ;
        public ScoreViewResData data ;
    }
}