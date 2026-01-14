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
            if ($action === 'add') {
                // Add new publisher
                $penerbit_id = trim($_POST['penerbit_id'] ?? '');
                $penerbit_nama = trim($_POST['penerbit_nama'] ?? '');
                if (empty($penerbit_id) || empty($penerbit_nama)) {
                    throw new Exception('ID Penerbit dan Nama Penerbit harus diisi.');
                }
                // Check if penerbit_id already exists
                $stmt = mysqli_prepare($conn, "SELECT penerbit_id FROM tbl_penerbit WHERE penerbit_id = ?");
                mysqli_stmt_bind_param($stmt, 's', $penerbit_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    throw new Exception('ID Penerbit sudah ada.');
                }
                mysqli_stmt_close($stmt);
                // Insert new publisher
                $stmt = mysqli_prepare($conn, "INSERT INTO tbl_penerbit (penerbit_id, penerbit_nama) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ss', $penerbit_id, $penerbit_nama);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Penerbit berhasil ditambahkan.';
                    $message_type = 'success';
                    header('Location: index.php?page=kelola_penerbit&message=' . urlencode($message) . '&type=' . $message_type);
                    exit;
                } else {
                    throw new Exception('Gagal menambahkan penerbit: ' . mysqli_error($conn));
                }
            } elseif ($action === 'edit') {
                // Edit publisher
                $penerbit_id = trim($_POST['penerbit_id'] ?? '');
                $penerbit_nama = trim($_POST['penerbit_nama'] ?? '');
                if (empty($penerbit_id) || empty($penerbit_nama)) {
                    throw new Exception('ID Penerbit dan Nama Penerbit harus diisi.');
                }
                $stmt = mysqli_prepare($conn, "UPDATE tbl_penerbit SET penerbit_nama = ? WHERE penerbit_id = ?");
                mysqli_stmt_bind_param($stmt, 'ss', $penerbit_nama, $penerbit_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Penerbit berhasil diupdate.';
                    $message_type = 'success';
                    header('Location: index.php?page=kelola_penerbit&message=' . urlencode($message) . '&type=' . $message_type);
                    exit;
                } else {
                    throw new Exception('Gagal mengupdate penerbit: ' . mysqli_error($conn));
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
// Get action from GET parameter
$action = $_GET['action'] ?? 'add';
$edit_id = $_GET['id'] ?? '';
$edit_data = null;
if ($action === 'edit' && !empty($edit_id)) {
    try {
        $conn = db_connect();
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_penerbit WHERE penerbit_id = ?");
        mysqli_stmt_bind_param($stmt, 's', $edit_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $edit_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    } catch (Exception $e) {
        $message = 'Error loading publisher for edit: ' . $e->getMessage();
        $message_type = 'error';
        $action = 'add';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'edit' ? 'Edit' : 'Tambah'; ?> Penerbit - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="assets/form_penerbit.css">
</head>
<body>
    <div class="form-container">
        <h2><?php echo $action === 'edit' ? 'Edit' : 'Tambah'; ?> Penerbit</h2>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($action === 'add'): ?>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="penerbit_id">ID Penerbit:</label>
                    <input type="text" id="penerbit_id" name="penerbit_id" required maxlength="5">
                </div>
                <div class="form-group">
                    <label for="penerbit_nama">Nama Penerbit:</label>
                    <input type="text" id="penerbit_nama" name="penerbit_nama" required maxlength="150">
                </div>
                <button type="submit" class="btn btn-primary">Tambah</button>
                <a href="index.php?page=kelola_penerbit" class="btn btn-secondary">Batal</a>
            </form>
        <?php elseif ($action === 'edit' && $edit_data): ?>
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <div class="form-group">
                    <label for="penerbit_id">ID Penerbit:</label>
                    <input type="text" id="penerbit_id" name="penerbit_id" value="<?php echo htmlspecialchars($edit_data['penerbit_id']); ?>" readonly required>
                </div>
                <div class="form-group">
                    <label for="penerbit_nama">Nama Penerbit:</label>
                    <input type="text" id="penerbit_nama" name="penerbit_nama" value="<?php echo htmlspecialchars($edit_data['penerbit_nama']); ?>" required maxlength="150">
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php?page=kelola_penerbit" class="btn btn-secondary">Batal</a>
            </form>
        <?php else: ?>
            <p>Halaman tidak valid.</p>
            <a href="index.php?page=kelola_penerbit" class="btn btn-secondary">Kembali</a>
        <?php endif; ?>
    </div>
</body>
</html>
