<?php
/**
 * API: Blog Categories & Authors management
 * GET  /api/blog-categories.php          -> list all categories (public)
 * GET  /api/blog-categories.php?authors  -> list all authors (public)
 * POST /api/blog-categories.php         -> add/edit/delete (requires auth)
 * Body: { action: "add|edit|delete", type: "category|author|tag", ... }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once __DIR__ . '/db-config.php';
require_once __DIR__ . '/admin-auth.php';

$pdo = getDBConnection();

// --- GET: Public lists ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['authors'])) {
        $stmt = $pdo->query('SELECT id, name, bio, image, page_url FROM blog_authors WHERE is_active = 1 ORDER BY name');
        echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
    } elseif (isset($_GET['tags'])) {
        $stmt = $pdo->query('SELECT id, name, slug FROM blog_tags ORDER BY name');
        echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
    } else {
        $stmt = $pdo->query('SELECT id, name, slug, description FROM blog_categories WHERE is_active = 1 ORDER BY name');
        echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
    }
    exit;
}

// --- POST: Modifications require admin ---
requireAdminAuth();
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && !empty($_POST)) $input = $_POST;
if (!$input) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'No data']); exit; }

$action = $input['action'] ?? '';
$type   = $input['type'] ?? 'category';

try {
    if ($type === 'category') {
        if ($action === 'add') {
            $name = trim($input['name'] ?? '');
            $desc = trim($input['description'] ?? '');
            if (!$name) { echo json_encode(['status'=>'error','message'=>'Name required']); exit; }
            $slug = generateSlug($name);
            // Make slug unique
            $base = $slug; $i = 1;
            while ($pdo->prepare('SELECT id FROM blog_categories WHERE slug=?')->execute([$slug]) && $pdo->query("SELECT id FROM blog_categories WHERE slug='$slug'")->fetch()) {
                $slug = $base . '-' . $i++;
            }
            $stmt = $pdo->prepare('INSERT INTO blog_categories (name, slug, description) VALUES (?,?,?)');
            $stmt->execute([$name, $slug, $desc]);
            echo json_encode(['status'=>'success','message'=>'Category added','data'=>['id'=>$pdo->lastInsertId(),'slug'=>$slug]]);

        } elseif ($action === 'edit') {
            $id   = (int)($input['id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $desc = trim($input['description'] ?? '');
            if (!$id || !$name) { echo json_encode(['status'=>'error','message'=>'ID and name required']); exit; }
            $pdo->prepare('UPDATE blog_categories SET name=?, description=? WHERE id=?')->execute([$name,$desc,$id]);
            echo json_encode(['status'=>'success','message'=>'Category updated']);

        } elseif ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare('UPDATE blog_categories SET is_active=0 WHERE id=?')->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Category deactivated']);
        }

    } elseif ($type === 'author') {
        if ($action === 'add' || $action === 'edit') {
            $id      = (int)($input['id'] ?? 0);
            $name    = trim($input['name'] ?? '');
            $bio     = trim($input['bio'] ?? '');
            $image   = trim($input['image'] ?? '');
            $pageUrl = trim($input['page_url'] ?? '');
            if (!$name) { echo json_encode(['status'=>'error','message'=>'Author name required']); exit; }
            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO blog_authors (name,bio,image,page_url) VALUES (?,?,?,?)');
                $stmt->execute([$name,$bio,$image,$pageUrl]);
                echo json_encode(['status'=>'success','message'=>'Author added','data'=>['id'=>$pdo->lastInsertId()]]);
            } else {
                $pdo->prepare('UPDATE blog_authors SET name=?,bio=?,image=?,page_url=? WHERE id=?')->execute([$name,$bio,$image,$pageUrl,$id]);
                echo json_encode(['status'=>'success','message'=>'Author updated']);
            }
        } elseif ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare('UPDATE blog_authors SET is_active=0 WHERE id=?')->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Author deactivated']);
        }

    } elseif ($type === 'tag') {
        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare('DELETE FROM blog_tag_relations WHERE tag_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM blog_tags WHERE id=?')->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Tag deleted']);
        }
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
