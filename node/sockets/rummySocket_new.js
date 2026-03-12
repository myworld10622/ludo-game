
const rummyPointController = require('../controllers/api/rummyPointController');
const db = require('../models')
const Sequelize = require('sequelize');
const UserModel = db.user

var timeoutArr = {};
var intervalArr = {};
const timeout_time = 30000;
const bot_timeout = 10000;
const start_game_time = 8000;
const wait_after_start = 4000;
var rummy_timer = (timeout_time / 1000);
var interval_id;

module.exports = function (rummy_socket, request, timer, BASE_URL) {

    rummy_socket.on('connection', (socket) => {
        console.log("New Connection Rummy ", socket.id)

        socket.on('join-room', msg => {

            var user_id = msg.user_id;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    // console.log(userData.rummy_table_id);
                    socket.join("room" + userData.rummy_table_id);
                    console.log("join-room", "User " + user_id + " Room " + userData.rummy_table_id + " Joined Successfully");
                    socket.emit('join-room', "User " + user_id + " Room " + userData.rummy_table_id + " Joined Successfully");
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('join-room', res);
                }

            });
        });

        socket.on('get-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var no_of_players = msg.no_of_players;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {

                    // update socket_id of user
                    UserModel.update(
                        { socket_id: socket.id },
                        { where: { id: user_id } }
                    ).then(result => { })
                    // end update socket_id of user
                    // var userData = user.toJSON();
                    // console.log(userData.rummy_table_id);
                    rummyPointController.getTable({
                        user_id,
                        token,
                        no_of_players,
                        boot_value
                    })
                        .then((response) => {
                            var body = response;
                            console.log("get-table", body);
                            socket.emit('get-table', JSON.stringify(body));
                            if (body.table_data !== undefined) {
                                var rummy_table_id = body.table_data[0].table_id;
                                socket.join("room" + rummy_table_id);
                                rummy_socket.in("room" + rummy_table_id).emit('trigger', "call_status");
                                var clients = rummy_socket.adapter.rooms.get("room" + rummy_table_id);
                                const numClients = clients ? clients.size : 0;
                                console.log("No of Users get - ", numClients);

                                // if(numClients>1){
                                // var userData = user.toJSON();
                                var roomname = "room" + rummy_table_id;

                                timeoutArr[roomname] = new timer(function () {
                                    clearInterval(intervalArr[roomname]);
                                    rummyPointController.startGame({
                                        user_id,
                                        token,
                                    }).then(response => {
                                        var body = response
                                        console.log("start-game", body);
                                        socket.emit('start-game', JSON.stringify(body));
                                        // var body = JSON.parse(response.body);
                                        if (body.game_id !== undefined) {
                                            console.log("start-game-trigger", body.game_id);
                                            // rummy_socket.in("room" + rummy_table_id).emit('trigger', "" + body.game_id);
                                            rummy_socket.in(roomname).emit('trigger', "" + body.game_id);

                                            setTimeout(function () {
                                                if (timeoutArr[roomname] !== undefined) {
                                                    console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                                    timeoutArr[roomname].stop();
                                                    clearInterval(intervalArr[roomname]);
                                                }
                                                timeoutArr[roomname] = new timer(function () { autochaal(rummy_table_id) }, timeout_time)
                                                intervalArr[roomname] = setInterval(function () { rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                                            }, wait_after_start);
                                        } else {
                                            console.log("rummy timeoutArr[roomname] - ", timeoutArr[roomname]);
                                            console.log("rummy timeoutArr[roomname] time remaining- ", timeoutArr[roomname].getTimeLeft());
                                            console.log("body.message - ", body.message);
                                            console.log("rummy_table_id - ", rummy_table_id);
                                            if(body.message=='Active Game is Going On' && (timeoutArr[roomname]==undefined || timeoutArr[roomname].getTimeLeft()==0)){
                                                autochaal(rummy_table_id);
                                                
                                                timeoutArr[roomname] = new timer(function() { autochaal(rummy_table_id) }, timeout_time);
                                                intervalArr[roomname] = setInterval(function(){rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft()/1000))}, 1000);
                                            }
                                            // rummy_socket.in("room" + rummy_table_id).emit('trigger', "call_status");
                                        }
                                    }).catch(error => {
                                        console.log(error)
                                    })
                                }, start_game_time)

                                clearInterval(intervalArr[roomname]);
                                intervalArr[roomname] = setInterval(function () { rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                            }
                        }).catch(error => {
                            console.log(error);
                        })
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
                    rummyPointController.getPrivateTable({
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
                            rummy_socket.in("room" + body.table_data[0].table_id).emit('trigger', "call_status");
                            var clients = rummy_socket.adapter.rooms.get("room" + body.table_data[0].table_id);
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
                    rummyPointController.joinTable({
                        user_id,
                        table_id,
                        token,
                    })
                        .then(response => {
                            var body = response
                            console.log("join-table", body);
                            socket.emit('join-table', JSON.stringify(body));
                            // var body = JSON.parse(response.body);
                            if (body.table_data !== undefined) {
                                var rummy_table_id = body.table_data[0].table_id;
                                socket.join("room" + rummy_table_id);
                                rummy_socket.in("room" + rummy_table_id).emit('trigger', "call_status");

                                var clients = rummy_socket.adapter.rooms.get("room" + rummy_table_id);
                                const numClients = clients ? clients.size : 0;
                                console.log("No of Users get - ", numClients);

                                if (numClients > 1) {
                                    // var userData = user.toJSON();
                                    rummyPointController.startGame({
                                        user_id,
                                        token,
                                    }).then(response => {
                                        var body = response;
                                        console.log("start-game", body);
                                        socket.emit('start-game', JSON.stringify(body));
                                        // var body = JSON.parse(response.body);
                                        if (body.game_id !== undefined) {
                                            console.log("start-game-trigger", body.game_id);
                                            // rummy_socket.in("room" + rummy_table_id).emit('trigger', "" + body.game_id);

                                            var roomname = "room" + rummy_table_id;
                                            rummy_socket.in(roomname).emit('trigger', "" + body.game_id);
                                            if (timeoutArr[roomname] !== undefined) {
                                                console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                                timeoutArr[roomname].stop();
                                                clearInterval(intervalArr[roomname]);
                                            }
                                            timeoutArr[roomname] = new timer(function () { autochaal(rummy_table_id) }, timeout_time)
                                            intervalArr[roomname] = setInterval(function () { rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                                        } else {
                                            rummy_socket.in("room" + rummy_table_id).emit('trigger', "call_status");
                                        }
                                    }).catch(error => {
                                        console.log(error)
                                    })
                                }
                            }
                        }).catch(error => {
                            console.log(error)
                        })
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
                    rummyPointController.joinTableWithCode({
                        user_id,
                        code,
                        token,
                    })
                        .then(response => {
                            var body = response;
                            console.log("join-table-with-code", body);
                            socket.emit('join-table-with-code', JSON.stringify(body));
                            // var body = JSON.parse(response.body);
                            socket.join("room" + body.table_data[0].table_id);
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                        }).catch(error => {
                            console.log(error)
                        })
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
                    rummyPointController.startGame({
                        user_id,
                        token,
                    }).then(response => {
                        var body = response;
                        console.log("start-game", body);
                        socket.emit('start-game', JSON.stringify(body));
                        // var body = JSON.parse(response.body);
                        if (body.game_id !== undefined) {
                            var roomname = "room" + userData.rummy_table_id;
                            rummy_socket.in(roomname).emit('trigger', "" + body.game_id);

                            setTimeout(function () {
                                if (timeoutArr[roomname] !== undefined) {
                                    console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                    timeoutArr[roomname].stop();
                                    clearInterval(intervalArr[roomname]);
                                }
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.rummy_table_id) }, timeout_time);
                                intervalArr[roomname] = setInterval(function () { rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                            }, wait_after_start);
                        } else {
                            // rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                        }
                    }).catch(error => {
                        console.log(error);
                    })
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
                    rummyPointController.leaveTable({
                        user_id,
                        token
                    })
                        .then(response => {
                            var body = response;
                            console.log("leave-game", body);
                            socket.emit('leave-table', JSON.stringify(body));
                            // var body = JSON.parse(response.body);
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                            socket.leave("room" + userData.rummy_table_id);
                        }).catch(error => {
                            console.log(error)
                        })
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
            var json = msg.json;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPointController.packGame({
                        user_id,
                        token,
                        json
                    }).then(response => {
                        var body = response;
                        console.log("pack-game", body);
                        socket.emit('pack-game', JSON.stringify(body));
                        // var body = JSON.parse(response.body);
                        rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                    })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('pack-game', res);
                }
            });
        });

        socket.on('wrong-delclare', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var json = msg.json;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPointController.wrongDeclare({
                        user_id,
                        token,
                        json
                    })
                        .then(response => {
                            var body = response;
                            console.log("wrong-delclare", body);
                            socket.emit('wrong-delclare', JSON.stringify(body));
                            // console.log(response.body);
                            // var body = JSON.parse(response.body);
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                        })
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('wrong-delclare', res);
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
                    rummyPointController.myCard({
                        user_id,
                        token
                    })
                        .then(response => {
                            var body = response;
                            console.log("my-card", user_id);
                            console.log("my-card value", body);
                            socket.emit('my-card', JSON.stringify(body));
                            // rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                            console.log("trigger room - " + userData.rummy_table_id + " socket.rooms -", socket.rooms);
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
                    rummyPointController.myCard({
                        user_id,
                        token
                    })
                        .then(response => {
                            var body = response;
                            console.log("check-my-card", user_id);
                            socket.emit('check-my-card', JSON.stringify(body));
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                            console.log("trigger room - " + userData.rummy_table_id + " socket.rooms -", socket.rooms);
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
                    rummyPointController.cardValue({ user_id, token, card_1, card_2, card_3, card_4, card_5, card_6 })
                        .then(response => {
                            var body = response;
                            console.log("card-value", body);
                            socket.emit('card-value', JSON.stringify(body));
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
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
            var json = msg.json;
            var card = msg.card;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    rummyPointController.dropCard({
                        user_id,
                        token,
                        json,
                        card
                    })
                        .then(response => {
                            var body = response;
                            console.log("drop-card", body);
                            socket.emit('drop-card', JSON.stringify(body));
                            // var body = JSON.parse(response.body);

                            var roomname = "room" + userData.rummy_table_id;
                            rummy_socket.in(roomname).emit('trigger', "call_status");

                            // console.log("timeoutArr.roomname",timeoutArr[roomname]);

                            if (timeoutArr[roomname] !== undefined) {
                                console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                timeoutArr[roomname].stop();
                                clearInterval(intervalArr[roomname]);
                            }

                            if (body.bot !== undefined && body.bot == 1) {
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.rummy_table_id) }, bot_timeout)
                            }
                            else {
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.rummy_table_id) }, timeout_time)
                            }
                            intervalArr[roomname] = setInterval(function () { rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                        }).catch(error => console.log(error))

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
                    rummyPointController.getCard({ user_id, token })
                        .then(response => {
                            var body = response;
                            console.log("get-card", JSON.stringify(body));
                            socket.emit('get-card', JSON.stringify(body));
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error))
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
                    rummyPointController.getDropCard({ user_id, token })
                        .then(response => {
                            var body = response;
                            console.log("get-drop-card", JSON.stringify(body));
                            socket.emit('get-drop-card', JSON.stringify(body));
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                        }).catch(error => console.log(error))

                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('get-drop-card', res);
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
                    rummyPointController.declare({ user_id, token, json })
                        .then(response => {
                            var body = response;
                            console.log("declare", JSON.stringify(body));
                            socket.emit('declare', JSON.stringify(body));
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
                        })
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
                    rummyPointController.declareBack({ user_id, token, json })
                        .then(response => {
                            var body = response;
                            console.log("declare-back", JSON.stringify(body));
                            socket.emit('declare-back', JSON.stringify(body));
                            rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
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
                    rummyPointController.getStatus({ user_id, token, game_id })
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
            console.log("User Disconnected Rummy - " + socket.id + " reason - " + reason);

            // UserModel.findOne({ where: { socket_id: socket.id, isDeleted: 0 } }).then(user => {
            //     if (user) {
            //         var userData = user.toJSON();

            //         var options = {
            //             'method': 'POST',
            //             'url': BASE_URL+'/api/rummy/leave_table',
            //             'headers': {
            //                 'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
            //                 'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
            //             },
            //             formData: {
            //                 'user_id': userData.id,
            //                 'token': userData.token,
            //             }
            //         };
            //         request(options, function (error, response) {
            //             if (error) throw new Error(error);
            //             // console.log(response.body);
            //             console.log("disconnect leave-game", response.body);
            //             socket.emit('leave-table', response.body);
            //             var body = JSON.parse(response.body);
            //             rummy_socket.in("room" + userData.rummy_table_id).emit('trigger', "call_status");
            //             socket.leave("room" + userData.rummy_table_id);
            //         });

            //         // update socket_id of user
            //         UserModel.update(
            //             { socket_id: "" },
            //             { where: { id: userData.id } }
            //         ).then(result => {})
            //         // end update socket_id of user
            //     }
            // })

        });
    });

    function autochaal(room_id) {
        rummyPointController.rummyAutoChaal(room_id)
            .then((response) => {
                console.log("auto-chaal - " + room_id, response);
                rummy_socket.in("room" + room_id).emit('trigger', "call_status");
                var roomname = "room" + room_id;
                // if(timeoutArr.roomname!==undefined){
                timeoutArr[roomname].stop();
                clearInterval(intervalArr[roomname]);
                // }
                var clients = rummy_socket.adapter.rooms.get("room" + room_id);
                const numClients = clients ? clients.size : 0;
                console.log("Rummy Point Stop Autochaal No of Users - ", numClients);
                console.log("Rummy Point Stop Autochaal room_id - ", room_id);
                if (response == 'Running') {
                    timeoutArr[roomname] = new timer(function () { autochaal(room_id) }, timeout_time)
                    intervalArr[roomname] = setInterval(function () { rummy_socket.in(roomname).emit('rummy_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                }
            }).catch((error) => {
                console.log(error)
            })
    }
}
