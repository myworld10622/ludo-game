using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class BetPool : MonoBehaviour
{
    public static BetPool Instance;

    private Stack<BetFootprint> _BetFootprints;
    public List<BetSpace> _BetsList;
    private List<BetSpace> _RebetList;

    private void Awake()
    {
        if (Instance == null)
        {
            _BetFootprints = new Stack<BetFootprint>();
            _BetsList = new List<BetSpace>();

            Instance = this;
        }
        else
            Destroy(this.gameObject);
    }

    public void ResetStatus()
    {
        _BetFootprints.Clear();
        _RebetList = new List<BetSpace>(_BetsList);
        _BetsList.Clear();
    }

    public void Add(BetSpace space, float value)
    {
        _BetFootprints.Push(new BetFootprint(space, value));

        if (!_BetsList.Contains(space))
            _BetsList.Add(space);
    }

    public void Clear()
    {
        foreach (BetSpace bet in _BetsList)
            bet.Clear();

        // _BetFootprints.Clear();
        // _BetsList.Clear();

        // SceneRoulette._Instance.rollButton.interactable = false;
        // SceneRoulette._Instance.undoButton.interactable = false;
        // SceneRoulette._Instance.clearButton.interactable = false;

        ResultManager.totalBet = 0;
    }

    public void Undo()
    {
        BetFootprint footprint = _BetFootprints.Pop();
        footprint.betSpace.RemoveBet(footprint.value);

        if (footprint.betSpace.GetValue() >= 0)
        {
            _BetsList.Remove(footprint.betSpace);
        }

        SceneRoulette._Instance.clearButton.interactable = _BetFootprints.Count > 0;
        SceneRoulette._Instance.undoButton.interactable = _BetFootprints.Count > 0;
        SceneRoulette._Instance.rollButton.interactable = _BetFootprints.Count > 0;
    }

    public IEnumerator Rebet()
    {
        if (!RouletteManager.destroychip)
        {
            ResultManager.totalBet = 0;
            RouletteAudioManager.SoundPlay(3);

            foreach (BetSpace bet in _RebetList)
            {
                bet.Rebet();
                yield return null;
            }

            SceneRoulette._Instance.clearButton.interactable = true;
            SceneRoulette._Instance.undoButton.interactable = true;
        }
    }
}

[System.Serializable]
public class BetFootprint
{
    public BetSpace betSpace;
    public float value;

    public BetFootprint(BetSpace betSpace, float value)
    {
        this.betSpace = betSpace;
        this.value = value;
    }
}
