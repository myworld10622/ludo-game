const { TRIPPLE_FUN_BETS } = require("../constants");
const db = require("../models");
const { getAttributes } = require("../utils/util");
const userService = require("./userService");
const TrippleFun = db.TrippleFun;
const TrippleFunBet = db.TrippleFunBet;
const TrippleFunRoom = db.TrippleFunRoom;
const TrippleFunMap = db.TrippleFunMap;
const Card = db.Card;
const { Op, fn, col, where } = require("sequelize");

class TrippleFunService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await TrippleFun.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error("Game not found");
      }
      return game;
    } catch (error) {
      console.log(error);
      throw new Error("Error fetching Tripple Fun");
    }
  }

  async create(data) {
    try {
      return await TrippleFun.create(data);
    } catch (error) {
      console.log(error);
      throw new Error("Error creating Tripple Fun");
    }
  }

  async update(id, data) {
    try {
      await TrippleFun.update(data, {
        where: { id },
      });
    } catch (error) {
      console.log(error);
      throw new Error("Error updating data");
    }
  }

  async getRooms(roomId = "", userId = "", attributes = []) {
    const attributeOptions = getAttributes(attributes);
    const options = {
      ...attributeOptions,
      order: [["id", "DESC"]],
    };
    if (roomId) {
      options.where = {
        id: roomId,
      };
    }
    let rooms = await TrippleFunRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { tripple_fun_room_id: roomId });
    }

    return rooms;
  }

  async getActiveGameOnTable(roomId = "", attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const options = {
        ...attributeOptions,
        limit: 1,
        order: [["id", "DESC"]],
      };
      if (roomId) {
        options.where = {
          room_id: roomId,
        };
      }
      let activeGame = await TrippleFun.findAll(options);

      return activeGame;
    } catch (error) {
      console.log(error);
      throw new Error("Error while place bet");
    }
  }

  async getLastThreeGames(roomId = "", attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const options = {
        ...attributeOptions,
        limit: 3,
        order: [["id", "DESC"]],
      };
      if (roomId) {
        options.where = {
          room_id: roomId,
        };
      }
      let activeGame = await TrippleFun.findAll(options);

      return activeGame;
    } catch (error) {
      console.log(error);
      throw new Error("Error while place bet");
    }
  }

  async getCards(limit = "") {
    try {
      const options = {
        where: {
          cards: {
            [db.Sequelize.Op.notIn]: ["JKR1", "JKR2"],
          },
        },
        order: db.sequelize.random(),
      };
      if (limit) {
        options.limit = limit;
      }
      const cards = await Card.findAll(options);
      return cards;
    } catch (error) {
      console.log(error);
      throw new Error("Error while place bet");
    }
  }

  async lastWinningBet(roomId, limit = 40) {
    try {
      const conditions = {
        status: 1,
      };
      if (roomId) {
        conditions.room_id = roomId;
      }
      const options = {
        limit,
        room_id: roomId,
        order: [["id", "DESC"]],
        where: conditions,
      };
      return await TrippleFun.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Tripple Fun BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await TrippleFunBet.create(data);
      await userService.update(data.user_id, {
        todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`),
      });
      return bet;
    } catch (error) {
      console.log(error);
      throw new Error("Error while place bet");
    }
  }

  async getBetById(id, attributes = []) {
    try {
      const options = getAttributes(attributes);
      const bet = await TrippleFunBet.findByPk(id, options);
      if (!bet) {
        throw new Error("Bet not found");
      }
      return bet;
    } catch (error) {
      console.log(error);
      throw new Error("Error fetching Tripple Fun");
    }
  }

  async viewBet(userId = "", gameId = "", bet = "", betId = "", limit = "") {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.tripple_fun_id = gameId;
      }
      if (bet !== "") {
        conditionPayload.bet = bet;
      }
      if (betId) {
        conditionPayload.id = betId;
      }
      // apply conditions
      const options = {
        where: conditionPayload,
        order: [["id", "DESC"]],
      };
      if (limit) {
        options.limit = limit;
      }

      return await TrippleFunBet.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error fetching Data");
    }
  }

  async viewAllBetsByGameId(gameId, userId = "", attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const conditionPayload = {
        tripple_fun_id: gameId,
        [Op.and]: [where(fn("length", col("bet")), 1)],
      };
      if (userId) {
        conditionPayload.user_id = userId;
      }
      // apply conditions
      const options = {
        ...attributeOptions,
        where: conditionPayload,
        order: [["id", "DESC"]],
      };

      return await TrippleFunBet.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error fetching Data");
    }
  }

  async viewAllPannaBetsByGameId(gameId, userId = "", attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);

      const conditionPayload = {
        tripple_fun_id: gameId,
        // Add condition for bet length === 3
        [Op.and]: [where(fn("length", col("bet")), 3)],
      };

      if (userId) {
        conditionPayload.user_id = userId;
      }

      const options = {
        ...attributeOptions,
        where: conditionPayload,
        order: [["id", "DESC"]],
      };

      return await TrippleFunBet.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error fetching Data");
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await TrippleFunBet.findAll({
        attributes: ["*", "tbl_tripple_fun.room_id"],
        include: [
          {
            model: TrippleFun,
            attributes: [],
          },
        ],
        where: {
          user_id: userId,
        },
        order: [["added_date", "DESC"]],
        raw: true,
        nest: true,
        subQuery: false,
      });

      return bets;
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetching data");
    }
  }

  async gameHistory(roomId, limit = 10) {
    try {
      const queryOptions = {
        where: {
          status: 1,
        },
        order: [["id", "DESC"]],
      };
      if (roomId) {
        queryOptions.where.room_id = roomId;
      }
      if (limit) {
        queryOptions.limit = limit;
      }
      return await TrippleFun.findAll(queryOptions);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetching data");
    }
  }

  async myHistory(userId, limit) {
    try {
      const queryOptions = {
        attributes: ["*", "tbl_tripple_fun.status"],
        include: [
          {
            model: TrippleFun,
            attributes: [],
          },
        ],
        where: {
          user_id: userId,
        },
        order: [["id", "DESC"]],
        raw: true,
        nest: true,
        subQuery: false,
      };
      if (limit) {
        queryOptions.limit = limit;
      }
      return await TrippleFunBet.findAll(queryOptions);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetching data");
    }
  }

  async updateBet(id, data) {
    try {
      await TrippleFunBet.update(data, {
        where: { id },
      });
    } catch (error) {
      console.log(error);
      throw new Error("Error updating data");
    }
  }

  async checkBetNumberPlacedByUser(gameId, userId, bet) {
    try {
      return await TrippleFunBet.findOne({
        attributes: ["id"],
        where: {
          tripple_fun_id: gameId,
          bet,
          user_id: userId,
        },
      });
    } catch (error) {
      console.log(error);
      throw new Error("Error while check bet");
    }
  }

  async getRoomOnlineUsers(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_roulette WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await TrippleFunBet.findOne({
        attributes: [
          [db.Sequelize.fn("COUNT", db.Sequelize.col("id")), "online"],
        ],
        where: {
          tripple_fun_id: db.Sequelize.literal(`(${subquery})`),
        },
        raw: true,
      });

      return result ? result.online : 0;
    } catch (error) {
      console.error("Error fetching online count:", error);
    }
  }

  // Like Helper
  async getBetDataByBets(bets) {
    const betData = {
      totalBetAmount: 0,
      zeroAmount: 0,
      oneAmount: 0,
      twoAmount: 0,
      threeAmount: 0,
      fourAmount: 0,
      fiveAmount: 0,
      sixAmount: 0,
      sevenAmount: 0,
      eightAmount: 0,
      nineAmount: 0,
      // greenAmount: 0,
      // violetAmount: 0,
      // redAmount: 0,
      // smallAmount: 0,
      // bigAmount: 0,
    };
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == 0) {
        betData.zeroAmount += bet.amount;
      } else if (bet.bet == 1) {
        betData.oneAmount += bet.amount;
      } else if (bet.bet == 2) {
        betData.twoAmount += bet.amount;
      } else if (bet.bet == 3) {
        betData.threeAmount += bet.amount;
      } else if (bet.bet == 4) {
        betData.fourAmount += bet.amount;
      } else if (bet.bet == 5) {
        betData.fiveAmount += bet.amount;
      } else if (bet.bet == 6) {
        betData.sixAmount += bet.amount;
      } else if (bet.bet == 7) {
        betData.sevenAmount += bet.amount;
      } else if (bet.bet == 8) {
        betData.eightAmount += bet.amount;
      } else if (bet.bet == 9) {
        betData.nineAmount += bet.amount;
      }
    }

    return betData;
  }

  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Tripple Fun MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        tripple_fun_id: gameId,
        card,
      };
      return await TrippleFunMap.create(payload);
    } catch (error) {
      console.log(error);
      throw new Error("Error creating Tripple Fun Map");
    }
  }

  async getGameCards(gameId) {
    try {
      return TrippleFunMap.findAll({
        where: {
          tripple_fun_id: gameId,
        },
      });
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }
}

module.exports = new TrippleFunService();
