#!/bin/bash
# Deploy updated ludoRoomSocket.js to server
# Run from the games/node directory:
#   bash deploy_to_server.sh

SERVER="root@socket.roxludo.com"
REMOTE_DIR="/www/wwwroot/socket.roxludo.com"

echo "==> Uploading ludoRoomSocket.js ..."
scp sockets/ludoRoomSocket.js "$SERVER:$REMOTE_DIR/sockets/ludoRoomSocket.js"

echo "==> Restarting pm2 process ..."
ssh "$SERVER" "cd $REMOTE_DIR && pm2 restart ludo-socket --update-env"

echo "==> Done. Tailing logs for 10s ..."
ssh "$SERVER" "pm2 logs ludo-socket --lines 30 --nostream"
