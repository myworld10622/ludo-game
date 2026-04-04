using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using DG.Tweening;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class EmojiHandlerOffline : MonoBehaviour
    {
        [SerializeField] public string senderId;
        [SerializeField] public string tabelId;

        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public void InsidePopUpEmojiClick(int emojiNo)
        {
            int seatIndex = ResolveEmojiSeatIndex();

            if (LudoV2MatchmakingBridge.Instance != null && LudoV2MatchmakingBridge.Instance.TrySendEmoji(emojiNo, seatIndex))
            {
                return;
            }

            socketNumberEventReceiver.emojiResponse.Data.emoji = emojiNo;
            socketNumberEventReceiver.emojiResponse.Data.seatIndex = seatIndex;
            socketNumberEventReceiver.ludoNumberGsNew.EmojiSet();
            //GameManager.instace.socketConnection.SendDataToSocket(GameManager.instace.ludoNumberEventManager.EmojiSendReq(senderId, emojiNo, tabelId),
                //EmojiAcknowledgement, "EMOJI");
        }

        private int ResolveEmojiSeatIndex()
        {
            if (DashBoardManagerOffline.instance != null && DashBoardManagerOffline.instance.IsPassAndPlay)
            {
                return socketNumberEventReceiver != null ? socketNumberEventReceiver.userStartIndex : 0;
            }

            if (socketNumberEventReceiver != null && socketNumberEventReceiver.signUpResponce != null)
            {
                return socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex;
            }

            return 0;
        }

        private void EmojiAcknowledgement(string expectAcknowledgement) =>
            Debug.Log("EmojiAcknowledgement || expectAcknowledgement  " + expectAcknowledgement);
    }
}
