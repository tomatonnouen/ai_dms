<?php
/**
 * 資料自動仕分けシステム - 登録アプリ
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ログイン必須
requireLogin();

// カテゴリ一覧取得
$db = getDB();
$stmt = $db->query('SELECT id, name FROM categories ORDER BY display_order ASC');
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料登録 - 資料自動仕分けシステム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📄 資料登録</h1>
            <div class="header-actions">
                <a href="<?= BASE_PATH ?>/viewer/" class="btn btn-secondary">閲覧画面へ</a>
                <a href="<?= BASE_PATH ?>/auth/logout.php" class="btn btn-secondary">ログアウト</a>
            </div>
        </header>

        <main class="main">
            <!-- アップロードエリア -->
            <div id="upload-area" class="upload-area">
                <div class="upload-content">
                    <div class="upload-icon">📁</div>
                    <p class="upload-text">PDFファイルをドラッグ＆ドロップ</p>
                    <p class="upload-text-sub">または</p>
                    <label for="file-input" class="btn btn-primary">ファイルを選択</label>
                    <input type="file" id="file-input" accept=".pdf" style="display: none;">
                    <p class="upload-limit">最大50MB、10ページまで</p>
                </div>
            </div>

            <!-- 解析中 -->
            <div id="analyzing" class="analyzing" style="display: none;">
                <div class="spinner"></div>
                <p>AIで資料を解析中...</p>
            </div>

            <!-- 確認・編集エリア -->
            <div id="preview-area" class="preview-area" style="display: none;">
                <h2>解析結果を確認・編集</h2>

                <form id="document-form">
                    <div class="form-group">
                        <label for="category">カテゴリ <span class="required">*</span></label>
                        <select id="category" name="category_id">
                            <option value="">選択してください</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= h($cat['id']) ?>"><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="confidence" id="confidence"></div>
                    </div>

                    <div class="form-group">
                        <label for="title">タイトル <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="summary">概要</label>
                        <textarea id="summary" name="summary" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="document_date">文書日付</label>
                        <input type="date" id="document_date" name="document_date">
                    </div>

                    <div class="form-group">
                        <label for="suggested_filename">ファイル名</label>
                        <input type="text" id="suggested_filename" name="suggested_filename" placeholder="例: annual_meeting_2025">
                        <small>タイトルから自動生成されます。日本語も使用可能です。</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="cancel-btn" class="btn btn-secondary">キャンセル</button>
                        <button type="submit" id="save-btn" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>

            <!-- 完了メッセージ -->
            <div id="success-message" class="success-message" style="display: none;">
                <div class="success-icon">✅</div>
                <h2>登録完了！</h2>
                <p>資料が正常に登録されました</p>
                <div class="form-actions">
                    <button id="another-btn" class="btn btn-primary">続けて登録</button>
                    <a href="<?= BASE_PATH ?>/viewer/" class="btn btn-secondary">閲覧画面へ</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        const BASE_PATH = '<?= BASE_PATH ?>';
    </script>
    <script src="script.js"></script>
</body>
</html>
