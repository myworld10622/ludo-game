const express = require('express');
const router = express.Router();
const authController = require('../../controllers/api/authController');
const { loginSchema, sendOtpSchema } = require('../../requests/auth');
const validateSchema = require('../../middleware/validationMiddleware');

router.post('/login', validateSchema(loginSchema), authController.login);
router.post('/send-otp', validateSchema(sendOtpSchema), authController.otp);

module.exports = router;