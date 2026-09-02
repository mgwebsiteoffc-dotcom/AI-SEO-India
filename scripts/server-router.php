<?php

// Router for the built-in `php -S` dev server (WASM PHP in this sandbox has no
// `php artisan serve`): serve real files from public/ directly and send every
// other request through Laravel's front controller. Mirrors Laravel server.php.
//
//   php -S 0.0.0.0:8123 -t public scripts/server-router.php

$publicPath = dirname(__DIR__).'/public';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = realpath($publicPath.$path);

if ($path !== '/' && $file !== false && is_file($file) && str_starts_with($file, realpath($publicPath))) {
    return false; // let the built-in server serve the static file
}

require $publicPath.'/index.php';
