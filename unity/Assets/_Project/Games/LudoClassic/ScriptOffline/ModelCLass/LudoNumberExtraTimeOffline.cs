namespace LudoClassicOffline
{
    [System.Serializable]
    public class ExtraTimeData
    {
        public int startTurnSeatIndex;
        public int diceValue;
        public long resumeTimeStamp;
        public int remainingTimer;
    }
    [System.Serializable]
    public class ExtraTime
    {
        public string en;
        public ExtraTimeData data;
    }

}