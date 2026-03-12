const { RED_BLACK_BETS, RED_BLACK_WINNING_PRICE, BACCARAT_BETS } = require('../constants');
const db = require('../models');
const { getRummyCardPoints } = require('../utils/cards');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const Bacarrat = db.Bacarrat;
const BacarratBet = db.BacarratBet;
const BacarratRoom = db.BacarratRoom;
const BacarratMap = db.BacarratMap;
const Card = db.Card;

class BaccaratService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await Bacarrat.findByPk(id, attributeOptions);
      if (!game) {
        throw new Error('Game not found');
      }
      return game;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Red Black');
    }
  }

  async create(data) {
    try {
      return await Bacarrat.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Red Black');
    }
  }

  async update(id, data) {
    try {
      await Bacarrat.update(data, {
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
    let rooms = await BacarratRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { baccarat_id: roomId })
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
      let activeGame = await Bacarrat.findAll(options);

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
      return await Bacarrat.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      playerBetAmount: 0,
      bankerBetAmount: 0,
      tieBetAmount: 0,
      playerPairBetAmount: 0,
      bankerPairBetAmount: 0,
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == RED_BLACK_BETS.red) {
        if (calculation) {
          betData.playerBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.red);
        } else {
          betData.playerBetAmount += (+bet.amount)
        }
      } else if (bet.bet == RED_BLACK_BETS.black) {
        if (calculation) {
          betData.bankerBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.black);
        } else {
          betData.bankerBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.pair) {
        if (calculation) {
          betData.tieBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.pair);
        } else {
          betData.tieBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.color) {
        if (calculation) {
          betData.playerPairBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.color);
        } else {
          betData.playerPairBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.pure_sequence) {
        if (calculation) {
          betData.bankerPairBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.pure_sequence);
        } else {
          betData.bankerPairBetAmount += (+bet.amount);
        }
      }
    }

    return betData;
  }

  async getRoomOnlineUsers(roomId) {
    try {
      // First get the latest roulette ID for the given room ID
      const subquery = `(SELECT id FROM tbl_baccarat WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await BacarratBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          baccarat_id: db.Sequelize.literal(`(${subquery})`)
        },
        raw: true
      });

      return result ? result.online : 0;
    } catch (error) {
      console.error('Error fetching online count:', error);
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Red Black BET /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createBet(data) {
    try {
      const bet = await BacarratBet.create(data);
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
      const bet = await BacarratBet.findByPk(id, options);
      if (!bet) {
        throw new Error('Bet not found');
      }
      return bet;
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Red Black');
    }
  }

  async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
    try {
      const conditionPayload = {};
      if (userId) {
        conditionPayload.user_id = userId;
      }
      if (gameId) {
        conditionPayload.baccarat_id = gameId;
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

      return await BacarratBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await BacarratBet.findAll({
        attributes: [
          '*',
          'tbl_baccarat.room_id'
        ],
        include: [{
          model: Bacarrat,
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
      await BacarratBet.update(data, {
        where: { id }
      });
    } catch (error) {
      console.log(error);
      throw new Error('Error updating data');
    }
  }


  /////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Red Black MAP /////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////

  async createMap(gameId, card) {
    try {
      const payload = {
        baccarat_id: gameId,
        card
      }
      return await BacarratMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Red Black Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return BacarratMap.findAll({
        where: {
          baccarat_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }

  async cardValue(card1, card2, card3 = '000') {
    let card1Num = parseInt(card1.substring(2), 10);
    let card2Num = parseInt(card2.substring(2), 10);
    // let card3Num = parseInt(card3.substring(2), 10);

    if (isNaN(card1Num)) {
      card1Num = 0;
      if (card1.substring(2) == "A") {
        card1Num = 1;
      }
    }
    if (isNaN(card2Num)) {
      card2Num = 0;
      if (card2.substring(2) == "A") {
        card2Num = 1;
      }
    }
    // if (isNaN(card3Num)) {
    //   card3Num = getRummyCardPoints(card3Num);
    // }

    // Calculate total points and return the remainder when divided by 10
    const totalPoints = card1Num + card2Num;
    return totalPoints % 10;
  }

  async getWinner(player, banker) {
    let winner = '';

    if (player === banker) {
      winner = BACCARAT_BETS.tie;
    } else {
      winner = (player > banker) ? BACCARAT_BETS.player : BACCARAT_BETS.banker;
    }

    return winner;
  }

  async isPair(card1, card2) {
    const card1Num = card1.substring(2);
    const card2Num = card2.substring(2);

    return card1Num == card2Num ? true : false;
  };

}

module.exports = new BaccaratService();
