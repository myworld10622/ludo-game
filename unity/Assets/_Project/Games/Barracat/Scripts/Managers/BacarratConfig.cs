using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class BacarratConfig : MonoBehaviour
{    
    public const string BaccaratPutBet = Configuration.BaseUrl + "/api/Baccarat/place_bet";
    public const string BaccaratResult = Configuration.BaseUrl + "/api/baccarat/get_result";
}
