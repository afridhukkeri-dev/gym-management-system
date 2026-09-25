<?php
require_once __DIR__ . '/config.php';

/**
 * PDO database connection helper.
 * This file intentionally does not expose credentials to the browser.
 */
function getDatabaseConnection(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log('Database connection failed: ' . $e->getMessage());
        }

        throw new RuntimeException('Database connection failed. Please check the database configuration.');
    }
}
