using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{

    public class LudoNumberWinnerDataOffline : MonoBehaviour
    {
        public GameObject twoPlayer;
        public GameObject fourPlayer;
        public Sprite greenSprite;
        public Sprite blueSprite;
        public List<LudoNumberResultPlayerDataOffline> twoPlayerDataList = new List<LudoNumberResultPlayerDataOffline>();
        public List<LudoNumberResultPlayerDataOffline> fourPlayerDataList = new List<LudoNumberResultPlayerDataOffline>();
        public SocketNumberEventReceiverOffline socketNumberEventReceiver;
        public Image scoreBordWinImage;
        public Sprite win, score;

        public void SetWinnerData(BattleFinishData data)
        {
            int maxPlayerCount = socketNumberEventReceiver?.joinTableResponse?.data?.maxPlayerCount ?? 2;
            int playerCount = data?.payload?.players?.Count ?? 0;
            bool useFourPlayerLayout = maxPlayerCount > 2;

            Debug.Log("SetWinnerData" + playerCount);
            twoPlayer.SetActive(!useFourPlayerLayout);
            fourPlayer.SetActive(useFourPlayerLayout);

            List<LudoNumberResultPlayerDataOffline> rows = useFourPlayerLayout ? fourPlayerDataList : twoPlayerDataList;
            foreach (LudoNumberResultPlayerDataOffline row in rows)
            {
                if (row == null) continue;
                row.userName.text = string.Empty;
                row.score.text = "0";
                row.winAmount.text = "₹0";
                row.crown.SetActive(false);
                row.boxImage.GetComponent<Image>().sprite = blueSprite;
                row.gameObject.SetActive(false);
            }

            for (int i = 0; i < playerCount && i < rows.Count; i++)
            {
                LudoNumberResultPlayerDataOffline row = rows[i];
                AvtarData player = data.payload.players[i];
                row.gameObject.SetActive(true);
                row.userName.text = player.username;
                row.score.text = player.score.ToString();
                row.winAmount.text = "₹" + player.winAmount.ToString();
                row.setProfile(player.avatar);

                if (player.winType == "win")
                {
                    row.boxImage.GetComponent<Image>().sprite = greenSprite;
                    row.crown.SetActive(true);
                }
                else if (player.winType == "loss" || player.winType == "lost")
                {
                    row.boxImage.GetComponent<Image>().sprite = blueSprite;
                    row.crown.SetActive(false);
                }
                else if (player.winType == "tie")
                {
                    row.boxImage.GetComponent<Image>().sprite = greenSprite;
                    row.crown.SetActive(false);
                }
            }
        }
    }
}
