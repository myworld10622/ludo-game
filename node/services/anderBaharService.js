const { Op } = require('sequelize');
const { ANDER_BAHER_BETS, ANDER_BAHER_WINNING_PRICE } = require('../constants');
const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const AnderBahar = db.AnderBahar;
const AnderBaharBet = db.AnderBaharBet;
const AnderBaharRoom = db.AnderBaharRoom;
const AnderBaharMap = db.AnderBaharMap;
const Card = db.Card;

class AnderBaharService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await AnderBahar.findByPk(id, attributeOptions);
            if (!game) {
                throw new Error('Game not found');
            }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Ander Bahar');
        }
    }

    async create(data) {
        try {
            return await AnderBahar.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating Ander Bahar');
        }
    }

    async update(id, data) {
        try {
            await AnderBahar.update(data, {
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
        let rooms = await AnderBaharRoom.findAll(options);

        if (roomId && userId) {
            userService.update(userId, { ander_bahar_room_id: roomId })
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
            let activeGame = await AnderBahar.findAll(options);

            return activeGame;
        } catch (error) {
            console.log(error)
            throw new Error('Error while place bet');
        }
    }

    async getCards(limit = '', notEqualTo = '', equalTo = '') {
        try {
            const whereConditions = [];
            if (notEqualTo) {
                whereConditions.push({
                    cards: {
                        [Op.notLike]: `%${notEqualTo}%`
                    }
                });
            }
            if (equalTo) {
                whereConditions.push({
                    cards: {
                        [Op.like]: `%${equalTo}%`
                    }
                });
            }

            const options = {
                where: whereConditions.length > 0 ? { [Op.and]: whereConditions } : {},
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
                order: [['id', 'DESC']]
            }
            return await AnderBahar.findAll(options);
        } catch (error) {
            console.log(error);
            throw new Error("Error while fetch winning records");
        }
    }

    // Like Helper
    async getBetDataByBets(bets, calculation = 0) {
        const betData = {
            totalBetAmount: 0,
            anderBetAmount: 0,
            baherBetAmount: 0
        }
        for (let i = 0; i < bets.length; i++) {
            const bet = bets[i];
            betData.totalBetAmount += bet.amount;
            if (bet.bet == ANDER_BAHER_BETS.ander) {
                if (calculation) {
                    betData.anderBetAmount += (+bet.amount * ANDER_BAHER_WINNING_PRICE.ander);
                } else {
                    betData.anderBetAmount += (+bet.amount);
                }
            }
            else if (bet.bet == ANDER_BAHER_BETS.baher) {
                if (calculation) {
                    betData.baherBetAmount += (+bet.amount * ANDER_BAHER_WINNING_PRICE.baher);
                } else {
                    betData.baherBetAmount += (+bet.amount);
                }
            }
        }

        return betData;
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Ander Bahar BET /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createBet(data) {
        try {
            return await AnderBaharBet.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error while place bet');
        }
    }

    async getBetById(id, attributes = []) {
        try {
            const options = getAttributes(attributes)
            const bet = await AnderBaharBet.findByPk(id, options);
            if (!bet) {
                throw new Error('Bet not found');
            }
            return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Ander Bahar');
        }
    }

    async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
        try {
            const conditionPayload = {};
            if (userId) {
                conditionPayload.user_id = userId;
            }
            if (gameId) {
                conditionPayload.ander_baher_id = gameId;
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

            return await AnderBaharBet.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Data');
        }
    }

    async viewAllBetsByGameId(gameId, userId = "", attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const conditionPayload = {
                ander_baher_id: gameId
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

            return await AnderBaharBet.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Data');
        }
    }

    async walletHistory(userId) {
        try {
            const bets = await AnderBaharBet.findAll({
                attributes: [
                    '*',
                    'andar_bahar.room_id'
                ],
                include: [{
                    model: AnderBahar,
                    required: true,
                    as: "andar_bahar",
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
            await AnderBaharBet.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating data');
        }
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Ander Bahar MAP /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createMap(gameId, card) {
        try {
            const payload = {
                ander_bahar_id: gameId,
                card
            }
            return await AnderBaharMap.create(payload);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating Ander Bahar Map');
        }
    }

    async getGameCards(gameId) {
        try {
            return AnderBaharMap.findAll({
                where: {
                    ander_bahar_id: gameId
                }
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while get Game Cards");
        }
    }

}

module.exports = new AnderBaharService();