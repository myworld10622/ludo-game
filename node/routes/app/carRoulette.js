const express = require('express');
const router = express.Router();
const carRouletteController = require('../../controllers/api/carRouletteController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/animalRoulette');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), carRouletteController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), carRouletteController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), carRouletteController.walletHistory);

module.exports = router;