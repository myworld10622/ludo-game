using System.Collections.Generic;
using UnityEngine;

[System.Serializable]
public struct GameOption
{
    public string optionName;
    public bool isEnabled;
}

[CreateAssetMenu(
    fileName = "GameBackendData",
    menuName = "ScriptableObjects/GameBackendData",
    order = 1
)]
public class GameBackendData : ScriptableObject
{
    public Sprite coin;
    public Color inputcolor;
    public Color placeholdercolor;
    public List<GameOption> options;
    public List<Sprite> avatars;
    public List<string> avatar_Name;
    public List<GameObject> obj;
    public int addchip;
    public string moveto;
}
