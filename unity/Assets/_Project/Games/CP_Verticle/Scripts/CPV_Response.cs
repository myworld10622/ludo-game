using System;
using System.Collections.Generic;
using UnityEngine;

public class CPV_Response : MonoBehaviour { }

[Serializable]
public class CPVBotUser
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
public class CPVGameData
{
    public string id;
    public string room_id;
    public string winning;
    public string status;
    public string added_date;
    public int time_remaining;
    public string end_datetime;
    public string updated_date;
}

[Serializable]
public class CPVGameCard
{
    public string id;
    public string color_prediction_id;
    public string card;
    public string added_date;
}

[Serializable]
public class CPVLastWinning
{
    public string id;
    public string room_id;
    public string card1;
    public string card2;
    public string card3;
    public string winning;
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

[Serializable]
public class CPVJSONResponse
{
    public List<CPVBotUser> bot_user;
    public string message;
    public List<CPVGameData> game_data;
    public List<CPVGameCard> game_cards;
    public string online;
    public List<object> online_users; // Change the type if you know the structure
    public List<object> last_bet; // Change the type if you know the structure
    public int my_bet_0;
    public int my_bet_1;
    public int my_bet_2;
    public int my_bet_3;
    public int my_bet_4;
    public int my_bet_5;
    public int my_bet_6;
    public int my_bet_7;
    public int my_bet_8;
    public int my_bet_9;
    public int my_bet_10;
    public int my_bet_11;
    public int my_bet_12;
    public int my_bet_big;
    public int my_bet_small;
    public List<CPVLastWinning> last_winning;
    public int code;
}

[Serializable]
public class CPhistoryGameData
{
    public string id;
    public string color_prediction_id;
    public string user_id;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
    public string status;
}

[Serializable]
public class CPGameHistoryWinningEntry
{
    public string id;
    public string room_id;
    public string card1;
    public string card2;
    public string card3;
    public string winning;
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

[Serializable]
public class CPGameHistoryWinningData
{
    public List<CPGameHistoryWinningEntry> last_winning;
    public int code;
}

[System.Serializable]
public class VCPBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}

[Serializable]
public class BetIdDetails
{
    public string id;
    public string winning;
    public string status;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
    public string name;
}

[Serializable]
public class BetRootObject
{
    public BetIdDetails bet_id_details;
    public string message;
    public int code;
}
