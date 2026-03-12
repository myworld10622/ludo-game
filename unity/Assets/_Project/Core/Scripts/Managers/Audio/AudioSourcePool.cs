using System.Collections.Generic;
using UnityEngine;

public class AudioSourcePool : MonoBehaviour
{
    public static AudioSourcePool Instance;

    [SerializeField]
    private int initialPoolSize = 5;

    [SerializeField]
    private AudioSource audioSourcePrefab;

    private List<AudioSource> audioSourcePool = new List<AudioSource>();

    private void Awake()
    {
        if (Instance == null)
            Instance = this;
        else
            Destroy(gameObject);

        // Initialize the pool
        for (int i = 0; i < initialPoolSize; i++)
        {
            AddAudioSourceToPool();
        }
    }

    private void AddAudioSourceToPool()
    {
        AudioSource newSource = Instantiate(audioSourcePrefab, transform);
        newSource.gameObject.SetActive(false);
        audioSourcePool.Add(newSource);
    }

    public AudioSource GetAvailableAudioSource()
    {
        foreach (var source in audioSourcePool)
        {
            if (!source.isPlaying)
            {
                source.gameObject.SetActive(true);
                return source;
            }
        }

        // If all sources are busy, create a new one
        AddAudioSourceToPool();
        return audioSourcePool[audioSourcePool.Count - 1];
    }

    public void ReturnAudioSource(AudioSource source)
    {
        source.Stop();
        source.gameObject.SetActive(false);
    }
}