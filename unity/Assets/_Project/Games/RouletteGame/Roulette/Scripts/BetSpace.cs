using System.Collections;
using TMPro;
using UnityEngine;

[System.Serializable]
public enum BetType
{
    Straight,
    Split,
    Corner,
    Street,
    DoubleStreet,
    Row,
    Dozen,
    Low,
    High,
    Even,
    Odd,
    Red,
    Black,
}

public class BetSpace : MonoBehaviour
{
    public ChipStack stack;
    public BetType betType;
    public static int numLenght = 38; //Change this to change the amount of rewards

    [SerializeField]
    public int[] winningNumbers;

    public MeshRenderer[] betSpaceRender;

    private MeshRenderer mesh;
    public float lastBet = 0;
    public int betnum;
    public float total_bet;
    public TextMeshPro text_for_bet;

    public static bool BetsEnabled { get; private set; } = true;

    public float GetValue() => stack.GetValue();

    void Start()
    {
        mesh = GetComponent<MeshRenderer>();

        if (mesh)
            mesh.enabled = false;

        stack = Cloth.InstanceStack();
        stack.SetInitialPosition(transform.position);
        stack.transform.SetParent(transform);
        stack.transform.localPosition = Vector3.zero;
        ResultManager.RegisterBetSpace(this);
        //AmericanWheel.OnRebetAndSpin += Rebet;
    }

    // private void OnMouseEnter()
    // {
    //     //ToolTipManager.SelectTarget(stack);

    //     if (mesh)
    //         mesh.enabled = true;

    //     if (!BetsEnabled)
    //         return;

    //     if (betSpaceRender.Length > 0)
    //     {
    //         foreach (MeshRenderer spaceRender in betSpaceRender)
    //         {
    //             spaceRender.enabled = true;
    //         }
    //     }
    // }

    void OnMouseExit()
    {
        //ToolTipManager.Deselect();

        if (mesh)
            mesh.enabled = false;

        if (!BetsEnabled)
            return;

        if (betSpaceRender.Length > 0)
        {
            foreach (MeshRenderer spaceRender in betSpaceRender)
            {
                spaceRender.enabled = false;
            }
        }
    }

    // private void OnMouseUp()
    // {
    //     Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);
    //     RaycastHit hit;

    //     // Check if the ray hits any collider
    //     if (Physics.Raycast(ray, out hit))
    //     {
    //         // Check if the collider belongs to a GameObject
    //         if (hit.collider.gameObject.name == gameObject.name)
    //         {
    //             Debug.Log("Clicked on object with collider: " + hit.collider.gameObject.name);
    //             if (hit.collider.gameObject.name == this.gameObject.name)
    //             {
    //                 float selectedValue = ChipManager.GetSelectedValue();
    //                 Debug.Log("RES_Check + SelectedValue + " + selectedValue);
    //                 if (RouletteManager.canbet)
    //                     ApplyBet(selectedValue);
    //             }
    //             //   ToolTipManager.SelectTarget(stack);
    //         }
    //     }
    //     else
    //     {
    //         Debug.Log("Clicked on empty space");
    //         // Add your code here to perform actions when no object with a collider is clicked
    //     }
    // }
    public void ApplyBetFromManager()
    {
        if (mesh)
            mesh.enabled = true;

        if (!BetsEnabled)
            return;

        if (betSpaceRender.Length > 0)
        {
            foreach (MeshRenderer spaceRender in betSpaceRender)
            {
                spaceRender.enabled = true;
            }
        }
        float selectedValue = ChipManager.GetSelectedValue();
        if (RouletteManager.canbet)
            ApplyBet(selectedValue);
    }

    public void ApplyBet(float selectedValue)
    {
        if (betnum == 1)
        {
            total_bet += selectedValue;
            text_for_bet.text = total_bet.ToString();
            return;
        }

        if (BetsEnabled && selectedValue > 0)
        {
            RouletteAudioManager.SoundPlay(3);
            print("Bet applyed! with: " + selectedValue);

            BalanceManager.ChangeBalance(-selectedValue);
            ResultManager.totalBet += selectedValue;

            Debug.Log("RES_Check + selectedValue value + " + selectedValue);
            stack.Add(selectedValue);
            total_bet += selectedValue;
            lastBet = stack.GetValue();
            //text_for_bet.text = total_bet.ToString();

            BetPool.Instance.Add(this, selectedValue);
            BetPool.Instance.Rebet();

            SceneRoulette._Instance.clearButton.interactable = true;
            SceneRoulette._Instance.undoButton.interactable = true;
            SceneRoulette._Instance.rollButton.interactable = true;
            SceneRoulette._Instance.rebetButton.gameObject.SetActive(false);
            SceneRoulette.UpdateLocalPlayerText();
            betnum++;
            StartCoroutine(destroychip());
        }
    }

    public void ApplyPreviousBet(float selectedValue)
    {
        if (betnum == 1)
        {
            total_bet += selectedValue;
            text_for_bet.text = total_bet.ToString();
            return;
        }

        if (BetsEnabled && selectedValue > 0)
        {
            RouletteAudioManager.SoundPlay(3);
            print("Bet applyed! with: " + selectedValue);

            BalanceManager.ChangeBalance(-selectedValue);
            ResultManager.totalBet += selectedValue;

            Debug.Log("RES_Check + selectedValue value + " + selectedValue);
            stack.PreviousAdd(selectedValue);
            total_bet += selectedValue;
            lastBet = stack.GetValue();
            text_for_bet.text = total_bet.ToString();

            BetPool.Instance.Add(this, selectedValue);
            BetPool.Instance.Rebet();

            SceneRoulette._Instance.clearButton.interactable = true;
            SceneRoulette._Instance.undoButton.interactable = true;
            SceneRoulette._Instance.rollButton.interactable = true;
            SceneRoulette._Instance.rebetButton.gameObject.SetActive(false);
            SceneRoulette.UpdateLocalPlayerText();
            betnum++;
            StartCoroutine(destroychip());
        }
    }

    IEnumerator destroychip()
    {
        yield return new WaitForSeconds(1);
        if (RouletteManager.destroychip)
        {
            stack.LastbetClear();
            RouletteManager.destroychip = false;
        }
    }

    public void RemoveBet(float value)
    {
        BalanceManager.ChangeBalance(value);
        ResultManager.totalBet -= value;
        stack.Remove(value);
        betnum = 0;
        lastBet = stack.GetValue();
        SceneRoulette.UpdateLocalPlayerText();
        stack.destroychip();
    }

    public float ResolveBet(int result)
    {
        Debug.Log("Bet space clear");
        total_bet = 0;
        int multiplier = numLenght / winningNumbers.Length;

        bool won = false;

        foreach (int num in winningNumbers)
        {
            if (num == result)
            {
                won = true;

                if (mesh && betType == BetType.Straight)
                    mesh.enabled = true;
                break;
            }
        }

        float winAmount = 0;

        if (won)
        {
            winAmount = stack.Win(multiplier);
        }
        else
        {
            stack.Clear();
        }

        return winAmount;
    }

    public void Rebet()
    {
        if (lastBet == 0)
            return;

        if (!LimitBetPlate.AllowLimit(lastBet))
        {
            lastBet = 0;
            return;
        }

        if (BetsEnabled && BalanceManager.Balance - lastBet >= 0)
        {
            BalanceManager.ChangeBalance(-lastBet);
            ResultManager.totalBet += lastBet;
            stack.SetValue(lastBet);
            lastBet = stack.GetValue();

            BetPool.Instance.Add(this, lastBet);

            SceneRoulette._Instance.clearButton.interactable = true;
            SceneRoulette._Instance.undoButton.interactable = true;
            SceneRoulette._Instance.rollButton.interactable = true;
            SceneRoulette._Instance.rebetButton.gameObject.SetActive(false);
            SceneRoulette.UpdateLocalPlayerText();

            //StartCoroutine(destroychip());
        }
        else
            lastBet = 0;
    }

    public void Clear()
    {
        float val = stack.GetValue();
        BalanceManager.ChangeBalance(val);
        ResultManager.totalBet -= val;
        lastBet = 0;
        betnum = 0;
        stack.Clear();
        SceneRoulette.UpdateLocalPlayerText();
    }

    public static void EnableBets(bool enable)
    {
        BetsEnabled = enable;
    }
}
