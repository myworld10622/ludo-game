using System.Collections.Generic;
namespace LudoClassicOffline
{
    [System.Serializable]
    public class TieBreakerModelData
    {
        public int winnerIndex ;
        public bool isCancelToken ;
        public List<UserDatum> userData ;
    }
    [System.Serializable]
    public class TieBreakerModel
    {
        public string en ;
        public TieBreakerModelData data ;
    }
    [System.Serializable]
    public class UserDatum
    {
        public int seatIndex ;
        public int furthestToken ;
        public int tokenIndex ;
    }
}