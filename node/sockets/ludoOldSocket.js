
const { json } = require('stream/consumers');
const db = require('../models')
const Sequelize  = require('sequelize');
const UserModel = db.user

module.exports = function (ludo_old_socket, request, timer, BASE_URL) {
    

    ludo_old_socket.on('connection', (socket) => {
        console.log("New Connection Ludo ",socket.id)

        socket.recovered = true;

        socket.on("message", (param_callback) => {
            console.log('message',param_callback);
            // ludo_old_socket.emit('message',param_callback);

            // var param_callback = JSON.parse(callback);

            var user_id = param_callback.user_id;
            var token = param_callback.token;
            var step = param_callback.counter;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var ludo_table_id = userData.ludo_table_id;
                    // console.log(userData.rummy_table_id);
                    ludo_old_socket.in("room" + ludo_table_id).emit('message',JSON.stringify(param_callback));

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/ludoOld/chaal',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                            'chaal': JSON.stringify(param_callback),
                            'step': step
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("chaal", response.body);
                        socket.emit('chaal', response.body);
                    });
                }
            });
        });

        socket.on('get-table', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var boot_value = msg.boot_value;
            var token = msg.token;
            var invite_code = msg.invite_code;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    // console.log(userData.rummy_table_id);

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/ludoOld/get_table',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                            'invite_code': invite_code,
                            'boot_value': boot_value
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("get-table", response.body);
                        socket.emit('get-table', response.body);
                        var body = JSON.parse(response.body);
                        if(body.table_data!==undefined){
                            var ludo_table_id = body.table_data[0].ludo_table_id;
                            socket.join("room" + ludo_table_id);
                            ludo_old_socket.in("room" + ludo_table_id).emit('trigger', "call_status");
                            var clients = ludo_old_socket.adapter.rooms.get("room" + ludo_table_id);
                            const numClients = clients ? clients.size : 0;
                            console.log("No of Users get - ", numClients);

                            // if(numClients>1){
                                // var userData = user.toJSON();
                                
                            // }
                        }
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('get-table', res);
                }

            });
        });

        socket.on('get-step', msg => {
            // socket.join("");
            var user_id = msg.user_id;
            var token = msg.token;
            var step = msg.counter;
            var res = {};

            UserModel.findOne({ where: { id: user_id, token: token, isDeleted: 0 } }).then(user => {
                if (user) {
                    var userData = user.toJSON();
                    var ludo_table_id = userData.ludo_table_id;
                    // console.log(userData.rummy_table_id);

                    var options = {
                        'method': 'POST',
                        'url': BASE_URL+'/api/ludoOld/get_step',
                        'headers': {
                            'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                            'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=mmo9qqjgcpk00r04ll9dcqaohrir95pe'
                        },
                        formData: {
                            'user_id': user_id,
                            'token': token,
                            'step': step
                        }
                    };
                    request(options, function (error, response) {
                        if (error) throw new Error(error);
                        // console.log(response.body);
                        console.log("get-step", response.body);
                        socket.emit('get-step', response.body);
                        
                        socket.join("room" + ludo_table_id);
                    });
                }
                else {
                    res['code'] = 201;
                    res['message'] = 'Invalid User';
                    socket.emit('get-table', res);
                }

            });
        });

    });
}