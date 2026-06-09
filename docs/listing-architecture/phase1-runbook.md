# Phase 1 Runbook

## Goal
Verify that phase-1 listing architecture work is stable after code changes or deployments.

## 1. Syntax and lint
Run:

```powershell
php -l "plugins/bornado-routing/bornado-routing.php"
php -l "plugins/bornado-search-core/bornado-search-core.php"
php -l "plugins/bornado-search-core/includes/class-bornado-listing-api.php"
```

## 2. Canonical route checks
Check these URLs in the browser:

- `/uk/`
- `/uk/london/`
- `/uk/property/`
- `/uk/london/property/`
- `/uk/london/property/page/2/`

Expect:
- no accidental fallback to `?country_id=` or `?cat_id=`
- canonical remains semantic
- pagination uses path-based `/page/{n}/`

## 3. Query pollution checks
Test non-structural filters such as:

- `sort`
- `min_price`
- `max_price`
- keyword search

Expect:
- structural state remains in the path
- public query args are deterministic
- `page-number` does not survive as canonical public query state

## 4. API checks
Test:

- `/wp-json/bornado/v1/listings`
- `/wp-json/bornado/v1/listings?page=2&sort=price-desc`
- `/wp-json/bornado/v1/listings/{id}`

Expect:
- valid JSON
- `pagination` block present
- `links.self` exists
- `links.next` and `links.prev` appear only when appropriate

## 5. Search UX checks
On a listing page:

- trigger a filter change
- change sort
- paginate
- remove the last active tag

Expect:
- no dangling `?`
- no structural filter chips for city/category from semantic path
- URL remains clean and shareable

## 6. Documentation checks
Confirm these files exist and match current implementation intent:

- `docs/listing-architecture/url-and-indexing-policy.md`
- `docs/listing-architecture/listing-api-contract.md`
- `docs/listing-architecture/headless-migration-blueprint.md`
- `docs/listing-architecture/implementation-journal.md`

## 7. If something regresses
Review in this order:

1. `plugins/bornado-routing/bornado-routing.php`
2. `plugins/bornado-search-core/includes/class-bornado-listing-api.php`
3. `plugins/bornado-search-core/assets/js/bornado-search-core.js`
4. `adforest-child/functions.php`
5. `adforest-child/template-parts/layouts/search/cards/card-list.php`
