<?php

if (!defined('BORNADO_AI_EXTRACTION_SERVICE_BASE_URL')) {
    define('BORNADO_AI_EXTRACTION_SERVICE_BASE_URL', 'https://bornado.com/Services/bornado-ai-extraction-platform/public/index.php');}

if (!defined('BORNADO_AI_EXTRACTION_SERVICE_KEY')) {
    define('BORNADO_AI_EXTRACTION_SERVICE_KEY', (string) (getenv('BORNADO_AI_EXTRACTION_SERVICE_KEY') ?: ''));
}