<?php
session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';
require_once __DIR__ . '/includes/auth-check.php';

$pdo     = getDBConnection();
$authors = $pdo->query('SELECT * FROM blog_authors ORDER BY name')->fetchAll();

$pageTitle  = 'Manage Authors';
$activePage = 'authors';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">
    <!-- Author List -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">All Authors</span>
            <span style="color:#6b7280;font-size:13px"><?= count($authors) ?> author<?= count($authors)!==1?'s':'' ?></span>
        </div>
        <div style="overflow-x:auto">
        <?php if ($authors): ?>
        <table class="data-table">
            <thead><tr><th>Author</th><th>Bio</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($authors as $a): ?>
            <tr id="author-row-<?= $a['id'] ?>">
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <?php if ($a['image']): ?>
                        <img src="<?= htmlspecialchars($a['image']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
                        <?php else: ?>
                        <div style="width:36px;height:36px;border-radius:50%;background:#0f1d35;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px"><?= strtoupper(substr($a['name'],0,1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($a['name']) ?></div>
                            <?php if ($a['page_url']): ?><div style="font-size:11px;color:#6b7280"><?= htmlspecialchars($a['page_url']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;color:#6b7280;max-width:200px"><?= $a['bio'] ? substr(htmlspecialchars($a['bio']),0,80).'...' : '—' ?></td>
                <td><span class="badge <?= $a['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <div class="actions">
                        <button onclick="editAuthor(<?= htmlspecialchars(json_encode($a)) ?>)" class="action-btn action-btn-edit" title="Edit"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteAuthor(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>')" class="action-btn action-btn-delete" title="Deactivate"><i class="fas fa-ban"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-user-edit"></i><h3>No authors yet</h3><p>Add your first author.</p></div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Form -->
    <div class="card" id="authorFormCard">
        <div class="card-header"><span class="card-title" id="authorFormTitle">Add Author</span></div>
        <div class="card-body">
            <form id="authorForm">
                <input type="hidden" id="authorId" name="id" value="">
                <div class="form-group">
                    <label class="form-label">Author Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="authorName" placeholder="e.g. Dr. Anita Sharma" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Bio <span style="font-weight:400;color:#6b7280">(Optional)</span></label>
                    <textarea class="form-control" id="authorBio" rows="3" placeholder="Short author biography..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Author Image <span style="font-weight:400;color:#6b7280">(Optional)</span></label>
                    <div id="authorImgArea" class="image-upload-area" style="padding:20px">
                        <i class="fas fa-user-circle"></i>
                        <p>Upload author photo</p>
                        <small>JPG, WebP · Max 2MB · Recommended 200×200px</small>
                        <input type="file" id="authorImgInput" accept="image/jpeg,image/webp,image/png">
                    </div>
                    <div id="authorImgPreview" style="display:none"></div>
                    <input type="hidden" id="authorImageUrl" name="image">
                    <div class="form-group" style="margin-top:10px">
                        <label class="form-label" style="font-size:12px">Or paste image URL</label>
                        <input type="url" class="form-control" id="authorImageUrlInput" placeholder="https://..." style="font-size:13px">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Author Page URL <span style="font-weight:400;color:#6b7280">(Optional)</span></label>
                    <input type="url" class="form-control" id="authorPageUrl" placeholder="https://...">
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary" id="authorSaveBtn"><i class="fas fa-save"></i> Save Author</button>
                    <button type="button" class="btn btn-secondary" onclick="resetAuthorForm()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initImageUpload('authorImgArea','authorImgInput','authorImgPreview','authorImageUrl','author');
});

document.getElementById('authorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id     = document.getElementById('authorId').value;
    const name   = document.getElementById('authorName').value.trim();
    const bio    = document.getElementById('authorBio').value.trim();
    const image  = document.getElementById('authorImageUrl').value || document.getElementById('authorImageUrlInput').value;
    const page   = document.getElementById('authorPageUrl').value.trim();
    if (!name) { showToast('Author name is required','error'); return; }

    const btn = document.getElementById('authorSaveBtn');
    btn.innerHTML = '<span class="spinner"></span> Saving...'; btn.disabled = true;

    fetch('/api/blog-categories.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action: id ? 'edit' : 'add', type: 'author', id, name, bio, image, page_url: page })
    }).then(r => r.json()).then(data => {
        if (data.status === 'success') { showToast(data.message,'success'); setTimeout(() => location.reload(), 1000); }
        else { showToast(data.message || 'Error','error'); }
    }).catch(() => showToast('Network error','error'))
    .finally(() => { btn.innerHTML = '<i class="fas fa-save"></i> Save Author'; btn.disabled = false; });
});

function editAuthor(author) {
    document.getElementById('authorId').value    = author.id;
    document.getElementById('authorName').value  = author.name || '';
    document.getElementById('authorBio').value   = author.bio || '';
    document.getElementById('authorPageUrl').value = author.page_url || '';
    document.getElementById('authorImageUrlInput').value = author.image || '';
    document.getElementById('authorFormTitle').textContent = 'Edit Author';
    document.getElementById('authorFormCard').scrollIntoView({ behavior: 'smooth' });
}

function resetAuthorForm() {
    document.getElementById('authorId').value = '';
    document.getElementById('authorForm').reset();
    document.getElementById('authorFormTitle').textContent = 'Add Author';
}

function deleteAuthor(id, name) {
    showConfirm('Deactivate Author', `Deactivate "${name}"? They won't appear in new posts.`, function() {
        fetch('/api/blog-categories.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'delete', type: 'author', id })
        }).then(r => r.json()).then(d => {
            if (d.status === 'success') { showToast('Author deactivated','success'); document.getElementById('author-row-'+id)?.remove(); }
            else showToast(d.message,'error');
        });
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
