using System.Collections;
using System.Collections.Generic;
using Newtonsoft.Json;
using UnityEngine;
using UnityEngine.Networking;

public class ApiManager : MonoBehaviour
{
    public string noOfPlayers;
    public ReciveTableClass reciveTableClass;
    public List<GameObject> listofroom;
    public RoomPrefabController roomPrefabController;
    public Transform roomTransform;




    public void ClickOnGetLobbyBtn(string no_of_players)
    {
        noOfPlayers = no_of_players;
        StartCoroutine(LudoPostRequest(Configuration.LudoGettablemaster));
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

        string userId = Configuration.GetId();
        string userToken = Configuration.GetToken();

        if (string.IsNullOrWhiteSpace(userId) || string.IsNullOrWhiteSpace(userToken))
        {
            Debug.LogError("RES_Check + Ludo table list aborted: missing logged-in user id/token.");
            yield break;
        }

        WWWForm form = new WWWForm();
        form.AddField("user_id", userId);
        form.AddField("no_of_players", noOfPlayers);
        form.AddField("token", userToken);

        using (UnityWebRequest request = UnityWebRequest.Post(url, form))
        {
            if (!string.IsNullOrWhiteSpace(Configuration.TokenLoginHeader))
            {
                request.SetRequestHeader("Token", Configuration.TokenLoginHeader);
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

                if (reciveTableClass?.table_data == null)
                {
                    Debug.LogWarning("RES_Check + Ludo table_list returned no table_data.");
                    yield break;
                }

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
