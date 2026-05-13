'use strict';
/**
 * LobbyPage — Appium page object for the main lobby screen.
 *
 * Unity UI elements are accessed via accessibility IDs or UiAutomator2 selectors.
 * Resource IDs follow the pattern "com.rox.ludo:id/<name>" for native views,
 * but Unity renders via GL; use accessibility labels set in Unity's Accessibility plugin.
 */

class LobbyPage {
  constructor(driver) {
    this.driver = driver;
  }

  // ── Selectors ────────────────────────────────────────────────────────────

  get classicGameButton()  { return $('~LudoClassicButton'); }
  get play2PButton()       { return $('~Play2Players'); }
  get play4PButton()       { return $('~Play4Players'); }
  get matchmakingLoader()  { return $('~MatchmakingLoaderCanvas'); }
  get profileButton()      { return $('~ProfileButton'); }
  get balanceLabel()       { return $('~BalanceLabel'); }

  // ── Actions ──────────────────────────────────────────────────────────────

  async waitForVisible(timeoutMs = 15000) {
    await this.classicGameButton.waitForDisplayed({ timeout: timeoutMs });
  }

  async startClassic2Player() {
    await this.classicGameButton.click();
    await this.play2PButton.waitForDisplayed({ timeout: 5000 });
    await this.play2PButton.click();
  }

  async startClassic4Player() {
    await this.classicGameButton.click();
    await this.play4PButton.waitForDisplayed({ timeout: 5000 });
    await this.play4PButton.click();
  }

  async waitForMatchmakingLoader() {
    await this.matchmakingLoader.waitForDisplayed({ timeout: 10000 });
  }

  async waitForMatchmakingComplete() {
    // Loader disappears when game starts
    await this.matchmakingLoader.waitForDisplayed({ timeout: 20000, reverse: true });
  }

  async getBalance() {
    try {
      return await this.balanceLabel.getText();
    } catch {
      return null;
    }
  }
}

module.exports = { LobbyPage };
