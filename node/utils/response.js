const { HTTP_NOT_ACCEPTABLE, HTTP_OK, HTTP_INSUFFIENT_PAYMENT } = require("../constants");

class HttpResponse {
    errorResponse(res, message, code) {
        return res.status(HTTP_OK).json({
            message,
            code
        })
    }

    successResponse(res, data = null) {
        return res.status(HTTP_OK).json({
            message: 'Success',
            ...data,
            code: HTTP_OK
        })
    }

    successResponseWitDynamicCode(res, data) {
        return res.status(HTTP_OK).json({
            ...data
        })
    }

    insufficientAmountResponse(res, amount = 0, data = null) {
        let message = 'Insufficient Wallet Amount';
        if (amount) {
            message = 'Required Minimum ' + amount + ' Coins to Play';
        }
        return res.status(HTTP_OK).json({
            message,
            ...data,
            code: HTTP_INSUFFIENT_PAYMENT
        })
    }

    normalResponse(message, code, data = null) {
        return {
            message,
            ...data,
            code
        }
    }
}

module.exports = new HttpResponse();