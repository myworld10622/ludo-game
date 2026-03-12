using System.Collections;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class SceneRoulette : MonoBehaviour
{
    public static SceneRoulette _Instance;

    public static int uiState = 0; // popup window shows or not

    public BetPool pool;
    public EuropeanWheel _EuroWheel; // slot game clase
    public AmericanWheel _AmeWheel; // slot game clase

    [Space]
    [Header("Text")]
    public TMP_Text textBalance; // user balance info
    public TMP_Text textBet; // user bet info
    public TMP_Text resultText; // result info

    [Space]
    [Header("UI")]
    public Button clearButton;
    public Button undoButton;
    public Button rebetButton;
    public Button rollButton;

    public Slider volumeSlider;

    [Space]
    [Header("Extra")]
    public CameraController camCtrl;
    public static float WaitTime;
    public static bool GameStarted = false;
    public static bool MenuOn = false;

    void Awake()
    {
        _Instance = this;
    }

    private void Start()
    {
        BalanceManager.SetBalance(1000);
    }

    public void MessageQuitResult(int value)
    {
        if (value == 0)
        {
            Application.Quit();
        }
    }

    public void OnButtonClear()
    {
        RouletteAudioManager.SoundPlay(3);
        clearButton.interactable = false;
        rollButton.interactable = false;
        pool.Clear();
    }

    public void OnButtonUndo()
    {
        undoButton.interactable = false;
        RouletteAudioManager.SoundPlay(3);
        pool.Undo();
    }

    public void OnButtonRebet()
    {
        rebetButton.gameObject.SetActive(false);
        //StartCoroutine(pool.Rebet());
    }

    public void OnButtonRoll()
    {
        undoButton.interactable = false;
        clearButton.interactable = false;
        rollButton.interactable = false;
        //resultText.text = "";
        Debug.Log("RES_Check + Spin");
        SpinRoulette();
    }

    public void SpinRoulette()
    {
        if (_EuroWheel != null)
            _EuroWheel.Spin();
        else if (_AmeWheel != null)
            _AmeWheel.Spin();

        ChangeUI();
        RouletteAudioManager.SoundPlay(2);
    }

    public void ChangeUI()
    {
        if (camCtrl != null)
            camCtrl.GoToTarget();
        //ToolTipManager.Deselect();
        clearButton.interactable = false;
        undoButton.interactable = false;
        rebetButton.gameObject.SetActive(false);
        rollButton.interactable = false;
        ChipManager.EnableChips(false);
    }

    public void BlockBets()
    {
        MenuOn = true;
        BetSpace.EnableBets(false);
    }

    public void ReleaseBets()
    {
        MenuOn = false;
        BetSpace.EnableBets(!GameStarted);
    }

    public static void UpdateLocalPlayerText()
    {
        _Instance.textBet.text = "Bet: " + ResultManager.totalBet.ToString("F2");
        _Instance.textBalance.text = BalanceManager.Balance.ToString("F2");
    }

    #region
    public void buttonclickedbg(GameObject panel)
    {
        panel.SetActive(true);
    }

    public void buttonclick(GameObject panel)
    {
        Vector3 originalScale = new Vector3();
        originalScale = panel.transform.localScale;
        panel.transform.localScale = Vector3.zero; // Set initial scale to 0

        panel.SetActive(true); // Activate the panel
        panel.transform.localScale = Vector3.zero; // Start from scale 0
        panel.transform.DOScale(originalScale, 0.6f).SetEase(Ease.OutBack); // Animate scale from 0 to 1 with easing
    }

    public void buttoncancel(GameObject panel)
    {
        StartCoroutine(cancelbuttonclick(panel));
    }

    public IEnumerator cancelbuttonclick(GameObject panel)
    {
        Vector3 originalScale = new Vector3();
        originalScale = panel.transform.localScale;

        panel.transform.localScale = originalScale; // Start from actual scale
        panel.transform.DOScale(Vector3.zero, 0.6f).SetEase(Ease.InBack); // Animate scale from 0 to 1 with easing
        yield return new WaitForSeconds(0.6f);
        panel.SetActive(false);
        //panel.GetComponentInParent<GameObject>().SetActive(false);
        panel.transform.localScale = originalScale;
    }
    #endregion
}
