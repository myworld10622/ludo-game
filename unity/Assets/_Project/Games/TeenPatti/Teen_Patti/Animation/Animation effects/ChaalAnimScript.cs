using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;

public class ChaalAnimScript : MonoBehaviour
{
    public Transform startPosition;
    public Transform endPosition;
    public float duration = 2f;

    private float startTime;
    private Vector3 startScale;

    private bool startanim = false;

    IEnumerator waitforanim()
    {
        startPosition = this.transform;
        yield return new WaitForSeconds(1.5f);
        startTime = Time.time;
        startScale = transform.localScale;
        endPosition = (GameObject.Find("pngegg (1)").transform.GetChild(0).GetChild(1).GetChild(0).GetChild(1).transform);
        startanim = true;
        yield return new WaitForSeconds(1);
        Destroy(this.gameObject);
    }

    void Start()
    {
        StartCoroutine(waitforanim());
    }

    void Update()
    {
        if (startanim)
        {
            float percentageComplete = (Time.time - startTime) / duration;

            transform.position = Vector3.Lerp(startPosition.position, endPosition.position, percentageComplete);

            // Scale the object from 1 to 0
            transform.localScale = Vector3.Lerp(startScale, Vector3.zero, percentageComplete);

            if (percentageComplete >= 1.0f)
            {
                transform.localScale = startScale; // Reset scale
                transform.position = startPosition.position; // Reset position
                startTime = Time.time; // Restart the timer for the next movement
            }
        }
    }
}
