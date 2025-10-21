/**
 * WebRTC Signaling Server
 * Simple Socket.IO server for video call signaling
 *
 * Installation:
 * npm install socket.io express
 *
 * Run:
 * node signaling-server.js
 */

const express = require('express');
const app = express();
const server = require('http').createServer(app);
const io = require('socket.io')(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.PORT || 3000;

// Store active rooms
const rooms = new Map();

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    // Join a room
    socket.on('join-room', (roomId, userId) => {
        console.log(`User ${userId} joining room ${roomId}`);

        socket.join(roomId);

        // Initialize room if doesn't exist
        if (!rooms.has(roomId)) {
            rooms.set(roomId, new Set());
        }

        const room = rooms.get(roomId);
        room.add(userId);

        // Notify others in the room
        socket.to(roomId).emit('user-connected', userId);

        // Send list of current users
        socket.emit('room-users', Array.from(room));
    });

    // Forward WebRTC offer
    socket.on('offer', (roomId, userId, offer) => {
        console.log(`Forwarding offer from ${userId} in room ${roomId}`);
        socket.to(roomId).emit('offer', userId, offer);
    });

    // Forward WebRTC answer
    socket.on('answer', (roomId, userId, answer) => {
        console.log(`Forwarding answer from ${userId} in room ${roomId}`);
        socket.to(roomId).emit('answer', userId, answer);
    });

    // Forward ICE candidate
    socket.on('ice-candidate', (roomId, userId, candidate) => {
        console.log(`Forwarding ICE candidate from ${userId}`);
        socket.to(roomId).emit('ice-candidate', userId, candidate);
    });

    // Handle disconnect
    socket.on('disconnecting', () => {
        const socketRooms = Array.from(socket.rooms);

        socketRooms.forEach(roomId => {
            if (roomId !== socket.id) {
                const room = rooms.get(roomId);
                if (room) {
                    // Remove user from room
                    room.forEach(userId => {
                        // Notify others
                        socket.to(roomId).emit('user-disconnected', userId);
                    });

                    // Clean up empty rooms
                    if (room.size === 0) {
                        rooms.delete(roomId);
                    }
                }
            }
        });
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });

    // Chat message
    socket.on('message', (roomId, userId, message) => {
        socket.to(roomId).emit('message', userId, message);
    });
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        activeRooms: rooms.size,
        uptime: process.uptime()
    });
});

server.listen(PORT, () => {
    console.log(`Signaling server running on port ${PORT}`);
    console.log(`WebSocket URL: ws://localhost:${PORT}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM received, shutting down gracefully...');
    server.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
});
