using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class RouletteResponse : MonoBehaviour { }

[System.Serializable]
public class RouletteBotUser
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
public class RouletteGameData
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

[System.Serializable]
public class RouletteGameCards
{
    public string id;
    public string animal_roulette_id;
    public string card;
    public string added_date;
}

[System.Serializable]
public class RouletteLastWinning
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

[System.Serializable]
public class RouletteRootObject
{
    public List<RouletteBotUser> bot_user;
    public string message;
    public List<RouletteGameData> game_data;
    public List<RouletteGameCards> game_cards;
    public string online;
    public List<object> online_users; // Assuming it's an array of objects
    public List<object> last_bet; // Assuming it's an array of objects
    public int tiger_amount;
    public int snake_amount;
    public int shark_amount;
    public int fox_amount;
    public int cheetah_amount;
    public int bear_amount;
    public int whale_amount;
    public int lion_amount;
    public List<RouletteLastWinning> last_winning;
    public int code;
}

[System.Serializable]
public class RouletteBetResult
{
    public float win_amount;
    public int bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}

[System.Serializable]
public class NumberDetails
{
    public Color ColorSet;
    public List<string> NumbersList;
}

[Serializable]
public class RoulettePutBetResponse
{
    public string message;
    public string wallet;
    public int code;
}

[System.Serializable]
public class BetData
{
    public string id;
    public string roulette_id;
    public string user_id;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string minus_unutilized_wallet;
    public string minus_winning_wallet;
    public string minus_bonus_wallet;
    public string added_date;
}

[System.Serializable]
public class RouletteBetResponse
{
    public string message;
    public List<BetData> bet;
    public int amount;
    public string wallet;
    public int code;
}
