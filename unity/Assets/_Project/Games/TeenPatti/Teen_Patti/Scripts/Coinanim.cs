using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using TMPro;
using AndroApps;

public class Coinanim : MonoBehaviour
{
    public Transform startPosition;
    public Transform[] endPositions;
    public float moveDuration = 0.1f;
    public float delayBetweenCards = 0.5f;
    public Transform[] cards;


    private int totalFrames = 10; // Total frames to complete the scaling
    private int currentFrame = 0;
    public GameObject parent;
    public bool coin, start;
    public string amount;
    public TextMeshProUGUI text;

    /// <summary>
    /// Awake is called when the script instance is being loaded.
    /// </summary>
    void Awake()
    {
        GameObject.Find("pngegg (1)").GetComponent<GetCoin>().coinstodelete.Add(this.gameObject);
    }


    IEnumerator destroycoin()
    {
        yield return new WaitForSeconds(1);
        Destroy(this.gameObject);
    }

    void Update()
    {
        if (coin)
        {
            GameObject obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;

            if (currentFrame < totalFrames)
            {
                Transform card = cards[0];
                startPosition = transform.parent.parent;
                card.position = startPosition.position;
                card.rotation = startPosition.rotation;

                float scaleValue = Mathf.Lerp(0f, 1f, (float)currentFrame / (totalFrames - 1));

                parent.transform.localScale = new Vector3(scaleValue, parent.transform.localScale.y, parent.transform.localScale.z);

                currentFrame++;
            }
            else
            {
                if (!start)
                {
                    StartCoroutine(MoveAllCards());
                    endPositions[0] = (GameObject.Find("pngegg (1)").GetComponent<GetCoin>().coin().transform);
                    start = true;
                }
            }
        }
    }
    private void Start()
    {
        if (!coin)
        {
            this.GetComponent<SpriteRenderer>().enabled = false;
            startPosition = transform.parent.parent;
            StartCoroutine(MoveAllCards());
            endPositions[0] = (GameObject.Find("pngegg (1)").transform.GetChild(0).GetChild(1).GetChild(0).transform);
        }
    }

    private IEnumerator MoveAllCards()
    {
        Debug.Log("Called");
        for (int i = 0; i < cards.Length; i++)
        {
            yield return new WaitForSeconds(delayBetweenCards);

            // Set the initial positions, rotations, and scales to be the same as the start position
            Transform card = cards[i];
            if (!coin)
            {
                card.position = startPosition.position;
                card.rotation = startPosition.rotation;
                card.localScale = startPosition.localScale;
            }

            // Call the MoveCard function to start the movement
            StartCoroutine(MoveCard(card, i));
        }
    }

    private IEnumerator MoveCard(Transform card, int index)
    {
        if (!coin)
        {
            this.GetComponent<SpriteRenderer>().enabled = true;
            float elapsedTime = 0f;
            card.gameObject.SetActive(true);
            while (elapsedTime < 1f)
            {
                // Calculate the interpolation factor
                float t = elapsedTime / 1f;

                // Interpolate position
                card.position = Vector3.Lerp(startPosition.position, GetEndPosition(index), t);

                // Interpolate rotation
                card.rotation = Quaternion.Slerp(startPosition.rotation, GetEndRotation(index), t);

                // Interpolate scale
                if (!coin)
                    card.localScale = Vector3.Lerp(startPosition.localScale, GetEndScale(index), t);

                // Update the elapsed time
                elapsedTime += Time.deltaTime;

                yield return null;
            }

            // Ensure the final positions, rotations, and scales are exactly the same as the end positions
            card.position = GetEndPosition(index);
            card.rotation = GetEndRotation(index);
            card.localScale = GetEndScale(index);
        }
        else
        {
            float elapsedTime = 0f;
            card.gameObject.SetActive(true);
            while (elapsedTime < 0.4f)
            {
                // Calculate the interpolation factor
                float t = elapsedTime / 0.4f;

                // Interpolate position
                card.position = Vector3.Lerp(startPosition.position, GetEndPosition(index), t);

                // Interpolate rotation
                card.rotation = Quaternion.Slerp(startPosition.rotation, GetEndRotation(index), t);

                // Interpolate scale
                if (!coin)
                    card.localScale = Vector3.Lerp(startPosition.localScale, GetEndScale(index), t);

                // Update the elapsed time
                elapsedTime += Time.deltaTime;

                yield return null;
            }

            // Ensure the final positions, rotations, and scales are exactly the same as the end positions
            card.position = GetEndPosition(index);
            card.rotation = GetEndRotation(index);
        }
    }

    private Vector3 GetEndPosition(int index)
    {
        // Return the end position for the corresponding index
        return endPositions[index].position;
    }

    private Quaternion GetEndRotation(int index)
    {
        // Return the end rotation for the corresponding index
        return endPositions[index].rotation;
    }

    private Vector3 GetEndScale(int index)
    {
        // Return the end scale for the corresponding index
        return endPositions[index].localScale;
    }
}
