const { createSlotGame, declareSlotWinner, getSlotGameStatus } = require("../controllers/api/slotGameController");

module.exports = function (slot_game_socket, request, BASE_URL) {
    let gameCreated = false;
    let timer = 10;
    let intervalId;
    const roomId = 1;

    slot_game_socket.on('connection', (socket) => {
        console.log("Slot Game User Connected - " + socket.id);

        // Start the game if not already created
        if (!gameCreated) {
            gameCreated = true;
            timer = 10;

            // Create initial game
            createSlotGame().then((game_id) => {
                slot_game_socket.emit('game_id', game_id);
            });
            

            // Start the timer
            intervalId = setInterval(timerFunction, 1000);
        }

        socket.on('slot_game_timer', (msg) => {
            console.log("Timer requested by client:", msg);
        });

        slot_game_socket.on("disconnect", (reason) => {
            console.log("User Disconnected - " + socket.id + " reason - " + reason);
        });
    });

    function timerFunction() {
        // console.log("slot_game_timer_function_started", timer);

        if (timer <= 0) {
            clearInterval(intervalId);

            // Declare winner
            declareSlotWinner()
                .then(() => {
                    gameCreated = false;

                    // Emit game status to clients
                    getSlotGameStatus(roomId)
                        .then((data) => {
                            slot_game_socket.emit('slot_game_status', JSON.stringify(data));
                        })
                        .catch((error) => {
                            console.error("Error fetching game status:", error);
                        });

                    // Restart game if users are connected
                    const userCount = slot_game_socket.sockets.size;
                    if (userCount > 0 && !gameCreated) {
                        gameCreated = true;

                        setTimeout(() => {
                            createSlotGame().then((game_id) => {
                                slot_game_socket.emit('game_id', game_id);
                            });
                        }, 5000);

                        setTimeout(() => {
                            intervalId = setInterval(timerFunction, 1000);
                        }, 10000);

                        timer = 10;
                    }
                })
                .catch((error) => {
                    console.error("Error declaring winner:", error);
                });
        } else {
            // Emit game status every 5 seconds
            if (timer % 5 === 0) {
                getSlotGameStatus(roomId)
                    .then((data) => {
                        slot_game_socket.emit('slot_game_status', JSON.stringify(data));
                    })
                    .catch((error) => {
                        console.error("Error fetching game status:", error);
                    });
            }

            console.log("slot_game_timer", timer);
            slot_game_socket.emit('slot_game_timer', timer);
            timer--;
        }
    }
};
