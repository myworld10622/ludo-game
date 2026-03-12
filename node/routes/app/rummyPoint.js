const express = require('express');
const router = express.Router();
const rummyPointController = require('../../controllers/api/rummyPointController');
const validateToken = require('../../middleware/validateToken');

router.post('/status', validateToken, rummyPointController.status);

module.exports = router;