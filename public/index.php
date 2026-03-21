<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Redirect to web installer if not yet configured
if (!file_exists(__DIR__ . '/../.env')) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!str_starts_with($uri, '/install')) {
        header('Location: /install/');
        exit;
    }
}

// Check if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
