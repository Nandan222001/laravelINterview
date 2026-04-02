# Real-Time Communication & Data Flow - Part 2

## Laravel Echo + Pusher Setup with Redis Broadcasting (Questions 101-200)

### 101. What is Laravel Echo and how does it work with broadcasting?
Laravel Echo is a JavaScript library that makes it easy to subscribe to channels and listen for events broadcast by Laravel. It works with Pusher, Socket.io, and Ably drivers, providing a unified API for real-time event handling.

### 102. How do you configure Laravel Broadcasting with Pusher?
```php
// config/broadcasting.php
return [
    'default' => env('BROADCAST_DRIVER', 'pusher'),
    
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'encrypted' => true,
                'host' => env('PUSHER_HOST', '127.0.0.1'),
                'port' => env('PUSHER_PORT', 6001),
                'scheme' => env('PUSHER_SCHEME', 'http'),
            ],
        ],
    ],
];

// .env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

### 103. How do you set up Laravel Echo on the frontend?
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': 'Bearer ' + authToken
        }
    }
});
```

### 104. How do you create a broadcastable event in Laravel?
```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderShipped implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('orders.' . $this->order->user_id);
    }

    public function broadcastAs()
    {
        return 'order.shipped';
    }

    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'shipped_at' => $this->order->shipped_at,
        ];
    }
}
```

### 105. How do you listen to events with Laravel Echo?
```javascript
// Listen on private channel
Echo.private(`orders.${userId}`)
    .listen('.order.shipped', (e) => {
        console.log('Order shipped:', e.order_id);
        updateOrderStatus(e.order_id, e.status);
    });

// Listen on public channel
Echo.channel('notifications')
    .listen('NotificationSent', (e) => {
        console.log('Notification:', e.message);
    });

// Listen on presence channel
Echo.join('chat.1')
    .here((users) => {
        console.log('Users here:', users);
    })
    .joining((user) => {
        console.log('User joining:', user.name);
    })
    .leaving((user) => {
        console.log('User leaving:', user.name);
    })
    .listen('MessageSent', (e) => {
        console.log('Message:', e.message);
    });
```

### 106. How do you configure Redis for Laravel Broadcasting?
```php
// config/broadcasting.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],

// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],

// Install predis
// composer require predis/predis
```

### 107. How do you set up Laravel WebSockets for self-hosted solution?
```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

```php
// config/websockets.php
return [
    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => false,
            'enable_statistics' => true,
        ],
    ],
];

// Start server
php artisan websockets:serve
```

### 108. How do you authenticate private channels in Laravel?
```php
// routes/channels.php
Broadcast::channel('orders.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return $user->can('access-room', $roomId) ? [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
    ] : false;
});
```

### 109. What are the different channel types in Laravel Broadcasting?
- **Public Channels**: No authentication required, anyone can listen
- **Private Channels**: Require authentication, prefixed with 'private-'
- **Presence Channels**: Like private but track who's subscribed, prefixed with 'presence-'

### 110. How do you broadcast to specific users?
```php
use Illuminate\Support\Facades\Broadcast;

// Broadcast to specific user
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Event
class UserNotification implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }
}

// Trigger
event(new UserNotification($user, $message));
```

### 111. How do you implement presence channels?
```javascript
Echo.join('chat.1')
    .here((users) => {
        // Initial list of users
        displayUsers(users);
    })
    .joining((user) => {
        // User joined
        addUserToList(user);
    })
    .leaving((user) => {
        // User left
        removeUserFromList(user);
    })
    .listenForWhisper('typing', (e) => {
        // Someone is typing
        showTypingIndicator(e.user);
    });
```

### 112. How do you use client events (whispers) in Laravel Echo?
```javascript
// Send whisper
Echo.join('chat.1')
    .whisper('typing', {
        user: currentUser,
        typing: true
    });

// Listen for whisper
Echo.join('chat.1')
    .listenForWhisper('typing', (e) => {
        console.log(e.user.name + ' is typing...');
    });

// Enable client messages in config
// config/websockets.php
'enable_client_messages' => true,
```

### 113. How do you queue broadcast events in Laravel?
```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

// Immediate broadcast
class OrderShipped implements ShouldBroadcastNow
{
    // ...
}

// Queued broadcast (default)
class OrderShipped implements ShouldBroadcast
{
    // ...
}

// Custom queue
class OrderShipped implements ShouldBroadcast
{
    public $broadcastQueue = 'broadcasts';
    
    public function broadcastQueue()
    {
        return 'high-priority';
    }
}
```

### 114. How do you broadcast notifications in Laravel?
```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class InvoicePaid extends Notification
{
    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->amount,
        ]);
    }

    public function broadcastType()
    {
        return 'invoice.paid';
    }
}

// Send notification
$user->notify(new InvoicePaid($invoice));
```

### 115. How do you listen to notification broadcasts?
```javascript
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log('Notification received:', notification);
        
        if (notification.type === 'invoice.paid') {
            showInvoicePaidNotification(notification.invoice_id);
        }
    });
```

### 116. How do you implement typing indicators with Laravel Echo?
```php
// Enable client events
// config/websockets.php
'enable_client_messages' => true,
```

```javascript
let typingTimer;
const typingDelay = 1000;

document.getElementById('message-input').addEventListener('input', () => {
    clearTimeout(typingTimer);
    
    Echo.join('chat.1').whisper('typing', {
        user: currentUser,
        typing: true
    });
    
    typingTimer = setTimeout(() => {
        Echo.join('chat.1').whisper('typing', {
            user: currentUser,
            typing: false
        });
    }, typingDelay);
});

Echo.join('chat.1')
    .listenForWhisper('typing', (e) => {
        if (e.typing) {
            showTypingIndicator(e.user);
        } else {
            hideTypingIndicator(e.user);
        }
    });
```

### 117. How do you implement real-time model broadcasting in Laravel?
```php
use Illuminate\Database\Eloquent\BroadcastsEvents;

class Post extends Model
{
    use BroadcastsEvents;

    protected $dispatchesEvents = [
        'created' => PostCreated::class,
        'updated' => PostUpdated::class,
        'deleted' => PostDeleted::class,
    ];

    public function broadcastOn($event)
    {
        return [$this, new PrivateChannel('user.'.$this->user_id)];
    }
}
```

### 118. How do you configure Laravel Echo with custom authentication?
```javascript
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/api/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                })
                .then(response => {
                    callback(false, response.data);
                })
                .catch(error => {
                    callback(true, error);
                });
            }
        };
    },
});
```

### 119. How do you handle broadcast events in Laravel with Redis?
```php
// .env
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

// config/broadcasting.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],

// Start queue worker
php artisan queue:work redis --queue=broadcasts

// Or use Horizon
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

### 120. How do you implement broadcast middleware?
```php
namespace App\Broadcasting;

class CustomChannelMiddleware
{
    public function handle($request, $next)
    {
        // Check rate limiting
        if ($this->tooManyAttempts($request->user())) {
            return response('Too many attempts', 429);
        }
        
        // Log channel access
        logger()->info('Channel access', [
            'user' => $request->user()->id,
            'channel' => $request->channel_name
        ]);
        
        return $next($request);
    }
}

// Apply middleware
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return true;
})->middleware(['auth', CustomChannelMiddleware::class]);
```

### 121. How do you test broadcasting in Laravel?
```php
use Illuminate\Support\Facades\Event;

class OrderTest extends TestCase
{
    public function test_order_shipped_event_is_broadcast()
    {
        Event::fake([OrderShipped::class]);
        
        $order = Order::factory()->create();
        $order->ship();
        
        Event::assertDispatched(OrderShipped::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }
    
    public function test_broadcast_channel_authentication()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'socket_id' => '123.456',
                'channel_name' => 'private-orders.' . $user->id,
            ])
            ->assertSuccessful();
    }
}
```

### 122. How do you implement conditional broadcasting?
```php
class OrderShipped implements ShouldBroadcast
{
    public $order;

    public function broadcastWhen()
    {
        return $this->order->value > 100;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('orders.' . $this->order->user_id);
    }
}
```

### 123. How do you broadcast to multiple channels?
```php
class SystemAlert implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return [
            new Channel('system.alerts'),
            new PrivateChannel('admin.alerts'),
            new PresenceChannel('emergency.team'),
        ];
    }
}
```

### 124. How do you implement Laravel Echo with Socket.io?
```javascript
import Echo from 'laravel-echo';
import io from 'socket.io-client';

window.io = io;

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001',
    auth: {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    }
});
```

```php
// config/broadcasting.php
'socket.io' => [
    'driver' => 'socket.io',
    'host' => env('SOCKET_IO_HOST', '127.0.0.1'),
    'port' => env('SOCKET_IO_PORT', 6001),
],
```

### 125. How do you handle broadcast errors in Laravel?
```php
use Illuminate\Broadcasting\BroadcastException;

try {
    event(new OrderShipped($order));
} catch (BroadcastException $e) {
    logger()->error('Broadcast failed', [
        'error' => $e->getMessage(),
        'order' => $order->id
    ]);
    
    // Fallback notification method
    $order->user->notify(new OrderShippedEmail($order));
}
```

### 126. How do you implement private chat with Laravel Echo?
```php
// Event
class MessageSent implements ShouldBroadcast
{
    public $message;

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->message->conversation_id);
    }
}

// Route
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return Conversation::where('id', $conversationId)
        ->where(function ($query) use ($user) {
            $query->where('user_one_id', $user->id)
                  ->orWhere('user_two_id', $user->id);
        })
        ->exists();
});
```

```javascript
Echo.private(`chat.${conversationId}`)
    .listen('MessageSent', (e) => {
        appendMessage(e.message);
    });
```

### 127. How do you implement online user tracking?
```php
Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
    ];
});
```

```javascript
const onlineUsers = [];

Echo.join('online')
    .here((users) => {
        onlineUsers.push(...users);
        updateOnlineUsersList(onlineUsers);
    })
    .joining((user) => {
        onlineUsers.push(user);
        updateOnlineUsersList(onlineUsers);
    })
    .leaving((user) => {
        const index = onlineUsers.findIndex(u => u.id === user.id);
        if (index !== -1) {
            onlineUsers.splice(index, 1);
        }
        updateOnlineUsersList(onlineUsers);
    });
```

### 128. How do you configure broadcast event name formatting?
```php
class OrderShipped implements ShouldBroadcast
{
    public function broadcastAs()
    {
        return 'order.shipped';
    }
}
```

```javascript
// Listen with custom event name
Echo.private('orders.1')
    .listen('.order.shipped', (e) => {
        console.log('Order shipped:', e);
    });
```

### 129. How do you implement broadcast retries?
```php
class OrderShipped implements ShouldBroadcast
{
    public $tries = 3;
    public $timeout = 30;
    public $retryAfter = 60;

    public function retryUntil()
    {
        return now()->addMinutes(5);
    }
}
```

### 130. How do you broadcast model changes automatically?
```php
use Illuminate\Broadcasting\PrivateChannel;

class Product extends Model
{
    protected static function booted()
    {
        static::updated(function ($product) {
            broadcast(new ProductUpdated($product))->toOthers();
        });
        
        static::deleted(function ($product) {
            broadcast(new ProductDeleted($product))->toOthers();
        });
    }
}
```

### 131. How do you implement Laravel Echo reconnection handling?
```javascript
window.Echo.connector.pusher.connection.bind('state_change', (states) => {
    if (states.current === 'connected') {
        console.log('Reconnected');
        resubscribeToChannels();
    }
    
    if (states.current === 'disconnected') {
        console.log('Disconnected');
    }
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('Connection error:', error);
});
```

### 132. How do you implement broadcast encryption?
```php
use Illuminate\Support\Facades\Crypt;

class SecureMessageSent implements ShouldBroadcast
{
    public $encryptedMessage;

    public function __construct($message)
    {
        $this->encryptedMessage = Crypt::encryptString($message);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('secure.chat');
    }
}
```

### 133. How do you implement broadcast with Laravel Horizon?
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

```php
// config/horizon.php
return [
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'broadcasts'],
                'balance' => 'auto',
                'processes' => 10,
                'tries' => 3,
            ],
        ],
    ],
];
```

### 134. How do you implement rate limiting for broadcasts?
```php
use Illuminate\Support\Facades\RateLimiter;

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    $key = 'broadcast-auth:' . $user->id;
    
    if (RateLimiter::tooManyAttempts($key, 10)) {
        return false;
    }
    
    RateLimiter::hit($key, 60);
    
    return $user->can('access-room', $roomId);
});
```

### 135. How do you implement broadcast with custom data?
```php
class OrderShipped implements ShouldBroadcast
{
    public $order;
    private $internalData;

    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'tracking_number' => $this->order->tracking_number,
            'estimated_delivery' => $this->order->estimated_delivery->toIso8601String(),
        ];
    }
}
```

### 136. How do you implement presence channel with user info?
```php
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    if ($user->canAccessRoom($roomId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url,
            'role' => $user->role,
            'status' => $user->status,
        ];
    }
});
```

### 137. How do you implement Laravel Echo with HTTPS?
```javascript
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,
    wsHost: window.location.hostname,
    wsPort: 6001,
    wssPort: 6001,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});
```

### 138. How do you implement broadcast debugging?
```php
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\BroadcastEvent;

Event::listen(BroadcastEvent::class, function ($event) {
    logger()->debug('Broadcasting event', [
        'event' => get_class($event->event),
        'channels' => $event->channels,
        'payload' => $event->payload,
    ]);
});
```

```javascript
Pusher.logToConsole = true;

window.Echo.connector.pusher.connection.bind('message', (event) => {
    console.log('Pusher message:', event);
});
```

### 139. How do you implement broadcast with ShouldBroadcastNow?
```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CriticalAlert implements ShouldBroadcastNow
{
    public $alert;

    public function broadcastOn()
    {
        return new Channel('alerts');
    }
}

// Broadcast immediately
event(new CriticalAlert($alert));
```

### 140. How do you implement channel leave handling?
```javascript
const channel = Echo.join('chat.1');

// Leave channel
channel.leave();

// Or
Echo.leave('chat.1');

// Leave all channels
Echo.leaveChannel('chat.1');
```

### 141. How do you implement broadcast with model binding?
```php
Broadcast::channel('post.{post}', function ($user, Post $post) {
    return $user->id === $post->user_id;
});

class PostUpdated implements ShouldBroadcast
{
    public $post;

    public function broadcastOn()
    {
        return new PrivateChannel('post.' . $this->post->id);
    }
}
```

### 142. How do you implement broadcast with Laravel Sanctum?
```php
// api.php
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});
```

```javascript
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json',
        }
    }
});
```

### 143. How do you implement broadcast event filtering?
```php
class OrderUpdated implements ShouldBroadcast
{
    public $order;
    public $changes;

    public function broadcastWhen()
    {
        return in_array('status', $this->changes) || 
               in_array('total', $this->changes);
    }

    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'changes' => array_intersect_key(
                $this->order->toArray(),
                array_flip($this->changes)
            ),
        ];
    }
}
```

### 144. How do you implement broadcast with toOthers()?
```php
broadcast(new OrderShipped($order))->toOthers();
```

```javascript
Echo.private('orders.1')
    .listen('OrderShipped', (e) => {
        // Won't trigger for the user who initiated broadcast
        updateOrderStatus(e.order_id);
    });
```

### 145. How do you implement broadcast with dynamic channels?
```php
class UserActivityStream implements ShouldBroadcast
{
    protected $userId;
    protected $activityType;

    public function broadcastOn()
    {
        return new PrivateChannel("user.{$this->userId}.{$this->activityType}");
    }
}
```

```javascript
function subscribeToUserActivity(userId, activityType) {
    Echo.private(`user.${userId}.${activityType}`)
        .listen('UserActivityStream', (e) => {
            console.log('Activity:', e);
        });
}
```

### 146. How do you implement broadcast channel caching?
```php
use Illuminate\Support\Facades\Cache;

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    $cacheKey = "room.access.{$user->id}.{$roomId}";
    
    return Cache::remember($cacheKey, 300, function () use ($user, $roomId) {
        return $user->canAccessRoom($roomId) ? [
            'id' => $user->id,
            'name' => $user->name,
        ] : false;
    });
});
```

### 147. How do you implement broadcast throttling?
```php
use Illuminate\Support\Facades\RateLimiter;

class OrderShipped implements ShouldBroadcast
{
    public function broadcastWhen()
    {
        $key = 'broadcast.order.' . $this->order->id;
        
        return RateLimiter::attempt(
            $key,
            $maxAttempts = 1,
            function() {
                return true;
            },
            $decaySeconds = 60
        );
    }
}
```

### 148. How do you implement Laravel Echo error handling?
```javascript
Echo.private('orders.1')
    .listen('OrderShipped', (e) => {
        console.log('Order shipped:', e);
    })
    .error((error) => {
        console.error('Channel error:', error);
        retryConnection();
    });

function retryConnection() {
    setTimeout(() => {
        Echo.private('orders.1').listen('OrderShipped', handler);
    }, 5000);
}
```

### 149. How do you implement broadcast logging?
```php
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\BroadcastEvent;

class BroadcastLogger
{
    public function handle(BroadcastEvent $event)
    {
        Log::info('Broadcasting event', [
            'event' => get_class($event->event),
            'channels' => $event->channels,
            'connections' => count($event->channels),
            'timestamp' => now(),
        ]);
    }
}

// Register in EventServiceProvider
protected $listen = [
    BroadcastEvent::class => [
        BroadcastLogger::class,
    ],
];
```

### 150. How do you implement broadcast with conditional channels?
```php
class OrderUpdate implements ShouldBroadcast
{
    public $order;

    public function broadcastOn()
    {
        $channels = [
            new PrivateChannel('user.' . $this->order->user_id),
        ];

        if ($this->order->total > 10000) {
            $channels[] = new PrivateChannel('admin.high-value-orders');
        }

        if ($this->order->requires_approval) {
            $channels[] = new PrivateChannel('manager.approvals');
        }

        return $channels;
    }
}
```

### 151. How do you implement Redis pub/sub for broadcasting?
```php
use Illuminate\Support\Facades\Redis;

// Publisher
Redis::publish('channel:name', json_encode([
    'event' => 'OrderShipped',
    'data' => ['order_id' => 123]
]));

// Subscriber
Redis::subscribe(['channel:name'], function ($message) {
    $data = json_decode($message, true);
    event(new OrderShipped($data));
});
```

### 152. How do you implement broadcast with channel groups?
```php
class TeamUpdate implements ShouldBroadcast
{
    public $team;

    public function broadcastOn()
    {
        $channels = [
            new PrivateChannel('team.' . $this->team->id),
        ];

        foreach ($this->team->members as $member) {
            $channels[] = new PrivateChannel('user.' . $member->id);
        }

        return $channels;
    }
}
```

### 153. How do you implement Laravel Echo event chaining?
```javascript
Echo.private('orders.1')
    .listen('OrderCreated', (e) => {
        console.log('Order created:', e.order_id);
    })
    .listen('OrderShipped', (e) => {
        console.log('Order shipped:', e.order_id);
    })
    .listen('OrderDelivered', (e) => {
        console.log('Order delivered:', e.order_id);
    });
```

### 154. How do you implement broadcast metrics collection?
```php
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\BroadcastEvent;

class BroadcastMetricsCollector
{
    protected $metrics = [];

    public function __construct()
    {
        Event::listen(BroadcastEvent::class, function ($event) {
            $this->recordMetric($event);
        });
    }

    protected function recordMetric($event)
    {
        $this->metrics[] = [
            'event' => get_class($event->event),
            'channels' => count($event->channels),
            'timestamp' => now(),
        ];
    }
}
```

### 155. How do you implement Laravel Echo with Vue 3?
```javascript
import { ref, onMounted, onUnmounted } from 'vue';

export function useEcho(channelName, eventName) {
    const data = ref(null);
    const error = ref(null);
    let channel = null;

    onMounted(() => {
        channel = window.Echo.private(channelName)
            .listen(eventName, (e) => {
                data.value = e;
            })
            .error((e) => {
                error.value = e;
            });
    });

    onUnmounted(() => {
        if (channel) {
            window.Echo.leave(channelName);
        }
    });

    return { data, error };
}
```

### 156. How do you implement broadcast with Laravel Livewire?
```php
use Livewire\Component;

class OrderStatus extends Component
{
    public $order;

    protected $listeners = [
        'echo:orders.{order.id},OrderShipped' => 'handleOrderShipped',
    ];

    public function handleOrderShipped($payload)
    {
        $this->order->refresh();
        $this->emit('orderUpdated', $this->order);
    }

    public function render()
    {
        return view('livewire.order-status');
    }
}
```

### 157. How do you implement broadcast event versioning?
```php
class OrderShippedV2 implements ShouldBroadcast
{
    public function broadcastAs()
    {
        return 'order.shipped.v2';
    }

    public function broadcastWith()
    {
        return [
            'version' => '2.0',
            'order' => $this->order,
            'metadata' => [
                'api_version' => '2.0',
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
```

### 158. How do you implement broadcast with connection pooling?
```php
use Illuminate\Support\Facades\Redis;

class RedisBroadcastPool
{
    protected $connections = [];
    protected $maxConnections = 10;

    public function getConnection()
    {
        if (count($this->connections) < $this->maxConnections) {
            $connection = Redis::connection('broadcast');
            $this->connections[] = $connection;
            return $connection;
        }

        return $this->connections[array_rand($this->connections)];
    }

    public function broadcast($channel, $event)
    {
        $connection = $this->getConnection();
        $connection->publish($channel, json_encode($event));
    }
}
```

### 159. How do you implement broadcast failover strategy?
```php
class BroadcastFailover
{
    protected $primaryDriver = 'pusher';
    protected $fallbackDriver = 'redis';

    public function broadcast($event)
    {
        try {
            Broadcast::connection($this->primaryDriver)->event($event);
        } catch (\Exception $e) {
            logger()->warning('Primary broadcast failed, using fallback');
            
            try {
                Broadcast::connection($this->fallbackDriver)->event($event);
            } catch (\Exception $e) {
                logger()->error('All broadcast methods failed');
                throw $e;
            }
        }
    }
}
```

### 160. How do you implement broadcast performance monitoring?
```php
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\BroadcastEvent;

class BroadcastPerformanceMonitor
{
    public function __construct()
    {
        Event::listen(BroadcastEvent::class, function ($event) {
            $startTime = microtime(true);
            
            register_shutdown_function(function() use ($startTime, $event) {
                $duration = (microtime(true) - $startTime) * 1000;
                
                if ($duration > 1000) {
                    logger()->warning('Slow broadcast detected', [
                        'event' => get_class($event->event),
                        'duration_ms' => $duration,
                    ]);
                }
            });
        });
    }
}
```

### 161. How do you implement broadcast with message compression?
```php
class CompressedBroadcast implements ShouldBroadcast
{
    public $compressedData;

    public function __construct($data)
    {
        $this->compressedData = base64_encode(gzcompress(json_encode($data)));
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->compressedData,
            'compressed' => true,
        ];
    }
}
```

### 162. How do you implement broadcast with custom payload transformation?
```php
class OrderShipped implements ShouldBroadcast
{
    public $order;

    public function broadcastWith()
    {
        return [
            'order' => $this->transformOrder($this->order),
            'timestamp' => now()->toIso8601String(),
            'event_id' => Str::uuid(),
        ];
    }

    protected function transformOrder($order)
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'total' => $order->formatted_total,
            'items_count' => $order->items->count(),
        ];
    }
}
```

### 163. How do you implement broadcast with message deduplication?
```php
class DeduplicatedBroadcast implements ShouldBroadcast
{
    public $uniqueId;

    public function __construct()
    {
        $this->uniqueId = Str::uuid();
    }

    public function broadcastWith()
    {
        return [
            'unique_id' => $this->uniqueId,
            'data' => $this->data,
            'timestamp' => now()->timestamp,
        ];
    }
}
```

```javascript
const receivedMessages = new Set();

Echo.channel('updates')
    .listen('DeduplicatedBroadcast', (e) => {
        if (receivedMessages.has(e.unique_id)) {
            return;
        }
        
        receivedMessages.add(e.unique_id);
        processMessage(e);
        
        setTimeout(() => {
            receivedMessages.delete(e.unique_id);
        }, 300000);
    });
```

### 164. How do you implement broadcast cleanup on shutdown?
```php
class BroadcastCleanup
{
    public function handle()
    {
        $pendingJobs = DB::table('jobs')
            ->where('queue', 'broadcasts')
            ->where('created_at', '<', now()->subHours(24))
            ->delete();
        
        logger()->info('Cleaned up broadcast jobs', [
            'count' => $pendingJobs,
        ]);
    }
}

// Schedule
protected function schedule(Schedule $schedule)
{
    $schedule->call(new BroadcastCleanup)->daily();
}
```

### 165. How do you implement broadcast with rate limiting per user?
```php
use Illuminate\Support\Facades\RateLimiter;

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    $key = 'channel-auth:' . $user->id . ':' . $roomId;
    
    if (RateLimiter::tooManyAttempts($key, 5)) {
        return false;
    }
    
    RateLimiter::hit($key, 60);
    
    return $user->canAccessRoom($roomId);
});
```

### 166. How do you implement broadcast health checks?
```php
class BroadcastHealthCheck
{
    public function check()
    {
        try {
            $testChannel = 'health-check-' . Str::random(8);
            broadcast(new TestBroadcast($testChannel));
            
            return [
                'status' => 'healthy',
                'driver' => config('broadcasting.default'),
                'timestamp' => now(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ];
        }
    }
}

Route::get('/health/broadcast', function () {
    return (new BroadcastHealthCheck())->check();
});
```

### 167. How do you implement broadcast with geographic routing?
```php
class OrderShipped implements ShouldBroadcast
{
    public function broadcastOn()
    {
        $region = $this->order->shipping_region;
        
        return [
            new PrivateChannel('orders.' . $this->order->user_id),
            new Channel('orders.region.' . $region),
        ];
    }

    public function broadcastVia()
    {
        return match($this->order->shipping_region) {
            'US', 'CA' => ['pusher-us'],
            'EU' => ['pusher-eu'],
            'ASIA' => ['pusher-asia'],
            default => ['pusher'],
        };
    }
}
```

### 168. How do you implement broadcast event batching?
```php
use Illuminate\Support\Facades\Bus;

class BatchBroadcaster
{
    public function broadcastBatch(array $events)
    {
        $jobs = collect($events)->map(function ($event) {
            return new BroadcastEvent($event);
        });

        Bus::batch($jobs)
            ->then(function () {
                logger()->info('All broadcasts completed');
            })
            ->catch(function (\Throwable $e) {
                logger()->error('Broadcast batch failed');
            })
            ->dispatch();
    }
}
```

### 169. How do you implement custom channel authorization?
```php
Broadcast::channel('custom.{resourceId}', function ($user, $resourceId) {
    $resource = Resource::find($resourceId);
    
    if (!$resource) return false;
    
    if ($resource->owner_id === $user->id) {
        return ['id' => $user->id, 'role' => 'owner'];
    }
    
    if ($resource->team->hasMember($user)) {
        return ['id' => $user->id, 'role' => 'member'];
    }
    
    if ($user->hasPermission('view-resource', $resource)) {
        return ['id' => $user->id, 'role' => 'viewer'];
    }
    
    return false;
});
```

### 170. How do you implement Laravel Echo with multiple connections?
```javascript
const echo1 = new Echo({
    broadcaster: 'pusher',
    key: 'key1',
    cluster: 'cluster1',
});

const echo2 = new Echo({
    broadcaster: 'pusher',
    key: 'key2',
    cluster: 'cluster2',
});

echo1.channel('channel1').listen('Event1', handler1);
echo2.channel('channel2').listen('Event2', handler2);
```

### 171. How do you implement broadcast with Laravel Queue priorities?
```php
class HighPriorityAlert implements ShouldBroadcast
{
    public $connection = 'redis';
    public $queue = 'high';
}

class NormalUpdate implements ShouldBroadcast
{
    public $connection = 'redis';
    public $queue = 'default';
}

// Start workers with priorities
// php artisan queue:work redis --queue=high,default
```

### 172. How do you implement Laravel Echo presence timeout?
```javascript
const presenceTimeouts = new Map();

Echo.join('chat.1')
    .joining((user) => {
        clearTimeout(presenceTimeouts.get(user.id));
        addUser(user);
    })
    .leaving((user) => {
        const timeout = setTimeout(() => {
            removeUser(user);
            presenceTimeouts.delete(user.id);
        }, 5000);
        
        presenceTimeouts.set(user.id, timeout);
    });
```

### 173. How do you implement broadcast with connection-specific data?
```php
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'sender' => $this->user,
            'socket_id' => $this->socket,
        ];
    }
}

// Client sends socket ID
axios.post('/messages', {
    message: 'Hello'
}, {
    headers: {
        'X-Socket-ID': Echo.socketId()
    }
});
```

### 174. How do you implement broadcast monitoring dashboard?
```php
class BroadcastDashboard
{
    public function getMetrics()
    {
        return [
            'total_broadcasts' => Cache::get('metrics:broadcasts:total', 0),
            'failed_broadcasts' => Cache::get('metrics:broadcasts:failed', 0),
            'average_latency' => Cache::get('metrics:broadcasts:latency', 0),
            'active_connections' => $this->getActiveConnections(),
        ];
    }

    protected function getActiveConnections()
    {
        // Implementation depends on driver
        return Redis::pubsub('numsub', 'laravel_database_*');
    }
}
```

### 175. How do you implement broadcast with Laravel Reverb?
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### 176. How do you implement user status updates with broadcasting?
```php
class UserStatusChanged implements ShouldBroadcast
{
    public $user;
    public $status;

    public function broadcastOn()
    {
        return [
            new PresenceChannel('online'),
            new PrivateChannel('user.' . $this->user->id),
        ];
    }
}
```

```javascript
Echo.join('online')
    .listen('UserStatusChanged', (e) => {
        updateUserStatus(e.user.id, e.status);
    });
```

### 177. How do you implement broadcast with Laravel Echo Server?
```bash
npm install -g laravel-echo-server
laravel-echo-server init
laravel-echo-server start
```

```json
{
  "authHost": "http://localhost",
  "authEndpoint": "/broadcasting/auth",
  "database": "redis",
  "databaseConfig": {
    "redis": {
      "port": "6379",
      "host": "localhost"
    }
  },
  "devMode": true,
  "port": "6001",
  "protocol": "http"
}
```

### 178. How do you implement broadcast connection monitoring?
```javascript
class EchoMonitor {
    constructor() {
        this.setupMonitoring();
    }

    setupMonitoring() {
        const pusher = window.Echo.connector.pusher;

        pusher.connection.bind('connecting', () => {
            this.updateConnectionStatus('connecting');
        });

        pusher.connection.bind('connected', () => {
            this.updateConnectionStatus('connected');
        });

        pusher.connection.bind('unavailable', () => {
            this.updateConnectionStatus('unavailable');
        });

        pusher.connection.bind('failed', () => {
            this.updateConnectionStatus('failed');
        });
    }

    updateConnectionStatus(status) {
        document.getElementById('connection-status').textContent = status;
    }
}

new EchoMonitor();
```

### 179. How do you implement broadcast with Laravel Reverb scaling?
```php
// config/reverb.php
return [
    'apps' => [
        [
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST'),
                'port' => env('REVERB_PORT'),
                'scheme' => env('REVERB_SCHEME', 'https'),
            ],
            'ping_interval' => env('REVERB_PING_INTERVAL', 30),
            'max_message_size' => 10000,
        ],
    ],
    
    'scaling' => [
        'enabled' => env('REVERB_SCALING_ENABLED', false),
        'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
    ],
];
```

### 180. How do you implement broadcast with custom serialization?
```php
class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total' => $this->total,
            'items' => OrderItemResource::collection($this->items),
        ];
    }
}

class OrderShipped implements ShouldBroadcast
{
    use SerializesModels;

    public $order;

    public function broadcastWith()
    {
        return [
            'order' => new OrderResource($this->order),
        ];
    }
}
```

### 181. How do you implement Redis connection pooling for broadcasts?
```php
use Illuminate\Support\Facades\Redis;

class RedisConnectionPool
{
    protected $pool = [];
    protected $maxSize = 10;

    public function getConnection()
    {
        if (count($this->pool) < $this->maxSize) {
            $connection = Redis::connection();
            $this->pool[] = $connection;
            return $connection;
        }

        return $this->pool[array_rand($this->pool)];
    }

    public function broadcast($channel, $message)
    {
        $connection = $this->getConnection();
        $connection->publish($channel, json_encode($message));
    }
}
```

### 182. How do you implement broadcast with Laravel Inertia.js?
```javascript
// resources/js/app.js
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
});

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
```

### 183. How do you implement broadcast with CORS configuration?
```php
// config/cors.php
return [
    'paths' => ['api/*', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'https://app.example.com'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 184. How do you implement broadcast with Laravel Folio?
```php
// resources/views/pages/dashboard.blade.php
<?php
use function Laravel\Folio\name;

name('dashboard');
?>

<div x-data="dashboard()">
    <div x-text="orderCount"></div>
</div>

@script
<script>
    Alpine.data('dashboard', () => ({
        orderCount: 0,
        
        init() {
            Echo.private('user.{{ auth()->id() }}')
                .listen('OrderCreated', (e) => {
                    this.orderCount++;
                });
        }
    }));
</script>
@endscript
```

### 185. How do you implement broadcast channel unsubscribe?
```javascript
const channel = Echo.private('orders.1');

// Stop listening to specific event
channel.stopListening('OrderShipped');

// Unsubscribe from all events
channel.unsubscribe();

// Leave channel completely
Echo.leave('orders.1');
```

### 186. How do you implement broadcast with Laravel Octane?
```php
// config/octane.php
return [
    'listeners' => [
        RequestTerminated::class => [
            FlushBroadcastEvents::class,
        ],
    ],
];

class FlushBroadcastEvents
{
    public function handle($event)
    {
        // Flush any queued broadcast events
    }
}
```

### 187. How do you implement broadcast with semantic versioning?
```php
class OrderShippedV1 implements ShouldBroadcast
{
    public function broadcastAs()
    {
        return 'order.shipped.v1';
    }
}

class OrderShippedV2 implements ShouldBroadcast
{
    public function broadcastAs()
    {
        return 'order.shipped.v2';
    }
}
```

```javascript
// Support both versions
Echo.private('orders.1')
    .listen('.order.shipped.v1', handleV1)
    .listen('.order.shipped.v2', handleV2);
```

### 188. How do you implement broadcast with message ordering?
```php
class SequencedBroadcast implements ShouldBroadcast
{
    public $sequence;
    public static $globalSequence = 0;

    public function __construct()
    {
        $this->sequence = ++self::$globalSequence;
    }

    public function broadcastWith()
    {
        return [
            'sequence' => $this->sequence,
            'data' => $this->data,
        ];
    }
}
```

### 189. How do you implement broadcast with connection draining?
```php
let $draining = false;

process.on('SIGTERM', () => {
    $draining = true;
    
    // Notify all connected clients
    broadcast(new ServerShutdown())->toOthers();
    
    // Give clients time to reconnect
    setTimeout(() => {
        process.exit(0);
    }, 10000);
});
```

### 190. How do you implement broadcast with webhook notifications?
```php
class BroadcastWebhookNotifier
{
    public function notify($event)
    {
        $webhooks = Webhook::where('event', get_class($event))->get();
        
        foreach ($webhooks as $webhook) {
            Http::post($webhook->url, [
                'event' => get_class($event),
                'data' => $event->broadcastWith(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }
}
```

### 191. How do you implement broadcast with Laravel Vapor?
```php
// vapor.yml
id: 12345
name: my-app
environments:
    production:
        memory: 1024
        timeout: 30
        queues:
            - broadcasts

// Use SQS for broadcasts
'sqs' => [
    'driver' => 'sqs',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'queue' => env('SQS_QUEUE', 'broadcasts'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
],
```

### 192. How do you implement broadcast with Laravel Telescope?
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

```php
// config/telescope.php
'watchers' => [
    Watchers\BroadcastWatcher::class => [
        'enabled' => env('TELESCOPE_BROADCAST_WATCHER', true),
    ],
],
```

### 193. How do you implement broadcast with Laravel Pail?
```bash
composer require laravel/pail --dev
php artisan pail --filter=broadcast
```

```php
use Illuminate\Support\Facades\Log;

Log::channel('broadcast')->info('Broadcasting event', [
    'event' => OrderShipped::class,
    'order_id' => $order->id,
]);
```

### 194. How do you implement broadcast with distributed rate limiting?
```php
use Illuminate\Support\Facades\Redis;

async function checkRateLimit($userId) {
    $key = "ratelimit:broadcast:{$userId}";
    $current = await Redis::incr($key);
    
    if ($current === 1) {
        await Redis::expire($key, 60);
    }
    
    return $current <= 100;
}
```

### 195. How do you implement broadcast message acknowledgment?
```php
class AcknowledgedBroadcast implements ShouldBroadcast
{
    public $messageId;

    public function __construct()
    {
        $this->messageId = Str::uuid();
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'data' => $this->data,
            'requires_ack' => true,
        ];
    }
}
```

```javascript
Echo.channel('updates')
    .listen('AcknowledgedBroadcast', (e) => {
        processMessage(e);
        
        axios.post('/broadcast/ack', {
            message_id: e.message_id
        });
    });
```

### 196. How do you implement broadcast with feature flags?
```php
use Laravel\Pennant\Feature;

class FeatureFlaggedBroadcast implements ShouldBroadcast
{
    public function broadcastWhen()
    {
        return Feature::active('realtime-notifications');
    }

    public function broadcastOn()
    {
        $channels = [new PrivateChannel('user.' . $this->user->id)];
        
        if (Feature::active('broadcast-to-dashboard')) {
            $channels[] = new Channel('dashboard');
        }
        
        return $channels;
    }
}
```

### 197. How do you implement broadcast with Laravel Pulse?
```php
use Laravel\Pulse\Facades\Pulse;

class BroadcastMetrics
{
    public function record(BroadcastEvent $event)
    {
        Pulse::record('broadcast', [
            'event' => get_class($event->event),
            'channels' => count($event->channels),
        ]);
    }
}
```

### 198. How do you implement broadcast with connection affinity?
```php
use Illuminate\Support\Str;

class ConnectionAffinity
{
    public function getServerForUser($userId)
    {
        $hash = crc32($userId);
        $servers = config('broadcast.servers');
        $index = $hash % count($servers);
        
        return $servers[$index];
    }
}
```

### 199. How do you implement broadcast with multi-tenancy?
```php
class TenantBroadcast implements ShouldBroadcast
{
    protected $tenantId;

    public function broadcastOn()
    {
        return new PrivateChannel("tenant.{$this->tenantId}.updates");
    }
}

Broadcast::channel('tenant.{tenantId}.updates', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});
```

### 200. How do you implement broadcast with request tracing?
```php
use Illuminate\Support\Str;

class TracedBroadcast implements ShouldBroadcast
{
    public $traceId;

    public function __construct()
    {
        $this->traceId = Str::uuid();
    }

    public function broadcastWith()
    {
        return [
            'trace_id' => $this->traceId,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

// Log trace
Log::info('Broadcast sent', [
    'trace_id' => $event->traceId,
    'event' => get_class($event),
]);
```
