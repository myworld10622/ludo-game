const Joi = require('joi');

const loginSchema = Joi.object({
    mobile: Joi.any().required().messages({
        'any.required': 'Mobile field is required'
    }),
    password: Joi.any().required().messages({
        'any.required': 'Password field is required'
    })
}).unknown(true);

const sendOtpSchema = Joi.object({
    mobile: Joi.any().required().messages({
        'any.required': 'Mobile field is required'
    })
}).unknown(true);

module.exports = {
    loginSchema,
    sendOtpSchema
};