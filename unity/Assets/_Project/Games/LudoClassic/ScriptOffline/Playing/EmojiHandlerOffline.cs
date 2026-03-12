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
            socketNumberEventReceiver.emojiResponse.Data.emoji = emojiNo;
            socketNumberEventReceiver.emojiResponse.Data.seatIndex = 0;

            socketNumberEventReceiver.ludoNumberGsNew.EmojiSet();
            //GameManager.instace.socketConnection.SendDataToSocket(GameManager.instace.ludoNumberEventManager.EmojiSendReq(senderId, emojiNo, tabelId),
                //EmojiAcknowledgement, "EMOJI");
        }
        private void EmojiAcknowledgement(string expectAcknowledgement) =>
            Debug.Log("EmojiAcknowledgement || expectAcknowledgement  " + expectAcknowledgement);
    }
}