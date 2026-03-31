<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HMIF Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-container">

    <div class="login-box">
        <img src="assets/logo/himp.png" alt="Logo HMIF" class="logo-login">

        <h2>HMIF Drive</h2>
        <p>Masuk ke Himpunan Drive</p>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'too_many_attempts' && isset($_GET['wait'])): ?>
            <div id="lockout-msg" style="background: #fce8e6; color: #ea4335; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; border-left: 4px solid #ea4335;">
                Terlalu banyak percobaan login. <br> 
                Silakan tunggu <span id="timer" style="font-weight: bold;"><?php echo (int)$_GET['wait']; ?></span> detik lagi.
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'login_failed'): ?>
            <div style="background: #fce8e6; color: #ea4335; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; border-left: 4px solid #ea4335;">
                Username atau password salah!
            </div>
        <?php endif; ?>

        <form action="includes/login_proc.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <input type="text" name="username" placeholder="Username" required 
                   pattern="[a-z0-9]+" title="Username hanya huruf kecil dan angka">
            
            <input type="password" name="password" placeholder="Password" required>
            
            <button type="submit" id="btn-masuk">Masuk</button>
        </form>

        <button class="theme-toggle" id="themeToggle">
            <img id="themeIcon" src="assets/icon/sun-regular.png" alt="Icon" width="18" style="margin-right: 5px;"> 
            <span id="themeText">Terang</span>
        </button>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        const body = document.body;
        const loginButton = document.getElementById('btn-masuk');

        function updateToggleButton(isDark) {
            if (themeIcon && themeText) {
                themeIcon.src = isDark ? "assets/icon/moon-regular.png" : "assets/icon/sun-regular.png";
                themeText.innerText = isDark ? "Gelap" : "Terang";
            }
        }

        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            body.setAttribute('data-theme', 'dark');
            updateToggleButton(true);
        }

        themeToggle.addEventListener('click', () => {
            const isNowDark = body.getAttribute('data-theme') !== 'dark';
            body.setAttribute('data-theme', isNowDark ? 'dark' : 'light');
            localStorage.setItem('theme', isNowDark ? 'dark' : 'light');
            updateToggleButton(isNowDark);
        });

        const timerElement = document.getElementById('timer');
        const msgElement = document.getElementById('lockout-msg');

        if (timerElement) {
            let timeLeft = parseInt(timerElement.innerText);
            
            if (loginButton) {
                loginButton.disabled = true;
                loginButton.style.opacity = "0.6";
                loginButton.style.cursor = "not-allowed";
            }

            const countdown = setInterval(() => {
                timeLeft--;
                timerElement.innerText = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    if (msgElement) msgElement.style.display = 'none';
                    if (loginButton) {
                        loginButton.disabled = false;
                        loginButton.style.opacity = "1";
                        loginButton.style.cursor = "pointer";
                    }
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }, 1000);
        }
    </script>
</body>
</html>
