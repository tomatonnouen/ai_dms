# レンタルサーバー設定手順

このファイルは、ロリポップなどのレンタルサーバーにアップロードする際の設定手順です。

## 1. config.phpの作成

サーバー上で `web/includes/config.php` を作成してください。

```bash
# SSHまたはFTPでサーバーにアクセスし、以下のコマンドを実行
cd /home/users/xxx/web/ai_dms/web/includes/
cp config.sample.php config.php
```

## 2. config.phpの編集

`config.php` を編集して、以下の設定を環境に合わせて変更してください：

### 必須設定

```php
// アプリケーション設定
define('BASE_PATH', '/ai_dms/web');  // ← サーバー上の実際のパスに変更

// データベース設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'document_system');
define('DB_USER', 'your_user');      // ← データベースユーザー名
define('DB_PASS', 'your_password');  // ← データベースパスワード

// Gemini API設定
define('GEMINI_API_KEY', 'your-gemini-api-key');  // ← Gemini APIキー
```

### BASE_PATHの設定例

- `/ai_dms/web/` に配置した場合: `'/ai_dms/web'`
- `/web/` 直下に配置した場合: `'/web'`
- ドキュメントルート直下に配置した場合: `''` (空文字列)

## 3. ディレクトリパーミッション設定

```bash
chmod 755 web/uploads
chmod 755 web/uploads/2025
chmod 755 web/uploads/2026
```

## 4. データベース設定

MySQLデータベースを作成し、スキーマを適用してください：

```bash
mysql -u your_user -p your_database < database/schema.sql
```

## 5. 動作確認

ブラウザで以下のURLにアクセスして動作確認：

```
https://yourdomain.com/ai_dms/web/auth/login.php
```

初期ログイン情報：
- ユーザー名: `admin`
- パスワード: `password`

## トラブルシューティング

### 404エラーが出る場合

`config.php` の `BASE_PATH` が正しく設定されているか確認してください。

### データベース接続エラーが出る場合

`config.php` のデータベース設定を確認してください。

### エラーログの確認

```bash
tail -f /home/users/xxx/web/ai_dms/web/logs/error.log
```
