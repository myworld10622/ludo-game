const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, JACKPOT_FOR_BET, JACKPOT_BETS, JACKPOT_WINNING_PRICE, JACKPOT_TIME_FOR_START_NEW_GAME } = require('../../constants');
const adminService = require('../../services/adminService');
const jackpotService = require('../../services/jackpotService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class JackpotController {
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
            const game = await jackpotService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                jackpot_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await jackpotService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            if (bet == JACKPOT_BETS.set) {
                jackpotService.updateJackpotAmount(amount)
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.jackpot, betData);
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
            const game = await jackpotService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await jackpotService.viewBet(user_id, game_id);
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
            const walletHistory = await jackpotService.walletHistory(user_id);
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
            jackpotService.updateBet(betId, gameBetPayload);

            // Update Jackpot
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            jackpotService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await jackpotService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.jackpot, gameBet);
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
            const rooms = await jackpotService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await jackpotService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        jackpotService.create({ room_id: room.id });
                        console.log('Jackpot Game Created Successfully');
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
            const rooms = await jackpotService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await jackpotService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added

                        const setting = await adminService.setting(["jackpot_status", "jackpot_random", "jackpot_coin", "admin_commission", "admin_coin"]);
                        const random = setting.jackpot_random;

                        let minNumber = setting.jackpot_status == 1 ? "SET" : "";
                        var winning = "";
                        let winningMultiply = 0;
                        const bets = await jackpotService.viewBet('', gameId);
                        const { totalBetAmount, highBetAmount, pairBetAmount, colorBetAmount, sequenceBetAmount, pureSequenceBetAmount, setBetAmount } = await jackpotService.getBetDataByBets(bets, 1);

                        let card1;
                        let card2;
                        let card3;
                        if (minNumber != "SET") {
                            if (setting && random == 1) {
                                const cardArray = ['HIGH_CARD', 'PAIR', 'COLOR', 'SEQUENCE', 'PURE_SEQUENCE'];
                                minNumber = cardArray[Math.floor(Math.random() * cardArray.length)];
                            } else if (setting && random == 2) {

                                const admin_coin = setting.admin_coin;

                                if (highBetAmount == 0 && pairBetAmount == 0 && colorBetAmount == 0 && sequenceBetAmount == 0 && pureSequenceBetAmount == 0) {

                                    const cardArray = ['HIGH_CARD', 'PAIR', 'COLOR', 'SEQUENCE', 'PURE_SEQUENCE'];
                                    minNumber = cardArray[Math.floor(Math.random() * cardArray.length)];

                                } else {
                                    const cardArray = ['HIGH_CARD', 'PAIR', 'COLOR', 'SEQUENCE', 'PURE_SEQUENCE'];

                                    const optionArray = getRandomFromFromArray(cardArray, cardArray.length);

                                    minNumber = "";
                                    for (const element of optionArray) {
                                        // console.log("element",element);
                                        switch (element) {
                                            case "HIGH_CARD":
                                                if (highBetAmount > 0 && admin_coin >= highBetAmount) {
                                                    minNumber = "HIGH_CARD";
                                                }
                                                break;

                                            case "PAIR":
                                                if (pairBetAmount > 0 && admin_coin >= pairBetAmount) {
                                                    minNumber = "PAIR";
                                                }
                                                break;

                                            case "COLOR":
                                                if (colorBetAmount > 0 && admin_coin >= colorBetAmount) {
                                                    minNumber = "COLOR";
                                                }
                                                break;

                                            case "SEQUENCE":
                                                if (sequenceBetAmount > 0 && admin_coin >= sequenceBetAmount) {
                                                    minNumber = "SEQUENCE";
                                                }
                                                break;

                                            case "PURE_SEQUENCE":
                                                if (pureSequenceBetAmount > 0 && admin_coin >= pureSequenceBetAmount) {
                                                    minNumber = "PURE_SEQUENCE";
                                                }
                                                break;
                                        }

                                        if (minNumber !== "") {
                                            break;
                                        }
                                    }
                                    // console.log("minNumber",minNumber);
                                    if (minNumber === '') {
                                        const numArray = [];
                                        numArray['HIGH_CARD'] = highBetAmount;
                                        numArray['PAIR'] = pairBetAmount;
                                        numArray['COLOR'] = colorBetAmount;
                                        numArray['SEQUENCE'] = sequenceBetAmount;
                                        numArray['PURE_SEQUENCE'] = pureSequenceBetAmount;

                                        const minValue = Math.min(...Object.values(numArray));
                                        // Get all keys which have minimum value
                                        const minKeys = Object.keys(numArray).filter(key => numArray[key] === minValue);
                                        // select a random from minimum
                                        minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                                    }
                                }

                            } else {
                                const numArray = [];
                                numArray['HIGH_CARD'] = highBetAmount;
                                numArray['PAIR'] = pairBetAmount;
                                numArray['COLOR'] = colorBetAmount;
                                numArray['SEQUENCE'] = sequenceBetAmount;
                                numArray['PURE_SEQUENCE'] = pureSequenceBetAmount;

                                const minValue = Math.min(...Object.values(numArray));
                                // Get all keys which have minimum value
                                const minKeys = Object.keys(numArray).filter(key => numArray[key] === minValue);
                                // select a random from minimum
                                minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                            }
                        }

                        const colorArray = ['BP', 'BL', 'RS', 'RP'];
                        const totalNumberArray = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

                        switch (minNumber) {
                            case "HIGH_CARD":
                                const high = getRandomNumber(1, 10);
                                switch (high) {
                                    case 1:
                                        card1 = 'BPA';
                                        card2 = 'RS8';
                                        card3 = 'BL3';
                                        break;

                                    case 2:
                                        card1 = 'BPK';
                                        card2 = 'RS7';
                                        card3 = 'BL4';
                                        break;

                                    case 3:
                                        card1 = 'BP9';
                                        card2 = 'RS7';
                                        card3 = 'BL2';
                                        break;

                                    case 4:
                                        card1 = 'BPK';
                                        card2 = 'RSA';
                                        card3 = 'BLJ';
                                        break;

                                    case 5:
                                        card1 = 'BP9';
                                        card2 = 'RS5';
                                        card3 = 'BL6';
                                        break;

                                    case 6:
                                        card1 = 'BP3';
                                        card2 = 'RS2';
                                        card3 = 'BL8';
                                        break;

                                    case 7:
                                        card1 = 'BP4';
                                        card2 = 'RS5';
                                        card3 = 'BL9';
                                        break;

                                    case 8:
                                        card1 = 'BP3';
                                        card2 = 'RS5';
                                        card3 = 'BL6';
                                        break;

                                    case 9:
                                        card1 = 'BPQ';
                                        card2 = 'RSK';
                                        card3 = 'BL8';
                                        break;

                                    case 10:
                                        card1 = 'BP4';
                                        card2 = 'RS6';
                                        card3 = 'BL9';
                                        break;

                                    default:
                                        card1 = 'BPA';
                                        card2 = 'RS8';
                                        card3 = 'BL3';
                                        break;
                                }

                                winning = JACKPOT_BETS.high;
                                winningMultiply = JACKPOT_WINNING_PRICE.high;
                                break;

                            case "PAIR":
                                const pairIndex = getRandomFromFromArray(totalNumberArray, 2);
                                console.log('pairIndex'+pairIndex);
                                const number1 = pairIndex[0];
                                const number2 = pairIndex[1];

                                card1 = 'BP' + number1;
                                card2 = 'RP' + number1;
                                card3 = 'BL' + number2;

                                winning = JACKPOT_BETS.pair;
                                winningMultiply = JACKPOT_WINNING_PRICE.pair;
                                break;

                            case "COLOR":
                                const colorIndex = getRandomNumber(0, (colorArray.length - 1));
                                const color = colorArray[colorIndex];

                                card1 = color + 'A';
                                card2 = color + '5';
                                card3 = color + '7';

                                winning = JACKPOT_BETS.color;
                                winningMultiply = JACKPOT_WINNING_PRICE.color;
                                break;

                            case "SEQUENCE":
                                const number = getRandomNumber(2, 7);

                                card1 = 'RP' + number;
                                card2 = 'BL' + (number + 1);
                                card3 = 'BP' + (number + 2);

                                winning = JACKPOT_BETS.sequence;
                                winningMultiply = JACKPOT_WINNING_PRICE.sequence;
                                break;

                            case "PURE_SEQUENCE":
                                const pureColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                const pureColor = colorArray[pureColorIndex];
                                const pureNumber = getRandomNumber(2, 7);

                                card1 = pureColor + pureNumber;
                                card2 = pureColor + (pureNumber + 1);
                                card3 = pureColor + (pureNumber + 2);

                                winning = JACKPOT_BETS.pure_sequence;
                                winningMultiply = JACKPOT_WINNING_PRICE.pure_sequence;
                                break;

                            case "SET":
                                const setNumberIndex = getRandomNumber(0, (totalNumberArray.length - 1));
                                const setNumber = totalNumberArray[setNumberIndex];

                                card1 = 'RP' + setNumber;
                                card2 = 'BL' + setNumber;
                                card3 = 'BP' + setNumber;

                                winning = JACKPOT_BETS.set;
                                const jackpotCoin = setting.jackpot_coin;
                                const minueCoin = Math.round(0.2 * jackpotCoin);
                                // const minusJackpotPoint = jackpotCoin * -1;
                                await jackpotService.updateJackpotAmount(minueCoin, "minus");

                                break;

                            default:

                                card1 = 'BPA';
                                card2 = 'RP7';
                                card3 = 'BL4';

                                winning = JACKPOT_BETS.high;
                                winningMultiply = JACKPOT_WINNING_PRICE.high;
                                break;
                        }

                        // Creating Mapping
                        await jackpotService.createMap(gameId, card1);
                        await jackpotService.createMap(gameId, card2);
                        await jackpotService.createMap(gameId, card3);

                        // Give winning Amount to user
                        const winnerBets = await jackpotService.viewBet('', gameId, winning);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;

                                if (winning == "SET") {
                                    const winningPercent = ((winnerBet.amount / setBetAmount) * 100);
                                    const winningAmount = Math.round((winningPercent / 100) * winningMultiply);
                                    totalWinningAmount += winningAmount;
                                    this.makeWinner(userId, betId, winningAmount, comission, gameId);
                                } else {
                                    const amount = winnerBet.amount * winningMultiply;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }
                            }

                        } else {
                            console.log("No Winning Bet Found");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + JACKPOT_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await jackpotService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.jackpot, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await jackpotService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id",
                "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await jackpotService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + JACKPOT_FOR_BET) - currentTimeSec;

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
                const online = await jackpotService.getRoomOnlineUsers(roomId);
                const onlineUsers = await getByConditions({ jackpot_room_id: roomId });
                // Get Bets for games
                const bets = await jackpotService.viewBet('', gameId);
                // const { dragonBetAmount, tigerBetAmount, tieBetAmount } = await jackpotService.getBetDataByBets(bets, 0);
                const { highBetAmount, pairBetAmount, colorBetAmount, sequenceBetAmount, pureSequenceBetAmount, setBetAmount } = await jackpotService.getBetDataByBets(bets, 0);

                const lastWinnings = await jackpotService.lastWinningBet(roomId, 15);

                const winners = await jackpotService.getJackpotWinners(1);
                let winnerUsers = [];
                if (Array.isArray(winners) && winners.length > 0) {
                    for (let index = 0; index < winners.length; index++) {
                        const element = winners[index];
                        winnerUsers = await jackpotService.getJackpotWinnerDetails(element.id);
                    }
                }
                const setting = await adminService.setting(["jackpot_coin"]);
                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: online,
                    online_users: onlineUsers.length,
                    high_card_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + highBetAmount,
                    pair_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + pairBetAmount,
                    color_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + colorBetAmount,
                    sequence_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + sequenceBetAmount,
                    pure_sequence_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + pureSequenceBetAmount,
                    set_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + setBetAmount,
                    last_bet: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + [bets[0]],
                    jackpot_amount: setting.jackpot_coin,
                    last_winning: lastWinnings,
                    big_winner: winnerUsers
                }
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

module.exports = new JackpotController();