'use strict';
require('dotenv').config({ path: require('path').resolve(__dirname, '../.env') });
const caps = require('./capabilities');

exports.config = {
  runner:   'local',
  hostname: process.env.APPIUM_SERVER_URL
    ? new URL(process.env.APPIUM_SERVER_URL).hostname
    : 'localhost',
  port:     4723,
  path:     '/',
  specs:    ['./appium/specs/**/*.spec.js'],
  maxInstances: 1,
  capabilities: [caps],
  logLevel:     'info',
  bail:         0,
  waitforTimeout:   30000,
  connectionRetryTimeout: 120000,
  connectionRetryCount:   3,
  framework:    'mocha',
  reporters:    [
    'spec',
    ['allure', { outputDir: 'reports/appium' }],
  ],
  mochaOpts: {
    ui:      'bdd',
    timeout: 180000,
  },
};
