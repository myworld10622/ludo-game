using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class PanelPositioner : MonoBehaviour
{
    public RectTransform panel;
    public List<GameObject> cards;
    public float panelWidth;
    public Vector3 panelPosition;
    public GameManager obj;
    public bool active;
    public Sprite red,
        green;

    private void Update()
    {
        SetPanelPosition();
    }

    void Start()
    {
        obj = GameObject.Find("GameManager").GetComponent<GameManager>();
        if (!obj.gamestart)
        {
            this.GetComponent<Image>().enabled = false;
            this.transform.GetChild(0).gameObject.SetActive(false);
            StartCoroutine(wait());
        }
    }

    IEnumerator wait()
    {
        yield return new WaitForSeconds(2.5f);
        this.GetComponent<Image>().enabled = true;
        this.transform.GetChild(0).gameObject.SetActive(true);
    }

    void SetPanelPosition()
    {
        if (!obj.isDragging)
        {
            if (active)
                this.GetComponent<Image>().sprite = green;
            else
                this.GetComponent<Image>().sprite = red;

            if (cards.Count == 1)
            {
                float cardWidth = cards[0].GetComponent<Renderer>().bounds.size.x;
                panel.sizeDelta = new Vector2(cardWidth, panel.sizeDelta.y);

                panel.anchoredPosition = new Vector2(
                    cards[0].transform.position.x,
                    panel.anchoredPosition.y
                );
                panel.position = new Vector3(panel.position.x, -2.78f, panel.position.z);
                panel.GetComponent<Canvas>().sortingOrder = 350;
            }
            else
            {
                //panelWidth = cards[cards.Count - 1].transform.position.x - cards[0].transform.position.x;
                //panel.sizeDelta = new Vector2(panelWidth, panel.sizeDelta.y);
                //panelPosition = cards[0].transform.position;
                ////panelPosition.x += panelWidth / 2f;
                //panelPosition.x += panelWidth;
                //panel.position = panelPosition;
                //panel.position = new Vector3(panelPosition.x, -0.5f, panelPosition.z);

                float totalWidth = 0f;
                float minX = float.MaxValue;
                float maxX = float.MinValue;

                // Calculate total width of all cards and find min/max X positions
                foreach (GameObject card in cards)
                {
                    Renderer renderer = card.GetComponent<Renderer>();
                    totalWidth += renderer.bounds.size.x;

                    float cardMinX = renderer.bounds.min.x;
                    float cardMaxX = renderer.bounds.max.x;

                    if (cardMinX < minX)
                        minX = cardMinX;

                    if (cardMaxX > maxX)
                        maxX = cardMaxX;
                }

                // Adjust panel width to fit within the bounds of the cards
                float panelWidth = maxX - minX;
                panel.sizeDelta = new Vector2(panelWidth, panel.sizeDelta.y);

                // Position panel
                float panelX = (minX + maxX) / 2f;
                panel.anchoredPosition = new Vector2(panelX, panel.anchoredPosition.y);
                panel.position = new Vector3(panel.position.x, -2.78f, panel.position.z);
                panel.GetComponent<Canvas>().sortingOrder = 350;
            }
        }
    }
}
