using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class ColorPredicationResponse : MonoBehaviour
{

}
[Serializable]
public class CPBotUser
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
public class CPGameData
{
    public string id;
    public string room_id;
    public string winning;
    public string status;
    public string added_date;
    public string time_remaining;
    public string end_datetime;
    public string updated_date;
}

[Serializable]
public class CPLastWinning
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
public class CPRoot
{
    public List<CPBotUser> bot_user;
    public string message;
    public List<CPGameData> game_data;
    public string online;
    public List<CPLastWinning> last_winning;
    public int code;
}

[System.Serializable]
public class ColorPredictionBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}
[Serializable]
public class CPTPutBetResponse
{
    public string message;
    public string bet_id;
    public string wallet;
    public int code;
}