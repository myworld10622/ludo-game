const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, HTTP_NOT_FOUND, HTTP_ALREADY_USED, HTTP_NO_CONTENT, HTTP_SERVER_ERROR, COLOR_PREDICTION_FOR_BET, COLOR_PREDICTIONS_BETS, COLOR_PREDICTION_WINNING_PRICE, COLOR_PREDICTION_TIME_FOR_START_NEW_GAME } = require('../../constants');
const adminService = require('../../services/adminService');
const colorPredictionService = require('../../services/colorPredictionService3Min');
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
            userWallet.minusUserWallet(user_id, amount, GAMES.cp3, betData);
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
            if(Array.isArray(gameHistory) && gameHistory.length == 0) {
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
            if(Array.isArray(myHistory) && myHistory.length == 0) {
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
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.cp3, dragonTigerBet);
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
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await colorPredictionService.getActiveGameOnTable(room.id, ["id", "status", "added_date"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        colorPredictionService.create({ room_id: room.id })
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        const { numberMultiply, smallBigMultiple, greenRedMultiple, violetMultiple, greenRedHalfMultiple } = COLOR_PREDICTION_WINNING_PRICE;
                        const addedDateTimestamp = new Date(gameData[0].added_date).getTime() / 1000;
                        const currentTime = Math.floor(Date.now() / 1000);
                        // If betting period is ended then declare winner
                        if ((addedDateTimestamp + COLOR_PREDICTION_FOR_BET) <= currentTime) {

                            let totalWinningAmount = 0;
                            const gameId = gameData[0].id;
                            const bets = await colorPredictionService.viewAllBetsByGameId(gameId, '', ["id", "user_id", "bet", "amount"]);
                            const betData = await colorPredictionService.getBetDataByBets(bets);
                            const totalBetAmount = betData.totalBetAmount;
                            const setting = await adminService.setting(["color_prediction_3_min_random", "admin_commission", "admin_coin", "distribute_precent"]);
                            const random = setting.color_prediction_3_min_random;
                            let minNumber;

                            const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            const colorGroups = {
                                [COLOR_PREDICTIONS_BETS.green]: ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'],
                                [COLOR_PREDICTIONS_BETS.violet]: ['ZERO', 'FIVE'],
                                [COLOR_PREDICTIONS_BETS.red]: ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'],
                                [COLOR_PREDICTIONS_BETS.small]: ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'],
                                [COLOR_PREDICTIONS_BETS.big]: ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE']
                            };

                            if (random === COLOR_PREDICTIONS_BETS.random) {
                                minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            } else if (random === COLOR_PREDICTIONS_BETS.least || random === 20) {
                                const betAmounts = {
                                    'ZERO': betData.zeroAmount, 'ONE': betData.oneAmount, 'TWO': betData.twoAmount,
                                    'THREE': betData.threeAmount, 'FOUR': betData.fourAmount, 'FIVE': betData.fiveAmount,
                                    'SIX': betData.sixAmount, 'SEVEN': betData.sevenAmount, 'EIGHT': betData.eightAmount,
                                    'NINE': betData.nineAmount
                                };
                                
                                for (const key in betAmounts) {
                                    betAmounts[key] = (betAmounts[key] * numberMultiply) + 
                                                    (betData.redAmount * greenRedMultiple) +
                                                    (betData.greenAmount * greenRedMultiple) +
                                                    (betData.violetAmount * violetMultiple) +
                                                    (betData.smallAmount * smallBigMultiple) +
                                                    (betData.bigAmount * smallBigMultiple);
                                }
                                
                                if (random === 20 && betData.totalBetAmount > 0) {
                                    // console.log("Total Admin Coin: ", setting.admin_coin);
                                    // console.log("Total Admin distribute_precent: ", setting.distribute_precent);
                                    const distributeAmount = (setting.admin_coin*(setting.distribute_precent/100)).toFixed(2);
                                    // console.log("Distribute Amount: ", distributeAmount);
                                    minNumber = numArray.find(num => betAmounts[num] > 0 && distributeAmount >= betAmounts[num]) ||
                                                Object.keys(betAmounts).reduce((a, b) => betAmounts[a] < betAmounts[b] ? a : b);
                                } else {
                                    const minValue = Math.min(...Object.values(betAmounts));
                                    const minKeys = Object.keys(betAmounts).filter(key => betAmounts[key] === minValue);
                                    minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                                }
                            } else if (colorGroups[random]) {
                                minNumber = colorGroups[random][Math.floor(Math.random() * colorGroups[random].length)];
                            } else {
                                minNumber = numArray[random];
                            }
                            // let totalWinningAmount = 0;
                            // const gameId = gameData[0].id;
                            // // Get bets on which Bet is Added
                            // const bets = await colorPredictionService.viewAllBetsByGameId(gameId, '', ["id", "user_id", "bet", "amount"]);
                            // const { totalBetAmount, zeroAmount, oneAmount, twoAmount, threeAmount, fourAmount, fiveAmount, sixAmount, sevenAmount, eightAmount, nineAmount, greenAmount, violetAmount, redAmount, smallAmount, bigAmount } = await colorPredictionService.getBetDataByBets(bets);

                            // const setting = await adminService.setting(["color_prediction_3_min_random", "admin_commission", "admin_coin"]);
                            // const random = setting.color_prediction_3_min_random;
                            // let minNumber;
                            // // Logic for winning
                            // if (random == COLOR_PREDICTIONS_BETS.random) {
                            //     const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            //     // select a random key
                            //     minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            // } else if (random == COLOR_PREDICTIONS_BETS.least) {
                            //     const numArray = [];
                            //     numArray['ZERO'] = (zeroAmount * numberMultiply) + (redAmount * greenRedHalfMultiple) + (violetAmount * violetMultiple) + (smallAmount * smallBigMultiple);
                            //     numArray['ONE'] = (oneAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (smallAmount * smallBigMultiple);
                            //     numArray['TWO'] = (twoAmount * numberMultiply) + (redAmount * greenRedMultiple) + (smallAmount * smallBigMultiple);
                            //     numArray['THREE'] = (threeAmount * numberMultiply) + (smallAmount * smallBigMultiple) + (greenAmount * greenRedMultiple);
                            //     numArray['FOUR'] = (fourAmount * numberMultiply) + (smallAmount * smallBigMultiple) + (redAmount * greenRedMultiple);
                            //     numArray['FIVE'] = (fiveAmount * numberMultiply) + (greenAmount * greenRedHalfMultiple) + (violetAmount * violetMultiple) + (smallAmount * smallBigMultiple);
                            //     numArray['SIX'] = (sixAmount * numberMultiply) + (redAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //     numArray['SEVEN'] = (sevenAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //     numArray['EIGHT'] = (eightAmount * numberMultiply) + (redAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //     numArray['NINE'] = (nineAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);

                            //     // Find the minimum value
                            //     const minValue = Math.min(...Object.values(numArray));
                            //     // Get all keys which have minimum value
                            //     const minKeys = Object.keys(numArray).filter(key => numArray[key] === minValue);
                            //     // select a random from minimum
                            //     minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                            // } else if (random == COLOR_PREDICTIONS_BETS.green) {
                            //     const numArray = ['ONE', 'THREE', 'FIVE', 'SEVEN', 'NINE'];
                            //     minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            // } else if (random == COLOR_PREDICTIONS_BETS.violet) {
                            //     const numArray = ['ZERO', 'FIVE'];
                            //     minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            // } else if (random == COLOR_PREDICTIONS_BETS.red) {
                            //     const numArray = ['ZERO', 'TWO', 'FOUR', 'SIX', 'EIGHT'];
                            //     minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            // } else if (random == COLOR_PREDICTIONS_BETS.small) {
                            //     const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR'];
                            //     minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            // } else if (random == COLOR_PREDICTIONS_BETS.big) {
                            //     const numArray = ['FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            //     minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            // } else if (random == 20) {
                            //     const admin_coin = setting.admin_coin;
                            //     const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            //     if (totalBetAmount == 0) {
                            //         // select a random key
                            //         minNumber = numArray[Math.floor(Math.random() * numArray.length)];
                            //     } else {
                            //         const optionArray = getRandomFromFromArray(numArray, numArray.length);
                            //         minNumber = "";
                            //         for (const element of optionArray) {
                            //             switch (element) {
                            //                 case "ZERO":
                            //                     const zeroCalculatedAmount = (zeroAmount * numberMultiply) + (redAmount * greenRedHalfMultiple) + (violetAmount * violetMultiple) + (smallAmount * smallBigMultiple);
                            //                     if (zeroCalculatedAmount > 0 && admin_coin >= zeroCalculatedAmount) {
                            //                         minNumber = "ZERO";
                            //                     }
                            //                     break;

                            //                 case "ONE":
                            //                     const oneCalculatedAmount = (oneAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (smallAmount * smallBigMultiple);
                            //                     if (oneCalculatedAmount > 0 && admin_coin >= oneCalculatedAmount) {
                            //                         minNumber = "ONE";
                            //                     }
                            //                     break;

                            //                 case "TWO":
                            //                     const twoCalculatedAmount = (twoAmount * numberMultiply) + (redAmount * greenRedMultiple) + (smallAmount * smallBigMultiple);
                            //                     if (twoCalculatedAmount > 0 && admin_coin >= twoCalculatedAmount) {
                            //                         minNumber = "TWO";
                            //                     }
                            //                     break;

                            //                 case "THREE":
                            //                     const threeCalculatedAmount = (threeAmount * numberMultiply) + (smallAmount * smallBigMultiple) + (greenAmount * greenRedMultiple);
                            //                     if (threeCalculatedAmount > 0 && admin_coin >= threeCalculatedAmount) {
                            //                         minNumber = "THREE";
                            //                     }
                            //                     break;

                            //                 case "FOUR":
                            //                     const fourCalculatedAmount = (fourAmount * numberMultiply) + (smallAmount * smallBigMultiple) + (redAmount * greenRedMultiple);
                            //                     if (fourCalculatedAmount > 0 && admin_coin >= fourCalculatedAmount) {
                            //                         minNumber = "FOUR";
                            //                     }
                            //                     break;

                            //                 case "FIVE":
                            //                     const fiveCalculatedAmount = (fiveAmount * numberMultiply) + (greenAmount * greenRedHalfMultiple) + (violetAmount * violetMultiple) + (smallAmount * smallBigMultiple);
                            //                     if (fiveCalculatedAmount > 0 && admin_coin >= fiveCalculatedAmount) {
                            //                         minNumber = "FIVE";
                            //                     }
                            //                     break;

                            //                 case "SIX":
                            //                     const sixCalculatedAmount = (sixAmount * numberMultiply) + (redAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //                     if (sixCalculatedAmount > 0 && admin_coin >= sixCalculatedAmount) {
                            //                         minNumber = "SIX";
                            //                     }
                            //                     break;

                            //                 case "SEVEN":
                            //                     const sevenCalculatedAmount = (sevenAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //                     if (sevenCalculatedAmount > 0 && admin_coin >= sevenCalculatedAmount) {
                            //                         minNumber = "SEVEN";
                            //                     }
                            //                     break;

                            //                 case "EIGHT":
                            //                     const eightCalculatedAmount = (eightAmount * numberMultiply) + (redAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //                     if (eightCalculatedAmount > 0 && admin_coin >= eightCalculatedAmount) {
                            //                         minNumber = "EIGHT";
                            //                     }
                            //                     break;

                            //                 case "NINE":
                            //                     const nineCalculatedAmount = (nineAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //                     if (nineCalculatedAmount > 0 && admin_coin >= nineCalculatedAmount) {
                            //                         minNumber = "NINE";
                            //                     }
                            //                     break;
                            //             }

                            //             if (minNumber !== "") {
                            //                 break;
                            //             }
                            //         }
                            //         if (minNumber === "") {
                            //             const numArray = [];
                            //             numArray['ZERO'] = (zeroAmount * numberMultiply) + (redAmount * greenRedHalfMultiple) + (violetAmount * violetMultiple) + (smallAmount * smallBigMultiple);
                            //             numArray['ONE'] = (oneAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (smallAmount * smallBigMultiple);
                            //             numArray['TWO'] = (twoAmount * numberMultiply) + (redAmount * greenRedMultiple) + (smallAmount * smallBigMultiple);
                            //             numArray['THREE'] = (threeAmount * numberMultiply) + (smallAmount * smallBigMultiple) + (greenAmount * greenRedMultiple);
                            //             numArray['FOUR'] = (fourAmount * numberMultiply) + (smallAmount * smallBigMultiple) + (redAmount * greenRedMultiple);
                            //             numArray['FIVE'] = (fiveAmount * numberMultiply) + (greenAmount * greenRedHalfMultiple) + (violetAmount * violetMultiple) + (smallAmount * smallBigMultiple);
                            //             numArray['SIX'] = (sixAmount * numberMultiply) + (redAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //             numArray['SEVEN'] = (sevenAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //             numArray['EIGHT'] = (eightAmount * numberMultiply) + (redAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);
                            //             numArray['NINE'] = (nineAmount * numberMultiply) + (greenAmount * greenRedMultiple) + (bigAmount * smallBigMultiple);

                            //             // Find the minimum value
                            //             const minValue = Math.min(...Object.values(numArray));
                            //             // Get all keys which have minimum value
                            //             const minKeys = Object.keys(numArray).filter(key => numArray[key] === minValue);
                            //             // select a random from minimum
                            //             minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                            //         }
                            //     }
                            // } else {
                            //     const numArray = ['ZERO', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE'];
                            //     minNumber = numArray[random];
                            // }

                            let color = '';
                            let colorMultiply = '';
                            let color1 = '';
                            let color1Multiply = '';
                            let winningNumber = '';
                            let numberMultiplyCalc = '';
                            let smallBig = '';

                            switch (minNumber) {
                                case 'ZERO':
                                    color = COLOR_PREDICTIONS_BETS.red;
                                    colorMultiply = greenRedHalfMultiple;
                                    color1 = COLOR_PREDICTIONS_BETS.violet;
                                    color1Multiply = violetMultiple;
                                    winningNumber = 0;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.small;
                                    break;
                                case 'ONE':
                                    color = COLOR_PREDICTIONS_BETS.green;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 1;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.small;
                                    break;
                                case 'TWO':
                                    color = COLOR_PREDICTIONS_BETS.red;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 2;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.small;
                                    break;
                                case 'THREE':
                                    color = COLOR_PREDICTIONS_BETS.green;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 3;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.small;
                                    break;
                                case 'FOUR':
                                    color = COLOR_PREDICTIONS_BETS.red;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 4;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.small;
                                    break;
                                case 'FIVE':
                                    color = COLOR_PREDICTIONS_BETS.green;
                                    colorMultiply = greenRedHalfMultiple;
                                    color1 = COLOR_PREDICTIONS_BETS.violet;
                                    color1Multiply = violetMultiple;
                                    winningNumber = 5;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.big;
                                    break;
                                case 'SIX':
                                    color = COLOR_PREDICTIONS_BETS.red;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 6;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.big;
                                    break;
                                case 'SEVEN':
                                    color = COLOR_PREDICTIONS_BETS.green;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 7;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.big;
                                    break;
                                case 'EIGHT':
                                    color = COLOR_PREDICTIONS_BETS.red;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 8;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.big;
                                    break;
                                case 'NINE':
                                    color = COLOR_PREDICTIONS_BETS.green;
                                    colorMultiply = greenRedMultiple;
                                    winningNumber = 9;
                                    numberMultiplyCalc = numberMultiply;
                                    smallBig = COLOR_PREDICTIONS_BETS.big;
                                    break;

                                default:
                                    color = '';
                                    colorMultiply = '';
                                    color1 = '';
                                    color1Multiply = '';
                                    winningNumber = '';
                                    numberMultiplyCalc = '';
                                    break;
                            }

                            colorPredictionService.createMap(gameId, winningNumber);

                            // Give winning Amount to user
                            const winnerBets = bets.filter((bet) => bet.bet == winningNumber);
                            const comission = setting.admin_commission;
                            if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                                for (let j = 0; j < winnerBets.length; j++) {
                                    const winnerBet = winnerBets[j];
                                    const userId = winnerBet.user_id;
                                    const betId = winnerBet.id;
                                    const amount = winnerBet.amount * numberMultiplyCalc;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }

                            } else {
                                console.log("No Winning Bet Found");
                            }

                            if (color) {
                                const colorBets = bets.filter((bet) => bet.bet == color);
                                if (Array.isArray(colorBets) && colorBets.length > 0) {
                                    for (let j = 0; j < colorBets.length; j++) {
                                        const winnerBet = colorBets[j];
                                        const userId = winnerBet.user_id;
                                        const betId = winnerBet.id;
                                        const amount = winnerBet.amount * colorMultiply;
                                        totalWinningAmount += amount;
                                        this.makeWinner(userId, betId, amount, comission, gameId);
                                    }

                                } else {
                                    console.log("No Winning Bet Found for color");
                                }
                            }

                            if (color1) {
                                const colorBets = bets.filter((bet) => bet.bet == color1);
                                if (Array.isArray(colorBets) && colorBets.length > 0) {
                                    for (let j = 0; j < colorBets.length; j++) {
                                        const winnerBet = colorBets[j];
                                        const userId = winnerBet.user_id;
                                        const betId = winnerBet.id;
                                        const amount = winnerBet.amount * color1Multiply;
                                        totalWinningAmount += amount;
                                        this.makeWinner(userId, betId, amount, comission, gameId);
                                    }

                                } else {
                                    console.log("No Winning Bet Found for color 1");
                                }
                            }

                            if (smallBig) {
                                const smallBigBets = bets.filter((bet) => bet.bet == smallBig);
                                if (Array.isArray(smallBigBets) && smallBigBets.length > 0) {
                                    for (let j = 0; j < smallBigBets.length; j++) {
                                        const winnerBet = smallBigBets[j];
                                        const userId = winnerBet.user_id;
                                        const betId = winnerBet.id;
                                        const amount = winnerBet.amount * smallBigMultiple;
                                        totalWinningAmount += amount;
                                        this.makeWinner(userId, betId, amount, comission, gameId);
                                    }

                                } else {
                                    console.log("No Winning Bet Found for small big");
                                }
                            }

                            const now = new Date();
                            // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                            const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + COLOR_PREDICTION_TIME_FOR_START_NEW_GAME * 1000));

                            const updatePayload = {
                                status: 1,
                                winning: winningNumber,
                                total_amount: totalBetAmount,
                                admin_profit: totalBetAmount - totalWinningAmount,
                                end_datetime: endDateTime,
                                random
                            }
                            colorPredictionService.update(gameData[0].id, updatePayload);
                            // If admin profit is in positive or nagative then log this
                            if (updatePayload.admin_profit != 0) {
                                userWallet.directAdminProfitStatement(GAMES.cp3, updatePayload.admin_profit, gameData[0].id);
                            }

                            return updatePayload
                        } else {
                            console.log("No CP Game to start")
                        }
                    } else {
                        const addedDateTimestamp = new Date(gameData[0].added_date).getTime() / 1000;
                        const currentTime = Math.floor(Date.now() / 1000);
                        if (addedDateTimestamp < currentTime) {
                            const onlineUsers = await getByConditions({ color_prediction_room_id: room.id });
                            if (Array.isArray(onlineUsers) && onlineUsers.length > 0) {
                                colorPredictionService.create({ room_id: room.id });
                            } else {
                                console.log("No Online User Found")
                            }
                        } else {
                            console.log("No Game to end")
                        }
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