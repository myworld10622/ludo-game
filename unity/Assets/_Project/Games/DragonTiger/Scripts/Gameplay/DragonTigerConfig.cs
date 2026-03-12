using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class DragonTigerConfig : MonoBehaviour
{
    public const string DragonTigerPutBet = Configuration.APIUrl + "DragonTiger/place_bet";
    public const string DragonTigerResult = Configuration.APIUrl + "DragonTiger/get_result";
    public const string DragonTigerHistory = Configuration.UserUrl + "wallet_history_dragon";

    public static string GetDragonTigerId()
    {
        return PlayerPrefs.GetString("dntid");
    }
}
