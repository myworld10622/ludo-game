const {
  HTTP_NOT_ACCEPTABLE,
  HTTP_SWITCH_PROTOCOL,
  HTTP_WIN,
  HTTP_LOOSER,
  GAMES,
  HTTP_NOT_FOUND,
  HTTP_ALREADY_USED,
  HTTP_NO_CONTENT,
  HTTP_SERVER_ERROR,
  TRIPPLE_FUN_FOR_BET,
  TRIPPLE_FUN_BETS,
  TRIPPLE_FUN_WINNING_PRICE,
  TRIPPLE_FUN_TIME_FOR_START_NEW_GAME,
} = require("../../constants");
const adminService = require("../../services/adminService");
const trippleFunService = require("../../services/trippleFunService");
const {
  getAllPredefinedBots,
  getByConditions,
} = require("../../services/userService");
const { UserWalletService } = require("../../services/walletService");
var dateFormat = require("date-format");
const {
  errorResponse,
  successResponse,
  successResponseWitDynamicCode,
  insufficientAmountResponse,
} = require("../../utils/response");
const db = require("../../models");
const {
  getRandomNumber,
  getAmountByPercentage,
  getRoundNumber,
  getRandomFromFromArray,
} = require("../../utils/util");
const userWallet = new UserWalletService();

class TrippleFunController {
  constructor() {
    this.makeWinner = this.makeWinner.bind(this);
    this.placeBet = this.placeBet.bind(this);
    this.declareWinner = this.declareWinner.bind(this);
    this.declareThreeRoundWinner = this.declareThreeRoundWinner.bind(this);
  }

  async placeBet(req, res) {
    try {
      const { game_id, user_id, bet, amount } = req.body;
      if (game_id % 3 !== 1 && bet > 9) {
        return errorResponse(
          res,
          "Please wait for round one to place panna bets",
          HTTP_NOT_ACCEPTABLE
        );
      }
      const user = req.user;
      if (user.wallet < amount && amount > 0) {
        return insufficientAmountResponse(res);
      }
      const setting = await adminService.getById(1, ["tripple_fun_withdraw"]);
      if (user.wallet < setting.tripple_fun_withdraw) {
        return insufficientAmountResponse(res, setting.tripple_fun_withdraw);
      }

      // game
      const game = await trippleFunService.getById(game_id);
      if (!game) {
        return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
      }
      if (game.status) {
        return errorResponse(
          res,
          "Can't Place Bet, Game Has Been Ended",
          HTTP_NO_CONTENT
        );
      }

      // const checkBet = await trippleFunService.checkBetNumberPlacedByUser(
      //   game_id,
      //   user_id,
      //   bet
      // );
      // if (checkBet) {
      //   return errorResponse(
      //     res,
      //     "Already Added Bet On Same Number",
      //     HTTP_ALREADY_USED
      //   );
      // }

      const payload = {
        tripple_fun_id: game_id,
        user_id,
        bet,
        amount,
      };

      const betData = await trippleFunService.createBet(payload);
      if (!betData) {
        return errorResponse(res, "Something Wents Wrong", HTTP_SERVER_ERROR);
      }
      // Not wait for calculation of wallet
      userWallet.minusUserWallet(user_id, amount, GAMES.trippleFun, betData);
      const responseData = {
        bet_id: betData.id,
        wallet: user.wallet - amount,
      };
      return successResponse(res, responseData);
    } catch (error) {
      console.log(error);
      return errorResponse(res, error.message, HTTP_SERVER_ERROR);
    }
  }

  async getResult(req, res) {
    try {
      const { game_id, user_id } = req.body;
      const game = await trippleFunService.getById(game_id);
      if (!game) {
        return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
      }
      const betData = await trippleFunService.viewAllBetsByGameId(
        game_id,
        user_id,
        ["id", "user_amount", "amount"]
      );
      let winAmount = 0;
      let betAmount = 0;
      if (Array.isArray(betData) && betData.length == 0) {
        const responsePayload = {
          win_amount: winAmount,
          bet_amount: betAmount,
          diff_amount: winAmount - betAmount,
          message: "No Bet",
          code: HTTP_SWITCH_PROTOCOL,
        };
        return successResponseWitDynamicCode(res, responsePayload);
      }
      for (let index = 0; index < betData.length; index++) {
        const element = betData[index];
        winAmount += element.user_amount;
        betAmount += element.amount;
      }

      const responsePayload = {
        win_amount: winAmount,
        bet_amount: betAmount,
        diff_amount: winAmount - betAmount,
      };

      if (responsePayload.diff_amount > 0) {
        responsePayload.message = "You Win";
        responsePayload.code = HTTP_WIN;
      } else {
        responsePayload.message = "You Loss";
        responsePayload.code = HTTP_LOOSER;
      }
      return successResponseWitDynamicCode(res, responsePayload);
    } catch (error) {
      return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
    }
  }

  async walletHistory(req, res) {
    try {
      const { user_id } = req.body;
      const walletHistory = await trippleFunService.walletHistory(user_id);
      const setting = await adminService.setting(["min_redeem"]);
      const responsePayload = {
        GameLog: walletHistory,
        MinRedeem: setting.min_redeem,
      };
      return successResponse(res, responsePayload);
    } catch (error) {
      return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
    }
  }

  async gameHistory(req, res) {
    try {
      const gameHistory = await trippleFunService.gameHistory(1, 50);
      if (Array.isArray(gameHistory) && gameHistory.length == 0) {
        return errorResponse(res, "No Logs", HTTP_NOT_ACCEPTABLE);
      }
      const responsePayload = {
        last_winning: gameHistory,
      };
      return successResponse(res, responsePayload);
    } catch (error) {
      return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
    }
  }

  async myHistory(req, res) {
    try {
      const { user_id } = req.body;
      const myHistory = await trippleFunService.myHistory(user_id, 150);
      if (Array.isArray(myHistory) && myHistory.length == 0) {
        return errorResponse(res, "No logs", HTTP_NOT_ACCEPTABLE);
      }
      const responsePayload = {
        game_data: myHistory,
      };
      return successResponse(res, responsePayload);
    } catch (error) {
      return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
    }
  }

  async makeWinner(userId, betId, amount, comission, gameId) {
    try {
      const adminComissionAmount = await getAmountByPercentage(
        amount,
        comission
      );
      const userWinningAmount = getRoundNumber(
        amount - adminComissionAmount,
        2
      );
      const dragonTigerBetPayload = {
        winning_amount: amount,
        user_amount: userWinningAmount,
        comission_amount: adminComissionAmount,
      };
      // Update bet
      trippleFunService.updateBet(betId, dragonTigerBetPayload);

      // Update Dragon Tiger
      const dragonTigerPayload = {
        winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
        user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
        comission_amount: db.sequelize.literal(
          `comission_amount + ${adminComissionAmount}`
        ),
      };

      trippleFunService.update(gameId, dragonTigerPayload);

      // Get Bet to check amount deducted from which wallet
      const dragonTigerBet = await trippleFunService.getBetById(betId, [
        "id",
        "minus_unutilized_wallet",
        "minus_winning_wallet",
        "minus_bonus_wallet",
      ]);
      // Add to Wallet
      userWallet.plusUserWallet(
        userId,
        betId,
        userWinningAmount,
        adminComissionAmount,
        GAMES.trippleFun,
        dragonTigerBet
      );
    } catch (error) {
      console.log(error);
    }
  }

  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// SOCKET FUNCTIONS /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////
  // Create Game From Socket
  async createTrippleFunGame() {
    try {
      const rooms = await trippleFunService.getRooms("", "", ["id"]);
      if (rooms && Array.isArray(rooms) && rooms.length > 0) {
        for (let index = 0; index < rooms.length; index++) {
          const room = rooms[index];
          const gameData = await trippleFunService.getActiveGameOnTable(
            room.id,
            ["id", "status"]
          );
          // If Game Not found OR Game is ended then create New Game
          if (
            (Array.isArray(gameData) && gameData.length === 0) ||
            (gameData.length > 0 && gameData[0].status === 1)
          ) {
            trippleFunService.create({ room_id: room.id, card: "" });
            console.log("Prediction Created Successfully");
            return;
          }
        }
      } else {
        console.log("No Rooms Available");
      }
    } catch (error) {
      console.log(error);
    }
  }

  // Declare Winner From Socket
  async declareWinner() {
    try {
      const rooms = await trippleFunService.getRooms("", "", ["id"]);
      if (rooms && Array.isArray(rooms) && rooms.length > 0) {
        for (let index = 0; index < rooms.length; index++) {
          const room = rooms[index];
          const gameData = await trippleFunService.getActiveGameOnTable(
            room.id,
            ["id", "status", "added_date"]
          );

          if (gameData[0].status === 1 && gameData[0].id % 3 === 0) {
            this.declareThreeRoundWinner();
          }
          // If game is on going
          if (Array.isArray(gameData) && gameData.length === 0) {
            trippleFunService.create({ room_id: room.id });
            continue;
          }

          if (gameData[0].status === 0) {
            const { numberMultiply } = TRIPPLE_FUN_WINNING_PRICE;
            const addedDateTimestamp =
              new Date(gameData[0].added_date).getTime() / 1000;
            const currentTime = Math.floor(Date.now() / 1000);
            // If betting period is ended then declare winner
            if (addedDateTimestamp + TRIPPLE_FUN_FOR_BET <= currentTime) {
              let totalWinningAmount = 0;
              const gameId = gameData[0].id;
              const bets = await trippleFunService.viewAllBetsByGameId(
                gameId,
                "",
                ["id", "user_id", "bet", "amount"]
              );
              const betData = await trippleFunService.getBetDataByBets(bets);
              const totalBetAmount = betData.totalBetAmount;
              const setting = await adminService.setting([
                "tripple_fun_random",
                "admin_commission",
                "admin_coin",
                "distribute_precent",
              ]);
              const random = setting.tripple_fun_random;
              let minNumber;

              const numArray = [
                "ZERO",
                "ONE",
                "TWO",
                "THREE",
                "FOUR",
                "FIVE",
                "SIX",
                "SEVEN",
                "EIGHT",
                "NINE",
              ];

              if (random === TRIPPLE_FUN_BETS.random) {
                minNumber =
                  numArray[Math.floor(Math.random() * numArray.length)];
              } else if (random === TRIPPLE_FUN_BETS.least || random === 20) {
                const betAmounts = {
                  ZERO: betData.zeroAmount,
                  ONE: betData.oneAmount,
                  TWO: betData.twoAmount,
                  THREE: betData.threeAmount,
                  FOUR: betData.fourAmount,
                  FIVE: betData.fiveAmount,
                  SIX: betData.sixAmount,
                  SEVEN: betData.sevenAmount,
                  EIGHT: betData.eightAmount,
                  NINE: betData.nineAmount,
                };

                for (const key in betAmounts) {
                  betAmounts[key] = betAmounts[key] * numberMultiply;
                }

                if (random === 20 && betData.totalBetAmount > 0) {
                  const distributeAmount = (
                    setting.admin_coin *
                    (setting.distribute_precent / 100)
                  ).toFixed(2);

                  minNumber =
                    numArray.find(
                      (num) =>
                        betAmounts[num] > 0 &&
                        distributeAmount >= betAmounts[num]
                    ) ||
                    Object.keys(betAmounts).reduce((a, b) =>
                      betAmounts[a] < betAmounts[b] ? a : b
                    );
                } else {
                  const minValue = Math.min(...Object.values(betAmounts));
                  const minKeys = Object.keys(betAmounts).filter(
                    (key) => betAmounts[key] === minValue
                  );
                  minNumber =
                    minKeys[Math.floor(Math.random() * minKeys.length)];
                }
              } else {
                minNumber = numArray[random];
              }

              let winningNumber = "";
              let numberMultiplyCalc = "";

              switch (minNumber) {
                case "ZERO":
                  winningNumber = 0;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "ONE":
                  winningNumber = 1;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "TWO":
                  winningNumber = 2;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "THREE":
                  winningNumber = 3;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "FOUR":
                  winningNumber = 4;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "FIVE":
                  winningNumber = 5;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "SIX":
                  winningNumber = 6;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "SEVEN":
                  winningNumber = 7;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "EIGHT":
                  winningNumber = 8;
                  numberMultiplyCalc = numberMultiply;

                  break;
                case "NINE":
                  winningNumber = 9;
                  numberMultiplyCalc = numberMultiply;
                  break;

                default:
                  winningNumber = "";
                  numberMultiplyCalc = "";
                  break;
              }

              trippleFunService.createMap(gameId, winningNumber);

              // Give winning Amount to user
              const winnerBets = bets.filter((bet) => bet.bet == winningNumber);
              const comission = setting.admin_commission;
              if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                for (let j = 0; j < winnerBets.length; j++) {
                  const winnerBet = winnerBets[j];
                  const userId = winnerBet.user_id;
                  const betId = winnerBet.id;
                  const amount = winnerBet.amount * numberMultiplyCalc;
                  totalWinningAmount += amount;
                  this.makeWinner(userId, betId, amount, comission, gameId);
                }
              } else {
                console.log("No Winning Bet Found");
              }

              const now = new Date();

              const endDateTime = dateFormat.asString(
                "yyyy-MM-dd hh:mm:ss",
                new Date(
                  now.getTime() + TRIPPLE_FUN_TIME_FOR_START_NEW_GAME * 1000
                )
              );

              const updatePayload = {
                status: 1,
                winning: winningNumber,
                total_amount: totalBetAmount,
                admin_profit: totalBetAmount - totalWinningAmount,
                end_datetime: endDateTime,
                random,
              };
              await trippleFunService.update(gameData[0].id, updatePayload);
              // If admin profit is in positive or nagative then log this
              if (updatePayload.admin_profit != 0) {
                userWallet.directAdminProfitStatement(
                  GAMES.trippleFun,
                  updatePayload.admin_profit,
                  gameData[0].id
                );
              }

              return updatePayload;
            } else {
              console.log("No tripple Fun Game to start");
            }
          } else {
            const addedDateTimestamp =
              new Date(gameData[0].added_date).getTime() / 1000;
            const currentTime = Math.floor(Date.now() / 1000);
            if (addedDateTimestamp < currentTime) {
              const onlineUsers = await getByConditions({
                tripple_fun_room_id: room.id,
              });
              if (Array.isArray(onlineUsers) && onlineUsers.length > 0) {
                trippleFunService.create({ room_id: room.id });
              } else {
                console.log("No Online User Found");
              }
            } else {
              console.log("No Game to end");
            }
          }
        }
      }
    } catch (error) {
      console.log(error);
    }
  }

  // Declare all three round winner new

  async declareThreeRoundWinner() {
    try {
      const lastThreeGameData = await trippleFunService.getLastThreeGames(1, [
        "id",
        "status",
        "random",
        "winning",
      ]);

      if (
        !lastThreeGameData ||
        lastThreeGameData[0].id % 3 !== 0 ||
        lastThreeGameData.length < 3 ||
        !lastThreeGameData.every((bet) => bet.status === 1)
      ) {
        return;
      }

      const rooms = await trippleFunService.getRooms("", "", ["id"]);
      if (rooms && Array.isArray(rooms) && rooms.length > 0) {
        for (let index = 0; index < rooms.length; index++) {
          // if (gameData[0].status === 0) {
          const { numberMultiply } = TRIPPLE_FUN_WINNING_PRICE;

          let totalWinningAmount = 0;
          const gameId = lastThreeGameData[2].id;
          const bets = await trippleFunService.viewAllPannaBetsByGameId(
            gameId,
            "",
            ["id", "user_id", "bet", "amount"]
          );
          const betData = await trippleFunService.getBetDataByBets(bets);
          const totalBetAmount = betData.totalBetAmount;
          const setting = await adminService.setting([
            "tripple_fun_random",
            "admin_commission",
            "admin_coin",
            "distribute_precent",
          ]);
          const random = setting.tripple_fun_random;
          let minNumber;

          const numArray = [
            "ZERO",
            "ONE",
            "TWO",
            "THREE",
            "FOUR",
            "FIVE",
            "SIX",
            "SEVEN",
            "EIGHT",
            "NINE",
          ];

          if (random === TRIPPLE_FUN_BETS.random) {
            minNumber = numArray[Math.floor(Math.random() * numArray.length)];
          } else if (random === TRIPPLE_FUN_BETS.least || random === 20) {
            const betAmounts = {
              ZERO: betData.zeroAmount,
              ONE: betData.oneAmount,
              TWO: betData.twoAmount,
              THREE: betData.threeAmount,
              FOUR: betData.fourAmount,
              FIVE: betData.fiveAmount,
              SIX: betData.sixAmount,
              SEVEN: betData.sevenAmount,
              EIGHT: betData.eightAmount,
              NINE: betData.nineAmount,
            };

            for (const key in betAmounts) {
              betAmounts[key] = betAmounts[key] * numberMultiply;
            }

            if (random === 20 && betData.totalBetAmount > 0) {
              const distributeAmount = (
                setting.admin_coin *
                (setting.distribute_precent / 100)
              ).toFixed(2);

              minNumber =
                numArray.find(
                  (num) =>
                    betAmounts[num] > 0 && distributeAmount >= betAmounts[num]
                ) ||
                Object.keys(betAmounts).reduce((a, b) =>
                  betAmounts[a] < betAmounts[b] ? a : b
                );
            } else {
              const minValue = Math.min(...Object.values(betAmounts));
              const minKeys = Object.keys(betAmounts).filter(
                (key) => betAmounts[key] === minValue
              );
              minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
            }
          } else {
            minNumber = numArray[random];
          }

          let winningNumber = Number(
            `${lastThreeGameData[2].winning}${lastThreeGameData[1].winning}${lastThreeGameData[0].winning}`
          );

          let numberMultiplyCalc = numberMultiply;

          await trippleFunService.createMap(gameId, winningNumber);

          // Give winning Amount to user
          const winnerBets = bets.filter((bet) => bet.bet == winningNumber);
          const comission = setting.admin_commission;
          if (Array.isArray(winnerBets) && winnerBets.length > 0) {
            for (let j = 0; j < winnerBets.length; j++) {
              const winnerBet = winnerBets[j];
              const userId = winnerBet.user_id;
              const betId = winnerBet.id;
              const amount = winnerBet.amount * numberMultiplyCalc;
              totalWinningAmount += amount;
              this.makeWinner(userId, betId, amount, comission, gameId);
            }
          } else {
            console.log("No Winning Bet Found");
          }

          const now = new Date();

          const endDateTime = dateFormat.asString(
            "yyyy-MM-dd hh:mm:ss",
            new Date(now.getTime() + TRIPPLE_FUN_TIME_FOR_START_NEW_GAME * 1000)
          );

          const updatePayload = {
            status: 1,
            winning: winningNumber,
            total_amount: totalBetAmount,
            admin_profit: totalBetAmount - totalWinningAmount,
            end_datetime: endDateTime,
            random,
          };
          trippleFunService.update(gameData[0].id, updatePayload);
          // If admin profit is in positive or nagative then log this
          if (updatePayload.admin_profit != 0) {
            userWallet.directAdminProfitStatement(
              GAMES.trippleFun,
              updatePayload.admin_profit,
              gameData[0].id
            );
          }

          // return updatePayload;
          // }
          // else {
          //   const addedDateTimestamp =
          //     new Date(gameData[0].added_date).getTime() / 1000;
          //   const currentTime = Math.floor(Date.now() / 1000);
          //   if (addedDateTimestamp < currentTime) {
          //     const onlineUsers = await getByConditions({
          //       tripple_fun_room_id: room.id,
          //     });
          //     if (Array.isArray(onlineUsers) && onlineUsers.length > 0) {
          //       trippleFunService.create({ room_id: room.id });
          //     } else {
          //       console.log("No Online User Found");
          //     }
          //   } else {
          //     console.log("No Game to end");
          //   }
          // }
        }
      }
    } catch (error) {
      console.log(error);
    }
  }

  // Call from socket
  async getActiveGameSocket(roomId) {
    try {
      // const botUsers = await getAllPredefinedBots();
      const gameData = await trippleFunService.getActiveGameOnTable(roomId, [
        "id",
        "status",
        "added_date",
        "room_id",
        "winning",
        "end_datetime",
        "updated_date",
      ]);
      if (Array.isArray(gameData) && gameData.length > 0) {
        let gameCards = [];
        const gameId = gameData[0].id;
        if (gameData[0].status) {
          gameCards = await trippleFunService.getGameCards(gameId);
        }

        const addedDatetime = new Date(gameData[0].added_date);
        const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
        const currentTimeSec = Math.floor(Date.now() / 1000);

        // Remaining Time
        const timeRemaining =
          addedDatetimeSec + TRIPPLE_FUN_FOR_BET - currentTimeSec;

        const newGameData = [
          {
            id: gameId,
            room_id: gameData[0].room_id,
            winning: gameData[0].winning,
            status: gameData[0].status,
            added_date: gameData[0].added_date,
            main_card: gameData[0].main_card,
            time_remaining: timeRemaining,
            end_datetime: gameData[0].end_datetime,
            updated_date: gameData[0].updated_date,
          },
        ];

        // Get Online Users
        const onlineUsers = await getByConditions({
          tripple_fun_room_id: roomId,
        });
        // game online users
        const online = await trippleFunService.getRoomOnlineUsers(roomId);
        // Get Bets for games
        const bets = await trippleFunService.viewBet("", gameId);
        const {
          zeroAmount,
          oneAmount,
          twoAmount,
          threeAmount,
          fourAmount,
          fiveAmount,
          sixAmount,
          sevenAmount,
          eightAmount,
          nineAmount,
          // greenAmount,
          // violetAmount,
          // redAmount,
          // smallAmount,
          // bigAmount,
        } = await trippleFunService.getBetDataByBets(bets);

        const lastWinnings = await trippleFunService.lastWinningBet(roomId);

        // const randomAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 100)

        const responsePayload = {
          // bot_user: botUsers,
          game_data: newGameData,
          game_cards: gameCards,
          online,
          online_users: onlineUsers,
          last_bet: bets[0],
          my_bet_0: zeroAmount,
          my_bet_1: oneAmount,
          my_bet_2: twoAmount,
          my_bet_3: threeAmount,
          my_bet_4: fourAmount,
          my_bet_5: fiveAmount,
          my_bet_6: sixAmount,
          my_bet_7: sevenAmount,
          my_bet_8: eightAmount,
          my_bet_9: nineAmount,
          // my_bet_10: greenAmount,
          // my_bet_11: violetAmount,
          // my_bet_12: redAmount,
          // my_bet_big: bigAmount,
          // my_bet_small: smallAmount,
          last_winning: lastWinnings,
        };
        return responsePayload;
      } else {
        return false;
      }
    } catch (error) {
      console.log(error);
      throw new Error("Error while get active game");
    }
  }
}

module.exports = new TrippleFunController();
