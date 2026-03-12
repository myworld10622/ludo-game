const dbConfig = require("../config/dbConfig.js");
const { Sequelize, DataTypes } = require("sequelize");
const sequelize = new Sequelize(dbConfig.DB, dbConfig.USER, dbConfig.PASSWORD, {
  logging: false,
  host: dbConfig.HOST,
  port: dbConfig.PORT,
  dialect: dbConfig.dialect,
  useUTC: false,
  dateStrings: dbConfig.dateStrings,
  timezone: "+05:30",
});

// sequelize.authenticate()

const db = {};

db.Sequelize = Sequelize;
db.sequelize = sequelize;

/////////////////////////////////////// Aviator Models ///////////////////////////////////////////////
db.Aviator = require("./aviator/aviatorModel.js")(sequelize, DataTypes);
db.AviatorBet = require("./aviator/aviatorBetModel.js")(sequelize, DataTypes);

////////////////////////////////// Dragon Tiger Models ///////////////////////////////////////////////
db.DragonTiger = require("./dragonTiger/dragonTigerModel.js")(
  sequelize,
  DataTypes
);
db.DragonTigerBet = require("./dragonTiger/dragonTigerBetModel.js")(
  sequelize,
  DataTypes
);
db.DragonTigerRoom = require("./dragonTiger/dragonTigerRoomModel.js")(
  sequelize,
  DataTypes
);
db.DragonTigerMap = require("./dragonTiger/dragonTigerMapModel.js")(
  sequelize,
  DataTypes
);

db.SlotGame = require("./slotGame/slotGameModel.js")(sequelize, DataTypes);
db.SlotGameBet = require("./slotGame/slotGameBetModel.js")(
  sequelize,
  DataTypes
);
db.SlotGameRoom = require("./slotGame/slotGameRoomModel.js")(
  sequelize,
  DataTypes
);
db.SlotGameMap = require("./slotGame/slotGameMapModel.js")(
  sequelize,
  DataTypes
);

/////////////////////////////////// Roulette Models ///////////////////////////////////////////////
db.Roulette = require("./roulette/rouletteModel.js")(sequelize, DataTypes);
db.RouletteBet = require("./roulette/rouletteBetModel.js")(
  sequelize,
  DataTypes
);
db.RouletteRoom = require("./roulette/rouletteRoomModel.js")(
  sequelize,
  DataTypes
);
db.RouletteMap = require("./roulette/rouletteMapModel.js")(
  sequelize,
  DataTypes
);
db.RouletteTempBet = require("./roulette/rouletteTempBetModel.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Color Prediction Models ///////////////////////////////////////////////
db.ColorPrediction = require("./colorPrediction/colorPredictionModel.js")(
  sequelize,
  DataTypes
);
db.ColorPredictionBet = require("./colorPrediction/colorPredictionBetModel.js")(
  sequelize,
  DataTypes
);
db.ColorPredictionRoom =
  require("./colorPrediction/colorPredictionRoomModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionMap = require("./colorPrediction/colorPredictionMapModel.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Color Prediction 1 Min Models ///////////////////////////////////////////////
db.ColorPrediction1Min = require("./colorPrediction1/colorPredictionModel.js")(
  sequelize,
  DataTypes
);
db.ColorPredictionBet1Min =
  require("./colorPrediction1/colorPredictionBetModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionRoom1Min =
  require("./colorPrediction1/colorPredictionRoomModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionMap1Min =
  require("./colorPrediction1/colorPredictionMapModel.js")(
    sequelize,
    DataTypes
  );

////////////////////////////////// Tripple Fun Models ///////////////////////////////////////////////
db.TrippleFun = require("./trippleFun/trippleFunModel.js")(
  sequelize,
  DataTypes
);
db.TrippleFunBet = require("./trippleFun/trippleFunBetModel.js")(
  sequelize,
  DataTypes
);
db.TrippleFunRoom = require("./trippleFun/trippleFunRoomModel.js")(
  sequelize,
  DataTypes
);
db.TrippleFunMap = require("./trippleFun/trippleFunMapModel.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Color Prediction 3 Min Models ///////////////////////////////////////////////
db.ColorPrediction3Min = require("./colorPrediction3/colorPredictionModel.js")(
  sequelize,
  DataTypes
);
db.ColorPredictionBet3Min =
  require("./colorPrediction3/colorPredictionBetModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionRoom3Min =
  require("./colorPrediction3/colorPredictionRoomModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionMap3Min =
  require("./colorPrediction3/colorPredictionMapModel.js")(
    sequelize,
    DataTypes
  );

////////////////////////////////// Color Prediction 5 Min Models ///////////////////////////////////////////////
db.ColorPrediction5Min = require("./colorPrediction5/colorPredictionModel.js")(
  sequelize,
  DataTypes
);
db.ColorPredictionBet5Min =
  require("./colorPrediction5/colorPredictionBetModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionRoom5Min =
  require("./colorPrediction5/colorPredictionRoomModel.js")(
    sequelize,
    DataTypes
  );
db.ColorPredictionMap5Min =
  require("./colorPrediction5/colorPredictionMapModel.js")(
    sequelize,
    DataTypes
  );

////////////////////////////////// Ander Bahar Model Models ///////////////////////////////////////////////
db.AnderBahar = require("./anderBahar/anderBahar.js")(sequelize, DataTypes);
db.AnderBaharBet = require("./anderBahar/anderBaharBet.js")(
  sequelize,
  DataTypes
);
db.AnderBaharRoom = require("./anderBahar/anderBaharRoom.js")(
  sequelize,
  DataTypes
);
db.AnderBaharMap = require("./anderBahar/anderBaharMap.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Bet/Bonus Level Models ///////////////////////////////////////////////
db.BetIncomeLog = require("./bonus/betIncomeLog.js")(sequelize, DataTypes);
db.BetIncomeMaster = require("./bonus/betIncomeMaster.js")(
  sequelize,
  DataTypes
);
db.SalaryBonus = require("./bonus/salaryBonus.js")(sequelize, DataTypes);
db.SalaryBonusLog = require("./bonus/salaryBonusLog.js")(sequelize, DataTypes);
db.RebateBonus = require("./bonus/rebateBonus.js")(sequelize, DataTypes);
db.DailyMissionRechargeBonus = require("./bonus/dailyMissionRechargeBonus.js")(
  sequelize,
  DataTypes
);
db.DailyMissionRechargeBonusLog =
  require("./bonus/dailyMissionRechargeBonusLog.js")(sequelize, DataTypes);
db.AttendenceBonus = require("./bonus/attendenceBonus.js")(
  sequelize,
  DataTypes
);
db.AttendenceBonusLog = require("./bonus/attendenceBonusLog.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Rummy Pool Models ///////////////////////////////////////////////
db.RummyPool = require("./rummyPool/rummyPoolModel.js")(sequelize, DataTypes);
db.RummyPoolCard = require("./rummyPool/rummyPoolCardModel.js")(
  sequelize,
  DataTypes
);
db.RummyPoolCardDrop = require("./rummyPool/rummyPoolCardDropModel.js")(
  sequelize,
  DataTypes
);
db.RummyPoolLog = require("./rummyPool/rummyPoolLogModel.js")(
  sequelize,
  DataTypes
);
db.RummyPoolTable = require("./rummyPool/rummyPoolTableModel.js")(
  sequelize,
  DataTypes
);
db.RummyPoolTableMaster = require("./rummyPool/rummyPoolTableMasterModel.js")(
  sequelize,
  DataTypes
);
db.RummyPoolTableUser = require("./rummyPool/rummyPoolTableUserModel.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Rummy Point Models ///////////////////////////////////////////////
db.RummyPoint = require("./rummyPoint/rummyPointModel.js")(
  sequelize,
  DataTypes
);
db.RummyPointCard = require("./rummyPoint/rummyPointCardModel.js")(
  sequelize,
  DataTypes
);
db.RummyPointCardDrop = require("./rummyPoint/rummyPointCardDropModel.js")(
  sequelize,
  DataTypes
);
db.RummyPointLog = require("./rummyPoint/rummyPointLogModel.js")(
  sequelize,
  DataTypes
);
db.RummyPointTable = require("./rummyPoint/rummyPointTableModel.js")(
  sequelize,
  DataTypes
);
db.RummyPointTableMaster =
  require("./rummyPoint/rummyPointTableMasterModel.js")(sequelize, DataTypes);
db.RummyPointTableUser = require("./rummyPoint/rummyPointTableUserModel.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Rummy Deal Models ///////////////////////////////////////////////
db.RummyDeal = require("./rummyDeal/rummyDealModel.js")(sequelize, DataTypes);
db.RummyDealCard = require("./rummyDeal/rummyDealCardModel.js")(
  sequelize,
  DataTypes
);
db.RummyDealCardDrop = require("./rummyDeal/rummyDealCardDropModel.js")(
  sequelize,
  DataTypes
);
db.RummyDealLog = require("./rummyDeal/rummyDealLogModel.js")(
  sequelize,
  DataTypes
);
db.RummyDealTable = require("./rummyDeal/rummyDealTableModel.js")(
  sequelize,
  DataTypes
);
db.RummyDealTableMaster = require("./rummyDeal/rummyDealTableMasterModel.js")(
  sequelize,
  DataTypes
);
db.RummyDealTableUser = require("./rummyDeal/rummyDealTableUserModel.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Rummy Tournament Models ///////////////////////////////////////////////
db.RummyTournamentType = require("./rummyTournament/rummyTournamentType.js")(
  sequelize,
  DataTypes
);
db.RummyTournamentTableMaster =
  require("./rummyTournament/rummyTournamentTableMaster.js")(
    sequelize,
    DataTypes
  );
db.RummyTournamentParticipants =
  require("./rummyTournament/rummyTournamentParticipants.js")(
    sequelize,
    DataTypes
  );
db.RummyTournamentTable = require("./rummyTournament/rummyTournamentTable.js")(
  sequelize,
  DataTypes
);
db.RummyTournament = require("./rummyTournament/rummyTournament.js")(
  sequelize,
  DataTypes
);
db.RummyTournamentTableUser =
  require("./rummyTournament/rummyTournamentTableUser.js")(
    sequelize,
    DataTypes
  );
db.RummyTournamentCard = require("./rummyTournament/rummyTournamentCard.js")(
  sequelize,
  DataTypes
);
db.RummyTournamentCardDrop =
  require("./rummyTournament/rummyTournamentCardDrop.js")(sequelize, DataTypes);
db.RummyTournamentLog = require("./rummyTournament/rummyTournamentLog.js")(
  sequelize,
  DataTypes
);
db.RummyTournamentPrizes =
  require("./rummyTournament/rummyTournamentPrizes.js")(sequelize, DataTypes);
db.RummyTournamentRounds =
  require("./rummyTournament/rummyTournamentRounds.js")(sequelize, DataTypes);
db.RummyTournamentLogTickets =
  require("./rummyTournament/rummyTournamentTickets.js")(sequelize, DataTypes);
db.RummyTournamentWinners =
  require("./rummyTournament/rummyTournamentWinners.js")(sequelize, DataTypes);

////////////////////////////////// Seven Up Models ///////////////////////////////////////////////
db.SevenUp = require("./sevenUp/sevenUp.js")(sequelize, DataTypes);
db.SevenUpBet = require("./sevenUp/sevenUpBet.js")(sequelize, DataTypes);
db.SevenUpRoom = require("./sevenUp/sevenUpRoom.js")(sequelize, DataTypes);
db.SevenUpMap = require("./sevenUp/sevenUpMap.js")(sequelize, DataTypes);

////////////////////////////////// Head Tail Models ///////////////////////////////////////////////
db.HeadTail = require("./headTail/headTail.js")(sequelize, DataTypes);
db.HeadTailBet = require("./headTail/headTailBet.js")(sequelize, DataTypes);
db.HeadTailRoom = require("./headTail/headTailRoom.js")(sequelize, DataTypes);
db.HeadTailMap = require("./headTail/headTailMap.js")(sequelize, DataTypes);

////////////////////////////////// Icon Roulette Models ///////////////////////////////////////////////
db.IconRoulette = require('./iconRoulette/iconRoulette.js')(sequelize, DataTypes);
db.IconRouletteBet = require('./iconRoulette/iconRouletteBet.js')(sequelize, DataTypes);
db.IconRouletteRoom = require('./iconRoulette/iconRouletteRoom.js')(sequelize, DataTypes);
db.IconRouletteMap = require('./iconRoulette/iconRouletteMap.js')(sequelize, DataTypes);

////////////////////////////////// Car Roulette Models ///////////////////////////////////////////////
db.CarRoulette = require("./carRoulette/carRoulette.js")(sequelize, DataTypes);
db.CarRouletteBet = require("./carRoulette/carRouletteBet.js")(
  sequelize,
  DataTypes
);
db.CarRouletteRoom = require("./carRoulette/carRouletteRoom.js")(
  sequelize,
  DataTypes
);
db.CarRouletteMap = require("./carRoulette/carRouletteMap.js")(
  sequelize,
  DataTypes
);

////////////////////////////////// Red Black Models ///////////////////////////////////////////////
db.RedBlack = require("./redBlack/redBlack.js")(sequelize, DataTypes);
db.RedBlackBet = require("./redBlack/redBlackBet.js")(sequelize, DataTypes);
db.RedBlackRoom = require("./redBlack/redBlackRoom.js")(sequelize, DataTypes);
db.RedBlackMap = require("./redBlack/redBlackMap.js")(sequelize, DataTypes);

////////////////////////////////// Jackpot Models ///////////////////////////////////////////////
db.Jackpot = require("./jackpot/jackpot.js")(sequelize, DataTypes);
db.JackpotBet = require("./jackpot/jackpotBet.js")(sequelize, DataTypes);
db.JackpotRoom = require("./jackpot/jackpotRoom.js")(sequelize, DataTypes);
db.JackpotMap = require("./jackpot/jackpotMap.js")(sequelize, DataTypes);

////////////////////////////////// Jhandi Munda Models ///////////////////////////////////////////////
db.JhandiMunda = require('./jhandiMunda/jhandiMunda.js')(sequelize, DataTypes);
db.JhandiMundaBet = require('./jhandiMunda/jhandiMundaBet.js')(sequelize, DataTypes);
db.JhandiMundaRoom = require('./jhandiMunda/jhandiMundaRoom.js')(sequelize, DataTypes);
db.JhandiMundaMap = require('./jhandiMunda/jhandiMundaMap.js')(sequelize, DataTypes);

////////////////////////////////// Three Dice Models ///////////////////////////////////////////////
db.ThreeDice = require('./threeDice/threeDice.js')(sequelize, DataTypes);
db.ThreeDiceBet = require('./threeDice/threeDiceBet.js')(sequelize, DataTypes);
db.ThreeDiceMap = require('./threeDice/threeDiceMap.js')(sequelize, DataTypes);
db.ThreeDiceRoom = require('./threeDice/threeDiceRoom.js')(sequelize, DataTypes);



////////////////////////////////// Animal Roulette Models ///////////////////////////////////////////////
db.AnimalRoulette = require('./animalRoulette/animalRoulette.js')(sequelize, DataTypes);
db.AnimalRouletteBet = require('./animalRoulette/animalRouletteBet.js')(sequelize, DataTypes);
db.AnimalRouletteRoom = require('./animalRoulette/animalRouletteRoom.js')(sequelize, DataTypes);
db.AnimalRouletteMap = require('./animalRoulette/animalRouletteMap.js')(sequelize, DataTypes);

////////////////////////////////// Bacarrat Models ///////////////////////////////////////////////
db.Bacarrat = require("./bacarrat/bacarrat.js")(sequelize, DataTypes);
db.BacarratBet = require("./bacarrat/bacarratBet.js")(sequelize, DataTypes);
db.BacarratRoom = require("./bacarrat/bacarratRoom.js")(sequelize, DataTypes);
db.BacarratMap = require("./bacarrat/bacarratMap.js")(sequelize, DataTypes);

////////////////////////////////// Teenpatti Models ///////////////////////////////////////////////
db.Teenpatti = require("./teenpatti/teenpatti.js")(sequelize, DataTypes);
db.TeenpattiCard = require("./teenpatti/teenpattiCard.js")(
  sequelize,
  DataTypes
);
// db.TeenpattiCardDrop = require('./teenpatti/teenpattiCardDrop.js')(sequelize, DataTypes);
db.TeenpattiLog = require("./teenpatti/teenpattiLog.js")(sequelize, DataTypes);
db.TeenpattiTable = require("./teenpatti/teenpattiTable.js")(sequelize,DataTypes);
db.TeenpattiTableMaster = require("./teenpatti/teenpattiTableMaster.js")(sequelize,DataTypes);
db.TeenpattiTableUser = require("./teenpatti/teenpattiTableUser.js")(sequelize,DataTypes);
db.Slideshow = require("./teenpatti/slideshow.js")(sequelize, DataTypes);

/////////////////////////////////// Main Layout Models ///////////////////////////////////////////////
db.Card = require("./cards/cardModel.js")(sequelize, DataTypes);
db.setting = require("./settingModel.js")(sequelize, DataTypes);
db.statement = require("./statementModel.js")(sequelize, DataTypes);
db.DirectProfitStatement = require("./directProfitStatementModel.js")(
  sequelize,
  DataTypes
);
db.CardRummy = require("./cards/cardRummyModel.js")(sequelize, DataTypes);
db.RobotCard = require("./cards/robotCards.js")(sequelize, DataTypes);
db.ShareWallet = require("./shareWalletModel.js")(sequelize, DataTypes);
db.TipLog = require("./tiplog.js")(sequelize, DataTypes);
db.Gift = require("./gift.js")(sequelize, DataTypes);

////////////////////////////////// User Models ///////////////////////////////////////////////////////
db.user = require("./users/userModel.js")(sequelize, DataTypes);
db.admin = require("./users/adminModel.js")(sequelize, DataTypes);
db.BotUser = require("./users/botUserModel.js")(sequelize, DataTypes);
db.Otp = require("./users/otp.js")(sequelize, DataTypes);

Object.keys(db).forEach((modelName) => {
  if (db[modelName].associate) {
    db[modelName].associate(db);
  }
});

// db.aviator.hasMany(db.user, { foreignKey: 'id' });
// db.user.belongsTo(db.aviator, { foreignKey: 'id' });

// db.sequelize.sync({ force: false })

module.exports = db;
