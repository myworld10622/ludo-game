// Dragon tiger start
// var request = require('request');

module.exports = function (golden_wheel_socket, request, BASE_URL) {
    var game_created = false;
    var place_bet_timer = 40;
    var show_result_timer = 10;
    var show_winner_timer = 10;

    var timer = place_bet_timer;
    var interval_id;
    golden_wheel_socket.on('connection', (socket) => {

        golden_wheel_socket.emit('show_result_timer', show_result_timer);

        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            var options = {
                'method': 'GET',
                'url': BASE_URL+'/api/cron/golden_wheel_create_socket'
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);

                // console.log("golden_wheel_create_socket", response.body);
            });
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('golden_wheel_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        golden_wheel_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            var options = {
                'method': 'GET',
                'url': BASE_URL+'/api/cron/golden_wheel_winner_socket'
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);
                game_created = false;
                // console.log("golden_wheel_winner_socket", response.body);

                // Send Status
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/goldenWheel/get_active_game_socket',
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
                    // console.log("golden_wheel_status", "status");
                    golden_wheel_socket.emit('golden_wheel_status', response.body);

                });

                var user_count = golden_wheel_socket.sockets.size;
                // console.log("user_count - ", user_count);
                // console.log("game created - ", game_created);
                if (user_count > 0 && !game_created) {
                    game_created = true;
                    setTimeout(function () {

                        // Create Game
                        var options = {
                            'method': 'GET',
                            'url': BASE_URL+'/api/cron/golden_wheel_create_socket'
                        };
                        request(options, function (error, response) {
                            if (error) throw new Error(error);

                            // console.log("golden_wheel_create_socket", response.body);
                        });

                        // // Send Status
                        // var options = {
                        //     'method': 'POST',
                        //     'url': BASE_URL+'/api/goldenWheel/get_active_game_socket',
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
                        //     // console.log("golden_wheel_status", "status");
                        //     golden_wheel_socket.emit('golden_wheel_status', response.body);

                        // });

                    }, show_result_timer * 1000);
                    setTimeout(function () {
                        interval_id = setInterval(timer_function, 1000);
                    }, (show_result_timer + show_winner_timer) * 1000);

                    timer = place_bet_timer;
                }

            });


        } else {
            if (timer % 3 == 0) {
                // Send Status
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/goldenWheel/get_active_game_socket',
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
                    // console.log("golden_wheel_status", "status");
                    golden_wheel_socket.emit('golden_wheel_status', response.body);

                });
            }

            // console.log("golden_wheel_timer", timer);
            golden_wheel_socket.emit('golden_wheel_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End