using System;
using System.IO;
using System.Linq;
using UnityEditor;
using UnityEditor.Build.Reporting;
using UnityEngine;

public static class WebGlProductionBuild
{
    private const string DefaultOutputPath = @"E:\New Rox APK\WebGl";

    [MenuItem("Tools/Build WebGL Production (E:/New Rox APK/WebGl)")]
    public static void BuildFromMenu() => Build();

    public static void Build()
    {
        string outputPath = Environment.GetEnvironmentVariable("ROX_WEBGL_OUTPUT_PATH");
        if (string.IsNullOrWhiteSpace(outputPath))
        {
            outputPath = DefaultOutputPath;
        }

        outputPath = Path.GetFullPath(outputPath);
        Directory.CreateDirectory(outputPath);

        string[] scenes = EditorBuildSettings.scenes
            .Where(scene => scene.enabled)
            .Select(scene => scene.path)
            .ToArray();

        if (scenes.Length == 0)
        {
            throw new InvalidOperationException("No enabled scenes found in Build Settings.");
        }

        if (!EditorUserBuildSettings.SwitchActiveBuildTarget(BuildTargetGroup.WebGL, BuildTarget.WebGL))
        {
            throw new InvalidOperationException("Failed to switch active build target to WebGL.");
        }

        PlayerSettings.WebGL.compressionFormat = WebGLCompressionFormat.Disabled;
        PlayerSettings.WebGL.dataCaching = false;
        PlayerSettings.WebGL.exceptionSupport = WebGLExceptionSupport.None;
#if UNITY_2022_1_OR_NEWER
        PlayerSettings.WebGL.debugSymbolMode = WebGLDebugSymbolMode.Off;
#endif

        BuildPlayerOptions options = new BuildPlayerOptions
        {
            scenes = scenes,
            locationPathName = outputPath,
            targetGroup = BuildTargetGroup.WebGL,
            target = BuildTarget.WebGL,
            // CleanBuildCache is triggering a Burst/ILPP artifact race on this project's
            // WebGL batch builds. Use the existing Library state instead of wiping codegen
            // outputs right before ILPP resolves them.
            options = BuildOptions.None,
        };

        BuildReport report = BuildPipeline.BuildPlayer(options);
        BuildSummary summary = report.summary;

        if (summary.result != BuildResult.Succeeded)
        {
            throw new Exception(
                $"WebGL production build failed. Result: {summary.result}, Errors: {summary.totalErrors}, Warnings: {summary.totalWarnings}"
            );
        }

        Debug.Log($"WebGL production build succeeded: {outputPath}");
        EditorApplication.Exit(0);
    }
}
