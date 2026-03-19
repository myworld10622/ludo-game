using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using TMPro;

namespace LudoClassicOffline
{
    public enum ToastMessage
    {
        STARTGAME,
        EXTRAMOVE,
        PLUS56,
        WAITFORPLAYER,
        FINISHGAME
    }

    public class LudoNumberTostMessageOffline : MonoBehaviour
    {
        [SerializeField]
        public  GameObject WaitingForPlayerMessage;
        public int toastCount = 0;

        private TMP_Text waitingTextTmp;
        private Text waitingTextLegacy;

        private void OnDisable()
        {
            if (WaitingForPlayerMessage != null && WaitingForPlayerMessage.transform != null)
            {
                WaitingForPlayerMessage.transform.DOKill();
            }
        }

        private void OnDestroy()
        {
            if (WaitingForPlayerMessage != null && WaitingForPlayerMessage.transform != null)
            {
                WaitingForPlayerMessage.transform.DOKill();
            }
        }

        public void ShowToastMessages(ToastMessage toastMessage)
        {
            if (WaitingForPlayerMessage == null || WaitingForPlayerMessage.transform == null)
            {
                return;
            }

            toastCount++;
            switch (toastMessage)
            {
                case ToastMessage.STARTGAME:
                    break;
                case ToastMessage.EXTRAMOVE:
                    break;
                case ToastMessage.PLUS56:
                    break;
                case ToastMessage.WAITFORPLAYER:
                    WaitingForPlayerMessage.transform.DOKill();
                    WaitingForPlayerMessage.SetActive(true);
                    WaitingForPlayerMessage.transform.DOMoveX(0f, 1.75f).SetEase(Ease.InOutBack);
                    break;
                case ToastMessage.FINISHGAME:
                    break;
                default:
                    break;
            }
        }

        public void HideWaitForPlayerToast(bool immediately)
        {
            if (WaitingForPlayerMessage == null || WaitingForPlayerMessage.transform == null)
            {
                return;
            }

            WaitingForPlayerMessage.transform.DOKill();
            if (immediately)
            {
                WaitingForPlayerMessage.SetActive(false);
                return;
            }

            WaitingForPlayerMessage.transform.DOMoveX(-1120f, 1.75f).SetDelay(1.0f);
        }

        public void SetWaitingMessage(string message)
        {
            if (WaitingForPlayerMessage == null)
            {
                return;
            }

            if (waitingTextTmp == null)
            {
                waitingTextTmp = WaitingForPlayerMessage.GetComponentInChildren<TMP_Text>(true);
            }

            if (waitingTextLegacy == null)
            {
                waitingTextLegacy = WaitingForPlayerMessage.GetComponentInChildren<Text>(true);
            }

            if (waitingTextTmp != null)
            {
                waitingTextTmp.text = message;
            }

            if (waitingTextLegacy != null)
            {
                waitingTextLegacy.text = message;
            }
        }
    }
}
