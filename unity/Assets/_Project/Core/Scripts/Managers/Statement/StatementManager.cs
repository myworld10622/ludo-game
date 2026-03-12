using System;
using System.Collections.Generic;
using System.Globalization;
using System.Threading.Tasks;
using UnityEngine;
using UnityEngine.UI;

public class StatementManager : MonoBehaviour
{
    public GameObject prefab;
    public Transform parent;
    public List<GameObject> statementobj;

    private int itemcount = 0;

    private void OnEnable()
    {
        foreach (var item in statementobj)
        {
            Destroy(item.gameObject);
        }
        statementobj.Clear();
        itemcount = 0;
        // Destroy all existing pool objects and reset active objects queue
        //  ObjectPoolUtil.DestroyPool(pool_key);
        // activeObjects.Clear();
        LoadStatements();
    }

    private void OnDisable()
    {
        foreach (var item in statementobj)
        {
            Destroy(item.gameObject);
        }
        statementobj.Clear();
        //   ObjectPoolUtil.DestroyPool(pool_key);
    }

    private void LoadStatements()
    {
        Debug.Log("Loading statements...");
        FetchAndDisplayStatements();
    }

    private async void FetchAndDisplayStatements()
    {
        string url = $"{Configuration.Url}{Configuration.GameStatement}";
        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };

        try
        {
            var statementOutput = await APIManager.Instance.Post<StatementOutputs>(url, formData);
            if (statementOutput?.statement == null || statementOutput.statement.Count == 0)
            {
                Debug.LogWarning("No statements available.");
                return;
            }

            foreach (var statement in statementOutput.statement)
            {
                itemcount++;
                GameObject go = Instantiate(prefab, parent);
                statementobj.Add(go);
                // statements.Add(go);

                // Assign values to UI elements
                SetText(go.transform.GetChild(0).GetChild(0), itemcount.ToString());
                SetText(go.transform.GetChild(1).GetChild(0), statement.source);
                SetText(go.transform.GetChild(2).GetChild(0), statement.source_id);

                float bracketAmount = float.Parse(statement.amount);
                var walletText = go.transform.GetChild(3).GetChild(0);
                var amountText = go.transform.GetChild(3).GetChild(1);

                SetText(walletText, statement.current_wallet.ToString());
                SetText(
                    amountText,
                    FormatBracketAmount(bracketAmount),
                    GetAmountColor(bracketAmount)
                );

                string formattedDateTime = FormatDateTime(statement.added_date);
                SetText(go.transform.GetChild(4).GetChild(0), formattedDateTime);
            }
        }
        catch (Exception ex)
        {
            Debug.LogError($"Failed to fetch statements: {ex.Message}\n{ex.StackTrace}");
        }
    }

    // Helper to set text and optional color
    private void SetText(Transform element, string text, Color? color = null)
    {
        var textComponent = element.GetComponent<Text>();
        textComponent.text = text;
        if (color.HasValue)
        {
            textComponent.color = color.Value;
        }
    }

    // Helper to format the bracket amount
    private string FormatBracketAmount(float amount)
    {
        return amount >= 0 ? $"(+{amount})" : $"({amount})".ToString();
    }

    // Helper to determine the color based on the bracket amount
    private Color GetAmountColor(float amount)
    {
        if (amount > 0)
            return Color.green;
        if (amount < 0)
            return Color.red;
        return Color.yellow;
    }

    private void UpdateStatementUI(GameObject obj, Statement statement, int position)
    {
        StatementUI statementUI = obj.GetComponent<StatementUI>();
        if (statementUI != null)
        {
            statementUI.SetData(statement, position);
        }
    }

    private void OnScrollChanged(Vector2 scrollPosition)
    {
        // Check if we need to load more data
        /*  ObjectPoolUtil.OnScrollChanged(
             scrollRect,
             statements,
             ref currentStartIndex,
             visibleItemCount,
             ref activeObjects,
             UpdateStatementUI
         ); */
    }

    public string FormatDateTime(string inputDateTime)
    {
        // Parse input date time string
        DateTime dateTime = DateTime.ParseExact(
            inputDateTime,
            "yyyy-MM-dd HH:mm:ss",
            System.Globalization.CultureInfo.InvariantCulture
        );

        // Format date part (dd-mmm-yy)
        string formattedDate =
            dateTime.ToString("dd")
            + "-"
            + GetMonthAbbreviation(dateTime.Month)
            + "-"
            + dateTime.ToString("yy");

        // Format time part (hh.mm AM/PM)
        string formattedTime =
            dateTime.ToString("hh:mm") + " " + (dateTime.Hour >= 12 ? "PM" : "AM");

        return formattedDate + "\n" + formattedTime;
    }

    private string GetMonthAbbreviation(int month)
    {
        switch (month)
        {
            case 1:
                return "Jan";
            case 2:
                return "Feb";
            case 3:
                return "Mar";
            case 4:
                return "Apr";
            case 5:
                return "May";
            case 6:
                return "Jun";
            case 7:
                return "Jul";
            case 8:
                return "Aug";
            case 9:
                return "Sep";
            case 10:
                return "Oct";
            case 11:
                return "Nov";
            case 12:
                return "Dec";
            default:
                return "";
        }
    }
}
