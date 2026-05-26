# WhatsApp Cloud API Integration

## Recommended production mode

For production, use `template` mode with approved WhatsApp templates. This is the correct path for business-initiated transactional notifications such as:

- listing published
- user registered
- listing rejected
- listing expiring soon
- payment completed

## Required environment variables

- `BORNADO_NOTIFICATION_WHATSAPP_PROVIDERS=whatsapp-cloud-api`
- `BORNADO_WA_ENABLED=true`
- `BORNADO_WA_PHONE_NUMBER_ID=...`
- `BORNADO_WA_ACCESS_TOKEN=...`
- `BORNADO_WA_MESSAGE_MODE=template`
- `BORNADO_WA_TEMPLATE_LANGUAGE=fa`

Template names:

- `BORNADO_WA_TEMPLATE_LISTING_PUBLISHED`
- `BORNADO_WA_TEMPLATE_USER_REGISTERED`
- `BORNADO_WA_TEMPLATE_LISTING_REJECTED`
- `BORNADO_WA_TEMPLATE_LISTING_EXPIRING`
- `BORNADO_WA_TEMPLATE_PAYMENT_COMPLETED`

## Template parameter mapping

### `listing.published`

Body parameters:

1. `payload.listing.title`
2. `payload.listing.manageUrl`

### `user.registered`

Body parameters:

1. `payload.user.profileUrl`

### `listing.rejected`

Body parameters:

1. `payload.listing.title`
2. `payload.listing.editUrl`

## Test mode

If you want a non-production test path, you can use:

- `BORNADO_WA_MESSAGE_MODE=text`

Or:

- keep `template` mode
- enable `BORNADO_WA_TEXT_FALLBACK_ENABLED=true`

Text mode is useful for controlled testing, but template mode is the correct long-term production path.
