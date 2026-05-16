using System;
using System.IO;
using System.Linq;
using UnityEditor;
using UnityEditor.Build.Reporting;
using UnityEngine;

public static class AndroidProductionBuild
{
    private const string DefaultOutputPath = @"E:\New Rox APK\Latest new ROX 15.05\ROX 6.2\ROX_Ludo.apk";
    private const string BundleId = "com.roxludo.roxludo";

    [UnityEditor.MenuItem("Tools/Build Android APK (E:/New Rox APK)")]
    public static void BuildFromMenu() => Build();

    public static void Build()
    {
        string outputPath = Environment.GetEnvironmentVariable("ROX_ANDROID_APK_PATH");
        if (string.IsNullOrWhiteSpace(outputPath))
        {
            outputPath = DefaultOutputPath;
        }

        string keystorePassword = Environment.GetEnvironmentVariable("ROX_ANDROID_KEYSTORE_PASS");
        string keyaliasPassword = Environment.GetEnvironmentVariable("ROX_ANDROID_KEYALIAS_PASS");

        // CodeMagic: keystore path can be overridden via env var
        string keystorePath = Environment.GetEnvironmentVariable("ROX_ANDROID_KEYSTORE_PATH");
        if (!string.IsNullOrWhiteSpace(keystorePath) && File.Exists(keystorePath))
        {
            PlayerSettings.Android.keystoreName = keystorePath;
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
        PlayerSettings.bundleVersion = "6.2.0";
        PlayerSettings.Android.bundleVersionCode = 62;
        PlayerSettings.SetApplicationIdentifier(BuildTargetGroup.Android, BundleId);
        PlayerSettings.SetScriptingBackend(BuildTargetGroup.Android, ScriptingImplementation.IL2CPP);
        PlayerSettings.Android.targetArchitectures = AndroidArchitecture.ARMv7 | AndroidArchitecture.ARM64;
        PlayerSettings.Android.minSdkVersion = AndroidSdkVersions.AndroidApiLevel23;
        PlayerSettings.Android.targetSdkVersion = AndroidSdkVersions.AndroidApiLevel34;
        PlayerSettings.SetManagedStrippingLevel(BuildTargetGroup.Android, ManagedStrippingLevel.Medium);
        PlayerSettings.stripEngineCode = true;

        if (!string.IsNullOrWhiteSpace(keystorePassword))
        {
            PlayerSettings.Android.keystorePass = keystorePassword;
        }

        if (!string.IsNullOrWhiteSpace(keyaliasPassword))
        {
            PlayerSettings.Android.keyaliasPass = keyaliasPassword;
        }

        // High quality graphics settings
        PlayerSettings.gpuSkinning = true;
        PlayerSettings.graphicsJobs = true;
        QualitySettings.SetQualityLevel(QualitySettings.names.Length - 1, true); // Highest quality level
        QualitySettings.vSyncCount = 1;
        QualitySettings.antiAliasing = 4;
        QualitySettings.anisotropicFiltering = AnisotropicFiltering.ForceEnable;
        QualitySettings.shadowDistance = 40f;
        QualitySettings.shadows = ShadowQuality.All;
        Application.targetFrameRate = 60;

        // AAB for Play Store, APK for direct install
        bool buildAab = outputPath.EndsWith(".aab", StringComparison.OrdinalIgnoreCase);
        EditorUserBuildSettings.buildAppBundle = buildAab;

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
