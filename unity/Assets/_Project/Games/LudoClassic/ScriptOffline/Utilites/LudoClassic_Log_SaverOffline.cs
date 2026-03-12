using System;
using System.Collections.Concurrent;
using System.Collections.Generic;
using System.IO;
using System.Text;
using UnityEditor;
using UnityEngine;
using UnityEngine.UI;

namespace LudoClassicOffline
{

    public class LudoClassic_Log_SaverOffline : MonoBehaviour
    {
        string filename = "";
        [SerializeField] string logFileName;
        private void Awake()
        {
            try
            {
                filename = Application.dataPath + "/" + logFileName + ".text";
                if (File.Exists(filename))
                    File.Delete(filename);
                Debug.Log(filename);

            }
            catch (Exception)
            {
                throw;
            }
        }
        private void OnDisable()
        {
            Application.logMessageReceived -= Log;
        }
        private void OnEnable()
        {
            Application.logMessageReceived += Log;
        }
        public void Log(string LogMsg, string logStack, LogType log)
        {
            if (SystemInfo.deviceName == "Galaxy A13" || SystemInfo.deviceUniqueIdentifier == "5f33502fe67d4f97") return;

            TextWriter tw = new StreamWriter(filename, true);

            tw.WriteLine("[" + System.DateTime.Now + "]" + LogMsg);

            tw.Close();

        }
    }
}