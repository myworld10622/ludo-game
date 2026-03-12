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
            // var cloneEmojiParent = id == GameManager.instace.ludoNumbersAcknowledgementHandler.myUserId ? GameManager.instace.emojiParent : GameManager.instace.emojiParentOppo;
            if (id == GameManagerOffline.instace.selfUserID)
            {
                cloneEmojiParent = GameManagerOffline.instace.emojiParent;
                Debug.Log("cloneEmojiParent => " + cloneEmojiParent);
            }
            else
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
            emojiClone = Instantiate(emojiPrefab, cloneEmojiParent);
            emojiClone.GetComponent<RectTransform>().anchoredPosition = Vector2.zero;
            Animator anim = emojiClone.GetComponent<Animator>();
            anim.runtimeAnimatorController = emojiAnimatiorList[number];
            SoundManagerOffline.instance.EmojiSoundPlay(SoundManagerOffline.instance.emojiSoundClip, number);
            Destroy(emojiClone, 2.2f);
        }
    }
}