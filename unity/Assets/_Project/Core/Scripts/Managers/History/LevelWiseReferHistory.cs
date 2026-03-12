using AndroApps;
using Newtonsoft.Json;
using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using TMPro;
using UnityEngine;
using UnityEngine.Events;
using UnityEngine.Networking;
using UnityEngine.UI;

[Serializable]
public class Refferearnlog
{
    public string id;
    public string user_id;
    public string referred_user_id;
    public string coin;
    public string added_date;
    public string name;
    public string refer_count;
    public string level;
}

[Serializable]
public class LevelWiseRefer
{
    public List<Refferearnlog> refferearnlog;
    public string message;
    public int code;
}

public class LevelWiseReferHistory : MonoBehaviour
{
    public StatementDetails statementDetail;
    public StatementOutputs statementOutput;
    public Transform content;
    public GameObject prefab;
    public List<GameObject> listoflevelwisereferprefab;
    public LevelWiseRefer LevelWisedata;
    // // [Header("User")] public Manager CurrentPackage;
    // //public TMP_Dropdown LevelsDropDown;

    // [Header("LevelList")]
    // public List<GameObject> OptionsList = new List<GameObject>();
    // public GameObject OptionPrefab;
    // public Transform ListParent;
    // public GameObject ReferList;
    // public TMP_Text LevelNameTxt;
    // public List<Refferearnlog> originalList;
    // public List<Refferearnlog> filteredList;
    // public TMP_InputField searchInputField;
    // public TextMeshProUGUI leveltext;
    // public int levelnumber;
    public GameObject loadinganim;

    private void OnEnable()
    {
        LevelWiseRefer();
        //OptionListManage();
        //ReferList.SetActive(false);

        //   filteredList = new List<Refferearnlog>(originalList);
    }


    #region  Filter Options

    public void OnSearchValueChanged()
    {
        // filteredList = originalList
        // .Where(item =>
        // item.name.ToLower().Contains(searchInputField.text.ToLower()) ||
        // item.referred_user_id.ToLower().Contains(searchInputField.text.ToLower()))
        // .ToList();

        // UpdateDisplay(filteredList);
    }

    //void onlevelchange()
    //{
    //    filteredList = originalList
    //        .Where(item => item.level.ToLower().Contains(leveltext.text))
    //        .ToList();

    //    UpdateDisplay(filteredList);
    //}

    void UpdateDisplay(List<Refferearnlog> listToDisplay)
    {
        if (listoflevelwisereferprefab.Count > 0)
        {
            for (int i = 0; i < listoflevelwisereferprefab.Count; i++)
            {
                Destroy(listoflevelwisereferprefab[i]);
            }
        }

        listoflevelwisereferprefab.Clear();

        for (int i = 0; i < listToDisplay.Count; i++)
        {
            GameObject go = Instantiate(prefab, content);

            var TextReference = go.GetComponent<WithdrawtransactionsUI>();

            //only name as different use only depent on data


            TextReference.sr_no.text = listToDisplay[i].referred_user_id;
            TextReference.coin.text = listToDisplay[i].name;
            TextReference.status.text = listToDisplay[i].refer_count;
            TextReference.added_date.text = listToDisplay[i].coin;
            listoflevelwisereferprefab.Add(go);
        }
    }

    #endregion

    public void LevelWiseRefer()
    {
        StartCoroutine(PostLWR(0));
    }

    IEnumerator PostLWR(int level)
    {
        loadinganim.SetActive(false);

        string url = Configuration.LevelWiseRefer;
        WWWForm form = new WWWForm();

        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("level", level);


        using (UnityWebRequest request = UnityWebRequest.Post(url, form))
        {
            request.SetRequestHeader("token", Configuration.TokenLoginHeader);

            yield return request.SendWebRequest();

            if (request.result == UnityWebRequest.Result.ConnectionError || request.result == UnityWebRequest.Result.ProtocolError)
            {
                Debug.LogError("Error: " + request.error);
            }
            else
            {
                Debug.Log("RES_Value + Level Wise: " + request.downloadHandler.text);

                LevelWisedata = JsonConvert.DeserializeObject<LevelWiseRefer>(request.downloadHandler.text);

                if (listoflevelwisereferprefab.Count > 0)
                {
                    for (int i = 0; i < listoflevelwisereferprefab.Count; i++)
                    {
                        Destroy(listoflevelwisereferprefab[i]);
                    }
                }

                listoflevelwisereferprefab.Clear();


                for (int i = 0; i < LevelWisedata.refferearnlog.Count; i++)
                {
                    GameObject go = Instantiate(prefab, content);
                    var TextReference = go.GetComponent<WithdrawtransactionsUI>();

                    //only name as different use only depent on data


                    TextReference.sr_no.text = LevelWisedata.refferearnlog[i].referred_user_id;
                    TextReference.coin.text = LevelWisedata.refferearnlog[i].name;
                    TextReference.status.text = LevelWisedata.refferearnlog[i].coin;
                    TextReference.added_date.text = LevelWisedata.refferearnlog[i].added_date;
                    listoflevelwisereferprefab.Add(go);

                    // var data= go.GetComponent<LevelWiseData>();

                    // go.transform.GetChild(1).GetComponent<TextMeshProUGUI>().text = LevelWisedata.refferearnlog[i].referred_user_id;
                    // go.transform.GetChild(2).GetComponent<TextMeshProUGUI>().text = LevelWisedata.refferearnlog[i].name;
                    // go.transform.GetChild(3).GetComponent<TextMeshProUGUI>().text = LevelWisedata.refferearnlog[i].refer_count;
                    // go.transform.GetChild(4).GetComponent<TextMeshProUGUI>().text = LevelWisedata.refferearnlog[i].coin;
                    listoflevelwisereferprefab.Add(go);
                    //originalList.Add(LevelWisedata.refferearnlog[i]);
                }

                loadinganim.SetActive(false);
            }
        }
    }


    public void OptionListManage()
    {
        // if (ListParent.childCount > 0)
        // {
        //     for (int i = 0; i <= ListParent.childCount - 1; i++)
        //     {
        //         Destroy(ListParent.GetChild(i).gameObject);
        //     }
        //     OptionsList.Clear();
        // }
        // for (int i = 0; i <= 10; i++)
        // {
        //     GameObject obj = Instantiate(OptionPrefab, ListParent);
        //     obj.transform.GetChild(0).GetComponent<TMP_Text>().text = (i + 1).ToString();
        //     obj.GetComponent<Button>().onClick.AddListener(() => OnLevelBtnClick(obj.transform.GetChild(0).GetComponent<TMP_Text>()));
        //     OptionsList.Add(obj);
        // }
    }


    public void OnLevelBtnClick(TMP_Text Txt)
    {
        // if (listoflevelwisereferprefab.Count > 0)
        // {
        //     for (int i = 0; i < listoflevelwisereferprefab.Count; i++)
        //     {
        //         Destroy(listoflevelwisereferprefab[i]);
        //     }
        // }

        // listoflevelwisereferprefab.Clear();
        // loadinganim.SetActive(true);
        // LevelNameTxt.text = Txt.text;
        // //onlevelchange();
        // levelnumber = int.Parse(LevelNameTxt.text);
        // StartCoroutine(PostLWR(levelnumber));
        // ReferList.SetActive(false);
    }
}
