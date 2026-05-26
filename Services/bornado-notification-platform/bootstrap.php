<?php
declare(strict_types=1);

if (!defined('BORNADO_NOTIFICATION_PLATFORM_DIR')) {
    define('BORNADO_NOTIFICATION_PLATFORM_DIR', __DIR__);
}

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'Bornado\\NotificationPlatform\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $relativePath  = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        $filePath      = BORNADO_NOTIFICATION_PLATFORM_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relativePath;

        if (is_file($filePath)) {
            require_once $filePath;
        }
    }
);
