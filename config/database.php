<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_pm_kayaba');

function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
    $conn->set_charset("utf8mb4");
    return $conn;
}

function query($sql) {
    $conn = connectDB();
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

function escapeString($string) {
    $conn = connectDB();
    $escaped = $conn->real_escape_string($string);
    $conn->close();
    return $escaped;
}