const { SLOT_WIN, SLOT_MULTIPLY } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const SlotGame = db.SlotGame;
const SlotGameBet = db.SlotGameBet;
const SlotGameRoom = db.SlotGameRoom;
const SlotGameMap = db.SlotGameMap;
const Card = db.Card;

class SlotGameService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await SlotGame.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Slot Game');
    }
  }

  async create(data) {
    try {
      return await SlotGame.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Slot Game');
    }
  }

  async update(id, data) {
    try {
      await SlotGame.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating Slot Game');
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
    let rooms = await SlotGameRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { slot_game_room_id: roomId })
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
      let activeGame = await SlotGame.findAll(options);

      return activeGame;
    } catch (error) {
      console.log(error)
      throw new Error('Error while fetching active Slot game');
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
      throw new Error('Error fetching cards for Slot Game');
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
      return await SlotGame.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetching winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      winBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      // if (bet.bet == SLOT_WIN) {
      //   if (calculation) {
      //     betData.winBetAmount += (+bet.amount * SLOT_MULTIPLY);
      //   } else {
      //     betData.winBetAmount += (+bet.amount)
      //   }
      // }
    }

    return betData;
  }

  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// SLOT GAME BET ////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await SlotGameBet.create(data);
      await userService.update(data.user_id, { todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`) })
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error placing Slot game bet');
    }
  }

  async getBetById(id, attributes = []) {
    try {
      const options = getAttributes(attributes);
      const bet = await SlotGameBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Slot game bet');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.slot_game_id = gameId;
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

      return await SlotGameBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Slot game bet data');
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await SlotGameBet.findAll({
        attributes: [
          '*',
          'tbl_slot_game.room_id'
        ],
        include: [{
          model: SlotGame,
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
      console.log(error)
      throw new Error('Error while fetching Slot game data');
    }
  }

  async updateBet(id, data) {
    try {
      await SlotGameBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating Slot game bet');
    }
  }

  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// SLOT GAME MAP ////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        slot_game_id: gameId,
        card
      }
      return await SlotGameMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Slot Game Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return SlotGameMap.findAll({
        where: {
          slot_game_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetching game cards for Slot Game");
    }
  }
}

module.exports = new SlotGameService();
