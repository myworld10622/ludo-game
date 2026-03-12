const { CAR_ROULETTE_GAME } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const CarRoulette = db.CarRoulette;
const CarRouletteBet = db.CarRouletteBet;
const CarRouletteRoom = db.CarRouletteRoom;
const CarRouletteMap = db.CarRouletteMap;
const Card = db.Card;

class CarRouletteService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await CarRoulette.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Car Roulette');
    }
  }

  async create(data) {
    try {
      return await CarRoulette.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Car Roulette');
    }
  }

  async update(id, data) {
    try {
      await CarRoulette.update(data, {
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
    let rooms = await CarRouletteRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { car_roulette_room_id: roomId })
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
      let activeGame = await CarRoulette.findAll(options);

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
      return await CarRoulette.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      toyotaBetAmount: 0,
      mahindraBetAmount: 0,
      audiBetAmount: 0,
      bmwBetAmount: 0,
      mercedesBetAmount: 0,
      porscheBetAmount: 0,
      lamborghiniBetAmount: 0,
      ferrariBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == CAR_ROULETTE_GAME.toyota) {
        if (calculation) {
          betData.toyotaBetAmount += (+bet.amount * CAR_ROULETTE_GAME.toyotaMultiply);
        } else {
          betData.toyotaBetAmount += (+bet.amount)
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.mahindra) {
        if (calculation) {
          betData.mahindraBetAmount += (+bet.amount * CAR_ROULETTE_GAME.mahindraMultiply);
        } else {
          betData.mahindraBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.audi) {
        if (calculation) {
          betData.audiBetAmount += (+bet.amount * CAR_ROULETTE_GAME.audiMultiply);
        } else {
          betData.audiBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.bmw) {
        if (calculation) {
          betData.bmwBetAmount += (+bet.amount * CAR_ROULETTE_GAME.bmwMultiply);
        } else {
          betData.bmwBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.mercedes) {
        if (calculation) {
          betData.mercedesBetAmount += (+bet.amount * CAR_ROULETTE_GAME.mercedesMultiply);
        } else {
          betData.mercedesBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.porsche) {
        if (calculation) {
          betData.porscheBetAmount += (+bet.amount * CAR_ROULETTE_GAME.porscheMultiply);
        } else {
          betData.porscheBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.lamborghini) {
        if (calculation) {
          betData.lamborghiniBetAmount += (+bet.amount * CAR_ROULETTE_GAME.lamborghiniMultiply);
        } else {
          betData.lamborghiniBetAmount += (+bet.amount);
        }
      }
      if (bet.bet == CAR_ROULETTE_GAME.ferrari) {
        if (calculation) {
          betData.ferrariBetAmount += (+bet.amount * CAR_ROULETTE_GAME.ferrariMultiply);
        } else {
          betData.ferrariBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Car Roulette BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await CarRouletteBet.create(data);
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
      const bet = await CarRouletteBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Car Roulette');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.car_roulette_id = gameId;
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

      return await CarRouletteBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async getRoomOnline(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_car_roulette WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await CarRouletteBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          car_roulette_id: db.Sequelize.literal(`(${subquery})`)
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
      const bets = await CarRouletteBet.findAll({
        attributes: [
          '*',
          'car.room_id'
        ],
        include: [{
          model: CarRoulette,
          as: "car",
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
      await CarRouletteBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Car Roulette MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        car_roulette_id: gameId,
        card
      }
      return await CarRouletteMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Car Roulette Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return CarRouletteMap.findAll({
        where: {
          car_roulette_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }

}

module.exports = new CarRouletteService();
