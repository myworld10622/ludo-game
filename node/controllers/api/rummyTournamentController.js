const { GAMES, HTTP_NOT_ACCEPTABLE, HTTP_OK, RUMMY_CARDS, CHAAL_PERCENT, NO_CHAAL_PERCENT, MAX_POINTS, HTTP_TIME_REMAINING } = require("../../constants");
const { successResponse, normalResponse, errorResponse } = require("../../utils/response");
const userService = require('../../services/userService');
const { UserWalletService } = require("../../services/walletService");
const { getRoundNumber, getAmountByPercentage, getAmountByPercentageWithoutRound } = require("../../utils/util");
const adminService = require("../../services/adminService");
const rummyTournamentService = require("../../services/rummyTournamentService");
const { cardPoints } = require("../../utils/cards");
const errorHandler = require("../../error/errorHandler");
const { sequelize, Sequelize } = require("../../models");
const dateHelper = require("../../utils/date");
const userWallet = new UserWalletService();
class RummyTournamentController {
    constructor() {
        this.get_table = this.get_table.bind(this);
        this.status = this.status.bind(this);
    }

    async get_table(req, res) {
        const { type } = req.body;
        let responseData;
        switch (type) {
            case "get_table":
                responseData = await this.getTable(req.body);
                break;

            case "start":
                responseData = await this.startGame(req.body);
                break;

            case "leave":
                responseData = await this.leaveTable(req.body);
                break;

            case "pack":
                responseData = await this.packGame(req.body);
                break;

            case "my_card":
                responseData = await this.myCard(req.body);
                break;

            case "get_card":
                responseData = await this.getCard(req.body);
                break;

            case "get_drop_card":
                responseData = await this.getDropCard(req.body);
                break;

            case "card_value":
                responseData = await this.cardValue(req.body);
                break;

            case "drop_card":
                responseData = await this.dropCard(req.body);
                break;

            case "declare":
                responseData = await this.declare(req.body);
                break;

            case "declare_back":
                responseData = await this.declareBack(req.body);
                break;

            default:
                break;
        }

        return successResponse(res, responseData);
    }

    async getTable(data) {
        try {
            const { user_id, token, tournament_id } = data;
            if (!user_id || !tournament_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            console.log('tournament_id:', tournament_id)
            const tournament = await rummyTournamentService.getTournamentById(tournament_id);
            console.log("TTTT", tournament)
            if (tournament && tournament.is_completed == 1) {
                return normalResponse("Tournament is completed", HTTP_NOT_ACCEPTABLE);
            }
            /*const tournementStart = new Date(tournament.start_date);
            const [hours, minutes, seconds] = tournament.start_time.split(':').map(Number);
            tournementStart.setHours(hours, minutes, seconds);
            const now = new Date();

            if (tournementStart <= now) {
                return normalResponse("You can't play now", HTTP_NOT_ACCEPTABLE);
            }*/

            if (user.rummy_tournament_table_id) {
                const tableData = await rummyTournamentService.tableUsers(user.rummy_tournament_table_id);
                const tableInfo = await rummyTournamentService.getTable(user.rummy_tournament_table_id);
                // if winner is zero it mesna table is not finished
                if (tableInfo.winner_id == 0) {
                    if (Array.isArray(tableData) && tableData.length > 0) {
                        return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData });
                    }
                }
            }

            const userParticipated = await rummyTournamentService.getSingleParticipatedUser(user_id, tournament_id, ["id", "tournament_id", "amount", "can_tournament_play"]);
            if (!userParticipated) {
                return normalResponse("User Not Participated This Tournament", HTTP_NOT_ACCEPTABLE);
            }

            if (userParticipated.can_tournament_play == 0) {
                return normalResponse("You can't play new round", HTTP_NOT_ACCEPTABLE);
            }

            const tournamentRound = await rummyTournamentService.getPendingTournamentRound(tournament_id, ["round", "status", "start_date", "start_time"]);
            if (!tournamentRound) {
                return normalResponse("Tournament does not have active or pending rounds", HTTP_NOT_ACCEPTABLE);
            }

            const round = tournamentRound.round;

            /*const existsInTableUser = await rummyTournamentService.checkUserInTableUser(tournament_id, user_id, round);
            console.log("Check exists", tournament_id, user_id, round, existsInTableUser)
            if (existsInTableUser) {
                const tableData = await rummyTournamentService.tableUsers(user.rummy_tournament_table_id);
                await userService.update(user_id, { rummy_tournament_table_id: existsInTableUser.table_id });
                if (Array.isArray(tableData) && tableData.length > 0) {
                    return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData });
                }
            }*/

            const now = new Date();
            if (tournamentRound.status == 0) {
                // console.log(upcomingRoundData.start_time)
                if (round == 1) {
                    const tournamentStartTime = new Date(tournament.start_date);
                    const [tHour, tMinute, tSecond] = tournament.start_time.split(':').map(Number);
                    tournamentStartTime.setHours(tHour, tMinute, tSecond);

                    if (tournamentStartTime < now) {
                        return normalResponse("Tournament Play Time Exceed You Can't Play Now.", HTTP_NOT_ACCEPTABLE);
                    }
                }

                const upcomingRoundData = tournamentRound.toJSON();
                // console.log(upcomingRoundData.start_time)
                const upcomingRoundTime = new Date(upcomingRoundData.start_date);
                const [hour, minute, second] = upcomingRoundData.start_time.split(':').map(Number);
                upcomingRoundTime.setHours(hour, (minute - 5), second);

                if (upcomingRoundTime > now) {
                    return normalResponse("Please Wait Time Raimining for Round", HTTP_TIME_REMAINING, { time: tournamentRound.start_time });
                }

                // const roundPayload = {
                //     status: 1
                // }
                // rummyTournamentService.updateTournamentRound(tournament_id, round, roundPayload);
            }

            const table = await rummyTournamentService.getTournamentAvailableTable(tournament_id);

            if (table) {
                const existsInTableUser = await rummyTournamentService.checkUserInTableUser(table.id, user_id);
                console.log("Check exists", tournament_id, user_id, round, existsInTableUser)
                if (existsInTableUser) {
                    const tableData = await rummyTournamentService.tableUsers(existsInTableUser.table_id);
                    await userService.update(user_id, { rummy_tournament_table_id: existsInTableUser.table_id });
                    if (Array.isArray(tableData) && tableData.length > 0) {
                        return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData });
                    }
                }
            }

            const checkRoundPlayed = await rummyTournamentService.getTournamentRoundPlayed(tournament_id, user_id, round);
            if (checkRoundPlayed) {
                const userRound = checkRoundPlayed.toJSON();
                if (userRound.winner_id == 0) {
                    await userService.update(user_id, { rummy_tournament_table_id: userRound.table_id });
                    const tableData = await rummyTournamentService.tableUsers(user.rummy_tournament_table_id);
                    if (Array.isArray(tableData) && tableData.length > 0) {
                        return normalResponse("You Are Already On Table", HTTP_OK, { table_data: tableData });
                    }
                } else {
                    const havePendingRounds = await rummyTournamentService.havePendingTournamentTable(table.tournament_id, table.round);
                    if (havePendingRounds) {
                        return normalResponse("Please Wait Round is not completed", HTTP_NOT_ACCEPTABLE);
                    }
                }
            }

            let tableId = "";
            let seatPosition = 1;
            if (table) {
                tableId = table.id;
                seatPosition = await rummyTournamentService.getAvailableSeatPosition(tableId)
                if (!seatPosition) {
                    seatPosition = 1;
                }
                /*const joinUserPayload = {
                    user_id,
                    tournament_id,
                    table_id: tableId,
                    seat_position: seatPosition
                }
                await rummyTournamentService.joinUserToTable(joinUserPayload);*/
            } else {
                const tableData = {
                    tournament_id,
                    round
                }
                const rummyTable = await rummyTournamentService.createTable(tableData);
                tableId = rummyTable.id;
            }

            const tableUserData = {
                tournament_id,
                table_id: tableId,
                user_id: user.id,
                seat_position: seatPosition,
                round
            }

            await rummyTournamentService.addTableUser(tableUserData);

            const tableUsers = await rummyTournamentService.tableUsers(tableId);

            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async startGame(data) {
        try {
            const { user_id, tournament_id } = data;
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            const rummyTableId = user.rummy_tournament_table_id;
            if (!rummyTableId) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }
            // const userParticipated = await rummyTournamentService.getSingleParticipatedUser(user_id, tournament_id);
            // if (!userParticipated) {
            //     return normalResponse("User Not Participated This Tournament", HTTP_NOT_ACCEPTABLE);
            // }

            const table = await rummyTournamentService.getTable(rummyTableId);

            const tournamentRound = await rummyTournamentService.getPendingTournamentRound(tournament_id, ["round", "status", "start_date", "start_time"]);
            if (tournamentRound) {
                const roundPayload = {
                    status: 1
                }
                rummyTournamentService.updateTournamentRound(tournament_id, tournamentRound.round, roundPayload);
            }

            let tableUsers = await rummyTournamentService.tableUsers(rummyTableId);

            const game = await rummyTournamentService.getActiveGameOnTable(rummyTableId, ["id"]);

            if (game) {
                return normalResponse("Active Game is Going On", HTTP_NOT_ACCEPTABLE);
            }

            if (Array.isArray(tableUsers) && tableUsers.length < 0) {
                return normalResponse("Minimum 2 Players Required to Start the Game", HTTP_NOT_ACCEPTABLE);
            }

            const cardLimit = (tableUsers.length * RUMMY_CARDS) + 2;
            const cards = await rummyTournamentService.getStartCards(cardLimit);
            const firstElement = cards[0].toJSON();
            const joker = firstElement.cards;

            // Update Table
            const tableUserPayload = {
                game_points: 0
            }
            const tableHaveGames = await rummyTournamentService.getLatestGameOnTable(rummyTableId);
            if (!tableHaveGames) {
                tableUserPayload.total_points = tableUsers.length * MAX_POINTS
            }
            rummyTournamentService.updateTournamentTableUser(rummyTableId, tableUserPayload);


            const gameData = {
                table_id: user.rummy_tournament_table_id,
                tournament_id: table.tournament_id,
                joker
            }
            const newGame = await rummyTournamentService.create(gameData);

            const tablePayload = {
                deal_round: sequelize.literal(`deal_round + 1`)
            }
            if (!table.total_deal_round) {
                // if fresh game on table then update total deal
                tablePayload.total_deal_round = tableUsers.length;
                // const tableData = {
                //     total_deal_round: tableUsers.length
                // }
            }
            rummyTournamentService.updateTournamentTable(rummyTableId, tablePayload);

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

                    await rummyTournamentService.giveGameCards(tableUserData);
                }

                // rummyTournamentService.addGameCount(element.user_id);

                const gameLog = {
                    game_id: newGame.id,
                    user_id: element.user_id,
                    action: 0
                }

                rummyTournamentService.addGameLog(gameLog);

                if (index === 0) {
                    rummyTournamentService.updateSeatNumber(table.id, element.seat_position)
                }
            }

            const tableDropCardPayload = {
                game_id: newGame.id,
                user_id: 0,
                card: dropCard
            }

            await rummyTournamentService.startDropGameCards(tableDropCardPayload);
            rummyTournamentService.updateTournamentTableMaster(table.tournament_id, { is_completed: 2 })

            return normalResponse("Success", HTTP_OK, { game_id: newGame.id });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    // unused code
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
            if (user.rummy_tournament_table_id) {
                const responsePayload = {
                    table_data: [{ table_id: user.rummy_tournament_table_id }],
                    table_id: user.rummy_tournament_table_id
                }
                return normalResponse("You Are Already On Table", HTTP_NOT_ACCEPTABLE, responsePayload);
            }

            /*const userOnTable = await rummyTournamentService.checkUsersOnTable(table_id);

            if (!userOnTable) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }

            const table = await rummyTournamentService.getTable(table_id);

            if (!table) {
                return normalResponse("Invalid Table Id", HTTP_NOT_ACCEPTABLE);
            }
            const bootValue = table.boot_value;

            if (user.wallet < bootValue) {
                const message = 'Required Minimum ' + table.boot_value + ' Coins to Play';
                return normalResponse(message, HTTP_NOT_ACCEPTABLE);
            }

            const seatPosition = await rummyTournamentService.getAvailableSeatPosition(table_id)
            const tableUserData = {
                table_id,
                user_id: user.id,
                seat_position: seatPosition
            }

            await rummyTournamentService.addTableUser(tableUserData);*/

            const tableUsers = await rummyTournamentService.tableUsers(table_id);

            return normalResponse("Success", HTTP_OK, { table_data: tableUsers });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async leaveTable(data) {
        try {
            const { user_id, tournament_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            /*const tableId = user.rummy_tournament_table_id;

            const tableUserData = {
                table_id: tableId,
                user_id: user.id,
            }

            await rummyTournamentService.removeTableUser(tableUserData);

            const game = await rummyTournamentService.getActiveGameOnTable(tableId, ["id", "amount"]);

            if (game) {
                await rummyTournamentService.packGame(user_id, tableId, game.id);
            }*/

            return normalResponse("Success", HTTP_OK);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
        }
    }

    async packGame(data) {
        try {
            const { user_id, tournament_id } = data;
            if (!user_id) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            // const table = await rummyTournamentService.getTable(tableId);

            const chaalCount = await rummyTournamentService.chaalCount(game.id, user_id);

            const percent = chaalCount > 0 ? CHAAL_PERCENT : NO_CHAAL_PERCENT;
            await rummyTournamentService.packGame(user_id, tableId, game.id, percent);

            const gameUsers = await rummyTournamentService.gameUsers(game.id);
            if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                const winnerUser = gameUsers[0].toJSON();
                await rummyTournamentService.declareWinner(tableId, game.id, winnerUser.user_id);
                await this.finishGame(tableId)
                /*const winnerData = {
                    points: 0,
                    table_id: tableId,
                    user_id: winnerUser.user_id,
                    game_id: game.id,
                    json: ''
                }
                await rummyTournamentService.declare(winnerData);*/

                /*const allTableUsers = await rummyTournamentService.tableUsers(tableId);
                if (Array.isArray(allTableUsers) && allTableUsers.length >= 2) {
                    await this.makeWinner(allTableUsers, tableId);
                }*/
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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyTournamentService.getMyCards(game.id, user_id);
            return normalResponse("Success", HTTP_OK, { cards, gaie_id: game.id });

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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            let joker = "";
            if (game) {
                joker = game.joker;
            }

            let cardValue = rummyTournamentService.cardValue("", card_1, card_2, card_3, card_4, card_5, card_6);
            if (cardValue) {
                if (cardValue[0] == 0) {
                    cardValue = rummyTournamentService.cardValue(joker, card_1, card_2, card_3, card_4, card_5, card_6);
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
            if (!user_id || !card) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE);
            }
            const user = await userService.getById(user_id);
            if (!user) {
                return normalResponse("Invalid User", HTTP_NOT_ACCEPTABLE);
            }
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyTournamentService.getMyCards(game.id, user_id);

            if (Array.isArray(cards) && cards.length <= RUMMY_CARDS) {
                return normalResponse("Please Get Or Pick Card First And Then Drop One", HTTP_NOT_ACCEPTABLE);
            }

            const isCardAvailable = await rummyTournamentService.getMyCard(game.id, user_id, card);

            if (isCardAvailable) {
                if (card == 'JKR1' || card == 'JKR2') {
                    return normalResponse("You Can\'t Drop Joker Card", HTTP_NOT_ACCEPTABLE);
                }
                const tableUserData = {
                    game_id: game.id,
                    user_id,
                    card
                }

                await rummyTournamentService.dropGameCard(tableUserData, json);

                /*const gameUsers = await rummyTournamentService.gameAllUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 2) {
                    const bots = await rummyTournamentService.getGameBot(game.id);
                    if (Array.isArray(bots) && bots.length > 0) {
                        responsePayload.bot = 1
                    }
                }*/

                return normalResponse("Success", HTTP_OK);
            }

            return normalResponse("Invalid Card", HTTP_OK);

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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyTournamentService.getMyCards(game.id, user_id);
            if (Array.isArray(cards) && cards.length > RUMMY_CARDS) {
                return normalResponse("Please Drop Card And Then Pick One", HTTP_NOT_ACCEPTABLE);
            }

            const randomCard = await rummyTournamentService.getRamdomGameCard(game.id);
            if (randomCard) {
                const tableUserData = {
                    game_id: game.id,
                    user_id,
                    card: randomCard.cards,
                    isDeleted: 0
                }

                await rummyTournamentService.giveGameCards(tableUserData);
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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyTournamentService.getMyCards(game.id, user_id);
            if (Array.isArray(cards) && cards.length > RUMMY_CARDS) {
                return normalResponse("Please Drop Card And Then Pick One", HTTP_NOT_ACCEPTABLE);
            }

            const dropCard = await rummyTournamentService.getAndDeleteGameDropCard(game.id);
            if (dropCard) {
                const tableUserData = {
                    game_id: game.id,
                    user_id,
                    card: dropCard.card,
                    is_drop_card: 1,
                    isDeleted: 0
                }

                await rummyTournamentService.giveGameCards(tableUserData);

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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const cards = await rummyTournamentService.getMyCards(game.id, user_id);
            if (Array.isArray(cards) && cards.length > RUMMY_CARDS) {
                return normalResponse("Please Drop Card And Then Declare", HTTP_NOT_ACCEPTABLE);
            }

            const gameLog = await rummyTournamentService.gameLog(game.id, 1);
            const log = gameLog[0].toJSON();
            if (log.action == 3) {
                return normalResponse("Already Declare", HTTP_NOT_ACCEPTABLE);
            }

            const jsonArray = JSON.parse(json);
            let points = 0;

            for (let index = 0; index < jsonArray.length; index++) {
                const element = jsonArray[index];
                if (element.card_group == 0) {
                    points += cardPoints(element.cards, game.joker)
                }
            }

            if (points > 0) {
                return normalResponse("Wrong Declare", HTTP_NOT_ACCEPTABLE);
            }

            // points = (points > 80) ? 80 : points;

            const declareData = {
                user_id,
                game_id: game.id,
                table_id: tableId,
                points: 0,
                json
            }

            await rummyTournamentService.declare(declareData);

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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const game = await rummyTournamentService.getActiveGameOnTable(tableId);

            if (!game) {
                return normalResponse("Game Not Started", HTTP_NOT_ACCEPTABLE);
            }

            const gameLog = await rummyTournamentService.gameLog(game.id, 1);

            const remainingGameUsers = await rummyTournamentService.gameUsers(game.id);

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

            // const table = await rummyTournamentService.getTable(tableId);
            // const actualPoints = points * getRoundNumber((table.boot_value / 80), 2);

            const declareData = {
                user_id,
                game_id: game.id,
                points,
                table_id: tableId,
                json
            }
            await rummyTournamentService.declare(declareData);


            // get declrae count for the game
            const declareLog = await rummyTournamentService.gameLog(game.id, '', 3);
            const declareCount = declareLog.length;

            if (remainingGameUsers.length <= declareCount) {
                // const game = await rummyTournamentService.getActiveGameOnTable(tableId);
                const winnerLog = declareLog[declareCount - 1].toJSON();
                const winnerId = winnerLog.user_id;
                // const totalAmount = await rummyTournamentService.totalAmountOnTable(tableId);
                // const adminComissionAmount = await getAmountByPercentage(totalAmount, comission);
                // const userWinningAmount = getRoundNumber(totalAmount - adminComissionAmount, 2);
                await rummyTournamentService.declareWinner(tableId, game.id, winnerId);
                await this.finishGame(tableId)

                // const tableUsers = await rummyTournamentService.tableUsers(tableId);
                // if (Array.isArray(tableUsers) && tableUsers.length >= 2) {
                //     await this.makeWinner(tableUsers, tableId);
                // }

                // await rummyTournamentService.updateTotalWinningAmtTable(totalAmount, userWinningAmount, adminComissionAmount, tableId, singleUser.user_id);
                // await userWallet.addToWallet(userWinningAmount, singleUser.user_id);
                // userWallet.statementLog(singleUser.user_id, GAMES.poolRummy, userWinningAmount, tableId, 0, adminComissionAmount);
                // await userWallet.minusUserWallet(user_id, actualPoints, GAMES.pointRummy);
                // const setting = await adminService.setting(["admin_commission"]);
                // const comission = setting.admin_commission;
                // this.makeWinner(game.id, game.amount, declareLog[declareCount - 1], comission);
            }

            return normalResponse("Success", HTTP_OK, { winner: 0 });

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async finishGame(tableId) {
        try {
            const table = await rummyTournamentService.getTournamentInfoByTable(tableId);
            if (table.total_deal_round == table.deal_round) {
                const tableRound = await rummyTournamentService.getTournamentRound(table.tournament_id, table.round, ["winner_user_count"]);
                const maxRoundWinners = tableRound.winner_user_count;
                const tableUsers = await rummyTournamentService.tableUsersByPoints(tableId);
                for (let index = 0; index < tableUsers.length; index++) {
                    const element = tableUsers[index];
                    const partipientsPayload = {};
                    partipientsPayload.round = table.round;
                    if (index < maxRoundWinners) {
                        partipientsPayload.total_points = element.total_points;
                    } else {
                        partipientsPayload.total_points = element.total_points;
                        partipientsPayload.can_tournament_play = 0;
                    }

                    await rummyTournamentService.updateTournamentParticipient(table.tournament_id, element.user_id, partipientsPayload);
                }

                // No need for this because there will be no single winner
                await rummyTournamentService.updateTournamentTable(tableId, { winner_id: tableUsers[0].user_id });

                const nextRound = await rummyTournamentService.getTournamentRound(table.tournament_id, (table.round + 1), ["start_date", "start_time"]);
                if (nextRound) {
                    await rummyTournamentService.updateTournamentTableMaster(table.tournament_id, { next_round_start_date: nextRound.start_date, next_round_start_time: nextRound.start_time });
                }

                const roundPayload = {
                    status: 2
                }
                // if all deal completed then update round to completed
                await rummyTournamentService.updateTournamentRound(table.tournament_id, table.round, roundPayload);
                // Update Table to 0 After all deal finish
                // await userService.updateByConditions({ rummy_tournament_table_id: tableId }, { rummy_tournament_table_id: 0 })

                // if total round completed
                const havePendingRounds = await rummyTournamentService.havePendingTournamentTable(table.tournament_id, table.round);
                if (table.total_round == table.round && !havePendingRounds) {
                    await rummyTournamentService.updateTournamentTableMaster(table.tournament_id, { is_completed: 1 });
                    const totalPassCount = table.total_pass_count;
                    /*if(totalPassCount > 0) {
                        const topWinners = await rummyTournamentService.tournamentWinnersByPoints(table.tournament_id, totalPassCount);
                        if(Array.isArray(topWinners) && topWinners.length > 0) {
                            for (let ind = 0; ind < topWinners.length; ind++) {
                                const element = topWinners[ind];
                                const passWinnerPayload = {
                                    user_id: element.user_id,
                                    tournament_id: element.tournament_id,
                                    round: element.round
                                }
                                await rummyTournamentService.createPassWinner(passWinnerPayload)
                            }
                        }
                    }*/
                    this.distributePrizes(table.tournament_id, totalPassCount)
                }
            }
        } catch (error) {
            errorHandler.handle(error);
        }
    }

    async distributePrizes(tournamentId, totalPassCount) {
        try {
            // get All prizes
            const prizes = await rummyTournamentService.getTournamentPrizes(tournamentId);
            // max price count
            const totalWinners = prizes[prizes.length - 1].to_position;
            // get winners by highest points
            const winners = await rummyTournamentService.tournamentWinnersByPoints(tournamentId, totalWinners);
            for (let index = 0; index < prizes.length; index++) {
                const price = prizes[index];
                let from_position = price.from_position;
                while (from_position <= price.to_position) {
                    const winnerUser = winners[from_position - 1];
                    // Distribute price to winner from here
                    if (price.given_in_round == winnerUser.round) {
                        const pricePayload = {
                            user_id: winnerUser.user_id,
                            amount: price.winning_price,
                            position: from_position,
                            tournament_id: tournamentId,
                            round: winnerUser.round,
                            total_points: winnerUser.total_points
                        }
                        if (totalPassCount && totalPassCount >= from_position) {
                            pricePayload.is_ticket_win = 1;
                        }
                        await rummyTournamentService.distributePriceToWinner(pricePayload);
                        await userWallet.plusUserWallet(winnerUser.user_id, tournamentId, price.winning_price, 0, GAMES.tournamentRummy, winnerUser);
                    }
                    from_position++
                }
            }

            const tournamentCollectedPrice = await rummyTournamentService.getTotalTournamentAmount(tournamentId);
            const tournamentDistributedPrice = await rummyTournamentService.getTotalTournamentDistributedAmount(tournamentId);

            const adminComission = tournamentCollectedPrice - tournamentDistributedPrice;
            if (adminComission != 0) {
                userWallet.directAdminProfitStatement(GAMES.tournamentRummy, adminComission, tournamentId);
            }
        } catch (error) {
            errorHandler.handle(error)
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
            if (!user.rummy_tournament_table_id) {
                return normalResponse("You Are Not On Table", HTTP_NOT_ACCEPTABLE);
            }

            const tableId = user.rummy_tournament_table_id;
            const responseData = {};
            const tableData = await rummyTournamentService.tableUsers(tableId);

            const table = await rummyTournamentService.getTable(tableId);
            const activeGame = await rummyTournamentService.getActiveGameOnTable(tableId);

            let score = [];
            let winPoints = {};
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
                        total_points: 0,
                        wallet: 0,
                        invested: 0
                    };
                }

                for (let j = 0; j < tableData.length; j++) {
                    const element = tableData[j].toJSON();
                    const seat = element.seat_position - 1;
                    tableNewData[seat] = element;
                }

                responseData.table_users = tableNewData;
                responseData.table_detail = table;
                responseData.active_game_id = 0;
                responseData.game_status = 0;
                // responseData.table_amount = table.boot_value;

                if (activeGame) {
                    responseData.active_game_id = activeGame.id;
                    responseData.game_status = 1;
                }

                const points = await rummyTournamentService.getTablePoints(tableId);
                let pointsArray = [];
                /*for (let index = 0; index < points.length; index++) {
                    const element = points[index];
                    if (index % 3 != 0) {
                        pointsArray.push(element);
                        winPoints[element.user_id] = element.points;
                    }
                    score[element.user_id] = element.points;
                }*/
                responseData.points = points;
            }

            const gameId = game_id ? game_id : responseData.active_game_id;
            if (!gameId) {
                return normalResponse("Invalid Parameter", HTTP_NOT_ACCEPTABLE, responseData);
            }

            const game = await rummyTournamentService.getById(gameId);

            if (!game) {
                return normalResponse("Invalid Game", HTTP_NOT_ACCEPTABLE, responseData);
            }

            const gameLog = await rummyTournamentService.gameLog(gameId, 1);

            const gameUsers = await rummyTournamentService.gameAllUsers(gameId);

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

            const gameUser = await rummyTournamentService.gameOnlyUsers(game.id);
            responseData.game_users = gameUser;
            responseData.card_count = await rummyTournamentService.getGameTableCardCount(game.id);
            responseData.chaal = chaal;
            responseData.game_amount = game.amount;
            const lastCard = await rummyTournamentService.lastGameCard(game.id);
            const discardedCard = await rummyTournamentService.discardedGameCard(game.id);
            responseData.last_card = lastCard;
            responseData.discarded_card = discardedCard;
            const masterTable = await rummyTournamentService.getTableMaster(table.tournament_id);
            responseData.total_round = masterTable.total_round;

            if (user_id) {
                if (activeGame) {
                    const gameDropCard = await rummyTournamentService.getGameDropCard(game.id)
                    responseData.drop_card = [gameDropCard];
                } else {
                    responseData.drop_card = [];
                }
                responseData.joker = game.joker
            }
            responseData.message = "Success";
            responseData.table_winner_id = table.winner_id;
            if (game.winner_id > 0) {
                responseData.chaal = 0;
                responseData.message = "Game Completed";
                const gameUsersCards = [];
                for (let index = 0; index < gameUser.length; index++) {
                    const element = gameUser[index].toJSON();

                    // Fetch the declare log for the user
                    // const declareLog = await rummyTournamentService.gameLog(game.id, 1, '', element.user_id);
                    // const log = declareLog[0].toJSON();

                    // Fetch the user's game log JSON and parse it
                    const gameLogJson = await rummyTournamentService.gameLogJson(game.id, element.user_id);
                    gameUsersCards[index] = {};
                    const tableUserDetails = await rummyTournamentService.getTotalPoints(tableId, element.user_id);
                    gameUsersCards[index]['user'] = {
                        ...element,
                        win: game.winner_id == element.user_id ? tableUserDetails?.game_points : (tableUserDetails?.game_points * -1),
                        /*winPoints[element.user_id] !== undefined && winPoints[element.user_id]
                            ? winPoints[element.user_id] : 0,*/
                        //(game.winner_id === element.user_id) ? game.winner_id : 0,
                        total: tableUserDetails?.total_points || 0,
                        points: tableUserDetails?.game_points || 0,
                        // score: winPoints[element.user_id] !== undefined && winPoints[element.user_id] < 0
                        // ? Math.abs(winPoints[element.user_id]) : 0, //score[element.user_id] !== undefined ? score[element.user_id] : 0,
                        cards: (gameLogJson && gameLogJson.json) ? JSON.parse(gameLogJson.json) : []
                    };
                }

                responseData.game_users_cards = gameUsersCards;
                responseData.game_status = 2;

                const updatedDateTimestamp = new Date(game.updated_date).getTime() / 1000;
                const currentTime = Math.floor(Date.now() / 1000);
                const startTime = (updatedDateTimestamp + 15) - currentTime;

                responseData.game_start_time = startTime;
                responseData.winner_user_id = game.winner_id;
                let is_round_completed = 0;
                if (table.total_deal_round == table.deal_round) {
                    responseData.message = "Round Completed";
                    is_round_completed = 1;
                    const tableRound = await rummyTournamentService.getTournamentRound(table.tournament_id, (table.round + 1), ["start_date", "start_time"]);
                    if (tableRound) {
                        const tournementStartTimeFormat = new Date(tableRound.start_date);
                        const [fhour, fminute, fsecond] = tableRound.start_time.split(':').map(Number);
                        tournementStartTimeFormat.setHours(fhour, fminute, fsecond);
                        const nextRoundStartTime = dateHelper.timeToAmPM(tournementStartTimeFormat);
                        responseData.next_round_start_time = dateHelper.formatDateShort(tournementStartTimeFormat) + " " + nextRoundStartTime;
                    }
                }
                responseData.is_round_completed = is_round_completed;
                responseData.is_tournament_completed = masterTable.is_completed;
                if (masterTable.is_completed == 1) {
                    responseData.message = "Tournament Completed";
                }
            }

            return normalResponse("Success", HTTP_OK, responseData);

        } catch (error) {
            console.log(error)
            return normalResponse("Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            // return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async rummyAutoChaal(tableId) {
        const game = await rummyTournamentService.getActiveGameOnTable(tableId);
        if (game) {
            let chaal = 0;
            const gameLog = await rummyTournamentService.gameLog(game.id, 1);
            const gameUsers = await rummyTournamentService.gameAllUsers(game.id);
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

            const timeoutLog = await rummyTournamentService.gameLog(game.id, '', 2, chaal, 1);
            if (Array.isArray(timeoutLog) && timeoutLog.length < 2) {
                const cards = await rummyTournamentService.getMyCards(game.id, chaal);
                if (Array.isArray(cards) && cards.length <= RUMMY_CARDS) {
                    const randomCard = await rummyTournamentService.getRamdomGameCard(game.id);
                    if (randomCard) {
                        const tableUserData = {
                            game_id: game.id,
                            user_id: chaal,
                            card: randomCard.cards,
                            isDeleted: 0
                        }

                        await rummyTournamentService.giveGameCards(tableUserData);
                    }
                }

                const userCard = await rummyTournamentService.gameUserCard(game.id, chaal);
                if (userCard) {
                    const jsonArray = await rummyTournamentService.gameLog(game.id, 1, 2, chaal);
                    let json = "";
                    if (Array.isArray(jsonArray) && jsonArray.length > 0) {
                        const singleJSON = jsonArray[0].toJSON();
                        json = singleJSON.json;
                    }

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
                    // const timeout = userType == 0 ? 1 : 0;
                    await rummyTournamentService.dropGameCard(tableUserData, json, 1);
                }
            } else {
                await rummyTournamentService.packGame(chaal, game.id, 1);
                const gameUsers = await rummyTournamentService.gameUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                    const winnerUser = gameUsers[0].toJSON();
                    await rummyTournamentService.declareWinner(tableId, game.id, winnerUser.user_id);
                    await this.finishGame(tableId)
                    /*await rummyTournamentService.declareWinner(game.id, winnerUser.user_id);

                    const setting = await adminService.setting(["admin_commission"]);
                    const comission = setting.admin_commission;
                    const totalAmount = await rummyTournamentService.totalAmountOnTable(tableId);
                    const adminComissionAmount = await getAmountByPercentage(totalAmount, comission);
                    const userWinningAmount = getRoundNumber(totalAmount - adminComissionAmount, 2);

                    await rummyTournamentService.declareWinner(game.id, winnerUser.user_id);
                    await rummyTournamentService.updateTotalWinningAmtTable(totalAmount, userWinningAmount, adminComissionAmount, tableId, winnerUser.user_id);
                    await userWallet.addToWallet(userWinningAmount, winnerUser.user_id);*/
                }

                /*const tableUserData = {
                    table_id: tableId,
                    user_id: chaal
                }

                await rummyTournamentService.removeTableUser(tableUserData);*/
            }
        }
        return game ? "Running" : "Stop";
    }

    async rummyAutoChaal1(tableId) {
        const game = await rummyTournamentService.getActiveGameOnTable(tableId);
        if (game) {
            let chaal = 0;
            // let userType = 0;
            const gameLog = await rummyTournamentService.gameLog(game.id, 1);
            const gameUsers = await rummyTournamentService.gameAllUsers(game.id);
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

            /* if (userType == 1) {
                 const botChaal = await rummyTournamentService.chaalCount(game.id, chaal);
                 const randomNumber = getRandomNumber(8, 10)
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
 
                     await rummyTournamentService.declare(declareData)
                     return;
                 }
             }
 
             if (log.action == 3) {
                 const activeGameUser = await rummyTournamentService.gameUsers(game.id);
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
                         jsonArray = await rummyTournamentService.gameLog(game.id, 1, 2, element.user_id);
                     }
 
                     const alreadyDeclare = await rummyTournamentService.gameLog(game.id, 1, 3, element.user_id);
 
                     if (Array.isArray(alreadyDeclare) && alreadyDeclare.length == 0) {
                         const json = '[]';
                         const points = 80;
 
                         const table = await rummyTournamentService.getTable(tableId);
                         const actual_points = points * getRoundNumber((table.boot_value / 80), 2);
 
                         const data_log = {
                             user_id: element.user_id,
                             game_id: game.id,
                             table_id: tableId,
                             points: points,
                             actual_points,
                             json: json
                         };
 
                         await rummyTournamentService.declare(data_log);
                     }
 
                     const declare_log = await rummyTournamentService.gameLog(game.id, '', 3);
                     const declare_count = declare_log.length;
 
                     if (activeGameUser.length <= declare_count) {
                         const active_game = await rummyTournamentService.getActiveGameOnTable(tableId);
                         if (active_game) {
                             const setting = await adminService.setting(["admin_commission"]);
                             const commission = setting.admin_commission;
                             await this.makeWinner(active_game.id, active_game.amount, declare_log[declare_count - 1].user_id, commission);
                         }
                     }
                 }
                 return
             }*/

            const timeoutLog = await rummyTournamentService.gameLog(game.id, '', 2, chaal, 1);
            if (Array.isArray(timeoutLog) && timeoutLog.length < 2) {
                const cards = await rummyTournamentService.getMyCards(game.id, chaal);
                if (Array.isArray(cards) && cards.length <= RUMMY_CARDS) {
                    const randomCard = await rummyTournamentService.getRamdomGameCard(game.id);
                    if (randomCard) {
                        const tableUserData = {
                            game_id: game.id,
                            user_id: chaal,
                            card: randomCard.cards,
                            isDeleted: 0
                        }

                        await rummyTournamentService.giveGameCards(tableUserData);
                    }
                }

                const userCard = await rummyTournamentService.gameUserCard(game.id, chaal);
                if (userCard) {
                    const jsonArray = await rummyTournamentService.gameLog(game.id, 1, 2, chaal);
                    let json = "";
                    if (Array.isArray(jsonArray) && jsonArray.length > 0) {
                        const singleJSON = jsonArray[0].toJSON();
                        json = singleJSON.json
                    }
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
                    // const timeout = userType == 0 ? 1 : 0;
                    await rummyTournamentService.dropGameCard(tableUserData, json, 1);
                }
            } else {
                const percent = CHAAL_PERCENT;
                await rummyTournamentService.packGame(chaal, tableId, game.id, percent);
                const gameUsers = await rummyTournamentService.gameUsers(game.id);
                if (Array.isArray(gameUsers) && gameUsers.length == 1) {
                    const winnerData = {
                        points: 0,
                        table_id: tableId,
                        user_id: winnerUser.user_id,
                        game_id: game.id,
                        json: ''
                    };
                    await rummyTournamentService.declare(winnerData);
                    const winnerUser = gameUsers[0].toJSON();
                    await rummyTournamentService.declareWinner(game.id, winnerUser.user_id);
                    await this.finishGame(tableId);
                    // const allTableUsers = await rummyTournamentService.tableUsers(tableId);
                    // if (Array.isArray(allTableUsers) && allTableUsers.length >= 2) {
                    //     this.makeWinner(allTableUsers, tableId);
                    // }
                }

                /*const tableUserData = {
                    table_id: tableId,
                    user_id: chaal
                }

                await rummyTournamentService.removeTableUser(tableUserData);*/
            }
        }
        return game ? "Running" : "Stop";
    }




    // APIS
    async getTournaments(req, res) {
        try {
            const { user_id, tournament_type_id } = req.body;
            const user = req.user;
            const tournaments = await rummyTournamentService.getUpcomingTournaments(user_id, tournament_type_id);
            const finalArray = [];
            /*const tournamentsArray = await tournaments.map(async (item) => {
                const record = item.toJSON();
                record.is_full = 0;
                if (record.total_participants >= record.max_player) {
                    record.is_full = 1;
                }

                const tournementRegTime = new Date(record.registration_start_date);
                const [hours, minutes, seconds] = record.registration_start_time.split(':').map(Number);
                tournementRegTime.setHours(hours, minutes, seconds);
                const now = new Date();

                record.can_register = 0;
                if (tournementRegTime >= now) {
                    record.can_register = 1
                }

                const tournementStartTime = new Date(record.start_date);
                const [hour, minute, second] = record.start_time.split(':').map(Number);
                tournementStartTime.setHours(hour, minute, second);

                record.tournament_status = 0;
                if (tournementStartTime >= now && record.participationStatus == 1) {
                    record.tournament_status = 1;
                } else if (tournementStartTime <= now && record.participationStatus == 1) {
                    record.tournament_status = 2;
                } else if (tournementStartTime <= now && record.participationStatus == 0) {
                    record.tournament_status = 3;
                }

                const upcomingRound = await rummyTournamentService.getPendingTournamentRound(record.id, ["round", "status", "start_date", "start_time"]);
                if(upcomingRound) {
                    const upcomingRoundData = upcomingRound.toJSON();
                    // console.log(upcomingRoundData.start_time)
                    const upcomingRoundTime = new Date(upcomingRoundData.start_date);
                    const [hour, minute, second] = upcomingRoundData.start_time.split(':').map(Number);
                    upcomingRoundTime.setHours(hour, minute, second);

                    const diffInMs = upcomingRoundTime.getTime() - now.getTime();
                    const diffInSeconds = diffInMs / 1000;
                    record.upcoming_round_time = diffInSeconds;
                    record.upcoming_round = upcomingRoundData.round;
                }
                // if (tournementStartTime >= now) {
                //     record.tournament_status = 1
                // }
                console.log("ajajaja")
                finalArray.push(record)
                return record;
            })*/

            for (let index = 0; index < tournaments.length; index++) {
                const record = tournaments[index].toJSON();
                record.is_full = 0;
                if (record.total_participants >= record.max_player) {
                    record.is_full = 1;
                }

                const tournementRegTime = new Date(record.registration_start_date);
                const [hours, minutes, seconds] = record.registration_start_time.split(':').map(Number);
                tournementRegTime.setHours(hours, minutes, seconds);
                const now = new Date();

                record.can_register = 0;
                if (tournementRegTime >= now) {
                    record.can_register = 1
                }

                const tournementStartTime = new Date(record.start_date);
                const [hour, minute, second] = record.start_time.split(':').map(Number);
                tournementStartTime.setHours(hour, (minute - 5), second);

                // formatted start time
                const tournementStartTimeFormat = new Date(record.start_date);
                const [fhour, fminute, fsecond] = record.start_time.split(':').map(Number);
                tournementStartTimeFormat.setHours(fhour, fminute, fsecond);

                record.tournament_status = 0;
                // const currentTimeBefore5Minute = new Date();
                // currentTimeBefore5Minute.setMinutes(currentTime.getMinutes() - 5);

                // 0=Join,1=Already Joined,2=Time Pass Take seat, 3=Not Taken, 4=Time gone but played take seat again, 5=time gone
                if (tournementStartTime >= now && record.participationStatus == 1) {
                    record.tournament_status = 1;
                } else if (tournementStartTime <= now && record.participationStatus == 1 && tournementStartTimeFormat >= now) {
                    record.tournament_status = 2;
                } else if (tournementStartTime <= now && record.participationStatus == 0) {
                    record.tournament_status = 3;
                } else if (user.rummy_tournament_table_id && now > tournementStartTimeFormat) {
                    record.tournament_status = 4;
                }
                else if (record.participationStatus == 1 && record.can_tournament_play == 1) {
                    record.tournament_status = 4;
                } else if (now > tournementStartTimeFormat) {
                    record.tournament_status = 5;
                }

                /*const upcomingRound = await rummyTournamentService.getUpcomingTournamentRound(record.id, ["round", "status", "start_date", "start_time"]);
                if (upcomingRound) {
                    const upcomingRoundData = upcomingRound.toJSON();
                    // console.log(upcomingRoundData.start_time)
                    const upcomingRoundTime = new Date(upcomingRoundData.start_date);
                    const [hour, minute, second] = upcomingRoundData.start_time.split(':').map(Number);
                    upcomingRoundTime.setHours(hour, minute, second);

                    const diffInMs = upcomingRoundTime.getTime() - now.getTime();
                    const diffInSeconds = diffInMs / 1000;
                    record.upcoming_round_time = parseInt(diffInSeconds);
                    record.upcoming_round = upcomingRoundData.round;
                }*/

                const currentRound = await rummyTournamentService.getTournamentNextRound(record.id, ["round", "status"]);
                record.current_round = 0;
                if (currentRound) {
                    record.current_round = currentRound.round;
                    if (currentRound.round > 1) {
                        const upcomingRound = await rummyTournamentService.getUpcomingTournamentRound(record.id, ["round", "status", "start_date", "start_time"]);
                        if (upcomingRound) {
                            const upcomingRoundData = upcomingRound.toJSON();
                            // console.log(upcomingRoundData.start_time)
                            const upcomingRoundTime = new Date(upcomingRoundData.start_date);
                            const [hour, minute, second] = upcomingRoundData.start_time.split(':').map(Number);
                            upcomingRoundTime.setHours(hour, minute, second);

                            const diffInMs = upcomingRoundTime.getTime() - now.getTime();
                            const diffInSeconds = diffInMs / 1000;
                            record.upcoming_round_time = parseInt(diffInSeconds);
                            record.upcoming_round = upcomingRoundData.round;
                        }
                    }
                }

                record.registration_start_time_format = dateHelper.timeToAmPM(tournementRegTime);

                record.start_time_format = dateHelper.timeToAmPM(tournementStartTimeFormat);

                record.registration_start_date_format = dateHelper.formatDateShort(tournementRegTime);
                record.start_date_format = dateHelper.formatDateShort(tournementStartTimeFormat);

                const tournementStartTimeTimer = new Date(record.start_date);
                const [thour, tminute, tsecond] = record.start_time.split(':').map(Number);
                tournementStartTimeTimer.setHours(thour, (tminute), tsecond);

                const startTimeInMs = tournementStartTimeTimer.getTime() - now.getTime();
                const startTimeDiffInSeconds = startTimeInMs / 1000;

                record.start_time_in_second = "";
                if (startTimeDiffInSeconds <= 3600 && startTimeDiffInSeconds > 0) {
                    record.start_time_in_second = parseInt(startTimeDiffInSeconds);
                }

                // let isTakeSeat = 0;
                // if(user.rummy_tournament_table_id && now > tournementStartTimeFormat) {
                //     isTakeSeat = 1
                // }else if()
                // record.is_take_seat = 0;
                // if (tournementStartTime >= now) {
                //     record.tournament_status = 1
                // }
                finalArray.push(record)
            }
            return successResponse(res, { tournaments: finalArray });
        } catch (error) {
            errorHandler.handle(error);
        }
    }

    async participate(req, res) {
        // while partipatiate time is not checked
        try {
            const { user_id, tournament_id } = req.body;
            const user = req.user;
            if (!user_id || !tournament_id) {
                return errorResponse(res, "Invalid Parameters", HTTP_NOT_ACCEPTABLE);
            }
            const tournament = await rummyTournamentService.getUpcomingTournementById(tournament_id, user_id);
            if (!tournament) {
                return errorResponse(res, "Tournament Not Available", HTTP_NOT_ACCEPTABLE);
            }
            const tournamentData = tournament.toJSON();
            if (tournamentData.is_completed == 1) {
                return errorResponse(res, "Tournament is Already Completed", HTTP_NOT_ACCEPTABLE);
            }
            if (tournamentData.participationStatus == 1) {
                return errorResponse(res, "Already Participated This Tournament", HTTP_NOT_ACCEPTABLE);
            }
            if (tournamentData.total_participants >= tournamentData.max_player) {
                return errorResponse(res, "Seat Fulled Try Another Tournament", HTTP_NOT_ACCEPTABLE);
            }

            const tournementRegTime = new Date(tournamentData.registration_start_date);
            const [hours, minutes, seconds] = tournamentData.registration_start_time.split(':').map(Number);
            tournementRegTime.setHours(hours, minutes, seconds);
            const now = new Date();

            const tournementStartTime = new Date(tournamentData.start_date);
            const [shours, sminutes, sseconds] = tournamentData.start_time.split(':').map(Number);
            tournementStartTime.setHours(shours, (sminutes - 5), sseconds);

            const checkTournamentConflict = await rummyTournamentService.checkTournamentConflicts(tournament.id, user_id, tournementStartTime);
            if (checkTournamentConflict) {
                return errorResponse(res, "You have already partiapated tournament at same time range", HTTP_NOT_ACCEPTABLE);
            }

            if (tournementRegTime > now) {
                return errorResponse(res, "Registration Time not started Please wait..", HTTP_NOT_ACCEPTABLE);
            }
            if (tournementStartTime < now) {
                return errorResponse(res, "Registration Time Gone Please Try Another Tournament", HTTP_NOT_ACCEPTABLE);
            }
            const payload = {
                user_id,
                tournament_id
            }
            let userHavePass;
            let registrationFee = tournamentData.registration_fee;
            if (tournamentData.is_mega_tournament == 1) {
                userHavePass = await rummyTournamentService.userHaveTournamentPass(tournament_id, user_id);
                if (userHavePass) {
                    payload.join_type = 1;
                    registrationFee = 0;
                }
            }

            if (!userHavePass && user.wallet < tournamentData.registration_fee) {
                return errorResponse(res, "Insufficient Wallet Amount", HTTP_NOT_ACCEPTABLE);
            }
            // console.log(userHavePass);
            // return successResponse(res, {pass: userHavePass});
            payload.amount = registrationFee;
            const participent = await rummyTournamentService.participateTournament(payload);
            if (!userHavePass) {
                userWallet.minusUserWallet(user_id, tournamentData.registration_fee, GAMES.tournamentRummy, participent);
            }
            return successResponse(res);
        } catch (error) {
            errorHandler.handle(error);
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async info(req, res) {
        try {
            const { tournament_id } = req.body;
            if (!tournament_id) {
                return errorResponse(res, "Invalid Parameters", HTTP_NOT_ACCEPTABLE);
            }
            const info = await rummyTournamentService.getTournamentFullInfo(tournament_id);
            return successResponse(res, { tournament: info });
        } catch (error) {
            errorHandler.handle(error);
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async getTournementWinners(req, res) {
        try {
            const tournament_id = req.params.tournementId;
            const winners = await rummyTournamentService.getTournamentWinners(tournament_id);
            return successResponse(res, { winners: winners });
        } catch (error) {
            errorHandler.handle(error);
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async types(req, res) {
        try {
            const types = await rummyTournamentService.getTypes();
            return successResponse(res, { data: types });
        } catch (error) {
            errorHandler.handle(error);
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }
}

module.exports = new RummyTournamentController();