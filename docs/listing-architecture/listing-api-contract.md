# Listing API Contract

## Purpose
This API is the first independent listing/search contract that frontend code can target without depending on child-theme markup.

Current runtime:
- WordPress adapter
- AdForest-backed data
- plugin-owned contract in `plugins/bornado-search-core/`

## Namespace
- REST namespace: `bornado/v1`

## Endpoints
### GET `/wp-json/bornado/v1/listings`
Returns paginated listing cards.

Supported query params:
- `page`
- `per_page`
- `search`
- `country_id`
- `city_id`
- `cat_id`
- `min_price`
- `max_price`
- `sort`

Supported `sort` values:
- `newest`
- `oldest`
- `price-asc`
- `price-desc`
- `popular`

### GET `/wp-json/bornado/v1/listings/{id}`
Returns one published listing item.

## Response Shape
### Collection
```json
{
  "items": [],
  "pagination": {
    "page": 1,
    "per_page": 12,
    "total_items": 0,
    "total_pages": 0,
    "has_next": false,
    "has_prev": false
  },
  "links": {
    "self": "",
    "next": "",
    "prev": ""
  },
  "applied_filters": {},
  "supported_sorts": {},
  "contract_version": "2026-06-09",
  "runtime": {
    "provider": "wordpress-adapter",
    "post_type": "ad_post",
    "route_contract": "semantic-path-plus-query",
    "search_engine": "wp-query",
    "independent_layer": "plugin"
  }
}
```

### Item
Each listing item currently includes:
- identity: `id`, `title`
- routing: `permalink`, `canonical_url`
- content: `excerpt`
- timing: `posted_at`, `posted_label`, `posted_location`
- badges: `featured`, `urgent`, `verified`, `badges[]`
- commercial state: `price.raw`, `price.html`, `price.type`
- media: `media.primary_image`, `media.gallery`
- author summary
- location summary
- category summary
- view count

Single-item responses also include:
- `links.self`
- `links.canonical`

## Current Adapter Limits
- The API intentionally reuses WordPress/AdForest as a data source for now.
- It does not yet expose server-calculated facet counts.
- It does not yet replace the on-page HTML rendering path.
- It does not yet expose a write contract.

## Stability Goal
Frontend migration should target this contract, not the child-theme card HTML.

That means:
- new consumers should prefer the REST contract
- future backend migration should preserve this response shape as much as practical
