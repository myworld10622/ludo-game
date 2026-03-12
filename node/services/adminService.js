const db = require('../models');
const Admin = db.admin;
const Setting = db.setting;

class AdminService {
    async getById(id = 1, attributes = []) {
        try {
            let admin = null;
            if (attributes.length > 0) {
                admin = await Admin.findByPk(id, {
                    attributes
                });
            } else {
                admin = await Admin.findByPk(id);
            }

            if (!admin) {
                throw new Error('Admin not found');
            }
            return admin;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching user');
        }
    }

    async update(id, data) {
        try {
            await Admin.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating setting');
        }
    }


    /* Setting Serice */
    async setting(attributes = []) {
        try {
            let setting = null;
            if (attributes.length > 0) {
                setting = await Setting.findOne({
                    attributes
                });
            } else {
                setting = await Setting.findOne();
            }
            return setting;
        } catch (error) {
            console.log(error)
            throw new Error('Error fetching Data');
        }
    }

    async updateSetting(id, data) {
        try {
            await Setting.update(data, {
                where: { id }
            });
        } catch (error) {
            console.log(error);
            throw new Error('Error updating setting');
        }
    }
}

module.exports = new AdminService();