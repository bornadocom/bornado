# Bornado Schema Architecture

This module owns front-end JSON-LD for Bornado listing and landing pages.

## Goals

- Keep schema logic discoverable and modular.
- Separate reusable graph logic from page-shape logic.
- Support current archive routes and future SEO landing pages without rewriting everything.
- Avoid duplicate owners for the same schema node.

## Folder Layout

```text
schema/
  bootstrap.php
  README.md
  shared/
    helpers.php
    context.php
    category.php
    breadcrumb.php
    item-list.php
    graph.php
  pages/
    home-collection/
    country-collection/
    city-collection/
  shapes/
    category-root/
    category-country/
    category-country-city/
  verticals/
    property/
    jobs/
    vehicles/
    items/
    community/
    services/
```

## Core Concepts

### 1) Shared graph layer

Files in `shared/` should contain reusable logic only:

- `helpers.php`: stable IDs, refs, common CollectionPage assembly
- `context.php`: page-type detection and route context helpers
- `category.php`: category-shape detection and vertical enrichment registry
- `breadcrumb.php`: shared BreadcrumbList builder
- `item-list.php`: shared ItemList builder from the current ad query
- `graph.php`: handlers/extenders registry plus Rank Math integration

### 2) Page collections

These are location-led list pages:

- `home_collection`
- `country_collection`
- `city_collection`

They live under `pages/`.

### 3) Category shapes

Category URLs are modeled by shape, not duplicated per URL:

- `category_root_collection`
  - Example: `/property/`
- `category_country_collection`
  - Example: `/uk/property/`
- `category_country_city_collection`
  - Example: `/uk/london/property/`

These live under `shapes/`.

### 4) Vertical enrichments

Category pages use one shared schema core, then receive vertical-specific enrichment:

- `property`
- `jobs`
- `vehicles`
- `items`
- `community`
- `services`

Each vertical registers config through the filter:

`bornado_schema_manager_category_vertical_configs`

Current root term mapping:

- `338` => `property`
- `339` => `jobs`
- `340` => `vehicles`
- `341` => `services`
- `342` => `items`
- `343` => `community`

## Current Output Strategy

### Country pages

Country pages currently ship:

- `CollectionPage`
- shared `BreadcrumbList`
- shared-query `ItemList`

### City pages

City pages currently ship:

- `CollectionPage`
- shared `BreadcrumbList`
- shared-query `ItemList`

### Category pages

Category pages currently ship:

- `CollectionPage`
- shared `BreadcrumbList`
- shared-query `ItemList`
- vertical enrichment metadata

## Why shape + vertical?

This prevents an explosion of duplicated files.

Instead of creating separate full schema stacks for every combination like:

- `/property/`
- `/uk/property/`
- `/uk/london/property/`

and repeating that for six verticals, we split concerns:

- `shape` decides page structure
- `vertical` decides semantic enrichment

This is easier to maintain and scales better for future routes.

## Future SEO Landing Pages

The routing plugin may later create dedicated landing pages via:

`plugins/bornado-routing/includes/class-seo-landing-manager.php`

That will not conflict with this module if we follow one rule:

- introduce a distinct schema branch/shape for SEO landings

Recommended future landing shapes:

- `landing_country_category`
- `landing_country_city_category`

Their schema owner should be this module, not ad hoc template code.

## Validation Rules

When changing schema output:

1. Keep one primary owner per node.
2. Prefer explicit `@id` references between nodes.
3. Ensure `ItemList` only reflects visible listing results.
4. Keep `inLanguage` consistent (`fa-IR`).
5. Prefer `WebPage` references for breadcrumb/list items unless a more specific page type is truly justified.

## How To Extend

### Add a new category shape

1. Create:
   - `shapes/<shape>/collection-page.php`
   - `shapes/<shape>/item-list.php`
2. Load them in `bootstrap.php`
3. Register handler/extender in `shared/graph.php`
4. Return the new shape from `shared/context.php` or `shared/category.php`

### Add a new vertical

1. Create `verticals/<key>/enrich.php`
2. Register config with `bornado_schema_manager_category_vertical_configs`
3. Map term IDs and slug aliases
4. Add labels and keyword set

### Add a new shared node

If a node applies broadly, add it to `shared/` and extend through `shared/graph.php`.

## Operational Note

`functions.php` only loads `schema/bootstrap.php`.

If the `schema/` folder is missing on a live server, the site should still run, but the custom schema layer will not load. That makes uploads safer but also means partial deployments can silently fall back to older schema behavior.
