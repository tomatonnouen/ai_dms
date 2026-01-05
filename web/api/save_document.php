<?php
/**
 * API: ドキュメント保存
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

    // ファイルチェック
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonError('ファイルのアップロードに失敗しました');
    }

    $file = $_FILES['file'];
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $title = $_POST['title'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $documentDate = !empty($_POST['document_date']) ? $_POST['document_date'] : null;
    $suggestedFilename = $_POST['suggested_filename'] ?? '';

    // 拡張子チェック
    if (!isAllowedExtension($file['name'])) {
        jsonError('PDFファイルのみアップロード可能です');
    }

    // ファイルサイズチェック
    if (!isAllowedFileSize($file['size'])) {
        jsonError('ファイルサイズが大きすぎます（最大50MB）');
    }

    // ファイル名生成
    if (empty($suggestedFilename)) {
        $suggestedFilename = pathinfo($file['name'], PATHINFO_FILENAME);
    }
    $filePath = generateUniqueFilename($suggestedFilename, 'pdf');
    $fullPath = UPLOAD_DIR . $filePath;

    // ディレクトリ作成
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // ファイル移動
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        jsonError('ファイルの保存に失敗しました');
    }

    // ページ数取得
    $pageCount = getPdfPageCount($fullPath);

    // データベース登録
    $db = getDB();
    $stmt = $db->prepare('
        INSERT INTO documents (
            filename, original_filename, category_id, title, summary,
            document_date, upload_date, file_path, file_size, page_count, upload_source
        ) VALUES (
            ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?
        )
    ');

    $filename = basename($filePath);
    $stmt->execute([
        $filename,
        $file['name'],
        $categoryId,
        $title,
        $summary,
        $documentDate,
        $filePath,
        $file['size'],
        $pageCount,
        'web'
    ]);

    $documentId = $db->lastInsertId();

    // Ntfy通知
    $categoryName = 'なし';
    if ($categoryId) {
        $stmt = $db->prepare('SELECT name FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        if ($category) {
            $categoryName = $category['name'];
        }
    }

    sendNtfyNotification(
        '新規ドキュメント登録（Web）',
        sprintf(
            "カテゴリ: %s\nタイトル: %s\nページ数: %d",
            $categoryName,
            $title ?: '(タイトルなし)',
            $pageCount ?: 0
        ),
        'default',
        ['document', 'web']
    );

    jsonSuccess([
        'id' => $documentId,
        'filename' => $filename,
        'file_path' => $filePath
    ], 'ドキュメントが正常に保存されました');

} catch (Exception $e) {
    error_log('Save document error: ' . $e->getMessage());
    jsonError('ドキュメントの保存に失敗しました: ' . $e->getMessage());
}
