<?php

header('Content-Type: application/json');

echo json_encode([
    'opcache' => opcache_get_status(false)
], JSON_PRETTY_PRINT) . PHP_EOL;
