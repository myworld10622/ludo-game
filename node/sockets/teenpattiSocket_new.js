
const db = require('../models')
const teenpattiController = require('../controllers/api/teenpattiController');
const { getRandomNumber } = require('../utils/util');
const UserModel = db.user

module.exports = function (teenpatti_socket, request, timer, BASE_URL) {

    var timeoutArr = {};
    var intervalArr = {};
    var users = {};
    const timeout_time = 30000;
    var rummy_timer = (timeout_time / 1000);
    var interval_id;

    teenpatti_socket.on('connection', (socket) => {
        console.log("New Connection Teenpatti ", socket.id)

        socket.recovered = true;

        socket.on("ping", (callback) => {
            socket.emit('ping', callback);
        });

        socket.on('get-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var socket_id = socket.id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var res = {};

            users[socket_id] = user_id;
            users[user_id] = socket_id;

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    teenpattiController.getTable({
                        user_id,
                        token,
                        boot_value
                    }).then(response => {
                        console.log("get-table", JSON.stringify(response));
                        socket.emit('get-table', JSON.stringify(response));
                        var body = response;
                        if (body.table_data !== undefined) {
                            socket.join("room" + body.table_data[0].table_id);
                            teenpatti_socket.in("room" + body.table_data[0].table_id).emit('trigger', "call_status");
                            // console.log("get-table Tigger","room"+body.table_data[0].table_id);
                            // console.log(teenpatti_socket.sockets.adapter);
                            var clients = teenpatti_socket.adapter.rooms.get("room" + body.table_data[0].table_id);
                            var numClients = clients ? clients.size : 0;
                            console.log("No of Users - ", numClients);
                        }
                    }).catch(error => console.log(error));
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-table', res);
                }

            });
        });

        socket.on('get-customise-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    teenpattiController.getCustomiseTable({
                        user_id,
                        token,
                        boot_value
                    }).then(response => {
                        console.log("get-customise-table", JSON.stringify(response));
                        socket.emit('get-customise-table', JSON.stringify(response));
                        var body = response;
                        socket.join("room" + body.table_data[0].table_id);
                        teenpatti_socket.in("room" + body.table_data[0].table_id).emit('trigger', "call_status");
                        var clients = teenpatti_socket.adapter.rooms.get("room" + body.table_data[0].table_id);
                        var numClients = clients ? clients.size : 0;
                        console.log("No of Users - ", numClients);
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-customise-table', res);
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
                    teenpattiController.getPrivateTable({
                        user_id,
                        token,
                        boot_value
                    }).then(response => {
                        console.log("get-private-table", JSON.stringify(response));
                        socket.emit('get-private-table', JSON.stringify(response));
                        var body = response;
                        socket.join("room" + body.table_data[0].table_id);
                        teenpatti_socket.in("room" + body.table_data[0].table_id).emit('trigger', "call_status");
                        var clients = teenpatti_socket.adapter.rooms.get("room" + body.table_data[0].table_id);
                        var numClients = clients ? clients.size : 0;
                        console.log("No of Users - ", numClients);
                    }).catch(error => {
                        console.log(error)
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
                    teenpattiController.joinTable({
                        user_id,
                        table_id,
                        token
                    }).then(response => {
                        console.log("join-table", JSON.stringify(response));
                        socket.emit('join-table', JSON.stringify(response));
                        var body = response;
                        socket.join("room" + body.table_data[0].table_id);
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('join-table', res);
                }

            });
        });

        socket.on('start-game', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var blind_1 = msg.blind_1;
            var token = msg.token;
            var table_id = msg.table_id;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.startGame({
                        user_id,
                        token
                    }).then(response => {
                        console.log("start-game", JSON.stringify(response));
                        socket.emit('start-game', JSON.stringify(response));
                        var body = response;
                        if (body.game_id !== undefined) {
                            var roomname = "room" + userData.table_id;
                            teenpatti_socket.in(roomname).emit('trigger', "" + body.game_id);
                            if (timeoutArr[roomname] !== undefined) {
                                console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                timeoutArr[roomname].stop();
                                clearInterval(intervalArr[roomname]);
                            }
                            timeoutArr[roomname] = new timer(function () { autochaal(userData.table_id) }, timeout_time)
                            intervalArr[roomname] = setInterval(function () { teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                        } else {
                            var roomname = "room" + userData.table_id;
                            teenpatti_socket.in("room" + roomname).emit('trigger', "call_status");
                            if (timeoutArr[roomname] == undefined) {
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.table_id) }, timeout_time)
                                intervalArr[roomname] = setInterval(function () { teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                            }
                        }
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('start-game', res);
                }
            });
        });

        socket.on('chaal', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var plus = msg.plus;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var roomname = "room" + userData.table_id;
                    teenpattiController.chaal({
                        user_id,
                        plus,
                        token
                    }).then(response => {
                        socket.emit('chaal', JSON.stringify(response));
                        var body = response;
                        teenpatti_socket.in(roomname).emit('trigger', "call_status");

                        if (timeoutArr[roomname] !== undefined && body.code == 200) {
                            console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                        if (body.message != 'Pot Show' && body.code == 200) {
                            if (body.bot_id) {
                                const botTimer = getRandomNumber(6, 10) * 1000;
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.table_id) }, timeout_time)
                                setTimeout(() => {
                                    botChaal(body.table_id, body.game_id, body.user_id, body.bot_id, body.amount)
                                }, botTimer);
                            } else {
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.table_id) }, timeout_time)
                            }
                            intervalArr[roomname] = setInterval(function () { teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                        }
                    }).catch(error => console.log(error))

                    setTimeout(function () {
                        teenpatti_socket.in(roomname).emit('trigger', "call_status");
                    }, 2000);
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('chaal', res);
                }

            });
        });

        socket.on('show-game', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.showGame({
                        user_id,
                        token
                    }).then(response => {
                        console.log("show-game", JSON.stringify(response));
                        socket.emit('show-game', JSON.stringify(response));
                        var roomname = "room" + userData.table_id;
                        teenpatti_socket.in(roomname).emit('trigger', "call_status");

                        if (timeoutArr[roomname] !== undefined) {
                            console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('show-game', res);
                }
            });
        });

        socket.on('do-slide-show', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var slide_id = msg.slide_id;
            var type = msg.type;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.doSlideShow({
                        user_id,
                        token,
                        slide_id,
                        type
                    }).then(response => {
                        console.log("do-slide-show", JSON.stringify(response));
                        socket.emit('do-slide-show', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('do-slide-show', res);
                }
            });
        });

        socket.on('slide-show', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var prev_user_id = msg.prev_user_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.slideShow({
                        user_id,
                        token,
                        prev_user_id
                    }).then(response => {
                        console.log("slide-show", JSON.stringify(response));
                        socket.emit('slide-show', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('slide-show', res);
                }
            });
        });

        socket.on('switch-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.switchTable({
                        user_id,
                        token
                    }).then(response => {
                        console.log("switch-table", JSON.stringify(response));
                        socket.emit('switch-table', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => {
                        console.log(error)
                    })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('switch-table', res);
                }
            });
        });

        socket.on('see-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.seeCard({
                        user_id,
                        token
                    }).then(response => {
                        console.log("see-card", JSON.stringify(response));
                        socket.emit('see-card', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('see-card', res);
                }
            });
        });

        socket.on('leave-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var game_id = msg.game_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.leaveTable({
                        user_id,
                        token
                    }).then(response => {
                        console.log("leave-game", JSON.stringify(response));
                        socket.emit('leave-table', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                        socket.leave("room" + userData.table_id);
                    }).catch(error => console.log(error));
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
            var timeout = msg.timeout;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.packGame({
                        user_id,
                        token,
                        timeout
                    }).then(response => {
                        console.log("pack-game", JSON.stringify(response));
                        socket.emit('pack-game', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('pack-game', res);
                }
            });
        });

        socket.on('tip', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var tip = msg.tip;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.tip({
                        user_id,
                        token,
                        tip
                    }).then(response => {
                        console.log("tip", JSON.stringify(response));
                        socket.emit('tip', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('tip', res);
                }
            });
        });

        socket.on('chat', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var chat = msg.chat;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    teenpattiController.chat({
                        user_id,
                        chat,
                        token
                    }).then(response => {
                        console.log("chat", JSON.stringify(response));
                        socket.emit('chat', JSON.stringify(response));
                        teenpatti_socket.in("room" + userData.table_id).emit('trigger', "call_status");
                    }).catch(error => console.log(error))
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('chat', res);
                }
            });
        });

        socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });

        function autochaal(room_id) {
            teenpattiController.autoChaal(room_id)
                .then(response => {
                    console.log("auto-chaal", response);
                    teenpatti_socket.in("room" + room_id).emit('trigger', "call_status");
                    var roomname = "room" + room_id;

                    timeoutArr[roomname].stop();
                    clearInterval(intervalArr[roomname]);

                    var clients = teenpatti_socket.adapter.rooms.get("room" + room_id);
                    var numClients = clients ? clients.size : 0;
                    console.log("Stop Autochaal No of Users - ", numClients);
                    if (response == 'Running') {
                        timeoutArr[roomname] = new timer(function () { autochaal(room_id) }, timeout_time)
                        intervalArr[roomname] = setInterval(function () { teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                    }
                });
        }

        function botChaal(table_id, game_id, user_id, bot_id, amount) {
            var roomname = "room" + table_id;
            teenpattiController.botChaal({
                user_id,
                bot_id,
                amount,
                game_id,
                table_id
            }).then(response => {
                socket.emit('chaal', JSON.stringify(response));
                var body = response;
                teenpatti_socket.in(roomname).emit('trigger', "call_status");

                if (timeoutArr[roomname] !== undefined && body.code == 200) {
                    console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                    timeoutArr[roomname].stop();
                    clearInterval(intervalArr[roomname]);
                }
                if (body.message != 'Pot Show' && body.code == 200) {
                    timeoutArr[roomname] = new timer(function () { autochaal(table_id) }, timeout_time)
                    intervalArr[roomname] = setInterval(function () { teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                }
            }).catch(error => console.log(error))

            setTimeout(function () {
                teenpatti_socket.in(roomname).emit('trigger', "call_status");
            }, 2000);
        }
    });
}
