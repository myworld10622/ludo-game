const express = require('express');
const router = express.Router();
const rouletteController = require('../../controllers/api/rouletteController');
const validateSchema = require('../../middleware/validationMiddleware');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');
const { placeBetSchema } = require('../../requests/roulette');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), rouletteController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), rouletteController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), rouletteController.walletHistory);

module.exports = router;