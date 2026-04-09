using System;
using System.IO;
using System.Linq;
using UnityEditor;
using UnityEditor.Build.Reporting;

public static class AndroidProductionBuild
{
    private const string DefaultOutputPath = @"D:\Latest Rox Ludo Apk\ROX_Ludo_Production.apk";
    private const string BundleId = "com.roxludo.roxludo";

    public static void Build()
    {
        string outputPath = Environment.GetEnvironmentVariable("ROX_ANDROID_APK_PATH");
        if (string.IsNullOrWhiteSpace(outputPath))
        {
            outputPath = DefaultOutputPath;
        }

        outputPath = Path.GetFullPath(outputPath);
        Directory.CreateDirectory(Path.GetDirectoryName(outputPath) ?? Path.GetPathRoot(outputPath));

        string[] scenes = EditorBuildSettings.scenes
            .Where(scene => scene.enabled)
            .Select(scene => scene.path)
            .ToArray();

        if (scenes.Length == 0)
        {
            throw new InvalidOperationException("No enabled scenes found in Build Settings.");
        }

        EditorUserBuildSettings.SwitchActiveBuildTarget(BuildTargetGroup.Android, BuildTarget.Android);

        PlayerSettings.companyName = "RoxLudo";
        PlayerSettings.productName = "ROX Ludo";
        PlayerSettings.bundleVersion = "1.0.0";
        PlayerSettings.SetApplicationIdentifier(BuildTargetGroup.Android, BundleId);
        PlayerSettings.SetScriptingBackend(BuildTargetGroup.Android, ScriptingImplementation.IL2CPP);
        PlayerSettings.Android.targetArchitectures = AndroidArchitecture.ARMv7 | AndroidArchitecture.ARM64;
        PlayerSettings.Android.minSdkVersion = AndroidSdkVersions.AndroidApiLevel23;
        PlayerSettings.Android.targetSdkVersion = AndroidSdkVersions.AndroidApiLevel34;
        PlayerSettings.SetManagedStrippingLevel(BuildTargetGroup.Android, ManagedStrippingLevel.Medium);
        PlayerSettings.stripEngineCode = true;

        BuildPlayerOptions options = new BuildPlayerOptions
        {
            scenes = scenes,
            locationPathName = outputPath,
            targetGroup = BuildTargetGroup.Android,
            target = BuildTarget.Android,
            options = BuildOptions.CleanBuildCache | BuildOptions.StrictMode,
        };

        BuildReport report = BuildPipeline.BuildPlayer(options);
        BuildSummary summary = report.summary;

        if (summary.result != BuildResult.Succeeded)
        {
            throw new Exception(
                $"Android build failed. Result: {summary.result}, Errors: {summary.totalErrors}, Warnings: {summary.totalWarnings}"
            );
        }

        UnityEngine.Debug.Log($"Android APK build succeeded: {outputPath}");
        EditorApplication.Exit(0);
    }
}
