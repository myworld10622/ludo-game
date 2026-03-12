using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.SceneManagement;

public class ActiveCard : MonoBehaviour
{
    public Sprite ogsprite;
    public string scenename;

    // Start is called before the first frame update
    IEnumerator Start()
    {
        scenename = SceneManager.GetActiveScene().name;
        if (scenename == "Rummy_13")
        {
            yield return new WaitForSeconds(1);
            //if (!GameObject.Find("GameManager").GetComponent<GameManager>().Discardedlist.Contains(gameObject))
            //    GetComponent<Card>().enabled = true;
            //GetComponent<BoxCollider>().enabled = true;
        }
        else if (scenename == "Pool_Rummy")
        {
            yield return new WaitForSeconds(1);
            //if (!GameObject.Find("GameManager").GetComponent<GameManager_Pool>().Discardedlist.Contains(gameObject))
            //    GetComponent<Card>().enabled = true;
            //GetComponent<BoxCollider>().enabled = true;
        }
        else
        {
            yield return new WaitForSeconds(1);
            //if (!GameObject.Find("GameManager").GetComponent<GameManager_Deal>().Discardedlist.Contains(gameObject))
            //    GetComponent<Card>().enabled = true;
            //GetComponent<BoxCollider>().enabled = true;
        }
    }

    // Update is called once per frame
    void Update()
    {
        
    }
}
