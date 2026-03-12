using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class SoundManagerOffline : MonoBehaviour
    {
        public Toggle soundToggle;
        public Toggle musicToggle;
        public AudioSource musicAudioSource;
        public AudioSource soundAudioSource;
        public AudioSource timeAudioSource;
        public AudioSource tokenKillAudioSource;

        public AudioClip tokenMoveAudio;
        public AudioClip trunAudio;
        public AudioClip timerAudio;
        public AudioClip killAudio;
        public AudioClip winAudio;
        public AudioClip loseAudio;
        public AudioClip tokenEnterHomeAudio;
        public AudioClip tokenEnterSafeZoneAudio;
        public AudioClip diceAnimationAudio;
        public static SoundManagerOffline instance;

        public AudioSource emojiSoundAudio;
        public List<AudioClip> emojiSoundClip;
        internal int emojiSound = 1;

        private void Awake()
        {
            instance = this;

            musicToggle.isOn = Configuration.GetMusic() == "on";
            soundToggle.isOn = Configuration.GetSound() == "on";

            //PlayerPrefs.GetString("isMusic");

            musicToggle.onValueChanged.AddListener(OnMusicToggleChanged);
            soundToggle.onValueChanged.AddListener(OnSoundToggleChanged);
        }

        private void OnMusicToggleChanged(bool isOn)
        {
            Debug.Log($"Music: {isOn}");

            PlayerPrefs.SetString("music", isOn ? "on" : "");
            PlayerPrefs.Save();

            if (isOn)
            {
                AudioManager._instance.PlayBackgroundAudio();
            }
            else
            {
                AudioManager._instance.StopBackgroundAudio();
            }
        }

        private void OnSoundToggleChanged(bool isOn)
        {
            Debug.Log($"Sound: {isOn}");

            PlayerPrefs.SetString("sound", isOn ? "on" : "");
            PlayerPrefs.Save();
            if (!isOn)
                AudioManager._instance.StopEffect();
        }

        public void MusicPlay()
        {
            if (PlayerPrefs.GetString("isMusic") == "On")
            {
                musicAudioSource.Play();
            }
        }

        public void SoundPlay(AudioClip audioClip)
        {
            if (soundToggle.isOn)
            {
                soundAudioSource.clip = audioClip;
                soundAudioSource.Play();
            }
        }

        public void Vibration()
        {
            if (PlayerPrefs.GetString("isVibrate") == "On")
            {
#if UNITY_ANDROID
                Handheld.Vibrate();
#endif
            }
        }

        public void TokenKill(AudioClip audioClip)
        {
            if (soundToggle.isOn)
            {
                tokenKillAudioSource.clip = audioClip;
                tokenKillAudioSource.Play();
            }
        }

        public void TimeSound(AudioClip timeAudioClip)
        {
            timeAudioSource.clip = timeAudioClip;
            timeAudioSource.loop = true;
            timeAudioSource.Play();
        }

        public void TimeSoundStop(AudioClip timeStopAudioClip)
        {
            timeAudioSource.Stop();
            timeAudioSource.clip = timeStopAudioClip;
            timeAudioSource.loop = false;
        }

        public void EmojiSoundPlay(List<AudioClip> emojiSoundList, int number)
        {
            for (int i = 0; i < emojiSoundList.Count; i++)
            {
                emojiSoundAudio.clip = emojiSoundClip[number];
            }
            if (soundToggle.isOn)
            {
                if (emojiSound == 1)
                {
                    Debug.Log("on sound");
                    emojiSoundAudio.Play();
                    emojiSoundAudio.playOnAwake = false;
                    emojiSoundAudio.loop = false;
                }
                else
                {
                    Debug.Log("Off sound");
                    emojiSoundAudio.Stop();
                    emojiSoundAudio.playOnAwake = false;
                }
            }
        }
    }
}
