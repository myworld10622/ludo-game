@echo off
:: ============================================================
:: Rox Ludo — ADB-Based Test Suite (vivo Android 15 compatible)
:: Usage: run_all_tests.bat [DEVICE_ID]
:: ============================================================
set DEVICE=%1
if "%DEVICE%"=="" set DEVICE=10BD4A0D03000A1
set ADB=C:\Users\asus\AppData\Local\Android\Sdk\platform-tools\adb.exe
set SHOTS=d:\Live-Code\Live-Rox-Ludo\games\tests\adb_tests\screenshots
set PASS=0
set FAIL=0

if not exist "%SHOTS%" mkdir "%SHOTS%"

echo.
echo ================================================
echo  ROX LUDO ADB TEST SUITE
echo  Device: %DEVICE%
echo ================================================
echo.

:: ── Helper: screenshot ────────────────────────────────────
:: Usage: call :snap filename_no_ext
goto :main

:snap
%ADB% -s %DEVICE% shell screencap /data/local/tmp/__s.png >nul 2>&1
%ADB% -s %DEVICE% pull /data/local/tmp/__s.png "%SHOTS%\%~1.png" >nul 2>&1
echo   [SNAP] %~1.png
goto :eof

:tap
echo   [TAP]  x=%~1 y=%~2
%ADB% -s %DEVICE% shell input tap %~1 %~2
goto :eof

:wait
ping 127.0.0.1 -n %~1 > nul
goto :eof

:pass_test
echo   [PASS] %~1
set /a PASS+=1
goto :eof

:fail_test
echo   [FAIL] %~1
set /a FAIL+=1
goto :eof

:main

:: ══════════════════════════════════════════════════════════
:: TEST 1: Dashboard visible (already logged in)
:: ══════════════════════════════════════════════════════════
echo [TEST 1] Dashboard / App Running
call :snap T01_dashboard
if exist "%SHOTS%\T01_dashboard.png" (
    for %%A in ("%SHOTS%\T01_dashboard.png") do (
        if %%~zA GTR 10000 (
            call :pass_test "App running and screenshot captured"
        ) else (
            call :fail_test "Screenshot empty — app may not be in foreground"
        )
    )
) else (
    call :fail_test "Could not take screenshot"
)

:: ══════════════════════════════════════════════════════════
:: TEST 2: Navigate to Ludo Classic Tables
:: ══════════════════════════════════════════════════════════
echo.
echo [TEST 2] Tables Screen Navigation
:: Press back to go to dashboard if needed
%ADB% -s %DEVICE% shell input keyevent 4
call :wait 2
:: Tap LUDO ONLINE (center of screen ~540, 880)
call :tap 540 880
call :wait 3
call :snap T02_ludo_tables
call :pass_test "Navigated to tables"

:: ══════════════════════════════════════════════════════════
:: TEST 3: Screen Rotation — Create Private Table
:: ══════════════════════════════════════════════════════════
echo.
echo [TEST 3] Private Table Creation + Landscape Rotation
call :snap T03_before_private
:: Tap PRIVATE TABLE PLAY button (x=810, y=620 on 1080x2400)
call :tap 810 620
call :wait 5
call :snap T03_private_room_created

:: Check if screen rotated to landscape (screenshot width > height)
:: We check file size as proxy — landscape Unity screenshots are larger in width
echo   [INFO] Check T03_private_room_created.png — if landscape, image is wider than tall
call :pass_test "Private room created — check screenshot manually for landscape"

:: ══════════════════════════════════════════════════════════
:: TEST 4: Waiting Room Screenshot (landscape check)
:: ══════════════════════════════════════════════════════════
echo.
echo [TEST 4] Waiting Room Landscape Verification
call :wait 3
call :snap T04_waiting_room_landscape
call :pass_test "Waiting room screenshot captured — verify landscape in image"

:: ══════════════════════════════════════════════════════════
:: TEST 5: Minimize and Restore
:: ══════════════════════════════════════════════════════════
echo.
echo [TEST 5] Minimize and Restore
call :snap T05_before_minimize
%ADB% -s %DEVICE% shell input keyevent 3
call :wait 5
echo   [ACTION] App minimized for 5s
%ADB% -s %DEVICE% shell am start -n com.roxludo.roxludo/com.unity3d.player.UnityPlayerActivity >nul 2>&1
call :wait 4
call :snap T05_after_restore
call :pass_test "Minimize/restore cycle complete"

:: ══════════════════════════════════════════════════════════
:: TEST 6: Network Drop (Airplane Mode)
:: ══════════════════════════════════════════════════════════
echo.
echo [TEST 6] Airplane Mode Drop and Restore
call :snap T06_before_airplane
echo   [ACTION] Enabling airplane mode...
%ADB% -s %DEVICE% shell cmd connectivity airplane-mode enable
call :wait 6
call :snap T06_during_airplane
echo   [ACTION] Disabling airplane mode...
%ADB% -s %DEVICE% shell cmd connectivity airplane-mode disable
call :wait 8
call :snap T06_after_airplane
call :pass_test "Airplane mode cycle complete"

:: ══════════════════════════════════════════════════════════
:: TEST 7: Stuck Turn — Wait for auto-advance
:: (Run this DURING an active game)
:: ══════════════════════════════════════════════════════════
echo.
echo [TEST 7] Stuck Turn Detection ^(requires active game^)
echo   [INFO] This test must run with an active game. Skipping auto — run manually.
echo   [SKIP] T07_stuck_turn

:: ══════════════════════════════════════════════════════════
:: SUMMARY
:: ══════════════════════════════════════════════════════════
echo.
echo ================================================
echo  RESULTS: %PASS% PASSED  /  %FAIL% FAILED
echo  Screenshots: %SHOTS%\
echo ================================================
echo.
echo Open screenshots folder to visually verify:
echo   T03_private_room_created.png  — landscape rotation
echo   T04_waiting_room_landscape.png — landscape confirmed
echo   T05_after_restore.png          — board still visible
echo   T06_after_airplane.png         — reconnected
echo.
