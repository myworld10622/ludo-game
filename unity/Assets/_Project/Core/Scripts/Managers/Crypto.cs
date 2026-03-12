using System.Collections;
using System.Collections.Generic;
using AndroApps;
using EasyUI.Toast;
using TMPro;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;
#if !UNITY_WEBGL
using QRCoder;
#endif

public class Crypto : MonoBehaviour
{

#if !UNITY_WEBGL
    public TMP_InputField amounttext;
    public GameObject qrCodeImage;
    public GameObject loading,
        copy;

    public TextMeshProUGUI
        addresstext,
        paynowtext;
    public Sprite defaultss;

    private void OnEnable()
    {
        reset();
        amounttext.onValueChanged.AddListener(
            delegate
            {
                reset();
            }
        );
    }

    public void reset()
    {
        qrCodeImage.GetComponent<Image>().sprite = defaultss;
        //qrCodeImage.transform.GetChild(2).gameObject.SetActive(true);
        paynowtext.gameObject.SetActive(true);
        //  PlaceholderTxt.SetActive(true);
        loading.SetActive(false);
        copy.SetActive(false);
        addresstext.gameObject.SetActive(false);
        HandleInputValueChanged(amounttext.text);
    }

    public void OnRedeem()
    {
        Debug.Log("OnWithdrawalLog: ");
        loading.gameObject.SetActive(true);
        StartCoroutine(PostRedeem(Configuration.GetId(), Configuration.GetToken()));
    }

    void Start()
    {
        if (amounttext == null)
        {
            return;
        }

        amounttext.onValueChanged.AddListener(HandleInputValueChanged);
    }

    void HandleInputValueChanged(string input)
    {
        if (float.TryParse(input, out float number))
        {
            float result = number / float.Parse(Configuration.getdollar());

            Debug.Log("RES_Check + Dollar Value " + float.Parse(Configuration.getdollar()));

            paynowtext.text = result.ToString();

            paynowtext.gameObject.SetActive(true);

            Debug.Log("RES_Check + Result: " + paynowtext.text);
        }
        else
        {
            Debug.LogWarning("Invalid input, please enter a valid number.");
        }
    }

    public IEnumerator PostRedeem(string Id, string Token) //string Token)
    {
        string Url = Configuration.CryptoPayment;
        WWWForm form = new WWWForm();

        form.AddField("user_id", Id);
        form.AddField("token", Token);
        form.AddField("amount", paynowtext.text);
        form.AddField("coin", amounttext.text);

        UnityWebRequest www = UnityWebRequest.Post(Url, form);
        www.SetRequestHeader("Token", Configuration.TokenLoginHeader);

        yield return www.SendWebRequest();

        if (www.result == UnityWebRequest.Result.Success)
        {
            var responseText = www.downloadHandler.text;
            Debug.Log("Response WithdrawalLog : " + responseText);
            PaymentData paymentdata = JsonUtility.FromJson<PaymentData>(responseText);
            if (paymentdata.code == 200)
            {
                addresstext.text = paymentdata.pay_address;
                copy.SetActive(true);
                //  qrCodeImage.transform.GetChild(2).gameObject.SetActive(false);
                addresstext.gameObject.SetActive(true);
                paynowtext.text = "Pay now " + paymentdata.pay_amount + " " + paymentdata.coin_type;
                GenerateAndDisplayQRCode(paymentdata.pay_address);
            }
            else
            {
                showtoastmessage(paymentdata.message);
            }
        }
        else
        {
            Debug.Log("Error: " + www.error);
        }
    }

    public void showtoastmessage(string message)
    {
        Toast.Show(message, 3f);
    }

#if !UNITY_WEBGL
    void GenerateAndDisplayQRCode(string text)
    {
        QRCodeGenerator qrGenerator = new QRCodeGenerator();
        QRCodeData qrCodeData = qrGenerator.CreateQrCode(text, QRCodeGenerator.ECCLevel.Q);
        Texture2D qrCodeTexture = QRCodeHelper.GetQRCodeAsTexture2D(qrCodeData, 20);

        qrCodeImage.GetComponent<Image>().sprite = Sprite.Create(
            qrCodeTexture,
            new Rect(0, 0, qrCodeTexture.width, qrCodeTexture.height),
            new Vector2(0.5f, 0.5f)
        );
        loading.SetActive(false);
        qrCodeImage.gameObject.SetActive(true);
        paynowtext.gameObject.SetActive(true);
    }
#endif

    public void copytext()
    {
        GUIUtility.systemCopyBuffer = addresstext.text;
        showtoastmessage("Address Copied");
    }
#endif
}
[System.Serializable]
public class PaymentData
{
    public int order_id;
    public string Total_Amount;
    public string payment_id;
    public string pay_address;
    public float pay_amount;
    public string coin_type;
    public string message;
    public int code;
}