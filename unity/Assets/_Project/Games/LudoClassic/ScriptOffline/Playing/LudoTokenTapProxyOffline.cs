using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoTokenTapProxyOffline : MonoBehaviour, IPointerDownHandler, IPointerClickHandler
    {
        [SerializeField]
        private CoockieMovementOffline owner;

        [SerializeField]
        private Image proxyImage;

        public void Initialize(CoockieMovementOffline tokenOwner, Image image)
        {
            owner = tokenOwner;
            proxyImage = image;
        }

        private void LateUpdate()
        {
            if (owner == null || proxyImage == null)
                return;

            proxyImage.raycastTarget = owner.IsTapEnabled;
        }

        public void OnPointerDown(PointerEventData eventData)
        {
            owner?.TryHandleTap();
        }

        public void OnPointerClick(PointerEventData eventData)
        {
            owner?.TryHandleTap();
        }
    }
}
