using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class CarRouletteResponse : MonoBehaviour
{
    // Start is called before the first frame update
    void Start()
    {

    }

    // Update is called once per frame
    void Update()
    {

    }
}
[Serializable]
public class CarRouletteBetResponse
{
    public string message;
    public string wallet;
    public int code;
}
[System.Serializable]
public class CRBotUser
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
public class CRGameData
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
public class CRGameCard
{
    public string id;
    public string car_roulette_id;
    public string card;
    public string added_date;
}

[System.Serializable]
public class CRLastWinning
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
public class CRRootObject
{
    public List<CRBotUser> bot_user;
    public string message;
    public List<CRGameData> game_data;
    public List<CRGameCard> game_cards;
    public string online;
    public string[] online_users;
    public string[] last_bet;
    public int toyota_amount;
    public int mahindra_amount;
    public int audi_amount;
    public int bmw_amount;
    public int mercedes_amount;
    public int porsche_amount;
    public int lamborghini_amount;
    public int ferrari_amount;
    public List<CRLastWinning> last_winning;
    public int code;
}

[System.Serializable]
public class CarRouletteBetResult
{
    public float win_amount;
    public float bet_amount;
    public float diff_amount;
    public string wallet;
    public string message;
    public int code;
}

