@echo off
set ADB=C:\Users\asus\AppData\Local\Android\Sdk\platform-tools\adb.exe
set D=10BD4A0D03000A1
set OUT=d:\Live-Code\Live-Rox-Ludo\games\tests\adb_tests\screenshots\live_now.png
%ADB% -s %D% shell screencap /sdcard/g.png
%ADB% -s %D% pull /sdcard/g.png "%OUT%"
if exist "%OUT%" (echo PULLED OK) else (echo FAILED)
