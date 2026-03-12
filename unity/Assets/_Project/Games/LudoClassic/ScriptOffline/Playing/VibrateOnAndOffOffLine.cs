using System.Collections;
using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{
    public class VibrateOnAndOffOffLine : MonoBehaviour
    {
        public GameObject vibrateOnImage, vibrateOffImage;
        private void Start()
        {
            if (!PlayerPrefs.HasKey("isVibrate"))
                PlayerPrefs.SetString("isVibrate", "On");
            else
            {
                PlayerPrefs.GetString("isVibrate");
                if (PlayerPrefs.GetString("isVibrate") == "On")
                {
                    //vibrateOnBtn.SetActive(true);
                    vibrateOnImage.SetActive(true);
                    //vibrateOffBtn.SetActive(false);
                    vibrateOffImage.SetActive(false);
                }
                else
                {
                    //vibrateOnBtn.SetActive(false);
                    vibrateOnImage.SetActive(false);
                    //vibrateOffBtn.SetActive(true);
                    vibrateOffImage.SetActive(true);
                }
            }
        }
        public void VibrateOnBtn()
        {
            //vibrateOnBtn.SetActive(false);
            vibrateOnImage.SetActive(false);
            //vibrateOffBtn.SetActive(true);
            vibrateOffImage.SetActive(true);
            PlayerPrefs.SetString("isVibrate", "Off");
        }
        public void VibrateOffBtn()
        {
            //vibrateOnBtn.SetActive(true);
            vibrateOnImage.SetActive(true);
           // vibrateOffBtn.SetActive(false);
            vibrateOffImage.SetActive(false);
            PlayerPrefs.SetString("isVibrate", "On");
        }
    }
}
