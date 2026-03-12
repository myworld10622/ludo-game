using UnityEditor;
using UnityEngine;
using UnityEngine.U2D;

public class SpriteAtlasFixer : MonoBehaviour
{
    [MenuItem("Tools/Fix Sprite Atlas Packer")]
    public static void FixSpriteAtlasPacker()
    {
        string[] atlasGUIDs = AssetDatabase.FindAssets("t:SpriteAtlas");
        foreach (string guid in atlasGUIDs)
        {
            string path = AssetDatabase.GUIDToAssetPath(guid);
            SpriteAtlas atlas = AssetDatabase.LoadAssetAtPath<SpriteAtlas>(path);
            if (atlas != null)
            {
                SerializedObject serializedAtlas = new SerializedObject(atlas);
                SerializedProperty packerPolicy = serializedAtlas.FindProperty("m_PackerPolicy");
                if (packerPolicy != null)
                {
                    packerPolicy.stringValue = ""; // Set Scriptable Packer to None
                    serializedAtlas.ApplyModifiedProperties();
                    Debug.Log($"Fixed Sprite Atlas: {path}");
                }
            }
        }
        AssetDatabase.SaveAssets();
    }
}
