using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class AnimalRoulletConfig : MonoBehaviour
{
    public const string AnimalRouletteResult = Configuration.BaseUrl + "/api/AnimalRoulette/get_result";
    public const string AnimalRoulletePutBet = Configuration.BaseUrl + "/api/AnimalRoulette/place_bet";
}
