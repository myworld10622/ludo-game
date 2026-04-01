using System.Collections;
using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{
    public class LudoNumberRingControlOffline : MonoBehaviour
    {
        public void Update() => gameObject.transform.Rotate(0f, 0f, -420f * Time.deltaTime);
    }

}