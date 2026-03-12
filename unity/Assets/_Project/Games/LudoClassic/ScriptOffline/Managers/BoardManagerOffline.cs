using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{

    public enum playerColor
    {
        green,
        red,
        blue,
        yellow
    }

    public class BoardManagerOffline : MonoBehaviour
    {
        public Toggle greenToggle, redToggle, blueToggle, yellowToggle;
        public playerColor playerColor;

        public List<Image> homeBG;
        public List<Image> innerHome;
        public List<Image> innerHome0Child;
        public List<Image> innerHome1Child;
        public List<Image> innerHome2Child;
        public List<Image> innerHome3Child;
        public List<Sprite> homeBGSprite;
        public List<Sprite> innerHomeSprite;
        public List<Sprite> innerHomeChildSprite;
        public List<Image> plyer0Token;
        public List<Image> plyer1Token;
        public List<Image> plyer2Token;
        public List<Image> plyer3Token;
        public List<Sprite> playerTokenSprite;
        public Color green, blue, yellow, red;
        public List<Text> score;
        public List<Text> scoreText;
        public List<Image> leaveToken;
        public List<Sprite> leaveTokenSprite;
        public List<Image> tokenAroow;
        public List<Sprite> tokenArrowSprite;
        public List<Image> moveBG;
        public List<Sprite> moveBGSprite;
        public List<Image> plyer0Pathcolorbox;
        public List<Image> plyer1Pathcolorbox;
        public List<Image> plyer2Pathcolorbox;
        public List<Image> plyer3Pathcolorbox;
        public List<Sprite> plyerPathcolorboxSprite;
        public List<Image> winHome;
        public List<Sprite> winHomeSprite;
        public List<Image> leftPlayer;
        public List<Sprite> leftPlayerSprite;
        public Gradient greenGR, blueGR, yellowGR, redGR;

        private void Awake()
        {
            CheckColor(playerColor.ToString());
        }


        void Start()
        {
            greenToggle.onValueChanged.AddListener(delegate { if (greenToggle.isOn) CheckColor(playerColor.green.ToString()); playerColor = playerColor.green;});
            redToggle.onValueChanged.AddListener(delegate { if (redToggle.isOn) CheckColor(playerColor.red.ToString()); playerColor = playerColor.red;});
            blueToggle.onValueChanged.AddListener(delegate { if (blueToggle.isOn) CheckColor(playerColor.blue.ToString()); playerColor = playerColor.blue;});
            yellowToggle.onValueChanged.AddListener(delegate { if (yellowToggle.isOn) CheckColor(playerColor.yellow.ToString()); playerColor = playerColor.yellow;});
        }

        public void CheckColor(string color)
        {

            switch (color)
            {
                case "green":
                    ChangeBordColor(0);
                    break;
                case "blue":
                    ChangeBordColor(1);
                    break;
                case "yellow":
                    ChangeBordColor(2);
                    break;
                case "red":
                    ChangeBordColor(3);
                    break;
                default:
                    break;
            }
        }

        public void ChangeBordColor(int colorIndex)
        {
            int rotate = 90 * colorIndex;
            for (int i = 0; i < 4; i++)
            {
                homeBG[i].sprite = homeBGSprite[colorIndex];
                innerHome[i].sprite = innerHomeSprite[colorIndex];
                leaveToken[i].sprite = leaveTokenSprite[colorIndex];
                tokenAroow[i].sprite = tokenArrowSprite[colorIndex];
                moveBG[i].sprite = moveBGSprite[colorIndex];
                winHome[i].sprite = winHomeSprite[colorIndex];
                leftPlayer[i].sprite = leftPlayerSprite[colorIndex];
                tokenAroow[i].gameObject.transform.eulerAngles = new Vector3(0, 0, rotate);
                winHome[i].gameObject.transform.eulerAngles = new Vector3(0, 0, rotate);
                switch (colorIndex)
                {
                    case 0:
                        score[i].color = green;
                        scoreText[i].color = green;
                        break;
                    case 1:
                        score[i].color = blue;
                        scoreText[i].color = blue;
                        break;
                    case 2:
                        score[i].color = yellow;
                        scoreText[i].color = yellow;
                        break;
                    case 3:
                        score[i].color = red;
                        scoreText[i].color = red;
                        break;
                }



                switch (i)
                {
                    case 0:
                        for (int j = 0; j < innerHome0Child.Count; j++)
                        {
                            innerHome0Child[j].sprite = innerHomeChildSprite[colorIndex];
                        }
                        for (int j = 0; j < plyer0Token.Count; j++)
                        {
                            plyer0Token[j].sprite = playerTokenSprite[colorIndex];
                            plyer0Token[j].GetComponent<CoockieMovementOffline>().myColor = ReturnColor(colorIndex);
                            plyer0Token[j].GetComponent<CoockieMovementOffline>().colorOverSpeed = ReturnGradient(colorIndex);
                        }
                        for (int j = 0; j < plyer0Pathcolorbox.Count; j++)
                        {
                            plyer0Pathcolorbox[j].sprite = plyerPathcolorboxSprite[colorIndex];
                        }
                        break;
                    case 1:
                        for (int j = 0; j < innerHome1Child.Count; j++)
                        {
                            innerHome1Child[j].sprite = innerHomeChildSprite[colorIndex];
                        }
                        for (int j = 0; j < plyer1Token.Count; j++)
                        {
                            plyer1Token[j].sprite = playerTokenSprite[colorIndex];
                            plyer1Token[j].GetComponent<CoockieMovementOffline>().myColor = ReturnColor(colorIndex);
                            plyer1Token[j].GetComponent<CoockieMovementOffline>().colorOverSpeed = ReturnGradient(colorIndex);
                        }
                        for (int j = 0; j < plyer1Pathcolorbox.Count; j++)
                        {
                            plyer1Pathcolorbox[j].sprite = plyerPathcolorboxSprite[colorIndex];
                        }
                        break;
                    case 2:
                        for (int j = 0; j < innerHome2Child.Count; j++)
                        {
                            innerHome2Child[j].sprite = innerHomeChildSprite[colorIndex];
                        }
                        for (int j = 0; j < plyer2Token.Count; j++)
                        {
                            plyer2Token[j].sprite = playerTokenSprite[colorIndex];
                            plyer2Token[j].GetComponent<CoockieMovementOffline>().myColor = ReturnColor(colorIndex);
                            plyer2Token[j].GetComponent<CoockieMovementOffline>().colorOverSpeed = ReturnGradient(colorIndex);
                        }
                        for (int j = 0; j < plyer2Pathcolorbox.Count; j++)
                        {
                            plyer2Pathcolorbox[j].sprite = plyerPathcolorboxSprite[colorIndex];
                        }
                        break;
                    case 3:
                        for (int j = 0; j < innerHome3Child.Count; j++)
                        {
                            innerHome3Child[j].sprite = innerHomeChildSprite[colorIndex];
                        }
                        for (int j = 0; j < plyer3Token.Count; j++)
                        {
                            plyer3Token[j].sprite = playerTokenSprite[colorIndex];
                            plyer3Token[j].GetComponent<CoockieMovementOffline>().myColor = ReturnColor(colorIndex);
                            plyer3Token[j].GetComponent<CoockieMovementOffline>().colorOverSpeed = ReturnGradient(colorIndex);
                        }
                        for (int j = 0; j < plyer3Pathcolorbox.Count; j++)
                        {
                            plyer3Pathcolorbox[j].sprite = plyerPathcolorboxSprite[colorIndex];
                        }
                        break;
                    default:
                        break;
                }
                if (colorIndex == 3)
                    colorIndex = 0;
                else
                    colorIndex++;
            }
        }

        public Color ReturnColor(int colorIndex)
        {
            Color color = green;
            switch (colorIndex)
            {
                case 0:
                    color = green;
                    break;
                case 1:
                    color = blue;
                    break;
                case 2:
                    color = yellow;
                    break;
                case 3:
                    color = red;
                    break;
            }
            return color;
        }

        public Gradient ReturnGradient(int colorIndex)
        {
            Gradient color = greenGR;
            switch (colorIndex)
            {
                case 0:
                    color = greenGR;
                    break;
                case 1:
                    color = blueGR;
                    break;
                case 2:
                    color = yellowGR;
                    break;
                case 3:
                    color = redGR;
                    break;
            }
            return color;
        }
    }
}
