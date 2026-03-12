using System.Collections;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

public class PingManager : MonoBehaviour
{
    public string pingTarget = "https://api64.ipify.org/"; // Target URL for WebGL
    public Text pingText; // Assign your UI Text or TextMeshPro element
    public float pingInterval = 2f; // Time between pings

    void OnEnable()
    {
        pingText.gameObject.SetActive(false);
    }

    /* #if !UNITY_WEBGL
        private Ping ping;
        private Coroutine pingCoroutine;
    #endif

        void Start()
        {
    #if UNITY_WEBGL
            // Use UnityWebRequest for WebGL
            StartCoroutine(PingWebGLRoutine());
    #else
            // Use Ping for other platforms
            pingCoroutine = StartCoroutine(PingRoutine());
    #endif
        }

    #if !UNITY_WEBGL
        IEnumerator PingRoutine()
        {
            while (true)
            {
                // Create a new Ping object
                ping = new Ping(pingTarget);

                // Wait for up to 5 seconds for the ping to complete
                float timeout = 5f;
                while (!ping.isDone && timeout > 0f)
                {
                    timeout -= Time.deltaTime;
                    yield return null;
                }

                // Display the ping result
                if (ping.isDone)
                {
                    pingText.text = $"Ping: {ping.time} ms";
                    pingText.color = ping.time >= 100 ? Color.red : Color.green; // High latency in red
                }
                else
                {
                    pingText.text = "Ping: Timeout";
                    pingText.color = Color.yellow;
                }

                // Wait before the next ping
                yield return new WaitForSeconds(pingInterval);
            }
        }
    #endif

        IEnumerator PingWebGLRoutine()
        {
            while (true)
            {
                float startTime = Time.time;

                using (UnityWebRequest request = UnityWebRequest.Get(pingTarget))
                {
                    yield return request.SendWebRequest();

                    if (request.result == UnityWebRequest.Result.Success)
                    {
                        float roundTripTime = (Time.time - startTime) * 1000; // Convert to milliseconds
                        pingText.text = $"Ping: {roundTripTime:F1} ms";
                        pingText.color = roundTripTime >= 100 ? Color.red : Color.green; // High latency in red
                    }
                    else
                    {
                        pingText.text = "Ping: Timeout";
                        pingText.color = Color.yellow;
                    }
                }

                // Wait before the next ping
                yield return new WaitForSeconds(pingInterval);
            }
        }

        void OnDestroy()
        {
    #if !UNITY_WEBGL
            // Stop the coroutine when the object is destroyed
            if (pingCoroutine != null)
            {
                StopCoroutine(pingCoroutine);
            }
    #endif
        } */
}
