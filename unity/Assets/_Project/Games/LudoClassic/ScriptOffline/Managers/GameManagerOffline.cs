using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using UnityEngine;
using UnityEngine.SceneManagement;

namespace LudoClassicOffline
{
    public class GameManagerOffline : MonoBehaviour
    {
        public bool gameRunOnSdk;
        public static GameManagerOffline instace;
        public SocketConnectionOffline socketConnection;
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public DashBoardManagerOffline dashBoardManager;
        public LudoNumberEventManagerOffline ludoNumberEventManager;
        public LudoNumbersAcknowledgementHandlerOffline ludoNumbersAcknowledgementHandler;

        [SerializeField]
        internal LudoNumberUiManagerOffline LudoNumberUiManager;
        public GameState gameState;
        public RectTransform emojiParent,
            emojiParentOppo;
        public string selfUserID;

        private void Awake()
        {
            if (instace == null)
                instace = this;

            Input.multiTouchEnabled = false;
            Screen.orientation = ScreenOrientation.LandscapeLeft;
        }

        public void Signup() =>
            socketConnection.SendDataToSocket(
                ludoNumberEventManager.SignUpRequstData(),
                ludoNumbersAcknowledgementHandler.SignUpAcknowledged,
                "SIGNUP"
            );

        public void LeaveTable()
        {
            int gamePlayed = PlayerPrefs.GetInt("gamePlayed");
            int gameWon = PlayerPrefs.GetInt("gameWon");
            int gameLoss = PlayerPrefs.GetInt("gameLoss");
            DashBoardManagerOffline.instance.UpdateGameStatistics(
                gamePlayed,
                gameWon,
                gameLoss + 1
            );
            OnClickExit(); //socketConnection.SendDataToSocket(ludoNumberEventManager.SendLeaveTable(), ludoNumbersAcknowledgementHandler.LevaeTable, LudoNumberEventList.LEAVE_TABLE.ToString());
        }

        public void OnClickExit()
        {
            if (!gameRunOnSdk)
            {
#if UNITY_EDITOR
                UnityEditor.EditorApplication.isPlaying = false;
#endif
#if UNITY_ANDROID
                Application.Quit();
#endif
            }
            else
            {
                socketNumberEventReceiver.ResetGame();
                dashBoardManager.ResetGame();
                socketNumberEventReceiver.ludoNumberGsNew.ResetGame();
                ludoNumbersAcknowledgementHandler.ResetGame();
                SceneManager.LoadScene("LudoClassicModeOffline");
            }
        }

        public void Reconnect() =>
            socketConnection.SendDataToSocket(
                ludoNumberEventManager.Reconnect(),
                ludoNumbersAcknowledgementHandler.ReconnectAcknowledgement,
                "RECONNECTION"
            );

        public void DiceAnimation()
        {
            Debug.Log("Call dice Animation function = > DiceAnimation || Game Manager");
            socketNumberEventReceiver.DiceAnimationStart();
        }
    }

    public enum GameState
    {
        idel,
        run,
        playing,
    }
}
