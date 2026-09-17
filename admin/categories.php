<?php
session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';
require_once __DIR__ . '/includes/auth-check.php';

$pdo        = getDBConnection();
$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();

$pageTitle  = 'Manage Categories';
$activePage = 'categories';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
    <!-- List -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">All Categories</span>
            <span style="color:#6b7280;font-size:13px"><?= count($categories) ?> categories</span>
        </div>
        <div style="overflow-x:auto">
        <?php if ($categories): ?>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
            <tr id="cat-row-<?= $c['id'] ?>">
                <td style="font-weight:600"><?= htmlspecialchars($c['name']) ?></td>
                <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px"><?= htmlspecialchars($c['slug']) ?></code></td>
                <td style="font-size:12px;color:#6b7280;max-width:200px"><?= $c['description'] ? htmlspecialchars(substr($c['description'],0,60)) : '—' ?></td>
                <td><span class="badge <?= $c['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <div class="actions">
                        <button onclick='editCategory(<?= json_encode($c) ?>)' class="action-btn action-btn-edit"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')" class="action-btn action-btn-delete"><i class="fas fa-ban"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-folder-open"></i><h3>No categories yet</h3><p>Add your first category.</p></div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Form -->
    <div class="card" id="catFormCard">
        <div class="card-header"><span class="card-title" id="catFormTitle">Add Category</span></div>
        <div class="card-body">
            <form id="catForm">
                <input type="hidden" id="catId">
                <div class="form-group">
                    <label class="form-label">Category Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="catName" placeholder="e.g. Online MBA" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description <span style="font-weight:400;color:#6b7280">(Optional)</span></label>
                    <textarea class="form-control" id="catDesc" rows="3" placeholder="Brief description of this category..."></textarea>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary" id="catSaveBtn"><i class="fas fa-save"></i> Save Category</button>
                    <button type="button" class="btn btn-secondary" onclick="resetCatForm()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('catForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id   = document.getElementById('catId').value;
    const name = document.getElementById('catName').value.trim();
    const desc = document.getElementById('catDesc').value.trim();
    if (!name) { showToast('Category name required','error'); return; }

    const btn = document.getElementById('catSaveBtn');
    btn.innerHTML = '<span class="spinner"></span> Saving...'; btn.disabled = true;

    fetch('/api/blog-categories.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action: id ? 'edit' : 'add', type: 'category', id, name, description: desc })
    }).then(r => r.json()).then(d => {
        if (d.status === 'success') { showToast(d.message,'success'); setTimeout(() => location.reload(), 1000); }
        else showToast(d.message || 'Error','error');
    }).catch(() => showToast('Network error','error'))
    .finally(() => { btn.innerHTML = '<i class="fas fa-save"></i> Save Category'; btn.disabled = false; });
});

function editCategory(cat) {
    document.getElementById('catId').value          = cat.id;
    document.getElementById('catName').value        = cat.name;
    document.getElementById('catDesc').value        = cat.description || '';
    document.getElementById('catFormTitle').textContent = 'Edit Category';
    document.getElementById('catFormCard').scrollIntoView({ behavior:'smooth' });
}

function resetCatForm() {
    document.getElementById('catId').value = '';
    document.getElementById('catForm').reset();
    document.getElementById('catFormTitle').textContent = 'Add Category';
}

function deleteCategory(id, name) {
    showConfirm('Deactivate Category', `Deactivate "${name}"?`, function() {
        fetch('/api/blog-categories.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'delete', type:'category', id })
        }).then(r => r.json()).then(d => {
            if (d.status === 'success') { showToast('Category deactivated','success'); document.getElementById('cat-row-'+id)?.remove(); }
            else showToast(d.message,'error');
        });
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
