using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoNumbersBoxPropertyOffline : MonoBehaviour, IPointerClickHandler
    {
        public TokenPopupPositions tokenPopup;

        public int CanvasOrder;
        public Image trail;
        public int index;
        public BoxType boxType;

        private Transform tokenHolder;

        private void Awake()
        {
            if (transform.childCount > 1)
                tokenHolder = transform.GetChild(1);

            DisableBoardRaycasts();
        }

        public void UpdateMyColor(Color color) => trail.color = color;

        public void OnPointerClick(PointerEventData eventData)
        {
            TryForwardTapToToken();
        }

        public void CockieManage()
        {
            int myChildCount = transform.GetChild(1).childCount;
            Transform transformObj = transform.GetChild(1);
            for (int i = 0; i < myChildCount; i++)
            {
                transformObj.GetChild(i).DOKill();
            }

            if (myChildCount == 1)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(0f, 0f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 301+CanvasOrder;
                transformObj.GetChild(0).transform.localScale = new Vector2(1f, 1f);
            }
            if (myChildCount == 2)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-10f, 0f);
                transformObj.GetChild(1).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(10f, 0f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 301+CanvasOrder;
                transformObj.GetChild(1).transform.GetComponent<Canvas>().sortingOrder = 302+CanvasOrder;
                transformObj.GetChild(0).transform.localScale = new Vector2(0.8f, 0.8f);
                transformObj.GetChild(1).transform.localScale = new Vector2(0.8f, 0.8f);
            }
            if (myChildCount == 3)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(0f, 0f);
                transformObj.GetChild(1).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-20f, 0f);
                transformObj.GetChild(2).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(20f, 0f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 302+CanvasOrder;
                transformObj.GetChild(1).transform.GetComponent<Canvas>().sortingOrder = 303 + CanvasOrder;
                transformObj.GetChild(2).transform.GetComponent<Canvas>().sortingOrder = 301 + CanvasOrder;
                transformObj.GetChild(0).transform.localScale = new Vector2(0.7f, 0.7f);
                transformObj.GetChild(1).transform.localScale = new Vector2(0.7f, 0.7f);
                transformObj.GetChild(2).transform.localScale = new Vector2(0.7f, 0.7f);
            }
            if (myChildCount == 4)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-20f, 0f);
                transformObj.GetChild(1).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-7f, 0f);
                transformObj.GetChild(2).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(7f, 0f);
                transformObj.GetChild(3).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(20, 0f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 301 + CanvasOrder;
                transformObj.GetChild(1).transform.GetComponent<Canvas>().sortingOrder = 302 + CanvasOrder;
                transformObj.GetChild(2).transform.GetComponent<Canvas>().sortingOrder = 303 + CanvasOrder;
                transformObj.GetChild(3).transform.GetComponent<Canvas>().sortingOrder = 304 + CanvasOrder;
                transformObj.GetChild(0).transform.localScale = new Vector2(0.6f, 0.6f);
                transformObj.GetChild(1).transform.localScale = new Vector2(0.6f, 0.6f);
                transformObj.GetChild(2).transform.localScale = new Vector2(0.6f, 0.6f);
                transformObj.GetChild(3).transform.localScale = new Vector2(0.6f, 0.6f);
            }
            if (myChildCount == 5)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-20f, 0f);
                transformObj.GetChild(1).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-7f, 0);
                transformObj.GetChild(2).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(7f, 0);
                transformObj.GetChild(3).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(20f, 0);
                transformObj.GetChild(4).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(0f, -5f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 301 + CanvasOrder;
                transformObj.GetChild(1).transform.GetComponent<Canvas>().sortingOrder = 302 + CanvasOrder;
                transformObj.GetChild(2).transform.GetComponent<Canvas>().sortingOrder = 303 + CanvasOrder;
                transformObj.GetChild(3).transform.GetComponent<Canvas>().sortingOrder = 304 + CanvasOrder;
                transformObj.GetChild(4).transform.GetComponent<Canvas>().sortingOrder = 305 + CanvasOrder;
                transformObj.GetChild(0).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(1).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(2).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(3).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(4).transform.localScale = new Vector2(0.5f, 0.5f);
            }
            if (myChildCount == 6)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-20f, 0);
                transformObj.GetChild(1).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-7f, 0);
                transformObj.GetChild(2).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(7f, 0);
                transformObj.GetChild(3).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(20f, 0);
                transformObj.GetChild(4).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-10f, -5f);
                transformObj.GetChild(5).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(10, -5f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 301 + CanvasOrder;
                transformObj.GetChild(1).transform.GetComponent<Canvas>().sortingOrder = 302 + CanvasOrder;
                transformObj.GetChild(2).transform.GetComponent<Canvas>().sortingOrder = 303 + CanvasOrder;
                transformObj.GetChild(3).transform.GetComponent<Canvas>().sortingOrder = 304 + CanvasOrder;
                transformObj.GetChild(4).transform.GetComponent<Canvas>().sortingOrder = 305 + CanvasOrder;
                transformObj.GetChild(5).transform.GetComponent<Canvas>().sortingOrder = 306 + CanvasOrder;
                transformObj.GetChild(0).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(1).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(2).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(3).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(4).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(5).transform.localScale = new Vector2(0.5f, 0.5f);
            }
            if (myChildCount == 7)
            {
                transformObj.GetChild(0).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-20f, 0);
                transformObj.GetChild(1).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-7f, 0);
                transformObj.GetChild(2).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(7f, 0);
                transformObj.GetChild(3).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(20f, 0);
                transformObj.GetChild(4).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(-15f, -5f);
                transformObj.GetChild(5).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(0, -5f);
                transformObj.GetChild(6).transform.GetComponent<RectTransform>().anchoredPosition = new Vector2(15, -5f);
                transformObj.GetChild(0).transform.GetComponent<Canvas>().sortingOrder = 301;
                transformObj.GetChild(1).transform.GetComponent<Canvas>().sortingOrder = 302;
                transformObj.GetChild(2).transform.GetComponent<Canvas>().sortingOrder = 303;
                transformObj.GetChild(3).transform.GetComponent<Canvas>().sortingOrder = 304;
                transformObj.GetChild(4).transform.GetComponent<Canvas>().sortingOrder = 305;
                transformObj.GetChild(5).transform.GetComponent<Canvas>().sortingOrder = 306;
                transformObj.GetChild(6).transform.GetComponent<Canvas>().sortingOrder = 307;
                transformObj.GetChild(0).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(1).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(2).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(3).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(4).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(5).transform.localScale = new Vector2(0.5f, 0.5f);
                transformObj.GetChild(6).transform.localScale = new Vector2(0.5f, 0.5f);
            }

        }

        private void DisableBoardRaycasts()
        {
            Graphic[] graphics = GetComponentsInChildren<Graphic>(true);
            foreach (Graphic graphic in graphics)
            {
                if (graphic == null)
                    continue;

                if (tokenHolder != null && graphic.transform.IsChildOf(tokenHolder))
                    continue;

                graphic.raycastTarget = false;
            }

            if (trail != null)
                trail.raycastTarget = false;
        }

        private void TryForwardTapToToken()
        {
            if (tokenHolder == null)
                return;

            for (int i = tokenHolder.childCount - 1; i >= 0; i--)
            {
                CoockieMovementOffline token =
                    tokenHolder.GetChild(i).GetComponent<CoockieMovementOffline>();
                if (token != null && token.TryHandleTap())
                    return;
            }
        }
    }
    public enum TokenPopupPositions
    {
        None,
        Up,
        Down,
        Left,
        Right
    }

    public enum BoxType
    {
        None,
        Star,

    }
}
