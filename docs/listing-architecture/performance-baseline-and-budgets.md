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
- the temporary WordPress phase may use append-based UX
- the independent frontend phase must use stronger DOM control, including virtualization/windowing where needed

## Memory Watchpoints
The project should track:
- long-scroll memory growth on mid-range mobile devices
- repeated batch append stability
- jank appearing after several result batches

## Exit Trigger From WordPress Phase
Accelerate migration to the independent frontend if one or more of these become true:

- field/RUM `INP` exceeds target after repeated scroll batches
- retained DOM exceeds the approved budget
- memory growth becomes unstable during long-scroll sessions
- append-limited mitigations stop preserving smooth interaction

## Measurement Gap
This implementation pass establishes the budgets and trigger model, but not a final production-grade RUM dataset yet.

The next measurement pass should record:
- page-1 field data
- long-scroll interaction traces
- retained node counts after repeated batches
