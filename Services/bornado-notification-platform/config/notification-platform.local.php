<?php
declare(strict_types=1);

return array(
    'service' => array(
        'base_url'      => 'https://bornado.com',
        'shared_secret' => 'W6MgZximyrmt/CbO9djHA12cCPrbdlEy',
    ),
    'routing' => array(
        'channel_providers' => array(
            'whatsapp' => array('whatsapp-cloud-api'),
            'sms'      => array('dry-run'),
            'email'    => array('dry-run'),
        ),
    ),
    'providers' => array(
        'whatsapp-cloud-api' => array(
            'enabled'               => true,
            'base_url'              => 'https://graph.facebook.com',
            'api_version'           => 'v22.0',
            'phone_number_id'       => '1085939971276418',
            'access_token'          => 'EAAWSyHZCFB1kBRgm8clt0tlpb3iUoDQcnRRMqOJkX3zzlHSFonMDycZBOeB7itjzGGOqQZCSrsZC8tj11uYwYZBN5B9daoI7isVfXPwnvZBRRkZCKAW8EGTZBsqeZCOixcfGxXyuTd2GmW3wtZABbPZAevq6hYsIj78dZAxa8VFrw3Dy1A7DQJ8Bwpg84Eo2AQYt4bgqYItge1IaMkOz4PZChyBKP8JSbqX93rBVYAEM0IlG0LZCPPJiJlqtozV7aDKe6rhE5haBHAAno11n9vUNCHAHPwkocD',
            'message_mode'          => 'template',
            'default_language_code' => 'en_US',
            'text_fallback_enabled' => false,
            'timeout_seconds'       => 10,
            'verify_ssl'            => true,
            'template_map'          => array(
                'listing.published' => array(
                    'name'            => 'hello_world',
                    'language_code'   => 'en_US',
                    'body_parameters' => array(),
                ),
                'user.registered' => array(
                    'name'            => 'hello_world',
                    'language_code'   => 'en_US',
                    'body_parameters' => array(),
                ),
            ),
        ),
    ),
);
