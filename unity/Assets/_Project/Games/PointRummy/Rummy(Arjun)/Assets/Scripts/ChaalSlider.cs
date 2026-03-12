using AndroApps;
using DG.Tweening;
using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class ChaalSlider : MonoBehaviour
{
    public Slider slider;
    public float totalTime = 30f;
    public float timer;
    public bool ischaal;
    public Image sliderimage;
    public string id;
    public GameObject obj, obj2;
    public List<GameObject> prefab;
    public GameObject startcoinprefab;
    public Transform parent;
    public Transform parent2;
    public bool animated, begin, animated2;
    public string scenename;
    public string game;
    public string amount;
    public bool anim, start;
    public int autochaalcount;
    public bool animatedchaalovercard;
    public GameObject backcard;

    public bool showhelp;
    public GameObject placeholderglow, discardglow, finishdeskglow;
    public AudioSource coinaudio, timeraudio, chaalaudio;
    public bool callaudio, stopupdategameamount, check;
    public TextMeshProUGUI timertext;
    private bool starttimeraudio, startchaalaudio;
    public int seat;
    public float lerpSpeed = 3f;
    private float targetValue;
    public bool mychaal;
    public Transform targetPosition;
    public float duration = 1f; 
    public GameObject coinpanel;

    /// <summary>
    /// This function is called when the object becomes enabled and active.
    /// </summary>
    void OnEnable()
    {
        timer = 0;
        check = true;
    }

    IEnumerator startcoinanim()
    {
        Debug.Log("RES_Check + Start anim " + this.gameObject.transform.parent.name);
        yield return new WaitForSeconds(0.2f);
        obj.GetComponent<TeenPattiSocketManager>().firstbetcoin = false;
        Firstchaalanim();
    }

    private void Update()
    {
        if (scenename == "teenpatti")
        {
            obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
            if (obj.GetComponent<TeenPattiSocketManager>().gamestart && obj.GetComponent<TeenPattiSocketManager>().firstbetcoin && check)
            {
                Debug.Log("RES_Check + Anim Called 2 " + this.gameObject.transform.parent.name);
                check = false;
                callaudio = true;
                StartCoroutine(startcoinanim());
            }
        }


        if (ischaal)
        {
            if (scenename == "rummy" && game == "deal")
            {
                obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
                if (obj.GetComponent<DRummyConnection>().chaaltimer == 0)
                {
                    slider.gameObject.SetActive(false);
                    placeholderglow.SetActive(false);
                    discardglow.SetActive(false);
                    timertext.gameObject.SetActive(false);
                }
                else
                {
                    slider.gameObject.SetActive(true);
                    timertext.gameObject.SetActive(true);
                    //Debug.Log("RES_Value + ischaal + " + this.id);
                    animatedchaalovercard = true;
                    start = true;
                    animated = false;
                    float normalizedValue = Mathf.Clamp01((totalTime - obj.GetComponent<DRummyConnection>().chaaltimer) / totalTime);
                    timertext.text = obj.GetComponent<DRummyConnection>().chaaltimer + "";
                    slider.value = normalizedValue;
                }
            }
            else if (scenename == "rummy" && game == "pool")
            {
                obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
                if (obj.GetComponent<PoolRummyConnection>().chaaltimer == 0)
                {
                    slider.gameObject.SetActive(false);
                    placeholderglow.SetActive(false);
                    discardglow.SetActive(false);
                    timertext.gameObject.SetActive(false);
                }
                else
                {
                    slider.gameObject.SetActive(true);
                    timertext.gameObject.SetActive(true);
                    //Debug.Log("RES_Value + ischaal + " + this.id);
                    animatedchaalovercard = true;
                    start = true;
                    animated = false;

                    float normalizedValue = Mathf.Clamp01((totalTime - obj.GetComponent<PoolRummyConnection>().chaaltimer) / totalTime);
                    timertext.text = obj.GetComponent<PoolRummyConnection>().chaaltimer + "";
                    slider.value = normalizedValue;
                }
            }
            else if (scenename == "rummy" && game == "point")
            {
                obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
                if (obj.GetComponent<CachetaConnection>().chaaltimer == 0)
                {
                    slider.gameObject.SetActive(false);
                    placeholderglow.SetActive(false);
                    discardglow.SetActive(false);
                    timertext.gameObject.SetActive(false);
                }
                else
                {
                    slider.gameObject.SetActive(true);
                    timertext.gameObject.SetActive(true);
                    //Debug.Log("RES_Value + ischaal + " + this.id);
                    animatedchaalovercard = true;
                    start = true;
                    animated = false;
                    float normalizedValue = Mathf.Clamp01((totalTime - obj.GetComponent<CachetaConnection>().chaaltimer) / totalTime);
                    timertext.text = obj.GetComponent<CachetaConnection>().chaaltimer + "";
                    slider.value = normalizedValue;
                }
            }

            if (scenename == "teenpatti")
            {
                callaudio = true;
                stopupdategameamount = true;
                animated = false;
                coinaudio.gameObject.SetActive(false);
                if (obj.GetComponent<TeenPattiSocketManager>().chaaltimer == 0)
                {
                    slider.gameObject.SetActive(false);
                    timertext.gameObject.SetActive(false);
                }
                else
                {
                    slider.gameObject.SetActive(true);
                    //slider.value = 0;
                    Debug.Log("RES_Check + timer: " + obj.GetComponent<TeenPattiSocketManager>().chaaltimer);
                    //timertext.gameObject.SetActive(true);
                    //Debug.Log("RES_Value + ischaal + " + this.id);
                    //float normalizedValue = Mathf.Clamp01((totalTime - obj.GetComponent<TeenPattiSocketManager>().chaaltimer) / totalTime);
                    timertext.text = obj.GetComponent<TeenPattiSocketManager>().chaaltimer + "";
                    //slider.value = normalizedValue;
                    targetValue = Mathf.Clamp01((totalTime - obj.GetComponent<TeenPattiSocketManager>().chaaltimer) / totalTime);

                    // Smoothly interpolate the slider's value using Lerp
                    slider.value = Mathf.Lerp(slider.value, targetValue, lerpSpeed * Time.deltaTime);
                    Debug.Log("RES_Check + Chaal Timer: " + obj.GetComponent<TeenPattiSocketManager>().chaaltimer);
                    if (obj.GetComponent<TeenPattiSocketManager>().chaaltimer > 15)
                    {
                        slider.gameObject.transform.GetChild(1).GetChild(0).GetComponent<Image>().color = Color.green;
                    }
                    else if (obj.GetComponent<TeenPattiSocketManager>().chaaltimer > 5)
                    {
                        slider.gameObject.transform.GetChild(1).GetChild(0).GetComponent<Image>().color = Color.yellow;
                    }
                    else
                    {
                        slider.gameObject.transform.GetChild(1).GetChild(0).GetComponent<Image>().color = Color.red;
                    }

                    Color color = slider.gameObject.transform.GetChild(1).GetChild(0).GetComponent<Image>().color;
                    color.a = 0.5f;
                    slider.gameObject.transform.GetChild(1).GetChild(0).GetComponent<Image>().color = color;
                }
                obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
                if (id == Configuration.GetId())
                {
                    if (!startchaalaudio)
                    {
                        if (Configuration.GetSound() == "on")
                        {
                            chaalaudio.volume = 1f;
                            chaalaudio.Play();
                            startchaalaudio = true;
                        }
                        else
                        {
                            chaalaudio.volume = 0f;
                        }
                    }
                    obj.GetComponent<TeenPattiSocketManager>().canvasstart();
                    anim = true;
                }
                if (!begin)
                {
                    obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
                    obj.GetComponent<TeenPattiSocketManager>().IDtoplay.Add(obj.GetComponent<TeenPattiSocketManager>().TPresponseData.chaal);
                    begin = true;
                }

                if (timer >= 15f && !starttimeraudio)
                {
                    if (Configuration.GetSound() == "on")
                    {
                        timeraudio.volume = 1f;
                        timeraudio.Play();
                        starttimeraudio = true;
                    }
                    else
                    {
                        timeraudio.volume = 0f;
                    }
                }

            }
            else if (scenename == "rummy")
            {
                if (id == Configuration.GetId())
                {
                    if (showhelp)
                    {
                        placeholderglow.SetActive(true);
                        discardglow.SetActive(true);
                        showhelp = false;
                    }
                }
            }

            if (timer >= 29f)
            {
                if (scenename == "teenpatti")
                {
                    obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
                    if (Configuration.GetId() == id)
                        obj.GetComponent<TeenPattiSocketManager>().leave();
                    timer = 0f;
                }
            }
            else if (timer >= 29f)
            {
                if (scenename == "rummy" && game == "deal")
                {
                    timer = 0f;
                }
                if (scenename == "rummy" && game == "pool")
                {
                    timer = 0f;
                }
            }
        }
        else
        {
            Debug.Log("RES_Check + ischaal false Called");
            if (scenename == "Ludo")
            {
                slider.gameObject.SetActive(false);
                timertext.gameObject.SetActive(false);
                //this.gameObject.transform.GetChild(2).gameObject.SetActive(false);
            }
            if (scenename == "teenpatti")
            {
                Debug.Log("RES_Check + ischaal false in teenpatti Called");
                timeraudio.Stop();
                starttimeraudio = false;
                startchaalaudio = false;
                obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
                if (id == Configuration.GetId())
                {
                    obj.GetComponent<TeenPattiSocketManager>().stopcanvas();
                    // Debug.Log("RES_Check + amount " + obj.GetComponent<TeenPattiSocketManager>().TPresponseData.game_log[0].amount);
                    // anim = false;
                    // chaalanim();
                }
                totalTime = 30f;
                timer = 0f;
                slider.value = 0;
                timertext.gameObject.SetActive(false);
                slider.gameObject.SetActive(false);
                Debug.Log("RES_Check + slider false update");
                begin = false;
            }

            if (scenename == "rummy" && game == "point")
            {
                timertext.gameObject.SetActive(false);
                if (id == Configuration.GetId())
                {
                    placeholderglow.SetActive(false);
                    discardglow.SetActive(false);
                }
                showhelp = true;
                obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
                obj2 = GameObject.Find("GameManager");
                if (start)
                {
                    if (animatedchaalovercard && id != Configuration.GetId())
                    {
                        animatedchaalovercard = false;
                        StartCoroutine(movecardafterchaal(backcard, 0.5f));
                    }
                    if (Configuration.GetId() == id && obj2.GetComponent<GameManager>().spawnedCards.Count == 14)
                    {
                        if (obj2.GetComponent<GameManager>().finishdeskcard != null)
                        {
                            if (obj2.GetComponent<GameManager>().drawnard == obj2.GetComponent<GameManager>().finishdeskcard)
                            {
                                Destroy(obj2.GetComponent<GameManager>().finishdeskcard);
                                obj2.GetComponent<GameManager>().finishno();
                                StartCoroutine(isfinishcardPoint());
                            }
                            else
                            {
                                obj2.GetComponent<GameManager>().finishno();
                                StartCoroutine(isfinishcardPoint());
                            }
                        }
                        else
                        {
                            obj2.GetComponent<GameManager>().AutoDiscardCard(obj2.GetComponent<GameManager>().drawnard);
                        }
                    }
                }
                totalTime = 30f;
                timer = 0f;
                slider.gameObject.SetActive(false);
                begin = false;
            }
            else if (scenename == "rummy" && game == "deal")
            {
                timertext.gameObject.SetActive(false);
                if (id == Configuration.GetId())
                {
                    placeholderglow.SetActive(false);
                    discardglow.SetActive(false);
                }
                showhelp = true;
                obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
                obj2 = GameObject.Find("GameManager");
                if (start)
                {
                    if (animatedchaalovercard && id != Configuration.GetId())
                    {
                        animatedchaalovercard = false;
                        StartCoroutine(movecardafterchaal(backcard, 0.5f));
                    }
                    if (Configuration.GetId() == id && obj2.GetComponent<GameManager_Deal>().spawnedCards.Count == 14)
                    {
                        if (obj2.GetComponent<GameManager_Deal>().finishdeskcard != null)
                        {
                            if (obj2.GetComponent<GameManager_Deal>().drawnard == obj2.GetComponent<GameManager_Deal>().finishdeskcard)
                            {
                                Destroy(obj2.GetComponent<GameManager_Deal>().finishdeskcard);
                                obj2.GetComponent<GameManager_Deal>().finishno();
                                StartCoroutine(isfinishcardPool());
                            }
                            else
                            {
                                obj2.GetComponent<GameManager_Deal>().finishno();
                                StartCoroutine(isfinishcardPool());
                            }
                        }
                        else
                        {
                            obj2.GetComponent<GameManager_Deal>().AutoDiscardCard(obj2.GetComponent<GameManager_Deal>().drawnard);
                        }
                    }
                    //foreach (GameObject obj in obj2.GetComponent<GameManager_Deal>().discardedlist)
                    //{
                    //    obj.transform.position = GameObject.Find("Discard_Area").transform.position;
                    //}
                }
                totalTime = 30f;
                timer = 0f;
                slider.gameObject.SetActive(false);
                begin = false;
            }
            else if (scenename == "rummy" && game == "pool")
            {
                timertext.gameObject.SetActive(false);
                if (id == Configuration.GetId())
                {
                    placeholderglow.SetActive(false);
                    discardglow.SetActive(false);
                }
                showhelp = true;
                obj = GameObject.Find("Manager").transform.GetChild(0).gameObject;
                obj2 = GameObject.Find("GameManager");
                if (start)
                {
                    if (animatedchaalovercard && id != Configuration.GetId())
                    {
                        animatedchaalovercard = false;
                        StartCoroutine(movecardafterchaal(backcard, 0.5f));
                    }
                    if (Configuration.GetId() == id && obj2.GetComponent<GameManager_Pool>().spawnedCards.Count == 14)
                    {
                        if (obj2.GetComponent<GameManager_Pool>().finishdeskcard != null)
                        {
                            if (obj2.GetComponent<GameManager_Pool>().drawnard == obj2.GetComponent<GameManager_Pool>().finishdeskcard)
                            {
                                Destroy(obj2.GetComponent<GameManager_Pool>().finishdeskcard);
                                obj2.GetComponent<GameManager_Pool>().finishno();
                                StartCoroutine(isfinishcardPool());
                            }
                            else
                            {
                                obj2.GetComponent<GameManager_Pool>().finishno();
                                StartCoroutine(isfinishcardPool());
                            }
                        }
                        else
                        {
                            obj2.GetComponent<GameManager_Pool>().AutoDiscardCard(obj2.GetComponent<GameManager_Pool>().drawnard);
                        }
                    }
                    //foreach (GameObject obj in obj2.GetComponent<GameManager_Pool>().discardedlist)
                    //{
                    //    obj.transform.position = GameObject.Find("Discard_Area").transform.position;
                    //}
                }
                totalTime = 30f;
                timer = 0f;
                slider.gameObject.SetActive(false);
                begin = false;
            }
        }
    }

    IEnumerator isfinishcardPool()
    {
        yield return new WaitForSeconds(0.5f);
        obj2.GetComponent<GameManager_Pool>().AutoDiscardCard(obj2.GetComponent<GameManager_Pool>().drawnard);
        obj2.GetComponent<GameManager_Pool>().declaredialogue.SetActive(false);
    }

    IEnumerator isfinishcardDeal()
    {
        yield return new WaitForSeconds(0.5f);
        obj2.GetComponent<GameManager_Deal>().AutoDiscardCard(obj2.GetComponent<GameManager_Pool>().drawnard);
        obj2.GetComponent<GameManager_Deal>().declaredialogue.SetActive(false);
    }

    IEnumerator isfinishcardPoint()
    {
        yield return new WaitForSeconds(0.5f);
        obj2.GetComponent<GameManager>().AutoDiscardCard(obj2.GetComponent<GameManager_Pool>().drawnard);
        obj2.GetComponent<GameManager>().declaredialogue.SetActive(false);
    }

    IEnumerator destroycoin(GameObject ob)
    {
        if (callaudio)
        {
            if (Configuration.GetSound() == "on")
            {
                coinaudio.volume = 1f;
            }
            else
            {
                coinaudio.volume = 0f;
            }
            coinaudio.gameObject.SetActive(true);
            callaudio = false;
        }
        obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
        yield return new WaitForSeconds(0.8f);
        obj.GetComponent<TeenPattiSocketManager>().amounttext.text = obj.GetComponent<TeenPattiSocketManager>().TPresponseData.game_amount;
        //Destroy(ob);
    }

    void ChangeColor(string hexColor)
    {
        // Convert the hexadecimal string to a Color object
        Color newColor = HexToColor(hexColor);

        // Set the new color to the image
        sliderimage.color = newColor;
    }

    public IEnumerator movecardafterchaal(GameObject card, float duration)
    {
        GameObject obj = Instantiate(card);
        obj.transform.parent = this.transform;
        obj.transform.localScale = new Vector3(0.15f, 0.13f, 0);
        obj.transform.position = new Vector3(0, 0, 0);
        obj.transform.localPosition = new Vector3(0, 0, 0);
        obj.GetComponent<SpriteRenderer>().sortingOrder = 20;
        float elapsed = 0f;
        Vector3 startPosition = obj.transform.position;
        while (elapsed < duration)
        {
            obj.transform.position = Vector3.Lerp(startPosition, GameObject.Find("Discard_Area").transform.position
                , elapsed / duration);
            elapsed += Time.deltaTime;
            yield return null;
        }

        obj.transform.position = GameObject.Find("Discard_Area").transform.position;
        Destroy(obj);
    }

    public void Firstchaalanim()
    {
        Debug.Log("RES_Check + Start Chaal anim " + this.gameObject.transform.name);
        obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
        GameObject coin = Instantiate(startcoinprefab, parent);
        coin.transform.localPosition = Vector3.zero;
        coin.transform.DOMove(targetPosition.position, duration).SetEase(Ease.Linear);
        coin.transform.DOScale(Vector3.zero, duration).SetEase(Ease.Linear);
        StartCoroutine(startcoinpanel());
    }

    IEnumerator startcoinpanel()
    {
        yield return new WaitForSeconds(0.3f);
        if(id == Configuration.GetId())
        {
            coinpanel.transform.DOScale(new Vector3(0.01f, 0.01f, 0.01f), 0.4f).SetEase(Ease.Linear);
        }
    }

    public void chaalanim()
    {
        Debug.Log("RES_Check + Start Chaal anim " + this.gameObject.transform.name);
        obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
        Debug.Log("RES_Check + Anim");
        Debug.Log("RES_Check + animated " + animated);
        Debug.Log("RES_Check + clickedpack " + obj.GetComponent<TeenPattiSocketManager>().clickedpack);
        Debug.Log("RES_Check + gamestart " + obj.GetComponent<TeenPattiSocketManager>().gamestart);
        if (!this.animated && obj.GetComponent<TeenPattiSocketManager>().gamestart)
        {
            Debug.Log("RES_Check + Start instantiate " + this.gameObject.transform.name);
            GameObject coin = Instantiate(prefab[Random.Range(0, prefab.Count)], parent);
            //coin.transform.parent = parent;
            coin.transform.position = Vector3.zero;
            coin.GetComponent<CoinAmount>().coinamount.text = obj.GetComponent<TeenPattiSocketManager>().TPresponseData.game_log[0].amount;
            //coin.GetComponent<Coinanim>().amount = amount;
            StartCoroutine(destroycoin(coin));
            animated = true;
            //obj.GetComponent<TeenPattiSocketManager>().gamestart = false;
        }
    }

    public void chaalblindtextanim(GameObject prefabobj)
    {
        obj = GameObject.Find("Manager (1)").transform.GetChild(0).gameObject;
        if ((!animated2 && (obj.GetComponent<TeenPattiSocketManager>().clickedpack == false)) && obj.GetComponent<TeenPattiSocketManager>().gamestart == true)
        {
            GameObject coin = Instantiate(prefabobj, parent2);
            //coin.transform.parent = parent;
            coin.transform.position = Vector3.zero;
            //coin.GetComponent<Coinanim>().amount = amount;
            //StartCoroutine(destroycoin(coin));
            animated2 = true;
        }
    }

    // Function to convert a hexadecimal string to a Color object
    Color HexToColor(string hex)
    {
        Color color = Color.white;
        ColorUtility.TryParseHtmlString(hex, out color);
        return color;
    }
}
