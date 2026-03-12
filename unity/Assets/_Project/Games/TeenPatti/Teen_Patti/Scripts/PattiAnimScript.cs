using AndroApps;
using System;
using System.Collections;
using System.Collections.Generic;
using System.Reflection;
using UnityEngine;

public class PattiAnimScript : MonoBehaviour
{
    public Transform startPosition;
    public Transform[] endPositions;
    public float moveDuration = 1f;
    public float delayBetweenCards = 1f;
    public List<Transform> cards;
    public string scenename;
    public TeenPattiSocketManager tpSocket;
    public AudioClip pattiflip;
    public AudioSource source;


    private void Start()
    {
        if (scenename != "AndarBahar")
            StartCoroutine(MoveAllCards());
    }

    private void Update()
    {
        if (Input.GetKeyUp(KeyCode.P))
            StartCoroutine(MoveAllCards());
    }

    public IEnumerator MoveAllCards()
    {
        for (int i = 0; i < cards.Count; i++)
        {
            //NewAudioManager.instance.gamesource.clip = NewAudioManager.instance.Pattisoundclip;
            //NewAudioManager.instance.gamesource.Play();
            Debug.Log("Called");

            yield return new WaitForSeconds(delayBetweenCards);

            // Set the initial positions, rotations, and scales to be the same as the start position
            Transform card = cards[i];
            card.position = startPosition.position;
            card.rotation = startPosition.rotation;
            card.localScale = startPosition.localScale;
            cards[i].gameObject.SetActive(true);

            Debug.Log("RES_Check + issound");
            if (source != null)
            {
                source.clip = pattiflip;
                source.Play();
            }
            StartCoroutine(MoveCard(card, i));
        }

        if (scenename == "teenpatti")
        {
            tpSocket.showblind = true;
            for (int j = 0; j < tpSocket.profiles.Count; j++)
            {
                if (tpSocket.profiles[j].GetComponent<ChaalSlider>().id != Configuration.GetId())
                {
                    tpSocket.profiles[j].transform.GetChild(0).GetChild(3).gameObject.SetActive(true);
                }
            }
        }
    }

    private IEnumerator MoveCard(Transform card, int index)
    {
        float elapsedTime = 0f;
        card.gameObject.SetActive(true);
        while (elapsedTime < moveDuration)
        {
            // Calculate the interpolation factor
            float t = elapsedTime / moveDuration;

            // Interpolate position
            card.position = Vector3.Lerp(startPosition.position, GetEndPosition(index), t);

            // Interpolate rotation
            card.rotation = Quaternion.Slerp(startPosition.rotation, GetEndRotation(index), t);

            // Interpolate scale
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
        if (scenename == "AndarBahar")
            return new Vector3(1, 1, 1);
        else
            return endPositions[index].localScale;
    }
}
