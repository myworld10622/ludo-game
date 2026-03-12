using System;
using System.Collections;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Threading.Tasks;
using Mkey;
using TMPro;
using Unity.Burst.Intrinsics;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;

public class PaymentManager : MonoBehaviour
{
    public Transform content;
    public GameObject prefab;
    public TMP_InputField custom,
        utr_inputfield;
    public GameObject dialogue,
        manual_panel;
    public Button automatic_button;
    public Image qr_code_image,
        manual_ss_img;
    public GameObject manual_ss_logo;
    private int automatic_amount,
        manual_amount;
    public GameObject transaction_prefab;
    public Transform transaction_parent;

    public List<GameObject> transaction_panel_obj;
    public List<GameObject> add_cash_panel_obj;

    private Dictionary<string, GameObject> spawned_objects = new Dictionary<string, GameObject>();
    List<GameObject> instantiatedHistory = new List<GameObject>();

    public GameObject selectedAddcash,
        finalpanel;
    public GameObject selectedRecentTransaction;

    public Sprite UploadScreenshort;
    public USDTManual uSDTManual;
    public TextMeshProUGUI principal,
        bonus,
        newamount;
    public List<GameObject> buttonsobjs;

    public GameObject USDT_AUTO;
    async void OnEnable()
    {
#if UNITY_WEBGL
    USDT_AUTO.SetActive(false);
#endif
        await AvailableChips();
        DefaultSet();
    }

    public void DefaultSet()
    {
        selectedAddcash.SetActive(true);
        selectedRecentTransaction.SetActive(false);

        ClickAddCashButton();

        manual_ss_img.sprite = UploadScreenshort;

        utr_inputfield.text = "";
        manual_ss_logo.SetActive(true);
        custom.text = "";
    }

    #region Add Chips

    public async void ClickAddCashButton()
    {
        foreach (var obj in transaction_panel_obj)
        {
            obj.SetActive(false);
        }
        foreach (var obj in add_cash_panel_obj)
        {
            obj.SetActive(true);
        }
    }

    private GameObject CreateNewChip(PlanDetailchip coin)
    {
        GameObject go = Instantiate(prefab, content.transform);

        ChipUI chipUI = go.GetComponent<ChipUI>();
        chipUI.coinText.text = coin.price;
        buttonsobjs.Add(chipUI.chipButton.gameObject);
        spawned_objects[coin.coin] = go;

        return go;
    }

    public void ShowChips(PlanDetailsWrapper details)
    {
        var sortedPlanDetails = details.PlanDetails.OrderBy(p => int.Parse(p.coin)).ToList();

        foreach (var detail in sortedPlanDetails)
        {
            GameObject go = spawned_objects.TryGetValue(detail.coin, out GameObject existingObject)
                ? existingObject
                : CreateNewChip(detail);

            go.SetActive(true);
            ChipUI chipUI = go.GetComponent<ChipUI>();

            chipUI.coinText.text = detail.price;

            if (detail.coin == detail.price)
            {
                chipUI.percentageobj.SetActive(false);
            }
            else
            {
                chipUI.percentageobj.SetActive(true);
                float value = CalculatePercentage(int.Parse(detail.coin), int.Parse(detail.price));
                chipUI.percentage.text = value + "%";
            }

            chipUI.chipButton.onClick.RemoveAllListeners();

            chipUI.chipButton.onClick.AddListener(
                () => PlaceOrder(detail.id, detail.price, detail.coin)
            );

            chipUI.chipButton.onClick.AddListener(() => changebuttonui(chipUI.chipButton));
        }
    }

    public static float CalculatePercentage(int coin, int price)
    {
        int amount = coin - price;
        float divide = (float)amount / price; // Convert amount to float to get correct division
        float percentage = divide * 100f;
        Debug.Log("RES_Check + Percent " + percentage);
        return percentage;
    }

    public void changebuttonui(Button btn)
    {
        for (int i = 0; i < buttonsobjs.Count; i++)
        {
            if (buttonsobjs[i] == btn.gameObject)
            {
                Debug.Log("buttonobj " + buttonsobjs[i].gameObject);
                buttonsobjs[i].GetComponent<Image>().color = HexToColor("#5A5A5AFF");
            }
            else
            {
                buttonsobjs[i].GetComponent<Image>().color = HexToColor("#FFFFFFFF");
            }
        }
        if (!buttonsobjs.Contains(btn.gameObject))
        {
            Debug.LogError("The button is NOT in the list!");
        }
        else
        {
            Debug.Log("The button IS in the list.");
        }
    }

    private Color HexToColor(string hex)
    {
        Color color;
        if (ColorUtility.TryParseHtmlString(hex, out color))
        {
            return color;
        }
        return Color.white; // Default color if parsing fails
    }

    public void PlaceOrder(string id, string amount, string coin)
    {
        automatic_button.onClick.RemoveAllListeners();
        automatic_button.onClick.AddListener(async () => await PlaceOrderAPI(id, amount));
        automatic_amount = int.Parse(amount);
        manual_amount = int.Parse(amount);
        uSDTManual.amount = int.Parse(amount);
        ShowNewUI(id, amount, coin);
        //popup.buttonclick(dialogue);
        //PopUpUtil.ButtonClick(dialogue);
    }

    public void ShowNewUI(string id, string amount, string coin)
    {
        finalpanel.SetActive(true);
        principal.text = amount;
        if (int.Parse(coin) > int.Parse(amount))
            bonus.text = int.Parse(coin) - int.Parse(amount) + "";
        else
            bonus.text = "0";
        newamount.text = coin;

        // automatic_button.onClick.RemoveAllListeners();
        // automatic_button.onClick.AddListener(async () => await PlaceOrderAPI(id, coin));
    }

    public void OpenPopUP()
    {
        PopUpUtil.ButtonClick(dialogue);
    }

    #endregion

    #region Automatic Payment

    public void OpenURLInBrowser(string url)
    {
        CommonUtil.CheckLog("RES_Check + url open " + url);
        if (Application.platform == RuntimePlatform.Android)
        {
            OpenURLInAndroid(url);
        }
        else
        {
            OpenURLInWeb(url);
        }
    }

    private void OpenURLInAndroid(string url)
    {
        try
        {
            using (var unityPlayer = new AndroidJavaClass("com.unity3d.player.UnityPlayer"))
            using (
                var currentActivity = unityPlayer.GetStatic<AndroidJavaObject>("currentActivity")
            )
            {
                using (
                    var intent = new AndroidJavaObject(
                        "android.content.Intent",
                        "android.intent.action.VIEW"
                    )
                )
                using (
                    var uri = new AndroidJavaClass("android.net.Uri").CallStatic<AndroidJavaObject>(
                        "parse",
                        url
                    )
                )
                {
                    intent.Call<AndroidJavaObject>("setData", uri);
                    currentActivity.Call("startActivity", intent);
                }
            }
        }
        catch (AndroidJavaException e)
        {
            if (e.Message.Contains("ActivityNotFoundException"))
            {
                Debug.LogError($"No application can handle this intent. URL: {url}");
            }
            else
            {
                Debug.LogError($"Failed to launch intent: {e.Message}");
            }
        }
    }

    [System.Obsolete]
    private void OpenURLInWeb(string url)
    {
        Debug.Log("Open URL Online");
        Application.OpenURL(url);
    }

    #endregion

    #region  Custom Payment

    public void CustomPayment()
    {
        if (custom.text != string.Empty)
        {
            if (int.Parse(custom.text) == 0)
            {
                CommonUtil.ShowToast("Please enter a number greater than 0");
            }
            else
            {
                automatic_button.onClick.RemoveAllListeners();
                automatic_button.onClick.AddListener(
                    async () => await PlaceOrderAPI("", custom.text)
                );
                manual_amount = int.Parse(custom.text);
                automatic_amount = int.Parse(custom.text);
                PopUpUtil.ButtonClick(dialogue);
            }
        }
        else
            LoaderUtil.instance.ShowToast("Please enter a valid amount");
    }

    #endregion

    #region Manual Payment

    public async void OpenManual()
    {
        await QR_API();
        manual_panel.SetActive(true);
    }

    // public async void StartDownloadQR(string qr_imag_url)
    // {
    //     await DownloadQRAsync(qr_imag_url);
    // }
    public IEnumerator DownloadQR(string qrImageUrl)
    {
        // Send a web request to download the image
        using (UnityWebRequest request = UnityWebRequestTexture.GetTexture(qrImageUrl))
        {
            yield return request.SendWebRequest();

            // Check for network errors
            if (request.result != UnityWebRequest.Result.Success)
            {
                Debug.LogError("Error downloading QR image: " + request.error);
                yield break;
            }

            // Get the downloaded texture
            Texture2D texture = DownloadHandlerTexture.GetContent(request);

            // Convert Texture2D to Sprite
            Sprite sprite = Sprite.Create(
                texture,
                new Rect(0, 0, texture.width, texture.height),
                Vector2.zero
            );

            // Assign the sprite to the Image component
            qr_code_image.sprite = sprite;
            Debug.Log("QR code updated successfully.");
        }
    }

    // public async Task DownloadQRAsync(string qrImageUrl)
    // {
    //     try
    //     {
    //         HttpWebRequest request = (HttpWebRequest)WebRequest.Create(qrImageUrl);
    //         request.Method = "GET";

    //         using (WebResponse response = await request.GetResponseAsync())
    //         using (Stream stream = response.GetResponseStream())
    //         {
    //             if (stream != null)
    //             {
    //                 // Load the image from the stream into a Texture2D
    //                 using (MemoryStream memoryStream = new MemoryStream())
    //                 {
    //                     await stream.CopyToAsync(memoryStream);
    //                     memoryStream.Seek(0, SeekOrigin.Begin);

    //                     // Create Texture2D from the downloaded data
    //                     byte[] imageData = memoryStream.ToArray();
    //                     Texture2D texture = new Texture2D(2, 2);
    //                     texture.LoadImage(imageData);

    //                     // Convert Texture2D to Sprite
    //                     Sprite sprite = Sprite.Create(
    //                         texture,
    //                         new Rect(0, 0, texture.width, texture.height),
    //                         Vector2.zero
    //                     );

    //                     // Assign the sprite to the Image component
    //                     Debug.Log("Update QR Successfuly..");
    //                     qr_code_image.sprite = sprite;
    //                 }
    //             }
    //         }
    //     }
    //     catch (Exception ex)
    //     {
    //         Debug.LogError("Error downloading QR image: " + ex.Message);
    //     }
    // }

    public void OnUpdateScreenShotButtonClick(string target)
    {
        ImageUtil.Instance.OpenGallery(target, manual_ss_img, manual_ss_logo);
    }

    public async Task UpdateScreenShot(string target)
    {
        UnityMainThreadDispatcher.Instance.Enqueue(() => { });
    }

    public async void SubmitManualPayment()
    {
        if (string.IsNullOrEmpty(utr_inputfield.text))
        {
            LoaderUtil.instance.ShowToast("Please enter UTR Address");
            return;
        }

        // Check if payment screenshot is missing
        if (string.IsNullOrEmpty(SpriteManager.Instance.base64forimgmanualss))
        {
            LoaderUtil.instance.ShowToast("Please upload the Screen Shot of your payment");
            return;
        }

        // Proceed with API call if all checks are passed
        await Manual_Payment_API();
    }

    #endregion

    #region Transaction History

    public async void ClickPurchaseTransactionsButton()
    {
        foreach (var obj in transaction_panel_obj)
        {
            obj.SetActive(true);
        }
        foreach (var obj in add_cash_panel_obj)
        {
            obj.SetActive(false);
        }
        await PurchaseHistoryAPI();
    }

    public void ShowTransactions(PurchaseHistoryData purchaseHistoryData)
    {
        CommonUtil.CheckLog(
            "RES_check + transactions count " + purchaseHistoryData.purchase_history.Count
        );
        if (purchaseHistoryData.purchase_history.Count > 0)
        {
            instantiatedHistory.ForEach(x => Destroy(x));
            instantiatedHistory.Clear();

            for (int i = 0; i < purchaseHistoryData.purchase_history.Count; i++)
            {
                var purchase = purchaseHistoryData.purchase_history[i];

                /*  if (!instantiatedHistory.ContainsKey(purchase.id))
                 { */
                // Instantiate the prefab and set its values
                GameObject go = Instantiate(transaction_prefab, transaction_parent);
                TransactionUI historyUI = go.GetComponent<TransactionUI>();

                historyUI.id.text = purchase.id;
                historyUI.pricce.text = purchase.price;

                historyUI.date.text = historyUI.FormatDateTime(purchase.added_date);

                if (purchase.status == "0")
                {
                    //pending
                    historyUI.status.text = "Pending";
                }
                else if (purchase.status == "1")
                {
                    historyUI.status.text = "Success";
                    //success
                }
                else
                {
                    historyUI.status.text = "Rejected";
                    //rejected
                }

                // Add the instantiated object to the dictionary
                instantiatedHistory.Add(go); // Store the GameObject in the dictionary
                //}
                /*     else
                        {
                            CommonUtil.CheckLog(transaction_parent.childCount.ToString());
                        } */
            }
        }
    }

    #endregion

    #region  API

    public async Task AvailableChips()
    {
        string Url = Configuration.PlanChips;
        CommonUtil.CheckLog("RES_Check + API-Call + AvailableChips");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        PlanDetailsWrapper details = new PlanDetailsWrapper();
        details = await APIManager.Instance.Post<PlanDetailsWrapper>(Url, formData);

        ShowChips(details);
    }

    public async Task PlaceOrderAPI(string plan_id, string amount)
    {
        if (amount == "0")
        {
            CommonUtil.ShowToast("Please Enter Amount Greater than 0");
            return;
        }
        string Url = Configuration.UpiGateway;
        CommonUtil.CheckLog("RES_Check + API-Call + PlaceOrder " + plan_id + " , " + amount);

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "plan_id", plan_id },
            { "amount", amount },
        };
        OrderDetails details = new OrderDetails();
        details = await APIManager.Instance.Post<OrderDetails>(Url, formData);
        if (details.code == 200)
            OpenURLInBrowser(details.intentData);
        else
            CommonUtil.ShowToast(details.message);
    }

    public async Task QR_API()
    {
        string Url = Configuration.addcashgetQR;
        CommonUtil.CheckLog("RES_Check + API-Call + QR_API");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        GetQRApiResponse response = new GetQRApiResponse();
        response = await APIManager.Instance.Post<GetQRApiResponse>(Url, formData);

        Debug.Log(response.message);
        Debug.Log(response.qr_image);
        //StartDownloadQR(response.qr_image);
        StartCoroutine(DownloadQR(response.qr_image));
    }

    public async Task Manual_Payment_API()
    {
        string Url = Configuration.addcash;
        CommonUtil.CheckLog("RES_Check + API-Call + Manual_Payment_API");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
            { "utr", utr_inputfield.text },
            { "price", manual_amount.ToString() },
            { "ss_image", SpriteManager.Instance.base64forimgmanualss },
            { "type", "0" },
        };
        UPISuccessResponse response = new UPISuccessResponse();
        response = await APIManager.Instance.Post<UPISuccessResponse>(Url, formData);

        LoaderUtil.instance.ShowToast(response.message);
        manual_panel.SetActive(false);

        if (response.code == 200)
        {
            utr_inputfield.text = "";
            manual_ss_logo.SetActive(true);

            manual_ss_img.sprite = UploadScreenshort;
        }
    }

    public async Task PurchaseHistoryAPI()
    {
        string Url = Configuration.purchasehistory;
        CommonUtil.CheckLog("RES_Check + API-Call + PurchaseHistoryAPI");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        PurchaseHistoryData response = new PurchaseHistoryData();
        response = await APIManager.Instance.Post<PurchaseHistoryData>(Url, formData);
        CommonUtil.CheckLog(response.message);
        Debug.Log("PurchaseHistoryAPI" + response.message);
        if (response.code == 200)
        {
            ShowTransactions(response);
        }
        else
        {
            Debug.Log("RES_CHECK:" + response.message + "CODE:" + response.code);
        }
    }
    #endregion
}
