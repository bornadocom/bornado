# ADR 0002: WordPress Is a Producer Only

## Status

Accepted

## Context

Publishing logic inside WordPress currently spans UI, admin actions, and payment completion flows.

## Decision

Use WordPress only to detect business events and emit canonical event payloads. Do not place channel, provider, template, or fallback logic inside WordPress.

## Consequences

- WordPress upgrades or theme changes have lower impact on notification behavior.
- Event contracts stay stable even if the underlying CMS changes.
- Delivery features can evolve independently from AdForest.
