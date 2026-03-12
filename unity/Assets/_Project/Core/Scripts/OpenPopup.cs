using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;

public class OpenPopup : MonoBehaviour
{
    //public Vector3 Scale;
    //public float time = .5f;
    //changes
    public GameObject Penal;

    public static OpenPopup instance;

    private void OnEnable()
    {
        instance = this;
        transform.localScale = Vector3.zero;
        StartAnimation();
    }

    public void StartAnimation()
    {
        DOVirtual.DelayedCall(
            0.2f,
            () =>
            {
                transform.DOScale(Vector3.one, 0.2f).SetEase(Ease.OutBack);
            }
        );
    }

    public void OnDisable()
    {
        Debug.Log("Root:" + transform.root);
        transform.localScale = Vector3.one;
        transform
            .DOScale(Vector3.zero, 0.2f)
            .SetEase(Ease.InBack)
            .OnComplete(() =>
            {
                if (Penal)
                {
                    Penal.SetActive(false);
                }
                else
                {
                    transform.root.gameObject.SetActive(false);
                }
            });

        transform.localScale = Vector3.one;
    }

    public void ClosePanel(GameObject panelToClose)
    {
        // Make sure the panel is active before closing
        if (panelToClose.activeSelf)
        {
            // Scale down the panel and then disable it after the animation
            panelToClose
                .transform.DOScale(Vector3.zero, 0.2f)
                .SetEase(Ease.InBack)
                .OnComplete(() =>
                {
                    panelToClose.SetActive(false);
                    DOVirtual.DelayedCall(
                        0.2f,
                        () =>
                        {
                            Debug.Log("RES_Check + back " + panelToClose.gameObject.name);
                            if (panelToClose.gameObject.name != "backpanel")
                            {
                                panelToClose.transform.localScale = Vector3.one;
                            }
                        }
                    );
                });
        }
    }

    public void OnCloseObject(GameObject _Penal)
    {
        Debug.Log("Root:" + transform.root);
        _Penal.transform.localScale = Vector3.one;
        _Penal
            .transform.DOScale(Vector3.zero, 0.2f)
            .SetEase(Ease.InBack)
            .OnComplete(() =>
            {
                _Penal.SetActive(false);
                _Penal.transform.localScale = Vector3.one;
            });
    }
}
