<?php

declare(strict_types=1);

session_start();

date_default_timezone_set('Europe/Madrid');

// Carga simple de .env para desarrollo local sin dependencias externas.
$envPath = __DIR__ . '/../.env';
if (is_file($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

require __DIR__ . '/Database.php';
require __DIR__ . '/Repository.php';
require __DIR__ . '/helpers.php';

$repo = new Repository(Database::connection());
