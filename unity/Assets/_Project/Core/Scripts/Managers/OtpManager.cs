using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

[System.Serializable]
public class OtpOutputs
{
    public string message;
    public int otp_id;
    public int code;
}

[System.Serializable]
public class OtpDetails
{
    public TMP_InputField MobileInputfield;
    public TMP_InputField OTPCodeInputfield;

    public void Clear()
    {
        MobileInputfield.text = string.Empty;
        OTPCodeInputfield.text = string.Empty;
    }
}

[System.Serializable]
public class ForgotOutputs
{
    public string message;
    public int code;
}

[System.Serializable]
public class ForgotDetails
{
    public TMP_InputField MobileInputfield;

    public void Clear()
    {
        MobileInputfield.text = string.Empty;
    }
}

public class OtpManager : MonoBehaviour
{
    public static OtpManager Instance;

    [Header("OTP Manager")]
    public OtpOutputs OtpOutput = new OtpOutputs();
    public OtpDetails OtpDetail;

    [Header("Forgot Manager")]
    public ForgotOutputs ForgotOutput = new ForgotOutputs();
    public ForgotDetails ForgotDetial;

    private void Awake()
    {
        Instance = this;
    }

    public void ClearOTPDetails()
    {
        ForgotDetial.Clear();
        OtpDetail.Clear();
    }
}
