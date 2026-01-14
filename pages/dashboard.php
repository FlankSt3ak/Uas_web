<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Prevent caching of dashboard page
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
$user_level = $_SESSION['user_level'] ?? 'Operator';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';
$username = $_SESSION['username'] ?? 'user';
$user_foto = $_SESSION['user_foto'] ?? null;
// Get total users count
require_once __DIR__ . '/../includes/config.php';
try {
    $conn = db_connect();
    // Total Users
    $result = mysqli_query($conn, "SELECT COUNT(*) as total_users FROM tbl_user");
    $row = mysqli_fetch_assoc($result);
    $total_users = $row['total_users'];
    // Total Books
    $result = mysqli_query($conn, "SELECT COUNT(*) as total_buku FROM tbl_buku");
    $row = mysqli_fetch_assoc($result);
    $total_buku = $row['total_buku'];
    // Total Authors
    $result = mysqli_query($conn, "SELECT COUNT(*) as total_pengarang FROM tbl_pengarang");
    $row = mysqli_fetch_assoc($result);
    $total_pengarang = $row['total_pengarang'];
    // Total Publishers
    $result = mysqli_query($conn, "SELECT COUNT(*) as total_penerbit FROM tbl_penerbit");
    $row = mysqli_fetch_assoc($result);
    $total_penerbit = $row['total_penerbit'];
    mysqli_close($conn);
} catch (Exception $e) {
    $total_users = $total_buku = $total_pengarang = $total_penerbit = 0; // Fallback if database error
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="../includes/styles.css">
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <?php if ($user_foto): ?>
                <img src="<?php echo htmlspecialchars($user_foto); ?>" alt="Avatar" class="user-avatar">
            <?php else: ?>
                <div class="user-avatar" style="background: #007bff; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">
                    <?php echo strtoupper(substr($nama_lengkap, 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="user-info">
                <h2>Selamat datang, <?php echo htmlspecialchars($nama_lengkap); ?>!</h2>
                <p>Level: <strong><?php echo htmlspecialchars($user_level); ?></strong> | Username: <?php echo htmlspecialchars($username); ?></p>
            </div>
        </div>
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <?php if ($user_level === 'Administrator'): ?>
                <!-- Administrator Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="total-users"><?php echo htmlspecialchars($total_users); ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-buku"><?php echo htmlspecialchars($total_buku); ?></h3>
                        <p>Total Buku</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-penerbit"><?php echo htmlspecialchars($total_penerbit); ?></h3>
                        <p>Total Penerbit</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-pengarang"><?php echo htmlspecialchars($total_pengarang); ?></h3>
                        <p>Total Pengarang</p>
                    </div>
                </div>
                <div class="admin-features">
                    <div class="feature-card">
                        <h4>Kelola Kategori</h4>
                        <a href="?page=kelola_kategori">Kelola Kategori</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Penerbit</h4>
                        <a href="?page=kelola_penerbit">Kelola Penerbit</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Pengarang</h4>
                        <a href="?page=kelola_pengarang">Kelola Pengarang</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Buku</h4>
                        <a href="?page=kelola_buku">Kelola Buku</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Users</h4>
                        <a href="?page=kelola_user">Kelola Users</a>
                    </div>
                </div>
            <?php elseif ($user_level === 'Operator'): ?>
                <!-- Operator Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="total-buku"><?php echo htmlspecialchars($total_buku); ?></h3>
                        <p>Total Buku</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-pengarang"><?php echo htmlspecialchars($total_pengarang); ?></h3>
                        <p>Total Pengarang</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-penerbit"><?php echo htmlspecialchars($total_penerbit); ?></h3>
                        <p>Total Penerbit</p>
                    </div>
                </div>
                <div class="admin-features">
                    <div class="feature-card">
                        <h4>Kelola Kategori</h4>
                        <a href="?page=kelola_kategori">Kelola Kategori</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Penerbit</h4>
                        <a href="?page=kelola_penerbit">Kelola Penerbit</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Pengarang</h4>
                        <a href="?page=kelola_pengarang">Kelola Pengarang</a>
                    </div>
                    <div class="feature-card">
                        <h4>Kelola Buku</h4>
                        <a href="?page=kelola_buku">Kelola Buku</a>
                    </div>
                </div>
            <?php endif; ?>
            <!-- Recent Activity (sama untuk semua level) -->
            <div class="recent-activity">
                <h3>Aktivitas Terbaru</h3>
                <ul class="activity-list">
                    <li class="activity-item">
                        <span>Login ke sistem</span>
                        <span class="activity-time"><?php echo date('H:i:s'); ?> hari ini</span>
                    </li>
                    <li class="activity-item">
                        <span>Mengakses dashboard</span>
                        <span class="activity-time"><?php echo date('H:i:s'); ?> hari ini</span>
                    </li>
                    <!-- Tambahkan aktivitas lain sesuai kebutuhan -->
                </ul>
            </div>
        </div>
    </div>
    <script>
        // Mark that user is logged in in localStorage
        if (typeof Storage !== 'undefined') {
            localStorage.setItem('user_logged_in', 'true');
            localStorage.setItem('login_timestamp', '<?php echo time(); ?>');
            sessionStorage.setItem('logged_in', 'true');
            sessionStorage.setItem('on_login_page', 'false');
        }
        // Ultra-aggressive back button prevention - NO WARNINGS, SILENT REDIRECT
        (function() {
            // Replace entire browser history with dashboard
            history.replaceState({
                page: 'dashboard',
                preventBack: true
            }, 'Dashboard', location.href);
            // Push multiple states to make back button useless
            for (let i = 0; i < 10; i++) {
                history.pushState({
                    page: 'dashboard',
                    preventBack: true
                }, 'Dashboard', location.href);
            }
            // Handle any back/forward navigation - SILENT redirect
            window.addEventListener('popstate', function(event) {
                // Immediately push another state and redirect without any user interaction
                history.pushState({
                    page: 'dashboard',
                    preventBack: true
                }, 'Dashboard', location.href);
                // Force redirect without any delay or confirmation
                if (typeof Storage !== 'undefined' &&
                    localStorage.getItem('user_logged_in') === 'true') {
                    window.location.replace('index.php?page=dashboard');
                }
            });
            // Continuous monitoring - redirect if somehow we end up elsewhere
            setInterval(function() {
                if (typeof Storage !== 'undefined' &&
                    localStorage.getItem('user_logged_in') === 'true') {
                    if (!window.location.href.includes('page=dashboard')) {
                        window.location.replace('index.php?page=dashboard');
                    }
                }
            }, 500);
        })();
        // Prevent caching
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>