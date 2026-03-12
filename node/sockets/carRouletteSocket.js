// Ander Bahar start
// var request = require('request');
// var request = require('request');
const carRouletteController = require("../controllers/api/carRouletteController");

module.exports = function (car_roulette_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    car_roulette_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            carRouletteController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('car_roulette_timer', msg => {
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
            carRouletteController.declareWinner()
                .then(data => {
                    game_created = false;

                    carRouletteController.getActiveGameSocket(roomId)
                        .then(data => {
                            car_roulette_socket.emit('car_roulette_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = car_roulette_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            carRouletteController.createGame();
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
                carRouletteController.getActiveGameSocket(roomId)
                    .then(data => {
                        car_roulette_socket.emit('car_roulette_status', JSON.stringify(data));
                    })
                    .catch(error => {
                        console.log(error)
                    })
            }

            // console.log("car_roulette_timer", timer);
            car_roulette_socket.emit('car_roulette_timer', timer);
            timer--;
        }
    }
}

// Ander Bahar End