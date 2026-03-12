const express = require('express');
const router = express.Router();
const slotGameController = require('../../controllers/api/slotGameController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/slotGame');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), slotGameController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), slotGameController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), slotGameController.walletHistory);

module.exports = router;