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
                // Delete book
                $buku_isbn = trim($_POST['buku_isbn'] ?? '');
                if (empty($buku_isbn)) {
                    throw new Exception('ISBN Buku harus diisi.');
                }
                $stmt = mysqli_prepare($conn, "DELETE FROM tbl_buku WHERE buku_isbn = ?");
                mysqli_stmt_bind_param($stmt, 's', $buku_isbn);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Buku berhasil dihapus.';
                    $message_type = 'success';
                } else {
                    throw new Exception('Gagal menghapus buku: ' . mysqli_error($conn));
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
// Get all books with relationships for display
$books = [];
try {
    $conn = db_connect();
    $sql = "SELECT b.*, p.penerbit_nama, k.kategori_nama, a.pengarang_nama
            FROM tbl_buku b
            LEFT JOIN tbl_penerbit p ON b.penerbit_id = p.penerbit_id
            LEFT JOIN tbl_kategori k ON b.kategori_id = k.kategori_id
            LEFT JOIN tbl_pengarang a ON b.pengarang_id = a.pengarang_id
            ORDER BY b.buku_judul";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
    mysqli_close($conn);
} catch (Exception $e) {
    $message = 'Error loading books: ' . $e->getMessage();
    $message_type = 'error';
}
// Get publishers, categories, and authors for dropdowns
$publishers = $categories = $authors = [];
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
    // Handle error silently for dropdowns
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="assets/kelola_buku.css">
</head>
<body>
    <div class="crud-container">
        <div class="crud-header">
            <h1>Kelola Buku</h1>
            <div>
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Kembali ke Dashboard</a>
                <a href="index.php?page=form_buku&action=add" class="btn btn-primary">Tambah Buku</a>
            </div>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <!-- Books Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Judul</th>
                    <th>Penerbit</th>
                    <th>Kategori</th>
                    <th>Pengarang</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">Belum ada buku.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $buku): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($buku['buku_isbn']); ?></td>
                            <td><?php echo htmlspecialchars($buku['buku_judul']); ?></td>
                            <td><?php echo htmlspecialchars($buku['penerbit_nama']); ?></td>
                            <td><?php echo htmlspecialchars($buku['kategori_nama'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($buku['pengarang_nama'] ?? '-'); ?></td>
                            <td>Rp <?php echo htmlspecialchars(number_format($buku['buku_harga'], 0, ',', '.')); ?></td>
                            <td>
                                <a href="index.php?page=form_buku&action=edit&id=<?php echo htmlspecialchars($buku['buku_isbn']); ?>" class="btn btn-success">Edit</a>
                                <button onclick="deleteBook('<?php echo htmlspecialchars($buku['buku_isbn']); ?>')" class="btn btn-danger">Hapus</button>
                            </td>
                            <td>
                                <button onclick="openDetailModal('<?php echo htmlspecialchars($buku['buku_isbn']); ?>')" class="btn btn-primary">Detail</button>
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
            <p>Apakah Anda yakin ingin menghapus buku ini?</p>
            <form method="post" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_buku_isbn" name="buku_isbn">
                <button type="submit" class="btn btn-danger">Hapus</button>
                <button type="button" onclick="closeModal('deleteModal')" class="btn btn-secondary">Batal</button>
            </form>
        </div>
    </div>
    <!-- Detail Book Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Buku</h2>
                <span class="close" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div id="detailContent">
                <!-- Detail content will be loaded here -->
            </div>
            <button type="button" onclick="closeModal('detailModal')" class="btn btn-secondary" style="margin-top: 20px;">Tutup</button>
        </div>
    </div>
    <script>
        function deleteBook(isbn) {
            document.getElementById('delete_buku_isbn').value = isbn;
            document.getElementById('deleteModal').style.display = 'block';
        }
        function openDetailModal(isbn) {
            // Create a form to submit POST request for detail data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'get_book_detail';
            input.value = isbn;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
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
        // Handle detail modal data loading
        <?php if (isset($_POST['get_book_detail'])): ?>
            <?php
            $isbn = $_POST['get_book_detail'];
            try {
                $conn = db_connect();
                // Get complete book data with relationships
                $sql = "SELECT b.*,
                        p.penerbit_nama,
                        k.kategori_nama,
                        a.pengarang_nama
                        FROM tbl_buku b
                        LEFT JOIN tbl_penerbit p ON b.penerbit_id = p.penerbit_id
                        LEFT JOIN tbl_kategori k ON b.kategori_id = k.kategori_id
                        LEFT JOIN tbl_pengarang a ON b.pengarang_id = a.pengarang_id
                        WHERE b.buku_isbn = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 's', $isbn);
                mysqli_stmt_execute($stmt);
                $book_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_close($conn);
            } catch (Exception $e) {
                // Handle error
            }
            ?>
            document.addEventListener('DOMContentLoaded', function() {
                const detailContent = document.getElementById('detailContent');
                detailContent.innerHTML = `
                    <div class="detail-row">
                        <div class="detail-label">ISBN:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($book_detail['buku_isbn'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Judul Buku:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($book_detail['buku_judul'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Penerbit:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($book_detail['penerbit_nama'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Kategori:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($book_detail['kategori_nama'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Pengarang:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($book_detail['pengarang_nama'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal Terbit:</div>
                        <div class="detail-value"><?php
                            $date_value = $book_detail['buku_tglterbit'] ?? '';
                            if (!empty($date_value) && $date_value !== '0000-00-00') {
                                // Convert YYYY-MM-DD to DD/MM/YYYY safely
                                $date_parts = explode('-', $date_value);
                                if (count($date_parts) === 3) {
                                    echo htmlspecialchars($date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0]);
                                } else {
                                    echo htmlspecialchars($date_value);
                                }
                            } else {
                                echo '-';
                            }
                        ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Jumlah Halaman:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($book_detail['buku_jmlhalaman'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Harga:</div>
                        <div class="detail-value">Rp <?php echo htmlspecialchars(number_format($book_detail['buku_harga'] ?? 0, 0, ',', '.')); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Deskripsi:</div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($book_detail['buku_deskripsi'] ?? '-')); ?></div>
                    </div>
                `;
                document.getElementById('detailModal').style.display = 'block';
            });
        <?php endif; ?>
    </script>
</body>
</html>