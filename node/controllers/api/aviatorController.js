const { HTTP_NOT_ACCEPTABLE, HTTP_SWITCH_PROTOCOL, HTTP_WIN, HTTP_LOOSER, GAMES } = require('../../constants');
const adminService = require('../../services/adminService');
const aviatorService = require('../../services/aviatorService');
const { UserWalletService } = require('../../services/walletService');
const { errorResponse, successResponse, successResponseWitDynamicCode } = require('../../utils/response');
const db = require('../../models');
const { getAmountByPercentage, getRoundNumber } = require('../../utils/util');
const userService = require('../../services/userService');
const userWallet = new UserWalletService();

class AviatorController {
    constructor() {
        this.placeBet = this.placeBet.bind(this);
    }

    async placeBet(req, res) {
        try {
            const { game_id, user_id, amount } = req.body;
            const user = req.user;
            /*if (user.wallet < 20) {
                return errorResponse(res, "Required Minimum 20 Coins to Play", HTTP_NOT_ACCEPTABLE);
            }*/
            if (user.wallet < amount) {
                return errorResponse(res, "Insufficient Wallet Amount", HTTP_NOT_ACCEPTABLE);
            }
            // game
            const game = await aviatorService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            if (game.status) {
                return errorResponse(res, "Can't Place Bet, Game Has Been Ended", HTTP_NOT_ACCEPTABLE);
            }

            const payload = {
                aviator_id: game_id,
                user_id,
                amount
            }

            const betData = await aviatorService.createBet(payload);
            if (!betData) {
                return errorResponse(res, "Something Wents Wrong", HTTP_NOT_ACCEPTABLE);
            }
            // Not wait for calculation of wallet
            userWallet.minusUserWallet(user_id, amount, GAMES.aviator, betData);
            const data = {
                admin_coin: db.Sequelize.literal('admin_coin+' + req.body.amount),
            }
            adminService.updateSetting(1, data)
            return successResponse(res, {result: betData.toJSON()});
        } catch (error) {
            console.log(error)
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async redeem(req, res) {
        try {
            const { user_id, bet_id } = req.body;
            const multiplyAmount = req.body.amount;
            const setting = await adminService.setting(["admin_commission"]);
            const validBet = await aviatorService.getBetByUserId(bet_id, user_id, ["id", "winning_amount", "aviator_id", "amount", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            if (!validBet) {
                return errorResponse(res, "Invalid bet", HTTP_SWITCH_PROTOCOL);
            }
            if (validBet.winning_amount > 0) {
                return errorResponse(res, "Already Redeemed", HTTP_SWITCH_PROTOCOL);
            }
            const gameId = validBet.aviator_id;
            const game = await aviatorService.getById(gameId, ["id", "status", "blast_time"]);
            if (game.status != 1) {
                return errorResponse(res, "You can not redeem", HTTP_SWITCH_PROTOCOL);
            }
            if (multiplyAmount > parseFloat(game.blast_time)) {
                return errorResponse(res, "Invalid redeem amount", HTTP_SWITCH_PROTOCOL);
            }

            let amount = validBet.amount * multiplyAmount;
            const comission = setting.admin_commission;
            const adminComissionAmount = await getAmountByPercentage(amount, comission);
            const userWinningAmount = await getRoundNumber(amount - adminComissionAmount, 2);

            let betPayload = {
                bet: multiplyAmount,
                winning_amount: amount,
                user_amount: userWinningAmount,
                comission_amount: adminComissionAmount,
            }

            aviatorService.updateBet(bet_id, betPayload);

            // Update Dragon Tiger
            const dragonTigerPayload = {
                winning_amount: db.sequelize.literal(`winning_amount + ${amount}`),
                user_amount: db.sequelize.literal(`user_amount + ${userWinningAmount}`),
                comission_amount: db.sequelize.literal(`comission_amount + ${adminComissionAmount}`),
            }

            aviatorService.update(gameId, dragonTigerPayload);

            // Get Bet to check amount deducted from which wallet
            // const aviatorBet = await aviatorService.getBetById(bet_id, ["id", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);
            // Add to Wallet
            let data = {
                admin_coin: db.Sequelize.literal('admin_coin-' + amount)
            }
            adminService.updateSetting(1, data)
            await userWallet.plusUserWallet(user_id, bet_id, userWinningAmount, adminComissionAmount, GAMES.aviator, validBet);

            const user = await userService.getById(user_id);

            const responsePayload = {
                data: user,
                user_winning_amt: userWinningAmount,
                admin_winning_amt: adminComissionAmount,
                message: "Redeemd success"
            }

            return successResponse(res, responsePayload);
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async cancelBet(req, res) {
        try {
            const { bet_id, user_id } = req.body;
            if (!bet_id || !req.body.user_id) {
                return res.status(200).send({ code: 100, message: "Invalid Parameter" })
            }
            const validBet = await aviatorService.getBetByUserId(bet_id, user_id, ["id", "winning_amount", "aviator_id", "amount", "minus_unutilized_wallet", "minus_winning_wallet", "minus_bonus_wallet"]);

            if (!validBet) {
                return errorResponse(res, "Invalid bet", HTTP_SWITCH_PROTOCOL);
            }
            const gameId = validBet.aviator_id;
            const game = await aviatorService.getById(gameId, ["id", "status", "blast_time"]);
            if (game.status != 0) {
                return errorResponse(res, "You can not cancel the bet", HTTP_SWITCH_PROTOCOL);
            }

            let amount = validBet.amount;
            let bet_info = {
                bet_status: 1,
            }
            aviatorService.updateBet(bet_id, bet_info);
            let data = {
                admin_coin: db.Sequelize.literal('admin_coin-' + amount)
            }
            adminService.updateSetting(1, data)
            await userWallet.plusUserWallet(user_id, bet_id, amount, 0, GAMES.aviator + " Cancelled", validBet);

            const user = await userService.getById(user_id);

            const responsePayload = {
                data: user,
                message: "Cancelled Successfully"
            }
            return successResponse(res, responsePayload);
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }


    // Unused Code
    async getResult(req, res) {
        try {
            const { game_id, user_id } = req.body;
            const game = await aviatorService.getById(game_id);
            if (!game) {
                return errorResponse(res, "Invalid Game Id", HTTP_NOT_ACCEPTABLE);
            }
            const betData = await aviatorService.viewBet(user_id, game_id);
            let winAmount = 0;
            let betAmount = 0;
            if (!betData) {
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

    // Unused code
    async walletHistory(req, res) {
        try {
            const { user_id } = req.body;
            const walletHistory = await aviatorService.walletHistory(user_id);
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

    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// SOCKET FUNCTIONS /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
}

module.exports = new AviatorController();