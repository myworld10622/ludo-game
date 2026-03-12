using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class AviatorResponse : MonoBehaviour { }

public class AviatorCancelBet
{
    public string message;
    public AviatorRedeemData data;
    public int code;
}

[System.Serializable]
public class AviatorRedeemUserData
{
    public int code;
    public AviatorRedeemData data;
    public float user_winning_amt;
    public float admin_winning_amt;
    public string message;
}

[System.Serializable]
public class AviatorRedeemData
{
    public int id;
    public float wallet;
    public int user_type;
    public string name;
    public string bank_detail;
    public string adhar_card;
    public string upi;
    public string mobile;
    public string password;
    public string email;
    public int source;
    public string gender;
    public string profile_pic;
    public string referral_code;
    public int referred_by;
    public float unutilized_wallet;
    public float winning_wallet;
    public float bonus_wallet;
    public int spin_remaining;
    public string fcm;
    public string socket_id;
    public int table_id;
    public int poker_table_id;
    public int head_tail_room_id;
    public int rummy_table_id;
    //public int rummy_cacheta_table_id;
    public int? rummy_cacheta_table_id;
    public int ander_bahar_room_id;
    public int dragon_tiger_room_id;
    public int jackpot_room_id;
    public int seven_up_room_id;
    public int rummy_pool_table_id;
    public int rummy_deal_table_id;
    public int color_prediction_room_id;
    public int color_prediction_1_min_room_id;
    public int color_prediction_3_min_room_id;
    public int car_roulette_room_id;
    public int animal_roulette_room_id;
    public int ludo_table_id;
    public int red_black_id;
    public int baccarat_id;
    public int jhandi_munda_id;
    public int roulette_id;
    public int rummy_tournament_table_id;
    public int target_room_id;
    public int ander_bahar_plus_room_id;
    public int golden_wheel_room_id;
    public int game_played;
    public string token;
    public int status;
    public int premium;
    public string app_version;
    public int user_category_id;
    public string unique_token;
    public int isDeleted;
    public int aviator_room_id;
    public string added_date;
    public string updated_date;
}

[System.Serializable]
public class AviatorGameData
{
    public float time;
    public float game_id;
    public float sec;
}

[System.Serializable]
public class BetResponse
{
    public ResultData result;
    public int code;
    public string message;
}

[System.Serializable]
public class BetMessage
{
    public int code;
    public string message;
}

[System.Serializable]
public class ResultData
{
    public int id;
    public string dragon_tiger_id;
    public string user_id;
    public int bet;
    public int amount;
    public int winning_amount;
    public int user_amount;
    public int comission_amount;
    public string added_date;
}

[System.Serializable]
public class AviatorBlastGameData
{
    public string id;
    public string dragon_tiger_id;
    public string aviator_id;
    public string user_id;
    public string bet;
    public string bet_status;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string minus_unutilized_wallet;
    public string minus_winning_wallet;
    public string minus_bonus_wallet;
    public string added_date;
    public string status;
    public string blast_time;
    public string name;
}

[System.Serializable]
public class AviatorBlastRoot
{
    public List<AviatorBlastGameData> game_data;
    public int code;
}
