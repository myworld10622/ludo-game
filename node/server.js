const express = require("express");
require("dotenv").config();
const app = express();
const server = require("http").Server(app);
const io = require("socket.io")(server, { cors: { origin: "*" } });
// const bodyParser = require('body-parser')
var dateTime = require("node-datetime");
var request = require("request");
const db = require("./models");
const gameRoutes = require("./routes/app");
const cors = require("cors");
const Sequelize = require("sequelize");
const { HTTP_OK } = require("./constants/responseCode.js");
const errorHandler = require("./error/errorHandler.js");
const cron = require("node-cron");
const {
  handleDailyBonus,
  checkPaymentStatus,
  startInFiveMinuteTournament,
} = require("./services/cronJobsService.js");
const { BASE_URL } = require("./constants/index.js");

// allow cors
app.use(cors({ origin: "*" }));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use("/uploads", express.static("uploads"));
// app.use(bodyParser.urlencoded({extended: true}))
// const BASE_URL = process.env.BASE_URL || 'https://demo.androappstech.in';

var dt = dateTime.create();
var current_datetime = dt.format("Y-m-d H:M:S");
//routers
// const aviatorRouter = require('./routes/aviatorRouter');
// aviatorRouter.addAviator
//use

// app.use('/api/websocket', router)
// app.use('/api', aviatorRouter)
app.use("/api", gameRoutes);
app.get("/health", (req, res) => {
  res.status(HTTP_OK).json({ message: "Letscard Socket!!!" });
});
app.post("/event", (req, res) => {
  console.log("received SmartFOX" + JSON.stringify(req.body));
  res.status(HTTP_OK).json({ message: "Data Received" });
});
//api

//socket instances
const ander_bahar_socket = io.of("/ander_bahar");
const seven_up_down_socket = io.of("/seven_up_down");
const ander_bahar_plus_socket = io.of("/ander_bahar_plus");
const animal_roulette_socket = io.of("/animal_roulette");
const icon_roulette_socket = io.of("/icon_roulette");
const baccarat_socket = io.of("/baccarat");
const car_roulette_socket = io.of("/car_roulette");
const tripple_fun_socket = io.of("/tripple_fun");
const color_prediction_socket = io.of("/color_prediction");
const color_prediction_1min_socket = io.of("/color_prediction_1min");
const color_prediction_3min_socket = io.of("/color_prediction_3min");
const color_prediction_5min_socket = io.of("/color_prediction_5min");
const dragon_tiger_socket = io.of("/dragon_tiger");
const golden_wheel_socket = io.of("/golden_wheel");
const head_tail_socket = io.of("/head_tail");
const jackpot_socket = io.of("/jackpot");
const jhandi_munda_socket = io.of("/jhandi_munda");
const three_dice_socket = io.of("/three_dice");
const red_black_socket = io.of("/red_black");
const roulette_socket = io.of("/roulette");
const target_socket = io.of("/target");
const lottery_socket = io.of("/lottery");
const ludo_old_socket = io.of("/ludo_old");
const slot_game_socket = io.of("/slot_game");

const teenpatti_socket = io.of("/teenpatti");
const rummy_socket = io.of("/rummy");
const rummy_cacheta_socket = io.of("/rummy_cacheta");
const rummy_pool_socket = io.of("/rummy_pool");
const rummy_deal_socket = io.of("/rummy_deal");
const rummy_tournament_socket = io.of("/rummy_tournament");
const ludo_socket = io.of("/ludo");
const poker_socket = io.of("/poker");
const betreeno_socket = io.of("/betreeno");

const aviator_socket = io.of("/aviator");

function timer(callback, delay) {
  // console.log(callback)
  var id,
    started,
    remaining = delay,
    running;

  this.start = function () {
    running = true;
    started = new Date();
    id = setTimeout(callback, remaining);
  };

  this.pause = function () {
    running = false;
    clearTimeout(id);
    remaining -= new Date() - started;
  };

  this.stop = function () {
    running = false;
    clearTimeout(id);
    remaining = 0;
  };

  this.getTimeLeft = function () {
    // if (running) {
    //     this.pause()
    //     this.start()
    // }
    var remain = remaining - (new Date() - started);
    remain = remain > 0 ? remain : 0;
    // console.log("remaining", remain);
    // if(remain==0){
    //   clearTimeout(id)
    //   remaining = 0
    // }
    // console.log("this ",this);
    return remain;
  };

  this.getStateRunning = function () {
    return running;
  };

  this.start();
}

//Sockets
require("./sockets/anderBaharSocket.js")(ander_bahar_socket, request, BASE_URL);
require("./sockets/anderBaharPlusSocket.js")(
  ander_bahar_plus_socket,
  request,
  BASE_URL
);
require("./sockets/sevenUpDownSocket.js")(
  seven_up_down_socket,
  request,
  BASE_URL
);
require("./sockets/animalRouletteSocket.js")(
  animal_roulette_socket,
  request,
  BASE_URL
);
require("./sockets/baccaratSocket.js")(baccarat_socket, request, BASE_URL);
require("./sockets/carRouletteSocket.js")(
  car_roulette_socket,
  request,
  BASE_URL
);

require("./sockets/trippleFunSocket.js")(tripple_fun_socket, request, BASE_URL);

require("./sockets/colorPredictionSocket.js")(
  color_prediction_socket,
  request,
  BASE_URL
);
require("./sockets/colorPrediction1MinSocket.js")(
  color_prediction_1min_socket,
  request,
  BASE_URL
);
require("./sockets/colorPrediction3MinSocket.js")(
  color_prediction_3min_socket,
  request,
  BASE_URL
);
require("./sockets/colorPrediction5MinSocket.js")(
  color_prediction_5min_socket,
  request,
  BASE_URL
);
require("./sockets/dragonTigerSocket.js")(
  dragon_tiger_socket,
  request,
  BASE_URL
);
require("./sockets/goldenWheelSocket.js")(
  golden_wheel_socket,
  request,
  BASE_URL
);
require("./sockets/headTailSocket.js")(head_tail_socket, request, BASE_URL);
require("./sockets/jackpotSocket.js")(jackpot_socket, request, BASE_URL);
require("./sockets/jhandiMundaSocket.js")(
  jhandi_munda_socket,
  request,
  BASE_URL
);
require("./sockets/redBlackSocket.js")(red_black_socket, request, BASE_URL);
require("./sockets/rouletteSocket.js")(roulette_socket, request, BASE_URL);
require("./sockets/targetSocket.js")(target_socket, request, BASE_URL);
require("./sockets/slotGameSocket.js")(slot_game_socket, request, BASE_URL);
// require('./sockets/lotterySocket.js')(lottery_socket, request, BASE_URL, dateTime)

require("./sockets/teenpattiSocket.js")(
  teenpatti_socket,
  request,
  timer,
  BASE_URL
);
require("./sockets/rummySocket.js")(rummy_socket, request, timer, BASE_URL);
require("./sockets/rummyCachetaSocket.js")(
  rummy_cacheta_socket,
  request,
  timer,
  BASE_URL
);
require("./sockets/rummyPoolSocket.js")(
  rummy_pool_socket,
  request,
  timer,
  BASE_URL
);
require("./sockets/rummyDealSocket.js")(
  rummy_deal_socket,
  request,
  timer,
  BASE_URL
);
const rummyTournamentSocket = require("./sockets/rummyTournamentSocket.js")(
  rummy_tournament_socket,
  request,
  timer
);
require("./sockets/ludoOldSocket.js")(
  ludo_old_socket,
  request,
  timer,
  BASE_URL
);
require("./sockets/ludoSocket.js")(ludo_socket, request, timer, BASE_URL);
require("./sockets/pokerSocket.js")(poker_socket, request, timer, BASE_URL);
require("./sockets/betreenoSocket.js")(
  betreeno_socket,
  request,
  timer,
  BASE_URL
);

require("./sockets/aviatorSocket.js")(
  aviator_socket,
  Sequelize,
  db.sequelize,
  dateTime
);

// Define the task to run at 12:05 AM every day
// cron.schedule('5 0 * * *', () => {
//   handleDailyBonus();
// });

// Check Payment Status in every 52 second
cron.schedule("*/53 * * * * *", () => {
  checkPaymentStatus();
});

const tournamentsArray = {};
cron.schedule("* * * * *", () => {
  startInFiveMinuteTournament(tournamentsArray, rummyTournamentSocket);
});

//port

const PORT = process.env.PORT || 3002;

//server
process.setMaxListeners(0);
server.listen(PORT, () => {
  console.log(`server is runnig ${PORT}`);

  db.sequelize
    .authenticate()
    .then(() => console.log("Database connected successfully."))
    .catch((err) => console.error("Unable to connect to the database:", err));

  db.sequelize.options.logging = false;

  db.sequelize
    .sync()
    .then(() => {
      console.log("DB Synced.");
    })
    .catch((err) => {
      console.log("Failed to sync db: " + err.message);
    });
});
