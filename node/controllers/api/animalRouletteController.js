const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, ANIMAL_ROULETTE_GAME, ANIMAL_ROULETTE_FOR_BET, ANIMAL_ROULETTE_TIME_FOR_START_NEW_GAME } = require('../../constants');
const adminService = require('../../services/adminService');
const animalRouletteService = require('../../services/animalRouletteService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getAmountByPercentage, getRoundNumber, getRandomFromFromArray, getRandomNumber } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class AnimalRouletteController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            const setting = await adminService.setting(["animal_roullette_min_bet"]);

            if (user.wallet < setting.animal_roullette_min_bet) {
                return insufficientAmountResponse(res, setting.animal_roullette_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await animalRouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                animal_roulette_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await animalRouletteService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.animalRoulette, betData);
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
            const game = await animalRouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await animalRouletteService.viewBet(user_id, game_id);
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
            const walletHistory = await animalRouletteService.walletHistory(user_id);
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
            animalRouletteService.updateBet(betId, gameBetPayload);

            // Update Animal Roulette
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            animalRouletteService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await animalRouletteService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.animalRoulette, gameBet);
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
            const rooms = await animalRouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await animalRouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        animalRouletteService.create({ room_id: room.id });
                        console.log('Animal Roulette Game Created Successfully');
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
            const rooms = await animalRouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await animalRouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await animalRouletteService.viewBet('', gameId);
                        const { totalBetAmount, tigerBetAmount, snakeBetAmount, sharkBetAmount, foxBetAmount, cheetahBetAmount, bearBetAmount, whaleBetAmount, lionBetAmount } = await animalRouletteService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["animal_roulette_random", "admin_commission", "admin_coin"]);
                        const admin_coin = setting.admin_coin;
                        const random = setting.animal_roulette_random;
                        let winning;
                        // Logic for winning
                        let minNumber;
                        if (setting && random == 1) {
                            const animalArray = ['TIGER', 'SNAKE', 'SHARK', 'FOX', 'CHEETAH', 'BEAR', 'WHALE', 'LION'];
                            minNumber = animalArray[Math.floor(Math.random() * animalArray.length)];
                        } else if (setting && random == 2) {

                            if (tigerBetAmount == 0 && snakeBetAmount == 0 && sharkBetAmount == 0 && foxBetAmount == 0 && cheetahBetAmount == 0 && bearBetAmount == 0 && whaleBetAmount == 0 && lionBetAmount == 0) {

                                const animalArray = ['TIGER', 'SNAKE', 'SHARK', 'FOX', 'CHEETAH', 'BEAR', 'WHALE', 'LION'];
                                minNumber = animalArray[Math.floor(Math.random() * animalArray.length)];

                            } else {
                                const animalArray = ['TIGER', 'SNAKE', 'SHARK', 'FOX', 'CHEETAH', 'BEAR', 'WHALE', 'LION'];

                                const optionArray = getRandomFromFromArray(animalArray, animalArray.length);
                                // console.log("optionArray",optionArray);
                                minNumber = "";
                                if (admin_coin <= 0) {
                                    minNumber = ""; // No selection if admin_coin is negative
                                } else {
                                    for (const element of optionArray) {
                                        switch (element) {
                                            case "TIGER":
                                                if (tigerBetAmount > 0 && admin_coin >= tigerBetAmount) {
                                                    minNumber = "TIGER";
                                                }
                                                break;
                                            case "SNAKE":
                                                if (snakeBetAmount > 0 && admin_coin >= snakeBetAmount) {
                                                    minNumber = "SNAKE";
                                                }
                                                break;
                                            case "SHARK":
                                                if (sharkBetAmount > 0 && admin_coin >= sharkBetAmount) {
                                                    minNumber = "SHARK";
                                                }
                                                break;
                                            case "FOX":
                                                if (foxBetAmount > 0 && admin_coin >= foxBetAmount) {
                                                    minNumber = "FOX";
                                                }
                                                break;
                                            case "CHEETAH":
                                                if (cheetahBetAmount > 0 && admin_coin >= cheetahBetAmount) {
                                                    minNumber = "CHEETAH";
                                                }
                                                break;
                                            case "BEAR":
                                                if (bearBetAmount > 0 && admin_coin >= bearBetAmount) {
                                                    minNumber = "BEAR";
                                                }
                                                break;
                                            case "WHALE":
                                                if (whaleBetAmount > 0 && admin_coin >= whaleBetAmount) {
                                                    minNumber = "WHALE";
                                                }
                                                break;
                                            case "LION":
                                                if (lionBetAmount > 0 && admin_coin >= lionBetAmount) {
                                                    minNumber = "LION";
                                                }
                                                break;
                                        }
                                
                                        if (minNumber !== "") {
                                            break;
                                        }
                                    }
                                }

                                if (minNumber === '') {
                                    const animalObject = {  // Use an object instead of an array
                                        TIGER: tigerBetAmount,
                                        SNAKE: snakeBetAmount,
                                        SHARK: sharkBetAmount,
                                        FOX: foxBetAmount,
                                        CHEETAH: cheetahBetAmount,
                                        BEAR: bearBetAmount,
                                        WHALE: whaleBetAmount,
                                        LION: lionBetAmount
                                    };
                                    
                                    const minValue = Math.min(...Object.values(animalObject));
                                    
                                    // Get all keys which have the minimum value
                                    const minKeys = Object.keys(animalObject).filter(key => animalObject[key] === minValue);
                                    
                                    // Select a random one from the minimum values
                                    minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                                }
                            }

                        } else {
                            const animalObject = {  // Use an object instead of an array
                                TIGER: tigerBetAmount,
                                SNAKE: snakeBetAmount,
                                SHARK: sharkBetAmount,
                                FOX: foxBetAmount,
                                CHEETAH: cheetahBetAmount,
                                BEAR: bearBetAmount,
                                WHALE: whaleBetAmount,
                                LION: lionBetAmount
                            };
                            
                            const minValue = Math.min(...Object.values(animalObject));
                            
                            // Get all keys which have the minimum value
                            const minKeys = Object.keys(animalObject).filter(key => animalObject[key] === minValue);
                            
                            // Select a random one from the minimum values
                            minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                        }

                        let multiplyNumber;
                        switch (minNumber) {
                            case "TIGER":
                                winning = ANIMAL_ROULETTE_GAME.tiger;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.tigerMultiply;
                                break;

                            case "SNAKE":
                                winning = ANIMAL_ROULETTE_GAME.snake;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.snakeMultiply;
                                break;

                            case "SHARK":
                                winning = ANIMAL_ROULETTE_GAME.shark;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.sharkMultiply;
                                break;

                            case "FOX":
                                winning = ANIMAL_ROULETTE_GAME.fox;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.foxMultiply;
                                break;

                            case "CHEETAH":
                                winning = ANIMAL_ROULETTE_GAME.cheetah;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.cheetahMultiply;
                                break;

                            case "BEAR":
                                winning = ANIMAL_ROULETTE_GAME.bear;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.bearMultiply;
                                break;

                            case "WHALE":
                                winning = ANIMAL_ROULETTE_GAME.whale;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.whaleMultiply;
                                break;

                            case "LION":
                                winning = ANIMAL_ROULETTE_GAME.lion;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.lionMultiply;
                                break;

                            default:
                                winning = ANIMAL_ROULETTE_GAME.tiger;
                                multiplyNumber = ANIMAL_ROULETTE_GAME.tigerMultiply;
                                break;
                        }

                        await animalRouletteService.createMap(gameId, winning);

                        // Give winning Amount to user
                        const winnerBets = await animalRouletteService.viewBet('', gameId, winning);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;

                                const amount = winnerBet.amount * multiplyNumber;
                                totalWinningAmount += amount;
                                this.makeWinner(userId, betId, amount, comission, gameId);
                            }
                        } else {
                            console.log("No Winning Bet Found");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + ANIMAL_ROULETTE_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            admin_coin,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await animalRouletteService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.animalRoulette, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await animalRouletteService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await animalRouletteService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + ANIMAL_ROULETTE_FOR_BET) - currentTimeSec;

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
                const onlineUserCount = await animalRouletteService.getRoomOnline(roomId);
                const onlineUsers = await getByConditions({ animal_roulette_room_id: roomId });
                // Get Bets for games
                const bets = await animalRouletteService.viewBet('', gameId);
                const { tigerBetAmount, snakeBetAmount, sharkBetAmount, foxBetAmount, cheetahBetAmount, bearBetAmount, whaleBetAmount, lionBetAmount } = await animalRouletteService.getBetDataByBets(bets, 0);

                const lastWinnings = await animalRouletteService.lastWinningBet(roomId);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: onlineUserCount,
                    online_users: onlineUsers,
                    last_bet: bets[0],
                    tiger_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + tigerBetAmount,
                    snake_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + snakeBetAmount,
                    shark_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + sharkBetAmount,
                    fox_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + foxBetAmount,
                    cheetah_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + cheetahBetAmount,
                    bear_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + bearBetAmount,
                    whale_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + whaleBetAmount,
                    lion_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + lionBetAmount,
                    last_winning: lastWinnings
                }
                // console.log('responsePayload',responsePayload);
                animalRouletteService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.tiger_amount}`) });
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

module.exports = new AnimalRouletteController();