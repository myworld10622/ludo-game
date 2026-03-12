const express = require('express');
const router = express.Router();
const teenpattiController = require('../../controllers/api/teenpattiController');
const validateToken = require('../../middleware/validateToken');

router.post('/status', validateToken, teenpattiController.status);
router.post('/get_table_master', validateToken, teenpattiController.getTableMaster);
router.post('/history', validateToken, teenpattiController.walletHistory);

module.exports = router;