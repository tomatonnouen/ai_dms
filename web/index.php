<?php
/**
 * 資料自動仕分けシステム - トップページ
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// ログイン必須
requireLogin();

// 統計情報取得
$db = getDB();

// 総ドキュメント数
$stmt = $db->query('SELECT COUNT(*) as total FROM documents');
$totalDocuments = $stmt->fetch()['total'];

// カテゴリ数
$stmt = $db->query('SELECT COUNT(*) as total FROM categories');
$totalCategories = $stmt->fetch()['total'];

// 最近のアップロード（7日以内）
$stmt = $db->query('SELECT COUNT(*) as total FROM documents WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$recentUploads = $stmt->fetch()['total'];

// カテゴリ別ドキュメント数
$stmt = $db->query('
    SELECT c.name, COUNT(d.id) as count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id
    ORDER BY c.display_order ASC
    LIMIT 6
');
$categoryStats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料自動仕分けシステム</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            color: #666;
        }

        .header .user-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info .username {
            font-size: 14px;
            color: #666;
        }

        .user-info .logout-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .user-info .logout-link:hover {
            text-decoration: underline;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .action-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .action-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .action-description {
            font-size: 14px;
            color: #666;
        }

        .category-stats {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .category-stats h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
        }

        .category-list {
            display: grid;
            gap: 15px;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .category-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .category-count {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 24px;
            }

            .header .user-info {
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 28px;
            }

            .action-icon {
                font-size: 48px;
            }

            .action-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 資料自動仕分けシステム</h1>
            <p>PDFファイルを自動で分類・管理するシステム</p>
            <div class="user-info">
                <div class="username">👤 <?= h($_SESSION['username']) ?></div>
                <a href="/web/auth/logout.php" class="logout-link">ログアウト</a>
            </div>
        </div>

        <!-- 統計情報 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-label">総ドキュメント数</div>
                <div class="stat-value"><?= number_format($totalDocuments) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏷️</div>
                <div class="stat-label">カテゴリ数</div>
                <div class="stat-value"><?= number_format($totalCategories) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-label">最近のアップロード（7日以内）</div>
                <div class="stat-value"><?= number_format($recentUploads) ?></div>
            </div>
        </div>

        <!-- アクション -->
        <div class="actions-grid">
            <a href="/web/register/" class="action-card">
                <div class="action-icon">📤</div>
                <div class="action-title">資料登録</div>
                <div class="action-description">PDFファイルをアップロードして自動分類</div>
            </a>

            <a href="/web/viewer/" class="action-card">
                <div class="action-icon">🔍</div>
                <div class="action-title">資料閲覧</div>
                <div class="action-description">登録された資料を検索・閲覧</div>
            </a>

            <a href="/web/viewer/categories.php" class="action-card">
                <div class="action-icon">⚙️</div>
                <div class="action-title">カテゴリ管理</div>
                <div class="action-description">カテゴリの追加・編集・削除</div>
            </a>
        </div>

        <!-- カテゴリ別統計 -->
        <div class="category-stats">
            <h2>カテゴリ別ドキュメント数</h2>
            <div class="category-list">
                <?php if (empty($categoryStats)): ?>
                    <div style="text-align: center; color: #999; padding: 20px;">
                        カテゴリがありません
                    </div>
                <?php else: ?>
                    <?php foreach ($categoryStats as $cat): ?>
                        <div class="category-item">
                            <div class="category-name"><?= h($cat['name']) ?></div>
                            <div class="category-count"><?= number_format($cat['count']) ?>件</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
