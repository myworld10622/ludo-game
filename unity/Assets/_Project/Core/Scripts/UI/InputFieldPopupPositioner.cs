using UnityEngine;
using UnityEngine.UI;
using TMPro;
using DG.Tweening;
using UnityEngine.EventSystems;

namespace AndroApps.UI
{
    /// <summary>
    /// Moves a UI element (like a popup or input field container) upwards when an InputField is focused.
    /// This prevents the mobile keyboard from obscuring the field.
    /// </summary>
    public class InputFieldPopupPositioner : MonoBehaviour, ISelectHandler, IDeselectHandler
    {
        public RectTransform popup; 
        public float extraMoveUp = 0f;

        private RectTransform inputRect;
        private Canvas canvas;

        private Vector2 originalPos;
        private bool isFocused;

        [Header("Settings")]
        private float padding = 120f; // extra space above keyboard
        private float duration = 0.15f;

        void Start()
        {
            inputRect = GetComponent<RectTransform>();
            canvas = GetComponentInParent<Canvas>();
            originalPos = popup.anchoredPosition;

            TMP_InputField input = GetComponent<TMP_InputField>();
            if (input != null)
            {
                input.onSubmit.AddListener(OnSubmit);
            }
        }

        private void Update()
        {
            if (isFocused && !IsKeyboardOpen())
            {
                isFocused = false;

                popup.DOKill();
                popup.anchoredPosition = originalPos; // force reset instantly
            }
        }

        public void OnSelect(BaseEventData eventData)
        {
            isFocused = true;

            popup.DOKill(); // stop previous movement
            Adjust();
        }

        public void OnDeselect(BaseEventData eventData)
        {
            isFocused = false;
            ResetPos();
        }
        void OnSubmit(string value)
        {
            isFocused = false;
            ResetPos();
        }

        bool IsKeyboardOpen()
        {
#if UNITY_EDITOR
            return false;
#else
    return TouchScreenKeyboard.visible;
#endif
        }

        void Adjust()
        {
            float keyboardHeight = GetKeyboardHeight();
            if (keyboardHeight <= 0) return;

            Vector3[] corners = new Vector3[4];
            inputRect.GetWorldCorners(corners);

            float fieldBottom = corners[0].y;
            float fieldTop = corners[1].y;

            if (canvas.renderMode != RenderMode.ScreenSpaceOverlay && canvas.worldCamera != null)
            {
                fieldBottom = RectTransformUtility.WorldToScreenPoint(canvas.worldCamera, corners[0]).y;
                fieldTop = RectTransformUtility.WorldToScreenPoint(canvas.worldCamera, corners[1]).y;
            }

            float screenTopLimit = Screen.height - padding; // keep space from top
            float keyboardTop = keyboardHeight + padding;

            float moveUp = 0f;

            // 👉 Condition 1: bottom hidden behind keyboard
            if (fieldBottom < keyboardTop)
            {
                moveUp = keyboardTop - fieldBottom;
            }

            // 👉 Condition 2: top going outside screen
            if (fieldTop + moveUp > screenTopLimit)
            {
                moveUp -= (fieldTop + moveUp - screenTopLimit);
            }

            if (moveUp > 0)
            {
                float finalMove = moveUp / canvas.scaleFactor;

                float currentY = popup.anchoredPosition.y;
                float targetY = currentY + finalMove;

                targetY = Mathf.Max(originalPos.y, targetY);

                popup.DOKill();

                popup.DOAnchorPosY((targetY + extraMoveUp), duration)
                     .SetEase(Ease.OutCubic)
                     .SetUpdate(true);
            }
            else
            {
                ResetPos();
            }
        }

        void ResetPos()
        {
            popup.DOAnchorPos(originalPos, duration)
                 .SetEase(Ease.OutCubic)
                 .SetUpdate(true);
        }

        float GetKeyboardHeight()
        {
#if UNITY_EDITOR
            return Screen.height * 0.4f;
#else
        if (TouchScreenKeyboard.visible)
        {
            float h = TouchScreenKeyboard.area.height;
            if (h > 0) return h;
        }
        return Screen.height * 0.4f;
#endif
        }
    }
}
