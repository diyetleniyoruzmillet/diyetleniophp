import { Server as HTTPServer } from 'http';
import { Server, Socket } from 'socket.io';
import { logger } from './utils/logger';
import { config } from './config';

interface SignalingData {
  roomId: string;
  userId: number;
  userName: string;
  userType: 'dietitian' | 'client' | 'admin';
}

export class SignalingServer {
  private io: Server;
  private rooms: Map<string, Set<string>> = new Map();

  constructor(server: HTTPServer) {
    this.io = new Server(server, {
      cors: {
        origin: config.cors.origin,
        credentials: true,
      },
      path: '/socket.io',
    });

    this.setupEventHandlers();
  }

  private setupEventHandlers() {
    this.io.on('connection', (socket: Socket) => {
      logger.info('Client connected:', socket.id);

      // Join room
      socket.on('join-room', (data: SignalingData) => {
        const { roomId, userId, userName, userType } = data;

        socket.join(roomId);

        // Track room members
        if (!this.rooms.has(roomId)) {
          this.rooms.set(roomId, new Set());
        }
        this.rooms.get(roomId)!.add(socket.id);

        // Store user info in socket
        socket.data.userId = userId;
        socket.data.userName = userName;
        socket.data.userType = userType;
        socket.data.roomId = roomId;

        logger.info(`User ${userName} (${userId}) joined room ${roomId}`);

        // Notify others in room
        socket.to(roomId).emit('user-joined', {
          socketId: socket.id,
          userId,
          userName,
          userType,
        });

        // Send list of existing users in room
        const roomMembers = Array.from(this.rooms.get(roomId) || [])
          .filter((id) => id !== socket.id)
          .map((id) => {
            const memberSocket = this.io.sockets.sockets.get(id);
            return {
              socketId: id,
              userId: memberSocket?.data.userId,
              userName: memberSocket?.data.userName,
              userType: memberSocket?.data.userType,
            };
          });

        socket.emit('room-members', roomMembers);
      });

      // WebRTC signaling
      socket.on('offer', (data: { to: string; offer: RTCSessionDescriptionInit }) => {
        logger.info(`Offer from ${socket.id} to ${data.to}`);
        this.io.to(data.to).emit('offer', {
          from: socket.id,
          offer: data.offer,
        });
      });

      socket.on('answer', (data: { to: string; answer: RTCSessionDescriptionInit }) => {
        logger.info(`Answer from ${socket.id} to ${data.to}`);
        this.io.to(data.to).emit('answer', {
          from: socket.id,
          answer: data.answer,
        });
      });

      socket.on('ice-candidate', (data: { to: string; candidate: RTCIceCandidateInit }) => {
        this.io.to(data.to).emit('ice-candidate', {
          from: socket.id,
          candidate: data.candidate,
        });
      });

      // Chat messages
      socket.on('chat-message', (data: { message: string }) => {
        const roomId = socket.data.roomId;
        if (roomId) {
          this.io.to(roomId).emit('chat-message', {
            userId: socket.data.userId,
            userName: socket.data.userName,
            message: data.message,
            timestamp: new Date().toISOString(),
          });
        }
      });

      // Leave room
      socket.on('leave-room', () => {
        this.handleLeaveRoom(socket);
      });

      // Disconnect
      socket.on('disconnect', () => {
        logger.info('Client disconnected:', socket.id);
        this.handleLeaveRoom(socket);
      });
    });

    logger.info('🎥 WebRTC Signaling Server initialized');
  }

  private handleLeaveRoom(socket: Socket) {
    const roomId = socket.data.roomId;
    if (roomId) {
      socket.to(roomId).emit('user-left', {
        socketId: socket.id,
        userId: socket.data.userId,
        userName: socket.data.userName,
      });

      // Remove from room tracking
      const room = this.rooms.get(roomId);
      if (room) {
        room.delete(socket.id);
        if (room.size === 0) {
          this.rooms.delete(roomId);
          logger.info(`Room ${roomId} is now empty and removed`);
        }
      }

      socket.leave(roomId);
    }
  }

  public getRooms() {
    return Array.from(this.rooms.entries()).map(([roomId, members]) => ({
      roomId,
      memberCount: members.size,
    }));
  }
}
