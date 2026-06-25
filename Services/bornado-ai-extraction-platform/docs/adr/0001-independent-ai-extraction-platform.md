# ADR 0001: Independent AI Extraction Platform

## Status

Accepted

## Context

Bornado currently uses WordPress and AdForest, but AI extraction and prompt logic must survive:

- rapid growth in categories and fields
- strict production-safety requirements
- a future migration away from WordPress

## Decision

Create a standalone AI extraction platform inside `Services/` and keep WordPress as an adapter, not the primary home of prompt logic or schema logic.

## Consequences

- Canonical keys become the stable contract for AI instead of WordPress `term_id` values.
- Prompt generation, schema slicing, and resolution live outside WordPress.
- WordPress can be replaced later by writing a new resolver adapter.
- The first rollout can stay non-destructive because the service can run in parallel with the current live flow.
