// @ts-check
require('dotenv').config();
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir:     './playwright/specs',
  timeout:     600_000,   // 10 min per test (4P full games can take 5+ min)
  retries:     1,
  workers:     1,          // socket tests are stateful — run serially
  reporter:    [['html', { outputFolder: 'reports/playwright' }], ['list']],

  use: {
    // No browser needed — we use Playwright's test runner for structure/reporting
    // but drive socket.io clients directly.
    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'ludo-v2-protocol',
      testMatch: '**/*.spec.js',
    },
  ],
});
