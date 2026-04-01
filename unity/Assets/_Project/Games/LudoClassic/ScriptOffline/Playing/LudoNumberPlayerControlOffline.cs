using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{

    public class LudoNumberPlayerControlOffline : MonoBehaviour
    {
        public PlayerInfoData playerInfoData;
        public LudoNumbersUserData ludoNumbersUserData = new LudoNumbersUserData();
        public LudoNumberTostMessageOffline ludoNumberTostMessage;
        public LudoNumberUiManagerOffline ludoNumberUiManager;
        public Image playerProfile;
        public LudoNumberPlayerHomeOffline PlayerHome;
        public List<CoockieMovementOffline> coockieMovementList;
        public RectTransform emojiTransform;

        [Header("MOVER TEXT GAMEOBJECTS")]
        [SerializeField]
        private GameObject MoverBG;

        [Header("Player Profile")] public Text userName;

        public Text moveText;
        internal int SeatIndex;

        public void SetMoveIndex()
        {
            userName.text = LudoDisplayNameUtility.ResolveDisplayName(
                ludoNumbersUserData.userId,
                ludoNumbersUserData.username,
                SeatIndex
            );

            string imgUrl = ludoNumbersUserData.userProfile;
            if (!string.IsNullOrEmpty(imgUrl.Trim()))
            { }
              //  ludoNumberUiManager.SpriteLoder(playerProfile, imgUrl);

            MoverBG.SetActive(true);
        }
    }
}
