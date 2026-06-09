# Rendering Strategy

## Current Rendering Model
The current listing surface is hybrid:

- first render: server-rendered HTML
- additional interaction: AJAX / query-driven refresh
- infinite scroll / load more: UX enhancement on top of server-rendered result batches

## What Stays SSR Right Now
- page 1 listing HTML
- semantic route ownership
- canonical and robots decisions
- search card markup

## What Is Client Enhancement Right Now
- filter interactions
- load more / infinite scroll
- URL state sync via History API
- non-structural UX fixes in child-theme JS

## Why This Matters
The current crawl-safe strategy depends on HTML existing without requiring the crawler to scroll.

## Target Rendering Model
The long-term model is:

1. independent listing API
2. SSR frontend consuming that API
3. progressive enhancement for long-scroll UX

## WordPress-Phase Rules
- WordPress keeps rendering until the independent frontend is ready.
- Child theme remains a temporary presentation layer.
- Listing data contract should move to plugin/runtime-neutral code before markup moves.

## Migration Trigger
WordPress rendering remains acceptable only while:
- `INP` stays within budget
- scroll performance remains stable
- DOM growth remains controlled
- memory usage stays acceptable on mid-range devices

Once those conditions fail, the frontend migration is no longer optional.
