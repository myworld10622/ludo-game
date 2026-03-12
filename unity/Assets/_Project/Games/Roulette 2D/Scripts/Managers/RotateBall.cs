using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;

public class RotateBall : MonoBehaviour
{
    public GameObject targetObject;
    public Roulette2DManager manager;
    public RotateRoulette wheel;

    void OnEnable()
    {
        StartCoroutine(RotateObject());
    }

    IEnumerator RotateObject()
    {
        transform.rotation = Quaternion.Euler(-61.33f, 0f, 0f);
        float startTime = Time.time;
        CommonUtil.CheckLog("start spin");
        // Animate the rotation smoothly
        while (Time.time - startTime < 3)
        {
            CommonUtil.CheckLog("start spin 2");

            // Calculate how much time has passed and how far we need to interpolate
            float timeElapsed = (Time.time - startTime) / 3;

            // Smoothly interpolate between 0 and 1440 on the Z axis
            float currentZRotation = Mathf.Lerp(0f, 1080f, timeElapsed); // Using Lerp instead of LerpAngle
            CommonUtil.CheckLog("start spin 3 " + currentZRotation);

            // Set the new rotation (keeping X at -81 and Y at 0)
            targetObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, currentZRotation);

            // Yield return null to wait for the next frame
            yield return null;
        }

        // Make sure the rotation is set to the final value after the animation ends
        targetObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, 0f);

        float finalRotationTarget = wheel.positions[
            int.Parse(manager.RouletteData.game_data[0].winning)
        ]; // Replace this with your dynamic target value
        float finalStartTime = Time.time;
        while (Time.time - finalStartTime < 2) // 2 seconds to rotate to the target Z position
        {
            float finalTimeElapsed = (Time.time - finalStartTime) / 2;
            float finalZRotation = Mathf.Lerp(0f, finalRotationTarget, finalTimeElapsed); // Interpolate to the target

            targetObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, finalZRotation);
            yield return null;
        }

        // Ensure the rotation ends at the target value (e.g., 100 degrees)
        targetObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, finalRotationTarget);

        yield return new WaitForSeconds(1f);

        wheel.ScaleAndMoveBack();
    }
}
