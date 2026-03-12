const { COLOR_PREDICTIONS_BETS } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const ColorPrediction = db.ColorPrediction1Min;
const ColorPredictionBet = db.ColorPredictionBet1Min;
const ColorPredictionRoom = db.ColorPredictionRoom1Min;
const ColorPredictionMap = db.ColorPredictionMap1Min;
const Card = db.Card;

class ColorPredictionService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await ColorPrediction.findByPk(id, attributeOptions);
            if (!game) {
                throw new Error('Game not found');
            }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Color Prediction');
        }
    }

    async create(data) {
        try {
          return await ColorPrediction.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating Color Prediction');
        }
    }

    async update(id, data) {
        try {
            await ColorPrediction.update(data, {
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
        let rooms = await ColorPredictionRoom.findAll(options);

        if (roomId && userId) {
            userService.update(userId, { color_prediction_room_id: roomId })
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
            let activeGame = await ColorPrediction.findAll(options);

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
                order: [['id', 'DESC']],
                where: conditions
            }
            return await ColorPrediction.findAll(options);
        } catch (error) {
            console.log(error);
            throw new Error("Error while fetch winning records");
        }
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Color Prediction BET /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createBet(data) {
        try {
            const bet = await ColorPredictionBet.create(data);
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
            const bet = await ColorPredictionBet.findByPk(id, options);
            if (!bet) {
                throw new Error('Bet not found');
            }
            return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Color Prediction');
        }
    }

    async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
        try {
            const conditionPayload = {};
            if (userId) {
                conditionPayload.user_id = userId;
            }
            if (gameId) {
                conditionPayload.color_prediction_id = gameId;
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

            return await ColorPredictionBet.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Data');
        }
    }

    async viewAllBetsByGameId(gameId, userId = "", attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const conditionPayload = {
                color_prediction_id: gameId
            };
            if (userId) {
                conditionPayload.user_id = userId;
            }
            // apply conditions
            const options = {
                ...attributeOptions,
                where: conditionPayload,
                order: [['id', 'DESC']]
            };

            return await ColorPredictionBet.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Data');
        }
    }

    async walletHistory(userId) {
        try {
            const bets = await ColorPredictionBet.findAll({
                attributes: [
                    '*',
                    'tbl_color_prediction_1_min.room_id'
                    // [db.Sequelize.col('tbl_color_prediction_1_min.room_id'), 'room_id']
                ],
                include: [{
                    model: ColorPrediction,
                    attributes: [],
                    // attributes: ['room_id'], // Specify the room_id column from ColorPrediction
                    // where: {
                    //   id: db.Sequelize.col('tbl_color_prediction_1_min_bet.color_prediction_id')
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

    async gameHistory(roomId, limit = 10) {
        try {
            const queryOptions = {
                where: {
                    status: 1
                },
                order: [['id', 'DESC']]
            }
            if(roomId) {
                queryOptions.where.room_id = roomId;
            }
            if(limit) {
                queryOptions.limit = limit;
            }
            return await ColorPrediction.findAll(queryOptions);
        } catch (error) {
            console.log(error)
            throw new Error('Error while fetching data');
        }
    }

    async myHistory(userId, limit) {
        try {
            const queryOptions = {
                attributes: [
                    '*',
                    'tbl_color_prediction_1_min.status'
                ],
                include: [{
                    model: ColorPrediction,
                    attributes: [],
                }],
                where: {
                    user_id: userId
                },
                order: [['id', 'DESC']],
                raw: true,
                nest: true,
                subQuery: false
            }
            if (limit) {
                queryOptions.limit = limit;
            }
            return await ColorPredictionBet.findAll(queryOptions);
        } catch (error) {
            console.log(error)
            throw new Error('Error while fetching data');
        }
    }

    async updateBet(id, data) {
        try {
            await ColorPredictionBet.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating data');
        }
    }

    async checkBetNumberPlacedByUser(gameId, userId, bet) {
        try {
            return await ColorPredictionBet.findOne({
                attributes: ["id"],
                where: {
                    color_prediction_id: gameId,
                    bet,
                    user_id: userId
                }
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while check bet")
        }
    }

    async getRoomOnlineUsers(roomId) {
        try {
            // First get the latest roulette ID for the given room ID
            const subquery = `(SELECT id FROM tbl_roulette WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

            // Main query to count the bets for the latest roulette
            const result = await ColorPredictionBet.findOne({
                attributes: [
                    [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
                ],
                where: {
                    color_prediction_id: db.Sequelize.literal(`(${subquery})`)
                },
                raw: true
            });

            return result ? result.online : 0;
        } catch (error) {
            console.error('Error fetching online count:', error);
        }
    }

    // Like Helper
    async getBetDataByBets(bets) {
        const betData = {
            totalBetAmount: 0,
            zeroAmount: 0,
            oneAmount: 0,
            twoAmount: 0,
            threeAmount: 0,
            fourAmount: 0,
            fiveAmount: 0,
            sixAmount: 0,
            sevenAmount: 0,
            eightAmount: 0,
            nineAmount: 0,
            greenAmount: 0,
            violetAmount: 0,
            redAmount: 0,
            smallAmount: 0,
            bigAmount: 0
        }
        for (let i = 0; i < bets.length; i++) {
            const bet = bets[i];
            betData.totalBetAmount += bet.amount;
            if (bet.bet == 0) {
                betData.zeroAmount += bet.amount;
            } else if (bet.bet == 1) {
                betData.oneAmount += bet.amount;
            } else if (bet.bet == 2) {
                betData.twoAmount += bet.amount;
            } else if (bet.bet == 3) {
                betData.threeAmount += bet.amount;
            } else if (bet.bet == 4) {
                betData.fourAmount += bet.amount;
            } else if (bet.bet == 5) {
                betData.fiveAmount += bet.amount;
            } else if (bet.bet == 6) {
                betData.sixAmount += bet.amount;
            } else if (bet.bet == 7) {
                betData.sevenAmount += bet.amount;
            } else if (bet.bet == 8) {
                betData.eightAmount += bet.amount;
            } else if (bet.bet == 9) {
                betData.nineAmount += bet.amount;
            } else if (bet.bet == COLOR_PREDICTIONS_BETS.green) {
                betData.greenAmount += bet.amount;
            } else if (bet.bet == COLOR_PREDICTIONS_BETS.violet) {
                betData.violetAmount += bet.amount;
            } else if (bet.bet == COLOR_PREDICTIONS_BETS.red) {
                betData.redAmount += bet.amount;
            } else if (bet.bet == COLOR_PREDICTIONS_BETS.big) {
                betData.bigAmount += bet.amount;
            } else if (bet.bet == COLOR_PREDICTIONS_BETS.small) {
                betData.smallAmount += bet.amount;
            }
        }

        return betData;
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Color Prediction MAP /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createMap(gameId, card) {
        try {
            const payload = {
                color_prediction_id: gameId,
                card
            }
            return await ColorPredictionMap.create(payload);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating Color Prediction Map');
        }
    }

    async getGameCards(gameId) {
        try {
            return ColorPredictionMap.findAll({
                where: {
                    color_prediction_id: gameId
                }
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while get Game Cards");
        }
    }

}

module.exports = new ColorPredictionService();