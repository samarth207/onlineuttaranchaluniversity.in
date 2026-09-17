<?php
/**
 * API: Delete Blog
 * POST /api/blog-delete.php
 * Body: { "id": 123 }
 * Requires admin session. Soft-deletes (status = deleted).
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

$id = isset($input['id']) ? (int)$input['id'] : 0;
if (!$id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Blog ID required']); exit; }

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare('UPDATE blogs SET status = "deleted" WHERE id = ? AND status != "deleted"');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['status'=>'error','message'=>'Blog not found or already deleted']);
        exit;
    }
    echo json_encode(['status'=>'success','message'=>'Blog deleted successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Server error: '.$e->getMessage()]);
}
