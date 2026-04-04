using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoRoomChatMessageItem : MonoBehaviour
    {
        private Text senderText;
        private Text messageText;
        private Image bubbleImage;
        private LayoutElement layoutElement;

        public void Initialize(Text senderLabel, Text messageLabel, Image bubble, LayoutElement layout)
        {
            senderText = senderLabel;
            messageText = messageLabel;
            bubbleImage = bubble;
            layoutElement = layout;
        }

        public void Bind(LudoV2ChatMessagePayload payload, bool isLocalUser)
        {
            if (senderText == null || messageText == null)
            {
                return;
            }

            string senderName = payload?.sender?.display_name;
            if (string.IsNullOrWhiteSpace(senderName))
            {
                senderName = payload?.sender_type == "bot" ? "Bot" : "Player";
            }

            senderText.text = isLocalUser ? "You" : senderName;
            senderText.color = isLocalUser
                ? new Color32(102, 217, 176, 255)   // teal-green for self
                : new Color32(0, 168, 132, 255);     // WhatsApp green for others

            messageText.text = payload?.message ?? string.Empty;
            messageText.color = new Color32(232, 228, 222, 255); // warm white

            if (bubbleImage != null)
            {
                // WhatsApp dark theme: teal for self, dark gray for others
                bubbleImage.color = isLocalUser
                    ? new Color32(0, 92, 75, 255)
                    : new Color32(32, 44, 51, 255);
            }

            if (layoutElement != null)
            {
                layoutElement.minHeight = 58f;
                layoutElement.preferredHeight = -1f;
                layoutElement.flexibleHeight = 0f;
            }
        }
    }
}
