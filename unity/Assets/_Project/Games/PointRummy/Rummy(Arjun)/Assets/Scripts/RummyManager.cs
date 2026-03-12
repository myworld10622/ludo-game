using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using TMPro;
using UnityEditor;
using UnityEngine;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using UnityEngine.UIElements;

public class RummyManager : MonoBehaviour
{
    //public APIManager APIManager;
    public CachetaConnection cachetmanager;
    public List<string> names;
    public List<GameObject> Set;
    public GameObject highlighSprite;
    public Transform spawnPoint; // Single spawn point
    public List<Transform> cardLocations; // List of card locations
    public List<GameObject> deck; // List of 52 cards
    public List<GameObject> deck2; // List of 52 cards
    public List<GameObject> setFromAPI;
    public List<GameObject> spawnedCards; // List of spawned cards
    public List<GameObject> correctOrder; // List for correct order of spawned cards
    public List<GameObject> nonEmptyCards;
    public GameObject discradarea;
    private bool isDragging = false;
    private GameObject draggedCard;
    public GameObject suitSeparatorPrefab;
    private Vector3 cardOffset;
    private Vector3 draggedCardOriginalPosition;
    public List<GameObject> list1 = new List<GameObject>();
    public List<GameObject> list2 = new List<GameObject>();
    public List<GameObject> list3 = new List<GameObject>();
    public List<GameObject> list4 = new List<GameObject>();
    public List<GameObject> list5 = new List<GameObject>();
    public List<GameObject> list6 = new List<GameObject>();
    public bool check1,
        check2,
        check3,
        check4,
        check5,
        check6;
    public TextMeshProUGUI set,
        sequence,
        success;
    int setcheck,
        seqcheck;
    public int numoflist,
        numofmatch;
    public bool moveup = false;
    public float clicktime;
    public List<GameObject> uplist;
    public GameObject groupButton;
    public GameObject locationprefab;
    public GameObject locationparent;
    public List<GameObject> newgroupcards;
    public List<int> numlis1 = new List<int>();
    public List<int> numlis2 = new List<int>();
    public List<GameObject> newcards;
    public List<GameObject> discardedcards;
    public TextMeshProUGUI discardtext;
    public bool active = false;
    public GameObject wildcard,
        wildcardlocation;
    public GameObject wild;
    public int wildcardrank;
    public GameObject joker;
    public bool placeholderclicked = false;
    public bool discardedclicked = false;
    public string SceneName;
    public bool ischaal;
    public string name;
    public GameObject packgame;
    public GameObject declare;
    public GameObject declareback;
    public GameObject foundcard;
    public GameObject dicardcardbutton;
    public GameObject textovercardcanvas;
    public List<GameObject> textcavas;
    public List<GameObject> emptygameobjects;
    private bool spawned,
        one,
        two,
        three,
        four;
    public GameObject wildjokerimage;
    public List<GameObject> spritetochange;
    public List<Sprite> sprites;
    public Sprite backcard;
    public GameObject newgroupplaceholder;
    public TextMeshProUGUI grouptext;
    public bool groupspawned,
        cardsspawned;
    public List<GameObject> grouplist;
    public GameObject drop;
    public bool ispuresequenceincards;
    public GameObject drawnard;
    public bool getcard,
        finished;
    public GameObject declaredialogue,
        finishdeskcard;
    public Vector3 finishdeskcardogpos;

    //public RectTransform group1panel, group2panel, group3panel, group4panel, group5panel, group6panel;

    public void OnEnable()
    {
        StartCoroutine(rummycardsspawn());
    }

    IEnumerator rummycardsspawn()
    {
        Debug.Log("Res_Check rummy cards spawn");
        yield return new WaitForSeconds(3);
        names = cachetmanager.cardNames;
        locationparent.transform.position = new Vector3(
            -6.75f,
            locationparent.transform.position.y,
            locationparent.transform.position.z
        );
        Debug.Log("Object found to be 0");
        for (int i = 0; i < deck2.Count; i++)
        {
            deck2[i].name = deck2[i].name.ToLower();
        }
        yield return new WaitForSeconds(0.2f);
        Debug.Log("Object found to be 1");
        Debug.Log("Object found to be 1 " + names.Count);
        foreach (string str in names)
        {
            Debug.Log("Object found to be 2");

            // Find a GameObject with the same name as the current string
            GameObject foundObject = FindGameObjectByName(str);

            Debug.Log(str + " Object found to be");

            Debug.Log(foundObject + " Object found");

            // If a GameObject is found, add it to the list
            if (foundObject != null)
            {
                setFromAPI.Add(foundObject);
            }
        }

        Set = setFromAPI;

        drop.SetActive(true);

        // Spawn 9 cards at the single spawn point
        SpawnCards(3);

        // Sort the correct order based on suits
        SortCorrectOrder();

        // Move cards to their respective locations
        StartCoroutine(MoveCardsToLocations());
    }

    public IEnumerator spawnwildcard()
    {
        int value = 0;
        name = cachetmanager.gameDataList[0].joker;
        name = name.ToLower();
        for (int i = 0; i < deck2.Count; i++)
        {
            if (deck2[i].name == name)
                value = i;
        }
        wild = Instantiate(deck2[value], spawnPoint.position, Quaternion.identity);

        wild.name = deck2[value].name;

        wild.transform.SetParent(wildcardlocation.transform);

        wild.GetComponent<BoxCollider>().enabled = false;
        wild.GetComponent<Card>().enabled = false;
        wild.GetComponent<ActiveCard>().enabled = false;

        float duration = 0.4f; // Adjust the duration as needed
        float elapsed = 0f;
        wild.GetComponent<Animator>().enabled = true;
        Vector3 startPosition = wild.transform.position;
        Vector3 targetPosition = wildcardlocation.transform.position;
        wild.transform.localScale = new Vector3(1.2f, 1.2f, 1);
        while (elapsed < duration)
        {
            float t = Mathf.SmoothStep(0f, 1f, elapsed / duration);
            wild.transform.position = Vector3.Lerp(startPosition, targetPosition, t);
            elapsed += Time.deltaTime;
            yield return null;
        }

        wild.transform.position = targetPosition;
        wild.transform.rotation = Quaternion.identity;

        wild.GetComponent<SpriteRenderer>().sortingOrder = 0;

        packgame.SetActive(true);
    }

    List<int> InterchangePositions(List<GameObject> list1, List<GameObject> list2)
    {
        List<int> list = new List<int>();
        for (int i = 0; i < list2.Count; i++)
        {
            for (int j = 0; j < list1.Count; j++)
            {
                if (list2[i] == list1[j])
                    list.Add(j);
            }
        }
        for (int i = 0; i < list.Count; i++)
        {
            Debug.Log(list[i] + "listnumber to change");
        }

        return list;
    }

    GameObject FindGameObjectByName(string nameToFind)
    {
        // Find a GameObject by name in the gameObjectArray
        foreach (GameObject go in deck2)
        {
            if (go.name == nameToFind)
            {
                return go; // Return the GameObject if the name matches
            }
        }

        // Return null if no matching GameObject is found
        return null;
    }

    int GetSuitOrderFromName(string suitName)
    {
        // Convert the suit name to a numerical order (adjust as needed)
        switch (suitName.ToUpper())
        {
            case "HEARTS":
                return 0;
            case "SPADES":
                return 1;
            case "DIAMONDS":
                return 2;
            case "CLUBS":
                return 3;
            default:
                return -1; // Return a default value for an unknown suit
        }
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

    int GetCardRank(GameObject card)
    {
        // Extract the rank from the card name
        string cardName = card.name;

        // Assuming the format is "RANK_SUIT" (e.g., "ACE_DIAMONDS")
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

    void SplitCardsIntoGroups(
        List<GameObject> list1,
        List<GameObject> list2,
        List<GameObject> list3,
        List<GameObject> list4,
        List<GameObject> list5,
        List<GameObject> list6
    )
    {
        List<GameObject> currentList = list1;
        int emptyCount = 0;
        foreach (GameObject card in spawnedCards)
        {
            if (card.name == "Empty(Clone)")
            {
                emptyCount++;

                // Check if we've encountered two consecutive Empty(Clone) cards
                if (emptyCount == 2)
                {
                    // Switch to the next list and reset the empty count
                    if (currentList == list1)
                        currentList = list2;
                    else if (currentList == list2)
                        currentList = list3;
                    else if (currentList == list3)
                        currentList = list4;
                    else if (currentList == list4)
                        currentList = list5;
                    else if (currentList == list5)
                        currentList = list6;

                    emptyCount = 0; // Reset empty count
                }
            }
            else
            {
                // Add the card to the current list
                currentList.Add(card);
            }
        }
    }

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
            spawned = true;
        }

        foreach (GameObject card in textcavas)
            card.SetActive(false);

        if (list1.Count > 2)
        {
            textcavas[0].GetComponent<PanelPositioner>().cards = list1;
            textcavas[0].SetActive(true);
        }
        if (list2.Count > 2)
        {
            textcavas[1].GetComponent<PanelPositioner>().cards = list2;
            textcavas[1].SetActive(true);
        }
        if (list3.Count > 2)
        {
            textcavas[2].GetComponent<PanelPositioner>().cards = list3;
            textcavas[2].SetActive(true);
        }
        if (list4.Count > 2)
        {
            textcavas[3].GetComponent<PanelPositioner>().cards = list4;
            textcavas[3].SetActive(true);
        }
    }

    public bool IsSequence(List<GameObject> cards)
    {
        // Check if the list has more than 2 cards
        if (cards.Count <= 2)
        {
            Debug.Log("Rank Enter count " + cards.Count);
            return false;
        }

        string firstSuit = GetFirstSuit(cards);
        if (firstSuit == "")
        {
            // Handle the case where the suit cannot be determined
            return false;
        }

        HandleWildCards(cards);

        HandleJokerCards(cards);

        int jokerCount = CountJokers(cards);

        if (jokerCount > 0)
        {
            return CheckSequenceWithJokers(cards, firstSuit, jokerCount);
        }
        else
        {
            return CheckSequenceWithoutJokers(cards, firstSuit);
        }
    }

    private string GetFirstSuit(List<GameObject> cards)
    {
        string firstSuit = GetCardSuit(cards.FirstOrDefault());

        if (firstSuit == "jo")
        {
            // Determine the firstSuit from the next card if it's a wild card
            firstSuit = GetCardSuit(cards[1]);
        }

        return firstSuit;
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

    private bool CheckSequenceWithJokers(List<GameObject> cards, string firstSuit, int jokerCount)
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
        bool isAceAsOne = smallest == 1 && ranks[ranks.Count - 1] > 6;

        if (smallest == 1)
        {
            if (isAceAsOne)
            {
                ranks[0] = 11;
            }
            else
            {
                ranks[0] = 1;
            }
        }

        // Check if the sequence is valid without considering jokers
        if (IsSequenceValidwithjoker(ranks))
        {
            return true;
        }

        // Try to use jokers to fill in the gaps in the sequence
        for (int i = 0; i < ranks.Count - 1; i++)
        {
            int gap = ranks[i + 1] - ranks[i] - 1;

            if (gap > 0 && jokerCount >= gap)
            {
                // Fill the gap with jokers
                for (int j = 1; j <= gap; j++)
                {
                    ranks.Insert(i + j, ranks[i] + j);
                    jokerCount--;
                }
            }
        }

        // Check if the sequence is valid after using jokers
        return IsSequenceValidwithjoker(ranks);
    }

    private bool CheckSequenceWithoutJokers(List<GameObject> cards, string firstSuit)
    {
        List<int> ranks = new List<int>();

        foreach (GameObject card in cards)
        {
            ranks.Add(GetCardRank(card));
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
        }

        // Check if the sequence is valid
        return IsSequenceValid(ranks, cards);
    }

    private bool IsSequenceValidwithjoker(List<int> ranks)
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
                    return false;
                }
            }
        }
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

    private bool IsSequenceValid(List<int> ranks, List<GameObject> cards)
    {
        for (int i = 0; i < ranks.Count - 1; i++)
        {
            if (ranks[i] + 1 != ranks[i + 1])
            {
                return false;
            }
        }

        List<string> suits = new List<string>();

        foreach (var item in cards)
        {
            suits.Add(GetCardSuit(item));
        }

        if (suits.Distinct().Count() == 1)
        {
            ispuresequenceincards = true;
            return true;
        }
        return false;
    }

    public bool AreAllCardsSameRank(List<GameObject> cards)
    {
        HashSet<string> uniqueRanks = new HashSet<string>();
        foreach (GameObject card in cards)
        {
            string rank = GetCardRankFirstPart(card);
            if (uniqueRanks.Contains(rank))
            {
                return false;
            }
            uniqueRanks.Add(rank);
        }

        if (wild != null)
            wildcardrank = GetCardRank(wild);

        foreach (GameObject card in cards)
        {
            if (GetCardRank(card) == wildcardrank || card.name == "jkr1" || card.name == "jkr2")
                card.name = "joker";
        }

        if (cards.Count <= 2 || cards == null)
        {
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
            return newlist.All(newlist => GetCardRank(newlist) == referenceRank);
        }
        else
        {
            int referenceRank = GetCardRank(cards.First());
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

    void CheckAfterEveryMove()
    {
        Debug.Log("Rank Enter");
        numoflist = 0;
        numofmatch = 0;
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

        check1 = IsSequence(list1);
        check2 = IsSequence(list2);
        check3 = IsSequence(list3);
        check4 = IsSequence(list4);
        check5 = IsSequence(list5);
        check6 = IsSequence(list6);

        if (check1)
            seqcheck++;
        if (check2)
            seqcheck++;
        if (check3)
            seqcheck++;
        if (check4)
            seqcheck++;
        if (check5)
            seqcheck++;
        if (check6)
            seqcheck++;

        sequence.text = "Number of Sequence: " + seqcheck;

        setcheck = 0;

        if (!check1)
        {
            check1 = AreAllCardsSameRank(list1);
            if (check1)
                setcheck++;
        }
        if (!check2)
        {
            check2 = AreAllCardsSameRank(list2);
            if (check2)
                setcheck++;
        }
        if (!check3)
        {
            check3 = AreAllCardsSameRank(list3);
            if (check3)
                setcheck++;
        }
        if (!check4)
        {
            check4 = AreAllCardsSameRank(list4);
            if (check4)
                setcheck++;
        }
        if (!check5)
        {
            check5 = AreAllCardsSameRank(list5);
            if (check5)
                setcheck++;
        }
        if (!check6)
        {
            check6 = AreAllCardsSameRank(list6);
            if (check6)
                setcheck++;
        }

        set.text = "Number of Sets: " + setcheck;

        if (check1)
            HighlightCardsInSequences(list1);
        else
            RemoveHighlightCardsInSequences(list1);
        if (check2)
            HighlightCardsInSequences(list2);
        else
            RemoveHighlightCardsInSequences(list2);
        if (check3)
            HighlightCardsInSequences(list3);
        else
            RemoveHighlightCardsInSequences(list3);
        if (check4)
            HighlightCardsInSequences(list4);
        else
            RemoveHighlightCardsInSequences(list4);
        if (check5)
            HighlightCardsInSequences(list5);
        else
            RemoveHighlightCardsInSequences(list5);
        if (check6)
            HighlightCardsInSequences(list6);
        else
            RemoveHighlightCardsInSequences(list6);

        if (check1)
            numofmatch++;
        if (check2)
            numofmatch++;
        if (check3)
            numofmatch++;
        if (check4)
            numofmatch++;
        if (check5)
            numofmatch++;
        if (check6)
            numofmatch++;

        if (numoflist == numofmatch && ispuresequenceincards)
        {
            //set.enabled = false;
            //sequence.enabled = false;
            cachetmanager.API_CALL_declare();
            //declare.gameObject.SetActive(true);
            //success.enabled = true;
            //success.text = "You have successfully completed this round with " + setcheck + " Sets and " + seqcheck + " sequences";
            //for (int i = 0; i < spawnedCards.Count - 1; i++)
            //{
            //    spawnedCards[i].GetComponent<BoxCollider>().enabled = false;
            //}
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
        if (uplist.Count == 1)
            dicardcardbutton.SetActive(true);
        else
            dicardcardbutton.SetActive(false);
        card.GetComponent<BoxCollider>().enabled = false;
        if (uplist.Count == 0)
            uplist.Add(card);
        else if (!uplist.Contains(card))
            uplist.Add(card);

        float elapsed = 0f;
        Vector3 startPosition = card.transform.position;

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
        card.GetComponent<BoxCollider>().enabled = true;

        if (uplist.Count > 2 && uplist.Count < 5)
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
        card.GetComponent<BoxCollider>().enabled = false;
        if (uplist.Contains(card))
            uplist.Remove(card);

        float yOffset = 0.5f;

        Vector3 newPosition = card.transform.position - new Vector3(0.0f, yOffset, 0.0f);

        float elapsed = 0f;
        Vector3 startPosition = card.transform.position;

        while (elapsed < duration)
        {
            card.transform.position = Vector3.Lerp(startPosition, newPosition, elapsed / duration);
            elapsed += Time.deltaTime;
            yield return null;
        }

        card.transform.position = newPosition;

        card.GetComponent<Card>().cardup = false;
        card.GetComponent<BoxCollider>().enabled = true;

        if (uplist.Count < 3 || uplist.Count > 4)
        {
            groupButton.SetActive(false);
        }
        else
        {
            groupButton.SetActive(true);
        }
    }

    public void cardsdown()
    {
        for (int i = 0; i < uplist.Count; i++)
        {
            float yOffset = 0.5f;
            uplist[i].GetComponent<Card>().cardup = false;
            Vector3 newPosition = uplist[i].transform.position - new Vector3(0.0f, yOffset, 0.0f);
            uplist[i].transform.position = newPosition;
        }

        uplist.Clear();
    }

    IEnumerator spawnnewcard(GameObject clickedCard)
    {
        yield return new WaitForSeconds(0.5f);

        string newaskedcard = cachetmanager.askedCard;

        string formattednewcard = newaskedcard.ToLower();

        GameObject drawnCard = FindGameObjectByName(formattednewcard);

        if (list6.Count == 0)
        {
            for (int j = 0; j < 2; j++)
            {
                Debug.Log("Res_Check + new empty card instantiate");
                GameObject newEmptyCard = Instantiate(suitSeparatorPrefab);

                emptygameobjects.Add(newEmptyCard);

                spawnedCards.Add(newEmptyCard);

                if (spawnedCards.Count > cardLocations.Count)
                {
                    GameObject instantiatedObject = Instantiate(locationprefab);
                    Transform lastloc = cardLocations[cardLocations.Count - 1].transform;
                    instantiatedObject.transform.parent = locationparent.transform;
                    instantiatedObject.transform.position = new Vector3(
                        lastloc.transform.position.x + 0.7f,
                        lastloc.transform.position.y,
                        lastloc.transform.position.z
                    );
                    cardLocations.Add(instantiatedObject.transform);
                }

                newEmptyCard.transform.position = cardLocations[spawnedCards.Count - 1].position;
            }

            // Instantiate the drawn card at the placeholder position
            //GameObject newCard = Instantiate(drawnCard, spawnPoint.position, Quaternion.identity);
            GameObject newCard = Instantiate(
                drawnCard,
                spawnPoint.transform.position,
                Quaternion.identity
            );

            newCard.name = drawnCard.name;

            drawnard = newCard;

            // Add the new card to the spawned list
            spawnedCards.Add(newCard);

            if (spawnedCards.Count > cardLocations.Count)
            {
                GameObject instantiatedObject = Instantiate(locationprefab);
                GameObject lastloc = cardLocations[cardLocations.Count - 1].gameObject;
                instantiatedObject.transform.parent = locationparent.transform;
                instantiatedObject.transform.position = new Vector3(
                    lastloc.transform.position.x + 0.7f,
                    lastloc.transform.position.y,
                    lastloc.transform.position.z
                );
                cardLocations.Add(instantiatedObject.transform);
            }

            // Move the card towards the 10th location
            StartCoroutine(MoveCard(newCard, cardLocations[spawnedCards.Count - 1].position, 1.0f));

            nonEmptyCards = spawnedCards.Where(card => card.name != "Empty(Clone)").ToList();

            // Add the new card to the correct order list
            SortCorrectOrder();
        }
        else
        {
            GameObject newCard = Instantiate(
                drawnCard,
                spawnPoint.transform.position,
                Quaternion.identity
            );

            newCard.name = drawnCard.name;

            // Add the new card to the spawned list
            spawnedCards.Add(newCard);

            if (spawnedCards.Count > cardLocations.Count)
            {
                GameObject instantiatedObject = Instantiate(locationprefab);
                GameObject lastloc = cardLocations[cardLocations.Count - 1].gameObject;
                instantiatedObject.transform.parent = locationparent.transform;
                instantiatedObject.transform.position = new Vector3(
                    lastloc.transform.position.x + 0.7f,
                    lastloc.transform.position.y,
                    lastloc.transform.position.z
                );
                cardLocations.Add(instantiatedObject.transform);
            }

            // Move the card towards the 10th location
            StartCoroutine(MoveCard(newCard, cardLocations[spawnedCards.Count - 1].position, 1.0f));

            nonEmptyCards = spawnedCards.Where(card => card.name != "Empty(Clone)").ToList();

            // Add the new card to the correct order list
            SortCorrectOrder();
        }
    }

    IEnumerator clicked()
    {
        placeholderclicked = true;
        yield return new WaitForSeconds(1);
        placeholderclicked = false;
    }

    IEnumerator Discardedclicked()
    {
        discardedclicked = true;
        yield return new WaitForSeconds(1);
        discardedclicked = false;
    }

    void Update()
    {
        if (!finished)
        {
            setcardzlocation();
            // Check for input (click or touch)
            if (
                Input.GetMouseButtonDown(0)
                || (Input.touchCount > 0 && Input.GetTouch(0).phase == TouchPhase.Began)
            )
            {
                discradarea.GetComponent<BoxCollider>().enabled = false;

                // Raycast to detect the clicked/touched card
                Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

                RaycastHit hit;
                if (Physics.Raycast(ray, out hit))
                {
                    // Check if the hit object is a card
                    GameObject clickedCard = hit.collider.gameObject;
                    Debug.Log("Clicked Card: " + clickedCard.name);

                    if (spawnedCards.Contains(clickedCard))
                    {
                        Debug.Log("Exists");
                    }
                    else if (discardedcards.Contains(clickedCard))
                    {
                        if (ischaal && !cachetmanager.gameDataList[0].declare)
                        {
                            if (nonEmptyCards.Count == 13 && getcard)
                            {
                                getcard = false;
                                discardedcards.Remove(clickedCard);
                                StartCoroutine(Discardedclicked());

                                Debug.Log("RES_Check + empty");

                                if (list6.Count == 0)
                                {
                                    for (int j = 0; j < 2; j++)
                                    {
                                        GameObject newEmptyCard = Instantiate(suitSeparatorPrefab);

                                        emptygameobjects.Add(newEmptyCard);

                                        spawnedCards.Add(newEmptyCard);

                                        if (spawnedCards.Count > cardLocations.Count)
                                        {
                                            GameObject instantiatedObject = Instantiate(
                                                locationprefab
                                            );
                                            Transform lastloc = cardLocations[
                                                cardLocations.Count - 1
                                            ]
                                                .gameObject
                                                .transform;
                                            instantiatedObject.transform.parent =
                                                locationparent.transform;
                                            instantiatedObject.transform.position = new Vector3(
                                                lastloc.transform.position.x + 0.7f,
                                                lastloc.transform.position.y,
                                                lastloc.transform.position.z
                                            );
                                            cardLocations.Add(instantiatedObject.transform);
                                        }

                                        newEmptyCard.transform.position = cardLocations[
                                            spawnedCards.Count - 1
                                        ].position;
                                    }
                                }

                                drawnard = clickedCard;

                                spawnedCards.Add(clickedCard);

                                Debug.Log("Discard card " + clickedCard);

                                string cardName = clickedCard.name.ToUpper();

                                cachetmanager.API_CALL_get_drop_card();

                                getcard = false;

                                if (spawnedCards.Count > cardLocations.Count)
                                {
                                    GameObject instantiatedObject = Instantiate(locationprefab);
                                    GameObject lastloc = cardLocations[
                                        cardLocations.Count - 1
                                    ].gameObject;
                                    instantiatedObject.transform.parent = locationparent.transform;
                                    instantiatedObject.transform.position = new Vector3(
                                        lastloc.transform.position.x + 0.7f,
                                        lastloc.transform.position.y,
                                        lastloc.transform.position.z
                                    );
                                    cardLocations.Add(instantiatedObject.transform);
                                }

                                // Move the card towards the 10th location
                                StartCoroutine(
                                    MoveCard(
                                        clickedCard,
                                        cardLocations[spawnedCards.Count - 1].position,
                                        1.0f
                                    )
                                );

                                nonEmptyCards = spawnedCards
                                    .Where(card => card.name != "Empty(Clone)")
                                    .ToList();

                                // Add the new card to the correct order list
                                SortCorrectOrder();

                                clickedCard.GetComponent<BoxCollider>().enabled = true;
                            }
                            else
                            {
                                StartCoroutine(discradtextshow("You already have 14 cards"));
                                Debug.Log(
                                    "Clicked Card: " + clickedCard.name + " but 14 cards are there"
                                );
                            }
                        }
                        else
                            StartCoroutine(discradtextshow("Please wait for your Chaal"));
                    }

                    // Call the MoveCardUp function when a card is clicked
                    //MoveCardUp(clickedCard);
                    if (clickedCard.name == "placeholderCard" && getcard)
                    {
                        // Clicked on the placeholder card
                        getcard = false;
                        // Check if there are less than 10 cards in the spawned list
                        if (ischaal && !cachetmanager.gameDataList[0].declare)
                        {
                            if (nonEmptyCards.Count == 13)
                            {
                                StartCoroutine(clicked());

                                cachetmanager.API_CALL_get_card();

                                StartCoroutine(spawnnewcard(clickedCard));
                            }
                            else
                            {
                                StartCoroutine(discradtextshow("You already have 14 cards"));
                                Debug.Log(
                                    "Clicked Card: " + clickedCard.name + " but 14 cards are there"
                                );
                            }
                        }
                        else
                            StartCoroutine(discradtextshow("Please wait for your Chaal"));
                    }
                    else if (spawnedCards.Contains(clickedCard))
                    {
                        // Print the name of the clicked card
                        Debug.Log("Clicked Card: " + clickedCard.name);
                    }
                }
            }

            HandleInput();

            // Check for card release (touch or mouse release)
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

            if (uplist.Count == 1)
                dicardcardbutton.SetActive(true);
            else
                dicardcardbutton.SetActive(false);
        }
    }

    IEnumerator discradtextshow(string show)
    {
        discardtext.text = show;
        discardtext.enabled = true;
        yield return new WaitForSeconds(2);
        discardtext.enabled = false;
    }

    public void setcardzlocation()
    {
        if (!finished)
        {
            float value = 0;
            int order = 0;
            for (int i = 0; i < spawnedCards.Count; i++)
            {
                spawnedCards[i].transform.position = new Vector3(
                    spawnedCards[i].transform.position.x,
                    spawnedCards[i].transform.position.y,
                    value
                );
                value -= 0.5f;
                spawnedCards[i].transform.localScale = new Vector3(1.5f, 1.5f, 1);
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order;
                order++;
            }

            foreach (GameObject card in spawnedCards)
            {
                if (card.name == "joker")
                {
                    if (card.transform.childCount == 0)
                    {
                        Vector3 pos = new Vector3(-0.3f, -0.45f, 0);
                        GameObject jok = Instantiate(
                            wildjokerimage,
                            Vector3.zero,
                            Quaternion.identity
                        );
                        jok.transform.parent = card.transform;
                        jok.transform.localPosition = pos;
                        jok.GetComponent<SpriteRenderer>().sortingOrder =
                            card.GetComponent<SpriteRenderer>().sortingOrder + 1;
                    }
                }
            }

            foreach (GameObject card in discardedcards)
            {
                if (wild != null)
                {
                    string name;
                    string name2;
                    if (card.name.Contains("_"))
                    {
                        name = card.name.Replace("_", "");
                    }
                    else
                    {
                        name = card.name;
                    }
                    if (wild.name.Contains("_"))
                    {
                        name2 = wild.name.Replace("_", "");
                    }
                    else
                    {
                        name2 = wild.name;
                    }
                    if (name == name2)
                    {
                        if (card.transform.childCount == 0)
                        {
                            Vector3 pos = new Vector3(-0.3f, -0.45f, 0);
                            GameObject jok = Instantiate(
                                wildjokerimage,
                                Vector3.zero,
                                Quaternion.identity
                            );
                            jok.transform.parent = card.transform;
                            jok.transform.localPosition = pos;
                            jok.GetComponent<SpriteRenderer>().sortingOrder =
                                card.GetComponent<SpriteRenderer>().sortingOrder + 1;
                        }
                    }
                }
            }

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

                if (clickedCard.name != "placeholderCard")
                {
                    if (spawnedCards.Contains(clickedCard))
                    {
                        // Start dragging the card
                        isDragging = true;
                        discradarea.GetComponent<BoxCollider>().enabled = true;
                        draggedCard = clickedCard;
                        draggedCardOriginalPosition = clickedCard.transform.position;
                        cardOffset = clickedCard.transform.position - ray.origin;

                        // Disable the collider of the dragged card while dragging
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
            if (list6.Count == 0 && !groupspawned)
            {
                List<Transform> trans = new List<Transform>();
                groupspawned = true;
                for (int j = 0; j < 3; j++)
                {
                    if (trans.Count == 0)
                        trans.Add(spawnedCards[spawnedCards.Count - 1].transform);

                    GameObject newEmptyCard = Instantiate(suitSeparatorPrefab);

                    newEmptyCard.transform.position = new Vector3(
                        trans[trans.Count - 1].position.x + 0.7f,
                        trans[trans.Count - 1].position.y,
                        trans[trans.Count - 1].position.z + 0.5f
                    );

                    trans.Add(newEmptyCard.transform);

                    grouplist.Add(newEmptyCard);
                }

                GameObject newCard = Instantiate(
                    newgroupplaceholder,
                    new Vector3(
                        trans[trans.Count - 1].position.x + 0.7f,
                        trans[trans.Count - 1].position.y,
                        trans[trans.Count - 1].position.z + 0.5f
                    ),
                    Quaternion.identity
                );

                newCard.name = newgroupplaceholder.name;
            }
            clicktime += 1;
            for (int i = 0; i < discardedcards.Count; i++)
            {
                discardedcards[i].GetComponent<BoxCollider>().enabled = false;
            }
            draggedCard.GetComponent<SpriteRenderer>().sortingOrder = 20;
            Ray ray = Camera.main.ScreenPointToRay(Input.mousePosition);

            if (Input.touchCount > 0)
            {
                ray = Camera.main.ScreenPointToRay(Input.GetTouch(0).position);
            }
            // Move the dragged card based on input position
            draggedCard.transform.position = ray.origin + cardOffset;
        }
    }

    public void newgroupfromdragging(string name)
    {
        if (list6.Count == 0)
        {
            for (int j = 0; j < 2; j++)
            {
                GameObject newEmptyCard = Instantiate(suitSeparatorPrefab);

                emptygameobjects.Add(newEmptyCard);

                spawnedCards.Add(newEmptyCard);

                if (spawnedCards.Count > cardLocations.Count)
                {
                    GameObject instantiatedObject = Instantiate(locationprefab);
                    Transform lastloc = cardLocations[cardLocations.Count - 1].transform;
                    instantiatedObject.transform.parent = locationparent.transform;
                    instantiatedObject.transform.position = new Vector3(
                        lastloc.transform.position.x + 0.7f,
                        lastloc.transform.position.y,
                        lastloc.transform.position.z
                    );
                    cardLocations.Add(instantiatedObject.transform);
                }

                newEmptyCard.transform.position = cardLocations[spawnedCards.Count - 1].position;
            }

            for (int i = 0; i < spawnedCards.Count; i++)
            {
                if (spawnedCards[i].name == draggedCard.name)
                    spawnedCards.RemoveAt(i);
            }

            GameObject newCard = draggedCard;

            newCard.name = draggedCard.name;

            // Add the new card to the spawned list
            spawnedCards.Add(newCard);

            if (spawnedCards.Count > cardLocations.Count)
            {
                GameObject instantiatedObject = Instantiate(locationprefab);
                GameObject lastloc = cardLocations[cardLocations.Count - 1].gameObject;
                instantiatedObject.transform.parent = locationparent.transform;
                instantiatedObject.transform.position = new Vector3(
                    lastloc.transform.position.x + 0.7f,
                    lastloc.transform.position.y,
                    lastloc.transform.position.z
                );
                cardLocations.Add(instantiatedObject.transform);
            }

            for (int i = 0; i < spawnedCards.Count; i++)
            {
                spawnedCards[i].transform.position = cardLocations[i].transform.position;
            }

            // Move the card towards the 10th location
            StartCoroutine(MoveCard(newCard, cardLocations[spawnedCards.Count - 1].position, 1.0f));

            nonEmptyCards = spawnedCards.Where(card => card.name != "Empty(Clone)").ToList();

            Destroy(GameObject.Find("NewGroup"));
            foreach (GameObject obj in grouplist)
            {
                Destroy(obj);
            }

            // Add the new card to the correct order list
            SortCorrectOrder();
        }
    }

    void HandleCardRelease()
    {
        groupspawned = false;
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

            if (releasedCard.name == "NewGroup")
            {
                newgroupfromdragging(draggedCard.name);
                draggedCard.GetComponent<BoxCollider>().enabled = true;
                clicktime = 0;
                isDragging = false;
                grouplist.Clear();
                CheckForConsecutiveNames(spawnedCards);
            }
            else
            {
                Destroy(GameObject.Find("NewGroup"));
                foreach (GameObject obj in grouplist)
                {
                    Destroy(obj);
                }
                grouplist.Clear();
                moveback();
                CheckForConsecutiveNames(spawnedCards);
            }
        }
        else
        {
            Destroy(GameObject.Find("NewGroup"));
            foreach (GameObject obj in grouplist)
            {
                Destroy(obj);
            }
            grouplist.Clear();
            moveback();
            Debug.Log("far Released");
            CheckForConsecutiveNames(spawnedCards);
        }
    }

    public void moveback()
    {
        if (draggedCard == null)
        {
            return;
        }

        SpriteRenderer draggedSpriteRenderer = draggedCard.GetComponent<SpriteRenderer>();
        BoxCollider draggedBoxCollider = draggedCard.GetComponent<BoxCollider>();
        Card draggedCardComponent = draggedCard.GetComponent<Card>();

        if (draggedSpriteRenderer != null)
        {
            draggedSpriteRenderer.sortingOrder = 1;
        }

        if (draggedCard.transform.position == draggedCardOriginalPosition)
        {
            Debug.Log("AndropApps 2");
            clicktime = 0;
            isDragging = false;

            if (draggedCardComponent != null && draggedCardComponent.enabled)
            {
                if (!draggedCardComponent.cardup)
                {
                    Debug.Log("AndropApps 2_1");

                    if (discardedcards.Contains(draggedCard))
                    {
                        if (draggedBoxCollider != null)
                        {
                            draggedBoxCollider.enabled = true;
                        }
                        AudioManager._instance.ButtonClick();
                        discardedcards.Remove(draggedCard);
                    }
                    else
                    {
                        MoveCardUp(draggedCard);

                        if (draggedBoxCollider != null)
                        {
                            draggedBoxCollider.enabled = true;
                        }
                        AudioManager._instance.ButtonClick();
                        draggedCardComponent.cardup = true;
                    }
                }
                else
                {
                    Debug.Log("AndropApps 2_2");
                    StartCoroutine(MoveCardDown(draggedCard, 0.5f));
                }
            }

            CheckForConsecutiveNames(spawnedCards);
        }
        else
        {
            Debug.Log("AndropApps 1");
            clicktime = 0;
            isDragging = false;

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

                if (nonEmptyCards.Count == 14)
                {
                    if (releasedCard.name == "DiscardArea")
                    {
                        StartCoroutine(setboxcoliderfalse(releasedCard));
                        releasedCard.GetComponent<BoxCollider>().enabled = false;
                        dropcardscript(draggedCard);
                        return;
                    }
                }
                else if (releasedCard.name == "DiscardArea")
                {
                    StartCoroutine(
                        discradtextshow("You have 13 cards, please draw a card to discard.")
                    );
                }

                if (nonEmptyCards.Count == 14)
                {
                    if (releasedCard.name == "FinishDesk")
                    {
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
                        discradtextshow(
                            "You have 13 cards, please draw a card from deck and then Finsish the game."
                        )
                    );
                }

                if (spawnedCards.Contains(releasedCard) && draggedCard != releasedCard)
                {
                    MoveCard2(spawnedCards, draggedCardIndex, releasedCardIndex);
                    SortCorrectOrder();
                    CheckForConsecutiveNames(spawnedCards);
                    return;
                }
            }

            CheckForConsecutiveNames(spawnedCards);
            StartCoroutine(MoveCardBack(draggedCard, draggedCardOriginalPosition, 0.5f));
        }

        draggedCard = null;

        for (int i = 0; i < discardedcards.Count; i++)
        {
            BoxCollider collider = discardedcards[i].GetComponent<BoxCollider>();
            collider.enabled = true;
        }
    }

    IEnumerator setboxcoliderfalse(GameObject obj)
    {
        yield return new WaitForSeconds(1);
        obj.GetComponent<BoxCollider>().enabled = true;
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
        if (spawnedCards.Contains(finishdeskcard))
        {
            spawnedCards.Remove(finishdeskcard);
        }

        if (correctOrder.Contains(finishdeskcard))
        {
            correctOrder.Remove(finishdeskcard);
        }

        if (nonEmptyCards.Contains(finishdeskcard))
        {
            nonEmptyCards.Remove(finishdeskcard);
        }

        CheckForConsecutiveNames(spawnedCards);

        list1.Clear();
        list2.Clear();
        list3.Clear();
        list4.Clear();
        list5.Clear();
        list6.Clear();
        SplitCardsIntoGroups(list1, list2, list3, list4, list5, list6);

        cachetmanager.API_CALL_declare();
        finished = false;
        declaredialogue.SetActive(false);
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

        // Update the correct order list based on the new card order
        SortCorrectOrder();

        newgroupcards = CombineListsFunction();

        spawnedCards = newgroupcards;

        InstantiateObjectsToMatchCount(spawnedCards, cardLocations, locationprefab);

        CheckForConsecutiveNames(spawnedCards);

        //for (int i = 0; i < spawnedCards.Count; i++)
        //{
        //    spawnedCards[i].transform.position = cardLocations[i].transform.position;
        //}

        list1.Clear();
        list2.Clear();
        list3.Clear();
        list4.Clear();
        list5.Clear();
        list6.Clear();
        SplitCardsIntoGroups(list1, list2, list3, list4, list5, list6);
        //canvastext(list1, list2, list3, list4, list5, list6, list7);

        CheckAfterEveryMove();

        CheckForConsecutiveNames(spawnedCards);
    }

    public void ClickedDiscard()
    {
        if (nonEmptyCards.Count == 14)
        {
            dropcardscript(uplist[0]);
            dicardcardbutton.SetActive(false);
            return;
        }
        else
            StartCoroutine(discradtextshow("You have 13 cards, please draw a card to discard."));
    }

    public void DiscardCard(GameObject card)
    {
        Debug.Log("RES_Check + DiscardCard called");
        if (numoflist == numofmatch && ispuresequenceincards)
        {
            Debug.Log("RES_Check + Declare API called");
            cachetmanager.API_CALL_declare();
        }

        if (card == null)
        {
            Debug.LogError("Trying to discard a null card.");
            return;
        }

        StartCoroutine(MoveCard(card, DiscardAreaPosition(), 0.5f));

        if (
            card.transform.childCount != 0
            && card.transform.GetChild(0) != null
            && card.name != "joker"
        )
        {
            Destroy(card.transform.GetChild(0).gameObject);
        }

        Debug.Log("Discard card " + card);

        // Check if the card is in the spawnedCards list before removing
        if (spawnedCards.Contains(card))
        {
            spawnedCards.Remove(card);
        }

        // Check if the card is in the correctOrder list before removing
        if (correctOrder.Contains(card))
        {
            correctOrder.Remove(card);
        }

        // Check if the card is in the nonEmptyCards list before removing
        if (nonEmptyCards.Contains(card))
        {
            nonEmptyCards.Remove(card);
        }

        string droppedcard = card.GetComponent<Card>()?.name?.ToUpper();

        if (!string.IsNullOrEmpty(droppedcard))
        {
            cachetmanager.Idropped = droppedcard;
            Debug.Log("RES_Check + " + droppedcard + " DiscardCard");
            cachetmanager.API_CALL_drop_card(droppedcard);
            discardedcards.Add(card);
        }
        else
        {
            Debug.LogError("Card name is null or empty.");
            return;
        }

        int display = 0;
        for (int i = 0; i < discardedcards.Count; i++)
        {
            discardedcards[i].transform.localScale = new Vector3(1.2f, 1.2f, 1);
            if (
                discardedcards[i] != null
                && discardedcards[i].GetComponent<SpriteRenderer>() != null
            )
            {
                display++;
                discardedcards[i].GetComponent<SpriteRenderer>().sortingOrder = display;
            }
        }

        for (int i = 0; i < discardedcards.Count - 1; i++)
        {
            if (discardedcards[i] != null && discardedcards[i].GetComponent<BoxCollider>() != null)
            {
                discardedcards[i].GetComponent<BoxCollider>().enabled = false;
            }
        }

        discardedcards[discardedcards.Count - 1].GetComponent<BoxCollider>().enabled = true;

        StartCoroutine(RearrangeAndMoveCards());
    }

    #region DiscardCard Previous Code
    //void DiscardCard(GameObject card)
    //{
    //    StartCoroutine(MoveCard(card, DiscardAreaPosition(), 0.5f));

    //    if (card.transform.childCount != 0)
    //        Destroy(card.transform.GetChild(0).gameObject);

    //    Debug.Log("Discard card " + card);
    //    spawnedCards.Remove(card);
    //    correctOrder.Remove(card);
    //    nonEmptyCards.Remove(card);

    //    string droppedcard = card.GetComponent<Card>().name.ToUpper();

    //    cachetmanager.Idropped = droppedcard;

    //    cachetmanager.API_CALL_drop_card(card.GetComponent<Card>().name);

    //    Debug.Log("Added" + card.name);
    //    discardedcards.Add(card);

    //    int display = 0;
    //    for(int i = 0;i < discardedcards.Count;i++)
    //    {
    //        display++;
    //        discardedcards[i].GetComponent<SpriteRenderer>().sortingOrder = display;
    //    }

    //    for(int i = 0; i < discardedcards.Count - 1;i++)
    //    {
    //        discardedcards[i].GetComponent<BoxCollider>().enabled = false;
    //    }

    //    StartCoroutine(RearrangeAndMoveCards());
    //}
    #endregion

    public void adddiscardedcardsfromserver(string card)
    {
        Debug.Log("Add drop card 3");
        Debug.Log("Add drop card " + card);
        //foundcard = find(card);
        foreach (GameObject go in deck2)
        {
            if (go.name == card)
            {
                Debug.Log("Add drop card name to find " + go.name);
                foundcard = go;
            }
        }
        Debug.Log("Add drop card name to found");
        Vector3 discardpos = DiscardAreaPosition();
        Debug.Log(foundcard.name + " droppedcard by opponent");
        GameObject discardedcard = Instantiate(foundcard);
        discardedcard.GetComponent<Animator>().enabled = false;
        discardedcard.transform.position = discardpos;
        discardedcard.transform.localScale = new Vector3(1.2f, 1.2f, 1.2f);
        discardedcard.name = card;
        Debug.Log("Added" + discardedcard);
        discardedcards.Add(discardedcard);

        //foreach(GameObject colidercard in discardedcards)
        //    colidercard.GetComponent<BoxCollider>().enabled = true;

        int display = 0;
        for (int i = 0; i < discardedcards.Count; i++)
        {
            display++;
            discardedcards[i].GetComponent<SpriteRenderer>().sortingOrder = display;
        }

        for (int i = 0; i < discardedcards.Count - 1; i++)
        {
            discardedcards[i].GetComponent<BoxCollider>().enabled = false;
        }

        discardedcards[discardedcards.Count - 1].GetComponent<BoxCollider>().enabled = true;
    }

    GameObject find(string card)
    {
        foreach (GameObject go in deck2)
        {
            if (go.name == card)
            {
                Debug.Log("Add drop card name to find " + go.name);
                return go;
            }
        }

        return null;
    }

    IEnumerator RearrangeAndMoveCards()
    {
        // Rearrange the cards in the spawnedCards list
        spawnedCards = spawnedCards.OrderBy(card => cardLocations.IndexOf(card.transform)).ToList();

        // Move all cards to their ordered locations
        for (int i = 0; i < spawnedCards.Count; i++)
        {
            GameObject card = spawnedCards[i];
            Transform targetLocation = cardLocations[i];

            // Move the card to its respective location
            StartCoroutine(MoveCard(card, targetLocation.position, 0.5f));
        }

        yield return null; // Wait for the frame to complete

        // Enable colliders of all cards after rearranging is complete
        foreach (GameObject card in spawnedCards)
        {
            Collider cardCollider = card.GetComponent<Collider>();
            if (cardCollider != null)
            {
                cardCollider.enabled = true;
            }
        }

        SortCorrectOrder();
    }

    Vector3 DiscardAreaPosition()
    {
        // Implement your logic to return the position of the discard area
        // This can be the position of a specific GameObject, or a predefined position
        // For example, you can return the position of the GameObject named "DiscardArea"
        GameObject discardArea = GameObject.Find("DiscardArea");
        if (discardArea != null)
        {
            return discardArea.transform.position;
        }

        // If the discard area is not found, you can return a default position
        return Vector3.zero;
    }

    IEnumerator MoveCardBack(GameObject card, Vector3 targetPosition, float duration)
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

            card.transform.position = targetPosition;
        }
    }

    void MoveCard2<T>(List<T> list, int fromIndex, int toIndex)
    {
        if (fromIndex == toIndex)
        {
            // No change needed
            return;
        }

        groupButton.SetActive(false);
        for (int i = 0; i < uplist.Count; i++)
        {
            uplist[i].GetComponent<Card>().cardup = false;
        }

        // Determine the direction based on indices
        Direction direction = (toIndex > fromIndex) ? Direction.Right : Direction.Left;

        // Remove the item from the original position
        T item = list[fromIndex];
        list.RemoveAt(fromIndex);

        // Insert the item at the new position based on the direction
        if (direction == Direction.Right)
        {
            // Move to the right
            list.Insert(toIndex, item);
        }
        else
        {
            // Move to the left
            list.Insert(toIndex > 0 ? toIndex - 1 : 0, item);
        }

        for (int i = 0; i < spawnedCards.Count; i++)
        {
            GameObject card = spawnedCards[i];
            Transform targetLocation = cardLocations[i];

            // Set the position of the card to the position of the target location
            card.transform.position = targetLocation.position;
        }

        nonEmptyCards = spawnedCards.Where(card => card.name != "Empty(Clone)").ToList();
    }

    enum Direction
    {
        Left,
        Right,
    }

    void SpawnCards(int numberOfCards)
    {
        spawnedCards = new List<GameObject>();
        spritetochange.Clear();
        sprites.Clear();
        sprites.Clear();
        if (SceneName == "Rummy")
        {
            for (int i = 0; i < Set.Count; i++)
            {
                GameObject card = Instantiate(Set[i], spawnPoint.position, Quaternion.identity);
                card.GetComponent<Animator>().enabled = false;
                card.name = Set[i].name;
                spawnedCards.Add(card);
                spritetochange.Add(card);
                sprites.Add(card.GetComponent<SpriteRenderer>().sprite);
                card.GetComponent<ActiveCard>().ogsprite =
                    card.GetComponent<SpriteRenderer>().sprite;
                card.GetComponent<SpriteRenderer>().sprite = backcard;
                //deck.RemoveAt(i);
            }
        }
        else
        {
            int order = 13;
            for (int i = 0; i < Set.Count; i++)
            {
                GameObject card = Instantiate(Set[i], spawnPoint.position, Quaternion.identity);
                card.GetComponent<Animator>().enabled = false;
                card.name = Set[i].name;
                card.GetComponent<SpriteRenderer>().sortingOrder = order;
                spawnedCards.Add(card);
                spritetochange.Add(card);
                sprites.Add(card.GetComponent<SpriteRenderer>().sprite);
                card.GetComponent<ActiveCard>().ogsprite =
                    card.GetComponent<SpriteRenderer>().sprite;
                card.GetComponent<SpriteRenderer>().sprite = backcard;
                order--;
                //deck.RemoveAt(i);
            }

            int order2 = 1;
            for (int i = 0; i < spawnedCards.Count; i++)
            {
                spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order2;
                order2++;
            }
        }
    }

    List<GameObject> HasDuplicateStrings(List<GameObject> stringList)
    {
        HashSet<string> uniqueNames = new HashSet<string>();

        List<GameObject> duplicates = new List<GameObject>();

        foreach (GameObject gameObject in stringList)
        {
            if (!uniqueNames.Add(gameObject.name))
            {
                duplicates.Add(gameObject);
            }
        }

        return duplicates;
    }

    void SortCorrectOrder()
    {
        // Sort the correct order list based on suits (Diamonds, Spades, Hearts, Clubs)
        correctOrder = spawnedCards.OrderBy(card => GetSuitOrder(card)).ToList();
        list1.Clear();
        list2.Clear();
        list3.Clear();
        list4.Clear();
        list5.Clear();
        list6.Clear();
        SplitCardsIntoGroups(list1, list2, list3, list4, list5, list6);
        //canvastext(list1, list2, list3, list4, list5, list6, list7);
        CheckAfterEveryMove();
    }

    int GetSuitOrder(GameObject card)
    {
        // Extract the suit from the card name
        string cardName = card.name;
        cardName = card.name.Replace("_", "");
        // Assuming the format is "NUMBER_SUIT" (e.g., "FOUR_HEARTS")
        string[] parts = SplitString(cardName);
        Debug.Log("RES_Check + " + parts[0] + " , " + parts[1] + " of " + cardName);
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

    IEnumerator MoveCardsToLocations()
    {
        //for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardLocations.Count); i++)
        //{
        //    // Move the card to the respective location
        //    float duration = 1.0f; // Adjust the duration as needed
        //    float elapsed = 0f;
        //    spawnedCards[i].GetComponent<Animator>().enabled = true;
        //    Vector3 startPosition = spawnedCards[i].transform.position;
        //    Vector3 targetPosition = cardLocations[i].position;

        //    while (elapsed < duration)
        //    {
        //        spawnedCards[i].transform.position = Vector3.Lerp(startPosition, targetPosition, elapsed / duration);
        //        elapsed += Time.deltaTime;
        //        yield return null;
        //    }

        //    spawnedCards[i].transform.position = targetPosition;
        //}

        //float elapsed = 0f;
        //float duration = 10f;
        //while (elapsed < duration)
        //{
        //    for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardLocations.Count); i++)
        //    {
        //        float normalizedTime = elapsed / duration;
        //        spawnedCards[i].transform.position = Vector3.Lerp(spawnedCards[i].transform.position, cardLocations[i].position, normalizedTime);
        //    }

        //    elapsed += Time.deltaTime;

        //    if(elapsed > 1.3f)
        //    {
        //        break;
        //    }
        //    yield return null;
        //}

        //// Ensure that all cards are in their final positions
        //for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardLocations.Count); i++)
        //{
        //    spawnedCards[i].transform.position = cardLocations[i].position;
        //}


        for (int i = 0; i < Mathf.Min(spawnedCards.Count, cardLocations.Count); i++)
        {
            float duration = 0.2f; // Adjust the total duration as needed
            float elapsed = 0f;
            Vector3 startPosition = spawnedCards[i].transform.position;
            Vector3 targetPosition = cardLocations[i].position;

            while (elapsed < duration)
            {
                float t = Mathf.SmoothStep(0f, 1f, elapsed / duration);
                spawnedCards[i].transform.position = Vector3.Lerp(startPosition, targetPosition, t);
                elapsed += Time.deltaTime;
                yield return null;
            }

            spawnedCards[i].transform.position = targetPosition;
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = 1;
        }

        StartCoroutine(spawnwildcard());

        StartCoroutine(SortCorrectOrderAndRearrange());
    }

    IEnumerator SortCorrectOrderAndRearrange()
    {
        yield return new WaitForSeconds(0.2f);

        // Sort the correct order list based on suits (Diamonds, Spades, Hearts, Clubs)
        correctOrder = spawnedCards
            .OrderBy(card => GetSuitOrder(card))
            .ThenBy(card => GetCardRank(card))
            .ToList();

        // Rearrange the cards in the spawnedCards list based on the correct order
        //spawnedCards = spawnedCards.OrderBy(card => correctOrder.IndexOf(card)).ToList();

        //correctOrder = spawnedCards;

        spawnedCards = correctOrder;

        // Move all cards to their ordered locations
        MoveCardsToOrderedLocations();
    }

    string GetCardSuit(GameObject card)
    {
        // Extract the suit from the card name
        string cardName = card.name;

        // Assuming the format is "NUMBER_SUIT" (e.g., "FOUR_HEARTS")
        string[] parts = SplitString(cardName);

        return parts.Length >= 2 ? parts[0] : "";
    }

    void MoveCardsToOrderedLocations()
    {
        int currentLocationIndex = 0; // Index for the current card location

        Dictionary<string, int> suitCardCounts = new Dictionary<string, int>();

        // List to store the adjusted order with GameObjects between suits
        List<GameObject> adjustedOrder = new List<GameObject>();

        for (int i = 0; i < spawnedCards.Count; i++)
        {
            GameObject card = spawnedCards[i];

            // Determine the suit of the current card
            string currentSuit = GetCardSuit(card);

            // Increment the count for the current suit
            if (!suitCardCounts.ContainsKey(currentSuit))
            {
                suitCardCounts[currentSuit] = 1;
            }
            else
            {
                suitCardCounts[currentSuit]++;
            }

            // Determine the target location based on the current card index and suit count
            int cardsPerSuit = suitCardCounts[currentSuit];

            // Check if currentLocationIndex is within the valid range
            if (currentLocationIndex >= 0 && currentLocationIndex < cardLocations.Count)
            {
                Debug.Log("Check1");
                Transform targetLocation = cardLocations[currentLocationIndex];

                // Add the card to the adjusted order list
                adjustedOrder.Add(card);

                // Move the card to its respective location
                StartCoroutine(MoveCard(card, targetLocation.position, 0.5f));

                // Increment the current location index
                currentLocationIndex++;

                //Check if the suit changes
                if (i < spawnedCards.Count - 1 && currentSuit != GetCardSuit(spawnedCards[i + 1]))
                {
                    for (int j = 0; j < 2; j++)
                    {
                        // Insert a GameObject between suits
                        GameObject suitSeparator = Instantiate(suitSeparatorPrefab); // Create a GameObject as a suit separator
                        adjustedOrder.Add(suitSeparator);

                        emptygameobjects.Add(suitSeparator);
                        currentLocationIndex++;
                    }

                    // Skip the next location only if there are more cards of the current suit
                    if (cardsPerSuit < GetCardCountForSuit(currentSuit))
                    {
                        currentLocationIndex++;
                    }
                }
            }
            else
            {
                Debug.LogError("currentLocationIndex out of range.");
                break;
            }
        }
        // Update the spawnedCards list with the adjusted order
        spawnedCards = adjustedOrder;
        nonEmptyCards = adjustedOrder.Where(card => card.name != "Empty(Clone)").ToList();
        list1.Clear();
        list2.Clear();
        list3.Clear();
        list4.Clear();
        list5.Clear();
        list6.Clear();
        StartCoroutine(startanimate());
        SplitCardsIntoGroups(list1, list2, list3, list4, list5, list6);
        int order2 = 1;
        for (int i = 0; i < spawnedCards.Count; i++)
        {
            spawnedCards[i].GetComponent<SpriteRenderer>().sortingOrder = order2;
            order2++;
        }
        cardsspawned = true;
        //canvastext(list1, list2, list3, list4, list5, list6, list7);
        CheckAfterEveryMove();
    }

    IEnumerator startanimate()
    {
        //for (int j = 0; j < 13; j++)
        //{
        //    spawnedCards[j].GetComponent<Animator>().enabled = true;
        //    yield return new WaitForSeconds(0.01f);
        //    spritetochange[j].GetComponent<SpriteRenderer>().sprite = sprites[j];
        //    yield return new WaitForSeconds(0.01f);
        //}

        foreach (GameObject sp in spawnedCards)
        {
            if (sp.name == "Empty(Clone)")
            {
                // Skip this GameObject and continue to the next iteration
                continue;
            }
            sp.GetComponent<SpriteRenderer>().sprite = sp.GetComponent<ActiveCard>().ogsprite;

            yield return new WaitForSeconds(0.1f);
        }
    }

    int GetCardCountForSuit(string suit)
    {
        // Get the total count of cards for the given suit in the spawned cards
        return spawnedCards.Count(card => GetCardSuit(card) == suit);
    }

    IEnumerator MoveCard(GameObject card, Vector3 targetPosition, float duration)
    {
        if (card == null)
        {
            yield break; // Exit the coroutine if the card is null
        }

        float elapsed = 0f;
        Vector3 startPosition = card.transform.position;

        while (elapsed < duration)
        {
            if (card == null)
            {
                yield break; // Exit the coroutine if the card is null
            }

            card.transform.position = Vector3.Lerp(
                startPosition,
                targetPosition,
                elapsed / duration
            );
            elapsed += Time.deltaTime;
            yield return null;
        }

        if (card != null)
        {
            card.transform.position = targetPosition;
        }
    }

    void HighlightCardsInSequences(List<GameObject> list)
    {
        foreach (var card in list)
        {
            if (card.name == "joker")
            {
                if (card.transform.childCount == 1)
                    HighlightCard(card);
            }
            else if (card.transform.childCount == 0)
                HighlightCard(card);
        }
    }

    void RemoveHighlightCardsInSequences(List<GameObject> list)
    {
        foreach (var card in list)
        {
            if (card.name == "joker")
            {
                if (card.transform.childCount == 2)
                    Destroy(card.transform.GetChild(1).gameObject);
            }
            else if (card.transform.childCount != 0)
                Destroy(card.transform.GetChild(0).gameObject);
        }
    }

    void HighlightCard(GameObject card)
    {
        GameObject highlightCard = Instantiate(highlighSprite);
        highlightCard.transform.parent = card.transform;
        highlightCard.transform.localPosition = Vector3.zero;
        highlightCard.transform.localScale = new Vector3(0.95f, 1f, 1f);
        Debug.Log("high " + highlightCard.transform.parent.name);
    }

    #region UIButtons
    public void ReloadScene(string currentSceneName)
    {
        SceneManager.LoadScene(currentSceneName);
    }

    IEnumerator showgrouptext()
    {
        grouptext.gameObject.SetActive(true);
        yield return new WaitForSeconds(1);
        grouptext.gameObject.SetActive(false);
    }

    public void GroupButton()
    {
        if (list6.Count > 0)
        {
            StartCoroutine(showgrouptext());
            for (int i = 0; i < uplist.Count; i++)
            {
                uplist[i].GetComponent<Card>().cardup = false;
                StartCoroutine(MoveCardDown(uplist[i], 0.5f));
            }

            uplist.Clear();
        }
        else
        {
            newgroupcards = CombineListsFunction();

            spawnedCards = newgroupcards;

            InstantiateObjectsToMatchCount(spawnedCards, cardLocations, locationprefab);

            CheckForConsecutiveNames(spawnedCards);

            for (int i = 0; i < spawnedCards.Count; i++)
            {
                spawnedCards[i].transform.position = cardLocations[i].transform.position;
            }

            list1.Clear();
            list2.Clear();
            list3.Clear();
            list4.Clear();
            list5.Clear();
            list6.Clear();
            SplitCardsIntoGroups(list1, list2, list3, list4, list5, list6);
            //canvastext(list1, list2, list3, list4, list5, list6, list7);
            CheckAfterEveryMove();
        }

        groupButton.SetActive(false);
    }

    void CheckForConsecutiveNames(List<GameObject> list)
    {
        List<GameObject> objectsToRemove = new List<GameObject>();

        foreach (GameObject go in spawnedCards)
        {
            if (go.name != "Empty(Clone)")
            {
                if (discardedcards.Contains(go))
                {
                    Debug.Log("Removed " + go);
                    objectsToRemove.Add(go);
                }
            }
        }

        foreach (GameObject go in objectsToRemove)
        {
            spawnedCards.Remove(go);
            if (spawnedCards[spawnedCards.Count - 1].name == "Empty(Clone)")
            {
                Destroy(spawnedCards[spawnedCards.Count - 1]);
                spawnedCards.RemoveAt(spawnedCards.Count - 1);
            }
        }

        int consecutiveEmptyCount = 0;
        List<int> indexesToRemove = new List<int>();

        for (int i = 0; i < list.Count; i++)
        {
            if (list[i].name == "Empty(Clone)")
            {
                consecutiveEmptyCount++;

                if (consecutiveEmptyCount >= 3)
                {
                    indexesToRemove.Add(i - 2); // Add the index to remove from the list
                }
            }
            else
            {
                consecutiveEmptyCount = 0;
            }
        }

        // Remove the GameObjects from the list
        foreach (int index in indexesToRemove)
        {
            if (index >= 0 && index < list.Count)
            {
                Destroy(list[index]);
                list.RemoveAt(index);
            }
        }

        for (int i = 1; i < list.Count - 1; i++)
        {
            if (list[i].name == "Empty(Clone)" && list[i - 1].name != "Empty(Clone)")
            {
                if (list[i].name == "Empty(Clone)" && list[i + 1].name != "Empty(Clone)")
                {
                    GameObject newEmpty = Instantiate(
                        suitSeparatorPrefab,
                        list[i].transform.position + new Vector3(0.7f, 0f, 0f),
                        Quaternion.identity
                    );
                    list.Insert(i + 1, newEmpty);
                }
            }
        }

        if (list[0].name == "Empty(Clone)")
        {
            Destroy(list[0]);
            list.RemoveAt(0);
        }

        if (list[list.Count - 1].name == "Empty(Clone)")
        {
            Destroy(list[list.Count - 1]);
            list.RemoveAt(list.Count - 1);
        }

        if (!finished)
        {
            if (spawnedCards.Count > cardLocations.Count)
            {
                GameObject instantiatedObject = Instantiate(locationprefab);
                Transform lastloc = cardLocations[cardLocations.Count - 1].transform;
                instantiatedObject.transform.parent = locationparent.transform;
                instantiatedObject.transform.position = new Vector3(
                    lastloc.transform.position.x + 0.7f,
                    lastloc.transform.position.y,
                    lastloc.transform.position.z
                );
                cardLocations.Add(instantiatedObject.transform);
            }

            for (int i = 0; i < spawnedCards.Count; i++)
            {
                if (
                    spawnedCards[i].name != "Empty(Clone)"
                    && spawnedCards[i].GetComponent<Card>().cardup == true
                )
                    spawnedCards[i].transform.position = new Vector3(
                        cardLocations[i].transform.position.x,
                        cardLocations[i].transform.position.y + 0.5f,
                        cardLocations[i].transform.position.z
                    );
                else
                    spawnedCards[i].transform.position = cardLocations[i].transform.position;
            }
        }
    }

    List<GameObject> CombineListsFunction()
    {
        List<GameObject> combinedList = new List<GameObject>();

        for (int i = 0; i < spawnedCards.Count; i++)
        {
            combinedList.Add(spawnedCards[i]);
        }

        RemoveObjects(combinedList, uplist);

        if (combinedList[combinedList.Count - 1].transform.name == "Empty(Clone)")
        {
            GameObject gameObject = Instantiate(suitSeparatorPrefab);
            emptygameobjects.Add(gameObject);
            combinedList.Add(gameObject);
            for (int i = 0; i < uplist.Count; i++)
            {
                combinedList.Add(uplist[i]);
            }
        }
        else
        {
            for (int j = 0; j < 2; j++)
            {
                GameObject gameObject = Instantiate(suitSeparatorPrefab);
                emptygameobjects.Add(gameObject);
                combinedList.Add(gameObject);
            }
            for (int i = 0; i < uplist.Count; i++)
            {
                combinedList.Add(uplist[i]);
            }
        }

        for (int i = 0; i < uplist.Count; i++)
        {
            uplist[i].GetComponent<Card>().cardup = false;
        }

        uplist.Clear();

        return combinedList;
    }

    void InstantiateObjectsToMatchCount(
        List<GameObject> mainList,
        List<Transform> baseList,
        GameObject prefab
    )
    {
        int difference = mainList.Count - baseList.Count;

        if (difference > 0)
        {
            for (int i = 0; i < difference; i++)
            {
                GameObject instantiatedObject = Instantiate(prefab);
                GameObject lastloc = cardLocations[cardLocations.Count - 1].gameObject;
                instantiatedObject.transform.parent = locationparent.transform;
                instantiatedObject.transform.position = new Vector3(
                    lastloc.transform.position.x + 0.7f,
                    lastloc.transform.position.y,
                    lastloc.transform.position.z
                );
                baseList.Add(instantiatedObject.transform);
            }
        }
    }

    void RemoveObjects(List<GameObject> mainList, List<GameObject> objectsToRemove)
    {
        mainList.RemoveAll(obj => objectsToRemove.Contains(obj));
    }
    #endregion
}
