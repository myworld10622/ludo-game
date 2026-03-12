using System.Collections;
using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{
    [System.Serializable]
    public class LeaveTableEData
    {
        public bool success;
        public object error;
        public int playerSeatIndex;
    }
    [System.Serializable]
    public class MetricsLeave
    {
        public string uuid;
        public string ctst;
        public string srct;
        public long srpt;
        public string crst;
        public string userId;
        public int apkVersion;
        public string tableId;
    }
    [System.Serializable]
    public class LeaveTableE
    {
        public LeaveTableEData data;
        public MetricsLeave metrics;
        public string userId;
        public string tableId;
    }
}