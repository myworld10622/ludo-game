const db = require('../models');
const { getAttributes } = require('../utils/util');
const userService = require('./userService');
const Aviator = db.Aviator;
const AviatorBet = db.AviatorBet;

class AviatorService {
    async getById(id, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const game = await Aviator.findByPk(id, attributeOptions);
            if (!game) {
                throw new Error('Game not found');
            }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching aviator');
        }
    }

    async create(data) {
        try {
            return await Aviator.create(data);
        } catch (error) {
            console.log(error)
            throw new Error('Error creating aviator');
        }
    }

    async update(id, data) {
        try {
            await Aviator.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating data');
        }
    }


    /////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// Aviator BET /////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////

    async createBet(data) {
        try {
            const bet = await AviatorBet.create(data);
            await userService.update(data.user_id, { todays_bet: db.sequelize.literal(`todays_bet + ${data.amount}`) })
            return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error while place bet');
        }
    }

    async getBetById(id, attributes = []) {
        try {
            const options = getAttributes(attributes);
            const bet = await AviatorBet.findByPk(id, options);
            if (!bet) {
                throw new Error('Bet not found');
            }
            return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Dragon Tiger');
        }
    }

    async getBetByUserId(id, userId, attributes = []) {
        try {
            const attributeOptions = getAttributes(attributes);
            const options = {
                ...attributeOptions,
                where: {
                    id,
                    user_id: userId
                }
            }
            const bet = await AviatorBet.findOne(options);
            if (!bet) {
                throw new Error('Bet not found');
            }
            return bet;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching aviator bet');
        }
    }

    async updateBet(id, data) {
        try {
            await AviatorBet.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating data');
        }
    }
}

module.exports = new AviatorService();