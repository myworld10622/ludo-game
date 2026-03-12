using System.Collections.Generic;
using UnityEngine;

public class ObjectPoolUtil : MonoBehaviour
{
    public GameObject prefab; // The prefab to instantiate
    public int initialPoolSize = 10; // Initial size of the pool

    private Queue<GameObject> pool = new Queue<GameObject>();

    public void InitializePool(GameObject prefab, int size)
    {
        this.prefab = prefab;
        this.initialPoolSize = size;

        for (int i = 0; i < initialPoolSize; i++)
        {
            GameObject obj = Instantiate(prefab);
            obj.SetActive(false);
            pool.Enqueue(obj);
        }
    }

    public GameObject GetObject()
    {
        if (pool.Count > 0)
        {
            GameObject obj = pool.Dequeue();
            obj.SetActive(true);
            return obj;
        }
        else
        {
            GameObject obj = Instantiate(prefab);
            return obj;
        }
    }

    public void ReturnObject(GameObject obj)
    {
        obj.SetActive(false);
        pool.Enqueue(obj);
    }
    public void SetLocalScale(float scale)
    {
        foreach (GameObject obj in pool)
        {
            obj.transform.localScale = Vector3.one * scale; // Reset scale to default (1, 1, 1)
        }
    }
}
