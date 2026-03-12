const express = require('express');
const router = express.Router();
const aviatorController = require('../../controllers/api/aviatorController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema, redeemSchema } = require('../../requests/aviator');
const { userIDBetIdIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), aviatorController.placeBet);
router.post('/redeem', validateToken, validateSchema(redeemSchema), aviatorController.redeem)
router.post('/cancelBet', validateToken, validateSchema(userIDBetIdIDSchema), aviatorController.cancelBet);

module.exports = router;