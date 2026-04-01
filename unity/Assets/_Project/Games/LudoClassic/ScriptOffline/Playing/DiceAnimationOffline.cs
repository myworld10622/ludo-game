using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class DiceAnimationOffline : MonoBehaviour, IPointerClickHandler
    {
        public GameObject dice;
        public int diceNumber;
        public List<Sprite> diceAnimtion;
        public List<Sprite> diceList;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public GameManagerOffline gameManager;
        public LudoNumberGsNewOffline ludoNumberGsNew;
        public Coroutine diceCoroutine;

        public IEnumerator DiceRoll(int diceValue, int startTurnSeatIndex)
        {
            // Variable speed: fast tumble at start, slow down at end for realistic dice deceleration
            Image diceImage = dice.GetComponent<Image>();
            diceImage.raycastTarget = false;
            for (int i = 0; i < 23; i++)
            {
                // Decelerate: frames 0-10 fast (0.015s), 11-18 medium (0.025s), 19-22 slow (0.045s)
                float frameDelay = i < 11 ? 0.015f : (i < 19 ? 0.025f : 0.045f);
                yield return new WaitForSeconds(frameDelay);
                diceImage.sprite = diceAnimtion[i];
            }
            diceNumber = socketNumberEventReceiver.diceValue;
            socketNumberEventReceiver.ludoNumberGsNew.isAnimation = true;
            DicePostionStartCo(diceValue, startTurnSeatIndex);
        }

        public void OnPointerClick(PointerEventData eventData)
        {
            Debug.Log("Dice Animation || OnPointer Click || Click On dice");
            dice.transform.GetComponent<Image>().raycastTarget = false;
            StartDiceAnimation();
        }

        private void StartDiceAnimation()
        {
            for (int i = 0; i < ludoNumberGsNew.ludoNumberPlayerControl.Length; i++)
            {
                ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.turnTimeShowArrow.SetActive(false);
                ludoNumberGsNew.ludoNumberPlayerControl[i].ludoNumbersUserData.arrowAnimationOnTurnTime.enabled = false;
            }
            gameManager.DiceAnimation();
        }

        public void DiceAnimationStart(int diceValue, int startTurnSeatIndex)
        {
            // Punch scale on throw for tactile feedback, then settle before rolling
            transform.DOKill();
            transform.DOPunchScale(new Vector3(0.3f, 0.3f, 0), 0.2f, 5, 0.5f).OnComplete(() =>
            {
                dice.gameObject.GetComponent<RectTransform>().sizeDelta = new Vector3(130, 130);
                SoundManagerOffline.instance.SoundPlay(SoundManagerOffline.instance.diceAnimationAudio);

                if (diceCoroutine != null)
                    StopCoroutine(diceCoroutine);
                diceCoroutine = StartCoroutine(DiceRoll(diceValue, startTurnSeatIndex));
            });
        }

        public void DicePostionStartCo(int diceValue, int startTurnSeatIndex)
        {
            SoundManagerOffline.instance.soundAudioSource.Stop();
            StartCoroutine(DicePostion(diceValue, startTurnSeatIndex));
        }

        public IEnumerator DicePostion(int diceValue, int startTurnSeatIndex)
        {
            yield return new WaitForSeconds(0.01f);
            try
            {
                if (diceNumber - 1 >= 0)
                {
                    dice.GetComponent<Image>().sprite = diceList[diceNumber - 1];
                    RectTransform diceRect = dice.gameObject.GetComponent<RectTransform>();
                    diceRect.sizeDelta = new Vector3(85, 85);
                    // Punch scale on result reveal for satisfying feedback
                    dice.transform.DOKill();
                    dice.transform.localScale = Vector3.one;
                    dice.transform.DOPunchScale(new Vector3(0.4f, 0.4f, 0), 0.3f, 6, 0.4f);
                    StartCoroutine(WaitForSecond(diceValue, startTurnSeatIndex));
                }
                else
                {
                    Debug.LogError("I AM  Move =>" + (diceNumber - 1));
                }
            }
            catch (System.Exception ex)
            {
                Debug.LogError("Log try Catch =>" + ex.ToString());
            }
        }

        public IEnumerator WaitForSecond(int diceValue, int startTurnSeatIndex)
        {
            yield return new WaitForSeconds(0.1f);
            socketNumberEventReceiver.ludoNumberGsNew.DiceAnimationStart(diceValue, startTurnSeatIndex);
        }
    }
}
