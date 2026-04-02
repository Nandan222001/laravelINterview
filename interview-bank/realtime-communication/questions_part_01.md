# Real-Time Communication & Data Flow - Part 1

## WebSocket Fundamentals (Questions 1-100)

### 1. What is the WebSocket protocol and how does it differ from HTTP?
WebSocket is a full-duplex communication protocol providing persistent, bidirectional connections. Unlike HTTP's request-response model, WebSocket maintains an open connection allowing both parties to send data at any time without polling, reducing latency and overhead.

### 2. Explain the WebSocket handshake process.
The handshake starts with an HTTP upgrade request with `Upgrade: websocket` and `Connection: Upgrade` headers. Client sends `Sec-WebSocket-Key`, server responds with `Sec-WebSocket-Accept`. Upon HTTP 101 response, connection upgrades to WebSocket protocol.

### 3. How do you implement WebSocket connection with reconnection logic?
```javascript
class WebSocketClient {
  constructor(url) {
    this.url = url;
    this.reconnectInterval = 1000;
    this.maxReconnectInterval = 30000;
    this.reconnectDecay = 1.5;
    this.connect();
  }

  connect() {
    this.ws = new WebSocket(this.url);
    
    this.ws.onopen = () => {
      this.reconnectInterval = 1000;
      console.log('Connected');
    };
    
    this.ws.onclose = () => {
      setTimeout(() => {
        this.reconnectInterval = Math.min(
          this.reconnectInterval * this.reconnectDecay,
          this.maxReconnectInterval
        );
        this.connect();
      }, this.reconnectInterval);
    };
    
    this.ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };
  }

  send(data) {
    if (this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data));
    }
  }
}
```

### 4. What are the WebSocket readyState values?
- `0 (CONNECTING)`: Connection not yet established
- `1 (OPEN)`: Connection established and ready
- `2 (CLOSING)`: Connection closing handshake initiated
- `3 (CLOSED)`: Connection closed or couldn't be opened

### 5. How do you handle binary data in WebSocket?
```javascript
// Sending binary data
const buffer = new ArrayBuffer(8);
const view = new DataView(buffer);
view.setInt32(0, 42);
ws.send(buffer);

// Receiving binary data
ws.binaryType = 'arraybuffer';
ws.onmessage = (event) => {
  if (event.data instanceof ArrayBuffer) {
    const view = new DataView(event.data);
    const value = view.getInt32(0);
  }
};
```

### 6. What is the difference between WebSocket and Server-Sent Events (SSE)?
- WebSocket: Bidirectional, binary/text, custom protocol, requires handshake
- SSE: Unidirectional (server→client), text only, HTTP-based, auto-reconnect
- Use SSE for simple server updates, WebSocket for full-duplex communication

### 7. How do you implement WebSocket authentication?
```javascript
// Client
const token = localStorage.getItem('authToken');
const ws = new WebSocket(`wss://api.example.com?token=${token}`);

// Server (Node.js)
const WebSocket = require('ws');
const wss = new WebSocket.Server({
  verifyClient: (info, callback) => {
    const token = new URL(info.req.url, 'ws://localhost').searchParams.get('token');
    jwt.verify(token, SECRET, (err, decoded) => {
      if (err) return callback(false, 401, 'Unauthorized');
      info.req.user = decoded;
      callback(true);
    });
  }
});
```

### 8. How do you scale WebSocket connections across multiple servers?
```javascript
const Redis = require('ioredis');
const pub = new Redis();
const sub = new Redis();

// Subscribe to channel
sub.subscribe('chat:room:123');

sub.on('message', (channel, message) => {
  wss.clients.forEach(client => {
    if (client.room === '123' && client.readyState === WebSocket.OPEN) {
      client.send(message);
    }
  });
});

// Publish messages
wss.on('connection', (ws) => {
  ws.on('message', (message) => {
    pub.publish('chat:room:123', message);
  });
});
```

### 9. What is the purpose of the Sec-WebSocket-Key header?
It's a base64-encoded random value proving the client intended to open a WebSocket. Server concatenates it with magic GUID (258EAFA5-E914-47DA-95CA-C5AB0DC85B11), hashes with SHA-1, base64 encodes, and returns in Sec-WebSocket-Accept to prove WebSocket understanding.

### 10. How do you implement heartbeat/keepalive in WebSocket?
```javascript
class HeartbeatWebSocket {
  constructor(url) {
    this.url = url;
    this.heartbeatInterval = 30000;
    this.missedHeartbeats = 0;
    this.connect();
  }

  startHeartbeat() {
    this.heartbeatTimer = setInterval(() => {
      if (this.missedHeartbeats >= 3) {
        this.ws.close();
        return;
      }
      this.ws.send(JSON.stringify({ type: 'ping' }));
      this.missedHeartbeats++;
    }, this.heartbeatInterval);
  }

  handlePong() {
    this.missedHeartbeats = 0;
  }
}
```

### 11. What are WebSocket subprotocols?
Subprotocols define message format and protocol rules over WebSocket, specified in `Sec-WebSocket-Protocol` header. Examples: STOMP, WAMP, MQTT. Use for standardized communication formats or custom application protocols.

### 12. Explain WebSocket ping/pong frames.
Ping frames (opcode 0x9) check connection liveness. Recipient must respond with pong frame (opcode 0xA). This heartbeat mechanism detects broken connections and prevents timeout by intermediate proxies.

### 13. How do you secure WebSocket connections?
- Use WSS (WebSocket Secure) with TLS/SSL
- Implement authentication during handshake
- Validate Origin headers
- Use session tokens
- Implement rate limiting
- Validate and sanitize all messages
- Apply CORS policies
- Monitor for anomalous behavior

### 14. How do you implement WebSocket message queuing?
```javascript
class ReliableWebSocket {
  constructor(url) {
    this.messageQueue = [];
    this.connected = false;
    this.connect();
  }

  send(message) {
    if (this.connected && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(message));
    } else {
      this.messageQueue.push(message);
    }
  }

  flushQueue() {
    while (this.messageQueue.length > 0 && this.connected) {
      this.ws.send(JSON.stringify(this.messageQueue.shift()));
    }
  }
}
```

### 15. What are common causes of WebSocket connection drops?
- Network interruptions
- Idle timeout from proxies/load balancers
- Server restart or crash
- Client page navigation/refresh
- Firewall interference
- Resource exhaustion
- Explicit close by either party

### 16. What is the WebSocket masking requirement?
All client-to-server frames must be masked with a random 32-bit key to prevent cache poisoning attacks. Server-to-client frames must not be masked. Masking prevents intermediaries from misinterpreting WebSocket traffic.

### 17. How do you implement request-response pattern over WebSocket?
```javascript
class RpcWebSocket {
  constructor(url) {
    this.ws = new WebSocket(url);
    this.pendingRequests = new Map();
    this.requestId = 0;
    
    this.ws.onmessage = (event) => {
      const response = JSON.parse(event.data);
      const pending = this.pendingRequests.get(response.id);
      if (pending) {
        clearTimeout(pending.timeout);
        pending.resolve(response.result);
        this.pendingRequests.delete(response.id);
      }
    };
  }

  request(method, params, timeout = 5000) {
    return new Promise((resolve, reject) => {
      const id = ++this.requestId;
      const timer = setTimeout(() => {
        this.pendingRequests.delete(id);
        reject(new Error('Request timeout'));
      }, timeout);
      
      this.pendingRequests.set(id, { resolve, reject, timeout: timer });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }
}
```

### 18. What are WebSocket opcodes?
- `0x0`: Continuation frame
- `0x1`: Text frame
- `0x2`: Binary frame
- `0x8`: Connection close
- `0x9`: Ping
- `0xA`: Pong
- `0x3-0x7`: Reserved for data frames
- `0xB-0xF`: Reserved for control frames

### 19. How do you implement broadcast messaging in WebSocket?
```javascript
const rooms = new Map();

wss.on('connection', (ws) => {
  ws.on('message', (message) => {
    const data = JSON.parse(message);
    
    if (data.type === 'join') {
      ws.room = data.room;
      if (!rooms.has(data.room)) {
        rooms.set(data.room, new Set());
      }
      rooms.get(data.room).add(ws);
    }
    
    if (data.type === 'broadcast') {
      const roomClients = rooms.get(ws.room);
      roomClients?.forEach(client => {
        if (client !== ws && client.readyState === WebSocket.OPEN) {
          client.send(JSON.stringify(data.message));
        }
      });
    }
  });
});
```

### 20. What is the difference between close codes 1000 and 1001?
- `1000`: Normal closure, purpose fulfilled
- `1001`: Going away, endpoint disappearing (server shutdown, browser navigation)

### 21. What is the FIN bit in WebSocket frames?
The FIN (final) bit indicates whether this is the final fragment of a message. FIN=1 means final fragment or complete message, FIN=0 means more fragments follow. Allows sending large messages in chunks.

### 22. What is the difference between ws.close() and ws.terminate()?
- `ws.close()`: Graceful close, sends close frame, waits for acknowledgment
- `ws.terminate()`: Forceful close, immediately destroys socket without handshake
- Use close() for normal shutdown, terminate() for unresponsive connections

### 23. How do you handle WebSocket backpressure?
```javascript
wss.on('connection', (ws) => {
  ws.on('message', (message) => {
    if (ws.bufferedAmount > 1024 * 1024) {
      console.warn('Buffer full, dropping message');
      return;
    }
    
    if (ws.bufferedAmount > 512 * 1024) {
      ws.pause();
      const checkBuffer = setInterval(() => {
        if (ws.bufferedAmount < 256 * 1024) {
          ws.resume();
          clearInterval(checkBuffer);
        }
      }, 100);
    }
    
    ws.send(message);
  });
});
```

### 24. What is the WebSocket compression extension (permessage-deflate)?
It compresses WebSocket messages using DEFLATE algorithm, reducing bandwidth. Negotiated during handshake via `Sec-WebSocket-Extensions` header. Useful for text-heavy applications but adds CPU overhead.

### 25. What are the security implications of WebSocket in web applications?
- CSWSH (Cross-Site WebSocket Hijacking)
- Message injection attacks
- DoS via resource exhaustion
- Man-in-the-middle without WSS
- XSS can compromise WebSocket
- Lack of CORS-like protections
- Information disclosure

### 26. How do you implement WebSocket connection pooling?
```javascript
class WebSocketPool {
  constructor(url, poolSize = 5) {
    this.url = url;
    this.connections = [];
    this.currentIndex = 0;
    
    for (let i = 0; i < poolSize; i++) {
      this.connections.push(this.createConnection());
    }
  }

  createConnection() {
    const ws = new WebSocket(this.url);
    ws.ready = false;
    
    ws.onopen = () => {
      ws.ready = true;
    };
    
    ws.onclose = () => {
      setTimeout(() => {
        const index = this.connections.indexOf(ws);
        this.connections[index] = this.createConnection();
      }, 1000);
    };
    
    return ws;
  }

  send(message) {
    const ws = this.connections[this.currentIndex];
    this.currentIndex = (this.currentIndex + 1) % this.connections.length;
    
    if (ws.ready) {
      ws.send(message);
    }
  }
}
```

### 27. How do you implement WebSocket middleware pattern?
```javascript
class WebSocketMiddleware {
  constructor() {
    this.middlewares = [];
  }

  use(fn) {
    this.middlewares.push(fn);
    return this;
  }

  async handle(ws, message) {
    const context = { ws, message, data: {} };
    let index = 0;
    
    const next = async () => {
      if (index < this.middlewares.length) {
        await this.middlewares[index++](context, next);
      }
    };
    
    await next();
  }
}

const wsm = new WebSocketMiddleware();
wsm.use(async (ctx, next) => {
  console.log('Logging:', ctx.message);
  await next();
});
```

### 28. What are the performance considerations for WebSocket?
- Memory per connection (~10-50KB)
- CPU for encryption (WSS)
- File descriptor limits
- Bandwidth consumption
- Message serialization overhead
- Event loop blocking
- Connection scaling limits

### 29. How do you implement WebSocket message fragmentation?
```javascript
function sendFragmented(ws, message, chunkSize = 1024) {
  const buffer = Buffer.from(message);
  const chunks = Math.ceil(buffer.length / chunkSize);
  
  for (let i = 0; i < chunks; i++) {
    const start = i * chunkSize;
    const end = Math.min((i + 1) * chunkSize, buffer.length);
    const chunk = buffer.slice(start, end);
    const isFinal = i === chunks - 1;
    
    ws.send(chunk, { fin: isFinal });
  }
}
```

### 30. What is the WebSocket GUID and its purpose?
The GUID (258EAFA5-E914-47DA-95CA-C5AB0DC85B11) is a magic string concatenated with Sec-WebSocket-Key, then SHA-1 hashed and base64 encoded. It proves server understanding of WebSocket protocol and prevents confusion with HTTP responses.

### 31. How do you implement WebSocket load balancing?
```javascript
// Using Redis for pub/sub across instances
const Redis = require('ioredis');
const pub = new Redis();
const sub = new Redis();

const serverId = process.env.SERVER_ID;
const connections = new Map();

sub.subscribe('websocket:broadcast');

sub.on('message', (channel, message) => {
  const data = JSON.parse(message);
  if (data.serverId === serverId) return;
  
  const ws = connections.get(data.userId);
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify(data.payload));
  }
});

wss.on('connection', (ws, req) => {
  const userId = authenticateUser(req);
  connections.set(userId, ws);
  
  ws.on('message', (message) => {
    pub.publish('websocket:broadcast', JSON.stringify({
      serverId,
      userId,
      payload: JSON.parse(message)
    }));
  });
});
```

### 32. What are WebSocket extensions and how do they work?
Extensions modify the WebSocket protocol (compression, multiplexing, etc.). Negotiated via Sec-WebSocket-Extensions header during handshake. Common: permessage-deflate for compression. Extensions must be understood by both parties.

### 33. How do you implement priority queuing in WebSocket?
```javascript
class PriorityWebSocket {
  constructor(url) {
    this.ws = new WebSocket(url);
    this.queues = {
      high: [],
      medium: [],
      low: []
    };
    this.processing = false;
  }

  send(message, priority = 'medium') {
    this.queues[priority].push(message);
    this.processQueue();
  }

  processQueue() {
    if (this.processing || this.ws.readyState !== WebSocket.OPEN) return;
    
    this.processing = true;
    
    const getNext = () => {
      if (this.queues.high.length) return this.queues.high.shift();
      if (this.queues.medium.length) return this.queues.medium.shift();
      if (this.queues.low.length) return this.queues.low.shift();
      return null;
    };
    
    const processNext = () => {
      const message = getNext();
      if (!message) {
        this.processing = false;
        return;
      }
      this.ws.send(JSON.stringify(message));
      setImmediate(processNext);
    };
    
    processNext();
  }
}
```

### 34. What is the difference between graceful degradation and progressive enhancement for WebSocket?
- Graceful degradation: Start with WebSocket, fall back to polling
- Progressive enhancement: Start with polling, upgrade to WebSocket if available
- Socket.IO uses progressive enhancement approach

### 35. How do you monitor WebSocket connection health?
```javascript
class MonitoredWebSocket {
  constructor(url) {
    this.metrics = {
      messagesSent: 0,
      messagesReceived: 0,
      bytesSent: 0,
      bytesReceived: 0,
      errors: 0,
      reconnections: 0,
      averageLatency: 0
    };
    this.connect();
  }

  connect() {
    this.ws = new WebSocket(this.url);
    
    this.ws.onmessage = (event) => {
      this.metrics.messagesReceived++;
      this.metrics.bytesReceived += event.data.length;
    };
    
    this.ws.onerror = () => {
      this.metrics.errors++;
    };
  }

  send(message) {
    const data = JSON.stringify(message);
    this.ws.send(data);
    this.metrics.messagesSent++;
    this.metrics.bytesSent += data.length;
  }
}
```

### 36. What are the limitations of WebSocket in corporate networks?
- Proxy/firewall blocking
- Deep packet inspection interference
- Connection timeout policies
- WSS certificate issues
- Port restrictions
- Traffic shaping

### 37. How do you implement binary protocol over WebSocket?
```javascript
const MessageType = {
  PING: 0x01,
  PONG: 0x02,
  DATA: 0x03
};

class BinaryProtocol {
  static encode(type, payload) {
    const payloadBuffer = Buffer.from(JSON.stringify(payload));
    const buffer = Buffer.allocUnsafe(5 + payloadBuffer.length);
    
    buffer.writeUInt8(type, 0);
    buffer.writeUInt32BE(payloadBuffer.length, 1);
    payloadBuffer.copy(buffer, 5);
    
    return buffer;
  }

  static decode(buffer) {
    const type = buffer.readUInt8(0);
    const length = buffer.readUInt32BE(1);
    const payload = JSON.parse(buffer.slice(5, 5 + length).toString());
    return { type, payload };
  }
}
```

### 38. What is connection draining in WebSocket servers?
Gracefully closing connections before server shutdown:
1. Stop accepting new connections
2. Send close frames to existing connections
3. Wait for close acknowledgments
4. Force close after timeout
5. Shutdown server

### 39. How do you implement WebSocket session management?
```javascript
const sessions = new Map();

class WebSocketSession {
  constructor(ws, userId) {
    this.ws = ws;
    this.userId = userId;
    this.createdAt = Date.now();
    this.lastActivity = Date.now();
    this.data = {};
  }

  touch() {
    this.lastActivity = Date.now();
  }

  isExpired(timeout = 3600000) {
    return Date.now() - this.lastActivity > timeout;
  }
}

wss.on('connection', (ws, req) => {
  const userId = authenticateUser(req);
  const session = new WebSocketSession(ws, userId);
  sessions.set(userId, session);
  
  ws.on('message', () => {
    session.touch();
  });
  
  ws.on('close', () => {
    sessions.delete(userId);
  });
});
```

### 40. What are testing strategies for WebSocket applications?
- Unit tests: Mock WebSocket objects
- Integration tests: Test with real WebSocket server
- Load tests: Simulate many concurrent connections
- Chaos testing: Random disconnections/network issues
- Security tests: Authentication, authorization, injection

### 41. How do you implement WebSocket with TypeScript?
```typescript
interface WebSocketMessage {
  type: string;
  payload: any;
}

class TypedWebSocket {
  private ws: WebSocket;
  private listeners: Map<string, Array<(data: any) => void>>;

  constructor(url: string) {
    this.ws = new WebSocket(url);
    this.listeners = new Map();

    this.ws.onmessage = (event: MessageEvent) => {
      const message: WebSocketMessage = JSON.parse(event.data);
      this.emit(message.type, message.payload);
    };
  }

  on(event: string, callback: (data: any) => void): void {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, []);
    }
    this.listeners.get(event)!.push(callback);
  }

  private emit(event: string, data: any): void {
    this.listeners.get(event)?.forEach(callback => callback(data));
  }

  send(type: string, payload: any): void {
    if (this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ type, payload }));
    }
  }
}
```

### 42. How do you handle WebSocket timeouts on both client and server?
```javascript
// Client
class TimeoutWebSocket {
  constructor(url, timeout = 30000) {
    this.timeout = timeout;
    this.connect();
  }

  resetTimeout() {
    clearTimeout(this.timeoutTimer);
    this.timeoutTimer = setTimeout(() => {
      console.log('Connection timeout');
      this.ws.close();
    }, this.timeout);
  }
}

// Server
wss.on('connection', (ws) => {
  ws.isAlive = true;
  ws.on('pong', () => {
    ws.isAlive = true;
  });
});

const interval = setInterval(() => {
  wss.clients.forEach((ws) => {
    if (!ws.isAlive) return ws.terminate();
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);
```

### 43. How do you implement message acknowledgment in WebSocket?
```javascript
class AckWebSocket {
  constructor(url) {
    this.ws = new WebSocket(url);
    this.pendingMessages = new Map();
    this.messageId = 0;
    
    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.type === 'ack') {
        const pending = this.pendingMessages.get(data.id);
        if (pending) {
          clearTimeout(pending.timeout);
          pending.resolve();
          this.pendingMessages.delete(data.id);
        }
      }
    };
  }

  sendWithAck(message, timeout = 5000) {
    return new Promise((resolve, reject) => {
      const id = ++this.messageId;
      const timeoutHandle = setTimeout(() => {
        this.pendingMessages.delete(id);
        reject(new Error('Acknowledgment timeout'));
      }, timeout);
      
      this.pendingMessages.set(id, { resolve, timeout: timeoutHandle });
      this.ws.send(JSON.stringify({ id, ...message }));
    });
  }
}
```

### 44. What is the role of Origin header in WebSocket security?
The Origin header indicates the script's origin initiating the WebSocket. Servers should validate it to prevent cross-site WebSocket hijacking (CSWSH) attacks, rejecting connections from unauthorized origins.

### 45. How do you implement WebSocket rate limiting?
```javascript
const rateLimit = new Map();

wss.on('connection', (ws, req) => {
  const clientIp = req.socket.remoteAddress;
  
  ws.on('message', (message) => {
    const now = Date.now();
    const limit = rateLimit.get(clientIp) || { count: 0, resetTime: now + 60000 };
    
    if (now > limit.resetTime) {
      limit.count = 0;
      limit.resetTime = now + 60000;
    }
    
    limit.count++;
    rateLimit.set(clientIp, limit);
    
    if (limit.count > 100) {
      ws.send(JSON.stringify({ error: 'Rate limit exceeded' }));
      return;
    }
  });
});
```

### 46. How do you implement WebSocket error handling gracefully?
```javascript
class RobustWebSocket {
  constructor(url) {
    this.url = url;
    this.connect();
  }

  connect() {
    try {
      this.ws = new WebSocket(this.url);
      
      this.ws.onerror = (error) => {
        console.error('WebSocket error:', error);
        this.handleError(error);
      };
      
      this.ws.onclose = (event) => {
        if (!event.wasClean) {
          console.error('Connection died:', event.code, event.reason);
          this.reconnect();
        }
      };
    } catch (error) {
      console.error('Failed to create WebSocket:', error);
      this.reconnect();
    }
  }

  send(data) {
    try {
      if (this.ws.readyState === WebSocket.OPEN) {
        this.ws.send(JSON.stringify(data));
      } else {
        throw new Error('WebSocket not open');
      }
    } catch (error) {
      console.error('Send failed:', error);
      this.handleError(error);
    }
  }
}
```

### 47. How do you implement WebSocket connection state tracking?
```javascript
const connections = new Map();

wss.on('connection', (ws, req) => {
  const userId = authenticateUser(req);
  
  connections.set(ws, {
    userId,
    connectedAt: Date.now(),
    lastActivity: Date.now(),
    messageCount: 0
  });
  
  ws.on('message', () => {
    const state = connections.get(ws);
    if (state) {
      state.lastActivity = Date.now();
      state.messageCount++;
    }
  });
  
  ws.on('close', () => {
    connections.delete(ws);
  });
});
```

### 48. What are WebSocket frame types and their purposes?
- Text frame: UTF-8 encoded text data
- Binary frame: Raw binary data
- Close frame: Connection termination with optional status code
- Ping frame: Connection health check
- Pong frame: Response to ping
- Continuation frame: Additional data for fragmented message

### 49. How do you implement WebSocket message compression per message?
```javascript
const zlib = require('zlib');

function sendCompressed(ws, data) {
  const json = JSON.stringify(data);
  
  if (json.length > 1024) {
    zlib.deflate(json, (err, compressed) => {
      if (!err) {
        ws.send(compressed);
      }
    });
  } else {
    ws.send(json);
  }
}

ws.on('message', (message) => {
  if (Buffer.isBuffer(message)) {
    zlib.inflate(message, (err, decompressed) => {
      if (!err) {
        const data = JSON.parse(decompressed.toString());
      }
    });
  } else {
    const data = JSON.parse(message);
  }
});
```

### 50. How do you implement WebSocket connection metrics?
```javascript
const metrics = {
  totalConnections: 0,
  activeConnections: 0,
  totalMessages: 0,
  totalBytes: 0,
  errors: 0
};

wss.on('connection', (ws) => {
  metrics.totalConnections++;
  metrics.activeConnections++;
  
  ws.on('message', (message) => {
    metrics.totalMessages++;
    metrics.totalBytes += message.length;
  });
  
  ws.on('error', () => {
    metrics.errors++;
  });
  
  ws.on('close', () => {
    metrics.activeConnections--;
  });
});

app.get('/metrics', (req, res) => {
  res.json(metrics);
});
```

### 51. What is Socket.IO and how does it differ from raw WebSocket?
Socket.IO is a library providing real-time bidirectional communication with:
- Automatic transport fallback (WebSocket → polling)
- Automatic reconnection
- Room and namespace support
- Acknowledgment callbacks
- Broadcasting helpers
- Binary support

### 52. How do you set up a production Socket.IO server?
```javascript
const express = require('express');
const { createServer } = require('http');
const { Server } = require('socket.io');
const { createAdapter } = require('@socket.io/redis-adapter');
const Redis = require('ioredis');

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

const pubClient = new Redis();
const subClient = pubClient.duplicate();
io.adapter(createAdapter(pubClient, subClient));

httpServer.listen(3000);
```

### 53. How do you implement Socket.IO namespaces?
```javascript
const adminNamespace = io.of('/admin');

adminNamespace.use(async (socket, next) => {
  if (socket.user.role === 'admin') {
    next();
  } else {
    next(new Error('Unauthorized'));
  }
});

adminNamespace.on('connection', (socket) => {
  socket.on('broadcast:announcement', (data) => {
    io.emit('announcement', data);
  });
});

const chatNamespace = io.of('/chat');
chatNamespace.on('connection', (socket) => {
  socket.on('message', (data) => {
    chatNamespace.to(`room:${data.roomId}`).emit('message', data);
  });
});
```

### 54. What are Socket.IO rooms and how do you use them?
```javascript
io.on('connection', (socket) => {
  socket.on('join:game', async (gameId) => {
    await socket.join(`game:${gameId}`);
    
    socket.to(`game:${gameId}`).emit('player:joined', {
      userId: socket.user.id
    });
    
    const sockets = await io.in(`game:${gameId}`).fetchSockets();
    socket.emit('room:info', {
      players: sockets.length
    });
  });
  
  socket.on('game:move', (data) => {
    socket.to(`game:${data.gameId}`).emit('game:move', data);
  });
  
  socket.on('leave:game', (gameId) => {
    socket.leave(`game:${gameId}`);
  });
});
```

### 55. How do you implement acknowledgments in Socket.IO?
```javascript
// Server
socket.on('create:order', async (orderData, callback) => {
  try {
    const order = await createOrder(orderData);
    callback({ success: true, order });
  } catch (error) {
    callback({ success: false, error: error.message });
  }
});

// Client
socket.emit('create:order', orderData, (response) => {
  if (response.success) {
    console.log('Order created:', response.order);
  }
});

// With timeout
socket.timeout(5000).emit('create:order', orderData, (err, response) => {
  if (err) {
    console.error('Timeout or error');
  }
});
```

### 56. How do you handle Socket.IO disconnection and reconnection?
```javascript
// Server
io.on('connection', (socket) => {
  socket.on('disconnect', (reason) => {
    console.log('Disconnected:', reason);
    // 'transport close', 'client namespace disconnect', etc.
  });
  
  socket.on('disconnecting', () => {
    for (const room of socket.rooms) {
      socket.to(room).emit('user:leaving', socket.user.id);
    }
  });
});

// Client
const socket = io('http://localhost:3000', {
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionDelayMax: 5000,
  reconnectionAttempts: 5
});

socket.on('reconnect', (attemptNumber) => {
  console.log('Reconnected after', attemptNumber, 'attempts');
});
```

### 57. How do you implement Socket.IO middleware?
```javascript
io.use(async (socket, next) => {
  try {
    const token = socket.handshake.auth.token;
    const user = await verifyJWT(token);
    socket.user = user;
    next();
  } catch (error) {
    next(new Error('Authentication failed'));
  }
});

const rateLimit = new Map();
io.use((socket, next) => {
  const userId = socket.user.id;
  const now = Date.now();
  const limit = rateLimit.get(userId) || { count: 0, resetTime: now + 60000 };
  
  if (now > limit.resetTime) {
    limit.count = 0;
    limit.resetTime = now + 60000;
  }
  
  limit.count++;
  rateLimit.set(userId, limit);
  
  if (limit.count > 100) {
    next(new Error('Rate limit exceeded'));
  } else {
    next();
  }
});
```

### 58. How do you broadcast messages in Socket.IO?
```javascript
io.on('connection', (socket) => {
  // To all clients
  io.emit('announcement', 'Hello everyone');
  
  // To all except sender
  socket.broadcast.emit('user:joined', socket.user.username);
  
  // To specific room
  io.to('room:123').emit('message', data);
  
  // To multiple rooms
  io.to('room:123').to('room:456').emit('message', data);
  
  // To room except sender
  socket.to('room:123').emit('message', data);
  
  // Volatile (lossy, won't queue if disconnected)
  socket.volatile.emit('stock:price', price);
  
  // With compression
  socket.compress(true).emit('large:data', data);
});
```

### 59. How do you implement Socket.IO with Redis adapter for scaling?
```javascript
const { createAdapter } = require('@socket.io/redis-adapter');
const { createClient } = require('redis');

const pubClient = createClient({ url: 'redis://localhost:6379' });
const subClient = pubClient.duplicate();

await Promise.all([pubClient.connect(), subClient.connect()]);
io.adapter(createAdapter(pubClient, subClient));

// Get all socket IDs in room across servers
io.of('/').adapter.sockets(new Set(['room:123'])).then((sockets) => {
  console.log('Sockets in room:', sockets);
});

// Custom Redis operations
io.on('connection', (socket) => {
  socket.on('message', async (data) => {
    await pubClient.hSet(`messages:${data.roomId}`, {
      [Date.now()]: JSON.stringify(data)
    });
  });
});
```

### 60. How do you implement Socket.IO authentication?
```javascript
const jwt = require('jsonwebtoken');

io.use(async (socket, next) => {
  try {
    const token = socket.handshake.auth.token;
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const user = await User.findById(decoded.userId);
    
    if (!user) {
      return next(new Error('User not found'));
    }
    
    socket.user = user;
    next();
  } catch (error) {
    next(new Error('Authentication failed'));
  }
});

const adminNs = io.of('/admin');
adminNs.use((socket, next) => {
  if (socket.user.role !== 'admin') {
    return next(new Error('Unauthorized'));
  }
  next();
});
```

### 61. How do you handle Socket.IO error handling?
```javascript
io.on('connection', (socket) => {
  socket.on('error', (error) => {
    console.error('Socket error:', error);
  });
});

io.engine.on('connection_error', (err) => {
  console.error('Connection error:', err.code, err.message);
});

socket.on('risky:operation', async (data, callback) => {
  try {
    const result = await performOperation(data);
    callback({ success: true, result });
  } catch (error) {
    console.error('Operation failed:', error);
    callback({ success: false, error: error.message });
  }
});
```

### 62. How do you implement Socket.IO with clustering?
```javascript
const cluster = require('cluster');
const { setupMaster, setupWorker } = require('@socket.io/sticky');
const { createAdapter, setupPrimary } = require('@socket.io/cluster-adapter');

if (cluster.isMaster) {
  const httpServer = require('http').createServer();
  setupMaster(httpServer, { loadBalancingMethod: 'least-connection' });
  httpServer.listen(3000);
  setupPrimary();
  
  for (let i = 0; i < require('os').cpus().length; i++) {
    cluster.fork();
  }
} else {
  const io = new Server(httpServer);
  io.adapter(createAdapter());
  setupWorker(io);
}
```

### 63. How do you implement Socket.IO message compression?
```javascript
const io = new Server(httpServer, {
  perMessageDeflate: {
    threshold: 1024,
    zlibDeflateOptions: {
      chunkSize: 1024,
      memLevel: 7,
      level: 3
    }
  }
});

io.on('connection', (socket) => {
  socket.compress(true).emit('large:data', largeData);
  socket.compress(false).emit('small:data', smallData);
});
```

### 64. How do you implement Socket.IO presence tracking?
```javascript
const onlineUsers = new Map();

io.on('connection', (socket) => {
  onlineUsers.set(socket.user.id, {
    socketId: socket.id,
    username: socket.user.username,
    status: 'online',
    connectedAt: Date.now()
  });
  
  socket.broadcast.emit('user:online', {
    userId: socket.user.id,
    username: socket.user.username
  });
  
  socket.emit('users:online', Array.from(onlineUsers.values()));
  
  socket.on('status:change', (status) => {
    const user = onlineUsers.get(socket.user.id);
    if (user) {
      user.status = status;
      io.emit('user:status', { userId: socket.user.id, status });
    }
  });
  
  socket.on('disconnect', () => {
    onlineUsers.delete(socket.user.id);
    io.emit('user:offline', { userId: socket.user.id });
  });
});
```

### 65. How do you implement Socket.IO typing indicators?
```javascript
const typingUsers = new Map();

io.on('connection', (socket) => {
  socket.on('typing:start', (roomId) => {
    if (!typingUsers.has(roomId)) {
      typingUsers.set(roomId, new Set());
    }
    typingUsers.get(roomId).add(socket.user.id);
    
    socket.to(`room:${roomId}`).emit('user:typing', {
      userId: socket.user.id
    });
    
    socket.typingTimeout = setTimeout(() => {
      typingUsers.get(roomId)?.delete(socket.user.id);
      socket.to(`room:${roomId}`).emit('user:stopped-typing', {
        userId: socket.user.id
      });
    }, 5000);
  });
  
  socket.on('typing:stop', (roomId) => {
    clearTimeout(socket.typingTimeout);
    typingUsers.get(roomId)?.delete(socket.user.id);
    socket.to(`room:${roomId}`).emit('user:stopped-typing', {
      userId: socket.user.id
    });
  });
});
```

### 66. How do you implement Socket.IO binary data transfer?
```javascript
const fs = require('fs');
const path = require('path');

io.on('connection', (socket) => {
  socket.on('file:upload', (file, metadata, callback) => {
    const filename = `${Date.now()}-${metadata.name}`;
    const filepath = path.join(__dirname, 'uploads', filename);
    
    fs.writeFile(filepath, file, (err) => {
      if (err) {
        callback({ success: false, error: err.message });
      } else {
        callback({ success: true, filename });
      }
    });
  });
  
  socket.on('file:stream', (filename) => {
    const filepath = path.join(__dirname, 'files', filename);
    const stream = fs.createReadStream(filepath, {
      highWaterMark: 64 * 1024
    });
    
    stream.on('data', (chunk) => {
      socket.emit('file:chunk', chunk);
    });
    
    stream.on('end', () => {
      socket.emit('file:complete');
    });
  });
});
```

### 67. How do you implement Socket.IO with Express sessions?
```javascript
const session = require('express-session');
const RedisStore = require('connect-redis').default;

const sessionMiddleware = session({
  store: new RedisStore({ client: redisClient }),
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false
});

app.use(sessionMiddleware);

const wrap = middleware => (socket, next) => 
  middleware(socket.request, {}, next);

io.use(wrap(sessionMiddleware));

io.use((socket, next) => {
  const session = socket.request.session;
  if (session && session.userId) {
    socket.userId = session.userId;
    next();
  } else {
    next(new Error('Unauthorized'));
  }
});
```

### 68. How do you implement Socket.IO private messaging?
```javascript
const userSockets = new Map();

io.on('connection', (socket) => {
  userSockets.set(socket.user.id, socket.id);
  
  socket.on('private:message', async (data) => {
    const { recipientId, message } = data;
    
    const savedMessage = await Message.create({
      senderId: socket.user.id,
      recipientId,
      message,
      timestamp: Date.now()
    });
    
    const recipientSocketId = userSockets.get(recipientId);
    if (recipientSocketId) {
      io.to(recipientSocketId).emit('private:message', {
        messageId: savedMessage.id,
        senderId: socket.user.id,
        message
      });
    }
  });
  
  socket.on('disconnect', () => {
    userSockets.delete(socket.user.id);
  });
});
```

### 69. How do you implement Socket.IO rate limiting?
```javascript
class RateLimiter {
  constructor(maxRequests, windowMs) {
    this.maxRequests = maxRequests;
    this.windowMs = windowMs;
    this.requests = new Map();
  }
  
  check(key) {
    const now = Date.now();
    const record = this.requests.get(key) || {
      count: 0,
      resetTime: now + this.windowMs
    };
    
    if (now > record.resetTime) {
      record.count = 0;
      record.resetTime = now + this.windowMs;
    }
    
    record.count++;
    this.requests.set(key, record);
    return record.count <= this.maxRequests;
  }
}

const messageLimiter = new RateLimiter(20, 60000);

io.on('connection', (socket) => {
  socket.on('message', (data) => {
    if (!messageLimiter.check(socket.user.id)) {
      socket.emit('error', { message: 'Rate limit exceeded' });
      return;
    }
  });
});
```

### 70. How do you implement Socket.IO with TypeScript?
```typescript
import { Server, Socket } from 'socket.io';

interface ServerToClientEvents {
  message: (data: MessageData) => void;
}

interface ClientToServerEvents {
  'message:send': (message: string, callback: (response: Response) => void) => void;
}

interface SocketData {
  user: User;
}

const io = new Server<ClientToServerEvents, ServerToClientEvents, {}, SocketData>(httpServer);

io.on('connection', (socket) => {
  socket.on('message:send', (message, callback) => {
    io.emit('message', {
      id: generateId(),
      userId: socket.data.user.id,
      message
    });
    callback({ success: true });
  });
});
```

### 71. How do you implement Socket.IO health checks?
```javascript
const prometheus = require('prom-client');

const connectedClients = new prometheus.Gauge({
  name: 'socketio_connected_clients',
  help: 'Number of connected clients'
});

io.on('connection', (socket) => {
  connectedClients.inc();
  socket.on('disconnect', () => {
    connectedClients.dec();
  });
});

app.get('/metrics', async (req, res) => {
  res.set('Content-Type', prometheus.register.contentType);
  res.end(await prometheus.register.metrics());
});

app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    connections: io.engine.clientsCount
  });
});
```

### 72. How do you implement Socket.IO room management?
```javascript
io.on('connection', (socket) => {
  socket.on('create:room', async (roomData, callback) => {
    const roomId = generateRoomId();
    await socket.join(roomId);
    
    await Room.create({
      id: roomId,
      name: roomData.name,
      ownerId: socket.user.id,
      createdAt: Date.now()
    });
    
    callback({ success: true, roomId });
  });
  
  socket.on('list:rooms', async (callback) => {
    const rooms = Array.from(socket.rooms);
    callback({ rooms });
  });
});
```

### 73. How do you implement Socket.IO graceful shutdown?
```javascript
const gracefulShutdown = () => {
  console.log('Shutting down gracefully...');
  
  io.close(() => {
    console.log('All connections closed');
    process.exit(0);
  });
  
  setTimeout(() => {
    console.error('Forcing shutdown');
    process.exit(1);
  }, 10000);
};

process.on('SIGTERM', gracefulShutdown);
process.on('SIGINT', gracefulShutdown);
```

### 74. How do you implement Socket.IO event multiplexing?
```javascript
io.on('connection', (socket) => {
  socket.onAny((event, ...args) => {
    console.log(`Event: ${event}`, args);
    
    analytics.track({
      event,
      userId: socket.user.id,
      timestamp: Date.now()
    });
  });
  
  socket.onAnyOutgoing((event, ...args) => {
    console.log(`Outgoing: ${event}`, args);
  });
});
```

### 75. How do you implement Socket.IO connection recovery?
```javascript
const io = new Server(httpServer, {
  connectionStateRecovery: {
    maxDisconnectionDuration: 2 * 60 * 1000,
    skipMiddlewares: true
  }
});

io.on('connection', (socket) => {
  if (socket.recovered) {
    console.log('Connection recovered for', socket.id);
  } else {
    console.log('New connection', socket.id);
  }
});
```

### 76. How do you implement Socket.IO message batching?
```javascript
class MessageBatcher {
  constructor(socket, interval = 100) {
    this.socket = socket;
    this.interval = interval;
    this.batch = [];
    this.timer = null;
  }
  
  add(event, data) {
    this.batch.push({ event, data });
    
    if (!this.timer) {
      this.timer = setTimeout(() => {
        this.flush();
      }, this.interval);
    }
  }
  
  flush() {
    if (this.batch.length > 0) {
      this.socket.emit('batch', this.batch);
      this.batch = [];
    }
    this.timer = null;
  }
}
```

### 77. How do you implement Socket.IO with geolocation-based rooms?
```javascript
io.on('connection', (socket) => {
  socket.on('update:location', async (location) => {
    const { latitude, longitude } = location;
    const geohash = calculateGeohash(latitude, longitude, 5);
    
    socket.rooms.forEach(room => {
      if (room.startsWith('geo:')) {
        socket.leave(room);
      }
    });
    
    await socket.join(`geo:${geohash}`);
    
    socket.to(`geo:${geohash}`).emit('user:nearby', {
      userId: socket.user.id,
      location
    });
  });
});
```

### 78. How do you implement Socket.IO with message history?
```javascript
const messageHistory = new Map();

io.on('connection', (socket) => {
  socket.on('join:room', async (roomId) => {
    await socket.join(roomId);
    
    const history = messageHistory.get(roomId) || [];
    socket.emit('room:history', history);
  });
  
  socket.on('message:send', (data) => {
    const messageData = {
      id: generateId(),
      userId: socket.user.id,
      message: data.message,
      timestamp: Date.now()
    };
    
    if (!messageHistory.has(data.roomId)) {
      messageHistory.set(data.roomId, []);
    }
    const history = messageHistory.get(data.roomId);
    history.push(messageData);
    
    if (history.length > 100) history.shift();
    
    io.to(data.roomId).emit('message', messageData);
  });
});
```

### 79. How do you implement Socket.IO with message filtering?
```javascript
io.on('connection', (socket) => {
  socket.on('subscribe:filter', (filters) => {
    socket.filters = filters;
  });
  
  socket.on('broadcast', (data) => {
    io.sockets.sockets.forEach((clientSocket) => {
      if (matchesFilters(data, clientSocket.filters)) {
        clientSocket.emit('filtered:message', data);
      }
    });
  });
});

function matchesFilters(data, filters) {
  if (!filters) return true;
  return Object.keys(filters).every(key => data[key] === filters[key]);
}
```

### 80. How do you implement Socket.IO with distributed locks?
```javascript
const Redlock = require('redlock');
const redlock = new Redlock([redisClient]);

io.on('connection', (socket) => {
  socket.on('acquire:resource', async (resourceId, callback) => {
    try {
      const lock = await redlock.acquire([`lock:${resourceId}`], 5000);
      callback({ success: true, lockId: lock.value });
      
      socket.on('release:resource', async () => {
        await lock.release();
      });
    } catch (error) {
      callback({ success: false, error: 'Resource locked' });
    }
  });
});
```

### 81. How do you implement Socket.IO with event sourcing?
```javascript
const events = [];

io.on('connection', (socket) => {
  socket.on('action', (action) => {
    const event = {
      id: generateId(),
      type: action.type,
      payload: action.payload,
      userId: socket.user.id,
      timestamp: Date.now()
    };
    
    events.push(event);
    const newState = applyEvent(getCurrentState(), event);
    io.emit('state:update', newState);
  });
  
  socket.on('replay:events', (fromTimestamp, callback) => {
    const replayEvents = events.filter(e => e.timestamp >= fromTimestamp);
    callback(replayEvents);
  });
});
```

### 82. How do you implement Socket.IO with circuit breaker?
```javascript
class CircuitBreaker {
  constructor(threshold = 5, timeout = 60000) {
    this.threshold = threshold;
    this.timeout = timeout;
    this.failures = 0;
    this.state = 'CLOSED';
  }
  
  async execute(fn) {
    if (this.state === 'OPEN') {
      throw new Error('Circuit breaker is OPEN');
    }
    
    try {
      const result = await fn();
      this.onSuccess();
      return result;
    } catch (error) {
      this.onFailure();
      throw error;
    }
  }
  
  onSuccess() {
    this.failures = 0;
    this.state = 'CLOSED';
  }
  
  onFailure() {
    this.failures++;
    if (this.failures >= this.threshold) {
      this.state = 'OPEN';
    }
  }
}

const breaker = new CircuitBreaker();

io.on('connection', (socket) => {
  socket.on('external:call', async (data, callback) => {
    try {
      const result = await breaker.execute(() => externalService.call(data));
      callback({ success: true, result });
    } catch (error) {
      callback({ success: false, error: error.message });
    }
  });
});
```

### 83. How do you implement Socket.IO with multi-tenancy?
```javascript
io.on('connection', (socket) => {
  const tenantId = socket.user.tenantId;
  socket.join(`tenant:${tenantId}`);
  
  socket.on('message', async (data) => {
    io.to(`tenant:${tenantId}`).emit('message', {
      ...data,
      userId: socket.user.id
    });
    
    await Message.create({
      tenantId,
      ...data
    });
  });
  
  socket.on('get:data', async (query, callback) => {
    const data = await Data.find({
      tenantId,
      ...query
    });
    callback(data);
  });
});
```

### 84. How do you implement Socket.IO with request tracing?
```javascript
const { v4: uuidv4 } = require('uuid');

io.use((socket, next) => {
  socket.traceId = uuidv4();
  next();
});

io.on('connection', (socket) => {
  socket.onAny((event, ...args) => {
    const requestId = uuidv4();
    
    console.log({
      traceId: socket.traceId,
      requestId,
      event,
      userId: socket.user.id,
      timestamp: Date.now()
    });
  });
});
```

### 85. How do you implement Socket.IO with data validation?
```javascript
const Joi = require('joi');

const messageSchema = Joi.object({
  roomId: Joi.string().required(),
  message: Joi.string().max(1000).required(),
  metadata: Joi.object().optional()
});

io.on('connection', (socket) => {
  socket.on('message:send', async (data, callback) => {
    const { error, value } = messageSchema.validate(data);
    
    if (error) {
      callback({ success: false, error: error.details[0].message });
      return;
    }
    
    io.to(value.roomId).emit('message', {
      ...value,
      userId: socket.user.id
    });
    
    callback({ success: true });
  });
});
```

### 86. How do you implement Socket.IO with connection throttling?
```javascript
const throttle = new Map();

io.use((socket, next) => {
  const ip = socket.handshake.address;
  const now = Date.now();
  const record = throttle.get(ip);
  
  if (record && now - record.lastConnection < 1000) {
    next(new Error('Connection throttled'));
    return;
  }
  
  throttle.set(ip, { lastConnection: now });
  next();
});
```

### 87. How do you implement Socket.IO with message deduplication?
```javascript
const messageIds = new Set();

io.on('connection', (socket) => {
  socket.on('message', (data) => {
    const messageId = data.id || generateId();
    
    if (messageIds.has(messageId)) {
      console.log('Duplicate message ignored');
      return;
    }
    
    messageIds.add(messageId);
    setTimeout(() => messageIds.delete(messageId), 60000);
    
    io.emit('message', { ...data, id: messageId });
  });
});
```

### 88. How do you implement Socket.IO with backpressure handling?
```javascript
io.on('connection', (socket) => {
  let backpressure = false;
  
  socket.on('message', (data) => {
    if (backpressure) {
      socket.emit('error', { code: 'BACKPRESSURE' });
      return;
    }
    
    if (socket.conn.transport.writable === false) {
      backpressure = true;
      socket.conn.once('drain', () => {
        backpressure = false;
      });
      return;
    }
  });
});
```

### 89. How do you implement Socket.IO with dynamic room creation?
```javascript
io.on('connection', (socket) => {
  socket.on('create:room', async (roomData, callback) => {
    const roomId = generateRoomId();
    
    await Room.create({
      id: roomId,
      name: roomData.name,
      ownerId: socket.user.id,
      maxUsers: roomData.maxUsers || 10
    });
    
    await socket.join(roomId);
    callback({ success: true, roomId });
  });
  
  socket.on('join:room', async (roomId, callback) => {
    const room = await Room.findById(roomId);
    
    if (!room) {
      callback({ success: false, error: 'Room not found' });
      return;
    }
    
    const sockets = await io.in(roomId).fetchSockets();
    
    if (sockets.length >= room.maxUsers) {
      callback({ success: false, error: 'Room full' });
      return;
    }
    
    await socket.join(roomId);
    callback({ success: true });
  });
});
```

### 90. How do you implement Socket.IO with message persistence?
```javascript
const Queue = require('bull');
const messageQueue = new Queue('persist-messages');

messageQueue.process(async (job) => {
  await Message.create(job.data);
});

io.on('connection', (socket) => {
  socket.on('message', async (data) => {
    const messageData = {
      userId: socket.user.id,
      roomId: data.roomId,
      message: data.message,
      timestamp: Date.now()
    };
    
    await messageQueue.add(messageData);
    io.to(data.roomId).emit('message', messageData);
  });
});
```

### 91. How do you implement Socket.IO with connection state recovery?
```javascript
const connectionStates = new Map();

io.on('connection', (socket) => {
  const oldState = connectionStates.get(socket.user.id);
  
  if (oldState) {
    socket.data = oldState.data;
    oldState.rooms.forEach(room => socket.join(room));
    socket.emit('state:restored', oldState.data);
  }
  
  socket.on('disconnect', () => {
    connectionStates.set(socket.user.id, {
      data: socket.data,
      rooms: Array.from(socket.rooms)
    });
  });
});
```

### 92. How do you implement Socket.IO with smart retry?
```javascript
class SmartRetry {
  constructor(socket) {
    this.socket = socket;
    this.queue = [];
  }
  
  async send(event, data, options = {}) {
    const item = {
      event,
      data,
      attempts: 0,
      maxAttempts: options.maxAttempts || 3,
      backoff: options.backoff || 1000
    };
    
    this.queue.push(item);
    await this.processQueue();
  }
  
  async processQueue() {
    if (this.queue.length === 0) return;
    
    const item = this.queue[0];
    
    try {
      await this.sendWithAck(item.event, item.data);
      this.queue.shift();
    } catch (error) {
      item.attempts++;
      
      if (item.attempts >= item.maxAttempts) {
        this.queue.shift();
      } else {
        await new Promise(resolve => 
          setTimeout(resolve, item.backoff * item.attempts)
        );
      }
    }
    
    await this.processQueue();
  }
  
  sendWithAck(event, data) {
    return new Promise((resolve, reject) => {
      this.socket.timeout(5000).emit(event, data, (err, response) => {
        if (err) reject(err);
        else resolve(response);
      });
    });
  }
}
```

### 93. How do you implement Socket.IO with data synchronization?
```javascript
const documentStates = new Map();

io.on('connection', (socket) => {
  socket.on('sync:subscribe', (documentId) => {
    socket.join(`doc:${documentId}`);
    
    const state = documentStates.get(documentId) || { version: 0, data: {} };
    socket.emit('sync:state', state);
  });
  
  socket.on('sync:update', (data) => {
    const { documentId, changes, version } = data;
    const currentState = documentStates.get(documentId) || { version: 0, data: {} };
    
    if (version !== currentState.version) {
      socket.emit('sync:conflict', {
        expectedVersion: currentState.version
      });
      return;
    }
    
    const newState = {
      version: currentState.version + 1,
      data: applyChanges(currentState.data, changes)
    };
    
    documentStates.set(documentId, newState);
    socket.to(`doc:${documentId}`).emit('sync:update', {
      changes,
      version: newState.version
    });
  });
});
```

### 94. How do you implement Socket.IO with presence heartbeat?
```javascript
const presence = new Map();

io.on('connection', (socket) => {
  presence.set(socket.user.id, {
    socketId: socket.id,
    lastSeen: Date.now(),
    status: 'online'
  });
  
  socket.on('heartbeat', () => {
    const user = presence.get(socket.user.id);
    if (user) user.lastSeen = Date.now();
  });
  
  socket.on('disconnect', () => {
    const user = presence.get(socket.user.id);
    if (user) {
      user.status = 'offline';
      user.lastSeen = Date.now();
    }
  });
});

setInterval(() => {
  const now = Date.now();
  presence.forEach((user, userId) => {
    if (now - user.lastSeen > 60000) {
      user.status = 'offline';
    }
  });
}, 30000);
```

### 95. How do you implement Socket.IO with semantic versioning?
```javascript
io.use((socket, next) => {
  const clientVersion = socket.handshake.query.version;
  const serverVersion = process.env.API_VERSION;
  
  if (!isCompatible(clientVersion, serverVersion)) {
    next(new Error('Version mismatch'));
    return;
  }
  
  socket.apiVersion = clientVersion;
  next();
});

function isCompatible(client, server) {
  const [clientMajor] = client.split('.');
  const [serverMajor] = server.split('.');
  return clientMajor === serverMajor;
}
```

### 96. How do you implement Socket.IO with message ordering?
```javascript
const messageSequences = new Map();

io.on('connection', (socket) => {
  messageSequences.set(socket.id, 0);
  
  socket.on('message', (data) => {
    const sequence = messageSequences.get(socket.id) + 1;
    messageSequences.set(socket.id, sequence);
    
    io.to(data.roomId).emit('message', {
      ...data,
      sequence,
      userId: socket.user.id
    });
  });
  
  socket.on('disconnect', () => {
    messageSequences.delete(socket.id);
  });
});
```

### 97. How do you implement Socket.IO with connection draining?
```javascript
let draining = false;

process.on('SIGTERM', () => {
  draining = true;
  
  io.sockets.sockets.forEach((socket) => {
    socket.emit('server:shutdown', {
      message: 'Server is shutting down',
      reconnectAfter: 5000
    });
    
    setTimeout(() => {
      socket.disconnect(true);
    }, 10000);
  });
});

io.use((socket, next) => {
  if (draining) {
    next(new Error('Server is shutting down'));
  } else {
    next();
  }
});
```

### 98. How do you implement Socket.IO with distributed rate limiting?
```javascript
const Redis = require('ioredis');
const redis = new Redis();

async function checkRateLimit(userId) {
  const key = `ratelimit:${userId}`;
  const current = await redis.incr(key);
  
  if (current === 1) {
    await redis.expire(key, 60);
  }
  
  return current <= 100;
}

io.on('connection', (socket) => {
  socket.on('message', async (data, callback) => {
    const allowed = await checkRateLimit(socket.user.id);
    
    if (!allowed) {
      callback({ error: 'Rate limit exceeded' });
      return;
    }
    
    callback({ success: true });
  });
});
```

### 99. How do you implement Socket.IO with custom adapters?
```javascript
const { Adapter } = require('socket.io-adapter');

class CustomAdapter extends Adapter {
  constructor(nsp) {
    super(nsp);
    this.customData = new Map();
  }
  
  async addAll(id, rooms) {
    await super.addAll(id, rooms);
    // Custom logic
    console.log(`Socket ${id} joined rooms:`, rooms);
  }
  
  async broadcast(packet, opts) {
    await super.broadcast(packet, opts);
    // Custom logic
    this.logBroadcast(packet, opts);
  }
  
  logBroadcast(packet, opts) {
    console.log('Broadcasting:', packet.data, 'to rooms:', opts.rooms);
  }
}

io.adapter(CustomAdapter);
```

### 100. How do you implement Socket.IO with connection pooling by tenant?
```javascript
const tenantPools = new Map();

io.on('connection', (socket) => {
  const tenantId = socket.user.tenantId;
  
  if (!tenantPools.has(tenantId)) {
    tenantPools.set(tenantId, {
      sockets: new Set(),
      messageCount: 0,
      bytesTransferred: 0,
      createdAt: Date.now()
    });
  }
  
  const pool = tenantPools.get(tenantId);
  pool.sockets.add(socket);
  
  socket.on('message', (data) => {
    pool.messageCount++;
    pool.bytesTransferred += JSON.stringify(data).length;
    
    // Broadcast to tenant pool
    pool.sockets.forEach(s => {
      if (s !== socket && s.connected) {
        s.emit('message', data);
      }
    });
  });
  
  socket.on('disconnect', () => {
    pool.sockets.delete(socket);
    if (pool.sockets.size === 0) {
      tenantPools.delete(tenantId);
    }
  });
});

// Monitor tenant pools
setInterval(() => {
  tenantPools.forEach((pool, tenantId) => {
    console.log(`Tenant ${tenantId}:`, {
      connections: pool.sockets.size,
      messages: pool.messageCount,
      bytes: pool.bytesTransferred
    });
  });
}, 60000);
```
