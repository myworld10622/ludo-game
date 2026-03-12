using System.Collections;
using System.Collections.Generic;
using UnityEngine;
namespace LudoClassicOffline
{

    public class SoundOnAndOffOffline : MonoBehaviour
    {
        public GameObject soundOnImage, soundOffImage;
        private void Start()
        {
            if (!PlayerPrefs.HasKey("isSound"))
                PlayerPrefs.SetString("isSound", "On");
            else
            {
                PlayerPrefs.GetString("isSound");
                if (PlayerPrefs.GetString("isSound") == "On")
                {
                    //soundOnBtn.SetActive(true);
                    soundOnImage.SetActive(true);
                    //soundOffBtn.SetActive(false);
                    soundOffImage.SetActive(false);
                    SoundManagerOffline.instance.soundAudioSource.Play();
                }
                else
                {
                    //soundOnBtn.SetActive(false);
                    soundOnImage.SetActive(false);
                    //soundOffBtn.SetActive(true);
                    soundOffImage.SetActive(true);
                    SoundManagerOffline.instance.soundAudioSource.Stop();
                }
            }
        }
        public void SoundOnBtn()
        {
            Debug.Log("Sound Off");
            //soundOnBtn.SetActive(false);
            soundOnImage.SetActive(false);
            //soundOffBtn.SetActive(true);
            soundOffImage.SetActive(true);
            SoundManagerOffline.instance.soundAudioSource.Stop();
            PlayerPrefs.SetString("isSound", "Off");
        }
        public void SoundOffBtn()
        {
            Debug.Log("Sound on");
            //soundOnBtn.SetActive(true);
            soundOnImage.SetActive(true);
            //soundOffBtn.SetActive(false);
            soundOffImage.SetActive(false);
            SoundManagerOffline.instance.soundAudioSource.Play();
            PlayerPrefs.SetString("isSound", "On");
        }
    }
}
