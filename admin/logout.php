<?php
session_start();
require_once __DIR__ . '/../api/admin-auth.php';
destroyAdminSession();
header('Location: /admin/login.php?msg=logged_out');
exit;
