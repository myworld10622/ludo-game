using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.Events;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoNumberPlayerHomeOffline : MonoBehaviour
    {
        [Header("PLAYER INDEX VARIABLE")] public int playerIndex;
        public List<LudoNumberTokenOffline> tokensOnthisBox;
        public int CanvasOrder;
        public LineRenderer line;
        public COLOR color;
        public List<Transform> linePoints = new List<Transform>();
        public List<CoockieMovementOffline> AllTokensForThisHome;
        public List<CoockieMovementOffline> AllTokensForThisHomeForClassicMode;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;

        internal void AnimateLine(int endPoint, int tokenID, bool isRight, UnityAction callBack)
        {
            if(endPoint == -1)
            {
                callBack.Invoke();
                return;
            }
            StartCoroutine(StartAnimateLine(endPoint, tokenID, isRight, callBack));
        }
        private IEnumerator StartAnimateLine(int endPoint, int tokenID, bool isRight, UnityAction callBack)
        {
            Debug.Log("Line Animation Started For ---> " + gameObject.name);
            var points = endPoint + 1;
            for (var i = 0; i < points; i++)
            {
                line.positionCount++;
                line.SetPosition(i, linePoints[i].position);
                yield return new WaitForSeconds(0.05f);
            }

            var token = AllTokensForThisHome[tokenID];
            Debug.Log("Token ID ---> " + token.name);
           token.ShowAwayPopup(endPoint, isRight);
            callBack.Invoke();
        }


        internal void BlinkLine(UnityAction callBack)
        {
            Color lineColor = GetLineColor();
            line.DOColor(new Color2(lineColor, lineColor), new Color2(Color.white, Color.white), 0.2f).SetLoops(6)
                .OnComplete(
                    () =>
                    {
                        line.startColor = lineColor;
                        line.endColor = lineColor;
                        callBack.Invoke();
                    });
        }
        private Color32 GetLineColor()
        {
            switch (this.color)
            {
                case COLOR.green:
                    return Color.green;
                case COLOR.red:
                    return Color.red;
                case COLOR.blue:
                    return Color.blue;
                case COLOR.yellow:
                    return Color.yellow;
                default:
                    return Color.white;
            }
        }

        public void SetLayer(LudoNumberTokenOffline token)
        {
            Debug.Log("SetLayer");
            if (tokensOnthisBox.Count > 1)
            {
                for (int i = 0; i < tokensOnthisBox.Count; i++)
                {
                    if (token.playerHome.playerIndex != tokensOnthisBox[i].playerHome.playerIndex)
                    {
                        token.GetComponent<Canvas>().sortingOrder = CanvasOrder + i;
                        print(token.name + "" + i);
                    }
                    else
                    {
                        print(tokensOnthisBox[i].name + "Other token " + i);
                        tokensOnthisBox[i].GetComponent<Canvas>().sortingOrder = CanvasOrder + i;
                    }
                }
            }
        }
    }
}
