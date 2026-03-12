#if !UNITY_WEBGL
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
//using QRCoder;
using UnityEngine.UI;
using UnityEngine.Networking;
using TMPro;
using AndroApps;
using QRCoder;


public class QRCodeScanner : MonoBehaviour
{
    public Image qrCodeImage;

    public PaymentData paymentdata;
    public TMP_InputField coinsinputfield;

    #region Call QR API

    public void PlaceOrder()
    {
        StartCoroutine(PlaceORderRequest());
    }

    IEnumerator PlaceORderRequest()
    {
        string url = Configuration.Place_Order_NowPayment;
        WWWForm form = new WWWForm();
        //Debug.Log("RES_Check + enter search " + CurrentPackage.search);
        form.AddField("user_id", Configuration.GetId());
        form.AddField("token", Configuration.GetToken());
        form.AddField("coin", coinsinputfield.text);

        Debug.Log(
            "RES_Check + coin: "
                + coinsinputfield.text
                + " + user_id: "
                + Configuration.GetId()
                + " token: "
                + Configuration.GetToken()
        );

        using (UnityWebRequest request = UnityWebRequest.Post(url, form))
        {
            if (Configuration.TokenLoginHeader != null)
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
                request.result == UnityWebRequest.Result.ConnectionError
                || request.result == UnityWebRequest.Result.ProtocolError
            )
            {
                Debug.LogError("Error: " + request.error);
            }
            else
            {
                string response = request.downloadHandler.text;
                Debug.Log("RES_Chck + Sent Friend Request Response: " + response);
                paymentdata = JsonUtility.FromJson<PaymentData>(response);
                GenerateAndDisplayQRCode(paymentdata.pay_address);
            }
        }
    }

    #endregion



    void GenerateAndDisplayQRCode(string text)
    {
        //QRCodeGenerator qrGenerator = new QRCodeGenerator();
        //QRCodeData qrCodeData = qrGenerator.CreateQrCode(text, QRCodeGenerator.ECCLevel.Q);
        //Texture2D qrCodeTexture = QRCodeHelper.GetQRCodeAsTexture2D(qrCodeData, 20);

        //qrCodeImage.sprite = Sprite.Create(qrCodeTexture, new Rect(0, 0, qrCodeTexture.width, qrCodeTexture.height), new Vector2(0.5f, 0.5f));
    }
}

public static class QRCodeHelper
{
    public static Texture2D GetQRCodeAsTexture2D(QRCodeData qrCodeData, int pixelsPerModule)
    {
        int size = qrCodeData.ModuleMatrix.Count * pixelsPerModule;
        Texture2D texture = new Texture2D(size, size);
        for (int x = 0; x < size; x++)
        {
            for (int y = 0; y < size; y++)
            {
                bool isFilled = qrCodeData.ModuleMatrix[y / pixelsPerModule][x / pixelsPerModule];
                texture.SetPixel(x, y, isFilled ? Color.black : Color.white);
            }
        }
        texture.Apply();
        return texture;
    }
}
#endif
