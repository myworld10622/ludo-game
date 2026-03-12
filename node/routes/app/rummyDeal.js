const express = require('express');
const router = express.Router();
const rummyDealController = require('../../controllers/api/rummyDealController');
const validateToken = require('../../middleware/validateToken');

router.post('/status', validateToken, rummyDealController.status);
router.post('/check', validateToken, rummyDealController.get_table);

module.exports = router;