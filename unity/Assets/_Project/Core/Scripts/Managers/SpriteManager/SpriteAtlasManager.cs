using System.Collections.Generic;
using System.Linq;
using EasyButtons;
using UnityEditor;
using UnityEngine;
using UnityEngine.U2D;
using UnityEngine.UI;

public class SpriteAtlasManager : MonoBehaviour
{
    public SpriteAtlas spriteAtlas;
    public Image[] targetImages; // UI Images
    public SpriteRenderer[] targetRenderers; // SpriteRenderers
    public Sprite[] sprite;

    private Dictionary<string, Sprite> spriteCache = new Dictionary<string, Sprite>();

    async void OnEnable()
    {
        if (sprite == null || sprite.Length == 0)
        {
            return;
        }

        await SpriteAtlasUtil.Assign(
            targetImages,
            targetRenderers,
            sprite,
            spriteCache,
            spriteAtlas
        );
    }

    void OnDestroy()
    {
        spriteCache.Clear();
        SpriteAtlasUtil.ClearCache();
    }

    public void UpdateTargetImages()
    {
        // Clear the current targetImages and targetRenderers arrays
        targetImages = new Image[0];
        targetRenderers = new SpriteRenderer[0];

        // Find all Image components and SpriteRenderer components in the scene
        var imagesInScene = FindObjectsOfType<UnityEngine.UI.Image>();

        // Initialize the arrays with the correct lengths
        targetImages = new Image[imagesInScene.Length];

        // Add Image components to targetImages array
        List<Image> validImages = new List<Image>();

        // Add Image components to the list if they have a sprite assigned
        foreach (var image in imagesInScene)
        {
            if (image.sprite != null)
            {
                validImages.Add(image);
            }
        }

        // Convert the list to an array and assign it to targetImages
        targetImages = validImages.ToArray();
    }
    
    [Button]
    public void AssignAllImages()
    {
        /*   List<Sprite> SpriteList = new List<Sprite>();

          foreach (Image root in targetImages)
          {
              if (root.sprite != null)
                  SpriteList.Add(root.sprite);
          }
          foreach (var root in targetRenderers)
          {
              if (root.sprite != null)
                  SpriteList.Add(root.sprite);
          }
          sprite = SpriteList.ToArray(); */

        // spriteAtlas.Add(sprite);
        // AssetDatabase.SaveAssets();
        Debug.Log("Sprites added to SpriteAtlas successfully!");
    }

    [Button]
    public void AddImagesInSpriteAtlas()
    {
        UpdateTargetImages();
    }

    [Button]
    public void RemoveImages()
    {
        foreach (var img in targetImages)
        {
            img.sprite = null;
        }
    }

    [Button]
    public void ApplyImages()
    {
        for (int i = 0; i < targetImages.Length; i++)
        {
            targetImages[i].sprite = sprite[i];
        }
    }

    [Button]
    public void AddSpritesInSpriteAtlas()
    {
        StoreSpritesInSequence();
    }

    [Button]
    public void AddSpritesInAtlas()
    {
        //AddSpritesToAtlas(sprite, targetRenderers);
    }

    public void StoreSpritesInSequence()
    {
        sprite = new Sprite[0];
        sprite = new Sprite[targetImages.Length + targetRenderers.Length];
        // Store the sprites in sequence
        for (int i = 0; i < targetImages.Length; i++)
        {
            sprite[i] = targetImages[i].sprite;
            targetImages[i].gameObject.name = sprite[i].name;
            Debug.Log($"Sprite at position {i}: {sprite[i].name}");
        }
        if (targetRenderers.Length != 0)
        {
            for (int i = 0; i < targetRenderers.Length; i++)
            {
                sprite[i] = targetRenderers[i].sprite;
                targetRenderers[i].gameObject.name = sprite[i].name;
            }
        }
    }

    // private void AddSpritesToAtlas(Sprite[] sprites, SpriteRenderer[] renderer)
    // {
    //     if (spriteAtlas == null)
    //     {
    //         Debug.LogError("Sprite Atlas not assigned.");
    //         return;
    //     }

    //     // Add each sprite from the sequence to the atlas
    //     foreach (var sprite in sprites)
    //     {
    //         // This will add the sprite to the atlas if it isn't already there
    //         spriteAtlas.Add(new[] { sprite });
    //     }

    //     foreach (var sprite in renderer)
    //     {
    //         // This will add the sprite to the atlas if it isn't already there
    //         spriteAtlas.Add(new[] { sprite.sprite });
    //     }

    //     // Mark the sprite atlas as dirty so changes are saved
    //     EditorUtility.SetDirty(spriteAtlas);
    // }
}
