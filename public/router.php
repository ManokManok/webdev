<?php

/**
 * Router for PHP's built-in web server (Railway / CLI).
 * Serves existing files from public/ directly; everything else goes to Symfony.
 */
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

require __DIR__ . '/index.php';
