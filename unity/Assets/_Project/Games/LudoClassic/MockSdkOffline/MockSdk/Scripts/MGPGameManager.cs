using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using System;
using UnityEngine.SceneManagement;
using System.Runtime.InteropServices;
using Newtonsoft.Json;
using UnityEngine.Networking;
namespace MGPSDK
{

    public class MGPGameManager : MonoBehaviour
    {
        public string AuthToken;
        public string __userId;
        public string lobyid;
        public string userName;
        public string gameId;
        public bool Isbot, IsFTUE, isPlay;
        public string gameModeId;
        public int noOfPlayer;
        public int nofRound;
        public float entryFee;
        public float minEntryFee;
        public string gameModeName;
        public string projectType;
        public float maxEntryFee;
        public ServerType serverType;
        public List<string> ALLServerURL = new List<string>();
        public List<int> ALLServerPortNo = new List<int>();
        internal string S3URL = "";
        internal int portNo;
        public static MGPGameManager instance;
        string[] scenePaths;
        public static string sdkConfigJsonString;
        public AssetBundle assetBundle;
        public SDKConfiguration.SDKConfig sdkConfig;

        private void Awake()
        {
            Debug.Log("MGPGameManager || Awake ");
            instance = this;
            DontDestroyOnLoad(this.gameObject);
            S3URL = ALLServerURL[(int)(serverType)];
            portNo = ALLServerPortNo[(int)(serverType)];
            sdkConfigJsonString = "{\"data\":{\"accessToken\":\"" + AuthToken + "\",\"projectType\":\"" + projectType + "\",\"lobbyData\":{\"_id\":\"" + lobyid + "\",\"gameModeId\":\"" + gameModeId + "\",\"gameModeName\":\"" + gameModeName + "\",\"entryFee\":" + entryFee + "," +
                 "\"noOfPlayer\":" + noOfPlayer + ",\"minPlayer\":2,\"maxEntryFee\":" + maxEntryFee + ",\"minEntryFee\":" + minEntryFee + ",\"noOfRounds\":" + nofRound + ",\"winningAmount\":18,\"moneyMode\":\"texas\",\"isUseBot\":" + Isbot.ToString().ToLower() + "," +
                 "\"IsFTUE\":" + IsFTUE.ToString().ToLower() + "},\"gameData\":{\"assetsPath\":\"/data/user/0/com.threegames/files/636e40187d9acb813b72e411\",\"game\":\"CallBreak\",\"gameID\":\"" + gameId + "\"," +
                 "\"isPlay\":" + isPlay.ToString().ToLower() + "  },\"playerData\":[{\"name\":\"Ketul\",\"userId\":\"636ce30456ca6ca392a8dc6b\",\"profilPic\":\"\"}],\"location\":{\"latitude\":\"21.2124144\"," +
                 "\"longitude\":\"72.8502981\"},\"socketDetails\":{\"hostURL\":\"" + S3URL + "\",\"portNumber\":\"" + portNo + "\",\"socketTimeOut\":0}," +
                 "\"selfUserDetails\":{\"avatar\":\"https://artoon-game-platform.s3.amazonaws.com/mgp/ProfileImages/ProfileImages-1691467544636.png\",\"displayName\":\"" + userName + "\",\"mobileNumber\":\"9996543298\"," +
                 "\"userID\":\"" + __userId + "\"}}}"; Debug.Log(" <color=green>MGPGameManager || Awake || GetIntent || getStringExtra || sdkConfigJsonString :</color>" + sdkConfigJsonString);
            CallBackMethod(sdkConfigJsonString);
        }

        #region Custom Method
        public void CallBackMethod(string jsonString)
        {
            Debug.Log("MGPGameManager || CallBackMethod || jsonString : " + jsonString);
            sdkConfig = JsonConvert.DeserializeObject<SDKConfiguration.SDKConfig>(jsonString);
            Debug.Log("MGPGameManager || CallBackMethod || FilePath : " + sdkConfig.data.gameData.assetsPath);
            Debug.Log("<color=green>MGPGameManager || CallBackMethod ||  SOCKET URL FROM CMS ==> " + sdkConfig.data.socketDetails.hostURL + ":" + sdkConfig.data.socketDetails.portNumber + "</color>");

            //Debug.Log("Application.dataPath " + Application.dataPath);
            //Debug.Log("Application.persistentDataPath " + Application.streamingAssetsPath);
            //try
            //{
            //    if (!string.IsNullOrEmpty(Application.persistentDataPath))
            //    {
            //        assetBundle = AssetBundle.LoadFromFile(Application.streamingAssetsPath+"/callbreak");

            //        if (assetBundle != null)
            //        {
            //            if (assetBundle.isStreamedSceneAssetBundle)
            //            {
            //                scenePaths = assetBundle.GetAllScenePaths();

            //                for (int i = 0; i < scenePaths.Length; i++)
            //                {
            //                    Debug.Log("MGPGameManager || CallBackMethod || i: " + i + " || scenePaths || " + scenePaths[i]);
            //                }
            //                LoadAssteBundleScene(sdkConfig.data.gameData.game);
            //            }
            //        }
            //        else
            //        {
            //            Debug.LogError("text.text =  _asset bundle not Loaded   + (Application.streamingAssetsPath +  / + SceneURL).ToString()");
            //        }
            //        Debug.Log(" CallBackMethod => Yes, Asset Bundle And Game Name is Loaded ");
            //    }
            //    else
            //    {
            //        Debug.Log("Asset Bundle Could Not Be Found");
            //    }
            //}
            //catch (Exception ex)
            //{
            //    Debug.Log("Exception || " + ex.ToString());
            //}
        }

        public void LoadAssteBundleScene(string sceneName)
        {
            Debug.Log("MGPGameManager || CallBackMethod || LoadAssteBundleScene || sceneName || " + sceneName);
            SceneManager.LoadScene(sceneName);
        }
        #endregion
        public void OnClickQuite()
        {
            Debug.Log("MGPGameManager || OnClickQuite ");
#if UNITY_EDITOR
            Debug.Log("MGPGameManager || OnClickQuite || UNITY_EDITOR");
            UnityEditor.EditorApplication.isPlaying = false;
#elif UNITY_ANDROID && !UNITY_EDITOR            
            Application.Quit();
#endif
        }
        public AndroidJavaObject SendMsgToNative()
        {
            return new AndroidJavaObject("com.artoon.reactunitydemo.GGLauncher");
        }
        #region ServerTypes
        public enum ServerType
        {
            Live = 0,
            Dev = 1,
            Staging = 2,
            LocalKishan = 3,
            LocalVaibhav = 4
        }
        #endregion
    }
}