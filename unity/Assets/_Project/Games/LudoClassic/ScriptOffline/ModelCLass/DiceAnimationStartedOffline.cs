namespace LudoClassicOffline
{
    [System.Serializable]
    public class MetricsDiceAnimation
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
    public class DiceAnimationSend
    {
        public MetricsDiceAnimation metrics;
        public object data;
    }


}