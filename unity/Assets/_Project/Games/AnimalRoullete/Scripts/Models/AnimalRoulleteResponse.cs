using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class AnimalRoulleteResponse : MonoBehaviour
{

}
[System.Serializable]
public class ARBotUser
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
public class ARGameData
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
public class ARGameCards
{
    public string id;
    public string animal_roulette_id;
    public string card;
    public string added_date;
}

[System.Serializable]
public class ARLastWinning
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
public class SevenUPPutBetResponse
{
    public string message;
    public string wallet;
    public int code;
}
public class BetInfo
{
    public int currentAmount;
    public int maxAmount;
    public Text amountText;
    public GameObject coinPrefab;
    public Vector3 endPosition;
    public List<GameObject> objects = new List<GameObject>();
}

[System.Serializable]
public class ARRootObject
{
    public List<ARBotUser> bot_user;
    public string message;
    public List<ARGameData> game_data;
    public List<ARGameCards> game_cards;
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
    public List<ARLastWinning> last_winning;
    public int code;
}

[System.Serializable]
public class AnimalRouletteBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string message;
    public int code;
}
