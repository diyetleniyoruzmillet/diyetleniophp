/**
 * Diyetlenio WebRTC Client
 * Handles peer-to-peer video calls with signaling server
 */

class WebRTCClient {
    constructor(config) {
        this.roomId = config.roomId;
        this.userId = config.userId;
        this.userName = config.userName;
        this.signalingServerUrl = config.signalingServerUrl || 'http://localhost:3000';

        this.socket = null;
        this.localStream = null;
        this.peerConnections = new Map(); // Map of socketId -> RTCPeerConnection

        // ICE servers
        this.iceServers = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' },
                { urls: 'stun:stun2.l.google.com:19302' }
            ]
        };

        // State
        this.isMuted = false;
        this.isVideoOff = false;

        // Callbacks
        this.onLocalStream = null;
        this.onRemoteStream = null;
        this.onConnectionStateChange = null;
        this.onError = null;
        this.onUserJoined = null;
        this.onUserLeft = null;
    }

    /**
     * Initialize WebRTC client
     */
    async init() {
        try {
            // Get local media stream
            await this.getLocalStream();

            // Connect to signaling server
            await this.connectSignaling();

            // Join room
            this.joinRoom();

            return true;
        } catch (error) {
            console.error('WebRTC initialization error:', error);
            if (this.onError) this.onError(error);
            throw error;
        }
    }

    /**
     * Get local media stream (camera + microphone)
     */
    async getLocalStream() {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    frameRate: { ideal: 30 }
                },
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });

            if (this.onLocalStream) {
                this.onLocalStream(this.localStream);
            }

            console.log('Local stream acquired');
            return this.localStream;
        } catch (error) {
            console.error('Error accessing media devices:', error);
            throw new Error('Kamera veya mikrofon erişimi reddedildi. Lütfen tarayıcı izinlerini kontrol edin.');
        }
    }

    /**
     * Connect to signaling server
     */
    connectSignaling() {
        return new Promise((resolve, reject) => {
            this.socket = io(this.signalingServerUrl, {
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionDelay: 1000,
                reconnectionAttempts: 5
            });

            this.socket.on('connect', () => {
                console.log('Connected to signaling server:', this.socket.id);
                this.setupSignalingListeners();
                resolve();
            });

            this.socket.on('connect_error', (error) => {
                console.error('Signaling connection error:', error);
                reject(error);
            });

            this.socket.on('disconnect', (reason) => {
                console.log('Disconnected from signaling server:', reason);
                if (reason === 'io server disconnect') {
                    // Server disconnected, try to reconnect
                    this.socket.connect();
                }
            });
        });
    }

    /**
     * Setup signaling event listeners
     */
    setupSignalingListeners() {
        // Existing participants (when you join)
        this.socket.on('existing-participants', async (participants) => {
            console.log('Existing participants:', participants);
            for (const participant of participants) {
                await this.createPeerConnection(participant.socketId, true);
            }
        });

        // New user joined
        this.socket.on('user-joined', async ({ socketId, userId, userName }) => {
            console.log(`User joined: ${userName} (${socketId})`);
            await this.createPeerConnection(socketId, false);
            if (this.onUserJoined) this.onUserJoined({ socketId, userId, userName });
        });

        // User left
        this.socket.on('user-left', ({ socketId, userId, userName }) => {
            console.log(`User left: ${userName} (${socketId})`);
            this.closePeerConnection(socketId);
            if (this.onUserLeft) this.onUserLeft({ socketId, userId, userName });
        });

        // Receive offer
        this.socket.on('offer', async ({ from, offer }) => {
            console.log('Received offer from:', from);
            await this.handleOffer(from, offer);
        });

        // Receive answer
        this.socket.on('answer', async ({ from, answer }) => {
            console.log('Received answer from:', from);
            await this.handleAnswer(from, answer);
        });

        // Receive ICE candidate
        this.socket.on('ice-candidate', async ({ from, candidate }) => {
            console.log('Received ICE candidate from:', from);
            await this.handleIceCandidate(from, candidate);
        });
    }

    /**
     * Join room
     */
    joinRoom() {
        this.socket.emit('join-room', {
            roomId: this.roomId,
            userId: this.userId,
            userName: this.userName
        });
        console.log(`Joining room: ${this.roomId}`);
    }

    /**
     * Create peer connection
     */
    async createPeerConnection(socketId, isInitiator) {
        try {
            const pc = new RTCPeerConnection(this.iceServers);
            this.peerConnections.set(socketId, pc);

            // Add local tracks
            this.localStream.getTracks().forEach(track => {
                pc.addTrack(track, this.localStream);
            });

            // Handle ICE candidates
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    this.socket.emit('ice-candidate', {
                        to: socketId,
                        candidate: event.candidate,
                        roomId: this.roomId
                    });
                }
            };

            // Handle remote stream
            pc.ontrack = (event) => {
                console.log('Received remote track from:', socketId);
                if (this.onRemoteStream) {
                    this.onRemoteStream(event.streams[0], socketId);
                }
            };

            // Handle connection state change
            pc.onconnectionstatechange = () => {
                console.log(`Connection state (${socketId}):`, pc.connectionState);
                if (this.onConnectionStateChange) {
                    this.onConnectionStateChange(pc.connectionState, socketId);
                }

                if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
                    setTimeout(() => {
                        if (pc.connectionState === 'failed' || pc.connectionState === 'disconnected') {
                            this.closePeerConnection(socketId);
                        }
                    }, 5000);
                }
            };

            // If initiator, create and send offer
            if (isInitiator) {
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);

                this.socket.emit('offer', {
                    to: socketId,
                    offer: pc.localDescription,
                    roomId: this.roomId
                });
                console.log('Sent offer to:', socketId);
            }

            return pc;
        } catch (error) {
            console.error('Error creating peer connection:', error);
            throw error;
        }
    }

    /**
     * Handle incoming offer
     */
    async handleOffer(from, offer) {
        try {
            let pc = this.peerConnections.get(from);
            if (!pc) {
                pc = await this.createPeerConnection(from, false);
            }

            await pc.setRemoteDescription(new RTCSessionDescription(offer));

            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);

            this.socket.emit('answer', {
                to: from,
                answer: pc.localDescription,
                roomId: this.roomId
            });
            console.log('Sent answer to:', from);
        } catch (error) {
            console.error('Error handling offer:', error);
        }
    }

    /**
     * Handle incoming answer
     */
    async handleAnswer(from, answer) {
        try {
            const pc = this.peerConnections.get(from);
            if (pc) {
                await pc.setRemoteDescription(new RTCSessionDescription(answer));
                console.log('Set remote description for:', from);
            }
        } catch (error) {
            console.error('Error handling answer:', error);
        }
    }

    /**
     * Handle incoming ICE candidate
     */
    async handleIceCandidate(from, candidate) {
        try {
            const pc = this.peerConnections.get(from);
            if (pc) {
                await pc.addIceCandidate(new RTCIceCandidate(candidate));
            }
        } catch (error) {
            console.error('Error adding ICE candidate:', error);
        }
    }

    /**
     * Close peer connection
     */
    closePeerConnection(socketId) {
        const pc = this.peerConnections.get(socketId);
        if (pc) {
            pc.close();
            this.peerConnections.delete(socketId);
            console.log('Closed peer connection:', socketId);
        }
    }

    /**
     * Toggle microphone
     */
    toggleMute() {
        if (this.localStream) {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                this.isMuted = !this.isMuted;
                audioTrack.enabled = !this.isMuted;
                console.log('Microphone', this.isMuted ? 'muted' : 'unmuted');
                return this.isMuted;
            }
        }
        return false;
    }

    /**
     * Toggle video
     */
    toggleVideo() {
        if (this.localStream) {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                this.isVideoOff = !this.isVideoOff;
                videoTrack.enabled = !this.isVideoOff;
                console.log('Video', this.isVideoOff ? 'off' : 'on');
                return this.isVideoOff;
            }
        }
        return false;
    }

    /**
     * Leave room and cleanup
     */
    leave() {
        // Stop local stream
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }

        // Close all peer connections
        this.peerConnections.forEach((pc, socketId) => {
            pc.close();
        });
        this.peerConnections.clear();

        // Leave signaling room
        if (this.socket) {
            this.socket.emit('leave-room', { roomId: this.roomId });
            this.socket.disconnect();
        }

        console.log('Left room and cleaned up');
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebRTCClient;
}
