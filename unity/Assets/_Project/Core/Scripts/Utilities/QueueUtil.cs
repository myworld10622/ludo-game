using System.Collections;
using System.Collections.Generic;
using UnityEngine;

public static class QueueUtil
{
    public static GameObject DequeueLast(this Queue<GameObject> queue)
    {
        GameObject[] array = queue.ToArray();
        queue.Clear();
        for (int i = 0; i < array.Length - 1; i++)
        {
            queue.Enqueue(array[i]);
        }
        return array[array.Length - 1];
    }

    public static void EnqueueFront(this Queue<GameObject> queue, GameObject item)
    {
        GameObject[] array = queue.ToArray();
        queue.Clear();
        queue.Enqueue(item);
        foreach (var obj in array)
        {
            queue.Enqueue(obj);
        }
    }
}
