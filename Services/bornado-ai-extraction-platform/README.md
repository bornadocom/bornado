# Bornado AI Extraction Platform

Independent AI extraction platform reference implementation for Bornado.

## Goals

- Keep prompt orchestration, schema building, normalization, and resolution outside WordPress.
- Treat WordPress as a source and target adapter, not the home of AI logic.
- Reduce extraction errors as categories and fields grow by using canonical keys, schema slicing, and multi-layer validation.
- Keep future migration possible by resolving canonical keys into target-specific IDs only at the edge.
- Protect the live site by keeping write-path changes out of the first rollout phase.

## Current scope

- `GET /health`
- `GET /schema`
- `GET /prompt-package`
- `POST /resolve`
- WordPress REST catalog source for categories, locations, and enum taxonomies
- File-backed schema cache
- Canonical key-based output contract for AI
- Safe resolver that converts canonical keys into WordPress-ready taxonomy payloads

## Layout

- `public/index.php`: HTTP entrypoint
- `config/ai-extraction-platform.php`: runtime configuration
- `templates/core-prompt.md`: dynamic-aware core prompt template
- `src/Application/`: schema, prompt-package, and resolution orchestration
- `src/Infrastructure/`: WordPress REST source and schema cache
- `src/Domain/`: canonical key logic
- `openapi/`: service contract
- `schemas/`: JSON schema artifacts
- `docs/adr/`: architecture decisions

## How it works

1. The service reads curated data from WordPress REST taxonomies.
2. It builds a canonical schema for a market and channel.
3. It generates a prompt package where all hardcoded IDs are replaced by stable keys and a dynamic schema block.
4. AI returns stable keys such as `category_key`, `city_key`, and `ad_type_key`.
5. The resolver maps those keys to WordPress taxonomy IDs only at the final edge.

## Production notes

- `GET /schema` and `GET /prompt-package` use service-key auth.
- `POST /resolve` uses HMAC signature auth.
- WordPress Application Password auth is supported for protected taxonomy metadata.
- The service keeps the current live prompt untouched; it introduces a parallel independent path first.
- No AdForest core files are modified by this service.
