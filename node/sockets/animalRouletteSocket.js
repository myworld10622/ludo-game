const animalRouletteController = require("../controllers/api/animalRouletteController");

module.exports = function (animal_roulette_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    animal_roulette_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            animalRouletteController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('animal_roulette_timer', msg => {
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
            animalRouletteController.declareWinner()
                .then(data => {
                    game_created = false;

                    animalRouletteController.getActiveGameSocket(roomId)
                        .then(data => {
                            animal_roulette_socket.emit('animal_roulette_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = animal_roulette_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            animalRouletteController.createGame();
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
                animalRouletteController.getActiveGameSocket(roomId)
                    .then(data => {
                        animal_roulette_socket.emit('animal_roulette_status', JSON.stringify(data));
                    })
                    .catch(error => {
                        console.log(error)
                    })
            }

            // console.log("animal_roulette_timer", timer);
            animal_roulette_socket.emit('animal_roulette_timer', timer);
            timer--;
        }
    }
}

// Ander Bahar End