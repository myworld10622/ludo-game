using System.Collections.Generic;

namespace LudoClassicOffline 
{
    [System.Serializable]
    public class BattleFinishData
    {
        public string en ;
        public BattleFinishData data ;
        public string showResultOnAlert ;
        public Payload payload ;
    }
    [System.Serializable]
    public class Payload
    {
        public List<AvtarData> players ;
    }
    [System.Serializable]
    public class AvtarData
    {
        public string userId ;
        public string username ;
        public int seatIndex ;
        public bool isPlaying ;
        public string avatar ;
        public int score ;
        public double winAmount ;
        public string winType;
    }
    [System.Serializable]
    public class BattleFinish
    {
        public BattleFinishData data ;
    }
}
