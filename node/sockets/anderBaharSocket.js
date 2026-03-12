const anderBaharController = require("../controllers/api/anderBaharController");

module.exports = function (ander_bahar_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    ander_bahar_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            anderBaharController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('ander_bahar_timer', msg => {
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
            clearInterval(interval_id);

            // Make Winner
            anderBaharController.declareWinner()
                .then(data => {
                    game_created = false;

                    var winning_seconds = data;

                    anderBaharController.getActiveGameSocket(roomId)
                        .then(data => {
                            ander_bahar_socket.emit('ander_bahar_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = ander_bahar_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            anderBaharController.createGame();
                            interval_id = setInterval(timer_function, 1000);
                        }, (winning_seconds * 1000) + 5000);

                        timer = 15;
                    }
                })
                .catch(error => {
                    console.log(error)
                })

        } else {
            if (timer % 3 == 0) {
                // Send Status
                anderBaharController.getActiveGameSocket(roomId)
                .then(data => {
                    ander_bahar_socket.emit('ander_bahar_status', JSON.stringify(data));
                })
                .catch(error => {
                    console.log(error)
                })
            }

            // console.log("ander_bahar_timer", timer);
            ander_bahar_socket.emit('ander_bahar_timer', timer);
            timer--;
        }
    }
}

// Ander Bahar End