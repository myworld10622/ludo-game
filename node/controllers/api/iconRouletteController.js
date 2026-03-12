const {HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, ICON_ROULETTE_GAME, ICON_ROULETTE_FOR_BET, ICON_ROULETTE_TIME_FOR_START_NEW_GAME} = require('../../constants');
const adminService = require('../../services/adminService');
const iconRouletteService = require('../../services/iconRouletteService');
const { getAllPredefinedBots, getByConditions} = require('../../services/userService');
const { UserWalletService} = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse} = require('../../utils/response');
const db = require('../../models');
const { getAmountByPercentage, getRoundNumber, getRandomFromFromArray, getRandomNumber} = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class IconRouletteController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const {game_id, user_id, bet, amount} = req.body;
            const user = req.user;
            const setting = await adminService.setting(["icon_roullette_min_bet"]);

            if (user.wallet < setting.icon_roullette_min_bet) {
                return insufficientAmountResponse(res, setting.icon_roullette_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await iconRouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                icon_roulette_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await iconRouletteService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.iconRoulette, betData);
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
            const {game_id, user_id} = req.body;
            const game = await iconRouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await iconRouletteService.viewBet(user_id, game_id);
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
            const walletHistory = await iconRouletteService.walletHistory(user_id);
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
            iconRouletteService.updateBet(betId, gameBetPayload);

            // Update Icon Roulette
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            iconRouletteService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await iconRouletteService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.iconRoulette, gameBet);
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
            const rooms = await iconRouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await iconRouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        iconRouletteService.create({
                            room_id: room.id
                        });
                        console.log('Icon Roulette Game Created Successfully');
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
            const rooms = await iconRouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await iconRouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await iconRouletteService.viewBet('', gameId);
                        const { totalBetAmount, umbrellaBetAmount,  footballBetAmount,  sunBetAmount,  diyaBetAmount,  cowBetAmount,  bucketBetAmount,  kiteBetAmount,  topBetAmount,  roseBetAmount,  butterflyBetAmount,  pigeonBetAmount,  rabbitBetAmount} = await iconRouletteService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["icon_roulette_random", "admin_commission", "admin_coin"]);
                        const admin_coin = setting.admin_coin;
                        const random = setting.icon_roulette_random;
                        let winning;
                        // Logic for winning
                        let minNumber;
                        if (setting && random == 1) {
                            const iconArray = ['UMBRELLA', 'FOOTBALL', 'SUN', 'DIYA', 'COW', 'BUCKET', 'KITE', 'TOP', 'ROSE', 'BUTTERFLY', 'PIGEON', 'RABBIT'];
                            minNumber = iconArray[Math.floor(Math.random() * iconArray.length)];
                        } else if (setting && random == 2) {

                            if (umbrellaBetAmount == 0 && footballBetAmount == 0 && sunBetAmount == 0 && diyaBetAmount == 0 && cowBetAmount == 0 && bucketBetAmount == 0 && kiteBetAmount == 0 && topBetAmount == 0 && roseBetAmount == 0 && butterflyBetAmount == 0 && pigeonBetAmount == 0 && rabbitBetAmount == 0) {

                           const iconArray = ['UMBRELLA', 'FOOTBALL', 'SUN', 'DIYA', 'COW', 'BUCKET', 'KITE', 'TOP', 'ROSE', 'BUTTERFLY', 'PIGEON', 'RABBIT'];
                            minNumber = iconArray[Math.floor(Math.random() * iconArray.length)];

                            } else {
                               const iconArray = ['UMBRELLA', 'FOOTBALL', 'SUN', 'DIYA', 'COW', 'BUCKET', 'KITE', 'TOP', 'ROSE', 'BUTTERFLY', 'PIGEON', 'RABBIT'];
                                const optionArray = getRandomFromFromArray(iconArray, iconArray.length);
                                // console.log("optionArray",optionArray);
                                minNumber = "";
                                if (admin_coin <= 0) {
                                    minNumber = ""; // No selection if admin_coin is negative
                                } else {
                                    for (const element of optionArray) {
                                        switch (element) {
                                            case "UMBRELLA":
                                                if (umbrellaBetAmount > 0 && admin_coin >= umbrellaBetAmount) {
                                                    minNumber = "UMBRELLA";
                                                }
                                                break;
                                            case "FOOTBALL":
                                                if (footballBetAmount > 0 && admin_coin >= footballBetAmount) {
                                                    minNumber = "FOOTBALL";
                                                }
                                                break;
                                            case "SUN":
                                                if (sunBetAmount > 0 && admin_coin >= sunBetAmount) {
                                                    minNumber = "SUN";
                                                }
                                                break;
                                            case "DIYA":
                                                if (diyaBetAmount > 0 && admin_coin >= diyaBetAmount) {
                                                    minNumber = "DIYA";
                                                }
                                                break;
                                            case "COW":
                                                if (cowBetAmount > 0 && admin_coin >= cowBetAmount) {
                                                    minNumber = "COW";
                                                }
                                                break;
                                            case "BUCKET":
                                                if (bucketBetAmount > 0 && admin_coin >= bucketBetAmount) {
                                                    minNumber = "BUCKET";
                                                }
                                                break;
                                            case "KITE":
                                                if (kiteBetAmount > 0 && admin_coin >= kiteBetAmount) {
                                                    minNumber = "KITE";
                                                }
                                                break;
                                            case "TOP":
                                                if (topBetAmount > 0 && admin_coin >= topBetAmount) {
                                                    minNumber = "TOP";
                                                }
                                                break;
                                            case "ROSE":
                                                if (roseBetAmount > 0 && admin_coin >= roseBetAmount) {
                                                    minNumber = "ROSE";
                                                }
                                                break;
                                            case "BUTTERFLY":
                                                if (butterflyBetAmount > 0 && admin_coin >= butterflyBetAmount) {
                                                    minNumber = "BUTTERFLY";
                                                }
                                                break;
                                            case "PIGEON":
                                                if (pigeonBetAmount > 0 && admin_coin >= pigeonBetAmount) {
                                                    minNumber = "PIGEON";
                                                }
                                                break;
                                            case "RABBIT":
                                                if (rabbitBetAmount > 0 && admin_coin >= rabbitBetAmount) {
                                                    minNumber = "RABBIT";
                                                }
                                                break;
                                        }
                                
                                        if (minNumber !== "") {
                                            break;
                                        }
                                    }
                                }

                                if (minNumber === '') {
                                    const iconObject = {  // Use an object instead of an array
                                        UMBRELLA: umbrellaBetAmount,
                                        FOOTBALL: footballBetAmount,
                                        SUN: sunBetAmount,
                                        DIYA: diyaBetAmount,
                                        COW: cowBetAmount,
                                        BUCKET: bucketBetAmount,
                                        KITE: kiteBetAmount,
                                        TOP: topBetAmount,
                                        ROSE: roseBetAmount,
                                        BUTTERFLY: butterflyBetAmount,
                                        PIGEON: pigeonBetAmount,
                                        RABBIT: rabbitBetAmount
                                    };
                                    
                                    const minValue = Math.min(...Object.values(iconObject));
                                    
                                    // Get all keys which have the minimum value
                                    const minKeys = Object.keys(iconObject).filter(key => iconObject[key] === minValue);
                                    
                                    // Select a random one from the minimum values
                                    minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                                }
                            }

                        } else {
                            const iconObject = {  // Use an object instead of an array
                                UMBRELLA: umbrellaBetAmount,
                                FOOTBALL: footballBetAmount,
                                SUN: sunBetAmount,
                                DIYA: diyaBetAmount,
                                COW: cowBetAmount,
                                BUCKET: bucketBetAmount,
                                KITE: kiteBetAmount,
                                TOP: topBetAmount,
                                ROSE: roseBetAmount,
                                BUTTERFLY: butterflyBetAmount,
                                PIGEON: pigeonBetAmount,
                                RABBIT: rabbitBetAmount
                            };
                            
                            const minValue = Math.min(...Object.values(iconObject));
                            
                            // Get all keys which have the minimum value
                            const minKeys = Object.keys(iconObject).filter(key => iconObject[key] === minValue);
                            
                            // Select a random one from the minimum values
                            minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                        }

                        let multiplyNumber;
                        switch (minNumber) {
                            case "UMBRELLA":
                                winning = ICON_ROULETTE_GAME.umbrella;
                                multiplyNumber = ICON_ROULETTE_GAME.umbrellaMultiply;
                                break;

                            case "FOOTBALL":
                                winning = ICON_ROULETTE_GAME.football;
                                multiplyNumber = ICON_ROULETTE_GAME.footballMultiply;
                                break;

                            case "SUN":
                                winning = ICON_ROULETTE_GAME.sun;
                                multiplyNumber = ICON_ROULETTE_GAME.sunMultiply;
                                break;

                            case "DIYA":
                                winning = ICON_ROULETTE_GAME.diya;
                                multiplyNumber = ICON_ROULETTE_GAME.diyaMultiply;
                                break;

                            case "COW":
                                winning = ICON_ROULETTE_GAME.cow;
                                multiplyNumber = ICON_ROULETTE_GAME.cowMultiply;
                                break;

                            case "BUCKET":
                                winning = ICON_ROULETTE_GAME.bucket;
                                multiplyNumber = ICON_ROULETTE_GAME.bucketMultiply;
                                break;

                            case "KITE":
                                winning = ICON_ROULETTE_GAME.kite;
                                multiplyNumber = ICON_ROULETTE_GAME.kiteMultiply;
                                break;

                            case "TOP":
                                winning = ICON_ROULETTE_GAME.top;
                                multiplyNumber = ICON_ROULETTE_GAME.topMultiply;
                                break;

                            case "ROSE":
                                winning = ICON_ROULETTE_GAME.rose;
                                multiplyNumber = ICON_ROULETTE_GAME.roseMultiply;
                                break;

                            case "BUTTERFLY":
                                winning = ICON_ROULETTE_GAME.butterfly;
                                multiplyNumber = ICON_ROULETTE_GAME.butterflyMultiply;
                                break;

                            case "PIGEON":
                                winning = ICON_ROULETTE_GAME.pigeon;
                                multiplyNumber = ICON_ROULETTE_GAME.pigeonMultiply;
                                break;

                            case "RABBIT":
                                winning = ICON_ROULETTE_GAME.rabbit;
                                multiplyNumber = ICON_ROULETTE_GAME.rabbitMultiply;
                                break;

                            default:
                                winning = ICON_ROULETTE_GAME.umbrella;
                                multiplyNumber = ICON_ROULETTE_GAME.umbrellaMultiply;
                                break;
                        }

                        await iconRouletteService.createMap(gameId, winning);

                        // Give winning Amount to user
                        const winnerBets = await iconRouletteService.viewBet('', gameId, winning);
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
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + ICON_ROULETTE_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            admin_coin,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await iconRouletteService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.iconRoulette, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await iconRouletteService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await iconRouletteService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + ICON_ROULETTE_FOR_BET) - currentTimeSec;

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
                const onlineUserCount = await iconRouletteService.getRoomOnline(roomId);
                const onlineUsers = await getByConditions({ icon_roulette_room_id: roomId });
                // Get Bets for games
                const bets = await iconRouletteService.viewBet('', gameId);
                const { umbrellaBetAmount,  footballBetAmount,  sunBetAmount,  diyaBetAmount,  cowBetAmount,  bucketBetAmount,  kiteBetAmount,  topBetAmount,  roseBetAmount,  butterflyBetAmount,  pigeonBetAmount,  rabbitBetAmount } = await iconRouletteService.getBetDataByBets(bets, 0);

                const lastWinnings = await iconRouletteService.lastWinningBet(roomId);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: onlineUserCount,
                    online_users: onlineUsers,
                    last_bet: bets[0],
                    umbrella_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + umbrellaBetAmount,
                    football_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + footballBetAmount,
                    sun_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + sunBetAmount,
                    diya_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + diyaBetAmount,
                    cow_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + cowBetAmount,
                    bucket_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + bucketBetAmount,
                    kite_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + kiteBetAmount,
                    top_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + topBetAmount,
                    rose_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + roseBetAmount,
                    butterfly_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + butterflyBetAmount,
                    pigeon_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + pigeonBetAmount,
                    rabbit_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + rabbitBetAmount,
                    last_winning: lastWinnings
                }
                // console.log('responsePayload',responsePayload);
                iconRouletteService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.umbrella_amount}`) });
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

module.exports = new IconRouletteController();