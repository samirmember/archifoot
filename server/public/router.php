<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicPath = __DIR__.$requestPath;

// Let the built-in server serve real files directly.
if ($requestPath !== '/' && is_file($publicPath)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/index.php';

require_once __DIR__.'/index.php';
