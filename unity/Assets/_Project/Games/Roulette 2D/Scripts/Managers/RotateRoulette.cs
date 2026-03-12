using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using Unity.Burst.Intrinsics;
using Unity.Mathematics;
using UnityEngine;

public class RotateRoulette : MonoBehaviour
{
    public GameObject targetObject; // Assign the GameObject you want to rotate
    public RotateBall ball;
    public List<float> positions;

    void OnEnable()
    {
        // First, scale and move the object simultaneously
        ScaleAndMove(() =>
        {
            // Once the scaling and moving are completed, start rotating
            StartCoroutine(RotateObject());
        });
    }

    void ScaleAndMove(TweenCallback onComplete)
    {
        // Create a sequence for scaling and moving
        Sequence scaleMoveSequence = DOTween.Sequence();

        // Add scaling to the sequence
        scaleMoveSequence.Join(
            transform.DOScale(new Vector3(11, 11, 11), 1f).SetEase(Ease.OutSine)
        );

        // Add movement to the sequence
        scaleMoveSequence.Join(transform.DOLocalMoveY(-50f, 1f).SetEase(Ease.OutSine));

        // Add a callback when the sequence completes
        scaleMoveSequence.OnComplete(onComplete);
    }

    IEnumerator RotateObject()
    {
        float startTime = Time.time;
        CommonUtil.CheckLog("start spin");
        ball.gameObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, 0f);
        ball.enabled = true;
        // Animate the rotation smoothly
        while (Time.time - startTime < 5)
        {
            CommonUtil.CheckLog("start spin 2");

            // Calculate how much time has passed and how far we need to interpolate
            float timeElapsed = (Time.time - startTime) / 5;

            // Smoothly interpolate between 0 and 1440 on the Z axis
            float currentZRotation = Mathf.Lerp(0f, -1440f, timeElapsed); // Using Lerp instead of LerpAngle
            CommonUtil.CheckLog("start spin 3 " + currentZRotation);

            // Set the new rotation (keeping X at -81 and Y at 0)
            targetObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, currentZRotation);

            // Yield return null to wait for the next frame
            yield return null;
        }

        // Make sure the rotation is set to the final value after the animation ends
        targetObject.transform.rotation = Quaternion.Euler(-61.33f, 0f, -1440f);
    }

    public void ScaleAndMoveBack()
    {
        // Create a sequence for scaling and moving
        Sequence scaleMoveSequence = DOTween.Sequence();

        // Add scaling to the sequence
        scaleMoveSequence.Join(transform.DOScale(new Vector3(5, 5, 5), 1f).SetEase(Ease.OutSine));

        // Add movement to the sequence
        scaleMoveSequence.Join(transform.DOLocalMoveY(160f, 1f).SetEase(Ease.OutSine));

        ball.enabled = false;
        this.GetComponent<RotateRoulette>().enabled = false;
    }
}
