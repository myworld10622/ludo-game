const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, THREE_DICE_FOR_BET, THREE_DICE_TIME_FOR_START_NEW_GAME, THREE_DICE_WINNING_PRICE } = require('../../constants');
const adminService = require('../../services/adminService');
const threeDiceService = require('../../services/threeDiceService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, wordToDigit, shuffleObject } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class threeDiceController {
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
            const game = await threeDiceService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                three_dice_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await threeDiceService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.threeDice, betData);
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
            const game = await threeDiceService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await threeDiceService.viewBet(user_id, game_id);
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
            const walletHistory = await threeDiceService.walletHistory(user_id);
            // const setting = await adminService.setting(["min_redeem"]);
            const responsePayload = {
                ThreeDicelog: walletHistory
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
            threeDiceService.updateBet(betId, gameBetPayload);

            // Update Game
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            threeDiceService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await threeDiceService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.threeDice, gameBet);
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
            const rooms = await threeDiceService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await threeDiceService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        threeDiceService.create({ room_id: room.id });
                        console.log('Three Dice Game Created Successfully');
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
            const rooms = await threeDiceService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await threeDiceService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added

                        const setting = await adminService.setting(["three_dice_random", "admin_commission", "admin_coin"]);
                        const random = setting.three_dice_random;
                        const admin_coin = setting.admin_coin;

                        const bets = await threeDiceService.viewBet('', gameId);
                        const { totalBetAmount, heartBetAmount, spadeBetAmount, diamondBetAmount, clubBetAmount, faceBetAmount, flagBetAmount } = await threeDiceService.getBetDataByBets(bets);

                        if (random == 1) {
                            await threeDiceService.createMap(gameId, getRandomNumber(1, 6));
                            await threeDiceService.createMap(gameId, getRandomNumber(1, 6));
                            await threeDiceService.createMap(gameId, getRandomNumber(1, 6));
                            // await threeDiceService.createMap(gameId, getRandomNumber(1, 6));
                            // await threeDiceService.createMap(gameId, getRandomNumber(1, 6));
                            // await threeDiceService.createMap(gameId, getRandomNumber(1, 6));
                        } 
                       /* else if (random == 2) {
                            let arr = {};
                            arr['ONE'] = heartBetAmount
                            arr['TWO'] = spadeBetAmount
                            arr['THREE'] = diamondBetAmount
                            arr['FOUR'] = clubBetAmount
                            arr['FIVE'] = faceBetAmount
                            arr['SIX'] = flagBetAmount
                            const betPlacedNumbers = Object.entries(arr).filter(([key, value]) => value > 0);
                            const betNotPlacedNumbers = Object.entries(arr).filter(([key, value]) => value === 0);

                            const filteredArray = shuffleObject(betPlacedNumbers);
                            // const sortedArr = Object.entries(arr).sort(([, a], [, b]) => a - b); // Sort the array

                            let diceCount = 6;
                            let adminTotalAmount = admin_coin;
                            for (const [key, value] of filteredArray) {
                                if (diceCount > 0) {
                                    const k = wordToDigit(key);

                                    let dice;

                                    const rotateDiceCount = Math.ceil(diceCount / filteredArray.length);
                                    let expectedRotation;
                                    let fromRotation;
                                    if (dice > rotateDiceCount) {
                                        expectedRotation = dice;
                                        fromRotation = dice - rotateDiceCount;
                                    } else {
                                        expectedRotation = rotateDiceCount;
                                    }
                                    const randomDice = getRandomNumber(1, expectedRotation);
                                    let betMultiply;
                                    for (let ind = 0; ind < (randomDice - 1); ind++) {
                                        switch ((randomDice - ind)) {
                                            case 1:
                                                betMultiply = THREE_DICE_WINNING_PRICE.oneDice;
                                                break;
                                            case 2:
                                                betMultiply = THREE_DICE_WINNING_PRICE.twoDice;
                                                break;
                                            case 3:
                                                betMultiply = THREE_DICE_WINNING_PRICE.threeDice;
                                                break;
                                            case 4:
                                                betMultiply = THREE_DICE_WINNING_PRICE.fourDice;
                                                break;
                                            case 5:
                                                betMultiply = THREE_DICE_WINNING_PRICE.fiveDice;
                                                break;
                                            case 6:
                                                betMultiply = THREE_DICE_WINNING_PRICE.sixDice;
                                                break;
                                            default:
                                                break;
                                        }

                                        if (betMultiply > 0 && (value * betMultiply) < adminTotalAmount) {
                                            adminTotalAmount -= value * betMultiply;
                                            dice = randomDice - ind;
                                            break;
                                        }
                                    }
                                    // Call the function to create a map for each dice
                                    for (let i = 0; i < dice; i++) {
                                        await threeDiceService.createMap(gameId, getRandomNumber(1, k));
                                    }

                                    diceCount -= dice;
                                } else {
                                    break;
                                }
                            }


                        }*/
                        else {
                            let arr = {};
                            arr['ONE'] = heartBetAmount
                            arr['TWO'] = spadeBetAmount
                            arr['THREE'] = diamondBetAmount
                            // arr['FOUR'] = clubBetAmount
                            // arr['FIVE'] = faceBetAmount
                            // arr['SIX'] = flagBetAmount

                            arr = shuffleObject(arr);
                            const sortedArr = Object.entries(arr).sort(([, a], [, b]) => a - b); // Sort the array

                            let diceCount = 6;
                            let remainingBalance = totalBetAmount;

                            for (const [key, value] of sortedArr) {
                                if (diceCount > 0) {
                                    console.log("Dice Count", diceCount)
                                    const k = wordToDigit(key);

                                    let dice;
                                    if (remainingBalance > (value * THREE_DICE_WINNING_PRICE.spade)) {
                                        
                                        const twoDice = diceCount >= 2 ? 2 : diceCount;
                                        const threeDice = diceCount >= 3 ? 3 : diceCount;
                                        dice = (value * THREE_DICE_WINNING_PRICE.spade) === 0 ? getRandomNumber(1, threeDice) : twoDice;

                                        remainingBalance -= (value * THREE_DICE_WINNING_PRICE.spade);
                                        console.log("Remaining Balance Here", twoDice, threeDice, remainingBalance, value * THREE_DICE_WINNING_PRICE.spade, dice)
                                    } else {
                                        console.log("Else Dice Remaining")
                                        dice = 1;
                                    }
                                    console.log("DICE DICE", dice)

                                    // Call the function to create a map for each dice
                                    for (let i = 0; i < dice; i++) {
                                        console.log("DDDDDD", k)
                                        console.log(getRandomNumber(1, k));
                                        await threeDiceService.createMap(gameId, getRandomNumber(1, k));
                                    }

                                    diceCount -= dice;
                                } else {
                                    break;
                                }
                            }
                        }

                        // Loop over the numbers from 1 to 6
                        for (let i = 1; i <= 3; i++) {
                            // Fetch count for each dice
                            const count = await threeDiceService.mapCount(gameData[0].id, i);

                            if (count > 0) {
                                let multiply = 0;

                                // Determine the multiplier based on the count
                                switch (count) {
                                    case 1:
                                        multiply = THREE_DICE_WINNING_PRICE.oneDice;
                                        break;
                                    case 2:
                                        multiply = THREE_DICE_WINNING_PRICE.twoDice;
                                        break;
                                    case 3:
                                        multiply = THREE_DICE_WINNING_PRICE.threeDice;
                                        break;
                                    // case 4:
                                    //     multiply = THREE_DICE_WINNING_PRICE.fourDice;
                                    //     break;
                                    // case 5:
                                    //     multiply = THREE_DICE_WINNING_PRICE.fiveDice;
                                    //     break;
                                    // case 6:
                                    //     multiply = THREE_DICE_WINNING_PRICE.sixDice;
                                    //     break;
                                    default:
                                        break;
                                }

                                if (multiply > 0) {
                                    // Fetch all bets for the current game and dice number
                                    const winnerBets = await threeDiceService.viewBet('', gameId, i);

                                    if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                                        for (const bet of winnerBets) {
                                            const amount = bet.amount * multiply;
                                            totalWinningAmount += amount;

                                            // Make the user a winner and update their balance
                                            await this.makeWinner(bet.user_id, bet.id, amount, setting.admin_commission, gameId);
                                        }
                                    } else {
                                        console.log("No Winning Bet Found");
                                    }
                                }
                            }
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + THREE_DICE_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await threeDiceService.update(gameId, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.threeDice, updatePayload.admin_profit, gameId);
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
            const gameData = await threeDiceService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await threeDiceService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + THREE_DICE_FOR_BET) - currentTimeSec;

                const newGameData = [
                    {
                        id: gameId,
                        room_id: gameData[0].room_id,
                        winning: gameData[0].winning,
                        status: gameData[0].status,
                        added_date: gameData[0].added_date,
                        time_remaining: timeRemaining,
                        end_datetime: gameData[0].end_datetime,
                        updated_date: gameData[0].updated_date
                    }
                ];

                // Get Online Users
                const online = await threeDiceService.getRoomOnlineUsers(roomId);
                const onlineUsers = await getByConditions({ three_dice_id: roomId });
                // Get Bets for games
                const bets = await threeDiceService.viewBet('', gameId);
                // const { dragonBetAmount, tigerBetAmount, tieBetAmount } = await threeDiceService.getBetDataByBets(bets, 0);
                const { heartBetAmount, spadeBetAmount, diamondBetAmount, clubBetAmount, faceBetAmount, flagBetAmount } = await threeDiceService.getBetDataByBets(bets);

                const lastWinnings = await threeDiceService.lastWinningBet(roomId, 15);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: online,
                    online_users: onlineUsers.length,
                    // heart_amount: getRandomNumber(0, 10000) + heartBetAmount,
                    // spade_amount: getRandomNumber(0, 10000) + spadeBetAmount,
                    // diamond_amount: getRandomNumber(0, 10000) + diamondBetAmount,
                    // club_amount: getRandomNumber(0, 10000) + clubBetAmount,
                    // face_amount: getRandomNumber(0, 10000) + faceBetAmount,
                    // flag_amount: getRandomNumber(0, 10000) + flagBetAmount,
                    heart_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + heartBetAmount,
                    spade_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + spadeBetAmount,
                    diamond_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + diamondBetAmount,
                    // club_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + clubBetAmount,
                    // face_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + faceBetAmount,
                    // flag_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + flagBetAmount,
                    last_bet: [bets[0]],
                    last_winning: lastWinnings
                }
                threeDiceService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.heart_amount}`) });
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

module.exports = new threeDiceController();