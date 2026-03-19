using System.Collections;
using System.Collections.Generic;
using System.Threading.Tasks;
using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class DailyRewards : MonoBehaviour
{
    public Transform dailyrewardpanel,
        dailyrewadcontent;
    public List<GameObject> dailyrewardlist;
    public Button collect;
    private WelcomBonusRoot bonus;
    public Profile profile_wallet;

    private static bool rewardsShown = false;

    async void Awake()
    {
        if (!rewardsShown)
        {
            rewardsShown = true; // Mark rewards as shown
            await ShowRewards();
        }
    }

    public async void DailyRewardButton()
    {
        await ShowRewards(true);
    }

    public async Task ShowRewards(bool click = false)
    {
        if (APIManager.Instance == null)
        {
            Debug.LogWarning("DailyRewards skipped because APIManager.Instance is null.");
            return;
        }

        string Url = Configuration.Url + Configuration.Welcomebonus;
        Debug.Log("RES_Check + API-Call + ShowRewards");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        bonus = new WelcomBonusRoot();
        bonus = await APIManager.Instance.Post<WelcomBonusRoot>(Url, formData);
        if (bonus == null)
        {
            Debug.LogWarning("DailyRewards response was null.");
            return;
        }

        if (bonus.welcome_bonus == null)
        {
            Debug.Log("DailyRewards response did not include welcome_bonus data.");
            return;
        }

        Debug.Log("bonus.collected_days" + bonus.collected_days);
        Debug.Log("bonus.welcome_bonus.Count" + bonus.welcome_bonus.Count);
        if (bonus.collected_days <= bonus.welcome_bonus.Count)
        {
            Debug.Log("RES_check + Welcome Count " + bonus.welcome_bonus.Count);
            if (
                !click
                && (
                    bonus.today_collected != "0"
                    || bonus.welcome_bonus.Count == bonus.collected_days
                )
            )
            {
                return;
            }
            else
            {
                int rewardSlots = dailyrewardlist != null ? dailyrewardlist.Count : 0;
                if (rewardSlots == 0)
                {
                    Debug.LogWarning("DailyRewards has no reward slot objects assigned.");
                    return;
                }

                for (int i = 0; i < bonus.welcome_bonus.Count && i < rewardSlots; i++)
                {
                    if (dailyrewardlist[i] == null)
                    {
                        continue;
                    }

                    dailyrewardlist[i].transform.GetChild(1).GetComponent<TextMeshProUGUI>().text =
                        bonus.welcome_bonus[i].coin;
                    if ((i + 1) <= bonus.collected_days)
                    {
                        dailyrewardlist[i]
                            .transform.GetChild(0)
                            .GetChild(0)
                            .gameObject.SetActive(true);
                    }
                }
            }
            if (dailyrewardpanel != null)
            {
                dailyrewardpanel.gameObject.SetActive(false);
            }
            Debug.Log("RES_check + Open daily rewards");
        }
        if (dailyrewardpanel != null)
        {
            PopUpUtil.ButtonClick(dailyrewardpanel.gameObject);
        }
    }

    public async void Collect()
    {
        await collectRewards();
    }

    public async Task collectRewards()
    {
        string Url = Configuration.Url + Configuration.Collect_welcome_bonus;
        Debug.Log("RES_Check + API-Call + profile");

        var formData = new Dictionary<string, string>
        {
            { "user_id", Configuration.GetId() },
            { "token", Configuration.GetToken() },
        };
        messageprint message = new messageprint();
        message = await APIManager.Instance.Post<messageprint>(Url, formData);
        if (LoaderUtil.instance != null)
        {
            LoaderUtil.instance.ShowToast(message.message);
        }

        Profile profile = GetComponent<Profile>();
        if (profile != null)
        {
            profile.UpdateWallet();
        }

        if (dailyrewardpanel != null)
        {
            PopUpUtil.ButtonCancel(dailyrewardpanel.gameObject);
        }
    }
}
