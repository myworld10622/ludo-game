const { HTTP_NOT_ACCEPTABLE } = require("../constants");
const { errorResponse } = require("../utils/response");

const formatJoiErrors = (error) => {
    return error.details.map(err => ({
        message: err.message,
        path: err.path.join('.'),
        type: err.type
    }));
};

const validateSchema = (schema) => (req, res, next) => {
    const { error } = schema.validate(req.body, { abortEarly: false });
    if (error) {
        const formattedErrors = formatJoiErrors(error);
        // return res.status(400).json({ errors: formattedErrors });
        const message = "Invalid Parameter";
        return errorResponse(res, message, HTTP_NOT_ACCEPTABLE);
    }
    next();
};

module.exports = validateSchema;