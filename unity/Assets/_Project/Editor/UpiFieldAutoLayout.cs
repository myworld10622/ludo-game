using System.Linq;
using UnityEditor;
using UnityEngine;

public static class UpiFieldAutoLayout
{
    private const string TargetName = "UPI ID InputField (Legacy)";
    private const float Left = 0f;
    private const float Right = -600f;
    private const float PosY = 10f;
    private const float Height = 120f;

    [MenuItem("Tools/RoxLudo/Fix UPI Field Layout")]
    public static void FixUpiLayout()
    {
        var candidates = Resources.FindObjectsOfTypeAll<RectTransform>()
            .Where(t => t != null && t.name == TargetName)
            .ToList();

        if (candidates.Count == 0)
        {
            Debug.LogWarning("UPI input field not found in scene.");
            return;
        }

        foreach (var rect in candidates)
        {
            var path = GetHierarchyPath(rect.transform);
            if (!path.Contains("Profile/BG/middle/Bank Details/Bank/FillDetails/PASSBOOK IMAGE :"))
            {
                continue;
            }

            Undo.RecordObject(rect, "Fix UPI Field Layout");
            rect.anchorMin = new Vector2(0f, 0f);
            rect.anchorMax = new Vector2(1f, 0f);
            rect.pivot = new Vector2(0.5f, 0.5f);
            rect.anchoredPosition = new Vector2(rect.anchoredPosition.x, PosY);
            rect.sizeDelta = new Vector2(rect.sizeDelta.x, Height);
            rect.offsetMin = new Vector2(Left, rect.offsetMin.y);
            rect.offsetMax = new Vector2(Right, rect.offsetMax.y);

            EditorUtility.SetDirty(rect);
            Debug.Log($"UPI field layout fixed at: {path}");
        }
    }

    private static string GetHierarchyPath(Transform target)
    {
        if (target == null)
        {
            return string.Empty;
        }

        var path = target.name;
        var current = target.parent;
        while (current != null)
        {
            path = current.name + "/" + path;
            current = current.parent;
        }

        return path;
    }
}
