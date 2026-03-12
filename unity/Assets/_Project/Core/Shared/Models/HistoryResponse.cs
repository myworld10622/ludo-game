using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class HistoryResponse : MonoBehaviour { }

[Serializable]
public class PokerLogEntry
{
    public string game_id;
    public string user_id;
    public string action;
    public string amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
}

[Serializable]
public class PokerLogResponse
{
    public List<PokerLogEntry> Pokerlog;
    public string message;
    public int code;
}

[Serializable]
public class PoolGameLogEntry
{
    public string game_id;
    public string user_id;
    public string action;
    public string amount;
    public string user_amount;
    public string winning_amount;
    public string commission_amount;
    public string added_date;
}

[Serializable]
public class PoolGameLogResponse
{
    public List<PoolGameLogEntry> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[Serializable]
public class PointRummyGameLogEntry
{
    public string game_id;
    public string user_id;
    public string action;
    public string amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
}

[Serializable]
public class PointRummyGameLogResponse
{
    public List<PointRummyGameLogEntry> RummyGameLog;
    public string message;
    public int code;
}

[Serializable]
public class JhandiMundaLogEntry
{
    // public string jhandi_munda_id;
    // public string invest;
    // public string winning_amount;
    // public string added_date;
    // public string table_type;
    public string id;
    public string jhandi_munda_id;
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

[Serializable]
public class JhandiMundaLogResponse
{
    public List<JhandiMundaLogEntry> JhandiMundalog;
    public string message;
    public int code;
}

[System.Serializable]
public class TeenPattiGameLog
{
    public string game_id;
    public string invest;
    public string winning_amount;
    public string admin_commission;
    public string added_date;
    public string table_type;
}

[System.Serializable]
public class TPRootObject
{
    public List<TeenPattiGameLog> TeenPattiGameLog;
    public string message;
    public int code;
}

[Serializable]
public class ABGameLogEntry
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
    public string room_id;
}

[Serializable]
public class ABGameLogData
{
    public List<ABGameLogEntry> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[System.Serializable]
public class DNTGameLogEntry
{
    public string id;
    public string dragon_tiger_id;
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
    public string room_id;
}

[System.Serializable]
public class DNTRootObject
{
    public DNTGameLogEntry[] GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[System.Serializable]
public class sevenGameLogEntry
{
    public string id;
    public string seven_up_id;
    public string user_id;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
    public string room_id;
}

[System.Serializable]
public class HTGameDataHistory
{
    public List<GameLogHeadAndtail> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[System.Serializable]
public class RouletteGameHis
{
    public List<RouletteGameLog> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[System.Serializable]
public class RouletteGameLog
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
    public string room_id;
}

[System.Serializable]
public class GameLogHeadAndtail
{
    public string id;
    public string head_tail_id;
    public string user_id;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
    public string room_id;
}

[System.Serializable]
public class sevenRootObject
{
    public sevenGameLogEntry[] GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[Serializable]
public class AnimalRouletteGameLogEntry
{
    public string id;
    public string animal_roulette_id;
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
    public string room_id;
}

[Serializable]
public class AnimalRouletteGameLogResponse
{
    public List<AnimalRouletteGameLogEntry> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[Serializable]
public class BaccaratGameLogEntry
{
    public string id;
    public string baccarat_id;
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
    public string room_id;
}

[Serializable]
public class BaccaratGameLogResponse
{
    public List<BaccaratGameLogEntry> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[Serializable]
public class ColorPredictionGameLogEntry
{
    public string id;
    public string color_prediction_id;
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
    public string room_id;
}

[Serializable]
public class ColorPredictionGameLogResponse
{
    public List<ColorPredictionGameLogEntry> GameLog;
    public string MinRedeem;
    public string message;
    public int code;
}

[Serializable]
public class AvaitorGameHistoryDataList
{
    public List<AviatorgamehistorGameData> game_data;
    public int code;
}

[Serializable]
public class AviatorgamehistorGameData
{
    public string id;
    public string dragon_tiger_id;
    public string user_id;
    public string bet;
    public string bet_status;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
    public string status;
    public string name;
    public string avatar;
}

[Serializable]
public class historyGameData
{
    public string id;
    public string color_prediction_id;
    public string user_id;
    public string bet;
    public string amount;
    public string winning_amount;
    public string user_amount;
    public string comission_amount;
    public string added_date;
    public string status;
}

[Serializable]
public class historyGameDataList
{
    public List<historyGameData> game_data;
}
