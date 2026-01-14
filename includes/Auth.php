<?php
require_once __DIR__ . '/config.php';
class Auth
{
    private $conn;
    public function __construct($conn = null)
    {
        $this->conn = $conn ?: db_connect();
    }
    /**
     * Attempt to log the user in.
     * Returns an array: ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login(string $username, string $password): array
    {
        $username = trim($username);
        // Use exact column names from tbl_user: user_id, username, password, user_level, nama_lengkap, user_foto
        $stmt = $this->conn->prepare("SELECT user_id, username, password, user_level, nama_lengkap, user_foto FROM tbl_user WHERE username = ? LIMIT 1");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Query prepare failed.'];
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $hashed = $row['password'];
            // Prefer secure hashed passwords (password_hash). If your DB uses plain text,
            // the fallback below allows login for backward compatibility (less secure).
            $passwordOk = false;
            if (password_verify($password, $hashed)) {
                $passwordOk = true;
            } elseif (hash_equals($hashed, $password)) {
                // plain text fallback (not recommended)
                $passwordOk = true;
            }
            if ($passwordOk) {
                // Session should already be started by caller, but ensure here too.
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                // Set session values based on tbl_user columns
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_level'] = $row['user_level'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                $_SESSION['user_foto'] = $row['user_foto'];
                return ['success' => true, 'message' => 'Login berhasil', 'user' => $row];
            }
            return ['success' => false, 'message' => 'Username atau password salah.'];
        }
        return ['success' => false, 'message' => 'Username atau password salah.'];
    }
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Unset all session variables
        $_SESSION = [];
        // Delete session cookie if present
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        // Destroy session
        session_destroy();
    }
    /**
     * Optional helper: check if logged in
     */
    public function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['user_id']);
    }
    /**
     * Optional helper: require a role (or array of roles)
     */
    public function requireRole($roles)
    {
        if (!$this->check()) {
            header('Location: index.php?page=login');
            exit;
        }
        $userLevel = $_SESSION['user_level'] ?? '';
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array($userLevel, $allowed, true)) {
            // Not authorized
            http_response_code(403);
            echo 'Akses ditolak.';
            exit;
        }
    }
}