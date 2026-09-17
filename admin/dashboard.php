<?php
session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';
require_once __DIR__ . '/includes/auth-check.php';

$pdo = getDBConnection();
$now = date('Y-m-d H:i:s');

// Stats
$stats = [
    'total'     => (int)$pdo->query('SELECT COUNT(*) FROM blogs WHERE status != "deleted"')->fetchColumn(),
    'published' => (int)$pdo->query('SELECT COUNT(*) FROM blogs WHERE status = "published"')->fetchColumn(),
    'draft'     => (int)$pdo->query('SELECT COUNT(*) FROM blogs WHERE status = "draft"')->fetchColumn(),
    'scheduled' => (int)$pdo->query('SELECT COUNT(*) FROM blogs WHERE status = "scheduled"')->fetchColumn(),
    'pending'   => (int)$pdo->query('SELECT COUNT(*) FROM blogs WHERE status = "pending"')->fetchColumn(),
    'views'     => (int)$pdo->query('SELECT COALESCE(SUM(views),0) FROM blogs WHERE status = "published"')->fetchColumn(),
];

// Recent blogs
$recentStmt = $pdo->query('SELECT b.id, b.title, b.slug, b.status, b.publish_date, b.views, b.updated_at, a.name as author_name
    FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
    WHERE b.status != "deleted" ORDER BY b.updated_at DESC LIMIT 8');
$recentBlogs = $recentStmt->fetchAll();

// Scheduled upcoming
$scheduledStmt = $pdo->prepare('SELECT title, slug, publish_date FROM blogs WHERE status = "scheduled" AND publish_date > ? ORDER BY publish_date ASC LIMIT 5');
$scheduledStmt->execute([$now]);
$scheduled = $scheduledStmt->fetchAll();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue"><i class="fas fa-newspaper"></i></div>
        <div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total Blogs</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-green"><i class="fas fa-check-circle"></i></div>
        <div><div class="stat-value"><?= $stats['published'] ?></div><div class="stat-label">Published</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-orange"><i class="fas fa-pencil-alt"></i></div>
        <div><div class="stat-value"><?= $stats['draft'] ?></div><div class="stat-label">Drafts</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-navy"><i class="fas fa-clock"></i></div>
        <div><div class="stat-value"><?= $stats['scheduled'] ?></div><div class="stat-label">Scheduled</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-orange"><i class="fas fa-hourglass-half"></i></div>
        <div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending Review</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-green"><i class="fas fa-eye"></i></div>
        <div><div class="stat-value"><?= number_format($stats['views']) ?></div><div class="stat-label">Total Views</div></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px">
    <!-- Recent Blogs -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-clock" style="color:#d42b2b;margin-right:8px"></i>Recently Updated</span>
            <a href="/admin/blogs.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div style="overflow-x:auto">
        <?php if ($recentBlogs): ?>
        <table class="data-table">
            <thead><tr>
                <th>Title</th><th>Status</th><th>Views</th><th>Updated</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentBlogs as $b): ?>
            <tr>
                <td>
                    <div class="col-title" style="max-width:240px"><?= htmlspecialchars($b['title']) ?></div>
                    <div class="col-slug">/blog/<?= htmlspecialchars($b['slug']) ?></div>
                </td>
                <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                <td><?= number_format($b['views']) ?></td>
                <td style="color:#6b7280;font-size:12px"><?= date('d M Y', strtotime($b['updated_at'])) ?></td>
                <td>
                    <div class="actions">
                        <a href="/admin/blog-editor.php?id=<?= $b['id'] ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                        <?php if ($b['status'] === 'published'): ?>
                        <a href="/blog/<?= $b['slug'] ?>" class="action-btn action-btn-view" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-newspaper"></i><h3>No blogs yet</h3><p>Create your first blog post!</p></div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Quick Actions -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Quick Actions</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <a href="/admin/blog-editor.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Blog Post</a>
                <a href="/admin/blogs.php?status=pending" class="btn btn-secondary"><i class="fas fa-hourglass-half"></i> Review Pending (<?= $stats['pending'] ?>)</a>
                <a href="/admin/categories.php" class="btn btn-secondary"><i class="fas fa-folder-plus"></i> Manage Categories</a>
                <a href="/admin/authors.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Manage Authors</a>
            </div>
        </div>

        <!-- Scheduled Posts -->
        <?php if ($scheduled): ?>
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-calendar-alt" style="color:#3b82f6;margin-right:6px"></i>Upcoming Scheduled</span></div>
            <div class="card-body" style="padding:16px">
                <?php foreach ($scheduled as $s): ?>
                <div style="padding:10px 0;border-bottom:1px solid #f1f5f9">
                    <div style="font-size:13px;font-weight:600;color:#1a2332"><?= htmlspecialchars($s['title']) ?></div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px"><i class="fas fa-clock"></i> <?= date('d M Y, H:i', strtotime($s['publish_date'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
