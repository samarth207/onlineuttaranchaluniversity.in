<?php
session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';
require_once __DIR__ . '/includes/auth-check.php';

$pdo  = getDBConnection();
$now  = date('Y-m-d H:i:s');

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$catFilter    = (int)($_GET['category'] ?? 0);
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where  = ['b.status != "deleted"'];
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['draft','pending','published','scheduled'])) {
    $where[] = 'b.status = ?'; $params[] = $statusFilter;
}
if ($search) { $where[] = 'b.title LIKE ?'; $params[] = '%' . $search . '%'; }

$whereSql  = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b WHERE $whereSql");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$pages    = ceil($total / $perPage);

$params[] = $perPage; $params[] = $offset;
$stmt = $pdo->prepare("SELECT b.id, b.title, b.slug, b.status, b.publish_date, b.views, b.created_at, b.updated_at, b.category_ids, b.read_time, a.name as author_name
    FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
    WHERE $whereSql ORDER BY b.updated_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params);
$blogs = $stmt->fetchAll();

// Categories for filter
$categories = $pdo->query('SELECT id, name FROM blog_categories WHERE is_active = 1 ORDER BY name')->fetchAll();

$statusCounts = [];
foreach (['all','published','draft','pending','scheduled'] as $s) {
    $q = $s === 'all' ? "SELECT COUNT(*) FROM blogs WHERE status != 'deleted'" : "SELECT COUNT(*) FROM blogs WHERE status = '$s'";
    $statusCounts[$s] = (int)$pdo->query($q)->fetchColumn();
}

$msg        = $_GET['msg'] ?? '';
$pageTitle  = 'All Blogs';
$activePage = 'blogs';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($msg === 'saved'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Blog saved successfully!</div><?php endif; ?>

<!-- Status Tabs -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
<?php foreach (['all'=>'All','published'=>'Published','draft'=>'Drafts','pending'=>'Pending','scheduled'=>'Scheduled'] as $s => $label): ?>
    <a href="?status=<?= $s ?>&search=<?= urlencode($search) ?>" 
       class="btn btn-<?= $statusFilter === $s ? 'primary' : 'secondary' ?> btn-sm">
        <?= $label ?> <span style="background:rgba(255,255,255,0.2);border-radius:50px;padding:1px 7px;font-size:11px"><?= $statusCounts[$s] ?></span>
    </a>
<?php endforeach; ?>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <input type="text" name="search" class="search-input" placeholder="Search blogs..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i> Search</button>
        <?php if ($search || $statusFilter !== 'all'): ?>
        <a href="/admin/blogs.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
    <div style="margin-left:auto;color:#6b7280;font-size:13px"><?= $total ?> blog<?= $total !== 1 ? 's' : '' ?></div>
</div>

<!-- Table -->
<div class="card">
    <div style="overflow-x:auto">
    <?php if ($blogs): ?>
    <table class="data-table">
        <thead><tr>
            <th style="width:40%">Title</th>
            <th>Author</th>
            <th>Status</th>
            <th>Published</th>
            <th>Views</th>
            <th>Updated</th>
            <th style="text-align:center">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($blogs as $b): ?>
        <tr id="blog-row-<?= $b['id'] ?>">
            <td>
                <div class="col-title"><?= htmlspecialchars($b['title']) ?></div>
                <div class="col-slug">/blog/<?= htmlspecialchars($b['slug']) ?></div>
                <?php if ($b['read_time']): ?><div style="font-size:11px;color:#9ca3af;margin-top:2px"><i class="fas fa-clock"></i> <?= $b['read_time'] ?> min read</div><?php endif; ?>
            </td>
            <td style="font-size:13px"><?= htmlspecialchars($b['author_name'] ?? 'N/A') ?></td>
            <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            <td style="font-size:12px;color:#6b7280"><?= $b['publish_date'] ? date('d M Y', strtotime($b['publish_date'])) : '—' ?></td>
            <td style="font-size:13px"><?= number_format($b['views']) ?></td>
            <td style="font-size:12px;color:#6b7280"><?= date('d M Y', strtotime($b['updated_at'])) ?></td>
            <td>
                <div class="actions" style="justify-content:center">
                    <a href="/admin/blog-editor.php?id=<?= $b['id'] ?>" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                    <?php if ($b['status'] === 'published'): ?>
                    <a href="/blog/<?= $b['slug'] ?>" class="action-btn action-btn-view" target="_blank" title="View Live"><i class="fas fa-eye"></i></a>
                    <?php endif; ?>
                    <button onclick="deleteBlog(<?= $b['id'] ?>, <?= htmlspecialchars(json_encode($b['title'])) ?>)" class="action-btn action-btn-delete" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-newspaper"></i>
        <h3>No blogs found</h3>
        <p><?= $search ? 'Try a different search term.' : 'Create your first blog post!' ?></p>
        <a href="/admin/blog-editor.php" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-plus"></i> New Blog</a>
    </div>
    <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid #f1f5f9">
        <div class="pagination">
            <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" class="page-btn <?= $i===$page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?>&status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
