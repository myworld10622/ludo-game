using System.Collections;
using System.Collections.Generic;
using System.Linq;
using UnityEngine;
using UnityEngine.Networking;

public static class AudioUtil
{
    // Dictionary to store cached audio clips
    private static Dictionary<string, AudioClip> audioClipCache = new Dictionary<string, AudioClip>();

    // Method to load an audio file from StreamingAssets and play it
    public static IEnumerator LoadAndPlayAudio(string fileName, AudioSource audioSource, bool loop = false, bool playSound = false)
    {
        // Construct the file path
        string filePath = System.IO.Path.Combine(Application.streamingAssetsPath, fileName);

        // On WebGL, Application.streamingAssetsPath is already a URL, so do not add "file://"
#if UNITY_WEBGL
        string url = filePath; // WebGL paths are already valid HTTP/HTTPS URLs
#elif UNITY_ANDROID
        string url = filePath;
#else
        string url = "file://" + filePath; // For other platforms, use file:// prefix
#endif


        //  Debug.Log($"Loading audio from: {url}");

        /*      #if UNITY_WEBGL
             // WebGL: Ensure the path doesn't repeat 'StreamingAssets' in the URL
             string url = "StreamingAssets/Audio/" + fileName; // Correct WebGL path
     #else
             string url = "file://" + filePath; // For non-WebGL platforms, use the file:// prefix
     #endif

             Debug.Log($"Loading audio from: {url}"); */

        // Check if the audio clip is already cached
        if (audioClipCache.ContainsKey(url))
        {
            audioSource.clip = audioClipCache[url];
            audioSource.loop = loop;

            if (playSound)
                StartAudio(audioSource);
        }
        else
        {
            using (UnityWebRequest www = UnityWebRequestMultimedia.GetAudioClip(url, AudioType.UNKNOWN))
            {
                yield return www.SendWebRequest();

                if (www.result == UnityWebRequest.Result.ConnectionError || www.result == UnityWebRequest.Result.ProtocolError)
                {
                    Debug.LogError($"Error loading audio file:{url} ERROR : {www.error}");
                }
                else
                {
                    AudioClip audioClip = DownloadHandlerAudioClip.GetContent(www);
                    audioClipCache[url] = audioClip;

                    audioSource.clip = audioClip;
                    audioSource.loop = loop;

                    if (playSound)
                        StartAudio(audioSource);

                    if (fileName.Contains("plane-takeoff"))
                    {
                        AudioManager._instance.planetakeof = audioSource;
                    }
                }
            }
        }
    }

    // Method to assign an audio clip to an AudioSource
    public static void AssignAudioClip(AudioSource audioSource, AudioClip clip)
    {
        audioSource.clip = clip;
    }

    // Method to start playing audio
    public static void StartAudio(AudioSource audioSource)
    {
        if (!audioSource.isPlaying)
        {
            //audioSource.volume = 1;
            audioSource.Play();
        }
    }

    // Method to stop playing audio
    public static void StopAudio(AudioSource audioSource)
    {
        if (audioSource.isPlaying)
        {
            audioSource.Stop();
        }
    }
    public static void StopAllAudio()
    {
        /* foreach (KeyValuePair<string, AudioClip> entry in audioClipCache)
        {
            GameObject[] audioObjects = GameObject.FindObjectsOfType<GameObject>();

            foreach (GameObject obj in audioObjects)
            {
                AudioSource source = obj.GetComponent<AudioSource>();
                if (source != null && source.isPlaying)
                {
                    source.Stop();
                }
            }
        } */

        AudioSource[] allAudioSources = GameObject.FindObjectsOfType<AudioSource>();


        foreach (AudioSource audioSource in allAudioSources)
        {
            if (audioSource.isPlaying)
            {
                audioSource.Stop();
                //audioSource.Stop();
            }
        }
        allAudioSources.ToList<AudioSource>().Find(x => x == AudioManager._instance.bgAudioSource).Play();
    }
    // Method to set the volume of an audio source
    public static void SetVolume(AudioSource audioSource, float volume)
    {
        audioSource.volume = volume;
    }
}
