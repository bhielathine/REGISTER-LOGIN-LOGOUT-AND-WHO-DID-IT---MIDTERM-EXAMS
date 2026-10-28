<?php
require_once __DIR__ . '/config.php';

/** One shared PDO connection. */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements (SQL injection protection)
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Check config/config.php and import database/schema.sql.');
        }
    }
    return $pdo;
}
$pdo = db();
