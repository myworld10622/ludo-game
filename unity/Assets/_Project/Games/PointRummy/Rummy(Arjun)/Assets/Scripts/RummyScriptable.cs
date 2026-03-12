
using UnityEngine;

[CreateAssetMenu(fileName = "RummyData", menuName = "ScriptableObjects/RummyScriptable", order = 1)]
public class RummyScriptable : ScriptableObject
{
    public string user_id;
    public string token;
    public string no_of_players;
    public string boot_value;
    public string tableCode;
    public string id;
}
