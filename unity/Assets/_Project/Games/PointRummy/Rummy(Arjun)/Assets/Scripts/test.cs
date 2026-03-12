using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class test : MonoBehaviour
{
    public GameObject panel;
    public List<GameObject> list;

    // Start is called before the first frame update
    void Start()
    {
        GameObject panelPrefab = Instantiate(panel);
        panelPrefab.GetComponent<PanelPositioner>().cards = list;
    }

    // Update is called once per frame
    void Update()
    {
        
    }
}
