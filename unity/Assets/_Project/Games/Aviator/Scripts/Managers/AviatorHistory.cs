using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class AviatorHistory : MonoBehaviour
{
    [SerializeField]
    AvaitorGameHistoryDataList all_history,
        my_history;
    AviatorBlastRoot blast_root;

    [SerializeField]
    List<GameObject> all_history_objects,
        my_history_objects;

    [SerializeField]
    GameObject all_history_prefab,
        my_history_prefab;

    [SerializeField]
    Transform all_history_content,
        my_history_content;

    [SerializeField]
    List<Sprite> m_buttons_history = new List<Sprite>();

    public List<TextMeshProUGUI> last_blasts;

    public TextMeshProUGUI all_bet_amount;

    void OnEnable()
    {
        GetGameHistory();
        GetBlastHistory();
    }

    #region Blast History

    public async void GetBlastHistory()
    {
        string Url = AviatorConfig.AviatorHistory;
        CommonUtil.CheckLog("RES_Check + API-Call + all game history");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        blast_root = await APIManager.Instance.Post<AviatorBlastRoot>(Url, formData);

        if (blast_root.code == 200)
        {
            for (int i = 0; i < last_blasts.Count; i++)
            {
                Debug.Log("RES_Check + show Text ");
                if (blast_root.game_data.Count > i)
                {
                    last_blasts[i].text = blast_root.game_data[i].blast_time;
                }
            }
        }
    }

    #endregion

    #region All History
    public async void GetGameHistory()
    {
        string Url = AviatorConfig.AviatorGameHistory;
        CommonUtil.CheckLog("RES_Check + API-Call + all game history");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        if (all_history_objects.Count > 0)
        {
            for (int i = 0; i < all_history_objects.Count; i++)
            {
                Destroy(all_history_objects[i]);
            }
        }

        all_history_objects.Clear();

        all_history = await APIManager.Instance.Post<AvaitorGameHistoryDataList>(Url, formData);

        all_bet_amount.text = "" + all_history.game_data.Count;

        for (int i = 0; i < all_history.game_data.Count; i++)
        {
            GameObject go = Instantiate(all_history_prefab, all_history_content);
            go.GetComponent<AviatorAllHistoryPrefab>().SetText(all_history.game_data[i], m_buttons_history, true);
            all_history_objects.Add(go);
        }
    }
    #endregion

    #region My History
    public async void GetMyGameHistory()
    {
        string Url = AviatorConfig.AviatorMyHistory;
        CommonUtil.CheckLog("RES_Check + API-Call + my game history");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        if (my_history_objects.Count > 0)
        {
            for (int i = 0; i < my_history_objects.Count; i++)
            {
                Destroy(my_history_objects[i]);
            }
        }

        my_history_objects.Clear();

        my_history = await APIManager.Instance.Post<AvaitorGameHistoryDataList>(Url, formData);

        //all_bet_amount.text = "" + all_history.game_data.Count;

        for (int i = 0; i < my_history.game_data.Count; i++)
        {
            GameObject go = Instantiate(my_history_prefab, my_history_content);
            go.GetComponent<AviatorAllHistoryPrefab>()
                .SetText(my_history.game_data[i], m_buttons_history, false);
            my_history_objects.Add(go);
        }
    }
    #endregion
}
