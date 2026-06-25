# Bornado AI Extraction Platform Runbook

## 1. Goal

The fully dynamic flow is now category-first and template-driven.

Make should no longer keep a long static system prompt or manually map category fields.

Instead:

1. WordPress bridge exposes live category-template metadata
2. The service builds a scoped prompt package from that live metadata
3. AI returns a generic JSON with `dynamic_fields`
4. The service resolves and saves the ad through the WordPress bridge

## 2. Prepare the service config

Create:

- `config/ai-extraction-platform.local.php`

Fill in:

- `service.base_url`
- `service.shared_secret`
- `service.ops_key`
- `source.wordpress.base_url`
- `source.wordpress.application_password`
- `source.wordpress.catalog_endpoint`
- `source.wordpress.service_key`

Optional but recommended:

- `target.wordpress_bridge.ingest_endpoint`

If `target.wordpress_bridge.ingest_endpoint` is empty, the service automatically derives it from the catalog endpoint by replacing `/catalog` with `/ingest`.

## 3. Prepare the WordPress bridge config

Create:

- `plugins/bornado-ai-extraction-bridge/config/bornado-ai-extraction-bridge-config.php`

Set:

- `BORNADO_AI_EXTRACTION_SERVICE_BASE_URL`
- `BORNADO_AI_EXTRACTION_SERVICE_KEY`

Optional:

- `BORNADO_AI_EXTRACTION_AUTHOR_ID`

If no author ID is configured, the bridge falls back to the first administrator.

## 4. Activate the WordPress bridge plugin

Activate:

- `Bornado AI Extraction Bridge`

Then open:

- `Tools -> Bornado AI Bridge`

Verify:

- `catalog` endpoint is visible
- `ingest` endpoint is visible
- remote service URL is correct
- service key is configured

## 5. Expose the service

Required endpoints:

- `GET /health`
- `GET /schema`
- `GET /prompt-package`
- `POST /resolve`
- `POST /ingest`

Local test server example:

```bash
php -S 127.0.0.1:8086 -t Services/bornado-ai-extraction-platform/public
```

## 6. Test health

```bash
curl "http://127.0.0.1:8086/health"
```

Expected:

- `status = ok`

## 7. Test schema

```bash
curl -H "X-Bornado-Service-Key: YOUR_SERVICE_KEY" \
  "http://127.0.0.1:8086/schema?market=uk&channel=instagram"
```

Expected:

- `categories`
- `templates`
- `fields.by_category`
- live template-derived field descriptors

## 8. Test prompt package: classify stage

```bash
curl -H "X-Bornado-Service-Key: YOUR_SERVICE_KEY" \
  "http://127.0.0.1:8086/prompt-package?market=uk&channel=instagram&stage=classify"
```

Expected:

- `dynamic_schema.stage = classify`
- top-level categories only
- no category-specific field list yet

## 9. Test prompt package: extract stage

```bash
curl -H "X-Bornado-Service-Key: YOUR_SERVICE_KEY" \
  "http://127.0.0.1:8086/prompt-package?market=uk&channel=instagram&stage=extract&category_hint=property"
```

Expected:

- `dynamic_schema.stage = extract`
- `category_schema`
- only the field list for `property`

## 10. Test resolve

Create a body like this:

```json
{
  "market": "uk",
  "channel": "instagram",
  "extraction": {
    "status": "approved",
    "category_key": "property",
    "country_key": "gb",
    "city_key": "london",
    "primary_contact": "07493995660",
    "secondary_contacts": [],
    "seo_title": "اجاره آپارتمان در لندن",
    "slug": "اجاره-آپارتمان-در-لندن",
    "final_ad_text": "یک واحد تمیز برای اجاره موجود است.",
    "dynamic_fields": {
      "price": 1800,
      "price_type": "fixed",
      "ad_type": "rent",
      "property_type": "apartment",
      "bedrooms": 2
    }
  }
}
```

Send it to:

```bash
curl -X POST "http://127.0.0.1:8086/resolve" \
  -H "Content-Type: application/json" \
  -H "X-Bornado-Signature: YOUR_HMAC_SIGNATURE" \
  --data "@resolve.json"
```

Expected:

- `resolution_status = resolved`
- `target_payload.wordpress_bridge`

## 11. Test ingest

This writes to WordPress, so use controlled sample data first.

```bash
curl -X POST "http://127.0.0.1:8086/ingest" \
  -H "Content-Type: application/json" \
  -H "X-Bornado-Signature: YOUR_HMAC_SIGNATURE" \
  --data "@resolve.json"
```

Expected:

- `resolution.resolution_status = resolved`
- `publish.ingest_status = saved`
- returned `post.id`

## 12. Test the WordPress bridge directly

Catalog:

```bash
curl "https://your-site.example.com/wp-json/bornado-ai-bridge/v1/catalog?market=gb&channel=instagram&key=YOUR_OPS_KEY"
```

Expected:

- `categories`
- `templates`
- template-driven field descriptors

## 13. Make integration

### Recommended fully dynamic flow

If category is not known in advance:

1. `GET /prompt-package?stage=classify`
2. AI classify pass
3. `GET /prompt-package?stage=extract&category_hint={{classified_category_key}}`
4. AI extract pass
5. `POST /ingest`

If category is already known upstream:

1. `GET /prompt-package?stage=extract&category_hint=KNOWN_CATEGORY`
2. AI extract pass
3. `POST /ingest`

### Important rules

- do not keep a manual long system prompt inside Make
- use `composed_prompt` from the service every time
- do not manually map category template fields in Make
- let AI output `dynamic_fields`
- let the service resolve and save them
- do not invent default country/city logic in Make beyond passing fallback metadata to AI

## 14. Safe rollout

Recommended rollout:

1. bridge plugin active
2. `catalog` test passing
3. `schema` test passing
4. classify prompt test passing
5. extract prompt test passing
6. `resolve` test passing
7. `ingest` test passing on controlled samples
8. Make runs in shadow mode
9. compare old flow vs new flow on real samples
10. switch production writes to `/ingest`
