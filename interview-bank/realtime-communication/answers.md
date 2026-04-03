# Realtime Communication - Complete Answers

1. WebSockets maintain persistent, bidirectional connection between client and server. Unlike HTTP (request/response), WebSocket allows server to push data to client anytime. Enable with `ws://` protocol, requires server supporting WebSocket upgrade.

2. Server-Sent Events (SSE): unidirectional communication from server to client. Browser opens persistent HTTP connection, server sends events with `data:` format. Simpler than WebSocket, no need for custom server.

3. Laravel Broadcasting: abstraction layer for real-time events. Drivers: Pusher (third-party service), Redis (for self-hosted), Log (for testing). Define events as broadcastable, queue workers handle distribution.

4. Redis pub/sub: publish messages to channels, subscribers receive instantly. Useful for horizontal scaling - multiple servers subscribe to same Redis instance for shared events.

5. Real-time sync strategies: optimistic updates (update UI immediately, sync server async), pessimistic updates (wait for server confirmation), eventual consistency (multiple sources of truth).

6. Connection management: track active connections, handle disconnections gracefully, reconnection with exponential backoff, clean up stale connections periodically.

7. WebSocket security: validate authentication before accepting connection, authorize subscribers, validate message content, rate limit connections. Use HTTPS/WSS (WebSocket Secure) in production.

8. Broadcast events: Laravel events tagged with `ShouldBroadcast` interface. Define `broadcastOn()` channel name, `broadcastWith()` payload. Dispatched with `broadcast($event)`.

9. Private channels: only authenticated users can listen. `broadcast-app-key` required in request. Authorization checked against `broadcast()` policies. Syntax: `private-user.{$userId}`.

10. Presence channels: know who's listening. `presence-room.{$roomId}`. Laravel tracks joined/left users. Access with `$presence->users()` on client. Useful for "user online" indicators.

11. Direct messaging: broadcast to specific user. Create private channel for user: `private-chat.{$userId}`. Only recipient can subscribe. Alternative: one-to-one channel with hash of both user IDs.

12. Broadcast data size: keep payloads small - only necessary data. Compress JSON if large. Avoid sending entire models - send IDs, fetch data on client if needed.

13. Broadcast authorization: define policies in `channels.php`. Example: `Broadcast::channel('private-order.{orderId}', function($user, $orderId) { return $user->owns($orderId); });`

14. Queue workers for broadcast: `php artisan queue:work` processes queued broadcast events. Separate workers from request handlers. Multiple workers for scalability.

15. Broadcast testing: use fake broadcaster in tests. `Broadcast::expectsToBeRouted()->toBroadcasted();` validates event broadcasted. Mock connections, assert data.

16. Reconnection handling: client-side exponential backoff - wait 1s, 2s, 4s, 8s before retry. Server validates reconnection, restores user context. Prevent duplicate events from reconnects.

17. Offline handling: queue messages when client offline, send on reconnection. Persistence: store in database or Redis. Trade-off: complexity vs completeness.

18. Browser compatibility: WebSocket supported in modern browsers (IE 10+). For older browsers, fallback to polling (repeated HTTP requests). Libraries like Socket.io handle fallback automatically.

19. Load balancing: sticky sessions required - user's connection routes to same server. Load balancer must preserve session. Alternative: shared state in Redis (session data, broadcast routing).

20. Broadcast performance: monitor connection count, memory per connection, message throughput. Scale horizontally - add servers, load balance connections, share state in Redis.

21. Pusher alternative: self-hosted with Socket.io and Node.js. Lower cost, full control, requires DevOps overhead. Or use Redis-based broadcasting for simpler setup.

22. Chat implementation: create one-to-one or group channels, broadcast messages as events, render in real-time on client, show typing indicators, delivery confirmation.

23. Notification system: user subscribes to notification channel, app broadcasts notifications as they're generated. Priority levels, delivery guarantees, read receipts.

24. Multiplayer games: game state broadcast to all players, client sends actions, server validates and broadcasts results. Latency critical, optimize payload size and frequency.

25. Live updates: product price changes, inventory updates, analytics dashboards. Broadcast changes to interested clients, refresh data in real-time.

26. Heartbeat mechanism: client sends periodic ping, server responds pong. Detects stale connections, prevents proxy/firewall from dropping idle connection. Configure interval based on infrastructure.

27. Message ordering: guarantee messages delivered in order for important data. Use sequence numbers, client-side buffering. For non-critical updates, order doesn't matter.

28. Bandwidth optimization: throttle updates, aggregate multiple updates into single broadcast, use compression, delta updates (only changed fields).

29. Fallback protocols: WebSocket → SSE → Long polling → Short polling. Browser auto-falls back if WebSocket fails. Library handles transparently (Socket.io includes fallbacks).

30. Debugging real-time: browser DevTools show WebSocket frames. Server logs show broadcast events. Monitor Redis with `redis-cli MONITOR`. Trace message flow end-to-end.

31. Broadcast middleware: transform payload before broadcasting, add timestamps, user context, trace IDs. Example: add request ID to all broadcasts for tracing.

32. Rate limiting broadcasts: prevent spam, limit message frequency per user/channel. Database or Redis store message count, increment and check on broadcast.

33. Broadcast validation: validate message structure, sanitize content, check authorization. Server doesn't blindly broadcast client-sent data.

34. Event sourcing + real-time: broadcast domain events, clients subscribe to event stream. Replays available for new subscribers. Audit trail maintained automatically.

35. Presence online status: broadcast user online/offline events, frontend shows presence indicators. Heartbeat detects disconnections. Alternative: simple online column in users table.

36. Activity streams: user actions (like, comment, follow) broadcast to followers. Pagination challenges with real-time updates. Solutions: cursor-based, separate chronological array.

37. Live forms validation: as user types, broadcast validation messages in real-time. Show field-level errors without form submission. Debounce client-side to reduce messages.

38. Collaborative editing: multiple users editing document simultaneously. Operational transformation or CRDT techniques handle concurrent edits. Conflict resolution strategies.

39. Real-time notifications UI: toast notifications for events, sound/vibration alerts (with permission), unread count badges, notification center with history.

40. Broadcast security headers: validate origin, prevent CSRF, restrict broadcast to authenticated users only, use HTTPS/WSS exclusively in production.

41. Connection pooling for broadcasts: pool connections to external services (Pusher), reuse connections, backoff on failures, circuit breaker for service failures.

42. Broadcast delivery guarantees: at-most-once (fire and forget, fast), at-least-once (with retries, may have duplicates), exactly-once (complex, usually not needed for real-time).

43. Long polling fallback: client repeatedly requests updates. Set long timeout to simulate real-time. Less efficient than WebSocket, better than short polling. Useful when WebSocket unavailable.

44. Server-Sent Events implementation: response with `Content-Type: text/event-stream`, send events with `id:`, `event:`, `data:` lines, client auto-reconnects on disconnect.

45. Real-time search: user types search query, broadcast query to search service, results broadcast back to user. Debounce to limit queries, cache common searches.

46. Video streaming over WebSocket: not recommended for large media. Use HTTP streaming (chunked response) for video. WebSocket better for control messages (play, pause, seek).

47. Broadcast to multiple channels: `broadcastOn(['channel1', 'channel2'])` in event. Useful for notifying multiple groups of users of important event.

48. Redis connection pooling: use PRedis or Predis library for pooling. Share connections across multiple broadcast operations, reduce connection overhead.

49. Message queuing for broadcasts: use queue system to batch broadcast messages, send at intervals, deduplicate, ensure delivery. Reliable but adds latency.

50. Real-time collaboration features: shared cursors, live selection highlighting, collaborative whiteboard. Requires broadcasting mouse positions, selection state, drawing operations.

51. Broadcast caching: cache event payloads if broadcasting to many subscribers. Serialize once, send to multiple clients. Invalidate when data changes.

52. Event replay: store broadcast events, replay to new subscribers. Useful for late joiners in chat, collaborating documents. Trade-off: storage, replay performance.

53. Sub-channels: hierarchical channels like `notifications.user.{userId}.order` for notifications nested by type and user. Allows fine-grained subscriptions.

54. Broadcast filtering: recipient filters events before processing. Example: user only processes notifications for their own orders. Server-side or client-side filtering.

55. Real-time dashboard: broadcast metrics, analytics updates. Use aggregation on backend to reduce message volume. Client-side charting libraries (Chart.js) update on data arrival.

56. Audio/video signaling: WebSocket for signaling (offer, answer, ICE candidates), WebRTC for actual audio/video. Separate protocol channels, WebSocket just coordinates.

57. Screen sharing: WebRTC data channels for screen capture stream. High bandwidth required, compression necessary. Fallback to scheduled uploads for limited bandwidth.

58. Real-time alerting: critical events (system down, traffic spike) broadcast to admin dashboard. Audio alert, visual indicator, persistent notification. Integration with monitoring tools.

59. Broadcast scheduling: schedule event broadcasts for future time. Broadcast service stores and sends at scheduled time. Useful for scheduled notifications, announcements.

60. Multi-region broadcasting: broadcast to geographically distributed subscribers. Use CDN-like distribution, region-specific channels, minimize latency for critical updates.

61. Broadcast authentication token expiry: token expires after time. Client must refresh before expiry. Server validates token on each broadcast attempt, rejects expired.

62. Broadcast error handling: network errors, server errors, broadcast failures. Retry logic on client, fallback to polling if broadcasts unreliable. Notify user of connection status.

63. Broadcast memory management: connections consume memory, scale limits around 10K connections per server. Monitor memory, disconnect idle, load balance across servers.

64. Real-time file uploads: progress broadcast to user, show percentage complete. Use chunks for large files, broadcast progress per chunk. Pause/resume capability.

65. Live leaderboards: broadcast score updates, real-time ranking changes. Frequent updates, aggregate scores, broadcast top N periodically instead of all changes.

66. Broadcast compression: gzip event payloads for large broadcasts. Reduce bandwidth, increase latency slightly. Enable for large payload broadcasts.

67. Broadcast versioning: event structure changes over time, support multiple versions. Client-side filtering, graceful degradation for unknown event types.

68. Real-time search autocomplete: broadcast matching results as user types. Debounce queries, cache results, broadcast within latency budget.

69. Broadcast retry with exponential backoff: failed broadcasts retry with increasing delay. Max retries before giving up. Useful for transient failures.

70. Broadcast dead letter queue: failed broadcasts after retries stored for analysis. Manual reprocessing if needed, audit trail of delivery failures.

71. Real-time collaboration: operational transformation, CRDT, or eventual consistency models. Broadcast each operation, clients apply operations to converge on same state.

72. Presence features: show who's online, typing indicator (broadcast "user typing" event), last seen timestamp, idle detection.

73. Broadcast logging: log all broadcasts for debugging, compliance, audit. Structure logs with broadcast ID, channel, user, timestamp, payload size.

74. Broadcast metrics: message count, latency, failure rate, connection count. Integrate with APM (New Relic, Datadog) for visibility.

75. Broadcast rate limiting by type: some message types high frequency (cursor position), others low (notifications). Different limits per message type.

76. Real-time stock ticker: broadcast price updates, show percentage change, highlight price movements. Update frequency based on price change threshold.

77. Broadcast TLS/SSL: use WSS (WebSocket Secure) for encrypted communication. Client verifies server certificate, prevents man-in-the-middle attacks.

78. Broadcast origin validation: verify broadcast came from expected source (authenticated user), prevent unauthorized broadcasts from spoofed sources.

79. Real-time user behavior analytics: broadcast user actions (page view, button click), analytics service processes in real-time. Important for fraud detection.

80. Broadcast deduplication: prevent sending same message twice due to retries/replays. Use message ID, recipient tracks received IDs, drops duplicates.

81. Broadcast prioritization: high-priority events (alerts) sent immediately, low-priority (metrics) batched. Queue system prioritizes, ensures important messages delivered first.

82. Real-time inventory: broadcast stock level changes, clients update inventory display. Handle race conditions (multiple purchases), use database as source of truth.

83. Broadcast fallback to email: critical broadcasts also sent via email if real-time fails. User ensures receive important notification even if connection lost.

84. Broadcast batching window: collect events for short time (1 second), send batch instead of individual. Reduces message volume, increases latency slightly.

85. Real-time collaboration with conflict resolution: when two users edit same field, define resolution strategy (last write wins, merge, manual conflict).

86. Broadcast to external systems: send broadcast payload to webhooks, external APIs. Decouple internal broadcasts from external integrations.

87. Real-time feature flags: broadcast feature flag changes, clients update behavior immediately. No deploy needed for toggling features.

88. Broadcast circuit breaker: if broadcast service down, stop sending, return quickly. Auto-recover when service back up. Prevent cascading failures.

89. Real-time recommendations: broadcast personalized recommendations as they're generated, user sees fresh suggestions without refresh.

90. Broadcast subscriber count: broadcast channel subscriber count, show "X people watching". Useful for FOMO, engagement metrics, fraud detection (abnormal count).

91. Real-time geolocation: broadcast location updates for delivery tracking, rideshare apps. Track movements on map in real-time.

92. Broadcast data encryption: encrypt sensitive broadcast data, decrypt on client. End-to-end encryption prevents server from reading message content.

93. Real-time social features: broadcast like/comment notifications, show live comment stream, broadcast follow events.

94. Broadcast audience segmentation: broadcast only to users matching criteria (location, device, subscription level). Server-side filtering before send.

95. Real-time SLA monitoring: broadcast SLA violations, alerting system responds immediately. Notify support team of incidents.

96. Broadcast idempotency: same message sent twice shouldn't cause duplicate state changes. Recipient deduplicates by message ID.

97. Real-time form validation: broadcast validation results as user fills form, show real-time feedback. Improves UX, reduces form errors.

98. Broadcast content negotiation: client specifies format (JSON, MessagePack), server sends in client-preferred format. Reduces payload size.

99. Real-time accessibility: broadcasts include alt text, captions for media. Ensure real-time features accessible to all users.

100. Real-time system health: broadcast system metrics (CPU, memory, response time) to admin dashboard, enable rapid incident response.

