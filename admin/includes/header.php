<?php
/**
 * Admin Panel Header — Sidebar + HTML Head
 * $pageTitle must be set before including this file.
 * $activePage must be set to identify the current nav item.
 */
$pageTitle  = $pageTitle ?? 'Admin Panel';
$activePage = $activePage ?? '';
$adminName  = $_SESSION['admin_name'] ?? 'Admin';
$adminRole  = $_SESSION['admin_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= htmlspecialchars($pageTitle) ?> — Uttaranchal University Blog CMS</title>
<link rel="shortcut icon" href="/assets/images/24_onlineUU.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="document.getElementById('adminSidebar').classList.remove('open');this.classList.remove('active')"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <img src="/assets/images/24_onlineUU.png" alt="Online Uttaranchal University" onerror="this.style.display='none'">
            <div>
                <div class="brand-name">UU Blog CMS</div>
                <div class="brand-sub">Admin Panel</div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="/admin/dashboard.php" class="nav-item <?= $activePage==='dashboard' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="/admin/blogs.php" class="nav-item <?= $activePage==='blogs' ? 'active' : '' ?>">
            <i class="fas fa-newspaper"></i><span>All Blogs</span>
        </a>
        <a href="/admin/blog-editor.php" class="nav-item <?= $activePage==='new-blog' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i><span>New Blog</span>
        </a>

        <div class="nav-section-label">Manage</div>
        <a href="/admin/categories.php" class="nav-item <?= $activePage==='categories' ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i><span>Categories</span>
        </a>
        <a href="/admin/authors.php" class="nav-item <?= $activePage==='authors' ? 'active' : '' ?>">
            <i class="fas fa-user-edit"></i><span>Authors</span>
        </a>

        <div class="nav-section-label">System</div>
        <a href="/admin/settings.php" class="nav-item <?= $activePage==='settings' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i><span>Settings</span>
        </a>
        <a href="/blog/" class="nav-item" target="_blank">
            <i class="fas fa-external-link-alt"></i><span>View Blog</span>
        </a>
        <a href="/admin/logout.php" class="nav-item nav-item-danger">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($adminName) ?></div>
            <div class="user-role"><?= ucfirst($adminRole) ?></div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <h1 class="page-heading"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
        <div class="topbar-right">
            <a href="/blog/" target="_blank" class="topbar-btn">
                <i class="fas fa-eye"></i> View Site
            </a>
            <a href="/admin/blog-editor.php" class="topbar-btn topbar-btn-primary">
                <i class="fas fa-plus"></i> New Blog
            </a>
        </div>
    </header>

    <div class="admin-content">
