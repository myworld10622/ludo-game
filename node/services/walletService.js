const db = require('../models');
const adminService = require('./adminService');
const statementService = require('./statementService');
const userService = require('./userService');
const DirectProfitStatement = db.DirectProfitStatement;

class UserWalletService {
    constructor() {
        this.minusUserWallet = this.minusUserWallet.bind(this);
        this.plusUserWallet = this.plusUserWallet.bind(this);
        this.statementLog = this.statementLog.bind(this);
    }

    async minusUserWallet(userId, amount, source, ref = null, userType = 0) {
        try {
            amount = parseFloat(amount);
            const initialAmount = amount;
            // get user wallets
            const userData = await userService.getById(userId, ["winning_wallet", "unutilized_wallet", "bonus_wallet"]);
            // if user have unutilized wallet then deduct
            const unutilizedWallet = userData.unutilized_wallet || 0;
            const unutilizedWalletMinus = (unutilizedWallet > amount) ? amount : unutilizedWallet;
            // deduct from main amount
            amount -= unutilizedWalletMinus;
            const walletDeductions = { unutilized_wallet: 0, winning_wallet: 0, bonus_wallet: 0 };
            if (unutilizedWalletMinus > 0) {
                const updateFields = {
                    unutilized_wallet: db.sequelize.literal(`unutilized_wallet - ${unutilizedWalletMinus}`),
                    wallet: db.sequelize.literal(`wallet - ${unutilizedWalletMinus}`)
                };

                walletDeductions.unutilized_wallet = unutilizedWalletMinus;
                await userService.update(userId, updateFields);
                /*User.update(updateFields, {
                    where: { id: userId }
                });*/
            }
            // after unutilized if amount still greater then 0 then check for winning_wallet
            if (amount > 0) {
                const winningWallet = userData.winning_wallet || 0;
                const winningWalletMinus = (winningWallet > amount) ? amount : winningWallet;
                amount -= winningWalletMinus;
                if (winningWalletMinus > 0) {
                    const updateFields = {
                        winning_wallet: db.sequelize.literal(`winning_wallet - ${winningWalletMinus}`),
                        wallet: db.sequelize.literal(`wallet - ${winningWalletMinus}`)
                    };
                    walletDeductions.winning_wallet = winningWalletMinus;
                    await userService.update(userId, updateFields);
                    /*User.update(updateFields, {
                        where: { id: userId }
                    });*/
                }
            }

            // after winning_wallet if amount still greater then 0 then check for bonus_wallet
            if (amount > 0) {
                const bonusWallet = userData.bonus_wallet || 0;
                const bonusWalletMinus = (bonusWallet > amount) ? amount : bonusWallet;
                amount -= bonusWalletMinus;
                if (bonusWalletMinus > 0) {
                    const updateFields = {
                        bonus_wallet: db.sequelize.literal(`bonus_wallet - ${bonusWalletMinus}`),
                        wallet: db.sequelize.literal(`wallet - ${bonusWalletMinus}`)
                    };

                    walletDeductions.bonus_wallet = bonusWalletMinus;
                    await userService.update(userId, updateFields);
                    /*User.update(updateFields, {
                        where: { id: userId }
                    });*/
                }
            }
            // console.log('walletDeductions',walletDeductions);
            let sourceId = 0;
            if (ref && typeof ref === "object") {
                // update in same reference table
                ref.minus_unutilized_wallet = walletDeductions.unutilized_wallet;
                ref.minus_winning_wallet = walletDeductions.winning_wallet;
                ref.minus_bonus_wallet = walletDeductions.bonus_wallet;
                ref.save();

                sourceId = ref.id;
            }

            this.statementLog(userId, source, initialAmount * -1, sourceId, userType);

            return walletDeductions;
        } catch (error) {
            console.log(error)
            throw new Error('Error while update wallet');
        }
    }


    async plusUserWallet(userId, betId, amount, comission, source, betRef = null, userType = 0, bonus = 0, added_date = null) {
        try {
            amount = parseFloat(amount);
            const walletArray = {
                wallet: db.sequelize.literal(`wallet + ${amount}`)
            };
            if (bonus === 1) {
                walletArray.bonus_wallet = db.sequelize.literal(`bonus_wallet + ${amount}`);
            } else {
                if (amount > 0 && betRef && typeof betRef === "object") {
                    let winningAmount = amount;
                    if (betRef.minus_unutilized_wallet) {
                        walletArray.unutilized_wallet = db.sequelize.literal(`unutilized_wallet + ${betRef.minus_unutilized_wallet}`);
                        // walletArray.winning_wallet = betRef.minus_unutilized_wallet;
                        winningAmount -= betRef.minus_unutilized_wallet
                    }
                    if (betRef.minus_bonus_wallet) {
                        walletArray.bonus_wallet = db.sequelize.literal(`bonus_wallet + ${betRef.minus_bonus_wallet}`);
                        // walletArray.bonus_wallet = betRef.minus_bonus_wallet;
                        winningAmount -= betRef.minus_bonus_wallet;
                    }
                    if (winningAmount > 0) {
                        walletArray.winning_wallet = db.sequelize.literal(`winning_wallet + ${winningAmount}`);
                        // walletArray.winning_wallet = winningAmount;
                    }
                } else {
                    walletArray.winning_wallet = db.sequelize.literal(`winning_wallet + ${amount}`);
                }
            }

            await userService.update(userId, walletArray);

            this.statementLog(userId, source, amount, betId, userType, comission, added_date);
        } catch (error) {
            console.log(error)
            throw new Error('Error while update wallet');
        }
    }

    async statementLog(userId, source, amount, sourceId, userType = 0, adminCommission = 0, added_date = null) {
        try {
            adminCommission = parseFloat(adminCommission);
            let user = null;
            if (userType) {
                // Agent
                user = await adminService.getById(userId, ["wallet"]);
            } else {
                user = await userService.getById(userId, ["wallet"]);
            }
            const setting = await adminService.setting(["admin_coin"]);

            let adminCurrentCoin = (+adminCommission);
            if (setting) {
                adminCurrentCoin += setting.admin_coin
            }
            // update admin commission
            if (adminCommission) {
                let data = {
                    admin_coin: db.Sequelize.literal('admin_coin+' + adminCommission),
                }
                adminService.updateSetting(1, data)
            }

            if (user) {
                let statementPayload = {
                    user_id: userId,
                    source: source,
                    source_id: sourceId,
                    user_type: userType,
                    amount,
                    current_wallet: user.wallet,
                    admin_commission: adminCommission,
                    admin_coin: adminCurrentCoin
                }
                if (added_date) {
                    statementPayload.added_date = added_date;
                }

                statementService.create(statementPayload)
            }

        } catch (error) {
            console.log(error)
            throw new Error('Error fetching users');
        }
    }

    async directAdminProfitStatement(source, adminComission, sourceId = 0, added_date = null) {
        adminComission = parseFloat(adminComission);
        const setting = await adminService.setting(["admin_coin"]);
        adminService.updateSetting(1, { admin_coin: db.sequelize.literal(`admin_coin + ${adminComission}`) });
        const adminCurrentWallet = setting.admin_coin + adminComission;
        const statementPayload = {
            source,
            source_id: sourceId,
            admin_coin: adminCurrentWallet,
            admin_commission: adminComission
        };
        if (added_date) {
            statementPayload.added_date = added_date;
        }
        DirectProfitStatement.create(statementPayload);
    }

    async addToWallet(amount, userId) {
        try {
            const updateFields = {
                wallet: db.sequelize.literal(`wallet -+ ${amount}`)
            };
            return await userService.update(userId, updateFields);
        } catch (error) {
            console.log(error)
            throw new Error("Error while update user waller")
        }
    }
}

module.exports = {
    UserWalletService
};