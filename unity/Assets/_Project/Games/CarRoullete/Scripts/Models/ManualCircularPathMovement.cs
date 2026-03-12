using UnityEngine;

public class ManualCircularPathMovement : MonoBehaviour
{
    public float speed = 5f; // Speed of the car's movement
    private float progress = 0f; // Progress along the path (0 to 1)

    public Vector3[] pathPoints; // Define points for the path
    public float stopTime = 8f; // Time after which the car should stop (in seconds)
    private bool shouldStop = false; // Flag to determine when to stop

    private float elapsedTime = 0f; // Timer to track time
    private float totalPathLength;

    public int stopAtIndex;

    /// <summary>
    /// Awake is called when the script instance is being loaded.
    /// </summary>
    void Awake()
    {
        totalPathLength = 0f;
        for (int i = 0; i < pathPoints.Length; i++)
        {
            int nextIndex = (i + 1) % pathPoints.Length;
            totalPathLength += Vector3.Distance(pathPoints[i], pathPoints[nextIndex]);
        }
    }
    
    void Update()
    {
        // Update the elapsed time
        elapsedTime += Time.deltaTime;

        // Check if 8 seconds have passed
        if (elapsedTime >= stopTime)
        {
            // If we've reached or passed the stop time, stop at the 4th waypoint
            shouldStop = true;
        }

        if (!shouldStop)
        {
            // Move the car along the path
            progress += speed * Time.deltaTime / totalPathLength;

            // Wrap progress to loop
            if (progress > 1f)
                progress -= 1f;

            // Calculate position along the path
            Vector3 position = GetPositionOnPath(progress);
            transform.localPosition = position; // Use localPosition for UI elements

            // Update rotation
            Vector3 nextPosition = GetPositionOnPath(progress + 0.01f); // Small offset for direction
            Vector3 direction = (nextPosition - position).normalized;

            if (direction != Vector3.zero)
            {
                // Calculate the angle based on the direction of movement
                float angle = Mathf.Atan2(direction.y, direction.x) * Mathf.Rad2Deg;

                // Adjust the angle based on the car's front orientation
                float frontOrientation = 367.941f; // Your current z rotation for the front
                float adjustedAngle = angle - frontOrientation + 180f; // Add 180 to flip the rotation

                // Get the target rotation
                Quaternion targetRotation = Quaternion.Euler(new Vector3(0, 0, adjustedAngle));

                // Smoothly rotate towards the target rotation
                float rotationSpeed = 5f; // Adjust the speed as needed
                transform.rotation = Quaternion.Slerp(transform.rotation, targetRotation, Time.deltaTime * rotationSpeed);
            }
        }
        else
        {
            // Stop the car at the 4th waypoint
            transform.localPosition = pathPoints[stopAtIndex - 1]; // Stop at the 4th waypoint
        }
    }

    Vector3 GetPositionOnPath(float t)
    {
        float pathDistance = t * totalPathLength;
        float currentDistance = 0f;

        for (int i = 0; i < pathPoints.Length; i++)
        {
            int nextIndex = (i + 1) % pathPoints.Length;
            float segmentLength = Vector3.Distance(pathPoints[i], pathPoints[nextIndex]);

            // Check if we're within this segment
            if (currentDistance + segmentLength >= pathDistance)
            {
                // Calculate the position along the segment
                float segmentT = (pathDistance - currentDistance) / segmentLength;
                return Vector3.Lerp(pathPoints[i], pathPoints[nextIndex], segmentT);
            }

            currentDistance += segmentLength;
        }

        return pathPoints[0]; // Fallback position (shouldn't reach here)
    }
}
