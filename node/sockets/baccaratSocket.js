const baccaratController = require("../controllers/api/baccaratController");

module.exports = function (baccarat_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    baccarat_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            baccaratController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('baccarat_timer', msg => {
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
            baccaratController.declareWinner()
                .then(data => {
                    game_created = false;

                    baccaratController.getActiveGameSocket(roomId)
                        .then(data => {
                            baccarat_socket.emit('baccarat_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = baccarat_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            baccaratController.createGame();
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
                baccaratController.getActiveGameSocket(roomId)
                    .then(data => {
                        baccarat_socket.emit('baccarat_status', JSON.stringify(data));
                    }).catch(error => {
                        console.log(error)
                    })
            }

            // console.log("baccarat_timer", timer);
            baccarat_socket.emit('baccarat_timer', timer);
            timer--;
        }
    }
}

// Ander Bahar End