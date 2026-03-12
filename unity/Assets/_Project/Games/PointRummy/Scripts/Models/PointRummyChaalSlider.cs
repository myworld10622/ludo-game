using System.Collections;
using System.Threading.Tasks;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class PointRummyChaalSlider : MonoBehaviour
{
    public Slider slider;
    public float totalTime = 30f;
    private float timer;
    public bool ischaal;
    public string id;
    private GameObject obj,
        obj2;
    public bool animated,
        begin;
    public bool start;
    public bool animatedchaalovercard;
    public GameObject backcard;
    private CachetaConnection cachetaConnection;
    public bool showhelp;
    public TextMeshProUGUI timertext;

    void OnEnable()
    {
        timer = 0;
        slider.gameObject.SetActive(false);
        timertext.gameObject.SetActive(false);
    }

    // Call this method from another script when ischaal is set to true

    public void StartChaal()
    {
        start = true;
        obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;

        cachetaConnection = obj.GetComponent<CachetaConnection>();

        StartCoroutine(startchaalslider());
    }

    public IEnumerator startchaalslider()
    {
        CommonUtil.CheckLog("Timer called 2");

        ischaal = true;
        if (cachetaConnection.waitforchaal)
        {
            cachetaConnection.waitforchaal = false;
            yield return new WaitForSeconds(5);
            CommonUtil.CheckLog("Timer called 3");
            animatedchaalovercard = true;
            slider.gameObject.SetActive(true);
            timertext.gameObject.SetActive(true);

            timer = 30;

            // Start the repeating timer update method
            InvokeRepeating("UpdateTimer", 0f, 1f); // Call UpdateTimer every second
        }
        else
        {
            yield return new WaitForSeconds(1);
            CommonUtil.CheckLog("Timer called 4");
            animatedchaalovercard = true;
            slider.gameObject.SetActive(true);
            timertext.gameObject.SetActive(true);

            timer = 30;

            // Start the repeating timer update method
            InvokeRepeating("UpdateTimer", 0f, 1f); // Call UpdateTimer every second
        }
    }

    // Stop the slider when ischaal becomes false
    public void StopChaal()
    {
        ischaal = false;

        CancelInvoke("UpdateTimer");

        slider.gameObject.SetActive(false);
        timertext.gameObject.SetActive(false);

        ResetChaal();
    }

    private void UpdateTimer()
    {
        // Update the chaalTimer value
        float chaalTimer = cachetaConnection.chaaltimer;

        if (chaalTimer == 29)
        {
            timer = chaalTimer;
        }

        if (Configuration.GetId() == id)
            CommonUtil.CheckLog("my Chaal");

        // Check for stop condition
        if (timer <= 0 && Configuration.GetId() == id)
        {
            StopChaal();
            CancelInvoke("UpdateTimer"); // Stop the repeating method
            return;
        }

        // Update the slider value
        float normalizedValue = Mathf.Clamp01((totalTime - chaalTimer) / totalTime);
        slider.value = normalizedValue;
        timertext.text = chaalTimer.ToString("0");
    }

    private void ResetChaal()
    {
        Debug.Log("RES_Check + ischaal false Called");
        timertext.gameObject.SetActive(false);
        showhelp = false;

        obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
        obj2 = GameObject.Find("GameManager");

        if (start)
        {
            if (animatedchaalovercard && id != Configuration.GetId())
            {
                animatedchaalovercard = false;
                StartCoroutine(MoveCardAfterChaal(backcard, 0.5f));
            }

            if (
                Configuration.GetId() == id
                && obj2.GetComponent<GameManager>().spawnedCards.Count == 14
            )
            {
                CommonUtil.CheckLog("Auto Chaal");
                HandleGameManagerLogic();
            }
        }

        totalTime = 30f;
        timer = 0f;
        slider.gameObject.SetActive(false);
        begin = false;
    }

    private void HandleGameManagerLogic()
    {
        if (obj2.GetComponent<GameManager>().finishdeskcard != null)
        {
            if (
                obj2.GetComponent<GameManager>().drawnard
                == obj2.GetComponent<GameManager>().finishdeskcard
            )
            {
                Destroy(obj2.GetComponent<GameManager>().finishdeskcard);
                obj2.GetComponent<GameManager>().finishno();
                StartCoroutine(IsFinishCardPoint());
            }
            else
            {
                obj2.GetComponent<GameManager>().finishno();
                StartCoroutine(IsFinishCardPoint());
            }
        }
        else
        {
            obj2.GetComponent<GameManager>()
                .AutoDiscardCard(obj2.GetComponent<GameManager>().drawnard);
        }
    }

    IEnumerator IsFinishCardPoint()
    {
        yield return new WaitForSeconds(0.5f);
        obj2.GetComponent<GameManager>()
            .AutoDiscardCard(obj2.GetComponent<GameManager_Pool>().drawnard);
        obj2.GetComponent<GameManager>().declaredialogue.SetActive(false);
    }

    public IEnumerator MoveCardAfterChaal(GameObject card, float duration)
    {
        GameObject obj = Instantiate(card, this.transform);
        obj.transform.localScale = new Vector3(0.15f, 0.13f, 0);
        obj.transform.localPosition = Vector3.zero;
        obj.GetComponent<SpriteRenderer>().sortingOrder = 20;

        obj.transform.DOMove(GameObject.Find("Discard_Area").transform.position, duration)
            .OnComplete(() => Destroy(obj));

        yield return null;
    }
}
