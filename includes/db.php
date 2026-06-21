<?php
// ============================================================
// includes/db.php
// Database connection configuration
// International Student Services Database
// DB Environment variables needs to be added to Railway
// ============================================================

// $DB_HOST = "localhost";
// $DB_HOST = "127.0.0.1";
// $DB_NAME = "intl_students_db";
// $DB_USER = "root";
// $DB_PORT = "3306";
// $DB_PASS = "";        // default XAMPP password is blank


// try {
//     $pdo = new PDO(
//         "mysql:host={$DB_HOST};dbname={$DB_NAME};port=3306;charset=utf8mb4",
//         $DB_USER,
//         $DB_PASS,
//         [
//             PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//             PDO::ATTR_EMULATE_PREPARES   => false,
//         ]
//     );
// } catch (PDOException $e) {
//     die("Database connection failed: " . $e->getMessage());
// }

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$db = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

try{
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

return $pdo;
?>
