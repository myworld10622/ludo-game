    using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using UnityEngine.Networking;

namespace LudoClassicOffline
{
    public class LudoNumberResultPlayerDataOffline : MonoBehaviour
    {
        public Image profilePic;
        public Text userName, score, winAmount;
        public GameObject crown;
        public GameObject boxImage;
        internal void setProfile(string url)
        {
          //  GameManager.instace.LudoNumberUiManager.SpriteLoder(profilePic, url);
        }

    }
}