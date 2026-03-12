using System.Collections;
using TMPro;
using UnityEngine;

public class XaxisMovement : MonoBehaviour
{
    public float speed = 5f;

    private bool shouldMove;

    public GameObject obj;

    public bool spawn;

    public int xaxis = 550;

    public int amount = 10;

    public YAxisMovement yaxis;

    IEnumerator spawnnewtext()
    {
        while (!YAxisMovement.blasted)
        {
            yield return new WaitForSeconds(2);
            xaxis += 175;
            amount += 2;
            GameObject go = Instantiate(obj, this.transform);
            go.GetComponent<TextMeshProUGUI>().text = amount + "s";
            go.transform.localPosition = new Vector3(xaxis, -5, 0);
            yaxis.xaxisobjs.Add(go);
        }
    }

    bool IsInteger(float number)
    {
        return Mathf.Approximately(number, Mathf.Round(number));
    }

    void Update()
    {
        if (!shouldMove)
            return;

        if (!YAxisMovement.blasted)
            transform.Translate(Vector3.down * speed * Time.deltaTime);
    }
}
