<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Prevent caching for security
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : '';
// Special handling for login page - extra cache prevention
if ($page === 'login') {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    // Handle status check request
    if (isset($_GET['check_status'])) {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            http_response_code(200);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <h1>DAFTAR BUKU</h1>
    </header>
    <!-- Navigation/Menu -->
    <?php include 'includes/nav.php'; ?>
    <!-- Main Content Area -->
    <section>
        <div class="main-content">
            <?php
            // Load page based on query parameters
            $page_paths = [
                'home' => __DIR__ . '/public/home.php',
                'about' => __DIR__ . '/public/about.php',
                'contact' => __DIR__ . '/public/contact.php',
                'login' => __DIR__ . '/public/login.php',
                'dashboard' => __DIR__ . '/pages/dashboard.php',
                'logout' => __DIR__ . '/pages/logout.php',
                'kelola_kategori' => __DIR__ . '/pages/kelola_kategori.php',
                'form_kategori' => __DIR__ . '/pages/form_kategori.php',
                'kelola_penerbit' => __DIR__ . '/pages/kelola_penerbit.php',
                'form_penerbit' => __DIR__ . '/pages/form_penerbit.php',
                'kelola_pengarang' => __DIR__ . '/pages/kelola_pengarang.php',
                'form_pengarang' => __DIR__ . '/pages/form_pengarang.php',
                'kelola_buku' => __DIR__ . '/pages/kelola_buku.php',
                'form_buku' => __DIR__ . '/pages/form_buku.php',
                'kelola_user' => __DIR__ . '/pages/kelola_user.php',
                'form_user' => __DIR__ . '/pages/form_user.php',
            ];
            if (isset($page_paths[$page]) && file_exists($page_paths[$page])) {
                include $page_paths[$page];
            } else {
                // Fallback to public directory for any other pages
                $page_file = __DIR__ . '/public/' . basename($page) . '.php';
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    echo '<p>Halaman tidak ditemukan.</p>';
                }
            }
            ?>
        </div>
    </section>
    <footer>
        <p>&copy; 2025 - Daftar Buku Sederhana</p>
    </footer>
</body>
</html>