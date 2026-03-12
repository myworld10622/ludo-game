using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class GetCoin : MonoBehaviour
{
    public List<GameObject> coins;
    public List<GameObject> coinstodelete;

    public GameObject coin()
    {
        return coins[Random.Range(0, coins.Count)];
    }
}
