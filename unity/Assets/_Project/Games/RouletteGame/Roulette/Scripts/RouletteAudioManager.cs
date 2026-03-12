using System.Collections;
using System.Collections.Generic;
using AndroApps;
using UnityEngine;
using UnityEngine.UI;

public class RouletteAudioManager : MonoBehaviour
{
    public static RouletteAudioManager _Instance;

    public int maxAudioSourcePool = 15;
    public AudioEvent[] audioEvent;
    private List<AudioSource> AudioSourcePool;
    public AudioSource AudioSourceBGM;
    private AudioSource auxiliarAS;

    public static float MusicVolume = 1;
    public static float SoundVolume = 1;

    public SceneRoulette scene;

    private AudioSource rolingSound;

    void Awake()
    {
        _Instance = this;

        AudioSourcePool = new List<AudioSource>();
        AudioSourceAlloc();
    }

    private void Start()
    {
        // scene.musicToggle.onValueChanged.AddListener(ToggleVolumeSlider);
        // scene.soundToggle.onValueChanged.AddListener(ToggleSound);

        GetComponent<AudioSource>().playOnAwake = false;
        GetComponent<AudioSource>().Stop();
        if (Configuration.GetMusic() == "on")
        {
            GetComponent<AudioSource>().Play();
        }
    }

    public void ToggleVolumeSlider(bool value)
    {
        scene.volumeSlider.gameObject.SetActive(value);
    }

    public void ToggleSound(bool value)
    {
        SoundVolume = value ? 0 : 1;
        if (auxiliarAS)
            auxiliarAS.volume = SoundVolume;
    }

    public void ChangeVolume()
    {
        MusicVolume = scene.volumeSlider.value;
        AudioSourceBGM.volume = MusicVolume;
    }

    public void AudioSourceAlloc()
    {
        AudioSourcePool.Clear();

        for (int i = 0; i < maxAudioSourcePool; ++i)
        {
            AudioSource aS = gameObject.AddComponent<AudioSource>();
            aS.loop = false;
            AudioSourcePool.Add(aS);
        }
    }

    public AudioSource AudioSourcePop()
    {
        if (AudioSourcePool.Count <= 0)
            return null;

        AudioSource aS = AudioSourcePool[0];
        AudioSourcePool.RemoveAt(0);
        AudioSourcePool.Add(aS);

        return aS;
    }

    public static void SoundPlay(int iType)
    {
        if (Configuration.GetSound() == "on")
        {
            AudioSource aS = _Instance.AudioSourcePop();
            if (iType == 2)
                _Instance.auxiliarAS = aS;
            _Instance.audioEvent[iType].PlayIn(aS, SoundVolume);
        }
    }

    public static void StopAuxiliar()
    {
        _Instance.auxiliarAS.Stop();
    }

    public void SoundPlayCoroutine(int iType, float fDelay)
    {
        if (Configuration.GetMusic() == "on")
        {
            StartCoroutine(SoundPlayIn(iType, fDelay));
        }
    }

    public IEnumerator SoundPlayIn(int iType, float fDelay)
    {
        if (Configuration.GetMusic() == "on")
        {
            if (fDelay > 0.0001f)
                yield return new WaitForSeconds(fDelay);

            SoundPlay(iType);
        }
    }

    public static void MusicPlay()
    {
        if (MusicVolume == 0)
            return;
        _Instance.AudioSourceBGM.volume = MusicVolume;
        _Instance.AudioSourceBGM.Play();
    }

    public static bool MusicIsPlaying()
    {
        return _Instance.AudioSourceBGM.isPlaying;
    }

    public static void MusicStop()
    {
        _Instance.AudioSourceBGM.Stop();
    }

    private void Update()
    {
        //  if (Configuration.GetMusic() == "on")
        //  {
        //      AudioSourceBGM.Play();
        //      AudioSourceBGM.volume = 0.6f;
        //  }
        //  else
        //  {
        //      AudioSourceBGM.Stop();
        //      AudioSourceBGM.volume = 0.0f;
        //  }
    }
}
