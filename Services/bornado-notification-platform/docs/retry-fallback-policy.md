# Retry And Fallback Policy

## Routing order

Default routing is channel-first:

1. WhatsApp
2. SMS
3. Email

## Provider failover

- Try providers in configured order for the current channel.
- If one provider fails, try the next provider for the same channel.
- If no provider succeeds, move to the next eligible channel.

## Retry policy

- In the reference implementation, retries are driven by rerunning the worker after moving failed events back into the pending queue.
- In production, use exponential backoff and a dead-letter queue.

## Non-blocking business flows

- Event producers must never block the listing publication flow because of delivery failure.
- If no channel is available, mark the event as `no_route` and rely on logs/monitoring.
