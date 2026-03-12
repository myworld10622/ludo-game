using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using DG.Tweening;
using Mkey;
using UnityEditor;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

public class PointRummyTableManager : MonoBehaviour
{
    public GameObject table_prefab;
    public Transform table_parent;
    public string players_count;
    public GameObject two_player_obj;
    public GameObject six_player_obj;
    public PointRummyScriptable point_rummy_scriptable;
    public GameSelection selection;

    async void OnEnable()
    {
        ChooseTwoPlayers();
    }

    #region Select player

    public async void ChooseTwoPlayers()
    {
        players_count = "2";
        two_player_obj.SetActive(true);
        six_player_obj.SetActive(false);
        await TableListingAPI(players_count);
    }

    public async void ChooseSixPlayers()
    {
        players_count = "6";
        six_player_obj.SetActive(true);
        two_player_obj.SetActive(false);
        await TableListingAPI(players_count);
    }

    #endregion

    #region Show Table Listing

    public void ShowTableListing(ResponseData response, string player_number)
    {
        Debug.Log("RESPONCE :" + response.message + "CODE" + response.code);
        if (response.code == 200)
        {
            foreach (Transform child in table_parent)
            {
                Destroy(child.gameObject);
            }

            foreach (var tableData in response.table_data)
            {
                CommonUtil.CheckLog("Table Boot Value: " + tableData.boot_value);
                if (tableData.boot_value == "0.00")
                    continue;
                GameObject tableInstance = Instantiate(table_prefab, table_parent);
                tableInstance.transform.localScale = Vector3.one;

                RoomPrefabUI uI = tableInstance.GetComponent<RoomPrefabUI>();

                var uiMapping = new Dictionary<Text, string>
                {
                    { uI.point_value, tableData.point_value },
                    { uI.min_entry, tableData.boot_value },
                    { uI.max_players, player_number },
                    { uI.total_players, tableData.online_members },
                };

                foreach (var mapping in uiMapping)
                {
                    mapping.Key.text = mapping.Value;
                }

                uI.play_button.onClick.AddListener(
                    () => RummyScene(uI.min_entry.text, uI.max_players.text)
                );
                tableData.no_of_players = player_number;
            }

            var pos = table_parent.GetComponent<RectTransform>().position;
            pos.y = 0;
            table_parent.GetComponent<RectTransform>().position = pos;
        }
        else if (response.code == 205)
        {
            // point_rummy_scriptable.no_of_players = response.table_data[0].no_of_players;
            // point_rummy_scriptable.boot_value = response.table_data[0].boot_value;
            PlayerPrefs.SetString("Getpointplayer", response.table_data[0].no_of_players);
            PlayerPrefs.SetString("Getpointboot", response.table_data[0].boot_value);
            //SceneLoader.Instance.LoadScene("Rummy_13.unity");
            DOVirtual.DelayedCall(2f, () =>
            {
                //Addressables.LoadSceneAsync("Rummy_13.unity");
                SceneLoader.Instance.LoadScene("Rummy_13");
            });
        }
        else if (response.code == 406)
        {
            LoaderUtil.instance.ShowToast(response.message);
        }
    }

    #endregion

    #region Enter Rummy Scene

    public void RummyScene(string value, string players)
    {
        // point_rummy_scriptable.boot_value = value;
        // point_rummy_scriptable.no_of_players = players;
        PlayerPrefs.SetString("Getpointboot", value);
        PlayerPrefs.SetString("Getpointplayer", players);
        SceneLoader.Instance.LoadScene("Rummy_13");
    }

    #endregion

    #region API

    public async Task TableListingAPI(string no_of_players)
    {
        string Url = Configuration.RummyGettablemaster;
        CommonUtil.CheckLog("RES_Check + API-Call + Rummy Get table master");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "no_of_players", no_of_players },
        };

        var details = await APIManager.Instance.Post<ResponseData>(Url, formData);
        ShowTableListing(details, no_of_players);
    }

    #endregion
}
