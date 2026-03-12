const express = require('express');
const router = express.Router();
const threeDiceController = require('../../controllers/api/threeDiceController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/redBlack');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), threeDiceController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), threeDiceController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), threeDiceController.walletHistory);

module.exports = router;