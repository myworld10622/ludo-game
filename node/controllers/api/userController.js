const { HTTP_NOT_FOUND, HTTP_NOT_ACCEPTABLE } = require("../../constants");
const crypto = require('crypto');
const userService = require("../../services/userService");
const db = require("../../models");
const { successResponse } = require("../../utils/response");

const User = db.user;

class UserController {
    async getUser(req, res) {
        try {
            const user = req.user;
            return successResponse(res, { data: user });
        } catch (error) {
            return errorResponse(res, error.message, HTTP_NOT_ACCEPTABLE);
        }
    }

    async dailyAttendenceReport(req, res) {
        const user = req.user;
        const attendencesBonuses = await db.AttendenceBonus.findAll({});
        const startOfYesterday = new Date();
        startOfYesterday.setDate(startOfYesterday.getDate() - 1);
        startOfYesterday.setHours(0, 0, 0, 0);

        const endOfYesterday = new Date();
        endOfYesterday.setDate(endOfYesterday.getDate() - 1);
        endOfYesterday.setHours(23, 59, 59, 999);

        const lastAttendenceBonus = await db.AttendenceBonusLog.findOne({
            where: {
                added_date: {
                    [db.Sequelize.Op.between]: [startOfYesterday, endOfYesterday]
                },
                user_id: user.id
            },
            order: [['added_date', 'DESC']],
            attributes: ["day", "id"]
        });

        let daysCollected = 0;
        if (lastAttendenceBonus) {
            daysCollected = lastAttendenceBonus.day;
        }

        const attendenceFinalArray = [];
        for (let index = 0; index < attendencesBonuses.length; index++) {
            const element = attendencesBonuses[index].toJSON();
            if (element.id <= daysCollected) {
                element.collected = 1;
            } else {
                element.collected = 0;
            }
            if (element.id == (daysCollected + 1)) {
                element.today_attendence = 1;
            }
            attendenceFinalArray.push(element);
        }

        return successResponse(res, { data: attendenceFinalArray, todays_bet: user.todays_bet });
    }
}
module.exports = new UserController();