// Color Prediction start
// var request = require('request');

const { createColorPredictionGame, declareWinner, getActiveGameSocket } = require("../controllers/api/colorPredictionController");

module.exports = function (color_prediction_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    color_prediction_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            createColorPredictionGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('color_prediction_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Color Prediction Connected!");
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
            declareWinner()
                .then(data => {
                    game_created = false;
                    getActiveGameSocket(roomId)
                        .then(data => {
                            if (data) {
                                color_prediction_socket.emit('color_prediction_status', JSON.stringify(data));
                            }
                        })
                        .catch(err => console.log(err));

                    var user_count = color_prediction_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {

                            // Create Game
                            createColorPredictionGame();

                        }, 5000);
                        setTimeout(function () {
                            interval_id = setInterval(timer_function, 1000);
                        }, 10000);

                        timer = 15;
                    }
                })
                .catch(error => {
                    console.log(error);
                })

        } else {
            if (timer % 3 == 0) {
                // Send Status
                getActiveGameSocket(roomId)
                    .then(data => {
                        if (data) {
                            color_prediction_socket.emit('color_prediction_status', JSON.stringify(data));
                        }
                    })
                    .catch(err => console.log(err));
            }

            // console.log("color_prediction_timer", timer);
            color_prediction_socket.emit('color_prediction_timer', timer);
            timer--;
        }
    }
}

// Color Prediction End