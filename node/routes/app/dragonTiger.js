const express = require('express');
const router = express.Router();
const dragonTigerController = require('../../controllers/api/dragonTigerController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/dragonTiger');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), dragonTigerController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), dragonTigerController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), dragonTigerController.walletHistory);

module.exports = router;