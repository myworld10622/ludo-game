const { HTTP_NOT_FOUND, HTTP_NOT_ACCEPTABLE } = require("../../constants");
const crypto = require('crypto');
const userService = require("../../services/userService");
const { getRandomNumber } = require("../../utils/util");
const { successResponse, errorResponse } = require("../../utils/response");

class AuthController {
    async login(req, res) {
        // var md5Password = crypto.createHash('md5');
        try {
            const { mobile, password } = req.body;
            const user = await userService.getByMobile(mobile, ["id", "wallet", "user_type", "name", "upi", "mobile", "email", "source", "gender", "profile_pic", "referral_code", "token", "status", "premium", "app_version", "unique_token", "isDeleted", "password", "sw_password"]);
            if (user) {
                const userData = { ...user.toJSON() };
                if (password == user.password) {
                    if (userData.status == 1) {
                        return errorResponse(res, "You are blocked, Please contact to admin", HTTP_NOT_FOUND);
                    }
                    delete userData.password;
                    const uniqueId = crypto.randomUUID();
                    const token = crypto.createHash('md5').update(uniqueId).digest('hex');
                    userService.update(user.id, { token });
                    return successResponse(res, { user_data: { ...userData, token,  message: 'You Have Successfully Logged In' } })

                } else {
                    return errorResponse(res, "Please Enter Valid Password", HTTP_NOT_ACCEPTABLE);
                }
            } else {
                return errorResponse(res, "Please Enter Valid Mobile Number", HTTP_NOT_ACCEPTABLE);
            }
        } catch (error) {
            console.error('Error during login:', error);
            return res.status(500).json({
                code: '500',
                message: error,
            });
        }
    }

    async otp(req, res) {
        try {
            const { mobile } = req.body;
            const user = await userService.getByMobile(mobile, ["id", "mobile"])
            if (user) {
                return errorResponse(res, "Mobile Already Exist, Please Login", HTTP_NOT_FOUND);
            } else {
                const otp = getRandomNumber(1000, 9999);
                const otpData = await userService.otpCreate(mobile, otp);
                // send otp
                return successResponse(res, { otp_id: otpData.id })
            }
        } catch (error) {
            console.error('Error during login:', error);
            return res.status(500).json({
                code: '500',
                message: error,
            });
        }
    }
}
module.exports = new AuthController();