const { QueryTypes } = require('sequelize');
const { JACKPOT_BETS, JACKPOT_WINNING_PRICE } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const adminService = require('./adminService');
const Jackpot = db.Jackpot;
const Setting = db.setting;
const JackpotBet = db.JackpotBet;
const JackpotRoom = db.JackpotRoom;
const JackpotMap = db.JackpotMap;
const Card = db.Card;
const User = db.user;

class JackpotService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await Jackpot.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Jackpot');
    }
  }

  async create(data) {
    try {
      return await Jackpot.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Jackpot');
    }
  }

  async update(id, data) {
    try {
      await Jackpot.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }

  async updateJackpotAmount(amount, type = "add") {
    try {
      const updateFields = {
        jackpot_coin: type == "add" ? db.sequelize.literal(`jackpot_coin + ${amount}`) : db.sequelize.literal(`jackpot_coin - ${amount}`)
      };
      if (type == "minus") {
        updateFields.jackpot_status = 0
      }
      return await adminService.updateSetting(1, updateFields);
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
    let rooms = await JackpotRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { jackpot_room_id: roomId })
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
      let activeGame = await Jackpot.findAll(options);

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
      return await Jackpot.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  async getJackpotWinners(limit = '') {
    let query = `
      SELECT tbl_jackpot.id, tbl_jackpot.end_datetime as time, 
             SUM(tbl_jackpot_bet.winning_amount) as rewards, 
             (SELECT GROUP_CONCAT(card) 
              FROM tbl_jackpot_map 
              WHERE jackpot_id = tbl_jackpot.id 
              GROUP BY jackpot_id) as type, 
             COUNT(tbl_jackpot_bet.id) as winners 
      FROM tbl_jackpot 
      JOIN tbl_jackpot_bet ON tbl_jackpot.id = tbl_jackpot_bet.jackpot_id 
      WHERE tbl_jackpot.winning = 6 
      AND tbl_jackpot.status = 1 
      GROUP BY tbl_jackpot.id 
      ORDER BY tbl_jackpot.id DESC
    `;

    if (limit) {
      query += ` LIMIT :limit`; // Use Sequelize parameterization
    }

    const result = await db.sequelize.query(query, {
      replacements: { limit: parseInt(limit) },
      type: QueryTypes.SELECT
    });

    return result;
  }

  async getJackpotWinnerDetails(jackpot_id) {
    try {
      const result = await JackpotBet.findAll({
        attributes: [
          'amount', 'winning_amount',
          'users.name', 'users.profile_pic'
        ],
        include: [{
          model: User,
          as: "users",
          attributes: []
        }],
        where: {
          jackpot_id
        },
        order: [['winning_amount', 'DESC']],
        limit: 1,
        raw: true
      });

      return result;  // This will return an array of objects
    } catch (error) {
      console.error('Error fetching jackpot winner details:', error);
      throw error;
    }
  };

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      highBetAmount: 0,
      pairBetAmount: 0,
      colorBetAmount: 0,
      sequenceBetAmount: 0,
      pureSequenceBetAmount: 0,
      setBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == JACKPOT_BETS.red) {
        if (calculation) {
          betData.highBetAmount += (+bet.amount * JACKPOT_WINNING_PRICE.red);
        } else {
          betData.highBetAmount += (+bet.amount)
        }
      } else if (bet.bet == JACKPOT_BETS.pair) {
        if (calculation) {
          betData.pairBetAmount += (+bet.amount * JACKPOT_WINNING_PRICE.pair);
        } else {
          betData.pairBetAmount += (+bet.amount);
        }
      } else if (bet.bet == JACKPOT_BETS.color) {
        if (calculation) {
          betData.colorBetAmount += (+bet.amount * JACKPOT_WINNING_PRICE.color);
        } else {
          betData.colorBetAmount += (+bet.amount);
        }
      } else if (bet.bet == JACKPOT_BETS.sequence) {
        if (calculation) {
          betData.sequenceBetAmount += (+bet.amount * JACKPOT_WINNING_PRICE.sequence);
        } else {
          betData.sequenceBetAmount += (+bet.amount);
        }
      } else if (bet.bet == JACKPOT_BETS.pure_sequence) {
        if (calculation) {
          betData.pureSequenceBetAmount += (+bet.amount * JACKPOT_WINNING_PRICE.pure_sequence);
        } else {
          betData.pureSequenceBetAmount += (+bet.amount);
        }
      } else if (bet.bet == JACKPOT_BETS.set) {
        if (calculation) {
          betData.setBetAmount += (+bet.amount * JACKPOT_WINNING_PRICE.set);
        } else {
          betData.setBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }

  async getRoomOnlineUsers(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_jackpot WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await JackpotBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          jackpot_id: db.Sequelize.literal(`(${subquery})`)
        },
        raw: true
      });

      return result ? result.online : 0;
    } catch (error) {
      console.error('Error fetching online count:', error);
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Jackpot BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await JackpotBet.create(data);
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
      const bet = await JackpotBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Jackpot');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.jackpot_id = gameId;
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

      return await JackpotBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await JackpotBet.findAll({
        attributes: [
          '*',
          'tbl_jackpot.room_id'
        ],
        include: [{
          model: Jackpot,
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
      await JackpotBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Jackpot MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        jackpot_id: gameId,
        card
      }
      return await JackpotMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Jackpot Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return JackpotMap.findAll({
        where: {
          jackpot_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }
}

module.exports = new JackpotService();
