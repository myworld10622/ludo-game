using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class DiceAnimationForFTUEPanelOffline : MonoBehaviour, IPointerClickHandler
    {
        public GameObject dice;
        public int diceNumber;
        public List<Sprite> diceAnimtion;
        public List<Sprite> diceList;
        public FTUEManagerOffline ftueManager;
        public GameObject arrowFirstStep;

        public IEnumerator DiceRoll()
        {
            for (int i = 0; i < 24; i++)
            {
                dice.transform.GetComponent<Image>().raycastTarget = false;
                yield return new WaitForSeconds(0.01f);
                dice.GetComponent<Image>().sprite = diceAnimtion[i];
            }
            DicePostionStart();
        }

        public void OnPointerClick(PointerEventData eventData)
        {
            ftueManager.artoonLogo.SetActive(false);
            DiceAnimationStart();
        }


        public void DiceAnimationStart()
        {
            transform.DOScale(1f, 0.1f).OnComplete(() =>
            {
                SoundManagerOffline.instance.SoundPlay(SoundManagerOffline.instance.diceAnimationAudio);
                StartCoroutine(DiceRoll());
                transform.DOScale(1f, 0.1f);
            });
        }

        public void DicePostionStart()
        {
            SoundManagerOffline.instance.soundAudioSource.Stop();
            StartCoroutine(DicePostion());
        }

        public IEnumerator DicePostion()
        {
            yield return new WaitForSeconds(0.1f);
            dice.GetComponent<Image>().sprite = diceList[diceNumber - 1];
            ftueManager.artoonLogo.SetActive(true);
            if (ftueManager.Step1UI.activeInHierarchy)
            {
                ftueManager.NextArrow.SetActive(true);
                arrowFirstStep.SetActive(false);
                ftueManager.NextButton.interactable = true;
            }
            else if (ftueManager.Step5UI.activeInHierarchy)
            {
                ftueManager.Step_5_Arrow.SetActive(true);
                arrowFirstStep.SetActive(false);
            }
            else if (ftueManager.Step6UI.activeInHierarchy)
            {
                ftueManager.Step_6_Arrow.SetActive(true);
                arrowFirstStep.SetActive(false);
                if (MGPSDK.MGPGameManager.instance.sdkConfig.data.lobbyData.gameModeName.Equals("CLASSIC"))
                    ftueManager.TutorialTextMessage.text = "Reaching home with a token will help win game";
                else
                    ftueManager.TutorialTextMessage.text = "Reaching home with a token will score points as well";
            }
            else if (ftueManager.CaptureStepHighLight.activeInHierarchy)
            {
                arrowFirstStep.SetActive(false);
                ftueManager.NextArrow.SetActive(true);
                ftueManager.NextButton.interactable = true;
            }
        }
    }
}