# Dynamic Make Prompt Flow

## Important

Do **not** keep a long manual system prompt inside Make anymore.

Always fetch the live prompt from the service:

- classify stage: `GET /prompt-package?stage=classify`
- extract stage: `GET /prompt-package?stage=extract&category_hint=...`

Then use:

- AI system prompt = `{{HTTP_PROMPT_PACKAGE.data.composed_prompt}}`
- AI user message = one of the templates below

## Classify Stage

Use this when category is **not** known yet.

### Prompt package request

```text
GET /prompt-package?market=uk&channel=instagram&stage=classify
```

### AI user message

```text
Publisher Metadata (NEVER use as advertiser contact or advertiser location unless explicitly repeated inside the ad itself):
Publisher Name: {{15.ownerFullName}}
Publisher Username: {{15.ownerUsername}}

Default Country ID:
{{17.`4`}}

Default City ID:
{{17.`5`}}

Platform Label For Unspecified IDs:
{{17.`8`}}

OCR Text From Image:
{{58.fullTextAnnotation.text}}

Post Caption:
{{15.caption}}
```

### Expected result

The AI should mainly determine:

- `status`
- `reason`
- `category_key`
- `country_key`
- `city_key`
- base ad fields

At this stage `dynamic_fields` should stay empty.

## Extract Stage

Use this after classify stage, or whenever category is already known upstream.

### Prompt package request

```text
GET /prompt-package?market=uk&channel=instagram&stage=extract&category_hint={{CLASSIFY_AI_JSON.category_key}}
```

### AI user message

```text
Publisher Metadata (NEVER use as advertiser contact or advertiser location unless explicitly repeated inside the ad itself):
Publisher Name: {{15.ownerFullName}}
Publisher Username: {{15.ownerUsername}}

Default Country ID:
{{17.`4`}}

Default City ID:
{{17.`5`}}

Platform Label For Unspecified IDs:
{{17.`8`}}

OCR Text From Image:
{{58.fullTextAnnotation.text}}

Post Caption:
{{15.caption}}
```

### Expected result

The AI should now return:

- all base fields
- `dynamic_fields`

And `dynamic_fields` must contain only the fields allowed by the live `category_schema` inside the fetched prompt package.

## Final Ingest Request

After the extract AI pass, send the result to the service instead of manually mapping fields in Make.

```json
{
  "market": "uk",
  "channel": "instagram",
  "extraction": {{EXTRACT_AI_JSON}}
}
```

Request:

```text
POST /ingest
```

## Recommended Make Order

If category is unknown:

1. `GET /prompt-package?stage=classify`
2. AI classify
3. `GET /prompt-package?stage=extract&category_hint={{classified_category_key}}`
4. AI extract
5. `POST /ingest`

If category is already known:

1. `GET /prompt-package?stage=extract&category_hint=KNOWN_CATEGORY`
2. AI extract
3. `POST /ingest`

## Critical Rules

- do not paste a fixed manual system prompt in Make
- do not manually map category fields in Make
- do not hardcode template fields in Make
- always use the service-generated `composed_prompt`
- always send AI JSON to `/ingest`