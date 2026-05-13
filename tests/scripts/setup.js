#!/usr/bin/env node
'use strict';
/**
 * setup.js — Post-install setup helper.
 * Creates .env from .env.example if not present.
 * Creates reports/ directory.
 */
const fs   = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');

// Create .env
const envFile    = path.join(root, '.env');
const envExample = path.join(root, '.env.example');
if (!fs.existsSync(envFile) && fs.existsSync(envExample)) {
  fs.copyFileSync(envExample, envFile);
  console.log('✓ Created .env from .env.example — please edit it with your server details.');
} else if (fs.existsSync(envFile)) {
  console.log('✓ .env already exists.');
}

// Create reports/
const reportsDir = path.join(root, 'reports');
if (!fs.existsSync(reportsDir)) {
  fs.mkdirSync(reportsDir, { recursive: true });
  fs.mkdirSync(path.join(reportsDir, 'playwright'), { recursive: true });
  fs.mkdirSync(path.join(reportsDir, 'appium'),     { recursive: true });
  console.log('✓ Created reports/ directory.');
}

console.log('\nSetup complete. Next steps:');
console.log('  1. Edit tests/.env with your LUDO_SERVER_URL');
console.log('  2. npm test              — run Playwright specs');
console.log('  3. npm run bot:run       — run gameplay bots');
console.log('  4. npm run locust:ui     — load test UI');
console.log('  5. npm run appium:test   — run Appium specs (requires running emulator)');
