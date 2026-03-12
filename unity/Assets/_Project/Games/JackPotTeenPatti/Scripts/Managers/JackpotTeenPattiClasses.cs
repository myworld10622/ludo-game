using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public static class JackpotTeenPattiClasses { }

[Serializable]
public class jktBotUser
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
public class jktGameData
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
public class jktLastWinning
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
public class jktGameCard
{
    public string id;
    public string jackpot_id;
    public string card;
    public string added_date;
}

[Serializable]
public class jktRoot
{
    public List<jktBotUser> bot_user;
    public string message;
    public List<jktGameData> game_data;
    public List<jktGameCard> game_cards;
    public string online;
    public List<string> online_users;
    public string high_card_amount;
    public string pair_amount;
    public string color_amount;
    public string sequence_amount;
    public string pure_sequence_amount;
    public string set_amount;
    public List<string> last_bet;
    public string jackpot_amount;
    public List<jktLastWinning> last_winning;
    public List<JTPBigWinner> big_winner;
    public int code;
}

[System.Serializable]
public class JTPBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}

[System.Serializable]
public class JTPBigWinner
{
    public string amount;
    public string winning_amount;
    public string name;
    public string profile_pic;
}
