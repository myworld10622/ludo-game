const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, SEVEN_UP_GAME, SEVEN_UP_TIME_FOR_START_NEW_GAME, SEVEN_UP_FOR_BET } = require('../../constants');
const adminService = require('../../services/adminService');
const sevenUpService = require('../../services/sevenUpService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class SevenUpController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            const setting = await adminService.setting(["seven_up_min_bet"]);

            if (user.wallet < setting.seven_up_min_bet) {
                return insufficientAmountResponse(res, setting.seven_up_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await sevenUpService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                seven_up_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await sevenUpService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.sevenUp, betData);

            const bets = await sevenUpService.viewBet(user_id, game_id);
            const { downBetAmount, upBetAmount, tieBetAmount } = await sevenUpService.getBetDataByBets(bets, 0);
            const responseData = {
                bet_id: betData.id,
                wallet: user.wallet - amount,
                my_down_bet: downBetAmount,
                my_up_bet: upBetAmount,
                my_tie_bet: tieBetAmount
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
            const game = await sevenUpService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await sevenUpService.viewBet(user_id, game_id);
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
            const walletHistory = await sevenUpService.walletHistory(user_id);
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
            const sevenUpBetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            sevenUpService.updateBet(betId, sevenUpBetPayload);

            // Update Seven Up
            const sevenUpPayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            sevenUpService.update(gameId, sevenUpPayload);

            // Get Bet to check amount deducted from which wallet
            const sevenUpBet = await sevenUpService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.sevenUp, sevenUpBet);
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
            const rooms = await sevenUpService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await sevenUpService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        sevenUpService.create({ room_id: room.id });
                        console.log('Seven Up Game Created Successfully');
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
            const rooms = await sevenUpService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await sevenUpService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await sevenUpService.viewBet('', gameId);
                        const { totalBetAmount, downBetAmount, upBetAmount, tieBetAmount } = await sevenUpService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["up_down_random", "admin_commission", "admin_coin"]);
                        const random = setting.up_down_random;
                        var winning = "";

                        // console.log("up_down_random",random);
                        // Logic for winning
                        if (setting && random == 1) {
                            winning = getRandomNumber(0, 2);
                        } else if (setting && random == 2) {
                            // console.log("up_down_random in ",random);
                            // console.log("downBetAmount",downBetAmount);
                            // console.log("upBetAmount",upBetAmount);
                            // console.log("tieBetAmount",tieBetAmount);

                            const admin_coin = setting.admin_coin;
                            if (downBetAmount == 0 && upBetAmount == 0 && tieBetAmount == 0) {
                                winning = getRandomNumber(0, 2);
                                // console.log("up_down no bet random winning",winning);
                            } else {
                                const optionArr = [0, 1, 2];
                                const optionArray = getRandomFromFromArray(optionArr, optionArr.length);
                                // console.log("seven up optionArray", optionArray);
                                for (const element of optionArray) {
                                    // console.log("seven up element", element);
                                    switch (element) {
                                        case 0:
                                            // console.log("seven up downBetAmount", downBetAmount);
                                            // console.log("seven up admin_coin", admin_coin);
                                            if (downBetAmount > 0 && admin_coin >= downBetAmount) {
                                                winning = 0;
                                            }
                                            break;

                                        case 1:
                                            // console.log("seven up upBetAmount", upBetAmount);
                                            // console.log("seven up admin_coin", admin_coin);
                                            if (upBetAmount > 0 && admin_coin >= upBetAmount) {
                                                winning = 1;
                                            }
                                            break;

                                        case 2:
                                            // console.log("seven up tieBetAmount", tieBetAmount);
                                            // console.log("seven up admin_coin", admin_coin);
                                            if (tieBetAmount > 0 && admin_coin >= tieBetAmount) {
                                                winning = 2;
                                            }
                                            break;
                                    }
                                    console.log("seven up for winning", winning);

                                    if (winning !== "") {
                                        break;
                                    }
                                }
                                console.log("seven up winning", winning);
                                if (winning === "") {
                                    // console.log("inside winning ", winning);
                                    if (downBetAmount > tieBetAmount && upBetAmount > tieBetAmount) {
                                        winning = SEVEN_UP_GAME.tie;
                                    } else {
                                        winning = downBetAmount > upBetAmount ? SEVEN_UP_GAME.up : SEVEN_UP_GAME.down;
                                    }
                                }
                                // console.log("seven up winning -", winning);
                            }

                        } else {
                            if (downBetAmount == 0 && upBetAmount == 0 && tieBetAmount == 0) {
                                winning = getRandomNumber(0, 2);
                            } else if (downBetAmount > tieBetAmount && upBetAmount > tieBetAmount) {
                                winning = SEVEN_UP_GAME.tie;
                            } else {
                                winning = downBetAmount > upBetAmount ? SEVEN_UP_GAME.up : SEVEN_UP_GAME.down;
                            }
                        }

                        const winningNumber = (winning == SEVEN_UP_GAME.down) ? getRandomNumber(2, 6) : ((winning == SEVEN_UP_GAME.up) ? getRandomNumber(8, 12) : 7);

                        // Crate Mapping
                        await sevenUpService.createMap(gameId, winningNumber);

                        // Give winning Amount to user
                        const winnerBets = await sevenUpService.viewBet('', gameId, winning);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;
                                if (winning == SEVEN_UP_GAME.tie) {
                                    const amount = winnerBet.amount * SEVEN_UP_GAME.upDownTieMultiply;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                } else {
                                    const amount = winnerBet.amount * SEVEN_UP_GAME.upDownMultiply;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }
                            }

                        } else {
                            console.log("No Winning Bet Found Seven Up");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + SEVEN_UP_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        sevenUpService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.sevenUp, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await sevenUpService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await sevenUpService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + SEVEN_UP_FOR_BET) - currentTimeSec;

                const newGameData = [
                    {
                        id: gameId,
                        room_id: gameData[0].room_id,
                        main_card: gameData[0].main_card,
                        winning: gameData[0].winning,
                        status: gameData[0].status,
                        added_date: gameData[0].added_date,
                        time_remaining: timeRemaining,
                        end_datetime: gameData[0].end_datetime,
                        updated_date: gameData[0].updated_date
                    }
                ];

                // Get Online Users
                const onlineUserCount = await sevenUpService.getRoomOnline(roomId);
                const onlineUsers = await getByConditions({ seven_up_room_id: roomId });
                // Get Bets for games
                const bets = await sevenUpService.viewBet('', gameId);
                const { downBetAmount, upBetAmount, tieBetAmount } = await sevenUpService.getBetDataByBets(bets);

                const lastWinnings = await sevenUpService.lastWinningBet(roomId);

                const randomDownAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 100)
                const randomUpAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 100)
                const randomTieAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 200)

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: onlineUserCount,
                    online_users: onlineUsers,
                    last_bet: bets[0],
                    down_bet: downBetAmount + randomDownAmount,
                    up_bet: upBetAmount + randomUpAmount,
                    tie_bet: tieBetAmount + randomTieAmount,
                    last_winning: lastWinnings
                }

                // Update Random Amount
                sevenUpService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.down_bet}`) });
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

module.exports = new SevenUpController();