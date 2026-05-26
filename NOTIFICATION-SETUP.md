# Notification Setup

This is the simplest place to start.

## What you need to edit

There are only 2 main config places you need to care about:

1. WordPress bridge config:
   - `My-Customization/bornado-notification-bridge/config/bornado-notification-bridge-config.php`
2. Notification service local config:
   - `Services/bornado-notification-platform/config/notification-platform.local.php`

Use the `.example` files beside them as your starting point.

## Simple endpoints added for you

If this code is deployed on your site, these are the easiest URLs:

- `https://bornado.com/notification-events.php`
- `https://bornado.com/notification-send-sample.php?key=YOUR_SHARED_SECRET&to=YOUR_VERIFIED_RECIPIENT`
- `https://bornado.com/notification-consume.php?key=YOUR_SHARED_SECRET`

## WordPress side

Set these values:

- `BORNADO_NOTIFICATION_INGEST_URL`
- `BORNADO_NOTIFICATION_SHARED_SECRET`
- `BORNADO_NOTIFICATION_SOURCE_SYSTEM`

## Notification service side

Set these values:

- `service.base_url`
- `service.shared_secret`
- `providers.whatsapp-cloud-api.phone_number_id`
- `providers.whatsapp-cloud-api.access_token`
- approved WhatsApp template names inside `template_map`

## Fastest real-world test

### Browser-style test after deployment

1. Queue one sample event:

```text
https://bornado.com/notification-send-sample.php?key=YOUR_SHARED_SECRET&to=YOUR_VERIFIED_RECIPIENT
```

2. Process the queue:

```text
https://bornado.com/notification-consume.php?key=YOUR_SHARED_SECRET
```

If the recipient number is allowed in the Meta test environment, this should send the test template.

## Fastest local test

1. Start the service:

```powershell
powershell -ExecutionPolicy Bypass -File "Services/bornado-notification-platform/bin/start-local.ps1"
```

2. Run the sample event:

```powershell
powershell -ExecutionPolicy Bypass -File "Services/bornado-notification-platform/bin/send-sample-event.ps1"
```

3. Process the queue:

```powershell
php "Services/bornado-notification-platform/bin/consume-outbox.php" 20
```

## When real messages will start working

Real WhatsApp messages will start working when:

- the service is running
- the bridge points to that service
- the shared secret matches on both sides
- the WhatsApp access token is real
- the phone number ID is real
- the WhatsApp templates are approved

## Current status of your credentials

- `Phone Number ID` was validated successfully
- `hello_world` template exists and is `APPROVED`
- template language is `en_US`

What is still missing for a real outbound message test:

- a recipient phone number that is allowed in your current Meta test setup
