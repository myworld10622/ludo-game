using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class TPChaalSlider : MonoBehaviour
{
    public Slider slider;
    public float totalTime = 30f;
    public float timer;
    public bool ischaal;
    public TPSocketManager obj;
    public string id;
    public TextMeshProUGUI timertext;
    public SpriteRenderer card1;
    public SpriteRenderer card2;

    private void Start()
    {
        timer = 0;
    }

    private void Update()
    {
        if (ischaal)
        {
            slider.gameObject.SetActive(true);
            timertext.gameObject.SetActive(true);
            float normalizedValue = Mathf.Clamp01((totalTime - obj.chaaltimer) / totalTime);
            timertext.text = obj.chaaltimer + "";
            slider.value = normalizedValue;
        }
        else
        {
            slider.gameObject.SetActive(false);
            timertext.gameObject.SetActive(false);
        }
    }
}
