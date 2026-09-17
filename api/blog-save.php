<?php
/**
 * API: Save / Update Blog
 * POST /api/blog-save.php
 * Requires admin session.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }

session_start();
require_once __DIR__ . '/db-config.php';
require_once __DIR__ . '/admin-auth.php';
requireAdminAuth();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input && !empty($_POST)) $input = $_POST;
if (!$input) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'No data received']); exit; }

// --- Extract & sanitize fields ---
$id               = isset($input['id']) && $input['id'] ? (int)$input['id'] : null;
$title            = trim($input['title'] ?? '');
$slug             = trim($input['slug'] ?? '');
$excerpt          = trim($input['excerpt'] ?? '');
$content          = $input['content'] ?? '';
$metaTitle        = trim($input['meta_title'] ?? '');
$metaDesc         = trim($input['meta_description'] ?? '');
$focusKeyword     = trim($input['focus_keyword'] ?? '');
$primaryKeyword   = trim($input['primary_keyword'] ?? '');
$featureImage     = trim($input['feature_image'] ?? '');
$featureAlt       = trim($input['feature_image_alt'] ?? '');
$featureTitle     = trim($input['feature_image_title'] ?? '');
$authorId         = !empty($input['author_id']) ? (int)$input['author_id'] : null;
$categoryIds      = $input['category_ids'] ?? [];
$status           = $input['status'] ?? 'draft';
$publishDate      = $input['publish_date'] ?? null;
$ogImage          = trim($input['og_image'] ?? $featureImage);
$schemaJson       = $input['schema_json'] ?? null;
$tags             = $input['tags'] ?? [];

// Validate
$validStatuses = ['draft','pending','published','scheduled'];
if (!in_array($status, $validStatuses)) $status = 'draft';

if (empty($title)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Blog title is required']); exit; }
if (empty($featureImage)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Feature image is required']); exit; }
if (empty($featureAlt)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Feature image alt text is required']); exit; }
if (empty($excerpt)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Short description / excerpt is required']); exit; }
if (empty($focusKeyword)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Focus keyword is required']); exit; }
if (empty($categoryIds)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'At least one category is required']); exit; }
if ($status === 'scheduled' && empty($publishDate)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Publish date required for scheduled posts']); exit; }

try {
    $pdo = getDBConnection();

    // Generate slug
    if (empty($slug)) $slug = generateSlug($title);
    $slug = generateSlug($slug); // normalize
    $slug = makeUniqueSlug($pdo, $slug, $id);

    // TOC & heading IDs
    $toc         = extractTOC($content);
    $content     = injectHeadingIds($content);
    $readTime    = estimateReadTime($content);
    $tocJson     = json_encode($toc);
    $catJson     = json_encode(array_map('intval', (array)$categoryIds));
    $publishDt   = $publishDate ? date('Y-m-d H:i:s', strtotime($publishDate)) : null;
    if ($status === 'published' && !$publishDt) $publishDt = date('Y-m-d H:i:s');

    if ($id) {
        // UPDATE
        $stmt = $pdo->prepare('UPDATE blogs SET
            title=:title, slug=:slug, excerpt=:excerpt, content=:content, toc=:toc,
            feature_image=:fi, feature_image_alt=:fia, feature_image_title=:fit,
            meta_title=:mt, meta_description=:md, focus_keyword=:fk, primary_keyword=:pk,
            author_id=:aid, category_ids=:cids, status=:status, publish_date=:pd,
            og_image=:og, schema_json=:schema, read_time=:rt
            WHERE id=:id');
        $stmt->execute([
            ':title'=>htmlspecialchars($title,ENT_QUOTES,'UTF-8'),
            ':slug'=>$slug, ':excerpt'=>htmlspecialchars($excerpt,ENT_QUOTES,'UTF-8'),
            ':content'=>$content, ':toc'=>$tocJson,
            ':fi'=>$featureImage, ':fia'=>htmlspecialchars($featureAlt,ENT_QUOTES,'UTF-8'),
            ':fit'=>htmlspecialchars($featureTitle,ENT_QUOTES,'UTF-8'),
            ':mt'=>htmlspecialchars($metaTitle,ENT_QUOTES,'UTF-8'),
            ':md'=>htmlspecialchars($metaDesc,ENT_QUOTES,'UTF-8'),
            ':fk'=>htmlspecialchars($focusKeyword,ENT_QUOTES,'UTF-8'),
            ':pk'=>htmlspecialchars($primaryKeyword,ENT_QUOTES,'UTF-8'),
            ':aid'=>$authorId, ':cids'=>$catJson, ':status'=>$status,
            ':pd'=>$publishDt, ':og'=>$ogImage, ':schema'=>$schemaJson,
            ':rt'=>$readTime, ':id'=>$id
        ]);
        $blogId = $id;
    } else {
        // INSERT
        $stmt = $pdo->prepare('INSERT INTO blogs
            (title,slug,excerpt,content,toc,feature_image,feature_image_alt,feature_image_title,
            meta_title,meta_description,focus_keyword,primary_keyword,author_id,category_ids,
            status,publish_date,og_image,schema_json,read_time)
            VALUES(:title,:slug,:excerpt,:content,:toc,:fi,:fia,:fit,:mt,:md,:fk,:pk,:aid,:cids,
            :status,:pd,:og,:schema,:rt)');
        $stmt->execute([
            ':title'=>htmlspecialchars($title,ENT_QUOTES,'UTF-8'),
            ':slug'=>$slug, ':excerpt'=>htmlspecialchars($excerpt,ENT_QUOTES,'UTF-8'),
            ':content'=>$content, ':toc'=>$tocJson,
            ':fi'=>$featureImage, ':fia'=>htmlspecialchars($featureAlt,ENT_QUOTES,'UTF-8'),
            ':fit'=>htmlspecialchars($featureTitle,ENT_QUOTES,'UTF-8'),
            ':mt'=>htmlspecialchars($metaTitle,ENT_QUOTES,'UTF-8'),
            ':md'=>htmlspecialchars($metaDesc,ENT_QUOTES,'UTF-8'),
            ':fk'=>htmlspecialchars($focusKeyword,ENT_QUOTES,'UTF-8'),
            ':pk'=>htmlspecialchars($primaryKeyword,ENT_QUOTES,'UTF-8'),
            ':aid'=>$authorId, ':cids'=>$catJson, ':status'=>$status,
            ':pd'=>$publishDt, ':og'=>$ogImage, ':schema'=>$schemaJson, ':rt'=>$readTime
        ]);
        $blogId = (int)$pdo->lastInsertId();
    }

    // Handle tags
    $pdo->prepare('DELETE FROM blog_tag_relations WHERE blog_id = ?')->execute([$blogId]);
    if (!empty($tags)) {
        foreach ((array)$tags as $tagName) {
            $tagName = trim($tagName);
            if (!$tagName) continue;
            $tagSlug = generateSlug($tagName);
            $pdo->prepare('INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)')->execute([$tagName, $tagSlug]);
            $tagRow = $pdo->prepare('SELECT id FROM blog_tags WHERE slug = ?');
            $tagRow->execute([$tagSlug]);
            $tagId = $tagRow->fetchColumn();
            if ($tagId) {
                $pdo->prepare('INSERT IGNORE INTO blog_tag_relations (blog_id, tag_id) VALUES (?, ?)')->execute([$blogId, $tagId]);
            }
        }
    }

    echo json_encode([
        'status'  => 'success',
        'message' => $id ? 'Blog updated successfully' : 'Blog created successfully',
        'data'    => ['id' => $blogId, 'slug' => $slug]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Server error: '.$e->getMessage()]);
}
