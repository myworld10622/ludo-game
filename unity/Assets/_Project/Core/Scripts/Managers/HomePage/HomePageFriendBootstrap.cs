using LudoClassicOffline;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

namespace AndroApps
{
    public class HomePageFriendBootstrap : MonoBehaviour
    {
        private const string BootstrapObjectName = "HomePageFriendBootstrap";

        [RuntimeInitializeOnLoadMethod(RuntimeInitializeLoadType.AfterSceneLoad)]
        private static void Register()
        {
            SceneManager.sceneLoaded -= HandleSceneLoaded;
            SceneManager.sceneLoaded += HandleSceneLoaded;
            HandleSceneLoaded(SceneManager.GetActiveScene(), LoadSceneMode.Single);
        }

        private static void HandleSceneLoaded(Scene scene, LoadSceneMode mode)
        {
            if (!scene.IsValid() || !string.Equals(scene.name, "HomePage", System.StringComparison.OrdinalIgnoreCase))
            {
                CleanupBootstrap();
                return;
            }

            GameObject bootstrap = GameObject.Find(BootstrapObjectName);
            if (bootstrap == null)
            {
                bootstrap = new GameObject(BootstrapObjectName);
            }

            LudoFriendApiService apiService = bootstrap.GetComponent<LudoFriendApiService>();
            if (apiService == null)
            {
                apiService = bootstrap.AddComponent<LudoFriendApiService>();
            }

            LudoFriendPanelController panelController = bootstrap.GetComponent<LudoFriendPanelController>();
            if (panelController == null)
            {
                panelController = bootstrap.AddComponent<LudoFriendPanelController>();
            }

            panelController.SetRoomActionAvailability(false);
            panelController.SetHomeShortcutAvailability(false);

            BindSceneShortcut(panelController);
        }

        private static void CleanupBootstrap()
        {
            GameObject bootstrap = GameObject.Find(BootstrapObjectName);
            if (bootstrap != null)
            {
                Object.Destroy(bootstrap);
            }
        }

        private static void BindSceneShortcut(LudoFriendPanelController panelController)
        {
            if (panelController == null)
            {
                return;
            }

            Transform homePageCanvas = FindSceneTransform("HomePageCanvas");
            Transform shortcut = FindChildTransform(homePageCanvas, "Friends-icon");
            if (shortcut == null)
            {
                shortcut = FindChildTransform(homePageCanvas, "friends-icon");
            }

            if (shortcut == null)
            {
                Transform shortcutBackground = FindChildTransform(homePageCanvas, "icon-bg (2)");
                if (shortcutBackground != null && shortcutBackground.childCount > 0)
                {
                    shortcut = shortcutBackground.GetChild(0);
                }
            }

            if (shortcut == null)
            {
                shortcut = FindChildTransform(homePageCanvas, "icon-bg (2)");
            }

            if (shortcut == null)
            {
                return;
            }

            Image image = shortcut.GetComponent<Image>();
            if (image != null)
            {
                image.enabled = true;
            }

            Button button = shortcut.GetComponent<Button>();
            if (button == null)
            {
                button = shortcut.gameObject.AddComponent<Button>();
            }

            button.onClick.RemoveAllListeners();
            button.onClick.AddListener(panelController.OpenHomePanelFromShortcut);
        }

        private static Transform FindSceneTransform(string objectName)
        {
            if (string.IsNullOrWhiteSpace(objectName))
            {
                return null;
            }

            Transform[] transforms = Resources.FindObjectsOfTypeAll<Transform>();
            for (int i = 0; i < transforms.Length; i++)
            {
                Transform current = transforms[i];
                if (current == null || !current.gameObject.scene.IsValid())
                {
                    continue;
                }

                if (string.Equals(current.name, objectName, System.StringComparison.OrdinalIgnoreCase))
                {
                    return current;
                }
            }

            return null;
        }

        private static Transform FindChildTransform(Transform root, string objectName)
        {
            if (root == null || string.IsNullOrWhiteSpace(objectName))
            {
                return null;
            }

            Transform[] transforms = root.GetComponentsInChildren<Transform>(true);
            for (int i = 0; i < transforms.Length; i++)
            {
                Transform current = transforms[i];
                if (current != null && string.Equals(current.name, objectName, System.StringComparison.OrdinalIgnoreCase))
                {
                    return current;
                }
            }

            return null;
        }
    }
}
