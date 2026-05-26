# Template Catalog

## Principles

- Templates are owned by the notification platform, not by WordPress.
- Templates are versioned with the event contract.
- Channel-specific copy lives separately from routing rules.

## Current templates

### `listing.published`

- `whatsapp`: concise transactional notification with a management link
- `sms`: short version of the same message
- `email`: richer message with edit and management URLs

### `user.registered`

- welcome/onboarding message with profile URL

### `listing.rejected`

- moderation follow-up with edit URL

### `listing.expiring_soon`

- reminder message with manage URL

### `payment.completed`

- confirmation message with order reference

## Placeholder syntax

Use `{{path.to.field}}`, for example:

- `{{payload.user.displayName}}`
- `{{payload.listing.title}}`
- `{{payload.listing.manageUrl}}`
