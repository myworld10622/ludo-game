namespace LudoClassicOffline
{
    [System.Serializable]
    public class ReconnectData
    {
        public string roomName;
        public string userId;
    }
    [System.Serializable]
    public class MetricsReconnect
    {
        public string uuid;
        public string ctst;
        public string srct;
        public string srpt;
        public string crst;
        public string userId;
        public int apkVersion;
        public string tableId;
    }
    [System.Serializable]
    public class Reconnect
    {
        public MetricsReconnect metrics;
        public ReconnectData data;
    }
}