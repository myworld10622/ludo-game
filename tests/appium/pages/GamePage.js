'use strict';
/**
 * GamePage — Appium page object for the active game board.
 *
 * Unity renders via OpenGL; UI elements are identified via
 * Android Accessibility IDs set in Unity through the
 * Accessibility plugin (or a custom native overlay layer).
 *
 * If your Unity build does not expose accessibility IDs,
 * use coordinate-based taps as a fallback (commented below).
 */

class GamePage {
  constructor(driver) {
    this.driver = driver;
  }

  // ── Selectors ────────────────────────────────────────────────────────────

  get boardContainer()      { return $('~LudoBoard'); }
  get rollDiceButton()      { return $('~RollDiceButton'); }
  get winPanel()            { return $('~WinPanel'); }
  get losePanel()           { return $('~LosePanel'); }
  get homeButton()          { return $('~HomeButton'); }
  get settingsButton()      { return $('~SettingsButton'); }
  get timerLabel()          { return $('~TurnTimerLabel'); }
  get chatOpenButton()      { return $('~ChatOpenButton'); }
  get chatInput()           { return $('~ChatInput'); }
  get chatSendButton()      { return $('~ChatSendButton'); }
  get waitingMessage()      { return $('~WaitingMessage'); }

  // Token elements — indexed by seat (0-3) and token (0-3)
  token(seat, ti)           { return $(`~Token_${seat}_${ti}`); }

  // ── Actions ──────────────────────────────────────────────────────────────

  async waitForBoard(timeoutMs = 15000) {
    await this.boardContainer.waitForDisplayed({ timeout: timeoutMs });
  }

  async tapRollDice() {
    await this.rollDiceButton.waitForDisplayed({ timeout: 5000 });
    await this.rollDiceButton.click();
  }

  async tapToken(seat, ti) {
    const el = this.token(seat, ti);
    await el.waitForDisplayed({ timeout: 5000 });
    await el.click();
  }

  async waitForWinPanel(timeoutMs = 120000) {
    await this.winPanel.waitForDisplayed({ timeout: timeoutMs });
  }

  async waitForLosePanel(timeoutMs = 120000) {
    await this.losePanel.waitForDisplayed({ timeout: timeoutMs });
  }

  async isRollDiceVisible() {
    try {
      return await this.rollDiceButton.isDisplayed();
    } catch {
      return false;
    }
  }

  async getTimerText() {
    try {
      return await this.timerLabel.getText();
    } catch {
      return null;
    }
  }

  async tapHome() {
    await this.homeButton.waitForDisplayed({ timeout: 5000 });
    await this.homeButton.click();
  }

  async sendChatMessage(message) {
    await this.chatOpenButton.click();
    await this.chatInput.waitForDisplayed({ timeout: 3000 });
    await this.chatInput.setValue(message);
    await this.chatSendButton.click();
  }

  // ── Coordinate-based fallbacks ────────────────────────────────────────────
  // Use when accessibility IDs are not set in the Unity build.

  async tapByCoords(x, y) {
    await this.driver.touchAction([{ action: 'tap', x, y }]);
  }

  async tapRollDiceFallback(screenWidth = 1080, screenHeight = 2340) {
    // Roll dice button is typically centered-bottom in landscape
    // Adjust for your specific layout
    const x = Math.floor(screenWidth * 0.85);
    const y = Math.floor(screenHeight * 0.5);
    await this.tapByCoords(x, y);
  }
}

module.exports = { GamePage };
