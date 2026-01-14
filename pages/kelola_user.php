<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
// Check if user is logged in and has appropriate level
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
$user_level = $_SESSION['user_level'] ?? 'Operator';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';
// Only Administrator can access this page
if ($user_level !== 'Administrator') {
    echo '<p>Akses ditolak. Halaman ini hanya untuk Administrator.</p>';
    exit;
}
require_once __DIR__ . '/../includes/config.php';
// Handle CRUD operations
$message = '';
$message_type = '';
// Check for messages from form_user.php redirects
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = db_connect();
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            if ($action === 'delete') {
                // Delete user
                $user_id = (int)($_POST['user_id'] ?? 0);
                if (empty($user_id)) {
                    throw new Exception('User ID harus diisi.');
                }
                // Prevent deleting own account
                if ($user_id == $_SESSION['user_id']) {
                    throw new Exception('Anda tidak dapat menghapus akun sendiri.');
                }
                $stmt = mysqli_prepare($conn, "DELETE FROM tbl_user WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'User berhasil dihapus.';
                    $message_type = 'success';
                } else {
                    throw new Exception('Gagal menghapus user: ' . mysqli_error($conn));
                }
            }
            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }
        }
        mysqli_close($conn);
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}
// Get all users for display
$users = [];
try {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT * FROM tbl_user ORDER BY nama_lengkap");
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_close($conn);
} catch (Exception $e) {
    $message = 'Error loading users: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Administrator</title>
    <link rel="stylesheet" href="assets/kelola_user.css">
</head>
<body>
    <div class="crud-container">
        <div class="crud-header">
            <h1>Kelola User</h1>
            <div>
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Kembali ke Dashboard</a>
                <a href="index.php?page=form_user" class="btn btn-primary">Tambah User</a>
            </div>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <!-- Users Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Belum ada user.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($user['user_email']); ?></td>
                            <td>
                                <span class="user-level <?php echo htmlspecialchars($user['user_level']); ?>">
                                    <?php echo htmlspecialchars($user['user_level']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($user['user_foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['user_foto']); ?>" alt="Foto <?php echo htmlspecialchars($user['nama_lengkap']); ?>" class="user-photo">
                                <?php else: ?>
                                    <div class="user-photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="index.php?page=form_user&id=<?php echo $user['user_id']; ?>" class="btn btn-success">Edit</a>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" class="btn btn-danger">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Hapus</h2>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <p>Apakah Anda yakin ingin menghapus user ini?</p>
            <form method="post" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_user_id" name="user_id">
                <button type="submit" class="btn btn-danger">Hapus</button>
                <button type="button" onclick="closeModal('deleteModal')" class="btn btn-secondary">Batal</button>
            </form>
        </div>
    </div>
    <script>
        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>