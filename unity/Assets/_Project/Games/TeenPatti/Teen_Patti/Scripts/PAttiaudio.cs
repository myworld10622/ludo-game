using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class PAttiaudio : MonoBehaviour
{
    public AudioSource source;

    private void OnEnable()
    {
        source = GetComponent<AudioSource>();
        if(PlayerPrefs.GetString("sound") == "on")
        {
            source.enabled = true;
        }
        else
        {
            source.enabled = false;
        }    
    }
}
