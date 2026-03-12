const { GAMES, HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, ROULETTE_NUMBER_MULTIPLY, ROULETTE_COLOR_MULTIPLY, ROULETTE_ODD_EVEN_MULTIPLY, ROULETTE_TWELTH_COLUMN_MULTIPLY, ROULETTE_EIGHTEEN_COLUMN_MULTIPLY, ROULETTE_ROW_MULTIPLY, ROULETTE_TWO_SPLIT_MULTIPLY, ROULETTE_FOUR_SPLIT_MULTIPLY, ROULETTE_TIME_FOR_START_NEW_GAME, HTTP_INSUFFIENT_PAYMENT, HTTP_NOT_FOUND, HTTP_ALREADY_USED, HTTP_NO_CONTENT, HTTP_SERVER_ERROR, ROULETTE_TIME_FOR_BET, HTTP_OK, RUMMY_CARDS, CHAAL_PERCENT, NO_CHAAL_PERCENT, MAX_POINTS } = require("../../constants");
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse, normalResponse } = require("../../utils/response");
const rouletteService = require('../../services/rouletteService');
const userService = require('../../services/userService');
const { UserWalletService } = require("../../services/walletService");
const { getRandomNumber, getRoundNumber, getAmountByPercentage, generateUniqueNumber, getAmountByPercentageWithoutRound } = require("../../utils/util");
const adminService = require("../../services/adminService");
var dateFormat = require('date-format');
const db = require("../../models");
const rummyPointService = require("../../services/rummyPointService");
const { cardPoints } = require("../../utils/cards");
const userWallet = new UserWalletService();

class RummyPointController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.get_table = this.get_table.bind(this);
        this.status = this.status.bind(this);
    }

    async get_table(req, res) {
        const responseData = await this.getTable({ user_id: 1, no_of_players: "6", boot_value: 80, game_id: 1, card: 'BP8', json: [{ card_group: 'BP', cards: 'BP8' }] });
        return successResponse(res, responseData);
    }

    async getTable(data) {
        try {
            const { user_id, token, no_of_players, boot_value } = data;
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (user.rummy_table_id) {
                const tableData = await rummyPointService.tableUsers(user.rummy_table_id);
                if (Array.isArray(tableData) && tableData.length > 0) {
                    const table = tableData[0].toJSON();
                    return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData, no_of_players: table.no_of_players });
                }
            }
            if (!["2", "6"].includes(no_of_players)) {
                return normalResponse("Invalid No. Of Players", HTTP_NOT_ACCEPTABLE);
            }

            const masterTables = await rummyPointService.getTableMaster(boot_value, no_of_players);
            if (Array.isArray(masterTables) && masterTables.length === 0) {
                return normalResponse("Invalid Boot Value", HTTP_NOT_ACCEPTABLE);
            }

            const tableMaster = masterTables[0].toJSON();
            if (user.wallet < tableMaster.boot_value) {
                const message = 'Required Minimum ' + parseInt(tableMaster.boot_value) + ' Coins to Play';
                return normalResponse(message, HTTP_NOT_ACCEPTABLE);
            }

            const tableAmount = tableMaster.boot_value;
            const tables = await rummyPointService.getCustomizeActiveTable(tableAmount, no_of_players);

            let seatPosition = 1;
            let tableId = "";
            if (Array.isArray(tables) && tables.length > 0) {
                for (let index = 0; index < tables.length; index++) {
                    const element = tables[index].toJSON();
                    if (element.members < tableMaster.no_of_players) {
                        tableId = element.rummy_table_id;
                        seatPosition = await rummyPointService.getAvailableSeatPosition(tableId)
                        if (!seatPosition) {
                            seatPosition = 1;
                            tableId = '';
                            break;
                        }
                    }
                }
            }

            if (!tableId) {
                const tableData = {
                    boot_value: tableAmount,
                    no_of_players
                }

                const rummyTable = await rummyPointService.createTable(tableData);
                tableId = rummyTable.id;
            }

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
                seat_position: seatPosition
            }

            await rummyPointService.addTableUser(tableUserData);

            const tableUsers = await rummyPointService.tableUsers(tableId);

            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async startGame(data) {
        try {
            const { user_id, token } = data;
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            const rummyTableId = user.rummy_table_id;
            if (!rummyTableId) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const table = await rummyPointService.getTable(rummyTableId);
            const bootValue = table.boot_value;

            let tableUsers = await rummyPointService.tableUsers(rummyTableId);
            const setting = await adminService.setting(["robot_rummy", "admin_coin", "point_rummy_random"]);

            // const masterTables = await rummyPointService.getTableMaster(boot_value, no_of_players);
            if (Array.isArray(tableUsers) && tableUsers.length < 2) {
                const robotRummy = setting.robot_rummy;
                if (robotRummy == 0 && table.private == 0) {
                    const bots = await userService.getFreeRummyBots();
                    if (Array.isArray(bots) && bots.length > 0) {
                        let seatPosition = await rummyPointService.getAvailableSeatPosition(rummyTableId);
                        if (!seatPosition) {
                            seatPosition = 1;
                        }
                        const tableBotData = {
                            table_id: rummyTableId,
                            user_id: bots[0].id,
                            seat_position: seatPosition,
                        }

                        await rummyPointService.addTableUser(tableBotData);
                    }

                    tableUsers = await rummyPointService.tableUsers(rummyTableId);
                }
            }

            const game = await rummyPointService.getActiveGameOnTable(rummyTableId, ["id"]);

            if (game) {
                return normalResponse("Active Game is Going On", HTTP_NOT_ACCEPTABLE);
            }

            if (Array.isArray(tableUsers) && tableUsers.length >= 2) {
                for (let index = 0; index < tableUsers.length; index++) {
                    const element = tableUsers[index].toJSON();
                    if (element.wallet < bootValue) {
                        const tableUserData = {
                            // table_id: element.rummy_table_id, // php code
                            table_id: element.table_id,
                            user_id: element.user_id
                        }

                        await rummyPointService.removeTableUser(tableUserData);
                        tableUsers = await rummyPointService.tableUsers(rummyTableId);
                    }
                }
            }

            if (Array.isArray(tableUsers) && tableUsers.length > 2) {
                for (let index = 0; index < tableUsers.length; index++) {
                    const element = tableUsers[index].toJSON();
                    if (element.user_type == 1) {
                        const tableUserData = {
                            table_id: element.table_id,
                            user_id: element.user_id
                        }

                        await rummyPointService.removeTableUser(tableUserData);
                        tableUsers = await rummyPointService.tableUsers(rummyTableId);
                    }
                }
            }

            if (Array.isArray(tableUsers) && tableUsers.length < 0) {
                return normalResponse("Minimum 2 Players Required to Start the Game", HTTP_NOT_ACCEPTABLE);
            }

            const amount = 0;
            const cardLimit = (tableUsers.length * RUMMY_CARDS) + 2;
            const cards = await rummyPointService.getStartCards(cardLimit);
            const firstElement = cards[0].toJSON();
            const joker = firstElement.cards;

            const gameData = {
                table_id: user.rummy_table_id,
                joker: joker
            }
            
            if(setting.admin_coin < bootValue && setting.point_rummy_random == 2) {
                gameData.winner_type = 1;
            }
            const newGame = await rummyPointService.create(gameData);
            let roundTableData = [];

            if (table.start_seat_no != 0) {
                let beforeRoundTableData = [];
                let afterRoundTableData = [];

                for (let index = 0; index < tableUsers.length; index++) {
                    const element = tableUsers[index].toJSON();
                    if (element.seat_position <= table.start_seat_no) {
                        beforeRoundTableData.push(element);
                    } else {
                        afterRoundTableData.push(element);
                    }
                }

                roundTableData = [...afterRoundTableData, ...beforeRoundTableData];
            } else {
                roundTableData = tableUsers
            }

            let dropCard = "";
            let end = 1;

            for (let index = 0; index < roundTableData.length; index++) {
                const element = roundTableData[index];
                let start = end;
                end = start + RUMMY_CARDS;

                for (let i = start; i < end; i++) {
                    const card = cards[i].toJSON();
                    if (!dropCard && joker.substring(2) != card.cards.substring(2)) {
                        dropCard = card.cards;
                        i++;
                        end++;
                    }

                    const tableUserData = {
                        game_id: newGame.id,
                        user_id: element.user_id,
                        card: card.cards
                    }

                    await rummyPointService.giveGameCards(tableUserData);
                }

                await rummyPointService.addGameCount(element.user_id);

                const gameLog = {
                    game_id: newGame.id,
                    user_id: element.user_id,
                    action: 0,
                    amount: amount
                }

                rummyPointService.addGameLog(gameLog);

                if (index === 0) {
                    rummyPointService.updateSeatNumber(table.id, element.seat_position)
                }

            }

            const tableUserPayload = {
                game_id: newGame.id,
                user_id: 0,
                card: dropCard
            }

            await rummyPointService.startDropGameCards(tableUserPayload);

            return normalResponse("Success", HTTP_OK, { game_id: newGame.id, table_amount: amount });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async getPrivateTable(data) {
        try {
            const { user_id, boot_value } = data;
            if (!user_id || !boot_value) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (user.table_id) {
                return normalResponse("You Are Already On Table", HTTP_NOT_ACCEPTABLE);
            }
            const tableData = {
                boot_value: boot_value,
                maximum_blind: 4,
                chaal_limit: parseInt(boot_value) * 128,
                pot_limit: parseInt(boot_value) * 1024,
                private: 2,
                code: generateUniqueNumber()
            }

            const rummyTable = await rummyPointService.createTable(tableData);

            const tableId = rummyTable.id;

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
                seat_position: 1
            }

            await rummyPointService.addTableUser(tableUserData);

            const tableUsers = await rummyPointService.tableUsers(tableId);

            return normalResponse("Success", HTTP_OK, { table_id: tableId, table_data: tableUsers, table_code: tableData.code });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async joinTable(data) {
        try {
            const { user_id, table_id } = data;
            if (!user_id || !table_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (user.rummy_table_id) {
                const responsePayload = {
                    table_data: [{ table_id: user.rummy_table_id }],
                    table_id: user.rummy_table_id
                }
                return normalResponse("You Are Already On Table", HTTP_NOT_ACCEPTABLE, responsePayload);
            }

            const userOnTable = await rummyPointService.checkUsersOnTable(table_id);

            if (!userOnTable) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }

            const table = await rummyPointService.getTable(table_id);

            if (!table) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }
            const bootValue = table.boot_value;

            if (user.wallet < bootValue) {
                const message = 'Required Minimum ' + table.boot_value + ' Coins to Play';
                return normalResponse(message, HTTP_NOT_ACCEPTABLE);
            }

            const seatPosition = await rummyPointService.getAvailableSeatPosition(table_id)
            const tableUserData = {
                table_id,
                user_id: user.id,
                seat_position: seatPosition
            }

            await rummyPointService.addTableUser(tableUserData);

            const tableUsers = await rummyPointService.tableUsers(table_id);

            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async joinTableWithCode(data) {
        try {
            const { user_id, code } = data;
            if (!user_id || !code) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (user.rummy_table_id) {
                return normalResponse("You Are Already On Table", HTTP_NOT_ACCEPTABLE);
            }

            const table = await rummyPointService.getTableByCode(code);

            if (!table) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }
            const bootValue = table.boot_value;

            if (user.wallet < bootValue) {
                const message = 'Required Minimum ' + table.boot_value + ' Coins to Play';
                return normalResponse(message, HTTP_NOT_ACCEPTABLE);
            }

            const seatPosition = await rummyPointService.getAvailableSeatPosition(table.id)
            const tableUserData = {
                table_id: table.id,
                user_id: user.id,
                seat_position: seatPosition
            }

            await rummyPointService.addTableUser(tableUserData);

            const tableUsers = await rummyPointService.tableUsers(table.id);

            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async leaveTable(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;

            const tableUserData = {
                table_id: user.rummy_table_id,
                user_id: user.id,
            }

            await rummyPointService.removeTableUser(tableUserData);

            const game = await rummyPointService.getActiveGameOnTable(tableId, ["id", "amount"]);

            if (game) {
                const onlyGameUsers = await rummyPointService.gameOnlyUsers(game.id, user.id);
                if (Array.isArray(onlyGameUsers) && onlyGameUsers.length > 0) {
                    const table = await rummyPointService.getTable(tableId);
                    const bootValue = table.boot_value;
                    const chaalCount = await rummyPointService.chaalCount(game.id, user_id);

                    const percent = chaalCount > 0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                    const amount = await getAmountByPercentageWithoutRound(bootValue, percent);

                    // pack game
                    const timeout = '';
                    await rummyPointService.packGame(user_id, game.id, timeout, '', amount, percent);
                    await userWallet.minusUserWallet(user_id, amount, GAMES.pointRummy);

                    const gameUsers = await rummyPointService.gameUsers(game.id);

                    if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                        const activeGame = await rummyPointService.getActiveGameOnTable(tableId);
                        const winnerUser = gameUsers[0].toJSON();
                        const setting = await adminService.setting(["admin_commission"]);
                        const comission = setting.admin_commission;
                        await this.makeWinner(activeGame.id, activeGame.amount, winnerUser.user_id, comission);
                    }
                    // make winner
                }
            }

            const tableUsers = await rummyPointService.tableUsers(tableId);

            if (Array.isArray(tableUsers) && tableUsers.length === 1) {
                const tableUser = tableUsers[0].toJSON();
                if (!tableUser.mobile) {
                    const tableUserData = {
                        table_id: tableUser.table_id,
                        user_id: tableUser.user_id
                    }

                    await rummyPointService.removeTableUser(tableUserData);
                }
            }

            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async packGame(data) {
        try {
            const { user_id, timeout, json } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const table = await rummyPointService.getTable(tableId);
            const bootValue = table.boot_value;

            const chaalCount = await rummyPointService.chaalCount(game.id, user_id);

            const percent = chaalCount > 0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
            const amount = await getAmountByPercentageWithoutRound(bootValue, percent);
            await rummyPointService.packGame(user_id, game.id, timeout, json, amount, percent);
            await userWallet.minusUserWallet(user_id, amount, GAMES.pointRummy);

            const gameUsers = await rummyPointService.gameUsers(game.id);

            if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                const activeGame = await rummyPointService.getActiveGameOnTable(tableId);
                // const game = await rummyPointService.getActiveGameOnTable(tableId);
                const winnerUser = gameUsers[0].toJSON();
                const setting = await adminService.setting(["admin_commission"]);
                const comission = setting.admin_commission;
                await this.makeWinner(activeGame.id, activeGame.amount, winnerUser.user_id, comission);
            }

            if (timeout == 1) {
                const tableUserData = {
                    table_id: tableId,
                    user_id: user.id
                }

                await rummyPointService.removeTableUser(tableUserData);
            }
            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async wrongDeclare(data) {
        try {
            const { user_id, timeout, json } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const table = await rummyPointService.getTable(tableId);
            const bootValue = table.boot_value;

            // const chaalCount = await rummyPointService.chaalCount(tableId);

            const percent = 100;
            const amount = await getAmountByPercentageWithoutRound(bootValue, percent);
            await rummyPointService.packGame(user_id, game.id, timeout, json, amount, percent);
            await userWallet.minusUserWallet(user_id, amount, GAMES.pointRummy);

            const gameUsers = await rummyPointService.gameUsers(game.id);

            if (Array.isArray(gameUsers) && gameUsers.length > 0) {
                // const game = await rummyPointService.getActiveGameOnTable(tableId);
                const activeGame = await rummyPointService.getActiveGameOnTable(tableId);
                const winnerUser = gameUsers[0].toJSON();
                const setting = await adminService.setting(["admin_commission"]);
                const comission = setting.admin_commission;
                await this.makeWinner(activeGame.id, activeGame.amount, winnerUser.user_id, comission);
            }

            if (timeout == 1) {
                const tableUserData = {
                    table_id: tableId,
                    user_id: user.id
                }

                await rummyPointService.removeTableUser(tableUserData);
            }
            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async myCard(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyPointService.getMyCards(game.id, user_id);
            return normalResponse("Success", HTTP_OK, { cards });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async cardValue(data) {
        try {
            const { user_id, card_1, card_2, card_3, card_4, card_5, card_6 } = data;
            if (!user_id || !card_1 || !card_2) {
                return normalResponse("Minimum 3 cards Needed For Grouping", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            let joker = "";
            if (game) {
                joker = game.joker;
            }

            let cardValue = rummyPointService.cardValue("", card_1, card_2, card_3, card_4, card_5, card_6);
            if (cardValue) {
                if (cardValue[0] == 0) {
                    cardValue = rummyPointService.cardValue(joker, card_1, card_2, card_3, card_4, card_5, card_6);
                }

                return normalResponse("Success", HTTP_OK, { card_value: cardValue });
            }

            return normalResponse("Invalid Card Value", HTTP_NOT_ACCEPTABLE);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async dropCard(data) {
        try {
            const { user_id, card, json } = data;
            let responsePayload = {
                bot: 0
            }
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE, responsePayload);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE, responsePayload);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE, responsePayload);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE, responsePayload);
            }

            const cards = await rummyPointService.getMyCards(game.id, user_id);

            if (Array.isArray(cards) && cards.length <= RUMMY_CARDS) {
                return normalResponse("Please Get Or Pick Card First And Then Drop One", HTTP_NOT_ACCEPTABLE, responsePayload);
            }

            const isCardAvailable = await rummyPointService.getMyCard(game.id, user_id, card);

            if (isCardAvailable) {
                if (card == 'JKR1' || card == 'JKR2') {
                    return normalResponse("You Can\'t Drop Joker Card", HTTP_NOT_ACCEPTABLE);
                }
                const tableUserData = {
                    game_id: game.id,
                    user_id,
                    card
                }

                await rummyPointService.dropGameCard(tableUserData, json);

                const gameUsers = await rummyPointService.gameAllUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 2) {
                    const bots = await rummyPointService.getGameBot(game.id);
                    if (Array.isArray(bots) && bots.length > 0) {
                        responsePayload.bot = 1
                    }
                }

                return normalResponse("Success", HTTP_OK, responsePayload);
            }

            return normalResponse("Invalid Card", HTTP_OK, responsePayload);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async getCard(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyPointService.getMyCards(game.id, user_id);
            if (Array.isArray(cards) && cards.length > RUMMY_CARDS) {
                return normalResponse("Please Drop Card And Then Pick One", HTTP_NOT_ACCEPTABLE);
            }

            const randomCard = await rummyPointService.getRamdomGameCard(game.id);
            if (randomCard) {
                const tableUserData = {
                    game_id: game.id,
                    user_id,
                    card: randomCard.cards,
                    isDeleted: 0
                }

                await rummyPointService.giveGameCards(tableUserData);
                return normalResponse("Success", HTTP_OK, { card: [randomCard] });
            }
            return normalResponse("Invalid Chaal", HTTP_NOT_ACCEPTABLE);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async getDropCard(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyPointService.getMyCards(game.id, user_id);
            if (Array.isArray(cards) && cards.length > RUMMY_CARDS) {
                return normalResponse("Please Drop Card And Then Pick One", HTTP_NOT_ACCEPTABLE);
            }

            const dropCard = await rummyPointService.getAndDeleteGameDropCard(game.id);
            if (dropCard) {
                const tableUserData = {
                    game_id: game.id,
                    user_id,
                    card: dropCard.card,
                    is_drop_card: 1,
                    isDeleted: 0
                }

                await rummyPointService.giveGameCards(tableUserData);

                return normalResponse("Success", HTTP_OK, { card: [dropCard] });
            }

            return normalResponse("Invalid Chaal", HTTP_NOT_ACCEPTABLE);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async declare(data) {
        try {
            const { user_id, json } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const jsonArray = JSON.parse(json);
            let points = 0;

            for (let index = 0; index < jsonArray.length; index++) {
                const element = jsonArray[index];
                if (element.card_group == 0) {
                    points += cardPoints(element.cards, game.joker)
                }
            }

            points = (points > 80) ? 80 : points;

            const declareData = {
                user_id,
                game_id: game.id,
                points,
                actual_points: 0,
                json
            }
            await rummyPointService.declare(declareData);

            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async declareBack(data) {
        try {
            const { user_id, json } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const game = await rummyPointService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const gameLog = await rummyPointService.gameLog(game.id, 1);

            const remainingGameUsers = await rummyPointService.gameUsers(game.id);

            if (Array.isArray(gameLog)) {
                const log = gameLog[0].toJSON();
                if (log.action != 3) {
                    return normalResponse("Invalid Declare Back", HTTP_NOT_ACCEPTABLE);
                }
            }

            const jsonArray = JSON.parse(json);
            let points = 0;

            for (let index = 0; index < jsonArray.length; index++) {
                const element = jsonArray[index];
                if (element.card_group == 0) {
                    points += cardPoints(element.cards, game.joker)
                }
            }

            points = (points > 80) ? 80 : points;

            const table = await rummyPointService.getTable(tableId);
            const actualPoints = points * getRoundNumber((table.boot_value / 80), 2);

            const declareData = {
                user_id,
                game_id: game.id,
                points,
                table_id: tableId,
                actual_points: actualPoints,
                json
            }
            await rummyPointService.declare(declareData);

            const declareLog = await rummyPointService.gameLog(game.id, '', 3);
            const declareCount = declareLog.length;

            if (remainingGameUsers.length <= declareCount) {
                const winnerUser = declareLog[declareCount - 1].toJSON();
                // const game = await rummyPointService.getActiveGameOnTable(tableId);
                const activeGame = await rummyPointService.getActiveGameOnTable(tableId);
                await userWallet.minusUserWallet(user_id, actualPoints, GAMES.pointRummy);
                const setting = await adminService.setting(["admin_commission"]);
                const comission = setting.admin_commission;
                await this.makeWinner(activeGame.id, activeGame.amount, winnerUser.user_id, comission);
            }

            return normalResponse("Success", HTTP_OK, { winner: 0 });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async status(req, res) {
        const responseData = await this.getStatus(req.body);
        return successResponse(res, responseData);
    }
    async getStatus(data) {
        try {
            const { user_id, game_id } = data;

            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_table_id;
            const responseData = {};
            const tableData = await rummyPointService.tableUsers(tableId);
            if (tableId) {
                const tableNewData = [];
                for (let index = 0; index < 6; index++) {
                    tableNewData[index] = {
                        id: 0,
                        table_id: 0,
                        user_id: 0,
                        seat_position: index + 1,
                        added_date: 0,
                        updated_date: 0,
                        isDeleted: 0,
                        name: 0,
                        mobile: 0,
                        profile_pic: 0,
                        wallet: 0
                    };
                }

                for (let j = 0; j < tableData.length; j++) {
                    const element = tableData[j].toJSON();
                    tableNewData[element.seat_position - 1] = element
                }

                const table = await rummyPointService.getTable(tableId);

                responseData.table_users = tableNewData;
                responseData.table_detail = table;
                responseData.active_game_id = 0;
                responseData.game_status = 0;
                responseData.table_amount = table.boot_value
                /*const responseData = {
                    table_users: tableNewData,
                    table_detail: table,
                    active_game_id: 0,
                    game_status: 0,
                    table_amount: table.boot_value
                }*/

                const activeGame = await rummyPointService.getActiveGameOnTable(tableId);

                if (activeGame) {
                    responseData.active_game_id = activeGame.id;
                    responseData.game_status = 1;
                }
            }

            const gameId = game_id ? game_id : responseData.active_game_id;
            if (!gameId) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE, responseData);
            }

            const game = await rummyPointService.getById(gameId);

            if (!game) {
                return normalResponse("Invalid Game", HTTP_NOT_ACCEPTABLE, responseData);
            }

            const gameLog = await rummyPointService.gameLog(gameId, 1);

            const gameUsers = await rummyPointService.gameAllUsers(gameId);

            let chaal = 0;
            let element = 0;
            const log = gameLog[0].toJSON();

            for (let index = 0; index < gameUsers.length; index++) {
                const singleUser = gameUsers[index];
                if (singleUser.user_id == log.user_id) {
                    element = index;
                    break;
                }
            }

            let chaalIndex = 0;
            for (let index = 0; index < gameUsers.length; index++) {
                chaalIndex = (index + element) % gameUsers.length;
                if (index > 0) {
                    if (!gameUsers[chaalIndex].packed) {
                        chaal = gameUsers[chaalIndex].user_id;
                        break;
                    }
                }
            }

            responseData.game_log = gameLog;
            responseData.all_users = tableData;
            responseData.declare = false;
            responseData.declare_user_id = 0;
            if (log.action == 3) {
                responseData.declare = true;
                responseData.declare_user_id = log.user_id;
            }

            const gameUser = await rummyPointService.gameOnlyUsers(game.id);
            responseData.game_users = gameUser
            responseData.chaal = chaal;
            responseData.game_amount = game.amount;
            const lastCard = await rummyPointService.lastGameCard(game.id);
            const discardedCard = await rummyPointService.discardedGameCard(game.id);
            const chaalCount = await rummyPointService.chaalCount(game.id, chaal);
            const percent = chaalCount > 0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
            const cutPoint = await getAmountByPercentageWithoutRound(MAX_POINTS, percent);
            responseData.last_card = lastCard;
            responseData.discarded_card = discardedCard;
            responseData.cut_point = cutPoint;

            if (user_id) {
                const gameDropCard = await rummyPointService.getGameDropCard(game.id)
                responseData.drop_card = [gameDropCard];
                responseData.joker = game.joker
            }
            responseData.message = "Success";
            if (game.winner_id > 0) {
                responseData.chaal = 0;
                responseData.message = "Game Completed";
                const gameUsersCards = [];
                for (let index = 0; index < gameUser.length; index++) {
                    const element = gameUser[index].toJSON();

                    // Fetch the declare log for the user
                    const declareLog = await rummyPointService.gameLog(game.id, 1, '', element.user_id);
                    const log = declareLog[0].toJSON();

                    // Fetch the user's game log JSON and parse it
                    const gameLogJson = await rummyPointService.gameLogJson(game.id, element.user_id);
                    gameUsersCards[index] = {};
                    gameUsersCards[index]['user'] = {
                        ...element,
                        win: (game.winner_id === element.user_id) ? game.user_winning_amt : log.amount,
                        result: log.action,
                        score: log.points,
                        cards: (gameLogJson && gameLogJson.json) ? JSON.parse(gameLogJson.json) : []
                    };
                }

                responseData.game_users_cards = gameUsersCards;
                responseData.game_status = 2;
                responseData.winner_user_id = game.winner_id;
            }

            return normalResponse("Success", HTTP_OK, responseData);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async makeWinner(gameId, amount, userId, comission) {
        try {
            const adminComissionAmount = await getAmountByPercentage(amount, comission);
            const userWinningAmount = getRoundNumber(amount - adminComissionAmount, 2);
            const gamePayload = {
                admin_winning_amt: adminComissionAmount,
                user_winning_amt: userWinningAmount,
                winner_id: userId
            }
            // Update bet
            rummyPointService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            // const rouletteBet = await rouletteService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, 0, userWinningAmount, adminComissionAmount, GAMES.pointRummy);
        } catch (error) {
            console.log(error);
        }
    }

    async rummyAutoChaal(tableId) {
        const game = await rummyPointService.getActiveGameOnTable(tableId);
        const setting = await adminService.setting(["admin_commission", "admin_coin"]);
        if (game) {
            let chaal = 0;
            let userType = 0;
            let declareCount = 0;
            const gameLog = await rummyPointService.gameLog(game.id, 1);
            const gameUsers = await rummyPointService.gameAllUsers(game.id);
            let element = 0;
            const log = gameLog[0].toJSON();
            for (let i = 0; i < gameUsers.length; i++) {
                const item = gameUsers[i];
                if (item.user_id == log.user_id) {
                    element = i;
                    continue;
                }
            }

            let index = 0;
            for (let ind = 0; ind < gameUsers.length; ind++) {
                const item = gameUsers[ind];
                index = (ind + element) % gameUsers.length;
                if (ind > 0) {
                    if (!gameUsers[index].packed) {
                        chaal = gameUsers[index].user_id;
                        userType = gameUsers[index].user_type;
                        break;
                    }
                }
            }

            if (userType == 1) {
                const botChaal = await rummyPointService.chaalCount(game.id, chaal);
                let randomNumber = getRandomNumber(100, 110)
                if(game.winner_type == 1) {
                    randomNumber = getRandomNumber(2, 3)
                }
                if (botChaal > randomNumber) {
                    const combination_json = [];

                    combination_json.push('[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]');
                    combination_json.push('[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]');
                    combination_json.push('[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]');
                    combination_json.push('[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]');
                    combination_json.push('[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]');
                    combination_json.push('[{"card_group":"5","cards":["BL4","BL3_","BL2_"]},{"card_group":"4","cards":["BL10","BL9","BL8_","BLJ"]},{"card_group":"5","cards":["RP6_","RP5","RP4"]},{"card_group":"4","cards":["BLK_","BLQ_","BLJ_"]}]');
                    combination_json.push('[{"card_group":"5","cards":["RSA_","RSK_","RSQ_"]},{"card_group":"4","cards":["BPK_","BPQ_","BPJ_"]},{"card_group":"4","cards":["BL8_","BL6","BL7"]},{"card_group":"5","cards":["BP9_","BP8","BP7_","BP6_"]}]');
                    combination_json.push('[{"card_group":"4","cards":["RSJ","RS9_","RS8_","RS10"]},{"card_group":"4","cards":["RP7","RP5","RP6_"]},{"card_group":"4","cards":["BLA","BL3_","BL2_"]},{"card_group":"5","cards":["BPA_","BPK","BPQ"]}]');

                    const bot_combination_json = combination_json[Math.floor(Math.random() * combination_json.length)];

                    const declareData = {
                        user_id: chaal,
                        game_id: game.id,
                        points: 0,
                        actual_points: 0,
                        json: bot_combination_json
                    };

                    await rummyPointService.declare(declareData)
                    return;
                }
            }

            if (log.action == 3) {
                const activeGameUser = await rummyPointService.gameUsers(game.id);
                const combination_json = [];
                for (let index = 0; index < activeGameUser.length; index++) {
                    const element = activeGameUser[index];
                    let jsonArray = [];
                    if (userType == 1) {
                        combination_json.push('[{"card_group":"6","cards":["BLK","RSK","RPK"]},{"card_group":"5","cards":["BP10_","BP9","BP8"]},{"card_group":"4","cards":["RS3_","RS2_","JKR2","RS4"]},{"card_group":"6","cards":["JKR1","RP8_","RS8"]}]');
                        combination_json.push('[{"card_group":"6","cards":["RS9_","BL9_","BP9"]},{"card_group":"4","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BLA","BLK_","BLQ_"]},{"card_group":"5","cards":["RPQ","RPJ","RP10_"]}]');
                        combination_json.push('[{"card_group":"6","cards":["RS6_","RP6_","BP6"]},{"card_group":"5","cards":["RPA_","RP4_","RP3","RP2"]},{"card_group":"4","cards":["BP4_","BP3_","JKR2"]},{"card_group":"5","cards":["BL8_","BL7_","BL6_"]}]');
                        combination_json.push('[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]');
                        combination_json.push('[{"card_group":"6","cards":["RS2_","BL2_","BP2","RP2_"]},{"card_group":"6","cards":["RS4_","BP4","RP4_"]},{"card_group":"5","cards":["RP7_","RP6_","RP5_"]},{"card_group":"4","cards":["BL5","BL4_","BL3"]}]');

                        const bot_combination_json = combination_json[Math.floor(Math.random() * combination_json.length)];

                        jsonArray.push({ json: bot_combination_json });
                        jsonArray = JSON.parse(JSON.stringify(jsonArray));
                    } else {
                        jsonArray = await rummyPointService.gameLog(game.id, 1, 2, element.user_id);
                    }

                    const alreadyDeclare = await rummyPointService.gameLog(game.id, 1, 3, element.user_id);

                    if (Array.isArray(alreadyDeclare) && alreadyDeclare.length == 0) {
                        const json = '[]';
                        const points = 80;

                        const table = await rummyPointService.getTable(tableId);
                        const actual_points = points * getRoundNumber((table.boot_value / 80), 2);

                        const data_log = {
                            user_id: element.user_id,
                            game_id: game.id,
                            table_id: tableId,
                            points: points,
                            actual_points,
                            json: json
                        };

                        await rummyPointService.declare(data_log);
                    }

                    const declare_log = await rummyPointService.gameLog(game.id, '', 3);
                    const declare_count = declare_log.length;

                    if (activeGameUser.length <= declare_count) {
                        const active_game = await rummyPointService.getActiveGameOnTable(tableId);
                        if (active_game) {
                            const commission = setting.admin_commission;
                            await this.makeWinner(active_game.id, active_game.amount, declare_log[declare_count - 1].user_id, commission);
                        }
                    }
                }
                return
            }

            const timeoutLog = await rummyPointService.gameLog(game.id, '', 2, chaal, 1);
            if (Array.isArray(timeoutLog) && timeoutLog.length < 2) {
                const cards = await rummyPointService.getMyCards(game.id, chaal);
                if (Array.isArray(cards) && cards.length <= RUMMY_CARDS) {
                    const randomCard = await rummyPointService.getRamdomGameCard(game.id);
                    if (randomCard) {
                        const tableUserData = {
                            game_id: game.id,
                            user_id: chaal,
                            card: randomCard.cards,
                            isDeleted: 0
                        }

                        await rummyPointService.giveGameCards(tableUserData);
                    }
                }

                const userCard = await rummyPointService.gameUserCard(game.id, chaal);
                if (userCard) {
                    const jsonArray = await rummyPointService.gameLog(game.id, 1, 2, chaal);
                    // let json = (Array.isArray(jsonArray) && jsonArray.length > 0) ? jsonArray[0].json : '';
                    const singleJSON = jsonArray[0];
                    //.toJSON();
                    let json = (Array.isArray(jsonArray) && jsonArray.length > 0) ? singleJSON.json : '';
                    let card = "";
                    if (userCard.card == "JKR1" || userCard.card == "JKR2") {
                        if (json) {
                            const arr = JSON.parse(json);
                            const finalArray = [];
                            arr.forEach((value) => {
                                if (!card && value.card_group === 0) {
                                    card = value.cards[0];

                                    const card_json = {
                                        card_group: "0",
                                        cards: [userCard.card]
                                    };

                                    finalArray.push(card_json);
                                    return; // Continue to the next iteration
                                }

                                finalArray.push(value);
                            });
                            json = JSON.stringify(finalArray);
                        }
                    }

                    card = card || userCard?.card;
                    const tableUserData = {
                        game_id: game.id,
                        user_id: chaal,
                        card
                    }
                    const timeout = userType == 0 ? 1 : 0;
                    await rummyPointService.dropGameCard(tableUserData, json, timeout);
                }
            } else {
                const table = await rummyPointService.getTable(tableId);
                const bootValue = table.boot_value;
                const chaalCount = await rummyPointService.chaalCount(game.id, chaal);
                const percent = (chaalCount > 0) ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
                const amount = await getAmountByPercentageWithoutRound(bootValue, percent);
                await rummyPointService.packGame(chaal, game.id, 1, '', amount, percent);
                await userWallet.minusUserWallet(chaal, amount, GAMES.pointRummy);
                const gameUsers = await rummyPointService.gameUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                    const winnerUser = gameUsers[0].toJSON();
                    const comission = setting.admin_commission;
                    this.makeWinner(game.id, game.amount, winnerUser.user_id, comission);
                }

                const tableUserData = {
                    table_id: tableId,
                    user_id: chaal
                }

                await rummyPointService.removeTableUser(tableUserData);
            }
        }
        return game ? "Running" : "Stop";
    }
}

module.exports = new RummyPointController();