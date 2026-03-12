const Joi = require('joi');

const placeBetSchema = Joi.object({
    user_id: Joi.number().required().messages({
        'number.base': 'User Id must be number',
        'any.required': 'User Id field is required'
    }),
    game_id: Joi.number().integer().min(0).required().messages({
        'number.base': 'Game Id must be number',
        'any.required': 'Game Id field is required'
    }),
    bet: Joi.any().required().messages({
        'any.only': 'Invalid Bet',
        'any.required': 'Bet field is required'
    }),
    amount: Joi.number().min(1).required().messages({
        'any.base': 'Amount must be number',
        'number.min': 'Amount must be greater then 0',
        'any.required': 'Amount field is required'
    })
}).unknown(true);

module.exports = {
    placeBetSchema
};