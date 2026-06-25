<?php
declare(strict_types=1);

return array(
    'service' => array(
        'base_url' => 'https://bornado.com/Services/bornado-ai-extraction-platform/public/index.php',
        'shared_secret' => 'c3e6768e976c4a9f8780d091840de522',
        'ops_key' => 'e6306ce022904a42bd963b822764b780',
    ),
    'source' => array(
        'wordpress' => array(
            'base_url' => 'https://bornado.com',
            'username' => 'service-reader',
            'application_password' => 'GAzq bZ1y ocY6 YCQX GcNa NbpP',
            'timeout_seconds' => 12,
            'catalog_endpoint' => 'https://bornado.com/wp-json/bornado-ai-bridge/v1/catalog',
            'service_key' => 'e6306ce022904a42bd963b822764b780',
        ),
    ),
    'target' => array(
        'wordpress_bridge' => array(
            'ingest_endpoint' => 'https://bornado.com/wp-json/bornado-ai-bridge/v1/ingest?key=e6306ce022904a42bd963b822764b780',
            'service_key' => 'e6306ce022904a42bd963b822764b780',
            'timeout_seconds' => 12,
        ),
    ),
);