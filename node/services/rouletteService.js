const { ROULETTE_BETS, R_TWELFTH_1ST, R_TWELFTH_2ND, R_TWELFTH_3RD, R_EIGHTEENTH_1ST, R_EIGHTEENTH_2ND, R_ODD, R_EVEN, R_RED, R_BLACK, R_ROW_1, R_ROW_2, R_ROW_3, R_1_2, R_2_3, R_4_5, R_5_6, R_7_8, R_8_9, R_10_11, R_11_12, R_13_14, R_14_15, R_16_17, R_17_18, R_19_20, R_20_21, R_22_23, R_23_24, R_25_26, R_26_27, R_28_29, R_29_30, R_31_32, R_32_33, R_34_35, R_35_36, R_0_1, R_0_2, R_0_3, R_1_4, R_2_5, R_3_6, R_4_7, R_5_8, R_6_9, R_7_10, R_8_11, R_9_12, R_10_13, R_11_14, R_12_15, R_13_16, R_14_17, R_15_18, R_16_19, R_17_20, R_18_21, R_19_22, R_20_23, R_21_24, R_22_25, R_23_26, R_24_27, R_25_28, R_26_29, R_27_30, R_28_31, R_29_32, R_30_33, R_31_34, R_32_35, R_33_36, R_0_1_2, R_0_2_3, R_1_2_4_5, R_2_3_5_6, R_4_5_7_8, R_5_6_8_9, R_7_8_10_11, R_8_9_11_12, R_10_11_13_14, R_11_12_14_15, R_13_14_16_17, R_14_15_17_18, R_16_17_19_20, R_17_18_20_21, R_19_20_22_23, R_20_21_23_24, R_22_23_25_26, R_23_24_26_27, R_25_26_28_29, R_26_27_29_30, R_28_29_31_32, R_29_30_32_33, R_31_32_34_35, R_32_33_35_36 } = require('../constants');
const db = require('../models');
const { getAttributes, getRoundNumber, getRandomNumber } = require('../utils/util');
const userService = require('./userService');
const Roulette = db.Roulette;
const RouletteBet = db.RouletteBet;
const RouletteRoom = db.RouletteRoom;
const RouletteMap = db.RouletteMap;
const Card = db.Card;
const RouletteTempBet = db.RouletteTempBet;

class RouletteService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await Roulette.findByPk(id, attributeOptions);
            if (!game) {
                throw new Error('Game not found');
            }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Roulette');
        }
    }

    async create(data) {
        try {
            return await Roulette.create(data);
            // await userService.update(data.user_id, { todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`) })
            // return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error creating Roulette');
        }
    }

    async update(id, data) {
        try {
            await Roulette.update(data, {
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
        let rooms = await RouletteRoom.findAll(options);

        if (roomId && userId) {
            userService.update(userId, { roulette_id: roomId })
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
            let activeGame = await Roulette.findAll(options);

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

    async lastWinningBet(roomId, limit = 10) {
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
            return await Roulette.findAll(options);
        } catch (error) {
            console.log(error);
            throw new Error("Error while fetch winning records");
        }
    }

    async totalBetAmount(gameId, bet = "") {
        try {
            const whereClause = { roulette_id: gameId };

            if (bet !== '') {
                whereClause.bet = bet;
            }

            const result = await RouletteBet.findOne({
                attributes: [
                    [db.sequelize.fn('SUM', db.sequelize.col('amount')), 'amount']
                ],
                where: whereClause,
            });

            return result ? result.get('amount') : 0;
        } catch (error) {
            console.error('Error fetching sum amount:', error);
        }
    }

    async getRoomOnlineUsers(roomId) {
        try {
            // First get the latest roulette ID for the given room ID
            const subquery = `(SELECT id FROM tbl_roulette WHERE room_id = ${roomId} ORDER BY id DESC LIMIT 1)`;

            // Main query to count the bets for the latest roulette
            const result = await RouletteBet.findOne({
                attributes: [
                    [db.Sequelize.fn('COUNT', db.Sequelize.col('id')), 'online']
                ],
                where: {
                    roulette_id: db.Sequelize.literal(`(${subquery})`)
                },
                raw: true
            });

            return result ? result.online : 0;
        } catch (error) {
            console.error('Error fetching online count:', error);
        }
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Roulette BET /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createBet(data) {
        try {
            const bet = await RouletteBet.create(data);
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
            const bet = await RouletteBet.findByPk(id, options);
            if (!bet) {
                throw new Error('Bet not found');
            }
            return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Roulette');
        }
    }

    async viewBet(userId = '', gameId = '', bet = '', betId = '', limit = '') {
        try {
            const conditionPayload = {};
            if (userId) {
                conditionPayload.user_id = userId;
            }
            if (gameId) {
                conditionPayload.roulette_id = gameId;
            }
            if (bet !== '') {
                conditionPayload.bet = bet;
                if (bet && Array.isArray(bet)) {
                    conditionPayload.bet = {
                        [db.Sequelize.Op.in]: bet
                    };
                }
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

            return await RouletteBet.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Data');
        }
    }

    async walletHistory(userId) {
        try {
            const bets = await RouletteBet.findAll({
                attributes: [
                    '*',
                    'tbl_roulette.room_id'
                ],
                include: [{
                    model: Roulette,
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
            await RouletteBet.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating data');
        }
    }

    async checkBetNumberPlacedByUser(gameId, userId, bet) {
        try {
            return await RouletteBet.findOne({
                attributes: ["id"],
                where: {
                    roulette_id: gameId,
                    bet,
                    user_id: userId
                }
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while check bet")
        }
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Roulette MAP /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createMap(gameId, card) {
        try {
            const payload = {
                roulette_id: gameId,
                card
            }
            return await RouletteMap.create(payload);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating Roulette Map');
        }
    }

    async getGameCards(gameId) {
        try {
            return RouletteMap.findAll({
                where: {
                    roulette_id: gameId
                }
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while get Game Cards");
        }
    }

    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Roulette Temp Bet /////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createTempBet(gameId) {
        try {
            const payload = [];
            for (let index = 0; index < 37; index++) {
                payload.push({ roulette_id: gameId, amount: 0, bet: index })
            }
            return await RouletteTempBet.bulkCreate(payload);
        } catch (error) {
            console.log(error);
            throw new Error("Error while bulk insert temp bet");
        }
    }

    async updateTempBet(gameId, bet, amount) {
        try {
            // get bet array
            const betArray = ROULETTE_BETS[bet];
            // distribute the amout equally
            let distributeBetAmount = amount / betArray.length;
            distributeBetAmount = getRoundNumber(distributeBetAmount, 5);
            // get bets and update amout bet wise
            const bets = await RouletteTempBet.findAll({
                where: {
                    roulette_id: gameId,
                    bet: {
                        [db.Sequelize.Op.in]: betArray
                    }
                }
            });

            for (let index = 0; index < bets.length; index++) {
                const element = bets[index];
                console.log(element.id, distributeBetAmount)
                await RouletteTempBet.update({
                    amount: db.sequelize.literal(`amount + ${distributeBetAmount}`)
                }, {
                    where: { id: element.id }
                });
            }
        } catch (error) {
            console.log(error);
            throw new Error("Error while bulk update temp bet");
        }
    }

    async getMinimumTempBet(gameId, bet = "") {
        try {
            const options = {
                where: {
                    roulette_id: gameId,
                },
                order: [['amount', 'ASC']]
            }
            if (bet !== "") {
                options.where.bet = bet;
            }
            const bets = await RouletteTempBet.findAll(options);
            return bets;
        } catch (error) {
            console.log(error)
            throw new Error('Error while place bet');
        }
    }

    async getLeastBets(data) {
        let minBetValue = Infinity;
        const minBetList = [];

        // data.forEach(obj => {
        //     if (obj.amount < minBetValue) {
        //         minBetValue = obj.amount;
        //         minBetList.length = 0; // Clear the list as a new minimum is found
        //         minBetList.push(obj);
        //     } else if (obj.amount === minBetValue) {
        //         minBetList.push(obj);
        //     }
        // });
        for (let i = 0; i < data.length; i++) {
            const item = data[i];
            if (item.amount < minBetValue) {
                minBetValue = item.amount;
                minBetList.length = 0;
                minBetList.push(item);
            } else if (item.amount === minBetValue) {
                minBetList.push(item);
            }
        }

        const minRandomIndex = getRandomNumber(0, (minBetList.length - 1));
        const minObj = minBetList[minRandomIndex];
        return minObj.bet;
    }

    async getPlacedBetList(data) {
        const betPlacedList = [];
        for (let i = 0; i < data.length; i++) {
            const item = data[i];
            if (item.amount > 0) {
                betPlacedList.push(item);
            }
        }
        return betPlacedList;
    }

    async clearTempBetEntries(gameId) {
        try {
            await RouletteTempBet.destroy({
                where: {
                    roulette_id: gameId
                }
            })
        } catch (error) {
            throw new Error("Error while delete temp bets");
        }
    }


    // Logic for calculate Price
    async getWinnerNumbersByBet(winner) {
        let color = '';
        let oddEven = '';
        let twelthColumn = '';
        let eighteenColumn = '';
        let row = '';
        let split2 = [];
        let split4 = [];

        switch (winner) {
            case 0:
                row = R_ROW_2;
                split2 = [R_0_1, R_0_2, R_0_3];
                split4 = [R_0_1_2, R_0_2_3];
                break;
            case 1:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_3;
                split2 = [R_0_1, R_1_2, R_1_4];
                split4 = [R_0_1_2, R_1_2_4_5];
                break;
            case 2:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_2;
                split2 = [R_0_2, R_1_2, R_2_3, R_2_5];
                split4 = [R_0_1_2, R_1_2_4_5, R_0_2_3, R_2_3_5_6];
                break;
            case 3:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_1;
                split2 = [R_0_3, R_2_3, R_3_6];
                split4 = [R_0_2_3, R_2_3_5_6];
                break;
            case 4:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_3;
                split2 = [R_1_4, R_4_5, R_4_7];
                split4 = [R_1_2_4_5, R_4_5_7_8];
                break;
            case 5:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_2;
                split2 = [R_2_5, R_4_5, R_5_6, R_5_8];
                split4 = [R_1_2_4_5, R_4_5_7_8, R_2_3_5_6, R_5_6_8_9];
                break;
            case 6:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_1;
                split2 = [R_3_6, R_6_9, R_5_6];
                split4 = [R_2_3_5_6, R_5_6_8_9];
                break;
            case 7:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_3;
                split2 = [R_4_7, R_7_8, R_7_10];
                split4 = [R_4_5_7_8, R_7_8_10_11];
                break;
            case 8:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_2;
                split2 = [R_5_8, R_7_8, R_8_9, R_8_11];
                split4 = [R_4_5_7_8, R_7_8_10_11, R_5_6_8_9, R_8_9_11_12];
                break;
            case 9:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_1;
                split2 = [R_6_9, R_8_9, R_9_12];
                split4 = [R_5_6_8_9, R_8_9_11_12];
                break;
            case 10:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_3;
                split2 = [R_7_10, R_10_11, R_10_13];
                split4 = [R_7_8_10_11, R_10_11_13_14];
                break;
            case 11:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_2;
                split2 = [R_8_11, R_10_11, R_11_12, R_11_14];
                split4 = [R_7_8_10_11, R_10_11_13_14, R_11_12_14_15, R_8_9_11_12];
                break;
            case 12:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_1ST;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_1;
                split2 = [R_9_12, R_11_12, R_12_15];
                split4 = [R_8_9_11_12, R_11_12_14_15];
                break;
            case 13:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_3;
                split2 = [R_10_13, R_13_14, R_13_16];
                split4 = [R_10_11_13_14, R_13_14_16_17];
                break;
            case 14:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_2;
                split2 = [R_11_14, R_13_14, R_14_15, R_14_17];
                split4 = [R_10_11_13_14, R_13_14_16_17, R_11_12_14_15, R_14_15_17_18];
                break;
            case 15:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_1;
                split2 = [R_12_15, R_14_15, R_15_18];
                split4 = [R_11_12_14_15, R_14_15_17_18];
                break;
            case 16:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_3;
                split2 = [R_13_16, R_16_17, R_16_19];
                split4 = [R_13_14_16_17, R_16_17_19_20];
                break;
            case 17:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_2;
                split2 = [R_14_17, R_16_17, R_17_18, R_17_20];
                split4 = [R_13_14_16_17, R_16_17_19_20, R_14_15_17_18, R_17_18_20_21];
                break;
            case 18:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_1ST;
                row = R_ROW_1;
                split2 = [R_15_18, R_17_18, R_18_21];
                split4 = [R_14_15_17_18, R_17_18_20_21];
                break;
            case 19:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_3;
                split2 = [R_16_19, R_19_20, R_19_22];
                split4 = [R_16_17_19_20, R_19_20_22_23];
                break;
            case 20:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_2;
                split2 = [R_17_20, R_19_20, R_20_21, R_20_23];
                split4 = [R_16_17_19_20, R_19_20_22_23, R_17_18_20_21, R_20_21_23_24];
                break;
            case 21:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_1;
                split2 = [R_18_21, R_20_21, R_21_24];
                split4 = [R_17_18_20_21, R_20_21_23_24];
                break;
            case 22:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_3;
                split2 = [R_19_22, R_22_23, R_22_25];
                split4 = [R_19_20_22_23, R_22_23_25_26];
                break;
            case 23:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_2;
                split2 = [R_20_23, R_22_23, R_23_24, R_23_26];
                split4 = [R_19_20_22_23, R_22_23_25_26, R_20_21_23_24, R_23_24_26_27];
                break;
            case 24:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_2ND;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_1;
                split2 = [R_21_24, R_23_24, R_24_27];
                split4 = [R_20_21_23_24, R_23_24_26_27];
                break;
            case 25:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_3;
                split2 = [R_22_25, R_25_26, R_25_28];
                split4 = [R_22_23_25_26, R_25_26_28_29];
                break;
            case 26:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_2;
                split2 = [R_23_26, R_25_26, R_26_27, R_26_29];
                split4 = [R_22_23_25_26, R_25_26_28_29, R_23_24_26_27, R_26_27_29_30];
                break;
            case 27:
                color = R_RED;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_1;
                split2 = [R_24_27, R_26_27, R_27_30];
                split4 = [R_23_24_26_27, R_26_27_29_30];
                break;
            case 28:
                color = R_BLACK;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_3;
                split2 = [R_25_28, R_28_29, R_28_31];
                split4 = [R_25_26_28_29, R_28_29_31_32];
                break;
            case 29:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_2;
                split2 = [R_26_29, R_28_29, R_29_30, R_29_32];
                split4 = [R_25_26_28_29, R_26_27_29_30, R_28_29_31_32, R_29_30_32_33];
                break;
            case 30:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_1;
                split2 = [R_27_30, R_29_30, R_30_33];
                split4 = [R_26_27_29_30, R_29_30_32_33];
                break;
            case 31:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_3;
                split2 = [R_28_31, R_31_32, R_31_34];
                split4 = [R_28_29_31_32, R_31_32_34_35];
                break;
            case 32:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_2;
                split2 = [R_29_32, R_31_32, R_32_33, R_32_35];
                split4 = [R_28_29_31_32, R_31_32_34_35, R_29_30_32_33, R_32_33_35_36];
                break;
            case 33:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_1;
                split2 = [R_30_33, R_32_33, R_33_36];
                split4 = [R_29_30_32_33, R_32_33_35_36];
                break;
            case 34:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_3;
                split2 = [R_31_34, R_34_35];
                split4 = [R_31_32_34_35];
                break;
            case 35:
                color = R_BLACK;
                oddEven = R_ODD;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_2;
                split2 = [R_32_35, R_34_35, R_35_36];
                split4 = [R_31_32_34_35, R_32_33_35_36];
                break;
            case 36:
                color = R_RED;
                oddEven = R_EVEN;
                twelthColumn = R_TWELFTH_3RD;
                eighteenColumn = R_EIGHTEENTH_2ND;
                row = R_ROW_1;
                split2 = [R_33_36, R_35_36];
                split4 = [R_32_33_35_36];
                break;
            default:
                color = '';
                oddEven = '';
                twelthColumn = '';
                eighteenColumn = '';
                row = '';
                split2 = [];
                split4 = [];
                break;
        }

        return { color, oddEven, twelthColumn, eighteenColumn, row, split2, split4 }
    }

}

module.exports = new RouletteService();