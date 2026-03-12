const express = require("express");
const router = express.Router();
const trippleFunController = require("../../controllers/api/trippleFunController");
const validateSchema = require("../../middleware/validationMiddleware");
const { placeBetSchema } = require("../../requests/trippleFun");
const { userIDGameIDSchema, userIDSchema } = require("../../requests/global");
const validateToken = require("../../middleware/validateToken");

router.post(
  "/place_bet",
  validateToken,
  validateSchema(placeBetSchema),
  trippleFunController.placeBet
);
router.post(
  "/get_result",
  validateToken,
  validateSchema(userIDGameIDSchema),
  trippleFunController.getResult
);
router.post(
  "/wallet_history",
  validateToken,
  validateSchema(userIDSchema),
  trippleFunController.walletHistory
);
router.post(
  "/myHistory",
  validateToken,
  validateSchema(userIDSchema),
  trippleFunController.myHistory
);
router.post(
  "/GameHistory",
  validateToken,
  validateSchema(userIDSchema),
  trippleFunController.gameHistory
);

module.exports = router;
