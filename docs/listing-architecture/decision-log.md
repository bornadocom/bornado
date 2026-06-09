# Decision Log

## ADR-Listing-001: Hybrid listing architecture
Status: accepted

Decision:
Keep listing pages as crawlable paginated URLs with SSR HTML, and treat infinite scroll / load more as progressive enhancement.

Why:
- Google indexing guidance for infinite scroll requires paginated loading with stable URLs.
- This keeps current SEO safe while preserving UX quality.

Rejected alternative:
- JS-only infinite scroll as the only access path to results.

## ADR-Listing-002: Semantic path owns structure, query owns non-structural filters
Status: accepted

Decision:
Country, city, category, and pagination belong to the semantic path. Non-structural filters stay in the query string.

Why:
- Canonical URLs become predictable.
- Structural route state no longer leaks into removable filter chips.
- Cache keys and internal linking become cleaner.

Rejected alternative:
- Mixing city/category/page state between path and query depending on source widget.

## ADR-Listing-003: Deterministic query ordering
Status: accepted

Decision:
Public query parameters for semantic listing URLs must be sorted recursively before URL generation.

Why:
- Prevents canonical drift caused only by parameter order.
- Improves CDN/cache hit consistency.
- Makes logs and regression diffs easier to compare.

## ADR-Listing-004: `page-number` is structural pagination state
Status: accepted

Decision:
Treat `page-number` as a structural alias of pagination, not as a public filter parameter.

Why:
- The route contract already uses `/page/{n}/`.
- Leaving `page-number` in public query state creates non-canonical duplicates.

## ADR-Listing-005: Listing API belongs to plugin layer, not child theme
Status: accepted

Decision:
Introduce the independent listing API under `plugins/bornado-search-core/`.

Why:
- The API contract must survive theme changes and future frontend migration.
- Plugin ownership is closer to domain behavior than child-theme markup.

Rejected alternative:
- Building API endpoints inside `adforest-child/`.

## ADR-Listing-006: WordPress is a temporary adapter, not the final runtime
Status: accepted

Decision:
The new listing API currently adapts WordPress/AdForest data, but the contract is designed to outlive the current runtime.

Why:
- This reduces migration risk.
- Future frontend work can target the contract instead of theme templates.

## ADR-Listing-007: `llms.txt` is not a phase-1 requirement
Status: accepted

Decision:
Do not treat `llms.txt` as a required part of the listing architecture rollout.

Why:
- Official Google documentation does not require it for AI Search visibility.
- Technical SEO, crawlability, and high-quality content remain the primary levers.

## ADR-Listing-008: AI crawler policy is business-controlled
Status: accepted

Decision:
Allow/disallow decisions for `Google-Extended`, `GPTBot`, and other AI crawlers are governance decisions, not hard-coded technical assumptions.

Why:
- The right answer depends on business, legal, and licensing constraints.

## ADR-Listing-009: WordPress phase must have an exit trigger
Status: accepted

Decision:
The temporary WordPress phase is valid only while field performance remains within budget.

Why:
- `content-visibility` reduces paint cost but does not solve unlimited DOM accumulation.
- The project must not remain indefinitely on temporary mitigations once long-scroll performance degrades.
