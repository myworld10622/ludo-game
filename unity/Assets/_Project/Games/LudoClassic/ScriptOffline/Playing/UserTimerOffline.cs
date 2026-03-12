using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;



namespace LudoClassicOffline
{

    public class UserTimerOffline : MonoBehaviour
    {
        #region VARIABLES
        [Header("TIMER VARIABLES")]
        public bool IsPlayerTurn;

        public float TotalTime;

        [Header("TIMER IMAGE VARIABLE")]

        public GameObject AllPlayerTimerImage;

        [Header("PLAYER INDEX VARIABLE")]
        private int PlayerIndex;

        public Sprite green;
        public Sprite red;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;


        #endregion
        private void OnEnable()
        {
            PlayerIndex = 0;
        }



        public void Reapet(float _remainTime, float _turnTime)
        {
            turnTime = _turnTime;
            remainTime = _remainTime;
            AllPlayerTimerImage.gameObject.SetActive(true);
            AllPlayerTimerImage.gameObject.GetComponent<Image>().color = Color.green;
            if (this.gameObject.activeSelf == true)
                InvokeRepeating(nameof(TurnTimeStart), 0f, 0.02f);

        }

        public void TurnDataReset()
        {
            CancelInvoke(nameof(TurnTimeStart));
            AllPlayerTimerImage.gameObject.SetActive(false);
        }

        float turnTime;
        float remainTime;
        public void TurnTimeStart()
        {
            AllPlayerTimerImage.gameObject.GetComponent<Image>().fillAmount = (remainTime / turnTime);
            remainTime -= 0.02f;
            AllPlayerTimerImage.gameObject.SetActive(true);
            if (AllPlayerTimerImage.gameObject.GetComponent<Image>().fillAmount <= 0.3)
            {
                IsPlayerTurn = true;
                if (IsPlayerTurn == true && !isSound)
                {
                    if (socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex == socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex)
                    {
                      //  TimeCount();
                    }
                }
                AllPlayerTimerImage.gameObject.transform.GetChild(0).GetComponent<Image>().sprite = red;
                AllPlayerTimerImage.gameObject.GetComponent<Image>().color = Color.red;
            }
            if (AllPlayerTimerImage.gameObject.GetComponent<Image>().fillAmount <= 0)
            {
                AllPlayerTimerImage.gameObject.GetComponent<Image>().fillAmount = 1;
                AllPlayerTimerImage.gameObject.SetActive(false);
                SoundManagerOffline.instance.TimeSoundStop(SoundManagerOffline.instance.timerAudio);
                CancelInvoke(nameof(TurnTimeStart));
                PlayerIndex += 1;
                TotalTime = 10;
                isSound = false;
                //if(!socketNumberEventReceiver.ludoNumberGsNew.tokenMovement)
                //    socketNumberEventReceiver.ludoNumberGsNew.ChangeTurnSeatIndex();
            }

            if (PlayerIndex == 4)
                PlayerIndex = 0;
        }

        public Coroutine coroutine;
        public void TimeCount()
        {
            if (coroutine != null && this.gameObject.activeSelf == true)
                StopCoroutine(coroutine);
            coroutine = StartCoroutine(TimeManage());
        }

        public bool isSound;

        IEnumerator TimeManage()
        {
            isSound = true;
            SoundManagerOffline.instance.TimeSound(SoundManagerOffline.instance.timerAudio);
            yield return new WaitForSeconds(0.1f);
        }

        public void TimeCountStop()
        {
            if (coroutine != null && this.gameObject.activeInHierarchy == true)
                StopCoroutine(coroutine);
        }
    }

}