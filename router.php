<?php
/**
 * Router for the PHP built-in server:
 *   php -S 127.0.0.1:8031 router.php
 * Serves existing files directly, everything else goes through index.php.
 */
require_once __DIR__ . '/includes/compat.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    if (str_ends_with($file, '.php')) {
        require $file;
        return true;
    }
    return false;   // let the built-in server stream static assets
}

// directory with its own index.php (e.g. /admin/) — mirrors DirectoryIndex
if ($path !== '/' && is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    require rtrim($file, '/') . '/index.php';
    return true;
}

require __DIR__ . '/index.php';
