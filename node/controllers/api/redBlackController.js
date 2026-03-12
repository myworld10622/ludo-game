const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, RED_BLACK_BETS, RED_BLACK_WINNING_PRICE, RED_BLACK_TIME_FOR_START_NEW_GAME, RED_BLACK_FOR_BET } = require('../../constants');
const adminService = require('../../services/adminService');
const redBlackService = require('../../services/redBlackService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
const { getCardPoints } = require('../../utils/cards');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class RedBlackController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            const setting = await adminService.setting(["red_and_black_min_bet"]);

            if (user.wallet < setting.red_and_black_min_bet) {
                return insufficientAmountResponse(res, setting.red_and_black_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await redBlackService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                red_black_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await redBlackService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.redBlack, betData);
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
            const game = await redBlackService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await redBlackService.viewBet(user_id, game_id);
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
            const walletHistory = await redBlackService.walletHistory(user_id);
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
            redBlackService.updateBet(betId, gameBetPayload);

            // Update Game
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            redBlackService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await redBlackService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.redBlack, gameBet);
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
            const rooms = await redBlackService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await redBlackService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        redBlackService.create({ room_id: room.id });
                        console.log('Red vs Black Game Created Successfully');
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
            const rooms = await redBlackService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await redBlackService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await redBlackService.viewBet('', gameId);
                        const { totalBetAmount, redBetAmount, blackBetAmount, pairBetAmount, colorBetAmount, sequenceBetAmount, pureSequenceBetAmount, setBetAmount } = await redBlackService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["red_black_random", "admin_commission", "admin_coin"]);
                        const random = setting.red_black_random;
                        const admin_coin = setting.admin_coin;
                        // Logic for winning
                        let card1;
                        let card2;
                        let card3;
                        let card4;
                        let card5;
                        let card6;

                        if (setting && random == 1) {
                            const cards = await redBlackService.getCards(6);
                            card1 = cards[0].cards;
                            card2 = cards[1].cards;
                            card3 = cards[2].cards;
                            card4 = cards[3].cards;
                            card5 = cards[4].cards;
                            card6 = cards[5].cards;
                        }else if (setting && random == 2) {
                            const optionArray = ['R_PAIR', 'R_COLOR', 'R_SEQUENCE', 'R_PURE_SEQUENCE', 'R_SET', 'B_PAIR', 'B_COLOR', 'B_SEQUENCE', 'B_PURE_SEQUENCE', 'B_SET'];
                            const numArray = [];
                            numArray['R_PAIR'] = redBetAmount + pairBetAmount;
                            numArray['R_COLOR'] = redBetAmount + colorBetAmount;
                            numArray['R_SEQUENCE'] = redBetAmount + sequenceBetAmount;
                            numArray['R_PURE_SEQUENCE'] = redBetAmount + pureSequenceBetAmount;
                            numArray['R_SET'] = redBetAmount + setBetAmount;

                            numArray['B_PAIR'] = blackBetAmount + pairBetAmount;
                            numArray['B_COLOR'] = blackBetAmount + colorBetAmount;
                            numArray['B_SEQUENCE'] = blackBetAmount + sequenceBetAmount;
                            numArray['B_PURE_SEQUENCE'] = blackBetAmount + pureSequenceBetAmount;
                            numArray['B_SET'] = blackBetAmount + setBetAmount;
                            const randomWinnerArray = getRandomFromFromArray(optionArray, optionArray.length);
                            let minNumber = '';
                            for (let index = 0; index < randomWinnerArray.length; index++) {
                                const element = randomWinnerArray[index];
                                if (numArray[element] > 0 && admin_coin > numArray[element]) {
                                    minNumber = element;
                                    break;
                                }
                            }
                            if (minNumber == '') {
                                const minValue = Math.min(...Object.values(numArray));
                                // Get all keys which have minimum value
                                const minKeys = Object.keys(numArray).filter(key => numArray[key] === minValue);
                                // select a random from minimum
                                minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                            }

                            const high = getRandomNumber(1, 10);
                            const highCards = [];
                            const bigCards = [];
                            let cards = [];

                            switch (high) {
                                case 1:
                                    highCards.push("BPA");
                                    highCards.push("RS8");
                                    highCards.push("BL3");
                                    break;

                                case 2:
                                    highCards.push("BPK");
                                    highCards.push("RS7");
                                    highCards.push("BL4");
                                    break;

                                case 3:
                                    highCards.push("BP9");
                                    highCards.push("RS7");
                                    highCards.push("BL2");
                                    break;

                                case 4:
                                    highCards.push("BPK");
                                    highCards.push("RSA");
                                    highCards.push("BLJ");
                                    break;

                                case 5:
                                    highCards.push("BP9");
                                    highCards.push("RS5");
                                    highCards.push("BL6");
                                    break;

                                case 6:
                                    highCards.push("BP3");
                                    highCards.push("RS2");
                                    highCards.push("BL8");
                                    break;

                                case 7:
                                    highCards.push("BP4");
                                    highCards.push("RS5");
                                    highCards.push("BL9");
                                    break;

                                case 8:
                                    highCards.push("BP3");
                                    highCards.push("RS5");
                                    highCards.push("BL6");
                                    break;

                                case 9:
                                    highCards.push("BPQ");
                                    highCards.push("RSK");
                                    highCards.push("BL8");
                                    break;

                                case 10:
                                    highCards.push("BP4");
                                    highCards.push("RS6");
                                    highCards.push("BL9");
                                    break;

                                default:
                                    highCards.push("BPA");
                                    highCards.push("RS8");
                                    highCards.push("BL3");
                                    break;
                            }

                            let winningMultiply = 0;
                            const colorArray = ['BP', 'BL', 'RS', 'RP'];
                            const totalNumberArray = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

                            switch (minNumber) {
                                case "R_PAIR":
                                    const numberIndex = getRandomFromFromArray(totalNumberArray, 2);
                                    const number1 = numberIndex[0];
                                    const number2 = numberIndex[1];

                                    bigCards.push('BP' + number1);
                                    bigCards.push('RP' + number1);
                                    bigCards.push('BL' + number2);

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_COLOR":
                                    const colorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const color = colorArray[colorIndex];

                                    bigCards.push(color + 'A');
                                    bigCards.push(color + '5');
                                    bigCards.push(color + '7');

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_SEQUENCE":
                                    const number = getRandomNumber(2, 7);

                                    bigCards.push('RP' + number);
                                    bigCards.push('BL' + (number + 1));
                                    bigCards.push('BP' + (number + 2));

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_PURE_SEQUENCE":
                                    const pureColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const pureColor = colorArray[pureColorIndex];
                                    const pureNumber = getRandomNumber(2, 7);

                                    bigCards.push(pureColor + pureNumber);
                                    bigCards.push(pureColor + (pureNumber + 1));
                                    bigCards.push(pureColor + (pureNumber + 2));

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_SET":
                                    const setNumberIndex = getRandomNumber(0, (totalNumberArray.length - 1));
                                    const setNumber = totalNumberArray[setNumberIndex];

                                    bigCards.push('BP' + setNumber);
                                    bigCards.push('RP' + setNumber);
                                    bigCards.push('BL' + setNumber);

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "B_PAIR":
                                    const blackNumberIndex = getRandomFromFromArray(totalNumberArray, 2);
                                    const bNumber1 = blackNumberIndex[0];
                                    const bNumber2 = blackNumberIndex[1];

                                    bigCards.push('BP' + bNumber1);
                                    bigCards.push('RP' + bNumber1);
                                    bigCards.push('BL' + bNumber2);

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "B_COLOR":
                                    const bColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const bColor = colorArray[bColorIndex];

                                    bigCards.push(bColor + 'A');
                                    bigCards.push(bColor + '5');
                                    bigCards.push(bColor + '7');

                                    cards = highCards.concat(bigCards);
                                    break;

                                case "B_SEQUENCE":
                                    const bNumber = getRandomNumber(2, 7);

                                    bigCards.push('RP' + bNumber);
                                    bigCards.push('BL' + (bNumber + 1));
                                    bigCards.push('BP' + (bNumber + 2));

                                    cards = highCards.concat(bigCards);
                                    break;

                                case "B_PURE_SEQUENCE":
                                    const bPureColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const bPureColor = colorArray[bPureColorIndex];
                                    const bPureNumber = getRandomNumber(2, 7);

                                    bigCards.push(bPureColor + bPureNumber);
                                    bigCards.push(bPureColor + (bPureNumber + 1));
                                    bigCards.push(bPureColor + (bPureNumber + 2));

                                    cards = highCards.concat(bigCards);
                                    break;

                                case "B_SET":
                                    const bSetNumberIndex = getRandomNumber(0, (totalNumberArray.length - 1));
                                    const bSetNumber = totalNumberArray[bSetNumberIndex];

                                    bigCards.push('BP' + bSetNumber);
                                    bigCards.push('RP' + bSetNumber);
                                    bigCards.push('BL' + bSetNumber);

                                    cards = highCards.concat(bigCards);
                                    break;

                                default:

                                    bigCards.push('BPA');
                                    bigCards.push('RP7');
                                    bigCards.push('BL4');

                                    cards = bigCards.concat(highCards);
                                    break;
                            }
                            card1 = cards[0];
                            card2 = cards[1];
                            card3 = cards[2];
                            card4 = cards[3];
                            card5 = cards[4];
                            card6 = cards[5];
                        } else {
                            const numArray = [];
                            numArray['R_PAIR'] = redBetAmount + pairBetAmount;
                            numArray['R_COLOR'] = redBetAmount + colorBetAmount;
                            numArray['R_SEQUENCE'] = redBetAmount + sequenceBetAmount;
                            numArray['R_PURE_SEQUENCE'] = redBetAmount + pureSequenceBetAmount;
                            numArray['R_SET'] = redBetAmount + setBetAmount;

                            numArray['B_PAIR'] = blackBetAmount + pairBetAmount;
                            numArray['B_COLOR'] = blackBetAmount + colorBetAmount;
                            numArray['B_SEQUENCE'] = blackBetAmount + sequenceBetAmount;
                            numArray['B_PURE_SEQUENCE'] = blackBetAmount + pureSequenceBetAmount;
                            numArray['B_SET'] = blackBetAmount + setBetAmount;

                            const minValue = Math.min(...Object.values(numArray));
                            // Get all keys which have minimum value
                            const minKeys = Object.keys(numArray).filter(key => numArray[key] === minValue);
                            // select a random from minimum
                            const minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];

                            const high = getRandomNumber(1, 10);
                            const highCards = [];
                            const bigCards = [];
                            let cards = [];

                            switch (high) {
                                case 1:
                                    highCards.push("BPA");
                                    highCards.push("RS8");
                                    highCards.push("BL3");
                                    break;

                                case 2:
                                    highCards.push("BPK");
                                    highCards.push("RS7");
                                    highCards.push("BL4");
                                    break;

                                case 3:
                                    highCards.push("BP9");
                                    highCards.push("RS7");
                                    highCards.push("BL2");
                                    break;

                                case 4:
                                    highCards.push("BPK");
                                    highCards.push("RSA");
                                    highCards.push("BLJ");
                                    break;

                                case 5:
                                    highCards.push("BP9");
                                    highCards.push("RS5");
                                    highCards.push("BL6");
                                    break;

                                case 6:
                                    highCards.push("BP3");
                                    highCards.push("RS2");
                                    highCards.push("BL8");
                                    break;

                                case 7:
                                    highCards.push("BP4");
                                    highCards.push("RS5");
                                    highCards.push("BL9");
                                    break;

                                case 8:
                                    highCards.push("BP3");
                                    highCards.push("RS5");
                                    highCards.push("BL6");
                                    break;

                                case 9:
                                    highCards.push("BPQ");
                                    highCards.push("RSK");
                                    highCards.push("BL8");
                                    break;

                                case 10:
                                    highCards.push("BP4");
                                    highCards.push("RS6");
                                    highCards.push("BL9");
                                    break;

                                default:
                                    highCards.push("BPA");
                                    highCards.push("RS8");
                                    highCards.push("BL3");
                                    break;
                            }

                            let winningMultiply = 0;
                            const colorArray = ['BP', 'BL', 'RS', 'RP'];
                            const totalNumberArray = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

                            switch (minNumber) {
                                case "R_PAIR":
                                    const numberIndex = getRandomFromFromArray(totalNumberArray, 2);
                                    const number1 = numberIndex[0];
                                    const number2 = numberIndex[1];

                                    bigCards.push('BP' + number1);
                                    bigCards.push('RP' + number1);
                                    bigCards.push('BL' + number2);

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_COLOR":
                                    const colorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const color = colorArray[colorIndex];

                                    bigCards.push(color + 'A');
                                    bigCards.push(color + '5');
                                    bigCards.push(color + '7');

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_SEQUENCE":
                                    const number = getRandomNumber(2, 7);

                                    bigCards.push('RP' + number);
                                    bigCards.push('BL' + (number + 1));
                                    bigCards.push('BP' + (number + 2));

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_PURE_SEQUENCE":
                                    const pureColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const pureColor = colorArray[pureColorIndex];
                                    const pureNumber = getRandomNumber(2, 7);

                                    bigCards.push(pureColor + pureNumber);
                                    bigCards.push(pureColor + (pureNumber + 1));
                                    bigCards.push(pureColor + (pureNumber + 2));

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "R_SET":
                                    const setNumberIndex = getRandomNumber(0, (totalNumberArray.length - 1));
                                    const setNumber = totalNumberArray[setNumberIndex];

                                    bigCards.push('BP' + setNumber);
                                    bigCards.push('RP' + setNumber);
                                    bigCards.push('BL' + setNumber);

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "B_PAIR":
                                    const blackNumberIndex = getRandomFromFromArray(totalNumberArray, 2);
                                    const bNumber1 = blackNumberIndex[0];
                                    const bNumber2 = blackNumberIndex[1];

                                    bigCards.push('BP' + bNumber1);
                                    bigCards.push('RP' + bNumber1);
                                    bigCards.push('BL' + bNumber2);

                                    cards = bigCards.concat(highCards);
                                    break;

                                case "B_COLOR":
                                    const bColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const bColor = colorArray[bColorIndex];

                                    bigCards.push(bColor + 'A');
                                    bigCards.push(bColor + '5');
                                    bigCards.push(bColor + '7');

                                    cards = highCards.concat(bigCards);
                                    break;

                                case "B_SEQUENCE":
                                    const bNumber = getRandomNumber(2, 7);

                                    bigCards.push('RP' + bNumber);
                                    bigCards.push('BL' + (bNumber + 1));
                                    bigCards.push('BP' + (bNumber + 2));

                                    cards = highCards.concat(bigCards);
                                    break;

                                case "B_PURE_SEQUENCE":
                                    const bPureColorIndex = getRandomNumber(0, (colorArray.length - 1));
                                    const bPureColor = colorArray[bPureColorIndex];
                                    const bPureNumber = getRandomNumber(2, 7);

                                    bigCards.push(bPureColor + bPureNumber);
                                    bigCards.push(bPureColor + (bPureNumber + 1));
                                    bigCards.push(bPureColor + (bPureNumber + 2));

                                    cards = highCards.concat(bigCards);
                                    break;

                                case "B_SET":
                                    const bSetNumberIndex = getRandomNumber(0, (totalNumberArray.length - 1));
                                    const bSetNumber = totalNumberArray[bSetNumberIndex];

                                    bigCards.push('BP' + bSetNumber);
                                    bigCards.push('RP' + bSetNumber);
                                    bigCards.push('BL' + bSetNumber);

                                    cards = highCards.concat(bigCards);
                                    break;

                                default:

                                    bigCards.push('BPA');
                                    bigCards.push('RP7');
                                    bigCards.push('BL4');

                                    cards = bigCards.concat(highCards);
                                    break;
                            }

                            card1 = cards[0];
                            card2 = cards[1];
                            card3 = cards[2];
                            card4 = cards[3];
                            card5 = cards[4];
                            card6 = cards[5];
                        }

                        // Creating Mapping
                        await redBlackService.createMap(gameId, card1);
                        await redBlackService.createMap(gameId, card2);
                        await redBlackService.createMap(gameId, card3);
                        await redBlackService.createMap(gameId, card4);
                        await redBlackService.createMap(gameId, card5);
                        await redBlackService.createMap(gameId, card6);

                        const redPoint = await redBlackService.cardValue(card1, card2, card3);
                        const blackPoint = await redBlackService.cardValue(card4, card5, card6);

                        const winnerPosition = await redBlackService.getWinnerPosition(redPoint, blackPoint);

                        const winning = winnerPosition == 0 ? RED_BLACK_BETS.red : RED_BLACK_BETS.black;
                        const multiply = winning == RED_BLACK_BETS.red ? RED_BLACK_WINNING_PRICE.red : RED_BLACK_WINNING_PRICE.black;

                        // Give winning Amount to user
                        const winnerBets = await redBlackService.viewBet('', gameId, winning);
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

                        const winningRule = winning == RED_BLACK_BETS.red ? redPoint[0] : blackPoint[0];
                        let multiplyRule = 0;
                        if (winningRule > 0) {
                            switch (winningRule) {
                                case (RED_BLACK_BETS.pair - 1):
                                    multiplyRule = RED_BLACK_WINNING_PRICE.pair
                                    break;

                                case (RED_BLACK_BETS.color - 1):
                                    multiplyRule = RED_BLACK_WINNING_PRICE.color
                                    break;

                                case (RED_BLACK_BETS.sequence - 1):
                                    multiplyRule = RED_BLACK_WINNING_PRICE.sequence
                                    break;

                                case (RED_BLACK_BETS.pure_sequence - 1):
                                    multiplyRule = RED_BLACK_WINNING_PRICE.pure_sequence
                                    break;

                                case (RED_BLACK_BETS.set - 1):
                                    multiplyRule = RED_BLACK_WINNING_PRICE.set
                                    break;

                                default:
                                    multiplyRule = 0
                                    break;
                            }

                            const winnerBets = await redBlackService.viewBet('', gameId, (winningRule + 1));
                            if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                                const comission = setting.admin_commission;
                                for (let j = 0; j < winnerBets.length; j++) {
                                    const winnerBet = winnerBets[j];
                                    const userId = winnerBet.user_id;
                                    const betId = winnerBet.id;

                                    const amount = winnerBet.amount * multiplyRule;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }

                            } else {
                                console.log("No Winning Bet Found");
                            }
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + RED_BLACK_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            winning_rule: winningRule,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await redBlackService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.redBlack, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await redBlackService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "winning_rule", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await redBlackService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + RED_BLACK_FOR_BET) - currentTimeSec;

                const newGameData = [
                    {
                        id: gameId,
                        room_id: gameData[0].room_id,
                        winning: gameData[0].winning,
                        winning_rule: gameData[0].winning_rule,
                        status: gameData[0].status,
                        added_date: gameData[0].added_date,
                        time_remaining: timeRemaining,
                        end_datetime: gameData[0].end_datetime,
                        updated_date: gameData[0].updated_date
                    }
                ];

                // Get Online Users
                const online = await redBlackService.getRoomOnlineUsers(roomId);
                const onlineUsers = await getByConditions({ red_black_id: roomId });
                // Get Bets for games
                const bets = await redBlackService.viewBet('', gameId);
                const { redBetAmount, blackBetAmount, pairBetAmount, colorBetAmount, sequenceBetAmount, pureSequenceBetAmount, setBetAmount } = await redBlackService.getBetDataByBets(bets, 0);

                const lastWinnings = await redBlackService.lastWinningBet(roomId);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: online,
                    online_users: onlineUsers.length,
                    red_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + redBetAmount,
                    black_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + blackBetAmount,
                    pair_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + pairBetAmount,
                    color_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + colorBetAmount,
                    sequence_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + sequenceBetAmount,
                    pure_sequence_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + pureSequenceBetAmount,
                    set_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + setBetAmount,
                    last_bet: bets[0],
                    last_winning: lastWinnings
                }

                // Update Random Amount
                redBlackService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.red_amount}`) });
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

module.exports = new RedBlackController();