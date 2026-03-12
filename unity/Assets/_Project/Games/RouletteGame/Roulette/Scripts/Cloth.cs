using UnityEngine;

public class Cloth : MonoBehaviour
{
    public static GameObject chipStackPref;

    private void Awake()
    {
        chipStackPref = Instantiate(Resources.Load<GameObject>("chipstack"));
    }

    public static ChipStack InstanceStack()
    {
        GameObject ob = Instantiate(chipStackPref);

        //RouletteManager.coinsinstantiated.Add(ob);

        return ob.GetComponent<ChipStack>();
    }
}
