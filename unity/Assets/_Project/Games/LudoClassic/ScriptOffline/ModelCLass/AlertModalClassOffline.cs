namespace LudoClassicOffline
{
    [System.Serializable]
    public class AlertMessageData
    {
        public string en ;
        public AlertMessageData data ;
        public string message ;
        public int errorCode ;
    }
    [System.Serializable]
    public class AlertMessage
    {
        public AlertMessageData data ;
    }
}