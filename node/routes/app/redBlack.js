const express = require('express');
const router = express.Router();
const redBlackController = require('../../controllers/api/redBlackController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/redBlack');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), redBlackController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), redBlackController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), redBlackController.walletHistory);

module.exports = router;