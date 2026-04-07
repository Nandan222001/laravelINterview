# Realtime Communication - Supplemental Answers

## Socket.io Integration

### How do you integrate Socket.io with a Laravel backend?

**Server-side (Node.js with Socket.io):**
```javascript
const express = require('express');
const { createServer } = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');
const jwt = require('jsonwebtoken');

const app = express();
const httpServer = createServer(app);

const io = new Server(httpServer, {
  cors: {
    origin: process.env.ALLOWED_ORIGINS.split(','),
    credentials: true
  },
  pingTimeout: 60000,
  pingInterval: 25000,
  maxHttpBufferSize: 1e6,
  transports: ['websocket', 'polling']
});

// Redis adapter for horizontal scaling
const { createAdapter } = require('@socket.io/redis-adapter');
const pubClient = new Redis(process.env.REDIS_URL);
const subClient = pubClient.duplicate();
io.adapter(createAdapter(pubClient, subClient));

// Authentication middleware
io.use(async (socket, next) => {
  try {
    const token = socket.handshake.auth.token;
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    
    // Verify with Laravel backend
    const response = await fetch(`${process.env.LARAVEL_API_URL}/api/user`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (!response.ok) {
      return next(new Error('Authentication failed'));
    }
    
    const user = await response.json();
    socket.user = user;
    next();
  } catch (error) {
    next(new Error('Authentication failed'));
  }
});

io.on('connection', (socket) => {
  console.log(`User connected: ${socket.user.id}`);
  
  // Join user-specific room
  socket.join(`user:${socket.user.id}`);
  
  socket.on('disconnect', () => {
    console.log(`User disconnected: ${socket.user.id}`);
  });
});

httpServer.listen(3000);
```

**Laravel Integration:**
```php
// app/Broadcasting/SocketIoBroadcaster.php
namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Predis\Client as Redis;

class SocketIoBroadcaster extends Broadcaster
{
    protected $redis;
    
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }
    
    public function broadcast(array $channels, $event, array $payload = [])
    {
        foreach ($channels as $channel) {
            $this->redis->publish('socket.io', json_encode([
                'channel' => $this->formatChannel($channel),
                'event' => $event,
                'payload' => $payload
            ]));
        }
    }
    
    public function auth($request)
    {
        // Authentication logic
    }
    
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }
}

// Broadcasting to Socket.io from Laravel
use Illuminate\Support\Facades\Redis;

Redis::publish('socket.io', json_encode([
    'channel' => 'user:123',
    'event' => 'notification',
    'payload' => [
        'message' => 'Hello from Laravel!'
    ]
]));
```

**Node.js listening to Laravel broadcasts:**
```javascript
const subscriber = new Redis(process.env.REDIS_URL);

subscriber.subscribe('socket.io', (err, count) => {
  if (err) {
    console.error('Failed to subscribe:', err);
    return;
  }
  console.log(`Subscribed to ${count} channel(s)`);
});

subscriber.on('message', (channel, message) => {
  const data = JSON.parse(message);
  
  // Emit to specific Socket.io channel/room
  io.to(data.channel).emit(data.event, data.payload);
});
```

### How do you implement Socket.io namespaces for multi-tenant applications?

```javascript
// Create namespace per tenant
const tenants = ['tenant1', 'tenant2', 'tenant3'];

tenants.forEach(tenantId => {
  const namespace = io.of(`/${tenantId}`);
  
  // Tenant-specific authentication
  namespace.use(async (socket, next) => {
    const token = socket.handshake.auth.token;
    const user = await verifyJWT(token);
    
    if (user.tenant_id !== tenantId) {
      return next(new Error('Tenant mismatch'));
    }
    
    socket.user = user;
    next();
  });
  
  namespace.on('connection', (socket) => {
    console.log(`User ${socket.user.id} connected to tenant ${tenantId}`);
    
    // Tenant-specific rooms
    socket.on('join:room', async (roomId) => {
      // Verify room belongs to tenant
      const room = await Room.findOne({ 
        id: roomId, 
        tenant_id: tenantId 
      });
      
      if (!room) {
        socket.emit('error', { message: 'Room not found' });
        return;
      }
      
      await socket.join(`room:${roomId}`);
      socket.emit('room:joined', { roomId });
    });
    
    socket.on('message', (data) => {
      namespace.to(`room:${data.roomId}`).emit('message', {
        ...data,
        userId: socket.user.id,
        timestamp: Date.now()
      });
    });
  });
});
```

### How do you handle Socket.io connection state recovery?

```javascript
const io = new Server(httpServer, {
  connectionStateRecovery: {
    maxDisconnectionDuration: 2 * 60 * 1000, // 2 minutes
    skipMiddlewares: true
  }
});

// Store connection state in Redis
const connectionStates = new Map();

io.on('connection', (socket) => {
  if (socket.recovered) {
    // Connection recovered, restore state
    console.log('Connection recovered for user:', socket.user.id);
    
    const savedState = connectionStates.get(socket.user.id);
    if (savedState) {
      // Restore rooms
      savedState.rooms.forEach(room => socket.join(room));
      
      // Send missed messages
      socket.emit('state:restored', {
        rooms: savedState.rooms,
        missedMessages: savedState.missedMessages
      });
    }
  } else {
    console.log('New connection for user:', socket.user.id);
  }
  
  // Track connection state
  socket.on('join:room', (roomId) => {
    socket.join(roomId);
    updateConnectionState(socket.user.id, socket.rooms);
  });
  
  socket.on('disconnect', () => {
    connectionStates.set(socket.user.id, {
      rooms: Array.from(socket.rooms),
      disconnectedAt: Date.now(),
      missedMessages: []
    });
    
    // Clean up after timeout
    setTimeout(() => {
      connectionStates.delete(socket.user.id);
    }, 2 * 60 * 1000);
  });
});
```

### How do you implement Socket.io acknowledgments for reliable message delivery?

```javascript
// Server-side
io.on('connection', (socket) => {
  socket.on('create:order', async (orderData, callback) => {
    try {
      // Process order
      const order = await Order.create({
        ...orderData,
        userId: socket.user.id
      });
      
      // Send acknowledgment
      callback({ 
        success: true, 
        orderId: order.id,
        timestamp: Date.now()
      });
      
      // Broadcast to relevant users
      io.to(`user:${socket.user.id}`).emit('order:created', {
        orderId: order.id,
        status: order.status
      });
    } catch (error) {
      callback({ 
        success: false, 
        error: error.message 
      });
    }
  });
});

// Client-side with timeout and retry
class ReliableSocketClient {
  constructor(url) {
    this.socket = io(url);
    this.maxRetries = 3;
  }
  
  async sendWithAck(event, data, timeout = 5000) {
    let retries = 0;
    
    while (retries < this.maxRetries) {
      try {
        const response = await new Promise((resolve, reject) => {
          const timer = setTimeout(() => {
            reject(new Error('Acknowledgment timeout'));
          }, timeout);
          
          this.socket.timeout(timeout).emit(event, data, (err, response) => {
            clearTimeout(timer);
            if (err) {
              reject(err);
            } else {
              resolve(response);
            }
          });
        });
        
        if (response.success) {
          return response;
        } else {
          throw new Error(response.error);
        }
      } catch (error) {
        retries++;
        if (retries >= this.maxRetries) {
          throw error;
        }
        // Exponential backoff
        await new Promise(resolve => 
          setTimeout(resolve, Math.pow(2, retries) * 1000)
        );
      }
    }
  }
}

// Usage
const client = new ReliableSocketClient('http://localhost:3000');

try {
  const result = await client.sendWithAck('create:order', {
    productId: 123,
    quantity: 2
  });
  console.log('Order created:', result.orderId);
} catch (error) {
  console.error('Failed to create order:', error);
}
```

## Presence Channels

### How do you implement presence channels with Socket.io?

```javascript
const onlineUsers = new Map(); // userId -> socket data
const roomPresence = new Map(); // roomId -> Set of userIds

io.on('connection', (socket) => {
  // Add user to global presence
  onlineUsers.set(socket.user.id, {
    socketId: socket.id,
    username: socket.user.name,
    avatar: socket.user.avatar,
    status: 'online',
    connectedAt: Date.now(),
    lastSeen: Date.now()
  });
  
  // Notify others user is online
  socket.broadcast.emit('user:online', {
    userId: socket.user.id,
    username: socket.user.name,
    avatar: socket.user.avatar
  });
  
  // Send current online users to new connection
  socket.emit('presence:online', {
    users: Array.from(onlineUsers.values())
  });
  
  // Join presence room
  socket.on('presence:join', async (roomId) => {
    await socket.join(`presence:${roomId}`);
    
    if (!roomPresence.has(roomId)) {
      roomPresence.set(roomId, new Set());
    }
    roomPresence.get(roomId).add(socket.user.id);
    
    // Get all users in room
    const usersInRoom = Array.from(roomPresence.get(roomId))
      .map(userId => onlineUsers.get(userId))
      .filter(Boolean);
    
    // Notify user who's here
    socket.emit('presence:here', {
      roomId,
      users: usersInRoom
    });
    
    // Notify others user joined
    socket.to(`presence:${roomId}`).emit('presence:joining', {
      roomId,
      user: onlineUsers.get(socket.user.id)
    });
  });
  
  // Leave presence room
  socket.on('presence:leave', (roomId) => {
    socket.leave(`presence:${roomId}`);
    roomPresence.get(roomId)?.delete(socket.user.id);
    
    socket.to(`presence:${roomId}`).emit('presence:leaving', {
      roomId,
      userId: socket.user.id
    });
  });
  
  // Status updates
  socket.on('presence:status', (status) => {
    const user = onlineUsers.get(socket.user.id);
    if (user) {
      user.status = status;
      user.lastSeen = Date.now();
      
      // Broadcast status change
      io.emit('user:status', {
        userId: socket.user.id,
        status: status
      });
    }
  });
  
  // Heartbeat
  socket.on('presence:heartbeat', () => {
    const user = onlineUsers.get(socket.user.id);
    if (user) {
      user.lastSeen = Date.now();
    }
  });
  
  // Disconnect handling
  socket.on('disconnecting', () => {
    // Notify all rooms user was in
    for (const room of socket.rooms) {
      if (room.startsWith('presence:')) {
        const roomId = room.replace('presence:', '');
        socket.to(room).emit('presence:leaving', {
          roomId,
          userId: socket.user.id
        });
        roomPresence.get(roomId)?.delete(socket.user.id);
      }
    }
  });
  
  socket.on('disconnect', () => {
    onlineUsers.delete(socket.user.id);
    io.emit('user:offline', {
      userId: socket.user.id
    });
  });
});

// Periodic cleanup of stale connections
setInterval(() => {
  const now = Date.now();
  const timeout = 60000; // 1 minute
  
  onlineUsers.forEach((user, userId) => {
    if (now - user.lastSeen > timeout) {
      onlineUsers.delete(userId);
      io.emit('user:offline', { userId });
    }
  });
}, 30000);
```

### How do you implement typing indicators in presence channels?

```javascript
const typingUsers = new Map(); // roomId -> Map(userId -> timeout)

io.on('connection', (socket) => {
  socket.on('typing:start', (roomId) => {
    if (!typingUsers.has(roomId)) {
      typingUsers.set(roomId, new Map());
    }
    
    const roomTyping = typingUsers.get(roomId);
    
    // Clear existing timeout
    if (roomTyping.has(socket.user.id)) {
      clearTimeout(roomTyping.get(socket.user.id));
    }
    
    // Broadcast typing event
    socket.to(`presence:${roomId}`).emit('user:typing', {
      roomId,
      userId: socket.user.id,
      username: socket.user.name
    });
    
    // Auto-stop typing after 3 seconds
    const timeout = setTimeout(() => {
      roomTyping.delete(socket.user.id);
      socket.to(`presence:${roomId}`).emit('user:stopped-typing', {
        roomId,
        userId: socket.user.id
      });
    }, 3000);
    
    roomTyping.set(socket.user.id, timeout);
  });
  
  socket.on('typing:stop', (roomId) => {
    const roomTyping = typingUsers.get(roomId);
    if (roomTyping?.has(socket.user.id)) {
      clearTimeout(roomTyping.get(socket.user.id));
      roomTyping.delete(socket.user.id);
      
      socket.to(`presence:${roomId}`).emit('user:stopped-typing', {
        roomId,
        userId: socket.user.id
      });
    }
  });
  
  socket.on('disconnect', () => {
    // Clear all typing indicators for this user
    typingUsers.forEach((roomTyping, roomId) => {
      if (roomTyping.has(socket.user.id)) {
        clearTimeout(roomTyping.get(socket.user.id));
        roomTyping.delete(socket.user.id);
        
        io.to(`presence:${roomId}`).emit('user:stopped-typing', {
          roomId,
          userId: socket.user.id
        });
      }
    });
  });
});
```

### How do you implement presence channels with Laravel Echo?

```javascript
// Frontend with Laravel Echo
Echo.join('chat.1')
  .here((users) => {
    // Initial list of users in the room
    console.log('Users currently in room:', users);
    displayUsers(users);
  })
  .joining((user) => {
    // A user has joined the room
    console.log('User joined:', user.name);
    addUserToList(user);
    showNotification(`${user.name} joined the chat`);
  })
  .leaving((user) => {
    // A user has left the room
    console.log('User left:', user.name);
    removeUserFromList(user);
    showNotification(`${user.name} left the chat`);
  })
  .error((error) => {
    console.error('Presence channel error:', error);
  });

// Listen for messages in the presence channel
Echo.join('chat.1')
  .listen('MessageSent', (e) => {
    displayMessage(e.message);
  });

// Send typing indicator via whisper
const messageInput = document.getElementById('message-input');
let typingTimer;

messageInput.addEventListener('input', () => {
  clearTimeout(typingTimer);
  
  Echo.join('chat.1').whisper('typing', {
    user: currentUser
  });
  
  typingTimer = setTimeout(() => {
    Echo.join('chat.1').whisper('stopped-typing', {
      user: currentUser
    });
  }, 1000);
});

// Listen for typing indicators
Echo.join('chat.1')
  .listenForWhisper('typing', (e) => {
    showTypingIndicator(e.user);
  })
  .listenForWhisper('stopped-typing', (e) => {
    hideTypingIndicator(e.user);
  });
```

**Laravel Backend:**
```php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    // Verify user can access room
    if ($user->canAccessRoom($roomId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
            'status' => $user->status
        ];
    }
    return false;
});

// app/Events/MessageSent.php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    public $message;
    
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function broadcastOn()
    {
        return new PresenceChannel('chat.' . $this->message->room_id);
    }
    
    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'content' => $this->message->content,
            'created_at' => $this->message->created_at
        ];
    }
}

// Controller
public function sendMessage(Request $request, $roomId)
{
    $message = Message::create([
        'room_id' => $roomId,
        'user_id' => auth()->id(),
        'content' => $request->input('content')
    ]);
    
    broadcast(new MessageSent($message))->toOthers();
    
    return response()->json($message);
}
```

## Message Queuing

### How do you implement message queuing for offline users?

```javascript
const Redis = require('ioredis');
const redis = new Redis();

// Store messages for offline users
async function queueMessageForUser(userId, message) {
  const key = `offline_messages:${userId}`;
  await redis.rpush(key, JSON.stringify({
    ...message,
    queuedAt: Date.now()
  }));
  
  // Set expiry to 7 days
  await redis.expire(key, 7 * 24 * 60 * 60);
}

// Retrieve and clear queued messages
async function getQueuedMessages(userId) {
  const key = `offline_messages:${userId}`;
  const messages = await redis.lrange(key, 0, -1);
  await redis.del(key);
  
  return messages.map(msg => JSON.parse(msg));
}

io.on('connection', async (socket) => {
  // Send queued messages on connection
  const queuedMessages = await getQueuedMessages(socket.user.id);
  
  if (queuedMessages.length > 0) {
    socket.emit('messages:queued', {
      messages: queuedMessages,
      count: queuedMessages.length
    });
  }
  
  // Handle message sending with offline queue
  socket.on('message:send', async (data) => {
    const message = {
      id: generateId(),
      senderId: socket.user.id,
      recipientId: data.recipientId,
      content: data.content,
      timestamp: Date.now()
    };
    
    // Save to database
    await Message.create(message);
    
    // Try to send in real-time
    const recipientSocket = await findUserSocket(data.recipientId);
    
    if (recipientSocket) {
      recipientSocket.emit('message:received', message);
      socket.emit('message:delivered', { messageId: message.id });
    } else {
      // Queue for offline user
      await queueMessageForUser(data.recipientId, message);
      socket.emit('message:queued', { messageId: message.id });
    }
  });
});

// Helper to find user's socket
async function findUserSocket(userId) {
  const sockets = await io.fetchSockets();
  return sockets.find(s => s.user.id === userId);
}
```

### How do you implement priority message queuing?

```javascript
const Queue = require('bull');

// Create different queues for different priorities
const highPriorityQueue = new Queue('messages:high', {
  redis: { host: 'localhost', port: 6379 }
});

const normalPriorityQueue = new Queue('messages:normal', {
  redis: { host: 'localhost', port: 6379 }
});

const lowPriorityQueue = new Queue('messages:low', {
  redis: { host: 'localhost', port: 6379 }
});

// Process queues with appropriate concurrency
highPriorityQueue.process(10, async (job) => {
  await processMessage(job.data);
});

normalPriorityQueue.process(5, async (job) => {
  await processMessage(job.data);
});

lowPriorityQueue.process(2, async (job) => {
  await processMessage(job.data);
});

// Add messages to appropriate queue
async function queueMessage(message, priority = 'normal') {
  const queues = {
    high: highPriorityQueue,
    normal: normalPriorityQueue,
    low: lowPriorityQueue
  };
  
  const queue = queues[priority] || normalPriorityQueue;
  
  await queue.add(message, {
    attempts: 3,
    backoff: {
      type: 'exponential',
      delay: 2000
    },
    removeOnComplete: true,
    removeOnFail: false
  });
}

async function processMessage(messageData) {
  const { recipientId, content, type } = messageData;
  
  // Try to deliver via Socket.io
  const socket = await findUserSocket(recipientId);
  
  if (socket) {
    socket.emit('message', messageData);
  } else {
    // Store for later delivery
    await queueMessageForUser(recipientId, messageData);
  }
}

// Usage in Socket.io
io.on('connection', (socket) => {
  socket.on('message:send', async (data) => {
    const message = {
      id: generateId(),
      senderId: socket.user.id,
      recipientId: data.recipientId,
      content: data.content,
      type: data.type,
      timestamp: Date.now()
    };
    
    // Determine priority based on message type
    let priority = 'normal';
    if (data.type === 'alert') priority = 'high';
    if (data.type === 'notification') priority = 'low';
    
    await queueMessage(message, priority);
    
    socket.emit('message:queued', {
      messageId: message.id,
      priority
    });
  });
});
```

### How do you implement message persistence with guaranteed delivery?

```javascript
const { MongoClient } = require('mongodb');
const EventEmitter = require('events');

class MessageQueue extends EventEmitter {
  constructor(mongoUrl) {
    super();
    this.mongoClient = new MongoClient(mongoUrl);
    this.db = null;
    this.collection = null;
  }
  
  async connect() {
    await this.mongoClient.connect();
    this.db = this.mongoClient.db('realtime');
    this.collection = this.db.collection('message_queue');
    
    // Create indexes
    await this.collection.createIndex({ recipientId: 1, delivered: 1 });
    await this.collection.createIndex({ createdAt: 1 }, { expireAfterSeconds: 604800 }); // 7 days
  }
  
  async enqueue(message) {
    const document = {
      ...message,
      delivered: false,
      attempts: 0,
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    const result = await this.collection.insertOne(document);
    this.emit('enqueued', { messageId: result.insertedId });
    
    return result.insertedId;
  }
  
  async getPending(recipientId, limit = 100) {
    return await this.collection
      .find({ recipientId, delivered: false })
      .sort({ createdAt: 1 })
      .limit(limit)
      .toArray();
  }
  
  async markDelivered(messageId) {
    await this.collection.updateOne(
      { _id: messageId },
      { 
        $set: { 
          delivered: true,
          deliveredAt: new Date(),
          updatedAt: new Date()
        }
      }
    );
    
    this.emit('delivered', { messageId });
  }
  
  async incrementAttempts(messageId) {
    await this.collection.updateOne(
      { _id: messageId },
      { 
        $inc: { attempts: 1 },
        $set: { updatedAt: new Date() }
      }
    );
  }
}

// Initialize queue
const messageQueue = new MessageQueue('mongodb://localhost:27017');
await messageQueue.connect();

io.on('connection', async (socket) => {
  // Deliver pending messages
  const pendingMessages = await messageQueue.getPending(socket.user.id);
  
  for (const message of pendingMessages) {
    try {
      socket.emit('message', message);
      await messageQueue.markDelivered(message._id);
    } catch (error) {
      await messageQueue.incrementAttempts(message._id);
      console.error('Failed to deliver message:', error);
    }
  }
  
  // Handle new messages
  socket.on('message:send', async (data) => {
    const message = {
      senderId: socket.user.id,
      recipientId: data.recipientId,
      content: data.content,
      type: data.type || 'text',
      metadata: data.metadata || {}
    };
    
    // Persist message
    const messageId = await messageQueue.enqueue(message);
    
    // Try immediate delivery
    const recipientSocket = await findUserSocket(data.recipientId);
    
    if (recipientSocket) {
      try {
        recipientSocket.emit('message', { ...message, _id: messageId });
        await messageQueue.markDelivered(messageId);
        socket.emit('message:delivered', { messageId });
      } catch (error) {
        console.error('Failed immediate delivery:', error);
        socket.emit('message:queued', { messageId });
      }
    } else {
      socket.emit('message:queued', { messageId });
    }
  });
  
  // Acknowledge message receipt
  socket.on('message:ack', async (messageId) => {
    await messageQueue.markDelivered(messageId);
  });
});
```

## Scaling WebSocket Connections

### How do you scale WebSocket connections across multiple servers?

```javascript
// Using Redis Pub/Sub for horizontal scaling
const { createAdapter } = require('@socket.io/redis-adapter');
const { createClient } = require('redis');
const cluster = require('cluster');
const os = require('os');

if (cluster.isMaster) {
  // Master process
  const numCPUs = os.cpus().length;
  console.log(`Master process ${process.pid} starting ${numCPUs} workers`);
  
  for (let i = 0; i < numCPUs; i++) {
    cluster.fork();
  }
  
  cluster.on('exit', (worker, code, signal) => {
    console.log(`Worker ${worker.process.pid} died. Starting new worker...`);
    cluster.fork();
  });
} else {
  // Worker process
  const app = express();
  const httpServer = createServer(app);
  
  const io = new Server(httpServer, {
    cors: {
      origin: process.env.ALLOWED_ORIGINS.split(','),
      credentials: true
    }
  });
  
  // Redis adapter for cross-server communication
  const pubClient = createClient({ 
    url: process.env.REDIS_URL,
    socket: {
      reconnectStrategy: (retries) => Math.min(retries * 50, 500)
    }
  });
  
  const subClient = pubClient.duplicate();
  
  Promise.all([pubClient.connect(), subClient.connect()]).then(() => {
    io.adapter(createAdapter(pubClient, subClient));
    
    // Track connections per worker
    io.on('connection', (socket) => {
      console.log(`Worker ${process.pid}: User ${socket.user.id} connected`);
      
      socket.on('disconnect', () => {
        console.log(`Worker ${process.pid}: User ${socket.user.id} disconnected`);
      });
    });
    
    const PORT = process.env.PORT || 3000;
    httpServer.listen(PORT, () => {
      console.log(`Worker ${process.pid} listening on port ${PORT}`);
    });
  });
}
```

### How do you implement sticky sessions for load balancing?

**NGINX Configuration:**
```nginx
upstream socketio_nodes {
    ip_hash; # Sticky sessions based on client IP
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
    server 127.0.0.1:3002;
    server 127.0.0.1:3003;
}

server {
    listen 80;
    server_name example.com;
    
    location / {
        proxy_pass http://socketio_nodes;
        proxy_http_version 1.1;
        
        # WebSocket support
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }
}
```

**Alternative: Using @socket.io/sticky:**
```javascript
const cluster = require('cluster');
const http = require('http');
const { setupMaster, setupWorker } = require('@socket.io/sticky');
const { createAdapter, setupPrimary } = require('@socket.io/cluster-adapter');

if (cluster.isMaster) {
  console.log(`Master ${process.pid} is running`);
  
  const httpServer = http.createServer();
  
  // Setup sticky sessions
  setupMaster(httpServer, {
    loadBalancingMethod: 'least-connection' // or 'round-robin'
  });
  
  // Setup cluster adapter
  setupPrimary();
  
  httpServer.listen(3000);
  
  // Fork workers
  for (let i = 0; i < 4; i++) {
    cluster.fork();
  }
  
  cluster.on('exit', (worker) => {
    console.log(`Worker ${worker.process.pid} died`);
    cluster.fork();
  });
} else {
  console.log(`Worker ${process.pid} started`);
  
  const express = require('express');
  const { Server } = require('socket.io');
  
  const app = express();
  const httpServer = http.createServer(app);
  
  const io = new Server(httpServer);
  
  // Use cluster adapter
  io.adapter(createAdapter());
  
  // Setup worker
  setupWorker(io);
  
  io.on('connection', (socket) => {
    console.log(`Worker ${process.pid}: socket ${socket.id} connected`);
  });
}
```

### How do you handle high-traffic WebSocket applications?

```javascript
const { Server } = require('socket.io');
const Redis = require('ioredis');
const prometheus = require('prom-client');

// Metrics
const register = new prometheus.Register();

const connectionsGauge = new prometheus.Gauge({
  name: 'websocket_connections_total',
  help: 'Total number of active WebSocket connections',
  registers: [register]
});

const messagesCounter = new prometheus.Counter({
  name: 'websocket_messages_total',
  help: 'Total number of messages sent/received',
  labelNames: ['direction', 'type'],
  registers: [register]
});

const messageLatency = new prometheus.Histogram({
  name: 'websocket_message_latency_seconds',
  help: 'Message processing latency',
  buckets: [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1],
  registers: [register]
});

// Initialize Socket.io with optimizations
const io = new Server(httpServer, {
  // Transport configuration
  transports: ['websocket'], // WebSocket only for better performance
  
  // Connection limits
  maxHttpBufferSize: 1e6, // 1MB
  
  // Ping configuration
  pingTimeout: 60000,
  pingInterval: 25000,
  
  // Compression
  perMessageDeflate: {
    threshold: 1024 // Only compress messages > 1KB
  }
});

// Redis for scaling
const pubClient = new Redis({
  host: process.env.REDIS_HOST,
  port: process.env.REDIS_PORT,
  password: process.env.REDIS_PASSWORD,
  maxRetriesPerRequest: 3,
  enableReadyCheck: true,
  lazyConnect: true
});

const subClient = pubClient.duplicate();

// Connection pooling
pubClient.on('connect', () => {
  console.log('Redis pub client connected');
});

subClient.on('connect', () => {
  console.log('Redis sub client connected');
});

// Setup adapter
const { createAdapter } = require('@socket.io/redis-adapter');
io.adapter(createAdapter(pubClient, subClient));

// Rate limiting
const rateLimits = new Map();

function checkRateLimit(userId, limit = 100, window = 60000) {
  const now = Date.now();
  const userLimit = rateLimits.get(userId) || { count: 0, resetTime: now + window };
  
  if (now > userLimit.resetTime) {
    userLimit.count = 0;
    userLimit.resetTime = now + window;
  }
  
  userLimit.count++;
  rateLimits.set(userId, userLimit);
  
  return userLimit.count <= limit;
}

// Message batching
class MessageBatcher {
  constructor(flushInterval = 100) {
    this.batches = new Map();
    this.flushInterval = flushInterval;
    this.timers = new Map();
  }
  
  add(roomId, message) {
    if (!this.batches.has(roomId)) {
      this.batches.set(roomId, []);
    }
    
    this.batches.get(roomId).push(message);
    
    if (!this.timers.has(roomId)) {
      this.timers.set(roomId, setTimeout(() => {
        this.flush(roomId);
      }, this.flushInterval));
    }
  }
  
  flush(roomId) {
    const messages = this.batches.get(roomId);
    
    if (messages && messages.length > 0) {
      io.to(roomId).emit('messages:batch', messages);
      this.batches.delete(roomId);
    }
    
    if (this.timers.has(roomId)) {
      clearTimeout(this.timers.get(roomId));
      this.timers.delete(roomId);
    }
  }
}

const batcher = new MessageBatcher(50);

io.on('connection', (socket) => {
  connectionsGauge.inc();
  
  // Authentication check
  if (!socket.user) {
    socket.disconnect(true);
    return;
  }
  
  // Join user room
  socket.join(`user:${socket.user.id}`);
  
  // Message handling with metrics
  socket.on('message', async (data) => {
    const startTime = Date.now();
    
    // Rate limiting
    if (!checkRateLimit(socket.user.id)) {
      socket.emit('error', { code: 'RATE_LIMIT_EXCEEDED' });
      return;
    }
    
    messagesCounter.inc({ direction: 'received', type: data.type });
    
    try {
      // Process message
      const message = {
        id: generateId(),
        userId: socket.user.id,
        content: data.content,
        roomId: data.roomId,
        timestamp: Date.now()
      };
      
      // Batch messages for high throughput
      if (data.batch) {
        batcher.add(data.roomId, message);
      } else {
        io.to(data.roomId).emit('message', message);
      }
      
      messagesCounter.inc({ direction: 'sent', type: data.type });
    } finally {
      const duration = (Date.now() - startTime) / 1000;
      messageLatency.observe(duration);
    }
  });
  
  socket.on('disconnect', () => {
    connectionsGauge.dec();
  });
});

// Metrics endpoint
app.get('/metrics', async (req, res) => {
  res.set('Content-Type', register.contentType);
  res.end(await register.metrics());
});

// Health check
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    connections: io.engine.clientsCount,
    uptime: process.uptime(),
    memory: process.memoryUsage()
  });
});

// Graceful shutdown
process.on('SIGTERM', gracefulShutdown);
process.on('SIGINT', gracefulShutdown);

function gracefulShutdown() {
  console.log('Shutting down gracefully...');
  
  // Stop accepting new connections
  io.close(() => {
    console.log('All connections closed');
    
    // Close Redis connections
    Promise.all([
      pubClient.quit(),
      subClient.quit()
    ]).then(() => {
      console.log('Redis connections closed');
      process.exit(0);
    });
  });
  
  // Force shutdown after 30 seconds
  setTimeout(() => {
    console.error('Forcing shutdown');
    process.exit(1);
  }, 30000);
}
```

### How do you implement connection pooling and resource management?

```javascript
const { Pool } = require('pg');
const Redis = require('ioredis');

// Database connection pool
const dbPool = new Pool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  max: 20, // Maximum connections
  min: 5, // Minimum connections
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});

// Redis connection pool
const redisPool = new Redis.Cluster([
  {
    host: process.env.REDIS_HOST_1,
    port: process.env.REDIS_PORT_1
  },
  {
    host: process.env.REDIS_HOST_2,
    port: process.env.REDIS_PORT_2
  }
], {
  redisOptions: {
    password: process.env.REDIS_PASSWORD
  },
  clusterRetryStrategy: (times) => {
    return Math.min(times * 100, 2000);
  }
});

// Resource manager
class ResourceManager {
  constructor() {
    this.connections = new Map();
    this.maxConnectionsPerUser = 5;
  }
  
  canConnect(userId) {
    const userConnections = this.connections.get(userId) || new Set();
    return userConnections.size < this.maxConnectionsPerUser;
  }
  
  addConnection(userId, socketId) {
    if (!this.connections.has(userId)) {
      this.connections.set(userId, new Set());
    }
    this.connections.get(userId).add(socketId);
  }
  
  removeConnection(userId, socketId) {
    const userConnections = this.connections.get(userId);
    if (userConnections) {
      userConnections.delete(socketId);
      if (userConnections.size === 0) {
        this.connections.delete(userId);
      }
    }
  }
  
  getConnectionCount(userId) {
    return (this.connections.get(userId) || new Set()).size;
  }
}

const resourceManager = new ResourceManager();

io.use((socket, next) => {
  if (!resourceManager.canConnect(socket.user.id)) {
    return next(new Error('Connection limit exceeded'));
  }
  next();
});

io.on('connection', (socket) => {
  resourceManager.addConnection(socket.user.id, socket.id);
  
  socket.on('disconnect', () => {
    resourceManager.removeConnection(socket.user.id, socket.id);
  });
});

// Cleanup old connections periodically
setInterval(() => {
  const sockets = io.sockets.sockets;
  
  resourceManager.connections.forEach((socketIds, userId) => {
    socketIds.forEach(socketId => {
      if (!sockets.has(socketId)) {
        resourceManager.removeConnection(userId, socketId);
      }
    });
  });
}, 60000);
```
