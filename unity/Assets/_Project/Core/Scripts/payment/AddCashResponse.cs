using System;
using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class AddCashResponse : MonoBehaviour { }

[Serializable]
public class PurchaseHistoryEntry
{
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

[Serializable]
public class PurchaseHistoryData
{
    public string message;
    public List<PurchaseHistoryEntry> purchase_history;
    public int code;
}

[Serializable]
public class PlanDetailchip
{
    public string id;
    public string coin;
    public string price;
    public string title;
    public string added_date;
    public string updated_date;
    public string isDeleted;
}

[Serializable]
public class PlanDetailsWrapper
{
    public int code;
    public string message;
    public List<PlanDetailchip> PlanDetails;
}

[Serializable]
public class OrderDetails
{
    public int order_id;
    public string Total_Amount;
    public string intent_url;
    public string intentData;
    public string transaction_id;
    public string gateway_transaction_id;
    public string message;
    public int code;
}

[Serializable]
public class AutomaticPaymentStatusResponse
{
    public int code;
    public string message;
    public int order_id;
    public string transaction_id;
    public string gateway_transaction_id;
    public string status;
    public string status_label;
    public bool is_terminal;
    public string amount;
    public string updated_date;
}

[Serializable]
public class RummyKingOrderDetails
{
    public int order_id;
    public string Total_Amount;
    public string Intent;
    public string message;
    public int code;
}

[System.Serializable]
public class GetQRApiResponse
{
    public int code;
    public string message;
    public bool gateway_enabled;
    public string gateway_name;
    public string type;           // "upi" or "bank"
    public string upi_id;
    public string bank_name;
    public string account_holder;
    public string account_number;
    public string ifsc_code;
    public string qr_image;
}

[System.Serializable]
public class UPISuccessResponse
{
    public string message;
    public string Utr;

    public int code;
}

[System.Serializable]
public class PurchaseHistoryItem
{
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
public class PurchaseHistoryResponse
{
    public string message;
    public PurchaseHistoryItem[] purchase_history;
    public int code;
}

[Serializable]
public class TransferPlayer
{
    public string user_id;
    public string username;
    public string name;
    public string mobile;
    public string email;
}

[Serializable]
public class TransferLookupResponse
{
    public string message;
    public TransferPlayer player;
    public int code;
}

[Serializable]
public class TransferHistoryEntry
{
    public string id;
    public string transfer_id;
    public string direction;
    public string status;
    public string amount;
    public string currency;
    public string user_id;
    public string username;
    public string mobile;
    public string email;
    public string note;
    public string added_date;
}

[Serializable]
public class TransferHistoryResponse
{
    public string message;
    public TransferHistoryEntry[] transfer_history;
    public int code;
}

[Serializable]
public class TransferWalletResponse
{
    public string message;
    public TransferHistoryEntry transfer;
    public string wallet;
    public int code;
}
