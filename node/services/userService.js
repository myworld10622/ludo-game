const { Op } = require('sequelize');
const db = require('../models');
const { getAttributes } = require('../utils/util');
var dateFormat = require('date-format');
const adminService = require('./adminService');
const User = db.user;
const BotUser = db.BotUser;
const Otp = db.Otp;
const TipLog = db.TipLog;
const Gift = db.Gift;
class UserService {
    async getAll() {
        try {
            return await User.findAll();
        } catch (error) {
            console.log("CUST_ERR GET ALL USERS", error);
            throw new Error('Error fetching users');
        }
    }

    async getById(id, attributes = []) {
        try {
            let user = null;
            if (attributes.length > 0) {
                user = await User.findByPk(id, {
                    attributes
                });
            } else {
                user = await User.findByPk(id);
            }

            if (!user) {
                throw new Error('User not found');
            }
            return user;
        } catch (error) {
            console.log("CUST_ERR GET USER", error)
            throw new Error('Error fetching user');
        }
    }

    async getByMobile(mobile, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const options = {
                ...attributeOptions
            };
            options.where = {
                mobile
            }
            return await User.findOne(options);
        } catch (error) {
            console.log("CUST_ERR GET USER", error)
            throw new Error('Error fetching user');
        }
    }
    
    async getUserByMobile(mobile, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const user = await User.findOne({
                ...attributeOptions,
                where: {
                    mobile
                }
            })

            if (!user) {
                throw new Error('User not found');
            }
            return user;
        } catch (error) {
            console.log("CUST_ERR GET USER", error)
            throw new Error('Error fetching user');
        }
    }

    async create(data) {
        try {
            return await User.create(data);
        } catch (error) {
            console.log("CUST_ERR CREATE USER", error);
            throw new Error('Error creating user');
        }
    }

    async update(id, data) {
        try {
            await User.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log("CUST_ERR UPDATE USER", error);
            throw new Error('Error updating user');
        }
    }

    async updateByConditions(condisions, data) {
        try {
            await User.update(data, {
                where: { ...condisions }
            });
        } catch (error) {
            throw new Error('Error updating user');
        }
    }

    async delete(id) {
        try {
            const user = await User.findByPk(id);
            if (!user) {
                throw new Error('User not found');
            }
            await user.destroy();
            return { message: 'User deleted successfully' };
        } catch (error) {
            console.log("CUST_ERR DELETE USER", error);
            throw new Error('Error deleting user');
        }
    }

    async getByConditions(conditions, attributes = []) {
        try {
            const options = attributes.length > 0 ? { attributes } : {};
            options.where = conditions;
            const users = await User.findAll(options);
            return users;
        } catch (error) {
            console.log("CUST_ERR GET USER", error)
            throw new Error('Error fetching user');
        }
    }

    async getSingleByConditions(conditions, attributes = []) {
        try {
            const options = attributes.length > 0 ? { attributes } : {};
            options.where = conditions;
            return await User.findOne(options);
        } catch (error) {
            console.log("CUST_ERR GET USER", error)
            throw new Error('Error fetching user');
        }
    }

    /////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////// BOT USERS /////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async getAllPredefinedBots() {
        try {
            const options = {
                order: db.sequelize.random(),
                limit: 6
            };
            return await BotUser.findAll(options);
        } catch (error) {
            console.log(error);
            throw new Error('Error while fetch bots');
        }
    }

    async getFreeRummyBots(amount = 1000) {
        try {
            const whereConditions = {
                status: false,
                rummy_table_id: 0,
                user_type: 1,
                wallet: {
                    [Op.gte]: amount
                }
            }
            return await User.findAll({
                where: whereConditions
            });
        } catch (error) {
            throw new Error(error);
        }
    }

    // OTP
    async otpCreate(mobile, otp) {
        try {
            const otpData = await Otp.findOne({
                attributes: ["id"],
                where: {
                    mobile
                }
            })
            if (otpData) {
                const payload = {
                    otp,
                    added_date: dateFormat.asString('yyyy-MM-dd hh:mm:ss', new Date())
                }
                await Otp.update(payload, {
                    where: { id: otpData.id }
                });
                return otpData;
            }
            const payload = {
                otp,
                mobile
            }
            return await Otp.create(payload);
        } catch (error) {
            throw new Error(error);
        }
    }

    async getFreeBots(amount = 10000) {
        try {
            const whereConditions = {
                status: false,
                table_id: 0,
                rummy_table_id: 0,
                poker_table_id: 0,
                rummy_pool_table_id: 0,
                rummy_deal_table_id: 0,
                ludo_table_id: 0,
                user_type: 1,
                wallet: {
                    [Op.gte]: amount
                }
            }
            return await User.findAll({
                where: whereConditions
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error while fetch bots');
        }
    }

    async giveTip(amount, userId, tableId, giftId, toUserId) {
        try {
            await User.update({
                wallet: db.sequelize.literal(`wallet - ${amount}`)
            }, {
                where: { id: userId }
            });

            await User.update({
                winning_wallet: db.sequelize.literal(`winning_wallet - ${amount}`)
            }, {
                where: {
                    id: userId,
                    winning_wallet: {
                        [Op.gt]: 0
                    }
                }
            });

            await adminService.updateSetting(1, { admin_coin: db.sequelize.literal(`admin_coin + ${amount}`) })

            const tipPayload = {
                user_id: userId,
                to_user_id: toUserId,
                gift_id: giftId,
                table_id: tableId,
                coin: amount
            }
            return await TipLog.create(tipPayload);
        } catch (error) {
            throw new Error(error);
        }
    }

    async chat(data) {
        try {
            return await Chat.create(data);
        } catch (error) {
            throw new Error(error);
        }
    }

    async chatList(tableId) {
        try {
            return await Chat.findAll({
                table_id: tableId,
                order: [["id", "DESC"]]
            });
        } catch (error) {
            throw new Error(error);
        }
    }

    async giftList(tableId) {
        try {
            const curr = new Date();
            const lastMin = new Date(Date.now() - 30 * 1000);
            const queryOptions = {
                attributes: ['tbl_tip_log.*', 'gift.image'],
                where: {
                    gift_id: { [Op.ne]: 0 },
                    table_id: tableId,
                    added_date: {
                        [Op.between]: [lastMin, curr]
                    }
                },
                include: [{
                    model: Gift,
                    as: "gift",
                    attributes: [],
                    // where: {
                    //     id: Sequelize.col('tbl_tip_log.gift_id')
                    // }
                }]
            };
            return await TipLog.findAll(queryOptions);
        } catch (error) {
            throw new Error(error);
        }
    }
}

module.exports = new UserService();