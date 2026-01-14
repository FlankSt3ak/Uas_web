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
// Determine if this is add or edit mode
$is_edit = isset($_GET['id']) && !empty($_GET['id']);
$user_data = null;
if ($is_edit) {
    $user_id = (int)$_GET['id'];
    try {
        $conn = db_connect();
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_user WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_close($conn);
        if (!$user_data) {
            header('Location: index.php?page=kelola_user&error=User tidak ditemukan');
            exit;
        }
    } catch (Exception $e) {
        header('Location: index.php?page=kelola_user&error=Gagal memuat data user');
        exit;
    }
}
// Handle form submission
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = db_connect();
        // Handle file upload
        $user_foto = '';
        if (isset($_FILES['user_foto']) && $_FILES['user_foto']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['user_foto'];
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau GIF.');
            }
            // Validate file size (2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB.');
            }
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . uniqid() . '.' . $file_extension;
            $upload_path = __DIR__ . '/../uploads/user_photos/' . $new_filename;
            // Create directory if not exists
            $upload_dir = dirname($upload_path);
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $user_foto = 'uploads/user_photos/' . $new_filename;
            } else {
                throw new Exception('Gagal mengupload file foto.');
            }
        }
        if ($is_edit) {
            // Edit user
            $user_id = (int)$_POST['user_id'];
            $username = trim($_POST['username'] ?? '');
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $user_email = trim($_POST['user_email'] ?? '');
            $user_level = trim($_POST['user_level'] ?? 'Operator');
            $change_password = isset($_POST['change_password']);
            $password = trim($_POST['password'] ?? '');
            if (empty($username) || empty($nama_lengkap) || empty($user_email)) {
                throw new Exception('Username, nama lengkap, dan email harus diisi.');
            }
            // Check if username is already taken by another user
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_user WHERE username = ? AND user_id != ?");
            mysqli_stmt_bind_param($stmt, 'si', $username, $user_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                throw new Exception('Username sudah digunakan.');
            }
            // Check if email is already taken by another user
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_user WHERE user_email = ? AND user_id != ?");
            mysqli_stmt_bind_param($stmt, 'si', $user_email, $user_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                throw new Exception('Email sudah digunakan.');
            }
            // Handle photo update
            $photo_update_sql = '';
            $photo_params = [];
            $photo_types = '';
            if (!empty($user_foto)) {
                // New photo uploaded, delete old photo if exists
                if (!empty($user_data['user_foto']) && file_exists(__DIR__ . '/../' . $user_data['user_foto'])) {
                    unlink(__DIR__ . '/../' . $user_data['user_foto']);
                }
                $photo_update_sql = ', user_foto = ?';
                $photo_params[] = $user_foto;
                $photo_types = 's';
            }
            if ($change_password && !empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Password minimal 6 karakter.');
                }
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE tbl_user SET username = ?, nama_lengkap = ?, user_email = ?, user_level = ?, password = ?" . $photo_update_sql . " WHERE user_id = ?");
                $types = 'sssss' . $photo_types . 'i';
                $params = [$username, $nama_lengkap, $user_email, $user_level, $hashed_password];
                if (!empty($photo_params)) {
                    $params = array_merge($params, $photo_params);
                }
                $params[] = $user_id;
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE tbl_user SET username = ?, nama_lengkap = ?, user_email = ?, user_level = ?" . $photo_update_sql . " WHERE user_id = ?");
                $types = 'ssss' . $photo_types . 'i';
                $params = [$username, $nama_lengkap, $user_email, $user_level];
                if (!empty($photo_params)) {
                    $params = array_merge($params, $photo_params);
                }
                $params[] = $user_id;
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $message = 'User berhasil diupdate.';
            $message_type = 'success';
        } else {
            // Add new user
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $user_email = trim($_POST['user_email'] ?? '');
            $user_level = trim($_POST['user_level'] ?? 'Operator');
            if (empty($username) || empty($password) || empty($nama_lengkap) || empty($user_email)) {
                throw new Exception('Semua field harus diisi.');
            }
            if (strlen($password) < 6) {
                throw new Exception('Password minimal 6 karakter.');
            }
            // Check if username already exists
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_user WHERE username = ?");
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                throw new Exception('Username sudah digunakan.');
            }
            // Check if email already exists
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM tbl_user WHERE user_email = ?");
            mysqli_stmt_bind_param($stmt, 's', $user_email);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                throw new Exception('Email sudah digunakan.');
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO tbl_user (username, password, nama_lengkap, user_email, user_level, user_foto) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssss', $username, $hashed_password, $nama_lengkap, $user_email, $user_level, $user_foto);
            mysqli_stmt_execute($stmt);
            $message = 'User berhasil ditambahkan.';
            $message_type = 'success';
        }
        mysqli_close($conn);
        // Redirect back to kelola_user with success message
        header('Location: index.php?page=kelola_user&message=' . urlencode($message) . '&type=' . $message_type);
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> User - <?php echo htmlspecialchars($user_level); ?></title>
    <link rel="stylesheet" href="assets/form_user.css">
</head>
<body>
    <div class="form-container">
        <h2><?php echo $is_edit ? 'Edit' : 'Tambah'; ?> User</h2>
        <a href="index.php?page=kelola_user" class="btn btn-secondary" style="margin-bottom: 20px;">← Kembali ke Kelola User</a>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?php if ($is_edit): ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['user_id']); ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo $is_edit ? htmlspecialchars($user_data['username']) : ''; ?>" required maxlength="50">
                </div>
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap:</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo $is_edit ? htmlspecialchars($user_data['nama_lengkap']) : ''; ?>" required maxlength="100">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="user_email">Email:</label>
                    <input type="email" id="user_email" name="user_email" value="<?php echo $is_edit ? htmlspecialchars($user_data['user_email']) : ''; ?>" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="user_level">Level:</label>
                    <select id="user_level" name="user_level" required>
                        <option value="Operator" <?php echo ($is_edit && $user_data['user_level'] === 'Operator') ? 'selected' : ''; ?>>Operator</option>
                        <option value="Administrator" <?php echo ($is_edit && $user_data['user_level'] === 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <?php if ($is_edit): ?>
                    <label for="password">Password Baru (kosongkan jika tidak ingin mengubah):</label>
                    <input type="password" id="password" name="password" minlength="6">
                    <div style="margin-top: 5px;">
                        <input type="checkbox" id="change_password" name="change_password">
                        <label for="change_password" style="display: inline; margin-left: 5px;">Ubah password</label>
                    </div>
                <?php else: ?>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="user_foto">Foto Profil:</label>
                <input type="file" id="user_foto" name="user_foto" accept="image/*" onchange="previewImage(this)">
                <div id="image-preview" style="margin-top: 10px;">
                    <?php if ($is_edit && !empty($user_data['user_foto'])): ?>
                        <img id="preview-img" src="<?php echo htmlspecialchars($user_data['user_foto']); ?>" alt="Current Photo" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                    <?php else: ?>
                        <img id="preview-img" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; display: none;">
                    <?php endif; ?>
                </div>
                <small style="color: #666; font-size: 12px;">Format: JPG, PNG, GIF. Maksimal 2MB.</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $is_edit ? 'Update User' : 'Tambah User'; ?>
                </button>
                <a href="index.php?page=kelola_user" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview-img');
            const previewContainer = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan JPG, PNG, atau GIF.');
                    input.value = '';
                    return;
                }
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar. Maksimal 2MB.');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                // If no file selected and this is edit mode, keep current image
                <?php if ($is_edit && !empty($user_data['user_foto'])): ?>
                    preview.src = '<?php echo htmlspecialchars($user_data['user_foto']); ?>';
                    preview.style.display = 'block';
                <?php else: ?>
                    preview.src = '';
                    preview.style.display = 'none';
                <?php endif; ?>
            }
        }
    </script>
</body>
</html>