const express = require('express');
const router = express.Router();
const jackpotController = require('../../controllers/api/jackpotController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/redBlack');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), jackpotController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), jackpotController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), jackpotController.walletHistory);

module.exports = router;