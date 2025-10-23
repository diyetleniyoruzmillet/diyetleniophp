/**
 * Diyetlenio WebRTC Signaling Server
 * Handles WebRTC signaling for peer-to-peer video calls
 */

require('dotenv').config();
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
const server = http.createServer(app);

// CORS configuration
app.use(cors({
    origin: process.env.ALLOWED_ORIGINS || 'http://localhost',
    credentials: true
}));

app.use(express.json());

// Socket.IO setup
const io = new Server(server, {
    cors: {
        origin: process.env.ALLOWED_ORIGINS || 'http://localhost',
        methods: ['GET', 'POST'],
        credentials: true
    }
});

// Store active rooms and their participants
const rooms = new Map();

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        activeRooms: rooms.size,
        timestamp: new Date().toISOString()
    });
});

// Get room info
app.get('/room/:roomId', (req, res) => {
    const { roomId } = req.params;
    const room = rooms.get(roomId);

    if (!room) {
        return res.status(404).json({ error: 'Room not found' });
    }

    res.json({
        roomId,
        participants: Array.from(room.participants.keys()),
        createdAt: room.createdAt
    });
});

// Socket.IO connection handling
io.on('connection', (socket) => {
    console.log(`[${new Date().toISOString()}] New connection: ${socket.id}`);

    // Join a room
    socket.on('join-room', ({ roomId, userId, userName }) => {
        console.log(`[${new Date().toISOString()}] User ${userName} (${userId}) joining room ${roomId}`);

        // Create room if doesn't exist
        if (!rooms.has(roomId)) {
            rooms.set(roomId, {
                participants: new Map(),
                createdAt: new Date()
            });
        }

        const room = rooms.get(roomId);

        // Add participant to room
        room.participants.set(socket.id, {
            userId,
            userName,
            joinedAt: new Date()
        });

        // Join socket.io room
        socket.join(roomId);

        // Notify others in the room
        socket.to(roomId).emit('user-joined', {
            socketId: socket.id,
            userId,
            userName
        });

        // Send list of existing participants to the new user
        const existingParticipants = Array.from(room.participants.entries())
            .filter(([sid]) => sid !== socket.id)
            .map(([sid, data]) => ({
                socketId: sid,
                ...data
            }));

        socket.emit('existing-participants', existingParticipants);

        console.log(`[${new Date().toISOString()}] Room ${roomId} now has ${room.participants.size} participants`);
    });

    // Forward WebRTC offer
    socket.on('offer', ({ to, offer, roomId }) => {
        console.log(`[${new Date().toISOString()}] Forwarding offer from ${socket.id} to ${to}`);
        io.to(to).emit('offer', {
            from: socket.id,
            offer,
            roomId
        });
    });

    // Forward WebRTC answer
    socket.on('answer', ({ to, answer, roomId }) => {
        console.log(`[${new Date().toISOString()}] Forwarding answer from ${socket.id} to ${to}`);
        io.to(to).emit('answer', {
            from: socket.id,
            answer,
            roomId
        });
    });

    // Forward ICE candidate
    socket.on('ice-candidate', ({ to, candidate, roomId }) => {
        console.log(`[${new Date().toISOString()}] Forwarding ICE candidate from ${socket.id} to ${to}`);
        io.to(to).emit('ice-candidate', {
            from: socket.id,
            candidate,
            roomId
        });
    });

    // Handle disconnect
    socket.on('disconnect', () => {
        console.log(`[${new Date().toISOString()}] User disconnected: ${socket.id}`);

        // Find and remove user from all rooms
        for (const [roomId, room] of rooms.entries()) {
            if (room.participants.has(socket.id)) {
                const participant = room.participants.get(socket.id);
                room.participants.delete(socket.id);

                // Notify others
                socket.to(roomId).emit('user-left', {
                    socketId: socket.id,
                    userId: participant.userId,
                    userName: participant.userName
                });

                console.log(`[${new Date().toISOString()}] Removed ${participant.userName} from room ${roomId}`);

                // Delete room if empty
                if (room.participants.size === 0) {
                    rooms.delete(roomId);
                    console.log(`[${new Date().toISOString()}] Room ${roomId} deleted (empty)`);
                }
            }
        }
    });

    // Explicit leave room
    socket.on('leave-room', ({ roomId }) => {
        const room = rooms.get(roomId);
        if (room && room.participants.has(socket.id)) {
            const participant = room.participants.get(socket.id);
            room.participants.delete(socket.id);

            socket.leave(roomId);

            socket.to(roomId).emit('user-left', {
                socketId: socket.id,
                userId: participant.userId,
                userName: participant.userName
            });

            console.log(`[${new Date().toISOString()}] User ${participant.userName} left room ${roomId}`);

            if (room.participants.size === 0) {
                rooms.delete(roomId);
                console.log(`[${new Date().toISOString()}] Room ${roomId} deleted (empty)`);
            }
        }
    });
});

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`
    ╔════════════════════════════════════════════╗
    ║   Diyetlenio Signaling Server Started     ║
    ╠════════════════════════════════════════════╣
    ║   Port: ${PORT.toString().padEnd(36)}║
    ║   Environment: ${(process.env.NODE_ENV || 'development').padEnd(28)}║
    ║   Time: ${new Date().toISOString().padEnd(33)}║
    ╚════════════════════════════════════════════╝
    `);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM received, closing server gracefully...');
    server.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('SIGINT received, closing server gracefully...');
    server.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
});
