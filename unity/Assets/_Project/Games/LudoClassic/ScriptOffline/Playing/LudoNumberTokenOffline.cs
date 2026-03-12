using DG.Tweening;
using System;
using System.Collections;
using System.Collections.Generic;
using System.Net.NetworkInformation;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoNumberTokenOffline : MonoBehaviour
    {
        [Header("TOKEN INDEX VARIABLES")] public int tokenIndex;

        [Header("MOVEMENT STEP COUNT AND BOOLEAN")]
        public int MovementStep;
        public LudoNumbersPlayerHomeOffline playerHome;
        public static event Action<bool> SetPlayerring;


        public bool movement_able = true;
        private void Start() => SetPlayerringAnimation(true);
     
        private void OnEnable() => SetPlayerring += SetPlayerringAnimation;
        
        private void OnDisable() => SetPlayerring -= SetPlayerringAnimation;
       
        public static void OnSetPlayerring(bool val)
        {
            if (SetPlayerring != null)
                SetPlayerring(val);
        }

        void SetPlayerringAnimation(bool val)
        {
            playerHome.way_Point[MovementStep].GetComponent<LudoNumberPlayerHomeOffline>().SetLayer(this);
            if (val)
                if (movement_able)
                    transform.localScale = new Vector3(1f, 1f, 1f);
        }
    }
}
