const { GAMES, HTTP_NOT_ACCEPTABLE, HTTP_INSUFFIENT_PAYMENT, HTTP_OK, HTTP_INVALID, HIGH_CARDS } = require("../../constants");
const { errorResponse, successResponse, normalResponse } = require("../../utils/response");
const userService = require('../../services/userService');
const { UserWalletService } = require("../../services/walletService");
const { getRandomNumber, getRoundNumber, getAmountByPercentage, getRandomFromFromArray } = require("../../utils/util");
const adminService = require("../../services/adminService");
const teenpattiService = require("../../services/teenpattiService");
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
            const { user_id, boot_value } = data;
            if (!user_id || !boot_value) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id, ["table_id", "id", "wallet"]);
            if (!user) {
                return normalResponse("Invalid User", HTTP_INVALID);
            }
            if (user.table_id) {
                const tableData = await teenpattiService.tableUsers(user.table_id);
                if (Array.isArray(tableData) && tableData.length > 0) {
                    return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData });
                }
            }

            const masterTables = await teenpattiService.getTableMaster(boot_value);
            if (Array.isArray(masterTables) && masterTables.length === 0) {
                return normalResponse("Invalid Boot Value", HTTP_NOT_ACCEPTABLE);
            }

            const tableMaster = masterTables[0].toJSON();
            const minWallet = (tableMaster.boot_value) * 50;
            if (user.wallet < minWallet) {
                const message = 'Required Minimum ' + parseInt(minWallet) + ' Coins to Play';
                return normalResponse(message, HTTP_NOT_ACCEPTABLE);
            }

            // const tableAmount = tableMaster.boot_value;
            const tables = await teenpattiService.getCustomizeActiveTable(tableMaster.boot_value);

            let seatPosition = 1;
            let tableId = "";
            if (Array.isArray(tables) && tables.length > 0) {
                for (let index = 0; index < tables.length; index++) {
                    const element = tables[index].toJSON();
                    if (element.members < 5) {
                        tableId = element.table_id;
                        seatPosition = await teenpattiService.getAvailableSeatPosition(tableId)
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
                    boot_value: tableMaster.boot_value,
                    maximum_blind: 4,
                    chaal_limit: tableMaster.chaal_limit,
                    pot_limit: tableMaster.pot_limit
                }

                const teenpattiTable = await teenpattiService.createTable(tableData);
                tableId = teenpattiTable.id;

                const setting = await adminService.setting(["robot_teenpatti", "mobile"]);
                const robotTeenpatti = setting.robot_teenpatti;
                if (robotTeenpatti == 0) {
                    const bot = await userService.getFreeBots(minWallet);
                    if (Array.isArray(bot) && bot.length > 0) {
                        const tableBotData = {
                            table_id: tableId,
                            user_id: bot[0].id,
                            seat_position: 2
                        }

                        await teenpattiService.addTableUser(tableBotData);
                    }
                }

                if (setting.mobile) {
                    const adminUser = await userService.getUserByMobile(setting.mobile, ["id", "fcm"]);
                    if (adminUser && adminUser.fcm) {
                        const notificationPayload = {
                            msg: "Lets Card",
                            message: "New User On Teenpatti Table Boot Value " + tableMaster.boot_value
                        }
                        // send push notification
                    }
                }
            }

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
                seat_position: seatPosition
            }

            await teenpattiService.addTableUser(tableUserData);

            const tableUsers = await teenpattiService.tableUsers(tableId);

            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async getCustomiseTable(data) {
        try {
            const { user_id, boot_value } = data;
            if (!user_id || !boot_value) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id, ["table_id", "id", "wallet"]);
            if (!user) {
                return normalResponse("Invalid User", HTTP_INVALID);
            }
            if (user.wallet < boot_value) {
                const message = 'Required Minimum ' + boot_value + ' Coins to Play';
                return normalResponse(message, HTTP_INSUFFIENT_PAYMENT);
            }
            if (user.table_id) {
                return normalResponse("You Are Already On Table", HTTP_OK);
            }



            /*const masterTables = await teenpattiService.getTableMaster(boot_value);
            if (Array.isArray(masterTables) && masterTables.length === 0) {
                return normalResponse("Invalid Boot Value", HTTP_NOT_ACCEPTABLE);
            }

            const tableMaster = masterTables[0].toJSON();
            const minWallet = (tableMaster.boot_value) * 50;
            if (user.wallet < minWallet) {
                const message = 'Required Minimum ' + parseInt(minWallet) + ' Coins to Play';
                return normalResponse(message, HTTP_NOT_ACCEPTABLE);
            }*/

            // const tableAmount = tableMaster.boot_value;
            const tables = await teenpattiService.getCustomizeActiveTable(tableMaster.boot_value);

            let seatPosition = 1;
            let tableId = "";
            if (Array.isArray(tables) && tables.length > 0) {
                for (let index = 0; index < tables.length; index++) {
                    const element = tables[index].toJSON();
                    if (element.members < 5) {
                        tableId = element.table_id;
                        seatPosition = await teenpattiService.getAvailableSeatPosition(tableId)
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
                    boot_value: boot_value,
                    maximum_blind: 4,
                    chaal_limit: boot_value * 128,
                    pot_limit: boot_value * 128,
                    private: 2
                }

                const teenpattiTable = await teenpattiService.createTable(tableData);
                tableId = teenpattiTable.id;
            }

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
                seat_position: seatPosition
            }

            await teenpattiService.addTableUser(tableUserData);

            const tableUsers = await teenpattiService.tableUsers(tableId);

            return normalResponse("Success", HTTP_OK, { table_id: tableId, table_data: tableUsers });

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
            const user = await userService.getById(user_id, ["table_id", "id", "wallet"]);
            if (!user) {
                return normalResponse("Invalid User", HTTP_INVALID);
            }
            if (user.wallet < boot_value) {
                const message = 'Required Minimum ' + boot_value + ' Coins to Play';
                return normalResponse(message, HTTP_INSUFFIENT_PAYMENT);
            }
            if (user.table_id) {
                return normalResponse("You Are Already On Table", HTTP_OK, { boot_value });
            }

            const tableData = {
                boot_value: boot_value,
                maximum_blind: 4,
                chaal_limit: boot_value * 128,
                pot_limit: boot_value * 128,
                private: 1
            }

            const teenpattiTable = await teenpattiService.createTable(tableData);
            tableId = teenpattiTable.id;

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
                seat_position: 1
            }

            await teenpattiService.addTableUser(tableUserData);

            const tableUsers = await teenpattiService.tableUsers(tableId);

            return normalResponse("Success", HTTP_OK, { table_id: tableId, table_data: tableUsers });

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
            const tableId = user.table_id;
            if (!tableId) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const table = await teenpattiService.getTable(tableId);
            const bootValue = table.boot_value;

            let tableUsers = await teenpattiService.tableUsers(tableId);

            const game = await teenpattiService.getActiveGameOnTable(tableId, ["id"]);

            if (game) {
                return normalResponse("Active Game is Going On", HTTP_NOT_ACCEPTABLE);
            }

            /*if (Array.isArray(tableUsers) && tableUsers.length > 2) {
                const setting = await adminService.setting(["robot_rummy"]);
                const robotRummy = setting.robot_rummy;
                if (robotRummy == 0 && table.private == 0) {
                    const bots = await userService.getFreeRummyBots();
                    if (Array.isArray(bots) && bots.length > 0) {
                        let seatPosition = await teenpattiService.getAvailableSeatPosition(rummyTableId);
                        if (!seatPosition) {
                            seatPosition = 1;
                        }
                        const tableBotData = {
                            table_id: rummyTableId,
                            user_id: bots[0].id,
                            seat_position: seatPosition,
                        }

                        await teenpattiService.addTableUser(tableBotData);
                    }

                    tableUsers = await teenpattiService.tableUsers(rummyTableId);
                }
            }*/

            if (Array.isArray(tableUsers) && tableUsers.length > 2) {
                for (let index = 0; index < tableUsers.length; index++) {
                    const element = tableUsers[index].toJSON();
                    if (element.user_type == 1) {
                        const tableUserData = {
                            table_id: element.table_id,
                            user_id: element.user_id
                        }

                        await teenpattiService.removeTableUser(tableUserData);
                        tableUsers = await teenpattiService.tableUsers(tableId);
                    }
                }
            }

            if (Array.isArray(tableUsers) && tableUsers.length > 0) {
                for (let index = 0; index < tableUsers.length; index++) {
                    const element = tableUsers[index].toJSON();
                    if (element.wallet < bootValue) {
                        const tableUserData = {
                            table_id: tableId,
                            user_id: element.user_id
                        }

                        await teenpattiService.removeTableUser(tableUserData);
                        tableUsers = await teenpattiService.tableUsers(tableId);
                    }
                }
            }

            if (Array.isArray(tableUsers) && tableUsers.length < 2) {
                return normalResponse("Unable to Create Game, Only One User On Table", HTTP_NOT_ACCEPTABLE);
            }

            const setting = await adminService.setting(["admin_coin", "teen_patti_random"]);
            const gameData = {
                table_id: tableId,
                amount: tableUsers.length * bootValue
            }
            if(setting.admin_coin < table.pot_limit && setting.teen_patti_random == 2 && botPlaying) {
                gameData.winner_type = 1;
            }

            const newGame = await teenpattiService.create(gameData);
            const gameId = newGame.id;
            // let robotCardSelected = [];
            // let robotCards = null;
            // let robotCards = [];
            // let cards = [];
            const botPlaying = tableUsers.find((user) => user.dataValues.user_type == 1);
            if (setting.teen_patti_random == 2 && botPlaying) {
                let robotCards = [];
                let cards = [];
                if (setting.admin_coin < table.pot_limit) {
                    // bot win
                    robotCards = await teenpattiService.getCardsForWinner();
                    const highCards = getRandomFromFromArray(HIGH_CARDS);
                    cards = [highCards[0]]
                } else {
                    // user win
                    cards = await teenpattiService.getCardsForWinner();
                    const highCards = getRandomFromFromArray(HIGH_CARDS);
                    robotCards = [highCards[0]]
                }
                let tableUserData = [];
                for (let index = 0; index < tableUsers.length; index++) {
                    const user = tableUsers[index].toJSON();
                    if (user.user_type == 1) {
                        tableUserData = {
                            game_id: gameId,
                            user_id: user.user_id,
                            card1: robotCards[0].card1,
                            card2: robotCards[0].card2,
                            card3: robotCards[0].card3
                        };
                    } else {
                        tableUserData = {
                            game_id: gameId,
                            user_id: user.user_id,
                            card1: cards[0].card1,
                            card2: cards[0].card2,
                            card3: cards[0].card3
                        };
                    }

                    await teenpattiService.giveGameCards(tableUserData);
                    await userWallet.minusUserWallet(user_id, bootValue, GAMES.teenpatti);
                    const gameLog = {
                        game_id: gameId,
                        user_id: user.user_id,
                        action: 0,
                        amount: bootValue
                    }

                    await teenpattiService.addGameLog(gameLog);
                }
            } else {
                let robotCardSelected = [];
                const robotCards = await teenpattiService.getRobotCards(1);
                if (Array.isArray(robotCards) && robotCards.length > 0) {
                    robotCardSelected.push(robotCards[0].card1);
                    robotCardSelected.push(robotCards[0].card2);
                    robotCardSelected.push(robotCards[0].card3);
                }
                const cards = await teenpattiService.getCards((tableUsers.length * 3), robotCardSelected);

                let tableUserData = [];
                for (let index = 0; index < tableUsers.length; index++) {
                    const user = tableUsers[index].toJSON();
                    if (user.user_type == 1) {
                        if (Array.isArray(robotCards) && robotCards.length > 0) {
                            tableUserData = {
                                game_id: gameId,
                                user_id: user.user_id,
                                card1: robotCards[0].card1,
                                card2: robotCards[0].card2,
                                card3: robotCards[0].card3
                            };
                        } else {
                            tableUserData = {
                                game_id: gameId,
                                user_id: user.user_id,
                                card1: cards[(index * 3)].cards,
                                card2: cards[(index * 3) + 1].cards,
                                card3: cards[(index * 3) + 2].cards
                            };
                        }
                    } else {
                        tableUserData = {
                            game_id: gameId,
                            user_id: user.user_id,
                            card1: cards[(index * 3)].cards,
                            card2: cards[(index * 3) + 1].cards,
                            card3: cards[(index * 3) + 2].cards
                        };
                    }

                    await teenpattiService.giveGameCards(tableUserData);
                    await userWallet.minusUserWallet(user_id, bootValue, GAMES.teenpatti);
                    const gameLog = {
                        game_id: gameId,
                        user_id: user.user_id,
                        action: 0,
                        amount: bootValue
                    }

                    await teenpattiService.addGameLog(gameLog);
                }
            }

            return normalResponse("Success", HTTP_OK, { game_id: newGame.id, table_amount: bootValue });

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
            const table = await teenpattiService.getTable(table_id, ["boot_value", "id"]);
            if (user.table_id) {
                const responsePayload = {
                    boot_value: table.boot_value
                }
                return normalResponse("You Are Already On Table", HTTP_NOT_ACCEPTABLE, responsePayload);
            }

            const userOnTable = await teenpattiService.checkUsersOnTable(table_id);

            if (!userOnTable) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }

            if (!table) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }

            if (user.wallet < table.boot_value) {
                const message = 'Required Minimum ' + table.boot_value + ' Coins to Play';
                return normalResponse(message, HTTP_INSUFFIENT_PAYMENT);
            }

            const seatPosition = await teenpattiService.getAvailableSeatPosition(table_id)
            const tableUserData = {
                table_id,
                user_id: user.id,
                seat_position: seatPosition
            }

            await teenpattiService.addTableUser(tableUserData);

            const tableUsers = await teenpattiService.tableUsers(table_id);

            return normalResponse("Success", HTTP_OK, { boot_value: table.boot_value, table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async chaal(data) {
        try {
            const { user_id, plus } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }
            const gameId = game.id;

            const lastChaal = await teenpattiService.lastChaal(gameId);
            const seen = lastChaal.seen;
            let amount = lastChaal.amount;

            const cards = await teenpattiService.getMyCards(gameId, user.id);
            if (seen == 0 && (cards && cards.seen == 1)) {
                amount = amount * 2;
            } else if (seen == 1 && (cards && cards.seen == 0)) {
                amount = amount / 2;
            }
            if (plus == 1) {
                amount = amount * 2;
            }
            if (user.wallet < amount) {
                const message = 'Required Minimum ' + amount + ' Coins to Play';
                return normalResponse(message, HTTP_INSUFFIENT_PAYMENT);
            }
            const gameLog = await teenpattiService.gameLog(gameId, 1);
            const gameUsers = await teenpattiService.gameAllUsers(gameId);
            let chaal = 0;
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
                index = (ind + element) % gameUsers.length;
                if (ind > 0) {
                    if (!gameUsers[index].packed) {
                        chaal = gameUsers[index].user_id;
                        // userType = gameUsers[index].user_type;
                        break;
                    }
                }
            }

            if (chaal == user_id) {
                const table = await teenpattiService.getTable(tableId, ["id", "pot_limit"]);
                const responsePayload = {
                    bot: 0
                }
                if (table.pot_limit <= (game.amount + amount)) {
                    await teenpattiService.showGame(gameId, user_id, amount);
                    const activeGameUsers = await teenpattiService.gameUsers(gameId);
                    let winner = 0;
                    let winnerUserId = 0;
                    for (let index = 0; index < activeGameUsers.length; index++) {
                        const user1 = await teenpattiService.cardValue(activeGameUsers[winner].card1, activeGameUsers[winner].card2, activeGameUsers[winner].card3)
                        const user2 = await teenpattiService.cardValue(activeGameUsers[index + 1].card1, activeGameUsers[index + 1].card2, activeGameUsers[index + 1].card3)

                        const winnerPosition = await teenpattiService.getWinnerPosition(user1, user2);
                        winner = (winnerPosition == 0) ? winner : (index + 1);
                        if ((index + 2) == activeGameUsers.length) {
                            winnerUserId = activeGameUsers[winner].user_id;
                            break;
                        }
                    }
                    const setting = await adminService.setting(["admin_commission"]);
                    await this.makeWinner(gameId, (game.amount + amount), user_id, setting.admin_commission)

                    responsePayload.winner = winnerUserId;
                    return normalResponse("Pot Show", HTTP_OK, responsePayload);
                } else {
                    await teenpattiService.chaalGame(gameId, amount, user_id, plus);
                    if (gameUsers.length == 2) {
                        const bot = await teenpattiService.getGameBot(gameId);
                        if (bot) {
                            responsePayload.bot_id = bot.id;
                            responsePayload.user_id = user_id;
                            responsePayload.amount = amount;
                            responsePayload.game_id = gameId;
                            responsePayload.table_id = tableId;
                            // sleep(rand(6,10));
                            /*const timer = getRandomNumber(6, 10);
                            setTimeout(async () => {
                                const botId = bot.id;
                                const botChaalCount = await teenpattiService.chaalCount(gameId, botId);
                                const randomNumber = getRandomNumber(6, 15);
                                if (botChaalCount > randomNumber) {
                                    const activeGame = await teenpattiService.getActiveGameOnTable(tableId);
                                    const remainingGameUsers = await teenpattiService.gameUsers(activeGame.id);

                                    const user1 = await teenpattiService.cardValue(remainingGameUsers[0].card1, remainingGameUsers[0].card2, remainingGameUsers[0].card3)
                                    const user2 = await teenpattiService.cardValue(remainingGameUsers[1].card1, remainingGameUsers[1].card2, remainingGameUsers[1].card3)

                                    const winner = await teenpattiService.getWinnerPosition(user1, user2);
                                    let winnerUserId;
                                    if (winner == 2) {
                                        if (remainingGameUsers[0].user_id == botId) {
                                            winnerUserId = remainingGameUsers[0].user_id;
                                        } else {
                                            winnerUserId = remainingGameUsers[0].user_id;
                                        }
                                    } else {
                                        winnerUserId = remainingGameUsers[winner].user_id;
                                    }
                                    await teenpattiService.showGame(gameId, botId, amount);
                                    const setting = adminService.setting(["admin_commission"]);
                                    await this.makeWinner(gameId, (game.amount + amount), user_id, setting.admin_commission);

                                    responsePayload.bot = 1;
                                } else {
                                    await teenpattiService.chaalGame(gameId, botId, amount);
                                    responsePayload.bot = 1
                                }
                            }, timer)*/
                        }
                    }
                }
                return normalResponse("Success", HTTP_OK, responsePayload);
            }

            return normalResponse("Invalid Card", HTTP_OK, { chaal });

        } catch (error) {
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async botChaal(data) {
        try {
            const { user_id, amount, game_id, table_id, bot_id } = data;
            const gameId = game_id;
            const responsePayload = {}
            const botId = bot_id;
            const botChaalCount = await teenpattiService.chaalCount(gameId, botId);
            const randomNumber = getRandomNumber(6, 15);
            const game = await teenpattiService.getActiveGameOnTable(table_id);
            if (botChaalCount > randomNumber) {
                const activeGame = await teenpattiService.getActiveGameOnTable(table_id);
                const remainingGameUsers = await teenpattiService.gameUsers(activeGame.id);

                const user1 = await teenpattiService.cardValue(remainingGameUsers[0].card1, remainingGameUsers[0].card2, remainingGameUsers[0].card3)
                const user2 = await teenpattiService.cardValue(remainingGameUsers[1].card1, remainingGameUsers[1].card2, remainingGameUsers[1].card3)

                const winner = await teenpattiService.getWinnerPosition(user1, user2);
                let winnerUserId;
                if (winner == 2) {
                    if (remainingGameUsers[0].user_id == botId) {
                        winnerUserId = remainingGameUsers[0].user_id;
                    } else {
                        winnerUserId = remainingGameUsers[0].user_id;
                    }
                } else {
                    winnerUserId = remainingGameUsers[winner].user_id;
                }
                await teenpattiService.showGame(gameId, botId, amount);
                const setting = adminService.setting(["admin_commission"]);
                await this.makeWinner(gameId, (game.amount + amount), user_id, setting.admin_commission);

                responsePayload.bot = 1;
            } else {
                await teenpattiService.chaalGame(gameId, amount, botId);
                responsePayload.bot = 1
            }
            return normalResponse("Success", HTTP_OK, responsePayload);

        } catch (error) {
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async showGame(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }
            const gameId = game.id;
            const lastChaal = await teenpattiService.lastChaal(gameId);
            const seen = lastChaal.seen;
            let amount = lastChaal.amount;

            const cards = await teenpattiService.getMyCards(gameId, user.id);
            if (seen == 0 && (cards && cards.seen == 1)) {
                amount = amount * 2;
            } else if (seen == 1 && (cards && cards.seen == 0)) {
                amount = amount / 2;
            }
            if (user.wallet < amount) {
                return normalResponse("Insufficient Wallet Amount", HTTP_INSUFFIENT_PAYMENT);
            }

            const gameLog = await teenpattiService.gameLog(gameId, 1);
            const gameUsers = await teenpattiService.gameAllUsers(gameId);
            const remainingGameUsers = await teenpattiService.gameUsers(gameId);

            if (remainingGameUsers.length != 2) {
                return normalResponse("Show can be done between 2 users only", HTTP_NOT_ACCEPTABLE);
            }

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

            if (chaal == user_id) {
                const user1 = await teenpattiService.cardValue(remainingGameUsers[0].card1, remainingGameUsers[0].card2, remainingGameUsers[0].card3)
                const user2 = await teenpattiService.cardValue(remainingGameUsers[1].card1, remainingGameUsers[1].card2, remainingGameUsers[1].card3)

                const winner = await teenpattiService.getWinnerPosition(user1, user2);
                let winnerUserId;
                if (winner == 2) {
                    if (remainingGameUsers[0].user_id == user_id) {
                        winnerUserId = remainingGameUsers[0].user_id;
                    } else {
                        winnerUserId = remainingGameUsers[0].user_id;
                    }
                } else {
                    winnerUserId = remainingGameUsers[winner].user_id;
                }
                await teenpattiService.showGame(gameId, user_id, amount);
                const setting = await adminService.setting(["admin_commission"]);
                await this.makeWinner(gameId, (game.amount + amount), winnerUserId, setting.admin_commission);
                return normalResponse("Success", HTTP_OK, { winner: winnerUserId });
            }

            return normalResponse("Invalid Show", HTTP_NOT_ACCEPTABLE);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async doSlideShow(data) {
        try {
            const { user_id, slide_id, type } = data;
            if (!user_id || !slide_id || !type) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }
            const gameId = game.id;
            const slide = await teenpattiService.getSlideShowById(slide_id);
            if (type == 1) {
                const remainGameUsers = [];
                let user1 = await teenpattiService.gameUserCard(gameId, slide.user_id)
                let user2 = await teenpattiService.gameUserCard(gameId, slide.prev_id)
                remainGameUsers.push(user1);
                remainGameUsers.push(user2);

                user1 = await teenpattiService.cardValue(remainGameUsers[0].card1, remainGameUsers[0].card2, remainGameUsers[0].card3)
                user2 = await teenpattiService.cardValue(remainGameUsers[1].card1, remainGameUsers[1].card2, remainGameUsers[1].card3)

                let looserId;
                const winner = await teenpattiService.getWinnerPosition(user1, user2);
                if (winner = 2) {
                    looserId = remainGameUsers[0].user_id;
                } else {
                    const looser = (winner == 1) ? 0 : 1;
                    looserId = remainGameUsers[looser].user_id;
                }
                await teenpattiService.packGame(looserId, gameId);
            }
            await teenpattiService.updateSlideShow(slide_id, type);
            const lastChaal = await teenpattiService.lastChaal(gameId);
            const seen = lastChaal.seen;
            let amount = lastChaal.amount;

            const cards = await teenpattiService.getMyCards(gameId, user.id);
            if (seen == 0 && (cards && cards.seen == 1)) {
                amount = amount * 2;
            } else if (seen == 1 && (cards && cards.seen == 0)) {
                amount = amount / 2;
            }
            await teenpattiService.chaalGame(gameId, amount, slide.user_id)
            return normalResponse("Success", HTTP_OK);
        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async slideShow(data) {
        try {
            const { user_id, prev_user_id } = data;
            if (!user_id || !slide_id || !type) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            const prevUser = await userService.getById(prev_user_id);
            if (!prevUser) {
                return normalResponse("Invalid Previous User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }
            if (!prevUser.table_id) {
                return normalResponse("Previous Player Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }
            if (user.table_id != prevUser.table_id) {
                return normalResponse("Players Are Not Same Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }
            const gameId = game.id;
            const lastChaal = await teenpattiService.lastChaal(gameId);
            let amount = lastChaal.amount;
            if (user.wallet < amount) {
                return normalResponse("Insufficient Wallet Amount", HTTP_INSUFFIENT_PAYMENT);
            }
            const slide = await teenpattiService.getSlideShowById(slide_id);
            const gameLog = await teenpattiService.gameLog(gameId, 1);
            const gameUsers = await teenpattiService.gameAllUsers(gameId);
            const remainingGameUsers = await teenpattiService.gameUsers(gameId);

            if (remainingGameUsers.length == 2) {
                return normalResponse("Slide Show can not be done between 2 users only", HTTP_NOT_ACCEPTABLE);
            }

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

            if (chaal == user_id) {
                const slideData = await teenpattiService.slideShow(gameId, user_id, prev_user_id);
                return normalResponse("Success", HTTP_OK, { slide_id: slideData.id });
            }
            return normalResponse("Invalid Show", HTTP_NOT_ACCEPTABLE);
        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async switchTable(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            const setting = await adminService.setting(["admin_commission", "robot_teenpatti"]);
            if (game) {
                await teenpattiService.packGame(user_id, game.id);
                const gameUsers = await teenpattiService.gameUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                    await this.makeWinner(game.id, game.amount, gameUsers[0].user_id, setting.admin_commission)
                }
            }
            const table = await teenpattiService.getTable(tableId, ["boot_value", "maximum_blind", "chaal_limit", "pot_limit", "private"]);
            const tableAmount = table.boot_value;
            const tableData = {
                boot_value: tableAmount,
                maximum_blind: table.maximum_blind,
                chaal_limit: table.chaal_limit,
                pot_limit: table.pot_limit,
                private: table.private
            }
            const tables = await teenpattiService.getCustomizeActiveTable(tableAmount);


            let seatPosition = 1;
            let newTableId = "";
            if (Array.isArray(tables) && tables.length > 0) {
                for (let index = 0; index < tables.length; index++) {
                    const element = tables[index].toJSON();
                    if (tableId != element.table_id) {
                        if (element.members < 5) {
                            newTableId = element.table_id;
                            seatPosition = await teenpattiService.getAvailableSeatPosition(tableId)
                        }
                    }
                }
            }

            const tableUserData = {
                table_id: tableId,
                user_id: user.id
            }
            await teenpattiService.removeTableUser(tableUserData);
            if (!newTableId) {
                newTableId = await teenpattiService.createTable(tableData);
                if (setting.robot_teenpatti == 0) {
                    const minWallet = tableAmount * 50;
                    const bot = await userService.getFreeBots(minWallet);
                    if (bot) {
                        const tableBotData = {
                            table_id: newTableId,
                            user_id: bot[0].id,
                            seat_position: 2
                        }
                        await teenpattiService.addTableUser(tableBotData);
                    }
                }
            }

            const newTableuserdata = {
                table_id: newTableId,
                user_id: user.id,
                seat_position: seatPosition
            }
            await teenpattiService.addTableUser(tableUserData);

            const tableUsers = await teenpattiService.tableUsers(tableId);
            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });
        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async seeCard(data) {
        try {
            const { user_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }
            const gameId = game.id;
            const cards = await teenpattiService.getMyCards(gameId, user_id, 1);
            const cardValue = await teenpattiService.cardValue(cards.card1, cards.card2, cards.card3)
            return normalResponse("Success", HTTP_OK, { cards: [cards], CardValue: cardValue });
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
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
            }

            await teenpattiService.removeTableUser(tableUserData);

            const game = await teenpattiService.getActiveGameOnTable(tableId, ["id", "amount"]);

            if (game) {
                await teenpattiService.packGame(user_id, game.id)
                const gameUsers = await teenpattiService.gameUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                    const setting = await adminService.setting(["admin_commission"]);
                    await this.makeWinner(game.id, game.amount, gameUsers[0].user_id, setting.admin_commission)
                }
            }

            const tableUsers = await teenpattiService.tableUsers(tableId);

            if (Array.isArray(tableUsers) && tableUsers.length === 1) {
                const tableUser = tableUsers[0].toJSON();
                if (tableUser.user_type == 1) {
                    const tableUserData = {
                        table_id: tableUser.table_id,
                        user_id: tableUser.user_id
                    }

                    await teenpattiService.removeTableUser(tableUserData);
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
            const { user_id, timeout } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }
            const gameId = game.id;
            const gameLog = await teenpattiService.gameLog(gameId, 1);
            let gameUsers = await teenpattiService.gameAllUsers(gameId)

            let chaal = 0;
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
                index = (ind + element) % gameUsers.length;
                if (ind > 0) {
                    if (!gameUsers[index].packed) {
                        chaal = gameUsers[index].user_id;
                        break;
                    }
                }
            }

            if (chaal == user_id) {
                await teenpattiService.packGame(user_id, gameId, timeout);
                const gameUsers = await teenpattiService.gameUsers(gameId);
                if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                    const setting = await adminService.setting(["admin_commission"]);
                    await this.makeWinner(gameId, game.amount, gameUsers[0].user_id, setting.admin_commission);
                }
                if (timeout == 1) {
                    const tableUserdata = {
                        table_id: tableId,
                        user_id: user.id
                    }
                    await teenpattiService.removeTableUser(tableUserdata);
                }
            }
            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async tip(data) {
        try {
            const { user_id, tip, gift_id, to_user_id } = data;
            if (!user_id || !tip) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }
            if (user.wallet < tip) {
                return normalResponse("Insufficiant Tip Coins", HTTP_INSUFFIENT_PAYMENT);
            }

            await userService.giveTip(tip, user_id, tableId, gift_id, to_user_id);
            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Invalid Tip", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async chat(data) {
        try {
            const { user_id, chat } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }
            const tableId = user.table_id;
            const game = await teenpattiService.getActiveGameOnTable(tableId);
            if (chat) {
                const chatData = {
                    user_id,
                    chat,
                    game: "Teenpatti",
                    game_id: game.id,
                    table_id: tableId
                }
                await userService.chat(chatData);
            }
            const chats = await userService.chatList(tableId);
            return normalResponse("Success", HTTP_OK, { list: chats });
        } catch (error) {
            console.log(error)
            return normalResponse("Something went wrong !", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async status(req, res) {
        const responseData = await this.getStatus(req.body);
        return successResponse(res, responseData);
    }
    async getStatus(data) {
        try {
            const { user_id, table_id, game_id } = data;

            if (!user_id || !table_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            const table = await teenpattiService.getTable(table_id)
            if (!table) {
                return normalResponse("Invalid Table", HTTP_NOT_ACCEPTABLE);
            }
            const responseData = {};
            if (table_id) {
                let tableUsers = await teenpattiService.tableUsers(table_id);
                if (Array.isArray(tableUsers) && tableUsers.length > 0 && table.private == 0) {
                    const setting = await adminService.setting(["robot_teenpatti"]);
                    if (setting.robot_teenpatti == 0) {
                        const minWallet = table.boot_value * 50;
                        const bots = await userService.getFreeBots(minWallet);
                        if (Array.isArray(bots) && bots.length > 0) {
                            const tableBotData = {
                                table_id,
                                user_id: bots[0].id,
                                seat_position: 5
                            }
                            await teenpattiService.addTableUser(tableBotData);
                        }
                        tableUsers = await teenpattiService.tableUsers(table_id);
                    }
                }

                const tableNewData = [];
                for (let index = 0; index < 5; index++) {
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

                for (let j = 0; j < tableUsers.length; j++) {
                    const element = tableUsers[j].toJSON();
                    tableNewData[element.seat_position - 1] = element
                }
                responseData.table_users = tableNewData;
                responseData.all_users = tableUsers;
                responseData.table_detail = table;
                responseData.active_game_id = 0;
                responseData.game_status = 0;
                responseData.table_amount = 50;

                const activeGame = await teenpattiService.getActiveGameOnTable(table_id);

                if (activeGame) {
                    responseData.active_game_id = activeGame.id;
                    responseData.game_status = 1;
                }
            }
            if (!game_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE, responseData);
            }
            const game = await teenpattiService.getById(game_id);
            if (!game) {
                return normalResponse("Invalid Game", HTTP_NOT_ACCEPTABLE, responseData);
            }

            const gameLog = await teenpattiService.gameLog(gameId, 1);

            const gameUsers = await teenpattiService.gameAllUsers(gameId);

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
            // responseData.declare = false;
            // responseData.declare_user_id = 0;
            if (log.action == 3) {
                responseData.game_users = await teenpattiService.gameAllUsers(game_id);
            } else {
                responseData.game_users = await teenpattiService.gameOnlyUsers(game_id);
            }
            responseData.chaal = chaal;
            responseData.game_amount = game.amount;
            const chaalCount = await teenpattiService.chaalCount(game.id, chaal);
            if (chaalCount > 3) {
                await teenpattiService.getMyCards(game.id, chaal, 1);
            }
            const userCards = await teenpattiService.getMyCards(game.id, user_id);
            responseData.cards = [];
            if (userCards.seen == 1) {
                responseData.cards = [userCards]
            }

            const lastChaal = await teenpattiService.lastChaal(game.id);
            const seen = lastChaal.seen;
            let amount = lastChaal.amount;

            if (seen == 0 && (userCards && userCards.seen == 1)) {
                amount = amount * 2;
            } else if (seen == 1 && (userCards && userCards.seen == 0)) {
                amount = amount / 2;
            }
            responseData.table_amount = amount;
            responseData.slide_show = await teenpattiService.getSlideShow(game.id);
            const slideShowAccepted = await teenpattiService.getSlideShow(game.id, 1);
            if (Array.isArray(slideShowAccepted) && slideShowAccepted.length > 0) {
                responseData.slide_show_from_cards = await teenpattiService.gameAllUsers(game.id, slideShowAccepted[0].user_id)
                responseData.slide_show_to_cards = await teenpattiService.gameAllUsers(game.id, slideShowAccepted[0].prev_id)
            }
            responseData.game_gifts = await userService.giftList(table_id);
            if (game.winner_id > 0) {
                responseData.chaal = 0;
                responseData.message = "Game Completed";
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

    async getTableMaster(req, res) {
        try {
            const { user_id, boot_value } = req.body;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = req.user;
            if (user.table_id) {
                const tableData = await teenpattiService.tableUsers(user.table_id);
                if (Array.isArray(tableData) && tableData.length > 0) {
                    return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData });
                }
            }

            const masterTables = await teenpattiService.getTableMaster();

            return normalResponse("Success", HTTP_OK, { table_data: masterTables });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async walletHistory(req, res) {
        try {
            const { user_id } = req.body;
            const walletHistory = await teenpattiService.walletHistory(user_id);
            const setting = await adminService.setting(["min_redeem"]);
            const responsePayload = {
                TeenPattiGameLog: walletHistory
            }
            return successResponse(res, responsePayload);
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
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
            teenpattiService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            // const rouletteBet = await rouletteService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, 0, userWinningAmount, adminComissionAmount, GAMES.teenpatti);
        } catch (error) {
            console.log(error);
        }
    }

    async autoChaal(tableId) {
        const game = await teenpattiService.getActiveGameOnTable(tableId);
        if (game) {
            let chaal = 0;
            const gameLog = await teenpattiService.gameLog(game.id, 1);
            if (Array.isArray(gameLog) && gameLog.length > 0) {
                const gameUsers = await teenpattiService.gameAllUsers(game.id);
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
                    index = (ind + element) % gameUsers.length;
                    if (ind > 0) {
                        if (!gameUsers[index].packed) {
                            chaal = gameUsers[index].user_id;
                            break;
                        }
                    }
                }

                if (chaal != 0) {
                    await teenpattiService.packGame(chaal, game.id, 1);
                    const gameUsers = await teenpattiService.gameUsers(game.id);
                    if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                        const setting = await adminService.setting(["admin_commission"]);
                        await this.makeWinner(game.id, game.amount, gameUsers[0].user_id, setting.admin_commission);
                        const user = await userService.getById(gameUsers[0].user_id, ["id", "user_type"]);
                        if (user.user_type == 1) {
                            const tableUserData = {
                                table_id: tableId,
                                user_id: user.id
                            }
                            await teenpattiService.removeTableUser(tableUserData);
                        }
                    }
                    const tableUserData = {
                        table_id: tableId,
                        user_id: chaal
                    }
                    await teenpattiService.removeTableUser(tableUserData);
                }
            }
        }
        return game ? "Running" : "Stop";
    }
}

module.exports = new RummyPointController();