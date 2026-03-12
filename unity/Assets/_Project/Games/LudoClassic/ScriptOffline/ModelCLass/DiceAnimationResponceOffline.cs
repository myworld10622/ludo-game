using UnityEngine.Tilemaps;

namespace LudoClassicOffline
{
    [System.Serializable ]
    public class DiceAnimationData
    {
        public int startTurnSeatIndex ;
        public int diceValue ;
        public bool autoMove;
        public bool isExtraTurn ;
        public int autoMoveToken;
        public bool isSix;
    }
    [System.Serializable]
    public class DiceAnimationResponce
    {
        public string en ;
        public DiceAnimationData data ;
    }


}