using UnityEngine;

public class SpinnerRotator : MonoBehaviour
{
    public float speed = 300f;

    private void Update()
    {
        transform.Rotate(0f, 0f, -speed * Time.unscaledDeltaTime);
    }
}
