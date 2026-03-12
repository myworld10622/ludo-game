using System.Collections;
using System.Collections.Generic;
using UnityEngine;



public class HeadTailResponse : MonoBehaviour
{

}


[System.Serializable]
public class HTBotUser
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
public class HTGameData
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

[System.Serializable]
public class HTGameCard
{
    public string id;
    public string head_tail_id;
    public string card;
    public string added_date;
}

[System.Serializable]
public class HTOnlineUser
{
    // Add necessary fields based on your requirements
    public string id;
    public string name;
    public string user_type;
    public string wallet;
    public string profile_pic;
    // Add more fields as needed
}

[System.Serializable]
public class HTLastWinning
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

[System.Serializable]
public class HTRootObject
{
    public HTBotUser[] bot_user;
    public string message;
    public HTGameData[] game_data;
    public HTGameCard[] game_cards;
    public HTOnlineUser[] online_users;
    public int online;
    public string my_head_bet;
    public string my_tail_bet;
    public string head_bet;
    public string tail_bet;
    public HTLastWinning[] last_winning;
    public int code;
}

[System.Serializable]
public class HeadAndtailBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}



[System.Serializable]
public class RBPutBetResponse
{
    public string message;
    public string wallet;
    public int code;
}