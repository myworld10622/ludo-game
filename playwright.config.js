// @ts-check
// Root playwright.config.js — used by VS Code Playwright extension.
// Points to the tests/ subfolder where all specs and dependencies live.
require('dotenv').config({ path: './tests/.env' });
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir:  './tests/playwright/specs',
  timeout:  180_000,
  retries:  1,
  workers:  1,
  reporter: [
    ['html', { outputFolder: './tests/reports/playwright', open: 'never' }],
    ['list'],
  ],
  use: {
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'ludo-v2-protocol',
      testMatch: '**/*.spec.js',
    },
  ],
});
