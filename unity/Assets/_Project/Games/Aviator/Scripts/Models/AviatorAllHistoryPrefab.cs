using System;
using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class AviatorAllHistoryPrefab : MonoBehaviour
{
    public TextMeshProUGUI name;
    public TextMeshProUGUI amount;
    public TextMeshProUGUI winning_amount;
    public Image avatar,
        coin;

    public void SetText(
        AviatorgamehistorGameData game_data,
        List<Sprite> m_buttons_history,
        bool is_all
    )
    {
        name.text = game_data.name;
        amount.text = game_data.amount;
        winning_amount.text = game_data.winning_amount;
        coin.sprite = m_buttons_history[UnityEngine.Random.Range(0, m_buttons_history.Count)];
        if (is_all)
        {
            avatar.sprite = SpriteManager.Instance.avatar[
                UnityEngine.Random.Range(0, SpriteManager.Instance.avatar.Count - 1)
            ];
        }
        else
        {
            avatar.sprite = SpriteManager.Instance.profile_image;
        }
    }
}
