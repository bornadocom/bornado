# Implementation Journal

## 2026-06-09

### What Changed
- documented the listing architecture inside `docs/listing-architecture/`
- introduced a plugin-owned independent listing REST API
- tightened semantic URL behavior so pagination aliases like `page-number` do not leak into canonical public query state
- added deterministic query sorting for public semantic URLs
- added API navigation links (`self`, `next`, `prev`) for future consumers
- added explicit runbook and current-vs-target architecture documentation
- added a child-theme DOM windowing controller that leaves AdForest's native listing loader in place
- added spacer replacement and rehydration for off-screen search cards so retained DOM stays bounded during long scroll sessions
- reduced low-value card markup on search list cards where it was safe to do so

### Validation Outcome
- real browser validation reached roughly `337` tracked cards with only `26` mounted cards left in the live DOM
- measured live DOM for the full page was about `1798` nodes after the long-scroll test
- measured live DOM inside `#adforest-ajax-results` was about `961` nodes during the same session
- this confirms the original listing-card DOM-bloat problem was materially reduced from the earlier observed state (`~27000` nodes after ~340 ads)
- the retained DOM improvement was confirmed with direct console queries, not only DevTools snapshots

### Why
- the project needs a real migration-friendly contract, not only theme-level HTML
- canonical stability and query ordering are part of a trustworthy URL policy
- future frontend work must be able to continue without reconstructing architecture intent from memory
- the previous append-only long-scroll behavior let retained DOM grow without limit
- the project needed a no-core-change path to keep infinite-scroll UX without requiring a load-more button

### What Remains
- deeper parity with all AdForest filters/facets
- field measurement pass for long-scroll budgets
- frontend consumer outside WordPress
- stronger virtualization/windowing in the independent frontend phase
- optional migration from HTML batch cache to structured listing-data cache
- optional History API page-state sync during scroll if it can be added without harming UX

### Risks
- the current listing API is an adapter on top of WordPress/AdForest, not the final backend
- facet counts are not yet exposed
- theme-owned search entry points still exist and must be rechecked after upstream updates
- long-scroll stability still depends on consistent batch-height measurement and should be tested on mid-range mobile hardware
- HTML-string rehydration is pragmatic for the WordPress phase, but not the final frontend-state model
- the live DOM budget is now controlled for result cards, but other search-page DOM sources should still be audited separately when needed
- browser tooling can still show stale/detached snapshots that are larger than the live DOM; future checks should rely on explicit console measurements as well

### Intentional Non-Changes
- the plan file itself was not edited during implementation
- `llms.txt` was not introduced as a required SEO dependency
- AI crawler allow/disallow policy was not hard-coded as a universal rule
- parent-theme AdForest core files were not edited to achieve the new listing runtime

### Next Practical Step
- measure retained DOM, mounted card count, and scroll stability on real long-scroll sessions before widening the rollout
- if needed later, evaluate page-state URL sync as a separate UX decision rather than folding it into the DOM-control layer
