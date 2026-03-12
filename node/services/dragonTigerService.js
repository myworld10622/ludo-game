const { DRAGON, TIGER, TIE, DRAGON_OR_TIGET_MULTIPLY, TIE_MULTIPLY } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const DragonTiger = db.DragonTiger;
const DragonTigerBet = db.DragonTigerBet;
const DragonTigerRoom = db.DragonTigerRoom;
const DragonTigerMap = db.DragonTigerMap;
const Card = db.Card;

class DragonTigerService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await DragonTiger.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Dragon Tiger');
    }
  }

  async create(data) {
    try {
      return await DragonTiger.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Dragon Tiger');
    }
  }

  async update(id, data) {
    try {
      await DragonTiger.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
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
    let rooms = await DragonTigerRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { dragon_tiger_room_id: roomId })
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
      let activeGame = await DragonTiger.findAll(options);

      return activeGame;
    } catch (error) {
      console.log(error)
      throw new Error('Error while place bet');
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
      console.log(error)
      throw new Error('Error while place bet');
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
      return await DragonTiger.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      dragonBetAmount: 0,
      tigerBetAmount: 0,
      tieBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == DRAGON) {
        if (calculation) {
          betData.dragonBetAmount += (+bet.amount * DRAGON_OR_TIGET_MULTIPLY);
        } else {
          betData.dragonBetAmount += (+bet.amount)
        }
      }
      if (bet.bet == TIGER) {
        if (calculation) {
          betData.tigerBetAmount += (+bet.amount * DRAGON_OR_TIGET_MULTIPLY);
        } else {
          betData.tigerBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == TIE) {
        if (calculation) {
          betData.tieBetAmount += (+bet.amount * TIE_MULTIPLY);
        } else {
          betData.tieBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// DRAGON TIGER BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet= await DragonTigerBet.create(data);
      await userService.update(data.user_id, { todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`) })
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error while place bet');
    }
  }

  async getBetById(id, attributes = []) {
    try {
      const options = getAttributes(attributes)
      const bet = await DragonTigerBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Dragon Tiger');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.dragon_tiger_id = gameId;
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

      return await DragonTigerBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await DragonTigerBet.findAll({
        attributes: [
          '*',
          'tbl_dragon_tiger.room_id'
          // [db.Sequelize.col('tbl_dragon_tiger.room_id'), 'room_id']
        ],
        include: [{
          model: DragonTiger,
          attributes: [],
          // attributes: ['room_id'], // Specify the room_id column from DragonTiger
          // where: {
          //   id: db.Sequelize.col('tbl_dragon_tiger_bet.dragon_tiger_id')
          // }
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
      console.log(error)
      throw new Error('Error while fetching data');
    }
  }

  async updateBet(id, data) {
    try {
      await DragonTigerBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// DRAGON TIGER MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        dragon_tiger_id: gameId,
        card
      }
      return await DragonTigerMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Dragon Tiger Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return DragonTigerMap.findAll({
        where: {
          dragon_tiger_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }

}

module.exports = new DragonTigerService();
