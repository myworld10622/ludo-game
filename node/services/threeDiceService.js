const { THREE_DICE_BETS } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const ThreeDice = db.ThreeDice;
const ThreeDiceBet = db.ThreeDiceBet;
const ThreeDiceRoom = db.ThreeDiceRoom;
const ThreeDiceMap = db.ThreeDiceMap;
const Card = db.Card;

class ThreeDiceService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await ThreeDice.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching ThreeDice');
    }
  }

  async create(data) {
    try {
      return await ThreeDice.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating ThreeDice');
    }
  }

  async update(id, data) {
    try {
      await ThreeDice.update(data, {
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
    let rooms = await ThreeDiceRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { three_dice_room_id: roomId })
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
      let activeGame = await ThreeDice.findAll(options);

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
      return await ThreeDice.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets) {
    const betData = {
      totalBetAmount: 0,
      heartBetAmount: 0,
      spadeBetAmount: 0,
      diamondBetAmount: 0,
      // clubBetAmount: 0,
      // faceBetAmount: 0,
      // flagBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == THREE_DICE_BETS.heart) {
        betData.heartBetAmount += (+bet.amount)
      } else if (bet.bet == THREE_DICE_BETS.spade) {
        betData.spadeBetAmount += (+bet.amount);
      } else if (bet.bet == THREE_DICE_BETS.diamond) {
        betData.diamondBetAmount += (+bet.amount);
      } 
      // else if (bet.bet == THREE_DICE_BETS.club) {
      //   betData.clubBetAmount += (+bet.amount);
      // } else if (bet.bet == THREE_DICE_BETS.face) {
      //   betData.faceBetAmount += (+bet.amount);
      // } else if (bet.bet == THREE_DICE_BETS.flag) {
      //   betData.flagBetAmount += (+bet.amount);
      // }
    }

    return betData;
  }

  async getRoomOnlineUsers(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_three_dice WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await ThreeDiceBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          three_dice_id: db.Sequelize.literal(`(${subquery})`)
        },
        raw: true
      });

      return result ? result.online : 0;
    } catch (error) {
      console.error('Error fetching online count:', error);
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Three Dice Bet /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await ThreeDiceBet.create(data);
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
      const bet = await ThreeDiceBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Three Dice');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.three_dice_id = gameId;
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

      return await ThreeDiceBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await ThreeDiceBet.findAll({
        attributes: [
          '*',
          'tbl_three_dice.room_id'
        ],
        include: [{
          model: ThreeDice,
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
      throw new Error('Error while fetching data');
    }
  }

  async updateBet(id, data) {
    try {
      await ThreeDiceBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Three Dice MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        three_dice_id: gameId,
        card
      }
      return await ThreeDiceMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Three Dice Map');
    }
  }

  async mapCount(threeDiceId, card) {
    try {
      const count = await ThreeDiceMap.count({
        where: {
          three_dice_id: threeDiceId,
          card: card
        }
      });

      return count;
    } catch (error) {
      console.error('Error in counting rows:', error);
      throw error;
    }
  };

  async getGameCards(gameId) {
    try {
      return ThreeDiceMap.findAll({
        where: {
          three_dice_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }
}

module.exports = new ThreeDiceService();
