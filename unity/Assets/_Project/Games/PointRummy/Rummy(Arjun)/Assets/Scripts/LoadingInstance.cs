using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public class LoadingInstance : MonoBehaviour
{
    public static LoadingInstance instance;
    public bool load = true;
    // Start is called before the first frame update
    void Start()
    {
        if (instance != null)
        {
            Destroy(gameObject);
            return;
        }

        // Set the instance to this GameObject
        instance = this;

        // Make this GameObject persist across scene changes
        DontDestroyOnLoad(gameObject);
    }

    // Update is called once per frame
    void Update()
    {
        
    }
}
