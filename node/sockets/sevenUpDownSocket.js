// Seven Up Down start
// var request = require('request');
const sevenUpController = require("../controllers/api/sevenUpController");

module.exports = function (seven_up_down_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    seven_up_down_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            sevenUpController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('seven_up_down_timer', msg => {
            // connection.connect(function (err) {
            console.log("Seven Up Down Connected!");
            // });
        });

        seven_up_down_socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            sevenUpController.declareWinner()
                .then(data => {
                    game_created = false;

                    sevenUpController.getActiveGameSocket(roomId)
                        .then(data => {
                            seven_up_down_socket.emit('seven_up_down_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = seven_up_down_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            sevenUpController.createGame();

                            // sevenUpController.getActiveGameSocket(roomId)
                            //     .then(data => {
                            //         seven_up_down_socket.emit('seven_up_down_status', JSON.stringify(data));
                            //     })
                            //     .catch(error => {
                            //         console.log(error)
                            //     })
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
                sevenUpController.getActiveGameSocket(roomId)
                    .then(data => {
                        seven_up_down_socket.emit('seven_up_down_status', JSON.stringify(data));
                    })
                    .catch(error => {
                        console.log(error)
                    })
            }

            // console.log("seven_up_down_timer", timer);
            seven_up_down_socket.emit('seven_up_down_timer', timer);
            timer--;
        }
    }
}

// Seven Up Down End