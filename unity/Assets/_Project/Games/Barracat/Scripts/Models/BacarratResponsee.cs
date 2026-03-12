using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class BacarratResponsee : MonoBehaviour { }

[Serializable]
public class BacBotUser
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
public class BacGameData
{
    public string id;
    public string room_id;
    public string winning;
    public string player_pair;
    public string banker_pair;
    public string status;
    public string added_date;
    public int time_remaining;
    public string end_datetime;
    public string updated_date;
}

[Serializable]
public class BacGameCard
{
    public string id;
    public string baccarat_id;
    public string card;
    public string added_date;
}

[Serializable]
public class BacLastWinning
{
    public string id;
    public string room_id;
    public string main_card;
    public string winning;
    public string player_pair;
    public string banker_pair;
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
public class BacRoot
{
    public List<BacBotUser> bot_user;
    public string message;
    public List<BacGameData> game_data;
    public List<BacGameCard> game_cards;
    public int online;
    public int player_amount;
    public int banker_amount;
    public int tie_amount;
    public int player_pair_amount;
    public int banker_pair_amount;
    public List<object> last_bet;
    public List<BacLastWinning> last_winning;
    public int code;
}

[System.Serializable]
public class BaccaretBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}