using UnityEditor;
using UnityEngine;
using UnityEngine.UI;

public class ImageSpriteAssigner : EditorWindow
{
    [MenuItem("Tools/Assign Sprites to Images and SpriteRenderers")]
    public static void AssignSpritesToImagesAndSpriteRenderers()
    {
        // Start from the root GameObjects in the scene
        GameObject[] rootObjects = UnityEngine.SceneManagement.SceneManager.GetActiveScene().GetRootGameObjects();

        int updatedCount = 0;

        // Traverse the hierarchy and check for Image and SpriteRenderer components
        foreach (GameObject root in rootObjects)
        {
            updatedCount += ProcessGameObjectHierarchy(root);
        }

        Debug.Log($"Updated {updatedCount} components where GameObject names matched sprite names.");
    }

    private static int ProcessGameObjectHierarchy(GameObject obj)
    {
        int count = 0;

        // Check if the GameObject has an Image component
        Image imageComponent = obj.GetComponent<Image>();
        if (imageComponent != null && imageComponent.sprite != null)
        {
            // Update the GameObject's name to match the sprite name
            obj.name = imageComponent.sprite.name;
            count++;
        }

        // Check if the GameObject has a SpriteRenderer component
        SpriteRenderer spriteRenderer = obj.GetComponent<SpriteRenderer>();
        if (spriteRenderer != null && spriteRenderer.sprite != null)
        {
            // Update the GameObject's name to match the sprite name
            obj.name = spriteRenderer.sprite.name;
            count++;
        }

        // Recursively process all child objects, including inactive ones
        foreach (Transform child in obj.transform)
        {
            count += ProcessGameObjectHierarchy(child.gameObject);
        }

        return count;
    }
}
