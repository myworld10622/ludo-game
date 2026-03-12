// Dragon tiger start
// var request = require('request');

const { createDragonTigerGame, declareWinner, getActiveGameSocket } = require("../controllers/api/dragonTigerController");

module.exports = function (dragon_tiger_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    dragon_tiger_socket.on('connection', (socket) => {

        console.log("Dragin Tiger User Connected - " + socket.id);

        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            createDragonTigerGame();
            // timer_function();
            interval_id = setInterval(timer_function, 1000);
        }

        dragon_tiger_socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        console.log("dragon_tiger_timer_function_started", timer);

        if (timer <= 0) {
            clearInterval(interval_id);

            // Make Winner
            declareWinner()
                .then(data => {
                    game_created = false;
                    
                    getActiveGameSocket(roomId)
                        .then(data => {
                            dragon_tiger_socket.emit('dragon_tiger_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = dragon_tiger_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            createDragonTigerGame();
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
            if (timer % 5 == 0) {
                getActiveGameSocket(roomId)
                    .then(data => {
                        // console.log('data',data);
                        dragon_tiger_socket.emit('dragon_tiger_status', JSON.stringify(data));
                    })
                    .catch(error => {
                        console.log(error)
                    })
            }

            console.log("dragon_tiger_timer", timer);
            dragon_tiger_socket.emit('dragon_tiger_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End