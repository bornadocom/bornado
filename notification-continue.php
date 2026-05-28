<?php
declare(strict_types=1);

require __DIR__ . '/wp-load.php';

$token = isset($_GET['t']) ? trim((string) $_GET['t']) : '';
$target = home_url('/');

if ('' !== $token) {
    $target = add_query_arg(
        array(
            'bornado_continue_token' => $token,
        ),
        $target
    );
}

wp_safe_redirect($target, 302);
exit;
