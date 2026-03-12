const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, HTTP_NOT_FOUND, HTTP_NO_CONTENT, HTTP_SERVER_ERROR, ANDER_BAHER_BETS, ANDER_BAHER_WINNING_PRICE, ANDER_BAHER_FOR_BET } = require('../../constants');
const adminService = require('../../services/adminService');
const anderBaharService = require('../../services/anderBaharService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const userWallet = new UserWalletService();

class AnderBaharController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            const setting = await adminService.setting(["ander_bahar_min_bet"]);
            if (user.wallet < setting.ander_bahar_min_bet) {
                return insufficientAmountResponse(res, setting.ander_bahar_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await anderBaharService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NO_CONTENT);
            }

            const payload = {
                ander_baher_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await anderBaharService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_SERVER_ERROR);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.anderBahar, betData);
            const responseData = {
                bet_id: betData.id,
                wallet: user.wallet - amount
            }
            return successResponse(res, responseData);
        } catch (error) {
            console.log(error)
            return errorResponse(res, "Something Wents Wrong", HTTP_SERVER_ERROR);
        }
    }

    async getResult(req, res) {
        try {
            const { game_id, user_id } = req.body;
            const game = await anderBaharService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
            }
            const betData = await anderBaharService.viewAllBetsByGameId(game_id, user_id, ["user_amount", "amount"]);
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
            const walletHistory = await anderBaharService.walletHistory(user_id);
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
            const betPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            anderBaharService.updateBet(betId, betPayload);

            // Update Ander Bahar
            const gamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            anderBaharService.update(gameId, gamePayload);

            // Get Bet to check amount deducted from which wallet
            const anderBaharBet = await anderBaharService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.anderBahar, anderBaharBet);
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
            const rooms = await anderBaharService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await anderBaharService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        const card = await anderBaharService.getCards(1);
                        anderBaharService.create({ room_id: room.id, main_card: card[0].cards });
                        console.log('Ander Bahar Game Created Successfully');
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
            const rooms = await anderBaharService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await anderBaharService.getActiveGameOnTable(room.id, ["id", "status", "main_card"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const min = 6;
                        const max = 30;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await anderBaharService.viewBet('', gameId);
                        const { totalBetAmount, anderBetAmount, baherBetAmount } = await anderBaharService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["ander_bahar_random", "admin_commission", "admin_coin"]);
                        const random = setting.ander_bahar_random;
                        var winning = "";
                        // Logic for winning
                        if (setting && random == 1) {
                            winning = getRandomNumber(0, 1);
                        } else if (setting && random == 2) {

                            const admin_coin = setting.admin_coin;
                            if (anderBetAmount == 0 && baherBetAmount == 0) {
                                winning = getRandomNumber(0, 1);
                            }else{

                                const optionArr = [0,1];
                                const optionArray = getRandomFromFromArray(optionArr,optionArr.length);
                                for (const element of optionArray) {
                                    switch (element) {
                                        case 0:
                                            if(anderBetAmount>0 && admin_coin>=anderBetAmount){
                                                winning = 0;
                                            }
                                            break;

                                        case 1:
                                            if(baherBetAmount>0 && admin_coin>=baherBetAmount){
                                                winning = 1;
                                            }
                                            break;
                                    }

                                    if(winning!==""){
                                        break;
                                    }
                                }
                                if(winning===""){
                                    winning = anderBetAmount > baherBetAmount ? ANDER_BAHER_BETS.baher : ANDER_BAHER_BETS.ander;
                                }
                            }

                        } else {
                            if ((anderBetAmount > 0 || baherBetAmount > 0) && anderBetAmount != baherBetAmount) {
                                winning = anderBetAmount > baherBetAmount ? ANDER_BAHER_BETS.baher : ANDER_BAHER_BETS.ander;
                            } else {
                                winning = getRandomNumber(0, 1);
                            }
                        }

                        let valid = false;
                        let number;
                        do {
                            number = getRandomNumber(min, max);
                            if (winning === ANDER_BAHER_BETS.baher) {
                                valid = (number % 2 != 0);
                            } else {
                                valid = (number % 2 == 0);
                            }
                        } while (!valid);

                        const cardNumber = gameData[0].main_card.substring(2);
                        const middleCard = await anderBaharService.getCards(number, cardNumber);
                        const altCardObj = await anderBaharService.getCards(1, gameData[0].main_card, cardNumber);
                        const altCard = altCardObj[0].cards;

                        for (let index = 0; index < middleCard.length; index++) {
                            const element = middleCard[index];
                            await anderBaharService.createMap(gameData[0].id, element.cards);
                        }

                        await anderBaharService.createMap(gameData[0].id, altCard);

                        // Give winning Amount to user
                        const winnerBets = await anderBaharService.viewBet('', gameId, winning);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            const winnerMultiply = winning == ANDER_BAHER_BETS.ander ? ANDER_BAHER_WINNING_PRICE.ander : ANDER_BAHER_WINNING_PRICE.baher;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;
                                const amount = winnerBet.amount * winnerMultiply;
                                totalWinningAmount += amount;
                                this.makeWinner(userId, betId, amount, comission, gameId);
                            }

                        } else {
                            console.log("No Winning Bet Found");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        let seconds = Math.round(20 / 3) + 2;

                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + seconds * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await anderBaharService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.anderBahar, updatePayload.admin_profit, gameData[0].id);
                        }

                        return seconds
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
            const gameData = await anderBaharService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await anderBaharService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + ANDER_BAHER_FOR_BET) - currentTimeSec;

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
                const onlineUsers = await getByConditions({ ander_bahar_room_id: roomId });
                // Get Bets for games
                const bets = await anderBaharService.viewBet('', gameId);
                const { anderBetAmount, baherBetAmount, tieBetAmount } = await anderBaharService.getBetDataByBets(bets);

                const lastWinnings = await anderBaharService.lastWinningBet(roomId);

                // const randomAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 100)

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online_users: onlineUsers,
                    online: getRandomNumber(300, 350) + onlineUsers.length,
                    last_bet: bets[0],
                    ander_bet: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + anderBetAmount,
                    bahar_bet: getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000) + baherBetAmount,
                    last_winning: lastWinnings
                }

                // Update Random Amount
                anderBaharService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.ander_bet}`) });
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

module.exports = new AnderBaharController();