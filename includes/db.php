<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
try {
    $pdo = new PDO("mysql:host=localhost;dbname=win_lostproperty;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit("Database connection failed.");
}
