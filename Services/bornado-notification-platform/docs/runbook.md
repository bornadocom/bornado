# Runbook

## Normal flow

1. Producer sends `POST /events`.
2. Platform validates the envelope.
3. Event is written to the outbox queue.
4. Worker processes queued events.
5. Delivery attempts and final state are written to `storage/logs/delivery.jsonl`.

## If ingestion fails

- Check `X-Bornado-Signature` and shared secret.
- Check JSON validity.
- Check required envelope and payload fields.

## If events stay queued

- Confirm the worker is running.
- Check file permissions for `storage/outbox`.
- Inspect `storage/outbox/failed` for malformed events.

## If delivery never reaches WhatsApp

- Confirm the provider adapter for WhatsApp is configured.
- Confirm the contact model exposes a WhatsApp-capable destination.
- Confirm fallback policy is not skipping WhatsApp because capability was explicitly set to `false`.

## If duplicate sends happen

- Check that the producer is generating a stable `idempotencyKey`.
- Check that the same `eventId` is not being regenerated for distinct business events.
- Confirm the state directory is writable.
