using System.Collections;
using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{
    public class MusicOnAndOffOffline : MonoBehaviour
    {
        public GameObject musicOnImage, musicOffImage;

        private void Start()
        {
            if (!PlayerPrefs.HasKey("isMusic"))
                PlayerPrefs.SetString("isMusic", "On");
            else
            {
                PlayerPrefs.GetString("isMusic");

                if (PlayerPrefs.GetString("isMusic") == "On")
                {
                    //musicOnBtn.SetActive(true);
                    musicOnImage.SetActive(true);
                   // musicOffBtn.SetActive(false);
                    musicOffImage.SetActive(false);
                    SoundManagerOffline.instance.musicAudioSource.Play();
                }
                else
                {
                    //musicOnBtn.SetActive(false);
                    musicOnImage.SetActive(false);
                    //musicOffBtn.SetActive(true);
                    musicOffImage.SetActive(true);
                    SoundManagerOffline.instance.musicAudioSource.Stop();
                }
            }
        }
        public void MusicOnBtn()
        {
            //musicOnBtn.SetActive(false);
            musicOnImage.SetActive(false);
            //musicOffBtn.SetActive(true);
            musicOffImage.SetActive(true);
            SoundManagerOffline.instance.musicAudioSource.Stop();
            PlayerPrefs.SetString("isMusic", "Off");
            Debug.Log("PlayerPrefs || key || Click_On  ==> " + PlayerPrefs.GetString("isMusic"));
        }
        public void MusicOffBtn()
        {
            //musicOnBtn.SetActive(true);
            musicOnImage.SetActive(true);
            //musicOffBtn.SetActive(false);
            musicOffImage.SetActive(false);
            SoundManagerOffline.instance.musicAudioSource.Play();
            PlayerPrefs.SetString("isMusic", "On");
            Debug.Log("PlayerPrefs || key || Click_Off  ==> " + PlayerPrefs.GetString("isMusic"));
        }
    }
}
