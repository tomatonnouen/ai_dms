<?php
/**
 * 資料自動仕分けシステム - 設定ファイル（サンプル）
 *
 * このファイルをコピーして config.php にリネームし、
 * 環境に合わせて設定を変更してください。
 */

// データベース設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'document_system');
define('DB_USER', 'your_user');           // ← データベースユーザー名を入力
define('DB_PASS', 'your_password');       // ← データベースパスワードを入力
define('DB_CHARSET', 'utf8mb4');

// ファイル保存設定
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['pdf']);

// Gemini API設定
define('GEMINI_API_KEY', 'your-gemini-api-key');  // ← Gemini APIキーを入力
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent');
define('PDF_PAGE_LIMIT', 10);

// Ntfy設定（自サーバー・Basic認証付き）
define('NTFY_BASE_URL', 'https://tomaton-nouen.duckdns.org');  // NtfyサーバーのベースURL
define('NTFY_TOPIC', 'tasklist');                              // トピック名
define('NTFY_USER', 'tomaton');                                // Basic認証のユーザー名
define('NTFY_PASS', 'tomaton45381');                           // Basic認証のパスワード
define('NTFY_CLICK_URL', 'https://tomaton-nouen.com/task/tasklist.php'); // クリック時の遷移先URL

// セッション設定
define('SESSION_LIFETIME', 3600 * 24); // 24時間

// タイムゾーン
date_default_timezone_set('Asia/Tokyo');

// エラー表示設定（本番環境では以下をコメントアウト）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
