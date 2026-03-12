const db = require('../models');
const Statement = db.statement;

class AgentService {
    async getById(id) {
        try {
            const game = await DragonTiger.findByPk(id);
            if (!game) {
                throw new Error('Game not found');
            }
            return game;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Dragon Tiger');
        }
    }

    async create(data) {
        try {
            return await Statement.create(data);
        } catch (error) {
            console.log(error);
            throw new Error('Error creating statement');
        }
    }

    async update(id, data) {
        try {
            await Statement.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating statement');
        }
    }
}

module.exports = new AgentService();