const express = require('express');
const router = express.Router();
const validateSchema = require('../../middleware/validationMiddleware');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');
const iconRouletteController = require('../../controllers/api/iconRouletteController');
const { placeBetSchema } = require('../../requests/iconRoulette');


router.post('/place_bet', validateToken, validateSchema(placeBetSchema), iconRouletteController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), iconRouletteController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), iconRouletteController.walletHistory);

module.exports = router;
