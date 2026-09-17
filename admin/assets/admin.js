/**
 * UU Blog CMS — Admin JavaScript
 */

// ========= Toast Notifications =========
function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ========= Confirm Modal =========
let confirmCallback = null;
function showConfirm(title, msg, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMsg').textContent = msg;
    document.getElementById('confirmBackdrop').style.display = 'flex';
    confirmCallback = callback;
}
function closeConfirm() {
    document.getElementById('confirmBackdrop').style.display = 'none';
    confirmCallback = null;
}
document.addEventListener('DOMContentLoaded', function () {
    const okBtn = document.getElementById('confirmOkBtn');
    if (okBtn) {
        okBtn.addEventListener('click', function () {
            if (confirmCallback) confirmCallback();
            closeConfirm();
        });
    }
});

// ========= Blog Delete =========
function deleteBlog(id, title) {
    showConfirm(
        'Delete Blog',
        `Delete "${title}"? This cannot be undone.`,
        function () {
            fetch('/api/blog-delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Blog deleted successfully', 'success');
                    const row = document.getElementById('blog-row-' + id);
                    if (row) { row.style.opacity = '0'; row.style.transition = 'opacity 0.3s'; setTimeout(() => row.remove(), 300); }
                } else { showToast(data.message || 'Delete failed', 'error'); }
            })
            .catch(() => showToast('Network error', 'error'));
        }
    );
}

// ========= Char Counter =========
function initCharCounter(inputId, counterId, max, softMin) {
    const input   = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (!input || !counter) return;
    function update() {
        const len = input.value.length;
        counter.textContent = len + '/' + max;
        counter.className = 'char-counter';
        if (len > max) counter.className += ' danger';
        else if (softMin && len < softMin) counter.className += ' warn';
        else if (len > max * 0.9) counter.className += ' warn';
    }
    input.addEventListener('input', update);
    update();
}

// ========= Slug Generator =========
function initSlugGenerator(titleId, slugId) {
    const titleInput = document.getElementById(titleId);
    const slugInput  = document.getElementById(slugId);
    if (!titleInput || !slugInput) return;
    let slugEdited = false;
    slugInput.addEventListener('input', () => { slugEdited = true; });
    titleInput.addEventListener('input', function () {
        if (!slugEdited) {
            slugInput.value = generateSlug(this.value);
        }
    });
    slugInput.addEventListener('blur', function () {
        this.value = generateSlug(this.value) || slugInput.value;
    });
}
function generateSlug(str) {
    return str.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

// ========= Tags Input =========
function initTagsInput(wrapperId, hiddenInputId) {
    const wrapper = document.getElementById(wrapperId);
    const hidden  = document.getElementById(hiddenInputId);
    if (!wrapper || !hidden) return;

    const textInput = wrapper.querySelector('.tags-input');
    let tags = [];

    function renderTags() {
        wrapper.querySelectorAll('.tag-chip').forEach(c => c.remove());
        tags.forEach((tag, i) => {
            const chip = document.createElement('div');
            chip.className = 'tag-chip';
            chip.innerHTML = `<span>${escapeHtml(tag)}</span><button type="button" class="tag-chip-remove" data-idx="${i}"><i class="fas fa-times"></i></button>`;
            chip.querySelector('.tag-chip-remove').addEventListener('click', () => {
                tags.splice(i, 1); renderTags();
            });
            wrapper.insertBefore(chip, textInput);
        });
        hidden.value = JSON.stringify(tags);
    }

    function addTag(val) {
        val = val.trim();
        if (val && !tags.includes(val)) { tags.push(val); renderTags(); }
        textInput.value = '';
        textInput.focus();
    }

    textInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addTag(this.value); }
        if (e.key === 'Backspace' && !this.value && tags.length) { tags.pop(); renderTags(); }
    });
    wrapper.addEventListener('click', () => textInput.focus());

    // Pre-load existing tags
    try {
        const existing = JSON.parse(hidden.value || '[]');
        if (Array.isArray(existing)) { tags = existing; renderTags(); }
    } catch(e) {}
}

// ========= Image Upload with Preview =========
function initImageUpload(areaId, inputId, previewId, hiddenUrlId, apiType) {
    const area     = document.getElementById(areaId);
    const input    = document.getElementById(inputId);
    const preview  = document.getElementById(previewId);
    const hiddenUrl = document.getElementById(hiddenUrlId);
    if (!area || !input) return;

    function handleFile(file) {
        if (!file) return;
        const allowed = ['image/jpeg','image/jpg','image/webp','image/png','image/gif'];
        if (!allowed.includes(file.type)) { showToast('Invalid file type. Use JPG, WebP, or PNG.', 'error'); return; }
        if (file.size > 5 * 1024 * 1024) { showToast('File too large. Max 5MB.', 'error'); return; }

        area.innerHTML = '<div class="img-upload-loading"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:10px;color:#6b7280">Uploading...</p></div>';

        const formData = new FormData();
        formData.append('image', file);
        fetch('/api/blog-upload.php?type=' + (apiType || 'feature'), { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (hiddenUrl) hiddenUrl.value = data.data.url;
                showImagePreview(data.data.url);
                showToast('Image uploaded!', 'success');
            } else {
                resetUploadArea();
                showToast(data.message || 'Upload failed', 'error');
            }
        })
        .catch(() => { resetUploadArea(); showToast('Upload failed. Check connection.', 'error'); });
    }

    function showImagePreview(url) {
        area.style.display = 'none';
        if (preview) {
            preview.innerHTML = `<div class="image-preview"><img src="${url}" alt="Preview"><button type="button" class="image-preview-remove" onclick="removeImagePreview('${areaId}','${previewId}','${hiddenUrlId}')"><i class="fas fa-times"></i></button></div>`;
            preview.style.display = 'block';
        }
    }

    function resetUploadArea() {
        area.innerHTML = `<i class="fas fa-cloud-upload-alt"></i><p>Drag & drop image or <span class="browse-link">browse</span></p><small>JPG, WebP · Max 5MB · Recommended 1200×628px</small><input type="file" id="${inputId}" accept="image/jpeg,image/webp,image/png,image/gif">`;
        area.style.display = '';
        initImageUpload(areaId, inputId, previewId, hiddenUrlId, apiType);
    }

    input.addEventListener('change', e => handleFile(e.target.files[0]));
    area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', () => area.classList.remove('dragover'));
    area.addEventListener('drop', e => { e.preventDefault(); area.classList.remove('dragover'); handleFile(e.dataTransfer.files[0]); });
}

function removeImagePreview(areaId, previewId, hiddenUrlId) {
    const area    = document.getElementById(areaId);
    const preview = document.getElementById(previewId);
    const hidden  = document.getElementById(hiddenUrlId);
    if (area) area.style.display = '';
    if (preview) { preview.innerHTML = ''; preview.style.display = 'none'; }
    if (hidden) hidden.value = '';
}

// ========= TOC Builder =========
function buildTOC(content) {
    const parser = new DOMParser();
    const doc    = parser.parseFromString(content, 'text/html');
    const headings = doc.querySelectorAll('h2, h3, h4');
    const toc = [];
    headings.forEach(h => {
        const text  = h.textContent.trim();
        const id    = 'toc-' + text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        const level = parseInt(h.tagName.charAt(1));
        if (text) toc.push({ tag: h.tagName.toLowerCase(), text, id, level });
    });
    return toc;
}

function renderTOCPreview(toc, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (!toc.length) {
        container.innerHTML = '<p style="color:#9ca3af;font-size:13px;">TOC will auto-generate from H2/H3/H4 headings in content.</p>';
        return;
    }
    let html = '<ul class="toc-preview-list">';
    toc.forEach(item => {
        const indent = (item.level - 2) * 16;
        html += `<li style="padding-left:${indent}px;margin:3px 0;font-size:12.5px;color:#374151"><i class="fas fa-angle-right" style="color:#d42b2b;margin-right:5px;font-size:10px"></i>${escapeHtml(item.text)}</li>`;
    });
    html += '</ul>';
    container.innerHTML = html;
}

// ========= Category Checkbox Toggle =========
document.addEventListener('change', function(e) {
    if (e.target.matches('.cat-checkbox')) {
        const item = e.target.closest('.checkbox-item');
        if (item) item.classList.toggle('checked', e.target.checked);
    }
});

// ========= Helper =========
function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ========= Blog Save (AJAX) =========
function saveBlog(status, redirect) {
    // Sync TinyMCE content into the hidden textarea
    if (typeof tinymce !== 'undefined' && tinymce.get('blogContent')) {
        tinymce.get('blogContent').save();
    }

    // ---- Validation ----
    const required = [
        { id: 'blogTitle',      label: 'Blog Title' },
        { id: 'featureImageUrl',label: 'Feature Image' },
        { id: 'featureAlt',     label: 'Feature Image Alt Text' },
        { id: 'blogExcerpt',    label: 'Short Description / Excerpt' },
        { id: 'focusKeyword',   label: 'Focus Keyword' },
    ];
    for (const r of required) {
        const el = document.getElementById(r.id);
        if (el && !el.value.trim()) {
            showToast(r.label + ' is required.', 'error');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.focus();
            el.classList.add('error');
            setTimeout(() => el.classList.remove('error'), 3000);
            return;
        }
    }

    // At least one category
    const checkedCats = document.querySelectorAll('.cat-checkbox:checked');
    if (!checkedCats.length) { showToast('Please select at least one category.', 'error'); return; }

    const content = document.getElementById('blogContent') ? document.getElementById('blogContent').value : '';

    // Build form data
    const data = {
        id:                  document.getElementById('blogId')?.value || null,
        title:               document.getElementById('blogTitle')?.value,
        slug:                document.getElementById('blogSlug')?.value,
        excerpt:             document.getElementById('blogExcerpt')?.value,
        content:             document.getElementById('blogContent')?.value,
        meta_title:          document.getElementById('metaTitle')?.value,
        meta_description:    document.getElementById('metaDescription')?.value,
        focus_keyword:       document.getElementById('focusKeyword')?.value,
        primary_keyword:     document.getElementById('primaryKeyword')?.value,
        feature_image:       document.getElementById('featureImageUrl')?.value,
        feature_image_alt:   document.getElementById('featureAlt')?.value,
        feature_image_title: document.getElementById('featureTitle')?.value,
        author_id:           document.getElementById('authorId')?.value || null,
        status:              status || 'draft',
        publish_date:        (document.getElementById('publishDate')?.value || '') + ' ' + (document.getElementById('publishTime')?.value || '00:00'),
        tags:                JSON.parse(document.getElementById('tagsHidden')?.value || '[]'),
        category_ids:        Array.from(checkedCats).map(c => parseInt(c.value)),
    };

    // TOC
    const toc = buildTOC(data.content);
    data.toc  = toc;

    const saveBtn = document.getElementById('saveBtnMain');
    if (saveBtn) { saveBtn.classList.add('btn-loading'); saveBtn.innerHTML = '<span class="spinner"></span> Saving...'; }

    fetch('/api/blog-save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            if (redirect && res.data) {
                setTimeout(() => { window.location.href = '/admin/blogs.php?msg=saved'; }, 1200);
            } else if (res.data && res.data.id && !document.getElementById('blogId').value) {
                document.getElementById('blogId').value = res.data.id;
                history.replaceState(null, '', '?id=' + res.data.id);
            }
        } else {
            showToast(res.message || 'Save failed', 'error');
        }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'))
    .finally(() => {
        if (saveBtn) { saveBtn.classList.remove('btn-loading'); saveBtn.innerHTML = '<i class="fas fa-save"></i> Save'; }
    });
}
