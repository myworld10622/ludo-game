using UnityEngine;

public static class ProductionLogGuard
{
    [RuntimeInitializeOnLoadMethod(RuntimeInitializeLoadType.BeforeSceneLoad)]
    private static void ConfigureLogging()
    {
#if !UNITY_EDITOR && !DEVELOPMENT_BUILD
        Debug.unityLogger.logEnabled = false;
        Application.SetStackTraceLogType(LogType.Log, StackTraceLogType.None);
        Application.SetStackTraceLogType(LogType.Warning, StackTraceLogType.None);
        Application.SetStackTraceLogType(LogType.Error, StackTraceLogType.None);
        Application.SetStackTraceLogType(LogType.Assert, StackTraceLogType.None);
        Application.SetStackTraceLogType(LogType.Exception, StackTraceLogType.None);
#endif
    }
}
