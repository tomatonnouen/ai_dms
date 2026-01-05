# 資料自動仕分けシステム - WEBアプリケーション

紙やPDFで配布された各種資料をLLMで自動仕分けし、レンタルサーバー上にPDFで保存・表示するシステムのWEBアプリケーション部分です。

## 機能

### 登録アプリ（register/）
- PDFファイルのドラッグ&ドロップアップロード
- Gemini Flash 2.0による自動解析
  - カテゴリ自動判定
  - タイトル自動抽出
  - 概要自動生成
  - 文書日付自動抽出
  - ファイル名自動提案
- 解析結果の確認・修正
- データベースへの登録

### 閲覧アプリ（viewer/）
- ドキュメント一覧表示（カード形式）
- カテゴリ別フィルタリング
- キーワード検索（タイトル、概要、ファイル名）
- 日付範囲検索
- PDFビューア（モーダル表示）
- ドキュメント編集（メタデータ）
- ドキュメント削除
- カテゴリ管理（追加・削除）

### レスポンシブデザイン
- スマートフォン対応
- タブレット対応
- PC対応

## ディレクトリ構造

```
web/
├── uploads/                     # PDFファイル保存
│   ├── 2025/
│   │   └── uncategorized/
│   └── 2026/
│       └── uncategorized/
├── register/                    # 登録アプリ
│   ├── index.php
│   ├── script.js
│   └── style.css
├── viewer/                      # 閲覧アプリ
│   ├── index.php
│   ├── edit.php
│   ├── categories.php
│   ├── script.js
│   └── style.css
├── api/                         # APIエンドポイント
│   ├── upload_document.php      # ドキュメントアップロード（Ubuntu→Web）
│   ├── get_categories.php       # カテゴリ一覧取得
│   ├── analyze_pdf.php          # PDF解析（Web用）
│   ├── save_document.php        # ドキュメント保存
│   ├── update_document.php      # ドキュメント更新
│   ├── delete_document.php      # ドキュメント削除
│   └── search_documents.php     # ドキュメント検索
├── auth/                        # 認証
│   ├── login.php
│   └── logout.php
├── includes/                    # 共通ファイル
│   ├── config.php               # 設定
│   ├── db.php                   # DB接続
│   └── functions.php            # 共通関数
└── index.php                    # トップページ

database/
└── schema.sql                   # データベーススキーマ
```

## セットアップ手順

### 1. データベース設定

データベースを作成し、スキーマを適用します。

```bash
mysql -u root -p < database/schema.sql
```

または、MySQLクライアントで直接実行：

```sql
CREATE DATABASE document_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE document_system;
-- database/schema.sql の内容を実行
```

### 2. 設定ファイルの編集

`web/includes/config.php` を編集して、環境に合わせて設定を変更します。

```php
// データベース設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'document_system');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');

// Gemini API設定
define('GEMINI_API_KEY', 'your-gemini-api-key');

// Ntfy設定（オプション）
define('NTFY_TOPIC', 'your-ntfy-topic');
```

### 3. ファイルアップロード

WEBサーバーに `web/` ディレクトリ全体をアップロードします。

```bash
# レンタルサーバーへのアップロード例（SFTP/FTP使用）
# または、ローカル開発環境で使用する場合は、そのままで OK
```

### 4. ディレクトリパーミッション設定

アップロードディレクトリに書き込み権限を付与します。

```bash
chmod 755 web/uploads
chmod 755 web/uploads/2025
chmod 755 web/uploads/2025/uncategorized
chmod 755 web/uploads/2026
chmod 755 web/uploads/2026/uncategorized
```

### 5. 初期ユーザーでログイン

ブラウザで `https://yourdomain.com/web/auth/login.php` にアクセスし、以下の初期ユーザーでログインします。

- ユーザー名: `admin`
- パスワード: `password`

**セキュリティ上、初回ログイン後は必ずパスワードを変更してください。**

## 使用方法

### 資料登録

1. トップページから「資料登録」をクリック
2. PDFファイルをドラッグ&ドロップ、またはファイルを選択
3. AIによる自動解析を待つ（数秒〜数十秒）
4. 解析結果を確認・必要に応じて修正
5. 「保存」ボタンをクリック

### 資料閲覧

1. トップページから「資料閲覧」をクリック
2. カテゴリ、キーワード、日付で検索・フィルタリング
3. ドキュメントカードをクリックしてPDFを表示
4. 編集・削除も可能

### カテゴリ管理

1. トップページから「カテゴリ管理」をクリック
2. 新しいカテゴリを追加
3. 不要なカテゴリを削除（ドキュメントが紐づいていない場合のみ）

## API仕様

### カテゴリ一覧取得

```
GET /web/api/get_categories.php
```

### PDF解析

```
POST /web/api/analyze_pdf.php
Content-Type: application/json

{
  "pdf_base64": "JVBERi0xLjQK...",
  "filename": "example.pdf"
}
```

### ドキュメント保存

```
POST /web/api/save_document.php
Content-Type: multipart/form-data

file: (PDFファイル)
category_id: 1
title: "タイトル"
summary: "概要"
document_date: "2025-01-15"
suggested_filename: "example_file"
```

### ドキュメント検索

```
GET /web/api/search_documents.php?category_id=1&keyword=検索語&limit=20&offset=0
```

### ドキュメント更新

```
POST /web/api/update_document.php
Content-Type: application/json

{
  "id": 123,
  "category_id": 2,
  "title": "更新後タイトル",
  "summary": "更新後概要",
  "document_date": "2025-01-20"
}
```

### ドキュメント削除

```
POST /web/api/delete_document.php
Content-Type: application/json

{
  "id": 123
}
```

## 技術スタック

- **フロントエンド**: HTML5, CSS3, JavaScript (ES6+)
- **バックエンド**: PHP 7.4+
- **データベース**: MySQL/MariaDB
- **AI**: Google Gemini 2.0 Flash
- **認証**: セッションベース（bcrypt）

## レスポンシブデザイン

以下のデバイスで最適化されています：

- **スマートフォン**: 480px以下
- **タブレット**: 768px以下
- **PC**: 768px以上

## セキュリティ

- パスワードはbcryptでハッシュ化
- SQLインジェクション対策（プリペアドステートメント）
- XSS対策（HTMLエスケープ）
- ファイルアップロード検証（拡張子、サイズ）
- セッション管理

## トラブルシューティング

### PDFが表示されない

- ブラウザのPDFビューアが有効になっているか確認
- ファイルパスが正しいか確認
- アップロードディレクトリのパーミッションを確認

### Gemini API エラー

- APIキーが正しく設定されているか確認
- インターネット接続を確認
- APIの利用制限に達していないか確認

### データベース接続エラー

- `web/includes/config.php` の設定を確認
- データベースが作成されているか確認
- ユーザー権限を確認

## ライセンス

このプロジェクトは内部使用を目的としています。

## サポート

問題が発生した場合は、システム管理者に連絡してください。
