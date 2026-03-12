using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class SevenupdownResponse : MonoBehaviour
{

}
[Serializable]
public class sevenupdownBotUser
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
public class sevenupdownGameData
{
    public string id;
    public string room_id;
    public string main_card;
    public string winning;
    public string status;
    public string added_date;
    public int time_remaining;
    public string end_datetime;
    public string updated_date;
}

[Serializable]
public class sevenupdownGameCard
{
    public string id;
    public string seven_up_id;
    public string card;
    public string added_date;
}

[Serializable]
public class sevenupdownLastWinning
{
    public string id;
    public string room_id;
    public string main_card;
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
public class sevenupdownRootObject
{
    public List<sevenupdownBotUser> bot_user;
    public string message;
    public List<sevenupdownGameData> game_data;
    public List<sevenupdownGameCard> game_cards;
    public string online;
    public List<object> online_users; // The type might need adjustment based on the actual data
    public List<object> last_bet; // The type might need adjustment based on the actual data
    public List<sevenupdownLastWinning> last_winning;
    public int down_bet;
    public int up_bet;
    public int tie_bet;
    public int code;
}
[System.Serializable]
public class SevenUpDownBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}