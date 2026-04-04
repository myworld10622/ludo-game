using DG.Tweening;
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

        // Cached component references — never call GetComponent in Update/InvokeRepeating
        private Image _timerFillImage;
        private Image _timerIconImage;
        private Transform _timerTransform;
        private Tween _pulseTween;
        private bool _isCritical;

        // Consecutive skip tracking — reset when any player actually rolls
        private int _consecutiveSkips;

        #endregion
        private void OnEnable()
        {
            PlayerIndex = 0;
        }

        private void Awake()
        {
            CacheComponents();
        }

        private void CacheComponents()
        {
            if (_timerFillImage == null)
                _timerFillImage = AllPlayerTimerImage.GetComponent<Image>();
            if (_timerIconImage == null)
                _timerIconImage = AllPlayerTimerImage.transform.GetChild(0).GetComponent<Image>();
            if (_timerTransform == null)
                _timerTransform = AllPlayerTimerImage.transform;
        }

        public void Reapet(float _remainTime, float _turnTime)
        {
            CacheComponents();
            turnTime = _turnTime;
            remainTime = _remainTime;
            _isCritical = false;
            AllPlayerTimerImage.gameObject.SetActive(true);
            _timerFillImage.color = Color.green;
            _pulseTween?.Kill();
            _timerTransform.localScale = Vector3.one;
            if (this.gameObject.activeSelf == true)
                InvokeRepeating(nameof(TurnTimeStart), 0f, 0.02f);

        }

        public void TurnDataReset()
        {
            CancelInvoke(nameof(TurnTimeStart));
            _pulseTween?.Kill();
            if (_timerTransform != null)
                _timerTransform.localScale = Vector3.one;
            _isCritical = false;
            AllPlayerTimerImage.gameObject.SetActive(false);
            // Player actually rolled — reset consecutive skip counter
            _consecutiveSkips = 0;
        }

        float turnTime;
        float remainTime;
        public void TurnTimeStart()
        {
            float fill = remainTime / turnTime;
            _timerFillImage.fillAmount = fill;
            remainTime -= 0.02f;
            AllPlayerTimerImage.gameObject.SetActive(true);

            if (fill <= 0.3f && !_isCritical)
            {
                // Enter critical state once — smooth color transition + pulsing scale loop
                _isCritical = true;
                IsPlayerTurn = true;
                if (!isSound && socketNumberEventReceiver.userTurnStart.data.startTurnSeatIndex == socketNumberEventReceiver.signUpResponce.data.thisPlayerSeatIndex)
                {
                    // TimeCount();
                }
                _timerIconImage.sprite = red;
                _timerFillImage.DOColor(Color.red, 0.25f);
                _pulseTween?.Kill();
                _pulseTween = _timerTransform
                    .DOScale(1.15f, 0.3f)
                    .SetEase(Ease.InOutSine)
                    .SetLoops(-1, LoopType.Yoyo);
            }

            if (fill <= 0)
            {
                _pulseTween?.Kill();
                _timerTransform.localScale = Vector3.one;
                _timerFillImage.fillAmount = 1;
                _isCritical = false;
                AllPlayerTimerImage.gameObject.SetActive(false);
                SoundManagerOffline.instance?.TimeSoundStop(SoundManagerOffline.instance.timerAudio);
                CancelInvoke(nameof(TurnTimeStart));
                PlayerIndex += 1;
                TotalTime = 10;
                isSound = false;

                if (socketNumberEventReceiver?.ludoNumberGsNew != null
                    && !socketNumberEventReceiver.ludoNumberGsNew.tokenMovement)
                {
                    _consecutiveSkips++;
                    int maxPlayers = Mathf.Max(2, socketNumberEventReceiver.maxPlayer);
                    // Cancel match if every active player has skipped twice (maxPlayers * 2)
                    if (_consecutiveSkips >= maxPlayers * 2)
                    {
                        _consecutiveSkips = 0;
                        CommonUtil.ShowToast("Match cancelled: all players inactive");
                        if (DashBoardManagerOffline.instance != null)
                            DashBoardManagerOffline.instance.ClickOnLudoGameExitBtn();
                        return;
                    }
                    socketNumberEventReceiver.ludoNumberGsNew.ChangeTurnSeatIndex();
                }
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