using System.Collections;
using System.Collections.Generic;
using DG.Tweening;
using UnityEngine;

public static class CardUtil
{
    /// <summary>
    /// Sets the card's position, rotation, and scale to match a reference transform.
    /// </summary>
    public static void SetCardState(Transform card, Transform reference)
    {
        card.position = reference.position;
        card.rotation = reference.rotation;
        card.localScale = reference.localScale;
    }

    /// <summary>
    /// Animates a card to a target position, rotation, and scale using DoTween.
    /// </summary>
    public static IEnumerator AnimateCard(
        Transform card,
        Transform endPosition,
        float duration = 0.2f
    )
    {
        Sequence cardSequence = DOTween.Sequence();

        cardSequence
            .Join(card.DOMove(endPosition.position, duration))
            .Join(card.DORotateQuaternion(endPosition.rotation, duration))
            .Join(card.DOScale(endPosition.localScale, duration));

        yield return cardSequence.WaitForCompletion();
    }

    public static IEnumerator AnimateFinalCard(
        Transform card,
        Transform endPosition,
        float duration = 0.2f,
        MonoBehaviour coroutineStarter = null,
        IEnumerator nextCoroutine = null
    )
    {
        Sequence cardSequence = DOTween.Sequence();

        cardSequence
            .Join(card.DOMove(endPosition.position, duration))
            .Join(card.DORotateQuaternion(endPosition.rotation, duration))
            .Join(card.DOScale(endPosition.localScale, duration))
            .OnComplete(() => coroutineStarter.StartCoroutine(nextCoroutine));

        yield return cardSequence.WaitForCompletion();
    }

    public static IEnumerator AnimateFlipCard(
        Transform card,
        SpriteRenderer spriteRenderer,
        Sprite newSprite,
        float duration,
        float scale,
        float rotation
    )
    {
        float halfDuration = duration / 2f;
        PlayCardFlipSound();
        // Rotate to 90 degrees (halfway point) in half the duration
        yield return card.DORotate(new Vector3(0, 90, 0), halfDuration, RotateMode.FastBeyond360)
            .SetEase(Ease.Linear)
            .OnComplete(() =>
            {
                CommonUtil.CheckLog("Start changing sprites");
                // Change the sprite when halfway
                spriteRenderer.sprite = newSprite;
            })
            .WaitForCompletion();

        // Rotate from 90 to 180 degrees in the remaining duration
        yield return card.DORotate(
                new Vector3(0, rotation, 0),
                halfDuration,
                RotateMode.FastBeyond360
            )
            .SetEase(Ease.Linear)
            .WaitForCompletion();

        Vector3 newScale = card.localScale;
        newScale.x = scale;
        card.localScale = newScale;
    }

    public static IEnumerator AnimateFlipBackCard(
        Transform card,
        SpriteRenderer spriteRenderer,
        Sprite newSprite,
        float duration
    )
    {
        float halfDuration = duration / 2f;
        PlayCardFlipSound();
        // Rotate to 90 degrees (halfway point) in half the duration
        yield return card.DORotate(new Vector3(0, 90, 0), halfDuration, RotateMode.FastBeyond360)
            .SetEase(Ease.Linear)
            .OnComplete(() =>
            {
                CommonUtil.CheckLog("Start changing sprites");
                // Change the sprite when halfway
                spriteRenderer.sprite = newSprite;
            })
            .WaitForCompletion();

        // Rotate from 90 to 180 degrees in the remaining duration
        yield return card.DORotate(new Vector3(0, 180, 0), halfDuration, RotateMode.FastBeyond360)
            .SetEase(Ease.Linear)
            .WaitForCompletion();
    }

    /// <summary>
    /// Plays the card flip sound using the AudioManager.
    /// </summary>
    public static void PlayCardFlipSound()
    {
        if (AudioManager._instance != null)
        {
            AudioManager._instance.PlayCardFlipSound();
        }
    }

    /// <summary>
    /// Moves all cards from a start position to their respective end positions with animations.
    /// </summary>
    public static IEnumerator MoveAllCards(
        List<Transform> cards,
        Transform[] endPositions,
        Transform startPosition,
        MonoBehaviour coroutineStarter = null,
        IEnumerator nextCoroutine = null
    )
    {
        for (int i = 0; i < cards.Count; i++)
        {
            Transform card = cards[i];

            // Set initial state to match the start position
            SetCardState(card, startPosition);
            card.gameObject.SetActive(true);

            // Play card flip sound
            PlayCardFlipSound();

            if (i < cards.Count - 1)
            {
                // Animate the card to its end position
                yield return AnimateCard(card, endPositions[i], 0.2f);
            }
            else
            {
                yield return AnimateFinalCard(
                    card,
                    endPositions[i],
                    0.2f,
                    coroutineStarter,
                    nextCoroutine
                );
            }
        }
    }
}
