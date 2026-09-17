<?php
session_start();
require_once __DIR__ . '/../api/db-config.php';
require_once __DIR__ . '/../api/admin-auth.php';
require_once __DIR__ . '/includes/auth-check.php';

$pdo   = getDBConnection();
$blogId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$blog  = null;
$tags  = [];
$catIds = [];

if ($blogId) {
    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ? AND status != "deleted"');
    $stmt->execute([$blogId]);
    $blog = $stmt->fetch();
    if (!$blog) { header('Location: /admin/blogs.php?msg=not_found'); exit; }
    // Tags
    $tStmt = $pdo->prepare('SELECT t.name FROM blog_tags t JOIN blog_tag_relations r ON t.id = r.tag_id WHERE r.blog_id = ?');
    $tStmt->execute([$blogId]);
    $tags = array_column($tStmt->fetchAll(), 'name');
    $catIds = json_decode($blog['category_ids'] ?? '[]', true);
}

$categories = $pdo->query('SELECT * FROM blog_categories WHERE is_active = 1 ORDER BY name')->fetchAll();
$authors    = $pdo->query('SELECT * FROM blog_authors WHERE is_active = 1 ORDER BY name')->fetchAll();

// Programs for lead form select
$programs = ['Online MBA','Online BBA','Online MCA','Online BCA','Executive MBA'];

$pageTitle  = $blog ? 'Edit Blog' : 'New Blog';
$activePage = $blog ? 'blogs' : 'new-blog';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.tox-notification,.tox-statusbar__branding{display:none!important}
.tox .tox-toolbar__primary{background:#f8fafc!important}
.tox-tinymce{border:1.5px solid #e5e7eb!important;border-radius:8px!important}
.toc-preview-list{list-style:none;padding:0;margin:0}
.toc-preview-list li:last-child{border:none}
</style>

<!-- Sticky Action Bar -->
<div style="position:sticky;top:64px;z-index:90;background:#f0f2f7;padding-bottom:12px;margin:-28px -28px 20px;padding:12px 28px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="/admin/blogs.php" style="color:#6b7280;text-decoration:none;font-size:13px"><i class="fas fa-arrow-left"></i> All Blogs</a>
        <span id="lastSavedLabel" style="font-size:12px;color:#9ca3af"><?= $blog ? 'Last saved: '.date('d M Y H:i', strtotime($blog['updated_at'])) : 'Not saved yet' ?></span>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button onclick="saveBlog('draft', false)" class="btn btn-secondary btn-sm"><i class="fas fa-file-alt"></i> Save Draft</button>
        <button onclick="saveBlog('pending', false)" class="btn btn-secondary btn-sm" style="color:#d97706;border-color:#f59e0b"><i class="fas fa-hourglass-half"></i> Submit for Review</button>
        <button onclick="saveBlog('published', true)" class="btn btn-primary btn-sm" id="saveBtnMain"><i class="fas fa-save"></i> Publish</button>
    </div>
</div>

<input type="hidden" id="blogId" value="<?= $blog ? $blog['id'] : '' ?>">

<div class="editor-layout">

<!-- ============ MAIN COLUMN ============ -->
<div class="editor-main">

    <!-- SECTION 1: Blog Title & Excerpt -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-heading"></i>
            <span class="section-card-title">Blog Title & Excerpt</span>
        </div>
        <div class="section-card-body">
            <div class="form-group">
                <label class="form-label">Blog Title (H1) <span class="required">*</span> <span class="char-counter" id="titleCounter">0/70</span></label>
                <input type="text" class="form-control" id="blogTitle" maxlength="70"
                       value="<?= $blog ? htmlspecialchars($blog['title']) : '' ?>"
                       placeholder="Enter blog title (displays as H1 on page)">
                <div class="form-hint">This will be rendered as the main <strong>H1</strong> heading on the blog page.</div>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Short Description / Excerpt <span class="required">*</span> <span class="char-counter" id="excerptCounter">0/250</span></label>
                <textarea class="form-control" id="blogExcerpt" maxlength="250" rows="3"
                          placeholder="A brief summary shown in blog listing and meta description..."><?= $blog ? htmlspecialchars($blog['excerpt']) : '' ?></textarea>
            </div>
        </div>
    </div>

    <!-- SECTION 2: Feature Image -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-image"></i>
            <span class="section-card-title">Feature Image</span>
        </div>
        <div class="section-card-body">
            <?php if ($blog && $blog['feature_image']): ?>
            <div id="featureImgArea" style="display:none" class="image-upload-area">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Drag & drop image or <span class="browse-link">browse</span></p>
                <small>JPG, WebP · Max 5MB · Recommended 1200×628px</small>
                <input type="file" id="featureImgInput" accept="image/jpeg,image/webp,image/png">
            </div>
            <div id="featureImgPreview">
                <div class="image-preview">
                    <img src="<?= htmlspecialchars($blog['feature_image']) ?>" alt="Feature image preview">
                    <button type="button" class="image-preview-remove" onclick="removeImagePreview('featureImgArea','featureImgPreview','featureImageUrl')"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <input type="hidden" id="featureImageUrl" value="<?= htmlspecialchars($blog['feature_image']) ?>">
            <?php else: ?>
            <div id="featureImgArea" class="image-upload-area">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Drag & drop image or <span class="browse-link">browse</span></p>
                <small>JPG, WebP · Max 5MB · Recommended 1200×628px</small>
                <input type="file" id="featureImgInput" accept="image/jpeg,image/webp,image/png">
            </div>
            <div id="featureImgPreview" style="display:none"></div>
            <input type="hidden" id="featureImageUrl" value="">
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Image Alt Text <span class="required">*</span></label>
                    <input type="text" class="form-control" id="featureAlt"
                           value="<?= $blog ? htmlspecialchars($blog['feature_image_alt']) : '' ?>"
                           placeholder="Describe the image for SEO & accessibility">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Image Title <span style="font-weight:400;color:#6b7280">(Optional)</span></label>
                    <input type="text" class="form-control" id="featureTitle"
                           value="<?= $blog ? htmlspecialchars($blog['feature_image_title'] ?? '') : '' ?>"
                           placeholder="Image title attribute">
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: Blog Content Editor -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-pen-nib"></i>
            <span class="section-card-title">Blog Content</span>
            <span style="font-size:12px;color:#9ca3af;margin-left:auto" id="tocHeadingCount">0 headings</span>
        </div>
        <div class="section-card-body" style="padding:12px">
            <textarea id="blogContent"><?= $blog ? $blog['content'] : '' ?></textarea>
        </div>
    </div>

</div>

<!-- ============ SIDEBAR ============ -->
<div class="editor-sidebar">

    <!-- SECTION 4: SEO & Meta -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-search"></i>
            <span class="section-card-title">SEO & Meta</span>
        </div>
        <div class="section-card-body">
            <div class="form-group">
                <label class="form-label">Meta Title <span class="required">*</span> <span class="char-counter" id="metaTitleCounter">0/60</span></label>
                <input type="text" class="form-control" id="metaTitle" maxlength="70"
                       value="<?= $blog ? htmlspecialchars($blog['meta_title']) : '' ?>"
                       placeholder="SEO title (60 chars ideal)">
                <div class="meta-meter"><div class="meta-meter-fill meter-ok" id="metaTitleMeter" style="width:0%"></div></div>
                <div class="form-hint">60 characters ideal for Google display.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Meta Description <span class="required">*</span> <span class="char-counter" id="metaDescCounter">0/250</span></label>
                <textarea class="form-control" id="metaDescription" rows="3" maxlength="300"
                          placeholder="200–250 chars. Used in search results & blog listing."><?= $blog ? htmlspecialchars($blog['meta_description']) : '' ?></textarea>
                <div class="meta-meter"><div class="meta-meter-fill meter-ok" id="metaDescMeter" style="width:0%"></div></div>
            </div>
            <div class="form-group">
                <label class="form-label">Focus Keyword <span class="required">*</span></label>
                <input type="text" class="form-control" id="focusKeyword"
                       value="<?= $blog ? htmlspecialchars($blog['focus_keyword']) : '' ?>"
                       placeholder="e.g. online MBA course 2025">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Primary Keyword <span style="font-weight:400;color:#6b7280">(SEO Analytics)</span></label>
                <input type="text" class="form-control" id="primaryKeyword"
                       value="<?= $blog ? htmlspecialchars($blog['primary_keyword'] ?? '') : '' ?>"
                       placeholder="Primary target keyword">
            </div>
        </div>
    </div>

    <!-- SECTION 5: URL Slug -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-link"></i>
            <span class="section-card-title">URL Slug</span>
        </div>
        <div class="section-card-body">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Slug</label>
                <div style="display:flex;align-items:center;gap:0">
                    <span style="border:1.5px solid #e5e7eb;border-right:none;padding:10px 10px;border-radius:8px 0 0 8px;font-size:12px;color:#9ca3af;background:#f8fafc">/blog/</span>
                    <input type="text" class="form-control" id="blogSlug" style="border-radius:0 8px 8px 0;border-left-color:#e5e7eb"
                           value="<?= $blog ? htmlspecialchars($blog['slug']) : '' ?>"
                           placeholder="auto-generated-from-title">
                </div>
                <div class="form-hint">Lowercase, hyphens only. Auto-generated from title.</div>
            </div>
        </div>
    </div>

    <!-- SECTION 6: Publish Settings -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-calendar-alt"></i>
            <span class="section-card-title">Publish Settings</span>
        </div>
        <div class="section-card-body">
            <div class="form-group">
                <label class="form-label">Publish Status</label>
                <div class="status-tabs" id="statusTabs">
                    <?php $curStatus = $blog ? $blog['status'] : 'draft'; ?>
                    <?php foreach (['draft'=>'Draft','pending'=>'Pending','published'=>'Published','scheduled'=>'Scheduled'] as $s => $l): ?>
                    <button type="button" class="status-tab <?= $curStatus === $s ? 'active-'.$s : '' ?>"
                            data-status="<?= $s ?>"
                            onclick="selectStatus('<?= $s ?>')"><?= $l ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="scheduleDateRow" style="display:<?= ($curStatus === 'scheduled') ? 'block' : 'none' ?>">
                <div class="form-group">
                    <label class="form-label">Publish Date</label>
                    <input type="date" class="form-control" id="publishDate"
                           value="<?= $blog && $blog['publish_date'] ? date('Y-m-d', strtotime($blog['publish_date'])) : '' ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Publish Time</label>
                    <input type="time" class="form-control" id="publishTime"
                           value="<?= $blog && $blog['publish_date'] ? date('H:i', strtotime($blog['publish_date'])) : '09:00' ?>">
                </div>
            </div>
            <?php if ($blog): ?>
            <div style="font-size:12px;color:#9ca3af;margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9">
                <i class="fas fa-sync-alt"></i> Last updated: <?= date('d M Y H:i', strtotime($blog['updated_at'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 7: Categories -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-folder-open"></i>
            <span class="section-card-title">Categories <span class="required">*</span></span>
        </div>
        <div class="section-card-body">
            <div class="checkbox-group">
            <?php foreach ($categories as $cat): ?>
                <?php $checked = in_array($cat['id'], $catIds); ?>
                <label class="checkbox-item <?= $checked ? 'checked' : '' ?>">
                    <input type="checkbox" class="cat-checkbox" value="<?= $cat['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </label>
            <?php endforeach; ?>
            </div>
            <div style="margin-top:10px">
                <a href="/admin/categories.php" target="_blank" style="font-size:12px;color:#d42b2b;text-decoration:none"><i class="fas fa-plus"></i> Add new category</a>
            </div>
        </div>
    </div>

    <!-- SECTION 8: Tags -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-tags"></i>
            <span class="section-card-title">Tags <span style="font-weight:400;color:#9ca3af">(Optional)</span></span>
        </div>
        <div class="section-card-body">
            <div id="tagsWrapper" class="tags-input-wrapper">
                <input type="text" class="tags-input" placeholder="Type tag and press Enter...">
            </div>
            <input type="hidden" id="tagsHidden" value="<?= htmlspecialchars(json_encode($tags)) ?>">
            <div class="form-hint">Press Enter or comma after each tag.</div>
        </div>
    </div>

    <!-- SECTION 9: Author -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-user-edit"></i>
            <span class="section-card-title">Author</span>
        </div>
        <div class="section-card-body">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Select Author</label>
                <select class="form-control" id="authorId">
                    <option value="">— Select Author —</option>
                    <?php foreach ($authors as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= ($blog && $blog['author_id'] == $a['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div style="margin-top:8px">
                    <a href="/admin/authors.php" target="_blank" style="font-size:12px;color:#d42b2b;text-decoration:none"><i class="fas fa-plus"></i> Add new author</a>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 10: Table of Contents Preview -->
    <div class="section-card">
        <div class="section-card-header">
            <i class="fas fa-list-ol"></i>
            <span class="section-card-title">Table of Contents</span>
        </div>
        <div class="section-card-body">
            <div id="tocPreviewContainer">
                <p style="color:#9ca3af;font-size:13px">TOC auto-generates from H2/H3/H4 headings in your content.</p>
            </div>
        </div>
    </div>

    <!-- Bottom Save Buttons -->
    <div style="display:flex;flex-direction:column;gap:8px">
        <button onclick="saveBlog('published', true)" class="btn btn-primary btn-lg">
            <i class="fas fa-bolt"></i> Publish Now
        </button>
        <button onclick="saveBlog('scheduled', false)" class="btn btn-secondary">
            <i class="fas fa-clock"></i> Schedule Post
        </button>
        <button onclick="saveBlog('draft', false)" class="btn btn-secondary">
            <i class="fas fa-file-alt"></i> Save as Draft
        </button>
        <?php if ($blog && $blog['status'] === 'published'): ?>
        <a href="/blog/<?= htmlspecialchars($blog['slug']) ?>" target="_blank" class="btn btn-secondary">
            <i class="fas fa-external-link-alt"></i> View Live Post
        </a>
        <?php endif; ?>
    </div>

</div>
</div><!-- /.editor-layout -->

<!-- TinyMCE — via jsDelivr (no API key required) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js"></script>

<script>
// Programs list for lead form
const uuPrograms = <?= json_encode($programs) ?>;

tinymce.init({
    selector: '#blogContent',
    // Tell TinyMCE where to find its own plugin files on jsDelivr
    base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
    suffix: '.min',
    height: 680,
    menubar: 'file edit view insert format tools table',
    plugins: [
        'advlist','autolink','lists','link','image','charmap','preview','anchor',
        'searchreplace','visualblocks','code','fullscreen','insertdatetime',
        'table','help','wordcount'
        // hr merged into TinyMCE 6 core — use InsertHorizontalRule command
        // nonbreaking + emoticons have separate files; omit to avoid 404s
    ],
    toolbar: [
        'undo redo | styles | bold italic underline strikethrough | forecolor backcolor',
        'alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | InsertHorizontalRule',
        'link unlink | image | table | faqblock leadformblock | code fullscreen preview | removeformat'
    ],
    toolbar_mode: 'wrap',
    style_formats: [
        { title: 'Headings', items: [
            { title: 'Heading 2', format: 'h2' },
            { title: 'Heading 3', format: 'h3' },
            { title: 'Heading 4', format: 'h4' },
        ]},
        { title: 'Inline', items: [
            { title: 'Bold', format: 'bold' },
            { title: 'Italic', format: 'italic' },
            { title: 'Underline', format: 'underline' },
        ]},
        { title: 'Blocks', items: [
            { title: 'Paragraph', format: 'p' },
            { title: 'Blockquote', format: 'blockquote' },
        ]}
    ],
    // Image upload
    images_upload_url: '/api/blog-upload.php?type=content',
    images_upload_credentials: true,
    automatic_uploads: true,
    image_advtab: true,
    image_title: true,
    image_description: true,
    // Table
    table_appearance_options: true,
    table_advtab: true,
    table_cell_advtab: true,
    table_row_advtab: true,
    table_default_attributes: { border: '0' },
    table_default_styles: { 'border-collapse': 'collapse', 'width': '100%' },
    // Content appearance
    content_css: '/assets/css/blog-content.css',
    body_class: 'blog-editor-body',
    content_style: `
        body { font-family: 'Poppins', sans-serif; font-size: 15px; color: #1a2332; line-height: 1.8; max-width: 100%; padding: 20px; }
        h2 { color: #0f1d35; font-size: 22px; margin: 28px 0 12px; font-weight: 700; }
        h3 { color: #0f1d35; font-size: 18px; margin: 22px 0 10px; font-weight: 600; }
        h4 { color: #0f1d35; font-size: 16px; margin: 18px 0 8px; font-weight: 600; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        table td, table th { border: 1px solid #e5e7eb; padding: 10px 14px; }
        table th { background: #0f1d35; color: #fff; font-weight: 600; }
        table tr:nth-child(even) { background: #f8fafc; }
        .faq-block { background: #f8fafc; border-left: 4px solid #d42b2b; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .faq-question { color: #0f1d35; font-size: 16px; font-weight: 700; margin: 0 0 8px; }
        .faq-answer { color: #374151; margin: 0; }
        .blog-lead-form { background: linear-gradient(135deg, #0f1d35, #1a2d4a); border-radius: 12px; padding: 28px; margin: 24px 0; color: #fff; }
        .blf-headline { color: #fff !important; font-size: 20px; font-weight: 700; margin: 0 0 16px; text-align: center; }
        .blf-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
        .blf-input { padding: 10px 14px; border-radius: 6px; border: none; font-size: 14px; width: 100%; }
        .blf-btn { width: 100%; padding: 12px; background: #d42b2b; color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 700; cursor: pointer; }
        img { max-width: 100%; height: auto; border-radius: 8px; }
        blockquote { border-left: 4px solid #d42b2b; margin: 16px 0; padding: 12px 20px; background: #fef9ff; color: #374151; font-style: italic; border-radius: 0 8px 8px 0; }
    `,
    setup: function(editor) {
        // ===== FAQ Block Button =====
        editor.ui.registry.addButton('faqblock', {
            text: 'FAQ Block',
            icon: 'help',
            tooltip: 'Insert FAQ Section',
            onAction: function() {
                editor.windowManager.open({
                    title: 'Insert FAQ Block',
                    body: {
                        type: 'panel',
                        items: [
                            { type: 'input', name: 'question', label: 'Question *', placeholder: 'Enter your question here?' },
                            { type: 'textarea', name: 'answer', label: 'Answer *', placeholder: 'Enter the detailed answer here...' }
                        ]
                    },
                    buttons: [
                        { type: 'cancel', text: 'Cancel' },
                        { type: 'submit', text: 'Insert FAQ', primary: true }
                    ],
                    onSubmit: function(api) {
                        const d = api.getData();
                        if (!d.question.trim() || !d.answer.trim()) {
                            editor.notificationManager.open({ text: 'Both question and answer are required.', type: 'error', timeout: 3000 });
                            return;
                        }
                        editor.insertContent(`<div class="faq-block"><h3 class="faq-question">${d.question}</h3><div class="faq-answer"><p>${d.answer}</p></div></div><p>&nbsp;</p>`);
                        api.close();
                    }
                });
            }
        });

        // ===== Lead Form Block Button =====
        editor.ui.registry.addButton('leadformblock', {
            text: 'Lead Form',
            icon: 'document-properties',
            tooltip: 'Insert Lead Capture Form',
            onAction: function() {
                editor.windowManager.open({
                    title: 'Insert Lead Capture Form',
                    body: {
                        type: 'panel',
                        items: [
                            { type: 'input', name: 'headline', label: 'Form Headline', placeholder: 'Get Free Counseling' },
                            { type: 'input', name: 'subtext', label: 'Subtext (Optional)', placeholder: 'Fill the form and our team will call you back' },
                            { type: 'input', name: 'button_text', label: 'Button Text', placeholder: 'Apply Now' }
                        ]
                    },
                    buttons: [
                        { type: 'cancel', text: 'Cancel' },
                        { type: 'submit', text: 'Insert Form', primary: true }
                    ],
                    onSubmit: function(api) {
                        const d = api.getData();
                        const headline = d.headline || 'Get Free Counseling';
                        const subtext  = d.subtext || '';
                        const btnText  = d.button_text || 'Apply Now';
                        const programOptions = uuPrograms.map(p => `<option value="${p}">${p}</option>`).join('');
                        editor.insertContent(`
<div class="blog-lead-form">
    <div class="blf-inner">
        <h3 class="blf-headline">${headline}</h3>
        ${subtext ? `<p style="text-align:center;color:rgba(255,255,255,0.75);margin:-8px 0 16px;font-size:14px">${subtext}</p>` : ''}
        <form class="blf-form" action="/api/submit-lead.php" method="POST" onsubmit="return handleBlogLeadForm(this, event)">
            <div class="blf-fields">
                <input type="text" name="StudentName" placeholder="Your Name *" required class="blf-input">
                <input type="tel" name="StudentMobile" placeholder="Phone Number *" required class="blf-input" maxlength="15">
                <input type="email" name="StudentEmail" placeholder="Email Address *" required class="blf-input blf-full">
                <select name="StudentProgram" required class="blf-input blf-full">
                    <option value="">Select Course *</option>
                    ${programOptions}
                </select>
            </div>
            <button type="submit" class="blf-btn">${btnText}</button>
            <input type="hidden" name="source" value="Blog Form">
        </form>
    </div>
</div>
<p>&nbsp;</p>`);
                        api.close();
                    }
                });
            }
        });

        // ===== TOC Updater =====
        let tocTimer;
        editor.on('input keyup change NodeChange', function() {
            clearTimeout(tocTimer);
            tocTimer = setTimeout(function() {
                const content = editor.getContent();
                const toc     = buildTOC(content);
                renderTOCPreview(toc, 'tocPreviewContainer');
                document.getElementById('tocHeadingCount').textContent = toc.length + ' heading' + (toc.length !== 1 ? 's' : '');
            }, 800);
        });

        // ===== Word Count =====
        editor.on('WordCountUpdate', function(e) {
            // optional: show word count somewhere
        });

        // ===== Validate image alt text on image insert =====
        editor.on('SetContent', function() {
            // Check after content set
        });

        // ===== Inject lead form handler into TinyMCE iframe =====
        editor.on('init', function() {
            var win = editor.getWin();
            win.handleBlogLeadForm = function(form, e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                var origHTML = btn ? btn.innerHTML : '';
                if (btn) { btn.innerHTML = 'Sending...'; btn.disabled = true; }
                fetch('/api/submit-lead.php', { method: 'POST', body: new FormData(form) })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.Status === 'Success' || res.status === 'success') {
                        form.closest('.blog-lead-form').innerHTML = '<div style="text-align:center;padding:32px 20px"><div style="font-size:40px;margin-bottom:10px">✅</div><strong style="color:#fff;font-size:18px">Thank you!</strong><p style="color:rgba(255,255,255,0.8);margin-top:8px;font-size:14px">Our counselor will call you shortly.</p></div>';
                    } else {
                        if (btn) { btn.innerHTML = origHTML; btn.disabled = false; }
                        alert(res.Message || 'Please fill all required fields.');
                    }
                })
                .catch(function() {
                    if (btn) { btn.innerHTML = origHTML; btn.disabled = false; }
                    alert('Connection error. Please try again.');
                });
                return false;
            };
        });
    },
    // Force alt text on image dialog
    image_dimensions: true,
});

// ========= Init All Components =========
document.addEventListener('DOMContentLoaded', function() {
    // Char counters
    initCharCounter('blogTitle', 'titleCounter', 70, 40);
    initCharCounter('blogExcerpt', 'excerptCounter', 250, 120);
    initCharCounter('metaTitle', 'metaTitleCounter', 60, 40);
    initCharCounter('metaDescription', 'metaDescCounter', 250, 200);

    // Meta title meter
    document.getElementById('metaTitle').addEventListener('input', function() {
        const len   = this.value.length;
        const meter = document.getElementById('metaTitleMeter');
        const pct   = Math.min(100, (len / 60) * 100);
        meter.style.width = pct + '%';
        meter.className   = 'meta-meter-fill ' + (len > 60 ? 'meter-danger' : len >= 50 ? 'meter-ok' : 'meter-warn');
    });

    // Meta desc meter
    document.getElementById('metaDescription').addEventListener('input', function() {
        const len   = this.value.length;
        const meter = document.getElementById('metaDescMeter');
        const pct   = Math.min(100, (len / 250) * 100);
        meter.style.width = pct + '%';
        meter.className   = 'meta-meter-fill ' + (len > 250 ? 'meter-danger' : len >= 200 ? 'meter-ok' : 'meter-warn');
    });

    // Slug generator
    initSlugGenerator('blogTitle', 'blogSlug');

    // Auto-fill meta title from blog title if empty
    document.getElementById('blogTitle').addEventListener('input', function() {
        const mt = document.getElementById('metaTitle');
        if (!mt.value) mt.value = this.value.substring(0, 60);
        mt.dispatchEvent(new Event('input'));
    });

    // Tags input
    initTagsInput('tagsWrapper', 'tagsHidden');

    // Feature image upload
    initImageUpload('featureImgArea','featureImgInput','featureImgPreview','featureImageUrl','feature');

    // Trigger initial meter updates
    document.getElementById('metaTitle').dispatchEvent(new Event('input'));
    document.getElementById('metaDescription').dispatchEvent(new Event('input'));

    // Initial TOC if editing
    <?php if ($blog): ?>
    setTimeout(function() {
        if (typeof tinymce !== 'undefined' && tinymce.get('blogContent')) {
            const toc = buildTOC(tinymce.get('blogContent').getContent());
            renderTOCPreview(toc, 'tocPreviewContainer');
            document.getElementById('tocHeadingCount').textContent = toc.length + ' heading' + (toc.length !== 1 ? 's' : '');
        }
    }, 1500);
    <?php endif; ?>
});

// ========= Status Selection =========
let selectedStatus = '<?= $curStatus ?>';
function selectStatus(status) {
    selectedStatus = status;
    document.querySelectorAll('.status-tab').forEach(function(t) {
        t.className = 'status-tab';
    });
    var active = document.querySelector('.status-tab[data-status="' + status + '"]');
    if (active) active.className = 'status-tab active-' + status;
    document.getElementById('scheduleDateRow').style.display = (status === 'scheduled') ? 'block' : 'none';
}

// ========= Blog Lead Form Handler (for frontend use) =========
// This script will be needed in blog/post.php too
function handleBlogLeadForm(form, e) {
    e.preventDefault();
    const data = new FormData(form);
    data.append('source', 'Blog Lead Form');
    fetch('/api/submit-lead.php', { method: 'POST', body: data })
    .then(r => r.json()).then(res => {
        if (res.Status === 'Success') {
            form.innerHTML = '<div style="text-align:center;padding:20px;color:#fff"><i class="fas fa-check-circle" style="font-size:36px;color:#10b981;margin-bottom:8px;display:block"></i><strong>Thank you!</strong> We\'ll call you shortly.</div>';
        } else {
            alert(res.Message || 'Please fill all required fields.');
        }
    }).catch(() => alert('Something went wrong. Please try again.'));
    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
