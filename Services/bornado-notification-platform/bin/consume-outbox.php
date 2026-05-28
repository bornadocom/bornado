<?php
declare(strict_types=1);

use Bornado\NotificationPlatform\Application\QueueConsumer;

require_once dirname(__DIR__) . '/bootstrap.php';

/** @var array<string,mixed> $config */
$config = require dirname(__DIR__) . '/config/notification-platform.php';

$limit = 20;

if (!empty($argv[1]) && is_numeric($argv[1])) {
    $limit = max(1, (int) $argv[1]);
}

$consumer = new QueueConsumer($config);
$result   = $consumer->run($limit, false);

echo sprintf("Processed %d event(s).\n", (int) ($result['processed'] ?? 0));
