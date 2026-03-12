using System.Collections.Generic;

namespace LudoClassicOffline
{
    public class LudoNumberUserTrunStart
    {
        public int startTurnSeatIndex;
        public int diceValue;
        public bool isExtraTurn;
        public List<UserDetails> tokenPosition;
    }
    public class UserDetails
    {
        public int seatIndex;
        public List<int> tokenDetails;
    }
}
