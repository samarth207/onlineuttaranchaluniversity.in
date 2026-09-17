<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';

// Already logged in
if (isAdminLoggedIn()) { header('Location: /admin/dashboard.php'); exit; }

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        try {
            $pdo = getDBConnection();

            // Brute force check: max 5 attempts in 15 minutes
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
            $stmt->execute([$ip]);
            $attempts = (int)$stmt->fetchColumn();

            if ($attempts >= 5) {
                $error = 'Too many failed attempts. Please wait 15 minutes before trying again.';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // Successful login
                    $pdo->prepare('DELETE FROM admin_login_attempts WHERE ip_address = ?')->execute([$ip]);
                    $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
                    session_regenerate_id(true);
                    setAdminSession($admin);
                    $redirect = $_GET['redirect'] ?? '/admin/dashboard.php';
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    // Log failed attempt
                    $pdo->prepare('INSERT INTO admin_login_attempts (ip_address, username) VALUES (?, ?)')->execute([$ip, $username]);
                    $remaining = 5 - $attempts - 1;
                    $error = 'Invalid username or password.' . ($remaining > 0 ? " ($remaining attempts remaining)" : ' Account temporarily locked.');
                }
            }
        } catch (Exception $e) {
            $error = 'System error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login — Uttaranchal University Blog CMS</title>
<link rel="shortcut icon" href="/assets/images/24_onlineUU.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#0a1628 0%,#0f1d35 50%,#1a2d4a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden}
body::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.login-wrapper{position:relative;z-index:1;width:100%;max-width:440px}
.login-card{background:rgba(255,255,255,0.97);backdrop-filter:blur(20px);border-radius:24px;padding:48px 40px 40px;box-shadow:0 32px 80px rgba(0,0,0,0.5),0 0 0 1px rgba(255,255,255,0.1)}
.login-logo{text-align:center;margin-bottom:36px}
.login-logo img{height:56px;object-fit:contain;margin-bottom:12px}
.login-logo h1{font-size:20px;font-weight:800;color:#0f1d35;letter-spacing:-0.3px}
.login-logo p{font-size:13px;color:#888;margin-top:4px}
.login-divider{display:flex;align-items:center;gap:12px;margin-bottom:28px}
.login-divider::before,.login-divider::after{content:'';flex:1;height:1px;background:#e8e8e8}
.login-divider span{font-size:12px;font-weight:600;color:#bbb;text-transform:uppercase;letter-spacing:1px}
.form-group{margin-bottom:20px;position:relative}
label{display:block;font-size:12px;font-weight:700;color:#333;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px}
.input-wrapper{position:relative}
.input-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#aaa;font-size:15px;pointer-events:none}
input[type=text],input[type=password]{width:100%;padding:14px 16px 14px 46px;border:2px solid #ebebeb;border-radius:12px;font-size:14px;font-family:'Poppins',sans-serif;color:#0f1d35;background:#fafafa;transition:all 0.2s;outline:none}
input[type=text]:focus,input[type=password]:focus{border-color:#d42b2b;background:#fff;box-shadow:0 0 0 4px rgba(212,43,43,0.08)}
.password-toggle{position:absolute;right:16px;top:50%;transform:translateY(-50%);color:#aaa;cursor:pointer;font-size:15px;border:none;background:none;padding:4px}
.password-toggle:hover{color:#d42b2b}
.btn-login{width:100%;padding:15px;background:linear-gradient(135deg,#d42b2b,#c0392b);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;letter-spacing:0.3px;transition:all 0.3s;position:relative;overflow:hidden;margin-top:8px}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(212,43,43,0.4)}
.btn-login:active{transform:translateY(0)}
.btn-login .btn-ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,0.3);transform:scale(0);animation:ripple 0.6s linear}
@keyframes ripple{to{transform:scale(4);opacity:0}}
.alert-error{background:#fef0f0;border:1px solid #fcc;border-radius:10px;padding:12px 16px;color:#c0392b;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-error i{font-size:16px;flex-shrink:0}
.login-footer{text-align:center;margin-top:24px;font-size:12px;color:#aaa}
.login-footer a{color:#d42b2b;text-decoration:none;font-weight:600}
.back-to-site{text-align:center;margin-top:20px}
.back-to-site a{color:rgba(255,255,255,0.6);font-size:13px;text-decoration:none;transition:color 0.2s}
.back-to-site a:hover{color:#fff}
@media(max-width:480px){.login-card{padding:36px 24px 28px;border-radius:16px}}
</style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <img src="/assets/images/24_onlineUU.png" alt="Online Uttaranchal University" onerror="this.style.display='none'">
            <h1>UU Blog CMS</h1>
            <p>Admin Panel · Online Uttaranchal University</p>
        </div>

        <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <div class="login-divider"><span>Sign In</span></div>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($username) ?>" 
                           placeholder="Enter username" autocomplete="username" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In to Admin Panel
            </button>
        </form>

        <div class="login-footer">
            <a href="/blog/" target="_blank">← Back to Blog</a>
        </div>
    </div>
    <div class="back-to-site">
        <a href="/"><i class="fas fa-home"></i> onlineuttaranchaluniversity.com</a>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    const ic = document.getElementById('pwIcon');
    if (pw.type === 'password') { pw.type = 'text'; ic.className = 'fas fa-eye-slash'; }
    else { pw.type = 'password'; ic.className = 'fas fa-eye'; }
}
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    btn.disabled = true;
});
// Ripple effect
document.querySelector('.btn-login').addEventListener('click', function(e) {
    const ripple = document.createElement('span');
    ripple.className = 'btn-ripple';
    const rect = this.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
    this.appendChild(ripple);
    setTimeout(() => ripple.remove(), 700);
});
</script>
</body>
</html>
