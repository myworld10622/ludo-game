using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class ChipManager : MonoBehaviour
{

    public static Chip selected = null;
    private static ChipManager Instance;

    public GameObject[] Chips;
    public CanvasGroup cg;

    public static bool IsBetSuccess = false;


    private void Awake()
    {
        Instance = this;
        cg = gameObject.AddComponent<CanvasGroup>();
    }

    public static GameObject InstantiateChip(int index)
    {
        var gameob = Instantiate(Instance.Chips[index]);
        Debug.Log("NAME OF SPAWN CHIIP:" + gameob.name + "::INDEX OF::" + index);
        return gameob;
    }

    public static float GetSelectedValue()
    {
        if (selected != null)
            return selected.value;

        return 0;
    }

    public static void EnableChips(bool enable)
    {
        Instance.cg.interactable = enable;
    }
}