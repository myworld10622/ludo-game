using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;

namespace LudoClassicOffline
{
    public class BoardRotateManagerOffline : MonoBehaviour
    {
        public static bool isRotate;
        public List<Transform> allPlayerTokens;
        public RectTransform greenPlayer, bluePlayer, yellowPlayer, redPlayer;

        private readonly Vector3 rotatedRotation = new Vector3(0, 0, -90);
        private readonly Vector3 defaultRotation = Vector3.zero;

        private readonly Vector3[] rotatedPositions = 
        {
            new Vector3(-321f, -562f, 0), 
            new Vector3(-330f, 878f, 0),  
            new Vector3(390f, 795f, 0), 
            new Vector3(395f, -655f, 0)  
        };

        private readonly Vector3[] defaultPositions = 
        {
            new Vector3(-465f, -590f, 0), 
            new Vector3(-465f, 797f, 0),  
            new Vector3(335f, 790f, 0),   
            new Vector3(365f, -583f, 0)   
        };

        public void ClickOnRotateBtn()
        {
            isRotate = !isRotate;
            Vector3 targetRotation = isRotate ? rotatedRotation : defaultRotation;
            Vector3[] targetPositions = isRotate ? rotatedPositions : defaultPositions;

            foreach (var token in allPlayerTokens)
            {
                token.DORotate(targetRotation, 0.1f);
            }

            RectTransform[] players = { greenPlayer, bluePlayer, yellowPlayer, redPlayer };
            for (int i = 0; i < players.Length; i++)
            {
                players[i].DOAnchorPos(targetPositions[i], 0.1f);
                players[i].DORotate(targetRotation, 0.1f);
            }
        }
    }
}
