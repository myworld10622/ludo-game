// Game Number
const DRAGON = 0;
const TIGER = 1;
const TIE = 2;

const SEVEN_UP_GAME = {
  down: 0,
  up: 1,
  tie: 2,
  upDownMultiply: 2,
  upDownTieMultiply: 5,
};

const HEAD_TAIL_GAME = {
  head: 0,
  tail: 1,
  tie: 2,
  headTailMultiply: 2,
  headTailTieMultiply: 5,
};

const ANIMAL_ROULETTE_GAME = {
  tiger: 1,
  snake: 2,
  shark: 3,
  fox: 4,
  cheetah: 5,
  bear: 6,
  whale: 7,
  lion: 8,
  tigerMultiply: 5,
  snakeMultiply: 5,
  sharkMultiply: 5,
  foxMultiply: 5,
  cheetahMultiply: 10,
  bearMultiply: 15,
  whaleMultiply: 25,
  lionMultiply: 40,
};

const ICON_ROULETTE_GAME = {
    umbrella: 1,
    football: 2,
    sun: 3,
    diya: 4,
    cow: 5,
    bucket: 6,
    kite: 7,
    top: 8,
    rose: 9,
    butterfly: 10,
    pigeon: 11,
    rabbit: 12,
    umbrellaMultiply: 10,
    footballMultiply: 10,
    sunMultiply: 10,
    diyaMultiply: 10,
    cowMultiply: 10,
    bucketMultiply: 10,
    kiteMultiply: 10,
    topMultiply: 10,
    roseMultiply: 10,
    butterflyMultiply: 10,
    pigeonMultiply: 10,
    rabbitMultiply: 10
}

const CAR_ROULETTE_GAME = {
  toyota: 1,
  mahindra: 2,
  audi: 3,
  bmw: 4,
  mercedes: 5,
  porsche: 6,
  lamborghini: 7,
  ferrari: 8,
  toyotaMultiply: 5,
  mahindraMultiply: 5,
  audiMultiply: 5,
  bmwMultiply: 5,
  mercedesMultiply: 10,
  porscheMultiply: 15,
  lamborghiniMultiply: 25,
  ferrariMultiply: 40,
};

// Game Names
const GAMES = {
    dragonTiger: "Dragon vs Tiger",
    cp: "Color Prediction",
    cp1: "Color Prediction1Min",
    cp3: "Color Prediction3Min",
    cp5: "Color Prediction5Min",
    aviator: "Aviator",
    roulette: "Roulette",
    anderBahar: "Andar Bahar",
    betDailyBonus: "Bet Daily Bonus",
    salaryDailyBonus: "Salary Daily Bonus",
    dailyRebetBonus: "Rebate Bonus",
    dailyMissionRechargeBonus: "Mission Recharge Bonus",
    dailyAttendenceBonus: "Attendence Bonus",
    pointRummy: "Rummy",
    poolRummy: "Rummy Pool",
    dealRummy: "Rummy Deal",
    tournamentRummy: "Rummy Tournament",
    sevenUp: "Seven Up Down",
    headTail: "Head & Tail",
    animalRoulette: "Animal Roulette",
    carRoulette: "Car Roulette",
    redBlack: "Red vs Black",
    jackpot: "JackPot 3 Patti",
    jhandiMunda: "Jhandi Munda",
    threeDice: "Three Dice",
    baccarat: "Baccarat",
    teenpatti: "Teenpatti",
    slotGame: "Slot Game",
    iconRoulette: "Icon Roulette",
    trippleFun: "Tipple Fun"
}

// Winning amout X Times
const DRAGON_OR_TIGET_MULTIPLY = 2;
const SLOT_MULTIPLY = 2;
const TIE_MULTIPLY = 9;

// Game Start Time
const DRAGON_TIME_FOR_START_NEW_GAME = 5;
const SLOT_TIME_FOR_START_NEW_GAME = 5;
const ROULETTE_TIME_FOR_START_NEW_GAME = 5;
const COLOR_PREDICTION_TIME_FOR_START_NEW_GAME = 5;
const TRIPPLE_FUN_TIME_FOR_START_NEW_GAME = 5;
const SEVEN_UP_TIME_FOR_START_NEW_GAME = 5;
const HEAD_TAIL_TIME_FOR_START_NEW_GAME = 5;
const ANIMAL_ROULETTE_TIME_FOR_START_NEW_GAME = 5;
const CAR_ROULETTE_TIME_FOR_START_NEW_GAME = 5;
const RED_BLACK_TIME_FOR_START_NEW_GAME = 5;
const JACKPOT_TIME_FOR_START_NEW_GAME = 5;
const JHANDI_MUNDA_TIME_FOR_START_NEW_GAME = 5;
const THREE_DICE_TIME_FOR_START_NEW_GAME = 5;
const BACCARAT_TIME_FOR_START_NEW_GAME = 5;
const ICON_ROULETTE_TIME_FOR_START_NEW_GAME = 5;


// Time For Bet
const DRAGON_TIME_FOR_BET = 10;
const SLOT_TIME_FOR_BET = 10;
const ROULETTE_TIME_FOR_BET = 10;
const COLOR_PREDICTION_FOR_BET = 10;
const TRIPPLE_FUN_FOR_BET = 10;
const SEVEN_UP_FOR_BET = 30;
const HEAD_TAIL_FOR_BET = 10;
const ANIMAL_ROULETTE_FOR_BET = 10;
const CAR_ROULETTE_FOR_BET = 10;
const ANDER_BAHER_FOR_BET = 10;
const RED_BLACK_FOR_BET = 10;
const JACKPOT_FOR_BET = 10;
const JHANDI_MUNDA_FOR_BET = 10;
const THREE_DICE_FOR_BET = 10;
const BACCARAT_FOR_BET = 10;
const ICON_ROULETTE_FOR_BET = 10;


module.exports = {
  DRAGON,
  TIGER,
  TIE,
  GAMES,
  DRAGON_OR_TIGET_MULTIPLY,
  DRAGON_TIME_FOR_BET,
  SLOT_MULTIPLY,
  SLOT_TIME_FOR_BET,
  TIE_MULTIPLY,
  DRAGON_TIME_FOR_START_NEW_GAME,
  SLOT_TIME_FOR_START_NEW_GAME,

  ROULETTE_TIME_FOR_START_NEW_GAME,
  ROULETTE_TIME_FOR_BET,

  COLOR_PREDICTION_FOR_BET,
  COLOR_PREDICTION_TIME_FOR_START_NEW_GAME,

  TRIPPLE_FUN_FOR_BET,
  TRIPPLE_FUN_TIME_FOR_START_NEW_GAME,

  SEVEN_UP_GAME,
  SEVEN_UP_TIME_FOR_START_NEW_GAME,
  SEVEN_UP_FOR_BET,

  HEAD_TAIL_GAME,
  HEAD_TAIL_TIME_FOR_START_NEW_GAME,
  HEAD_TAIL_FOR_BET,

  ANIMAL_ROULETTE_GAME,
  ANIMAL_ROULETTE_TIME_FOR_START_NEW_GAME,
  ANIMAL_ROULETTE_FOR_BET,

  CAR_ROULETTE_GAME,
  CAR_ROULETTE_TIME_FOR_START_NEW_GAME,
  CAR_ROULETTE_FOR_BET,

  ANDER_BAHER_FOR_BET,

  RED_BLACK_TIME_FOR_START_NEW_GAME,
  RED_BLACK_FOR_BET,

  JACKPOT_TIME_FOR_START_NEW_GAME,
  JACKPOT_FOR_BET,

  JHANDI_MUNDA_TIME_FOR_START_NEW_GAME,
  JHANDI_MUNDA_FOR_BET,

  THREE_DICE_TIME_FOR_START_NEW_GAME,
  THREE_DICE_FOR_BET,

  BACCARAT_TIME_FOR_START_NEW_GAME,
  BACCARAT_FOR_BET,

  ICON_ROULETTE_GAME,
  ICON_ROULETTE_TIME_FOR_START_NEW_GAME,
  ICON_ROULETTE_FOR_BET,
}