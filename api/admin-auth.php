<?php
/**
 * Admin Auth Helper
 * Include this in every API endpoint that requires authentication.
 * Usage: require_once __DIR__ . '/admin-auth.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdminAuth() {
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please login.']);
        exit;
    }
}

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getCurrentAdmin() {
    return [
        'id'       => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'role'     => $_SESSION['admin_role'] ?? null,
        'name'     => $_SESSION['admin_name'] ?? null,
    ];
}

function setAdminSession($admin) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id']        = $admin['id'];
    $_SESSION['admin_username']  = $admin['username'];
    $_SESSION['admin_role']      = $admin['role'];
    $_SESSION['admin_name']      = $admin['full_name'] ?? $admin['username'];
}

function destroyAdminSession() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Generate a URL-safe slug from title
 */
function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Make slug unique by appending suffix if needed
 */
function makeUniqueSlug($pdo, $slug, $excludeId = null) {
    $original = $slug;
    $counter  = 1;
    while (true) {
        $sql    = 'SELECT id FROM blogs WHERE slug = :slug AND status != "deleted"';
        $params = [':slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $original . '-' . $counter;
        $counter++;
    }
}

/**
 * Estimate reading time from HTML content
 */
function estimateReadTime($html) {
    $text      = strip_tags($html);
    $wordCount = str_word_count($text);
    $minutes   = max(1, ceil($wordCount / 200));
    return $minutes;
}

/**
 * Extract TOC headings from HTML content
 */
function extractTOC($html) {
    $toc = [];
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);
    $headings = $xpath->query('//h2 | //h3 | //h4');
    $counters = ['h2' => 0, 'h3' => 0, 'h4' => 0];
    foreach ($headings as $heading) {
        $tag  = strtolower($heading->tagName);
        $text = trim($heading->textContent);
        if (!$text) continue;
        $id = 'toc-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
        $toc[] = [
            'tag'   => $tag,
            'text'  => $text,
            'id'    => $id,
            'level' => (int) substr($tag, 1)
        ];
    }
    return $toc;
}

/**
 * Inject IDs into headings in HTML content for TOC anchors
 */
function injectHeadingIds($html) {
    $html = preg_replace_callback('/<(h[234])([^>]*)>(.*?)<\/\1>/si', function($m) {
        $tag   = $m[1];
        $attrs = $m[2];
        $text  = $m[3];
        $plain = strip_tags($text);
        $id    = 'toc-' . preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($plain)));
        // Replace or add id attribute
        if (strpos($attrs, 'id=') !== false) {
            $attrs = preg_replace('/id="[^"]*"/', 'id="' . $id . '"', $attrs);
        } else {
            $attrs .= ' id="' . $id . '"';
        }
        return "<{$tag}{$attrs}>{$text}</{$tag}>";
    }, $html);
    return $html;
}
