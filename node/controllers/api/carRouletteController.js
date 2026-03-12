const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, CAR_ROULETTE_GAME, CAR_ROULETTE_FOR_BET, ANIMAL_ROULETTE_TIME_FOR, CAR_ROULETTE_GAME_START_NEW_GAME, CAR_ROULETTE_TIME_FOR_START_NEW_GAME } = require('../../constants');
const adminService = require('../../services/adminService');
const carRouletteService = require('../../services/carRouletteService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getAmountByPercentage, getRoundNumber, getRandomFromFromArray, getRandomNumber } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class CarRouletteController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            const setting = await adminService.setting(["car_roullette_min_bet"]);

            if (user.wallet < setting.car_roullette_min_bet) {
                return insufficientAmountResponse(res, setting.car_roullette_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await carRouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                car_roulette_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await carRouletteService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.carRoulette, betData);
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
            const game = await carRouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await carRouletteService.viewBet(user_id, game_id);
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
            const walletHistory = await carRouletteService.walletHistory(user_id);
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
            carRouletteService.updateBet(betId, gameBetPayload);

            // Update Car Roulette
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            carRouletteService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const gameBet = await carRouletteService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.carRoulette, gameBet);
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
            const rooms = await carRouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await carRouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        carRouletteService.create({ room_id: room.id });
                        console.log('Car Roulette Game Created Successfully');
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
            const rooms = await carRouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await carRouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await carRouletteService.viewBet('', gameId);
                        const { totalBetAmount, toyotaBetAmount, mahindraBetAmount, audiBetAmount, bmwBetAmount, mercedesBetAmount, porscheBetAmount, lamborghiniBetAmount, ferrariBetAmount } = await carRouletteService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["car_roulette_random", "admin_commission", "admin_coin"]);
                        const random = setting.car_roulette_random;
                        var winning = "";
                        // Logic for winning
                        // console.log("random",random);
                        var minNumber;
                        if (setting && random == 1) {
                            const animalArray = ['TOYOTA', 'MAHINDRA', 'AUDI', 'BMW', 'MERCEDES', 'PORSCHE', 'LAMBORGHINI', 'FERRARI'];
                            minNumber = animalArray[Math.floor(Math.random() * animalArray.length)];
                        }else if (setting && random == 2) {
                            const admin_coin = setting.admin_coin;

                            if(toyotaBetAmount==0 && mahindraBetAmount==0 && audiBetAmount==0 && bmwBetAmount==0 && mercedesBetAmount==0 && porscheBetAmount==0 && lamborghiniBetAmount==0 && ferrariBetAmount==0){

                                const animalArray = ['TOYOTA', 'MAHINDRA', 'AUDI', 'BMW', 'MERCEDES', 'PORSCHE', 'LAMBORGHINI', 'FERRARI'];
                                minNumber = animalArray[Math.floor(Math.random() * animalArray.length)];

                            }else{
                                const animalArray = ['TOYOTA', 'MAHINDRA', 'AUDI', 'BMW', 'MERCEDES', 'PORSCHE', 'LAMBORGHINI', 'FERRARI'];

                                const optionArray = getRandomFromFromArray(animalArray,animalArray.length);
                                // console.log("optionArray",optionArray);
                                minNumber = "";
                                for (const element of optionArray) {
                                    // console.log("element",element);
                                    switch (element) {
                                        case "TOYOTA":
                                            if(toyotaBetAmount>0 && admin_coin>=toyotaBetAmount){
                                                minNumber = "TOYOTA";
                                            }
                                            break;
            
                                        case "MAHINDRA":
                                            if(mahindraBetAmount>0 && admin_coin>=mahindraBetAmount){
                                                minNumber = "MAHINDRA";
                                            }
                                            break;
            
                                        case "AUDI":
                                            if(audiBetAmount>0 && admin_coin>=audiBetAmount){
                                                minNumber = "AUDI";
                                            }
                                            break;
            
                                        case "BMW":
                                            // console.log("bmwBetAmount",bmwBetAmount);
                                            // console.log("admin_coin",admin_coin);
                                            if(bmwBetAmount>0 && admin_coin>=bmwBetAmount){
                                                minNumber = "BMW";
                                            }
                                            break;
            
                                        case "MERCEDES":
                                            if(mercedesBetAmount>0 && admin_coin>=mercedesBetAmount){
                                                minNumber = "MERCEDES";
                                            }
                                            break;
            
                                        case "PORSCHE":
                                            if(porscheBetAmount>0 && admin_coin>=porscheBetAmount){
                                                minNumber = "PORSCHE";
                                            }
                                            break;
            
                                        case "LAMBORGHINI":
                                            if(lamborghiniBetAmount>0 && admin_coin>=lamborghiniBetAmount){
                                                minNumber = "LAMBORGHINI";
                                            }
                                            break;
            
                                        case "FERRARI":
                                            if(ferrariBetAmount>0 && admin_coin>=ferrariBetAmount){
                                                minNumber = "FERRARI";
                                            }
                                            break;
                                    }

                                    if(minNumber!==""){
                                        break;
                                    }
                                }
                                // console.log("minNumber",minNumber);
                                if(minNumber===''){
                                    const animalArray = [];
                                    animalArray['TOYOTA'] = toyotaBetAmount;
                                    animalArray['MAHINDRA'] = mahindraBetAmount;
                                    animalArray['AUDI'] = audiBetAmount;
                                    animalArray['BMW'] = bmwBetAmount
                                    animalArray['MERCEDES'] = mercedesBetAmount;
                                    animalArray['PORSCHE'] = porscheBetAmount;
                                    animalArray['LAMBORGHINI'] = lamborghiniBetAmount;
                                    animalArray['FERRARI'] = ferrariBetAmount;

                                    const minValue = Math.min(...Object.values(animalArray));
                                    // Get all keys which have minimum value
                                    const minKeys = Object.keys(animalArray).filter(key => animalArray[key] === minValue);
                                    // select a random from minimum
                                    minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                                }
                            }

                        } else {
                            const animalArray = [];
                            animalArray['TOYOTA'] = toyotaBetAmount;
                            animalArray['MAHINDRA'] = mahindraBetAmount;
                            animalArray['AUDI'] = audiBetAmount;
                            animalArray['BMW'] = bmwBetAmount
                            animalArray['MERCEDES'] = mercedesBetAmount;
                            animalArray['PORSCHE'] = porscheBetAmount;
                            animalArray['LAMBORGHINI'] = lamborghiniBetAmount;
                            animalArray['FERRARI'] = ferrariBetAmount;

                            const minValue = Math.min(...Object.values(animalArray));
                            // Get all keys which have minimum value
                            const minKeys = Object.keys(animalArray).filter(key => animalArray[key] === minValue);
                            // select a random from minimum
                            minNumber = minKeys[Math.floor(Math.random() * minKeys.length)];
                        }

                        let multiplyNumber;
                        switch (minNumber) {
                            case "TOYOTA":
                                winning = CAR_ROULETTE_GAME.toyota;
                                multiplyNumber = CAR_ROULETTE_GAME.toyotaMultiply;
                                break;

                            case "MAHINDRA":
                                winning = CAR_ROULETTE_GAME.mahindra;
                                multiplyNumber = CAR_ROULETTE_GAME.mahindraMultiply;
                                break;

                            case "AUDI":
                                winning = CAR_ROULETTE_GAME.audi;
                                multiplyNumber = CAR_ROULETTE_GAME.audiMultiply;
                                break;

                            case "BMW":
                                winning = CAR_ROULETTE_GAME.bmw;
                                multiplyNumber = CAR_ROULETTE_GAME.bmwMultiply;
                                break;

                            case "MERCEDES":
                                winning = CAR_ROULETTE_GAME.mercedes;
                                multiplyNumber = CAR_ROULETTE_GAME.mercedesMultiply;
                                break;

                            case "PORSCHE":
                                winning = CAR_ROULETTE_GAME.porsche;
                                multiplyNumber = CAR_ROULETTE_GAME.porscheMultiply;
                                break;

                            case "LAMBORGHINI":
                                winning = CAR_ROULETTE_GAME.lamborghini;
                                multiplyNumber = CAR_ROULETTE_GAME.lamborghiniMultiply;
                                break;

                            case "FERRARI":
                                winning = CAR_ROULETTE_GAME.ferrari;
                                multiplyNumber = CAR_ROULETTE_GAME.ferrariMultiply;
                                break;

                            default:
                                winning = CAR_ROULETTE_GAME.toyota;
                                multiplyNumber = CAR_ROULETTE_GAME.toyotaMultiply;
                                break;
                        }

                        await carRouletteService.createMap(gameId, winning);

                        // Give winning Amount to user
                        const winnerBets = await carRouletteService.viewBet('', gameId, winning);
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
                            console.log("No Winning Bet Found Car Roulette");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + CAR_ROULETTE_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await carRouletteService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.carRoulette, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await carRouletteService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await carRouletteService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + CAR_ROULETTE_FOR_BET) - currentTimeSec;

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
                const onlineUserCount = await carRouletteService.getRoomOnline(roomId);
                const onlineUsers = await getByConditions({ animal_roulette_room_id: roomId });
                // Get Bets for games
                const bets = await carRouletteService.viewBet('', gameId);
                const { toyotaBetAmount, mahindraBetAmount, audiBetAmount, bmwBetAmount, mercedesBetAmount, porscheBetAmount, lamborghiniBetAmount, ferrariBetAmount } = await carRouletteService.getBetDataByBets(bets, 0);

                const lastWinnings = await carRouletteService.lastWinningBet(roomId);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: onlineUserCount,
                    online_users: onlineUsers,
                    last_bet: bets[0],
                    toyota_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + toyotaBetAmount,
                    mahindra_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + mahindraBetAmount,
                    audi_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + audiBetAmount,
                    bmw_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + bmwBetAmount,
                    ferrari_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + mercedesBetAmount,
                    mercedes_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + porscheBetAmount,
                    porsche_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + lamborghiniBetAmount,
                    lamborghini_amount: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + ferrariBetAmount,
                    last_winning: lastWinnings
                }
                carRouletteService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.toyota_amount}`) });
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

module.exports = new CarRouletteController();