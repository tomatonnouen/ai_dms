<?php
/**
 * API: カテゴリ一覧取得
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();
    $stmt = $db->query('SELECT id, name, display_order FROM categories ORDER BY display_order ASC, name ASC');
    $categories = $stmt->fetchAll();

    jsonSuccess(['categories' => $categories]);
} catch (Exception $e) {
    error_log('Get categories error: ' . $e->getMessage());
    jsonError('カテゴリの取得に失敗しました');
}
