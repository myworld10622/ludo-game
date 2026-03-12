using UnityEngine;

[CreateAssetMenu(
    fileName = "PointRummyScriptable",
    menuName = "ScriptableObjects/PointRummyScriptable",
    order = 1
)]
public class PointRummyScriptable : ScriptableObject
{
    public string no_of_players;
    public string boot_value;
    public string tableCode;
}
