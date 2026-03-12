using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.Events;

namespace LudoClassicOffline
{

    public static class LudoNumber_WaitExtensionOffline
    {
        public static void WaitforTime(this MonoBehaviour mono, float delay, UnityAction action)
        {
            mono.StartCoroutine(ExecuteActionAfterDelay(delay, action));
        }

        private static IEnumerator ExecuteActionAfterDelay(float delay, UnityAction action)
        {
            yield return new WaitForSecondsRealtime(delay);
            action.Invoke();
            yield break;
        }
    }
}
