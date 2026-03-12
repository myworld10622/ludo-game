using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class RoomPrefabController : MonoBehaviour
{
    public Text entryFeeText;
    public Text winningAmountFeeText;

    public void SetPrefabData(string entryFee,int totalPlayer)
    {
        entryFeeText.text = entryFee;
        float winningAmount = float.Parse(entryFee);
        winningAmountFeeText.text = (winningAmount * totalPlayer).ToString("F2");
    }
}
