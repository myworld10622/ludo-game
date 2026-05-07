const request = require('request');
const { BASE_URL } = require('../constants/index.js');
const db = require('../models');
const UserModel = db.user;

const INTERNAL_TOKEN = process.env.INTERNAL_API_TOKEN || '';

function laravelPost(url, body, token, callback) {
    request({
        method: 'POST',
        url,
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }, callback);
}

function laravelGet(url, callback) {
    request({ method: 'GET', url }, callback);
}

module.exports = function (ludo_socket) {

    ludo_socket.on('connection', (socket) => {

        // ── Create Private Table ────────────────────────────────────────────
        // Client emits: { user_id, token, fee_amount, max_players }
        socket.on('create-private-table', (msg) => {
            const { user_id, token, fee_amount, max_players } = msg;

            UserModel.findOne({ where: { id: user_id, token, isDeleted: 0 } }).then(user => {
                if (!user) {
                    return socket.emit('private-table-created', { success: false, message: 'Invalid user.' });
                }

                laravelPost(
                    `${BASE_URL}/api/v1/ludo/private-table/create`,
                    { fee_amount, max_players },
                    token,
                    (error, response) => {
                        if (error) {
                            console.error('[PrivateTable] Create error:', error);
                            return socket.emit('private-table-created', { success: false, message: 'Server error.' });
                        }

                        try {
                            const parsed = JSON.parse(response.body);
                            if (!parsed.success) {
                                return socket.emit('private-table-created', { success: false, message: parsed.message || 'Failed.' });
                            }

                            const data = parsed.data;
                            const roomName = `private_table_${data.code}`;
                            socket.join(roomName);

                            console.log(`[PrivateTable] Created: ${data.code} by user ${user_id}`);
                            socket.emit('private-table-created', {
                                success: true,
                                code: data.code,
                                table_id: data.table_id,
                                fee_amount: data.fee_amount,
                                max_players: data.max_players,
                                status: data.status,
                            });
                        } catch (e) {
                            socket.emit('private-table-created', { success: false, message: 'Parse error.' });
                        }
                    }
                );
            });
        });

        // ── Join Private Table by Code ──────────────────────────────────────
        // Client emits: { user_id, token, code }
        socket.on('join-private-table', (msg) => {
            const { user_id, token, code } = msg;

            UserModel.findOne({ where: { id: user_id, token, isDeleted: 0 } }).then(user => {
                if (!user) {
                    return socket.emit('private-table-joined', { success: false, message: 'Invalid user.' });
                }

                laravelPost(
                    `${BASE_URL}/api/v1/ludo/private-table/join`,
                    { code: code.toUpperCase() },
                    token,
                    (error, response) => {
                        if (error) {
                            console.error('[PrivateTable] Join error:', error);
                            return socket.emit('private-table-joined', { success: false, message: 'Server error.' });
                        }

                        try {
                            const parsed = JSON.parse(response.body);
                            if (!parsed.success) {
                                return socket.emit('private-table-joined', { success: false, message: parsed.message || 'Failed.' });
                            }

                            const data = parsed.data;
                            const roomName = `private_table_${data.code}`;
                            socket.join(roomName);

                            console.log(`[PrivateTable] User ${user_id} joined ${data.code} (${data.current_players}/${data.max_players})`);

                            socket.emit('private-table-joined', {
                                success: true,
                                code: data.code,
                                table_id: data.table_id,
                                fee_amount: data.fee_amount,
                                max_players: data.max_players,
                                current_players: data.current_players,
                                prize_pool: data.prize_pool,
                                status: data.status,
                            });

                            // Notify all waiting players in room
                            ludo_socket.in(roomName).emit('private-table-player-joined', {
                                current_players: data.current_players,
                                max_players: data.max_players,
                            });

                            // All players joined — start game
                            if (data.ready_to_start) {
                                console.log(`[PrivateTable] Table ${data.code} full — starting game`);
                                ludo_socket.in(roomName).emit('private-table-start', {
                                    table_id: data.table_id,
                                    code: data.code,
                                    prize_pool: data.prize_pool,
                                    winner_prize: Math.round(data.prize_pool * 0.80),
                                });
                            }
                        } catch (e) {
                            socket.emit('private-table-joined', { success: false, message: 'Parse error.' });
                        }
                    }
                );
            });
        });

        // ── Get Table Info by Code (also joins socket room for waiting) ────────
        // Client emits: { code }
        socket.on('get-private-table-info', (msg) => {
            const { code } = msg;
            const upperCode = code.toUpperCase();
            const roomName = `private_table_${upperCode}`;

            // Join the socket room so this client receives player-joined / start events
            socket.join(roomName);
            console.log(`[PrivateTable] Socket rejoined/joined room ${roomName}`);

            laravelGet(`${BASE_URL}/api/v1/ludo/private-table/${upperCode}`, (error, response) => {
                if (error) {
                    return socket.emit('private-table-info', { success: false, message: 'Server error.' });
                }
                try {
                    const parsed = JSON.parse(response.body);
                    socket.emit('private-table-info', parsed);
                } catch (e) {
                    socket.emit('private-table-info', { success: false, message: 'Parse error.' });
                }
            });
        });

        // ── Complete Private Table ──────────────────────────────────────────
        // Called by Ludo game logic after winner determined
        // msg: { table_id, winner_id }
        socket.on('complete-private-table', (msg) => {
            const { table_id, winner_id } = msg;

            request({
                method: 'POST',
                url: `${BASE_URL}/api/internal/v1/ludo/private-table/complete`,
                headers: {
                    'X-Internal-Token': INTERNAL_TOKEN,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ table_id, winner_id }),
            }, (error, response) => {
                if (error) {
                    console.error('[PrivateTable] Complete error:', error);
                    return;
                }
                try {
                    const parsed = JSON.parse(response.body);
                    console.log(`[PrivateTable] Completed table ${table_id}, winner: ${winner_id}, prize: ${parsed.prize_paid}`);
                } catch (e) {}
            });
        });
    });
};
