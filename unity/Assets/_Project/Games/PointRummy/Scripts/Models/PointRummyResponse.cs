using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class PointRummyResponse : MonoBehaviour { }

[System.Serializable]
public class BoolWrapper
{
    public bool Value;
}

[System.Serializable]
public class Pointss
{
    public string game_id;
    public string user_id;
    public string points;
    public string total_points;
    public string name;
}

[System.Serializable]
public class TableData
{
    public string id;
    public string point_value;
    public string boot_value;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string online_members;
    public string no_of_players;
}

[System.Serializable]
public class ResponseData
{
    public string message;
    public List<TableData> table_data;
    public int code;
}

[System.Serializable]
public class PointRummyTableData
{
    public string user_id;
    public string token;
    public string no_of_players;
    public string tournament_id;
    public string boot_value;

    public string Id;
}

[System.Serializable]
public class Table
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
    public string user_type;
    public string no_of_players;
}

[System.Serializable]
public class SocketResponse
{
    public string message;
    public Table[] table_data;
    public int code;
}

[System.Serializable]
public class OnDropCardData
{
    public string message;
    public int code;
}

[System.Serializable]
public class DropCardData
{
    public string id;
    public string card;
}

[System.Serializable]
public class DropCardResponse
{
    public string message;
    public int code;
    public List<DropCardData> card;
}

[System.Serializable]
public class DataList
{
    public List<finalcarddata> dataList;
}

[System.Serializable]
public class finalcarddata
{
    public string card_group;
    public List<string> cards;

    public finalcarddata(string group, List<string> cardList)
    {
        card_group = group;
        cards = cardList;
    }
}

[System.Serializable]
public class LeaveTableData
{
    public string user_id;
    public string token;
    public string no_of_players;
    public string tournament_id;
    public string boot_value;
}

public class basicID
{
    public string user_id;
    public string token;
}

[System.Serializable]
public class GameResponse
{
    public string message;
    public int game_id;
    public string table_amount;
    public int code;
}

[System.Serializable]
public class GameCardDropData
{
    public string card;
    public string user_id;
    public string token;
    public string json;
}

[System.Serializable]
public class DiscardedCard
{
    public string id;
    public string game_id;
    public string user_id;
    public string card;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class PointRummyGameData
{
    public List<TableUser> table_users;
    public TableDetail table_detail;
    public string active_game_id;
    public int game_status;
    public string table_amount;
    public List<Points> points;
    public List<PointRummyGameLog> game_log;
    public List<TableUser> all_users;
    public bool declare;
    public string declare_user_id;
    public List<GameUser> game_users;
    public string chaal;
    public string game_amount;
    public LastCard last_card;
    public List<DiscardedCard> discarded_card;
    public int cut_point;
    public int round;
    public List<DropCard> drop_card;
    public string joker;
    public string message;
    public List<GameUserCards> game_users_cards;
    public string winner_user_id;
    public int code;
}

[System.Serializable]
public class RootObject2
{
    public List<TableUser> table_users;
    public TableDetail2 table_detail;
    public string active_game_id;
    public int game_status;
    public string table_amount;
    public List<Pointss> points;
    public List<CachetaGameLog> game_log;
    public List<TableUser> all_users;
    public bool declare;
    public string declare_user_id;
    public List<GameUser> game_users; // You might need to create a separate class for User within GameUsers
    public int chaal;
    public string game_amount;
    public LastCard last_card; // You should define a class for LastCard as well
    public int cut_point;
    public List<DropCard> drop_card; // You should define a class for DropCard as well
    public string joker;
    public string message;
    public string table_winner_id;
    public List<GameUserCards> game_users_cards; // You should define a class for GameUserCard as well
    public string winner_user_id;
    public int code;
}

[System.Serializable]
public class RummyGameData
{
    public List<TableUser> table_users;
    public TableUser table_detail;
    public int active_game_id;
    public int game_status;
    public string table_amount;
    public List<Pointss> points;
    public List<CachetaGameLog> game_log;
    public List<TableUser> all_users;
    public bool declare;
    public int declare_user_id;
    public List<GameUser> game_users;
    public int chaal;
    public string game_amount;
    public LastCard last_card;
    public int cut_point;
    public List<DropCard> drop_card;
    public string joker;
    public string message;
    public List<GameUserCards> game_users_cards;
    public string winner_user_id;
    public int code;
    public List<FlattenedUserCards> flattened_user_cards;
}

[System.Serializable]
public class GameUserCards
{
    public GameUser user;
}

[System.Serializable]
public class DropCard
{
    public string card;
}

[System.Serializable]
public class GameUser
{
    public string user_id;
    public string packed;
    public string name;
    public string win;
    public string result;
    public string score;
    public string total;
    public List<CardGroup> cards;
}

[System.Serializable]
public class CardGroup
{
    public string card_group;
    public List<string> cards;
}

[System.Serializable]
public class TableUser
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string name;
    public string mobile;
    public string total_points;
    public string profile_pic;
    public string wallet;
    public string user_type;
    public string no_of_players;
}

[System.Serializable]
public class TableDetail2
{
    public string id;
    public string boot_value;
    public string no_of_players;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string Private;
    public string code;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class TableDetail
{
    public string id;
    public string boot_value;
    public string no_of_players;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string private_value;
    public string code;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class PointRummyGameLog
{
    public string id;
    public string game_id;
    public string user_id;
    public string action;
    public string json;
    public string seen;
    public string amount;
    public string points;
    public string timeout;
    public string added_date;
}

[System.Serializable]
public class LastCard
{
    public string id;
    public string game_id;
    public string user_id;
    public string card;
    public string packed;
    public string is_drop_card;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class Points
{
    public string game_id;
    public string user_id;
    public string points;
    public string total_points;
    public string name;
}

[System.Serializable]
public class PlayerData
{
    public string id;
    public string table_id;
    public string user_id;
    public string seat_position;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string name;
    public string mobile;
    public string profile_pic;
    public string wallet;
    public string user_type;
    public string no_of_players;
    public string boot_value;
}

[System.Serializable]
public class TableDataifalready
{
    public string message;
    public PlayerData[] table_data;
    public string no_of_players;
    public int code;
}

[System.Serializable]
public class GameCardData
{
    public string user_id;
    public string token;
    public string gameid;
}

public class GamePackData
{
    public string user_id;
    public string token;
    public string json;
}

[System.Serializable]
public class UserIDDetails
{
    public string user_id;
    public string token;
}

[System.Serializable]
public class OneCard
{
    public string message;
    public int code;
    public singlecarddata[] card;
}

[System.Serializable]
public class singlecarddata
{
    public string cards;
}

[System.Serializable]
public class JsonCardData
{
    public List<MYCardData> cards;
    public string message;
    public int code;
}

[System.Serializable]
public class FlattenedUserCards
{
    public string user_id;
    public List<string> all_cards;

    public FlattenedUserCards()
    {
        all_cards = new List<string>();
    }
}

[System.Serializable]
public class MYCardData
{
    public string id;
    public string card;
    public string card_group;
}

[System.Serializable]
public class CachetaGameData
{
    public List<TableUser> table_users;
    public TableDetail table_detail;
    public string active_game_id;
    public int game_status;
    public string table_amount;
    public List<Pointss> points;
    public List<CachetaGameLog> game_log;
    public List<TableUser> all_users;
    public bool declare;
    public string declare_user_id;
    public List<GameUser> game_users;
    public string chaal;
    public string game_amount;
    public LastCard last_card;
    public int cut_point;
    public int round;
    public List<DropCard> drop_card;
    public string joker;
    public string message;
    public int code;
}

[System.Serializable]
public class CachetaGameLog
{
    public string id;
    public string game_id;
    public string user_id;
    public string action;
    public string json;
    public string seen;
    public string amount;
    public string points;
    public string timeout;
    public string added_date;
}
