using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;

public class Glow : MonoBehaviour
{
    [SerializeField]
    private Vector2 scaleMin = new Vector2(0.6f, 0.6f); // Minimum scale for x and y

    [SerializeField]
    private Vector2 scaleMax = new Vector2(0.75f, 0.75f); // Maximum scale for x and y

    [SerializeField]
    private float duration = 0.5f;

    void OnEnable()
    {
        StartGlow();
    }

    private void StartGlow()
    {
        // Scale up and down repeatedly for x and y
        transform
            .DOScale(new Vector3(scaleMax.x, scaleMax.y, transform.localScale.z), duration) // Scale up to maximum
            .SetEase(Ease.InOutSine) // Smooth ease
            .OnComplete(() =>
            {
                transform
                    .DOScale(new Vector3(scaleMin.x, scaleMin.y, transform.localScale.z), duration) // Scale down to minimum
                    .SetEase(Ease.InOutSine)
                    .OnComplete(StartGlow); // Restart the glow effect
            });
    }
}
