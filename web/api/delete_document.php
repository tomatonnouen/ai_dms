<?php
/**
 * API: ドキュメント削除
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

    // ドキュメント情報取得
    $db = getDB();
    $stmt = $db->prepare('SELECT file_path FROM documents WHERE id = ?');
    $stmt->execute([$id]);
    $document = $stmt->fetch();

    if (!$document) {
        jsonError('ドキュメントが見つかりません');
    }

    // ファイル削除
    $fullPath = UPLOAD_DIR . $document['file_path'];
    if (file_exists($fullPath)) {
        if (!unlink($fullPath)) {
            error_log('Failed to delete file: ' . $fullPath);
        }
    }

    // データベースから削除
    $stmt = $db->prepare('DELETE FROM documents WHERE id = ?');
    $stmt->execute([$id]);

    jsonSuccess(null, 'ドキュメントが削除されました');

} catch (Exception $e) {
    error_log('Delete document error: ' . $e->getMessage());
    jsonError('ドキュメントの削除に失敗しました: ' . $e->getMessage());
}
