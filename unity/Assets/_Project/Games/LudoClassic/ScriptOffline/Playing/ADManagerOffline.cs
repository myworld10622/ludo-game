// using GoogleMobileAds.Api;
// using GoogleMobileAds.Common;
// using System;
// using System.Collections;
// using System.Collections.Generic;
// using UnityEngine;
// namespace LudoClassicOffline
// {
//     public class ADManagerOffline : MonoBehaviour
//     {
//         public static ADManagerOffline instance;

//         public bool isLiveAds;

//         public AdPosition bannerPosition;

//         public string live_bannerID = "ca-app-pub-9036292764302696/9923523917";
//         public string live_interstitialID = "ca-app-pub-9036292764302696/2871864553";
//         public string live_rewardID = "ca-app-pub-9036292764302696/1624601185";
//         public string live_appOpenID = "ca-app-pub-9036292764302696/7540106995";

//         public BannerView _bannerView;
//         public InterstitialAd interstitialAd;
//         public RewardedAd rewardedAd;
//         public AppOpenAd appOpenAd;

//         internal static string bannerID;
//         internal static string interstitialID;
//         internal static string rewardID;
//         internal static string appOpenID;

//         public static bool isInterstitialLoad;
//         public static bool isRewardLoad;
//         public static bool isAdmobInit;
//         public static bool isAdsWatched;
//         public static bool isAppStateChangeFromInterstitialAd;
//         public static bool isSceneReload;
//         private DateTime _expireTime;


//         public string interstialAdString;

//         public DashBoardManagerOffline dashBoardManager;
//         private void OnEnable()
//         {
//             AppStateEventNotifier.AppStateChanged += OnAppStateChanged;
//         }

//         private void OnDisable()
//         {
//             AppStateEventNotifier.AppStateChanged -= OnAppStateChanged;
//         }
//         private void Awake()
//         {
//             if (instance == null)
//             {
//                 instance = this;
//                 DontDestroyOnLoad(gameObject);
//             }
//             else
//             {
//                 Destroy(gameObject);
//             }

//             if (isLiveAds)
//             {
//                 bannerID = live_bannerID;
//                 interstitialID = live_interstitialID;
//                 rewardID = live_rewardID;
//                 appOpenID = live_appOpenID;
//             }
//             else
//             {
//                 bannerID = "ca-app-pub-3940256099942544/6300978111";
//                 interstitialID = "ca-app-pub-3940256099942544/1033173712";
//                 rewardID = "ca-app-pub-3940256099942544/5224354917";
//                 appOpenID = "ca-app-pub-3940256099942544/3419835294";
//             }

//             AdmobInit();

//         }

//         public void AdmobInit()
//         {
//             if (Application.internetReachability != NetworkReachability.NotReachable)
//             {
//                 Debug.Log(PlayerPrefs.GetInt("removeAds"));
//                 if (PlayerPrefs.GetInt("removeAds") != 1)
//                 {
//                     MobileAds.Initialize((InitializationStatus initStatus) =>
//                     {
//                         Debug.Log(" === Successfully Integrated Admob ===");
//                         isAdmobInit = true;
//                         LoadAppOpenAd();
//                         LoadInterstitialAd();
//                         LoadRewardedAd();
//                         LoadBanner(true);
//                     });
//                 }
//             }

//         }
//         #region AdmobInit
//         public void Start()
//         {
//         }
//         #endregion

//         #region BANNER

//         public void LoadBanner(bool isBottom)
//         {
//             if (PlayerPrefs.GetInt("removeAds") == 0)
//             {
//                 if (isBottom)
//                 {
//                     bannerPosition = AdPosition.Bottom;
//                 }
//                 else
//                 {
//                     bannerPosition = AdPosition.Top;
//                 }
//                 RequestBannerAds();
//             }
//         }
//         void RequestBannerAds()
//         {
//             if (_bannerView != null)
//             {
//                 DestroyAd();

//             }
//             if (_bannerView == null)
//             {
//                 _bannerView = new BannerView(bannerID, AdSize.Banner, bannerPosition);
//             }
//             var adRequest = new AdRequest();
//             adRequest.Keywords.Add("unity-admob-sample");
//             Debug.Log("Loading banner ad.");
//             _bannerView.LoadAd(adRequest);
//         }

//         public void HideBanner(bool isHide)
//         {
//             if (_bannerView != null)
//             {
//                 if (isHide)
//                 {
//                     _bannerView.Hide();
//                 }
//                 else
//                 {
//                     _bannerView.Show();
//                 }
//             }
//         }

//         public void DestroyAd()
//         {
//             if (_bannerView != null)
//             {
//                 Debug.Log("Destroying banner ad.");
//                 _bannerView.Destroy();
//                 _bannerView = null;
//             }
//         }
//         #endregion

//         #region Interstitial
//         public void LoadInterstitialAd()
//         {
//             if (PlayerPrefs.GetInt("removeAds") != 1)
//             {
//                 if (interstitialAd != null)
//                 {
//                     interstitialAd.Destroy();
//                     interstitialAd = null;
//                 }

//                 var adRequest = new AdRequest();
//                 adRequest.Keywords.Add("unity-admob-sample");

//                 InterstitialAd.Load(interstitialID, adRequest,
//                     (InterstitialAd ad, LoadAdError error) =>
//                     {
//                         if (error != null || ad == null)
//                         {
//                             Debug.LogError("interstitial ad failed to load an ad " +
//                                            "with error : " + error + " " + interstitialID + " || " + isInterstitialLoad);
//                             isInterstitialLoad = false;
//                             return;
//                         }

//                         Debug.Log("Interstitial ad loaded with response : "
//                                   + ad.GetResponseInfo());

//                         isInterstitialLoad = true;
//                         interstitialAd = ad;
//                         RegisterEventHandlers(ad);
//                         RegisterReloadHandler(ad);

//                         //if (Splashscreen.instance != null)
//                         //    Splashscreen.instance.splashPanel.SetActive(false);

//                     });
//             }
//         }
//         public void ShowInterstitialAd()
//         {

//             if (isInterstitialLoad)
//             {
//                 //Loader.instance.LoaderPanel.SetActive(true);
//                 Debug.Log("Showing interstitial ad.");
//                 interstitialAd.Show();
//             }
//             else
//             {
//                 if (Application.internetReachability == NetworkReachability.NotReachable)
//                 {
//                     //Loader.instance.CloseAdLoaderPanel();
//                     return;
//                 }
//                 if (!isAdmobInit)
//                 {
//                     AdmobInit();
//                 }
//                 else
//                 {
//                     LoadInterstitialAd();
//                 }
//             }
//         }
//         private void RegisterEventHandlers(InterstitialAd ad)
//         {
//             ad.OnAdPaid += (AdValue adValue) =>
//             {
//                 Debug.Log(String.Format("Interstitial ad paid {0} {1}.",
//                     adValue.Value,
//                     adValue.CurrencyCode));
//             };
//             ad.OnAdImpressionRecorded += () =>
//             {
//                 Debug.Log("Interstitial ad recorded an impression.");
//             };
//             ad.OnAdClicked += () =>
//             {
//                 Debug.Log("Interstitial ad was clicked.");
//             };
//             ad.OnAdFullScreenContentOpened += () =>
//             {
//                 Debug.Log("Interstitial ad full screen content opened.");
//                 isInterstitialLoad = false;
//                 interstitialAd = null;
//                 //Loader.instance.LoaderPanel.SetActive(false);
//             };
//             ad.OnAdFullScreenContentClosed += () =>
//             {
//                 //Loader.instance.LoaderPanel.SetActive(false);

//                 isAdsWatched = false;
//                 isAppStateChangeFromInterstitialAd = true;

//                 // SetInterstialeData();
//                 /* if (PlayerStatus.MainMenu)
//                  {
//                      PlayerStatus.MainMenu = false;
//                      SceneManager.LoadScene("MainMenu");

//                  }
//                  else if (PlayerStatus.GameScreen)
//                  {
//                      PlayerStatus.GameScreen = false;
//                      SceneManager.LoadScene("GameScreen");
//                  }
//                  else if (PlayerStatus.PlayBtn)
//                  {
//                      if (PlayerPrefs.GetInt("tutorial") == 0)
//                      {
//                          SceneManager.LoadScene("HelpScreen");
//                      }
//                      else
//                      {
//                          SceneManager.LoadScene("GameScreen");
//                      }
//                  }*/
//                 Debug.Log("Interstitial ad full screen content closed.");
//             };
//             // Raised when the ad failed to open full screen content.
//             ad.OnAdFullScreenContentFailed += (AdError error) =>
//             {
//                 Debug.LogError("Interstitial ad failed to open full screen content " +
//                                "with error : " + error);
//             };
//         }

//         void SetInterstialeData()
//         {
//             if (interstialAdString == "back")
//             {
//                 GameManagerOffline.instace.LeaveTable();
//                 interstialAdString = "";
//             }
//         }

//         private void RegisterReloadHandler(InterstitialAd ad)
//         {
//             ad.OnAdFullScreenContentClosed += () =>
//             {
//                 Debug.Log("Interstitial Ad full screen content closed.");
//                 SetInterstialeData();
//                 LoadInterstitialAd();
//             };
//             ad.OnAdFullScreenContentFailed += (AdError error) =>
//             {
//                 Debug.LogError("Interstitial ad failed to open full screen content " +
//                                "with error : " + error);

//                 SetInterstialeData();
//                 LoadInterstitialAd();
//             };
           
//         }
//         #endregion

//         #region REWARD
//         public void LoadRewardedAd()
//         {
//             if (rewardedAd != null)
//             {
//                 rewardedAd.Destroy();
//                 rewardedAd = null;
//             }
//             Debug.Log("Loading the rewarded ad.");

//             var adRequest = new AdRequest();
//             adRequest.Keywords.Add("unity-admob-sample");

//             RewardedAd.Load(rewardID, adRequest,
//                 (RewardedAd ad, LoadAdError error) =>
//                 {
//                     if (error != null || ad == null)
//                     {
//                         isRewardLoad = false;
//                         Debug.LogError("Rewarded ad failed to load an ad " +
//                                        "with error : " + error + " " + rewardID + " || " + isRewardLoad);
//                         return;
//                     }

//                     Debug.Log("Rewarded ad loaded with response : "
//                               + ad.GetResponseInfo());
//                     rewardedAd = ad;
//                     RegisterEventHandlers(ad);
//                     RegisterReloadHandler(ad);
//                     isRewardLoad = true;
//                 });
//         }
//         public void ShowRewardedAd()
//         {
//             if (isRewardLoad)
//             {
//                 rewardedAd.Show((Reward reward) =>
//                 {
//                     // TODO: Reward the user.
//                     PlayerPrefs.SetString("TimeAfterReward", DateTime.Now.ToString());
//                     int chips = PlayerPrefs.GetInt("Totalchips");
//                     chips = chips + 200;
//                     PlayerPrefs.SetInt("Totalchips", chips);
//                     dashBoardManager.UpdateChips(chips);
//                     //  GameManager.instance.TimerSetAfterReward(60);

//                     //   dashBoardManager.RewarderAddtimer();
//                     PlayerPrefs.SetString("TimeAfterReward", DateTime.Now.ToString());
//                     dashBoardManager.RewarderAddtimer(30);

//                 });
//             }
//             else
//             {
//                 //GameHandler.instance.interNetPopup.SetActive(true);
//                 if (!isAdmobInit)
//                 {
//                     AdmobInit();
//                 }
//                 else
//                 {
//                     LoadRewardedAd();
//                 }
//             }
//         }
//         private void RegisterEventHandlers(RewardedAd ad)
//         {
//             ad.OnAdPaid += (AdValue adValue) =>
//             {
//                 Debug.Log(String.Format("Rewarded ad paid {0} {1}.",
//                     adValue.Value,
//                     adValue.CurrencyCode));
//             };
//             ad.OnAdImpressionRecorded += () =>
//             {
//                 Debug.Log("Rewarded ad recorded an impression.");
//             };
//             ad.OnAdClicked += () =>
//             {
//                 Debug.Log("Rewarded ad was clicked.");
//             };
//             ad.OnAdFullScreenContentOpened += () =>
//             {
//                 Debug.Log("Rewarded ad full screen content opened.");
//             };
//             ad.OnAdFullScreenContentClosed += () =>
//             {
//                 Debug.Log("Rewarded ad full screen content closed.");
//                 isAppStateChangeFromInterstitialAd = true;
//                 isRewardLoad = false;
//             };
//             ad.OnAdFullScreenContentFailed += (AdError error) =>
//             {
//                 Debug.LogError("Rewarded ad failed to open full screen content " +
//                                "with error : " + error);
//             };



//         }

//         private void RegisterReloadHandler(RewardedAd ad)
//         {
//             ad.OnAdFullScreenContentClosed += () =>
//             {
//                 Debug.Log("Rewarded Ad full screen content closed.");

//                 LoadRewardedAd();
//             };
//             ad.OnAdFullScreenContentFailed += (AdError error) =>
//             {
//                 Debug.LogError("Rewarded ad failed to open full screen content " +
//                                "with error : " + error);

//                 LoadRewardedAd();
//             };
//         }
//         #endregion

//         public static bool isAppOpenAd;
//         #region APPOPEN 
//         public void LoadAppOpenAd()
//         {
//             if (appOpenAd != null)
//             {
//                 appOpenAd.Destroy();
//                 appOpenAd = null;
//             }

//             Debug.Log("Loading the app open ad.");

//             var adRequest = new AdRequest();

//             AppOpenAd.Load(appOpenID, adRequest,
//                 (AppOpenAd ad, LoadAdError error) =>
//                 {
//                     if (error != null || ad == null)
//                     {
//                         Debug.LogError("app open ad failed to load an ad " +
//                                        "with error : " + error);
//                         return;
//                     }

//                     isAppOpenAd = true;
//                     Debug.Log("App open ad loaded with response : "
//                               + ad.GetResponseInfo());
//                     _expireTime = DateTime.Now + TimeSpan.FromHours(4);
//                     appOpenAd = ad;
//                     RegisterEventHandlers(ad);
//                     RegisterReloadHandler(ad);
//                 });
//         }

//         public bool IsAdAvailable
//         {
//             get
//             {
//                 return appOpenAd != null
//                        && appOpenAd.CanShowAd()
//                        && DateTime.Now < _expireTime;
//             }
//         }


//         public void ShowAppOpenAd()
//         {
//             if (appOpenAd != null && appOpenAd.CanShowAd())
//             {
//                 Debug.Log("Showing app open ad.");
//                 appOpenAd.Show();
//             }
//             else
//             {
//                 Debug.LogError("App open ad is not ready yet.");
//             }
//         }

//         private void RegisterEventHandlers(AppOpenAd ad)
//         {
//             // Raised when the ad is estimated to have earned money.
//             ad.OnAdPaid += (AdValue adValue) =>
//             {
//                 Debug.Log(String.Format("App open ad paid {0} {1}.",
//                     adValue.Value,
//                     adValue.CurrencyCode));
//             };
//             // Raised when an impression is recorded for an ad.
//             ad.OnAdImpressionRecorded += () =>
//             {
//                 Debug.Log("App open ad recorded an impression.");
//             };
//             // Raised when a click is recorded for an ad.
//             ad.OnAdClicked += () =>
//             {
//                 Debug.Log("App open ad was clicked.");
//             };
//             // Raised when an ad opened full screen content.
//             ad.OnAdFullScreenContentOpened += () =>
//             {
//                 Debug.Log("App open ad full screen content opened.");
//             };
//             // Raised when the ad closed full screen content.
//             ad.OnAdFullScreenContentClosed += () =>
//             {
//                 isAppOpenAd = false;
//                 Debug.Log("App open ad full screen content closed.");
//             };
//             // Raised when the ad failed to open full screen content.
//             ad.OnAdFullScreenContentFailed += (AdError error) =>
//             {
//                 Debug.LogError("App open ad failed to open full screen content " +
//                                "with error : " + error);
//             };
//         }



//         private void OnApplicationPause(bool pause)
//         {
//             //  Debug.LogError(pause);
//             /* if (!pause)
//              {
//                  Debug.LogError(" APP OPEN ");

//                  Debug.Log("APP OPEN ");
//                  if (UnityEngine.SceneManagement.SceneManager.GetActiveScene().name != "Splash") ShowAppOpenAd();

//              }*/
//         }
//         private void OnAppStateChanged(AppState state)
//         {
//             // Debug.Log("App State changed to : " + state);

//             // if the app is Foregrounded and the ad is available, show it.
//             /*  if (state == AppState.Foreground)
//               {
//                   if (IsAdAvailable)
//                   {
//                       ShowAppOpenAd();
//                   }
//               }*/
//         }


//         /* public void ShowAppOpenAd()
//          {
//              if (isAppStateChangeFromInterstitialAd)
//              {
//                  isAppStateChangeFromInterstitialAd = false;
//                  return;
//              }
//              if (isAppOpenAd)
//              {
//                  Debug.Log("Showing app open ad.");
//                  appOpenAd.Show();
//                  GameManager.isOneTimeShow = true;
//              }
//              else
//              {
//                  Debug.LogError("App open ad is not ready yet.");
//              }
//          }*/

//         private void RegisterReloadHandler(AppOpenAd ad)
//         {

//             // Raised when the ad closed full screen content.
//             ad.OnAdFullScreenContentClosed += () =>
//             {
//                 Debug.Log("App open ad full screen content closed.");

//                 // Reload the ad so that we can show another as soon as possible.
//                 LoadAppOpenAd();
//             };
//             // Raised when the ad failed to open full screen content.
//             ad.OnAdFullScreenContentFailed += (AdError error) =>
//             {
//                 Debug.LogError("App open ad failed to open full screen content " +
//                                "with error : " + error);

//                 // Reload the ad so that we can show another as soon as possible.
//                 LoadAppOpenAd();
//             };
//         }
//         #endregion
//     }
// }
