using System.Collections;
using System.Collections.Generic;
using UnityEngine;
namespace MGPSDK
{
    [System.Serializable]
    public class SDKConfiguration
    {
        [System.Serializable]
        public class SDKConfig
        {
            public SDKConfigData data;
        }
        [System.Serializable]
        public class SDKConfigData
        {
            public string projectType;
            public string accessToken;
            public LobbyData lobbyData;           
            public GameData gameData;
            public List<PlayerDetails> playerData;
            public SelfUserDetails selfUserDetails;
            public SocketDetails socketDetails;
            public Location location;
        }

        [System.Serializable]
        public class GameData
        {
            public string assetsPath;
            public string game;
            public string gameId;
            public bool isLandscapeGame;
            public bool isPlay;
        }
        [System.Serializable]
        public class LobbyData
        {
            public int minPlayer;
            public int noOfPlayer;
            public int noOfRounds;
            public string _id;
            public string moneyMode;
            public bool isUseBot;
            public bool IsFTUE;
            public double entryFee;
            public double winningAmount;
            public float minEntryFee;
            public float maxEntryFee;
            public string gameModeName;
            public string gameModeId;
        }
        [System.Serializable]
        public class PlayerDetails
        {
            public string name;
            public string userId;
            public string profilPic;
        }
        [System.Serializable]
        public class Location
        {
            public double latitude;
            public double longitude;
        }
        [System.Serializable]
        public class SocketDetails
        {
            public string hostURL;
            public string portNumber;
            public int socketTimeOut;
        }

        [System.Serializable]
        public class SelfUserDetails
        {
            public string userID;
            public string mobileNumber;
            public string displayName;
            public string avatar;

            public override string ToString()
            {
                return string.Format("userID: {0}, mobileNumber: {1}, displayName: {2}, avatar: {3}",
                                     userID, mobileNumber, displayName, avatar);
            }
        }

    }


}