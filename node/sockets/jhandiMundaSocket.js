const jhandiMundaController = require("../controllers/api/jhandiMundaController");

module.exports = function (jhandi_munda_socket, request, BASE_URL) {
    var game_created = false;
    var timer = 15;
    var interval_id;
    const roomId = 1;
    jhandi_munda_socket.on('connection', (socket) => {
        if (!game_created) {
            game_created = true;
            timer = 15;
            // Create Game
            jhandiMundaController.createGame();
            interval_id = setInterval(timer_function, 1000);
        }

        socket.on('jhandi_munda_timer', msg => {
            // connection.connect(function (err) {
            // console.log("Dragon Tiger Connected!");
            // });
        });

        jhandi_munda_socket.on("disconnect", (reason) => {
            // console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });


    function timer_function() {

        if (timer <= 0) {
            var response = {};
            clearInterval(interval_id);

            // Make Winner
            jhandiMundaController.declareWinner()
                .then(data => {
                    game_created = false;

                    jhandiMundaController.getActiveGameSocket(roomId)
                        .then(data => {
                            jhandi_munda_socket.emit('jhandi_munda_status', JSON.stringify(data));
                        })
                        .catch(error => {
                            console.log(error)
                        })
                    var user_count = jhandi_munda_socket.sockets.size;

                    if (user_count > 0 && !game_created) {
                        game_created = true;
                        setTimeout(function () {
                            // Create Game
                            jhandiMundaController.createGame();
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
                jhandiMundaController.getActiveGameSocket(roomId)
                    .then(data => {
                        jhandi_munda_socket.emit('jhandi_munda_status', JSON.stringify(data));
                    }).catch(error => {
                        console.log(error)
                    })
            }

            // console.log("jhandi_munda_timer", timer);
            jhandi_munda_socket.emit('jhandi_munda_timer', timer);
            timer--;
        }
    }
}

// Dragon Tiger End