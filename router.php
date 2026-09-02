<?php
declare(strict_types=1);

// Router for PHP's built-in development server.
$requestPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$projectRoot = realpath(__DIR__);
$requestedFile = realpath(__DIR__ . $requestPath);

if (
    $requestPath !== '/'
    && $projectRoot !== false
    && $requestedFile !== false
    && strpos($requestedFile, $projectRoot . DIRECTORY_SEPARATOR) === 0
    && is_file($requestedFile)
) {
    return false;
}

require __DIR__ . '/index.php';
