'use strict';
require('dotenv').config({ path: require('path').resolve(__dirname, '../.env') });
const path = require('path');

const APK_PATH = process.env.ANDROID_APK_PATH
  ? path.resolve(__dirname, '..', process.env.ANDROID_APK_PATH)
  : path.resolve(__dirname, '../../build/ludo.apk');

module.exports = {
  platformName:        'Android',
  'appium:deviceName': process.env.ANDROID_DEVICE_NAME || 'emulator-5554',
  'appium:platformVersion': process.env.ANDROID_PLATFORM_VERSION || '13',
  'appium:automationName': 'UiAutomator2',
  'appium:app':        APK_PATH,
  'appium:appPackage': process.env.ANDROID_APP_PACKAGE || 'com.rox.ludo',
  'appium:appActivity': process.env.ANDROID_APP_ACTIVITY || 'com.unity3d.player.UnityPlayerActivity',
  'appium:noReset':    false,
  'appium:fullReset':  false,
  'appium:newCommandTimeout': 120,
  'appium:autoGrantPermissions': true,
  'appium:uiautomator2ServerLaunchTimeout': 60000,
};
