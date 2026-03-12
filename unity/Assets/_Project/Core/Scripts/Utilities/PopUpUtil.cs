using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public static class PopUpUtil
{
    public static void ButtonClick(GameObject panel)
    {
        Image[] allTransforms = Resources.FindObjectsOfTypeAll<Image>();
        GameObject black_in = null;
        foreach (Image t in allTransforms)
        {
            if (t.name == "Blackin") // Change "TargetName" to your GameObject's name
            {
                black_in = t.gameObject;
                Debug.Log($"Found inactive GameObject True: {t.name}");
            }
        }
        black_in.SetActive(true);
        var originalScale = panel.transform.localScale;
        panel.transform.localScale = Vector3.zero; // Set initial scale to 0

        panel.SetActive(true); // Activate the panel
        panel.transform.localScale = Vector3.zero; // Start from scale 0
        panel.transform.DOScale(originalScale, 0.3f).SetEase(Ease.OutBack); // Animate scale from 0 to 1 with easing
    }

    public static void ButtonCancel(GameObject panel)
    {
        Image[] allTransforms = Resources.FindObjectsOfTypeAll<Image>();
        GameObject black_in = null;
        foreach (Image t in allTransforms)
        {
            if (t.name == "Blackin") // Change "TargetName" to your GameObject's name
            {
                black_in = t.gameObject;
                Debug.Log($"Found inactive object False: {t.name}");
            }
        }
        black_in.SetActive(false);
        Vector3 originalScale = new Vector3();
        originalScale = panel.transform.localScale;

        panel.transform.localScale = originalScale; // Start from actual scale

        panel
            .transform.DOScale(Vector3.zero, 0.2f)
            .SetEase(Ease.InBack)
            .OnComplete(() =>
            {
                panel.SetActive(false);
                panel.transform.localScale = originalScale;
            });
    }
}
