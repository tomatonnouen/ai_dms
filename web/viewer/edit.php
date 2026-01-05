<?php
/**
 * 資料自動仕分けシステム - 編集画面
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ログイン必須
requireLogin();

// ドキュメントID取得
$id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: ' . BASE_PATH . '/viewer/');
    exit;
}

// ドキュメント情報取得
$db = getDB();
$stmt = $db->prepare('
    SELECT d.*, c.name as category_name
    FROM documents d
    LEFT JOIN categories c ON d.category_id = c.id
    WHERE d.id = ?
');
$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    header('Location: ' . BASE_PATH . '/viewer/');
    exit;
}

// カテゴリ一覧取得
$stmt = $db->query('SELECT id, name FROM categories ORDER BY display_order ASC');
$categories = $stmt->fetchAll();

// 更新処理
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $title = $_POST['title'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $documentDate = !empty($_POST['document_date']) ? $_POST['document_date'] : null;

    try {
        $stmt = $db->prepare('
            UPDATE documents
            SET category_id = ?, title = ?, summary = ?, document_date = ?
            WHERE id = ?
        ');
        $stmt->execute([$categoryId, $title, $summary, $documentDate, $id]);

        $message = '更新しました';

        // 再読み込み
        $stmt = $db->prepare('
            SELECT d.*, c.name as category_name
            FROM documents d
            LEFT JOIN categories c ON d.category_id = c.id
            WHERE d.id = ?
        ');
        $stmt->execute([$id]);
        $document = $stmt->fetch();
    } catch (Exception $e) {
        error_log('Update error: ' . $e->getMessage());
        $error = '更新に失敗しました';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料編集 - 資料自動仕分けシステム</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .pdf-preview {
            margin-bottom: 30px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .pdf-preview iframe {
            width: 100%;
            height: 400px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📝 資料編集</h1>
            <div class="header-actions">
                <a href="<?= BASE_PATH ?>/viewer/" class="btn btn-secondary">閲覧画面へ</a>
            </div>
        </header>

        <main class="main edit-container">
            <?php if ($message): ?>
                <div class="message success"><?= h($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?= h($error) ?></div>
            <?php endif; ?>

            <!-- PDFプレビュー -->
            <div class="pdf-preview">
                <iframe src="<?= BASE_PATH ?>/viewer/view_pdf.php?id=<?= $id ?>"></iframe>
            </div>

            <!-- 編集フォーム -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="category">カテゴリ</label>
                    <select id="category" name="category_id">
                        <option value="">未分類</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat['id']) ?>"
                                <?= $document['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">タイトル</label>
                    <input type="text" id="title" name="title"
                        value="<?= h($document['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="summary">概要</label>
                    <textarea id="summary" name="summary" rows="4"><?= h($document['summary']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="document_date">文書日付</label>
                    <input type="date" id="document_date" name="document_date"
                        value="<?= h($document['document_date']) ?>">
                </div>

                <div class="form-group">
                    <label>ファイル情報</label>
                    <div style="font-size: 14px; color: #666;">
                        <p>ファイル名: <?= h($document['filename']) ?></p>
                        <p>ファイルサイズ: <?= formatFileSize($document['file_size']) ?></p>
                        <p>ページ数: <?= h($document['page_count']) ?>ページ</p>
                        <p>アップロード日時: <?= formatDate($document['upload_date'], 'Y年m月d日 H:i') ?></p>
                        <p>アップロード元: <?= $document['upload_source'] === 'scan' ? 'スキャン' : 'Web' ?></p>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= BASE_PATH ?>/viewer/" class="btn btn-secondary">キャンセル</a>
                    <button type="submit" class="btn btn-primary">更新</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
