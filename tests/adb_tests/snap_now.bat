@echo off
set ADB=C:\Users\asus\AppData\Local\Android\Sdk\platform-tools\adb.exe
set DEVICE=10BD4A0D03000A1
set SHOTS=d:\Live-Code\Live-Rox-Ludo\games\tests\adb_tests\screenshots

if not exist "%SHOTS%" mkdir "%SHOTS%"

%ADB% -s %DEVICE% shell screencap /sdcard/snap.png
%ADB% -s %DEVICE% pull /sdcard/snap.png "%SHOTS%\current_state.png"
echo Done
