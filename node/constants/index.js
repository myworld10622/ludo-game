const responseCode = require("./responseCode");
const game = require("./game");
const roulette = require("./roulette");
const colorPrediction = require("./coloPrediction");
const trippleFun = require("./trippleFun");
const rummy = require("./rummy");
const anderBaher = require("./anderBaher");
const redBlack = require("./redBlack");
const jackpot = require("./jackpot");
const jhandiMunda = require("./jhandiMunda");
const threeDice = require('./threeDice')
const baccarat = require("./baccarat");
const teenpatti = require("./teenpatti");
const BASE_URL = process.env.BASE_URL || "https://demo.androappstech.in";

module.exports = {
  ...responseCode,
  ...game,
  ...roulette,
  ...colorPrediction,
  ...trippleFun,
  ...rummy,
  ...anderBaher,
  ...redBlack,
  ...jackpot,
  ...jhandiMunda,
    ...threeDice,
  ...baccarat,
  ...teenpatti,
  BASE_URL,
};
