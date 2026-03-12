const rummyPoolController = require('../controllers/api/rummyPoolController');
const db = require('../models')
const Sequelize = require('sequelize');
const UserModel = db.user

var timeoutArr = {};
var intervalArr = {};
const timeout_time = 30000;
const wait_after_start = 4000;
var rummy_timer = (timeout_time / 1000);
var interval_id;

module.exports = function (rummy_pool_socket, request, timer, BASE_URL) {

    rummy_pool_socket.on('connection', (socket) => {
        console.log("New Connection Rummy Pool ", socket.id)

        socket.on('get-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var no_of_players = msg.no_of_players;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    rummyPoolController.getTable({
                        user_id,
                        token,
                        no_of_players,
                        boot_value
                    })
                        .then((response) => {
                            var body = response;
                            console.log("get-table", body);
                            socket.emit('get-table', JSON.stringify(body));
                            if (body.code != 406) {
                                var rummy_pool_table_id = body.table_data[0].table_id;
                                socket.join("room" + rummy_pool_table_id);
                                rummy_pool_socket.in("room" + rummy_pool_table_id).emit('trigger', "call_status");
                                var clients = rummy_pool_socket.adapter.rooms.get("room" + rummy_pool_table_id);
                                const numClients = clients ? clients.size : 0;
                                console.log("No of Users - ", numClients);

                                // if(numClients>1){
                                setTimeout(function () {
                                    rummyPoolController.startGame({
                                        user_id,
                                        token,
                                    }).then(response => {
                                        var body = response
                                        console.log("start-game", body);
                                        socket.emit('start-game', JSON.stringify(body));
                                        if (body.game_id !== undefined) {
                                            // rummy_pool_socket.in("room" + rummy_pool_table_id).emit('trigger', "" + body.game_id);

                                            var roomname = "room" + rummy_pool_table_id;
                                            rummy_pool_socket.in(roomname).emit('trigger', "" + body.game_id);

                                            setTimeout(function () {
                                                if (timeoutArr[roomname] !== undefined) {
                                                    console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                                    timeoutArr[roomname].stop();
                                                    clearInterval(intervalArr[roomname]);
                                                }
                                                timeoutArr[roomname] = new timer(function () { autochaal(rummy_pool_table_id) }, timeout_time)
                                                intervalArr[roomname] = setInterval(function () { rummy_pool_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                                            }, wait_after_start);
                                        } else {
                                            // rummy_pool_socket.in("room" + rummy_pool_table_id).emit('trigger', "call_status");
                                        }
                                    }).catch(error => {
                                        console.log(error)
                                    })
                                }, 8000);
                            }
                        })
                        .catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-table', res);
                }

            });
        });

        socket.on('get-private-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    rummyPoolController.getPrivateTable({
                        user_id,
                        token,
                        boot_value
                    })
                        .then(response => {
                            var body = response;
                            console.log("get-private-table", body);
                            socket.emit('get-private-table', JSON.stringify(body));
                            // var body = JSON.parse(response.body);
                            socket.join("room" + body.table_data[0].table_id);
                            rummy_pool_socket.in("room" + body.table_data[0].table_id).emit('trigger', "call_status");
                            var clients = rummy_pool_socket.adapter.rooms.get("room" + body.table_data[0].table_id);
                            const numClients = clients ? clients.size : 0;
                            console.log("No of Users - ", numClients);
                        }).catch(error => {
                            console.log(error);
                        })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-customise-table', res);
                }
            });
        });

        socket.on('join-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var table_id = msg.table_id;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.joinTable({
                        user_id,
                        table_id,
                        token,
                    })
                        .then(response => {
                            var body = response
                            console.log("join-table", body);
                            socket.emit('join-table', JSON.stringify(body));
                            socket.join("room" + body.table_data[0].table_id);
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        })
                        .catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('join-table', res);
                }

            });
        });

        socket.on('join-table-with-code', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var code = msg.code;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.joinTableWithCode({
                        user_id,
                        code,
                        token,
                    })
                        .then(response => {
                            var body = response;
                            console.log("join-table-with-code", body);
                            socket.emit('join-table-with-code', JSON.stringify(body));

                            socket.join("room" + body.table_data[0].table_id);
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        })
                        .catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('join-table-with-code', res);
                }

            });
        });

        socket.on('start-game', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var blind_1 = msg.blind_1;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.startGame({
                        user_id,
                        token,
                    }).then(response => {
                        var body = response;
                        console.log("start-game", body);
                        socket.emit('start-game', JSON.stringify(body));
                        if (body.game_id !== undefined) {
                            var roomname = "room" + userData.rummy_pool_table_id;
                            rummy_pool_socket.in(roomname).emit('trigger', "" + body.game_id);

                            setTimeout(function () {
                                if (timeoutArr[roomname] !== undefined) {
                                    console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                    timeoutArr[roomname].stop();
                                    clearInterval(intervalArr[roomname]);
                                }
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.rummy_pool_table_id) }, timeout_time)
                                intervalArr[roomname] = setInterval(function () { rummy_pool_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                            }, wait_after_start);
                        } else {
                            // rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }
                    })
                        .catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('start-game', res);
                }

            });
        });

        socket.on('leave-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var game_id = msg.game_id;
            // var table_id = msg.table_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.leaveTable({
                        user_id,
                        token
                    })
                        .then(response => {
                            var body = response;
                            console.log("leave-game", body);
                            socket.emit('leave-table', JSON.stringify(body));

                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                            socket.leave("room" + userData.rummy_pool_table_id);
                        })
                        .catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('leave-table', res);
                }
            });
        });

        socket.on('pack-game', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var game_id = msg.game_id;
            var timeout = 0;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.packGame({
                        user_id,
                        token,
                        timeout
                    }).then(response => {
                        var body = response;
                        console.log("pack-game", body);
                        socket.emit('pack-game', JSON.stringify(body));
                        // var body = JSON.parse(response.body);
                        rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('leave-table', res);
                }
            });
        });

        socket.on('my-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.myCard({
                        user_id,
                        token
                    })
                        .then(response => {
                            var body = response;
                            console.log("my-card value", body);
                            socket.emit('my-card', JSON.stringify(body));
                        }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('my-card', res);
                }
            });
        });

        socket.on('check-my-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.myCard({
                        user_id,
                        token
                    })
                        .then(response => {
                            var body = response;
                            console.log("check-my-card", user_id);
                            socket.emit('check-my-card', JSON.stringify(body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => {
                            console.log(error);
                        })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('check-my-card', res);
                }
            });
        });

        socket.on('card-value', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var card_1 = msg.card_1;
            var card_2 = msg.card_2;
            var card_3 = msg.card_3;
            var card_4 = (msg.card_4 === undefined) ? '' : msg.card_4;
            var card_5 = (msg.card_5 === undefined) ? '' : msg.card_5;
            var card_6 = (msg.card_6 === undefined) ? '' : msg.card_6;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.cardValue({ user_id, token, card_1, card_2, card_3, card_4, card_5, card_6 })
                        .then(response => {
                            var body = response;
                            console.log("card-value", body);
                            socket.emit('card-value', JSON.stringify(body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('card-value', res);
                }
            });
        });

        socket.on('drop-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var card = msg.card;
            var json = msg.json;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.dropCard({
                        user_id,
                        token,
                        json,
                        card
                    })
                        .then(response => {
                            var body = response;
                            console.log("drop-card", body);
                            socket.emit('drop-card', JSON.stringify(body));

                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");

                            var roomname = "room" + userData.rummy_pool_table_id;
                            rummy_pool_socket.in(roomname).emit('trigger', "call_status");

                            // console.log("timeoutArr.roomname",timeoutArr[roomname]);

                            if (timeoutArr[roomname] !== undefined) {
                                console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                timeoutArr[roomname].stop();
                                clearInterval(intervalArr[roomname]);
                            }
                            timeoutArr[roomname] = new timer(function () { autochaal(userData.rummy_pool_table_id) }, timeout_time)
                            intervalArr[roomname] = setInterval(function () { rummy_pool_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                        })
                        .catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('drop-card', res);
                }
            });
        });

        socket.on('get-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.getCard({ user_id, token })
                        .then(response => {
                            var body = response;
                            console.log("get-card", JSON.stringify(body));
                            socket.emit('get-card', JSON.stringify(body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('get-card', res);
                }
            });
        });

        socket.on('get-drop-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.getDropCard({ user_id, token })
                        .then(response => {
                            var body = response;
                            console.log("get-drop-card", JSON.stringify(body));
                            socket.emit('get-drop-card', JSON.stringify(body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('get-drop-card', res);
                }
            });
        });

        socket.on('share-wallet', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};
            console.log("share-wallet", msg);
            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.shareWallet({ user_id, token })
                        .then((response) => {
                            socket.emit('share-wallet', JSON.stringify(response));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('share-wallet', res);
                }
            });
        });

        socket.on('do-share-wallet', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var share_wallet_id = msg.share_wallet_id;
            var type = msg.type;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.doShareWallet({ user_id, share_wallet_id, type, token })
                        .then((response) => {
                            console.log("do-share-wallet", JSON.stringify(response.body));
                            socket.emit('do-share-wallet', JSON.stringify(response.body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('do-share-wallet', res);
                }
            });
        });

        socket.on('declare', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var json = msg.json;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.declare({ user_id, token, json })
                        .then(response => {
                            var body = response;
                            console.log("declare", JSON.stringify(body));
                            socket.emit('declare', JSON.stringify(body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('declare', res);
                }
            });
        });

        socket.on('declare-back', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var json = msg.json;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.declareBack({ user_id, token, json })
                        .then(response => {
                            var body = response;
                            console.log("declare-back", JSON.stringify(body));
                            socket.emit('declare-back', JSON.stringify(body));
                            rummy_pool_socket.in("room" + userData.rummy_pool_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error))

                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('declare-back', res);
                }
            });
        });

        socket.on('status', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var game_id = (msg.game_id === undefined) ? '' : msg.game_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPoolController.getStatus({ user_id, token, game_id })
                        .then(response => {
                            var body = response;
                            console.log("status", JSON.stringify(body));
                            socket.emit('status', JSON.stringify(body));
                        }).catch(error => {
                            console.log(error)
                        })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('drop-card', res);
                }
            });
        });

        socket.on("disconnect", (reason) => {
            console.log("User Disconnected Rummy Pool - " + socket.id + " reason - " + reason);
        });
    });

    function autochaal(room_id) {
        rummyPoolController.rummyAutoChaal(room_id)
            .then(response => {
                console.log("auto-chaal - " + room_id, response);
                rummy_pool_socket.in("room" + room_id).emit('trigger', "call_status");
                var roomname = "room" + room_id;
                // if(timeoutArr.roomname!==undefined){
                timeoutArr[roomname].stop();
                clearInterval(intervalArr[roomname]);
                // }
                var clients = rummy_pool_socket.adapter.rooms.get("room" + room_id);
                const numClients = clients ? clients.size : 0;
                console.log("Stop Autochaal No of Users - ", numClients);
                if (response == 'Running') {
                    timeoutArr[roomname] = new timer(function () { autochaal(room_id) }, timeout_time)
                    intervalArr[roomname] = setInterval(function () { rummy_pool_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                }
            }).catch(error => console.log(error));
    }
}