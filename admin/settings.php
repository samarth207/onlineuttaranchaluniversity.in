<?php
session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';
require_once __DIR__ . '/includes/auth-check.php';

$pdo     = getDBConnection();
$admin   = getCurrentAdmin();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) { $error = 'New password must be at least 8 characters.'; }
        elseif ($new !== $confirm) { $error = 'New passwords do not match.'; }
        else {
            $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
            $stmt->execute([$admin['id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, $row['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$hash, $admin['id']]);
                $success = 'Password changed successfully!';
            }
        }
    }
}

$pageTitle  = 'Settings';
$activePage = 'settings';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width:600px">

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Account Info -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title"><i class="fas fa-user" style="color:#d42b2b;margin-right:8px"></i>Account Info</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div><div class="form-label">Username</div><div style="font-weight:600"><?= htmlspecialchars($admin['username']) ?></div></div>
            <div><div class="form-label">Role</div><div style="font-weight:600;text-transform:capitalize"><?= htmlspecialchars($admin['role']) ?></div></div>
        </div>
    </div>
</div>

<!-- Change Password -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title"><i class="fas fa-lock" style="color:#d42b2b;margin-right:8px"></i>Change Password</span></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label class="form-label">Current Password <span class="required">*</span></label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">New Password <span class="required">*</span> <span style="font-weight:400;color:#6b7280;font-size:12px">Min 8 characters</span></label>
                <input type="password" name="new_password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password <span class="required">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
        </form>
    </div>
</div>

<!-- TinyMCE API Key Info -->
<div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-info-circle" style="color:#3b82f6;margin-right:8px"></i>TinyMCE API Key</span></div>
    <div class="card-body">
        <div class="alert alert-info">
            The blog editor uses TinyMCE. For production use without the "Please register" notification, get a free API key from <a href="https://www.tiny.cloud" target="_blank" style="color:#1e40af;font-weight:600">tiny.cloud</a> and update it in <code>/admin/blog-editor.php</code>.
        </div>
        <p style="font-size:13px;color:#6b7280">Search for <code>TINYMCE_API_KEY</code> in the file and replace <code>no-api-key</code> with your key.</p>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
