using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class JackpotTeenPattiConfig : MonoBehaviour
{
    public const string JackpotTeenPattiResult = Configuration.BaseUrl + "/api/jackpot/get_result";
    public const string JackpotTeenPattiPutBet = Configuration.BaseUrl + "/api/jackpot/place_bet";
    public const string CustomNamespace = "/jackpot";
}
