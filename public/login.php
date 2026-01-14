<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// If the user is already logged in, redirect to the dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
// Prevent caching of login page
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . md5(uniqid()) . '"');
// If user is logged in, add meta refresh as additional safeguard
if (isset($_SESSION['user_id'])) {
    echo '<meta http-equiv="refresh" content="0;url=index.php?page=dashboard">';
}
?>
<script>
// INSTANT check - no delays after logout
(function() {
    // Priority 1: Check if we just logged out (from logout page)
    if (typeof Storage !== 'undefined' && sessionStorage.getItem('just_logged_out') === 'true') {
        // Just logged out, clear the flag and show form immediately
        sessionStorage.removeItem('just_logged_out');
        return;
    }
    // Priority 2: Check localStorage (should be cleared on logout)
    if (typeof Storage !== 'undefined') {
        var loggedIn = localStorage.getItem('user_logged_in');
        if (loggedIn === 'true') {
            var loginTime = localStorage.getItem('login_timestamp');
            if (loginTime) {
                var currentTime = Math.floor(Date.now() / 1000);
                if (currentTime - parseInt(loginTime) < 3600) { // Within 1 hour
                    window.location.replace('index.php?page=dashboard');
                    return;
                }
            }
        }
    }
    // Priority 3: Quick sessionStorage check
    if (typeof Storage !== 'undefined' && sessionStorage.getItem('logged_in') === 'true') {
        window.location.replace('index.php?page=dashboard');
        return;
    }
    // Priority 4: Minimal server check (only if needed)
    // Skip this for faster loading - rely on client-side checks
})();
</script>
<main class="auth-page">
    <div class="login-wrapper">
        <div class="card login-card">
            <div class="brand">
                <div class="logo">UPB</div>
                <div>
                    <h2>Masuk</h2>
                    <p class="form-note" style="margin:2px 0;color:#6b7280;font-size:13px;">Masukkan username dan kata sandi Anda</p>
                </div>
            </div>
            <?php if (!empty($_SESSION['login_errors'])): ?>
                <div class="alert alert-danger login-error" role="alert">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $_SESSION['login_errors'])); ?>
                </div>
                <?php unset($_SESSION['login_errors']); ?>
            <?php endif; ?>
            <form class="card-form" method="post" action="index.php?page=login_proses" novalidate>
                <label for="username">Username</label>
                <input class="form-control" id="username" name="username" type="text" required placeholder="Username" autocomplete="username" autofocus />
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input class="form-control" id="password" name="password" type="password" required placeholder="Password" autocomplete="current-password" />
                    <button type="button" class="password-toggle" id="togglePassword" aria-pressed="false" aria-label="Tampilkan password" title="Tampilkan password">
                        <svg class="icon-show" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="icon-hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 2l20 20M17 17A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a21.37 21.37 0 0 1 5-5"></path></svg>
                        <span class="sr-only">Tampilkan password</span>
                    </button>
                    <div class="tooltip" role="tooltip">Tampilkan password</div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="submitBtn">Masuk</button>
                    <a class="btn-secondary" href="index.php?page=home">Batal</a>
                </div>
            </form>
            <script>
                (function(){
                    var pwd = document.getElementById('password');
                    var toggle = document.getElementById('togglePassword');
                    var tooltip = document.querySelector('.password-wrapper .tooltip');
                    var sr = toggle ? toggle.querySelector('.sr-only') : null;
                    var form = document.querySelector('.card-form');
                    var submitBtn = document.getElementById('submitBtn');
                    var cancelBtn = document.querySelector('.btn-secondary');
                    toggle && toggle.addEventListener('click', function(){
                        var isHidden = pwd.getAttribute('type') === 'password';
                        var type = isHidden ? 'text' : 'password';
                        pwd.setAttribute('type', type);
                        toggle.classList.toggle('show', isHidden);
                        toggle.setAttribute('aria-pressed', String(isHidden));
                        var label = isHidden ? 'Sembunyikan password' : 'Tampilkan password';
                        toggle.setAttribute('aria-label', label);
                        if (sr) sr.textContent = label;
                        if (tooltip) tooltip.textContent = label;
                    });
                    form && form.addEventListener('submit', function(){
                        if (submitBtn) {
                            submitBtn.classList.add('loading');
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner" role="status" aria-hidden="true"></span>Masuk...';
                        }
                        if (cancelBtn) {
                            cancelBtn.classList.add('disabled');
                            cancelBtn.setAttribute('aria-disabled','true');
                        }
                    });
                })();
            </script>
        </div>
    </div>
</main>