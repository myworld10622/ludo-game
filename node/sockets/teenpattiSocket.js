
const db = require('../models')
const Sequelize  = require('sequelize');
const UserModel = db.user

module.exports = function (teenpatti_socket, request, timer, BASE_URL) {
    
    var timeoutArr = {};
    var intervalArr = {};
    var users = {};
    const timeout_time = 30000;
    var rummy_timer = (timeout_time/1000);
    var interval_id;

    teenpatti_socket.on('connection', (socket) => {
        console.log("New Connection Teenpatti ",socket.id)

        socket.recovered = true;

        socket.on("ping", (callback) => {
            socket.emit('ping',callback);
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

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    // console.log(userData.table_id);

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/get_table',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token,
                        'boot_value': boot_value
                        }
                    };
                    request(options, function (error, response) {
                    if (error) throw new Error(error);
                    // console.log(response.body);
                        console.log("get-table",response.body);
                        socket.emit('get-table',response.body);
                        var body = JSON.parse(response.body);
                        if(body.table_data!==undefined){
                            socket.join("room"+body.table_data[0].table_id);
                            teenpatti_socket.in("room"+body.table_data[0].table_id).emit('trigger', "call_status");
                            // console.log("get-table Tigger","room"+body.table_data[0].table_id);
                            // console.log(teenpatti_socket.sockets.adapter);
                            var clients = teenpatti_socket.adapter.rooms.get("room"+body.table_data[0].table_id);
                            var numClients = clients ? clients.size : 0;
                            console.log("No of Users - ",numClients);
                        }
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-table',res);
                }

            });
        });

        socket.on('get-customise-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/get_customise_table',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token,
                        'boot_value': boot_value
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("get-customise-table",response.body);
                        socket.emit('get-customise-table',response.body);
                        var body = JSON.parse(response.body);
                        socket.join("room"+body.table_data[0].table_id);
                        teenpatti_socket.in("room"+body.table_data[0].table_id).emit('trigger', "call_status");
                        var clients = teenpatti_socket.adapter.rooms.get("room"+body.table_data[0].table_id);
                        var numClients = clients ? clients.size : 0;
                        console.log("No of Users - ",numClients);
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-customise-table',res);
                }

            });
        });

        socket.on('get-private-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/get_private_table',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token,
                        'boot_value': boot_value
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("get-private-table",response.body);
                        socket.emit('get-private-table',response.body);
                        var body = JSON.parse(response.body);
                        socket.join("room"+body.table_data[0].table_id);
                        teenpatti_socket.in("room"+body.table_data[0].table_id).emit('trigger', "call_status");
                        var clients = teenpatti_socket.adapter.rooms.get("room"+body.table_data[0].table_id);
                        var numClients = clients ? clients.size : 0;
                        console.log("No of Users - ",numClients);
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid Table';
                    socket.emit('get-customise-table',res);
                }
            });
        });

        socket.on('join-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var table_id = msg.table_id;
            var token = msg.token;
            var res = {};
        
            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/join_table',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'table_id': table_id,
                        'token': token,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("join-table",response.body);
                        socket.emit('join-table',response.body);
                        var body = JSON.parse(response.body);
                        socket.join("room"+body.table_data[0].table_id);
                        teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('join-table',res);
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

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                var userData = user.toJSON();
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/game/start_game',
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
                    console.log("start-game",response.body);
                    socket.emit('start-game',response.body);
                    var body = JSON.parse(response.body);
                    if(body.game_id!==undefined){
                        var roomname = "room"+userData.table_id;
                        teenpatti_socket.in(roomname).emit('trigger', ""+body.game_id);
                        if(timeoutArr[roomname]!==undefined){
                            console.log("Timer is Remaining - ",timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                        timeoutArr[roomname] = new timer(function() { autochaal(userData.table_id) }, timeout_time)
                        intervalArr[roomname] = setInterval(function(){teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft()/1000))}, 1000);
                    }else{
                        var roomname = "room"+userData.table_id;
                        teenpatti_socket.in("room"+roomname).emit('trigger', "call_status");
                        if(timeoutArr[roomname]==undefined){
                            timeoutArr[roomname] = new timer(function() { autochaal(userData.table_id) }, timeout_time)
                            intervalArr[roomname] = setInterval(function(){teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft()/1000))}, 1000);
                        }
                    }
                    
                });
                }
                else{
                res['code'] = 201;
                res['message'] = 'Invalid User';
                socket.emit('start-game',res);
                }

            });
        });

        socket.on('chaal', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var plus = msg.plus;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var roomname = "room"+userData.table_id;

                    // if(timeoutArr[roomname]!==undefined){
                    //     console.log("Timer is Remaining - ",timeoutArr[roomname].getStateRunning())
                    //     timeoutArr[roomname].stop();
                    //     clearInterval(intervalArr[roomname]);
                    // }
                    // timeoutArr[roomname] = new timer(function() { autochaal(userData.table_id) }, timeout_time)
                    // intervalArr[roomname] = setInterval(function(){teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft()/1000))}, 1000);
                    console.log("chaal msg",msg);
                    if((msg.bot!==undefined) && msg.bot==1){
                        setTimeout(function () {
                            teenpatti_socket.in(roomname).emit('trigger', "call_status");
                        }, 2000);
                    }
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/chaal',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token,
                        'plus': plus
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        socket.emit('chaal',response.body);
                        var body = JSON.parse(response.body);
                        // console.log(body);
                        console.log("chaal",response.body);
                        // teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");

                        // var bot_timeout = 0;
                        // if(body.bot!==undefined && body.bot==1){
                            // new timer(function() { teenpatti_socket.in(roomname).emit('trigger', "call_status"); }, 10)
                            // bot_timeout = 10;
                        // }

                        if(timeoutArr[roomname]!==undefined && body.code==200){
                            console.log("Timer is Remaining - ",timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                        // if(body.message!='Pot Show' && body.code==200){
                        if(body.code==200){
                            if(body.bot==0 || (body.message!='Pot Show' && body.bot==1)){
                                console.log("trigger body - ",body);
                                teenpatti_socket.in(roomname).emit('trigger', "call_status");

                                timeoutArr[roomname] = new timer(function() { autochaal(userData.table_id) }, timeout_time)
                                intervalArr[roomname] = setInterval(function(){teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft()/1000))}, 1000);
                            }else{
                                console.log("inside body - ",body);
                            }
                        }else{
                            console.log("body - ",body);
                        }
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('chaal',res);
                }

            });
        });

        socket.on('show-game', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/show_game',
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
                        console.log("show-game",response.body);
                        socket.emit('show-game',response.body);
                        var body = JSON.parse(response.body);
                        var roomname = "room"+userData.table_id;
                        teenpatti_socket.in(roomname).emit('trigger', "call_status");

                        if(timeoutArr[roomname]!==undefined){
                            console.log("Timer is Remaining - ",timeoutArr[roomname].getStateRunning())
                            timeoutArr[roomname].stop();
                            clearInterval(intervalArr[roomname]);
                        }
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('show-game',res);
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

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/do_slide_show',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token,
                        'slide_id': slide_id,
                        'type': type,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("do-slide-show",response.body);
                        socket.emit('do-slide-show',response.body);
                        var body = JSON.parse(response.body);
                        teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('do-slide-show',res);
                }
            });
        });

        socket.on('slide-show', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var prev_user_id = msg.prev_user_id;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/slide_show',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token,
                        'prev_user_id': prev_user_id,
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("slide-show",response.body);
                        socket.emit('slide-show',response.body);
                        var body = JSON.parse(response.body);
                        teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('slide-show',res);
                }
            });
        });

        socket.on('switch-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/switch_table',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("switch-table",response.body);
                        socket.emit('switch-table',response.body);
                        var body = JSON.parse(response.body);
                        teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('switch-table',res);
                }
            });
        });

        socket.on('see-card', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;

            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/game/see_card',
                        'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                        'user_id': user_id,
                        'token': token
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("see-card",response.body);
                        socket.emit('see-card',response.body);
                        var body = JSON.parse(response.body);
                        teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                    });
                }
                else{
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('see-card',res);
                }
            });
        });

        socket.on('leave-table', msg => {
        // socket.join("");
        var user_id = msg.user_id;
        var token = msg.token;
        var game_id = msg.game_id;

        var res = {};

        UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
            if (user) {
            var userData = user.toJSON();
            var options = {
                'method': 'POST',
                'url': BASE_URL+'/api/game/leave_table',
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
                console.log("leave-game",response.body);
                socket.emit('leave-table',response.body);
                var body = JSON.parse(response.body);
                teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                socket.leave("room"+userData.table_id);
            });
            }
            else{
            res['code'] = 201;
            res['message'] = 'Invalid User';
            socket.emit('leave-table',res);
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

        UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
            if (user) {
            var userData = user.toJSON();
            var options = {
                'method': 'POST',
                'url': BASE_URL+'/api/game/pack_game',
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
                console.log("pack-game",response.body);
                socket.emit('pack-game',response.body);
                // console.log(response.body);
                var body = JSON.parse(response.body);
                teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
            });
            }
            else{
            res['code'] = 201;
            res['message'] = 'Invalid User';
            socket.emit('pack-game',res);
            }
        });
        });

        socket.on('tip', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var tip = msg.tip;
            var token = msg.token;
    
            var res = {};
    
            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                var userData = user.toJSON();
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/game/tip',
                    'headers': {
                    'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                    'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                    },
                    formData: {
                    'user_id': user_id,
                    'token': token,
                    'tip': tip,
                    }
                };
                request(options, function (error, response) {
                    if (error) throw new Error(error);
                    // console.log(response.body);
                    console.log("tip",response.body);
                    socket.emit('tip',response.body);
                    // console.log(response.body);
                    var body = JSON.parse(response.body);
                    teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                });
                }
                else{
                res['code'] = 201;
                res['message'] = 'Invalid User';
                socket.emit('tip',res);
                }
            });
        });

        socket.on('chat', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var chat = msg.chat;
            var token = msg.token;
    
            var res = {};
    
            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 }}).then(user => {
                if (user) {
                var userData = user.toJSON();
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/game/chat',
                    'headers': {
                    'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                    'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                    },
                    formData: {
                    'user_id': user_id,
                    'token': token,
                    'chat': chat,
                    }
                };
                request(options, function (error, response) {
                    if (error) throw new Error(error);
                    // console.log(response.body);
                    console.log("chat",response.body);
                    socket.emit('chat',response.body);
                    // console.log(response.body);
                    var body = JSON.parse(response.body);
                    teenpatti_socket.in("room"+userData.table_id).emit('trigger', "call_status");
                });
                }
                else{
                res['code'] = 201;
                res['message'] = 'Invalid User';
                socket.emit('chat',res);
                }
            });
        });

        socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });

        function autochaal(room_id){
            var options = {
                'method': 'GET',
                'url': BASE_URL+'/api/cron/teenpatti_socket/'+room_id
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);
                // console.log(response.body);
                console.log("auto-chaal",response.body);
                teenpatti_socket.in("room"+room_id).emit('trigger', "call_status");
                var roomname = "room"+room_id;

                timeoutArr[roomname].stop();
                clearInterval(intervalArr[roomname]);

                var clients = teenpatti_socket.adapter.rooms.get("room" + room_id);
                var numClients = clients ? clients.size : 0;
                console.log("Stop Autochaal No of Users - ", numClients);
                if(response.body=='Running'){
                    timeoutArr[roomname] = new timer(function() { autochaal(room_id) }, timeout_time)
                    intervalArr[roomname] = setInterval(function(){teenpatti_socket.in(roomname).emit('timer', Math.round(timeoutArr[roomname].getTimeLeft()/1000))}, 1000);
                }
            });
        }
    });
}
