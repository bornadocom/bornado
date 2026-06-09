# Performance Baseline and Budgets

## Purpose
This file records the performance guardrails for listing/search work.

## Current Qualitative Baseline
Current strengths:
- first-batch HTML is server-rendered
- search-card LCP prioritization already exists for the first visible card
- several unnecessary assets are already removed on search pages

Current risks:
- repeated full server-side card rendering
- DOM growth during long-scroll sessions
- memory growth during append-heavy infinite scroll usage
- WordPress/theme-owned search logic still exists in multiple places

## Target Budgets
- `LCP <= 2.5s`
- `CLS <= 0.1`
- `INP <= 200ms`

## DOM Budget
The listing surface must not allow unbounded DOM growth during long-scroll usage.

Operational rule:
- the temporary WordPress phase may keep infinite-scroll UX only if retained DOM is bounded by runtime windowing
- the temporary WordPress phase should retain at most `3` mounted result batches at one time (`current + 1 before + 1 after`)
- the temporary WordPress phase should treat `4+` simultaneously mounted result batches as a regression
- retained cards should generally stay within roughly `page_size * 3` for the active session window
- the independent frontend phase must use stronger DOM control, including virtualization/windowing where needed

## Runtime Guardrails
Phase 1.5 introduces a windowed listing controller from the child-theme layer.

Expected behavior:
- page 1 remains SSR and crawlable
- additional pages load automatically from crawlable pagination URLs
- distant result batches are replaced by measured spacers
- removed batches can be rehydrated from cached HTML without rebuilding controller state
- URL sync follows the most visible live batch without requiring a full navigation

Diagnostics:
- the runtime should expose mounted-batch and retained-card counts for manual verification
- debug output may remain lightweight and developer-facing; it is not intended as a user feature

## Observed Validation Snapshot
Observed in a real long-scroll validation session after Phase 1.5 hardening:

- roughly `337` total cards were tracked by the windowing controller
- only `26` cards remained mounted in the live DOM
- `document.querySelectorAll('*').length` was about `1798`
- `document.querySelectorAll('#adforest-ajax-results *').length` was about `961`

Interpretation:
- live DOM retention for result cards is now bounded
- browser tooling that reports much larger counts should be cross-checked against explicit live-DOM console measurements

## Memory Watchpoints
The project should track:
- long-scroll memory growth on mid-range mobile devices
- repeated batch append stability
- jank appearing after several result batches
- scroll-anchor stability when batches are removed above the viewport

## Exit Trigger From WordPress Phase
Accelerate migration to the independent frontend if one or more of these become true:

- field/RUM `INP` exceeds target after repeated scroll batches
- retained DOM exceeds the approved budget
- memory growth becomes unstable during long-scroll sessions
- append-limited mitigations stop preserving smooth interaction
- batch windowing can no longer preserve stable scrolling without visible jumps

## Measurement Gap
This implementation pass establishes the budgets and trigger model, but not a final production-grade RUM dataset yet.

The next measurement pass should record:
- page-1 field data
- long-scroll interaction traces
- retained node counts after repeated batches
- mounted-batch count during long-scroll sessions
- retained-card count after page 3, page 5, and page 10 equivalents
