# Notification Setup

This file now reflects the live setup only.

## Files to upload

Upload these items to the live site root, next to `wp-config.php`:

- `Services/`
- `notification-events.php`
- `notification-consume.php`
- `notification-admin.php`
- `notification-webhook-whatsapp.php`

Upload this plugin folder into `wp-content/plugins/`:

- `My-Customization/bornado-notification-bridge/`

Do not upload the deleted manual test files.

## Files you edit

There are only 2 config files you need to care about:

1. WordPress bridge config:
   - `My-Customization/bornado-notification-bridge/config/bornado-notification-bridge-config.php`
2. Notification service live config:
   - `Services/bornado-notification-platform/config/notification-platform.local.php`

## WordPress side

These values must be set:

- `BORNADO_NOTIFICATION_INGEST_URL`
- `BORNADO_NOTIFICATION_SHARED_SECRET`
- `BORNADO_NOTIFICATION_OPS_URL`
- `BORNADO_NOTIFICATION_OPS_KEY`
- `BORNADO_NOTIFICATION_SOURCE_SYSTEM`

## Notification service side

These values must be set:

- `service.base_url`
- `service.shared_secret`
- `service.ops_key`
- `providers.whatsapp-cloud-api.phone_number_id`
- `providers.whatsapp-cloud-api.access_token`
- approved WhatsApp template names inside `template_map`
- `webhooks.whatsapp.verify_token`
- `webhooks.whatsapp.app_secret`

## Live endpoints

- Event ingestion:
  - `https://bornado.com/notification-events.php`
- Queue consumer:
  - `https://bornado.com/notification-consume.php?key=YOUR_OPS_KEY`
- Service operations dashboard:
  - `https://bornado.com/notification-admin.php?key=YOUR_OPS_KEY`
- WhatsApp webhook callback:
  - `https://bornado.com/notification-webhook-whatsapp.php`

## How the live flow works

1. A listing changes to `publish`
2. The WordPress bridge sends an event to `notification-events.php`
3. The event is written into the queue
4. `notification-consume.php` processes the queue
5. WhatsApp delivery is attempted with the approved live template
6. Meta posts delivery statuses and inbound messages to `notification-webhook-whatsapp.php`

## Cron

Set a cron job to call this URL every minute:

```text
https://bornado.com/notification-consume.php?key=YOUR_OPS_KEY
```

If your hosting panel supports HTTP cron, use that URL directly.

If your hosting panel only supports command cron, use a command like this:

```bash
curl -fsS "https://bornado.com/notification-consume.php?key=YOUR_OPS_KEY" >/dev/null
```

## Manual real test after deployment

1. Publish a real listing on the site
2. Run the consumer URL once
3. Check that WhatsApp received the approved template message
4. Open the ops dashboard and verify the outbound `wamid`
5. Confirm webhook verification and status updates in Meta

## Current live behavior

- `listing.published` is active and routes only to WhatsApp
- fallback test channels are disabled for live listing publication
- inbound WhatsApp handling is currently `log_only` until a product flow is defined
