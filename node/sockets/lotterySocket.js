// Dragon tiger start
// var request = require('request');

module.exports = function (lottery_socket, request, BASE_URL, dateTime) {
    var game_time_in_minute = 1;
    var game_created = false;
    var game_id = 0;
    var timer = game_time_in_minute*60;
    var interval_id;
    var game_start_time;

    lottery_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            var options = {
                'method': 'GET',
                'url': BASE_URL+'/api/cron/lottery_create_socket'
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);
                
                console.log("lottery_create_socket", response.body);
                var body = JSON.parse(response.body);
                if(body.code==200){
                    var dt = dateTime.create();
                    game_start_time = dt.format('Y-m-d H:M:S');
                    
                    game_id = body.game_id;
                }
            });
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('lottery_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        socket.on('get_timer', msg => {
            var dt = dateTime.create();
            
            var timer_response = {
                'game_id' : game_id,
                'timer' : timer,
                'time_in_min' : new Date(timer * 1000).toISOString().substring(14, 19),
                'current_date' : dt.format('Y-m-d'),
                'current_time' : dt.format('H:M:S')
            };
            // console.log("lottery_timer", timer);
            lottery_socket.emit('get_timer', JSON.stringify(timer_response));
        });

        lottery_socket.on("disconnect", (reason) => {
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
                'url': BASE_URL+'/api/cron/lottery_winner_socket'
            };
            request(options, function (error, response) {
                if (error) throw new Error(error);
                game_created = false;
                console.log("lottery_winner_socket", response.body);

                // Send Status
                var options = {
                    'method': 'POST',
                    'url': BASE_URL+'/api/lottery/get_active_game_socket',
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
                    // console.log("lottery_status", "status");
                    lottery_socket.emit('lottery_status', response.body);

                });

                var user_count = lottery_socket.sockets.size;
                // console.log("user_count - ", user_count);
                // console.log("game created - ", game_created);
                if (user_count > 0 && !game_created) {
                    game_created = true;
                    setTimeout(function () {

                        // Create Game
                        var options = {
                            'method': 'GET',
                            'url': BASE_URL+'/api/cron/lottery_create_socket'
                        };
                        request(options, function (error, response) {
                            if (error) throw new Error(error);
                            
                            console.log("lottery_create_socket", response.body);
                            var body = JSON.parse(response.body);
                            if(body.code==200){

                                var dt = dateTime.create();
                                game_start_time = dt.format('Y-m-d H:M:S');

                                game_id = body.game_id;
                            }
                            
                        });

                        // Send Status
                        var options = {
                            'method': 'POST',
                            'url': BASE_URL+'/api/lottery/get_active_game_socket',
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
                            // console.log("lottery_status", "status");
                            lottery_socket.emit('lottery_status', response.body);

                        });

                    }, 10000);
                    setTimeout(function () {
                        interval_id = setInterval(timer_function, 1000);
                    }, 20000);

                    timer = game_time_in_minute*60;
                }

            });


        } else {
            // if (timer % 3 == 0) {
            //     // Send Status
            //     var options = {
            //         'method': 'POST',
            //         'url': BASE_URL+'/api/lottery/get_active_game_socket',
            //         'headers': {
            //             'token': 'c7d3965d49d4a59b0da80e90646aee77548458b3377ba3c0fb43d5ff91d54ea28833080e3de6ebd4fde36e2fb7175cddaf5d8d018ac1467c3d15db21c11b6909',
            //             'Cookie': 'ci_session=ql4u3d0569peintlo3clko0vftbg0ni2; ci_session=2g8l0mfnnlcim6jp1lthsivp6583rli8'
            //         },
            //         formData: {
            //             'room_id': 1
            //         }
            //     };
            //     request(options, function (error, response) {
            //         if (error) throw new Error(error);
            //         // console.log(response.body);
            //         // console.log("lottery_status", "status");
            //         lottery_socket.emit('lottery_status', response.body);

            //     });
            // }
            var dt = dateTime.create();

            var next_game_time = AddMinutesToDate(game_start_time,game_time_in_minute);
            var minutes = next_game_time.getMinutes();
            var hours = (next_game_time.getHours() + 24) % 12 || 12;
            
            hours = hours < 10 ? '0' + hours : hours;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            
            
            
            var timer_response = {
                'game_id' : game_id,
                'timer' : timer,
                'time_in_min' : new Date(timer * 1000).toISOString().substring(14, 19),
                'game_start_time' : game_start_time,
                'next_game_start_time' : hours+":"+minutes,
                'current_date' : dt.format('Y-m-d'),
                'current_time' : dt.format('H:M:S')
            };
            // console.log("lottery_timer", timer);
            lottery_socket.emit('lottery_timer', JSON.stringify(timer_response));
            timer--;
        }
    }

    function AddMinutesToDate(date, minutes) {
        return new Date(new Date(date).getTime() + minutes*60000);
   }
}

// Dragon Tiger End