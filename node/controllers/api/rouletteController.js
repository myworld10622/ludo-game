const { GAMES, HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, ROULETTE_NUMBER_MULTIPLY, ROULETTE_COLOR_MULTIPLY, ROULETTE_ODD_EVEN_MULTIPLY, ROULETTE_TWELTH_COLUMN_MULTIPLY, ROULETTE_EIGHTEEN_COLUMN_MULTIPLY, ROULETTE_ROW_MULTIPLY, ROULETTE_TWO_SPLIT_MULTIPLY, ROULETTE_FOUR_SPLIT_MULTIPLY, ROULETTE_TIME_FOR_START_NEW_GAME, HTTP_INSUFFIENT_PAYMENT, HTTP_NOT_FOUND, HTTP_ALREADY_USED, HTTP_NO_CONTENT, HTTP_SERVER_ERROR, ROULETTE_TIME_FOR_BET, NUM_0_36, ROULETTE_BETS } = require("../../constants");
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require("../../utils/response");
const rouletteService = require('../../services/rouletteService');
const userService = require('../../services/userService');
const { UserWalletService } = require("../../services/walletService");
const { getRandomNumber, getRoundNumber, getAmountByPercentage, getRandomFromFromArray } = require("../../utils/util");
const adminService = require("../../services/adminService");
var dateFormat = require('date-format');
const db = require("../../models");
const userWallet = new UserWalletService();

class RouletteController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }
    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            // if (user.wallet < 100) {
            //     return errorResponse(res, "Required Minimum 100 Coins to Play", HTTP_NOT_ACCEPTABLE);
            // }
            if (user.wallet < amount && amount > 0) {
                // return errorResponse(res, "Insufficient Wallet Amount", HTTP_INSUFFIENT_PAYMENT);
                return insufficientAmountResponse(res);
            }
            // game
            const game = await rouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_FOUND);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NO_CONTENT);
            }

            const checkBet = await rouletteService.checkBetNumberPlacedByUser(game_id, user_id, bet);
            if (checkBet) {
                return errorResponse(res, "Already Added Bet On Same Number", HTTP_ALREADY_USED);
            }
            const payload = {
                roulette_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await rouletteService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_SERVER_ERROR);
            }
            rouletteService.updateTempBet(game_id, bet, amount);
            // rouletteService.createTempBet(game_id);
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.roulette, betData);

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
            const game = await rouletteService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await rouletteService.viewBet(user_id, game_id);
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
            const walletHistory = await rouletteService.walletHistory(user_id);
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
            const rouletteBetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            rouletteService.updateBet(betId, rouletteBetPayload);

            // Update Dragon Tiger
            const roulettePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            rouletteService.update(gameId, roulettePayload);

            // Get Bet to check amount deducted from which wallet
            const rouletteBet = await rouletteService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.roulette, rouletteBet);
        } catch (error) {
            console.log(error);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// SOCKET FUNCTIONS /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    // Create Game From Socket
    async createRoultteGame() {
        try {
            const rooms = await rouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await rouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        const game = await rouletteService.create({ room_id: room.id });
                        rouletteService.createTempBet(game.id);
                        console.log('First Roulette Created Successfully');
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
            const rooms = await rouletteService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await rouletteService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }
                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;

                        // const bets = await dragonTigerService.viewBet('', gameId);
                        const totalBetAmount = await rouletteService.totalBetAmount(gameId);

                        const setting = await adminService.setting(["roulette_random", "admin_commission", "admin_coin"]);
                        const random = setting.roulette_random;
                        let winning = "";
                        // Logic for winning
                        if (setting && random == 1) {
                            // get random value from 0 to 36 and make winner
                            winning = getRandomNumber(0, 36);
                        } else if (setting && random == 2) {
                            const admin_coin = setting.admin_coin;
                            const rouletteNumbers = [...NUM_0_36];

                            let eligibleNumbers = [];
                            let zeroBetNumbers = [];
                            let minBetAmount = Number.MAX_SAFE_INTEGER;
                            let minBetNumber = null;

                            // Function to get the total winning amount for a number considering all bets
                            const calculateWinningAmount = async (gameId, number) => {
                                let totalWinningAmount = 0;
                                
                                for (const [betType, betNumbers] of Object.entries(ROULETTE_BETS)) {
                                    if (betNumbers.includes(number)) {
                                        const betAmount = await rouletteService.totalBetAmount(gameId, betType);

                                        let multiplier = 0;
                                        if (betType >= 0 && betType <= 36) multiplier = ROULETTE_NUMBER_MULTIPLY;
                                        else if (betType === 44 || betType === 45) multiplier = ROULETTE_COLOR_MULTIPLY;
                                        else if (betType === 42 || betType === 43) multiplier = ROULETTE_ODD_EVEN_MULTIPLY;
                                        else if (betType === 37 || betType === 38 || betType === 39) multiplier = ROULETTE_TWELTH_COLUMN_MULTIPLY;
                                        else if (betType === 40 || betType === 41) multiplier = ROULETTE_EIGHTEEN_COLUMN_MULTIPLY;
                                        else if (betType === 46 || betType === 47 || betType === 48) multiplier = ROULETTE_ROW_MULTIPLY;
                                        else if (betType >= 49 && betType <= 108) multiplier = ROULETTE_TWO_SPLIT_MULTIPLY;
                                        else if (betType >= 109 && betType <= 132) multiplier = ROULETTE_FOUR_SPLIT_MULTIPLY;

                                        totalWinningAmount += betAmount * multiplier;
                                    }
                                }

                                return totalWinningAmount;
                            };

                            // Process all numbers and categorize them based on priority
                            for (const number of rouletteNumbers) {
                                const approx_winning_amount = await calculateWinningAmount(gameId, number);

                                if (approx_winning_amount > 0 && approx_winning_amount <= admin_coin) {
                                    eligibleNumbers.push(number); // First priority numbers
                                } 
                                else if (approx_winning_amount === 0) {
                                    zeroBetNumbers.push(number); // Second priority numbers
                                }

                                // Track the minimum payout number in case all other options fail
                                if (approx_winning_amount < minBetAmount) {
                                    minBetAmount = approx_winning_amount;
                                    minBetNumber = number;
                                }
                            }

                            // **First Priority**: Pick a random number from eligibleNumbers
                            if (eligibleNumbers.length > 0) {
                                winning = eligibleNumbers[Math.floor(Math.random() * eligibleNumbers.length)];
                            } 
                            // **Second Priority**: If no eligible numbers, pick from zeroBetNumbers
                            else if (zeroBetNumbers.length > 0) {
                                winning = zeroBetNumbers[Math.floor(Math.random() * zeroBetNumbers.length)];
                            } 
                            // **Third Priority**: If no zero-bet numbers, pick the minimum payout number
                            else {
                                winning = minBetNumber;
                            }
                        } else {
                            // if random is off then get all bet on which lowest bet is added and if number are multiple then pic random number out of this
                            const tempBets = await rouletteService.getMinimumTempBet(gameId);
                            winning = await rouletteService.getLeastBets(tempBets);
                            // winning = getRandomNumber(0, (tempBets.length - 1));
                        }

                        const { color, oddEven, twelthColumn, eighteenColumn, row, split2, split4 } = await rouletteService.getWinnerNumbersByBet(winning);

                        // Crate Mapping for winner
                        await rouletteService.createMap(gameId, winning);
                        const comission = setting.admin_commission;

                        const bets = await rouletteService.viewBet('', gameId, winning);
                        for (let index = 0; index < bets.length; index++) {
                            const bet = bets[index];
                            const amount = bet.amount * ROULETTE_NUMBER_MULTIPLY;
                            totalWinningAmount += amount;
                            this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                        }

                        if (color) {
                            const bets = await rouletteService.viewBet('', gameId, color);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_COLOR_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        if (oddEven) {
                            const bets = await rouletteService.viewBet('', gameId, oddEven);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_ODD_EVEN_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        if (twelthColumn) {
                            const bets = await rouletteService.viewBet('', gameId, twelthColumn);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_TWELTH_COLUMN_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        if (eighteenColumn) {
                            const bets = await rouletteService.viewBet('', gameId, eighteenColumn);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_EIGHTEEN_COLUMN_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        if (row) {
                            const bets = await rouletteService.viewBet('', gameId, row);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_ROW_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        if (split2 && Array.isArray(split2) && split2.length > 0) {
                            const bets = await rouletteService.viewBet('', gameId, split2);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_TWO_SPLIT_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        if (split4 && Array.isArray(split4) && split4.length > 0) {
                            const bets = await rouletteService.viewBet('', gameId, split4);
                            for (let index = 0; index < bets.length; index++) {
                                const bet = bets[index];
                                const amount = bet.amount * ROULETTE_FOUR_SPLIT_MULTIPLY;
                                totalWinningAmount += amount;
                                this.makeWinner(bet.user_id, bet.id, amount, comission, gameId);
                            }
                        }

                        const now = new Date();
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + ROULETTE_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await rouletteService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.roulette, updatePayload.admin_profit, gameData[0].id);
                        }

                        rouletteService.clearTempBetEntries(gameId);

                        return updatePayload
                    }
                }
            }
        } catch (error) {
            console.log(error)
        }
    }

    // Call from socket
    async getActiveGame(roomId) {
        try {
            // const totalBetHeart = req.body.total_bet_heart || 0;
            // const totalBetSpade = req.body.total_bet_spade || 0;
            // const totalBetDiamond = req.body.total_bet_diamond || 0;
            // const totalBetClub = req.body.total_bet_club || 0;
            // const totalBetFace = req.body.total_bet_face || 0;
            // const totalBetFlag = req.body.total_bet_flag || 0;

            const botUsers = await userService.getAllPredefinedBots();
            const gameData = await rouletteService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "end_datetime", "updated_date"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await rouletteService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + ROULETTE_TIME_FOR_BET) - currentTimeSec;

                const newGameData = [
                    {
                        id: gameId,
                        room_id: gameData[0].room_id,
                        // main_card: gameData[0].main_card,
                        winning: gameData[0].winning,
                        status: gameData[0].status,
                        added_date: gameData[0].added_date,
                        time_remaining: timeRemaining,
                        end_datetime: gameData[0].end_datetime,
                        updated_date: gameData[0].updated_date
                    }
                ];

                // Get Online Users
                // const onlineUsers = await getByConditions({ dragon_tiger_room_id: roomId });
                const online = await rouletteService.getRoomOnlineUsers(newGameData[0].room_id);
                // Get Bets for games
                const bets = await rouletteService.viewBet('', gameId, '', '', 1);

                const lastWinners = await rouletteService.lastWinningBet(roomId, 15);

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online,
                    last_bet: bets[0],
                    last_winning: lastWinners
                }
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

module.exports = new RouletteController();