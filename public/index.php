<?php

define('LARAVEL_START', microtime(true));

// Autoload
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap the app
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create & run the HTTP kernel
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request  = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Send the response & terminate
$response->send();
$kernel->terminate($request, $response);
