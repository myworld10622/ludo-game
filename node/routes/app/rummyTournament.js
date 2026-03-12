const express = require('express');
const router = express.Router();
const rummyTournamentController = require('../../controllers/api/rummyTournamentController');
const validateToken = require('../../middleware/validateToken');

router.post('/', validateToken, rummyTournamentController.getTournaments);
router.post('/status', validateToken, rummyTournamentController.status);
router.post('/participate', validateToken, rummyTournamentController.participate);
router.post('/info', rummyTournamentController.info);
router.post('/types', rummyTournamentController.types);
router.post('/check', rummyTournamentController.get_table);
router.get('/winners/:tournementId', rummyTournamentController.getTournementWinners);

module.exports = router;