namespace LudoClassicOffline
{
    [System.Serializable]
    public class SignRequestData
    {
        public string lobbyId;
        public int winning_amount;
        public string username;
        public string userId;
        public int maxPlayer;
        public string userProfile;
        public int entryFee;
        public string gameType;
    }
    [System.Serializable ]
    public class SignRequest
    {
        public SignRequestData data;
    }
    [System.Serializable]
    public class SignRequestDataSDK
    {
        public string acessToken;
        public int minPlayer;
        public int maxPlayer;
        public string lobbyId;
        public string gameId;
        public string userId;
        public string username;
        public string userProfile;
        public string entryFee;
        public string winningAmount;
        public bool isUseBot;
        public bool isFTUE;
        public string deviceId;
        public string gameType;
        public string projectType;
        public string gameModeId;
        public string gameModeName;
    }
    [System.Serializable]
    public class SignRequestSDk
    {
        public SignRequestDataSDK data;
    }
}