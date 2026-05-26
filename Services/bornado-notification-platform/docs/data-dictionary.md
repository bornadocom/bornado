# Data Dictionary

## Canonical contact model

### `payload.user.contacts[]`

- `channel`: one of `whatsapp`, `sms`, `email`, `push`, `in_app`
- `address`: destination identifier for the channel
- `verified`: whether the destination has been verified
- `primary`: whether this is the preferred method for the channel
- `priority`: lower values are tried first
- `capabilities`: channel-specific capability flags

### `payload.user.channelCapabilities`

Optional capability hints from the producer:

- `whatsapp`: `true`, `false`, or omitted
- `sms`: `true`, `false`, or omitted
- `email`: `true`, `false`, or omitted

Omitted values are treated as `unknown`. Policy can still route optimistically and rely on the provider response to decide fallback.

## Delivery state

### Event-level status

- `queued`
- `sent`
- `failed`
- `invalid`
- `duplicate`
- `no_route`

### Attempt-level status

- `sent`
- `failed`
- `skipped`

## Listing links

- `payload.listing.editUrl`: direct edit path when available
- `payload.listing.manageUrl`: stable dashboard URL where edit/delete actions are available
- `payload.listing.deleteUrl`: optional direct delete URL; may intentionally point to the manage page when destructive direct links are disabled
