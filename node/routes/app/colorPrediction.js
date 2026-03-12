const express = require('express');
const router = express.Router();
const colorPredictionController = require('../../controllers/api/colorPredictionController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/colorPrediction');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), colorPredictionController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), colorPredictionController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), colorPredictionController.walletHistory);
router.post('/myHistory', validateToken, validateSchema(userIDSchema), colorPredictionController.myHistory);
router.post('/GameHistory', validateToken, validateSchema(userIDSchema), colorPredictionController.gameHistory);

module.exports = router;