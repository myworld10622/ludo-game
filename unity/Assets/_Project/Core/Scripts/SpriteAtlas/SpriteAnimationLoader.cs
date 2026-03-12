using System.Collections;
using System.Collections.Generic;
using System.Net.Mime;
using UnityEngine;
using UnityEngine.U2D;
using UnityEngine.UI;

public class SpriteAnimationLoader : MonoBehaviour
{
    public SpriteAtlas spriteAtlas; // Assign this in the Inspector
    public string spritePrefix = "God of war_"; // Prefix for sprites
    public int startIndex = 0;
    public int endIndex = 47;
    public float frameRate = 0f; // Time per frame

    private List<Sprite> animationFrames = new List<Sprite>();
    private Image spriteRenderer;
    private int currentFrame = 0;

    void Start()
    {
        spriteRenderer = GetComponent<Image>();

        if (spriteAtlas == null)
        {
            Debug.LogError("Sprite Atlas is not assigned! Assign it in the Inspector.");
            return;
        }

        // Load sprites dynamically based on naming format
        for (int i = startIndex; i <= endIndex; i++)
        {
            string spriteName = $"{spritePrefix}{i:D5}"; // Example: "God of war_00000"
            Sprite sprite = spriteAtlas.GetSprite(spriteName);
            if (sprite != null)
            {
                animationFrames.Add(sprite);
            }
            else
            {
                Debug.LogWarning($"Sprite '{spriteName}' not found in Sprite Atlas!");
            }
        }

        // Start animation (this will run indefinitely)
        if (animationFrames.Count > 0)
        {
            InvokeRepeating(nameof(PlayAnimation), 0f, frameRate);
        }
        else
        {
            Debug.LogError("No valid sprites found! Check naming in the Sprite Atlas.");
        }
    }

    void PlayAnimation()
    {
        spriteRenderer.sprite = animationFrames[currentFrame];
        currentFrame = (currentFrame + 1) % animationFrames.Count; // Loop back after the last frame
    }
}
