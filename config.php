<?php
// Replace these values with the MySQL details shown in Arni's InfinityFree control panel.
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_XXXXXXXX_hospital');
define('DB_USER', 'if0_XXXXXXXX');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');
define('APP_NAME', 'Hospital Management System');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
