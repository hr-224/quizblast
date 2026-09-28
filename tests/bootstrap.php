<?php

// Every test run gets its own random APP_KEY instead of a static one committed to the
// repo — tests use RefreshDatabase and never need a stable key across runs, so there's
// nothing to gain from a fixed value and no secret-shaped string for a scanner to flag.
$key = 'base64:'.base64_encode(random_bytes(32));
putenv("APP_KEY={$key}");
$_ENV['APP_KEY'] = $key;
$_SERVER['APP_KEY'] = $key;

require __DIR__.'/../vendor/autoload.php';
