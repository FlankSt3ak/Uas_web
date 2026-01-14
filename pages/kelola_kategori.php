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
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = db_connect();
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            if ($action === 'delete') {
                // Delete category
                $kategori_id = trim($_POST['kategori_id'] ?? '');
                if (empty($kategori_id)) {
                    throw new Exception('ID Kategori harus diisi.');
                }
                $stmt = mysqli_prepare($conn, "DELETE FROM tbl_kategori WHERE kategori_id = ?");
                mysqli_stmt_bind_param($stmt, 's', $kategori_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Kategori berhasil dihapus.';
                    $message_type = 'success';
                    header('Location: index.php?page=kelola_kategori&message=' . urlencode($message) . '&type=' . $message_type);
                    exit;
                } else {
                    throw new Exception('Gagal menghapus kategori: ' . mysqli_error($conn));
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
// Get all categories for display
$categories = [];
try {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT * FROM tbl_kategori ORDER BY kategori_id");
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_close($conn);
} catch (Exception $e) {
    $message = 'Error loading categories: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="assets/kelola_kategori.css">
</head>
<body>
    <div class="crud-container">
        <div class="crud-header">
            <h1>Kelola Kategori</h1>
            <div>
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Kembali ke Dashboard</a>
                <a href="index.php?page=form_kategori&action=add" class="btn btn-primary">Tambah Kategori</a>
            </div>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <!-- Categories Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>ID Kategori</th>
                    <th>Nama Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">Belum ada kategori.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['kategori_id']); ?></td>
                            <td><?php echo htmlspecialchars($category['kategori_nama']); ?></td>
                            <td>
                                <a href="index.php?page=form_kategori&action=edit&id=<?php echo htmlspecialchars($category['kategori_id']); ?>" class="btn btn-success">Edit</a>
                                <a href="#" onclick="openDeleteModal('<?php echo htmlspecialchars($category['kategori_id']); ?>', '<?php echo htmlspecialchars($category['kategori_nama']); ?>')" class="btn btn-danger">Hapus</a>
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
            <div class="modal-header">Konfirmasi Hapus</div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus kategori <strong id="deleteCategoryName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <form method="post" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="kategori_id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Batal</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = '"' + name + '"';
            document.getElementById('deleteModal').style.display = 'block';
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>