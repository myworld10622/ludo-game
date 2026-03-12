const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, SLOT_WIN, SLOT_LOSS, SLOT_TIME_FOR_START_NEW_GAME, SLOT_TIME_FOR_BET } = require('../../constants');
const adminService = require('../../services/adminService');
const slotGameService = require('../../services/slotGameService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
var dateFormat = require('date-format');
const db = require('../../models');
const errorHandler = require('../../error/errorHandler');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray, shuffleArray } = require('../../utils/util');
const userWallet = new UserWalletService();

const paylines = [
    [0, 1, 2, 3, 4],
    [5, 6, 7, 8, 9],
    [10, 11, 12, 13, 14],
    [0, 6, 12, 8, 4],
    [10, 6, 2, 8, 14]
];

const paytable = {
    "A": { 3: 5, 4: 15, 5: 50 },
    "B": { 3: 5, 4: 15, 5: 75 },
    "C": { 3: 5, 4: 20, 5: 100 },
    // "D": { 3: 5, 4: 25, 5: 125 },
    // "E": { 3: 5, 4: 30, 5: 150 },
    // "F": { 3: 5, 4: 25, 5: 125 },
    // "G": { 3: 5, 4: 25, 5: 125 },
    // "H": { 3: 5, 4: 25, 5: 125 },
    // "I": { 3: 5, 4: 25, 5: 125 },
    // "J": { 3: 5, 4: 25, 5: 125 }
};

const symbols = ["A", "B", "C"];

class SlotGameController {
    constructor() {
        this.startGame = this.startGame.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.getResult = this.getResult.bind(this);
        this.makeWinner = this.makeWinner.bind(this);
        this.declareSlotWinner = this.declareSlotWinner.bind(this);
    }

    async createSlotGame() {
        try {
            const rooms = await slotGameService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await slotGameService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        const game = await slotGameService.create({ room_id: room.id });
                        console.log('Slot Game Created Successfully');
                        return game.id;
                    }
                }
            } else {
                console.log('No Rooms Available');
            }
        } catch (error) {
            console.log(error);
        }
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;

            // const setting = await adminService.setting(["slot_game_min_bet"]);

            // if (user.wallet < setting.slot_game_min_bet) {
            //     return insufficientAmountResponse(res, setting.slot_game_min_bet);
            // }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }

            const game = await slotGameService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                slot_game_id: game_id,
                user_id,
                bet,
                amount
            };

            const betData = await slotGameService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Went Wrong", HTTP_NOT_ACCEPTABLE);
            }

            userWallet.minusUserWallet(user_id, amount, GAMES.slotGame, betData);
            const responseData = {
                bet_id: betData.id,
                wallet: user.wallet - amount
            };
            return successResponse(res, responseData);
        } catch (error) {
            console.error(error);
            errorHandler.handle(error);
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async getResult(req, res) {
        try {
            const { game_id, user_id } = req.body;
            const game = await slotGameService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await slotGameService.viewBet(user_id, game_id);
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
                wallet: req.user.wallet,
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

    async makeWinner(userId, betId, amount, comission, gameId) {
        try {
            const adminComissionAmount = await getAmountByPercentage(amount, comission);
            const userWinningAmount = getRoundNumber(amount - adminComissionAmount, 2);
            const slotGameBetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }

            slotGameService.updateBet(betId, slotGameBetPayload);

            const slotGamePayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            slotGameService.update(gameId, slotGamePayload);

            const slotGameBet = await slotGameService.getBetById(betId);
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.slotGame, slotGameBet);
        } catch (error) {
            console.log(error);
        }
    }

    async startGame() {
        try {
            const games = await slotGameService.getRooms("", "", ["id"]);
            if (games && Array.isArray(games) && games.length > 0) {
                for (let index = 0; index < games.length; index++) {
                    const gameData = await slotGameService.getActiveGameOnTable(games[index].id, ["id", "status"]);
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        slotGameService.create({ room_id: games[index].id });
                        console.log('Slot Game Created Successfully');
                        return;
                    }
                }
            } else {
                console.log('No Games Available');
            }
        } catch (error) {
            console.log(error);
        }
    }

    async declareSlotWinner() {
        try {
            const games = await slotGameService.getRooms("", "", ["id"]);
            if (games && Array.isArray(games) && games.length > 0) {
                for (let index = 0; index < games.length; index++) {
                    const gameData = await slotGameService.getActiveGameOnTable(games[index].id, ["id", "status"]);
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }
    
                    if (gameData[0].status === 0) {
                        const gameId = gameData[0].id;
                        const bets = await slotGameService.viewBet('', gameId);
    
                        const setting = await adminService.setting(["slot_game_random", "admin_commission", "admin_coin"]);

                        let totalWinningAmount = 0;
                        let totalWinningMultiply = 0;
    
                        const { totalBetAmount } = await slotGameService.getBetDataByBets(bets);
    
                        var reelGrid = '';
                        if(totalBetAmount>0){
                            let per_line_bet = (totalBetAmount/paylines.length).toFixed(2);
                            console.log('per_line_bet -- ',per_line_bet);
                            let multiplyToBe = (setting.admin_coin/per_line_bet);
                            console.log('multiplyToBe -- ',multiplyToBe);
                            if(multiplyToBe>=paylines.length*100){
                                multiplyToBe = Math.floor(Math.random() * ((paylines.length*50) - 50 + 1)) + 50;
                                console.log('multiplyToBe if more than 100 ---- ',multiplyToBe);
                            }
                            reelGrid = this.generateReelGrid(paylines, paytable, multiplyToBe);
                        }else{
                            reelGrid = this.generateRandomReelGrid();
                        }

                        await slotGameService.createMap(gameData[0].id, JSON.stringify(reelGrid));
    
                        const { winnings } = this.calculatePaylineWins(reelGrid, paylines, paytable, setting.admin_coin);
    
                        winnings.forEach(win => {
                            totalWinningMultiply += win.multiply;
                        });

                        if (Array.isArray(bets) && bets.length > 0 && Array.isArray(winnings) && winnings.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < bets.length; j++) {
                                const winnerBet = bets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;

                                const amount = (winnerBet.amount/paylines.length) * totalWinningMultiply;
                                totalWinningAmount += amount;
                                this.makeWinner(userId, betId, amount, comission, gameId);
                            }

                        } else {
                            console.log("No Winning Bet Found Slot Game");
                        }
    
                        const adminProfit = totalBetAmount - totalWinningAmount;
    
                        const now = new Date();
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + SLOT_TIME_FOR_START_NEW_GAME * 1000));
    
                        const updatePayload = {
                            status: 1,
                            winning: winnings.length > 0 ? JSON.stringify(winnings) : SLOT_LOSS,
                            total_amount: totalBetAmount,
                            admin_profit: adminProfit,
                            end_datetime: endDateTime,
                            reel_grid: reelGrid,
                            winnings: winnings
                        };
    
                        await slotGameService.update(gameData[0].id, updatePayload);
    
                        if (adminProfit !== 0) {
                            userWallet.directAdminProfitStatement(GAMES.slotGame, adminProfit, gameData[0].id);
                        }
    
                        return updatePayload;
                    }
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    generateReelGrid(paylines, paytable, multiplyToBe) {
        const reelGrid = new Array(15).fill(null); // Initialize reel grid with 15 empty slots
        const symbols = Object.keys(paytable); // Extract available symbols from the paytable
        let totalWinnings = 0;
    
        // Helper function to calculate winnings for the current grid
        const calculateWinnings = (grid) => {
            let winnings = 0;
            for (const payline of paylines) {
                const lineSymbols = payline.map(idx => grid[idx]);
                const firstSymbol = lineSymbols[0];
    
                if (!firstSymbol) continue;
    
                let count = 0;
                for (let i = 0; i < lineSymbols.length; i++) {
                    if (lineSymbols[i] === firstSymbol) {
                        count++;
                    } else {
                        break;
                    }
                }
    
                if (paytable[firstSymbol] && paytable[firstSymbol][count]) {
                    const winAmount = paytable[firstSymbol][count];
                    winnings += winAmount;
                }
            }
            return winnings;
        };
    
        // Fill the reel grid while respecting multiplyToBe
        for (let i = 0; i < reelGrid.length; i++) {
            let selectedSymbol = symbols[Math.floor(Math.random() * symbols.length)]; // Randomly select a symbol
            reelGrid[i] = selectedSymbol;
    
            // Calculate winnings for the current grid
            totalWinnings = calculateWinnings(reelGrid);
    
            // If multiplyToBe is positive, ensure total winnings don't exceed it
            if (multiplyToBe > 0 && totalWinnings > multiplyToBe) {
                console.log(`Winnings exceed the limit (${multiplyToBe}). Adjusting last symbol...`);
    
                // Adjust the grid to ensure winnings don't exceed multiplyToBe
                for (let j = i; j >= 0; j--) {
                    for (const symbol of symbols) {
                        reelGrid[j] = symbol;
                        const newWinnings = calculateWinnings(reelGrid);
    
                        // Check if the new winnings are within the allowed limit
                        if (newWinnings <= multiplyToBe) {
                            totalWinnings = newWinnings;
                            break;
                        }
                    }
                    if (totalWinnings <= multiplyToBe) break; // Stop adjusting if winnings are within the limit
                }
            }
    
            // Ensure total winnings are never negative
            if (totalWinnings < 0) {
                totalWinnings = 0;
            }
        }
    
        console.log('Final reel grid:', reelGrid);
        return reelGrid;
    }

    generateRandomReelGrid() {
        const reelGrid = [];
        for (let i = 0; i < 15; i++) {
            reelGrid.push(symbols[Math.floor(Math.random() * symbols.length)]);
        }
        return reelGrid;
    }

    calculatePaylineWins(reelGrid, paylines, paytable) {
        const winnings = [];
        let totalWinnings = 0;
    
        for (const [index, payline] of paylines.entries()) {
            const lineSymbols = payline.map(idx => reelGrid[idx]);
            const firstSymbol = lineSymbols[0]; // Take the first symbol in the line as the base symbol
    
            if (!firstSymbol) continue; // Skip empty positions
    
            let count = 0;
            for (let i = 0; i < lineSymbols.length; i++) {
                if (lineSymbols[i] === firstSymbol) {
                    count++;
                } else {
                    break;
                }
            }
    
            if (paytable[firstSymbol] && paytable[firstSymbol][count]) {
                const multiplier = paytable[firstSymbol][count];
                winnings.push({ payline: index, symbol: firstSymbol, multiply: multiplier, count: count });
                totalWinnings += multiplier;
            }
        }
    
        return { winnings, totalWinnings };
    }

    async getSlotGameStatus(roomId) {
        try {
            // const botUsers = await getAllPredefinedBots();
            const gameData = await slotGameService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "end_datetime", "updated_date", "reel_grid", "winnings"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await slotGameService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + SLOT_TIME_FOR_BET) - currentTimeSec;

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
                        updated_date: gameData[0].updated_date,
                        reel_grid: gameData[0].reel_grid,
                        winnings: gameData[0].winnings
                    }
                ];

                // Get Online Users
                const onlineUsers = await getByConditions({ slot_game_room_id: roomId });
                // Get Bets for games
                const bets = await slotGameService.viewBet('', gameId);
                const { dragonBetAmount, tigerBetAmount, tieBetAmount } = await slotGameService.getBetDataByBets(bets, 0);

                const lastWinnings = await slotGameService.lastWinningBet(roomId);

                const randomDrgonAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const randomTgerAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const randomTieAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const responsePayload = {
                    // bot_user: botUsers,
                    paylines: paylines,
                    paytable: paytable,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: getRandomNumber(300, 350) + onlineUsers.length,
                    last_bet: bets[0],
                    // my_dragon_bet: dragonBetAmount,
                    // my_tiger_bet: tigerBetAmount,
                    // my_tie_bet: tieBetAmount,
                    // dragon_bet: randomDrgonAmount + dragonBetAmount,
                    // tiger_bet: randomTgerAmount + tigerBetAmount,
                    // tie_bet: randomTieAmount + tieBetAmount,
                    last_winning: lastWinnings
                }

                // Update Random Amount
                // slotGameService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.dragon_bet}`) });
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

    async makeWinner(userId, betId, amount, comission, gameId) {
        try {
            const adminComissionAmount = await getAmountByPercentage(amount, comission);
            const userWinningAmount = getRoundNumber(amount - adminComissionAmount, 2);
            const BetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            slotGameService.updateBet(betId, BetPayload);

            // Update Dragon Tiger
            const Payload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            slotGameService.update(gameId, Payload);

            // Get Bet to check amount deducted from which wallet
            const Bet = await slotGameService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.slotGame, Bet);
        } catch (error) {
            console.log(error);
        }
    }

    async walletHistory(req, res) {
        try {
            const { user_id } = req.body;
            const walletHistory = await slotGameService.walletHistory(user_id);
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
}

module.exports = new SlotGameController();
