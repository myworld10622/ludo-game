using System.Collections;
using System.Collections.Generic;
using UnityEngine;

namespace LudoClassicOffline
{

    public class LudoNumbersPlayerHomeOffline : MonoBehaviour
    {
        public int playerIndex;
        public List<RectTransform> way_Point;
        public COLOR color;
    }

    public enum COLOR
    {
        red = 0,
        green = 1,
        blue = 3,
        yellow = 2
    }
}
