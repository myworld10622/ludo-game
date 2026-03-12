using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class WithdrawResponse : MonoBehaviour { }

[Serializable]
public class List
{
    public string id;
    public string img;
    public string title;
    public string coin;
    public string amount;
    public string isDeleted;
    public string created_date;
    public string updated_date;
}

[Serializable]
public class Redeem_Outputs
{
    public List<List> List;
    public string message;
    public int code;
}

[Serializable]
public class WithDrawalLogsDetials
{
    public GameObject Panel;
    public GameObject Withdraw,
        Redeem;
    public GameObject Prefab;
    public Transform Parent;
    public GameObject go;

    public List<GameObject> Clone;
    public GameObject NoLogsFound;

    public GameObject Total;
    public GameObject Bonus;
    public GameObject WinningWallet;
    public GameObject UnutilizedWallet;
    public GameObject Total2;
    public GameObject Bonus2;
    public GameObject WinningWallet2;
    public GameObject UnutilizedWallet2;
    public Image Profile;
}

[Serializable]
public class Datum
{
    public string id;
    public string user_id;
    public string redeem_id;
    public string coin;
    public string mobile;
    public string status;
    public string created_date;
    public string updated_date;
    public string isDeleted;
    public string user_name;
    public string user_mobile;
    public string bank_detail;
    public string adhar_card;
    public string upi;
}

[Serializable]
public class WithDrawalLogsOutputs
{
    public string message;
    public List<Datum> data;
    public int code;
}

[Serializable]
public class HistroyDetails
{
    public GameObject Prefab;
    public Transform Parent;
    public GameObject Go;
    public List<GameObject> Clone;
}

[Serializable]
public class Redeem_Details
{
    public HistroyDetails HistroyDetail;
    public GameObject MessagePanel;
}

[Serializable]
public class RedeemOutputs
{
    public string message;
    public int code;
}
