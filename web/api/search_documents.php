<?php
/**
 * API: ドキュメント検索
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // パラメータ取得
    $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $keyword = $_GET['keyword'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = !empty($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // SQL構築
    $where = [];
    $params = [];

    if ($categoryId !== null) {
        if ($categoryId === 0) {
            // 未分類
            $where[] = 'category_id IS NULL';
        } else {
            $where[] = 'category_id = ?';
            $params[] = $categoryId;
        }
    }

    if (!empty($keyword)) {
        $where[] = '(title LIKE ? OR summary LIKE ? OR original_filename LIKE ?)';
        $keywordParam = '%' . $keyword . '%';
        $params[] = $keywordParam;
        $params[] = $keywordParam;
        $params[] = $keywordParam;
    }

    if (!empty($dateFrom)) {
        $where[] = 'document_date >= ?';
        $params[] = $dateFrom;
    }

    if (!empty($dateTo)) {
        $where[] = 'document_date <= ?';
        $params[] = $dateTo;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // 総件数取得
    $db = getDB();
    $countSql = "SELECT COUNT(*) as total FROM documents {$whereClause}";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    // ドキュメント取得
    $sql = "
        SELECT
            d.id, d.filename, d.original_filename, d.title, d.summary,
            d.document_date, d.upload_date, d.file_path, d.file_size, d.page_count,
            d.upload_source,
            c.name as category_name
        FROM documents d
        LEFT JOIN categories c ON d.category_id = c.id
        {$whereClause}
        ORDER BY d.upload_date DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll();

    jsonSuccess([
        'total' => $total,
        'documents' => $documents,
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (Exception $e) {
    error_log('Search documents error: ' . $e->getMessage());
    jsonError('ドキュメントの検索に失敗しました: ' . $e->getMessage());
}
