namespace LudoClassicOffline 
{
    [System.Serializable]
    public class MoveTokenData
    {
        public int tokenMove ;
        public bool flashMove ;
    }
    [System.Serializable]
    public class MoveToken
    {
        public MoveTokenData data = new MoveTokenData() ;
    }
}