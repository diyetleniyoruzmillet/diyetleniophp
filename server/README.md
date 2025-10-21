# Diyetlenio Signaling Server

WebRTC signaling server for peer-to-peer video calls.

## Installation

```bash
cd server
npm install
```

## Running

### Development
```bash
npm run dev
```

### Production
```bash
npm start
```

Or with PM2:
```bash
pm2 start signaling-server.js --name diyetlenio-signaling
```

## Configuration

Default port: 3000

To change port, set environment variable:
```bash
PORT=8080 npm start
```

## Usage in Frontend

Update `public/video-room.php` to connect to signaling server:

```javascript
const socket = io('http://localhost:3000');
const roomId = new URLSearchParams(window.location.search).get('room');
const userId = 'user-' + Math.random().toString(36).substr(2, 9);

// Join room
socket.emit('join-room', roomId, userId);

// Listen for offers
socket.on('offer', async (userId, offer) => {
    // Handle WebRTC offer
});

// Listen for answers
socket.on('answer', async (userId, answer) => {
    // Handle WebRTC answer
});

// Listen for ICE candidates
socket.on('ice-candidate', (userId, candidate) => {
    // Handle ICE candidate
});

// Send offer
socket.emit('offer', roomId, userId, offer);

// Send answer
socket.emit('answer', roomId, userId, answer);

// Send ICE candidate
socket.emit('ice-candidate', roomId, userId, candidate);
```

## Deployment

### With PM2

```bash
npm install -g pm2
pm2 start signaling-server.js
pm2 save
pm2 startup
```

### With Docker

```bash
docker build -t diyetlenio-signaling .
docker run -p 3000:3000 diyetlenio-signaling
```

### With systemd

Create `/etc/systemd/system/diyetlenio-signaling.service`:

```ini
[Unit]
Description=Diyetlenio Signaling Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/home/monster/diyetlenio/server
ExecStart=/usr/bin/node signaling-server.js
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl enable diyetlenio-signaling
sudo systemctl start diyetlenio-signaling
```

## Health Check

```bash
curl http://localhost:3000/health
```

Returns:
```json
{
  "status": "ok",
  "activeRooms": 2,
  "uptime": 123.456
}
```
