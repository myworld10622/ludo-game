const rouletteController = require("../controllers/api/rouletteController");

module.exports = function (roulette_socket, request, BASE_URL) {
    var game_created = false;

    var place_bet_timer = 15;
    var show_result_timer = 10;
    var show_winner_timer = 10;

    var timer = place_bet_timer;
    var interval_id;
    const roomId = 1;
    roulette_socket.on('connection', (socket) => {

        roulette_socket.emit('show_result_timer', show_result_timer);

        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            rouletteController.createRoultteGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('roulette_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        roulette_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            clearInterval(interval_id);

            // console.log('winner roulette_status');
            var user_count = roulette_socket.sockets.size;
            // console.log('user_count roulette_status',user_count);
            // Make Winner
            rouletteController.declareWinner()
                .then(data => {
                    game_created = false;
                    rouletteController.getActiveGame(roomId)
                        .then(data => {
                            // console.log('winner status roulette_status');
                            roulette_socket.emit('roulette_status', JSON.stringify(data));
                        }).catch(error => {
                            console.log(error)
                        })

                    // If users are there and game not created then create new game
                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            rouletteController.createRoultteGame();

                        }, show_result_timer * 1000);
                        setTimeout(function () {
                            interval_id = setInterval(timer_function, 1000);
                        }, (show_result_timer + show_winner_timer) * 1000);

                        timer = place_bet_timer;
                    }
                })
                .catch(error => {
                    console.log(error)
                })

        } else {
            if (timer % 3 == 0) {
                // Send Status
                rouletteController.getActiveGame(roomId)
                    .then(data => {
                        roulette_socket.emit('roulette_status', JSON.stringify(data));
                    }).catch(error => {
                        console.log(error)
                    })
            }

            // console.log("roulette_timer", timer);
            roulette_socket.emit('roulette_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End