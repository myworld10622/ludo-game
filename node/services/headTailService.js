const { HEAD_TAIL_GAME } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const HeadTail = db.HeadTail;
const HeadTailBet = db.HeadTailBet;
const HeadTailRoom = db.HeadTailRoom;
const HeadTailMap = db.HeadTailMap;
const Card = db.Card;

class HeadTailService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await HeadTail.findByPk(id, attributeOptions);
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
      return await HeadTail.create(data);
    } catch (error) {
      throw new Error(error);
    }
  }

  async update(id, data) {
    try {
      await HeadTail.update(data, {
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
    let rooms = await HeadTailRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { head_tail_room_id: roomId })
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
      let activeGame = await HeadTail.findAll(options);

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
      return await HeadTail.findAll(options);
    } catch (error) {
      throw new Error(error);
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      headBetAmount: 0,
      tailBetAmount: 0,
      tieBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == HEAD_TAIL_GAME.head) {
        if (calculation) {
          betData.headBetAmount += (+bet.amount * HEAD_TAIL_GAME.headTailMultiply);
        } else {
          betData.headBetAmount += (+bet.amount)
        }
      }
      if (bet.bet == HEAD_TAIL_GAME.tail) {
        if (calculation) {
          betData.tailBetAmount += (+bet.amount * HEAD_TAIL_GAME.headTailMultiply);
        } else {
          betData.tailBetAmount += (+bet.amount);
        }
      }
      // if (bet.bet == HEAD_TAIL_GAME.tie) {
      //   if (calculation) {
      //     betData.tieBetAmount += (+bet.amount * HEAD_TAIL_GAME.headTailTieMultiply);
      //   } else {
      //     betData.tieBetAmount += (+bet.amount);
      //   }
      // }
    }

    return betData;
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await HeadTailBet.create(data);
      await userService.update(data.user_id, { todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`) })
      return bet;
    } catch (error) {
      throw new Error(error);
    }
  }

  async getBetById(id, attributes = []) {
    try {
      const options = getAttributes(attributes)
      const bet = await HeadTailBet.findByPk(id, options);
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
        conditionPayload.head_tail_id = gameId;
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

      return await HeadTailBet.findAll(options);
    } catch (error) {
      throw new Error(error);
    }
  }

  async getRoomOnline(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_head_tail WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await HeadTailBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          head_tail_id: db.Sequelize.literal(`(${subquery})`)
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
      const bets = await HeadTailBet.findAll({
        attributes: [
          '*',
          'head_tail.room_id'
        ],
        include: [{
          model: HeadTail,
          as: "head_tail",
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
      await HeadTailBet.update(data, {
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
        head_tail_id: gameId,
        card
      }
      return await HeadTailMap.create(payload);
    } catch (error) {
      throw new Error(error);
    }
  }

  async getGameCards(gameId) {
    try {
      return HeadTailMap.findAll({
        where: {
          head_tail_id: gameId
        }
      })
    } catch (error) {
      throw new Error(error);
    }
  }

}

module.exports = new HeadTailService();
