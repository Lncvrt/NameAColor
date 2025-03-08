<?php
function connectToDatabase() {
    include __DIR__ . "/../config/connection.php";
    try {
        $dsn = "mysql:host=$dbhost;dbname=$dbname;port=$dbport";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $conn = new PDO($dsn, $dbusername, $dbpass, $options);
    } catch (PDOException $e) {
        die("Failed to connect to database: " . $e->getMessage());
    }
    return $conn;
}