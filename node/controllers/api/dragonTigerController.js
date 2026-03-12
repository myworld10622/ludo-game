const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES, DRAGON, TIGER, TIE, DRAGON_OR_TIGET_MULTIPLY, TIE_MULTIPLY, DRAGON_TIME_FOR_START_NEW_GAME, DRAGON_TIME_FOR_BET } = require('../../constants');
const adminService = require('../../services/adminService');
const dragonTigerService = require('../../services/dragonTigerService');
const { getAllPredefinedBots, getByConditions } = require('../../services/userService');
const { UserWalletService } = require('../../services/walletService');
const { getCardPoints } = require('../../utils/cards');
var dateFormat = require('date-format');
const { errorResponse, successResponse, successResponseWitDynamicCode, insufficientAmountResponse } = require('../../utils/response');
const db = require('../../models');
const { getRandomNumber, getAmountByPercentage, getRoundNumber, getRandomFromFromArray } = require('../../utils/util');
const errorHandler = require('../../error/errorHandler');
const userWallet = new UserWalletService();

class DragonTigerController {
    constructor() {
        this.makeWinner = this.makeWinner.bind(this);
        this.placeBet = this.placeBet.bind(this);
        this.declareWinner = this.declareWinner.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, bet, amount } = req.body;
            const user = req.user;
            //await getById(user_id);
            // if (!user) {
            //     return errorResponse(res, "Invalid User", HTTP_NOT_ACCEPTABLE);
            // }
            const setting = await adminService.setting(["dragon_tiger_min_bet"]);

            if (user.wallet < setting.dragon_tiger_min_bet) {
                return insufficientAmountResponse(res, setting.dragon_tiger_min_bet);
            }
            if (user.wallet < amount) {
                return insufficientAmountResponse(res);
            }
            // game
            const game = await dragonTigerService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                dragon_tiger_id: game_id,
                user_id,
                bet,
                amount
            }

            const betData = await dragonTigerService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.dragonTiger, betData);
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
            const game = await dragonTigerService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await dragonTigerService.viewBet(user_id, game_id);
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
            const walletHistory = await dragonTigerService.walletHistory(user_id);
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
            const dragonTigerBetPayload = {
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount
            }
            // Update bet
            dragonTigerService.updateBet(betId, dragonTigerBetPayload);

            // Update Dragon Tiger
            const dragonTigerPayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            dragonTigerService.update(gameId, dragonTigerPayload);

            // Get Bet to check amount deducted from which wallet
            const dragonTigerBet = await dragonTigerService.getBetById(betId, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            userWallet.plusUserWallet(userId, betId, userWinningAmount, adminComissionAmount, GAMES.dragonTiger, dragonTigerBet);
        } catch (error) {
            console.log(error);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// SOCKET FUNCTIONS /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    // Create Game From Socket
    async createDragonTigerGame() {
        try {
            const rooms = await dragonTigerService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await dragonTigerService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If Game Not found OR Game is ended then create New Game
                    if ((Array.isArray(gameData) && gameData.length === 0) || (gameData.length > 0 && gameData[0].status === 1)) {
                        dragonTigerService.create({ room_id: room.id });
                        console.log('Dragon Tiger Game Created Successfully');
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
            const rooms = await dragonTigerService.getRooms("", "", ["id"]);
            if (rooms && Array.isArray(rooms) && rooms.length > 0) {
                for (let index = 0; index < rooms.length; index++) {
                    const room = rooms[index];
                    const gameData = await dragonTigerService.getActiveGameOnTable(room.id, ["id", "status"]);
                    // If game is on going
                    if (Array.isArray(gameData) && gameData.length === 0) {
                        continue;
                    }

                    if (gameData[0].status === 0) {
                        let totalWinningAmount = 0;
                        // let dragonBetAmount = 0;
                        // let tigerBetAmount = 0;
                        // let tieBetAmount = 0;
                        const gameId = gameData[0].id;
                        // Get bets on which Bet is Added
                        const bets = await dragonTigerService.viewBet('', gameId);
                        const { totalBetAmount, dragonBetAmount, tigerBetAmount, tieBetAmount } = await dragonTigerService.getBetDataByBets(bets, 1);
                        /*for (let i = 0; i < bets.length; i++) {
                            const bet = bets[i];
                            totalBetAmount += bet.amount;
                            if (bet.bet == DRAGON) {
                                dragonBetAmount += (+bet.amount * DRAGON_OR_TIGET_MULTIPLY);
                            }
                            if (bet.bet == TIGER) {
                                tigerBetAmount += (+bet.amount * DRAGON_OR_TIGET_MULTIPLY);
                            }
                            if (bet.bet == TIE) {
                                tieBetAmount += (+bet.amount * TIE_MULTIPLY);
                            }
                        }*/

                        const setting = await adminService.setting(["dragon_tiger_random", "admin_commission", "admin_coin"]);
                        const random = setting.dragon_tiger_random;
                        var winning = "";
                        // Logic for winning
                        // random 1 = make any random winner
                        // random 2 = check if setting.admin_coin having more than equal to winning balance then make that rule win
                        // random 1 = make least betted option winner
                        if (setting && random == 1) {
                            winning = getRandomNumber(0, 2);
                        }
                        else if (setting && random == 3) {
                            winning = DRAGON;
                        }
                        else if (setting && random == 4) {
                            winning = TIGER;
                        }else if (setting && random == 2) {

                            const admin_coin = setting.admin_coin;

                            if (dragonBetAmount == 0 && tigerBetAmount == 0 && tieBetAmount == 0) {
                                winning = getRandomNumber(0, 2);
                            }else{

                                const optionArr = [0,1,2];
                                const optionArray = getRandomFromFromArray(optionArr,optionArr.length);

                                for (const element of optionArray) {

                                    switch (element) {
                                        case 0:
                                            if(dragonBetAmount>0 && admin_coin>=dragonBetAmount){
                                                winning = 0;
                                            }
                                            break;

                                        case 1:
                                            if(tigerBetAmount>0 && admin_coin>=tigerBetAmount){
                                                winning = 1;
                                            }
                                            break;

                                        case 2:
                                            if(tieBetAmount>0 && admin_coin>=tieBetAmount){
                                                winning = 2;
                                            }
                                            break;
                                    }

                                    if(winning!==""){
                                        break;
                                    }
                                }

                                if(winning===""){
                                    if (dragonBetAmount > tieBetAmount && tigerBetAmount > tieBetAmount) {
                                        winning = TIE;
                                    } else {
                                        winning = dragonBetAmount > tigerBetAmount ? TIGER : DRAGON;
                                    }
                                }
                            }

                        } else {
                            if (dragonBetAmount == 0 && tigerBetAmount == 0 && tieBetAmount == 0) {
                                winning = getRandomNumber(0, 2);
                            } else if (dragonBetAmount > tieBetAmount && tigerBetAmount > tieBetAmount) {
                                winning = TIE;
                            } else {
                                winning = dragonBetAmount > tigerBetAmount ? TIGER : DRAGON;
                            }
                        }
                        let cardDragon = '';
                        let cardTiger = '';
                        if (winning === TIE) {
                            const number = getRandomNumber(2, 10);
                            cardDragon = 'BP' + number;
                            cardTiger = 'RP' + number;
                        } else {
                            let card1Point;
                            let card2Point;
                            let smallCard = '';
                            let bigCard = '';
                            do {
                                const cards = await dragonTigerService.getCards(2);
                                card1Point = getCardPoints(cards[0].cards);
                                card2Point = getCardPoints(cards[1].cards);

                                // console.log(cards[0].cards,card1Point);
                                // console.log(cards[1].cards,card2Point);

                                // Assuming small card as first and big card as second
                                smallCard = cards[0].cards;
                                bigCard = cards[1].cards;

                                // console.log('before smallCard',smallCard);
                                // console.log('before bigCard',bigCard);

                                // if first card is bigger then second card the reassign the value
                                // console.log('Check card1Point > card2Point',card1Point + ' -- ' + card2Point);
                                if (card1Point > card2Point) {
                                    // console.log('Switch card1Point > card2Point',card1Point + ' -- ' + card2Point);
                                    bigCard = cards[0].cards
                                    smallCard = cards[1].cards;
                                }

                            } while (card1Point === card2Point);  // If same card the repeat the process

                            // const cardDragon = (winning == DRAGON ? bigCard : smallCard);
                            // const cardTiger = (winning == TIGER ? bigCard : smallCard);

                            // console.log('after smallCard',smallCard);
                            // console.log('after bigCard',bigCard);
                            // Assuming tiger win so assign big card to tiger
                            cardDragon = smallCard;
                            cardTiger = bigCard;
                            // If dragon win then make big card to dragon
                            if (winning === DRAGON) {
                                cardDragon = bigCard;
                                cardTiger = smallCard
                            }
                        }

                        // console.log('winning',winning);
                        // console.log('cardDragon',cardDragon);
                        // console.log('cardTiger',cardTiger);
                        // Crate Mapping For Dragon
                        await dragonTigerService.createMap(gameId, cardDragon);
                        // Crate Mapping For Tiger
                        await dragonTigerService.createMap(gameId, cardTiger);

                        // Give winning Amount to user
                        const winnerBets = await dragonTigerService.viewBet('', gameId, winning);
                        if (Array.isArray(winnerBets) && winnerBets.length > 0) {
                            const comission = setting.admin_commission;
                            for (let j = 0; j < winnerBets.length; j++) {
                                const winnerBet = winnerBets[j];
                                const userId = winnerBet.user_id;
                                const betId = winnerBet.id;
                                if (winning == TIE) {
                                    const amount = winnerBet.amount * TIE_MULTIPLY;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                } else {
                                    const amount = winnerBet.amount * DRAGON_OR_TIGET_MULTIPLY;
                                    totalWinningAmount += amount;
                                    this.makeWinner(userId, betId, amount, comission, gameId);
                                }
                            }

                        } else {
                            console.log("No Winning Bet Found Dragon Tiger");
                        }

                        const now = new Date();
                        // const updatedDate = dateFormat.asString('yyyy-MM-dd hh:mm:ss', now);
                        const endDateTime = dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date(now.getTime() + DRAGON_TIME_FOR_START_NEW_GAME * 1000));

                        const updatePayload = {
                            status: 1,
                            winning,
                            total_amount: totalBetAmount,
                            admin_profit: totalBetAmount - totalWinningAmount,
                            end_datetime: endDateTime,
                            random
                        }
                        await dragonTigerService.update(gameData[0].id, updatePayload);
                        // If admin profit is in positive or nagative then log this
                        if (updatePayload.admin_profit != 0) {
                            userWallet.directAdminProfitStatement(GAMES.dragonTiger, updatePayload.admin_profit, gameData[0].id);
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
            const gameData = await dragonTigerService.getActiveGameOnTable(roomId, ["id", "status", "added_date", "room_id", "main_card", "winning", "end_datetime", "updated_date", "random_amount"]);
            if ((Array.isArray(gameData) && gameData.length > 0)) {
                let gameCards = [];
                const gameId = gameData[0].id;
                if (gameData[0].status) {
                    gameCards = await dragonTigerService.getGameCards(gameId);
                }

                const addedDatetime = new Date(gameData[0].added_date);
                const addedDatetimeSec = Math.floor(addedDatetime.getTime() / 1000);
                const currentTimeSec = Math.floor(Date.now() / 1000);

                // Remaining Time 
                const timeRemaining = (addedDatetimeSec + DRAGON_TIME_FOR_BET) - currentTimeSec;

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
                const onlineUsers = await getByConditions({ dragon_tiger_room_id: roomId });
                // Get Bets for games
                const bets = await dragonTigerService.viewBet('', gameId);
                const { dragonBetAmount, tigerBetAmount, tieBetAmount } = await dragonTigerService.getBetDataByBets(bets, 0);

                const lastWinnings = await dragonTigerService.lastWinningBet(roomId);

                const randomDrgonAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const randomTgerAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const randomTieAmount = getRandomNumber(gameData[0].random_amount, gameData[0].random_amount + 4000)
                const responsePayload = {
                    bot_user: botUsers,
                    game_data: newGameData,
                    game_cards: gameCards,
                    online: getRandomNumber(300, 350) + onlineUsers.length,
                    last_bet: bets[0],
                    my_dragon_bet: dragonBetAmount,
                    my_tiger_bet: tigerBetAmount,
                    my_tie_bet: tieBetAmount,
                    dragon_bet: randomDrgonAmount + dragonBetAmount,
                    tiger_bet: randomTgerAmount + tigerBetAmount,
                    tie_bet: randomTieAmount + tieBetAmount,
                    last_winning: lastWinnings
                }

                // Update Random Amount
                dragonTigerService.update(gameId, { random_amount: db.sequelize.literal(`random_amount + ${responsePayload.dragon_bet}`) });
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

module.exports = new DragonTigerController();