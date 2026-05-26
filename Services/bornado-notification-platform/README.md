# Bornado Notification Platform

Independent notification platform reference implementation for Bornado.

## Goals

- Keep all notification rules outside WordPress.
- Treat WordPress as an event producer, not a delivery engine.
- Support multiple channels and multiple providers without locking business logic to one vendor.
- Make future migration possible by preserving stable event contracts and a canonical contact model.

## Current scope

- HTTP ingestion endpoint: `POST /events`
- File-backed outbox queue for asynchronous processing
- Delivery orchestration with policy-based channel routing
- Canonical contact resolution
- Idempotent event processing state
- Template rendering with placeholder replacement
- Dry-run provider adapter for safe integration and test environments
- Real WhatsApp Cloud API adapter with template-mode support

## Layout

- `public/index.php`: ingest events and queue them
- `bin/consume-outbox.php`: asynchronous worker
- `config/notification-platform.php`: routing, templates, provider mapping
- `src/`: contracts, domain model, orchestration, infrastructure
- `docs/`: ADRs, event catalog, template catalog, runbook, retry policy
- `examples/`: sample event payloads for integration tests
- `openapi/`: ingestion API contract
- `schemas/`: versioned JSON schemas

## Quick start

1. Create `config/notification-platform.local.php` from `config/notification-platform.local.php.example`.
2. Fill in your real WhatsApp and shared-secret values there.
3. Serve `public/index.php` behind your preferred web server, or use `bin/start-local.ps1`.
4. Point WordPress or any other producer to `POST /events`.
5. Run the worker on a schedule:

```bash
php bin/consume-outbox.php 20
```

For the simplest human-friendly entrypoint, see:

- `../../NOTIFICATION-SETUP.md`

## Production notes

- Replace the file queue with a durable broker when you move beyond the reference implementation.
- `whatsapp-cloud-api` is ready for real integration; configure it through env vars and approved templates.
- Keep `dry-run` as a safe fallback in non-production environments.
- Keep provider secrets out of code and inject them from your runtime environment.
