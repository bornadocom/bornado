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

## 6. Windowed infinite-scroll checks
On a listing page with JavaScript enabled:

- scroll until at least page 2 and page 3 load automatically
- inspect the URL while page 2 / page 3 becomes the primary visible batch
- keep scrolling, then scroll back upward
- inspect the results container after several pages worth of content

Expect:
- no `Load More` button is required for the next page to appear
- crawlable pagination still exists in the server HTML, but long-scroll DOM stays bounded
- retained DOM stays bounded because distant batches are replaced by spacers
- scrolling back upward rehydrates older batches without obvious jumpiness
- refresh on the currently visible `/page/{n}/` URL keeps the user on that page family
- console checks like `document.querySelectorAll('*').length` and `BornadoWindowedInfiniteScroll.debug()` should confirm that mounted cards stay low while total tracked cards grows

Reference validation snapshot from this implementation pass:
- `document.querySelectorAll('*').length` was about `1798`
- `document.querySelectorAll('#adforest-ajax-results *').length` was about `961`
- `BornadoWindowedInfiniteScroll.debug()` showed roughly `337` total cards and `26` mounted cards

## 7. Documentation checks
Confirm these files exist and match current implementation intent:

- `docs/listing-architecture/url-and-indexing-policy.md`
- `docs/listing-architecture/listing-api-contract.md`
- `docs/listing-architecture/headless-migration-blueprint.md`
- `docs/listing-architecture/implementation-journal.md`

## 8. If something regresses
Review in this order:

1. `plugins/bornado-routing/bornado-routing.php`
2. `plugins/bornado-search-core/includes/class-bornado-listing-api.php`
3. `plugins/bornado-search-core/assets/js/bornado-search-core.js`
4. `adforest-child/functions.php`
5. `adforest-child/template-parts/layouts/search/cards/card-list.php`
6. `adforest-child/bornado-search-windowed-infinite-scroll.php`
7. `adforest-child/assets/js/bornado-search-dom-windowing.js`
