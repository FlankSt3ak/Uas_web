<nav>
    <ul>
        <li><a href="?page=home" <?php echo (!isset($_GET['page']) || $_GET['page'] === 'home') ? 'class="active"' : ''; ?>>Home</a></li>
        <li><a href="?page=about" <?php echo (isset($_GET['page']) && $_GET['page'] === 'about') ? 'class="active"' : ''; ?>>About</a></li>
        <li><a href="?page=contact" <?php echo (isset($_GET['page']) && $_GET['page'] === 'contact') ? 'class="active"' : ''; ?>>Contact</a></li>
        <?php if (!empty($_SESSION['user_id'] ?? null)): ?>
            <li><a href="?page=dashboard" <?php echo (isset($_GET['page']) && $_GET['page'] === 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <li><a href="?page=logout">Logout (<?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'User'); ?>)</a></li>
        <?php else: ?>
            <li><a href="?page=login" <?php echo (isset($_GET['page']) && $_GET['page'] === 'login') ? 'class="active"' : ''; ?>>Login</a></li>
        <?php endif; ?>
    </ul>
</nav>