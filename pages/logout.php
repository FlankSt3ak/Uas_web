<?php
require_once __DIR__ . '/../includes/Auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
$auth = new Auth();
$auth->logout();
// INSTANT cleanup and redirect
echo '<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Clear ALL storage instantly
        if (typeof Storage !== "undefined") {
            localStorage.clear();
            sessionStorage.clear();
            // Set logout flag
            sessionStorage.setItem("just_logged_out", "true");
        }
        // Immediate redirect to home
        window.location.replace("index.php?page=home");
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>';

// Fallback (should not reach here due to JavaScript redirect)
exit;