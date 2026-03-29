<?php

$host = '127.0.0.1';
$db   = 'dict_frcc';
$dbname = 'dict_frcc';
$username = 'root';
$password = '';
$pass = '';
$charset = 'utf8mb4';

// MySQLi connection for legacy pages and inserts
$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// PDO connection for pages that use prepared statements
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    $pdo = null;
    $db_error = $e->getMessage();
}
?>
