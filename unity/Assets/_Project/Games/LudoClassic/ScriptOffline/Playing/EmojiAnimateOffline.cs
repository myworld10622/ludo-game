using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;
using UnityEngine.Serialization;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class EmojiAnimateOffline : MonoBehaviour
    {
        [SerializeField] private string id;
        [SerializeField] GameObject emojiPrefab;
        [SerializeField] private List<RuntimeAnimatorController> emojiAnimatiorList;
        RectTransform cloneEmojiParent = null;
        public void EmojiAnimation(int number,int seatIndex)
        {
            this.gameObject.SetActive(true);
            GameObject emojiClone;
            cloneEmojiParent = ResolveEmojiParent(seatIndex);
            if (cloneEmojiParent == null && id == GameManagerOffline.instace.selfUserID)
            {
                cloneEmojiParent = GameManagerOffline.instace.emojiParent;
                Debug.Log("cloneEmojiParent => " + cloneEmojiParent);
            }
            else if (cloneEmojiParent == null)
            {
                for (int i = 0; i < GameManagerOffline.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
                {
                    Debug.Log("GameManager.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].playerInfoData.userId  => " + GameManagerOffline.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].playerInfoData.userId);
                    if (GameManagerOffline.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].playerInfoData.playerSeatIndex == seatIndex)  
                    {
                        cloneEmojiParent = GameManagerOffline.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i].emojiTransform;
                        Debug.Log("cloneEmojiParent 2 => " + cloneEmojiParent);
                    }
                }
            }

            if (cloneEmojiParent == null)
            {
                cloneEmojiParent = GameManagerOffline.instace.emojiParent;
            }

            emojiClone = Instantiate(emojiPrefab, cloneEmojiParent);
            emojiClone.GetComponent<RectTransform>().anchoredPosition = Vector2.zero;
            Animator anim = emojiClone.GetComponent<Animator>();
            anim.runtimeAnimatorController = emojiAnimatiorList[number];
            SoundManagerOffline.instance.EmojiSoundPlay(SoundManagerOffline.instance.emojiSoundClip, number);
            Destroy(emojiClone, 2.2f);
        }

        private RectTransform ResolveEmojiParent(int seatIndex)
        {
            if (GameManagerOffline.instace == null || GameManagerOffline.instace.ludoNumbersAcknowledgementHandler == null)
            {
                return null;
            }

            if (DashBoardManagerOffline.instance != null && DashBoardManagerOffline.instance.IsPassAndPlay)
            {
                for (int i = 0; i < GameManagerOffline.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl.Length; i++)
                {
                    var playerControl = GameManagerOffline.instace.ludoNumbersAcknowledgementHandler.ludoNumberPlayerControl[i];
                    if (playerControl != null && playerControl.playerInfoData.playerSeatIndex == seatIndex)
                    {
                        return playerControl.emojiTransform;
                    }
                }
            }

            return null;
        }
    }
}
