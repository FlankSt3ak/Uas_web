<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/Auth.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $errors[] = 'Username dan password harus diisi.';
    }
    if (empty($errors)) {
        $auth = new Auth();
        $result = $auth->login($username, $password);
        if ($result['success']) {
            // Login successful, redirect to dashboard
            // Set client-side flag for additional protection
            echo '<script>
                if (typeof Storage !== "undefined") {
                    localStorage.setItem("user_logged_in", "true");
                    localStorage.setItem("login_timestamp", "' . time() . '");
                    sessionStorage.setItem("logged_in", "true");
                }
            </script>';
            if (!headers_sent()) {
                header('Location: index.php?page=dashboard');
                exit;
            } else {
                echo '<script>window.location.href = "index.php?page=dashboard";</script>';
                exit;
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}
// If there is an error, save it in the session and redirect back to login.
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    if (!headers_sent()) {
        header('Location: index.php?page=login');
        exit;
    } else {
        echo 'Error redirect failed: ' . implode(', ', $errors);
        exit;
    }
}
// If not a POST request, redirect to login page
if (!headers_sent()) {
    header('Location: index.php?page=login');
    exit;
} else {
    echo 'Invalid request method';
    exit;
}
?>