const threeDiceController = require("../controllers/api/threeDiceController");

module.exports = function (three_dice_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    three_dice_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            threeDiceController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('three_dice_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        three_dice_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            threeDiceController.declareWinner()
                .then(data => {
                    game_created = false;

                    threeDiceController.getActiveGameSocket(roomId)
                        .then(data => {
                            three_dice_socket.emit('three_dice_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = three_dice_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            threeDiceController.createGame();
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
                threeDiceController.getActiveGameSocket(roomId)
                    .then(data => {
                        three_dice_socket.emit('three_dice_status', JSON.stringify(data));
                    }).catch(error => {
                        console.log(error)
                    })
            }

            // console.log("three_dice_timer", timer);
            three_dice_socket.emit('three_dice_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End