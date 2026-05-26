<?php
declare(strict_types=1);

$baseDir    = dirname(__DIR__);
$storageDir = $baseDir . DIRECTORY_SEPARATOR . 'storage';

$isList = static function (array $value): bool {
    if (function_exists('array_is_list')) {
        return array_is_list($value);
    }

    return array_keys($value) === range(0, count($value) - 1);
};

$mergeConfig = static function (array $base, array $override) use (&$mergeConfig, $isList) {
    foreach ($override as $key => $value) {
        if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
            if ($isList($base[$key]) || $isList($value)) {
                $base[$key] = $value;
                continue;
            }

            $base[$key] = $mergeConfig($base[$key], $value);
            continue;
        }

        $base[$key] = $value;
    }

    return $base;
};

$sharedSecret = getenv('BORNADO_NOTIFICATION_SHARED_SECRET');
$serviceUrl   = getenv('BORNADO_NOTIFICATION_SERVICE_URL');
$splitCsv     = static function ($value, array $default = array()) {
    if (!is_string($value) || '' === trim($value)) {
        return $default;
    }

    $items = array_map('trim', explode(',', $value));
    $items = array_values(array_filter($items, static function ($item) {
        return '' !== $item;
    }));

    return empty($items) ? $default : $items;
};

$whatsAppTemplateMap = array(
    'listing.published' => array(
        'name'            => getenv('BORNADO_WA_TEMPLATE_LISTING_PUBLISHED') ?: '',
        'language_code'   => getenv('BORNADO_WA_TEMPLATE_LANGUAGE') ?: 'fa',
        'body_parameters' => array(
            'payload.listing.title',
            'payload.listing.manageUrl',
        ),
    ),
    'user.registered' => array(
        'name'            => getenv('BORNADO_WA_TEMPLATE_USER_REGISTERED') ?: '',
        'language_code'   => getenv('BORNADO_WA_TEMPLATE_LANGUAGE') ?: 'fa',
        'body_parameters' => array(
            'payload.user.profileUrl',
        ),
    ),
    'listing.rejected' => array(
        'name'            => getenv('BORNADO_WA_TEMPLATE_LISTING_REJECTED') ?: '',
        'language_code'   => getenv('BORNADO_WA_TEMPLATE_LANGUAGE') ?: 'fa',
        'body_parameters' => array(
            'payload.listing.title',
            'payload.listing.editUrl',
        ),
    ),
    'listing.expiring_soon' => array(
        'name'            => getenv('BORNADO_WA_TEMPLATE_LISTING_EXPIRING') ?: '',
        'language_code'   => getenv('BORNADO_WA_TEMPLATE_LANGUAGE') ?: 'fa',
        'body_parameters' => array(
            'payload.listing.title',
            'payload.listing.manageUrl',
        ),
    ),
    'payment.completed' => array(
        'name'            => getenv('BORNADO_WA_TEMPLATE_PAYMENT_COMPLETED') ?: '',
        'language_code'   => getenv('BORNADO_WA_TEMPLATE_LANGUAGE') ?: 'fa',
        'body_parameters' => array(
            'payload.payment.orderId',
        ),
    ),
);

$config = array(
    'service' => array(
        'name'          => 'bornado-notification-platform',
        'source_system' => getenv('BORNADO_NOTIFICATION_SOURCE_SYSTEM') ?: 'bornado-wordpress',
        'base_url'      => $serviceUrl ?: 'http://localhost:8085',
        'shared_secret' => is_string($sharedSecret) ? trim($sharedSecret) : '',
        'default_locale'=> getenv('BORNADO_NOTIFICATION_DEFAULT_LOCALE') ?: 'fa-IR',
    ),
    'queue' => array(
        'pending_dir'    => $storageDir . DIRECTORY_SEPARATOR . 'outbox' . DIRECTORY_SEPARATOR . 'pending',
        'processing_dir' => $storageDir . DIRECTORY_SEPARATOR . 'outbox' . DIRECTORY_SEPARATOR . 'processing',
        'processed_dir'  => $storageDir . DIRECTORY_SEPARATOR . 'outbox' . DIRECTORY_SEPARATOR . 'processed',
        'failed_dir'     => $storageDir . DIRECTORY_SEPARATOR . 'outbox' . DIRECTORY_SEPARATOR . 'failed',
    ),
    'logging' => array(
        'delivery_log' => $storageDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'delivery.jsonl',
        'state_dir'    => $storageDir . DIRECTORY_SEPARATOR . 'state',
    ),
    'routing' => array(
        'allow_optimistic_channel_routing' => array(
            'whatsapp' => true,
            'sms'      => true,
            'email'    => false,
        ),
        'events' => array(
            'listing.published' => array(
                'channels' => array('whatsapp', 'sms', 'email'),
            ),
            'user.registered' => array(
                'channels' => array('whatsapp', 'sms', 'email'),
            ),
            'listing.rejected' => array(
                'channels' => array('whatsapp', 'sms', 'email'),
            ),
            'listing.expiring_soon' => array(
                'channels' => array('whatsapp', 'sms', 'email'),
            ),
            'payment.completed' => array(
                'channels' => array('whatsapp', 'sms', 'email'),
            ),
        ),
        'channel_providers' => array(
            'whatsapp' => $splitCsv(getenv('BORNADO_NOTIFICATION_WHATSAPP_PROVIDERS'), array('dry-run')),
            'sms'      => $splitCsv(getenv('BORNADO_NOTIFICATION_SMS_PROVIDERS'), array('dry-run')),
            'email'    => $splitCsv(getenv('BORNADO_NOTIFICATION_EMAIL_PROVIDERS'), array('dry-run')),
        ),
    ),
    'providers' => array(
        'dry-run' => array(
            'mode' => 'dry-run',
        ),
        'whatsapp-cloud-api' => array(
            'enabled'               => in_array(strtolower((string) getenv('BORNADO_WA_ENABLED')), array('1', 'true', 'yes', 'on'), true),
            'base_url'              => getenv('BORNADO_WA_BASE_URL') ?: 'https://graph.facebook.com',
            'api_version'           => getenv('BORNADO_WA_API_VERSION') ?: 'v22.0',
            'phone_number_id'       => getenv('BORNADO_WA_PHONE_NUMBER_ID') ?: '',
            'access_token'          => getenv('BORNADO_WA_ACCESS_TOKEN') ?: '',
            'message_mode'          => getenv('BORNADO_WA_MESSAGE_MODE') ?: 'template',
            'default_language_code' => getenv('BORNADO_WA_TEMPLATE_LANGUAGE') ?: 'fa',
            'text_fallback_enabled' => in_array(strtolower((string) getenv('BORNADO_WA_TEXT_FALLBACK_ENABLED')), array('1', 'true', 'yes', 'on'), true),
            'timeout_seconds'       => (int) (getenv('BORNADO_WA_TIMEOUT_SECONDS') ?: 10),
            'verify_ssl'            => !in_array(strtolower((string) getenv('BORNADO_WA_VERIFY_SSL')), array('0', 'false', 'no', 'off'), true),
            'template_map'          => $whatsAppTemplateMap,
        ),
    ),
    'templates' => array(
        'listing.published' => array(
            'fa-IR' => array(
                'whatsapp' => array(
                    'body' => 'آگهی شما با عنوان "{{payload.listing.title}}" منتشر شد. برای مدیریت آگهی از این لینک استفاده کنید: {{payload.listing.manageUrl}}',
                ),
                'sms' => array(
                    'body' => 'آگهی "{{payload.listing.title}}" منتشر شد. مدیریت: {{payload.listing.manageUrl}}',
                ),
                'email' => array(
                    'subject' => 'آگهی شما منتشر شد',
                    'body'    => "سلام {{payload.user.displayName}},\n\nآگهی شما با عنوان \"{{payload.listing.title}}\" منتشر شد.\n\nویرایش: {{payload.listing.editUrl}}\nمدیریت: {{payload.listing.manageUrl}}\n",
                ),
            ),
        ),
        'user.registered' => array(
            'fa-IR' => array(
                'whatsapp' => array(
                    'body' => 'عضویت شما با موفقیت انجام شد. از این لینک می‌توانید حساب خود را تکمیل کنید: {{payload.user.profileUrl}}',
                ),
                'sms' => array(
                    'body' => 'عضویت شما انجام شد. تکمیل حساب: {{payload.user.profileUrl}}',
                ),
                'email' => array(
                    'subject' => 'عضویت شما تکمیل شد',
                    'body'    => "سلام {{payload.user.displayName}},\n\nحساب شما ایجاد شد.\nپروفایل: {{payload.user.profileUrl}}\n",
                ),
            ),
        ),
        'listing.rejected' => array(
            'fa-IR' => array(
                'whatsapp' => array(
                    'body' => 'آگهی "{{payload.listing.title}}" نیاز به بازبینی دارد. از این لینک برای اصلاح استفاده کنید: {{payload.listing.editUrl}}',
                ),
            ),
        ),
        'listing.expiring_soon' => array(
            'fa-IR' => array(
                'whatsapp' => array(
                    'body' => 'آگهی "{{payload.listing.title}}" به‌زودی منقضی می‌شود. مدیریت: {{payload.listing.manageUrl}}',
                ),
            ),
        ),
        'payment.completed' => array(
            'fa-IR' => array(
                'whatsapp' => array(
                    'body' => 'پرداخت شما با موفقیت ثبت شد. شناسه سفارش: {{payload.payment.orderId}}',
                ),
            ),
        ),
    ),
);

$localOverrideFile = __DIR__ . DIRECTORY_SEPARATOR . 'notification-platform.local.php';
if (is_file($localOverrideFile)) {
    $override = require $localOverrideFile;
    if (is_array($override)) {
        $config = $mergeConfig($config, $override);
    }
}

return $config;
