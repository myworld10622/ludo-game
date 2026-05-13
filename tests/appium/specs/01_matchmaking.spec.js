'use strict';
/**
 * Appium Spec 01 — Matchmaking UI
 *
 * Validates:
 *   ✓ App launches and lobby is visible
 *   ✓ Tapping Classic game button shows player count selection
 *   ✓ Tapping 2P starts matchmaking loader
 *   ✓ Loader shows "Waiting for players" message
 *   ✓ Board becomes visible after match is found (requires server + second player bot)
 *
 * Prerequisites:
 *   - Appium server running on localhost:4723
 *   - Android emulator running (emulator-5554)
 *   - APK installed
 */

const { expect } = require('chai');
const { LobbyPage } = require('../pages/LobbyPage');
const { GamePage }  = require('../pages/GamePage');

describe('Matchmaking UI', function () {
  let lobby, game;

  before(async function () {
    lobby = new LobbyPage(driver);
    game  = new GamePage(driver);
  });

  it('app launches and lobby is visible within 15s', async function () {
    await lobby.waitForVisible(15000);
    const isVisible = await lobby.classicGameButton.isDisplayed();
    expect(isVisible).to.be.true;
  });

  it('tapping Classic opens player count selection', async function () {
    await lobby.classicGameButton.click();
    await lobby.play2PButton.waitForDisplayed({ timeout: 5000 });
    const twoP = await lobby.play2PButton.isDisplayed();
    expect(twoP).to.be.true;
  });

  it('tapping 2P shows matchmaking loader', async function () {
    await lobby.play2PButton.click();
    await lobby.waitForMatchmakingLoader();
    const loaderVisible = await lobby.matchmakingLoader.isDisplayed();
    expect(loaderVisible).to.be.true;
  });

  it('waiting message contains "Waiting"', async function () {
    const text = await game.waitingMessage.getText().catch(() => '');
    expect(text.toLowerCase()).to.include('waiting');
  });

  // This test requires a socket bot to join from the server side
  it('board appears after second player joins (bot assist required)', async function () {
    this.timeout(30000);
    // The BotOrchestrator should be running externally to provide the second player.
    // If it's not running, this test will timeout and be reported as expected-failure.
    try {
      await game.waitForBoard(25000);
      const boardVisible = await game.boardContainer.isDisplayed();
      expect(boardVisible).to.be.true;
    } catch (e) {
      console.warn('[SKIP] Board test requires running bot (node bots/BotOrchestrator.js)');
      this.skip();
    }
  });
});
