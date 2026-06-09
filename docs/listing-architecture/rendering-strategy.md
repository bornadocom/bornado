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
- native theme-driven infinite scroll / AJAX loading plus child-theme DOM windowing
- URL state sync via History API where already provided by the existing search runtime
- non-structural UX fixes in child-theme JS
- HTML batch rehydration for previously removed off-screen result groups

Current practical note:
- this pass focused on DOM control first, not scroll-driven URL replacement
- changing the visible URL during scroll is treated as a separate UX decision and should be added only if it improves refresh/share/navigation behavior without becoming distracting

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
- Infinite-scroll UX is acceptable only while DOM growth is bounded by batch windowing.
- `content-visibility` is a helper for live off-screen batches, not a substitute for DOM removal.
- Crawlable pagination links remain the public source of truth for URL ownership.
- During the WordPress phase, prefer keeping the theme's proven loading engine and layering DOM control on top of it instead of replacing loading logic prematurely.

## Migration Trigger
WordPress rendering remains acceptable only while:
- `INP` stays within budget
- scroll performance remains stable
- DOM growth remains controlled
- memory usage stays acceptable on mid-range devices

Once those conditions fail, the frontend migration is no longer optional.
