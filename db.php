<?php
// ============================================================
// includes/db.php
// Database connection configuration
// International Student Services Database
// ============================================================

$DB_HOST = "localhost";
$DB_NAME = "intl_students_db";
$DB_USER = "root";
$DB_PASS = "";        // default XAMPP password is blank

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
