using System.Collections;
using System.Collections.Generic;
using System.Net.Security;
using EasyUI.Toast;
using Newtonsoft.Json;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

[System.Serializable]
public class GetQRApiResponse_USDT
{
    public int code;
    public string message;
    public string qr_image;
    public string upi_id;
    public string usdt_address;
}

[System.Serializable]
public class UPISuccessResponse_USDT
{
    public int code;
    public string message;
    public string Utr;
}

[System.Serializable]
public class PurchaseHistoryItem_USDT
{/*  */
    public string id;
    public string user_id;
    public string plan_id;
    public string coin;
    public string price;
    public string payment;
    public string transaction_id;
    public string transaction_type;
    public string photo;
    public string status;
    public string utr;
    public string extra;
    public string razor_payment_id;
    public string json_response;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[System.Serializable]
public class PurchaseHistoryResponse_USDT
{
    public string message;
    public PurchaseHistoryItem[] purchase_history;
    public int code;
}
public class USDTManual : MonoBehaviour
{
    public GetQRApiResponse_USDT qrresponse;
    public UPISuccessResponse_USDT upiresponse;
    public PurchaseHistoryResponse_USDT historyResponse;
    public GameObject historypanel;
    public Transform parent;
    public GameObject purchaseinfoprefab;
    public Image qrCodeImage;
    public TMP_InputField UTRinputfield;
    public TMP_InputField Amountinputfield;
    public int amount;
    public TMP_Text UPI_ID_text;
    public TMP_Text AmountTxt;

    public Image SS_IMG;
    public Sprite SS_Sprite;

    public GameObject Ss_logo;

    public bool USDT;

    private void OnEnable()
    {
        SS_IMG.sprite = SS_Sprite;
        SS_IMG.gameObject.transform.GetChild(0).gameObject.SetActive(true);
        StartCoroutine(API_GETQR());
        Debug.Log("RES_VALUE AddCashManager Dollar Value: " + PlayerPrefs.GetString("getdollar"));
        float rate = float.Parse(PlayerPrefs.GetString("getdollar"));
        float Pay_Value = amount / rate;
        AmountTxt.text = "Please Pay " + Pay_Value.ToString("00.00");
    }

    #region ShowQR
    public IEnumerator API_GETQR()
    {
        Debug.Log("GetQR");
        string Url = Configuration.USDT_Get_QR;
        Debug.Log(
            "RES_Check + API-Call + addcashgetQR "
                + Url
                + " ID: "
                + Configuration.GetId()
                + " Token: "
                + Configuration.GetToken()
                + " Token Header "
                + Configuration.TokenLoginHeader
        );
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + addcashgetQR: " + responseText);
            qrresponse = new GetQRApiResponse_USDT();
            qrresponse = JsonConvert.DeserializeObject<GetQRApiResponse_USDT>(responseText);
            UPI_ID_text.text = qrresponse.usdt_address;
            StartCoroutine(DownloadQR(qrresponse.qr_image, qrCodeImage));
        }
        yield return null;
    }

    public void CopyToClipboard()
    {
        GUIUtility.systemCopyBuffer = UPI_ID_text.text;
        showtoastmessage("Address Copied");
    }

    IEnumerator DownloadQR(string qr_image, Image QR_Code)
    {
        using (UnityWebRequest www = UnityWebRequestTexture.GetTexture(qr_image))
        {
            yield return www.SendWebRequest();

            if (
                www.result == UnityWebRequest.Result.ConnectionError
                || www.result == UnityWebRequest.Result.ProtocolError
            )
            {
                Debug.LogError("Error downloading image: " + www.error);
            }
            else
            {
                Texture2D texture = DownloadHandlerTexture.GetContent(www);
                Sprite sprite = Sprite.Create(
                    texture,
                    new Rect(0, 0, texture.width, texture.height),
                    Vector2.zero
                );
                QR_Code.sprite = sprite;
            }
        }
    }
    #endregion

    #region addcash

    public void addcashbutton()
    {
        StartCoroutine(API_AddCash());
    }

    public void UploadScreenShort()
    {
        ImageUtil.Instance.OpenGallery("usdt_screenshort", SS_IMG, Ss_logo);
    }
    public IEnumerator API_AddCash()
    {
        Debug.Log("API_AddCash");
        string Url = Configuration.addcash;
        Debug.Log("RES_Check + API-Call + API_AddCash");
        WWWForm form = new WWWForm();
        form.AddField("utr", UTRinputfield.text);
        form.AddField("user_id", Configuration.GetId());
        form.AddField("price", amount.ToString());
        form.AddField("ss_image", SpriteManager.Instance.base64forimgeforusdt);
        form.AddField("token", Configuration.GetToken());
        form.AddField("type", "USDT");
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + API_AddCash: " + responseText);
            upiresponse = new UPISuccessResponse_USDT();
            upiresponse = JsonConvert.DeserializeObject<UPISuccessResponse_USDT>(responseText);

            if (upiresponse.code == 200)
            {
                showtoastmessage(
                    upiresponse.message
                        + ", Thank you for the purchase, our team will shortly add your amount into your wallet."
                );
            }
            else
            {
                showtoastmessage(upiresponse.message);
            }

            UTRinputfield.text = string.Empty;
            //Amountinputfield.text = string.Empty;
            this.gameObject.SetActive(false);
        }
        yield return null;
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 4f);
    }

    #endregion

    #region Purchase history

    public void purchaehistorybutton()
    {
        StartCoroutine(API_PurchaseHistory());
    }

    public IEnumerator API_PurchaseHistory()
    {
        Debug.Log("API_PurchaseHistory");
        string Url = Configuration.purchasehistory;
        Debug.Log("RES_Check + API-Call + API_PurchaseHistory");
        WWWForm form = new WWWForm();
        form.AddField("user_id", Configuration.GetId());
        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        yield return www.SendWebRequest();
        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Res_Value + API_PurchaseHistory: " + responseText);
            historyResponse = new PurchaseHistoryResponse_USDT();
            historyResponse = JsonConvert.DeserializeObject<PurchaseHistoryResponse_USDT>(
                responseText
            );
            historypanel.SetActive(true);
            if (parent.childCount > 0)
            {
                var children = new System.Collections.Generic.List<GameObject>();

                foreach (Transform child in parent)
                {
                    children.Add(child.gameObject);
                }

                foreach (var child in children)
                {
                    Destroy(child);
                }
            }
            for (int i = 0; i < historyResponse.purchase_history.Length; i++)
            {
                GameObject panelobj = Instantiate(purchaseinfoprefab, parent);
                panelobj.transform.GetChild(0).GetChild(0).GetComponent<Text>().text = (
                    i + 1
                ).ToString();
                panelobj.transform.GetChild(1).GetChild(0).GetComponent<Text>().text =
                    historyResponse.purchase_history[i].utr;
                panelobj.transform.GetChild(2).GetChild(0).GetComponent<Text>().text =
                    historyResponse.purchase_history[i].price;
                panelobj.transform.GetChild(3).GetChild(0).GetComponent<Text>().text =
                    historyResponse.purchase_history[i].added_date;
                string status = "";
                if (historyResponse.purchase_history[i].status == "0")
                    status = "Pending";
                else if (historyResponse.purchase_history[i].status == "1")
                    status = "Successfull";
                else if (historyResponse.purchase_history[i].status == "2")
                    status = "Rejected";
                panelobj.transform.GetChild(4).GetChild(0).GetComponent<Text>().text = status;
            }
        }
        yield return null;
    }

    #endregion
}
