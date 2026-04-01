using System.Collections;
using System.Collections.Generic;
using Best.HTTP;
using Best.HTTP.Request.Upload.Forms;
using UnityEngine;
using UnityEngine.Networking;
using static System.Net.WebRequestMethods;

public class Configuration : MonoBehaviour
{

    // public const string BaseUrl = "http://localhost:8000/";
    // public const string Website = "http://localhost:8000/";
    // public const string BaseSocketUrl = "http://localhost:3002";
    public const string BaseUrl = "https://ludo.betzono.tech/";
    public const string Website = "https://ludo.betzono.tech/";
    public const string BaseSocketUrl = "https://socket.betzono.tech/";
    public const string LudoTournamentListUrl = BaseUrl + "api/v1/tournaments";
    public const string LudoTournamentInfoUrl = BaseUrl + "api/v1/tournaments/";

    public const string APIUrl = BaseUrl + "api/";
    public const string UserUrl = BaseUrl + "api/User/";
    public const string LudoV2QueueJoinUrl = BaseUrl + "api/v1/ludo/queue/join";
    public const string LudoV2RoomUrl = BaseUrl + "api/v1/ludo/rooms/";
    public const string TournamentListUrl = BaseUrl + "api/v1/tournaments";
    public const string TournamentClaimRoomUrl = BaseUrl + "api/v1/tournaments/";
    public const string LudoV2SocketNamespace = "/ludo_v2";

    //A1P
    public const string paymentgateway = BaseUrl + "api/plan/Place_Order_blinkpe";

    //Sky Rummy
    public const string SkyRummyPaymentGateway = BaseUrl + "api/plan/Place_Order_upi";

    // UPI gateway
    public const string UpiGateway = BaseUrl + "api/Plan/Place_Order_Upi_Gateway";

    //Rummy King
    public const string RummyKingpaymentgateway = BaseUrl + "api/plan/Place_Order_upi";
    public const string PlanChips = BaseUrl + "api/plan";
    public const string CheckStatus = BaseUrl + "api/plan/check_status";

    #region Game Status URL
    public const string GameRummyStatus = BaseUrl + "api/rummy/status";
    public const string GameDealRummyStatus = BaseUrl + "api/RummyDeal/status";
    public const string GameTournamentRummyStatus = BaseSocketUrl + "/api/rummy-tournament/status";
    public const string GameTournamentPrizes = BaseSocketUrl + "/api/rummy-tournament/winners/";
    public const string GamePoolRummyStatus = BaseUrl + "api/RummyPool/status";
    public const string GameTeenPattiStatus = BaseUrl + "api/game/status";
    public const string teenpattijointable = Url + "game/join_table";
    #endregion

    #region gettable
    public const string GetTableAPIURL = BaseUrl + "api/callback/jungle_rummy_card";
    #endregion

    public const string LevelWiseRefer = BaseUrl + "api/User/reffer_level";

    public const string RedeemHistory = BaseUrl + "api/Redeem/WithDrawal_log";
    public const string PurchaseHistory = BaseUrl + "api/User/purchase_history";

    public const string GameNotificcations = BaseUrl + "api/User/randomBoatUsers";

    public const string DepositBonusHistory = BaseUrl + "api/User/getDepositeBonus";
    public const string BetCommission = BaseUrl + "api/User/bet_commission_log";

    public const string RebateHistory = BaseUrl + "api/User/rebateHistory";

    public const string GameLogo = BaseUrl + "api/plan/Place_Order_upi";

    public const string Url = BaseUrl + "api/";
    public const string rouletteresult = BaseUrl + "/api/roulette/get_result";
    public const string RouletteCancelBet = BaseUrl + "/api/Roulette/cancel_bet";
    public const string RoulettePlacePreviousBet = BaseUrl + "/api/Roulette/repeat_bet";
    public const string SevenUpDownResult = BaseUrl + "/api/SevenUp/get_result";
    public const string BaccaratResult = BaseUrl + "/api/baccarat/get_result";
    public const string ColorPredictionResult = BaseSocketUrl + "/api/ColorPrediction/get_result";
    public const string AnimalRouletteResult = BaseUrl + "/api/AnimalRoulette/get_result";

    public const string JackpotTeenPatiResult = BaseUrl + "/api/jackpot/get_result";
    public const string VCPResult = BaseSocketUrl + "/api/ColorPrediction/get_result";
    public const string VCP1MinResult = BaseSocketUrl + "api/ColorPrediction1Min/get_result";
    public const string VCP3MinResult = BaseSocketUrl + "/api/ColorPrediction3Min/get_result";
    public const string VCP5MinResult = BaseSocketUrl + "/api/ColorPrediction5Min/get_result";

    #region AddCash
    public const string addcash = Url + "Plan/addcash";
    public const string purchasehistory = Url + "User/purchase_history";
    public const string addcashgetQR = Url + "Plan/get_qr";
    public const string USDT_Get_QR = Url + "Plan/get_usdt_qr";

    #endregion
    public const string salarystatement = Url + "User/salary_bonus_log";

    public const string Signup = "User/register";
    public const string LogIn = "User/login";
    public const string profile = "User/profile";
    public const string wallet = "User/wallet";
    public const string AviatorHistory = Url + "/User/aviatorHistory";

    //public const string AviatorHistory = Url + "/User/aviatorGameHistoryByLimit";
    //public const string AviatorHistory = BaseSocketUrl + "/User/aviatorGameHistoryByLimit";
    public const string AviatorAddBet = BaseSocketUrl + "/api/aviator/place_bet";
    public const string AviatorCancelBet = BaseSocketUrl + "/api/aviator/cancelBet";
    public const string AviatorRedeem = BaseSocketUrl + "/api/aviator/redeem";
    public const string Forgot = "User/forgot_password";
    public const string guest_register = "user/guest_register";
    public const string UpdatePassword = "User/update_password";
    public const string Plan = "Plan";
    public const string Welcomebonus = "User/welcome_bonus";

    public const string Redeem_Withdraw = "Redeem/Withdraw";
    public const string Redeem_Withdraw_custom = "Redeem/Withdraw_custom";
    public const string Redeem_list = "Redeem/list";
    public const string Redeem = "Redeem/list";
    public const string RedeemWithDrawallog = "Redeem/WithDrawal_log";

    public const string Checkaadhar = "user/check_adhar";
    public const string Withdraw = "User/withdrawal_log";

    public const string Wining_History = "User/winning_history";
    public const string Update_bank_details = "user/update_bank_details";
    public const string Update_kyc = "user/update_kyc";
    public const string Change_password = "user/change_password";
    public const string Collect_welcome_bonus = "User/collect_welcome_bonus";

    public const string wallet_history_dragon = "User/wallet_history_dragon";
    public const string wallet_history_roulette = "User/wallet_history_roulette";
    public const string Update_profile = "user/update_profile";

    public const string gameonoff = "user/game_on_off";

    //User/collect_welcome_bonus
    // public const string winning_history = "User/winning_history";
    // public const string Welcomebonus = "User/welcome_bonus";
    // public const string teenpatti_gamelog_history = "User/teenpatti_gamelog_history";

    public const string Wallethistory = "user/wallet_history_all";
    public const string GameStatement = "User/getStatement";
    public const string Usersetting = "user/setting";
    public const string Usersendotp = "User/send_otp";

    // Roulette
    //public const string RoulettePlaceBet = "Roulette/place_bet";
    public const string RoulettePlaceBetmultiple = "Roulette/place_bet_multiple";
    public const string Roulettedouble_bet = "Roulette/double_bet";
    public const string RouletteRepeat = "roulette/repeat_bet";
    public const string RouletteCancel_bet = "roulette/cancel_bet";
    public const string RoulettePlaceBet = BaseUrl + "/api/Roulette/place_bet";

    //Andar Bahar
    public const string AnderBaharPlaceBet = "AnderBahar/place_bet";

    // seven up Down
    public const string SevenUpDownBet = "SevenUp/place_bet";

    //baccarat
    public const string BaccaratBet = "baccarat/place_bet";

    public const string Begins = "Begins";
    public const string Stops = "Stops";
    public const string waitingforthenextround = "waiting for the next round ";
    public const string term = "Term and Conditions";
    public const string PrivacyPolicy = "Privacy Policy";

    //public const string GetActiveGame = "AnderBahar/get_active_game";


    public const string getCurrencyAvailable = "Plan/getCurrencyAvailable";
    public const string Place_Order_NowPayment = "Plan/Place_Order_NowPayment";
    public const string Callback_verify_NowPayment = "Callback/verify_NowPayment";

    public const string ProfileImage = BaseUrl + "data/post/";
    public const string BannerImage = BaseUrl + "uploads/banner/";
    public const string NotificationBannerImage = BaseUrl + "uploads/images/";
    public const string UserProfile = "User/profile";
    public const string DataRedeem = BaseUrl + "data/Redeem/";

    public const string TokenLoginHeader =
        "c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909";

    public const string RummySocket = BaseUrl + "rummy";
    public const string RummyStatus = Url + "status";
    public const string RummyGettablemaster = Url + "rummy/get_table_master";

    public const string RummyPoolGettablemaster = Url + "RummyPool/get_table_master";

    public const string RummyDealGettablemaster = Url + "RummyDeal/get_table_master";

    public const string TeenpattiSocket = BaseUrl + "teenpatti";
    public const string TeenpattiStatus = Url + "status";
    public const string TeenPattiGettablemaster = Url + "game/get_table_master";

    public const string AviatorGameHistory = Url + "User/aviator_GameHistory";
    public const string AviatorMyHistory = Url + "User/aviator_myHistory";

    public const string CarRoulletGameHistory = Url + "User/wallet_history_car_roulette";
    public const string AnimalRoulletGameHistory = Url + "User/wallet_history_animal_roulette";
    public const string JackPotGameHistory = Url + "User/wallet_history_jackpot";
    public const string RedAndBlackGameHistory = Url + "User/wallet_history_red_black";
    public const string BaccaratGameHistory = Url + "User/wallet_history_baccarat";
    public const string Rummy_poolGameHistory = Url + "User/wallet_history_rummy_pool";
    public const string Rummy_dealGameHistory = Url + "User/wallet_history_rummy_deal";
    public const string Rummy_PointGameHistory = Url + "User/rummy_gamelog_history";
    public const string JhandiMundaGameHistory = Url + "User/jhandiMunda_gamelog_history";
    public const string PockerGameHistory = Url + "User/poker_gamelog_history";

    public const string poker_get_table_master = Url + "poker/get_table_master";
    public const string Poker_Status = Url + "poker/status";

    public const string CryptoPayment = Url + "Plan/Place_Order_NowPayment";

    public const int ROYAL_FLUSH = 1;
    public const int STRAIGHT_FLUSH = 2;
    public const int FOUR_OF_A_KIND = 3;
    public const int FULL_HOUSE = 4;
    public const int FLUSH = 5;
    public const int STRAIGHT = 6;
    public const int THREE_OF_KIND = 7;
    public const int TWO_PAIR = 8;
    public const int PAIR = 9;
    public const int HIGH_CARD = 10;
    public const int INVALID = 0;

    public const int SET = 1;
    public const int PURE_SEQUENCE = 2;
    public const int SEQUENCE = 3;
    public const int COLOR = 4;
    public const int TPPAIR = 5;
    public const int TPHIGH_CARD = 6;

    //jackpotteenppatti
    public const int jktset = 6;
    public const int jktpureeq = 5;
    public const int jktseq = 4;
    public const int jktcolor = 3;
    public const int jktpair = 2;
    public const int jkthigh = 1;

    public const string Jackpot_TeenPatti_PutBet = BaseUrl + "/api/jackpot/place_bet";

    //colorprediction
    public const int green = 10;
    public const int violet = 11;
    public const int red = 12;
    public const int small = 15;
    public const int big = 16;

    public const string JColor_Prediction_PutBet = BaseSocketUrl + "/api/ColorPrediction/place_bet";

    //JhundiMunda
    public const string JhundiMunda_Prediction_PutBet = BaseUrl + "/api/JhandiMunda/place_bet";

    //Baccarat
    public const int PLAYER_VALUE = 0;
    public const int BANKER_VALUE = 1;
    public const int TIE_VALUE = 2;
    public const int PLAYER_PAIR_VALUE = 3;
    public const int BANKER_PAIR_VALUE = 4;

    public const string Bacarrat_PutBet = BaseUrl + "/api/Baccarat/place_bet";

    //Color_Prediction Verticle

    public const string Color_Prediction_1Min_PutBet =
        BaseSocketUrl + "/api/ColorPrediction1Min/place_bet";
    public const string Color_Prediction_3Min_PutBet =
        BaseSocketUrl + "/api/ColorPrediction3Min/place_bet";
    public const string Color_Prediction_5Min_PutBet =
        BaseSocketUrl + "/api/ColorPrediction5Min/place_bet";
    public const string Color_Prediction_PutBet = BaseSocketUrl + "/api/ColorPrediction/place_bet";

    public const string AnimalRoulletePutBet = BaseUrl + "/api/AnimalRoulette/place_bet";

    public const string Color_Prediction_1Min_myHistory = Url + "ColorPrediction1Min/myHistory";
    public const string Color_Prediction_3Min_myHistory = Url + "ColorPrediction3Min/myHistory";
    public const string Color_Prediction_myHistory = Url + "ColorPrediction/myHistory";

    public const string Color_Prediction_1Min_GameHistory = Url + "ColorPrediction1Min/GameHistory";
    public const string Color_Prediction_3Min_GameHistory = Url + "ColorPrediction3Min/GameHistory";
    public const string Color_Prediction_5Min_GameHistory = Url + "ColorPrediction5Min/GameHistory";
    public const string Color_Prediction_5Min_myHistory = Url + "ColorPrediction5Min/myHistory";
    public const string Color_Prediction_GameHistory = Url + "ColorPrediction/GameHistory";

    public const string TeenPattiHistory = BaseUrl + "api/User/teenpatti_gamelog_history";

    public const string sevenupdownHistory = Url + "User/wallet_history_seven_up";
    public const string AndarBaharHistory = BaseUrl + "api/User/wallet_history";
    public const string AndarBaharPlusHistory =
        BaseUrl + "api/User/wallet_history_andar_bahar_plus";

    public const string HeadAndTileGameHistory = Url + "User/wallet_history_head_tail";
    public const string DragonTigerHistory = Url + "User/wallet_history_dragon";
    public const string RouletteGameHistory = Url + "User/wallet_history_roulette";

    public const string Color_PredicationGameHistory = Url + "User/wallet_history_color_prediction"; // use for both games
    public const string Color_PredicationGameHistoryfor1min =
        Url + "User/wallet_history_color_prediction_1_min"; // use for both games
    public const string Color_PredicationGameHistoryfor3min =
        Url + "User/wallet_history_color_prediction_3_min"; // use for both games
    public const string Color_PredicationGameHistoryfor5min =
        Url + "User/wallet_history_color_prediction_5_min"; // use for both games

    //Ludo

    public const string LudoGettablemaster = Url + "ludo/get_table_master";
    public const string GameLudoStatus = BaseUrl + "api/ludo/status";

    //Rummy Tournament

    public const string RummyTournamentList = BaseSocketUrl + "/api/rummy-tournament";
    public const string RummyTournamentParticipate =
        BaseSocketUrl + "/api/rummy-tournament/participate";
    public const string RummyTournamentInfo = BaseSocketUrl + "/api/rummy-tournament/info";
    public const string RummyTournamenetSocket =
        BaseSocketUrl + "/api/rummy_tournament/start_time_in_second";

    //generate ticket
    public const string createticket = BaseUrl + "api/User/createTicket";
    public const string getticket = BaseUrl + "api/User/getTickets";

    public const string Get_Notification = Url + "User/getNotifications";

    //Game name Registration
    //Kuber fantasy
    //public string app = "kuberfantasy";

    //Royal Ticasa
    public const string app = "royalticasa";

    #region Privacy and policy URLs

    public const string PrivacyAndpolicy = BaseUrl + "privacy-policy";
    public const string TermsAndCondititon = BaseUrl + "terms-conditions";
    public const string ContactUs = BaseUrl + "contact-us";
    public const string DeleteAccount = BaseUrl + "delete-account";
    public const string VCPmindetails = Url + "ColorPrediction/get_bet_details";
    public const string VCP1mindetails = Url + "ColorPrediction1Min/get_bet_details";
    public const string VCP3mindetails = Url + "ColorPrediction3Min/get_bet_details";
    public const string VCP5mindetails = Url + "ColorPrediction5Min/get_bet_details";

    public const string opponentdetails = "Ludo/getOpponentProfile";
    public const string create_Table = "Ludo/get_private_table_ludo_bachpan";
    public const string join_Table = "Ludo/join_table_with_code_bachpan";
    public const string Leave_Table = "Ludo/leave_table_bachpan";
    public const string Start_Game = "Ludo/start_game_bachpan";
    public const string Statement = "Ludo/Statement";
    public const string Win_Game = "Ludo/make_winner_bachpan";
    public const string Get_Table_Master = "Ludo/get_table_master_bachpan";
    public const string get_table_ludo_bachpan = "Ludo/get_table_ludo_bachpan";

    #region rani bet agent add chips

    public const string AgentChat = BaseUrl + "api/user/agent_chats";

    public const string getAgentList = BaseUrl + "api/User/getAgentList";
    public const string getAgentConversionID = BaseUrl + "api/User/generateConversationId";
    public const string sendMessage = BaseUrl + "api/User/startConversation";
    public const string puchaseFromAgent = BaseUrl + "api/Plan/puchaseFromAgent";
    public const string withdrawFromAgent = BaseUrl + "api/Redeem/withdrawRequestForAgent";

    #endregion


    public static string GetId()
    {
        return PlayerPrefs.GetString("id");
    }

    public static string GetToken()
    {
        return PlayerPrefs.GetString("token");
    }

    public static string GetProfilePic()
    {
        return PlayerPrefs.GetString("profile_pic");
    }

    public static string GetAdharPic()
    {
        return PlayerPrefs.GetString("adhar_pic");
    }

    public static string GetWithdraw()
    {
        return PlayerPrefs.GetString("withdraw");
    }

    public static string GetMobile()
    {
        return PlayerPrefs.GetString("mobile");
    }

    public static string GetAddCash()
    {
        return PlayerPrefs.GetString("addcash");
    }

    public static string GetPanPic()
    {
        return PlayerPrefs.GetString("pan_pic");
    }

    public static string GetPassbookPic()
    {
        return PlayerPrefs.GetString("passbool_pic");
    }

    public static string GetWallet()
    {
        return PlayerPrefs.GetString("wallet");
    }

    public static string GetName()
    {
        return PlayerPrefs.GetString("name");
    }

    public static string GetSound()
    {
        return PlayerPrefs.GetString("sound");
    }

    public static string GetMusic()
    {
        return PlayerPrefs.GetString("music");
    }

    public static string GetTournament_ID()
    {
        return PlayerPrefs.GetString("m_id");
    }

    public static string GetRoomCode()
    {
        return PlayerPrefs.GetString("room_code");
    }

    public static string GetTableID()
    {
        return PlayerPrefs.GetString("table_id");
    }

    public static string getcreate()
    {
        return PlayerPrefs.GetString("create");
    }

    public static string getjoin()
    {
        return PlayerPrefs.GetString("join");
    }

    public static string getmobile()
    {
        return PlayerPrefs.GetString("mobile");
    }

    public static string getsevenupdownid()
    {
        return PlayerPrefs.GetString("sevenupdownid");
    }

    public static string getabid()
    {
        return PlayerPrefs.GetString("abid");
    }

    public static string getbacid()
    {
        return PlayerPrefs.GetString("bacid");
    }

    public static string gethntid()
    {
        return PlayerPrefs.GetString("htid");
    }

    public static string getrbid()
    {
        return PlayerPrefs.GetString("rbid");
    }

    public static string getjptid()
    {
        return PlayerPrefs.GetString("jptid");
    }

    public static string getcpid()
    {
        return PlayerPrefs.GetString("cpid");
    }

    public static string getjmid()
    {
        return PlayerPrefs.GetString("jmid");
    }

    public static string getFCMToken()
    {
        return PlayerPrefs.GetString("FCMToken");
    }

    public static string getbankfilledornot()
    {
        return PlayerPrefs.GetString("getbankfilledornot");
    }

    public static string getcryptofilledornot()
    {
        return PlayerPrefs.GetString("getcryptofilledornot");
    }

    public static string getemail()
    {
        return PlayerPrefs.GetString("email");
    }

    public static string getdollar()
    {
        return PlayerPrefs.GetString("getdollar");
    }

    public static string GetBonus()
    {
        return PlayerPrefs.GetString("bonus");
    }

    public static string GetWinning()
    {
        return PlayerPrefs.GetString("winning");
    }

    public static string GetUnutilized()
    {
        return PlayerPrefs.GetString("unutilized");
    }

    public static string Getpointplayer()
    {
        return PlayerPrefs.GetString("Getpointplayer");
    }

    public static string Getpointboot()
    {
        return PlayerPrefs.GetString("Getpointboot");
    }

    public static string Getpoolplayer()
    {
        return PlayerPrefs.GetString("Getpoolplayer");
    }

    public static string Getpoolboot()
    {
        return PlayerPrefs.GetString("Getpoolboot");
    }

    public static string Getdealplayer()
    {
        return PlayerPrefs.GetString("Getdealplayer");
    }

    public static string Getdealid()
    {
        return PlayerPrefs.GetString("Getdealid");
    }

    public static string Getdealboot()
    {
        return PlayerPrefs.GetString("Getdealboot");
    }

    public static string Getpooltableid()
    {
        return PlayerPrefs.GetString("Getpooltableid");
    }

    public static string Gettpboot()
    {
        return PlayerPrefs.GetString("Gettpboot");
    }

    public static bool showbanner = true;
    public const bool LudoV2EnabledByDefault = true;

    public static bool IsLudoV2Enabled()
    {
        return PlayerPrefs.GetInt("ludo_v2_enabled", LudoV2EnabledByDefault ? 1 : 0) == 1;
    }

    public static string GetLudoTournamentClaimRoomUrl(string tournamentUuid)
    {
        return BaseUrl + "api/v1/tournaments/" + tournamentUuid + "/claim-room";
    }

    public static void SetLudoV2Enabled(bool enabled)
    {
        PlayerPrefs.SetInt("ludo_v2_enabled", enabled ? 1 : 0);
        PlayerPrefs.Save();
    }

    #endregion

    public static async void GetProfileWallet()
    {
        string url = Url + wallet;
        var formData = new Dictionary<string, string>
        {
            { "user_id", GetId() },
            { "token", GetToken() },
        };
        Wallet myResponse = await APIManager.Instance.Post<Wallet>(url, formData);
        if (myResponse.code == 200)
        {
            PlayerPrefs.SetString("wallet", myResponse.wallet);
            PlayerPrefs.Save();
        }
        else
        {
            Debug.Log("Error_new:" + myResponse.message);
        }
        Debug.Log("RES+Message" + myResponse.message);
        Debug.Log("RES+Code" + myResponse.code);
    }

    // #region getwallet

    // public static IEnumerator GetProfileWallet()
    // {
    //     string url = Url + wallet;
    //     Debug.Log("RES_Check + API-Call + wallet " + url);
    //     WWWForm form = new WWWForm();
    //     form.AddField("user_id", GetId()); // before Configuration.GetId()
    //     form.AddField("token", GetToken()); // before Configuration.GetToken()
    //     UnityWebRequest www = UnityWebRequest.Post(url, form);
    //     www.SetRequestHeader("Token", TokenLoginHeader);
    //     yield return www.SendWebRequest();
    //     if (www.result == UnityWebRequest.Result.Success)
    //     {
    //         var responseText = www.downloadHandler.text;
    //         Debug.Log("Res_Value + GetWallet: " + responseText);
    //         Wallet wallet = new Wallet();
    //         wallet = JsonUtility.FromJson<Wallet>(responseText);
    //         if (wallet.code == 200)
    //         {
    //             PlayerPrefs.SetString("wallet", wallet.wallet);
    //             PlayerPrefs.Save();
    //         }
    //         else
    //         {
    //             Debug.Log("errornew" + www.error);
    //             Debug.Log("error" + www.error);
    //         }
    //     }
    //     else
    //     {
    //         Debug.Log("RES_Check + www response + " + www.result);
    //     }
    //     yield return null;
    // }

    // #endregion
}
