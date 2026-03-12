using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;

public class TextAnimToCoin : MonoBehaviour
{
    public Transform targetPosition;
    public GameObject Object;

    private void OnEnable()
    {
        Invoke("anim", 1f);
    }

    public void anim()
    {
        this.GetComponent<Animator>().enabled = false;
        Object.transform.DOMove(targetPosition.position, 0.5f).SetEase(Ease.Linear);
        Object.transform.DOScale(Vector3.zero, 0.5f).SetEase(Ease.Linear);
    }
}
