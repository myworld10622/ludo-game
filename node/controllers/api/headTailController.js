const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, HEAD_TAIL_GAME, HEAD_TAIL_TIME_FOR_START_NEW_GAME, HEAD_TAIL_FOR_BET } = require('../../constants');
const adminService = require('../../services/adminService');
const headTailService = require('../../services/headTailService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const { getCardPoints } = require('../../utils/cards');
const userWallet = new UserWalletService();

class HeadTailController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            const setting = await adminService.setting(["head_tails_min_bet"]);

            if (user.wallet < setting.head_tails_min_bet) {
                return insufficientAmountResponse(res, setting.head_tails_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await headTailService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                head_tail_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await headTailService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.headTail, betData);

            const bets = await headTailService.viewBet(user_id, game_id);
            const { headBetAmount, tailBetAmount, tieBetAmount } = await headTailService.getBetDataByBets(bets, 0);
            const responseData = {
                bet_id: betData.id,
                wallet: user.wallet - amount,
                my_head_bet: headBetAmount,
                my_tail_bet: tailBetAmount,
                my_tie_bet: tieBetAmount
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
            const game = await headTailService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await headTailService.viewBet(user_id, game_id);
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
            const walletHistory = await headTailService.walletHistory(user_id);
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
            headTailService.updateBet(betId, betPayload);

            // Update Head Tail
            const headTailPayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            headTailService.update(gameId, headTailPayload);

            // Get Bet to check amount deducted from which wallet
            const headTailBet = await headTailService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.headTail, headTailBet);
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
            const rooms = await headTailService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await headTailService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        headTailService.create({ room_id: room.id });
                        console.log('Head Tail Game Created Successfully');
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
            const rooms = await headTailService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await headTailService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await headTailService.viewBet('', gameId);
                        const { totalBetAmount, headBetAmount, tailBetAmount, tieBetAmount } = await headTailService.getBetDataByBets(bets, 1);

                        const setting = await adminService.setting(["head_tail_random", "admin_commission", "admin_coin"]);
                        const random = setting.head_tail_random;
                        var winning = "";
                        console.log("head tail random",random);
                        // Logic for winning
                        if (setting && random == 1) {
                            winning = getRandomNumber(0, 1);
                        }else if (setting && random == 2) {
                            
                            const admin_coin = setting.admin_coin;
                            if (headBetAmount == 0 && tailBetAmount == 0) {
                                winning = getRandomNumber(0, 1);
                            }else{
                                const optionArr = [0,1];
                                const optionArray = getRandomFromFromArray(optionArr,optionArr.length);
                                // console.log("head tail optionArray",optionArray);
                                for (const element of optionArray) {
                                    // console.log("head tail element",element);
                                    switch (element) {
                                        case 0:
                                            // console.log("head tail HEAD headBetAmount",headBetAmount);
                                            // console.log("head tail HEAD admin_coin",admin_coin);
                                            if(headBetAmount>0 && admin_coin>=headBetAmount){
                                                winning = 0;
                                            }
                                            break;

                                        case 1:
                                            // console.log("head tail TAIL headBetAmount",headBetAmount);
                                            // console.log("head tail TAIL admin_coin",admin_coin);
                                            if(tailBetAmount>0 && admin_coin>=tailBetAmount){
                                                winning = 1;
                                            }
                                            break;
                                    }
                                    // console.log("head tail for winning",winning);

                                    if(winning!==""){
                                        break;
                                    }
                                }
                                // console.log("head tail winning",winning);
                                if(winning===""){
                                    // console.log("inside winning ",winning);
                                    winning = headBetAmount > tailBetAmount ? HEAD_TAIL_GAME.tail : HEAD_TAIL_GAME.head
                                }
                                // console.log("head tail winning -",winning);
                            }

                        } else {
                            if (headBetAmount > 0 || tailBetAmount > 0) {
                                winning = headBetAmount > tailBetAmount ? HEAD_TAIL_GAME.tail : HEAD_TAIL_GAME.head
                            } else {
                                winning = getRandomNumber(0, 1);
                            }
                        }

                        // console.log("head tail winning final -",winning);

                        const cards = await headTailService.getCards(2);
                        const card1Point = getCardPoints(cards[0].cards);
                        const card2Point = getCardPoints(cards[1].cards);

                        let smallCard = '';
                        let bigCard = '';
                        if (card1Point > card2Point) {
                            bigCard = cards[0].cards
                            smallCard = cards[1].cards;
                        } else {
                            bigCard = cards[1].cards
                            smallCard = cards[0].cards;
                        }

                        const cardHead = winning == HEAD_TAIL_GAME.head ? bigCard : smallCard;
                        const cardTail = winning == HEAD_TAIL_GAME.tail ? bigCard : smallCard;
                        // const winningNumber = (winning == HEAD_TAIL_GAME.head) ? getRandomNumber(2, 6) : ((winning == HEAD_TAIL_GAME.tail) ? getRandomNumber(8, 12) : 7);

                        // Crate Mapping
                        await headTailService.createMap(gameId, cardHead);
                        await headTailService.createMap(gameId, cardTail);

                        // Give winning Amount to user
                        const winnerBets = await headTailService.viewBet('', gameId, winning);
                        // console.log("winning", winning);
                        // console.log("winnerBets", winnerBets);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;
                                const amount = winnerBet.amount * HEAD_TAIL_GAME.headTailMultiply;
                                totalWinningAmount += amount;
                                this.makeWinner(userId, betId, amount, comission, gameId);
                            }

                        } else {
                            console.log("No Winning Bet Found Head Tail");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + HEAD_TAIL_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        headTailService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.headTail, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await headTailService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await headTailService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + HEAD_TAIL_FOR_BET) - currentTimeSec;

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
                // const onlineUserCount = await headTailService.getRoomOnline(roomId);
                const onlineUsers = await getByConditions({ head_tail_room_id: roomId });
                // Get Bets for games
                const bets = await headTailService.viewBet('', gameId);
                const { headBetAmount, tailBetAmount, tieBetAmount } = await headTailService.getBetDataByBets(bets);

                const lastWinnings = await headTailService.lastWinningBet(roomId);

                const randomHeadAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const randomTailAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const randomTieAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)

                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    // online: onlineUserCount,
                    online_users: onlineUsers,
                    last_bet: bets[0],
                    my_head_bet: headBetAmount,
                    my_tail_bet: tailBetAmount,
                    my_tie_bet: tieBetAmount,
                    head_bet: headBetAmount + randomHeadAmount,
                    tail_bet: tailBetAmount + randomTailAmount,
                    tie_bet: tieBetAmount + randomTieAmount,
                    last_winning: lastWinnings
                }

                // Update Random Amount
                headTailService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.head_bet}`) });
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

module.exports = new HeadTailController();