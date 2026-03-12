using UnityEngine;

public class DiceController : MonoBehaviour
{
    public Vector3 startpos;
    public Transform myparent;

    void OnEnable()
    {
        SetOriginalPosition();
    }

    // Assign these rotations based on your dice's model alignment
    private Quaternion[] diceRotations = new Quaternion[]
    {
        Quaternion.Euler(-8, 0, 90), // Side 1
        Quaternion.Euler(-8, 180, 90), // Side 2
        Quaternion.Euler(0, 90, 90), // Side 3
        Quaternion.Euler(90, 180, 0), // Side 4
        Quaternion.Euler(90, -90, 0), // Side 5
        Quaternion.Euler(
            90,
            90,
            0
        ) // Side 6
        ,
    };

    // Call this function with a value between 1 and 6
    public void SetDiceSide(int side)
    {
        if (side < 1 || side > 6)
        {
            Debug.LogError("Invalid dice side! Please enter a number between 1 and 6.");
            return;
        }
        // Apply the corresponding rotation
        transform.rotation = diceRotations[side - 1];
    }

    public void SetOriginalPosition()
    {
        this.transform.parent = myparent;
        transform.localPosition = startpos;
    }
}
