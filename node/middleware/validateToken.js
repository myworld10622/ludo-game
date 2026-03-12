const { HTTP_NOT_ACCEPTABLE, HTTP_UNAUTHORIZED } = require("../constants");
const db = require("../models");
const { errorResponse } = require("../utils/response");

const validateToken = async (req, res, next) => {
    const { token, user_id } = req.body;
    if (!token) {
        return errorResponse(res, "Access Denied. No Token Provided.", HTTP_NOT_ACCEPTABLE);
    }

    try {
        const condition = {
            token
        }
        if (user_id) {
            condition.id = user_id
        }
        const user = await db.user.findOne({
            where: condition
        });
        if (!user) {
            return errorResponse(res, "Access Denied. Invali Token.", HTTP_UNAUTHORIZED);
        }
        req.user = user;
        next();
    } catch (err) {
        console.log(err)
        return errorResponse(res, "Server Error.", HTTP_NOT_ACCEPTABLE);
    }
};

module.exports = validateToken;