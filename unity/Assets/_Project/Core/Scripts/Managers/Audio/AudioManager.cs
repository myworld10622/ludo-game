using System.Collections;
using System.Collections.Generic;
using System.IO;
using UnityEngine;

public class AudioManager : MonoBehaviour
{
    public static AudioManager _instance;

    // AudioSource references for Background Music and a pool for Sound Effects
    public AudioSource bgAudioSource;
    private List<AudioSource> effectAudioSources = new List<AudioSource>();
    public string button_click_source = "button-click.wav";
    public string plane_take_off_source = "plane-takeoff.wav";
    public string plane_crash_source = "plane-crash.wav";
    public string inputfield_click_source = "input-click.mp3";
    public string win_source = "win.mp3";
    public string lose_source = "lost.mp3";
    public string place_bet_source = "place-bet.mp3";
    public string stop_bet_source = "stop-bet.mp3";
    public string highlight_win_source = "highlight-win.wav";
    public string ticking_source = "ticking.wav";
    public string card_flip_source = "card-flip.mp3";
    public string coin_drop_source = "coin-drop-sound.mp3";
    public string coin_multiple_source = "coin-distribute.mp3";
    public string Dragon_Highlight_sourc = "dragon-shout.mp3";
    public string Tiger_Highlight_source = "tiger-attack.mp3";
    public string Coin_Flip_source = "coin-flip.mp3";
    public string Wheel_Round_source = "spin-wheel.mp3";
    public string Dice_Roll = "diceplay.mp3";
    public string Reel_Spin_slot = "spin-sound-slot.wav";
    public string Car_Racing_source = "racing-car-sound-effect.mp3";

    // Path to the Audio folder in StreamingAssets
    public string audioFolderPath = "Audio";
    private int maxAudioSources = 10; // Maximum simultaneous sound effects

    public List<AudioSource> m_allSoundPlayAudioSource = new List<AudioSource>();

    void Awake()
    {
        // Ensure only one instance of AudioManager persists across scenes
        if (_instance == null)
        {
            _instance = this;
            DontDestroyOnLoad(gameObject); // Persist across scene loads
        }
        else
        {
            Destroy(gameObject); // Destroy any duplicate AudioManager instances
        }
    }

    void OnEnable()
    {
        if (!PlayerPrefs.HasKey("music"))
        {
            PlayerPrefs.SetString("music", "on");
            PlayerPrefs.Save();
        }

        if (!PlayerPrefs.HasKey("sound"))
        {
            PlayerPrefs.SetString("sound", "on");
            PlayerPrefs.Save();
        }
        //StopBackgroundAudio();
    }

    void Start()
    {
        PlayBackgroundAudio();
    }

    public void PlayBackgroundAudio()
    {
        if (Configuration.GetMusic() == "on")
        {
            /*     string bgAudioPath = Path.Combine(Application.streamingAssetsPath, audioFolderPath, "background.wav");

    #if UNITY_WEBGL
                // For WebGL, use a relative path that can be served from the server
                bgAudioPath = "Audio/background.wav";
    #endif */
            // Load and play background music (looping)
            StartCoroutine(
                AudioUtil.LoadAndPlayAudio(
                    $"{audioFolderPath}/background.mp3",
                    bgAudioSource,
                    true,
                    true
                )
            ); // Loop background music
        }
    }

    // Method to stop background music
    public void StopBackgroundAudio()
    {
        //  AudioUtil.StopAllAudio();
        AudioUtil.StopAudio(bgAudioSource);
    }

    public void StopAudio()
    {
        AudioManager._instance.StopEffect();
        //AudioUtil.StopAllAudio();
    }

    // Method to play a specific sound effect
    public void PlayEffect(string effectName)
    {
        //        Debug.Log("PlayEffect" + Configuration.GetSound());
        if (Configuration.GetSound() == "on")
        {
            //     CommonUtil.CheckLog($"+ effect{effectName}");
            AudioSource availableSource = GetAvailableAudioSource();
            if (availableSource != null)
            {
                StartCoroutine(
                    AudioUtil.LoadAndPlayAudio(
                        $"{audioFolderPath}/{effectName}",
                        availableSource,
                        false,
                        true
                    )
                );

                m_allSoundPlayAudioSource.Add(availableSource);
            }
        }
        else
        {
            /// CommonUtil.CheckLog("Sound is off");
        }
    }

    public AudioSource planetakeof;
    public void StopEffect()
    {
        m_allSoundPlayAudioSource.ForEach(x => x.Stop());
        m_allSoundPlayAudioSource.Clear();
        // if (planetakeof != null)
        // {
        //     planetakeof.Stop();
        // }
        // else
        // {
        //     Debug.Log("Its Null");
        // }
    }

    private AudioSource GetAvailableAudioSource()
    {
        foreach (var source in effectAudioSources)
        {
            if (!source.isPlaying)
            {
                return source;
            }
        }

        // If all sources are busy, add a new one
        AudioSource newSource = gameObject.AddComponent<AudioSource>();
        effectAudioSources.Add(newSource);
        return newSource;
    }


    // Method to set background music volume
    public void SetBackgroundVolume(float volume)
    {
        AudioUtil.SetVolume(bgAudioSource, volume);
    }

    // Method to set sound effects volume
    public void SetEffectVolume(float volume)
    {
        foreach (var source in effectAudioSources)
        {
            source.volume = volume;
        }
    }

    public void ButtonClick()
    {
        CommonUtil.CheckLog($"Button::{button_click_source}");
        PlayEffect(button_click_source);
    }

    public void PlaneCrash()
    {
        CommonUtil.CheckLog($"Button::{button_click_source}");
        PlayEffect(plane_crash_source);
    }

    public void PlaneTakeOff()
    {
        CommonUtil.CheckLog($"Button::{button_click_source}");
        PlayEffect(plane_take_off_source);
    }

    public void ClickInputField()
    {
        PlayEffect(inputfield_click_source);
    }

    public void PlayWinSound()
    {
        PlayEffect(win_source);
    }

    public void PlayLoseSound()
    {
        PlayEffect(lose_source);
    }

    public void PlayPlaceBetSound()
    {
        PlayEffect(place_bet_source);
    }

    public void PlayStopBetSound()
    {
        PlayEffect(stop_bet_source);
    }

    public void PlayHighlightWinSound()
    {
        PlayEffect(highlight_win_source);
    }

    public void PlayTickingSound()
    {
        PlayEffect(ticking_source);
    }

    public void PlayCardFlipSound()
    {
        PlayEffect(card_flip_source);
    }

    public void PlayCoinDrop()
    {
        PlayEffect(coin_drop_source);
    }

    public void PlayMultipleCoinDrop()
    {
        PlayEffect(coin_multiple_source);
    }

    public void PlayDragonHightligtWin()
    {
        PlayEffect(Dragon_Highlight_sourc);
    }

    public void PlayTigerHighlightWin()
    {
        PlayEffect(Tiger_Highlight_source);
    }

    public void PlayCoinFlipSound()
    {
        PlayEffect(Coin_Flip_source);
    }

    public void PlayWheelSound()
    {
        PlayEffect(Wheel_Round_source);
    }

    public void PlayDiceSound()
    {
        Debug.LogError("Play Dice Sound");
        PlayEffect(Dice_Roll);
    }

    public void PlaySlotReelSpin()
    {
        if (Configuration.GetMusic() == "on")
        {
            PlayEffect(Reel_Spin_slot);
        }
    }
    public void PlayCarRacingSound()
    {
        PlayEffect(Car_Racing_source);
    }
}
