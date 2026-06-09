# Listing Architecture Overview

## Purpose
This directory is the source of truth for the Bornado listing/search architecture work.

It documents:

- what exists today
- what changed in this implementation pass
- what remains temporary inside WordPress
- what the target independent architecture looks like

## Current State
The current listing experience is still rendered through WordPress + AdForest, but key infrastructure already exists for a cleaner future state:

- semantic routing lives in `plugins/bornado-routing/`
- shared search state/helpers live in `plugins/bornado-search-core/`
- search card rendering and UX overrides live in `adforest-child/`

Important practical point:

- listing HTML is currently server-rendered
- infinite scroll / load more are UX layers on top of server-rendered results
- the new independent API contract now lives in `plugins/bornado-search-core/`

## Target State
The target architecture is:

1. crawlable paginated listing URLs with stable canonicals
2. infinite scroll as progressive enhancement only
3. plugin-owned listing data contract independent from theme markup
4. gradual migration from WordPress runtime to an SSR frontend

## Files In This Directory
- `decision-log.md`: key architectural decisions and rejected alternatives
- `architecture-current-vs-target.md`: visual and textual current-vs-target architecture snapshot
- `url-and-indexing-policy.md`: canonical, pagination, filter, sort, and crawl policy
- `listing-api-contract.md`: public API contract for listing data
- `headless-migration-blueprint.md`: route-by-route migration plan to the independent frontend
- `phase1-runbook.md`: deployment and validation checklist for this implementation phase
- `rendering-strategy.md`: what remains SSR, what is client enhancement, migration triggers
- `performance-baseline-and-budgets.md`: current baseline, budgets, and exit triggers
- `migration-ledger.md`: what is still temporary vs extracted
- `implementation-journal.md`: this implementation pass summary

## Scope Boundary
This documentation covers the listing/search surface only.

It does not redefine:

- single-ad layout architecture
- dashboard architecture
- notifications architecture

unless they directly affect listing/search behavior.
