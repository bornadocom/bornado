<?php
declare(strict_types=1);

$sharedSecret = trim((string) (getenv('BORNADO_NOTIFICATION_SHARED_SECRET') ?: 'be67d9eef04f5f9cffbbbcf811e5bd02'));
$opsKey       = trim((string) (getenv('BORNADO_NOTIFICATION_OPS_KEY') ?: $sharedSecret));
$phoneNumberId = trim((string) (getenv('BORNADO_WA_PHONE_NUMBER_ID') ?: '1099127826622214'));
$accessToken   = trim((string) (getenv('BORNADO_WA_ACCESS_TOKEN') ?: 'EAAWSyHZCFB1kBRjpUNhi73IGPynCZClZAJiG8TxgLLZCDqPi6c3yz2cZAyafwQGpkh9stMalIVMxEqjVqvZCwfPXHpcKzyd5OimElrc5zZBcDbLZBlmbuWhPPahakiyohP4xFA3VaJ48N2cPNlwxNRi6MMShO3wbOg5ymfrxIy9gfePGjgfY2bNZCZCQhZBtKp2Uj7XUgZDZD'));

return array(
    'service' => array(
        'base_url'      => 'https://bornado.com',
        'shared_secret' => $sharedSecret,
        'ops_key'       => $opsKey,
    ),
    'routing' => array(
        'events' => array(
            'listing.published' => array(
                'channels' => array('whatsapp'),
            ),
            'user.registered' => array(
                'channels' => array(),
            ),
            'listing.rejected' => array(
                'channels' => array(),
            ),
            'listing.expiring_soon' => array(
                'channels' => array(),
            ),
            'payment.completed' => array(
                'channels' => array(),
            ),
        ),
        'channel_providers' => array(
            'whatsapp' => array('whatsapp-cloud-api'),
            'sms'      => array(),
            'email'    => array(),
        ),
    ),
    'providers' => array(
        'whatsapp-cloud-api' => array(
            'enabled'               => true,
            'base_url'              => 'https://graph.facebook.com',
            'api_version'           => 'v22.0',
            'phone_number_id'       => $phoneNumberId,
            'access_token'          => $accessToken,
            'message_mode'          => 'template',
            'default_language_code' => 'fa',
            'text_fallback_enabled' => false,
            'timeout_seconds'       => 10,
            'verify_ssl'            => true,
            'template_map'          => array(
                'listing.published' => array(
                    'name'            => 'listing_published_manage_fa_v2',
                    'language_code'   => 'fa',
                    'header_media'    => array(
                        'type' => 'image',
                        'link' => 'https://bornado.com/wp-content/uploads/2026/05/Bornado.png',
                    ),
                    'body_parameters' => array(
                        array(
                            'name' => 'listing_title',
                            'path' => 'payload.listing.title',
                        ),
                    ),
                    'button_parameters' => array(
                        array(
                            'sub_type'   => 'url',
                            'index'      => 0,
                            'parameters' => array(
                                'payload.listing.continueToken',
                            ),
                        ),
                    ),
                ),
                'user.registered' => array(
                    'name'            => 'hello_world',
                    'language_code'   => 'en_US',
                    'body_parameters' => array(),
                ),
            ),
        ),
    ),
    'webhooks' => array(
        'whatsapp' => array(
            'verify_token'    => trim((string) (getenv('BORNADO_WA_WEBHOOK_VERIFY_TOKEN') ?: '77849858769122034828')),
            'app_secret'      => trim((string) (getenv('BORNADO_WA_APP_SECRET') ?: 'be67d9eef04f5f9cffbbbcf811e5bd02')),
            'phone_number_id' => $phoneNumberId,
            'inbound_mode'    => trim((string) (getenv('BORNADO_WA_WEBHOOK_INBOUND_MODE') ?: 'log_only')),
        ),
    ),
);
