using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using DG.Tweening;
using TMPro;
using UnityEngine;
using UnityEngine.TerrainUtils;
using UnityEngine.UI;

public class GameBetUtil : MonoBehaviour
{
    public static Vector3 initialScale = new Vector3(0.4f, 0.4f, 0.4f);
    public static Vector3 targetScale = new Vector3(0.3f, 0.3f, 0.3f);
    private static bool isBetting = false; // Flag to control the betting loop
    public static int coinamount = 0,
        currentValue;

    //StartCoroutine(GameBetUtil.StartBet(coins, colliderList, profiles, onlineUser, dummyObjects, isAI: true));  // For AI bet
    //StartCoroutine(GameBetUtil.StartBet(coins, colliderList, profiles, onlineUser, dummyObjects, isAI: false)); // For Online bet
    //GameBetUtil.StopBet();
    public static IEnumerator StartBet(
        List<GameObject> coins,
        List<Collider2D> colliderList,
        List<GameObject> profiles,
        Animator onlineUser, // Can be null if it's an AI bet
        List<GameObject> dummyObjects,
        bool isAI = true
    ) // Add a flag to switch between AI and Online
    {
        Debug.Log(isAI ? "AIBet started" : "OnlineBet started");

        isBetting = true; // Set the flag to true to start the loop

        while (isBetting) // Keep running while isBetting is true
        {
            if (isAI)
            {
                // AI Bet Logic
                int coinnum = UnityEngine.Random.Range(0, coins.Count);
                var randomCollider = colliderList[UnityEngine.Random.Range(0, colliderList.Count)];
                var profile = profiles[UnityEngine.Random.Range(0, profiles.Count)];

                // Get coin from pool
                var poolManager = coins[coinnum].GetComponent<ObjectPoolUtil>();
                var coin = poolManager.GetObject();

                // Set parent and position
                coin.transform.SetParent(profile.transform.GetChild(1));
                coin.transform.localPosition = Vector3.zero;
                dummyObjects.Add(coin);

                // Animate coin
                coin.transform.SetParent(randomCollider.transform);

                coin.transform.localScale = initialScale;
                coin.transform.DOLocalMove(GetRandomPositionInCollider(randomCollider), 0.8f)
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(targetScale, 0.2f);
                    });

                //playcoin audio
                AudioManager._instance?.PlayCoinDrop();
            }
            else
            {
                // Online Bet Logic
                if (onlineUser != null)
                {
                    onlineUser.enabled = true;

                    var randomCollider = colliderList[
                        UnityEngine.Random.Range(0, colliderList.Count)
                    ];
                    var poolManager = coins[UnityEngine.Random.Range(0, coins.Count)]
                        .GetComponent<ObjectPoolUtil>();
                    var coin = poolManager.GetObject();

                    coin.transform.SetParent(onlineUser.transform);
                    coin.transform.localPosition = Vector3.zero;

                    dummyObjects.Add(coin);
                    coin.transform.SetParent(randomCollider.transform);
                    coin.transform.localScale = initialScale;
                    coin.transform.DOLocalMove(GetRandomPositionInCollider(randomCollider), 0.8f)
                        .OnComplete(() =>
                        {
                            coin.transform.DOScale(targetScale, 0.2f);
                        });

                    //playcoinaudio
                    AudioManager._instance?.PlayCoinDrop();
                }
            }
            //yield return new WaitForSeconds(.2f);
            yield return new WaitForSeconds(UnityEngine.Random.Range(0.2f, 0.6f)); // Wait for random time between coin drops
        }

        Debug.Log(isAI ? "AIBet finished" : "OnlineBet finished");
    }

    public static IEnumerator StartBet(
        List<GameObject> coins,
        List<Collider2D> colliderList,
        List<GameObject> profiles,
        Animator onlineUser, // Can be null if it's an AI bet
        List<GameObject> dummyObjects,
        List<TextMeshProUGUI> colliderTexts,
        bool isAI = true
    ) // Add a flag to switch between AI and Online
    {
        Debug.Log(isAI ? "AIBet started" : "OnlineBet started");

        isBetting = true; // Set the flag to true to start the loop

        while (isBetting) // Keep running while isBetting is true
        {
            if (isAI)
            {
                // AI Bet Logic
                int coinnum = UnityEngine.Random.Range(0, coins.Count);

                int colliderint = UnityEngine.Random.Range(0, colliderList.Count);
                var randomCollider = colliderList[colliderint];
                var profile = profiles[UnityEngine.Random.Range(0, profiles.Count)];
                if (!int.TryParse(colliderTexts[colliderint].text, out currentValue))
                {
                    currentValue = 0;
                }

                int coinamount = GetIntegerFromString(coins[coinnum].name);
                int newAmount = currentValue + coinamount;

                colliderTexts[colliderint].text = newAmount.ToString();

                // Get coin from pool
                var poolManager = coins[coinnum].GetComponent<ObjectPoolUtil>();
                var coin = poolManager.GetObject();

                // Set parent and position
                coin.transform.SetParent(profile.transform.GetChild(1));
                coin.transform.localPosition = Vector3.zero;
                dummyObjects.Add(coin);

                // Animate coin
                coin.transform.SetParent(randomCollider.transform);

                coin.transform.localScale = initialScale;
                coin.transform.DOLocalMove(GetRandomPositionInCollider(randomCollider), 0.8f)
                    .OnComplete(() =>
                    {
                        coin.transform.DOScale(targetScale, 0.2f);
                    });

                //playcoin audio
                AudioManager._instance?.PlayCoinDrop();
            }
            else
            {
                // Online Bet Logic
                if (onlineUser != null)
                {
                    onlineUser.enabled = true;
                    int colliderint = UnityEngine.Random.Range(0, colliderList.Count);
                    var randomCollider = colliderList[colliderint];
                    int coinnum = UnityEngine.Random.Range(0, coins.Count);
                    var poolManager = coins[coinnum].GetComponent<ObjectPoolUtil>();
                    var coin = poolManager.GetObject();
                    if (!int.TryParse(colliderTexts[colliderint].text, out currentValue))
                    {
                        currentValue = 0;
                    }

                    int coinamount = GetIntegerFromString(coins[coinnum].name);
                    int newAmount = currentValue + coinamount;

                    colliderTexts[colliderint].text = newAmount.ToString();

                    coin.transform.SetParent(onlineUser.transform);
                    coin.transform.localPosition = Vector3.zero;

                    dummyObjects.Add(coin);
                    coin.transform.SetParent(randomCollider.transform);
                    coin.transform.localScale = initialScale;
                    coin.transform.DOLocalMove(GetRandomPositionInCollider(randomCollider), 0.8f)
                        .OnComplete(() =>
                        {
                            coin.transform.DOScale(targetScale, 0.2f);
                        });

                    //playcoinaudio
                    AudioManager._instance?.PlayCoinDrop();
                }
            }
            //yield return new WaitForSeconds(.2f);
            yield return new WaitForSeconds(UnityEngine.Random.Range(0.2f, 0.6f)); // Wait for random time between coin drops
        }

        Debug.Log(isAI ? "AIBet finished" : "OnlineBet finished");
    }

    public static int GetIntegerFromString(string input)
    {
        string numbers = new string(input.Where(char.IsDigit).ToArray()); // Extract digits

        if (string.IsNullOrEmpty(numbers))
            return 0; // Return 0 if no digits are found

        if (int.TryParse(numbers, out int result))
            return result;

        return int.MaxValue; // Prevent overflow by returning the max int value
    }

    public static void StopBet()
    {
        isBetting = false; // Set the flag to false to stop the loop
    }

    public static Vector3 GetRandomPositionInCollider(Collider2D collider2D)
    {
        // Get the bounds of the BoxCollider2D
        Bounds bounds = collider2D.bounds;

        // Generate random X and Y positions within the BoxCollider2D bounds
        float randomX = UnityEngine.Random.Range(bounds.min.x, bounds.max.x);
        float randomY = UnityEngine.Random.Range(bounds.min.y, bounds.max.y);

        // Convert world position to local position relative to the canvas
        Vector2 localPoint;
        RectTransformUtility.ScreenPointToLocalPointInRectangle(
            collider2D.transform.GetComponent<RectTransform>(),
            new Vector2(randomX, randomY),
            null,
            out localPoint
        );

        return localPoint; // Return local position in Canvas space
    }

    public static void MoveAllCoinsIntoTop(
        List<GameObject> dummyObjects,
        Transform firstMovePosition,
        List<Collider2D> colliderList,
        string winning,
        int andarAmountInt,
        int baharAmountInt,
        GameObject userProfile,
        List<GameObject> profiles,
        Action updateData
    )
    {
        // Play the coin distribution audio

        // Merge objects into dummy list and clear originals
        int userWinCoins = 0;

        // Destroy half of the coins and remove them from the list
        for (int i = 0; i < dummyObjects.Count / 2; i++)
        {
            Destroy(dummyObjects[i]);
            dummyObjects.RemoveAt(i);
        }

        //AudioManager._instance.PlayMultipleCoinDrop();
        bool hasplayedsound = false;
        bool has_coin_move_table = false;
        // Move remaining coins to the first move position
        dummyObjects.ForEach(x => x.transform.SetParent(firstMovePosition));
        dummyObjects.ForEach(x =>
            x.transform.DOLocalMove(Vector3.zero, 0.6f)
                .OnComplete(() =>
                {
                    if (!hasplayedsound)
                    {
                        hasplayedsound = true;
                        AudioManager._instance?.PlayMultipleCoinDrop();
                    }
                    //Collider2D randomCollider = winning == "0" ? colliderList[0] : colliderList[1];
                    //Debug.LogError("Winning::" + winning);
                    Collider2D randomCollider = new Collider2D();
                    randomCollider = colliderList[int.Parse(winning)];
                    /*   randomCollider = winning switch
                      {
                          "0" => colliderList[0],
                          "1" => colliderList[1],
                          "2" => colliderList[2],
                          "3" => colliderList[3],
                          _ => colliderList[^1],//mean last element
                      }; */
                    x.transform.SetParent(randomCollider.transform);
                    //AudioManager._instance.PlayMultipleCoinDrop();
                    x.transform.DOLocalMove(GetRandomPositionInCollider(randomCollider), 0.6f)
                        .OnComplete(() =>
                        {
                            if (!has_coin_move_table)
                            {
                                has_coin_move_table = true;
                                AudioManager._instance?.PlayMultipleCoinDrop();
                            }
                            bool isUserWin = (baharAmountInt > 0 || andarAmountInt > 0);
                            //     bool isUserWin =
                            //    (winning == "0" && andarAmountInt > 0)
                            //    || (winning == "1" && baharAmountInt > 0);
                            if (isUserWin && userWinCoins < 10)
                            {
                                userWinCoins++;
                                MoveCoinToUserProfile(x, userProfile, dummyObjects, updateData);
                            }
                            else
                            {
                                MoveCoinToRandomProfile(x, profiles, dummyObjects, updateData);
                            }
                        });
                })
        );
    }

    // Helper to move coin to user profile
    private static void MoveCoinToUserProfile(
        GameObject coin,
        GameObject userProfile,
        List<GameObject> dummyObjects,
        Action updateData
    )
    {
        AudioManager._instance?.PlayMultipleCoinDrop();
        coin.transform.SetParent(
            userProfile.transform.GetChild(0).GetChild(0).GetChild(2).transform
        );
        coin.transform.DOLocalMove(Vector3.zero, 0.7f)
            .OnComplete(() =>
            {
                Destroy(coin);
                dummyObjects.Remove(coin);
                dummyObjects.RemoveAll(item => item == null);
                updateData?.Invoke();
            });
    }

    // Helper to move coin to random profile
    private static void MoveCoinToRandomProfile(
        GameObject coin,
        List<GameObject> profiles,
        List<GameObject> dummyObjects,
        Action updateData
    )
    {
        var randomProfile = profiles[UnityEngine.Random.Range(0, profiles.Count)];
        coin.transform.SetParent(randomProfile.transform.GetChild(1).transform);
        coin.transform.DOLocalMove(Vector3.zero, 0.7f)
            .OnComplete(() =>
            {
                Destroy(coin);
                dummyObjects.Remove(coin);
                dummyObjects.RemoveAll(item => item == null);
                updateData?.Invoke();
            });
    }

    #region GameCoinUpdate



    public static void OnButtonClickParticle(
        int buttonIndex,
        List<ParticleSystem> particleSystems,
        ref ParticleSystem currentParticle
    )
    {
        CommonUtil.CheckLog("Particle num " + buttonIndex);
        ParticleSystem newParticle = particleSystems[buttonIndex];
        if (currentParticle == newParticle && currentParticle.isPlaying)
        {
            currentParticle.Stop();
            currentParticle = null;
        }
        else
        {
            currentParticle?.Stop();
            newParticle?.Play();
            currentParticle = newParticle;
        }
    }

    public static void UpdateButtonInteractability(
        string walletAmountStr,
        List<Button> buttons,
        List<ParticleSystem> particleSystems,
        ref GameObject buttonClicked,
        ref ParticleSystem currentParticle,
        bool gameStart,
        Action<int> buttonClick,
        Action<GameObject> clickedButton,
        Action<int> coinInstantiate,
        ref string betAmount
    )
    {
        if (!float.TryParse(walletAmountStr, out float walletAmount))
        {
            Debug.LogWarning("Invalid wallet amount.");
            return;
        }

        if (!gameStart)
        {
            DisableAllButtons(buttons, particleSystems);
            return;
        }

        int buttonIndex = 0;
        foreach (var button in buttons)
        {
            if (float.TryParse(button.name, out float buttonValue))
            {
                button.interactable = walletAmount >= buttonValue;

                if (!button.interactable && buttonClicked.name == button.name)
                {
                    HandleButtonInteractivity(
                        buttons,
                        particleSystems,
                        ref buttonClicked,
                        ref currentParticle,
                        walletAmount,
                        buttonClick,
                        clickedButton,
                        coinInstantiate,
                        ref betAmount
                    );
                    break;
                }
            }

            buttonIndex++;
        }

        // Handle particle effects for the clicked button
        for (int i = 0; i < buttons.Count; i++)
        {
            if (buttons[i].gameObject == buttonClicked)
            {
                buttonClick(int.Parse(buttons[i].name));
                clickedButton(buttons[i].gameObject);
                if (coinInstantiate != null)
                    coinInstantiate(i);
                OnButtonClickParticle(i, particleSystems, ref currentParticle);

                if (i == 0 && walletAmount < 10)
                {
                    Debug.LogError("walletAmount" + walletAmount);
                    betAmount = "0";
                }
            }
        }
    }

    private static void DisableAllButtons(
        List<Button> buttons,
        List<ParticleSystem> particleSystems
    )
    {
        foreach (var button in buttons)
        {
            button.interactable = false;
        }

        foreach (var particle in particleSystems)
        {
            particle.Stop();
        }
    }

    private static void HandleButtonInteractivity(
        List<Button> buttons,
        List<ParticleSystem> particleSystems,
        ref GameObject buttonClicked,
        ref ParticleSystem currentParticle,
        float walletAmount,
        Action<int> buttonClick,
        Action<GameObject> clickedButton,
        Action<int> coinInstantiate,
        ref string betAmount
    )
    {
        StopAllParticles(particleSystems, ref currentParticle);

        //betAmount = "0";
        int lastIndex = FindLastInteractableButton(walletAmount, buttons);

        if (lastIndex >= 0)
        {
            buttonClick(int.Parse(buttons[lastIndex].name));
            clickedButton(buttons[lastIndex].gameObject);
            //coinInstantiate(lastIndex);
            OnButtonClickParticle(lastIndex, particleSystems, ref currentParticle);
        }
        else
        {
            ResetToFirstButton(
                buttons,
                particleSystems,
                buttonClick,
                clickedButton,
                coinInstantiate,
                ref betAmount
            );
        }
    }

    private static void StopAllParticles(
        List<ParticleSystem> particleSystems,
        ref ParticleSystem currentParticle
    )
    {
        currentParticle?.Stop();
        currentParticle = null;

        foreach (var particle in particleSystems)
        {
            particle.Stop();
        }
    }

    private static int FindLastInteractableButton(float walletAmount, List<Button> buttons)
    {
        for (int i = buttons.Count - 1; i >= 0; i--)
        {
            if (
                float.TryParse(buttons[i].name, out float buttonValue)
                && walletAmount >= buttonValue
            )
            {
                return i;
            }
        }

        return -1; // No valid button found
    }

    private static void ResetToFirstButton(
        List<Button> buttons,
        List<ParticleSystem> particleSystems,
        Action<int> buttonClick,
        Action<GameObject> clickedButton,
        Action<int> coinInstantiate,
        ref string betAmount
    )
    {
        buttonClick(0);
        clickedButton(buttons[0].gameObject);
        //coinInstantiate(0);
        //  OnButtonClickParticle(0, particleSystems, currentParticle);
        buttons[0].interactable = true;
        //  betAmount = "0";
    }

    #endregion
}
