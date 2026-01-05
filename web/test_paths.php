<?php
/**
 * パス解決テストファイル
 *
 * サーバー上でパスが正しく解決されているか確認するためのファイル
 * ブラウザで https://yourdomain.com/ai_dms/web/test_paths.php にアクセスして確認
 */

echo "<h1>パス解決テスト</h1>";

echo "<h2>基本情報</h2>";
echo "<pre>";
echo "__FILE__: " . __FILE__ . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__FILE__): " . dirname(__FILE__) . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
echo "</pre>";

echo "<h2>includesディレクトリ</h2>";
echo "<pre>";
$includesDir = __DIR__ . '/includes';
echo "includesディレクトリ: {$includesDir}\n";
echo "存在チェック: " . (is_dir($includesDir) ? "存在する" : "存在しない") . "\n";
echo "</pre>";

echo "<h2>config.php</h2>";
echo "<pre>";
$configPath = __DIR__ . '/includes/config.php';
echo "config.phpのパス: {$configPath}\n";
echo "存在チェック: " . (file_exists($configPath) ? "存在する" : "存在しない") . "\n";
echo "読み取り可能: " . (is_readable($configPath) ? "可" : "不可") . "\n";
echo "</pre>";

echo "<h2>config.sample.php</h2>";
echo "<pre>";
$configSamplePath = __DIR__ . '/includes/config.sample.php';
echo "config.sample.phpのパス: {$configSamplePath}\n";
echo "存在チェック: " . (file_exists($configSamplePath) ? "存在する" : "存在しない") . "\n";
echo "読み取り可能: " . (is_readable($configSamplePath) ? "可" : "不可") . "\n";
echo "</pre>";

echo "<h2>functions.php</h2>";
echo "<pre>";
$functionsPath = __DIR__ . '/includes/functions.php';
echo "functions.phpのパス: {$functionsPath}\n";
echo "存在チェック: " . (file_exists($functionsPath) ? "存在する" : "存在しない") . "\n";
echo "読み取り可能: " . (is_readable($functionsPath) ? "可" : "不可") . "\n";
echo "</pre>";

echo "<h2>config.phpの読み込みテスト</h2>";
echo "<pre>";
try {
    if (file_exists($configPath)) {
        require_once $configPath;
        echo "✓ config.phpの読み込み成功\n";

        // 定数の確認
        echo "\n定数チェック:\n";
        echo "BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : "未定義") . "\n";
        echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : "未定義") . "\n";
        echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : "未定義") . "\n";
        echo "GEMINI_API_KEY: " . (defined('GEMINI_API_KEY') ? (strlen(GEMINI_API_KEY) > 10 ? "設定済み" : "未設定") : "未定義") . "\n";
    } else {
        echo "✗ config.phpが見つかりません\n";
        echo "→ config.sample.phpをコピーしてconfig.phpを作成してください\n";
    }
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<h2>アクション</h2>";
echo "<p>config.phpが存在しない場合:</p>";
echo "<pre>cd " . __DIR__ . "/includes\ncp config.sample.php config.php\nchmod 644 config.php</pre>";

echo "<hr>";
echo "<p><strong>注意:</strong> このファイルは本番環境では削除してください。</p>";
