using TMPro;
using UnityEngine;
using UnityEngine.UI;

public class AddressableDownload : MonoBehaviour
{
    public Slider progressBar;
    public GameObject panel, prefab;
    public TextMeshProUGUI progress_text;
    public GameSelection select;

//     // private Dictionary<string, string> sceneDictionary = new Dictionary<string, string>
//     // {
//     //     { "point", "Point Rummy" },
//     //     { "pool", "Pool Rummy" },
//     //     { "deal", "Deal Rummy" },
//     //     { "teenpatti", "Teen Patti" },
//     // };

//     void Start()
//     {
//         //progress_text.color = Color.white;
//     }

//     private void LoadDynamic(string scene)
//     {
//         if (progressBar != null)
//         {
//             panel.gameObject.SetActive(false); // Hide progress UI
//         }
//         if (scene == "point")
//         {
//             select.loaddynamicscenebyname("PointTable.unity");
//         }
//         else if (scene == "pool")
//         {
//             select.loaddynamicscenebyname("Join_Table(Pool).unity");
//         }
//         else if (scene == "deal")
//         {
//             select.loaddynamicscenebyname("Join_Table(Deal).unity");
//         }
//         else if (scene == "teenpatti")
//         {
//             Debug.Log("teenpatti Calling when downloading TeenPatti_JoinTable.unity");
//             //select.loaddynamicscenebyname("TeenPatti_GamePlay.unity");
//             //select.loaddynamicscenebyname("TeenPatti_JoinTable.unity");
//         }
//         else
//             select.loaddynamicscenebyname(scene);
//     }

//     public void CheckAndDownload(string scenename)
//     {
//         GameObject clickedObject = EventSystem.current.currentSelectedGameObject;
//         clickedObject.GetComponent<LoaderScript>().CheckAndDownload(scenename);
//         //StartCoroutine(ManageSceneLoading(scenename, clickedObject));
//     }

//     IEnumerator ManageSceneLoading(string scenename, GameObject clickedObject)
//     {
//         bool isPackedTogetherDownloaded = false;
//         bool isSceneDownloaded = false;

//         yield return StartCoroutine(
//             CheckAndDownloadScene(
//                 scenename,
//                 result => isPackedTogetherDownloaded = result,
//                 clickedObject
//             )
//         );

//         if (isPackedTogetherDownloaded)
//         {
//             Debug.Log("Loading SceneA...");
//             CommonUtil.ShowToast($"{scenename} Download Successfuly...");
//             IsOnlyone = false;
//             panel.SetActive(false);
//             //LoadDynamic(scenename);
//         }
//     }

//     IEnumerator CheckAndDownloadScene(
//         string sceneadress,
//         System.Action<bool> callback,
//         GameObject clickedObject
//     )
//     {
//         var checkSizeHandle = Addressables.GetDownloadSizeAsync(sceneadress);
//         yield return checkSizeHandle;

//         if (checkSizeHandle.Result > 0)
//         {
//             Debug.Log(
//                 $"Group {sceneadress} needs to be downloaded. Size: {checkSizeHandle.Result} bytes."
//             );
//             yield return StartCoroutine(DownloadScene(sceneadress, clickedObject));
//             callback(true);
//         }
//         else
//         {
//             Debug.Log($"Group {sceneadress} is already downloaded.");
//             LoadDynamic(sceneadress);
//             callback(true);
//         }
//     }

//     IEnumerator DownloadScene(string sceneadress, GameObject clickedObject)
//     {
//         // Step 1: Get Download Size
//         AsyncOperationHandle<long> sizeCheck = Addressables.GetDownloadSizeAsync(sceneadress);
//         yield return sizeCheck;

//         if (sizeCheck.Status == AsyncOperationStatus.Failed)
//         {
//             Debug.LogError("Failed to get download size.");
//             yield break;
//         }

//         if (sizeCheck.Result > 0) // Only download if necessary
//         {
//             Debug.Log($"Downloading {sceneadress}...");
//             var downloadHandle = Addressables.DownloadDependenciesAsync(sceneadress);

//             while (!downloadHandle.IsDone)
//             {
//                 UpdateProgress(downloadHandle.PercentComplete, clickedObject);
//                 yield return null;
//             }

//             if (downloadHandle.Status == AsyncOperationStatus.Succeeded)
//             {
//                 Debug.Log($"Successfully downloaded group: {sceneadress}");
//                 Addressables.Release(downloadHandle);

//                 // Ensure UI reaches 100% before loading the scene
//                 StartCoroutine(SmoothProgressTo100AndLoadScene(sceneadress, clickedObject));
//             }
//             else
//             {
//                 Debug.LogError($"Failed to download group: {sceneadress}");
//                 yield break;
//             }
//         }
//         else
//         {
//             Debug.Log($"{sceneadress} is already downloaded.");
//             panel.SetActive(false);
//             //   StartCoroutine(LoadSceneSafely(sceneadress));
//         }
//     }
//     private bool IsOnlyone = false;

//     void UpdateProgress(float progress, GameObject clickedObject)
//     {
//         if (!IsOnlyone)
//         {
//             // IsOnlyone = true;
//             // GameObject obj = Instantiate(prefab, clickedObject.transform);
//             // obj.transform.localPosition = Vector3.zero;
//             // panel = obj.GetComponent<LoaderScript>().Panel;
//             // progress_text = obj.GetComponent<LoaderScript>().text;
//             // progressBar = obj.GetComponent<LoaderScript>().slider;
//             // progress_text.color = Color.white;
//             // panel.SetActive(true);
//             // int percent = Mathf.RoundToInt(progress * 100);
//             // progressBar.value = progress;
//             // progress_text.text = "Downloading.. " + percent + "%";
//         }

//     }

//     // Smooth transition from current progress to 100% then load scene
//     IEnumerator SmoothProgressTo100AndLoadScene(string sceneName, GameObject clickedObject)
//     {
//         float currentProgress = progressBar.value;
//         float duration = 0.2f; // Faster transition to 100%
//         float elapsedTime = 0;

//         while (elapsedTime < duration)
//         {
//             elapsedTime += Time.deltaTime;
//             float newProgress = Mathf.Lerp(currentProgress, 1f, elapsedTime / duration);
//             UpdateProgress(newProgress, clickedObject);
//             yield return null;
//         }

//         // UpdateProgress(1f, clickedObject); // Ensure it's exactly 100% at the end

//         // Load the scene immediately after progress reaches 100%

//         //CommonUtil.ShowToast($"{sceneName} Download Successfuly...");
//         //StartCoroutine(LoadSceneSafely(sceneName));
//         panel.SetActive(false);
//         IsOnlyone = false;
//     }

//     IEnumerator LoadSceneSafely(string sceneName)
//     {
//         string finalscene = sceneName;
//         Debug.Log($"Loading scene: {sceneName}");

//         if (sceneName == "point")
//         {
//             finalscene = "PointTable.unity";
//         }
//         else if (sceneName == "pool")
//         {
//             finalscene = "Join_Table(Pool).unity";
//         }
//         else if (sceneName == "deal")
//         {
//             finalscene = "Join_Table(Deal).unity";
//         }
//         else if (sceneName == "teenpatti")
//         {
//             finalscene = "TeenPatti_GamePlay.unity";
//         }
//         else
//         {
//             finalscene = sceneName;
//         }

//         AsyncOperationHandle<SceneInstance> handle = Addressables.LoadSceneAsync(
//             finalscene,
//             LoadSceneMode.Single
//         );
//         yield return handle;

//         if (handle.Status == AsyncOperationStatus.Succeeded)
//         {
//             Debug.Log($"Scene {finalscene} loaded successfully!");
//         }
//         else
//         {
//             Debug.LogError($"Failed to load scene: {finalscene}");
//         }
//     }
    public void CheckAndDownload(string scenename)
    {
        var clickedObject = UnityEngine.EventSystems.EventSystem.current?.currentSelectedGameObject;
        if (clickedObject == null)
        {
            return;
        }

        var loader = clickedObject.GetComponent<LoaderScript>();
        if (loader != null)
        {
            loader.loaddynamicscenebyname(scenename);
        }
    }
}
