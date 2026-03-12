const express = require('express');
const router = express.Router();
const jhandiMundaController = require('../../controllers/api/jhandiMundaController');
const validateSchema = require('../../middleware/validationMiddleware');
const { placeBetSchema } = require('../../requests/redBlack');
const { userIDGameIDSchema, userIDSchema } = require('../../requests/global');
const validateToken = require('../../middleware/validateToken');

router.post('/place_bet', validateToken, validateSchema(placeBetSchema), jhandiMundaController.placeBet);
router.post('/get_result', validateToken, validateSchema(userIDGameIDSchema), jhandiMundaController.getResult);
router.post('/wallet_history', validateToken, validateSchema(userIDSchema), jhandiMundaController.walletHistory);

module.exports = router;