# Migration Ledger

## Purpose
Track what is already independent, what is still temporary, and what remains to be extracted.

## Already Independent or Plugin-Owned
- semantic route resolution in `plugins/bornado-routing/`
- shared search helpers in `plugins/bornado-search-core/`
- listing REST contract in `plugins/bornado-search-core/includes/class-bornado-listing-api.php`

## Still Temporary in WordPress / Theme Layer
- final listing HTML rendering
- card template markup in `adforest-child/template-parts/layouts/search/cards/card-list.php`
- several UX fixes in `adforest-child/functions.php` and related child-theme files
- AdForest search query construction and search page rendering

## Temporary But Acceptable
- child-theme CSS/JS for search UX
- query-shape compatibility shims
- performance overrides that reduce current-page cost without changing the core theme

## Not Yet Extracted
- independent facet-count service
- frontend SSR application outside WordPress
- dedicated search/read store
- production-grade observability around listing API usage

## Migration Direction
1. stabilize URL/indexing policy
2. establish plugin-owned listing API contract
3. move frontend consumers to the API
4. replace WordPress listing runtime with independent SSR frontend

## Blockers To Watch
- theme-owned query behavior that still bypasses shared helpers
- long-scroll DOM accumulation in the temporary phase
- any future drift between semantic route policy and API consumers
