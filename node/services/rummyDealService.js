const { Op } = require('sequelize');
const { RummyDeal, RummyDealTable, user, RummyDealTableUser, sequelize, RummyDealTableMaster, Sequelize, RummyDealCard, CardRummy, RummyDealLog, RummyDealCardDrop, ShareWallet } = require('../models');
const { getAttributes, trimByUnderscor } = require('../utils/util');
const userService = require('./userService');
const { MAX_POINTS } = require('../constants');
const { getRummyCardPoints } = require('../utils/cards');

class RummyDealService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await RummyDeal.findByPk(id, attributeOptions);

            // if (!game) {
            //     throw new Error('Game not found');
            // }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching user');
        }
    }

    async getLatestGameOnTable(tableId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await RummyDeal.findOne({
                ...attributeOptions,
                where: {
                    table_id: tableId
                }
            });
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching user');
        }
    }

    async create(data) {
        try {
            return await RummyDeal.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async getTable(tableId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await RummyDealTable.findByPk(tableId, attributeOptions);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching table');
        }
    }

    async chaalCount(gameId, userId) {
        try {
            return await RummyDealLog.count({
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

    async packGame(userId, gameId, timeout = 0, json = '') {
        try {
            await RummyDealCard.update(
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

            // const points = await Math.round((percent / 100) * MAX_POINTS);
            const data = {
                user_id: userId,
                game_id: gameId,
                seen: 0,
                json,
                timeout,
                action: 1
            }

            return await RummyDealLog.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching table');
        }
    }

    async getMyCards(gameId, userId, card = "") {
        try {
            const whereClause = {
                game_id: gameId,
                user_id: userId
            };

            if (card) {
                whereClause.card = card;
            }

            return await RummyDealCard.findAll({
                attributes: [
                    'id',
                    'card',
                    [sequelize.fn('SUBSTRING', sequelize.col('card'), 1, 2), 'card_group']
                ],
                where: whereClause
            });

            // return results.map(result => result.get({ plain: true }));
        } catch (error) {
            console.error('Error querying tbl_rummy_deal_card:', error);
            throw error;
        }
    }

    async getMyCard(gameId, userId, card) {
        try {
            const whereClause = {
                game_id: gameId,
                user_id: userId,
                card: card
            };

            return await RummyDealCard.findOne({
                attributes: [
                    'id',
                    'card',
                    [sequelize.fn('SUBSTRING', sequelize.col('card'), 1, 2), 'card_group']
                ],
                where: whereClause
            });

            // return results.map(result => result.get({ plain: true }));
        } catch (error) {
            console.error('Error querying tbl_rummy_deal_card:', error);
            throw error;
        }
    }

    async cardValue(joker, card1, card2, card3, card4 = "", card5 = "", card6 = "", card7 = "", card8 = "", card9 = "") {
        let rule = 0;
        let value = 0;
        const jokerNum = trimByUnderscor(joker).substring(2);
        let card1Color = card1.substring(0, 2);
        let card1Num = card1.substring(2);
        let card1NumSet = card1Num;
        let card1ColorSet = card1Color;

        let card2Color = card2.substring(0, 2);
        let card2Num = card2.substring(2);
        let card2NumSet = card2Num;
        let card2ColorSet = card2Color;

        let card3Color = card3.substring(0, 2);
        let card3Num = card3.substring(2);
        let card3NumSet = card3Num;
        let card3ColorSet = card3Color;

        let card4Color = "";
        let card4Num = "";
        let card4NumSet = "";
        let card4ColorSet = "";
        if (card4) {
            card4Color = card4.substring(0, 2);
            card4Num = card4.substring(2);
            card4NumSet = card4Num;
            card4ColorSet = card4Color;
        }
        let card5Color = "";
        let card5Num = "";
        if (card5) {
            card5Color = card5.substring(0, 2);
            card5Num = card5.substring(2);
            // const card5NumSet = card5Num;
            // const card5ColorSet = card5Color;
        }
        let card6Color = "";
        let card6Num = "";
        if (card6) {
            card6Color = card6.substring(0, 2);
            card6Num = card6.substring(2);
        }

        if (card1Num == jokerNum) {
            card1Color = "JK";
            card1Num = 0;
            card1NumSet = card1Num;
        }
        if (card2Num == jokerNum) {
            card2Color = "JK";
            card2Num = 0;
            card2NumSet = card2Num;
        }
        if (card3Num == jokerNum) {
            card3Color = "JK";
            card3Num = 0;
            card3NumSet = card3Num;
        }
        if (card4Num && card4Num == jokerNum) {
            card4Color = "JK";
            card4Num = 0;
            card4NumSet = card4Num;
        }
        if (card5Num && card5Num == jokerNum) {
            card5Color = "JK";
            card5Num = 0;
        }
        if (card6Num && card6Num == jokerNum) {
            card6Color = "JK";
            card6Num = 0;
        }

        let set = "";
        let colorGroup = "";
        if (card1Color != "JK") {
            set = card1NumSet;
            colorGroup = card1Color;
        } else if (card2Color != "JK") {
            set = card2NumSet;
            colorGroup = card2Color;
        } else if (card3Color != "JK") {
            set = card3NumSet;
            colorGroup = card3Color;
        } else if (card4Color && card4Color != "JK") {
            set = card4NumSet;
            colorGroup = card4Color;
        } else if (card5Color && card5Color != "JK") {
            colorGroup = card5Color
        } else {
            colorGroup = card6Color;
        }

        let jokerCount = 0;
        if (card1Color == "JK") {
            card1NumSet = set;
            card1Color = colorGroup;
            card1ColorSet = "";
            jokerCount++;
        }
        if (card2Color == "JK") {
            card2NumSet = set;
            card2Color = colorGroup;
            card2ColorSet = "";
            jokerCount++;
        }
        if (card3Color == "JK") {
            card3NumSet = set;
            card3Color = colorGroup;
            card3ColorSet = "";
            jokerCount++;
        }
        if (card4Color && card4Color == "JK") {
            card4NumSet = set;
            card4Color = colorGroup;
            card4ColorSet = "";
            jokerCount++;
        }
        if (card5Color && card5Color == "JK") {
            card5Color = colorGroup;
            jokerCount++;
        }
        if (card6Color && card6Color == "JK") {
            card6Color = colorGroup;
            jokerCount++;
        }

        let color = "";
        if (card4NumSet && (card1NumSet == card2NumSet) && (card2ColorSet == card3NumSet) && (card3NumSet == card4NumSet)) {
            if (!card5) {
                set = getRummyCardPoints(set);
                if ((card1ColorSet == card2ColorSet && (card1ColorSet != "" || card2ColorSet != "")) || (card2ColorSet == card3ColorSet && (card2ColorSet != "" || card3ColorSet != "")) ||
                    (card3ColorSet == card4ColorSet && (card3ColorSet != "" || card4ColorSet != "")) ||
                    (card1ColorSet == card4ColorSet && card1ColorSet != "" || card4ColorSet != "")
                ) {
                    rule = 0;
                    value = 0;
                } else {
                    set = parseInt(set);
                    rule = 6;
                    value = set;
                }
            }
        } else if ((card1NumSet == card2NumSet) && (card2NumSet == card3NumSet)) {
            if (!card4) {
                set = getRummyCardPoints(set);
                if ((card1ColorSet == card2ColorSet && (card1ColorSet != "" || card2ColorSet != "")) || (card2ColorSet == card3ColorSet && (card2ColorSet != "" || card3ColorSet != "")) ||
                    (card1ColorSet == card3ColorSet && (card1ColorSet != "" || card3ColorSet != ""))
                ) {
                    rule = 0;
                    value = 0;
                } else {
                    set = parseInt(set);
                    rule = 6;
                    value = set;
                }
            }
        } else {
            color = false;
            if ((card1Color == card2Color) && (card2Color == card3Color)) {
                if (card6Color && card5Color != card6Color) {
                    return [rule, value]
                } else if (card5Color && card4Color != card5Color) {
                    return [rule, value]
                } else if (card4Color && card3Color != card4Color) {
                    return [rule, value]
                }
                color = true;
            } else {
                return [rule, value]
            }

            card1Num = getRummyCardPoints(card1Num);
            card2Num = getRummyCardPoints(card2Num);
            card3Num = getRummyCardPoints(card3Num);

            card1Num = parseInt(card1Num)
            card2Num = parseInt(card2Num)
            card3Num = parseInt(card3Num)
        }

        if (card4Num) {
            card4Num = getRummyCardPoints(card4Num);
            card4Num = parseInt(card4Num)
        }

        if (card5Num) {
            card5Num = getRummyCardPoints(card5Num);
            card5Num = parseInt(card5Num)
        }

        if (card6Num) {
            card6Num = getRummyCardPoints(card6Num);
            card6Num = parseInt(card6Num)
        }

        let arr = [];
        if (card6Num) {
            arr = [card1Num, card2Num, card3Num, card4Num, card5Num, card6Num];
        } else if (card5Num) {
            arr = [card1Num, card2Num, card3Num, card4Num, card5Num];
        } else if (card4Num) {
            arr = [card1Num, card2Num, card3Num, card4Num];
        } else {
            arr = [card1Num, card2Num, card3Num];
        }

        arr.sort(arr);

        let sequence = false;
        let aceJokerCount = jokerCount;
        const totalCard = arr.length;

        for (let index = 0; index < arr.length; index++) {
            const element = arr[index];
            if (element != 0 && totalCard > (index + 1)) {
                sequence = true;
            } else if ((element + 2) == arr[index + 1] && jokerCount > 0) {
                jokerCount--;
                sequence = true;
            } else {
                sequence = false;
                break;
            }
        }

        if (sequence && color) {
            value = arr[0];
            rule = value == 0 ? 4 : 5
        }

        if (rule == 0) {
            if (arr.includes(14)) {
                arr = arr.map(item => item == 14 ? 1 : item);
                totalCard = arr.length;
                for (let index = 0; index < arr.length; index++) {
                    const element = arr[index];
                    if (element != 0 && totalCard > (index + 1)) {
                        sequence = true;
                    } else if ((element + 2) == arr[index + 1] && aceJokerCount > 0) {
                        aceJokerCount--;
                        sequence = true;
                    } else {
                        sequence = false;
                        break;
                    }
                }
            }

            if (sequence && color) {
                value = arr[0];
                rule = value == 0 ? 4 : 5
            }
        }
        return [rule, value];
    }

    async getTableByCode(code, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await RummyDealTable.findOne({
                where: {
                    private: 2,
                    invitation_code: code,
                },
                ...attributeOptions,
            });
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching table');
        }
    }

    async tableUsers(tableId) {
        try {
            const tableUsers = await RummyDealTableUser.findAll({
                attributes: [
                    'id', 'user_id', 'table_id', 'seat_position', 'total_points',
                    [sequelize.col('tbl_users.name'), 'name'],
                    [sequelize.col('tbl_users.mobile'), 'mobile'],
                    [sequelize.col('tbl_users.profile_pic'), 'profile_pic'],
                    [sequelize.col('tbl_users.wallet'), 'wallet'],
                    [sequelize.col('tbl_users.user_type'), 'user_type'],
                    [sequelize.col('tbl_rummy_deal_table.no_of_players'), 'no_of_players'],
                    [sequelize.col('tbl_rummy_deal_table.boot_value'), 'boot_value']
                ],
                include: [
                    {
                        model: user,
                        as: 'tbl_users',
                        attributes: []
                    },
                    {
                        model: RummyDealTable,
                        as: 'tbl_rummy_deal_table',
                        attributes: []
                    }
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
        const rummyTables = await RummyDealTableMaster.findAll({
            attributes: [
                'id',
                'game_count',
                'boot_value',
                [Sequelize.fn('COUNT', Sequelize.col('tbl_rummy_deal_tables.tbl_users.id')), 'online_members'],
                [Sequelize.col('tbl_rummy_deal_tables.no_of_players'), 'no_of_players']
            ],
            include: [
                {
                    model: RummyDealTable,
                    as: 'tbl_rummy_deal_tables',
                    required: false,
                    attributes: [], // Exclude attributes from this table in the final result
                    include: [
                        {
                            model: user,
                            as: 'tbl_users',
                            required: false,
                            attributes: [], // Exclude attributes from this table in the final result
                        }
                    ]
                }
            ],
            where: whereConditions,
            group: [
                'tbl_rummy_deal_table_master.boot_value'
            ],
            // raw: true,
            // nest: true,
            // subQuery: false
        });
        return rummyTables;
    }

    async update(id, data) {
        try {
            await RummyDeal.update(data, {
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
                'rummy_deal_table_id',
                [Sequelize.fn('COUNT', Sequelize.col('tbl_rummy_deal_table.id')), 'members']
            ],
            include: [
                {
                    model: RummyDealTable,
                    as: 'tbl_rummy_deal_table',
                    required: true,
                    attributes: [],
                    where: whereConditions
                }
            ],
            where: {
                rummy_deal_table_id: {
                    [Op.ne]: 0
                }
            },
            group: [
                'tbl_users.rummy_deal_table_id'
            ]
        });

        return activeTables;
    }

    async getAvailableSeatPosition(tableId) {
        try {
            const sql = `
                SELECT * FROM (
                    SELECT 1 AS mycolumn UNION ALL
                    SELECT 2 UNION ALL
                    SELECT 3 UNION ALL
                    SELECT 4 UNION ALL
                    SELECT 5 UNION ALL
                    SELECT 6 UNION ALL
                    SELECT 7
                ) a
                WHERE mycolumn NOT IN (
                    SELECT seat_position
                    FROM tbl_rummy_deal_table_user
                    WHERE table_id = :tableId
                    AND isDeleted = 0
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
        return await RummyDeal.findOne(options);
    }

    async getLastGameOnTable(tableId, attributes = []) {
        const attributeOptions = getAttributes(attributes);
        const options = {
            ...attributeOptions,
            where: {
                winner_id: {
                    [Sequelize.Op.ne] : 0
                },
                table_id: tableId
            },
            order: [['id', 'DESC']]
        }
        return await RummyDeal.findOne(options);
    }



    async createTable(data) {
        try {
            return await RummyDealTable.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async addTableUser(data) {
        try {
            const rummyTableUser = await RummyDealTableUser.create(data);

            if (data.table_id && data.user_id) {
                userService.update(data.user_id, { rummy_deal_table_id: data.table_id })
            }

            return rummyTableUser;
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async removeTableUser(data) {
        try {
            await RummyDealTableUser.update(
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
                await userService.update(data.user_id, { rummy_deal_table_id: 0 })
            }
        } catch (error) {
            console.log(error)
            throw new Error(error);
        }
    }

    async gameOnlyUsers(gameId, userId = "") {
        const whereClause = {
            game_id: gameId,
        };

        if (userId) {
            whereClause.user_id = userId;
        }
        const results = await RummyDealCard.findAll({
            attributes: ['user_id', 'packed',[sequelize.col('tbl_user.name'), 'name']],
            include: [{
                model: user,
                as: 'tbl_user',
                required: true,
                attributes: [],
            }],
            where: whereClause,
            group: ['tbl_rummy_deal_card.user_id']
        });

        return results;
    }

    async getStartCards(limit) {
        try {
            return await CardRummy.findAll({
                where: {
                    cards: {
                        [Sequelize.Op.notIn]: ['JKR1', 'JKR2']
                    }
                },
                order: sequelize.random(),
                limit
            })
        } catch (error) {
            console.log(error)
            throw new Error('Error while get rummy cards');
        }
    }

    async giveGameCards(data) {
        try {
            return await RummyDealCard.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error while get card');
        }
    }

    async addGameCount(userId) {
        try {
            await userService.update(userId, {
                game_played: sequelize.literal(`game_played + 1`)
            });
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async addGameLog(data) {
        try {
            return await RummyDealLog.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async updateSeatNumber(tableId, seatNo) {
        try {
            await RummyDealTable.update(
                {
                    start_seat_no: seatNo
                },
                {
                    where: {
                        id: tableId
                    }
                }
            )
        } catch (error) {

        }
    }

    async startDropGameCards(payload) {
        await RummyDealCard.update(
            {
                isDeleted: 1
            },
            {
                where: payload
            }
        )

        const dropCard = await RummyDealCardDrop.create(payload);

        return dropCard;
    }

    async rummyCardDrop(data) {
        try {
            return await RummyDealCardDrop.create(data)
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async checkUsersOnTable(tableId) {
        try {
            return await userService.getSingleByConditions({ rummy_deal_table_id: tableId }, ["id"])
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async gameUsers(gameId) {
        try {
            return await RummyDealCard.findAll({
                where: {
                    // packed: false,
                    packed: false,
                    game_id: gameId
                },
                group: ['user_id'],
            })
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async gameAllUsers(gameId) {
        try {
            return await RummyDealCard.findAll({
                attributes: ['tbl_rummy_deal_card.*', 'tbl_user.name', 'tbl_user.user_type'],
                include: [{
                    model: user,
                    as: "tbl_user",
                    attributes: [],
                    // where: { id: sequelize.col('tbl_rummy_deal_card.user_id') }
                }],
                where: { game_id: gameId },
                group: ['tbl_rummy_deal_card.user_id'],
                raw: true
            });
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async dropGameCard(tableData, json, timeout = 0) {
        try {
            await RummyDealCard.update({
                isDeleted: 1
            }, {
                where: tableData
            });

            const dropGame = await RummyDealCardDrop.create(tableData);

            const gameLogs = {
                user_id: tableData.user_id,
                game_id: tableData.game_id,
                json,
                timeout,
                action: 2,
            }

            await RummyDealLog.create(gameLogs);

            return dropGame;
        } catch (error) {
            throw new Error("Error while drop card");
        }
    }

    async getGameBot(gameId) {
        try {
            return await user.findAll({
                include: [
                    {
                        model: RummyDealCard,
                        as: 'tbl_rummy_deal_cards',
                        required: true,
                        attributes: []
                    }
                ],
                where: {
                    mobile: "",
                    packed: false,
                    game_id: gameId
                }
            });
        } catch (error) {

        }
    }

    async getRamdomGameCard(gameId) {
        try {
            return await CardRummy.findOne({
                where: {
                    cards: {
                        [Op.and]: [
                            sequelize.literal(`cards NOT IN (SELECT joker FROM tbl_rummy_deal WHERE id = ${gameId})`),
                            sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_deal_card WHERE game_id = ${gameId} AND isDeleted = 0)`),
                            sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_deal_card_drop WHERE game_id = ${gameId} AND isDeleted = 0)`)
                        ]
                    }
                },
                order: sequelize.random()
            });
        } catch (error) {
            console.log(error)
            throw new Error("Error while get random game card");
        }
    }

    async getGameTableCardCount(gameId) {
        try {
            const cardsCount = await CardRummy.count({
                where: {
                    [Op.and]: [
                        Sequelize.literal(`cards NOT IN (SELECT joker FROM tbl_rummy_deal WHERE id=${gameId})`),
                        Sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_deal_card WHERE game_id=${gameId} AND isDeleted=0)`),
                        Sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_deal_card_drop WHERE game_id=${gameId} AND isDeleted=0)`)
                    ]
                },
                order: Sequelize.literal('RAND()')
            });
            return cardsCount;
        } catch (error) {
            console.log(error)
            throw new Error("Error while get random game card");
        }
    }

    async getAllGameOnTable(tableId) {
        try {
            return await RummyDeal.findAll({
                where: {
                    table_id: tableId
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while get all games");
        }
    }

    async getTablePoints(tableId) {
        try {
            return await RummyDealLog.findAll({
                attributes: [
                    'game_id',
                    'user_id',
                    'points',
                    'total_points',
                    [sequelize.col('tbl_users.name'), 'name']
                ],
                include: [
                    {
                        model: RummyDeal,
                        attributes: [],
                        where: { table_id: tableId }
                    },
                    {
                        model: user,
                        as: 'tbl_users',
                        attributes: []
                    }
                ],
                where: {
                    [Op.or]: [
                        { action: 3 },
                        { action: 1 }
                    ]
                },
                order: [['id', 'ASC']],
                raw: true // Ensures you get plain JavaScript objects
            });
        } catch (error) {
            console.log(error);
            throw new Error("Error while get table points");
        }
    }

    async getTotalPoints(table_id, user_id) {
        const result = await RummyDealTableUser.findOne({
            attributes: ['total_points'],
            where: {
                table_id: table_id,
                user_id: user_id
            }
        });

        return result ? result.total_points : null;
    }

    async getAndDeleteGameDropCard(gameId) {
        try {
            const droppedCard = await RummyDealCardDrop.findOne({
                where: {
                    game_id: gameId
                },
                attributes: ["id", "card"],
                order: [["id", "DESC"]]
            });

            if (droppedCard) {
                await RummyDealCardDrop.update(
                    {
                        isDeleted: 1
                    },
                    {
                        where: {
                            id: droppedCard.id
                        }
                    }
                )
            }

            return droppedCard
        } catch (error) {
            console.log(error)
            throw new Error("Error while delete dropped card");
        }
    }

    async declare(data) {
        try {
            await RummyDealTableUser.update(
                {
                    total_points: sequelize.literal(`total_points + ${data.points}`)
                },
                {
                    where: {
                        table_id: data.table_id,
                        user_id: data.user_id
                    }
                }
            );

            const userTableData = await RummyDealTableUser.findOne({
                attributes: ["total_points"],
                where: {
                    table_id: data.table_id,
                    user_id: data.user_id
                }
            });

            /*await RummyDeal.update(
                {
                    amount: sequelize.literal(`amount + ${data.points}`)
                },
                {
                    where: {
                        id: data.game_id
                    }
                }
            );*/

            const logData = {
                user_id: data.user_id,
                game_id: data.game_id,
                points: data.points,
                total_points: userTableData.total_points,
                action: 3,
                json: data.json,
            }

            return await RummyDealLog.create(logData);
        } catch (error) {
            throw new Error(error);
        }
    }

    async gameLog(gameId, limit = '', status = '', user_id = '', timeout = '') {
        try {
            const conditions = {
                game_id: gameId
            };
            if (status) {
                conditions.action = status
            }
            if (user_id) {
                conditions.user_id = user_id
            }
            if (timeout) {
                conditions.timeout = timeout
            }
            const options = {
                order: [["id", "DESC"]],
                where: conditions
            }
            if (limit) {
                options.limit = limit;
            }
            return await RummyDealLog.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch game logs");
        }
    }

    async gameLogJson(gameId, userId) {
        try {
            return await RummyDealLog.findOne({
                where: {
                    game_id: gameId,
                    user_id: userId
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch game logs")
        }
    }

    async lastGameCard(gameId) {
        try {
            return await RummyDealCard.findOne({
                where: {
                    packed: false,
                    game_id: gameId
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch last game card")
        }
    }

    async discardedGameCard(gameId) {
        try {
            return await RummyDealCardDrop.findAll({
                where: {
                    game_id: gameId
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch discarded card")
        }
    }

    async getGameDropCard(gameId) {
        try {
            return await RummyDealCardDrop.findOne({
                attributes: ["card"],
                where: {
                    game_id: gameId
                },
                order: [["id", "DESC"]]
            })
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch discarded card")
        }
    }

    async gameUserCard(gameId, userId) {
        try {
            return await RummyDealCard.findOne({
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

    async totalAmountOnTable(tableId) {
        try {
            const result = await RummyDeal.findOne({
                attributes: [[Sequelize.fn('SUM', Sequelize.col('amount')), 'Total_amount']],
                where: {
                    table_id: tableId
                }
            });

            return result.dataValues.Total_amount;
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch Total Amount")
        }
    }

    async updateTotalWinningAmtTable(amount, userWinningAmount, adminWinningAmount, tableId, winnerId) {
        try {
            return await RummyDealTable.update(
                {
                    winning_amount: amount,
                    user_amount: userWinningAmount,
                    commission_amount: adminWinningAmount,
                    winner_id: winnerId
                },
                {
                    where: {
                        id: tableId
                    }
                }
            );
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch Update Total Amount")
        }
    }

    async declareWinner(gameId, userId) {
        try {
            return await RummyDeal.update(
                {
                    winner_id: userId
                },
                {
                    where: {
                        id: gameId
                    }
                }
            );
        } catch (error) {
            console.log(error)
            throw new Error("Error while declare winner")
        }
    }

    async invested(tableId, seatPosition) {
        try {
            const sql = `
            SELECT SUM(tbl_rummy_deal_log.amount) AS amount,
            tbl_rummy_deal_table_user.seat_position,
            tbl_rummy_deal_table_user.user_id 
            FROM 
                tbl_rummy_deal_table_user 
            JOIN 
                tbl_rummy_deal 
                ON tbl_rummy_deal_table_user.table_id = tbl_rummy_deal.table_id 
            JOIN 
                tbl_rummy_deal_log 
                ON tbl_rummy_deal.id = tbl_rummy_deal_log.game_id 
                AND tbl_rummy_deal_log.user_id = tbl_rummy_deal_table_user.user_id 
            WHERE 
                tbl_rummy_deal_table_user.table_id = :tableId
                AND tbl_rummy_deal_table_user.seat_position = :seatPosition
                AND tbl_rummy_deal_log.amount > 0
        `;

            const result = await sequelize.query(sql, {
                replacements: { tableId, seatPosition },
                type: sequelize.QueryTypes.SELECT
            });
            /*const result = await RummyDealTableUser.findOne({
                attributes: [
                    [Sequelize.fn('SUM', Sequelize.col('RummyDealLog.amount')), 'amount'],
                    'seat_position',
                    'user_id'
                ],
                include: [
                    {
                        model: RummyDeal,
                        required: true,
                        include: [
                            {
                                model: RummyDealLog,
                                required: true,
                                where: {
                                    amount: {
                                        [Sequelize.Op.gt]: 0
                                    },
                                    user_id: Sequelize.col('tbl_rummy_deal_table_user.user_id')
                                }
                            }
                        ]
                    }
                ],
                where: {
                    table_id: tableId,
                    seat_position: seatPosition
                },
                group: ['tbl_rummy_deal_table_user.seat_position', 'tbl_rummy_deal_table_user.user_id']
            });*/

            if (result.length > 0) {
                return result[0];
            }
            return 0;
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch invested")
        }
    }

    async getShareWallet(tableId) {
        try {
            const tableUsers = await ShareWallet.findAll({
                attributes: [
                    'tbl_share_wallet.*',
                    [sequelize.col('tbl_users.name'), 'name'],
                    [sequelize.col('to_user.name'), 'to_name']
                ],
                include: [
                    {
                        model: user,
                        as: 'to_user', // This alias should match the alias in your association
                        required: true,
                        attributes: []
                    },
                    {
                        model: user,
                        as: 'tbl_users', // This alias should match the alias in your association
                        required: true,
                        attributes: []
                    }
                ],
                where: {
                    table_id: tableId,
                    status: 0
                }
            });

            return tableUsers;
        } catch (error) {
            console.error('Error fetching table users:', error);
        }
    }

    async getShareWalletLimit(tableId, limit = "") {
        try {
            const options = {
                attributes: [
                    'tbl_share_wallet.*',
                    [sequelize.col('tbl_users.name'), 'name']
                ],
                where: {
                    table_id: tableId
                },
                include: [
                    {
                        model: user,
                        required: true,
                        attributes: [],
                    }
                ],
                order: [
                    ['id', 'DESC']
                ]
            };

            if (limit) {
                options.limit = limit;
            }

            return await ShareWallet.findAll(options);
        } catch (error) {
            console.error('Error fetching table users:', error);
        }
    }

    async shareWallet(payload) {
        try {
            return await ShareWallet.create(payload);
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch invested")
        }
    }

    async updateShareWallet(id, status) {
        try {
            return await ShareWallet.update({ status }, {
                where: {
                    id,
                    status: 0
                }
            });
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch invested")
        }
    }
}

module.exports = new RummyDealService();