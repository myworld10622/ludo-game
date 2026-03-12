const express = require('express');
const router = express.Router();
const headTailController = require('../../controllers/api/headTailController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/sevenUp');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), headTailController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), headTailController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), headTailController.walletHistory);

module.exports = router;