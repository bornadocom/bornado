<?php
declare(strict_types=1);

$_SERVER['REQUEST_URI'] = '/webhooks/whatsapp';

require __DIR__ . '/Services/bornado-notification-platform/public/index.php';
