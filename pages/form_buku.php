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
                // Add new book
                $buku_isbn = trim($_POST['buku_isbn'] ?? '');
                $buku_judul = trim($_POST['buku_judul'] ?? '');
                $penerbit_id = trim($_POST['penerbit_id'] ?? '');
                $buku_tglterbit_raw = trim($_POST['buku_tglterbit'] ?? '');
                $buku_jmlhalaman = (int)($_POST['buku_jmlhalaman'] ?? 0);
                $buku_deskripsi = trim($_POST['buku_deskripsi'] ?? '');
                $buku_harga = (float)($_POST['buku_harga'] ?? 0);
                $kategori_id = trim($_POST['kategori_id'] ?? '');
                $pengarang_id = trim($_POST['pengarang_id'] ?? '');
                // Validate and format date
                $buku_tglterbit = null;
                if (!empty($buku_tglterbit_raw)) {
                    $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y'];
                    foreach ($formats as $format) {
                        $date = DateTime::createFromFormat($format, $buku_tglterbit_raw);
                        if ($date !== false) {
                            $buku_tglterbit = $date->format('Y-m-d');
                            break;
                        }
                    }
                }
                if (empty($buku_isbn) || empty($buku_judul) || empty($penerbit_id) ||
                    empty($kategori_id) || empty($pengarang_id) || empty($buku_tglterbit)) {
                    throw new Exception('ISBN, Judul, Penerbit, Kategori, Pengarang, dan Tanggal Terbit harus diisi.');
                }
                // Check if penerbit_id exists
                $stmt = mysqli_prepare($conn, "SELECT penerbit_id FROM tbl_penerbit WHERE penerbit_id = ?");
                mysqli_stmt_bind_param($stmt, 's', $penerbit_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 0) {
                    throw new Exception('Penerbit tidak valid.');
                }
                mysqli_stmt_close($stmt);
                // Check if kategori_id exists
                if ($kategori_id !== null && $kategori_id !== '') {
                    $stmt = mysqli_prepare($conn, "SELECT kategori_id FROM tbl_kategori WHERE kategori_id = ?");
                    mysqli_stmt_bind_param($stmt, 's', $kategori_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 0) {
                        throw new Exception('Kategori tidak valid.');
                    }
                    mysqli_stmt_close($stmt);
                }
                // Check if pengarang_id exists
                if ($pengarang_id !== null && $pengarang_id !== '') {
                    $stmt = mysqli_prepare($conn, "SELECT pengarang_id FROM tbl_pengarang WHERE pengarang_id = ?");
                    mysqli_stmt_bind_param($stmt, 's', $pengarang_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 0) {
                        throw new Exception('Pengarang tidak valid.');
                    }
                    mysqli_stmt_close($stmt);
                }
                // Check if buku_isbn already exists
                $stmt = mysqli_prepare($conn, "SELECT buku_isbn FROM tbl_buku WHERE buku_isbn = ?");
                mysqli_stmt_bind_param($stmt, 's', $buku_isbn);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    throw new Exception('ISBN Buku sudah ada.');
                }
                mysqli_stmt_close($stmt);
                // Insert new book
                $stmt = mysqli_prepare($conn, "INSERT INTO tbl_buku (buku_isbn, buku_judul, kategori_id, penerbit_id, pengarang_id, buku_tglterbit, buku_jmlhalaman, buku_deskripsi, buku_harga) VALUES (?, ?, ?, ?, ?, DATE(?), ?, ?, ?)");
                // types: isbn(s), judul(s), kategori_id(s), penerbit_id(s), pengarang_id(s), tgl(s), jmlhalaman(i), deskripsi(s), harga(d)
                mysqli_stmt_bind_param($stmt, 'ssssssisd', $buku_isbn, $buku_judul, $kategori_id, $penerbit_id, $pengarang_id, $buku_tglterbit, $buku_jmlhalaman, $buku_deskripsi, $buku_harga);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Buku berhasil ditambahkan.';
                    $message_type = 'success';
                    header('Location: index.php?page=kelola_buku&message=' . urlencode($message) . '&type=' . $message_type);
                    exit;
                } else {
                    throw new Exception('Gagal menambahkan buku: ' . mysqli_error($conn));
                }
            } elseif ($action === 'edit') {
                // Edit book
                $buku_isbn = trim($_POST['buku_isbn'] ?? '');
                $buku_judul = trim($_POST['buku_judul'] ?? '');
                $penerbit_id = trim($_POST['penerbit_id'] ?? '');
                $buku_tglterbit_raw = trim($_POST['buku_tglterbit'] ?? '');
                $buku_jmlhalaman = (int)($_POST['buku_jmlhalaman'] ?? 0);
                $buku_deskripsi = trim($_POST['buku_deskripsi'] ?? '');
                $buku_harga = (float)($_POST['buku_harga'] ?? 0);
                $kategori_id = trim($_POST['kategori_id'] ?? '');
                $pengarang_id = trim($_POST['pengarang_id'] ?? '');
                // Validate and format date
                $buku_tglterbit = '';
                if (!empty($buku_tglterbit_raw)) {
                    $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y'];
                    foreach ($formats as $format) {
                        $date = DateTime::createFromFormat($format, $buku_tglterbit_raw);
                        if ($date !== false) {
                            $buku_tglterbit = $date->format('Y-m-d');
                            break;
                        }
                    }
                    if ($buku_tglterbit === '') {
                        $buku_tglterbit = '';
                    }
                }
                if (empty($buku_isbn) || empty($buku_judul) || empty($penerbit_id) ||
                    empty($buku_tglterbit) || empty($kategori_id) || empty($pengarang_id)) {
                    throw new Exception('ISBN, Judul, Penerbit, Tanggal Terbit, Kategori, dan Pengarang harus diisi.');
                }
                // Check if penerbit_id exists
                $stmt_check = mysqli_prepare($conn, "SELECT penerbit_id FROM tbl_penerbit WHERE penerbit_id = ?");
                mysqli_stmt_bind_param($stmt_check, 's', $penerbit_id);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) == 0) {
                    throw new Exception('Penerbit tidak valid.');
                }
                mysqli_stmt_close($stmt_check);
                // Check if kategori_id exists
                $stmt_check = mysqli_prepare($conn, "SELECT kategori_id FROM tbl_kategori WHERE kategori_id = ?");
                mysqli_stmt_bind_param($stmt_check, 's', $kategori_id);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) == 0) {
                    throw new Exception('Kategori tidak valid.');
                }
                mysqli_stmt_close($stmt_check);
                // Check if pengarang_id exists
                $stmt_check = mysqli_prepare($conn, "SELECT pengarang_id FROM tbl_pengarang WHERE pengarang_id = ?");
                mysqli_stmt_bind_param($stmt_check, 's', $pengarang_id);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) == 0) {
                    throw new Exception('Pengarang tidak valid.');
                }
                mysqli_stmt_close($stmt_check);
                $stmt = mysqli_prepare($conn, "UPDATE tbl_buku SET buku_judul = ?, kategori_id = ?, penerbit_id = ?, pengarang_id = ?, buku_tglterbit = DATE(?), buku_jmlhalaman = ?, buku_deskripsi = ?, buku_harga = ? WHERE buku_isbn = ?");
                // types: judul(s), kategori_id(s), penerbit_id(s), pengarang_id(s), tgl(s), jmlhalaman(i), deskripsi(s), harga(d), isbn(s)
                mysqli_stmt_bind_param($stmt, 'sssssisds', $buku_judul, $kategori_id, $penerbit_id, $pengarang_id, $buku_tglterbit, $buku_jmlhalaman, $buku_deskripsi, $buku_harga, $buku_isbn);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Buku berhasil diupdate.';
                    $message_type = 'success';
                    header('Location: index.php?page=kelola_buku&message=' . urlencode($message) . '&type=' . $message_type);
                    exit;
                } else {
                    throw new Exception('Gagal mengupdate buku: ' . mysqli_error($conn));
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
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_buku WHERE buku_isbn = ?");
        mysqli_stmt_bind_param($stmt, 's', $edit_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $edit_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    } catch (Exception $e) {
        $message = 'Error loading book for edit: ' . $e->getMessage();
        $message_type = 'error';
        $action = 'add';
    }
}
// Get dropdown data
$publishers = [];
$categories = [];
$authors = [];
try {
    $conn = db_connect();
    $result = mysqli_query($conn, "SELECT * FROM tbl_penerbit ORDER BY penerbit_nama");
    while ($row = mysqli_fetch_assoc($result)) {
        $publishers[] = $row;
    }
    $result = mysqli_query($conn, "SELECT * FROM tbl_kategori ORDER BY kategori_nama");
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    $result = mysqli_query($conn, "SELECT * FROM tbl_pengarang ORDER BY pengarang_nama");
    while ($row = mysqli_fetch_assoc($result)) {
        $authors[] = $row;
    }
    mysqli_close($conn);
} catch (Exception $e) {
    $message = 'Error loading dropdown data: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'edit' ? 'Edit' : 'Tambah'; ?> Buku - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="assets/form_buku.css">
</head>
<body>
    <div class="form-container">
        <h2><?php echo $action === 'edit' ? 'Edit' : 'Tambah'; ?> Buku</h2>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($action === 'add'): ?>
            <form method="post" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="buku_isbn">ISBN:</label>
                        <input type="text" id="buku_isbn" name="buku_isbn" required maxlength="20">
                    </div>
                    <div class="form-group">
                        <label for="buku_judul">Judul Buku:</label>
                        <input type="text" id="buku_judul" name="buku_judul" required maxlength="200">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="penerbit_id">Penerbit:</label>
                        <select id="penerbit_id" name="penerbit_id" required>
                            <option value="">Pilih Penerbit</option>
                            <?php foreach ($publishers as $publisher): ?>
                                <option value="<?php echo htmlspecialchars($publisher['penerbit_id']); ?>"><?php echo htmlspecialchars($publisher['penerbit_nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="buku_tglterbit">Tanggal Terbit:</label>
                        <input type="date" id="buku_tglterbit" name="buku_tglterbit" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="buku_jmlhalaman">Jumlah Halaman:</label>
                        <input type="number" id="buku_jmlhalaman" name="buku_jmlhalaman" min="1">
                    </div>
                    <div class="form-group">
                        <label for="buku_harga">Harga:</label>
                        <input type="number" id="buku_harga" name="buku_harga" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="kategori_id">Kategori:</label>
                        <select id="kategori_id" name="kategori_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['kategori_id']); ?>"><?php echo htmlspecialchars($category['kategori_nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pengarang_id">Pengarang:</label>
                        <select id="pengarang_id" name="pengarang_id" required>
                            <option value="">Pilih Pengarang</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo htmlspecialchars($author['pengarang_id']); ?>"><?php echo htmlspecialchars($author['pengarang_nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="buku_deskripsi">Deskripsi:</label>
                    <textarea id="buku_deskripsi" name="buku_deskripsi" maxlength="1000"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Tambah</button>
                <a href="index.php?page=kelola_buku" class="btn btn-secondary">Batal</a>
            </form>
        <?php elseif ($action === 'edit' && $edit_data): ?>
            <form method="post" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="edit">
                <div class="form-row">
                    <div class="form-group">
                        <label for="buku_isbn">ISBN:</label>
                        <input type="text" id="buku_isbn" name="buku_isbn" value="<?php echo htmlspecialchars($edit_data['buku_isbn']); ?>" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="buku_judul">Judul Buku:</label>
                        <input type="text" id="buku_judul" name="buku_judul" value="<?php echo htmlspecialchars($edit_data['buku_judul']); ?>" required maxlength="200">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="penerbit_id">Penerbit:</label>
                        <select id="penerbit_id" name="penerbit_id" required>
                            <option value="">Pilih Penerbit</option>
                            <?php foreach ($publishers as $publisher): ?>
                                <option value="<?php echo htmlspecialchars($publisher['penerbit_id']); ?>" <?php echo $publisher['penerbit_id'] === $edit_data['penerbit_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($publisher['penerbit_nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="buku_tglterbit">Tanggal Terbit:</label>
                        <input type="date" id="buku_tglterbit" name="buku_tglterbit" value="<?php echo htmlspecialchars($edit_data['buku_tglterbit'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="buku_jmlhalaman">Jumlah Halaman:</label>
                        <input type="number" id="buku_jmlhalaman" name="buku_jmlhalaman" value="<?php echo htmlspecialchars($edit_data['buku_jmlhalaman']); ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label for="buku_harga">Harga:</label>
                        <input type="number" id="buku_harga" name="buku_harga" value="<?php echo htmlspecialchars($edit_data['buku_harga']); ?>" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="kategori_id">Kategori:</label>
                        <select id="kategori_id" name="kategori_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['kategori_id']); ?>" <?php echo $category['kategori_id'] === $edit_data['kategori_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['kategori_nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pengarang_id">Pengarang:</label>
                        <select id="pengarang_id" name="pengarang_id" required>
                            <option value="">Pilih Pengarang</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo htmlspecialchars($author['pengarang_id']); ?>" <?php echo $author['pengarang_id'] === $edit_data['pengarang_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($author['pengarang_nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="buku_deskripsi">Deskripsi:</label>
                    <textarea id="buku_deskripsi" name="buku_deskripsi" maxlength="1000"><?php echo htmlspecialchars($edit_data['buku_deskripsi']); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php?page=kelola_buku" class="btn btn-secondary">Batal</a>
            </form>
        <?php else: ?>
            <p>Halaman tidak valid.</p>
            <a href="index.php?page=kelola_buku" class="btn btn-secondary">Kembali</a>
        <?php endif; ?>
    </div>
    <script>
        function validateForm() {
            var kategori = document.getElementById('kategori_id').value;
            if (kategori === '') {
                alert('Kategori harus diisi.');
                return false;
            }
            var pengarang = document.getElementById('pengarang_id').value;
            if (pengarang === '') {
                alert('Pengarang harus diisi.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>