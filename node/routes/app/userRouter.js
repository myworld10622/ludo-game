const express = require('express');
const router = express.Router();
const userController = require('../../controllers/api/userController');
const validateToken = require('../../middleware/validateToken');

router.post('/info', validateToken, userController.getUser);
router.post('/daily-attendence-report', validateToken, userController.dailyAttendenceReport);

module.exports = router;