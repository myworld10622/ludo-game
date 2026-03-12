const express = require('express');
const router = express.Router();
const anderBaharController = require('../../controllers/api/anderBaharController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/anderBahar');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), anderBaharController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), anderBaharController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), anderBaharController.walletHistory);

module.exports = router;