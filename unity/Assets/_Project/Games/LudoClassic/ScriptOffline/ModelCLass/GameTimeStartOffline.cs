namespace LudoClassicOffline
{
    [System.Serializable]
    public class GameTimeStartData
    {
        public string en;
        public Data data;
        public int waitingTimer;
    }
    [System.Serializable]
    public class GameTimeStart
    {
        public GameTimeStartData data;
    }
}