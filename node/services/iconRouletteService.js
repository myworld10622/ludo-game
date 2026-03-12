const { ICON_ROULETTE_GAME } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const IconRoulette = db.IconRoulette;
const IconRouletteBet = db.IconRouletteBet;
const IconRouletteRoom = db.IconRouletteRoom;
const IconRouletteMap = db.IconRouletteMap;
const Card = db.Card;

class IconRouletteService {
  async getById(id, attributes = []) {  
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await IconRoulette.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Icon Roulette');
    }
  }

  async create(data) {
    try {
      return await IconRoulette.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Icon Roulette');
    }
  }

  async update(id, data) {
    try {
      await IconRoulette.update(data, {
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
    let rooms = await IconRouletteRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { icon_roulette_room_id: roomId })
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
      let activeGame = await IconRoulette.findAll(options);

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
      return await IconRoulette.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      umbrellaBetAmount: 0,
      footballBetAmount: 0,
      sunBetAmount: 0,
      diyaBetAmount: 0,
      cowBetAmount: 0,
      bucketBetAmount: 0,
      kiteBetAmount: 0,
      topBetAmount: 0,
      roseBetAmount: 0,
      butterflyBetAmount: 0,
      pigeonBetAmount: 0,
      rabbitBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == ICON_ROULETTE_GAME.umbrella) {
        if (calculation) {
          betData.umbrellaBetAmount += (+bet.amount * ICON_ROULETTE_GAME.umbrellaMultiply);
        } else {
          betData.umbrellaBetAmount += (+bet.amount)
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.football) {
        if (calculation) {
          betData.footballBetAmount += (+bet.amount * ICON_ROULETTE_GAME.footballMultiply);
        } else {
          betData.footballBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.sun) {
        if (calculation) {
          betData.sunBetAmount += (+bet.amount * ICON_ROULETTE_GAME.sunMultiply);
        } else {
          betData.sunBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.diya) {
        if (calculation) {
          betData.diyaBetAmount += (+bet.amount * ICON_ROULETTE_GAME.diyaMultiply);
        } else {
          betData.diyaBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.cow) {
        if (calculation) {
          betData.cowBetAmount += (+bet.amount * ICON_ROULETTE_GAME.cowMultiply);
        } else {
          betData.cowBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.bucket) {
        if (calculation) {
          betData.bucketBetAmount += (+bet.amount * ICON_ROULETTE_GAME.bucketMultiply);
        } else {
          betData.bucketBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.kite) {
        if (calculation) {
          betData.kiteBetAmount += (+bet.amount * ICON_ROULETTE_GAME.kiteMultiply);
        } else {
          betData.kiteBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.top) {
        if (calculation) {
          betData.topBetAmount += (+bet.amount * ICON_ROULETTE_GAME.topMultiply);
        } else {
          betData.topBetAmount += (+bet.amount);
        }
      }
      
      if (bet.bet == ICON_ROULETTE_GAME.rose) {
        if (calculation) {
          betData.roseBetAmount += (+bet.amount * ICON_ROULETTE_GAME.roseMultiply);
        } else {
          betData.roseBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.butterfly) {
        if (calculation) {
          betData.butterflyBetAmount += (+bet.amount * ICON_ROULETTE_GAME.butterflyMultiply);
        } else {
          betData.butterflyBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.pigeon) {
        if (calculation) {
          betData.pigeonBetAmount += (+bet.amount * ICON_ROULETTE_GAME.pigeonMultiply);
        } else {
          betData.pigeonBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ICON_ROULETTE_GAME.rabbit) {
        if (calculation) {
          betData.rabbitBetAmount += (+bet.amount * ICON_ROULETTE_GAME.rabbitMultiply);
        } else {
          betData.rabbitBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Icon Roulette BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await IconRouletteBet.create(data);
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
      const bet = await IconRouletteBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Icon Roulette');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.icon_roulette_id = gameId;
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

      return await IconRouletteBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async getRoomOnline(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_icon_roulette WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await IconRouletteBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          icon_roulette_id: db.Sequelize.literal(`(${subquery})`)
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
      const bets = await IconRouletteBet.findAll({
        attributes: [
          '*',
          'icon.room_id'
        ],
        include: [{
          model: IconRoulette,
          as: "icon",
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
      await IconRouletteBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Icon Roulette MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        icon_roulette_id: gameId,
        card
      }
      return await IconRouletteMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Icon Roulette Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return IconRouletteMap.findAll({
        where: {
          icon_roulette_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }

}

module.exports = new IconRouletteService();
