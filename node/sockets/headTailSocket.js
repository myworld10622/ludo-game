// Dragon tiger start
// var request = require('request');
const headTailController = require("../controllers/api/headTailController");

module.exports = function (head_tail_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    head_tail_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            headTailController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('head_tail_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        head_tail_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            headTailController.declareWinner()
                .then(data => {
                    game_created = false;

                    headTailController.getActiveGameSocket(roomId)
                        .then(data => {
                            head_tail_socket.emit('head_tail_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = head_tail_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            headTailController.createGame();
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
                headTailController.getActiveGameSocket(roomId)
                    .then(data => {
                        head_tail_socket.emit('head_tail_status', JSON.stringify(data));
                    })
                    .catch(error => {
                        console.log(error)
                    })
            }

            // console.log("head_tail_timer", timer);
            head_tail_socket.emit('head_tail_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End