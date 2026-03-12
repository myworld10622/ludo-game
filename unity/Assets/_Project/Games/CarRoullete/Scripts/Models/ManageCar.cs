using DG.Tweening;
using UnityEngine;

public class ManageCar : MonoBehaviour
{
    public DOTweenPath carPath;
    private Tween pathTween;
    public int stopCar, loopCount;

    void Start()
    {
        carPath = GetComponent<DOTweenPath>();
        pathTween = GetComponent<DOTweenPath>().GetTween()
            .OnStepComplete(OnLoopComplete)
            .OnWaypointChange(OnWaypointReached);
        //carPath.duration = 5f;
        //OnWaypointReached(stopCar);
    }

    private void OnLoopComplete()
    {
        loopCount++;
        //Debug.Log($"Completed Loop: {loopCount}");
    }

    private void OnWaypointReached(int waypointIndex)
    {
        //        Debug.Log($"Reached Waypoint: {waypointIndex}");

        if (waypointIndex == stopCar && loopCount == 3)
        {
            pathTween.Pause();
            //Debug.Log($"Car stopped at waypoint {waypointIndex + 1} after {loopCount} loops."); // +1 to show the actual waypoint number

            //DOVirtual.DelayedCall(3f, RestartPath);
        }
    }

    public void RestartPath()
    {
        // Reset the loop count and restart the tween
        loopCount = 0;
        pathTween.Restart(); // Restart the tween from the beginning
        Debug.Log("Car path restarted.");
    }

}
