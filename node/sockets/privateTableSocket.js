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

function laravelInternalPost(url, body, callback) {
    request({
        method: 'POST',
        url,
        headers: {
            'X-Internal-Token': INTERNAL_TOKEN,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }, callback);
}

function laravelGet(url, callback) {
    request({ method: 'GET', url }, callback);
}

function parseBody(body) {
    try { return JSON.parse(body); } catch (e) { return null; }
}

function normalizeMsg(msg) {
    if (!msg) return {};
    if (typeof msg === 'string') {
        try { return JSON.parse(msg); } catch (e) { return {}; }
    }
    return msg;
}

module.exports = function (ludo_socket) {

    ludo_socket.on('connection', (socket) => {

        // ── Create Private Table ────────────────────────────────────────────
        // Client emits: { user_id, token, fee_amount, max_players }
        socket.on('create-private-table', (rawMsg) => {
            const msg = normalizeMsg(rawMsg);
            const { user_id, token, fee_amount, max_players } = msg;

            UserModel.findOne({ where: { id: user_id, token, isDeleted: 0 } }).then(user => {
                if (!user) {
                    return socket.emit('private-table-created', { success: false, message: 'Invalid user or session expired.' });
                }

                laravelPost(
                    `${BASE_URL}/api/v1/ludo/private-table/create`,
                    { fee_amount, max_players },
                    token,
                    (error, response) => {
                        if (error) {
                            console.error('[PrivateTable] Create error:', error);
                            return socket.emit('private-table-created', { success: false, message: 'Server error. Please try again.' });
                        }

                        const parsed = parseBody(response.body);
                        if (!parsed) {
                            return socket.emit('private-table-created', { success: false, message: 'Unexpected server response.' });
                        }

                        if (!parsed.success) {
                            return socket.emit('private-table-created', {
                                success: false,
                                message: parsed.message || 'Failed to create table.',
                                error_code: parsed.error_code || null,
                            });
                        }

                        const data = parsed.data;
                        const roomName = `private_table_${data.code}`;
                        socket.join(roomName);

                        console.log(`[PrivateTable] Created: ${data.code} by user ${user_id} (fee=${data.fee_amount}, max=${data.max_players})`);
                        socket.emit('private-table-created', {
                            success: true,
                            message: parsed.message,
                            code: data.code,
                            table_id: data.table_id,
                            fee_amount: data.fee_amount,
                            max_players: data.max_players,
                            status: data.status,
                        });
                    }
                );
            }).catch(err => {
                console.error('[PrivateTable] User lookup error:', err);
                socket.emit('private-table-created', { success: false, message: 'Server error. Please try again.' });
            });
        });

        // ── Join Private Table by Code ──────────────────────────────────────
        // Client emits: { user_id, token, code }
        socket.on('join-private-table', (rawMsg) => {
            const msg = normalizeMsg(rawMsg);
            const { user_id, token, code } = msg;

            UserModel.findOne({ where: { id: user_id, token, isDeleted: 0 } }).then(user => {
                if (!user) {
                    return socket.emit('private-table-joined', { success: false, message: 'Invalid user or session expired.' });
                }

                laravelPost(
                    `${BASE_URL}/api/v1/ludo/private-table/join`,
                    { code: code.toUpperCase() },
                    token,
                    (error, response) => {
                        if (error) {
                            console.error('[PrivateTable] Join error:', error);
                            return socket.emit('private-table-joined', { success: false, message: 'Server error. Please try again.' });
                        }

                        const parsed = parseBody(response.body);
                        if (!parsed) {
                            return socket.emit('private-table-joined', { success: false, message: 'Unexpected server response.' });
                        }

                        if (!parsed.success) {
                            return socket.emit('private-table-joined', {
                                success: false,
                                message: parsed.message || 'Failed to join table.',
                                error_code: parsed.error_code || null,
                            });
                        }

                        const data = parsed.data;
                        const roomName = `private_table_${data.code}`;
                        socket.join(roomName);

                        console.log(`[PrivateTable] User ${user_id} joined ${data.code} (${data.current_players}/${data.max_players})`);

                        socket.emit('private-table-joined', {
                            success: true,
                            message: parsed.message,
                            code: data.code,
                            table_id: data.table_id,
                            fee_amount: data.fee_amount,
                            max_players: data.max_players,
                            current_players: data.current_players,
                            prize_pool: data.prize_pool,
                            status: data.status,
                        });

                        // Notify everyone else in the room that a new player joined
                        socket.to(roomName).emit('private-table-player-joined', {
                            current_players: data.current_players,
                            max_players: data.max_players,
                        });

                        // All players ready — capture holds then start game
                        if (parsed.ready_to_start) {
                            console.log(`[PrivateTable] Table ${data.code} full — capturing fees and starting game`);

                            laravelInternalPost(
                                `${BASE_URL}/api/internal/v1/ludo/private-table/start`,
                                { table_id: data.table_id },
                                (startError, startResponse) => {
                                    const startParsed = parseBody(startResponse && startResponse.body);

                                    if (startError || !startParsed || !startParsed.success) {
                                        console.error(`[PrivateTable] Start failed for table ${data.table_id}:`,
                                            startError || (startParsed && startParsed.message));
                                        // Still emit start — game can run; fee capture will be retried or handled manually
                                    }

                                    ludo_socket.in(roomName).emit('private-table-start', {
                                        table_id: data.table_id,
                                        code: data.code,
                                        prize_pool: data.prize_pool,
                                        winner_prize: Math.round(data.prize_pool * 0.80),
                                    });
                                }
                            );
                        }
                    }
                );
            }).catch(err => {
                console.error('[PrivateTable] User lookup error:', err);
                socket.emit('private-table-joined', { success: false, message: 'Server error. Please try again.' });
            });
        });

        // ── Leave Private Table ─────────────────────────────────────────────
        // Client emits: { user_id, token, code }
        socket.on('leave-private-table', (rawMsg) => {
            const msg = normalizeMsg(rawMsg);
            const { user_id, token, code } = msg;

            UserModel.findOne({ where: { id: user_id, token, isDeleted: 0 } }).then(user => {
                if (!user) {
                    return socket.emit('private-table-left', { success: false, message: 'Invalid user.' });
                }

                laravelPost(
                    `${BASE_URL}/api/v1/ludo/private-table/leave`,
                    { code: code.toUpperCase() },
                    token,
                    (error, response) => {
                        if (error) {
                            console.error('[PrivateTable] Leave error:', error);
                            return socket.emit('private-table-left', { success: false, message: 'Server error.' });
                        }

                        const parsed = parseBody(response.body);
                        if (!parsed || !parsed.success) {
                            return socket.emit('private-table-left', {
                                success: false,
                                message: (parsed && parsed.message) || 'Failed to leave table.',
                            });
                        }

                        const data = parsed.data;
                        const roomName = `private_table_${data.code}`;

                        console.log(`[PrivateTable] User ${user_id} left ${data.code} (${data.current_players}/${data.max_players})`);

                        socket.emit('private-table-left', {
                            success: true,
                            message: parsed.message,
                        });

                        socket.leave(roomName);

                        // Notify remaining players
                        ludo_socket.in(roomName).emit('private-table-player-left', {
                            current_players: data.current_players,
                            max_players: data.max_players,
                        });
                    }
                );
            }).catch(err => {
                console.error('[PrivateTable] User lookup error:', err);
                socket.emit('private-table-left', { success: false, message: 'Server error.' });
            });
        });

        // ── Get Table Info by Code ──────────────────────────────────────────
        // Client emits: { code }
        socket.on('get-private-table-info', (rawMsg) => {
            const msg = normalizeMsg(rawMsg);
            const { code } = msg;
            const upperCode = code.toUpperCase();
            const roomName = `private_table_${upperCode}`;

            socket.join(roomName);

            laravelGet(`${BASE_URL}/api/v1/ludo/private-table/${upperCode}`, (error, response) => {
                if (error) {
                    return socket.emit('private-table-info', { success: false, message: 'Server error.' });
                }
                const parsed = parseBody(response.body);
                socket.emit('private-table-info', parsed || { success: false, message: 'Parse error.' });
            });
        });

        // ── Complete Private Table ──────────────────────────────────────────
        // Called by Ludo game logic after winner determined
        // msg: { table_id, winner_id }
        socket.on('complete-private-table', (rawMsg) => {
            const msg = normalizeMsg(rawMsg);
            const { table_id, winner_id } = msg;

            laravelInternalPost(
                `${BASE_URL}/api/internal/v1/ludo/private-table/complete`,
                { table_id, winner_id },
                (error, response) => {
                    if (error) {
                        console.error('[PrivateTable] Complete error:', error);
                        return;
                    }
                    const parsed = parseBody(response.body);
                    if (parsed && parsed.success) {
                        console.log(`[PrivateTable] Completed table ${table_id}, winner: ${winner_id}, prize: ${parsed.prize_paid}`);
                    } else {
                        console.error(`[PrivateTable] Complete failed for table ${table_id}:`, parsed && parsed.message);
                    }
                }
            );
        });
    });
};
