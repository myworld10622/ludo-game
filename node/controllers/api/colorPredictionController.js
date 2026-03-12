const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, HTTP_NOT_FOUND, HTTP_ALREADY_USED, HTTP_NO_CONTENT, HTTP_SERVER_ERROR, COLOR_PREDICTION_FOR_BET, COLOR_PREDICTIONS_BETS, COLOR_PREDICTION_WINNING_PRICE, COLOR_PREDICTION_TIME_FOR_START_NEW_GAME } = require('../../constants');
const adminService = require('../../services/adminService');
const colorPredictionService = require('../../services/colorPredictionService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const userWallet = new UserWalletService();

class ColorPredictionController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            if (user.wallet < amount && amount > 0) {
                return insufficientAmountResponse(res);
            }
            const setting = await adminService.getById(1, ["color_prediction_withdraw"]);
            if (user.wallet < setting.color_prediction_withdraw) {
                return insufficientAmountResponse(res, setting.color_prediction_withdraw);
            }

            // game
            const game = await colorPredictionService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NO_CONTENT);
            }

            const checkBet = await colorPredictionService.checkBetNumberPlacedByUser(game_id, user_id, bet);
            if (checkBet) {
                return errorResponse(res, "Already Added Bet On Same Number", HTTP_ALREADY_USED);
            }

            const payload = {
                color_prediction_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await colorPredictionService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_SERVER_ERROR);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.cp, betData);
            const responseData = {
                bet_id: betData.id,
                wallet: user.wallet - amount
            }
            return successResponse(res, responseData);
        } catch (error) {
            console.log(error)
            return errorResponse(res, error.message, HTTP_SERVER_ERROR);
        }
    }

    async getResult(req, res) {
        try {
            const { game_id, user_id } = req.body;
            const game = await colorPredictionService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
            }
            const betData = await colorPredictionService.viewAllBetsByGameId(game_id, user_id, ["id", "user_amount", "amount"]);
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
            const walletHistory = await colorPredictionService.walletHistory(user_id);
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

    async gameHistory(req, res) {
        try {
            const gameHistory = await colorPredictionService.gameHistory(1, 50);
            if (Array.isArray(gameHistory) && gameHistory.length == 0) {
                return errorResponse(res, "No Logs", HTTP_NOT_ACCEPTABLE);
            }
            const responsePayload = {
                last_winning: gameHistory
            }
            return successResponse(res, responsePayload);
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async myHistory(req, res) {
        try {
            const { user_id } = req.body;
            const myHistory = await colorPredictionService.myHistory(user_id, 150);
            if (Array.isArray(myHistory) && myHistory.length == 0) {
                return errorResponse(res, "No logs", HTTP_NOT_ACCEPTABLE);
            }
            const responsePayload = {
                game_data: myHistory
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
            const dragonTigerBetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            colorPredictionService.updateBet(betId, dragonTigerBetPayload);

            // Update Dragon Tiger
            const dragonTigerPayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            colorPredictionService.update(gameId, dragonTigerPayload);

            // Get Bet to check amount deducted from which wallet
            const dragonTigerBet = await colorPredictionService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.cp, dragonTigerBet);
        } catch (error) {
            console.log(error);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// SOCKET FUNCTIONS /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    // Create Game From Socket
    async createColorPredictionGame() {
        try {
            const rooms = await colorPredictionService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await colorPredictionService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        colorPredictionService.create({ room_id: room.id, card: '' });
                        console.log('Prediction Created Successfully');
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
            const rooms = await colorPredictionService.getRooms("", "", ["id"]);
            if (!Array.isArray(rooms) || rooms.length === 0) return;

            for (const room of rooms) {
                const gameData = await colorPredictionService.getActiveGameOnTable(room.id, ["id", "status", "added_date"]);

                if (Array.isArray(gameData) && gameData.length === 0) {
                    await colorPredictionService.create({ room_id: room.id });
                    continue;
                }

                const game = gameData[0];

                if (game.status === 0) {
                    const gameStart = new Date(game.added_date).getTime() / 1000;
                    const now = Math.floor(Date.now() / 1000);
                    if ((gameStart + COLOR_PREDICTION_FOR_BET) > now) continue;

                    const gameId = game.id;
                    const bets = await colorPredictionService.viewAllBetsByGameId(gameId, '', ["id", "user_id", "bet", "amount"]);
                    const betData = await colorPredictionService.getBetDataByBets(bets);
                    const setting = await adminService.setting(["color_prediction_random", "admin_commission", "admin_coin", "distribute_precent"]);

                    const { numberMultiply, smallBigMultiple, greenRedMultiple, violetMultiple, greenRedHalfMultiple } = COLOR_PREDICTION_WINNING_PRICE;
                    const totalBetAmount = betData.totalBetAmount;
                    const random = setting.color_prediction_random;

                    const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];

                    const colorGroups = {
                        [COLOR_PREDICTIONS_BETS.green]: ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'],
                        [COLOR_PREDICTIONS_BETS.violet]: ['ZERO', 'FIVE'],
                        [COLOR_PREDICTIONS_BETS.red]: ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'],
                        [COLOR_PREDICTIONS_BETS.small]: ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'],
                        [COLOR_PREDICTIONS_BETS.big]: ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE']
                    };

                    const multipliers = {
                        greenRed: greenRedMultiple,
                        violet: violetMultiple,
                        smallBig: smallBigMultiple
                    };

                    let minNumber;

                    if (random === COLOR_PREDICTIONS_BETS.random) {
                        minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                    }

                    else if (random === COLOR_PREDICTIONS_BETS.least) {
                        const betAmounts = this.getBetAmounts(betData, numberMultiply, colorGroups, multipliers);
                        const minValue = Math.min(...Object.values(betAmounts));
                        const minKeys = Object.keys(betAmounts).filter(key => betAmounts[key] === minValue);
                        minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                    }

                    else if (random === COLOR_PREDICTIONS_BETS.optimisation) {
                        const betAmounts = this.getBetAmounts(betData, numberMultiply, colorGroups, multipliers);
                        const distributeAmount = (setting.admin_coin * (setting.distribute_precent / 100)).toFixed(2);
                        const optimisedKeys = Object.keys(betAmounts).filter(key => betAmounts[key] > 0 && distributeAmount >= betAmounts[key]);

                        if (optimisedKeys.length > 0) {
                            minNumber = optimisedKeys[Math.floor(Math.random() * optimisedKeys.length)];
                        } else {
                            const minValue = Math.min(...Object.values(betAmounts));
                            const minKeys = Object.keys(betAmounts).filter(key => betAmounts[key] === minValue);
                            minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                        }
                    }

                    else if (colorGroups[random]) {
                        minNumber = colorGroups[random][Math.floor(Math.random() * colorGroups[random].length)];
                    }

                    else {
                        minNumber = numArray[random];
                    }

                    let winningNumber = numArray.indexOf(minNumber);
                    let color = '', colorMultiply = 0, color1 = '', color1Multiply = 0, smallBig = '', numberMultiplyCalc = numberMultiply;

                    switch (minNumber) {
                        case 'ZERO':
                            color = COLOR_PREDICTIONS_BETS.red;
                            colorMultiply = greenRedHalfMultiple;
                            color1 = COLOR_PREDICTIONS_BETS.violet;
                            color1Multiply = violetMultiple;
                            smallBig = COLOR_PREDICTIONS_BETS.small;
                            break;
                        case 'ONE':
                        case 'THREE':
                        case 'SEVEN':
                        case 'NINE':
                            color = COLOR_PREDICTIONS_BETS.green;
                            colorMultiply = greenRedMultiple;
                            smallBig = winningNumber < 5 ? COLOR_PREDICTIONS_BETS.small : COLOR_PREDICTIONS_BETS.big;
                            break;
                        case 'TWO':
                        case 'FOUR':
                        case 'SIX':
                        case 'EIGHT':
                            color = COLOR_PREDICTIONS_BETS.red;
                            colorMultiply = greenRedMultiple;
                            smallBig = winningNumber < 5 ? COLOR_PREDICTIONS_BETS.small : COLOR_PREDICTIONS_BETS.big;
                            break;
                        case 'FIVE':
                            color = COLOR_PREDICTIONS_BETS.green;
                            colorMultiply = greenRedHalfMultiple;
                            color1 = COLOR_PREDICTIONS_BETS.violet;
                            color1Multiply = violetMultiple;
                            smallBig = COLOR_PREDICTIONS_BETS.big;
                            break;
                    }

                    await colorPredictionService.createMap(gameId, winningNumber);

                    let totalWinningAmount = 0;
                    const commission = setting.admin_commission;

                    const payoutGroups = [
                        { bet: winningNumber, multiplier: numberMultiplyCalc },
                        { bet: color, multiplier: colorMultiply },
                        { bet: color1, multiplier: color1Multiply },
                        { bet: smallBig, multiplier: smallBigMultiple }
                    ];

                    for (const group of payoutGroups) {
                        if (group.bet === '' || group.multiplier === 0) continue;
                        const matchingBets = bets.filter(bet => bet.bet == group.bet);
                        for (const winnerBet of matchingBets) {
                            const amount = winnerBet.amount * group.multiplier;
                            totalWinningAmount += amount;
                            await this.makeWinner(winnerBet.user_id, winnerBet.id, amount, commission, gameId);
                        }
                    }

                    const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(Date.now() + COLOR_PREDICTION_TIME_FOR_START_NEW_GAME * 1000));

                    const updatePayload = {
                        status: 1,
                        winning: winningNumber,
                        total_amount: totalBetAmount,
                        admin_profit: totalBetAmount - totalWinningAmount,
                        end_datetime: endDateTime,
                        random
                    };

                    await colorPredictionService.update(gameId, updatePayload);

                    if (updatePayload.admin_profit !== 0) {
                        await userWallet.directAdminProfitStatement(GAMES.cp, updatePayload.admin_profit, gameId);
                    }

                    return updatePayload;
                } else {
                    const gameStart = new Date(game.added_date).getTime() / 1000;
                    const now = Math.floor(Date.now() / 1000);
                    if (gameStart < now) {
                        const onlineUsers = await getByConditions({ color_prediction_room_id: room.id });
                        if (Array.isArray(onlineUsers) && onlineUsers.length > 0) {
                            await colorPredictionService.create({ room_id: room.id });
                        }
                    }
                }
            }
        } catch (error) {
            console.log("declareWinner error:", error);
        }
    }

    getBetAmounts(betData, numberMultiply, colorGroups, multipliers) {
        const betAmounts = {};
        const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];

        for (const num of numArray) {
            let amount = betData[`${num.toLowerCase()}Amount`] * numberMultiply;

            for (const colorKey in colorGroups) {
                if (colorGroups[colorKey].includes(num)) {
                    const colorId = parseInt(colorKey);

                    switch (colorId) {
                        case COLOR_PREDICTIONS_BETS.green:
                            if (betData.greenAmount > 0)
                                amount += betData.greenAmount * multipliers.greenRed;
                            break;
                        case COLOR_PREDICTIONS_BETS.red:
                            if (betData.redAmount > 0)
                                amount += betData.redAmount * multipliers.greenRed;
                            break;
                        case COLOR_PREDICTIONS_BETS.violet:
                            if (betData.violetAmount > 0)
                                amount += betData.violetAmount * multipliers.violet;
                            break;
                        case COLOR_PREDICTIONS_BETS.small:
                            if (betData.smallAmount > 0)
                                amount += betData.smallAmount * multipliers.smallBig;
                            break;
                        case COLOR_PREDICTIONS_BETS.big:
                            if (betData.bigAmount > 0)
                                amount += betData.bigAmount * multipliers.smallBig;
                            break;
                    }
                }
            }

            betAmounts[num] = amount;
        }

        return betAmounts;
    }
    // Call from socket
    async getActiveGameSocket(roomId) {
        try {
            const botUsers = await getAllPredefinedBots();
            const gameData = await colorPredictionService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "winning", "end_datetime", "updated_date"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await colorPredictionService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + COLOR_PREDICTION_FOR_BET) - currentTimeSec;

                const newGameData = [
                    {
                        id: gameId,
                        room_id: gameData[0].room_id,
                        winning: gameData[0].winning,
                        status: gameData[0].status,
                        added_date: gameData[0].added_date,
                        main_card: gameData[0].main_card,
                        time_remaining: timeRemaining,
                        end_datetime: gameData[0].end_datetime,
                        updated_date: gameData[0].updated_date
                    }
                ];

                // Get Online Users
                const onlineUsers = await getByConditions({ color_prediction_room_id: roomId });
                // game online users
                const online = await colorPredictionService.getRoomOnlineUsers(roomId);
                // Get Bets for games
                const bets = await colorPredictionService.viewBet('', gameId);
                const { zeroAmount, oneAmount, twoAmount, threeAmount, fourAmount, fiveAmount, sixAmount, sevenAmount, eightAmount, nineAmount, greenAmount, violetAmount, redAmount, smallAmount, bigAmount } = await colorPredictionService.getBetDataByBets(bets);

                const lastWinnings = await colorPredictionService.lastWinningBet(roomId);

                // const randomAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 100)

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online,
                    online_users: onlineUsers,
                    last_bet: bets[0],
                    my_bet_0: zeroAmount,
                    my_bet_1: oneAmount,
                    my_bet_2: twoAmount,
                    my_bet_3: threeAmount,
                    my_bet_4: fourAmount,
                    my_bet_5: fiveAmount,
                    my_bet_6: sixAmount,
                    my_bet_7: sevenAmount,
                    my_bet_8: eightAmount,
                    my_bet_9: nineAmount,
                    my_bet_10: greenAmount,
                    my_bet_11: violetAmount,
                    my_bet_12: redAmount,
                    my_bet_big: bigAmount,
                    my_bet_small: smallAmount,
                    last_winning: lastWinnings
                }
                return responsePayload;

            } else {
                return false
            }
        } catch (error) {
            console.log(error);
            throw new Error("Error while get active game");
        }
    }
}

module.exports = new ColorPredictionController();