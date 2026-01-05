<?php
/**
 * 資料自動仕分けシステム - 閲覧アプリ
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
    <title>資料閲覧 - 資料自動仕分けシステム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📚 資料閲覧</h1>
            <div class="header-actions">
                <a href="<?= BASE_PATH ?>/register/" class="btn btn-primary">資料登録</a>
                <a href="<?= BASE_PATH ?>/viewer/categories.php" class="btn btn-secondary">カテゴリ管理</a>
                <a href="<?= BASE_PATH ?>/auth/logout.php" class="btn btn-secondary">ログアウト</a>
            </div>
        </header>

        <main class="main">
            <!-- 検索・フィルターエリア -->
            <div class="search-area">
                <div class="search-row">
                    <div class="search-group">
                        <label for="category-filter">カテゴリ</label>
                        <select id="category-filter">
                            <option value="">全て</option>
                            <option value="0">未分類</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= h($cat['id']) ?>"><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-group search-group-wide">
                        <label for="keyword-search">キーワード検索</label>
                        <div class="search-input-wrapper">
                            <input type="text" id="keyword-search" placeholder="タイトル、概要、ファイル名で検索">
                            <button id="search-btn" class="btn-search">🔍 検索</button>
                        </div>
                    </div>
                </div>

                <div class="search-row">
                    <div class="search-group">
                        <label for="date-from">文書日付（開始）</label>
                        <input type="date" id="date-from">
                    </div>

                    <div class="search-group">
                        <label for="date-to">文書日付（終了）</label>
                        <input type="date" id="date-to">
                    </div>

                    <div class="search-group">
                        <button id="reset-btn" class="btn btn-secondary">リセット</button>
                    </div>
                </div>
            </div>

            <!-- 統計情報 -->
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-label">総件数</div>
                    <div class="stat-value" id="total-count">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">表示件数</div>
                    <div class="stat-value" id="display-count">-</div>
                </div>
            </div>

            <!-- ドキュメント一覧 -->
            <div id="document-list" class="document-list">
                <div class="loading">読み込み中...</div>
            </div>

            <!-- ページネーション -->
            <div id="pagination" class="pagination" style="display: none;">
                <button id="prev-btn" class="btn btn-secondary">← 前へ</button>
                <span id="page-info"></span>
                <button id="next-btn" class="btn btn-secondary">次へ →</button>
            </div>
        </main>
    </div>

    <script>
        const BASE_PATH = '<?= BASE_PATH ?>';
    </script>
    <script src="script.js"></script>
</body>
</html>
