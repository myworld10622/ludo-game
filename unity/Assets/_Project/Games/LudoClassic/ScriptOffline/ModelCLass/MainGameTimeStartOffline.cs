namespace LudoClassicOffline
{
    [System.Serializable]
    public class GameMainTimerData
    {
        public int waitingTimer;
    }
    [System.Serializable]
    public class GameMainTimer
    {
        public string en;
        public GameMainTimerData data;
    }
}