<?php
// Set Zona Waktu (Opsional, untuk data waktu)
date_default_timezone_set('Asia/Jakarta');
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_uasweb');
function db_connect()
{
    // Try default host first. If host is 'localhost' and the socket is unavailable,
    // attempt TCP connection via 127.0.0.1 as a fallback (common XAMPP macOS issue).
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn && DB_HOST === 'localhost') {
        $conn = @mysqli_connect('127.0.0.1', DB_USER, DB_PASS, DB_NAME);
    }
    if (!$conn) {
        $err = mysqli_connect_error();
        // Throw an exception instead of dying so callers (CLI/web) can handle it gracefully
        throw new RuntimeException("Database connection failed: $err. Ensure MySQL is running and credentials in includes/config.php are correct. If you use XAMPP, start MySQL via the XAMPP control panel or run '/Applications/XAMPP/xamppfiles/xampp start'.");
    }
    // Set connection charset to utf8mb4 for proper Unicode handling
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}
// Optional helper to close connection
function db_close($conn)
{
    if ($conn) {
        mysqli_close($conn);
    }
}