using System.Collections;
using System.Collections.Generic;
using Newtonsoft.Json;
using UnityEngine;
using UnityEngine.Networking;

public class ApiManager : MonoBehaviour
{
    public const string ludoLobbyUrl = "https://games.androappstech.in/api/ludo/get_table_master";
    public const string userid = "155";
    public string noOfPlayers;
    public const string token = "91c595b94b2bf6ee794111a431ecd05d";
    public const string TokenLoginHeader =
         "c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909";


    public ReciveTableClass reciveTableClass;
    public List<GameObject> listofroom;
    public RoomPrefabController roomPrefabController;
    public Transform roomTransform;




    public void ClickOnGetLobbyBtn(string no_of_players)
    {
        noOfPlayers = no_of_players;
        StartCoroutine(LudoPostRequest(ludoLobbyUrl));
    }

    IEnumerator LudoPostRequest(string url)
    {
        if (listofroom.Count > 0)
        {
            for (int i = 0; i < listofroom.Count; i++)
            {
                Destroy(listofroom[i]);
            }
            listofroom.Clear();
        }

        WWWForm form = new WWWForm();
        form.AddField("user_id", userid);
        form.AddField("no_of_players", noOfPlayers);
        form.AddField("token", token);

        using (UnityWebRequest request = UnityWebRequest.Post(url, form))
        {
            if (TokenLoginHeader != null)
            {
                request.SetRequestHeader("Token", TokenLoginHeader);
            }
            else
            {
                Debug.LogError("Error: TokenLogIn is null.");
                yield break;
            }

            yield return request.SendWebRequest();

            if (
                request.result == UnityWebRequest.Result.ConnectionError || request.result == UnityWebRequest.Result.ProtocolError
            )
            {
                Debug.LogError("RES_Check + Error: " + request.error);
            }
            else
            {
                string response = request.downloadHandler.text;
                Debug.Log("RES_Check + Ludo table_list Response: " + response);
                reciveTableClass = JsonConvert.DeserializeObject<ReciveTableClass>(response);

                for (int i = 0; i < reciveTableClass.table_data.Count; i++)
                {
                    RoomPrefabController roomPrefab = Instantiate(roomPrefabController, roomTransform, false);
                    roomPrefab.SetPrefabData(reciveTableClass.table_data[i].boot_value, int.Parse(noOfPlayers));
                    listofroom.Add(roomPrefab.gameObject);
                }
            }
        }
    }
}

// Root myDeserializedClass = JsonConvert.DeserializeObject<Root>(myJsonResponse);
[System.Serializable]
public class ReciveTableClass
{
    public string message;
    public List<TableData1> table_data;
    public int code;
}

[System.Serializable]
public class TableData1
{
    public string id;
    public string room_id;
    public string boot_value;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string online_members;
    public string no_of_players;
}