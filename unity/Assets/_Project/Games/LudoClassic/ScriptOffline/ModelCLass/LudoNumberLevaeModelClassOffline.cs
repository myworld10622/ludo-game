namespace LudoClassicOffline
{
    [System.Serializable]
    public class LevaeTableData
    {
        public bool userSelfLeave ;
    }
    [System.Serializable]
    public class Metrics
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
    public class LevaeTable
    {
        public Metrics metrics ;
        public LevaeTableData data ;
    }

    [System.Serializable]
    public class LeaveTableEventData
    {
        public int playerSeatIndex;
        public bool userSelfLeave;
    }
    [System.Serializable]
    public class LeaveTableEvent
    {
        public string en;
        public LeaveTableEventData data;
    }


}