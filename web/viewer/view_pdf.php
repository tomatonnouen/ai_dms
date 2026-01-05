<?php
/**
 * PDF表示スクリプト
 * ブラウザでPDFを直接開くために適切なヘッダーを送信
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ログイン必須
requireLogin();

// ドキュメントID取得
$id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    http_response_code(400);
    die('Invalid document ID');
}

// ドキュメント情報取得
$db = getDB();
$stmt = $db->prepare('SELECT file_path, original_filename FROM documents WHERE id = ?');
$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    http_response_code(404);
    die('Document not found');
}

// ファイルパス構築
$filePath = UPLOAD_DIR . $document['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// PDFとして送信
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $document['original_filename'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=3600');
header('Accept-Ranges: bytes');

// ファイルを出力
readfile($filePath);
exit;
