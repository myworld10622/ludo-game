using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class SelectGamesMenu : MonoBehaviour
{
    // Background images for each category (Assign in Inspector)
    public List<Image> images = new List<Image>();

    public void ClickAllButton(GameObject panel)
    {
        //  images.ForEach(image => image.enabled = false);
        for (int i = 0; i < images.Count; i++)
        {
            if (images[i].gameObject.name == panel.name)
            {
                images[i].enabled = true;
            }
            else
            {
                images[i].enabled = false;
            }
        }
    }
}
