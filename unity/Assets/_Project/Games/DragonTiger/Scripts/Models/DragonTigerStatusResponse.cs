using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class DragonTigerStatusResponse : MonoBehaviour
{

}
#region Data
[Serializable]
public class DTBotUser
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
public class DTGameData
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
public class DTGameCard
{
    public string id;
    public string dragon_tiger_id;
    public string card;
    public string added_date;
}

[Serializable]
public class DTLastWinning
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
public class DTSocketResponse
{
    public List<DTBotUser> bot_user;
    public string message;
    public List<DTGameData> game_data;
    public List<DTGameCard> game_cards;
    public string my_dragon_bet;
    public string my_tiger_bet;
    public string my_tie_bet;
    public int dragon_bet;
    public int tiger_bet;
    public int tie_bet;
    public List<DTLastWinning> last_winning;
    public int code;
}

[Serializable]
public class DragonVsTigerResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}
[System.Serializable]
public class JsonResponse
{
    public string message;
    public int bet_id;
    public string wallet;
    public int code;
}
#endregion
