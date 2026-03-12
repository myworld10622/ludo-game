const express = require('express');
const router = express.Router();
const animalRouletteController = require('../../controllers/api/animalRouletteController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/animalRoulette');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), animalRouletteController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), animalRouletteController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), animalRouletteController.walletHistory);

module.exports = router;