# ADR 0001: Independent Notification Platform

## Status

Accepted

## Context

Bornado currently runs on WordPress and AdForest, but notification workflows must survive a future backend migration.

## Decision

Create a standalone notification platform that consumes domain events over HTTP and processes them asynchronously.

## Consequences

- WordPress becomes only one producer among many.
- Notification logic, templates, routing, retries, and provider adapters live outside WordPress.
- New producers can be added later without redesigning delivery logic.
