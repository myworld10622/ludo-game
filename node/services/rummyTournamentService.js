const { Op } = require('sequelize');
const { RummyTournament, RummyTournamentTable, user, RummyTournamentTableUser, sequelize, RummyTournamentTableMaster, Sequelize, RummyTournamentCard, CardRummy, RummyTournamentLog, RummyTournamentCardDrop, ShareWallet, RummyTournamentParticipants, RummyTournamentRounds, RummyTournamentPrizes, RummyTournamentWinners, RummyTournamentType } = require('../models');
const { getAttributes, trimByUnderscor } = require('../utils/util');
const userService = require('./userService');
const { MAX_POINTS } = require('../constants');
const { getRummyCardPoints } = require('../utils/cards');
const errorHandler = require('../error/errorHandler');
const dateHelper = require('../utils/date');

class RummyTournamentService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await RummyTournament.findByPk(id, attributeOptions);

            if (!game) {
                throw new Error('Game not found');
            }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching user');
        }
    }

    async getTournamentById(tournamentId) {
        try {
            return await RummyTournamentTableMaster.findByPk(tournamentId);
        } catch (error) {
            throw new Error(error);
        }
    }

    async checkUserInTableUser(table_id, user_id) {
        try {
            return await RummyTournamentTableUser.findOne({
                attributes: ["id", "table_id"],
                where: {
                    table_id,
                    user_id
                }
            })
        } catch (err) {
            throw new Error(err);
        }
    }

    async checkUserInTableUserOld(tournament_id, user_id, round) {
        try {
            return await RummyTournamentTableUser.findOne({
                attributes: ["id", "table_id"],
                where: {
                    tournament_id,
                    user_id,
                    round
                }
            })
        } catch (err) {
            throw new Error(err);
        }
    }

    async getSingleParticipatedUser(userId, tournamentId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await RummyTournamentParticipants.findOne({
                ...attributeOptions,
                where: {
                    user_id: userId,
                    tournament_id: tournamentId
                }
            })
        } catch (error) {
            throw Error(error);
        }
    }

    async getPendingTournamentRound(tournament_id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return RummyTournamentRounds.findOne({
                ...attributeOptions,
                where: {
                    tournament_id,
                    status: {
                        [Sequelize.Op.ne]: 2
                    }
                },
                order: [["round", "ASC"]]
            })
        } catch (error) {
            throw Error(error);
        }
    }

    async getUpcomingTournamentRound(tournament_id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return RummyTournamentRounds.findOne({
                ...attributeOptions,
                where: {
                    tournament_id,
                    status: 0
                },
                order: [["round", "ASC"]]
            })
        } catch (error) {
            throw Error(error);
        }
    }

    async getTournamentNextRound(tournament_id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return RummyTournamentRounds.findOne({
                ...attributeOptions,
                where: {
                    tournament_id,
                    status: 0
                },
                order: [["round", "ASC"]]
            })
        } catch (error) {
            throw Error(error);
        }
    }

    async getTournamentRound(tournament_id, round, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return RummyTournamentRounds.findOne({
                ...attributeOptions,
                where: {
                    tournament_id,
                    round,
                }
            })
        } catch (error) {
            throw Error(error);
        }
    }

    /*async getTournamentAvailableTable(tournamentId) {
        try {
            const table = await RummyTournamentTable.findOne({
                attributes: ["tournament_id", "id"],
                include: [
                    {
                        model: RummyTournamentTableUser,
                        attributes: [],
                        group: ['tournament_id'],
                        having: sequelize.literal('COUNT(tournament_id) < 6'),
                    },
                ],
                where: {
                    winner_id: {
                        [Op.ne]: 1
                    },
                    tournament_id: tournamentId
                }
            });

            return table;
        } catch (error) {
            console.log(error);
            throw Error(error);
        }
    }*/

    async getTournamentAvailableTable(tournamentId) {
        try {
            const table = await RummyTournamentTable.findOne({
                attributes: [
                    "tournament_id",
                    "id",
                    "round",
                    [Sequelize.literal(`(
                            SELECT COUNT(*) 
                            FROM tbl_rummy_tournament_table_user AS users 
                            WHERE users.table_id = tbl_rummy_tournament_table.id
                        )`), 'userCount']
                ],
                where: {
                    // winner_id: {
                    //     [Op.ne]: 1
                    // },
                    winner_id: 0,
                    tournament_id: tournamentId,
                    [Op.and]: Sequelize.literal(`(
                            SELECT COUNT(*) 
                            FROM tbl_rummy_tournament_table_user AS users 
                            WHERE users.table_id = tbl_rummy_tournament_table.id
                        ) < 6`)
                }
            });

            return table;
        } catch (error) {
            console.log(error);
            throw Error(error);
        }
    }


    // Below functions change

    async getLatestGameOnTable(tableId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await RummyTournament.findOne({
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
            return await RummyTournament.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async getTable(tableId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            return await RummyTournamentTable.findByPk(tableId, attributeOptions);
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching table');
        }
    }

    async getTournamentInfoByTable(tableId) {
        try {
            return await RummyTournamentTable.findOne({
                attributes: [
                    "id", "round", "total_deal_round", "deal_round", "tournament_id",
                    [sequelize.col('tbl_rummy_tournament_master.total_round'), 'total_round'],
                    [sequelize.col('tbl_rummy_tournament_master.is_winner_get_pass'), 'is_winner_get_pass'],
                    [sequelize.col('tbl_rummy_tournament_master.total_pass_count'), 'total_pass_count']
                ],
                include: [
                    {
                        model: RummyTournamentTableMaster,
                        as: 'tbl_rummy_tournament_master',
                        attributes: []
                    }
                ],
                where: {
                    id: tableId
                },
                raw: true
            });
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching table');
        }
    }

    async chaalCount(gameId, userId) {
        try {
            return await RummyTournamentLog.count({
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

    async packGame(userId, tableId, gameId, percent = 0) {
        try {
            await RummyTournamentCard.update(
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

            const points = await Math.round((percent / 100) * MAX_POINTS);

            await RummyTournamentTableUser.update({
                total_points: sequelize.literal(`total_points - ${points}`),
                game_points: points
            }, {
                where: {
                    table_id: tableId,
                    user_id: userId
                }
            })

            // const table = await this.getTable(tableId);

            const totalPoints = (await RummyTournamentTableUser.findOne({
                attributes: ["total_points"],
                where: {
                    table_id: tableId,
                    user_id: userId
                }
            })).total_points;

            const data = {
                user_id: userId,
                game_id: gameId,
                points,
                total_points: totalPoints,
                action: 1
            }

            return await RummyTournamentLog.create(data);
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

            return await RummyTournamentCard.findAll({
                attributes: [
                    'id',
                    'card',
                    [sequelize.fn('SUBSTRING', sequelize.col('card'), 1, 2), 'card_group']
                ],
                where: whereClause
            });

            // return results.map(result => result.get({ plain: true }));
        } catch (error) {
            console.error('Error querying tbl_rummy_tournament_card:', error);
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

            return await RummyTournamentCard.findOne({
                attributes: [
                    'id',
                    'card',
                    [sequelize.fn('SUBSTRING', sequelize.col('card'), 1, 2), 'card_group']
                ],
                where: whereClause
            });
        } catch (error) {
            console.error('Error querying tbl_rummy_tournament_card:', error);
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
            return await RummyTournamentTable.findOne({
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
            const tableUsers = await RummyTournamentTableUser.findAll({
                attributes: [
                    'id', 'user_id', 'table_id', 'seat_position', "game_points", "total_points",
                    [sequelize.col('tbl_users.name'), 'name'],
                    [sequelize.col('tbl_users.mobile'), 'mobile'],
                    [sequelize.col('tbl_users.profile_pic'), 'profile_pic'],
                    [sequelize.col('tbl_users.wallet'), 'wallet'],
                    [sequelize.col('tbl_users.user_type'), 'user_type']
                ],
                include: [
                    {
                        model: user,
                        as: 'tbl_users',
                        attributes: []
                    },
                    {
                        model: RummyTournamentTable,
                        as: 'tbl_rummy_tournament_table',
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

    async tableUsersByPoints(tableId) {
        try {
            const tableUsers = await RummyTournamentTableUser.findAll({
                attributes: [
                    'id', 'user_id', 'table_id', 'total_points'
                ],
                where: {
                    table_id: tableId
                },
                order: [['total_points', 'DESC']]
            });

            return tableUsers;
        } catch (error) {
            console.error('Error fetching table users:', error);
        }
    }

    async tournamentWinnersByPoints(tournamentId, limit = "") {
        try {
            const options = {
                attributes: [
                    'id', 'user_id', 'tournament_id', 'round', 'total_points', 'minus_unutilized_wallet', 'minus_winning_wallet', 'minus_bonus_wallet'
                ],
                where: {
                    tournament_id: tournamentId
                },
                order: [
                    ['round', 'DESC'],
                    ['total_points', 'DESC']
                ]
            }
            if (limit) {
                options.limit = limit;
            }
            return await RummyTournamentParticipants.findAll(options);
        } catch (error) {
            console.error('Error fetching table users:', error);
            throw Error(error)
        }
    }

    async getTableMaster(tournementId) {
        return await RummyTournamentTableMaster.findOne({
            attributes: [
                "id", "registration_start_date", "registration_start_time", "start_date", "start_time", "registration_fee", "winning_amount",
                "name", "max_player", "total_round", "is_mega_tournament", "is_completed"
            ],
            where: {
                id: tournementId
            }
        });
    }

    async update(id, data) {
        try {
            await RummyTournament.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating setting');
        }
    }

    async getCustomizeActiveTable(tableAmount, noOfPlayers) {
        const whereConditions = {
            boot_value: tableAmount,
        };
        if (noOfPlayers) {
            whereConditions.no_of_players = noOfPlayers;
        }
        const activeTables = await user.findAll({
            attributes: [
                'id',
                'rummy_tournament_table_id',
                [Sequelize.fn('COUNT', Sequelize.col('tbl_rummy_tournament_table.id')), 'members']
            ],
            include: [
                {
                    model: RummyTournamentTable,
                    as: 'tbl_rummy_tournament_table',
                    required: true,
                    attributes: [],
                    where: whereConditions
                }
            ],
            where: {
                rummy_tournament_table_id: {
                    [Op.ne]: 0
                }
            },
            group: [
                'tbl_users.rummy_tournament_table_id'
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
                    FROM tbl_rummy_tournament_table_user
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
        return await RummyTournament.findOne(options);
    }



    async createTable(data) {
        try {
            return await RummyTournamentTable.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async addTableUser(data) {
        try {
            const rummyTableUser = await RummyTournamentTableUser.create(data);

            if (data.table_id && data.user_id) {
                userService.update(data.user_id, { rummy_tournament_table_id: data.table_id })
            }

            return rummyTableUser;
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async removeTableUser(data) {
        try {
            await RummyTournamentTableUser.update(
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
                await userService.update(data.user_id, { rummy_tournament_table_id: 0 })
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
        const results = await RummyTournamentCard.findAll({
            attributes: ['user_id', 'packed', [sequelize.col('tbl_user.name'), 'name']],
            include: [{
                model: user,
                as: 'tbl_user',
                required: true,
                attributes: [],
            }],
            where: whereClause,
            group: ['tbl_rummy_tournament_card.user_id']
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
            return await RummyTournamentCard.create(data);
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
            return await RummyTournamentLog.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy game');
        }
    }

    async updateSeatNumber(tableId, seatNo) {
        try {
            await RummyTournamentTable.update(
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
            throw Error(error)
        }
    }

    async updateTournamentTable(tableId, data) {
        try {
            await RummyTournamentTable.update(
                data,
                {
                    where: {
                        id: tableId
                    }
                }
            )
        } catch (error) {
            throw Error(error)
        }
    }

    async updateTournamentTableUser(tableId, data, userId = 0) {
        try {
            const whereCond = {
                table_id: tableId
            }
            if (userId) {
                whereCond.user_id = userId;
            }
            await RummyTournamentTableUser.update(
                data,
                {
                    where: whereCond
                }
            )
        } catch (error) {
            throw Error(error)
        }
    }

    async updateTournamentRound(tournament_id, round, data) {
        try {
            await RummyTournamentRounds.update(
                data,
                {
                    where: {
                        tournament_id,
                        round
                    }
                }
            )
        } catch (error) {
            throw Error(error)
        }
    }

    async updateTournamentParticipient(tournamentId, userId, data) {
        try {
            await RummyTournamentParticipants.update(
                data,
                {
                    where: {
                        tournament_id: tournamentId,
                        user_id: userId
                    }
                }
            )
        } catch (error) {
            throw Error(error)
        }
    }

    async updateTournamentTableMaster(tournamentId, data) {
        try {
            await RummyTournamentTableMaster.update(
                data,
                {
                    where: {
                        id: tournamentId
                    }
                }
            )
        } catch (error) {
            throw Error(error)
        }
    }

    async havePendingTournamentTable(tournamentId, round) {
        try {
            return await RummyTournamentTable.findOne({
                attributes: ["id"],
                where: {
                    tournament_id: tournamentId,
                    round,
                    winner_id: 0
                }
            })
        } catch (error) {
            throw Error(error)
        }
    }

    async startDropGameCards(payload) {
        await RummyTournamentCard.update(
            {
                isDeleted: 1
            },
            {
                where: payload
            }
        )

        const dropCard = await RummyTournamentCardDrop.create(payload);

        return dropCard;
    }

    async rummyCardDrop(data) {
        try {
            return await RummyTournamentCardDrop.create(data)
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async checkUsersOnTable(tableId) {
        try {
            return await userService.getSingleByConditions({ rummy_tournament_table_id: tableId }, ["id"])
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async gameUsers(gameId) {
        try {
            return await RummyTournamentCard.findAll({
                where: {
                    // packed: false,
                    packed: 0,
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
            return await RummyTournamentCard.findAll({
                attributes: ['tbl_rummy_tournament_card.*', 'tbl_user.name', 'tbl_user.user_type'],
                include: [{
                    model: user,
                    as: "tbl_user",
                    attributes: [],
                    // where: { id: sequelize.col('tbl_rummy_tournament_card.user_id') }
                }],
                where: { game_id: gameId },
                group: ['tbl_rummy_tournament_card.user_id'],
                raw: true
            });
        } catch (error) {
            console.log(error);
            throw new Error("Error while drop Card");
        }
    }

    async dropGameCard(tableData, json, timeout = 0) {
        try {
            await RummyTournamentCard.update({
                isDeleted: 1
            }, {
                where: tableData
            });

            const dropGame = await RummyTournamentCardDrop.create(tableData);

            const gameLogs = {
                user_id: tableData.user_id,
                game_id: tableData.game_id,
                json,
                timeout,
                action: 2,
            }

            await RummyTournamentLog.create(gameLogs);

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
                        model: RummyTournamentCard,
                        as: 'tbl_rummy_tournament_cards',
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
                            sequelize.literal(`cards NOT IN (SELECT joker FROM tbl_rummy_pool WHERE id = ${gameId})`),
                            sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_tournament_card WHERE game_id = ${gameId} AND isDeleted = 0)`),
                            sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_tournament_card_drop WHERE game_id = ${gameId} AND isDeleted = 0)`)
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
                        Sequelize.literal(`cards NOT IN (SELECT joker FROM tbl_rummy_pool WHERE id=${gameId})`),
                        Sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_tournament_card WHERE game_id=${gameId} AND isDeleted=0)`),
                        Sequelize.literal(`cards NOT IN (SELECT card FROM tbl_rummy_tournament_card_drop WHERE game_id=${gameId} AND isDeleted=0)`)
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
            return await RummyTournament.findAll({
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
            return await RummyTournamentLog.findAll({
                attributes: [
                    'game_id',
                    'user_id',
                    'points',
                    'total_points',
                    [sequelize.col('tbl_users.name'), 'name']
                ],
                include: [
                    {
                        model: RummyTournament,
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
        const result = await RummyTournamentTableUser.findOne({
            attributes: ['total_points', 'game_points'],
            where: {
                table_id: table_id,
                user_id: user_id
            }
        });

        return result;// ? result.total_points : null;
    }

    async getAndDeleteGameDropCard(gameId) {
        try {
            const droppedCard = await RummyTournamentCardDrop.findOne({
                where: {
                    game_id: gameId
                },
                attributes: ["id", "card"],
                order: [["id", "DESC"]]
            });

            if (droppedCard) {
                await RummyTournamentCardDrop.update(
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
            const tablePayload = {
                game_points: data.points,
                total_points: sequelize.literal(`total_points - ${data.points}`)
            }
            await this.updateTournamentTableUser(data.table_id, tablePayload, data.user_id);

            const logData = {
                user_id: data.user_id,
                game_id: data.game_id,
                points: data.points,
                action: 3,
                json: data.json,
            }

            return await RummyTournamentLog.create(logData);
        } catch (error) {
            throw new Error("Error while declare game");
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
            return await RummyTournamentLog.findAll(options);
        } catch (error) {
            console.log(error)
            throw new Error("Error while fetch game logs");
        }
    }

    async gameLogJson(gameId, userId) {
        try {
            return await RummyTournamentLog.findOne({
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
            return await RummyTournamentCard.findOne({
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
            return await RummyTournamentCardDrop.findAll({
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
            return await RummyTournamentCardDrop.findOne({
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
            return await RummyTournamentCard.findOne({
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
            const result = await RummyTournament.findOne({
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
            return await RummyTournamentTable.update(
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

    async declareWinner(tableId, gameId, userId) {
        try {
            const allTableUsers = await this.tableUsers(tableId);
            let totalWinningPoints = 0;
            for (let index = 0; index < allTableUsers.length; index++) {
                const element = allTableUsers[index];
                totalWinningPoints += element.game_points;
            }
            await RummyTournament.update(
                {
                    winner_id: userId
                },
                {
                    where: {
                        id: gameId
                    }
                }
            );

            await RummyTournamentTableUser.update(
                {
                    total_points: sequelize.literal(`total_points + ${totalWinningPoints}`)
                },
                {
                    where: {
                        user_id: userId,
                        table_id: tableId
                    }
                }
            );

            /*RummyTournamentTableUser.update(
                {
                    game_points: 0
                },
                {
                    where: {
                        table_id: tableId
                    }
                }
            );*/

            /*RummyTournamentTable.update(
                {
                    deal_round: sequelize.literal(`deal_round + 1`)
                },
                {
                    where: {
                        id: tableId
                    }
                }
            )*/

            /*const logData = {
                user_id: userId,
                game_id: gameId,
                points: totalWinningPoints,
                action: 3,
                json: '',
            }

            return await RummyTournamentLog.create(logData);*/
            // Update Winner Points on declare winner
            await RummyTournamentLog.update(
                {
                    points: totalWinningPoints
                },
                {
                    where: {
                        user_id: userId,
                        game_id: gameId,
                        action: 3
                    }
                }
            );

            const logData = {
                user_id: userId,
                game_id: gameId,
                points: totalWinningPoints,
                action: 5,
                json: '',
            }

            return await RummyTournamentLog.create(logData);

        } catch (error) {
            console.log(error)
            throw new Error("Error while declare winner")
        }
    }

    async getUpcomingTournaments(userId, tournament_type_id = "") {
        try {
            const conditions = {};
            if(tournament_type_id) {
                conditions.tournament_type_id = tournament_type_id;
            }
            return await RummyTournamentTableMaster.findAll({
                attributes: [
                    "id", "registration_start_date", "registration_start_time", "start_date", "start_time", "registration_fee", "winning_amount",
                    "name", "max_player", "total_round", "is_mega_tournament", "is_completed",
                    [Sequelize.literal(`(
                        SELECT CASE 
                            WHEN COUNT(*) > 0 THEN 1
                            ELSE 0
                        END
                        FROM tbl_rummy_tournament_participants AS participants
                        WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                        AND participants.user_id = ${userId}
                    )`), 'participationStatus'],
                    [Sequelize.literal(`(
                        SELECT can_tournament_play
                        FROM tbl_rummy_tournament_participants AS participants
                        WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                        AND participants.user_id = ${userId}
                    )`), 'can_tournament_play'],
                    [Sequelize.literal(`(
                        SELECT COUNT(*)
                        FROM tbl_rummy_tournament_participants AS participants
                        WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                    )`), 'total_participants']
                ],
                where: {
                    // start_date: {
                    //     [Op.gte]: new Date()
                    // },
                    ...conditions,
                    [Op.or]: [
                        {
                            start_date: {
                                [Op.gte]: new Date()
                            }
                        },
                        {
                            is_completed: 2
                        }
                    ]
                }
            })
        } catch (error) {
            throw Error(error)
        }
    }

    async getUpcomingTournementById(tournementId, userId) {
        try {
            const today = new Date();
            const todayDate = today.toISOString().split('T')[0]; // 'YYYY-MM-DD'
            // const currentTime = today.toTimeString().split(' ')[0]; // 'HH:MM:SS'
            return await RummyTournamentTableMaster.findOne({
                attributes: [
                    "id",
                    "registration_start_date",
                    "registration_start_time",
                    "start_date",
                    "start_time",
                    "registration_fee",
                    "is_mega_tournament",
                    "max_player",
                    "is_completed",
                    [Sequelize.literal(`(
                        SELECT COUNT(*)
                        FROM tbl_rummy_tournament_participants AS participants
                        WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                    )`), 'total_participants'],
                    [Sequelize.literal(`(
                        SELECT CASE 
                            WHEN COUNT(*) > 0 THEN 1
                            ELSE 0
                        END
                        FROM tbl_rummy_tournament_participants AS participants
                        WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                        AND participants.user_id = ${userId}
                    )`), 'participationStatus'],
                ],
                where: {
                    // registration_start_date: {
                    //     [Op.lte]: todayDate
                    // },
                    // start_date: {
                    //     [Op.gte]: todayDate
                    // },
                    is_completed: 0,
                    id: tournementId
                }
            });
        } catch (error) {
            throw Error(error);
        }
    }

    async checkTournamentConflicts(tournementId, userId, startTime) {
        try {
            const tournementLastRound = await RummyTournamentRounds.findOne({
                where: {
                    tournament_id: tournementId
                },
                attributes: ["start_date", "start_time"],
                order: [["round", "DESC"]]
            });
            if (tournementLastRound) {
                const endTime = new Date(tournementLastRound.start_date);
                const [ehours, eminutes, eseconds] = tournementLastRound.start_time.split(':').map(Number);
                endTime.setHours(ehours, eminutes, eseconds);

                const tournaments = await RummyTournamentTableMaster.findAll({
                    attributes: [
                        "id",
                        "registration_start_time",
                        "start_date",
                        "start_time",
                        "is_completed",
                        [Sequelize.literal(`(
                            SELECT CASE 
                                WHEN COUNT(*) > 0 THEN 1
                                ELSE 0
                            END
                            FROM tbl_rummy_tournament_participants AS participants
                            WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                            AND participants.user_id = ${userId}
                        )`), 'participationStatus'],
                    ],
                    include: [
                        {
                            model: RummyTournamentRounds,
                            required: true,
                            as: "rounds",
                            attributes: ["id", "round", "start_date", "start_time"],
                            order: [["round", "ASC"]]
                        }
                    ],
                    where: {
                        is_completed: 0,
                        id: {
                            [Sequelize.Op.ne]: tournementId
                        },
                        start_date: new Date()
                    }
                });

                let hasConflict = false;
                for (let index = 0; index < tournaments.length; index++) {
                    const element = tournaments[index].toJSON();
                    if (element.participationStatus == 1) {
                        const tournementStartTime = new Date(element.start_date);
                        const [shours, sminutes, sseconds] = element.start_time.split(':').map(Number);
                        tournementStartTime.setHours(shours, sminutes, sseconds);

                        const tournementEndTime = new Date(element.rounds[element.rounds.length - 1].start_date);
                        const [ehours, eminutes, eseconds] = element.rounds[element.rounds.length - 1].start_time.split(':').map(Number);
                        tournementEndTime.setHours(ehours, eminutes, eseconds);

                        if (
                            (tournementStartTime >= startTime && tournementStartTime <= endTime) || // Case 1
                            (tournementEndTime >= startTime && tournementEndTime <= endTime) || // Case 2
                            (startTime >= tournementStartTime && endTime <= tournementEndTime) // Case 3
                        ) {
                            hasConflict = true;
                            console.log('Conflict found with tournament:');
                            break; // Exit the loop if a conflict is found
                        }
                    }
                }

                return hasConflict;
            }
        } catch (error) {
            throw Error(error);
        }
    }

    async participateTournament(payload) {
        try {
            return await RummyTournamentParticipants.create(payload);
        } catch (error) {
            throw Error(error)
        }
    }

    async getTournamentFullInfo(tournamentId) {
        try {
            return await RummyTournamentTableMaster.findOne({
                attributes: [
                    "id", "registration_start_date", "registration_start_time", "start_date", "start_time", "registration_fee", "winning_amount", "name", "max_player", "is_mega_tournament", "is_completed",
                    [Sequelize.literal(`(
                        SELECT COUNT(*)
                        FROM tbl_rummy_tournament_participants AS participants
                        WHERE participants.tournament_id = tbl_rummy_tournament_master.id
                    )`), 'total_participants']
                ],
                include: [
                    {
                        model: RummyTournamentRounds,
                        required: false,
                        as: "rounds",
                    },
                    {
                        model: RummyTournamentPrizes,
                        required: false,
                        as: "prizes"
                    }
                ],
                where: {
                    id: tournamentId
                }
            })
        } catch (error) {
            throw Error(error)
        }
    }

    async userHaveTournamentPass(tournamentId, userId) {
        try {
            return await RummyTournamentWinners.findOne({
                attributes: ["id", "tournament_id", "is_ticket_win"],
                where: {
                    // tournament_id: tournamentId,
                    user_id: userId,
                    is_ticket_win: 1
                },
                include: [
                    {
                        model: RummyTournamentTableMaster,
                        as: 'tournament',
                        attributes: [],
                        required: true,
                        where: {
                            pass_of_tournament_id: tournamentId
                        }
                    }
                ]
            })
        } catch (error) {
            throw new Error(error);
        }
    }

    async distributePriceToWinner(data) {
        try {
            return await RummyTournamentWinners.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating rummy table');
        }
    }

    async getTournamentPrizes(tournamentId) {
        try {
            return await RummyTournamentPrizes.findAll({
                attributes: ["tournament_id", "from_position", "to_position", "players", "winning_price", "given_in_round"],
                where: {
                    tournament_id: tournamentId
                },
                order: [["from_position", "ASC"]]
            })
        } catch (error) {
            throw new Error(error);
        }
    }

    async getTotalTournamentAmount(tournamentId) {
        try {
            return await RummyTournamentParticipants.count({
                where: {
                    tournament_id: tournamentId
                }
            });
        } catch (error) {
            throw new Error(error);
        }
    }

    async getTotalTournamentDistributedAmount(tournamentId) {
        try {
            return await RummyTournamentWinners.count({
                where: {
                    tournament_id: tournamentId
                }
            });
        } catch (error) {
            throw new Error(error);
        }
    }

    async getTournamentRoundPlayed(tournamentId, userId, round) {
        try {
            return await RummyTournamentTableUser.findOne({
                attributes: [
                    "tournament_id", "table_id", "user_id",
                    [sequelize.col('tbl_rummy_tournament_table.winner_id'), 'winner_id']
                ],
                include: [
                    {
                        model: RummyTournamentTable,
                        as: "tbl_rummy_tournament_table",
                        required: true,
                        where: {
                            round: round
                        }
                    }
                ],
                where: {
                    tournament_id: tournamentId,
                    user_id: userId
                }
            })
        } catch (error) {
            throw new Error(error);
        }
    }

    async getTournementStartInFiveMinute() {
        try {
            const currentTime = dateHelper.getCurrentTime();
            const timeIn5Minutes = dateHelper.get5MinutesAfterTime();
            console.log(currentTime, timeIn5Minutes)
            // const timeIn5Minutes = new Date();
            // timeIn5Minutes.setMinutes(currentTime.getMinutes() + 5);

            return await RummyTournamentTableMaster.findAll({
                include: [
                    {
                        model: RummyTournamentTable,
                        as: "tables",
                        required: false,
                        attributes: ["id"],
                        include: {
                            model: RummyTournamentTableUser,
                            as: "tbl_rummy_tournament_table_user",
                            attributes: ["user_id"],
                            required: false,
                            order: [["seat_position", "ASC"]]
                        }
                    },
                    {
                        model: RummyTournamentRounds,
                        as: "rounds",
                        required: true,
                        attributes: ["id", "round", "start_date", "start_time"],
                        where: {
                            status: 0
                        },
                        order: [["round", "ASC"]]
                    }
                ],
                where: {
                    /*start_date: {
                        [Op.gte]: new Date()
                    },
                    start_time: {
                        [Op.between]: [currentTime, timeIn5Minutes]
                    }*/
                    [Op.or]: [
                        {
                            start_date: { [Op.gte]: new Date() },
                            start_time: { [Op.between]: [currentTime, timeIn5Minutes] }
                        },
                        {
                            next_round_start_date: { [Op.gte]: new Date() },
                            next_round_start_time: { [Op.between]: [currentTime, timeIn5Minutes] }
                        }
                    ]
                }
            })
        } catch (error) {
            throw new Error(error);
        }
    }

    async getStartTournamentById(tournamentId) {
        try {
            return await RummyTournamentTableMaster.findOne({
                include: [
                    {
                        model: RummyTournamentTable,
                        as: "tables",
                        required: true,
                        attributes: ["id"],
                        where: {
                            winner_id: 0
                        },
                        include: {
                            model: RummyTournamentTableUser,
                            as: "tbl_rummy_tournament_table_user",
                            attributes: ["user_id"],
                            required: true,
                            order: [["seat_position", "ASC"]]
                        }
                    },
                    {
                        model: RummyTournamentRounds,
                        as: "rounds",
                        required: true,
                        attributes: ["id", "round", "start_date", "start_time"],
                        where: {
                            status: 0
                        },
                        order: [["round", "ASC"]]
                    }
                ],
                where: {
                    id: tournamentId
                }
            })
        } catch (error) {
            throw new Error(error);
        }
    }

    async getTournamentWinners(tournamentId) {
        try {
            return RummyTournamentWinners.findAll({
                attributes: [
                    "tournament_id", "amount", "user_id", "total_points", "round", "position", "is_ticket_win",
                    [sequelize.col('user.name'), 'user_name'], [sequelize.col('user.profile_pic'), 'profile_pic']
                ],
                include: [
                    {
                        model: user,
                        as: "user",
                        required: true,
                        attributes: []
                    }
                ],
                where: {
                    tournament_id: tournamentId
                },
                order: [["position", "ASC"]]
            })
        } catch (error) {
            throw new Error(error);
        }
    }

    async getTypes() {
        try {
            return RummyTournamentType.findAll({
                attributes: [
                    "id", "name", "image"
                ]
            })
        } catch (error) {
            throw new Error(error);
        }
    }
}

module.exports = new RummyTournamentService();