using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.SceneManagement;

using UnityEngine.SocialPlatforms.Impl;

namespace LudoClassicOffline
{

    public class BridgeControllerOffline : MonoBehaviour
    {
        #region VARIABLES
        public static BridgeControllerOffline Instance;
        internal Action<List<userProfile>, string> StartGameScene;
        public List<userProfile> userListOnMatch = new List<userProfile>();
        public string roomName = "Dummy";
        #endregion

        #region Awake
        private void Awake()
        {
            if (Instance == null)
                Instance = this;
            else
                Destroy(Instance);
        }
        #endregion

        #region OnEnable
        void OnEnable()
        {
            SceneManager.sceneLoaded += OnSceneLoaded;
        }
        #endregion

        #region OnSceneLoaded
        private void OnSceneLoaded(Scene scene, LoadSceneMode mode)
        {
            if (scene.name == "LS")
                StartGameScene?.Invoke(userListOnMatch, roomName);
        }
        #endregion

        #region OnDisable
        void OnDisable() => SceneManager.sceneLoaded -= OnSceneLoaded;
        #endregion
    }
}
