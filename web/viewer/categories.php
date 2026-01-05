<?php
/**
 * 資料自動仕分けシステム - カテゴリ管理
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ログイン必須
requireLogin();

$db = getDB();
$message = '';
$error = '';

// カテゴリ追加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'カテゴリ名を入力してください';
    } else {
        try {
            // 最大表示順序を取得
            $stmt = $db->query('SELECT MAX(display_order) as max_order FROM categories');
            $result = $stmt->fetch();
            $displayOrder = ($result['max_order'] ?? 0) + 1;

            $stmt = $db->prepare('INSERT INTO categories (name, display_order) VALUES (?, ?)');
            $stmt->execute([$name, $displayOrder]);

            $message = 'カテゴリを追加しました';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'そのカテゴリ名は既に存在します';
            } else {
                error_log('Add category error: ' . $e->getMessage());
                $error = 'カテゴリの追加に失敗しました';
            }
        }
    }
}

// カテゴリ削除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];

    try {
        // カテゴリに紐づくドキュメント数を確認
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM documents WHERE category_id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];

        if ($count > 0) {
            $error = "このカテゴリには {$count} 件のドキュメントが紐づいています。先にドキュメントのカテゴリを変更してください";
        } else {
            $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);

            $message = 'カテゴリを削除しました';
        }
    } catch (Exception $e) {
        error_log('Delete category error: ' . $e->getMessage());
        $error = 'カテゴリの削除に失敗しました';
    }
}

// カテゴリ一覧取得
$stmt = $db->query('
    SELECT c.id, c.name, c.display_order,
           COUNT(d.id) as document_count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id
    ORDER BY c.display_order ASC
');
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カテゴリ管理 - 資料自動仕分けシステム</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .category-container {
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

        .add-category {
            background: #f7fafc;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .add-category h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .add-form {
            display: flex;
            gap: 10px;
        }

        .add-form input {
            flex: 1;
        }

        .category-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .category-table th,
        .category-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .category-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #333;
        }

        .category-table tr:hover {
            background: #f7fafc;
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
        }

        @media (max-width: 768px) {
            .category-table {
                font-size: 14px;
            }

            .category-table th,
            .category-table td {
                padding: 10px;
            }

            .add-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🏷️ カテゴリ管理</h1>
            <div class="header-actions">
                <a href="<?= BASE_PATH ?>/viewer/" class="btn btn-secondary">閲覧画面へ</a>
            </div>
        </header>

        <main class="main category-container">
            <?php if ($message): ?>
                <div class="message success"><?= h($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?= h($error) ?></div>
            <?php endif; ?>

            <!-- カテゴリ追加 -->
            <div class="add-category">
                <h2>新しいカテゴリを追加</h2>
                <form method="POST" action="" class="add-form">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" placeholder="カテゴリ名" required>
                    <button type="submit" class="btn btn-primary">追加</button>
                </form>
            </div>

            <!-- カテゴリ一覧 -->
            <table class="category-table">
                <thead>
                    <tr>
                        <th>カテゴリ名</th>
                        <th>ドキュメント数</th>
                        <th>表示順序</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999;">
                                カテゴリがありません
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?= h($cat['name']) ?></strong></td>
                                <td><?= h($cat['document_count']) ?>件</td>
                                <td><?= h($cat['display_order']) ?></td>
                                <td>
                                    <?php if ($cat['document_count'] == 0): ?>
                                        <form method="POST" action="" style="display: inline;"
                                              onsubmit="return confirm('本当に削除しますか?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= h($cat['id']) ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 14px;">
                                                削除
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 14px;">
                                            (ドキュメントあり)
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
