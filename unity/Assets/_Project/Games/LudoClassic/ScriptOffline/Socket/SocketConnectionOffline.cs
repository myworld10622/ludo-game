using UnityEngine;
using Best.SocketIO;
using Best.SocketIO.Events;
using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using Newtonsoft.Json.Linq;
using System.Collections.Generic;
using UnityEngine.UI;
using DG.Tweening.Core.Easing;
using System.Collections;
using DG.Tweening;

namespace LudoClassicOffline
{


    public class SocketConnectionOffline : MonoBehaviour
    {
        public string socketUrl = "";
        public ServerType serverType;
        public List<string> ALLServerURL = new List<string>();
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        private static readonly byte[] ENCIV = new byte[16];
        private static byte[] ENCKEY = Encoding.ASCII.GetBytes("DFK8s58uWFCF4Vs8NCrgTxfMLwjL9WUy");
        public GameObject noInternetPopUp;
        // public HeartBeatManagerOffline heartBeatManager;
        [Header("SOCKET STATES")]
        public SocketState socketState;
        public List<LudoNumbersUserData> userDetails = new List<LudoNumbersUserData>();
        public int socketTimeout;
        public LudoNumberUiManagerOffline ludoNumberUiManager;
        public Text typeText;
        public bool isDisconnected = false;
        bool sendSpOnetime;
        public float signupRetryStartInterval = 2f;
        public float signupRetryTimer = 5f;
        public bool isAcknowledgementRecd;
        public bool isSignupRetryEnabled;
        public bool isSignUpRepeatRunning;
        public GameManagerOffline gameManager;
        public SocketManager socketManager;

        private void Start()
        {
            Application.runInBackground = false;
            if (GameManagerOffline.instace.gameRunOnSdk)
            {
                if (int.Parse(MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.portNumber) != 0)
                    socketUrl = MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.hostURL + ":" + MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.portNumber;
                else// No Need to Assign port number if directly code run on server 
                    socketUrl = MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.hostURL;// + ":" + MGPSDK.MGPGameManager.instance.sdkConfig.data.socketDetails.portNumber;
            }
            else
                socketUrl = ALLServerURL[(int)(serverType)];

            Debug.LogError(" Socket Manager ||  Socket URL = " + socketUrl);

            InterNetCheck();
            if (GameManagerOffline.instace.gameRunOnSdk)
            {
                if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.IsFTUE && !MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                {
                    FTUEManagerOffline.Instance.display.SetActive(false);
                    FTUEManagerOffline.Instance.FTUE_Panel.SetActive(true);
                }
                else
                {
                    //CreateSocket();
                }
            }
            else
            {
                CreateSocket();  // changes for set dashbord 27-10-23
            }
        }
        bool isFgbgtrue;

        void OnApplicationPause(bool pause)
        {
            // if (ludoNumberUiManager.gameManager.gameState == GameState.idel) return;
            //
            // if (pause)
            // {
            //     isFgbgtrue = true;
            //     isDisconnected = true;
            //     FoceFullySocketDisconnet();
            // }
            // else
            // {
            //     ludoNumberUiManager.ReconntionAnimation();
            //     CreateSocket();
            // }
        }


        void StartGamePlay(List<userProfile> profiles, string roomName)
        {
            CreateSocket();
        }

        public void FoceFullySocketDisconnet()
        {
            socketManager.Socket.Disconnect();
            ludoNumberUiManager.ReconntionAnimation();
            OpenLoadingScreenText();
        }


        private void OnDisable()
        {
            if (BridgeControllerOffline.Instance != null)
                BridgeControllerOffline.Instance.StartGameScene += StartGamePlay;
        }

        public void OnClickConnect()
        {
            this.gameObject.SetActive(false);
            OpenLoadingScreen("Loading");
            CreateSocket();
        }

        internal void OpenLoadingScreen(string str)
        {
            noInternetPopUp.SetActive(true);
            FTUENoInterNetPopUp.gameObject.SetActive(true);
            ///   heartBeatManager.CloseBtn.interactable = false;
        }
        internal void SetUsernameandID(string username, int userid, string roomName)
        {
            thisUserProfile thisUserProfiles = new thisUserProfile();
            thisUserProfiles.id = userid;
            thisUserProfiles.displayName = username;

        }

        internal bool IsInternetConnectedCheck()
        {
            bool userInternetStatus = (Application.internetReachability != NetworkReachability.NotReachable) && MyInternetRechabilityOffline.IsInternetAvailable;
            Debug.LogWarning("Checking Internet Status : " + userInternetStatus);
            return userInternetStatus;
        }

        public void InterNetCheck()
        {
            //if (Application.internetReachability == NetworkReachability.NotReachable)
            //{
            //    Debug.Log(" --- No InterNet --- ");
            //    noInternetPopUp.SetActive(true);
            //    FTUENoInterNetPopUp.gameObject.SetActive(true);
            //    heartBeatManager.CloseBtn.interactable = false;
            //}
            //else
            //{
            //    Debug.Log(" --- InterNet --- ");
            //    heartBeatManager.ReconntionWait();
            //    ludoNumberUiManager.reconnationPanel.SetActive(false);
            //    ludoNumberUiManager.FTUEReconnationPanel.SetActive(false);
            //    ludoNumberUiManager.reconnationAnimator.enabled = false;
            //    heartBeatManager.CloseBtn.interactable = true;
            //}
        }

        public void CreateSocket()
        {
            Debug.Log("Connecting Socket to URL " + socketUrl);

            //  heartBeatManager.StartCheckAndReconnectFunction();

            //  heartBeatManager.CallSendPingInvoke();

            SocketConnectionStart(socketUrl);// Call socket 
        }


        public SocketOptions SetSocketOption()
        {
            SocketOptions socketOptions = new SocketOptions();
            //socketOptions.ConnectWith = BestHTTP.SocketIO3.Transports.TransportTypes.WebSocket;
            socketOptions.Reconnection = false;
            socketOptions.ReconnectionAttempts = int.MaxValue;
            socketOptions.ReconnectionDelay = TimeSpan.FromMilliseconds(1000);
            socketOptions.ReconnectionDelayMax = TimeSpan.FromMilliseconds(5000);
            socketOptions.RandomizationFactor = 0.5f;
            socketOptions.Timeout = TimeSpan.FromMilliseconds(10000);
            socketOptions.AutoConnect = true;
            socketOptions.QueryParamsOnlyForHandshake = true;
            if (GameManagerOffline.instace.gameRunOnSdk)
            {
                socketOptions.Auth = (manager, socket) => new { token = MGPSDK.MGPGameManager.instance.sdkConfig.data.accessToken };
                Debug.Log("Auth Token  || Recived from backend " + MGPSDK.MGPGameManager.instance.sdkConfig.data.accessToken);
            }
            return socketOptions;
        }

        public void SocketConnectionStart(string socketURL)
        {
            // heartBeatManager.StartCheckAndReconnectFunction();

            if (socketManager != null)
            {
                if (socketManager.Socket.IsOpen)
                    socketManager.Socket.Disconnect();

            }
            if (!socketUrl.Contains("socket.io"))
                socketUrl = socketURL + "/socket.io/";
            else
                socketUrl = socketURL;

            Debug.Log("<color=blue> SocketConnectionOffline || SocketContionStart || Connection URL -> </color>" + socketUrl);
            socketManager = null;
            socketManager = new SocketManager(new Uri(socketUrl), SetSocketOption());
            socketManager.Socket.On(SocketIOEventTypes.Connect, SocketConnected);
            socketManager.Socket.On(SocketIOEventTypes.Disconnect, SocketDisconnect);
            socketManager.Socket.On<Error>(SocketIOEventTypes.Error, SocketError);
            var events = Enum.GetValues(typeof(LudoNumberEventList)) as LudoNumberEventList[];
            for (int i = 0; i < events.Length; i++)
            {
                socketManager.Socket.On<string>(events[i].ToString(), (res) =>
                {
                    var data = res;
                    if (data == null) return;
                    socketState = SocketState.Running;
                    JObject jsonObj = JObject.Parse(res.ToString());
                    string playLoad = jsonObj.GetValue("data").ToString();
                    socketNumberEventReceiver.ReciveData(playLoad);
                });
            }
        }
        private void SocketError(Error error)
        {
            Debug.Log("<Color=blue> <-- SocketError :: SocketError ---> </color>" + error.message);
            socketState = SocketState.Error;
        }
        private void SocketDisconnect()
        {
            Debug.Log("<Color=red> <-- SocketDisconnect :: SocketDisconnect ---> </color>");
            socketState = SocketState.Disconnect;
            isDisconnected = true;
        }
        public GameObject FTUENoInterNetPopUp;
        public void SocketConnected()
        {
            Debug.Log(" <color=green>   Socket Connect Succed </color>");
            Debug.Log("<Color=yellow> <-- Socket_Connection :: Connected ---> </color>");
            noInternetPopUp.gameObject.SetActive(false);
            FTUENoInterNetPopUp.gameObject.SetActive(false);
            // heartBeatManager.HideNoInternetConnectionPanel();
            ludoNumberUiManager.FTUEReconnationPanel.SetActive(false);
            ludoNumberUiManager.reconnationPanel.SetActive(false);
            if (isFgbgtrue)
                isFgbgtrue = false;
        }

        public void OpenLoadingScreenText(string str = "Connecting") => typeText.text = str;
        public void OnSocketConnected()
        {
            Debug.Log("Socket ID => " + socketManager.Socket.Id);
            socketState = SocketState.Connect;
            if (socketManager.Socket.IsOpen)
            {
                socketState = SocketState.Connect;

                if (isDisconnected)
                {
                    if (ludoNumberUiManager.ludoNumbersAcknowledgementHandler.tableId == null && ludoNumberUiManager.SignUpPopup.activeSelf)
                        ludoNumberUiManager.StopReconntionAnimation();
                    else if (ludoNumberUiManager.ludoNumbersAcknowledgementHandler.tableId == null && !ludoNumberUiManager.SignUpPopup.activeSelf && !ludoNumberUiManager.FTUEmanager.FTUE_Panel.activeSelf)
                        gameManager.Signup();
                    else if (ludoNumberUiManager.FTUEmanager.FTUE_Panel.activeSelf)
                        ludoNumberUiManager.StopReconntionAnimation();
                    else
                        gameManager.Reconnect();
                }
                else
                {
                    if (!sendSpOnetime)
                    {
                        sendSpOnetime = true;
                        StartCoroutine(ResetsendSpOnetime());
                    }
                    if (!ludoNumberUiManager.SignUpPopup.activeSelf && !gameManager.gameRunOnSdk) // signup reconnection
                    {
                        ludoNumberUiManager.ReconntionAnimation();
                        OpenLoadingScreenText();
                        Invoke(nameof(ResendSignUp), signupRetryStartInterval);
                        isSignUpRepeatRunning = true;
                    }
                    if (!ludoNumberUiManager.SignUpPopup.activeSelf && gameManager.gameRunOnSdk) // signup reconnection
                    {
                        ludoNumberUiManager.ReconntionAnimation();
                        OpenLoadingScreenText();
                        Invoke(nameof(ResendSignUp), signupRetryStartInterval);
                        isSignUpRepeatRunning = true;
                    }
                }
                //    heartBeatManager.CallSendPingInvoke();
            }
            else { }
            //   heartBeatManager.ResetCalcTimeOnSocketConnected();
        }
        IEnumerator ResetsendSpOnetime()
        {
            yield return new WaitForSecondsRealtime(2f);
            sendSpOnetime = false;
        }
        public void ResendSignUp() => gameManager.Signup();
        public void CancelResendSignUp() => CancelInvoke(nameof(ResendSignUp));

        public void SendDataToSocket(string jsonDataToString, Action<string> onComplete, string eventName)
        {
            Debug.Log("<color><b>" + eventName + "</b></color><color=red> || On request : </color> " + jsonDataToString);
            socketManager.Socket.ExpectAcknowledgement<string>(onComplete).Volatile().Emit(eventName.ToString(), jsonDataToString.ToString());
        }

        #region AESDecrypt
        public static string AESDecrypt(string cipherText, byte[] Key, byte[] IV)
        {
            byte[] cipherTextBytes;
            if (cipherText == null || cipherText.Length <= 0)
                throw new ArgumentNullException("cipherText");
            else
                cipherTextBytes = Convert.FromBase64String(cipherText);

            if (Key == null || Key.Length <= 0)
                throw new ArgumentNullException("Key");
            if (IV == null || IV.Length <= 0)
                throw new ArgumentNullException("Key");

            string plaintext = null;
            using (AesManaged aesAlg = new AesManaged())
            {
                aesAlg.Key = Key;
                aesAlg.IV = IV;
                ICryptoTransform decryptor = aesAlg.CreateDecryptor(aesAlg.Key, aesAlg.IV);
                using (MemoryStream msDecrypt = new MemoryStream(cipherTextBytes))
                {
                    using (CryptoStream csDecrypt = new CryptoStream(msDecrypt, decryptor, CryptoStreamMode.Read))
                    {
                        using (StreamReader srDecrypt = new StreamReader(csDecrypt))
                        {
                            plaintext = srDecrypt.ReadToEnd();
                        }
                    }
                }
            }
            return plaintext;
        }
        #endregion  

        #region AESEncrypt
        public static string AESEncrypt(string plainText, byte[] Key, byte[] IV)
        {
            // Check arguments.
            if (plainText == null || plainText.Length <= 0)
                throw new ArgumentNullException("plainText");
            if (Key == null || Key.Length <= 0)
                throw new ArgumentNullException("Key");
            if (IV == null || IV.Length <= 0)
                throw new ArgumentNullException("Key");


            byte[] encrypted;

            using (AesManaged aesAlg = new AesManaged())
            {
                aesAlg.Key = Key;
                aesAlg.IV = IV;
                ICryptoTransform encryptor = aesAlg.CreateEncryptor(aesAlg.Key, aesAlg.IV);
                using (MemoryStream msEncrypt = new MemoryStream())
                {
                    using (CryptoStream csEncrypt = new CryptoStream(msEncrypt, encryptor, CryptoStreamMode.Write))
                    {
                        using (StreamWriter swEncrypt = new StreamWriter(csEncrypt))
                        {
                            swEncrypt.Write(plainText);
                        }
                        encrypted = msEncrypt.ToArray();
                    }
                }
            }
            return Convert.ToBase64String(encrypted);

        }
        #endregion

        public enum SocketState
        {
            None,
            Close,
            Connect,
            Open,
            Running,
            Error,
            Disconnect
        }
        public enum ServerType
        {
            Live = 0,
            Dev = 1,
            Local = 2,
        }
    }
    public class thisUserProfile
    {
        public int id;
        public string mobileNumber;
        public string displayName;
        public string avatar;

        public thisUserProfile()
        {
            id = 0;
            mobileNumber = "";
            displayName = "";
            avatar = "";
        }
    }
}
