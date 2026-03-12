const express = require('express');
const router = express.Router();
const bacarratController = require('../../controllers/api/baccaratController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/redBlack');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), bacarratController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), bacarratController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), bacarratController.walletHistory);

module.exports = router;