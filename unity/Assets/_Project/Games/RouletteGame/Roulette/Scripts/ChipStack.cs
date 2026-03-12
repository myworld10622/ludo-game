using System;
using System.Collections.Generic;
using DG.Tweening;
using TMPro;
using UnityEngine;

public class ChipStack : MonoBehaviour
{
    public static readonly int[] CHIP_VALUES = new int[] { 1, 5, 10, 50, 100, 500 };
    public static readonly Vector3 CollectPosition = new Vector3(0, 0, -3);

    private Vector3 initialPosition;
    private float value = 0;

    public List<GameObject> chips;

    void Start()
    {
        initialPosition = transform.position;
    }

    public void SetInitialPosition(Vector3 pos)
    {
        transform.position = pos;
        initialPosition = pos;
    }

    public void Add(float value)
    {
        SetValue(value);
    }

    public void PreviousAdd(float value)
    {
        SetOneValue(value);
    }

    public void Remove(float value)
    {
        SetValue(value);
    }

    public float Clear()
    {
        float lastBet = value;
        value = 0;
        transform.position = initialPosition;

        if (chips != null)
        {
            foreach (GameObject chip in chips)
            {
                Destroy(chip);
            }
        }
        chips = null;
        this.transform.parent.GetComponent<BetSpace>().total_bet = 0;
        return lastBet;
    }

    public float LastbetClear()
    {
        float lastBet = value;
        Debug.Log("Bet space " + chips.Count);
        if (chips != null)
        {
            Destroy(chips[chips.Count - 1]);
            chips.RemoveAt(chips.Count - 1);
            Debug.Log("Bet space");
            this.transform.parent.GetComponent<BetSpace>().total_bet = 0;
        }

        return lastBet;
    }

    public void destroychip()
    {
        if (chips != null)
        {
            Destroy(chips[chips.Count - 1]);
            chips.RemoveAt(chips.Count - 1);
            Debug.Log("Bet space");
            this.transform.parent.GetComponent<BetSpace>().total_bet = 0;
        }
    }

    public float GetValue()
    {
        return value;
    }

    public void SetOneValue(float value)
    {
        Debug.Log("RES_Check + added text name " + this.transform.parent.name);
        Clear();

        //Debug.Log("RES_Check + before value " + value);

        if (value <= 0)
        {
            Debug.LogError("Chip does not have a child");
            return;
        }

        this.value = value;
        chips = new List<GameObject>();
        if (ChipManager.IsBetSuccess)
        {
            Debug.Log("RES_Check + IsBetSuccess");
            GameObject newChip = ChipManager.InstantiateChip(0);
            newChip.transform.parent = gameObject.transform;
            newChip.transform.localPosition = new Vector3(0, .01f * (chips.Count + 1), 0);
            chips.Add(newChip);
            Transform chipChild = chips[0].transform.GetChild(0);
            if (chipChild == null)
            {
                Debug.LogError("Chip does not have a child at index 0.");
                return;
            }
            else
            {
                Debug.Log("RES_Check + added text 4 " + chipChild);
            }

            TextMeshPro tmpText = chipChild.GetComponent<TextMeshPro>();
            if (tmpText == null)
            {
                Debug.LogError("No TextMeshProUGUI found on chip's first child.");
                return;
            }
            else
            {
                Debug.Log("RES_Check + added text 5 " + tmpText);
            }
            this.transform.parent.GetComponent<BetSpace>().text_for_bet = chips[0]
                .transform.GetChild(0)
                .GetComponent<TextMeshPro>();
        }
    }

    public void SetValue(float value)
    {
        Debug.Log("RES_Check + added text name " + this.transform.parent.name);
        Clear();

        //Debug.Log("RES_Check + before value " + value);

        if (value <= 0)
        {
            return;
        }

        this.value = value;
        chips = new List<GameObject>();

        int currentChipIndex = 0;

        for (int i = 0; i < CHIP_VALUES.Length; i++)
        {
            if (value == CHIP_VALUES[i])
            {
                Debug.Log("RES_Check + value " + i);
                currentChipIndex = i;
                break;
            }
        }
        Debug.Log("RES_Check + chipindex " + currentChipIndex);
        if (ChipManager.IsBetSuccess)
        {
            GameObject newChip = ChipManager.InstantiateChip(currentChipIndex);
            newChip.transform.parent = gameObject.transform;
            newChip.transform.localPosition = new Vector3(0, .01f * (chips.Count + 1), 0);
            chips.Add(newChip);
            Debug.Log("RES_Check + added text " + this.transform.parent.name);
            Debug.Log("RES_Check + added text 2 " + this.transform.parent.GetComponent<BetSpace>());
            Debug.Log(
                "RES_Check + added text 3 "
                    + chips[0].transform.GetChild(0).GetComponent<TextMeshProUGUI>()
            );
            Transform chipChild = chips[0].transform.GetChild(0);
            if (chipChild == null)
            {
                Debug.LogError("Chip does not have a child at index 0.");
                return;
            }
            else
            {
                Debug.Log("RES_Check + added text 4 " + chipChild);
            }

            TextMeshPro tmpText = chipChild.GetComponent<TextMeshPro>();
            if (tmpText == null)
            {
                Debug.LogError("No TextMeshProUGUI found on chip's first child.");
                return;
            }
            else
            {
                Debug.Log("RES_Check + added text 5 " + tmpText);
            }
            this.transform.parent.GetComponent<BetSpace>().text_for_bet = chips[0]
                .transform.GetChild(0)
                .GetComponent<TextMeshPro>();
        }
    }

    public float Win(int multiplier)
    {
        float winAmount = value * multiplier;
        SetValue(winAmount);

        if (winAmount > 0)
        {
            CollectChips();
        }

        return winAmount;
    }

    public void CollectChips()
    {
        transform
            .DOMove(CollectPosition, 1)
            .SetEase(Ease.InSine)
            .SetDelay(1.5f)
            .OnComplete(() =>
            {
                Clear();
            });
    }
}
