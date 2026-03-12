// using System;
// using System.Collections;
// using System.Collections.Generic;
// using UnityEngine;
// using UnityEngine.UI;
// using static LudoClassicOffline.SocketConnectionOffline;

// namespace LudoClassicOffline
// {

//     public class HeartBeatManagerOffline : MonoBehaviour
//     {
//         [SerializeField] int firstTimeDelay = 4, timeIntervalforHeartBeat = 1;

//         internal long pingTime;
//         internal long pongTime;
//         internal int pingMissedCounter;
//         public int NetworkPingTime;
//         public int MaxPingCounter;
//         [SerializeField] private Sprite noNetworkSprite;
//         [SerializeField] private Sprite networkSprite;
//         [SerializeField] private Image networkIndicatorImage;
//         [SerializeField] private Text networkLatency;
//         internal float latency;
//         public List<string> NetworkIndicatorColors;

//         public bool isSlowNetPopupEnabled;
//         internal int Network_id;
//         public List<int> PongTimer;
//         public GameObject popup;
//         public SocketConnectionOffline socketConnection;
//         public LudoNumberGsNewOffline ludoNumberGsNew;
//         internal bool IsGameEnded;
//         public bool internetPopupFlowEnabled;
//         private float calcTime = 0f;

//         private bool socketConnectionEstablished = false;
//         public bool isGameExperience;
//         public Button CloseBtn;

//         // internal void OnReceiveHBClient(JSONObject data)
//         // {
//         //     if (internetPopupFlowEnabled)
//         //     {
//         //         HideNoInternetConnectionPanel();
//         //     }
//         //     ResetCalcTimeOnSocketConnected();
//         // }
//         public void ResetCalcTimeOnSocketConnected() => calcTime = Time.realtimeSinceStartup;

//         public void CallSendPingInvoke()
//         {
//             networkLatency.text = "";
//             pingMissedCounter = 0;
//             ResetPingRepeating();
//             InvokeRepeating(nameof(sendPing), 1f, NetworkPingTime);
//             socketConnectionEstablished = true;
//         }
//         internal void HideNoInternetConnectionPanel()
//         {
//             ReconntionWait();
//             CloseBtn.interactable = true;
//         }

//         public void ReconntionWait() => StartCoroutine(WaitForNoCloseToReconntionPanel());

//         IEnumerator WaitForNoCloseToReconntionPanel()
//         {
//             yield return new WaitForSeconds(5f);
//         }
//         public void OnReceiveHB(JSONObject data)
//         {
//             char trim_char_arry = '"';
//             int N_id = int.Parse(data.GetField("data").GetField("Nid").ToString().Trim(trim_char_arry));
//             pongTime = new DateTimeOffset(DateTime.Now).ToUnixTimeMilliseconds();
//             networkLatency.text = (pongTime - pingTime).ToString();
//             long timeDuration = pongTime - pingTime;
//             latency = timeDuration;
//             bool isSlowInternetPopupEnabled = isSlowNetPopupEnabled;
//             if (Network_id != N_id)
//                 timeDuration = 5000;

//             if (timeDuration >= PongTimer[0] && timeDuration < PongTimer[1])
//             {
//                 if (isSlowInternetPopupEnabled)
//                     popup.SetActive(false);

//                 SetNetworkIndicatorColor(0);
//             }
//             else if (timeDuration > PongTimer[1] && timeDuration < PongTimer[2])
//             {
//                 if (isSlowInternetPopupEnabled)
//                     popup.SetActive(false);

//                 SetNetworkIndicatorColor(1);
//             }
//             else if (timeDuration > PongTimer[2] && timeDuration < PongTimer[3])
//             {
//                 if (isSlowInternetPopupEnabled)
//                     popup.SetActive(true);

//                 SetNetworkIndicatorColor(2);
//             }
//             else if (timeDuration > PongTimer[3] && timeDuration < PongTimer[4])
//             {
//                 if (isSlowInternetPopupEnabled)
//                     popup.SetActive(true);

//                 SetNetworkIndicatorColor(3);
//             }
//             else
//                 ShowNoNetworkImage();

//             pingMissedCounter = 0;
//             CancelInvoke(nameof(CheckPingPongCount));
//         }

//         public void SetNetworkIndicatorColor(int colorIndex)
//         {
//             Color indicatorColor;
//             ColorUtility.TryParseHtmlString(NetworkIndicatorColors[colorIndex], out indicatorColor);
//             var networkIndicator = networkIndicatorImage;

//             networkIndicator.color = indicatorColor;
//             networkIndicator.sprite = networkSprite;
//         }

//         public void ShowNoNetworkImage()
//         {
//             var networkIndicator = networkIndicatorImage;
//             networkIndicator.sprite = noNetworkSprite;
//             networkIndicator.color = Color.white;
//         }

//         public void ShowNoInternetConnectionPanel(string message)
//         {
//             ShowNoNetworkImage();
//             Debug.Log("ShowNoInternetConnectionPanel() | " +
//                              "Latency: " + latency + "\tMessage : " +
//                              message);
//             socketConnection.noInternetPopUp.SetActive(true);
//             socketConnection.FTUENoInterNetPopUp.gameObject.SetActive(true);
//             CloseBtn.interactable = false;
//         }

//         internal IEnumerator CheckAndReConnectSocket()
//         {
//             if ((socketConnection.socketState != SocketState.Disconnect && socketConnection.socketState != SocketState.Error &&
//                 socketConnection.socketState != SocketState.None && !IsGameEnded))
//             {
//                 yield return new WaitForSecondsRealtime(1);
//             }
//             else
//             {
//                 if (!IsGameEnded)
//                 {
//                     if (socketConnection.IsInternetConnectedCheck())
//                     {
//                         UnityEngine.Debug.Log("CheckAndReConnectSocket : Socket State : " + socketConnection.socketState);
//                         ConnectSocket();
//                     }
//                     else
//                     {
//                         ShowNoInternetConnectionPanel("Internet not available");
//                         socketConnection.socketManager.Socket.Disconnect();
//                     }
//                 }
//                 yield return new WaitForSecondsRealtime(1);
//             }
//         }

//         void CloseAndReConnectCheck() => StartCoroutine(CheckAndReConnectSocket());


//         internal void StartCheckAndReconnectFunction()
//         {
//             CancelInvoke(nameof(CloseAndReConnectCheck));
//             InvokeRepeating(nameof(CloseAndReConnectCheck), firstTimeDelay, timeIntervalforHeartBeat);
//         }
//         internal void ConnectSocket()
//         {
//             Debug.Log("Open Socket Manually");
//             socketConnection.CreateSocket();
//         }

//         public void CheckPingPongCount()
//         {
//             Debug.Log("NetworkPingTime \t" + NetworkPingTime + " \t MaxPingCounter \t" + MaxPingCounter);
//             Debug.Log("missPingNetwork \t" + pingMissedCounter + " \t PingCounter \t" + pingMissedCounter);

//             pingMissedCounter++;
//             if (!socketConnection.IsInternetConnectedCheck())
//             {
//                 Debug.Log("showing no network indicator");
//                 ShowNoNetworkImage();
//             }

//             if (pingMissedCounter > MaxPingCounter)
//             {
//                 Debug.Log("CheckPingPongCount() : pingMissedCounter " + pingMissedCounter + "\t>=" + "\tMaxPingCounter " +
//                     MaxPingCounter);
//                 ResetPingRepeating();
//                 CloseSocketForceFully();
//             }
//         }
//         internal void CloseSocketForceFully()
//         {
//             Debug.Log("CLIENT CLOSING SOCKET FORCEFULLY");
//             if (socketConnection.socketManager.Socket.IsOpen)
//                 (socketConnection.socketManager as BestHTTP.SocketIO3.IManager).Close(false);
//         }
//         public void ResetPingRepeating()
//         {
//             pingMissedCounter = 0;
//             CancelInvoke(nameof(sendPing));
//             CancelInvoke(nameof(CheckPingPongCount));
//         }
//         public void sendPing()
//         {
//             if ((socketConnection.socketState != SocketState.Disconnect && socketConnection.socketState != SocketState.Error &&
//                 socketConnection.socketState != SocketState.None && !IsGameEnded))
//             {
//                 pingTime = new DateTimeOffset(DateTime.Now).ToUnixTimeMilliseconds();
//                 SendHeartBeatEvent();
//                 CancelInvoke(nameof(CheckPingPongCount));
//                 Invoke(nameof(CheckPingPongCount), NetworkPingTime);
//             }
//         }
//         internal void SendHeartBeatEvent()
//         {

//             var root = LudoNumbersMetricsData.GetRootObject();
//             var data = new JSONObject();
//             data.AddField("Nid", Network_id += 1);
//             root.AddField("data", data);
//             SendDataWithAcknowledgement(root, ludoNumberGsNew.ludoNumbersAcknowledgementHandler.HeartBeatReceived,
//                  "HEART_BEAT");
//         }
//         public void SendDataWithAcknowledgement(JSONObject jsonData, Action<string> onComplete, string eventName)
//         {
//             string jsonDataToString = jsonData.ToString();
//             if (eventName != "HEART_BEAT" && eventName != "HEART_BEAT_CLIENT")
//             {
//                 Debug.Log("<color=red>CLIENT SENDING EVENT: " + eventName + "\t DATA: " + jsonDataToString + "</color>");
//             }
//             try
//             {
//                 string en = eventName.ToString();
//                 socketConnection.socketManager.Socket.ExpectAcknowledgement<string>(onComplete).Volatile().Emit(eventName.ToString(), jsonDataToString.ToString());
//             }
//             catch (Exception ex)
//             {
//                 Debug.LogError("Exception SendData Methods -> " + ex.ToString());
//             }
//         }

//         public static class LudoNumbersMetricsData
//         {
//             public static JSONObject GetRootObject()
//             {
//                 JSONObject root = new JSONObject();
//                 JSONObject metrics = new JSONObject();
//                 metrics.AddField(MetricsKeys.RAMDOM_USERID_KEY, GetUID());
//                 long ctst = new DateTimeOffset(DateTime.Now).ToUnixTimeMilliseconds();
//                 metrics.AddField(MetricsKeys.CURRENT_TIMESTAMP_KEY, ctst.ToString()); //Update timestamp for every request
//                 metrics.AddField(MetricsKeys.CURRENT_TIMESTAMP_CURRENT_TIMESTAMP_SERVER_KEY, "");
//                 metrics.AddField(MetricsKeys.CURRENT_TIMESTAMP_SERVER_REPLY_KEY, "");
//                 metrics.AddField(MetricsKeys.CURRENT_TIMESTAMP_CLIENT_ACKNOWLEDGEMENT_KEY, "1.2");
//                 metrics.AddField(MetricsKeys.USER_ID_KEY, "");
//                 metrics.AddField(MetricsKeys.APK_VERSION_KEY, 101);
//                 metrics.AddField(MetricsKeys.TABLE_ID_KEY, "");
//                 root.AddField("metrics", metrics);
//                 return root;

//             }
//             static string GetUID()
//             {
//                 Guid myuuid = Guid.NewGuid();
//                 string myuuidAsString = myuuid.ToString();
//                 return myuuidAsString;
//             }
//         }
//         public static class MetricsKeys
//         {
//             public const string RAMDOM_USERID_KEY = "uuid";
//             public const string CURRENT_TIMESTAMP_KEY = "ctst";
//             public const string CURRENT_TIMESTAMP_CURRENT_TIMESTAMP_SERVER_KEY = "srct";
//             public const string CURRENT_TIMESTAMP_SERVER_REPLY_KEY = "srpt";
//             public const string CURRENT_TIMESTAMP_CLIENT_ACKNOWLEDGEMENT_KEY = "crst";
//             public const string USER_ID_KEY = "userId";
//             public const string APK_VERSION_KEY = "apkVersion";
//             public const string TABLE_ID_KEY = "tableId";
//         }
//     }
// }
