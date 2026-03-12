const { Op, literal, fn, col } = require('sequelize');
const { Teenpatti, TeenpattiTable, user, TeenpattiTableUser, sequelize, TeenpattiTableMaster, Sequelize, TeenpattiCard, CardRummy, TeenpattiLog, Card, Slideshow, RobotCard } = require('../models');
const { getAttributes, trimByUnderscor, getRandomNumber, getRandomFromFromArray } = require('../utils/util');
const userService = require('./userService');
const { MAX_POINTS, GAMES } = require('../constants');
const { getRummyCardPoints } = require('../utils/cards');
const { UserWalletService } = require("./walletService");
const userWallet = new UserWalletService();

class TeenpattiService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await Teenpatti.findByPk(id, attributeOptions);

            // if (!game) {
            //     throw new Error('Game not found');
            // }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching user');
        }
    }

    async create(data) {
        try {
            return await Teenpatti.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async getTable(tableId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await TeenpattiTable.findByPk(tableId, attributeOptions);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching table');
        }
    }

    async chaalCount(gameId, userId) {
        try {
            return await TeenpattiLog.count({
                where: {
                    game_id: gameId,
                    action: 2,
                    user_id: userId,
                },
            });
        } catch (error) {
            console.log(error)
            throw new Error('Error getting logs');
        }
    }

    async packGame(userId, gameId, timeout = 0) {
        try {
            await TeenpattiCard.update(
                {
                    packed: 1
                },
                {
                    where: {
                        user_id: userId,
                        game_id: gameId
                    }
                }
            );

            const cardDetails = await TeenpattiCard.findOne({
                attributes: ["seen"],
                where: {
                    game_id: gameId,
                    user_id: userId,
                }
            })
            let seen = 0;
            if (cardDetails) {
                seen = cardDetails.seen;
            }

            const payloadData = {
                user_id: userId,
                game_id: gameId,
                seen,
                timeout,
                action: 1
            }

            return await TeenpattiLog.create(payloadData);
        } catch (error) {
            throw new Error(error);
        }
    }

    /*async getMyCards(gameId, userId, card = "") {
        try {
            const whereClause = {
                game_id: gameId,
                user_id: userId
            };

            if (card) {
                whereClause.card = card;
            }

            return await TeenpattiCard.findAll({
                attributes: [
                    'id',
                    'card',
                    [sequelize.fn('SUBSTRING', sequelize.col('card'), 1, 2), 'card_group']
                ],
                where: whereClause
            });

            // return results.map(result => result.get({ plain: true }));
        } catch (error) {
            console.error('Error querying tbl_rummy_card:', error);
            throw error;
        }
    }*/

    async getMyCards(gameId, userId, seen = 0) {
        try {
            const whereClause = {
                game_id: gameId,
                user_id: userId
            };

            if (seen) {
                TeenpattiCard.update({ seen: 1 }, {
                    where: whereClause
                });
            }

            return await TeenpattiCard.findOne({
                attributes: [
                    'id', 'card1', 'card2', 'card3', 'seen'
                ],
                where: whereClause
            });
        } catch (error) {
            console.error('Error querying tbl_rummy_deal_card:', error);
            throw error;
        }
    }

    async gameUserCard(gameId, userId) {
        try {
            const whereClause = {
                game_id: gameId,
                user_id: userId,
                packed: 0
            };

            return await TeenpattiCard.findOne({
                attributes: [
                    'id', 'card1', 'card2', 'card3', 'seen'
                ],
                where: whereClause
            });
        } catch (error) {
            console.error('Error querying tbl_rummy_deal_card:', error);
            throw error;
        }
    }

    async giveGameCards(data) {
        try {
            return await TeenpattiCard.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error while get card');
        }
    }

    async addGameLog(data) {
        try {
            return await TeenpattiLog.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async getRobotCards(limit) {
        try {
            return await RobotCard.findAll({
                order: sequelize.random(),
                limit
            })
        } catch (error) {
            throw new Error(error)
        }
    }

    async getCards(limit, robot_card_selected) {
        const cards = await Card.findAll({
            where: robot_card_selected?.length > 0 ? {
                cards: {
                    [Op.notIn]: robot_card_selected,
                },
            } : {},
            order: sequelize.random(),
            limit
        });

        return cards;
    };

    async cardValue(card1, card2, card3) {
        let rule = 1;
        let card1Value = 0;
        let card2Value = 0;
        let card3Value = 0;

        let card1Color = card1.substring(0, 2);
        let card1Num = card1.substring(2);

        let card2Color = card2.substring(0, 2);
        let card2Num = card2.substring(2);

        let card3Color = card3.substring(0, 2);
        let card3Num = card3.substring(2);

        if ((card1Num == card2Num) && (card2Num == card3Num)) {
            card1Num = getRummyCardPoints(card1Num);
            card1Num = parseInt(card1Num);
            rule = 6;
            card1Value = card1Num;
        } else {
            card1Num = getRummyCardPoints(card1Num);
            card2Num = getRummyCardPoints(card2Num);
            card3Num = getRummyCardPoints(card3Num);

            card1Num = parseInt(card1Num);
            card2Num = parseInt(card2Num);
            card3Num = parseInt(card3Num);

            let arr = [card1Num, card2Num, card3Num];
            arr.sort((a, b) => a - b);

            let sequence = false;
            if ((arr[0] === arr[1] - 1) && (arr[1] === arr[2] - 1)) {
                sequence = true;
            }

            // Exception for A23
            if (arr[0] === 2 && arr[1] === 3 && arr[2] === 14) {
                sequence = true;
                arr[2] = 3;
            }

            let color = false;
            if (card1Color === card2Color && card2Color === card3Color) {
                color = true;
            }

            if (sequence && color) {
                rule = 5;
                card1Value = arr[2];
            } else if (sequence) {
                rule = 4;
                card1Value = arr[2];
            } else if (color) {
                rule = 3;
                card1Value = arr[2];
            } else {
                if ((card1Num === card2Num) || (card2Num === card3Num) || (card1Num === card3Num)) {
                    rule = 2;
                    if (card1Num === card2Num) {
                        card1Value = card1Num;
                        card2Value = card3Num;
                    } else if (card2Num === card3Num) {
                        card1Value = card2Num;
                        card2Value = card1Num;
                    } else if (card1Num === card3Num) {
                        card1Value = card3Num;
                        card2Value = card2Num;
                    }
                } else {
                    rule = 1;
                    card1Value = arr[2];
                    card2Value = arr[1];
                    card3Value = arr[0];
                }
            }
        }
        return [rule, card1Value, card2Value, card3Value];
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
                            winner = 0;
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

    async tableUsers(tableId) {
        try {
            const tableUsers = await TeenpattiTableUser.findAll({
                attributes: [
                    'id', 'user_id', 'table_id', 'seat_position',
                    [sequelize.col('users.name'), 'name'],
                    [sequelize.col('users.mobile'), 'mobile'],
                    [sequelize.col('users.profile_pic'), 'profile_pic'],
                    [sequelize.col('users.wallet'), 'wallet'],
                    [sequelize.col('users.user_type'), 'user_type'],
                    // [sequelize.col('table.boot_value'), 'boot_value']
                ],
                include: [
                    {
                        model: user,
                        as: 'users',
                        attributes: []
                    },
                    // {
                    //     model: TeenpattiTable,
                    //     as: 'table',
                    //     attributes: []
                    // }
                ],
                where: {
                    table_id: tableId
                },
                order: [['seat_position', 'ASC']]
            });

            return tableUsers;
        } catch (error) {
            console.error('Error fetching table users:', error);
        }
    }

    async getTableMaster(bootValue = "") {
        const whereConditions = {};
        if (bootValue) {
            whereConditions.boot_value = bootValue;
        }
        const tables = await TeenpattiTableMaster.findAll({
            attributes: [
                'id',
                'boot_value',
                'maximum_blind',
                'chaal_limit',
                'pot_limit',
                [literal('FLOOR(100 + RAND()*(200-100))'), 'online_members'],
                [literal('(tbl_table_master.boot_value) * 50'), 'min_amount'],
            ],
            include: [
                {
                    model: TeenpattiTable,
                    // as: 'tbl_table',
                    required: false,
                    attributes: [], // Exclude attributes from this table in the final result
                    // on: {
                    //     boot_value: literal('tbl_table_master.boot_value = tbl_table.boot_value'),
                    // },
                    include: [
                        {
                            model: user,
                            as: 'users',
                            required: false,
                            attributes: [], // Exclude attributes from this table in the final result
                        }
                    ]
                }
            ],
            where: whereConditions,
            group: [
                'tbl_table_master.boot_value'
            ],
            order: [['boot_value', 'ASC']],
            // raw: true,
            // nest: true,
            // subQuery: false
        });
        return tables;
    }

    async update(id, data) {
        try {
            await Teenpatti.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating setting');
        }
    }

    async getCustomizeActiveTable(tableAmount) {
        const whereConditions = {
            boot_value: tableAmount,
        };
        const activeTables = await user.findAll({
            attributes: [
                'id',
                'table_id',
                [Sequelize.fn('COUNT', Sequelize.col('tbl_users.id')), 'members']
            ],
            include: [
                {
                    model: TeenpattiTable,
                    required: true,
                    attributes: [],
                    where: whereConditions
                }
            ],
            where: {
                table_id: {
                    [Op.ne]: 0
                }
            },
            group: [
                'tbl_users.table_id'
            ]
        });

        return activeTables;
    }

    async getAvailableSeatPosition(tableId) {
        try {
            const sql = `
            SELECT * FROM (
                SELECT 1 AS mycolumn
                UNION SELECT 2
                UNION SELECT 3
                UNION SELECT 4
                UNION SELECT 5
            ) a
            WHERE mycolumn NOT IN (
                SELECT seat_position FROM tbl_table_user WHERE table_id = :tableId AND isDeleted = 0
            )
            LIMIT 1
            `;

            const result = await sequelize.query(sql, {
                replacements: { tableId },
                type: sequelize.QueryTypes.SELECT
            });

            if (result.length > 0) {
                return result[0].mycolumn;
            } else {
                return false;
            }
        } catch (error) {
            console.error('Error fetching available seat position:', error);
            throw error;
        }
    }

    async getActiveGameOnTable(tableId, attributes = []) {
        const attributeOptions = getAttributes(attributes);
        const options = {
            ...attributeOptions,
            where: {
                winner_id: 0,
                table_id: tableId
            },
            order: [['id', 'DESC']]
        }
        return await Teenpatti.findOne(options);
    }



    async createTable(data) {
        try {
            return await TeenpattiTable.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async addTableUser(data) {
        try {
            const tableUser = await TeenpattiTableUser.create(data);

            if (data.table_id && data.user_id) {
                userService.update(data.user_id, { table_id: data.table_id })
            }

            return tableUser;
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async removeTableUser(data) {
        try {
            await TeenpattiTableUser.update(
                {
                    isDeleted: 1
                },
                {
                    where: {
                        user_id: data.user_id,
                        table_id: data.table_id
                    }
                });

            if (data.user_id && data.table_id) {
                await userService.update(data.user_id, { table_id: 0 })
            }
        } catch (error) {
            console.log(error)
            throw new Error(error);
        }
    }

    async gameOnlyUsers(gameId) {
        const whereClause = {
            game_id: gameId,
        };
        const results = await TeenpattiCard.findAll({
            attributes: ['user_id', 'packed', 'seen'],
            where: whereClause
        });
        return results;
    }

    async checkUsersOnTable(tableId) {
        try {
            return await userService.getSingleByConditions({ table_id: tableId }, ["id"])
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async gameUsers(gameId) {
        try {
            return await TeenpattiCard.findAll({
                where: {
                    // packed: false,
                    packed: false,
                    game_id: gameId
                },
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async gameAllUsers(gameId, userId = "") {
        try {
            const conditions = {
                game_id: gameId
            }
            if (userId) {
                conditions.user_id = userId
            }
            return await TeenpattiCard.findAll({
                attributes: ['tbl_game_card.*', 'user.name', 'user.user_type'],
                include: [{
                    model: user,
                    as: "user",
                    attributes: [],
                    // where: { id: sequelize.col('tbl_game_card.user_id') }
                }],
                where: conditions,
                raw: true
            });
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async showGame(gameId, userId, amount) {
        try {
            // minus wallet
            await userWallet.minusUserWallet(userId, amount, GAMES.teenpatti);
            await Teenpatti.update({
                amount: sequelize.literal(`amount + ${amount}`)
            }, {
                where: { id: gameId }
            });

            const gameCard = await TeenpattiCard.findOne({
                attributes: ["seen"],
                where: {
                    game_id: gameId,
                    user_id: userId
                }
            });
            const seen = gameCard?.seen;
            const gameLogPayload = {
                user_id: userId,
                game_id: gameId,
                seen,
                action: 3,
                amount
            }
            await TeenpattiLog.create(gameLogPayload);
        } catch (error) {
            throw new Error(error);
        }
    }

    async getSlideShowById(slideId) {
        try {
            return await Slideshow.findOne({
                where: {
                    id: slideId,
                    status: 0
                }
            });
        } catch (error) {
            throw new Error(error);
        }
    }

    async updateSlideShow(id, status) {
        try {
            return await Slideshow.update({
                status
            }, {
                where: {
                    id
                }
            });
        } catch (error) {
            throw new Error(error);
        }
    }

    async slideShow(gameId, userId, prevId) {
        try {
            const payload = {
                game_id: gameId,
                user_id: userId,
                prev_id: prevId,
                status: 0
            }
            return await Slideshow.create(payload);
        } catch (error) {
            throw new Error(error);
        }
    }

    async getSlideShow(gameId, status = "") {
        try {
            const queryOptions = {
                attributes: ['tbl_slide_show.*', 'user.name'],
                include: [{
                    model: user,
                    as: "user",
                    attributes: []
                }],
                where: {
                    game_id: gameId
                },
                order: [['id', 'DESC']]
            };

            if (status) {
                queryOptions.where.status = status;
            }

            return await Slideshow.findAll(queryOptions)
        } catch (error) {
            throw new Error(error);
        }
    }

    async chaalGame(gameId, amount, userId, plus = 0) {
        try {
            // minus wallet
            await userWallet.minusUserWallet(userId, amount, GAMES.teenpatti);
            await Teenpatti.update({
                amount: sequelize.literal(`amount + ${amount}`)
            }, {
                where: { id: gameId }
            });

            const gameCard = await TeenpattiCard.findOne({
                attributes: ["seen"],
                where: {
                    game_id: gameId,
                    user_id: userId
                }
            });
            const seen = gameCard?.seen;
            const gameLogPayload = {
                user_id: userId,
                game_id: gameId,
                seen,
                action: 2,
                plus,
                amount
            }
            await TeenpattiLog.create(gameLogPayload);
        } catch (error) {
            throw new Error(error);
        }
    }

    async getGameBot(gameId) {
        try {
            return await user.findOne({
                include: [
                    {
                        model: TeenpattiCard,
                        // as: 'tbl_game_card',
                        required: true,
                        attributes: [],
                        where: {
                            packed: false,
                            game_id: gameId
                        }
                    }
                ],
                where: {
                    user_type: 1,
                }
            });
        } catch (error) {
            console.log(error)
            throw new Error(error)
        }
    }

    async gameLog(gameId, limit = '', user_id) {
        try {
            const conditions = {
                game_id: gameId
            };
            if (user_id) {
                conditions.user_id = user_id
            }
            const options = {
                order: [["id", "DESC"]],
                where: conditions
            }
            if (limit) {
                options.limit = limit;
            }
            return await TeenpattiLog.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch game logs");
        }
    }


    async lastChaal(gameId) {
        try {
            return await TeenpattiLog.findOne({
                where: {
                    game_id: gameId,
                    action: {
                        [Op.in]: [0, 2],
                    }
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch last game card")
        }
    }

    async gameUserCard(gameId, userId) {
        try {
            return await TeenpattiCard.findOne({
                where: {
                    packed: false,
                    user_id: userId,
                    game_id: gameId
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch last game card")
        }
    }

    async walletHistory(userId) {
        try {
            const query = await TeenpattiLog.findAll({
                attributes: [
                    'game_id',
                    [fn('SUM', col('amount')), 'invest'],
                    [
                        literal(`(
                            SELECT user_winning_amt 
                            FROM tbl_game 
                            WHERE winner_id = ${userId} 
                            AND id = game_id
                        )`),
                        'winning_amount'
                    ],
                    'added_date',
                    [col('tbl_table.private'), 'table_type']
                ],
                include: [
                    {
                        model: Teenpatti,
                        attributes: [],
                        // on: { id: col('tbl_game_log.game_id') }
                        include: [
                            {
                                model: TeenpattiTable,
                                attributes: [],
                                // on: { id: col('tbl_game.table_id') }
                            }
                        ],
                    },
                    /*{
                        model: TeenpattiTable,
                        attributes: [],
                    }*/
                ],
                where: { user_id: userId },
                group: ['game_id'],
                order: [['game_id', 'DESC']],
                raw: true
            });

            return query;
        } catch (error) {
            console.log(error)
            throw new Error('Error while fetching data');
        }
    }

    // for optimization
    async getCardsForWinner() {
        const randomNumber = getRandomNumber(1, 6);
        const totalNumberArray = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        const colorArray = ['BP', 'BL', 'RS', 'RP'];
        let cards = {};
        switch (randomNumber) {
            case 1: // Pair
                const numberIndex = getRandomFromFromArray(totalNumberArray, 2);
                const number1 = totalNumberArray[numberIndex[0]]
                const number2 = totalNumberArray[numberIndex[1]];
                cards = { card1: "BP" + number1, card2: "RP" + number1, card3: "BL" + number2 }
                break;

            case 2: // Color
                const colorIndex = getRandomNumber(0, (colorArray.length - 1));
                const color = colorArray[colorIndex];
                cards = { card1: color + "A", card2: color + "8", card3: color + "3" }
                break;

            case 3: // Sequence
                const number = getRandomNumber(2, 7);
                cards = { card1: "RP" + number, card2: "BL" + (number + 1), card3: "BP" + (number + 2) }
                break;

            case 4: // Pure Seq
                const pureColorIndex = getRandomNumber(0, (colorArray.length - 1));
                const pureColor = colorArray[pureColorIndex];
                const pureNumber = getRandomNumber(2, 7);
                cards = { card1: (pureColor + pureNumber), card2: (pureColor + (pureNumber + 1)), card3: pureColor + (pureNumber + 2) }
                break;

            case 5: // Set
                const setNumberIndex = getRandomNumber(0, (totalNumberArray.length - 1));
                const setNumber = totalNumberArray[setNumberIndex];
                cards = { card1: "BP" + setNumber, card2: "RP" + setNumber, card3: "BL" + setNumber }
                break;

            case 6: // Set
                const HIGH_CARDS = [
                    { card1: "BPA", card2: "RSK", card3: "BL10" },
                    { card1: "BPA", card2: "RSQ", card3: "BL2" },
                    { card1: "RSA", card2: "BP10", card3: "BL8" },
                    { card1: "BLA", card2: "BLQ", card3: "RP9" },
                    { card1: "RPA", card2: "RSJ", card3: "BL10" },
                    { card1: "RPA", card2: "RSK", card3: "BL6" },
                    { card1: "RSA", card2: "BLQ", card3: "RP4" },
                    { card1: "BPA", card2: "BL7", card3: "BL10" },
                    { card1: "RPA", card2: "BLQ", card3: "BL2" },
                    { card1: "RSA", card2: "RP10", card3: "BL9" },
                ];
                const randomCard = getRandomNumber(0, 9);
                cards = HIGH_CARDS[randomCard];
                break;

            default:
                break;
        }
        return [cards];
    }

    async getColorCards() {


    }

    async getSequenceCards() {

    }
}

module.exports = new TeenpattiService();