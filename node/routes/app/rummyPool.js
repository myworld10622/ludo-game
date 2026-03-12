const express = require('express');
const router = express.Router();
const rummyPoolController = require('../../controllers/api/rummyPoolController');
const validateToken = require('../../middleware/validateToken');

router.post('/status', validateToken, rummyPoolController.status);
router.post('/check', validateToken, rummyPoolController.get_table);

module.exports = router;