using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

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
        public void ShowToastMessages(ToastMessage toastMessage)
        {
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
            if (immediately)
                WaitingForPlayerMessage.SetActive(false);
            WaitingForPlayerMessage.transform.DOMoveX(-1120f, 1.75f).SetDelay(1.0f);
        }
    }
}
