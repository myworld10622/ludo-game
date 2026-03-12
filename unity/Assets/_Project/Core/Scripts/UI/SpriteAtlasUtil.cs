using System.Collections.Generic;
using System.Threading.Tasks;
using UnityEngine;
using UnityEngine.U2D;
using UnityEngine.UI;

public static class SpriteAtlasUtil
{
    private static Dictionary<string, Sprite> cachedSprites = new Dictionary<string, Sprite>();

    public static Sprite LoadSprite(SpriteAtlas atlas, string spriteName)
    {
        if (atlas == null)
        {
            Debug.LogError("SpriteAtlas is null. Please provide a valid Sprite Atlas.");
            return null;
        }

        if (cachedSprites.TryGetValue(spriteName, out Sprite cachedSprite))
        {
            return cachedSprite;
        }

        Sprite sprite = atlas.GetSprite(spriteName);
        if (sprite != null)
        {
            cachedSprites[spriteName] = sprite;
            return sprite;
        }

        Debug.LogError($"Sprite '{spriteName}' not found in the provided Sprite Atlas.");
        return null;
    }

    public static void ClearCache()
    {
        cachedSprites.Clear();
        Debug.Log("SpriteAtlasUtil: Cache cleared.");
    }

    public static async Task Assign(
        Image[] targetImages,
        SpriteRenderer[] targetRenderers,
        Sprite[] spriteNames,
        Dictionary<string, Sprite> spriteCache,
        SpriteAtlas spriteAtlas
    )
    {
        await Task.Yield();

        if (
            (targetImages == null || targetImages.Length == 0)
            && (targetRenderers == null || targetRenderers.Length == 0)
        )
        {
            return;
        }

        if (spriteNames == null || spriteNames.Length == 0)
        {
            return;
        }

        if (spriteAtlas == null)
        {
            Debug.LogWarning("SpriteAtlasManager skipped because no SpriteAtlas is assigned.");
            return;
        }

        // Dictionary for Image components
        var imageDictionary = new Dictionary<string, List<Image>>();
        if (targetImages != null)
        {
            foreach (var img in targetImages)
            {
                if (img != null)
                {
                    if (!imageDictionary.ContainsKey(img.name))
                    {
                        imageDictionary[img.name] = new List<Image>();
                    }
                    imageDictionary[img.name].Add(img);
                }
                else
                {
                    Debug.LogWarning("Skipping null target image in SpriteAtlas assignment.");
                }
            }
        }

        // Dictionary for SpriteRenderers
        var rendererDictionary = new Dictionary<string, List<SpriteRenderer>>();
        if (targetRenderers != null)
        {
            foreach (var renderer in targetRenderers)
            {
                if (renderer != null)
                {
                    if (!rendererDictionary.ContainsKey(renderer.name))
                    {
                        rendererDictionary[renderer.name] = new List<SpriteRenderer>();
                    }
                    rendererDictionary[renderer.name].Add(renderer);
                }
                else
                {
                    Debug.LogWarning("Skipping null target renderer in SpriteAtlas assignment.");
                }
            }
        }

        for (int i = 0; i < spriteNames.Length; i++)
        {
            if (spriteNames[i] == null)
            {
                continue;
            }

            string spriteName = spriteNames[i].name;
            if (string.IsNullOrWhiteSpace(spriteName))
            {
                continue;
            }

            // Check for Images
            if (imageDictionary.TryGetValue(spriteName, out List<Image> targetImagesList))
            {
                AssignSpritesToTargets(targetImagesList, spriteName, spriteCache, spriteAtlas);
            }
            // Check for SpriteRenderers
            else if (
                rendererDictionary.TryGetValue(
                    spriteName,
                    out List<SpriteRenderer> targetRenderersList
                )
            )
            {
                AssignSpritesToTargets(targetRenderersList, spriteName, spriteCache, spriteAtlas);
            }
            else
            {
                Debug.LogWarning($"No matching targets found for sprite name: {spriteName}");
            }
        }
    }

    private static void AssignSpritesToTargets<T>(
        List<T> targetList,
        string spriteName,
        Dictionary<string, Sprite> spriteCache,
        SpriteAtlas spriteAtlas
    )
        where T : Component
    {
        if (!spriteCache.TryGetValue(spriteName, out Sprite loadedSprite))
        {
            loadedSprite = LoadSprite(spriteAtlas, spriteName);
            if (loadedSprite != null)
            {
                spriteCache[spriteName] = loadedSprite;
            }
            else
            {
                Debug.LogError($"Failed to load sprite: {spriteName}");
                return;
            }
        }

        foreach (var target in targetList)
        {
            if (target is Image image)
            {
                image.sprite = loadedSprite;
            }
            else if (target is SpriteRenderer renderer)
            {
                renderer.sprite = loadedSprite;
            }
        }
    }
}
