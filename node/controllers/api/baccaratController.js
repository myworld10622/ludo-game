const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, BACCARAT_TIME_FOR_START_NEW_GAME, BACCARAT_FOR_BET, BACCARAT_BETS, BACCARAT_WINNING_PRICE } = require('../../constants');
const adminService = require('../../services/adminService');
const baccaratService = require('../../services/baccaratService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class BaccaratController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await baccaratService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                baccarat_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await baccaratService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.baccarat, betData);
            const responseData = {
                bet_id: betData.id,
                wallet: user.wallet - amount
            }
            return successResponse(res, responseData);
        } catch (error) {
            console.log(error)
            errorHandler.handle(error)
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async getResult(req, res) {
        try {
            const { game_id, user_id } = req.body;
            const game = await baccaratService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await baccaratService.viewBet(user_id, game_id);
            let winAmount = 0;
            let betAmount = 0;
            if (Array.isArray(betData) && betData.length == 0) {
                const responsePayload = {
                    win_amount: winAmount,
                    bet_amount: betAmount,
                    diff_amount: winAmount - betAmount,
                    message: "No Bet",
                    code: HTTP_SWITCH_PROTOCOL
                }
                return successResponseWitDynamicCode(res, responsePayload);
            }
            for (let index = 0; index < betData.length; index++) {
                const element = betData[index];
                winAmount += element.user_amount;
                betAmount += element.amount;
            }

            const responsePayload = {
                win_amount: winAmount,
                bet_amount: betAmount,
                diff_amount: winAmount - betAmount
            }

            if (responsePayload.diff_amount > 0) {
                responsePayload.message = "You Win";
                responsePayload.code = HTTP_WIN;
            } else {
                responsePayload.message = "You Loss";
                responsePayload.code = HTTP_LOOSER;
            }
            return successResponseWitDynamicCode(res, responsePayload);
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async walletHistory(req, res) {
        try {
            const { user_id } = req.body;
            const walletHistory = await baccaratService.walletHistory(user_id);
            const setting = await adminService.setting(["min_redeem"]);
            const responsePayload = {
                GameLog: walletHistory,
                MinRedeem: setting.min_redeem,
            }
            return successResponse(res, responsePayload);
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async makeWinner(userId, betId, amount, comission, gameId) {
        try {
            const adminComissionAmount = await getAmountByPercentage(amount, comission);
            const userWinningAmount = getRoundNumber(amount - adminComissionAmount, 2);
            const gameBetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            baccaratService.updateBet(betId, gameBetPayload);

            // Update Game
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            baccaratService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await baccaratService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.baccarat, gameBet);
        } catch (error) {
            console.log(error);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// SOCKET FUNCTIONS /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    // Create Game From Socket
    async createGame() {
        try {
            const rooms = await baccaratService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await baccaratService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        baccaratService.create({ room_id: room.id });
                        console.log('Baccarat Game Created Successfully');
                        return;
                    }
                }
            } else {
                console.log('No Rooms Available');
            }
        } catch (error) {
            console.log(error);
        }
    }

    // Declare Winner From Socket
    async declareWinner() {
        try {
            const rooms = await baccaratService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await baccaratService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await baccaratService.viewBet('', gameId);
                        const { totalBetAmount } = await baccaratService.getBetDataByBets(bets);

                        const setting = await adminService.setting(["bacarate_random", "admin_commission"]);
                        const random = setting.bacarate_random;
                        // Logic for winning
                        const cards = await baccaratService.getCards(4);
                        let card1 = cards[0].cards;
                        let card2 = cards[1].cards;
                        let card3 = cards[2].cards;
                        let card4 = cards[3].cards;

                        let playerPoint = await baccaratService.cardValue(card1, card2);
                        let bankerPoint = await baccaratService.cardValue(card3, card4);
                        let winning = await baccaratService.getWinner(playerPoint, bankerPoint);
                        let multiply = 0;

                        switch (winning) {
                            case BACCARAT_BETS.player:
                                multiply = BACCARAT_WINNING_PRICE.player
                                break;

                            case BACCARAT_BETS.banker:
                                multiply = BACCARAT_WINNING_PRICE.banker
                                break;

                            case BACCARAT_BETS.tie:
                                multiply = BACCARAT_WINNING_PRICE.tie
                                break;

                            case BACCARAT_BETS.playerPair:
                                multiply = BACCARAT_WINNING_PRICE.playerPair
                                break;

                            case BACCARAT_BETS.bankerPair:
                                multiply = BACCARAT_WINNING_PRICE.bankerPair
                                break;

                            default:
                                break;
                        }

                        // Creating Mapping
                        await baccaratService.createMap(gameId, card1);
                        await baccaratService.createMap(gameId, card2);
                        await baccaratService.createMap(gameId, card3);
                        await baccaratService.createMap(gameId, card4);

                        // Give winning Amount to user
                        const winnerBets = await baccaratService.viewBet('', gameId, winning);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;

                                const amount = winnerBet.amount * multiply;
                                totalWinningAmount += amount;
                                this.makeWinner(userId, betId, amount, comission, gameId);
                            }

                        } else {
                            console.log("No Winning Bet Found");
                        }

                        const playerPair = await baccaratService.isPair(card1, card2);
                        const bankerPair = await baccaratService.isPair(card3, card4);

                        if (playerPair) {
                            const winnerBets = await baccaratService.viewBet('', gameId, BACCARAT_BETS.playerPair);
                            if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                                const comission = setting.admin_commission;
                                for (let j = 0; j < winnerBets.length; j++) {
                                    const winnerBet = winnerBets[j];
                                    const userId = winnerBet.user_id;
                                    const betId = winnerBet.id;

                                    const amount = winnerBet.amount * BACCARAT_WINNING_PRICE.playerPair;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }

                            }
                        }

                        if (bankerPair) {
                            const winnerBets = await baccaratService.viewBet('', gameId, BACCARAT_BETS.bankerPair);
                            if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                                const comission = setting.admin_commission;
                                for (let j = 0; j < winnerBets.length; j++) {
                                    const winnerBet = winnerBets[j];
                                    const userId = winnerBet.user_id;
                                    const betId = winnerBet.id;

                                    const amount = winnerBet.amount * BACCARAT_WINNING_PRICE.bankerPair;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }

                            }
                        }

                        const now = new Date();
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + BACCARAT_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            player_pair: playerPair,
                            banker_pair: bankerPair,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await baccaratService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.baccarat, updatePayload.admin_profit, gameId);
                        }

                        return updatePayload
                    }
                }
            }
        } catch (error) {
            console.log(error)
        }
    }

    // Call from socket
    async getActiveGameSocket(roomId) {
        try {
            const botUsers = await getAllPredefinedBots();
            const gameData = await baccaratService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "winning", "random_amount", "banker_pair", "player_pair", "end_datetime", "updated_date"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await baccaratService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + BACCARAT_FOR_BET) - currentTimeSec;

                const newGameData = [
                    {
                        id: gameId,
                        room_id: gameData[0].room_id,
                        winning: gameData[0].winning,
                        player_pair: gameData[0].player_pair,
                        banker_pair: gameData[0].banker_pair,
                        status: gameData[0].status,
                        added_date: gameData[0].added_date,
                        time_remaining: timeRemaining,
                        end_datetime: gameData[0].end_datetime,
                        updated_date: gameData[0].updated_date
                    }
                ];

                // Get Online Users
                // const online = await baccaratService.getRoomOnlineUsers(roomId);
                const onlineUsers = await getByConditions({ baccarat_id: roomId });
                const online = getRandomNumber(200, 300) + onlineUsers.length;
                // Get Bets for games
                const bets = await baccaratService.viewBet('', gameId);
                const { playerBetAmount, bankerBetAmount, tieBetAmount, playerPairBetAmount, bankerPairBetAmount } = await baccaratService.getBetDataByBets(bets, 0);

                const lastWinnings = await baccaratService.lastWinningBet(roomId);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: online,
                    online_users: onlineUsers,
                    player_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + playerBetAmount,
                    banker_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + bankerBetAmount,
                    tie_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + tieBetAmount,
                    player_pair_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + playerPairBetAmount,
                    banker_pair_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + bankerPairBetAmount,
                    last_bet: bets[0],
                    last_winning: lastWinnings
                }

                // Update Random Amount
                baccaratService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.player_amount}`) });
                // console.log('responsePayload',responsePayload);
                return responsePayload;

            } else {
                // console.log('false');
                return false
            }
        } catch (error) {
            console.log(error);
            throw new Error("Error while get active game");
        }
    }
}

module.exports = new BaccaratController();