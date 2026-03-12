const {
  createTrippleFunGame,
  declareWinner,
  declareThreeRoundWinner,
  getActiveGameSocket,
} = require("../controllers/api/trippleFunController");

module.exports = function (tripple_fun_socket, request, BASE_URL) {
  var game_created = false;
  var timer = 15;
  var interval_id;
  const roomId = 1;

  tripple_fun_socket.on("connection", (socket) => {
    if (!game_created) {
      game_created = true;
      timer = 30;
      // Create Game
      createTrippleFunGame();
      interval_id = setInterval(timer_function, 1000);
    }

    socket.on("tripple_fun_timer", (msg) => {
      // connection.connect(function (err) {
      // console.log("Ander Bahar Connected!");
      // });
    });

    socket.on("disconnect", (reason) => {
      console.log("User Disconnected - " + socket.id + " reason - " + reason);
    });
  });

  async function timer_function() {
    if (timer <= 0) {
      var response = {};
      clearInterval(interval_id);

      // Make Winner

      declareWinner()
        .then((data) => {
          game_created = false;
          getActiveGameSocket(roomId)
            .then((data) => {
              if (data) {
                // console.log("data - ", data);
                tripple_fun_socket.emit(
                  "tripple_fun_status",
                  JSON.stringify(data)
                );
              }
            })
            .catch((err) => console.log(err));

          var user_count = tripple_fun_socket.sockets.size;
          // console.log("user_count - ", user_count);
          // console.log("game created - ", game_created);
          if (user_count > 0 && !game_created) {
            game_created = true;
            setTimeout(async function () {
              // Create Game
              await createTrippleFunGame();
            }, 5000);
            setTimeout(function () {
              interval_id = setInterval(timer_function, 1000);
            }, 10000);

            timer = 30;
          }
        })
        .catch((error) => {
          console.log(error);
        });

      // await declareThreeRoundWinner()
      //   .then((data) => {
      //     getActiveGameSocket(roomId)
      //       .then((data) => {
      //         if (data) {
      //           tripple_fun_socket.emit(
      //             "tripple_fun_status",
      //             JSON.stringify(data)
      //           );
      //         }
      //       })
      //       .catch((err) => console.log(err));
      //   })
      //   .catch((error) => {
      //     console.log(error);
      //   });
    } else if (timer % 3 == 0) {
      // Send Status
      getActiveGameSocket(roomId)
        .then((data) => {
          if (data) {
            tripple_fun_socket.emit("tripple_fun_status", JSON.stringify(data));
          }
        })
        .catch((err) => console.log(err));
    }
    tripple_fun_socket.emit("tripple_fun_timer", timer);
    timer--;
  }
};
