# Event Catalog

## Envelope

Every event must include:

- `eventId`
- `eventType`
- `eventVersion`
- `occurredAt`
- `sourceSystem`
- `idempotencyKey`
- `locale`
- `payload`

## Event types

### `listing.published`

Used when a listing first transitions to `publish`.

Required payload fields:

- `payload.user.id`
- `payload.user.displayName`
- `payload.listing.id`
- `payload.listing.title`
- `payload.listing.manageUrl`

### `user.registered`

Used when a user account is created and ready for transactional onboarding.

Required payload fields:

- `payload.user.id`
- `payload.user.profileUrl`

### `listing.rejected`

Used when a listing is sent back for revision or moderation follow-up.

Required payload fields:

- `payload.listing.id`
- `payload.listing.title`
- `payload.listing.editUrl`

### `listing.expiring_soon`

Used before expiration so the user can renew or manage the listing.

Required payload fields:

- `payload.listing.id`
- `payload.listing.title`
- `payload.listing.manageUrl`

### `payment.completed`

Used when a payment transaction completes successfully.

Required payload fields:

- `payload.payment.orderId`

## Versioning rules

- Increment `eventVersion` only for backward-incompatible changes.
- Additive payload fields are allowed within the same version.
- Producers must not rename or remove existing fields without creating a new event version.
