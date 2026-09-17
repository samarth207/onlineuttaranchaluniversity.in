<?php
/**
 * API: Get Blog(s)
 * GET /api/blog-get.php?slug=slug-name        -> single post
 * GET /api/blog-get.php?latest=3              -> latest N published
 * GET /api/blog-get.php?list=1&status=all     -> admin list (requires auth)
 * GET /api/blog-get.php?category=slug&page=1  -> by category (public)
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();
require_once __DIR__ . '/db-config.php';
require_once __DIR__ . '/admin-auth.php';

$pdo = getDBConnection();
$now = date('Y-m-d H:i:s');

// --- Single post by slug ---
if (isset($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    $admin = isAdminLoggedIn();
    
    $sql = 'SELECT b.*, a.name as author_name, a.bio as author_bio, a.image as author_image, a.page_url as author_page
            FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
            WHERE b.slug = ? AND b.status != "deleted"';
    
    if (!$admin) {
        $sql .= ' AND b.status = "published" AND (b.publish_date IS NULL OR b.publish_date <= ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug, $now]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug]);
    }
    
    $blog = $stmt->fetch();
    if (!$blog) {
        http_response_code(404);
        echo json_encode(['status'=>'error','message'=>'Blog not found']);
        exit;
    }

    // Increment view count (non-admin only)
    if (!$admin) {
        $pdo->prepare('UPDATE blogs SET views = views + 1 WHERE id = ?')->execute([$blog['id']]);
    }

    // Get tags
    $tagStmt = $pdo->prepare('SELECT t.name, t.slug FROM blog_tags t 
                              JOIN blog_tag_relations r ON t.id = r.tag_id WHERE r.blog_id = ?');
    $tagStmt->execute([$blog['id']]);
    $blog['tags'] = $tagStmt->fetchAll();

    // Get categories
    $catIds = json_decode($blog['category_ids'] ?? '[]', true);
    $blog['categories'] = [];
    if (!empty($catIds)) {
        $placeholders = implode(',', array_fill(0, count($catIds), '?'));
        $catStmt = $pdo->prepare("SELECT id, name, slug FROM blog_categories WHERE id IN ($placeholders)");
        $catStmt->execute($catIds);
        $blog['categories'] = $catStmt->fetchAll();
    }

    // TOC decode
    $blog['toc'] = json_decode($blog['toc'] ?? '[]', true);

    echo json_encode(['status'=>'success','data'=>$blog]);
    exit;
}

// --- Latest published posts ---
if (isset($_GET['latest'])) {
    $limit = min(20, max(1, (int)$_GET['latest']));
    $stmt = $pdo->prepare('SELECT b.id, b.title, b.slug, b.excerpt, b.feature_image, b.feature_image_alt,
                           b.publish_date, b.read_time, b.views, b.category_ids,
                           a.name as author_name, a.image as author_image
                           FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
                           WHERE b.status = "published" AND (b.publish_date IS NULL OR b.publish_date <= ?)
                           ORDER BY b.publish_date DESC, b.created_at DESC LIMIT ?');
    $stmt->execute([$now, $limit]);
    $posts = $stmt->fetchAll();
    
    // Attach category names
    foreach ($posts as &$post) {
        $catIds = json_decode($post['category_ids'] ?? '[]', true);
        $post['categories'] = [];
        if (!empty($catIds)) {
            $pl = implode(',', array_fill(0, count($catIds), '?'));
            $cs = $pdo->prepare("SELECT name, slug FROM blog_categories WHERE id IN ($pl)");
            $cs->execute($catIds);
            $post['categories'] = $cs->fetchAll();
        }
    }
    echo json_encode(['status'=>'success','data'=>$posts]);
    exit;
}

// --- Admin list (requires auth) ---
if (isset($_GET['list'])) {
    requireAdminAuth();
    $statusFilter = $_GET['status'] ?? 'all';
    $search       = trim($_GET['search'] ?? '');
    $categoryFilter = (int)($_GET['category'] ?? 0);
    $page         = max(1, (int)($_GET['page'] ?? 1));
    $perPage      = 20;
    $offset       = ($page - 1) * $perPage;

    $where    = ['b.status != "deleted"'];
    $params   = [];

    if ($statusFilter !== 'all' && in_array($statusFilter, ['draft','pending','published','scheduled'])) {
        $where[] = 'b.status = ?';
        $params[] = $statusFilter;
    }
    if ($search) {
        $where[] = 'b.title LIKE ?';
        $params[] = '%' . $search . '%';
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $pdo->prepare("SELECT b.id, b.title, b.slug, b.status, b.publish_date, b.views,
                           b.created_at, b.updated_at, b.category_ids, b.read_time,
                           a.name as author_name
                           FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
                           WHERE $whereSql ORDER BY b.updated_at DESC LIMIT ? OFFSET ?");
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data'   => $posts,
        'meta'   => ['total'=>$total,'page'=>$page,'per_page'=>$perPage,'pages'=>ceil($total/$perPage)]
    ]);
    exit;
}

// --- Public category listing ---
if (isset($_GET['category'])) {
    $catSlug = trim($_GET['category']);
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;
    $offset  = ($page - 1) * $perPage;

    $catStmt = $pdo->prepare('SELECT id, name, slug FROM blog_categories WHERE slug = ? AND is_active = 1');
    $catStmt->execute([$catSlug]);
    $cat = $catStmt->fetch();
    if (!$cat) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Category not found']); exit; }

    $catId = $cat['id'];
    $stmt = $pdo->prepare('SELECT b.id, b.title, b.slug, b.excerpt, b.feature_image, b.feature_image_alt,
                           b.publish_date, b.read_time, b.views, a.name as author_name
                           FROM blogs b LEFT JOIN blog_authors a ON b.author_id = a.id
                           WHERE b.status = "published" AND (b.publish_date IS NULL OR b.publish_date <= ?)
                           AND JSON_CONTAINS(b.category_ids, ?)
                           ORDER BY b.publish_date DESC LIMIT ? OFFSET ?');
    $stmt->execute([$now, (string)$catId, $perPage, $offset]);
    $posts = $stmt->fetchAll();

    echo json_encode([
        'status'   => 'success',
        'category' => $cat,
        'data'     => $posts,
        'meta'     => ['page'=>$page,'per_page'=>$perPage]
    ]);
    exit;
}

// Default: no valid param
http_response_code(400);
echo json_encode(['status'=>'error','message'=>'Invalid request. Use ?slug=, ?latest=, ?list=1, or ?category=']);
