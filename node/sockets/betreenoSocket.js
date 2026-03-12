
const db = require('../models')
const Sequelize = require('sequelize');
const UserModel = db.user

var timeoutArr = {};
var intervalArr = {};
const timeout_time = 30000;
const bot_timeout = 10000;
const start_game_time = 8000;
var betreeno_timer = (timeout_time / 1000);
var interval_id;

module.exports = function (betreeno_socket, request, timer, BASE_URL) {

    betreeno_socket.on('connection', (socket) => {
        console.log("New Connection Betreeno ", socket.id)

        socket.on('get-table', msg => {

            var user_id = msg.user_id;
            var blind_1 = msg.blind_1;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL + '/api/betreeno/get_table',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                            'blind_1': blind_1
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("get-table", response.body);
                        socket.emit('get-table', response.body);
                        var body = JSON.parse(response.body);
                        if(body.table_data!=undefined){
                            console.log("get-table trigger", body.table_data[0].betreeno_table_id);
                            socket.join("room" + body.table_data[0].betreeno_table_id);
                            betreeno_socket.in("room" + body.table_data[0].betreeno_table_id).emit('trigger', "call_status");
                            var clients = betreeno_socket.adapter.rooms.get("room" + body.table_data[0].betreeno_table_id);
                            const numClients = clients ? clients.size : 0;
                            console.log("No of Users - ", numClients);
                        }
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-table', res);
                }

            });
        });

        socket.on('start-game', msg => {

            var user_id = msg.user_id;
            var blind_1 = msg.blind_1;
            var token = msg.token;
            // var table_id = msg.table_id;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL + '/api/betreeno/start_game',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("start-game", response.body);
                        socket.emit('start-game', response.body);
                        var body = JSON.parse(response.body);
                        if (body.game_id !== undefined) {
                            var roomname = "room" + userData.betreeno_table_id;
                            betreeno_socket.in(roomname).emit('trigger', "" + body.game_id);
                            if (timeoutArr[roomname] !== undefined) {
                                console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                                timeoutArr[roomname].stop();
                                clearInterval(intervalArr[roomname]);
                            }
                            timeoutArr[roomname] = new timer(function () { autochaal(userData.betreeno_table_id) }, timeout_time)
                            intervalArr[roomname] = setInterval(function () { betreeno_socket.in(roomname).emit('betreeno_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                        } else {
                            var roomname = "room" + userData.betreeno_table_id;
                            betreeno_socket.in(roomname).emit('trigger', "call_status");
                            if (timeoutArr[roomname] == undefined) {
                                timeoutArr[roomname] = new timer(function () { autochaal(userData.betreeno_table_id) }, timeout_time)
                                intervalArr[roomname] = setInterval(function () { betreeno_socket.in(roomname).emit('betreeno_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                            }
                        }
                        // betreeno_socket.in("room"+userData.betreeno_table_id).emit('trigger', ""+body.game_id);
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('start-game', res);
                }

            });
        });

        socket.on('chaal', msg => {

            var user_id = msg.user_id;
            var token = msg.token;
            var rule = msg.rule;
            var value = msg.value;
            var chaal_type = msg.chaal_type;
            // var raise = msg.raise;
            var amount = msg.amount;
            var game_id = msg.game_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL + '/api/betreeno/chaal',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                            'rule': rule,
                            'value': value,
                            'chaal_type': chaal_type,
                            // 'raise': raise,
                            'amount': amount,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("chaal", response.body);
                        socket.emit('chaal', response.body);
                        // var body = JSON.parse(response.body);
                        // console.log(body);
                        
                        // betreeno_socket.in("room"+userData.betreeno_table_id).emit('trigger', "call_status");
                        var roomname = "room" + userData.betreeno_table_id;
                        betreeno_socket.in(roomname).emit('trigger', "call_status");
                        if (timeoutArr[roomname] !== undefined) {
                            console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                        timeoutArr[roomname] = new timer(function () { autochaal(userData.betreeno_table_id) }, timeout_time)
                        intervalArr[roomname] = setInterval(function () { betreeno_socket.in(roomname).emit('betreeno_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('chaal', res);
                }

            });
        });

        socket.on('leave-table', msg => {

            var user_id = msg.user_id;
            var token = msg.token;
            var game_id = msg.game_id;
            var table_id = msg.table_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL + '/api/betreeno/leave_table',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("leave-game", response.body);
                        socket.emit('leave-table', response.body);
                        var body = JSON.parse(response.body);
                        // betreeno_socket.in("room"+userData.betreeno_table_id).emit('trigger', "call_status");

                        var roomname = "room" + userData.betreeno_table_id;
                        betreeno_socket.in(roomname).emit('trigger', "call_status");
                        if (timeoutArr[roomname] !== undefined) {
                            console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                        timeoutArr[roomname] = new timer(function () { autochaal(userData.betreeno_table_id) }, timeout_time)
                        intervalArr[roomname] = setInterval(function () { betreeno_socket.in(roomname).emit('betreeno_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);

                        socket.leave(roomname);
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('leave-table', res);
                }
            });
        });

        socket.on('pack-game', msg => {

            var user_id = msg.user_id;
            var game_id = msg.game_id;
            var timeout = msg.timeout;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL + '/api/betreeno/pack_game',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                            'timeout': timeout,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("pack-game", response.body);
                        socket.emit('pack-game', response.body);
                        // console.log(response.body);
                        var body = JSON.parse(response.body);
                        // betreeno_socket.in("room"+userData.betreeno_table_id).emit('trigger', "call_status");
                        var roomname = "room" + userData.betreeno_table_id;
                        betreeno_socket.in(roomname).emit('trigger', "call_status");
                        if (timeoutArr[roomname] !== undefined) {
                            console.log("Timer is Remaining - ", timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                        timeoutArr[roomname] = new timer(function () { autochaal(userData.betreeno_table_id) }, timeout_time)
                        intervalArr[roomname] = setInterval(function () { betreeno_socket.in(roomname).emit('betreeno_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('leave-table', res);
                }
            });
        });

    });

    function autochaal(room_id) {
        var options = {
            'method': 'GET',
            'url': BASE_URL + '/api/cron/betreeno_socket/' + room_id
        };
        request(options, function (error, response) {
            if (error) throw new Error(error);
            // console.log(response.body);
            console.log("auto-chaal", response.body);
            betreeno_socket.in("room" + room_id).emit('trigger', "call_status");
            var roomname = "room" + room_id;

            timeoutArr[roomname].stop();
            clearInterval(intervalArr[roomname]);

            // var clients = betreeno_socket.adapter.rooms.get("room" + room_id);
            var clients = betreeno_socket.adapter.rooms.get("room" + room_id);
            var numClients = clients ? clients.size : 0;
            console.log("Stop Autochaal No of Users - ", numClients);
            if (numClients > 0 && response.body == 'Running') {
                timeoutArr[roomname] = new timer(function () { autochaal(room_id) }, timeout_time)
                intervalArr[roomname] = setInterval(function () { betreeno_socket.in(roomname).emit('betreeno_timer', Math.round(timeoutArr[roomname].getTimeLeft() / 1000)) }, 1000);
            }
        });
    }
}
