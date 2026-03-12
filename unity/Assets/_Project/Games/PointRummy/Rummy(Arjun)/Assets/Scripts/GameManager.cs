using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using AndroApps;
using DG.Tweening;
using EasyUI.Toast;
using TMPro;
using UnityEditor;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using UnityEngine.UIElements;

public class GameManager : MonoBehaviour
{
    #region variables
    [Header("Iniialization")]
    public CachetaConnection cachetmanager;
    public List<string> names;
    public List<GameObject> setFromAPI;
    public List<GameObject> mycards;
    public Transform spawnPoint;
    public Sprite backcard;

    [Header("Cards")]
    public List<GameObject> deck;

    [Header("GameCards")]
    public List<GameObject> spawnedCards;
    public List<GameObject> sortedspawnedCards;

    [Header("Active Game's Card Sprites")]
    public List<GameObject> spritetochange;
    public List<Sprite> sprites;

    [Header("Card Groups")]
    public List<GameObject> list1 = new List<GameObject>();
    public List<GameObject> list2 = new List<GameObject>();
    public List<GameObject> list3 = new List<GameObject>();
    public List<GameObject> list4 = new List<GameObject>();
    public List<GameObject> list5 = new List<GameObject>();
    public List<GameObject> list6 = new List<GameObject>();
    public List<GameObject> obj = new List<GameObject>();

    [Header("Locations")]
    public List<Transform> cardPositions;
    public List<Vector3> initializedcardPositions;
    public float moveSpeed = 5f;

    [Header("Handle current card")]
    public GameObject draggedCard;
    public GameObject discardCard;
    public GameObject discradarea;
    public List<GameObject>[] lists;
    public GameObject GroupcardPrefab;

    [Header("Chaal and correct cards check")]
    public TextMeshProUGUI warning;

    [Header("On single click")]
    public List<GameObject> uplist = new List<GameObject>();
    public GameObject groupButton,
        declare,
        declareback;
    public GameObject dicardcardbutton;

    [Header("Buttons")]
    public GameObject drop;

    [Header("Wild Card")]
    public string wildcardname;
    public GameObject wildcard,
        wildcardlocation;

    [Header("Connection related variable")]
    public bool ischaal,
        getcard,
        finished;
    public GameObject declaredialogue,
        finishdeskcard;
    public Vector3 finishdeskcardogpos;
    public GameObject foundcard;
    public GameObject drawnard;
    public bool check1,
        check2,
        check3,
        check4,
        check5,
        check6;
    int setcheck,
        seqcheck;
    public int numoflist,
        numofmatch,
        numberofpuresequence,
        numberofimpuresequence;
    public int wildcardrank;
    public GameObject highlighSprite;
    public bool placeholderclicked,
        isValid;

    [Header("Wild joker image")]
    public GameObject wildjokerimage;

    [Header("Enable objects")]
    public GameObject placeholder,
        discardholder,
        finishholder;

    public bool moveup = false;
    public float clicktime;
    public bool ispuresequenceincards;
    public bool groupspawned,
        cardsspawned;
    public bool newgroup,
        spawned;
    private Vector3 targetPosition;
    private Tweener moveTween;
    private Vector3 cardOffset;
    public Vector3 draggedCardOriginalPosition;
    public bool isDragging,
        discardcard,
        gamestart;

    [Header("Rummy Game Objectives")]
    public GameObject puresequecetick;
    public GameObject sequencetick;
    public GameObject scoretick;
    public bool sequence,
        puresequence;
    private int sequencelist,
        puresequenceint;

    [Header("Show Text")]
    public GameObject textovercardcanvas;
    public List<GameObject> textcavas;

    [Header("Discarded Cards")]
    public string discardedcard;

    //public List<GameObject> discardedlist;
    public GameObject cardtobediscarded;
    public bool isdeclared = false,
        wrongdecralre = false,
        dropcardresponse = false,
        canplay = false;

    public int numofmatchcards = 0;

    public GameObject getcardfromserver;

    public List<List<GameObject>> finallist = new List<List<GameObject>>();

    public List<GameObject> listtemp1 = new List<GameObject>();
    public List<GameObject> list2temp = new List<GameObject>();
    public List<GameObject> list3temp = new List<GameObject>();
    public List<GameObject> list4temp = new List<GameObject>();
    public List<GameObject> list5temp = new List<GameObject>();
    public List<GameObject> list6temp = new List<GameObject>();

    #endregion

    #region initializing

    //cards distributed to each player first time
    public void OnEnable()
    {
        foreach (Transform t in cardPositions)
            initializedcardPositions.Add(t.position);
        lists = new List<GameObject>[6];
        lists[0] = list1;
        lists[1] = list2;
        lists[2] = list3;
        lists[3] = list4;
        lists[4] = list5;
        lists[5] = list6;
        placeholder.SetActive(true);
        discardholder.SetActive(true);
        finishholder.SetActive(true);
        StartCoroutine(beginspawn());
        StartCoroutine(startbool());
    }

    IEnumerator startbool()
    {
        gamestart = false;
        yield return new WaitForSeconds(2.5f);
        gamestart = true;
    }

    IEnumerator beginspawn()
    {
        yield return new WaitForSeconds(1);
        StartCoroutine(rummycardsspawn());
    }

    IEnumerator clicked()
    {
        placeholderclicked = true;
        yield return new WaitForSeconds(1);
        placeholderclicked = false;
    }

    IEnumerator rummycardsspawn()
    {
        Debug.Log("Res_Check rummy cards spawn");
        yield return new WaitForSeconds(0.2f);
        names = cachetmanager.cardNames;
        //Debug.Log("Object found to be 0");
        for (int i = 0; i < deck.Count; i++)
        {
            deck[i].name = deck[i].name.ToLower();
        }
        yield return new WaitForSeconds(0.2f);
        //Debug.Log("Object found to be 1");
        //Debug.Log("Object found to be 1 " + names.Count);
        foreach (string str in names)
        {
            //Debug.Log("Object found to be 2");

            // Find a GameObject with the same name as the current string
            GameObject foundObject = FindGameObjectByName(str);

            //Debug.Log(str + " Object found to be");

            //Debug.Log(foundObject + " Object found");

            // If a GameObject is found, add it to the list
            if (foundObject != null)
            {
                setFromAPI.Add(foundObject);
            }
        }

        drop.SetActive(true);

        SpawnCards();

        SortCorrectOrder();
    }

    public GameObject FindGameObjectByName(string nameToFind)
    {
        // Find a GameObject by name in the gameObjectArray
        foreach (GameObject go in deck)
        {
            if (go.name == nameToFind)
            {
                return go; // Return the GameObject if the name matches
            }
        }

        // Return null if no matching GameObject is found
        return null;
    }
    #endregion

    #region show points over groups

    public void showresultsofgroup(List<GameObject> list, RectTransform panel)
    {
        List<Vector3> positions = new List<Vector3>();
        foreach (GameObject obj in list)
            positions.Add(obj.transform.position);

        float minX = Mathf.Infinity;
        float maxX = -Mathf.Infinity;
        foreach (Vector3 pos in positions)
        {
            minX = Mathf.Min(minX, pos.x);
            maxX = Mathf.Max(maxX, pos.x);
        }

        panel.gameObject.SetActive(true);

        // Calculate the width of the panel with a reduction of 0.5f units from both sides
        float panelWidth = maxX - minX - 1f * 2;

        // Calculate the position of the panel
        float panelCenterX = (minX + maxX) / 2;
        float panelTopY = 0.48f;
        float panelBottomY = -0.48f;

        // Set the panel's size and position
        panel.sizeDelta = new Vector2(panelWidth, panel.sizeDelta.y);
        panel.position = new Vector3(
            panelCenterX,
            (panelTopY + panelBottomY) / 2 - 2.6f,
            panel.position.z
        );

        panel.transform.parent.GetComponent<Canvas>().sortingOrder = 50;
    }

    public void canvastext(
        List<GameObject> list1,
        List<GameObject> list2,
        List<GameObject> list3,
        List<GameObject> list4,
        List<GameObject> list5,
        List<GameObject> list6
    )
    {
        if (!spawned)
        {
            GameObject canvas = Instantiate(textovercardcanvas);
            textcavas.Add(canvas);

            GameObject canvas1 = Instantiate(textovercardcanvas);
            textcavas.Add(canvas1);

            GameObject canvas2 = Instantiate(textovercardcanvas);
            textcavas.Add(canvas2);

            GameObject canvas3 = Instantiate(textovercardcanvas);
            textcavas.Add(canvas3);

            GameObject canvas4 = Instantiate(textovercardcanvas);
            textcavas.Add(canvas4);

            GameObject canvas5 = Instantiate(textovercardcanvas);
            textcavas.Add(canvas5);
            spawned = true;
        }

        foreach (GameObject card in textcavas)
            card.SetActive(false);


        if (list1.Count != 0)
        {
            textcavas[0].GetComponent<PanelPositioner>().cards = list1;
            textcavas[0].SetActive(true);
        }
        if (list2.Count != 0)
        {
            textcavas[1].GetComponent<PanelPositioner>().cards = list2;
            textcavas[1].SetActive(true);
        }
        if (list3.Count != 0)
        {
            textcavas[2].GetComponent<PanelPositioner>().cards = list3;
            textcavas[2].SetActive(true);
        }
        if (list4.Count != 0)
        {
            textcavas[3].GetComponent<PanelPositioner>().cards = list4;
            textcavas[3].SetActive(true);
        }
        if (list5.Count != 0)
        {
            textcavas[4].GetComponent<PanelPositioner>().cards = list5;
            textcavas[4].SetActive(true);
        }
        if (list6.Count != 0)
        {
            textcavas[5].GetComponent<PanelPositioner>().cards = list6;
            textcavas[5].SetActive(true);
        }
    }

    #endregion

    #region RummyCards to be spawned and moved to desired locations

    #region main function
    void SpawnCards()
    {
        spawnedCards = new List<GameObject>();
        spritetochange.Clear();
        sprites.Clear();
        //int order = 13;
        for (int i = 0; i < setFromAPI.Count; i++)
        {
            GameObject card = Instantiate(setFromAPI[i], spawnPoint.position, Quaternion.identity);
            card.GetComponent<Animator>().enabled = false;
            card.name = setFromAPI[i].name;
            //card.GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards.Add(card);
            spritetochange.Add(card);
            sprites.Add(card.GetComponent<SpriteRenderer>().sprite);
            card.GetComponent<ActiveCard>().ogsprite = card.GetComponent<SpriteRenderer>().sprite;
            card.GetComponent<SpriteRenderer>().sprite = backcard;
            //order--;
        }

        int order2 = 1;
        for (int i = 0; i < spawnedCards.Count; i++)
        {
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order2;
            order2++;
        }

        SortSequence(spawnedCards);
    }

    public IEnumerator spawnwildcard()
    {
        int value = 0;
        wildcardname = cachetmanager.gameDataList[0].joker;
        wildcardname = wildcardname.ToLower();
        for (int i = 0; i < deck.Count; i++)
        {
            if (deck[i].name == wildcardname)
                value = i;
        }
        wildcard = Instantiate(deck[value], spawnPoint.position, Quaternion.identity);

        wildcard.name = deck[value].name;

        wildcard.transform.SetParent(wildcardlocation.transform);

        wildcard.GetComponent<BoxCollider>().enabled = false;
        wildcard.GetComponent<Card>().enabled = false;
        wildcard.GetComponent<ActiveCard>().enabled = false;

        float duration = 0.4f;
        float elapsed = 0f;
        wildcard.GetComponent<Animator>().enabled = true;
        Vector3 startPosition = wildcard.transform.position;
        Vector3 targetPosition = wildcardlocation.transform.position;
        wildcard.transform.localScale = new Vector3(1.2f, 1.2f, 0);
        while (elapsed < duration)
        {
            float t = Mathf.SmoothStep(0f, 1f, elapsed / duration);
            wildcard.transform.position = Vector3.Lerp(startPosition, targetPosition, t);
            elapsed += Time.deltaTime;
            yield return null;
        }

        wildcard.transform.position = targetPosition;
        wildcard.transform.rotation = Quaternion.identity;

        wildcard.GetComponent<SpriteRenderer>().sortingOrder = 0;
        wildcard.GetComponent<SpriteRenderer>().sortingOrder = 0;

        if (wildcard.transform.childCount == 0)
        {
            Vector3 pos = new Vector3(-0.3f, -0.35f, 0);
            GameObject jok = Instantiate(wildjokerimage, Vector3.zero, Quaternion.identity);
            jok.transform.parent = wildcard.transform;
            jok.transform.localPosition = pos;
            jok.GetComponent<SpriteRenderer>().sortingOrder =
                wildcard.GetComponent<SpriteRenderer>().sortingOrder + 50;
        }
    }

    #endregion

    #region seperating cards according to suits
    void SortCorrectOrder()
    {
        // Sort the correct order list based on suits (Diamonds, Spades, Hearts, Clubs)
        spawnedCards = spawnedCards.OrderBy(card => GetSuitOrder(card)).ToList();
        list1.Clear();
        list2.Clear();
        list3.Clear();
        list4.Clear();
        list5.Clear();
        list6.Clear();

        SplitCardsIntoGroups(list1, list2, list3, list4, list5, spawnedCards);

        StartCoroutine(IntitializeMoveCards());
    }

    int GetSuitOrder(GameObject card)
    {
        // Extract the suit from the card name
        string cardName = card.name;
        cardName = card.name.Replace("_", "");
        // Assuming the format is "NUMBER_SUIT" (e.g., "FOUR_HEARTS")
        string[] parts = SplitString(cardName);
        if (parts.Length >= 2)
        {
            string suit = parts[0];

            // Assign a numerical order to each suit (adjust as needed)
            switch (suit)
            {
                case "rp":
                    return 0;
                case "bl":
                    return 1;
                case "rs":
                    return 2;
                case "bp":
                    return 3;
                default:
                    return 4; // Default to the end
            }
        }

        return 4; // Default to the end if the format is not as expected
    }

    string[] SplitString(string inputString)
    {
        // Initialize variables
        string firstPart = "";
        string secondPart = "";

        // Iterate through each character in the input string
        for (int i = 0; i < inputString.Length; i++)
        {
            // Check if it is the first or second character
            if (i < 2)
            {
                firstPart += inputString[i];
            }
            else
            {
                secondPart += inputString[i];
            }
        }

        // Create an array to store the result
        string[] resultArray = { firstPart, secondPart };

        return resultArray;
    }

    void SplitCardsIntoGroups(
        List<GameObject> list1,
        List<GameObject> list2,
        List<GameObject> list3,
        List<GameObject> list4,
        List<GameObject> list5,
        List<GameObject> spawnedCards
    )
    {
        foreach (GameObject card in spawnedCards)
        {
            int suitOrder = GetSuitOrder(card);
            switch (suitOrder)
            {
                case 0:
                    list1.Add(card);
                    break;
                case 1:
                    list2.Add(card);
                    break;
                case 2:
                    list3.Add(card);
                    break;
                case 3:
                    list4.Add(card);
                    break;
                case 4:
                    list5.Add(card);
                    break;
            }
        }
    }

    #endregion

    #region moving cards to respective Location

    IEnumerator IntitializeMoveCards()
    {
        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            AudioManager._instance.PlayCardFlipSound();
            float duration = 0.1f; // Adjust the total duration as needed
            float elapsed = 0f;
            Vector3 startPosition = spawnedCards[i].transform.position;
            Vector3 targetPosition = cardPositions[i].position;

            while (elapsed < duration)
            {
                float t = Mathf.SmoothStep(0f, 1f, elapsed / duration);
                spawnedCards[i].transform.position = Vector3.Lerp(startPosition, targetPosition, t);
                elapsed += Time.deltaTime;
                yield return null;
            }
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
        }
        StartCoroutine(startanimate());
        StartCoroutine(spawnwildcard());
        StartCoroutine(MoveCardsingroup());
    }

    IEnumerator startanimate()
    {
        foreach (GameObject sp in spawnedCards)
        {
            sp.GetComponent<SpriteRenderer>().sprite = sp.GetComponent<ActiveCard>().ogsprite;

            yield return new WaitForSeconds(0.1f);
        }
    }

    IEnumerator MoveCardsingroup()
    {
        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        checkspacing();

        //  Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            float duration = 0.01f; // Adjust the total duration as needed
            float elapsed = 0f;
            Vector3 startPosition = spawnedCards[i].transform.position;
            Vector3 targetPosition = new Vector3(
                cardPositions[i].position.x,
                cardPositions[i].position.y,
                cardPositions[i].position.z
            );

            while (elapsed < duration)
            {
                float t = Mathf.SmoothStep(0f, 1f, elapsed / duration);
                spawnedCards[i].transform.position = Vector3.Lerp(startPosition, targetPosition, t);
                elapsed += Time.deltaTime;
                yield return null;
            }
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
        }

        foreach (GameObject card in spawnedCards)
        {
            if (GetCardRank(card) == wildcardrank || card.name == "jkr1" || card.name == "jkr2")
                card.name = "joker";
        }

        yield return new WaitForSeconds(1f);
        canplay = true;
        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();
    }

    public void checkspacing()
    {
        goupshifting();
        //ShiftLists();
        int currentpositionindex = 0;
        if (list1.Count > 0)
        {
            Debug.Log("RES_Check + Final list 1 " + list1.Count);
            for (int i = 0; i < list1.Count; i++)
            {
                Debug.Log("RES_Check + Final list 1 " + list1[i].name);
                cardPositions[currentpositionindex].transform.position = cardPositions[
                    currentpositionindex
                ]
                    .transform
                    .position;
                currentpositionindex++;
            }
        }

        if (list2.Count > 0)
        {
            for (int i = 0; i < list2.Count; i++)
            {
                Debug.Log("RES_Check + Final list 2 " + list2[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 1f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list3.Count > 0)
        {
            for (int i = 0; i < list3.Count; i++)
            {
                Debug.Log("RES_Check + Final list 3 " + list3[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 2f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list4.Count > 0)
        {
            for (int i = 0; i < list4.Count; i++)
            {
                Debug.Log("RES_Check + Final list 4 " + list4[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 3f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list5.Count > 0)
        {
            for (int i = 0; i < list5.Count; i++)
            {
                Debug.Log("RES_Check + Final list 5 " + list5[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 4f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list6.Count > 0)
        {
            for (int i = 0; i < list6.Count; i++)
            {
                Debug.Log("RES_Check + Final list 6 " + list6[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 5f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }
    }

    public void ClickedGroupcheckspacing()
    {
        goupshifting();
        //ShiftLists();
        int currentpositionindex = 0;
        if (list1.Count > 0)
        {
            Debug.Log("RES_Check + Final list 1 " + list1.Count);
            for (int i = 0; i < list1.Count; i++)
            {
                Debug.Log("RES_Check + Final list 1 " + list1[i].name);
                cardPositions[currentpositionindex].transform.position = cardPositions[
                    currentpositionindex
                ]
                    .transform
                    .position;
                currentpositionindex++;
            }
        }

        if (list2.Count > 0)
        {
            for (int i = 0; i < list2.Count; i++)
            {
                Debug.Log("RES_Check + Final list 2 " + list2[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 1f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list3.Count > 0)
        {
            for (int i = 0; i < list3.Count; i++)
            {
                Debug.Log("RES_Check + Final list 3 " + list3[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 2f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list4.Count > 0)
        {
            for (int i = 0; i < list4.Count; i++)
            {
                Debug.Log("RES_Check + Final list 4 " + list4[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 3f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list5.Count > 0)
        {
            for (int i = 0; i < list5.Count; i++)
            {
                Debug.Log("RES_Check + Final list 5 " + list5[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 4f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }

        if (list6.Count > 0)
        {
            for (int i = 0; i < list6.Count; i++)
            {
                Debug.Log("RES_Check + Final list 6 " + list6[i].name);
                cardPositions[currentpositionindex].transform.position = new Vector3(
                    cardPositions[currentpositionindex].transform.position.x + 5f,
                    cardPositions[currentpositionindex].transform.position.y,
                    cardPositions[currentpositionindex].transform.position.z
                );
                currentpositionindex++;
            }
        }
    }

    #endregion

    #endregion

    #region handle pick cards

    public void boxcolidersizeadjust(List<GameObject> listobj)
    {
        if (listobj.Count > 0)
        {
            if (listobj.Count == 1)
            {
                listobj[0].GetComponent<BoxCollider>().center = new Vector3(0, 0, 0);
                listobj[0].GetComponent<BoxCollider>().size = new Vector3(1, 1.41f, 0.2f);
            }
            else
            {
                for (int i = 0; i < listobj.Count - 1; i++)
                {
                    listobj[i].GetComponent<BoxCollider>().center = new Vector3(-0.25f, 0, 0);
                    listobj[i].GetComponent<BoxCollider>().size = new Vector3(0.5f, 1.41f, 0.2f);
                }
                listobj[listobj.Count - 1].GetComponent<BoxCollider>().center = new Vector3(
                    0,
                    0,
                    0
                );
                listobj[listobj.Count - 1].GetComponent<BoxCollider>().size = new Vector3(
                    1,
                    1.41f,
                    0.2f
                );
            }
        }
    }

    public void handlealllist()
    {
        boxcolidersizeadjust(list1);
        boxcolidersizeadjust(list2);
        boxcolidersizeadjust(list3);
        boxcolidersizeadjust(list4);
        boxcolidersizeadjust(list5);
        boxcolidersizeadjust(list6);

        //if (!isDragging && cardsspawned)
        //{
        //    if (list1.Count > 0)
        //        showresultsofgroup(list1, group1panel);
        //    else
        //        group1panel.gameObject.SetActive(false);
        //    if (list2.Count > 0)
        //        showresultsofgroup(list2, group2panel);
        //    else
        //        group2panel.gameObject.SetActive(false);
        //    if (list3.Count > 0)
        //        showresultsofgroup(list3, group3panel);
        //    else
        //        group3panel.gameObject.SetActive(false);
        //    if (list4.Count > 0)
        //        showresultsofgroup(list4, group4panel);
        //    else
        //        group4panel.gameObject.SetActive(false);
        //    if (list5.Count > 0)
        //        showresultsofgroup(list5, group5panel);
        //    else
        //        group5panel.gameObject.SetActive(false);
        //    if (list6.Count > 0)
        //        showresultsofgroup(list6, group6panel);
        //    else
        //        group6panel.gameObject.SetActive(false);
        //}
    }

    #endregion

    #region handle card movement and entire rummy logic

    public void handlewildcardjokerimage()
    {
        if (wildcard != null)
            wildcardrank = GetCardRank(wildcard);

        HandleWildCards(spawnedCards);

        HandleJokerCards(spawnedCards);
        foreach (GameObject card in spawnedCards)
        {
            if (card.name == "joker")
            {
                if (card.transform.childCount == 0)
                {
                    Vector3 pos = new Vector3(-0.3f, -0.35f, 0);
                    GameObject jok = Instantiate(wildjokerimage, Vector3.zero, Quaternion.identity);
                    jok.transform.parent = card.transform;
                    jok.transform.localPosition = pos;
                    jok.GetComponent<SpriteRenderer>().sortingOrder =
                        card.GetComponent<SpriteRenderer>().sortingOrder + 50;
                }
            }
        }

        //foreach (GameObject card in Discardedlist)
        //{
        //    if (wildcard != null)
        //    {
        //        string name;
        //        string name2;
        //        if (card.name.Contains("_"))
        //        {
        //            name = card.name.Replace("_", "");
        //        }
        //        else
        //        {
        //            name = card.name;
        //        }
        //        if (wildcard.name.Contains("_"))
        //        {
        //            name2 = wildcard.name.Replace("_", "");
        //        }
        //        else
        //        {
        //            name2 = wildcard.name;
        //        }
        //        if (name == name2)
        //        {
        //            if (card.transform.childCount == 0)
        //            {
        //                Vector3 pos = new Vector3(-0.3f, -0.45f, 0);
        //                GameObject jok = Instantiate(wildjokerimage, Vector3.zero, Quaternion.identity);
        //                jok.transform.parent = card.transform;
        //                jok.transform.localPosition = pos;
        //                jok.GetComponent<SpriteRenderer>().sortingOrder = card.GetComponent<SpriteRenderer>().sortingOrder + 50;
        //            }
        //        }
        //    }
        //}

        //for(int i = 0; i < Discardedlist.Count - 1; i++)
        //{
        //    if (Discardedlist[i].transform.childCount > 0)
        //        Discardedlist[i].transform.GetChild(0).gameObject.SetActive(false);
        //}
    }

    public void ShowJokerForDiscard(GameObject card)
    {
        Vector3 pos = new Vector3(-0.3f, -0.35f, 0);
        GameObject jok = Instantiate(wildjokerimage, Vector3.zero, Quaternion.identity);
        jok.transform.parent = card.transform;
        jok.transform.localPosition = pos;
        jok.GetComponent<SpriteRenderer>().sortingOrder =
            card.GetComponent<SpriteRenderer>().sortingOrder + 50;
    }

    void Update()
    {
        if (draggedCard == null)
        {
            //Debug.Log("RES_Check + dragged card is null");
            Destroy(GameObject.Find("New_Group"));
            newgroup = false;
        }
        if (!finished && canplay)
        {
            handlewildcardjokerimage();
            handlealllist();
            int order = 0;
            for (int i = 0; i < spawnedCards.Count; i++)
            {
                order++;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            }
            if (
                Input.GetMouseButtonDown(0)
                || (Input.touchCount > 0 && Input.GetTouch(0).phase == TouchPhase.Began)
            )
            {
                //discradarea.GetComponent<BoxCollider>().enabled = false;

                Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

                RaycastHit hit;
                if (Physics.Raycast(ray, out hit))
                {
                    GameObject clickedCard = hit.collider.gameObject;
                    Debug.Log("Clicked Card: " + clickedCard.name);

                    if (spawnedCards.Contains(clickedCard))
                    {
                        Debug.Log("Exists");
                    }
                }
            }

            HandleInput();

            if (
                isDragging
                && (
                    Input.GetMouseButtonUp(0)
                    || (Input.touchCount > 0 && Input.GetTouch(0).phase == TouchPhase.Ended)
                )
            )
            {
                Debug.Log("Handle release is been called");
                HandleCardRelease();
            }
        }
    }

    public void DropPopupOpen(bool set)
    {
        canplay = set;
    }
    void HandleInput()
    {
        // Check for touch or mouse click
        if (
            Input.GetMouseButtonDown(0)
            || (Input.touchCount > 0 && Input.GetTouch(0).phase == TouchPhase.Began)
        )
        {
            Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

            RaycastHit hit;
            if (Physics.Raycast(ray, out hit))
            {
                GameObject clickedCard = hit.collider.gameObject;
                if (clickedCard.name == "placeholderCard" && getcard)
                {
                    Debug.Log("RES_Check + Clicked Gaddi");
                    getcard = false;
                    if (ischaal && !cachetmanager.gameDataList[0].declare)
                    {
                        if (spawnedCards.Count == 13)
                        {
                            StartCoroutine(clicked());

                            cachetmanager.API_CALL_get_card();
                        }
                        else
                        {
                            StartCoroutine(
                                showtext(
                                    warning,
                                    "You already have 14 cards, Please Discard a Card"
                                )
                            );
                        }
                    }
                    else
                        StartCoroutine(showtext(warning, "Please Wait For Your Chaal"));
                }
                //if ((clickedCard.name == "Discard_Area" && getcard) || (Discardedlist.Contains(clickedCard) && getcard))
                if ((clickedCard.name == "Discard_Area" && getcard))
                {
                    Debug.Log("RES_Value + " + clickedCard.name);
                    getcard = false;
                    if (ischaal && !cachetmanager.gameDataList[0].declare)
                    {
                        if (spawnedCards.Count == 13)
                        {
                            int wildrank = 0;

                            int discardedcardrank = 0;

                            string cardname = GameObject
                                .Find("Discard_Area")
                                .transform.GetChild(1)
                                .gameObject.name;

                            string discardedname = cardname.Replace("(Clone)", "").Trim();

                            discardedname = discardedname.ToLower();

                            discardedcardrank = GetCardRankname(discardedname);

                            wildrank = GetCardRankname(wildcardname);

                            Debug.Log(
                                "Res_Check + discardedrank + "
                                    + discardedcardrank
                                    + " , wildrrank + "
                                    + wildrank
                            );

                            if (
                                wildrank == discardedcardrank
                                || discardedname == "jkr1"
                                || discardedname == "jkr2"
                            )
                            {
                                showtoastmessage("You cannot pick joker card");
                                getcard = true;
                            }
                            else
                            {
                                cachetmanager.API_CALL_get_drop_card();
                            }
                        }
                        else
                        {
                            StartCoroutine(
                                showtext(
                                    warning,
                                    "You already have 14 cards, Please Discard a Card"
                                )
                            );
                        }
                    }
                    else
                        StartCoroutine(showtext(warning, "Please Wait For Your Chaal"));
                }
                if (clickedCard.name != "placeholderCard")
                {
                    if (spawnedCards.Contains(clickedCard))
                    {
                        isDragging = true;
                        discradarea.GetComponent<BoxCollider>().enabled = true;
                        draggedCard = clickedCard;
                        draggedCardOriginalPosition = clickedCard.transform.position;
                        Vector3 intersection = GetIntersectionWithCardPlane(ray, clickedCard);
                        cardOffset = clickedCard.transform.position - intersection;

                        Collider draggedCollider = draggedCard.GetComponent<Collider>();
                        if (draggedCollider != null)
                        {
                            draggedCollider.enabled = false;
                        }
                    }
                }
            }
        }

        // Handle dragging
        if (isDragging)
        {
            if (!newgroup)
                InstantiateGroupCard();
            clicktime += 1;
            draggedCard.GetComponent<SpriteRenderer>().sortingOrder = 20;
            Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

            if (Input.touchCount > 0)
            {
                ray = Camera.main.ScreenPointToRay(Input.GetTouch(0).position);
            }
            // Move the dragged card based on input position
            Vector3 intersection = GetIntersectionWithCardPlane(ray, draggedCard);
            if (moveTween != null && moveTween.IsActive())
                moveTween.Kill();
            moveTween = draggedCard
                .transform.DOMove(intersection + cardOffset, 0.1f)
                .SetEase(Ease.OutSine);

            // RaycastHit hit;
            // if (Physics.Raycast(ray, out hit))
            // {
            //     GameObject releasedCard = hit.collider.gameObject;

            //     Debug.Log(releasedCard.name + " Released");

            //     List<GameObject> from = draggedcardlist(releasedCard);

            //     getcardsafterdragging(releasedCard);

            //     // int draggedCardIndex = spawnedCards.IndexOf(draggedCard);
            //     // int releasedCardIndex = spawnedCards.IndexOf(releasedCard);
            //     // MoveCardDraaged(spawnedCards, draggedCardIndex, releasedCardIndex, releasedCard);
            // }

            //DraggedCardRearrange(draggedCard);
            //goupshifting();
            //draggedCard.transform.position = intersection + cardOffset;

            // targetPosition = GetIntersectionWithCardPlane(ray, draggedCard) + cardOffset;

            // // Use DOTween to smoothly move the card
            // if (moveTween != null && moveTween.IsActive())
            //     moveTween.Kill(); // Stop any existing tween
            // moveTween = draggedCard.transform.DOMove(targetPosition, 0.1f).SetEase(Ease.OutSine);
        }
    }

    Vector3 GetIntersectionWithCardPlane(Ray ray, GameObject card)
    {
        Plane plane = new Plane(Camera.main.transform.forward, card.transform.position);
        float distance;
        if (plane.Raycast(ray, out distance))
        {
            return ray.GetPoint(distance);
        }
        return Vector3.zero;
    }

    void HandleCardRelease()
    {
        if (!isDragging && moveTween != null && moveTween.IsActive())
        {
            moveTween.Kill();
        }
        isDragging = false;
        obj.Clear();
        Debug.Log("Called");

        Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

        if (Input.touchCount > 0)
        {
            ray = Camera.main.ScreenPointToRay(Input.GetTouch(0).position);
        }

        RaycastHit hit;
        if (Physics.Raycast(ray, out hit))
        {
            GameObject releasedCard = hit.collider.gameObject;

            Debug.Log(releasedCard.name + " Released");

            if (releasedCard.name == "New_Group")
            {
                if (!isDragging && moveTween != null && moveTween.IsActive())
                {
                    moveTween.Kill();
                }
                Debug.Log("RES_Check + New Group");
                rearrangenewgroup(draggedCard);
                draggedCard.GetComponent<BoxCollider>().enabled = true;
                clicktime = 0;
                isDragging = false;
                obj.Clear();
                Destroy(GameObject.Find("New_Group"));
                newgroup = false;
                //grouplist.Clear();
                //CheckForConsecutiveNames(spawnedCards);
            }
            else
            {
                Destroy(GameObject.Find("New_Group"));
                newgroup = false;
                //foreach (GameObject obj in grouplist)
                //{
                //    Destroy(obj);
                //}
                //grouplist.Clear();
                moveback();
            }
        }
        else
        {
            Destroy(GameObject.Find("New_Group"));
            newgroup = false;
            //foreach (GameObject obj in grouplist)
            //{
            //    Destroy(obj);
            //}
            //grouplist.Clear();
            moveback();
            Debug.Log("RES_Check + far Released");
            //CheckForConsecutiveNames(spawnedCards);
        }
    }

    void InstantiateGroupCard()
    {
        newgroup = true;
        if (list6.Count == 0)
        {
            GameObject lastCard = spawnedCards[spawnedCards.Count - 1];
            GameObject groupcard = Instantiate(GroupcardPrefab);
            Vector3 newPosition = lastCard.transform.position + new Vector3(2f, 0, 0);
            groupcard.name = "New_Group";
            groupcard.transform.position = newPosition;
        }
    }

    void MoveCardUp(GameObject card)
    {
        float yOffset = 0.5f;

        Vector3 newPosition = card.transform.position + new Vector3(0.0f, yOffset, 0.0f);

        StartCoroutine(MoveCardUP(card, newPosition, 0.5f));
    }

    IEnumerator MoveCardUP(GameObject card, Vector3 targetPosition, float duration)
    {
        string hexColor = "#747474";
        Color color;

        if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
        {
            card.GetComponent<SpriteRenderer>().color = color;
        }
        if (uplist.Count == 0)
            uplist.Add(card);
        else if (!uplist.Contains(card))
            uplist.Add(card);

        if (uplist.Count == 1)
        {
            if (ischaal)
            {
                Debug.Log("RES_Check + discard");
                dicardcardbutton.SetActive(true);
            }
        }
        else
            dicardcardbutton.SetActive(false);

        yield return new WaitForSeconds(0);

        //float elapsed = 0f;
        //Vector3 startPosition = card.transform.position;

        //while (elapsed < duration)
        //{
        //    card.transform.position = Vector3.Lerp(startPosition, targetPosition, elapsed / duration);
        //    elapsed += Time.deltaTime;
        //    yield return null;
        //}

        //card.transform.position = targetPosition;
        //card.GetComponent<BoxCollider>().enabled = true;

        if (uplist.Count > 1 && uplist.Count < 5)
        {
            groupButton.SetActive(true);
        }
        else
        {
            groupButton.SetActive(false);
        }
    }

    IEnumerator MoveCardDown(GameObject card, float duration)
    {
        //card.GetComponent<BoxCollider>().enabled = false;
        if (uplist.Contains(card))
            uplist.Remove(card);

        if (uplist.Count == 1)
        {
            if (ischaal)
                dicardcardbutton.SetActive(true);
        }
        else
        {
            dicardcardbutton.SetActive(false);
        }

        //float yOffset = 0.5f;

        //Vector3 newPosition = card.transform.position - new Vector3(0.0f, yOffset, 0.0f);

        //float elapsed = 0f;
        //Vector3 startPosition = card.transform.position;
        yield return new WaitForSeconds(0);

        string hexColor = "#FFFFFF";
        Color color;

        if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
        {
            card.GetComponent<SpriteRenderer>().color = color;
        }

        //while (elapsed < duration)
        //{
        //    card.transform.position = Vector3.Lerp(startPosition, newPosition, elapsed / duration);
        //    elapsed += Time.deltaTime;
        //    yield return null;
        //}

        //card.transform.position = newPosition;

        card.GetComponent<Card>().cardup = false;
        card.GetComponent<BoxCollider>().enabled = true;

        if (uplist.Count < 1)
        {
            groupButton.SetActive(false);
        }
        else
        {
            groupButton.SetActive(true);
        }

        int order1 = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            order1++;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order1;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }
    }

    public void cardsdown()
    {
        for (int i = 0; i < uplist.Count; i++)
        {
            string hexColor = "#FFFFFF";
            Color color;

            if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
            {
                uplist[i].GetComponent<SpriteRenderer>().color = color;
            }
        }

        uplist.Clear();

        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }
    }

    public void moveback()
    {
        if (draggedCard == null)
        {
            return;
        }
        if (spawnedCards.Contains(draggedCard))
        {
            SpriteRenderer draggedSpriteRenderer = draggedCard.GetComponent<SpriteRenderer>();
            BoxCollider draggedBoxCollider = draggedCard.GetComponent<BoxCollider>();
            Card draggedCardComponent = draggedCard.GetComponent<Card>();

            if (draggedSpriteRenderer != null)
            {
                draggedSpriteRenderer.sortingOrder = 1;
            }

            if (draggedCard.transform.position == draggedCardOriginalPosition)
            {
                if (!isDragging && moveTween != null && moveTween.IsActive())
                {
                    moveTween.Kill();
                }
                Debug.Log("RES_Check + Release + " + draggedCard);
                clicktime = 0;
                isDragging = false;
                obj.Clear();
                if (draggedCardComponent != null && draggedCardComponent.enabled)
                {
                    Debug.Log("RES_Check + move back");
                    if (!draggedCardComponent.cardup)
                    {
                        Debug.Log("AndropApps 2_1");

                        if (spawnedCards.Contains(draggedCard))
                        {
                            MoveCardUp(draggedCard);

                            if (draggedBoxCollider != null)
                            {
                                draggedBoxCollider.enabled = true;
                            }

                            draggedCardComponent.cardup = true;

                            int order = 0;
                            for (
                                int i = 0;
                                i < Mathf.Min(spawnedCards.Count, cardPositions.Count);
                                i++
                            )
                            {
                                order++;
                                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
                            }
                        }
                    }
                    else
                    {
                        Debug.Log("AndropApps 2_2");
                        StartCoroutine(MoveCardDown(draggedCard, 0.5f));
                    }
                }
            }
            else
            {
                if (!isDragging && moveTween != null && moveTween.IsActive())
                {
                    moveTween.Kill();
                }
                Debug.Log("AndropApps 1");
                clicktime = 0;
                isDragging = false;
                obj.Clear();
                Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

                if (Input.touchCount > 0)
                {
                    ray = Camera.main.ScreenPointToRay(Input.GetTouch(0).position);
                }

                RaycastHit hit;
                if (Physics.Raycast(ray, out hit))
                {
                    GameObject releasedCard = hit.collider.gameObject;

                    Debug.Log(releasedCard.name + " Released");

                    int draggedCardIndex = spawnedCards.IndexOf(draggedCard);
                    int releasedCardIndex = spawnedCards.IndexOf(releasedCard);

                    if (draggedBoxCollider != null)
                    {
                        draggedBoxCollider.enabled = true;
                    }

                    if (spawnedCards.Count == 14)
                    {
                        if (releasedCard.name == "Discard_Area")
                        {
                            DiscardCard(draggedCard);
                            return;
                        }
                    }
                    else if (releasedCard.name == "Discard_Area")
                    {
                        StartCoroutine(
                            showtext(warning, "You have 13 cards, please draw a card to discard.")
                        );
                    }

                    if (spawnedCards.Count == 14)
                    {
                        if (releasedCard.name == "FinishDesk")
                        {
                            Debug.Log("RES_Check Correct Declare");
                            finished = true;
                            draggedCard.transform.position = releasedCard.transform.position;
                            finishdeskcard = draggedCard;
                            finishdeskcardogpos = draggedCardOriginalPosition;
                            finishdeskcard.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
                            declaredialogue.SetActive(true);
                            return;
                        }
                    }
                    else if (releasedCard.name == "FinishDesk")
                    {
                        StartCoroutine(
                            showtext(
                                warning,
                                "You have 13 cards, please draw a card from deck and then Finsish the game."
                            )
                        );
                    }

                    if (spawnedCards.Contains(releasedCard) && draggedCard != releasedCard)
                    {
                        Debug.Log("RES_Check + " + releasedCard);
                        MoveCard2(spawnedCards, draggedCardIndex, releasedCardIndex, releasedCard);
                        canvastext(list1, list2, list3, list4, list5, list6);
                        CheckAfterEveryMove();
                        return;
                    }
                }

                StartCoroutine(MoveCardBack(draggedCard, draggedCardOriginalPosition, 0.5f));
            }

            draggedCard = null;
        }
        else
            Destroy(draggedCard);

        //for (int i = 0; i < discardedcards.Count; i++)
        //{
        //    BoxCollider collider = discardedcards[i].GetComponent<BoxCollider>();
        //    collider.enabled = true;
        //}
    }

    IEnumerator hidecolliders()
    {
        GameObject ph = GameObject.Find("placeholderCard");
        GameObject d = GameObject.Find("Discard_Area");

        ph.GetComponent<BoxCollider>().enabled = false;
        d.GetComponent<BoxCollider>().enabled = false;

        yield return new WaitForSeconds(2.5f);

        ph.GetComponent<BoxCollider>().enabled = true;
        d.GetComponent<BoxCollider>().enabled = true;
    }

    public void AutoDiscardCard(GameObject card)
    {
        Debug.Log("RES_Check + DiscardCard called");
        if (card != null)
        {
            if (draggedCard == getcardfromserver)
            {
                if (!isDragging && moveTween != null && moveTween.IsActive())
                {
                    moveTween.Kill();
                }
                isDragging = false;
                obj.Clear();
                spawnedCards.Remove(card);
                List<GameObject> fromlist = draggedcardlist(card);
                if (fromlist != null)
                    fromlist.Remove(card);
                Destroy(card);
            }
            else
            {
                StartCoroutine(hidecolliders());
                card.transform.position = GameObject.Find("Discard_Area").transform.position;
                spawnedCards.Remove(card);
                List<GameObject> fromlist = draggedcardlist(card);
                if (fromlist != null)
                    fromlist.Remove(card);

                for (int i = 0; i < cardPositions.Count; i++)
                {
                    cardPositions[i].transform.position = initializedcardPositions[i];
                }

                string hexColor = "#FFFFFF";
                Color color;

                if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
                {
                    card.GetComponent<SpriteRenderer>().color = color;
                }

                string droppedcard = card.GetComponent<Card>().name.ToUpper();

                if (!string.IsNullOrEmpty(droppedcard))
                {
                    cachetmanager.Idropped = droppedcard;
                    Debug.Log("RES_Check + " + droppedcard + " DiscardCard");
                }
                else
                {
                    Debug.LogError("Card name is null or empty.");
                    Destroy(card);
                    return;
                }

                checkspacing();

                Debug.Log("RES_Check + start move initialize");
                int order = 0;
                for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
                {
                    Vector3 targetPosition = cardPositions[i].position;
                    order++;
                    spawnedCards[i].transform.position = targetPosition;
                    spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                    spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
                }
                if (!isDragging && moveTween != null && moveTween.IsActive())
                {
                    moveTween.Kill();
                }
                isDragging = false;
                obj.Clear();
                //discardedlist.Add(card);
                card.SetActive(false);
                //int display = 0;
                //for (int i = 0; i < discardedlist.Count; i++)
                //{
                //    display++;
                //    discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
                //}

                //for (int i = 0; i < discardedlist.Count; i++)
                //{
                //    discardedlist[i].GetComponent<BoxCollider>().enabled = false;
                //    discardedlist[i].GetComponent<Card>().enabled = false;
                //    discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
                //}

                //for (int i = 0; i < discardedlist.Count - 1; i++)
                //{
                //    if (discardedlist[i].transform.childCount > 0)
                //        Destroy(discardedlist[i].transform.GetChild(0).gameObject);
                //}
                card.GetComponent<Card>().enabled = false;
                Destroy(card);
                canvastext(list1, list2, list3, list4, list5, list6);
                CheckAfterEveryMove();
            }
        }
        else
        {
            showtoastmessage("RES_Check + Dicard card is empty");
        }
    }

    public void DiscardAfterONResponse(GameObject card, string area)
    {
        StartCoroutine(hidecolliders());
        if (card != null)
        {
            card.transform.position = GameObject.Find(area).transform.position;
            spawnedCards.Remove(card);
            List<GameObject> fromlist = draggedcardlist(card);
            if (fromlist != null)
                fromlist.Remove(card);

            string hexColor = "#FFFFFF";
            Color color;

            if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
            {
                card.GetComponent<SpriteRenderer>().color = color;
            }

            card.GetComponent<Card>().enabled = false;
            if (!isdeclared)
                Destroy(card);
        }

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        checkspacing();

        Debug.Log("RES_Check + start move card to new positions");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }
        //discardedlist.Add(discardCard);

        //int display = 0;
        //for (int i = 0; i < discardedlist.Count; i++)
        //{
        //    display++;
        //    discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
        //}

        //for (int i = 0; i < discardedlist.Count; i++)
        //{
        //    discardedlist[i].GetComponent<BoxCollider>().enabled = false;
        //    discardedlist[i].GetComponent<Card>().enabled = false;
        //    discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
        //}

        //for (int i = 0; i < discardedlist.Count - 1; i++)
        //{
        //    if (discardedlist[i].transform.childCount > 0)
        //        Destroy(discardedlist[i].transform.GetChild(0).gameObject);
        //}
        uplist.Clear();
        dicardcardbutton.SetActive(false);
        if (isdeclared)
        {
            if (numofmatchcards == 13 || numofmatchcards == 14)
            {
                if (isvaliddeclare())
                    cachetmanager.API_CALL_declare();
                else
                    cachetmanager.API_CALL_Wrongdeclare();
            }
            else
            {
                cachetmanager.API_CALL_Wrongdeclare();
            }
        }
        else
        {
            canvastext(list1, list2, list3, list4, list5, list6);
            CheckAfterEveryMove();
        }
    }

    public bool isvaliddeclare()
    {
        if (numberofpuresequence > 0)
        {
            if (numberofpuresequence >= 2 || numberofimpuresequence > 0)
            {
                Debug.Log("RES_Check Correct Declare");
                return true;
            }
            else
            {
                Debug.Log("RES_Check Wrong Declare");
                return false;
            }
        }
        else
        {
            Debug.Log("RES_Check Wrong Declare");
            return false;
        }
    }

    public void DiscardCard(GameObject card)
    {
        Debug.Log("RES_Check + DiscardCard called");
        if (card != null)
        {
            discardCard = draggedCard;
            string droppedcard = card.GetComponent<Card>().name.ToUpper();
            if (!string.IsNullOrEmpty(droppedcard))
            {
                cachetmanager.Idropped = droppedcard;
                Debug.Log("RES_Check + " + droppedcard + " DiscardCard");
                cachetmanager.API_CALL_drop_card(droppedcard);
                //discardCard.tra
                discardCard.SetActive(false);
                //StartCoroutine(waitfordropcardresponse());
            }
            else
            {
                Debug.LogError("Card name is null or empty.");
                Destroy(card);
                return;
            }
        }
        else
        {
            showtoastmessage("RES_Check + Dicard card is empty");
        }
    }

    IEnumerator waitfordropcardresponse()
    {
        yield return new WaitForSeconds(5);
        //if(dropcardresponse)
        //{
        //    Destroy(discardCard);
        //    dropcardresponse = false;
        //}
        //else
        //{
        //    discardCard.SetActive(true);
        //    StartCoroutine(MoveCardBack(discardCard, draggedCardOriginalPosition, 0.5f));
        //}
    }

    //public void DiscardCard(GameObject card)
    //{
    //    Debug.Log("RES_Check + DiscardCard called");
    //    if (card != null)
    //    {
    //        StartCoroutine(hidecolliders());
    //        draggedCard.transform.position = GameObject.Find("Discard_Area").transform.position;
    //        spawnedCards.Remove(draggedCard);
    //        List<GameObject> fromlist = draggedcardlist(draggedCard);
    //        if (fromlist != null)
    //            fromlist.Remove(draggedCard);

    //        for (int i = 0; i < cardPositions.Count; i++)
    //        {
    //            cardPositions[i].transform.position = initializedcardPositions[i];
    //        }

    //        string hexColor = "#FFFFFF";
    //        Color color;

    //        if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
    //        {
    //            card.GetComponent<SpriteRenderer>().color = color;
    //        }

    //        string droppedcard = card.GetComponent<Card>().name.ToUpper();

    //        if (!string.IsNullOrEmpty(droppedcard))
    //        {
    //            cachetmanager.Idropped = droppedcard;
    //            Debug.Log("RES_Check + " + droppedcard + " DiscardCard");
    //            cachetmanager.API_CALL_drop_card(droppedcard);
    //            GameObject.Find("ProfilePic").transform.GetChild(0).GetComponent<PointRummyChaalSlider>().discardglow.SetActive(false);
    //            GameObject.Find("ProfilePic").transform.GetChild(0).GetComponent<PointRummyChaalSlider>().finishdeskglow.SetActive(false);
    //        }
    //        else
    //        {
    //            Debug.LogError("Card name is null or empty.");
    //            Destroy(card);
    //            return;
    //        }

    //        checkspacing();

    //        Debug.Log("RES_Check + start move initialize");
    //        int order = 0;
    //        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
    //        {
    //            Vector3 targetPosition = cardPositions[i].position;
    //            order++;
    //            spawnedCards[i].transform.position = targetPosition;
    //            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
    //            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
    //        }
    //        discardedlist.Add(card);

    //        int display = 0;
    //        for (int i = 0; i < discardedlist.Count; i++)
    //        {
    //            display++;
    //            discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
    //        }

    //        for (int i = 0; i < discardedlist.Count; i++)
    //        {
    //            discardedlist[i].GetComponent<BoxCollider>().enabled = false;
    //            discardedlist[i].GetComponent<Card>().enabled = false;
    //            discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
    //        }

    //        for (int i = 0; i < discardedlist.Count - 1; i++)
    //        {
    //            if (discardedlist[i].transform.childCount > 0)
    //                Destroy(discardedlist[i].transform.GetChild(0).gameObject);
    //        }

    //        draggedCard.GetComponent<Card>().enabled = false;
    //        canvastext(list1, list2, list3, list4, list5, list6);
    //        CheckAfterEveryMove();
    //    }
    //    else
    //    {
    //        showtoastmessage("RES_Check + Dicard card is empty");
    //    }
    //}

    IEnumerator checkOnDropmessagefromserver()
    {
        yield return new WaitForSeconds(2);
        if (!cachetmanager.gotdropresponse)
            StartCoroutine(MoveCardBack(cardtobediscarded, draggedCardOriginalPosition, 0.5f));
    }

    public void discardcardresponsefromserver()
    {
        StartCoroutine(hidecolliders());
        if (draggedCard != null)
        {
            draggedCard.transform.position = GameObject.Find("Discard_Area").transform.position;
            spawnedCards.Remove(draggedCard);
        }
        List<GameObject> fromlist = draggedcardlist(draggedCard);
        fromlist.Remove(draggedCard);
        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        string hexColor = "#FFFFFF";
        Color color;

        if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
        {
            cardtobediscarded.GetComponent<SpriteRenderer>().color = color;
        }

        checkspacing();

        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }
        //discardedlist.Add(cardtobediscarded);

        //int display = 0;
        //for (int i = 0; i < discardedlist.Count; i++)
        //{
        //    display++;
        //    discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
        //}

        //for (int i = 0; i < discardedlist.Count; i++)
        //{
        //    discardedlist[i].GetComponent<BoxCollider>().enabled = false;
        //    discardedlist[i].GetComponent<Card>().enabled = false;
        //    discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
        //}

        //for (int i = 0; i < discardedlist.Count - 1; i++)
        //{
        //    if (discardedlist[i].transform.childCount > 0)
        //        Destroy(discardedlist[i].transform.GetChild(0).gameObject);
        //}

        draggedCard.GetComponent<Card>().enabled = false;
        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

    enum Direction
    {
        Left,
        Right,
    }

    void MoveCard2<T>(List<T> list, int fromIndex, int toIndex, GameObject releasedcard)
    {
        if (fromIndex == toIndex)
        {
            return;
        }

        RearrangeGroup(draggedCard, releasedcard);
    }

    void MoveCardDragged<T>(List<T> list, int fromIndex, int toIndex, GameObject releasedcard)
    {
        if (fromIndex == toIndex)
        {
            return;
        }

        RearrangeGroupWhileDragging(draggedCard, releasedcard);
    }

    public void rearrangenewgroup(GameObject card)
    {
        if (list1.Count == 0)
        {
            ClickedNewGroupAddObjectAfterNewCard(card, list1);
        }
        else if (list2.Count == 0)
        {
            ClickedNewGroupAddObjectAfterNewCard(card, list2);
        }
        else if (list3.Count == 0)
        {
            ClickedNewGroupAddObjectAfterNewCard(card, list3);
        }
        else if (list4.Count == 0)
        {
            ClickedNewGroupAddObjectAfterNewCard(card, list4);
        }
        else if (list5.Count == 0)
        {
            ClickedNewGroupAddObjectAfterNewCard(card, list5);
        }
        else
        {
            ClickedNewGroupAddObjectAfterNewCard(card, list6);
        }

        List<GameObject> fromlist = draggedcardlist(card);
        fromlist.Remove(card);

        finallist.Clear();
        //goupshifting();
        checkspacing();

        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }

        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();

        uplist.Clear();
    }

    public void RearrangeGroup(GameObject card, GameObject tocard)
    {
        List<GameObject> fromlist = draggedcardlist(card);
        List<GameObject> tolist = draggedcardlist(tocard);

        //tolist.Add(card);

        fromlist.Remove(card);

        AddObjectAfter(tocard, card, tolist);
        spawnedCards.Remove(card);
        AddObjectAfter(tocard, card, spawnedCards);
    }

    public void RearrangeGroupWhileDragging(GameObject card, GameObject tocard)
    {
        List<GameObject> fromlist = draggedcardlist(card);
        List<GameObject> tolist = draggedcardlist(tocard);

        //tolist.Add(card);

        fromlist.Remove(card);

        AddObjectAfterDragging(tocard, card, tolist);
        spawnedCards.Remove(card);
        AddObjectAfterDragging(tocard, card, spawnedCards);
    }

    void RemovefromSelectedlist(GameObject card)
    {
        if (list1.Contains(card))
        {
            Debug.Log("RES_Check + fromList list1");
            list1.Remove(card);
        }
        else if (list2.Contains(card))
        {
            Debug.Log("RES_Check + fromList list2");
            list2.Remove(card);
        }
        else if (list3.Contains(card))
        {
            Debug.Log("RES_Check + fromList list3");
            list3.Remove(card);
        }
        else if (list4.Contains(card))
        {
            Debug.Log("RES_Check + fromList list4");
            list4.Remove(card);
        }
        else if (list5.Contains(card))
        {
            Debug.Log("RES_Check + fromList list5");
            list5.Remove(card);
        }
        else if (list6.Contains(card))
        {
            Debug.Log("RES_Check + fromList list4");
            list6.Remove(card);
        }
    }

    List<GameObject> draggedcardlist(GameObject card)
    {
        if (list1.Contains(card))
        {
            Debug.Log("RES_Check + fromList list1");
            return list1;
        }
        if (list2.Contains(card))
        {
            Debug.Log("RES_Check + fromList list2");
            return list2;
        }
        if (list3.Contains(card))
        {
            Debug.Log("RES_Check + fromList list3");
            return list3;
        }
        if (list4.Contains(card))
        {
            Debug.Log("RES_Check + fromList list4");
            return list4;
        }
        if (list5.Contains(card))
        {
            Debug.Log("RES_Check + fromList list5");
            return list5;
        }
        if (list6.Contains(card))
        {
            Debug.Log("RES_Check + fromList list4");
            return list6;
        }
        Debug.Log("RES_Check + fromList null");
        return null;
    }

    public void getcardsafterdragging(GameObject card)
    {
        obj.Clear();
        List<List<GameObject>> allLists = new List<List<GameObject>>
        {
            list1,
            list2,
            list3,
            list4,
            list5,
            list6,
        };
        if (list1.Contains(card))
        {
            int index = list1.IndexOf(card);
            if (index != 0)
            {
                for (int i = index; i < list1.Count; i++)
                {
                    obj.Add(list1[i]);
                }

                // Add remaining lists fully
                for (int i = 1; i < allLists.Count; i++)
                {
                    if (allLists[i].Count > 0) // Ensure list is not empty before adding separator
                    {
                        obj.Add(null); // Add empty GameObject (separator)
                    }

                    obj.AddRange(allLists[i]);
                }
            }
        }
        else if (list2.Contains(card))
        {
            int index = list2.IndexOf(card);
            for (int i = index; i < list2.Count; i++)
            {
                obj.Add(list2[i]);
            }

            // Add remaining lists fully
            for (int i = 2; i < allLists.Count; i++)
            {
                if (allLists[i].Count > 0) // Ensure list is not empty before adding separator
                {
                    obj.Add(null); // Add empty GameObject (separator)
                }

                obj.AddRange(allLists[i]);
            }
        }
        else if (list3.Contains(card))
        {
            int index = list3.IndexOf(card);
            for (int i = index; i < list3.Count; i++)
            {
                obj.Add(list3[i]);
            }

            // Add remaining lists fully
            for (int i = 3; i < allLists.Count; i++)
            {
                if (allLists[i].Count > 0) // Ensure list is not empty before adding separator
                {
                    obj.Add(null); // Add empty GameObject (separator)
                }

                obj.AddRange(allLists[i]);
            }
        }
        else if (list4.Contains(card))
        {
            int index = list4.IndexOf(card);
            for (int i = index; i < list4.Count; i++)
            {
                obj.Add(list4[i]);
            }

            // Add remaining lists fully
            for (int i = 4; i < allLists.Count; i++)
            {
                if (allLists[i].Count > 0) // Ensure list is not empty before adding separator
                {
                    obj.Add(null); // Add empty GameObject (separator)
                }

                obj.AddRange(allLists[i]);
            }
        }
        else if (list5.Contains(card))
        {
            int index = list5.IndexOf(card);
            for (int i = index; i < list5.Count; i++)
            {
                obj.Add(list5[i]);
            }

            // Add remaining lists fully
            for (int i = 5; i < allLists.Count; i++)
            {
                if (allLists[i].Count > 0) // Ensure list is not empty before adding separator
                {
                    obj.Add(null); // Add empty GameObject (separator)
                }

                obj.AddRange(allLists[i]);
            }
        }
        else if (list6.Contains(card))
        {
            int index = list6.IndexOf(card);
            for (int i = index; i < list6.Count; i++)
            {
                obj.Add(list6[i]);
            }
        }

        Vector3 currentPosition = obj[0].transform.position; // Start position from first card
        int order2 = obj[0].GetComponent<SpriteRenderer>().sortingOrder;
        float spacing = 0.7f; // Normal spacing
        float emptySpace = 1.0f; // Extra space for empty object

        for (int i = 0; i < obj.Count; i++)
        {
            if (obj[i] != null) // Normal card
            {
                currentPosition.x += spacing;
                obj[i].transform.position = currentPosition;
                obj[i].GetComponent<SpriteRenderer>().sortingOrder = order2;
                obj[i].GetComponent<BoxCollider>().enabled = true;
            }
            else // Empty separator
            {
                currentPosition.x += emptySpace; // Add extra space
            }
        }
    }

    public void AddObjectAfter(GameObject targetcard, GameObject draggedcard, List<GameObject> list)
    {
        int index = list.IndexOf(targetcard);

        if (index != -1)
        {
            list.Insert(index + 1, draggedcard);
            spawnedCards.Remove(draggedcard);
            spawnedCards.Insert(index + 1, draggedcard);
        }
        else
        {
            Debug.LogError("Target object not found in the list.");
        }

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        checkspacing();

        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }
    }

    public void AddObjectAfterDragging(
        GameObject targetcard,
        GameObject draggedcard,
        List<GameObject> list
    )
    {
        int index = list.IndexOf(targetcard);

        if (index != -1)
        {
            list.Insert(index + 1, draggedcard);
            spawnedCards.Remove(draggedcard);
            spawnedCards.Insert(index + 1, draggedcard);
        }
        else
        {
            Debug.LogError("Target object not found in the list.");
        }

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        // checkspacing();

        // Debug.Log("RES_Check + start move initialize");
        // int order = 0;
        // for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        // {
        //     Vector3 targetPosition = cardPositions[i].position;
        //     order++;
        //     spawnedCards[i].transform.position = targetPosition;
        //     spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
        //     spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        // }
    }

    public void AddObjectAfterNewCard(GameObject draggedcard, List<GameObject> list)
    {
        list.Insert(list.Count, draggedcard);
        spawnedCards.Remove(draggedcard);
        spawnedCards.Insert(spawnedCards.Count, draggedcard);

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }
        checkspacing();
        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }

        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();
    }

    public void DraggedCardRearrange(GameObject card)
    {
        if (list1.Count == 0)
        {
            NewGroupAddObjectAfterdragcard(card, list1);
        }
        else if (list2.Count == 0)
        {
            NewGroupAddObjectAfterdragcard(card, list2);
        }
        else if (list3.Count == 0)
        {
            NewGroupAddObjectAfterdragcard(card, list3);
        }
        else if (list4.Count == 0)
        {
            NewGroupAddObjectAfterdragcard(card, list4);
        }
        else if (list5.Count == 0)
        {
            NewGroupAddObjectAfterdragcard(card, list5);
        }
        else
        {
            NewGroupAddObjectAfterdragcard(card, list6);
        }

        List<GameObject> fromlist = draggedcardlist(card);
        fromlist.Remove(card);

        finallist.Clear();
        //goupshifting();
        checkspacing();
    }

    public void NewGroupAddObjectAfterdragcard(GameObject draggedcard, List<GameObject> list)
    {
        list.Insert(list.Count, draggedcard);
        spawnedCards.Remove(draggedcard);
        spawnedCards.Insert(spawnedCards.Count, draggedcard);

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            if (spawnedCards[i] == draggedcard)
            {
                Vector3 targetPosition = cardPositions[i].position;
                order++;
                spawnedCards[i].transform.position = targetPosition;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
            }
        }
    }

    public void ClickedNewGroupAddObjectAfterNewCard(GameObject draggedcard, List<GameObject> list)
    {
        list.Insert(list.Count, draggedcard);
        spawnedCards.Remove(draggedcard);
        spawnedCards.Insert(spawnedCards.Count, draggedcard);

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 targetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }
    }

    public void rearrangespawncards(
        GameObject targetcard,
        GameObject draggedcard,
        List<GameObject> list
    )
    {
        int index = list.IndexOf(targetcard);

        if (index != -1)
        {
            list.Insert(index + 1, draggedcard);
        }
        else
        {
            Debug.LogError("Target object not found in the list.");
        }

        //StartCoroutine(MoveCardsingroup());
    }

    public IEnumerator MoveCardBack(GameObject card, Vector3 targetPosition, float duration)
    {
        if (card.GetComponent<Card>().cardup == true)
        {
            float elapsed = 0f;
            Vector3 startPosition = card.transform.position;
            // Enable the collider of the dragged card after dragging is complete
            Collider draggedCollider = draggedCard.GetComponent<Collider>();
            if (draggedCollider != null)
            {
                draggedCollider.enabled = true;
            }
            while (elapsed < duration)
            {
                card.transform.position = Vector3.Lerp(
                    startPosition,
                    targetPosition,
                    elapsed / duration
                );
                elapsed += Time.deltaTime;
                yield return null;
            }
            card.SetActive(true);
            card.transform.position = targetPosition;
            card.GetComponent<Card>().cardup = false;
            cardsdown();
        }
        else
        {
            float elapsed = 0f;
            Vector3 startPosition = card.transform.position;
            // Enable the collider of the dragged card after dragging is complete
            Collider draggedCollider = draggedCard.GetComponent<Collider>();
            if (draggedCollider != null)
            {
                draggedCollider.enabled = true;
            }
            while (elapsed < duration)
            {
                card.transform.position = Vector3.Lerp(
                    startPosition,
                    targetPosition,
                    elapsed / duration
                );
                elapsed += Time.deltaTime;
                yield return null;
            }
            card.SetActive(true);
            card.transform.position = targetPosition;
        }

        card.SetActive(true);
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 target = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = target;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
            spawnedCards[i].SetActive(true);
        }
    }

    #endregion

    #region rearrange list

    void ShiftLists()
    {
        if (list1.Count == 0)
        {
            ShiftElements(list2, list1);
            ShiftElements(list3, list2);
            ShiftElements(list4, list3);
            ShiftElements(list5, list4);
            ShiftElements(list6, list5);
        }
        if (list2.Count == 0)
        {
            ShiftElements(list3, list2);
            ShiftElements(list4, list3);
            ShiftElements(list5, list4);
            ShiftElements(list6, list5);
        }
        if (list3.Count == 0)
        {
            ShiftElements(list4, list3);
            ShiftElements(list5, list4);
            ShiftElements(list6, list5);
        }
        if (list4.Count == 0)
        {
            ShiftElements(list5, list4);
            ShiftElements(list6, list5);
        }
        if (list5.Count == 0)
        {
            ShiftElements(list6, list5);
        }
    }

    void goupshifting()
    {
        #region previous code
        //List<int> countTemp = new List<int>();

        //// Add non-empty lists to the final list
        //if (list1.Count > 0)
        //{
        //    listtemp1 = list1;
        //    Debug.Log("RES_Check + List 1 Count " + list1.Count);
        //    Debug.Log("RES_Check + listtemp1 Count " + listtemp1.Count);
        //}
        //if (list2.Count > 0)
        //    list2temp = list2;
        //if (list3.Count > 0)
        //    list3temp = list3;
        //if (list4.Count > 0)
        //    list4temp = list4;
        //if (list5.Count > 0)
        //    list5temp = list5;
        //if (list6.Count > 0)
        //    list6temp = list6;

        //if(listtemp1.Count > 0)
        //{
        //    Debug.Log("RES_Check + listtemp1 Count 2 " + listtemp1.Count);
        //    countTemp.Add(1);
        //}
        //if (list2temp.Count > 0)
        //{
        //    countTemp.Add(2);
        //}
        //if (list3temp.Count > 0)
        //{
        //    countTemp.Add(3);
        //}
        //if (list4temp.Count > 0)
        //{
        //    countTemp.Add(4);
        //}
        //if (list5temp.Count > 0)
        //{
        //    countTemp.Add(5);
        //}
        //if (list6temp.Count > 0)
        //{
        //    countTemp.Add(6);
        //}

        //for(int i =0; i < countTemp.Count; i++)
        //{
        //    if(i == 0)
        //    {
        //        Debug.Log("RES_Check + listtemp1 Count 3" + listtemp1.Count);
        //        int num = 0;
        //        num = countTemp[i];
        //        list1 = gettemp(num);
        //    }
        //    if (i == 1)
        //    {
        //        int num = 0;
        //        num = countTemp[i];
        //        list2 = gettemp(num);
        //    }
        //    if (i == 2)
        //    {
        //        int num = 0;
        //        num = countTemp[i];
        //        list3 = gettemp(num);
        //    }
        //    if (i == 3)
        //    {
        //        int num = 0;
        //        num = countTemp[i];
        //        list4 = gettemp(num);
        //    }
        //    if (i == 4)
        //    {
        //        int num = 0;
        //        num = countTemp[i];
        //        list5 = gettemp(num);
        //    }
        //    if (i == 5)
        //    {
        //        int num = 0;
        //        num = countTemp[i];
        //        list6 = gettemp(num);
        //    }
        //}
        #endregion
        finallist.Clear();
        Debug.Log("RES_Check + List 1 Count before adding" + list1.Count);
        if (list1.Count > 0)
        {
            finallist.Add(list1);
            Debug.Log("RES_Check + List 1 Count " + list1.Count);
        }
        Debug.Log("RES_Check + List 2 Count before adding" + list2.Count);
        if (list2.Count > 0)
        {
            finallist.Add(list2);
            Debug.Log("RES_Check + List 2 Count " + list2.Count);
        }
        Debug.Log("RES_Check + List 3 Count before adding" + list3.Count);
        if (list3.Count > 0)
        {
            finallist.Add(list3);
            Debug.Log("RES_Check + List 3 Count " + list3.Count);
        }
        Debug.Log("RES_Check + List 4 Count before adding" + list4.Count);
        if (list4.Count > 0)
        {
            finallist.Add(list4);
            Debug.Log("RES_Check + List 4 Count " + list4.Count);
        }
        Debug.Log("RES_Check + List 5 Count before adding" + list5.Count);
        if (list5.Count > 0)
        {
            finallist.Add(list5);
            Debug.Log("RES_Check + List 5 Count " + list5.Count);
        }
        Debug.Log("RES_Check + List 6 Count  before adding" + list6.Count);
        if (list6.Count > 0)
        {
            finallist.Add(list6);
            Debug.Log("RES_Check + List 6 Count " + list6.Count);
        }

        Debug.Log("RES_Check + finallist " + finallist.Count);

        //if (finallist.Count > 0) list1 = finallist[0];
        //if (finallist.Count > 1) list2 = finallist[1];
        //if (finallist.Count > 2) list3 = finallist[2];
        //if (finallist.Count > 3) list4 = finallist[3];
        //if (finallist.Count > 4) list5 = finallist[4];
        //if (finallist.Count > 5) list6 = finallist[5];



        if (finallist.Count == 1)
        {
            Debug.Log("RES_Check + block 1");
            Debug.Log("RES_Check + fnallist list 1 + " + finallist[0].Count);
            list1 = finallist[0];
            list2 = null;
            list3 = null;
            list4 = null;
            list5 = null;
            list6 = null;
        }
        else if (finallist.Count == 2)
        {
            Debug.Log("RES_Check + block 2");
            Debug.Log("RES_Check + fnallist list 1 + " + finallist[0].Count);
            Debug.Log("RES_Check + fnallist list 2 + " + finallist[1].Count);
            list1 = finallist[0];
            list2 = finallist[1];
            list3 = null;
            list4 = null;
            list5 = null;
            list6 = null;
        }
        else if (finallist.Count == 3)
        {
            Debug.Log("RES_Check + block 3");
            list1 = finallist[0];
            list2 = finallist[1];
            list3 = finallist[2];
            list4 = null;
            list5 = null;
            list6 = null;
        }
        else if (finallist.Count == 4)
        {
            Debug.Log("RES_Check + block 4");
            Debug.Log("RES_Check + fnallist list 1 + " + finallist[0].Count);
            Debug.Log("RES_Check + fnallist list 2 + " + finallist[1].Count);
            Debug.Log("RES_Check + fnallist list 3 + " + finallist[2].Count);
            Debug.Log("RES_Check + fnallist list 4 + " + finallist[3].Count);
            list1 = finallist[0];
            list2 = finallist[1];
            list3 = finallist[2];
            list4 = finallist[3];
            list5 = null;
            list6 = null;
        }
        else if (finallist.Count == 5)
        {
            Debug.Log("RES_Check + block 5");
            Debug.Log("RES_Check + fnallist list 1 + " + finallist[0].Count);
            Debug.Log("RES_Check + fnallist list 2 + " + finallist[1].Count);
            Debug.Log("RES_Check + fnallist list 3 + " + finallist[2].Count);
            Debug.Log("RES_Check + fnallist list 4 + " + finallist[3].Count);
            Debug.Log("RES_Check + fnallist list 5 + " + finallist[4].Count);
            list1 = finallist[0];
            list2 = finallist[1];
            list3 = finallist[2];
            list4 = finallist[3];
            list5 = finallist[4];
            list6 = null;
        }
        else
        {
            Debug.Log("RES_Check + block 6");
            list1 = finallist[0];
            list2 = finallist[1];
            list3 = finallist[2];
            list4 = finallist[3];
            list5 = finallist[4];
            list6 = finallist[5];
        }

        if (list1 == null)
            list1 = new List<GameObject>();
        if (list2 == null)
            list2 = new List<GameObject>();
        if (list3 == null)
            list3 = new List<GameObject>();
        if (list4 == null)
            list4 = new List<GameObject>();
        if (list5 == null)
            list5 = new List<GameObject>();
        if (list6 == null)
            list6 = new List<GameObject>();

        Debug.Log("RES_Check + list1 " + list1.Count);
        Debug.Log("RES_Check + list2 " + list2.Count);
        Debug.Log("RES_Check + list3 " + list3.Count);
        Debug.Log("RES_Check + list4 " + list4.Count);
        Debug.Log("RES_Check + list5 " + list5.Count);
        Debug.Log("RES_Check + list6 " + list6.Count);
    }

    public List<GameObject> removecardforgrouping(List<GameObject> cards)
    {
        List<GameObject> tempcards = new List<GameObject>();

        for (int i = 0; i < cards.Count; i++)
        {
            cards.Remove(cards[i]);

            tempcards = cards;
        }

        return tempcards;
    }

    List<GameObject> gettemp(int value)
    {
        if (value == 1)
        {
            return listtemp1;
        }
        else if (value == 2)
        {
            return list2temp;
        }
        else if (value == 3)
        {
            return list3temp;
        }
        else if (value == 4)
        {
            return list4temp;
        }
        else if (value == 5)
        {
            return list5temp;
        }
        else
        {
            return list6temp;
        }
    }

    void ShiftElements(List<GameObject> sourceList, List<GameObject> destinationList)
    {
        foreach (GameObject obj in sourceList)
        {
            destinationList.Add(obj);
        }
        sourceList.Clear();
    }

    #endregion

    #region UIButtons
    public void clickedgroupbutton()
    {
        if (lists[5].Count > 0)
        {
            StartCoroutine(showtext(warning, "You already have 6 groups"));
            cardsdown();
            uplist.Clear();
            groupButton.SetActive(false);
        }
        else
        {
            int emptylistindex = 0;

            //ShiftLists();

            //for (int i = 0; i < lists.Length; i++)
            //{
            //    if (lists[i].Count == 0)
            //    {
            //        emptylistindex = i;
            //        Debug.Log("RES_Values + Emptylist " + emptylistindex);
            //        break;
            //    }
            //}

            if (list1.Count == 0)
            {
                Debug.Log("RES_Value + Emptylist + list1");
                for (int i = 0; i < uplist.Count; i++)
                {
                    ClickedNewGroupAddObjectAfterNewCard(uplist[i], list1);
                }
                Debug.Log("RES_Value + filledlist + list1 " + list1.Count);
            }
            else if (list2.Count == 0)
            {
                Debug.Log("RES_Value + Emptylist + list2");
                for (int i = 0; i < uplist.Count; i++)
                {
                    ClickedNewGroupAddObjectAfterNewCard(uplist[i], list2);
                }
                Debug.Log("RES_Value + filledlist + list1 " + list2.Count);
            }
            else if (list3.Count == 0)
            {
                Debug.Log("RES_Value + Emptylist + list3");
                for (int i = 0; i < uplist.Count; i++)
                {
                    ClickedNewGroupAddObjectAfterNewCard(uplist[i], list3);
                }
                Debug.Log("RES_Value + filledlist + list1 " + list3.Count);
            }
            else if (list4.Count == 0)
            {
                Debug.Log("RES_Value + Emptylist + list4");
                for (int i = 0; i < uplist.Count; i++)
                {
                    ClickedNewGroupAddObjectAfterNewCard(uplist[i], list4);
                }
                Debug.Log("RES_Value + filledlist + list1 " + list4.Count);
            }
            else if (list5.Count == 0)
            {
                Debug.Log("RES_Value + Emptylist + list5");
                for (int i = 0; i < uplist.Count; i++)
                {
                    ClickedNewGroupAddObjectAfterNewCard(uplist[i], list5);
                }
                Debug.Log("RES_Value + filledlist + list1 " + list5.Count);
            }
            else
            {
                Debug.Log("RES_Value + Emptylist + list6");
                for (int i = 0; i < uplist.Count; i++)
                {
                    ClickedNewGroupAddObjectAfterNewCard(uplist[i], list6);
                }
                Debug.Log("RES_Value + filledlist + list1 " + list6.Count);
            }

            //for (int i = 0; i < uplist.Count; i++)
            //{
            //    ClickedNewGroupAddObjectAfterNewCard(uplist[i], lists[emptylistindex]);
            //}

            for (int i = 0; i < uplist.Count; i++)
            {
                uplist[i].GetComponent<Card>().cardup = false;

                string hexColor = "#FFFFFF";
                Color color;

                if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
                {
                    uplist[i].GetComponent<SpriteRenderer>().color = color;
                }
                List<GameObject> fromlist = draggedcardlist(uplist[i]);
                RemovefromSelectedlist(uplist[i]);
                Debug.Log("RES_Check + draggedcardlist + " + uplist[i]);
                //Debug.Log("RES_Check + fromlist + " + fromlist.ToString());
            }

            finallist.Clear();
            //goupshifting();
            ClickedGroupcheckspacing();

            Debug.Log("RES_Check + start move initialize");
            int order = 0;
            for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
            {
                Vector3 targetPosition = cardPositions[i].position;
                order++;
                spawnedCards[i].transform.position = targetPosition;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
            }

            canvastext(list1, list2, list3, list4, list5, list6);
            CheckAfterEveryMove();

            uplist.Clear();
            //uplist = null;
            //uplist = new List<GameObject>();
            groupButton.SetActive(false);
        }
    }

    public void reload()
    {
        SceneManager.LoadScene("SampleScene");
    }

    IEnumerator MoveDiscardCard(GameObject card, Vector3 targetPosition, float duration)
    {
        float elapsed = 0f;
        Vector3 startPosition = card.transform.position;

        Debug.Log("RES_Check + autochaal");
        while (elapsed < duration)
        {
            card.transform.position = Vector3.Lerp(
                startPosition,
                targetPosition,
                elapsed / duration
            );
            elapsed += Time.deltaTime;
            yield return null;
        }

        card.transform.position = targetPosition;

        spawnedCards.Remove(card);
        List<GameObject> fromlist = draggedcardlist(card);
        fromlist.Remove(card);

        for (int i = 0; i < cardPositions.Count; i++)
        {
            cardPositions[i].transform.position = initializedcardPositions[i];
        }

        checkspacing();

        Debug.Log("RES_Check + start move initialize");
        int order = 0;
        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        {
            Vector3 TargetPosition = cardPositions[i].position;
            order++;
            spawnedCards[i].transform.position = TargetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        }

        //Destroy(card);

        cardsdown();
        dicardcardbutton.SetActive(false);
        groupButton.SetActive(false);
        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();
    }

    public void autochaal(GameObject card)
    {
        StartCoroutine(
            MoveDiscardCard(card, GameObject.Find("Discard_Area").transform.position, 0.5f)
        );

        #region Previous Code
        //if (spawnedCards.Count == 14)
        //{
        //    draggedCard = null;
        //    isDragging = false;
        //    StartCoroutine(hidecolliders());
        //    string droppedcard = card.GetComponent<Card>().name.ToUpper();
        //    string hexColor = "#FFFFFF";
        //    Color color;

        //    if (UnityEngine.ColorUtility.TryParseHtmlString(hexColor, out color))
        //    {
        //        card.GetComponent<SpriteRenderer>().color = color;
        //    }

        //    if (!string.IsNullOrEmpty(droppedcard))
        //    {
        //        cachetmanager.Idropped = droppedcard;
        //        Debug.Log("RES_Value + " + droppedcard + " autochaal");
        //        cachetmanager.API_CALL_drop_card(droppedcard);
        //        GameObject.Find("ProfilePic").transform.GetChild(0).GetComponent<PointRummyChaalSlider>().discardglow.SetActive(false);
        //        GameObject.Find("ProfilePic").transform.GetChild(0).GetComponent<PointRummyChaalSlider>().finishdeskglow.SetActive(false);
        //    }
        //    else
        //    {
        //        Debug.LogError("Card name is null or empty.");
        //        return;
        //    }

        //    StartCoroutine(MoveDiscardCard(card, GameObject.Find("Discard_Area").transform.position
        //        , 0.5f));
        //    spawnedCards.Remove(card);
        //    List<GameObject> fromlist = draggedcardlist(card);
        //    fromlist.Remove(card);
        //    //discardedlist.Add(card);

        //    //int display = 0;
        //    //for (int i = 0; i < discardedlist.Count; i++)
        //    //{
        //    //    display++;
        //    //    discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
        //    //}

        //    //for (int i = 0; i < discardedlist.Count; i++)
        //    //{
        //    //    discardedlist[i].GetComponent<BoxCollider>().enabled = false;
        //    //    discardedlist[i].GetComponent<Card>().enabled = false;
        //    //    discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
        //    //}

        //    for (int i = 0; i < cardPositions.Count; i++)
        //    {
        //        cardPositions[i].transform.position = initializedcardPositions[i];
        //    }

        //    checkspacing();

        //    Debug.Log("RES_Check + start move initialize");
        //    int order = 0;
        //    for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        //    {
        //        Vector3 targetPosition = cardPositions[i].position;
        //        order++;
        //        spawnedCards[i].transform.position = targetPosition;
        //        spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
        //        spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        //    }

        //    //for (int i = 0; i < discardedlist.Count - 1; i++)
        //    //{
        //    //    if(discardedlist[i].transform.childCount > 0)
        //    //       Destroy(discardedlist[i].transform.GetChild(0).gameObject);
        //    //}

        //    //discardedcard.transform.localScale = new Vector3(1.3f, 1.2f, 0);

        //    cardsdown();
        //    dicardcardbutton.SetActive(false);
        //    groupButton.SetActive(false);
        //    canvastext(list1, list2, list3, list4, list5, list6);
        //    CheckAfterEveryMove();
        //}
        #endregion
    }

    public void clickeddiscard()
    {
        if (spawnedCards.Count == 14)
        {
            Debug.Log("RES_Check + DiscardCard called");
            if (uplist[0] != null)
            {
                discardCard = uplist[0];
                string droppedcard = discardCard.GetComponent<Card>().name.ToUpper();
                if (!string.IsNullOrEmpty(droppedcard))
                {
                    cachetmanager.Idropped = droppedcard;
                    Debug.Log("RES_Check + " + droppedcard + " DiscardCard");
                    cachetmanager.API_CALL_drop_card(droppedcard);
                    discardCard.SetActive(false);
                    //StartCoroutine(waitfordropcardresponse());
                }
                else
                {
                    Debug.LogError("Card name is null or empty.");
                    Destroy(discardCard);
                    return;
                }
            }
            else
            {
                showtoastmessage("RES_Check + Dicard card is empty");
            }
        }
        else
        {
            StartCoroutine(showtext(warning, "You have 13 cards, please draw a card to discard."));
            cardsdown();
            uplist.Clear();
            dicardcardbutton.SetActive(false);
        }
    }

    public void finishno()
    {
        finished = false;
        finishdeskcard.transform.localScale = new Vector3(1.5f, 1.5f, 1.5f);
        StartCoroutine(MoveCardBack(finishdeskcard, finishdeskcardogpos, 0.5f));
        finishdeskcard = null;
        finishdeskcardogpos = Vector3.zero;
        declaredialogue.SetActive(false);
    }

    public void Finishyes()
    {
        if (
            finishdeskcard.GetComponent<Card>().name.ToLower() != "jkr1"
            && finishdeskcard.GetComponent<Card>().name.ToLower() != "jkr2"
        )
        {
            isdeclared = true;
            Debug.Log("RES_Check + declare yes");
            cachetmanager.API_CALL_drop_card(finishdeskcard.GetComponent<Card>().name.ToUpper());
            finished = false;
            declaredialogue.SetActive(false);
        }
        else
        {
            showtoastmessage("Cannot Declare with Joker");
            finished = false;
            finishdeskcard.transform.localScale = new Vector3(1.5f, 1.5f, 1.5f);
            StartCoroutine(MoveCardBack(finishdeskcard, finishdeskcardogpos, 0.5f));
            finishdeskcard = null;
            finishdeskcardogpos = Vector3.zero;
            declaredialogue.SetActive(false);
        }

        #region Previous Code
        //if (spawnedCards.Contains(finishdeskcard))
        //{
        //    spawnedCards.Remove(finishdeskcard);
        //}

        //GameObject.Find("ProfilePic").transform.GetChild(0).GetComponent<PointRummyChaalSlider>().discardglow.SetActive(false);
        //GameObject.Find("ProfilePic").transform.GetChild(0).GetComponent<PointRummyChaalSlider>().finishdeskglow.SetActive(false);

        //List<GameObject> fromlist = draggedcardlist(finishdeskcard);
        //fromlist.Remove(finishdeskcard);
        ////Discardedlist.Add(finishdeskcard);
        //for (int i = 0; i < cardPositions.Count; i++)
        //{
        //    cardPositions[i].transform.position = initializedcardPositions[i];
        //}

        //checkspacing();

        //Debug.Log("RES_Check + start move initialize");
        //int order = 0;
        //for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
        //{
        //    Vector3 targetPosition = cardPositions[i].position;
        //    order++;
        //    spawnedCards[i].transform.position = targetPosition;
        //    spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
        //    spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
        //}
        ////discardedcard.transform.localScale = new Vector3(1.3f, 1.2f, 0);

        ////int order2 = 1;
        ////for (int i = 0; i < Discardedlist.Count; i++)
        ////{
        ////    Discardedlist[i].GetComponent<BoxCollider>().enabled = false;
        ////    Discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = order2;
        ////    order2++;
        ////}
        //isdeclared = true;
        //draggedCard.GetComponent<Card>().enabled = false;
        //canvastext(list1, list2, list3, list4, list5, list6);
        //CheckAfterEveryMove();
        //Debug.Log("RES_Check + declare yes");
        //cachetmanager.API_CALL_drop_card(finishdeskcard.name.ToUpper());
        //finished = false;
        //declaredialogue.SetActive(false);
        #endregion
    }

    public void Finishwrongyes()
    {
        isdeclared = true;
        wrongdecralre = true;
        Debug.Log("RES_Check + declare yes");
        cachetmanager.API_CALL_drop_card(finishdeskcard.name.ToUpper());
        finished = false;
    }

    public void dropcardscript(GameObject card)
    {
        GameObject discardcard = card;
        // Discard the card
        DiscardCard(discardcard);

        Collider draggedCollider1 = card.GetComponent<Collider>();
        if (draggedCollider1 != null)
        {
            draggedCollider1.enabled = true;
        }
    }

    #endregion

    #region getcard

    IEnumerator showtext(TextMeshProUGUI warntext, string message)
    {
        warntext.gameObject.SetActive(true);
        warntext.text = message;
        yield return new WaitForSeconds(2);
        warntext.gameObject.SetActive(false);
    }

    //public IEnumerator InatntiateSingleCardDiscardarea()
    //{
    //    yield return new WaitForSeconds(0.0f);

    //    string newaskedcard = cachetmanager.askedCard;

    //    string formattednewcard = newaskedcard.ToLower();

    //    GameObject get = FindGameObjectByName(formattednewcard);

    //    GameObject getcard = Instantiate(get);

    //    getcardfromserver = getcard;

    //    Debug.Log("RES_Check + getcardfromserver + " + getcardfromserver);

    //    if (list6.Count > 0)
    //    {
    //        getcard.transform.position = GameObject.Find("Discard_Area").transform.position;

    //        getcard.name = formattednewcard;

    //        drawnard = getcard;

    //        list6.Add(getcard);

    //        spawnedCards.Add(getcard);

    //        for (int i = 0; i < cardPositions.Count; i++)
    //        {
    //            cardPositions[i].transform.position = initializedcardPositions[i];
    //        }

    //        checkspacing();

    //        StartCoroutine(MoveNewCard(getcard, cardPositions[13].position, 0.5f));

    //        Debug.Log("RES_Check + single card move initialize");
    //        int order = 0;
    //        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
    //        {
    //            Vector3 targetPosition = cardPositions[i].position;
    //            order++;
    //            spawnedCards[i].transform.position = targetPosition;
    //            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
    //            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
    //        }
    //    }
    //    else
    //    {
    //        int fullestListIndex = -1;

    //        for (int i = 0; i < lists.Length; i++)
    //        {
    //            if (lists[i].Count == 0)
    //            {
    //                fullestListIndex = i;
    //                break;
    //            }
    //        }

    //        getcard.transform.position = GameObject.Find("Discard_Area").transform.position;

    //        getcard.name = formattednewcard;

    //        drawnard = getcard;

    //        lists[fullestListIndex].Add(getcard);

    //        spawnedCards.Add(getcard);

    //        for (int i = 0; i < cardPositions.Count; i++)
    //        {
    //            cardPositions[i].transform.position = initializedcardPositions[i];
    //        }

    //        checkspacing();

    //        StartCoroutine(MoveNewCard(getcard, cardPositions[13].position, 0.5f));

    //        Debug.Log("RES_Check + single card move initialize");
    //        int order = 0;
    //        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
    //        {
    //            Vector3 targetPosition = cardPositions[i].position;
    //            order++;
    //            spawnedCards[i].transform.position = targetPosition;
    //            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
    //            spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
    //        }
    //    }
    //    canvastext(list1, list2, list3, list4, list5, list6);
    //    CheckAfterEveryMove();
    //}

    public IEnumerator InatntiateSingleCard(string objectplaceholder)
    {
        yield return new WaitForSeconds(0.0f);

        string newaskedcard = cachetmanager.askedCard;

        string formattednewcard = newaskedcard.ToLower();

        GameObject get = FindGameObjectByName(formattednewcard);

        GameObject getcard = Instantiate(get);

        getcardfromserver = getcard;

        if (list6.Count > 0)
        {
            getcard.transform.position = GameObject.Find(objectplaceholder).transform.position;

            getcard.name = formattednewcard;

            drawnard = getcard;

            list6.Add(getcard);

            spawnedCards.Add(getcard);

            for (int i = 0; i < cardPositions.Count; i++)
            {
                cardPositions[i].transform.position = initializedcardPositions[i];
            }

            checkspacing();

            StartCoroutine(MoveNewCard(getcard, cardPositions[13].position, 0.5f));

            Debug.Log("RES_Check + single card move initialize");
            int order = 0;
            for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
            {
                Vector3 targetPosition = cardPositions[i].position;
                order++;
                spawnedCards[i].transform.position = targetPosition;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
            }
        }
        else
        {
            int fullestListIndex = -1;

            for (int i = 0; i < lists.Length; i++)
            {
                if (lists[i].Count == 0)
                {
                    fullestListIndex = i;
                    break;
                }
            }

            if (list1.Count == 0)
            {
                list1.Add(getcard);
            }
            else if (list2.Count == 0)
            {
                list2.Add(getcard);
            }
            else if (list3.Count == 0)
            {
                list3.Add(getcard);
            }
            else if (list4.Count == 0)
            {
                list4.Add(getcard);
            }
            else if (list5.Count == 0)
            {
                list5.Add(getcard);
            }
            else
            {
                list6.Add(getcard);
            }

            getcard.transform.position = GameObject.Find(objectplaceholder).transform.position;

            getcard.name = formattednewcard;

            drawnard = getcard;

            //lists[fullestListIndex].Add(getcard);

            spawnedCards.Add(getcard);

            for (int i = 0; i < cardPositions.Count; i++)
            {
                cardPositions[i].transform.position = initializedcardPositions[i];
            }

            checkspacing();

            StartCoroutine(MoveNewCard(getcard, cardPositions[13].position, 0.5f));

            Debug.Log("RES_Check + single card move initialize");
            int order = 0;
            for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
            {
                Vector3 targetPosition = cardPositions[i].position;
                order++;
                spawnedCards[i].transform.position = targetPosition;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
            }
        }
        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();
    }

    public void AddDiacrdCardtoSpawnedCard()
    {
        if (list6.Count > 0)
        {
            GameObject getcard = GameObject.Find(discardedcard);

            getcardfromserver = getcard;

            getcard.GetComponent<Card>().enabled = false;

            //discardedlist.Remove(getcard);

            drawnard = getcard;

            list6.Add(getcard);

            spawnedCards.Add(getcard);

            for (int i = 0; i < cardPositions.Count; i++)
            {
                cardPositions[i].transform.position = initializedcardPositions[i];
            }

            checkspacing();

            StartCoroutine(MoveNewCard(getcard, cardPositions[13].position, 0.5f));

            Debug.Log("RES_Check + single card move initialize");
            int order = 0;
            for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
            {
                Vector3 targetPosition = cardPositions[i].position;
                order++;
                spawnedCards[i].transform.position = targetPosition;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
                spawnedCards[i].transform.transform.localScale = new Vector3(1.5f, 1.5f, 0);
            }
        }
        else
        {
            int fullestListIndex = -1;

            for (int i = 0; i < lists.Length; i++)
            {
                if (lists[i].Count == 0)
                {
                    fullestListIndex = i;
                    break;
                }
            }

            GameObject getcard = GameObject.Find(discardedcard);

            drawnard = getcard;

            //discardedlist.Remove(getcard);

            lists[fullestListIndex].Add(getcard);

            spawnedCards.Add(getcard);

            for (int i = 0; i < cardPositions.Count; i++)
            {
                cardPositions[i].transform.position = initializedcardPositions[i];
            }

            checkspacing();

            StartCoroutine(MoveNewCard(getcard, cardPositions[13].position, 0.5f));

            Debug.Log("RES_Check + single card move initialize");
            int order = 0;
            for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardPositions.Count); i++)
            {
                Vector3 targetPosition = cardPositions[i].position;
                order++;
                spawnedCards[i].transform.position = targetPosition;
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards[i].GetComponent<BoxCollider>().enabled = true;
                spawnedCards[i].transform.transform.localScale = new Vector3(1.5f, 1.5f, 0);
            }
        }

        int order3 = 0;
        for (int i = 0; i < 14; i++)
        {
            order3++;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order3;
            Debug.Log(
                "RES_Check + order + " + spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder
            );
        }
        canvastext(list1, list2, list3, list4, list5, list6);
        CheckAfterEveryMove();
    }

    IEnumerator MoveNewCard(GameObject card, Vector3 targetPosition, float duration)
    {
        float elapsed = 0f;
        Vector3 startPosition = card.transform.position;
        AudioManager._instance.ButtonClick();
        while (elapsed < duration)
        {
            card.transform.position = Vector3.Lerp(
                startPosition,
                targetPosition,
                elapsed / duration
            );
            elapsed += Time.deltaTime;
            yield return null;
        }

        card.transform.position = targetPosition;

        foreach (GameObject obj in spawnedCards)
        {
            obj.GetComponent<Card>().enabled = true;
            obj.GetComponent<BoxCollider>().enabled = true;
        }
    }

    #endregion

    #region Server card add remove functions

    IEnumerator firsttimediscardcardfromserver(string card)
    {
        yield return new WaitForSeconds(1);
        discardcard = true;
        Debug.Log("Add drop card 3");
        Debug.Log("Add drop card " + card);
        //foundcard = find(card);
        foreach (GameObject go in deck)
        {
            if (go.name == card)
            {
                Debug.Log("Add drop card name to find " + go.name);
                foundcard = go;
            }
        }
        Debug.Log("Add drop card name to found");
        Vector3 discardpos = GameObject.Find("Discard_Area").transform.position;
        Debug.Log(foundcard.name + " droppedcard by opponent");
        GameObject discardedcard = Instantiate(foundcard);
        discardedcard.GetComponent<Animator>().enabled = false;
        discardedcard.transform.position = discardpos;
        discardedcard.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
        discardedcard.name = card;
        Debug.Log("Added" + discardedcard);
        //discardedlist.Add(discardedcard);

        //int display = 0;
        //for (int i = 0; i < discardedlist.Count; i++)
        //{
        //    display++;
        //    discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
        //}

        //for (int i = 0; i < discardedlist.Count; i++)
        //{
        //    discardedlist[i].GetComponent<BoxCollider>().enabled = false;
        //    discardedlist[i].GetComponent<Card>().enabled = false;
        //    discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
        //}

        //if(GetCardRank(discardedlist[discardedlist.Count - 1]) == GetCardRank(wildcard))
        //{
        //    if (discardedlist[discardedlist.Count - 1].transform.childCount == 0)
        //    {
        //        Vector3 pos = new Vector3(-0.3f, -0.35f, 0);
        //        GameObject jok = Instantiate(wildjokerimage, Vector3.zero, Quaternion.identity);
        //        jok.transform.parent = discardedlist[discardedlist.Count - 1].transform;
        //        jok.transform.localPosition = pos;
        //        jok.GetComponent<SpriteRenderer>().sortingOrder = discardedlist[discardedlist.Count - 1].GetComponent<SpriteRenderer>().sortingOrder + 50;
        //    }
        //}
    }

    public void adddiscardedcardsfromserver(string card)
    {
        if (!discardcard)
        {
            StartCoroutine(firsttimediscardcardfromserver(card));
        }
        else
        {
            Debug.Log("Add drop card 3");
            Debug.Log("Add drop card " + card);
            //foundcard = find(card);
            foreach (GameObject go in deck)
            {
                if (go.name == card)
                {
                    Debug.Log("Add drop card name to find " + go.name);
                    foundcard = go;
                }
            }
            Debug.Log("Add drop card name to found");
            Vector3 discardpos = GameObject.Find("Discard_Area").transform.position;
            Debug.Log(foundcard.name + " droppedcard by opponent");
            GameObject discardedcard = Instantiate(foundcard);
            discardedcard.GetComponent<Animator>().enabled = false;
            discardedcard.transform.position = discardpos;
            discardedcard.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
            discardedcard.name = card;
            Debug.Log("Added" + discardedcard);
            //discardedlist.Add(discardedcard);

            //int display = 0;
            //for (int i = 0; i < discardedlist.Count; i++)
            //{
            //    display++;
            //    discardedlist[i].GetComponent<SpriteRenderer>().sortingOrder = display;
            //}

            //for (int i = 0; i < discardedlist.Count; i++)
            //{
            //    discardedlist[i].GetComponent<BoxCollider>().enabled = false;
            //    discardedlist[i].GetComponent<Card>().enabled = false;
            //    discardedlist[i].transform.localScale = new Vector3(1.3f, 1.2f, 0);
            //    if (discardedlist[i].transform.childCount > 0)
            //        Destroy(discardedlist[i].transform.GetChild(0).gameObject);
            //}

            //if(GetCardRank(discardedlist[discardedlist.Count - 1]) == GetCardRank(wildcard))
            //{
            //    if (discardedlist[discardedlist.Count - 1].transform.childCount == 0)
            //    {
            //       Vector3 pos = new Vector3(-0.3f, -0.35f, 0);
            //       GameObject jok = Instantiate(wildjokerimage, Vector3.zero, Quaternion.identity);
            //       jok.transform.parent = discardedlist[discardedlist.Count - 1].transform;
            //       jok.transform.localPosition = pos;
            //       jok.GetComponent<SpriteRenderer>().sortingOrder = discardedlist[discardedlist.Count - 1].GetComponent<SpriteRenderer>().sortingOrder + 50;
            //    }
            //}
        }
    }

    #endregion

    #region check

    public void CheckAfterEveryMove()
    {
        //Debug.Log("Rank Enter");
        numoflist = 0;
        numofmatch = 0;
        numofmatchcards = 0;
        numberofimpuresequence = 0;
        numberofpuresequence = 0;
        isValid = false;

        if (list1.Count != 0)
            numoflist++;
        if (list2.Count != 0)
            numoflist++;
        if (list3.Count != 0)
            numoflist++;
        if (list4.Count != 0)
            numoflist++;
        if (list5.Count != 0)
            numoflist++;
        if (list6.Count != 0)
            numoflist++;

        seqcheck = 0;

        check1 = IsSequence(list1, textcavas[0]);
        check2 = IsSequence(list2, textcavas[1]);
        check3 = IsSequence(list3, textcavas[2]);
        check4 = IsSequence(list4, textcavas[3]);
        check5 = IsSequence(list5, textcavas[4]);
        check6 = IsSequence(list6, textcavas[5]);

        if (check1)
        {
            seqcheck++;
            textcavas[0].GetComponent<PanelPositioner>().active = true;
        }
        if (check2)
        {
            seqcheck++;
            textcavas[1].GetComponent<PanelPositioner>().active = true;
        }
        if (check3)
        {
            seqcheck++;
            textcavas[2].GetComponent<PanelPositioner>().active = true;
        }
        if (check4)
        {
            seqcheck++;
            textcavas[3].GetComponent<PanelPositioner>().active = true;
        }
        if (check5)
        {
            seqcheck++;
            textcavas[4].GetComponent<PanelPositioner>().active = true;
        }
        if (check6)
        {
            seqcheck++;
            textcavas[5].GetComponent<PanelPositioner>().active = true;
        }

        //sequence.text = "Number of Sequence: " + seqcheck;

        setcheck = 0;

        if (!check1)
        {
            check1 = AreAllCardsSameRank(list1, textcavas[0]);
            if (check1)
                setcheck++;
        }
        if (!check2)
        {
            check2 = AreAllCardsSameRank(list2, textcavas[1]);
            if (check2)
                setcheck++;
        }
        if (!check3)
        {
            check3 = AreAllCardsSameRank(list3, textcavas[2]);
            if (check3)
                setcheck++;
        }
        if (!check4)
        {
            check4 = AreAllCardsSameRank(list4, textcavas[3]);
            if (check4)
                setcheck++;
        }
        if (!check5)
        {
            check5 = AreAllCardsSameRank(list5, textcavas[4]);
            if (check5)
                setcheck++;
        }
        if (!check6)
        {
            check6 = AreAllCardsSameRank(list6, textcavas[5]);
            if (check6)
                setcheck++;
        }

        //set.text = "Number of Sets: " + setcheck;

        if (check1)
            textcavas[0].GetComponent<PanelPositioner>().active = true;
        else
            textcavas[0].GetComponent<PanelPositioner>().active = false;
        if (check2)
            textcavas[1].GetComponent<PanelPositioner>().active = true;
        else
            textcavas[1].GetComponent<PanelPositioner>().active = false;
        if (check3)
            textcavas[2].GetComponent<PanelPositioner>().active = true;
        else
            textcavas[2].GetComponent<PanelPositioner>().active = false;
        if (check4)
            textcavas[3].GetComponent<PanelPositioner>().active = true;
        else
            textcavas[3].GetComponent<PanelPositioner>().active = false;
        if (check5)
            textcavas[4].GetComponent<PanelPositioner>().active = true;
        else
            textcavas[4].GetComponent<PanelPositioner>().active = false;
        if (check6)
            textcavas[5].GetComponent<PanelPositioner>().active = true;
        else
            textcavas[5].GetComponent<PanelPositioner>().active = false;

        if (check1)
        {
            numofmatch++;
            numofmatchcards += list1.Count;
        }
        else
            showinvalidnum(textcavas[0], list1);
        if (check2)
        {
            numofmatch++;
            numofmatchcards += list2.Count;
        }
        else
            showinvalidnum(textcavas[1], list2);
        if (check3)
        {
            numofmatch++;
            numofmatchcards += list3.Count;
        }
        else
            showinvalidnum(textcavas[2], list3);
        if (check4)
        {
            numofmatch++;
            numofmatchcards += list4.Count;
        }
        else
            showinvalidnum(textcavas[3], list4);
        if (check5)
        {
            numofmatch++;
            numofmatchcards += list5.Count;
        }
        else
            showinvalidnum(textcavas[4], list5);
        if (check6)
        {
            numofmatch++;
            numofmatchcards += list6.Count;
        }
        else
            showinvalidnum(textcavas[5], list6);

        for (int i = 0; i < puresequenceint; i++)
        {
            if (
                !textcavas[puresequenceint]
                    .transform.GetChild(0)
                    .GetComponent<TextMeshProUGUI>()
                    .text.Contains("Pure Sequence")
            )
                puresequence = false;

            if (
                !textcavas[sequencelist]
                    .transform.GetChild(0)
                    .GetComponent<TextMeshProUGUI>()
                    .text.Contains("Impure Sequence")
            )
                sequence = false;
        }

        for (int i = 0; i < textcavas.Count; i++)
        {
            if (
                textcavas[i]
                    .transform.GetChild(0)
                    .GetComponent<TextMeshProUGUI>()
                    .text.Contains("Pure Sequence")
            )
            {
                numberofpuresequence++;
                puresequenceint = i;
                puresequence = true;
            }
            if (
                textcavas[i]
                    .transform.GetChild(0)
                    .GetComponent<TextMeshProUGUI>()
                    .text.Contains("Impure Sequence")
            )
            {
                numberofimpuresequence++;
                sequencelist = i;
                sequence = true;
            }
        }

        for (int i = 0; i < textcavas.Count; i++)
        {
            Debug.Log("RES_check + success seq " + numberofpuresequence);
            Debug.Log("RES_check + success imseq " + numberofimpuresequence);
            if (numberofpuresequence >= 2 || numberofimpuresequence > 0 && numberofpuresequence > 0)
            {
                isValid = true;
                Debug.Log("RES_check + success");
                if (
                    textcavas[i]
                        .transform.GetChild(0)
                        .GetComponent<TextMeshProUGUI>()
                        .text.Contains("Pure Sequence")
                )
                {
                    textcavas[i].transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        "Pure Sequence";
                }
                else if (
                    textcavas[i]
                        .transform.GetChild(0)
                        .GetComponent<TextMeshProUGUI>()
                        .text.Contains("Impure Sequence")
                )
                {
                    textcavas[i].transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
                        "Impure Sequence";
                }
                else if (
                    textcavas[i]
                        .transform.GetChild(0)
                        .GetComponent<TextMeshProUGUI>()
                        .text.Contains("Set")
                )
                {
                    textcavas[i].transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Set";
                }
            }
        }

        if (sequence)
            sequencetick.SetActive(true);
        else
            sequencetick.SetActive(false);

        if (puresequence)
            puresequecetick.SetActive(true);
        else
            puresequecetick.SetActive(false);

        if (numoflist == numofmatch)
            scoretick.SetActive(true);
        else
            scoretick.SetActive(false);

        //if (numoflist == numofmatch && ispuresequenceincards)
        //{
        //    cachetmanager.API_CALL_declare();
        //}
    }

    public void showinvalidnum(GameObject canvas, List<GameObject> list)
    {
        int num = 0;
        for (int i = 0; i < list.Count; i++)
        {
            int individual = GetCardRank(list[i]);

            if (individual == 1)
                individual = 10;

            if (individual >= 10)
                individual = 10;

            num = num + individual;
        }
        canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid (" + num + ")";

        canvas.GetComponent<PanelPositioner>().active = false;
    }

    public void showvalidnum(GameObject canvas, List<GameObject> list, string numtext)
    {
        int num = 0;
        for (int i = 0; i < list.Count; i++)
        {
            int individual = GetCardRank(list[i]);

            if (individual == 1)
                individual = 10;

            if (individual >= 10)
                individual = 10;

            num = num + individual;
        }
        canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text =
            numtext + "(" + num + ")";

        canvas.GetComponent<PanelPositioner>().active = false;
    }

    void HighlightCardsInSequences(List<GameObject> list)
    {
        //foreach (var card in list)
        //{
        //    if (card.name == "joker")
        //    {
        //        if (card.transform.childCount == 1)
        //            HighlightCard(card);
        //    }

        //    else if (card.transform.childCount == 0)
        //        HighlightCard(card);
        //}
    }

    void HighlightCard(GameObject card)
    {
        GameObject highlightCard = Instantiate(highlighSprite);
        highlightCard.transform.parent = card.transform;
        highlightCard.transform.localPosition = Vector3.zero;
        highlightCard.transform.localScale = new Vector3(0.95f, 1f, 1f);
        Debug.Log("high " + highlightCard.transform.parent.name);
    }

    void RemoveHighlightCardsInSequences(List<GameObject> list)
    {
        //foreach (var card in list)
        //{
        //    if (card.name == "joker")
        //    {
        //        if (card.transform.childCount == 2)
        //            Destroy(card.transform.GetChild(1).gameObject);
        //    }
        //    else if (card.transform.childCount != 0)
        //        Destroy(card.transform.GetChild(0).gameObject);
        //}
    }

    public bool AreAllCardsSameRank(List<GameObject> cards, GameObject canvas)
    {
        HashSet<string> uniqueRanks = new HashSet<string>();
        foreach (GameObject card in cards)
        {
            string rank = GetCardRankFirstPart(card);
            //  Debug.Log("RES_Check + " + rank + cards);
            if (uniqueRanks.Contains(rank))
            {
                // Debug.Log("RES_Check + AreAllCardsSameRank + uniqueRanks.Contains(rank)");
                canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid";
                return false;
            }
            uniqueRanks.Add(rank);
        }

        if (wildcard != null)
            wildcardrank = GetCardRank(wildcard);

        foreach (GameObject card in cards)
        {
            if (GetCardRank(card) == wildcardrank || card.name == "jkr1" || card.name == "jkr2")
                card.name = "joker";
        }

        if (cards.Count <= 2 || cards == null)
        {
            // Debug.Log("RES_Check + AreAllCardsSameRank + cards.Count <= 2 || cards == null");
            canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid";
            return false;
        }

        bool jokerbool = false;
        foreach (GameObject card in cards)
        {
            if (card.name == "joker")
                jokerbool = true;
        }

        if (jokerbool)
        {
            List<GameObject> newlist = new List<GameObject>();
            foreach (GameObject name in cards)
            {
                if (name.name == "joker")
                    continue;
                else
                    newlist.Add(name);
            }

            int referenceRank = GetCardRank(newlist.First());
            //Debug.Log("RES_Check + AreAllCardsSameRank + jokerbool");
            showvalidnum(canvas, cards, "Set");
            //canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Impure Set (0)";
            return newlist.All(newlist => GetCardRank(newlist) == referenceRank);
        }
        else
        {
            int referenceRank = GetCardRank(cards.First());
            // Debug.Log("RES_Check + AreAllCardsSameRank + nojokerbool");
            showvalidnum(canvas, cards, "Set");
            //canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Pure Set (0)";
            return cards.All(card => GetCardRank(card) == referenceRank);
        }
    }

    string GetCardRankFirstPart(GameObject card)
    {
        // Extract the rank from the card name
        string cardName = card.name;

        // Assuming the format is "RANK_SUIT" (e.g., "ACE_DIAMONDS")
        cardName = card.name.Replace("_", "");
        string[] parts = SplitString(cardName);

        if (parts.Length >= 1)
        {
            return parts[0];
        }

        return ""; // Default to an empty string if the format is not as expected
    }

    public bool checksequencesuit(List<GameObject> cards)
    {
        List<string> suits = new List<string>();

        foreach (var item in cards)
        {
            string suitname = GetCardSuit(item);
            if (suitname != "jo")
                suits.Add(suitname);
        }
        List<int> suitrank = new List<int>();
        foreach (var item in cards)
        {
            string suitname = GetCardSuit(item);
            if (suitname != "jo")
            {
                suitrank.Add(GetCardRank(item));
            }
        }

        suitrank.Sort();

        if (suitrank.Count != suitrank.Distinct().Count())
        {
            return false;
        }
        else
        {
            if (suits.Distinct().Count() == 1)
            {
                return true;
            }
            else
                return false;
        }
    }

    public bool IsSequence(List<GameObject> cards, GameObject canvas)
    {
        if (cards.Count <= 2)
        {
            //Debug.Log("Rank Enter count " + cards.Count);
            canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid (0)";
            return false;
        }

        string firstSuit = GetFirstSuit(cards);
        if (firstSuit == "")
        {
            canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid (0)";
            // Handle the case where the suit cannot be determined
            return false;
        }

        bool check = checksequencesuit(cards);

        if (check)
        {
            HandleWildCards(cards);

            HandleJokerCards(cards);

            int jokerCount = CountJokers(cards);

            if (jokerCount > 0)
            {
                bool isseqence = CheckSequenceWithoutJokers(cards, canvas);
                //  Debug.Log("Res_Check + sequencewithoutjoker + " + isseqence);
                if (isseqence)
                {
                    return CheckSequenceWithoutJokers(cards, canvas);
                }
                else
                {
                    return CheckSequenceWithJokers(cards, firstSuit, jokerCount, canvas);
                }
            }
            else
            {
                return CheckSequenceWithoutJokers(cards, canvas);
            }
        }
        else
        {
            canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid (0)";
            return false;
        }
    }

    string GetCardSuit(GameObject card)
    {
        // Extract the suit from the card name
        string cardName = card.name;

        // Assuming the format is "NUMBER_SUIT" (e.g., "FOUR_HEARTS")
        string[] parts = SplitString(cardName);

        return parts.Length >= 2 ? parts[0] : "";
    }

    string GetCardSuitforPureSequence(GameObject card)
    {
        // Extract the suit from the card name
        string cardName = card.GetComponent<Card>().name;

        // Assuming the format is "NUMBER_SUIT" (e.g., "FOUR_HEARTS")
        string[] parts = SplitString(cardName);

        return parts.Length >= 2 ? parts[0] : "";
    }

    private string GetFirstSuit(List<GameObject> cards)
    {
        string firstSuit = GetCardSuit(cards[0]);

        if (firstSuit == "jo")
        {
            // Determine the firstSuit from the next card if it's a wild card
            firstSuit = GetCardSuit(cards[1]);
        }

        return firstSuit;
    }

    int GetCardRank(GameObject card)
    {
        // Extract the rank from the card name
        string cardName = card.name;

        cardName = card.name.Replace("_", "");
        string[] parts = SplitString(cardName);

        if (parts.Length >= 2)
        {
            string rank = parts[1];

            // Convert the rank to a numerical value (adjust as needed)
            switch (rank)
            {
                case "a":
                    return 1;
                case "2":
                    return 2;
                case "3":
                    return 3;
                case "4":
                    return 4;
                case "5":
                    return 5;
                case "6":
                    return 6;
                case "7":
                    return 7;
                case "8":
                    return 8;
                case "9":
                    return 9;
                case "10":
                    return 10;
                case "j":
                    return 11;
                case "q":
                    return 12;
                case "k":
                    return 13;
                default:
                    return 0; // Default to 0 if the rank is not recognized
            }
        }

        return 0; // Default to 0 if the format is not as expected
    }

    public int GetCardRankname(string card)
    {
        // Assuming the format is "RANK_SUIT" (e.g., "ACE_DIAMONDS")
        card = card.Replace("_", "");
        string[] parts = SplitString(card);

        if (parts.Length >= 2)
        {
            string rank = parts[1];

            // Convert the rank to a numerical value (adjust as needed)
            switch (rank)
            {
                case "a":
                    return 1;
                case "2":
                    return 2;
                case "3":
                    return 3;
                case "4":
                    return 4;
                case "5":
                    return 5;
                case "6":
                    return 6;
                case "7":
                    return 7;
                case "8":
                    return 8;
                case "9":
                    return 9;
                case "10":
                    return 10;
                case "j":
                    return 11;
                case "q":
                    return 12;
                case "k":
                    return 13;
                default:
                    return 0; // Default to 0 if the rank is not recognized
            }
        }

        return 0; // Default to 0 if the format is not as expected
    }

    private void HandleWildCards(List<GameObject> cards)
    {
        foreach (GameObject card in cards)
        {
            if (GetCardRank(card) == wildcardrank)
            {
                card.name = "joker";
            }
        }
    }

    public bool handlewildcardforresult(string card)
    {
        if (GetCardRankforresult(card) == wildcardrank)
        {
            return true;
        }
        else
            return false;
    }

    int GetCardRankforresult(string card)
    {
        // Extract the rank from the card name
        string cardName = card;

        // Assuming the format is "RANK_SUIT" (e.g., "ACE_DIAMONDS")
        cardName = card.Replace("_", "");
        string[] parts = SplitString(cardName);

        if (parts.Length >= 2)
        {
            string rank = parts[1];

            // Convert the rank to a numerical value (adjust as needed)
            switch (rank)
            {
                case "a":
                    return 1;
                case "2":
                    return 2;
                case "3":
                    return 3;
                case "4":
                    return 4;
                case "5":
                    return 5;
                case "6":
                    return 6;
                case "7":
                    return 7;
                case "8":
                    return 8;
                case "9":
                    return 9;
                case "10":
                    return 10;
                case "j":
                    return 11;
                case "q":
                    return 12;
                case "k":
                    return 13;
                default:
                    return 0; // Default to 0 if the rank is not recognized
            }
        }

        return 0; // Default to 0 if the format is not as expected
    }

    private void HandleJokerCards(List<GameObject> cards)
    {
        foreach (GameObject card in cards)
        {
            if (card.name == "jkr1" || card.name == "jkr2")
            {
                card.name = "joker";
            }
        }
    }

    private int CountJokers(List<GameObject> cards)
    {
        int jokerCount = cards.Count(card => card.name == "joker");
        return jokerCount;
    }

    //private bool CheckSequenceWithJokers(List<GameObject> cards, string firstSuit, int jokerCount, GameObject canvas)
    //{
    //    List<int> ranks = new List<int>();

    //    foreach (GameObject card in cards.Where(card => card.name != "joker"))
    //    {
    //        ranks.Add(GetCardRank(card));
    //    }

    //    // Sort the ranks in ascending order
    //    ranks.Sort();

    //    int smallest = ranks[0];

    //    // Check if the smallest rank is 1, and adjust if necessary
    //    bool isAceAsOne = smallest == 1 && ranks[ranks.Count - 1] > 6;

    //    if (smallest == 1)
    //    {
    //        if (isAceAsOne)
    //        {
    //            ranks[0] = 14;
    //        }
    //        else
    //        {
    //            ranks[0] = 1;
    //        }
    //    }

    //    // Sort the ranks in ascending order
    //    ranks.Sort();

    //    // Check if the sequence is valid without considering jokers
    //    //if (IsSequenceValidwithjoker(ranks, canvas))
    //    //{
    //    //    canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Impure Sequence (0)";
    //    //    return true;
    //    //}

    //    for (int i = 0; i < ranks.Count - 1; i++)
    //    {
    //        int gap = ranks[i + 1] - ranks[i] - 1;

    //        if (gap > 0 && jokerCount >= gap)
    //        {
    //            // Fill the gap with jokers
    //            for (int j = 1; j <= gap; j++)
    //            {
    //                ranks.Insert(i + j, ranks[i] + j);
    //                jokerCount--;
    //            }
    //        }
    //        else
    //        {
    //            canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid (0)";
    //            break;
    //        }
    //    }

    //    return IsSequenceValidwithjoker(ranks, canvas);
    //}

    private bool CheckSequenceWithJokers(
        List<GameObject> cards,
        string firstSuit,
        int jokerCount,
        GameObject canvas
    )
    {
        List<int> ranks = new List<int>();

        foreach (GameObject card in cards.Where(card => card.name != "joker"))
        {
            ranks.Add(GetCardRank(card));
        }

        // Sort the ranks in ascending order
        ranks.Sort();

        int smallest = ranks[0];

        // Check if the smallest rank is 1, and adjust if necessary
        bool isAceAsOne = smallest == 1 && ranks[ranks.Count - 1] > 10;

        if (smallest == 1)
        {
            if (isAceAsOne)
            {
                ranks[0] = 14;
            }
            else
            {
                ranks[0] = 1;
            }
        }

        // Sort the ranks in ascending order
        ranks.Sort();
        int gap = 0;
        int finalgap = 0;
        for (int i = 0; i < ranks.Count - 1; i++)
        {
            gap = ranks[i + 1] - ranks[i] - 1;
            finalgap += gap;
            if (finalgap <= jokerCount)
            {
                continue;
            }
            else
            {
                canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid (0)";
                return false;
            }
        }
        showvalidnum(canvas, cards, "Impure Sequence");
        //canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Impure Sequence (0)";
        return true;
    }

    private bool CheckSequenceWithoutJokers(List<GameObject> cards, GameObject canvas)
    {
        List<int> ranks = new List<int>();

        foreach (GameObject card in cards)
        {
            ranks.Add(GetCardRankname(card.GetComponent<Card>().name));
        }

        // Sort the ranks in ascending order
        ranks.Sort();

        int smallest = ranks[0];

        // Check if the smallest rank is 1, and adjust if necessary
        bool isAceAsOne = smallest == 1 && ranks[ranks.Count - 1] > 6;

        if (smallest == 1)
        {
            if (isAceAsOne)
            {
                ranks[0] = 14;
            }
            else
            {
                ranks[0] = 1;
            }
            ranks.Sort();
        }

        // Check if the sequence is valid
        return IsSequenceValid(ranks, cards, canvas);
    }

    private bool IsSequenceValid(List<int> ranks, List<GameObject> cards, GameObject canvas)
    {
        for (int i = 0; i < ranks.Count - 1; i++)
        {
            if (ranks[i] + 1 != ranks[i + 1])
            {
                canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid";
                return false;
            }
        }

        List<string> suits = new List<string>();

        foreach (var item in cards)
        {
            suits.Add(GetCardSuitforPureSequence(item));
        }

        if (suits.Distinct().Count() == 1)
        {
            ispuresequenceincards = true;
            showvalidnum(canvas, cards, "Pure Sequence");
            //canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Pure Sequence (0)";
            return true;
        }
        canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid";
        return false;
    }

    private bool IsSequenceValidwithjoker(List<int> ranks, GameObject canvas)
    {
        for (int i = 0; i < ranks.Count - 1; i++)
        {
            int gap = ranks[i + 1] - ranks[i] - 1;

            if (gap > 0)
            {
                if (gap <= CountJokersBetween(ranks, i, i + 1))
                {
                    for (int j = 1; j <= gap; j++)
                    {
                        ranks.Insert(i + j, ranks[i] + j);
                    }
                }
                else
                {
                    canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Invalid";
                    return false;
                }
            }
        }
        //int jokercount = 0;
        //for(int i = 0; i < ranks.Count; i++)
        //{
        //    if (ranks[i] == wildcardrank)
        //        jokercount++;
        //}

        canvas.transform.GetChild(0).GetComponent<TextMeshProUGUI>().text = "Impure Sequence (0)";
        return true;
    }

    private int CountJokersBetween(List<int> ranks, int start, int end)
    {
        int jokerCount = 0;

        for (int i = start + 1; i < end; i++)
        {
            if (ranks[i] - ranks[i - 1] > 1)
            {
                jokerCount += ranks[i] - ranks[i - 1] - 1;
            }
        }

        return jokerCount;
    }

    #endregion

    #region sort sequence
    private void SortSequence(List<GameObject> cards)
    {
        cards.Sort(
            (a, b) =>
                GetCardRankname(a.GetComponent<Card>().name)
                    .CompareTo(GetCardRankname(b.GetComponent<Card>().name))
        );

        foreach (GameObject card in cards)
        {
            //  Debug.Log("RES_Check + Sorted card: " + card.GetComponent<Card>().name);
        }
    }
    #endregion
}
