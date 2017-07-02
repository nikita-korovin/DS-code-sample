var fs = require('fs');
var https = require('https');
var express = require('express');
var app = express();
var Redis = require('ioredis');
var redis = new Redis(6379, '127.0.0.1');

var options = {
    key: fs.readFileSync('/etc/letsencrypt/live/docswift.com/privkey.pem'),
    cert: fs.readFileSync('/etc/letsencrypt/live/docswift.com/cert.pem'),
    ca: fs.readFileSync('/etc/letsencrypt/live/docswift.com/chain.pem')
};
var serverPort = 8080;
var server = https.createServer(options, app);
var io = require('socket.io')(server);

redis.psubscribe('*', function(err, count){
    console.log(err);
    console.log(count);
});

redis.on('pmessage', function(subscribed, channel, message){
    console.log(message);
    message = JSON.parse(message);
    io.sockets.in(message.data.to.email).emit('event', {event: 1});
});

server.listen(serverPort, function() {
    console.log('server up and running at %s port', serverPort);
});

io.sockets.on('connection', function (socket) {
    socket.on('join', function (data) {
	console.log(data.email);
        socket.join(data.email);
    });
});
