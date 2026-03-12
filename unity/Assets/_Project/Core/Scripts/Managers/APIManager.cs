using System;
using System.Collections;
using System.Collections.Generic; // Requires Newtonsoft.Json package
using System.Threading.Tasks;
using Best.HTTP;
using Best.HTTP.Request.Upload.Forms;
using Newtonsoft.Json;
using UnityEngine;
using UnityEngine.Networking;

public class APIManager : MonoBehaviour
{
    private static APIManager _instance;
    public static APIManager Instance
    {
        get
        {
            if (_instance == null)
            {
                _instance = FindObjectOfType<APIManager>();
            }

            if (_instance == null)
            {
                var managerObject = new GameObject("APIManager");
                _instance = managerObject.AddComponent<APIManager>();
            }

            return _instance;
        }
    }
    private const string DefaultApiErrorMessage = "API PROBLEM CONTECT WITH BACKEND";

    private void Awake()
    {
        if (_instance != null && _instance != this)
        {
            Destroy(this.gameObject);
        }
        else
        {
            _instance = this;
            DontDestroyOnLoad(this.gameObject);
        }
    }

    public async Task<T> Post<T>(string url, Dictionary<string, string> formData)
    {
        CommonUtil.CheckLog("url " + url);
        MultipartFormDataStream form = new MultipartFormDataStream();
        foreach (var field in formData)
        {
            form.AddField(field.Key, field.Value);
        }
        var request = HTTPRequest.CreatePost(url);
        string json = DefaultApiErrorMessage;
        request.SetHeader("Token", Configuration.TokenLoginHeader);
        request.UploadSettings.UploadStream = form;
        try
        {
            var response = await request.GetHTTPResponseAsync();
            if (response.IsSuccess)
            {
                CommonUtil.CheckLog(
                    $"Res_CheckResponse: {typeof(T).FullName}" + response.DataAsText
                );
                //CommonUtil.CheckLog($"Expected return type: {typeof(T).FullName}");
                json = response.DataAsText;
                return DeserializeOrFallback<T>(json, DefaultApiErrorMessage);
            }
            else
            {
                CommonUtil.CheckLog(
                    $"Server sent an error: {response.StatusCode}-{response.Message}"
                );
                CommonUtil.CheckLog($"Server sent an error: {response.DataAsText}");
                return DeserializeOrFallback<T>(
                    response.DataAsText,
                    $"Server sent an error: {response.StatusCode}-{response.Message}"
                );
            }
        }
        catch (AsyncHTTPException e)
        {
            // 6. Error handling
            CommonUtil.CheckLog($"Request finished with error! Error: {e.Message}");
            return CreateErrorResponse<T>(e.Message);
        }
        return DeserializeOrFallback<T>(json, DefaultApiErrorMessage);
    }

    public async Task<T> PostWithCustomToken<T>(
        string url,
        Dictionary<string, string> formData,
        string token
    )
    {
        CommonUtil.CheckLog("url " + url);
        MultipartFormDataStream form = new MultipartFormDataStream();
        foreach (var field in formData)
        {
            form.AddField(field.Key, field.Value);
        }
        var request = HTTPRequest.CreatePost(url);
        string json = DefaultApiErrorMessage;
        request.SetHeader("Token", token);
        request.UploadSettings.UploadStream = form;
        try
        {
            var response = await request.GetHTTPResponseAsync();
            if (response.IsSuccess)
            {
                CommonUtil.CheckLog(
                    $"Res_CheckResponse: {typeof(T).FullName}" + response.DataAsText
                );
                //CommonUtil.CheckLog($"Expected return type: {typeof(T).FullName}");
                json = response.DataAsText;
                return DeserializeOrFallback<T>(json, DefaultApiErrorMessage);
            }
            else
            {
                CommonUtil.CheckLog(
                    $"Server sent an error: {response.StatusCode}-{response.Message}"
                );
                CommonUtil.CheckLog($"Server sent an error: {response.DataAsText}");
                return DeserializeOrFallback<T>(
                    response.DataAsText,
                    $"Server sent an error: {response.StatusCode}-{response.Message}"
                );
            }
        }
        catch (AsyncHTTPException e)
        {
            // 6. Error handling
            CommonUtil.CheckLog($"Request finished with error! Error: {e.Message}");
            return CreateErrorResponse<T>(e.Message);
        }
        return DeserializeOrFallback<T>(json, DefaultApiErrorMessage);
    }

    public async Task<T> PostRaw<T>(string url, Dictionary<string, string> formData)
    {
        CommonUtil.CheckLog("url " + url);

        // Serialize the formData dictionary into a JSON string
        string jsonData = JsonConvert.SerializeObject(formData);

        var request = HTTPRequest.CreatePost(url);
        string json = "API PROBLEM CONNECT WITH BACKEND";

        // Set the headers to indicate JSON content
        request.SetHeader("Token", Configuration.TokenLoginHeader);
        request.SetHeader("Content-Type", "application/json");

        // Upload raw JSON data as the request body
        request.UploadSettings.UploadStream = new System.IO.MemoryStream(
            System.Text.Encoding.UTF8.GetBytes(jsonData)
        );

        try
        {
            var response = await request.GetHTTPResponseAsync();
            if (response.IsSuccess)
            {
                CommonUtil.CheckLog(
                    $"Res_CheckResponse: {typeof(T).FullName}" + response.DataAsText
                );
                json = response.DataAsText;
                return DeserializeOrFallback<T>(json, "API PROBLEM CONNECT WITH BACKEND");
            }
            else
            {
                CommonUtil.CheckLog(
                    $"Server sent an error: {response.StatusCode}-{response.Message}"
                );
                CommonUtil.CheckLog($"Server sent an error: {response.DataAsText}");
                return DeserializeOrFallback<T>(
                    response.DataAsText,
                    $"Server sent an error: {response.StatusCode}-{response.Message}"
                );
            }
        }
        catch (AsyncHTTPException e)
        {
            // 6. Error handling
            CommonUtil.CheckLog($"Request finished with error! Error: {e.Message}");
            return CreateErrorResponse<T>(e.Message);
        }

        return DeserializeOrFallback<T>(json, "API PROBLEM CONNECT WITH BACKEND");
    }

    public async Task GetWallet()
    {
        string Url = Configuration.Url + Configuration.wallet;
        CommonUtil.CheckLog("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        Wallet wallet = new Wallet();
        wallet = await Post<Wallet>(Url, formData);
        if (wallet.code == 200)
        {
            PlayerPrefs.SetString("wallet", wallet.wallet);
            PlayerPrefs.Save();
        }
    }

    private static T DeserializeOrFallback<T>(string json, string fallbackMessage)
    {
        if (string.IsNullOrWhiteSpace(json))
        {
            return CreateErrorResponse<T>(fallbackMessage);
        }

        string trimmedJson = json.TrimStart();
        if (!trimmedJson.StartsWith("{") && !trimmedJson.StartsWith("["))
        {
            CommonUtil.CheckLog($"Invalid JSON payload: {json}");
            return CreateErrorResponse<T>(fallbackMessage);
        }

        try
        {
            return JsonConvert.DeserializeObject<T>(json);
        }
        catch (Exception ex)
        {
            CommonUtil.CheckLog($"JSON parse failed: {ex.Message} Raw: {json}");
            return CreateErrorResponse<T>(fallbackMessage);
        }
    }

    private static T CreateErrorResponse<T>(string message)
    {
        object instance;
        try
        {
            instance = Activator.CreateInstance(typeof(T));
        }
        catch
        {
            return default;
        }

        SetMemberValue(instance, "code", 0);
        SetMemberValue(instance, "Code", 0);
        SetMemberValue(instance, "message", message);
        SetMemberValue(instance, "Message", message);
        return (T)instance;
    }

    private static void SetMemberValue(object instance, string memberName, object value)
    {
        var type = instance.GetType();
        var field = type.GetField(memberName);
        if (field != null && field.FieldType.IsAssignableFrom(value.GetType()))
        {
            field.SetValue(instance, value);
            return;
        }

        var property = type.GetProperty(memberName);
        if (property != null && property.CanWrite && property.PropertyType.IsAssignableFrom(value.GetType()))
        {
            property.SetValue(instance, value);
        }
    }
}
