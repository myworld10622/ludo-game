const { DRAGON, TIGER, TIE, DRAGON_OR_TIGET_MULTIPLY, TIE_MULTIPLY, SEVEN_UP_GAME } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const SevenUp = db.SevenUp;
const SevenUpBet = db.SevenUpBet;
const SevenUpRoom = db.SevenUpRoom;
const SevenUpMap = db.SevenUpMap;
const Card = db.Card;

class SevenUpService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await SevenUp.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      throw new Error(error);
    }
  }

  async create(data) {
    try {
      return await SevenUp.create(data);
    } catch (error) {
      throw new Error(error);
    }
  }

  async update(id, data) {
    try {
      await SevenUp.update(data, {
        where: { id }
      });
    } catch (error) {
      throw new Error(error);
    }
  }

  async getRooms(roomId = '', userId = '', attributes = []) {
    const attributeOptions = getAttributes(attributes);
    const options = {
      ...attributeOptions,
      order: [['id', 'DESC']]
    };
    if (roomId) {
      options.where = {
        id: roomId
      };
    }
    let rooms = await SevenUpRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { seven_up_room_id: roomId })
    }

    return rooms;
  }

  async getActiveGameOnTable(roomId = '', attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const options = {
        ...attributeOptions,
        limit: 1,
        order: [['id', 'DESC']]
      };
      if (roomId) {
        options.where = {
          room_id: roomId
        };
      }
      let activeGame = await SevenUp.findAll(options);

      return activeGame;
    } catch (error) {
      throw new Error(error);
    }
  }

  async getCards(limit = '') {
    try {
      const options = {
        where: {
          cards: {
            [db.Sequelize.Op.notIn]: ['JKR1', 'JKR2']
          }
        },
        order: db.sequelize.random()
      }
      if (limit) {
        options.limit = limit;
      }
      const cards = await Card.findAll(options);
      return cards;
    } catch (error) {
      throw new Error(error);
    }
  }

  async lastWinningBet(roomId, limit = 20) {
    try {
      const conditions = {
        status: 1
      }
      if (roomId) {
        conditions.room_id = roomId;
      }
      const options = {
        limit,
        room_id: roomId,
        where: conditions,
        order: [["id", "DESC"]]
      }
      return await SevenUp.findAll(options);
    } catch (error) {
      throw new Error(error);
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      downBetAmount: 0,
      upBetAmount: 0,
      tieBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == SEVEN_UP_GAME.down) {
        if (calculation) {
          betData.downBetAmount += (+bet.amount * SEVEN_UP_GAME.upDownMultiply);
        } else {
          betData.downBetAmount += (+bet.amount)
        }
      }
      if (bet.bet == SEVEN_UP_GAME.up) {
        if (calculation) {
          betData.upBetAmount += (+bet.amount * SEVEN_UP_GAME.upDownMultiply);
        } else {
          betData.upBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == SEVEN_UP_GAME.tie) {
        if (calculation) {
          betData.tieBetAmount += (+bet.amount * SEVEN_UP_GAME.upDownTieMultiply);
        } else {
          betData.tieBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await SevenUpBet.create(data);
      await userService.update(data.user_id, { todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`) })
      return bet;
    } catch (error) {
      throw new Error(error);
    }
  }

  async getBetById(id, attributes = []) {
    try {
      const options = getAttributes(attributes)
      const bet = await SevenUpBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      throw new Error(error);
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.seven_up_id = gameId;
      }
      if (bet !== '') {
        conditionPayload.bet = bet;
      }
      if (betId) {
        conditionPayload.id = betId;
      }
      // apply conditions
      const options = {
        where: conditionPayload,
        order: [['id', 'DESC']]
      };
      if (limit) {
        options.limit = limit;
      }

      return await SevenUpBet.findAll(options);
    } catch (error) {
      throw new Error(error);
    }
  }

  async getRoomOnline(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_seven_up WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await SevenUpBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          seven_up_id: db.Sequelize.literal(`(${subquery})`)
        },
        raw: true
      });

      return result ? result.online : 0;
    } catch (error) {
      throw new Error(error);
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await SevenUpBet.findAll({
        attributes: [
          '*',
          'seven_up.room_id'
        ],
        include: [{
          model: SevenUp,
          as: "seven_up",
          attributes: [],
        }],
        where: {
          user_id: userId
        },
        order: [['added_date', 'DESC']],
        raw: true,
        nest: true,
        subQuery: false
      });

      return bets;
    } catch (error) {
      throw new Error(error);
    }
  }

  async updateBet(id, data) {
    try {
      await SevenUpBet.update(data, {
        where: { id }
      });
    } catch (error) {
      throw new Error(error);
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  /////////////////////////////  MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        seven_up_id: gameId,
        card
      }
      return await SevenUpMap.create(payload);
    } catch (error) {
      throw new Error(error);
    }
  }

  async getGameCards(gameId) {
    try {
      return SevenUpMap.findAll({
        where: {
          seven_up_id: gameId
        }
      })
    } catch (error) {
      throw new Error(error);
    }
  }

}

module.exports = new SevenUpService();
