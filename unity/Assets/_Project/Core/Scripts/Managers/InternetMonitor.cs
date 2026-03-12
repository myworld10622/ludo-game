using UnityEngine;
using System;

public class InternetMonitor : MonoBehaviour
{
    public static event Action OnInternetLost;    // Triggered when internet is lost
    public static event Action OnInternetRestored; // Triggered when internet is restored

    private bool hasInternet; // Tracks current internet state

    void Start()
    {
        hasInternet = IsInternetAvailable(); // Set initial state
    }

    void Update()
    {
        bool currentStatus = IsInternetAvailable();

        if (hasInternet && !currentStatus) // If internet was ON but now it's OFF
        {
            hasInternet = false; // Update status
            OnInternetLost?.Invoke(); // Trigger event once
            Debug.Log("🚨 Internet Lost!");
        }
        else if (!hasInternet && currentStatus) // If internet was OFF but now it's ON
        {
            hasInternet = true; // Update status
            OnInternetRestored?.Invoke(); // Trigger event once
            Debug.Log("✅ Internet Restored!");
        }
    }

    bool IsInternetAvailable()
    {
        return Application.internetReachability != NetworkReachability.NotReachable;
    }
}
