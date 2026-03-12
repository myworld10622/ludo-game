using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class MainCardAnim : MonoBehaviour
{
    public Transform targetPosition; // The target position to move towards
    public float moveSpeed = 5f; // Speed of movement
    public float scaleSpeed = 1f; // Speed of scaling

    private Vector3 initialScale; // Initial scale of the object

    private void Start()
    {
        initialScale = transform.localScale;
    }

    private void Update()
    {
        // Calculate the direction to move towards
        Vector3 direction = targetPosition.position - transform.position;

        // Normalize the direction vector to maintain constant speed
        direction.Normalize();

        // Move the object towards the target position
        transform.position += direction * moveSpeed * Time.deltaTime;

        // Calculate the scale to interpolate towards
        Vector3 targetScale = new Vector3(1.5f, 1.6f, 1.5f);

        // Interpolate the scale towards the target scale
        transform.localScale = Vector3.Lerp(transform.localScale, targetScale, scaleSpeed * Time.deltaTime);

        // Check if the object has reached the target position and scale
        if (Vector3.Distance(transform.position, targetPosition.position) < 0.1f)
        {
            // Optional: Do something when the object reaches the target position and scale
            Debug.Log("Object has reached the target position and scale!");

            // You might want to disable the script or perform other actions here
            enabled = false;
        }
    }
}
