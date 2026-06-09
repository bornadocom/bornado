# Headless Migration Blueprint

## Goal
Move listing/search from WordPress runtime to an independent SSR frontend without breaking:

- canonical URLs
- semantic routing
- crawlability
- current indexed surfaces

## Phase Map
### Phase A: Policy stabilization
Owned by current implementation pass.

Deliverables:
- stable semantic URL contract
- deterministic public query ordering
- plugin-owned listing API contract
- project documentation

### Phase B: Parallel frontend consumer
Build a separate frontend that reads from the listing API but does not replace production traffic yet.

Deliverables:
- SSR page for page 1 and page n
- same URL contract
- parity checks against WordPress-rendered listing pages

### Phase C: Controlled route takeover
Move selected listing routes to the new SSR frontend while preserving:
- exact canonical behavior
- sitemap compatibility
- semantic route ownership

### Phase D: Runtime retirement
Once parity and performance are proven:
- remove WordPress listing runtime from the public critical path
- keep WordPress only as admin/content producer until the backend migration is complete

## What Must Stay Stable During Migration
- semantic route shapes
- canonical rules
- index/noindex policy
- robots behavior
- public listing card data contract

## What Can Change
- rendering engine
- UI composition
- long-scroll implementation details
- caching/search backend

## Temporary Components That Can Be Retired Later
- child-theme card template overrides
- child-theme search-only UX shims
- theme-owned append/load-more behavior

## Go / No-Go For Route Cutover
Do not move public listing routes to the independent frontend unless:
- page 1 and paginated pages both render crawlable HTML
- canonical and robots output match policy
- listing API parity is sufficient for production cards
- no major regression exists in `LCP`, `CLS`, or `INP`
