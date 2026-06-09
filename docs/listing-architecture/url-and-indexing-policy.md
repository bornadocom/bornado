# URL and Indexing Policy

## Structural URL Contract
The semantic path owns structural state:

- country
- city
- category chain
- pagination

Examples:

- `/uk/`
- `/uk/london/`
- `/uk/property/`
- `/uk/london/property/`
- `/uk/london/property/page/2/`

## Query String Contract
The query string is reserved for non-structural state such as:

- `search`
- `sort`
- `min_price`
- `max_price`
- custom non-structural filter keys

These values must be:

- sanitized
- empty-value free
- deterministically ordered

## Pagination Rules
- Paginated listing URLs are canonical to themselves.
- Pagination is represented by `/page/{n}/` in semantic routes.
- `paged`, `page`, and `page-number` are treated as structural pagination aliases at input time and must not remain in the public canonical query.

## Canonical Rules
- Each clean listing URL self-canonicalizes.
- Structural duplicates must 301 to the canonical semantic route where possible.
- Parameter order must not create multiple equivalent canonical candidates.

## Index / Noindex Rules
### Indexable by default
- clean `country_only`
- clean `country_city`
- clean semantic routes backed by valid landing rules already accepted in current routing policy

### Noindex by default
- query-polluted semantic routes
- low-value filtered/sorted URLs
- legacy taxonomy archives used only as fallback/internal compatibility

## Empty State Policy
- If a route itself is invalid, return `404`.
- If a structurally valid route has no results, prefer a valid empty listing state only when the route is still meaningful.
- For future API/frontend migration, empty-result combinations that become standalone crawl targets should be evaluated for `404` vs valid empty page on a case-by-case basis.

## Sitemap Policy
- Only canonical, intended-to-index listing URLs belong in sitemaps.
- Parameterized low-value filter URLs do not belong in sitemaps.

## Internal Linking Policy
- All category/location/listing navigation should prefer semantic URLs.
- Widgets/forms may still use temporary query inputs internally, but public destinations should resolve to semantic routes whenever possible.

## Implementation Notes
This implementation pass added two important guardrails:

1. Recursive query sorting for public semantic URLs.
2. Removal of `page-number` from public canonical query state.
