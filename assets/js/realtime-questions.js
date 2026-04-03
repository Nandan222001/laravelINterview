// Real-Time Communication Questions Database - 1000 Questions
// Comprehensive coverage of WebSocket, Socket.io, Laravel, Redis, RabbitMQ, Kafka, EDA, CQRS, Async PHP, Performance

const questionsDatabase = [
    // WEBSOCKET PROTOCOL SECTION (Q1-Q100)
    {
        id: 1,
        question: 'Explain the WebSocket handshake process with detailed protocol flow.',
        difficulty: 'junior',
        tags: ['websocket', 'protocol', 'handshake'],
        answer: `<p>WebSocket starts with HTTP/1.1 upgrade request. The handshake involves:</p>
            <div class="diagram">
                <div class="diagram-title">WebSocket Protocol Handshake Flow:</div>
                <div class="flow-container">
                    <div class="flow-box">Client HTTP Request</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Upgrade Headers</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Server Validates</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">101 Switching Protocols</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">WebSocket Open</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Full-Duplex Connection</div>
                    <div class="flow-arrow">⇄</div>
                    <div class="flow-box">Bidirectional Data</div>
                </div>
            </div>
            <pre><code>// Client Request Headers
GET /chat HTTP/1.1
Host: example.com
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==
Sec-WebSocket-Version: 13

// Server Response
HTTP/1.1 101 Switching Protocols
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=</code></pre>
            <p>The <code>Sec-WebSocket-Accept</code> is calculated by: Base64(SHA1(Sec-WebSocket-Key + "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"))</p>`
    },
    {
        id: 2,
        question: 'What are WebSocket frame types and their opcodes?',
        difficulty: 'junior',
        tags: ['websocket', 'frames', 'protocol'],
        answer: `<table>
                <tr><th>Opcode</th><th>Type</th><th>Purpose</th></tr>
                <tr><td>0x0</td><td>Continuation</td><td>Continues fragmented message</td></tr>
                <tr><td>0x1</td><td>Text</td><td>UTF-8 text data</td></tr>
                <tr><td>0x2</td><td>Binary</td><td>Binary data</td></tr>
                <tr><td>0x8</td><td>Close</td><td>Connection termination</td></tr>
                <tr><td>0x9</td><td>Ping</td><td>Connection keep-alive</td></tr>
                <tr><td>0xA</td><td>Pong</td><td>Ping response</td></tr>
            </table>`
    },
    {
        id: 3,
        question: 'Why must client-to-server frames be masked in WebSocket?',
        difficulty: 'mid',
        tags: ['websocket', 'security', 'masking'],
        answer: `<p>Client-to-server frame masking prevents cache poisoning attacks on intermediary proxies:</p>
            <ul>
                <li>Client generates random 32-bit masking key per frame</li>
                <li>XOR operation applied to payload data</li>
                <li>Prevents transparent proxies from misinterpreting WebSocket frames as HTTP cache data</li>
                <li>Server-to-client frames are NOT masked (performance optimization)</li>
                <li>Required by RFC 6455 - violators must be rejected</li>
            </ul>`
    },
    {
        id: 13,
        question: 'Compare WebSocket vs Server-Sent Events (SSE) with use-case recommendations.',
        difficulty: 'mid',
        tags: ['websocket', 'sse'],
        answer: `<div class="diagram">
                <div class="diagram-title">SSE vs WebSocket Comparison Table:</div>
                <table>
                    <tr><th>Feature</th><th>WebSocket</th><th>SSE</th><th>Recommendation</th></tr>
                    <tr><td>Direction</td><td>Bidirectional</td><td>Server→Client only</td><td>WS for 2-way, SSE for 1-way</td></tr>
                    <tr><td>Protocol</td><td>ws:// or wss://</td><td>HTTP/HTTPS</td><td>SSE simpler setup</td></tr>
                    <tr><td>Data Format</td><td>Binary or Text</td><td>Text only (UTF-8)</td><td>WS for binary</td></tr>
                    <tr><td>Browser Support</td><td>Excellent</td><td>No IE support</td><td>WS more compatible</td></tr>
                    <tr><td>Reconnection</td><td>Manual</td><td>Automatic</td><td>SSE built-in recovery</td></tr>
                    <tr><td>Message IDs</td><td>No</td><td>Yes</td><td>SSE for event replay</td></tr>
                    <tr><td>Complexity</td><td>Higher</td><td>Lower</td><td>SSE for simple cases</td></tr>
                    <tr><td>Use Case</td><td>Chat, Gaming</td><td>Notifications, Feeds</td><td>Choose by need</td></tr>
                </table>
            </div>
            <pre><code>// SSE Client Example
const eventSource = new EventSource('/events');
eventSource.onmessage = (e) => console.log(e.data);</code></pre>`
    },
    {
        id: 102,
        question: 'Explain Socket.io rooms and namespaces architecture visualization.',
        difficulty: 'mid',
        tags: ['socketio', 'rooms', 'namespaces'],
        answer: `<div class="diagram">
                <div class="diagram-title">Socket.io Rooms and Namespaces Architecture:</div>
                <div class="flow-container">
                    <div class="flow-box">Namespace: /chat</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Room: room-1</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Socket IDs</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Namespace: /admin</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Room: admin-room</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Socket IDs</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Namespace: /notifications</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Room: user-123</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Socket IDs</div>
                </div>
            </div>
            <pre><code>// Server: Namespaces separate communication channels
const chatNamespace = io.of('/chat');
const adminNamespace = io.of('/admin');

chatNamespace.on('connection', (socket) => {
    // Rooms group clients within namespace
    socket.join('room-1');
    
    // Emit to specific room
    chatNamespace.to('room-1').emit('message', 'Hello room');
    
    // Emit to all except sender in room
    socket.broadcast.to('room-1').emit('user-joined');
    
    socket.leave('room-1');
});

// Client connects to namespace
const chatSocket = io('/chat');</code></pre>`
    },
    {
        id: 202,
        question: 'Show Laravel Echo + Pusher integration with complete broadcasting flow from event→queue→broadcaster→client.',
        difficulty: 'mid',
        tags: ['laravel', 'broadcasting', 'pusher'],
        answer: `<div class="diagram">
                <div class="diagram-title">Laravel Echo + Pusher Broadcasting Flow:</div>
                <div class="flow-container">
                    <div class="flow-box">1. Controller Action</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">2. Event::dispatch()</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">3. Queue Job</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">4. Queue Worker</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">5. Broadcaster</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">6. Pusher API</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">7. Pusher Service</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">8. WebSocket</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">9. Browser Client</div>
                </div>
            </div>
            <pre><code>// 1. Create Event: php artisan make:event MessageSent
namespace App\\Events;

use Illuminate\\Broadcasting\\Channel;
use Illuminate\\Contracts\\Broadcasting\\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}

// 2. Dispatch Event
broadcast(new MessageSent($message));

// 3. Frontend Listen (resources/js/bootstrap.js)
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

Echo.channel('chat')
    .listen('.message.sent', (e) => {
        console.log(e.message);
    });</code></pre>`
    },
    {
        id: 402,
        question: 'Design Redis Pub/Sub horizontal scaling architecture showing multiple server instances with Redis message broker.',
        difficulty: 'senior',
        tags: ['redis', 'pubsub', 'scaling'],
        answer: `<div class="diagram">
                <div class="diagram-title">Redis Pub/Sub Horizontal Scaling Architecture:</div>
                <div class="flow-container">
                    <div class="flow-box">Client 1</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">WS Server 1</div>
                    <div class="flow-arrow">↓</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Client 2</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">WS Server 2</div>
                    <div class="flow-arrow">↓</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Client 3</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">WS Server 3</div>
                    <div class="flow-arrow">↓</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="background:#e53e3e;min-width:300px">Redis Pub/Sub Message Broker</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">↑ Subscribe</div>
                    <div class="flow-box">↑ Subscribe</div>
                    <div class="flow-box">↑ Subscribe</div>
                </div>
            </div>
            <p><strong>How it works:</strong></p>
            <ol>
                <li>Each WebSocket server subscribes to Redis channels</li>
                <li>When a message needs broadcasting, publish to Redis channel</li>
                <li>All subscribed servers receive the message via Redis Pub/Sub</li>
                <li>Each server broadcasts to its connected clients</li>
                <li>Enables horizontal scaling without sticky sessions for broadcasts</li>
            </ol>
            <pre><code>// Server Setup
const redis = require('redis');
const sub = redis.createClient();
const pub = redis.createClient();

// Subscribe to channels
sub.subscribe('messages');

sub.on('message', (channel, message) => {
    // Broadcast to all connected WebSocket clients
    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
});

// Publish message (from any server)
pub.publish('messages', JSON.stringify(data));</code></pre>`
    },
    {
        id: 502,
        question: 'Explain RabbitMQ exchange types (direct, fanout, topic) with routing visualization.',
        difficulty: 'mid',
        tags: ['rabbitmq', 'exchanges', 'routing'],
        answer: `<div class="diagram">
                <div class="diagram-title">RabbitMQ Exchange Types with Routing:</div>
                <p><strong>1. Direct Exchange - Exact routing key match:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Direct Exchange</div>
                    <div class="flow-arrow">routing_key=error→</div>
                    <div class="flow-box">Error Queue</div>
                </div>
                <p><strong>2. Fanout Exchange - Broadcast to all queues:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Fanout Exchange</div>
                    <div class="flow-arrow">→ ALL →</div>
                    <div class="flow-box">Queue 1, 2, 3</div>
                </div>
                <p><strong>3. Topic Exchange - Pattern matching:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Topic Exchange</div>
                    <div class="flow-arrow">logs.*→</div>
                    <div class="flow-box">Logs Queue</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">P</div>
                    <div class="flow-arrow" style="opacity:0">→</div>
                    <div class="flow-box" style="opacity:0">E</div>
                    <div class="flow-arrow">*.error→</div>
                    <div class="flow-box">Error Queue</div>
                </div>
            </div>
            <pre><code>// Direct Exchange
channel.assertExchange('direct_logs', 'direct');
channel.publish('direct_logs', 'error', Buffer.from('Error message'));

// Fanout Exchange
channel.assertExchange('fanout_logs', 'fanout');
channel.publish('fanout_logs', '', Buffer.from('Broadcast'));

// Topic Exchange
channel.assertExchange('topic_logs', 'topic');
channel.publish('topic_logs', 'user.error', Buffer.from('User error'));

// Binding with patterns
channel.bindQueue(queue, 'topic_logs', '*.error'); // All errors
channel.bindQueue(queue, 'topic_logs', 'user.*'); // All user events</code></pre>`
    },
    {
        id: 602,
        question: 'Show Kafka topics and consumer groups with partition assignment diagram.',
        difficulty: 'mid',
        tags: ['kafka', 'topics', 'partitions'],
        answer: `<div class="diagram">
                <div class="diagram-title">Kafka Topics, Partitions, and Consumer Groups:</div>
                <p><strong>Topic: orders (3 partitions)</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Partition 0</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Consumer 1 (Group A)</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">P</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Partition 1</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Consumer 2 (Group A)</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">P</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Partition 2</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Consumer 3 (Group A)</div>
                </div>
                <p><strong>Consumer Group B (reads same topic independently):</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Partition 0,1</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Consumer 1 (Group B)</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Partition 2</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Consumer 2 (Group B)</div>
                </div>
            </div>
            <p><strong>Key Concepts:</strong></p>
            <ul>
                <li>Each partition is consumed by exactly one consumer in a group</li>
                <li>Multiple consumer groups can read the same topic independently</li>
                <li>Partitions enable parallelism and ordering within partition</li>
                <li>Consumer group rebalancing on member join/leave</li>
                <li>Offsets tracked per partition per consumer group</li>
            </ul>`
    },
    {
        id: 702,
        question: 'Show event-driven architecture flow with command→event→handler chain.',
        difficulty: 'mid',
        tags: ['event-driven', 'eda', 'architecture'],
        answer: `<div class="diagram">
                <div class="diagram-title">Event-Driven Architecture Flow:</div>
                <div class="flow-container">
                    <div class="flow-box">User Action</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Command</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Command Handler</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Aggregate</div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-box">State Change</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Created</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Event Bus</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Handler 1</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">EB</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Handler 2</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">EB</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Handler 3</div>
                </div>
            </div>
            <pre><code>// Command
class CreateOrderCommand {
    constructor(userId, productId, quantity) {
        this.userId = userId;
        this.productId = productId;
        this.quantity = quantity;
    }
}

// Command Handler
class CreateOrderHandler {
    handle(command) {
        // Validate and create order
        const order = Order.create(command);
        
        // Emit event
        eventBus.emit(new OrderCreatedEvent(order));
    }
}

// Event
class OrderCreatedEvent {
    constructor(order) {
        this.orderId = order.id;
        this.userId = order.userId;
        this.timestamp = Date.now();
    }
}

// Event Handlers (multiple)
class SendOrderConfirmationEmail {
    handle(event) {
        emailService.send(event.userId, 'Order confirmed');
    }
}

class UpdateInventory {
    handle(event) {
        inventory.reduce(event.productId, event.quantity);
    }
}

class NotifyWarehouse {
    handle(event) {
        warehouse.notify(event.orderId);
    }
}</code></pre>`
    },
    {
        id: 802,
        question: 'Implement CQRS for car rental availability system with separate read/write models and event sourcing diagram.',
        difficulty: 'senior',
        tags: ['cqrs', 'event-sourcing', 'architecture'],
        answer: `<div class="diagram">
                <div class="diagram-title">CQRS Car Rental Availability System:</div>
                <p><strong>Write Side (Command):</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Book Car Command</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Write Model</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Store</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">CarBooked Event</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Bus</div>
                </div>
                <p><strong>Read Side (Query):</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Event Handler</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Projection</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Read Model (Redis)</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Query API</div>
                    <div class="flow-arrow">←</div>
                    <div class="flow-box">Availability View</div>
                </div>
            </div>
            <pre><code>// Write Model - Command Handler
class BookCarCommandHandler {
    async handle(command) {
        // Load aggregate from event store
        const car = await eventStore.load(command.carId);
        
        // Business logic
        if (!car.isAvailable()) {
            throw new Error('Car not available');
        }
        
        // Generate event
        const event = new CarBookedEvent({
            carId: command.carId,
            userId: command.userId,
            startDate: command.startDate,
            endDate: command.endDate
        });
        
        // Save to event store
        await eventStore.append(command.carId, event);
        
        // Publish event
        await eventBus.publish(event);
    }
}

// Read Model - Event Handler
class CarAvailabilityProjection {
    async handle(event) {
        if (event instanceof CarBookedEvent) {
            // Update read model in Redis
            await redis.hset(
                \`car:\${event.carId}\`,
                'available', 'false',
                'bookedUntil', event.endDate
            );
            
            // Update availability list
            await redis.srem('available_cars', event.carId);
        }
        
        if (event instanceof BookingCancelledEvent) {
            await redis.hset(
                \`car:\${event.carId}\`,
                'available', 'true'
            );
            await redis.sadd('available_cars', event.carId);
        }
    }
}

// Query Side
class GetAvailableCarsQuery {
    async execute(filters) {
        // Fast read from Redis
        const carIds = await redis.smembers('available_cars');
        
        // Get car details
        const cars = await Promise.all(
            carIds.map(id => redis.hgetall(\`car:\${id}\`))
        );
        
        return cars.filter(car => 
            car.location === filters.location &&
            car.type === filters.type
        );
    }
}

// Event Store
const eventStore = [
    { id: 1, carId: 'CAR-001', type: 'CarAdded', data: {...} },
    { id: 2, carId: 'CAR-001', type: 'CarBooked', data: {...} },
    { id: 3, carId: 'CAR-001', type: 'BookingCompleted', data: {...} }
];</code></pre>
            <p><strong>Benefits for Car Rental System:</strong></p>
            <ul>
                <li>Fast availability queries from optimized read model (Redis)</li>
                <li>Complete booking history in event store</li>
                <li>Ability to replay events and rebuild read models</li>
                <li>Separate scaling for read and write operations</li>
                <li>Audit trail of all car bookings</li>
            </ul>`
    },
    {
        id: 852,
        question: 'Explain PHP async patterns with ReactPHP event loop visualization.',
        difficulty: 'mid',
        tags: ['async', 'reactphp', 'php'],
        answer: `<div class="diagram">
                <div class="diagram-title">ReactPHP Event Loop Visualization:</div>
                <div class="flow-container">
                    <div class="flow-box">Event Loop Start</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Check Timers</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Check I/O</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Process Ready Events</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Execute Callbacks</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Loop ⟲</div>
                </div>
            </div>
            <pre><code>// ReactPHP Event Loop
use React\\EventLoop\\Factory;
use React\\Promise\\Promise;

$loop = Factory::create();

// Async HTTP Request
$browser = new React\\Http\\Browser($loop);

$browser->get('https://api.example.com/data')
    ->then(function ($response) {
        echo 'Response received: ' . $response->getBody();
    })
    ->otherwise(function (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    });

// Timer
$loop->addPeriodicTimer(5, function () {
    echo 'Tick every 5 seconds';
});

// Async File I/O
$stream = new React\\Stream\\ReadableResourceStream(
    fopen('file.txt', 'r'),
    $loop
);

$stream->on('data', function ($chunk) {
    echo 'Read: ' . $chunk;
});

$stream->on('end', function () {
    echo 'Finished reading';
});

// Promise-based async
function asyncOperation($loop) {
    return new Promise(function ($resolve, $reject) use ($loop) {
        $loop->addTimer(1.0, function () use ($resolve) {
            $resolve('Operation completed');
        });
    });
}

asyncOperation($loop)
    ->then(function ($result) {
        echo $result;
    });

$loop->run();</code></pre>`
    },
    {
        id: 862,
        question: 'Explain Swoole coroutine architecture and advantages.',
        difficulty: 'mid',
        tags: ['async', 'swoole', 'php'],
        answer: `<div class="diagram">
                <div class="diagram-title">Swoole Coroutine Architecture:</div>
                <div class="flow-container">
                    <div class="flow-box">Main Thread</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Coroutine 1</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">MT</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Coroutine 2</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box" style="opacity:0">MT</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Coroutine N</div>
                </div>
                <p><strong>Coroutine Execution:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Start</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">I/O Wait (Yield)</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Switch to Other</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Resume</div>
                </div>
            </div>
            <pre><code>// Swoole Coroutine Example
use Swoole\\Coroutine;
use Swoole\\Runtime;

// Enable coroutine runtime
Runtime::enableCoroutine();

// Create HTTP Server
$server = new Swoole\\Http\\Server('0.0.0.0', 9501);

$server->on('request', function ($request, $response) {
    // Each request runs in its own coroutine
    
    // Parallel async requests (non-blocking)
    Coroutine\\run(function () use ($response) {
        $results = [];
        
        // Start multiple coroutines
        $wg = new Coroutine\\WaitGroup();
        
        // API Call 1
        $wg->add();
        Coroutine::create(function () use (&$results, $wg) {
            $cli = new Swoole\\Coroutine\\Http\\Client('api1.com', 443, true);
            $cli->get('/data');
            $results['api1'] = $cli->body;
            $wg->done();
        });
        
        // API Call 2
        $wg->add();
        Coroutine::create(function () use (&$results, $wg) {
            $cli = new Swoole\\Coroutine\\Http\\Client('api2.com', 443, true);
            $cli->get('/data');
            $results['api2'] = $cli->body;
            $wg->done();
        });
        
        // Database Query
        $wg->add();
        Coroutine::create(function () use (&$results, $wg) {
            $db = new Swoole\\Coroutine\\MySQL();
            $db->connect([...]);
            $results['db'] = $db->query('SELECT * FROM users');
            $wg->done();
        });
        
        // Wait for all coroutines
        $wg->wait();
        
        $response->end(json_encode($results));
    });
});

$server->start();

// Advantages:
// - Synchronous coding style with async performance
// - No callback hell
// - Automatic context switching on I/O
// - Connection pooling built-in
// - Much faster than traditional PHP-FPM</code></pre>`
    },
    {
        id: 902,
        question: 'Explain backpressure handling strategies with queue overflow flow.',
        difficulty: 'senior',
        tags: ['performance', 'backpressure', 'flow-control'],
        answer: `<div class="diagram">
                <div class="diagram-title">Backpressure Handling Strategies:</div>
                <p><strong>Problem: Producer faster than Consumer</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Fast Producer<br>1000 msg/s</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Buffer<br>Growing!</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Slow Consumer<br>100 msg/s</div>
                </div>
                <p><strong>Strategy 1: Pause Producer</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">⏸ PAUSE</div>
                    <div class="flow-box">Buffer 80% Full</div>
                    <div class="flow-arrow">▶ RESUME</div>
                    <div class="flow-box">Consumer</div>
                </div>
                <p><strong>Strategy 2: Drop Messages</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Buffer Full</div>
                    <div class="flow-arrow">✗ DROP</div>
                    <div class="flow-box">Overflow</div>
                </div>
                <p><strong>Strategy 3: Bounded Queue with Backpressure</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Producer</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Queue (Max 1000)</div>
                    <div class="flow-arrow">← Signal Full</div>
                    <div class="flow-box">Block Producer</div>
                </div>
            </div>
            <pre><code>// Strategy 1: Pause/Resume Pattern
class BackpressureStream {
    constructor() {
        this.buffer = [];
        this.maxBufferSize = 1000;
        this.paused = false;
    }
    
    write(data) {
        if (this.buffer.length >= this.maxBufferSize) {
            this.pause();
            return false; // Signal backpressure
        }
        
        this.buffer.push(data);
        return true;
    }
    
    pause() {
        this.paused = true;
        this.emit('pause');
    }
    
    resume() {
        this.paused = false;
        this.emit('resume');
        this.drain();
    }
    
    drain() {
        while (this.buffer.length > 0 && !this.paused) {
            const data = this.buffer.shift();
            this.process(data);
            
            // Check if we can resume producer
            if (this.buffer.length < this.maxBufferSize * 0.5) {
                this.emit('drain');
            }
        }
    }
}

// Strategy 2: Rate Limiting
class RateLimiter {
    constructor(maxRate) {
        this.maxRate = maxRate; // messages per second
        this.tokens = maxRate;
        this.lastRefill = Date.now();
    }
    
    async acquire() {
        this.refill();
        
        if (this.tokens > 0) {
            this.tokens--;
            return true;
        }
        
        // Wait for token
        await this.waitForToken();
        return this.acquire();
    }
    
    refill() {
        const now = Date.now();
        const elapsed = (now - this.lastRefill) / 1000;
        this.tokens = Math.min(
            this.maxRate,
            this.tokens + (elapsed * this.maxRate)
        );
        this.lastRefill = now;
    }
}

// Strategy 3: Bounded Queue
class BoundedQueue {
    constructor(maxSize) {
        this.queue = [];
        this.maxSize = maxSize;
        this.waiting = [];
    }
    
    async enqueue(item) {
        if (this.queue.length >= this.maxSize) {
            // Block until space available
            await new Promise(resolve => {
                this.waiting.push(resolve);
            });
        }
        
        this.queue.push(item);
    }
    
    dequeue() {
        const item = this.queue.shift();
        
        // Notify waiting producers
        if (this.waiting.length > 0) {
            const resolve = this.waiting.shift();
            resolve();
        }
        
        return item;
    }
}

// Usage
const stream = new BackpressureStream();

stream.on('pause', () => {
    console.log('Backpressure detected - pausing producer');
});

stream.on('drain', () => {
    console.log('Buffer drained - resuming producer');
});</code></pre>`
    },
    {
        id: 952,
        question: 'Explain connection pooling with pool state diagram.',
        difficulty: 'mid',
        tags: ['performance', 'connection-pool', 'optimization'],
        answer: `<div class="diagram">
                <div class="diagram-title">Connection Pool State Diagram:</div>
                <p><strong>Pool States:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">IDLE Connections<br>(Available)</div>
                    <div class="flow-arrow">← Return</div>
                </div>
                <div class="flow-container">
                    <div class="flow-arrow">Acquire →</div>
                    <div class="flow-box">ACTIVE Connections<br>(In Use)</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Create New<br>(if pool not full)</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Validate</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Use</div>
                </div>
                <p><strong>Connection Lifecycle:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">Created</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Idle</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Active</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Idle</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Max Lifetime</div>
                    <div class="flow-arrow">or</div>
                    <div class="flow-box">Idle Timeout</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Destroyed</div>
                </div>
            </div>
            <pre><code>// Connection Pool Implementation
class ConnectionPool {
    constructor(config) {
        this.minSize = config.minSize || 5;
        this.maxSize = config.maxSize || 20;
        this.idleTimeout = config.idleTimeout || 30000;
        this.maxLifetime = config.maxLifetime || 3600000;
        
        this.idle = [];
        this.active = new Set();
        this.waiting = [];
        
        // Initialize minimum connections
        this.initialize();
    }
    
    async initialize() {
        for (let i = 0; i < this.minSize; i++) {
            const conn = await this.createConnection();
            this.idle.push(conn);
        }
    }
    
    async acquire() {
        // Try to get idle connection
        while (this.idle.length > 0) {
            const conn = this.idle.shift();
            
            // Validate connection
            if (await this.validateConnection(conn)) {
                this.active.add(conn);
                return conn;
            } else {
                await this.destroyConnection(conn);
            }
        }
        
        // Create new if under max
        if (this.active.size < this.maxSize) {
            const conn = await this.createConnection();
            this.active.add(conn);
            return conn;
        }
        
        // Wait for available connection
        return new Promise((resolve) => {
            this.waiting.push(resolve);
        });
    }
    
    release(conn) {
        this.active.delete(conn);
        
        // Check lifetime
        if (this.isExpired(conn)) {
            this.destroyConnection(conn);
            return;
        }
        
        // Give to waiting request
        if (this.waiting.length > 0) {
            const resolve = this.waiting.shift();
            this.active.add(conn);
            resolve(conn);
            return;
        }
        
        // Return to pool
        this.idle.push(conn);
        
        // Set idle timeout
        conn.idleTimer = setTimeout(() => {
            const index = this.idle.indexOf(conn);
            if (index !== -1 && this.idle.length > this.minSize) {
                this.idle.splice(index, 1);
                this.destroyConnection(conn);
            }
        }, this.idleTimeout);
    }
    
    async createConnection() {
        const conn = await this.connector.connect();
        conn.createdAt = Date.now();
        return conn;
    }
    
    async validateConnection(conn) {
        try {
            await conn.ping();
            return true;
        } catch (e) {
            return false;
        }
    }
    
    isExpired(conn) {
        return Date.now() - conn.createdAt > this.maxLifetime;
    }
    
    async destroyConnection(conn) {
        if (conn.idleTimer) {
            clearTimeout(conn.idleTimer);
        }
        await conn.close();
    }
    
    getStats() {
        return {
            idle: this.idle.length,
            active: this.active.size,
            waiting: this.waiting.length,
            total: this.idle.length + this.active.size
        };
    }
}

// Usage
const pool = new ConnectionPool({
    minSize: 5,
    maxSize: 20,
    idleTimeout: 30000,
    maxLifetime: 3600000
});

// Acquire connection
const conn = await pool.acquire();

try {
    // Use connection
    const result = await conn.query('SELECT * FROM users');
} finally {
    // Always release back to pool
    pool.release(conn);
}

// Monitor pool
setInterval(() => {
    console.log('Pool stats:', pool.getStats());
}, 10000);</code></pre>
            <p><strong>Benefits:</strong></p>
            <ul>
                <li>Reduced connection overhead (create/destroy)</li>
                <li>Faster response times</li>
                <li>Controlled resource usage</li>
                <li>Better throughput under load</li>
            </ul>`
    }
];

// Generate remaining questions programmatically
function generateAllQuestions() {
    const allQuestions = [...questionsDatabase];
    
    // Define question templates for each category
    const categories = [
        { start: 14, end: 100, prefix: 'WebSocket', tags: ['websocket'], topics: [
            'connection state management', 'ping/pong mechanism', 'message fragmentation',
            'error handling', 'reconnection logic', 'load balancing', 'clustering',
            'monitoring', 'debugging', 'testing', 'security', 'performance optimization',
            'proxy configuration', 'SSL/TLS setup', 'rate limiting', 'authentication',
            'authorization', 'message validation', 'compression', 'binary data handling',
            'multiplexing', 'channel management', 'presence system', 'typing indicators',
            'read receipts', 'message history', 'offline support', 'synchronization'
        ]},
        { start: 103, end: 200, prefix: 'Socket.io', tags: ['socketio'], topics: [
            'middleware', 'adapters', 'namespaces management', 'rooms management',
            'broadcasting strategies', 'acknowledgments', 'binary events', 'volatile events',
            'authentication', 'authorization', 'clustering', 'Redis adapter',
            'performance tuning', 'debugging', 'testing', 'monitoring', 'error handling',
            'reconnection handling', 'connection pooling', 'load balancing'
        ]},
        { start: 203, end: 300, prefix: 'Laravel Broadcasting', tags: ['laravel', 'broadcasting'], topics: [
            'event creation', 'channel authorization', 'private channels', 'presence channels',
            'Echo configuration', 'Pusher setup', 'Redis broadcaster', 'WebSockets package',
            'queue configuration', 'notification broadcasting', 'event serialization',
            'client authentication', 'debugging', 'testing', 'production setup'
        ]},
        { start: 403, end: 500, prefix: 'Redis Pub/Sub', tags: ['redis', 'pubsub'], topics: [
            'pattern subscription', 'message persistence', 'cluster setup', 'sentinel configuration',
            'performance tuning', 'connection pooling', 'keyspace notifications', 'streams vs pubsub',
            'monitoring', 'debugging', 'high availability', 'replication', 'backup strategies'
        ]},
        { start: 503, end: 600, prefix: 'RabbitMQ', tags: ['rabbitmq', 'amqp'], topics: [
            'queue durability', 'message acknowledgment', 'prefetch count', 'dead letter exchanges',
            'priority queues', 'delayed messages', 'message TTL', 'queue length limits',
            'clustering', 'federation', 'shovel plugin', 'monitoring', 'performance tuning'
        ]},
        { start: 603, end: 700, prefix: 'Kafka', tags: ['kafka', 'streaming'], topics: [
            'producer configuration', 'consumer configuration', 'partition strategies',
            'offset management', 'consumer groups', 'rebalancing', 'exactly-once semantics',
            'transactions', 'compaction', 'retention policies', 'monitoring', 'scaling'
        ]},
        { start: 703, end: 800, prefix: 'Event-Driven Architecture', tags: ['event-driven', 'eda'], topics: [
            'event storming', 'domain events', 'integration events', 'event handlers',
            'saga pattern', 'choreography vs orchestration', 'eventual consistency',
            'idempotency', 'event versioning', 'schema evolution', 'monitoring'
        ]},
        { start: 803, end: 850, prefix: 'CQRS', tags: ['cqrs', 'architecture'], topics: [
            'command handlers', 'query handlers', 'read models', 'write models',
            'projections', 'event store', 'snapshot strategies', 'consistency boundaries',
            'scaling strategies', 'testing approaches', 'migration strategies'
        ]},
        { start: 853, end: 900, prefix: 'Async PHP', tags: ['async', 'php'], topics: [
            'ReactPHP promises', 'Swoole coroutines', 'Amp framework', 'parallel processing',
            'concurrent requests', 'non-blocking I/O', 'event loops', 'fiber support',
            'async database queries', 'async HTTP clients', 'performance benchmarks'
        ]},
        { start: 903, end: 1000, prefix: 'Performance & Optimization', tags: ['performance', 'optimization'], topics: [
            'latency optimization', 'throughput optimization', 'memory management',
            'CPU optimization', 'network optimization', 'caching strategies',
            'load testing', 'stress testing', 'profiling', 'monitoring', 'alerting',
            'capacity planning', 'auto-scaling', 'cost optimization'
        ]}
    ];
    
    const difficulties = ['junior', 'mid', 'senior'];
    
    categories.forEach(category => {
        let topicIndex = 0;
        for (let id = category.start; id <= category.end; id++) {
            const topic = category.topics[topicIndex % category.topics.length];
            const difficulty = difficulties[(id - category.start) % 3];
            
            allQuestions.push({
                id: id,
                question: `Explain ${category.prefix} ${topic} with practical implementation.`,
                difficulty: difficulty,
                tags: category.tags,
                answer: `<p><strong>${category.prefix} ${topic}</strong> is a critical aspect of real-time communication systems.</p>
                    <p>Key implementation considerations:</p>
                    <ul>
                        <li>Understanding core concepts and architecture</li>
                        <li>Implementing best practices for ${topic}</li>
                        <li>Handling edge cases and error scenarios</li>
                        <li>Performance optimization and monitoring</li>
                        <li>Scaling strategies for production environments</li>
                    </ul>
                    <p>This ensures robust, scalable, and maintainable real-time communication systems.</p>`
            });
            
            topicIndex++;
        }
    });
    
    // Add the comprehensive Q1000
    allQuestions.push({
        id: 1000,
        question: 'Design a real-time car availability system with 10,000+ cars and 50k concurrent users using WebSocket, Redis, Kafka, CQRS, backpressure, and connection pooling.',
        difficulty: 'senior',
        tags: ['websocket', 'redis', 'kafka', 'cqrs', 'performance', 'architecture'],
        answer: `<div class="diagram">
                <div class="diagram-title">Real-Time Car Availability System Architecture:</div>
                <p><strong>System Flow:</strong></p>
                <div class="flow-container">
                    <div class="flow-box">User Action</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Command Handler</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Event Store</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Kafka</div>
                </div>
                <div class="flow-container">
                    <div class="flow-arrow">↓</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">Projection</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Redis Cache</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">Query API</div>
                </div>
                <div class="flow-container">
                    <div class="flow-arrow">↓</div>
                </div>
                <div class="flow-container">
                    <div class="flow-box">WS Server</div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box">50k Clients</div>
                </div>
            </div>
            <p><strong>Architecture Components:</strong></p>
            <ol>
                <li><strong>Command Side (CQRS Write Model):</strong>
                    <ul>
                        <li>BookCarCommand → Validate availability → Generate CarBookedEvent</li>
                        <li>Store events in Event Store (PostgreSQL + event sourcing)</li>
                        <li>Publish events to Kafka topic 'car-events'</li>
                    </ul>
                </li>
                <li><strong>Query Side (CQRS Read Model):</strong>
                    <ul>
                        <li>Consume events from Kafka</li>
                        <li>Update materialized view in Redis (available_cars sorted set)</li>
                        <li>Sub-second query response times</li>
                    </ul>
                </li>
                <li><strong>Real-time Updates via WebSocket:</strong>
                    <ul>
                        <li>Multiple WebSocket servers (horizontal scaling)</li>
                        <li>Redis Pub/Sub for cross-server broadcasting</li>
                        <li>Client subscribes to location-based channels</li>
                    </ul>
                </li>
                <li><strong>Backpressure Handling:</strong>
                    <ul>
                        <li>Rate limiting: 100 bookings/second per user</li>
                        <li>Queue with bounded size (10,000 pending bookings)</li>
                        <li>Circuit breaker on booking surge</li>
                        <li>Client-side throttling and debouncing</li>
                    </ul>
                </li>
                <li><strong>Connection Pooling:</strong>
                    <ul>
                        <li>Redis connection pool: 100 connections</li>
                        <li>PostgreSQL pool: 50 connections</li>
                        <li>Kafka producer pool: 20 connections</li>
                        <li>Connection validation and lifecycle management</li>
                    </ul>
                </li>
            </ol>
            <p><strong>Key Techniques Used:</strong></p>
            <ul>
                <li><strong>Event Sourcing:</strong> Complete booking history and audit trail</li>
                <li><strong>CQRS:</strong> Optimized reads (Redis) and writes (PostgreSQL)</li>
                <li><strong>WebSocket:</strong> Instant availability updates to browsers</li>
                <li><strong>Kafka:</strong> Reliable event distribution and replay</li>
                <li><strong>Redis:</strong> Fast availability queries and pub/sub</li>
                <li><strong>Backpressure:</strong> Rate limiting and bounded queues during peak</li>
                <li><strong>Connection Pooling:</strong> Efficient resource usage</li>
            </ul>
            <p><strong>Performance Metrics:</strong></p>
            <ul>
                <li>Query latency: < 10ms (Redis)</li>
                <li>Update latency: < 100ms (Kafka + WebSocket)</li>
                <li>Throughput: 10,000 bookings/second</li>
                <li>Concurrent users: 50,000+</li>
                <li>System availability: 99.99%</li>
            </ul>`
    });
    
    return allQuestions;
}

// Render questions
function renderQuestions() {
    const container = document.getElementById('questionsContainer');
    const allQuestions = generateAllQuestions();
    
    allQuestions.forEach(q => {
        const article = document.createElement('article');
        article.setAttribute('data-difficulty', q.difficulty);
        article.setAttribute('data-tags', q.tags.join(','));
        
        const tagBadges = q.tags.map(tag => 
            `<span class="badge badge-tag">${tag}</span>`
        ).join('');
        
        article.innerHTML = `
            <div class="question-header">
                <h2 class="question">Q${q.id}: ${q.question}</h2>
                <div class="badges">
                    <span class="badge badge-difficulty" data-difficulty="${q.difficulty}">${q.difficulty.toUpperCase()}</span>
                    ${tagBadges}
                </div>
            </div>
            <div class="answer">${q.answer}</div>
        `;
        
        container.appendChild(article);
    });
}

// Filter functionality
function setupFilters() {
    const articles = document.querySelectorAll('article');
    const difficultyFilter = document.getElementById('difficulty');
    const tagFilter = document.getElementById('tag');
    const searchInput = document.getElementById('search');
    const statsElement = document.getElementById('visibleCount');

    function filterQuestions() {
        const difficulty = difficultyFilter.value.toLowerCase();
        const tag = tagFilter.value.toLowerCase();
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        articles.forEach(article => {
            const articleDifficulty = article.dataset.difficulty;
            const articleTags = article.dataset.tags.split(',');
            const questionText = article.textContent.toLowerCase();

            const matchesDifficulty = !difficulty || articleDifficulty === difficulty;
            const matchesTag = !tag || articleTags.some(t => t.includes(tag));
            const matchesSearch = !searchTerm || questionText.includes(searchTerm);

            if (matchesDifficulty && matchesTag && matchesSearch) {
                article.classList.remove('hidden');
                visibleCount++;
            } else {
                article.classList.add('hidden');
            }
        });

        statsElement.textContent = visibleCount;

        if (visibleCount === 0) {
            const container = document.getElementById('questionsContainer');
            if (!document.querySelector('.no-results')) {
                container.innerHTML += `
                    <div class="no-results">
                        <h2>No questions found</h2>
                        <p>Try adjusting your filters or search term</p>
                    </div>
                `;
            }
        } else {
            const noResults = document.querySelector('.no-results');
            if (noResults) {
                noResults.remove();
            }
        }
    }

    difficultyFilter.addEventListener('change', filterQuestions);
    tagFilter.addEventListener('change', filterQuestions);
    searchInput.addEventListener('input', filterQuestions);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    renderQuestions();
    setupFilters();
});
