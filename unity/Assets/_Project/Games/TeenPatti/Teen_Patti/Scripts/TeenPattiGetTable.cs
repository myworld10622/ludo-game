using System;
using System.Collections;
using System.Collections.Generic;
using AndroApps;
using DG.Tweening;
using Mkey;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.Profiling;
using UnityEngine.Rendering;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

[System.Serializable]
public class TeenPattiTableData
{
    public string id;
    public string boot_value;
    public string maximum_blind;
    public string chaal_limit;
    public string pot_limit;
    public string added_date;
    public string updated_date;
    public string isDeleted;
    public string online_members;
    public string min_amount;
}

[System.Serializable]
public class TeenPattiResponseData
{
    public string message;
    public List<TeenPattiTableData> table_data;
    public int code;
}

public class TeenPattiGetTable : MonoBehaviour
{
    public GameObject PlayerSelection;
    private string lobbiesurl = Configuration.TeenPattiGettablemaster;
    public TeenPattiResponseData responseData;
    public GameObject tableprefab;

    public Transform tableparent;

    public List<GameObject> listofroom;

    public TeenPattiData data;

    public Button[] buttons;

    // Start is called before the first frame update
    void Start()
    {
        StartCoroutine(PostRequest());
    }

    public void OnTeenPattiClick()
    {
        if (listofroom.Count > 0)
        {
            for (int i = 0; i < listofroom.Count; i++)
            {
                Destroy(listofroom[i]);
            }
            listofroom.Clear();
        }
        foreach (Transform item in tableparent.transform.transform)
        {
            Destroy(item.gameObject);
        }

        //  StartCoroutine(PostRequest());
    }

    IEnumerator PostRequest()
    {
        Debug.Log("Start");
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("no_of_players", "5");
        form.AddField("token", Configuration.GetToken());
        using (UnityWebRequest request = UnityWebRequest.Post(lobbiesurl, form))
        {
            request.SetRequestHeader("Token", Configuration.TokenLoginHeader);

            yield return request.SendWebRequest();

            if (
                request.result == UnityWebRequest.Result.ConnectionError
                || request.result == UnityWebRequest.Result.ProtocolError
            )
            {
                Debug.LogError("Error: " + request.error);
            }
            else
            {
                string response = request.downloadHandler.text;
                Debug.Log("table_list Response: " + response);

                responseData = JsonUtility.FromJson<TeenPattiResponseData>(response);

                if (responseData.code == 411)
                {
                    //InternetCheck.Instance.checkinvalid(responseData.code);
                }

                //InternetCheck.Instance.checkinvalid(responseData.code);

                if (responseData.code == 205)
                {
                    //data.boot_value = "10";
                    PlayerPrefs.SetString("Gettpboot", "10");
                    Debug.Log("CHECK ALREADY IN TABLE:");
                    // this.GetComponent<GameSelection>()
                    //     .loaddynamicscenebyname("TeenPatti_GamePlay.unity");
                    DOVirtual.DelayedCall(2f, () =>
                    {
                        Debug.Log(" After Delay CHECK ALREADY IN TABLE:");
                        this.GetComponent<GameSelection>()
                            .loaddynamicscenebyname("TeenPatti_GamePlay");
                    });
                    //Addressables.LoadSceneAsync("TeenPatti_GamePlay.unity");
                    // SceneLoader.Instance.LoadScene("TeenPatti_GamePlay.unity");
                }

                int num = responseData.table_data.Count;

                for (int i = 0; i < num; i++)
                {
                    GameObject data = Instantiate(tableprefab);
                    data.transform.parent = tableparent;
                    data.transform.localScale = new Vector3(1, 1, 1);
                    listofroom.Add(data);
                }

                for (int i = 0; i < listofroom.Count; i++)
                {
                    int roomindex = i;
                    listofroom[i].transform.GetChild(0).GetComponent<Text>().text = responseData
                        .table_data[i]
                        .boot_value;
                    listofroom[i].transform.GetChild(1).GetComponent<Text>().text = responseData
                        .table_data[i]
                        .min_amount;
                    listofroom[i].transform.GetChild(2).GetComponent<Text>().text = responseData
                        .table_data[i]
                        .pot_limit;
                    listofroom[i].transform.GetChild(3).GetComponent<Text>().text = responseData
                        .table_data[i]
                        .online_members;
                    listofroom[i]
                        .transform.GetChild(4)
                        .GetComponent<Button>()
                        .onClick.AddListener(
                            () =>
                                TeenPattiScene(
                                    listofroom[roomindex]
                                        .transform.GetChild(4)
                                        .GetComponent<Button>()
                                        .transform.parent
                                )
                        );
                }
            }
        }
    }

    public void OnLoadMainMenu()
    {
        this.GetComponent<GameSelection>().loaddynamicscenebyname("HomePage");
    }

    public void TeenPattiScene(Transform parent)
    {
        //data.boot_value = parent.GetChild(0).GetComponent<Text>().text;
        PlayerPrefs.SetString("Gettpboot", parent.GetChild(0).GetComponent<Text>().text);
        this.GetComponent<GameSelection>().loaddynamicscenebyname("TeenPatti_GamePlay");
    }
}
