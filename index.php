<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
header("Content-Security-Policy: default-src 'none'");

if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'http') {
        header('HTTP/1.1 400 BAD REQUEST');
        echo '400 BAD REQUEST';

        exit;
    }
} else {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        header('HTTP/1.1 400 BAD REQUEST');
        echo '400 BAD REQUEST';
        exit;
    }
}

require_once './bootstrap/app.php';
$app->run();
