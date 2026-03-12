const jackpotController = require("../controllers/api/jackpotController");

module.exports = function (jackpot_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    jackpot_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            jackpotController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('jackpot_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        jackpot_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            jackpotController.declareWinner()
                .then(data => {
                    game_created = false;

                    jackpotController.getActiveGameSocket(roomId)
                        .then(data => {
                            jackpot_socket.emit('jackpot_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = jackpot_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            jackpotController.createGame();
                        }, 5000);

                        setTimeout(function () {
                            interval_id = setInterval(timer_function, 1000);
                        }, 10000);
                        timer = 15;
                    }
                })
                .catch(error => {
                    console.log(error)
                })

        } else {
            if (timer % 3 == 0) {
                // Send Status
                jackpotController.getActiveGameSocket(roomId)
                    .then(data => {
                        jackpot_socket.emit('jackpot_status', JSON.stringify(data));
                    }).catch(error => {
                        console.log(error)
                    })
            }

            // console.log("jackpot_timer", timer);
            jackpot_socket.emit('jackpot_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End