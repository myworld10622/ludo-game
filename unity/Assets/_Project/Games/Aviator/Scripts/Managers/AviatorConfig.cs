using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class AviatorConfig : MonoBehaviour
{
    public const string AviatorGameHistory = Configuration.Url + "User/aviator_GameHistory";
    public const string AviatorHistory = Configuration.Url + "User/aviatorHistory";
    public const string AviatorMyHistory = Configuration.Url + "User/aviator_myHistory";
    public const string AviatorAddBet = Configuration.BaseSocketUrl + "/api/aviator/place_bet";
    public const string AviatorCancelBet = Configuration.BaseSocketUrl + "/api/aviator/cancelBet";
    public const string AviatorRedeem = Configuration.BaseSocketUrl + "/api/aviator/redeem";
    public const string AviatorToken = "173482882103bb4460fbbaa958585d91";
}
