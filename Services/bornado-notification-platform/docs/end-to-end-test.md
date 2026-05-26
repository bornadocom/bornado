# End-To-End Test

## Goal

Verify the full `listing.published` flow:

1. WordPress detects the listing publication
2. Bridge posts the canonical event
3. Notification platform queues the event
4. Worker consumes the event
5. WhatsApp provider receives the request

## Minimal setup

1. Configure the bridge constants in WordPress:
   - `BORNADO_NOTIFICATION_INGEST_URL`
   - `BORNADO_NOTIFICATION_SHARED_SECRET`
   - `BORNADO_NOTIFICATION_SOURCE_SYSTEM`
2. Configure the notification platform environment from `.env.example`
3. Start the ingestion endpoint
4. Run the worker:

```bash
php bin/consume-outbox.php 20
```

## Manual event test

Use the sample payload from `examples/events/listing.published.sample.json`.

Send it to the ingestion API with the same shared secret used by WordPress.

## Verification points

- `storage/outbox/processed` should contain the accepted event after worker execution
- `storage/logs/delivery.jsonl` should contain:
  - `queued`
  - one or more attempt records
  - final `sent`, `failed`, or `no_route`
- If WhatsApp Cloud API is active, the provider response should appear in the log record

## Recommended rollout sequence

1. Start with `dry-run`
2. Switch to `whatsapp-cloud-api` in `text` mode for controlled validation
3. Move to `template` mode with approved templates
