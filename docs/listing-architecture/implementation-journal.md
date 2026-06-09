# Implementation Journal

## 2026-06-09

### What Changed
- documented the listing architecture inside `docs/listing-architecture/`
- introduced a plugin-owned independent listing REST API
- tightened semantic URL behavior so pagination aliases like `page-number` do not leak into canonical public query state
- added deterministic query sorting for public semantic URLs
- added API navigation links (`self`, `next`, `prev`) for future consumers
- added explicit runbook and current-vs-target architecture documentation

### Why
- the project needs a real migration-friendly contract, not only theme-level HTML
- canonical stability and query ordering are part of a trustworthy URL policy
- future frontend work must be able to continue without reconstructing architecture intent from memory

### What Remains
- deeper parity with all AdForest filters/facets
- field measurement pass for long-scroll budgets
- frontend consumer outside WordPress
- stronger virtualization/windowing in the independent frontend phase

### Risks
- the current listing API is an adapter on top of WordPress/AdForest, not the final backend
- facet counts are not yet exposed
- theme-owned search entry points still exist and must be rechecked after upstream updates

### Intentional Non-Changes
- the plan file itself was not edited during implementation
- `llms.txt` was not introduced as a required SEO dependency
- AI crawler allow/disallow policy was not hard-coded as a universal rule

### Next Practical Step
- start consuming the listing API from the next isolated frontend/search surface instead of adding more theme-bound data logic
