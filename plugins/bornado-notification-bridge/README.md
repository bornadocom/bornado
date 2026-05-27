# Bornado Notification Bridge

WordPress bridge that emits canonical notification events to the standalone Bornado notification platform.

## Responsibilities

- Detect business events inside WordPress
- Build a CMS-agnostic event envelope
- Send the event to the ingestion API

## Non-responsibilities

- Choosing channels
- Selecting providers
- Rendering templates
- Handling retries and fallback policies

## Configuration

The easiest setup path is to create this file:

- `config/bornado-notification-bridge-config.php`

Start from:

- `config/bornado-notification-bridge-config.php.example`

That file should define these constants:

- `BORNADO_NOTIFICATION_INGEST_URL`
- `BORNADO_NOTIFICATION_SHARED_SECRET`
- `BORNADO_NOTIFICATION_SOURCE_SYSTEM`

Optional filters:

- `bornado_notification_bridge_ingest_url`
- `bornado_notification_bridge_shared_secret`
- `bornado_notification_bridge_source_system`
- `bornado_notification_bridge_timeout`
- `bornado_notification_bridge_blocking`
