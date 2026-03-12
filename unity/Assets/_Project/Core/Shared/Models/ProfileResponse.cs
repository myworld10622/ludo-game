using System;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class ProfileResponse : MonoBehaviour { }

[System.Serializable]
public class Notifications
{
    public string id;
    public string msg;
    public string image;
    public string url;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class ResponseDataNotification
{
    public string message;
    public Notifications[] notification;
    public int code;
}

[Serializable]
public class Ticket
{
    public string id;
    public string user_id;
    public string description;
    public string img;
    public string status;
    public string category;
    public string reply;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[Serializable]
public class TicketRootObject
{
    public string message;
    public List<Ticket> tickets;
    public int code;
}

[Serializable]
public class WelcomeBonus
{
    public string id;
    public string coin;
    public string game_played;
    public string added_date;
    public string updated_date;
}

[Serializable]
public class WelcomBonusRoot
{
    public string message;
    public string today_collected;
    public int collected_days;
    public List<WelcomeBonus> welcome_bonus;
    public int code;
}

[Serializable]
public class Statement
{
    public string id;
    public string user_id;
    public string source;
    public string source_id;
    public string amount;
    public string admin_commission;
    public string current_wallet;
    public string added_date;
    public string isDeleted;
}

[System.Serializable]
public class StatementOutputs
{
    public string message;
    public List<Statement> statement;
    public int code;
}

[System.Serializable]
public class StatementDetails
{
    public GameObject Prefab;
    public Transform Parent;
    public GameObject go;
    public List<GameObject> statement;
    public bool IsStatement;
}

[System.Serializable]
public class UpdateProfileOutputs
{
    public string message;
    public int code;
}

[System.Serializable]
public class Wallet
{
    public string message;
    public string wallet;

    public string winning_wallet;

    public string unutilized_wallet;

    public string bonus_wallet;

    public int code;

    // PlayerPrefs.SetString("winning", LogInOutput.user_data[0].winning_wallet);
    // PlayerPrefs.SetString("unutilized", LogInOutput.user_data[0].unutilized_wallet);
}

[Serializable]
public class notificationUserList
{
    public List<NotificationUser> List;
    public string message;
    public int code;
}

[Serializable]
public class NotificationUser
{
    public string id;
    public string name;
    public string gender;
    public string coin;
    public string avatar;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[Serializable]
public class BankOutputs
{
    public string message;
    public int code;
}

[System.Serializable]
public class messageprint
{
    public string message;
    public int code;
}

[System.Serializable]
public class OTP
{
    public string message;
    public string otp_id;
    public int code;
}

[System.Serializable]
public class Guest
{
    public string message;
    public string user_id;
    public string token;
    public int code;
}

[System.Serializable]
public class newLogInOutputs
{
    public string message;
    public List<UserDatum> user_data;
    public List<UserKyc> user_kyc;
    public List<UserBankDetail> user_bank_details;
    public List<string> avatar;
    public settings setting;
    public string notification_image;
    public List<AppBanner> app_banner;
    public int code;
}

[Serializable]
public class UserDatum
{
    public string id;
    public string name;
    public string user_type;
    public string bank_detail;
    public string adhar_card;
    public string upi;
    public string password;
    public string mobile;
    public string email;
    public string source;
    public string gender;
    public string profile_pic;
    public string referral_code;
    public string referred_by;
    public string wallet;
    public string unutilized_wallet;
    public string winning_wallet;
    public string bonus_wallet;
    public string spin_remaining;
    public string fcm;
    public string table_id;
    public string poker_table_id;
    public string head_tail_room_id;
    public string rummy_table_id;
    public string ander_bahar_room_id;
    public string dragon_tiger_room_id;
    public string jackpot_room_id;
    public string seven_up_room_id;
    public string rummy_pool_table_id;
    public string rummy_deal_table_id;
    public string color_prediction_room_id;
    public string color_prediction_1_min_room_id;
    public string color_prediction_3_min_room_id;
    public string car_roulette_room_id;
    public string animal_roulette_room_id;
    public string ludo_table_id;
    public string red_black_id;
    public string baccarat_id;
    public string jhandi_munda_id;
    public string roulette_id;
    public string rummy_tournament_table_id;
    public string target_room_id;
    public string ander_bahar_plus_room_id;
    public string golden_wheel_room_id;
    public string golden_wheel_star;
    public string game_played;
    public string token;
    public string status;
    public string premium;
    public string app_version;
    public string user_category_id;
    public string unique_token;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string user_category;
}

[System.Serializable]
public class UserBankDetails
{
    public string id;
    public string user_id;
    public string bank_name;
    public string ifsc_code;
    public string acc_holder_name;
    public string acc_no;
    public string passbook_img;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[Serializable]
public class UserKyc
{
    public string id;
    public string user_id;
    public string pan_no;
    public string pan_img;
    public string aadhar_no;
    public string aadhar_img;
    public string status;
    public string reason;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[Serializable]
public class UserBankDetail
{
    public string id;
    public string user_id;
    public string bank_name;
    public string ifsc_code;
    public string acc_holder_name;
    public string acc_no;
    public string passbook_img;
    public string crypto_address;
    public string crypto_wallet_type;
    public string crypto_qr;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class settings
{
    public string min_redeem;
    public string referral_amount;
    public string contact_us;
    public string terms;
    public string privacy_policy;
    public string help_support;
    public string app_version;
    public string share_text;
    public string dollar;
    public string referral_link;
    public string referral_id;
}

[System.Serializable]
public class newSignUpOutputs
{
    public string message;
    public string user_id;
    public string token;
    public int code;
}

[System.Serializable]
public class Setting
{
    public string id;
    public string first_name;
    public string last_name;
    public string email_id;
    public string mobile;
    public string role;
    public string status;
    public string password;
    public string sw_password;
    public string min_redeem;
    public string referral_amount;
    public string game_referral_percent;
    public string referral_id;
    public string referral_link;
    public string level_1;
    public string level_2;
    public string level_3;
    public string level_4;
    public string level_5;
    public string contact_us;
    public string about_us;
    public string refund_policy;
    public string terms;
    public string privacy_policy;
    public string help_support;
    public string admin_coin;
    public string jackpot_coin;
    public string jackpot_status;
    public string default_otp;
    public string game_for_private;
    public string app_version;
    public string app_url;
    public string logo;
    public string server_key;
    public string sms_url;
    public string joining_amount;
    public string admin_commission;
    public string whats_no;
    public string bonus;
    public string bonus_amount;
    public string payment_gateway;
    public string neokred_client_secret;
    public string neokred_project_id;
    public string upi_id;
    public string symbol;
    public string upi_merchant_id;
    public string upi_secret_key;
    public string razor_api_key;
    public string razor_secret_key;
    public string cashfree_client_id;
    public string cashfree_client_secret;
    public string cashfree_stage;
    public string paytm_mercent_id;
    public string paytm_mercent_key;
    public string payumoney_key;
    public string payumoney_salt;
    public string share_text;
    public string bank_detail_field;
    public string adhar_card_field;
    public string upi_field;
    public string ban_states;
    public string app_message;
    public string robot_teenpatti;
    public string robot_rummy;
    public string extra_spinner;
    public string ander_bahar_random;
    public string dragon_tiger_random;
    public string jackpot_random;
    public string up_down_random;
    public string car_roulette_random;
    public string animal_roulette_random;
    public string color_prediction_random;
    public string color_prediction_1_min_random;
    public string color_prediction_3_min_random;
    public string head_tail_random;
    public string red_black_random;
    public string bacarate_random;
    public string jhandi_munda_random;
    public string roulette_random;
    public string ander_bahar_plus_random;
    public string target_random;
    public string golden_wheel_random;
    public string created_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class UserSettingOutPuts
{
    public string message;
    public string notification_image;
    public List<AppBanner> app_banner;
    public List<string> avatar;
    public Setting setting;
    public int code;
}

[System.Serializable]
public class AppBanner
{
    public string id;
    public string banner;
    public string createdDate;
    public string updatedDate;
    public string isDeleted;
}

#region ProfileData
[System.Serializable]
public class newLogInDetails
{
    public TMP_InputField PasswordInputfield;
    public TMP_InputField MobileInputfield;
    public GameObject LogInPnl;

    public void Clear()
    {
        PasswordInputfield.text = string.Empty;
        MobileInputfield.text = string.Empty;
    }
}

[System.Serializable]
public class newSignUpDetails
{
    public TMP_InputField PasswordInputfield;
    public TMP_InputField MobileInputfield;
    public TMP_InputField NameInputfield;
    public TMP_InputField ReferralCodeInputfield;
    public Toggle _Toggle;
    public GameObject OtpPanel;

    public GameObject SignUpPnl;

    public void Clear()
    {
        PasswordInputfield.text = string.Empty;
        MobileInputfield.text = string.Empty;
        NameInputfield.text = string.Empty;
        if (!ReferralCodeInputfield.text.Contains("777-"))
        {
            ReferralCodeInputfield.text = string.Empty;
        }
    }
}

#endregion

#region Game Settings

[System.Serializable]
public class GameSetting
{
    public string id;
    public string teen_patti;
    public string dragon_tiger;
    public string andar_bahar;
    public string point_rummy;
    public string private_rummy;
    public string pool_rummy;
    public string deal_rummy;
    public string private_table;
    public string custom_boot;
    public string seven_up_down;
    public string car_roulette;
    public string jackpot_teen_patti;
    public string animal_roulette;
    public string color_prediction;
    public string color_prediction_vertical;
    public string poker;
    public string head_tails;
    public string red_vs_black;
    public string ludo_local;
    public string ludo_online;
    public string ludo_computer;
    public string bacarate;
    public string jhandi_munda;
    public string roulette;
    public string tournament_rummy;
    public string Aviator;
    public string aviator_vertical;
    public string Lottery;
    public string maintainance;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string aviator_ui_type;
    public string color_prediction_ui_type;
}

[System.Serializable]
public class GameRootObject
{
    public string message;
    public GameSetting game_setting;
    public int code;
}

[Serializable]
public class playbuttongames
{
    public string scene;
    public string name;
    public string backendname;
}

#endregion
