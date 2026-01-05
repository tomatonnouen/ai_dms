<?php
/**
 * API: ドキュメント更新
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 認証チェック
    if (!isLoggedIn()) {
        jsonError('認証が必要です');
    }

    // JSONペイロード取得
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id'])) {
        jsonError('ドキュメントIDが必要です');
    }

    $id = (int)$input['id'];
    $categoryId = !empty($input['category_id']) ? (int)$input['category_id'] : null;
    $title = $input['title'] ?? '';
    $summary = $input['summary'] ?? '';
    $documentDate = !empty($input['document_date']) ? $input['document_date'] : null;

    // データベース更新
    $db = getDB();
    $stmt = $db->prepare('
        UPDATE documents
        SET category_id = ?, title = ?, summary = ?, document_date = ?
        WHERE id = ?
    ');

    $stmt->execute([
        $categoryId,
        $title,
        $summary,
        $documentDate,
        $id
    ]);

    if ($stmt->rowCount() === 0) {
        jsonError('ドキュメントが見つからないか、更新する内容がありません');
    }

    jsonSuccess(null, 'ドキュメントが更新されました');

} catch (Exception $e) {
    error_log('Update document error: ' . $e->getMessage());
    jsonError('ドキュメントの更新に失敗しました: ' . $e->getMessage());
}
