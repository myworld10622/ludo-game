const { DRAGON, TIGER, TIE, DRAGON_OR_TIGET_MULTIPLY, TIE_MULTIPLY, RED_BLACK_BETS, RED_BLACK_WINNING_PRICE } = require('../constants');
const db = require('../models');
const { getRummyCardPoints } = require('../utils/cards');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const RedBlack = db.RedBlack;
const RedBlackBet = db.RedBlackBet;
const RedBlackRoom = db.RedBlackRoom;
const RedBlackMap = db.RedBlackMap;
const Card = db.Card;

class RedBlackService {
  async getById(id, attributes = []) {
    try {
      const attributeOptions = getAttributes(attributes);
      const game = await RedBlack.findByPk(id, attributeOptions);
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
      return await RedBlack.create(data);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Red Black');
    }
  }

  async update(id, data) {
    try {
      await RedBlack.update(data, {
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
    let rooms = await RedBlackRoom.findAll(options);

    if (roomId && userId) {
      userService.update(userId, { red_black_id: roomId })
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
      let activeGame = await RedBlack.findAll(options);

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
      return await RedBlack.findAll(options);
    } catch (error) {
      console.log(error);
      throw new Error("Error while fetch winning records");
    }
  }

  // Like Helper
  async getBetDataByBets(bets, calculation = 0) {
    const betData = {
      totalBetAmount: 0,
      redBetAmount: 0,
      blackBetAmount: 0,
      pairBetAmount: 0,
      colorBetAmount: 0,
      sequenceBetAmount: 0,
      pureSequenceBetAmount: 0,
      setBetAmount: 0
    }
    for (let i = 0; i < bets.length; i++) {
      const bet = bets[i];
      betData.totalBetAmount += bet.amount;
      if (bet.bet == RED_BLACK_BETS.red) {
        if (calculation) {
          betData.redBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.red);
        } else {
          betData.redBetAmount += (+bet.amount)
        }
      } else if (bet.bet == RED_BLACK_BETS.black) {
        if (calculation) {
          betData.blackBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.black);
        } else {
          betData.blackBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.pair) {
        if (calculation) {
          betData.pairBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.pair);
        } else {
          betData.pairBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.color) {
        if (calculation) {
          betData.colorBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.color);
        } else {
          betData.colorBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.sequence) {
        if (calculation) {
          betData.sequenceBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.sequence);
        } else {
          betData.sequenceBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.pure_sequence) {
        if (calculation) {
          betData.pureSequenceBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.pure_sequence);
        } else {
          betData.pureSequenceBetAmount += (+bet.amount);
        }
      } else if (bet.bet == RED_BLACK_BETS.set) {
        if (calculation) {
          betData.setBetAmount += (+bet.amount * RED_BLACK_WINNING_PRICE.set);
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
      const subquery = `(SELECT id FROM tbl_red_black WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

      // Main query to count the bets for the latest roulette
      const result = await RedBlackBet.findOne({
        attributes: [
          [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
        ],
        where: {
          red_black_id: db.Sequelize.literal(`(${subquery})`)
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
      const bet = await RedBlackBet.create(data);
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
      const bet = await RedBlackBet.findByPk(id, options);
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
        conditionPayload.red_black_id = gameId;
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

      return await RedBlackBet.findAll(options);
    } catch (error) {
      console.log(error)
      throw new Error('Error fetching Data');
    }
  }

  async walletHistory(userId) {
    try {
      const bets = await RedBlackBet.findAll({
        attributes: [
          '*',
          'tbl_red_black.room_id'
        ],
        include: [{
          model: RedBlack,
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
      await RedBlackBet.update(data, {
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
        red_black_id: gameId,
        card
      }
      return await RedBlackMap.create(payload);
    } catch (error) {
      console.log(error)
      throw new Error('Error creating Red Black Map');
    }
  }

  async getGameCards(gameId) {
    try {
      return RedBlackMap.findAll({
        where: {
          red_black_id: gameId
        }
      })
    } catch (error) {
      console.log(error);
      throw new Error("Error while get Game Cards");
    }
  }

  async cardValue(card1, card2, card3) {
    try {
      console.log("card ===============", card1, card2, card3)
      let rule = 1;
      let value = 0;
      let value2 = 0;
      let value3 = 0;

      let card1Color = card1.substring(0, 2);
      let card1Num = card1.substring(2);

      let card2Color = card2.substring(0, 2);
      let card2Num = card2.substring(2);

      let card3Color = card3.substring(0, 2);
      let card3Num = card3.substring(2);

      if ((card1Num == card2Num) && (card2Num == card3Num)) {
        card1Num = getRummyCardPoints(card1Num);
        rule = 6;
        value = card1Num;
      } else {
        card1Num = getRummyCardPoints(card1Num);
        card2Num = getRummyCardPoints(card2Num);
        card3Num = getRummyCardPoints(card3Num);

        const arr = [card1Num, card2Num, card3Num];
        arr.sort();

        let sequence = false;
        // Check if the cards form a sequence
        if ((arr[0] == arr[1] - 1) && (arr[1] == arr[2] - 1)) {
          sequence = true;
        }

        // Exception for A23 sequence (A = 14 in this case)
        if (arr[0] === 2 && arr[1] === 3 && arr[2] === 14) {
          sequence = true;
          arr[2] = 3;
        }

        // Check if all cards have the same color
        let color = false;
        if (card1Color === card2Color && card2Color === card3Color) {
          color = true;
        }

        // Determine the rule and assign values
        if (sequence && color) {
          rule = 5;
          value = arr[2];
        } else if (sequence) {
          rule = 4;
          value = arr[2];
        } else if (color) {
          rule = 3;
          value = arr[2];
        } else {
          if ((card1Num === card2Num) || (card2Num === card3Num) || (card1Num === card3Num)) {
            rule = 2;
            if (card1Num === card2Num) {
              value = card1Num;
              value2 = card3Num;
            } else if (card2Num === card3Num) {
              value = card2Num;
              value2 = card1Num;
            } else if (card1Num === card3Num) {
              value = card3Num;
              value2 = card2Num;
            }
          } else {
            rule = 1;
            value = arr[2];
            value2 = arr[1];
            value3 = arr[0];
          }
        }
      }
      return [rule, value, value2, value3];
    } catch (error) {
      throw new Error(error)
    }
  }

  async getWinnerPosition(user1, user2) {
    let winner = '';

    if (user1[0] === user2[0]) {
      switch (user1[0]) {
        case 6:
          winner = (user1[1] > user2[1]) ? 0 : 1;
          break;

        case 5:
        case 4:
          if (user1[1] === user2[1]) {
            winner = 2;
          } else {
            user1[1] = (user1[1] === 14) ? 15 : user1[1];
            user2[1] = (user2[1] === 14) ? 15 : user2[1];
            user1[1] = (user1[1] === 3) ? 14 : user1[1];
            user2[1] = (user2[1] === 3) ? 14 : user2[1];

            winner = (user1[1] > user2[1]) ? 0 : 1;
          }
          break;

        case 3:
          if (user1[1] === user2[1]) {
            winner = 2;
          } else {
            winner = (user1[1] > user2[1]) ? 0 : 1;
          }
          break;

        case 2:
          if (user1[1] === user2[1]) {
            if (user1[2] === user2[2]) {
              winner = 2;
            } else {
              winner = (user1[2] > user2[2]) ? 0 : 1;
            }
          } else {
            winner = (user1[1] > user2[1]) ? 0 : 1;
          }
          break;

        case 1:
          if (user1[1] === user2[1]) {
            if (user1[2] === user2[2]) {
              if (user1[3] === user2[3]) {
                winner = 2;
              } else {
                winner = (user1[3] > user2[3]) ? 0 : 1;
              }
            } else {
              winner = (user1[2] > user2[2]) ? 0 : 1;
            }
          } else {
            winner = (user1[1] > user2[1]) ? 0 : 1;
          }
          break;
      }
    } else {
      winner = (user1[0] > user2[0]) ? 0 : 1;
    }

    return winner;
  }

}

module.exports = new RedBlackService();
