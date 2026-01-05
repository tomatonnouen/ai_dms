<?php
/**
 * 資料自動仕分けシステム - 共通関数
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// BASE_PATHが未定義の場合はデフォルト値を設定
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/ai_dms/web');
}

/**
 * ログイン確認
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * ログイン必須チェック
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/auth/login.php');
        exit;
    }
}

/**
 * JSONレスポンスを返す
 * @param bool $success
 * @param mixed $data
 * @param string $message
 */
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * エラーレスポンスを返す
 * @param string $message
 */
function jsonError($message) {
    jsonResponse(false, null, $message);
}

/**
 * 成功レスポンスを返す
 * @param mixed $data
 * @param string $message
 */
function jsonSuccess($data = null, $message = '') {
    jsonResponse(true, $data, $message);
}

/**
 * ファイル拡張子を取得
 * @param string $filename
 * @return string
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * ファイル拡張子のチェック
 * @param string $filename
 * @return bool
 */
function isAllowedExtension($filename) {
    $ext = getFileExtension($filename);
    return in_array($ext, ALLOWED_EXTENSIONS);
}

/**
 * ファイルサイズのチェック
 * @param int $size
 * @return bool
 */
function isAllowedFileSize($size) {
    return $size <= MAX_FILE_SIZE;
}

/**
 * 安全なファイル名を生成
 * @param string $filename
 * @return string
 */
function sanitizeFilename($filename) {
    // ファイル名から拡張子を分離
    $ext = getFileExtension($filename);
    $name = pathinfo($filename, PATHINFO_FILENAME);

    // 安全な文字のみ残す（英数字、アンダースコア、ハイフン）
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);

    // 重複を避けるためタイムスタンプを追加
    $timestamp = date('YmdHis');

    return $name . '_' . $timestamp . '.' . $ext;
}

/**
 * ユニークなファイル名を生成
 * @param string $suggestedName 提案されたファイル名（拡張子なし）
 * @param string $ext 拡張子
 * @return string
 */
function generateUniqueFilename($suggestedName, $ext = 'pdf') {
    // 安全な文字のみ残す
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $suggestedName);

    // 年ディレクトリ
    $year = date('Y');
    $yearDir = UPLOAD_DIR . $year . '/';

    // ディレクトリが存在しない場合は作成
    if (!is_dir($yearDir)) {
        mkdir($yearDir, 0755, true);
    }

    // ファイル名の重複チェック
    $filename = $safeName . '.' . $ext;
    $filePath = $yearDir . $filename;
    $counter = 1;

    while (file_exists($filePath)) {
        $filename = $safeName . '_' . $counter . '.' . $ext;
        $filePath = $yearDir . $filename;
        $counter++;
    }

    return $year . '/' . $filename;
}

/**
 * PDFのページ数を取得（簡易版）
 * @param string $filepath
 * @return int|null
 */
function getPdfPageCount($filepath) {
    try {
        $content = file_get_contents($filepath);
        if ($content === false) {
            return null;
        }

        // /Count エントリを検索
        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            return (int)$matches[1];
        }

        // /Page キーワードをカウント（代替方法）
        $pageCount = preg_match_all('/\/Page\W/', $content, $matches);
        return $pageCount > 0 ? $pageCount : null;
    } catch (Exception $e) {
        error_log('Failed to get PDF page count: ' . $e->getMessage());
        return null;
    }
}

/**
 * Ntfy通知を送信（自サーバー・Basic認証付き）
 * @param string $title タイトル
 * @param string $message メッセージ本文
 */
function notify($title, $message) {
    // POST先のURL (例: https://example.com/mytopic)
    $url = NTFY_BASE_URL . '/' . NTFY_TOPIC;

    // cURLの初期化
    $ch = curl_init($url);

    // Basic認証の設定
    curl_setopt($ch, CURLOPT_USERPWD, NTFY_USER . ':' . NTFY_PASS);

    // POSTリクエストにする
    curl_setopt($ch, CURLOPT_POST, true);

    // HTTPヘッダ設定: タイトルとクリックアクションを付加
    $headers = [
        'Title: ' . $title,
        'Click: ' . NTFY_CLICK_URL
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // POSTデータ (メッセージ本文)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);

    // SSL証明書の検証 (Let's Encrypt等の正規証明書ならtrue推奨)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // レスポンスを返り値として取得
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // 実行
    $response = curl_exec($ch);

    // エラーチェック (ログに記録)
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log('Ntfy notification failed: ' . $error_msg);
    }

    // cURLセッション終了
    curl_close($ch);
}

/**
 * HTMLエスケープ
 * @param string $str
 * @return string
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF トークン生成
 * @return string
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF トークン検証
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 日付フォーマット
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'Y年m月d日') {
    if (empty($date)) {
        return '';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * ファイルサイズを人間が読みやすい形式に変換
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
