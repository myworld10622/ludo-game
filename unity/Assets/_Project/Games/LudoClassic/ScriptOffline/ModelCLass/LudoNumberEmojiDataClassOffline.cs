using System.Collections;
using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{
    public class LudoNumberEmojiDataClass : MonoBehaviour
    {
        // Root myDeserializedClass = JsonConvert.DeserializeObject<Root>(myJsonResponse);
        [System.Serializable]
        public class EmojiRequestData
        {
            public string userId;
            public string tableId;
            public int emoji;
            public string message;
        }

        [System.Serializable]
        public class EmojiRequest
        {
            public string en;
            public EmojiRequestData data;
        }

        // Root myDeserializedClass = JsonConvert.DeserializeObject<Root>(myJsonResponse);
        [System.Serializable]
        public class EmojiResponseData
        {
            public int seatIndex;
            public string tableId;
            public int emoji;
            public string message;
        }

        [System.Serializable]
        public class EmojiResponse
        {
            public string en;
            public EmojiResponseData Data;
        }
    }
}