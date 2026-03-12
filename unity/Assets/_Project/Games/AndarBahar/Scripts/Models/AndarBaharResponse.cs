using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class AndarBaharResponse : MonoBehaviour { }

[System.Serializable]
public class BotUser
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
public class GameCard
{
    public string id;
    public string ander_bahar_id;
    public string card;
    public string added_date;
}

[System.Serializable]
public class LastWinning
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
public class ABGameData
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
public class LastBet
{
    public string id;
    public string ander_baher_id;
    public string user_id;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
}

[System.Serializable]
public class RootObject
{
    public BotUser[] bot_user;
    public string message;
    public ABGameData[] game_data;
    public GameCard[] game_cards;
    public List<LastBet> last_bet;
    public int online;
    public int ander_bet;
    public int bahar_bet;
    public LastWinning[] last_winning;
    public int code;
}

[System.Serializable]
public class AndarBaharBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;

    public string wallet;
}

[Serializable]
public class BotsUser
{
    public GameObject UserCome;
    public GameObject NoUser;
    public Text BotName;
    public Text BotCoin;
    public Image ProfileImage;
}
