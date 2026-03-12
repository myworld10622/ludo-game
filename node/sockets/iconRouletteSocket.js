const iconRouletteController = require("../controllers/api/iconRouletteController");

module.exports = function (icon_roulette_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 180;
    var interval_id;
    const roomId = 1;
    icon_roulette_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 180;
            // Create Game
            iconRouletteController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('icon_roulette_timer', msg => {
        });

        socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            clearInterval(interval_id);

            // Make Winner
            iconRouletteController.declareWinner()
                .then(data => {
                    game_created = false;

                    iconRouletteController.getActiveGameSocket(roomId)
                        .then(data => {
                            icon_roulette_socket.emit('icon_roulette_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = icon_roulette_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            iconRouletteController.createGame();
                        }, 5000);

                        setTimeout(function () {
                            interval_id = setInterval(timer_function, 1000);
                        }, 10000);

                        timer = 180;
                    }
                })
                .catch(error => {
                    console.log(error)
                })

        } else {
            if (timer % 3 == 0) {
                // Send Status
                iconRouletteController.getActiveGameSocket(roomId)
                    .then(data => {
                        icon_roulette_socket.emit('icon_roulette_status', JSON.stringify(data));
                    })
                    .catch(error => {
                        console.log(error)
                    })
            }

            icon_roulette_socket.emit('icon_roulette_timer', timer);
            timer--;
        }
    }
}

// Icon Roulette End
