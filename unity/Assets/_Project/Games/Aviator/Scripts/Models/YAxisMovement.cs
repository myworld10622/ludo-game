using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class YAxisMovement : MonoBehaviour
{
    public float speed = 5f;

    private bool shouldMove;

    public GameObject obj;

    public bool spawn;

    public int yaxis = -25;

    public float amount = 1.8f;

    public Animator plane;

    public GameObject planeimg;

    public GameObject xaxisanimation,
        yaxisanimation;

    public GameObject explodefollow,
        blast,
        line;

    public int burstvalue;

    public static bool blasted = false;

    public List<GameObject> yaxisobjs;

    public List<GameObject> xaxisobjs;

    public XaxisMovement xaxis;

    private Vector3 yaxispos,
        xaxispos,
        followobjpos,
        blastobjpos;

    private bool start_game;

    public GameObject planeobj,
        lineobj,
        followobj,
        blastobj;

    public Sprite planesprite,
        lineprite,
        followsprite,
        blastsprite;

    public AviatorManager socket;
    public AviatorHistory history;

    public int seconds;

    public void backtomainmenu()
    {
        SceneManager.LoadScene("Main");
    }

    IEnumerator spawnnewtext()
    {
        while (!blasted)
        {
            yaxis += 75;
            amount += 0.2f;
            GameObject go = Instantiate(obj, this.transform);
            if (IsInteger(amount))
                go.GetComponent<TextMeshProUGUI>().text = amount.ToString("0.0") + "x";
            else
                go.GetComponent<TextMeshProUGUI>().text = amount + "x";
            go.transform.localPosition = new Vector3(-27, yaxis, 0);
            yaxisobjs.Add(go);
            yield return new WaitForSeconds(2.5f);
        }
    }

    bool IsInteger(float number)
    {
        return Mathf.Approximately(number, Mathf.Round(number));
    }

    void Start()
    {
        yaxispos = this.gameObject.transform.position;
        xaxispos = xaxis.gameObject.transform.position;
        followobjpos = followobj.transform.position;
        blastobjpos = blastobj.transform.position;
        planeimg.GetComponent<Animator>().enabled = false;
        line.GetComponent<Animator>().enabled = false;
        blast.GetComponent<Animator>().enabled = false;
        explodefollow.GetComponent<Animator>().enabled = false;
    }

    public void burstanim()
    {
        blasted = true;

        RectTransform trans = explodefollow.GetComponent<RectTransform>();
        planeimg.GetComponent<UnityEngine.UI.Image>().enabled = false;
        planeimg.GetComponent<Animator>().enabled = false;

        line.GetComponent<Animator>().enabled = false;
        line.GetComponent<Animator>().speed = 0;
        line.GetComponent<Image>().enabled = true;

        explodefollow.GetComponent<Animator>().speed = 0;
        explodefollow.GetComponent<Animator>().enabled = false;

        blast.transform.localPosition = trans.localPosition;
        blast.GetComponent<Animator>().enabled = true;
        StartCoroutine(reseteverything());
    }

    IEnumerator reseteverything()
    {
        history.GetBlastHistory();
        history.GetGameHistory();
        history.GetMyGameHistory();
        yield return new WaitForSeconds(2.30f);
        socket.amount.transform.parent.gameObject.SetActive(false);
        socket.waiting_text.SetActive(true);
        socket.plane_wait.SetActive(true);
        if (yaxisobjs.Count != 0)
        {
            foreach (var go in yaxisobjs)
            {
                Destroy(go);
                yaxisobjs.Clear();
            }
        }
        if (xaxisobjs.Count != 0)
        {
            foreach (var go in xaxisobjs)
            {
                Destroy(go);
                xaxisobjs.Clear();
            }
        }
        this.transform.position = yaxispos;
        xaxis.transform.position = xaxispos;

        followobj.transform.position = followobjpos;
        blastobj.transform.position = blastobjpos;

        planeobj.GetComponent<UnityEngine.UI.Image>().sprite = planesprite;
        lineobj.GetComponent<UnityEngine.UI.Image>().sprite = lineprite;
        followobj.GetComponent<UnityEngine.UI.Image>().sprite = followsprite;
        blastobj.GetComponent<UnityEngine.UI.Image>().sprite = blastsprite;

        line.GetComponent<Image>().enabled = false;

        seconds = int.Parse(socket.time) - 1;

        float starttime = 20 - seconds;
        Debug.Log("RES_Check + Time to start " + starttime);
        socket.countdown_image.GetComponent<Animator>().Play("Timer_Anim");
        socket.countdown_image.sprite = socket.countdown_sprite[seconds];
        socket.countdown_image.GetComponent<Animator>().speed = 1f;

        while (seconds > 0)
        {
            yield return new WaitForSeconds(1f);
            seconds -= 1;
            socket.countdown_image.sprite = socket.countdown_sprite[seconds];
            Debug.Log("RES_Check + seconds " + seconds);
        }

        Debug.Log("RES_Check + completed");

        start_game = false;
    }

    void Update()
    {
        if (socket.game_start && !start_game)
        {
            socket.waiting_text.SetActive(false);
            socket.plane_wait.SetActive(false);
            start_game = true;
            StartCoroutine(socket.Calc());
            planeimg.GetComponent<UnityEngine.UI.Image>().enabled = true;
            planeimg.GetComponent<Animator>().Play("RocketFly", -1, 0f);
            planeimg.GetComponent<Animator>().enabled = true;

            line.GetComponent<Animator>().Play("lineanim", -1, 0f);
            line.GetComponent<Animator>().enabled = true;
            line.GetComponent<Animator>().speed = 1;

            explodefollow.GetComponent<Animator>().Play("follow", -1, 0f);
            explodefollow.GetComponent<Animator>().enabled = true;
            explodefollow.GetComponent<Animator>().speed = 1;

            blast.GetComponent<Animator>().enabled = false;
            blast.GetComponent<Animator>().Play("blastanim", -1, 0f);
        }

        if (!shouldMove)
            return;

        if (!blasted)
            transform.Translate(Vector3.down * speed * Time.deltaTime);
    }
}
