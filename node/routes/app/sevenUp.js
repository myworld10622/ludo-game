const express = require('express');
const router = express.Router();
const sevenUpController = require('../../controllers/api/sevenUpController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/sevenUp');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), sevenUpController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), sevenUpController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), sevenUpController.walletHistory);

module.exports = router;