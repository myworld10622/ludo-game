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
            for (int i = 0; i < 23; i++)
            {
                dice.transform.GetComponent<Image>().raycastTarget = false;
                yield return new WaitForSeconds(0.01f);
                dice.GetComponent<Image>().sprite = diceAnimtion[i];
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
            transform.DOScale(1f, 0.1f).OnComplete(() =>
            {
                dice.gameObject.GetComponent<RectTransform>().sizeDelta = new Vector3(130 , 130);
                SoundManagerOffline.instance.SoundPlay(SoundManagerOffline.instance.diceAnimationAudio);

                if (diceCoroutine != null)
                    StopCoroutine(diceCoroutine);
                diceCoroutine = StartCoroutine(DiceRoll(diceValue, startTurnSeatIndex));
                transform.DOScale(1f, 0.1f);
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
                    dice.gameObject.GetComponent<RectTransform>().sizeDelta = new Vector3(85, 85);
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
