namespace LudoClassicOffline
{
    [System.Serializable]
    public class TurnMissData
    {
        public string en ;
        public TurnMissData data ;
        public int playerSeatIndex ;
        public int totalTurnMissCounter ;
        public int remainingTimer ;
    }
    [System.Serializable]
    public class TurnMiss
    {
        public TurnMissData data ;
    }
}