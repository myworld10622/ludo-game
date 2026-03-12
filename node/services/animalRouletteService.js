const { DRAGON, TIGER, TIE, DRAGON_OR_TIGET_MULTIPLY, TIE_MULTIPLY, ANIMAL_ROULETTE_GAME } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const AnimalRoulette = db.AnimalRoulette;
const AnimalRouletteBet = db.AnimalRouletteBet;
const AnimalRouletteRoom = db.AnimalRouletteRoom;
const AnimalRouletteMap = db.AnimalRouletteMap;
const Card = db.Card;

class AnimalRouletteService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await AnimalRoulette.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Animal Roulette');
    }
  }

  async create(data) {
    try {
      return await AnimalRoulette.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Animal Roulette');
    }
  }

  async update(id, data) {
    try {
      await AnimalRoulette.update(data, {
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
    let rooms = await AnimalRouletteRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { animal_roulette_room_id: roomId })
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
      let activeGame = await AnimalRoulette.findAll(options);

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
      return await AnimalRoulette.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      tigerBetAmount: 0,
      snakeBetAmount: 0,
      sharkBetAmount: 0,
      foxBetAmount: 0,
      cheetahBetAmount: 0,
      bearBetAmount: 0,
      whaleBetAmount: 0,
      lionBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == ANIMAL_ROULETTE_GAME.tiger) {
        if (calculation) {
          betData.tigerBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.tigerMultiply);
        } else {
          betData.tigerBetAmount += (+bet.amount)
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.snake) {
        if (calculation) {
          betData.snakeBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.snakeMultiply);
        } else {
          betData.snakeBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.shark) {
        if (calculation) {
          betData.sharkBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.sharkMultiply);
        } else {
          betData.sharkBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.fox) {
        if (calculation) {
          betData.foxBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.foxMultiply);
        } else {
          betData.foxBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.cheetah) {
        if (calculation) {
          betData.cheetahBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.cheetahMultiply);
        } else {
          betData.cheetahBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.bear) {
        if (calculation) {
          betData.bearBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.bearMultiply);
        } else {
          betData.bearBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.whale) {
        if (calculation) {
          betData.whaleBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.whaleMultiply);
        } else {
          betData.whaleBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == ANIMAL_ROULETTE_GAME.lion) {
        if (calculation) {
          betData.lionBetAmount += (+bet.amount * ANIMAL_ROULETTE_GAME.lionMultiply);
        } else {
          betData.lionBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Animal Roulette BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await AnimalRouletteBet.create(data);
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
      const bet = await AnimalRouletteBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Animal Roulette');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.animal_roulette_id = gameId;
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

      return await AnimalRouletteBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async getRoomOnline(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_animal_roulette WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await AnimalRouletteBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          animal_roulette_id: db.Sequelize.literal(`(${subquery})`)
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
      const bets = await AnimalRouletteBet.findAll({
        attributes: [
          '*',
          'animal.room_id'
        ],
        include: [{
          model: AnimalRoulette,
          as: "animal",
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
      await AnimalRouletteBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Animal Roulette MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        animal_roulette_id: gameId,
        card
      }
      return await AnimalRouletteMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Animal Roulette Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return AnimalRouletteMap.findAll({
        where: {
          animal_roulette_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }

}

module.exports = new AnimalRouletteService();
