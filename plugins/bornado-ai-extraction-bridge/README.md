# Bornado AI Extraction Bridge

Thin WordPress adapter for the independent `Services/bornado-ai-extraction-platform` service.

## What it does

- Exposes a curated catalog endpoint at `wp-json/bornado-ai-bridge/v1/catalog`
- Keeps AI prompt and validation logic out of WordPress
- Avoids editing AdForest core theme files
- Provides a small admin page under `Tools > Bornado AI Bridge`

## Configuration

Copy:

- `config/bornado-ai-extraction-bridge-config.php.example`

to:

- `config/bornado-ai-extraction-bridge-config.php`

Then set:

- `BORNADO_AI_EXTRACTION_SERVICE_BASE_URL`
- `BORNADO_AI_EXTRACTION_SERVICE_KEY`

## Auth model

The bridge catalog endpoint uses normal WordPress REST authentication and checks `manage_options`.
For service-to-WordPress access, use a dedicated WordPress user with an Application Password.
