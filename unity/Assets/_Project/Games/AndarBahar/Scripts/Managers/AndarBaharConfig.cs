using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class AndarBaharConfig : MonoBehaviour
{
    public const string andarbaharResult = Configuration.BaseUrl + "/api/AnderBahar/get_result";
    public const string AndarBaharPutBet = Configuration.BaseSocketUrl + "/api/AnderBahar/place_bet";
    public const string AndarBaharPlusPutBet = Configuration.BaseUrl + "/api/AnderBaharPlus/place_bet";
    public const string andarbaharPlusResult = Configuration.BaseUrl + "/api/AnderBaharPlus/get_result";
}
