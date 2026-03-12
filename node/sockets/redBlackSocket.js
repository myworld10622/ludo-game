const redBlackController = require("../controllers/api/redBlackController");

module.exports = function (red_black_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    red_black_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            // Create Game
            timer = 15;
            redBlackController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('red_black_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        red_black_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            redBlackController.declareWinner()
                .then(data => {
                    game_created = false;

                    var winning_seconds = data;

                    redBlackController.getActiveGameSocket(roomId)
                        .then(data => {
                            red_black_socket.emit('red_black_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = red_black_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            redBlackController.createGame();
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
                redBlackController.getActiveGameSocket(roomId)
                    .then(data => {
                        red_black_socket.emit('red_black_status', JSON.stringify(data));
                    }).catch(error => {
                        console.log(error)
                    })
            }

            // console.log("red_black_timer", timer);
            red_black_socket.emit('red_black_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End