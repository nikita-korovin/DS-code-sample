var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var Redis = require('ioredis');
var redis = new Redis(6379, '127.0.0.1');

redis.psubscribe('*', function(err, count){
});

redis.on('pmessage', function(subscribed, channel, message){
    message = JSON.parse(message);
    io.sockets.in(message.data.to.email).emit('event', {event: 1});
});

http.listen('8080');

io.sockets.on('connection', function (socket) {
    socket.on('join', function (data) {
        socket.join(data.email);
    });
});