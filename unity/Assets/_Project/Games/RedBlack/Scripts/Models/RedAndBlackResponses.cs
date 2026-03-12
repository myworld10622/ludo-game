using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class RedAndBlackResponses : MonoBehaviour { }

[System.Serializable]
public class RBBotUser
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

[System.Serializable]
public class RBGameData
{
    public string id;
    public string room_id;
    public string winning;
    public string winning_rule;
    public string status;
    public string added_date;
    public int time_remaining;
    public string end_datetime;
    public string updated_date;
}

[System.Serializable]
public class RBGameCard
{
    public string id;
    public string red_black_id;
    public string card;
    public string added_date;
}

[System.Serializable]
public class RBOnlineUser
{
    public string id;
    public string name;
    public string user_type;
    public string bank_detail;
    public string adhar_card;
    public string upi;
    // Add other properties as needed
}

[System.Serializable]
public class RBLastWinning
{
    public string id;
    public string room_id;
    public string main_card;
    public string winning;
    public string winning_rule;
    public string status;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string total_amount;
    public string admin_profit;
    public string end_datetime;
    public string random;
    public string added_date;
    public string updated_date;
}

[System.Serializable]
public class RBRootObject
{
    public List<RBBotUser> bot_user;
    public string message;
    public List<RBGameData> game_data;
    public List<RBGameCard> game_cards;
    public string online;
    public List<RBOnlineUser> online_users;
    public int red_amount;
    public int black_amount;
    public int pair_amount;
    public int color_amount;
    public int sequence_amount;
    public int pure_sequence_amount;
    public int set_amount;
    public List<string> last_bet;
    public List<RBLastWinning> last_winning;
    public int code;
}

[System.Serializable]
public class RedAndBlackBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}
