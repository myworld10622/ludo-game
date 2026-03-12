using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class VibrationController : MonoBehaviour
{
    private void OnEnable()
    {
        if (SystemInfo.supportsVibration)
        {
            //Handheld.Vibrate();
        }
        else
        {
            Debug.LogWarning("Vibration is not supported on this device.");
        }
    }

    IEnumerator Start()
    {
        yield return new WaitForSeconds(2.0f);
        this.GetComponent<VibrationController>().enabled = false;
    }

    // Update is called once per frame
    void Update()
    {
        
    }
}
