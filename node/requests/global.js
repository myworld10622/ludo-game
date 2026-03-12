const Joi = require('joi');

const userIDGameIDSchema = Joi.object({
    user_id: Joi.number().required().messages({
        'number.base': 'User Id must be number',
        'any.required': 'User Id field is required'
    }),
    game_id: Joi.number().integer().min(0).required().messages({
        'number.base': 'Game Id must be number',
        'any.required': 'Game Id field is required'
    })
}).unknown(true);

const userIDBetIdIDSchema = Joi.object({
    user_id: Joi.number().required().messages({
        'number.base': 'User Id must be number',
        'any.required': 'User Id field is required'
    }),
    bet_id: Joi.number().integer().min(0).required().messages({
        'number.base': 'Bet Id must be number',
        'any.required': 'Bet Id field is required'
    })
}).unknown(true);

const userIDSchema = Joi.object({
    user_id: Joi.number().required().messages({
        'number.base': 'User Id must be number',
        'any.required': 'User Id field is required'
    })
}).unknown(true);

module.exports = {
    userIDGameIDSchema,
    userIDBetIdIDSchema,
    userIDSchema
};