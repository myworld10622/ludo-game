using System.Collections;
using System.Collections.Generic;
using System.IO;
using UnityEngine;
using UnityEngine.UI;
namespace LudoClassicOffline
{
    public class SetAvtarAndPriceOffline : MonoBehaviour
    {

        [SerializeField] Image avtarProfileImage;
        [SerializeField] Image avterRing;
        [SerializeField] Image buttonImage;
        [SerializeField] Text buttonText;
        [SerializeField] Button changeAvterbutton;
        [SerializeField] List<Sprite> avtarSpriteList;
        [SerializeField] Sprite free, paid;
        [SerializeField] Sprite greenRing, yellowRing;
        [SerializeField] bool isEnable;
        [SerializeField] int myNo;

        public DashBoardManagerOffline dashBoardManager;


        private void Start()
        {
            for (int i = 0; i < dashBoardManager.avatar.players.Count; i++)
            {
                if (i == myNo)
                    if (dashBoardManager.avatar.players[i].isActive)
                    {
                        isEnable = true;
                        avterRing.sprite = greenRing;
                        buttonImage.sprite = free;
                        buttonText.text = "PUR";
                    }
            }
        }

        public void SetAvtarData(int no, bool isFree, int price, bool isEnable)
        {
            avtarProfileImage.sprite = avtarSpriteList[no];
            if (isFree)
            {
                buttonImage.sprite = free;
                buttonText.text = "FREE";
                avterRing.sprite = greenRing;
            }
            else
            {
                buttonImage.sprite = paid;
                buttonText.text = price.ToString();
                avterRing.sprite = yellowRing;
            }
            if (isEnable)
                changeAvterbutton.interactable = true;
            else
                changeAvterbutton.interactable = false;

        }

        public void ClickOnButton(int no)
        {
            Debug.Log("Click On button");
            if (isEnable)
                dashBoardManager.UpdateProfilePic(no);
            else
            {
                Debug.Log("Click On button" + dashBoardManager.totalChipsStore);
                int tchips = PlayerPrefs.GetInt("Totalchips");
                if (tchips >= 500)
                {
                    Debug.Log("Click On button" + tchips);

                    isEnable = true;
                    dashBoardManager.UpdateProfilePic(no);
                    avterRing.sprite = greenRing;
                    buttonImage.sprite = free;
                    buttonText.text = "PUR";
                    int chips = PlayerPrefs.GetInt("Totalchips");
                    chips = chips - 500;
                    PlayerPrefs.SetInt("Totalchips", chips);
                    dashBoardManager.UpdateChips(chips);
                }
                else
                {
                    dashBoardManager.alertPopUpForBalance.SetActive(true);
                }
            }
        }
    }
}
