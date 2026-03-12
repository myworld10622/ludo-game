// Ander Bahar start
// var request = require('request');

module.exports = function (color_prediction_5min_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 300;
    var interval_id;
    color_prediction_5min_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 300;
            // Create Game
            var options = {
                'method': 'GET',
                'url': BASE_URL+'/api/cron/color_prediction_5_min_create_socket'
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);

                // console.log("color_prediction_3_min_create_socket", response.body);
            });
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('color_prediction_5min_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Ander Bahar Connected!");
            // });
        });

        socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            var options = {
                'method': 'GET',
                'url': BASE_URL+'/api/cron/color_prediction_5_min_winner_socket'
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);
                game_created = false;
                // console.log("color_prediction_3min_winner_socket", response.body);

                // Send Status
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/colorPrediction5Min/get_active_game_socket',
                    'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=2g8l0mfnnlcim6jp1lthsivp6583rli8'
                    },
                    formData: {
                        'room_id': 1
                    }
                };
                request(options, function (error, response) {
                    if (error) throw new Error(error);
                    // console.log(response.body);
                    // console.log("color_prediction_3min_status", "status");
                    color_prediction_5min_socket.emit('color_prediction_5min_status', response.body);

                });

                var user_count = color_prediction_5min_socket.sockets.size;
                // console.log("user_count - ", user_count);
                // console.log("game created - ", game_created);
                if (user_count > 0 && !game_created) {
                    game_created = true;
                    setTimeout(function () {

                        // Create Game
                        var options = {
                            'method': 'GET',
                            'url': BASE_URL+'/api/cron/color_prediction_5_min_create_socket'
                        };
                        request(options, function (error, response) {
                            if (error) throw new Error(error);

                            // console.log("color_prediction_3_min_create_socket", response.body);
                        });

                        // // Send Status
                        // var options = {
                        //     'method': 'POST',
                        //     'url': BASE_URL+'/api/colorPrediction3Min/get_active_game_socket',
                        //     'headers': {
                        //         'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        //         'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=2g8l0mfnnlcim6jp1lthsivp6583rli8'
                        //     },
                        //     formData: {
                        //         'room_id': 1
                        //     }
                        // };
                        // request(options, function (error, response) {
                        //     if (error) throw new Error(error);
                        //     // console.log(response.body);
                        //     console.log("color_prediction_3min_status", "status");
                        //     color_prediction_5min_socket.emit('color_prediction_3min_status', response.body);

                        // });
                    }, 5000);
                    setTimeout(function () {
                        interval_id = setInterval(timer_function, 1000);
                    }, 10000);

                    timer = 180;
                }

            });

        } else {
            if (timer % 3 == 0) {
                // Send Status
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/colorPrediction5Min/get_active_game_socket',
                    'headers': {
                        'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
                        'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=2g8l0mfnnlcim6jp1lthsivp6583rli8'
                    },
                    formData: {
                        'room_id': 1
                    }
                };
                request(options, function (error, response) {
                    if (error) throw new Error(error);
                    // console.log(response.body);
                    // console.log("color_prediction_3min_status", "status");
                    color_prediction_5min_socket.emit('color_prediction_5min_status', response.body);

                });
            }

            // console.log("color_prediction_3min_timer", timer);
            color_prediction_5min_socket.emit('color_prediction_5min_timer', timer);
            timer--;
        }
    }
}

// Ander Bahar End