# Bornado Notification Platform

Independent notification platform reference implementation for Bornado.

## Goals

- Keep all notification rules outside WordPress.
- Treat WordPress as an event producer, not a delivery engine.
- Support multiple channels and multiple providers without locking business logic to one vendor.
- Make future migration possible by preserving stable event contracts and a canonical contact model.

## Current scope

- HTTP ingestion endpoint: `POST /events`
- WhatsApp webhook endpoint: `GET|POST /webhooks/whatsapp`
- Service operations dashboard: `GET|POST /ops`
- File-backed outbox queue for asynchronous processing
- Delivery orchestration with policy-based channel routing
- Canonical contact resolution
- Idempotent event processing state
- Template rendering with placeholder replacement
- Real WhatsApp Cloud API adapter with template-mode support
- Outbound `wamid` tracking and webhook status correlation

## Layout

- `public/index.php`: ingest events, ops dashboard, and WhatsApp webhooks
- `bin/consume-outbox.php`: asynchronous worker
- `config/notification-platform.php`: routing, templates, provider mapping
- `src/`: contracts, domain model, orchestration, infrastructure
- `docs/`: ADRs, event catalog, template catalog, runbook, retry policy
- `openapi/`: ingestion API contract
- `schemas/`: versioned JSON schemas

## Quick start

1. Create `config/notification-platform.local.php` from `config/notification-platform.local.php.example`.
2. Fill in your real WhatsApp, webhook, and secret values there or inject them via env vars.
3. Deploy the `Services/bornado-notification-platform` folder to your live site.
4. Expose the ingestion, ops, and WhatsApp webhook entrypoints through your site root wrappers.
5. Run the worker on a schedule:

```bash
php bin/consume-outbox.php 20
```

For the simplest human-friendly entrypoint, see:

- `../../NOTIFICATION-SETUP.md`

## Production notes

- Replace the file queue with a durable broker when you move beyond the reference implementation.
- `whatsapp-cloud-api` is ready for real integration; configure it through env vars and approved templates.
- Keep provider secrets out of code and inject them from your runtime environment.
- The service dashboard is intentionally thin and operational; WordPress should remain producer-only except for bridge health checks.
- Incoming WhatsApp messages are stored in `log_only` mode until you define a concrete product flow for them.
