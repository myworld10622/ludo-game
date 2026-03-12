using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.EventSystems;

public class Card : MonoBehaviour
{
    public bool cardup;
    public string name = "";

    private void OnEnable()
    {
        string originalString = gameObject.name;
        string modifiedString = originalString.Replace("(Clone)", "");
        name = modifiedString;
    }
}
