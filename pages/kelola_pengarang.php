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
// Only Administrator and Operator can access this page
if (!in_array($user_level, ['Administrator', 'Operator'])) {
    echo '<p>Akses ditolak. Halaman ini hanya untuk Administrator dan Operator.</p>';
    exit;
}
require_once __DIR__ . '/../includes/config.php';
// Handle CRUD operations
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = db_connect();
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            if ($action === 'delete') {
                // Delete author
                $pengarang_id = trim($_POST['pengarang_id'] ?? '');
                if (empty($pengarang_id)) {
                    throw new Exception('ID Pengarang harus diisi.');
                }
                $stmt = mysqli_prepare($conn, "DELETE FROM tbl_pengarang WHERE pengarang_id = ?");
                mysqli_stmt_bind_param($stmt, 's', $pengarang_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Pengarang berhasil dihapus.';
                    $message_type = 'success';
                } else {
                    throw new Exception('Gagal menghapus pengarang: ' . mysqli_error($conn));
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
// Get all authors for display
$authors = [];
try {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT * FROM tbl_pengarang ORDER BY pengarang_id");
    while ($row = mysqli_fetch_assoc($result)) {
        $authors[] = $row;
    }
    mysqli_close($conn);
} catch (Exception $e) {
    $message = 'Error loading authors: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengarang - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="assets/kelola_pengarang.css">
</head>
<body>
    <div class="crud-container">
        <div class="crud-header">
            <h1>Kelola Pengarang</h1>
            <div>
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Kembali ke Dashboard</a>
                <a href="index.php?page=form_pengarang&action=add" class="btn btn-primary">Tambah Pengarang</a>
            </div>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <!-- Authors Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>ID Pengarang</th>
                    <th>Nama Pengarang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($authors)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">Belum ada pengarang.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($authors as $author): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($author['pengarang_id']); ?></td>
                            <td><?php echo htmlspecialchars($author['pengarang_nama']); ?></td>
                            <td>
                                <a href="index.php?page=form_pengarang&action=edit&id=<?php echo htmlspecialchars($author['pengarang_id']); ?>" class="btn btn-success">Edit</a>
                                <button onclick="deleteAuthor('<?php echo htmlspecialchars($author['pengarang_id']); ?>')" class="btn btn-danger">Hapus</button>
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
            <p>Apakah Anda yakin ingin menghapus pengarang ini?</p>
            <form method="post" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_pengarang_id" name="pengarang_id">
                <button type="submit" class="btn btn-danger">Hapus</button>
                <button type="button" onclick="closeModal('deleteModal')" class="btn btn-secondary">Batal</button>
            </form>
        </div>
    </div>
    <script>
        function deleteAuthor(id) {
            document.getElementById('delete_pengarang_id').value = id;
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
