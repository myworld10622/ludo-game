using System.Collections;
using System.Collections.Generic;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{
    public class LudoNumbersGetTableInfoModel
    {
        public List<LudoNumbersUserData> PlayerInfo;
        public int thisPlayerSeatIndex;
        public List<int> playerMoves;
        public int turnTimer;
        public int extraTimer;
        public LudoNumbersGetTableInfoModel()
        {
            PlayerInfo = new List<LudoNumbersUserData>();
            thisPlayerSeatIndex = -1;
            playerMoves = new List<int>();
            turnTimer = 15;
            extraTimer = 10;
        }

    }
    [System.Serializable]
    public class LudoNumbersUserData
    {
        public string username;
        public string userId;
        public string userProfile;

        public List<int> tokenPositions;
        public int score;
        public int highestToken;
        public List<GameObject> playerCoockie;
        public List<GameObject> playerCoockieForClassicMode;
        public Text userNameText;
        public Image userImage;
        public GameObject artoonLogo;
        public Image timeImage;
        public GameObject diceNumber;
        public TextMeshProUGUI diceNumberText;
        public Image extraTimerImage;
        public Animator animatorOnTurn;
        public LudoNumbersPlayerHomeOffline ludoNumbersPlayerHome;
        public GameObject dice;
        public List<Image> ring;
        public List<Image> ringForClassicMode;
        public Color color;
        public Text scoreText;
        public GameObject scoreBox;	 // TODO
        public List<GameObject> lives;
        public GameObject infoBtn;
        public GameObject fristBox;
        public GameObject secondBox;
        public GameObject thirdBox;
        public List<GameObject> boxList  = new List<GameObject>();
        public GameObject leaveTableImage;
        public GameObject tokenParent;
        public GameObject tokenParentForClassicMode;
        public DiceAnimationOffline diceAnimation;
        public GameObject turnProfileBlink;
        public GameObject turnTimeShowArrow;
        public Animator arrowAnimationOnTurnTime;
        public GameObject smallRoundImage;
        public GameObject numberView;//TODO

        public LudoNumbersUserData()
        {
            username = "";
            userId = "";
            userProfile = "";
            tokenPositions = new List<int>();
            score = 0;
        }
    }

    [System.Serializable]
    public class LudoNumberBattleFinish
    {
        public string winnerUsername;
        public string winnerUserProfile;
        public int winnerScore;
        public int winAmount;

        public LudoNumberBattleFinish()
        {
            winnerUsername = "";
            winnerUserProfile = "";
            winnerScore = 0;
            winAmount = 0;
        }
    }
}
