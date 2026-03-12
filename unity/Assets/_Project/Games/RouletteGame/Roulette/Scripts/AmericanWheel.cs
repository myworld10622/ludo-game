using System.Collections;
using UnityEngine;

public class AmericanWheel : Wheel
{
    // private new readonly byte[] numbers = new byte[]
    // {
    //     37,
    //     27,
    //     10,
    //     25,
    //     29,
    //     12,
    //     8,
    //     19, // < "37" is for "00"
    //     31,
    //     18,
    //     6,
    //     21,
    //     33,
    //     16,
    //     4,
    //     23,
    //     35,
    //     14,
    //     2,
    //     0,
    //     00,
    //     28,
    //     9,
    //     26,
    //     30,
    //     11,
    //     7,
    //     20,
    //     32,
    //     17,
    //     5,
    //     22,
    //     34,
    //     15,
    //     3,
    //     24,
    //     36,
    //     13,
    //     1,
    // };
    private readonly new byte[] numbers = new byte[] { 37, 27, 10, 25, 29, 12, 8, 19, // < "37" is for "00"
        31, 18, 6, 21, 33, 16, 4, 23, 35, 14, 2, 0, 28, 9, 26,
        30, 11, 7, 20, 32, 17, 5, 22, 34, 15, 3, 24, 36, 13, 1 };
    public int Payout = 35;

    void Start()
    {
        BetSpace.numLenght = Payout;
        resultCheckerObject = new GameObject[numbers.Length];
        for (int i = 0; i < numbers.Length; i++)
        {
            int id = numbers[i];

            resultCheckerObject[id] = new GameObject("resultchecker");
            resultCheckerObject[id].transform.SetParent(transform);
            resultCheckerObject[id].transform.localPosition = new Vector3(0, .215f, 0);
            resultCheckerObject[id]
                .transform.RotateAround(
                    transform.position,
                    Vector3.up * .03f,
                    i * 360 / numbers.Length
                );

            resultCheckerObject[id].name = id.ToString();
            BetSpace.numLenght = 37;
        }
    }

    public override void Spin()
    {
        print("Spin American");
        base.Spin();
        StartCoroutine(SetResult());
    }

    private IEnumerator SetResult()
    {
        // yield return new WaitForSecondsRealtime(5);
        // print("Set Result");
        // // ball.FindNumber(Random.Range(0, 37), false);
        // ball.FindNumber(34, true);
        yield return new WaitForSecondsRealtime(5);
        Debug.Log(
            "RES_Check + winning "
                + int.Parse(
                    GameObject
                        .Find("Manager")
                        .transform.GetChild(0)
                        .GetComponent<RouletteManager>()
                        .RouletteData.game_data[0]
                        .winning
                )
        );
        if (
            GameObject
                .Find("Manager")
                .transform.GetChild(0)
                .GetComponent<RouletteManager>()
                .RouletteData.game_data[0]
                .winning == "-1"
        )
            ball.FindNumber(37, true);
        else
        {
            ball.FindNumber(
                int.Parse(
                    GameObject
                        .Find("Manager")
                        .transform.GetChild(0)
                        .GetComponent<RouletteManager>()
                        .RouletteData.game_data[0]
                        .winning
                ),
                true
            );
            //ball.FindNumber(37, true);
        }

    }
}
