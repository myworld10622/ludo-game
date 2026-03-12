using System;
using System.Collections;
using System.Collections.Generic;
using System.Globalization;
using UnityEngine;
using UnityEngine.UI;

public class StatementUI : MonoBehaviour
{
    public Text idText;
    public Text sourceIdText;
    public Text sourceText;
    public Text walletText;
    public Text currentWalletText;
    public Text dateText;

    public void SetData(Statement statement, int count)
    {
        idText.text = count + "";
        sourceIdText.text = statement.id;
        sourceText.text = statement.source;
        currentWalletText.text = statement.current_wallet;
        float amount = float.Parse(statement.amount);
        SetWalletDetails(walletText, amount);
        dateText.text = FormatDateTime(statement.added_date);
    }

    private string FormatDateTime(string inputDateTime)
    {
        if (
            DateTime.TryParseExact(
                inputDateTime,
                "yyyy-MM-dd HH:mm:ss",
                CultureInfo.InvariantCulture,
                DateTimeStyles.None,
                out var dateTime
            )
        )
        {
            string formattedDate = dateTime.ToString("dd-MMM-yy", CultureInfo.InvariantCulture);
            string formattedTime = dateTime.ToString("hh:mm tt", CultureInfo.InvariantCulture);
            return $"{formattedDate}\n{formattedTime}";
        }
        return "Invalid Date";
    }

    private void SetWalletDetails(Text walletText, float amount)
    {
        walletText.text = (amount > 0 ? "(+" : "(") + amount.ToString("F2") + ")";
        walletText.color =
            amount > 0 ? Color.green
            : amount < 0 ? Color.red
            : Color.yellow;
    }
}
